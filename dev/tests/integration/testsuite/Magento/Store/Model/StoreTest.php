<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Store\Model;

class StoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $_modelParams;

    /**
     * @var \Magento\Store\Model\Store|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_model;

    protected function setUp()
    {
        $this->_model = $this->_getStoreModel();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Store\Model\Store
     */
    protected function _getStoreModel()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->_modelParams = array(
            'context' => $objectManager->get('Magento\Framework\Model\Context'),
            'registry' => $objectManager->get('Magento\Framework\Registry'),
            'resource' => $objectManager->get('Magento\Store\Model\Resource\Store'),
            'coreFileStorageDatabase' => $objectManager->get('Magento\Core\Helper\File\Storage\Database'),
            'configCacheType' => $objectManager->get('Magento\Framework\App\Cache\Type\Config'),
            'url' => $objectManager->get('Magento\Framework\Url'),
            'request' => $objectManager->get('Magento\Framework\App\RequestInterface'),
            'configDataResource' => $objectManager->get('Magento\Core\Model\Resource\Config\Data'),
            'filesystem' => $objectManager->get('Magento\Framework\App\Filesystem'),
            'config' => $objectManager->get('Magento\Framework\App\Config\ReinitableConfigInterface'),
            'storeManager' => $objectManager->get('Magento\Store\Model\StoreManager'),
            'sidResolver' => $objectManager->get('Magento\Framework\Session\SidResolverInterface'),
            'cookie' => $objectManager->get('Magento\Framework\Stdlib\Cookie'),
            'httpContext' => $objectManager->get('Magento\Framework\App\Http\Context'),
            'session' => $objectManager->get('Magento\Framework\Session\SessionManagerInterface'),
            'currencyFactory' => $objectManager->get('Magento\Directory\Model\CurrencyFactory'),
            'currencyInstalled' => 'system/currency/installed',
        );

        return $this->getMock('Magento\Store\Model\Store', array('getUrl'), $this->_modelParams);
    }

    protected function tearDown()
    {
        $this->_model = null;
    }

    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad($loadId, $expectedId)
    {
        $this->_model->load($loadId);
        $this->assertEquals($expectedId, $this->_model->getId());
    }

    /**
     * @return array
     */
    public function loadDataProvider()
    {
        return array(array(1, 1), array('default', 1), array('nostore', null));
    }

    public function testSetGetWebsite()
    {
        $this->assertFalse($this->_model->getWebsite());
        $website = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            'Magento\Store\Model\StoreManagerInterface'
        )->getWebsite();
        $this->_model->setWebsite($website);
        $actualResult = $this->_model->getWebsite();
        $this->assertSame($website, $actualResult);
    }

    public function testSetGetGroup()
    {
        $this->assertFalse($this->_model->getGroup());
        $storeGroup = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            'Magento\Store\Model\StoreManager'
        )->getGroup();
        $this->_model->setGroup($storeGroup);
        $actualResult = $this->_model->getGroup();
        $this->assertSame($storeGroup, $actualResult);
    }

    /**
     * Isolation is enabled, as we pollute config with rewrite values
     *
     * @param string $type
     * @param bool $useRewrites
     * @param bool $useStoreCode
     * @param string $expected
     * @dataProvider getBaseUrlDataProvider
     * @magentoAppIsolation enabled
     */
    public function testGetBaseUrl($type, $useRewrites, $useStoreCode, $expected)
    {
        /* config operations require store to be loaded */
        $this->_model->load('default');
        \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            'Magento\Framework\App\Config\MutableScopeConfigInterface'
        )->setValue(
            \Magento\Store\Model\Store::XML_PATH_USE_REWRITES,
            $useRewrites,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            'Magento\Framework\App\Config\MutableScopeConfigInterface'
        )->setValue(
            \Magento\Store\Model\Store::XML_PATH_STORE_IN_URL,
            $useStoreCode,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $actual = $this->_model->getBaseUrl($type);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getBaseUrlDataProvider()
    {
        return array(
            array(\Magento\Framework\UrlInterface::URL_TYPE_WEB, false, false, 'http://localhost/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_WEB, false, true, 'http://localhost/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true, false, 'http://localhost/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true, true, 'http://localhost/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_LINK, false, false, 'http://localhost/index.php/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_LINK, false, true, 'http://localhost/index.php/default/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true, false, 'http://localhost/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true, true, 'http://localhost/default/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_DIRECT_LINK, false, false, 'http://localhost/index.php/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_DIRECT_LINK, false, true, 'http://localhost/index.php/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_DIRECT_LINK, true, false, 'http://localhost/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_DIRECT_LINK, true, true, 'http://localhost/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_STATIC, false, false, 'http://localhost/pub/static/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_STATIC, false, true, 'http://localhost/pub/static/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_STATIC, true, false, 'http://localhost/pub/static/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_STATIC, true, true, 'http://localhost/pub/static/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_CACHE, false, false, 'http://localhost/pub/cache/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_CACHE, false, true, 'http://localhost/pub/cache/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_CACHE, true, false, 'http://localhost/pub/cache/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_CACHE, true, true, 'http://localhost/pub/cache/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_LIB, false, false, 'http://localhost/pub/lib/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_LIB, false, true, 'http://localhost/pub/lib/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_LIB, true, false, 'http://localhost/pub/lib/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_LIB, true, true, 'http://localhost/pub/lib/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, false, false, 'http://localhost/pub/media/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, false, true, 'http://localhost/pub/media/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true, false, 'http://localhost/pub/media/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true, true, 'http://localhost/pub/media/')
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetBaseUrlInPub()
    {
        \Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize(
            array(
                \Magento\Framework\App\Filesystem::PARAM_APP_DIRS => array(
                    \Magento\Framework\App\Filesystem::PUB_DIR => array('uri' => '')
                )
            )
        );
        $this->_model = $this->_getStoreModel();
        $this->_model->load('default');

        $this->assertEquals(
            'http://localhost/pub/static/',
            $this->_model->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_STATIC)
        );
        $this->assertEquals(
            'http://localhost/pub/lib/',
            $this->_model->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LIB)
        );
        $this->assertEquals(
            'http://localhost/pub/media/',
            $this->_model->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
        );
    }

    /**
     * Isolation is enabled, as we pollute config with rewrite values
     *
     * @param string $type
     * @param bool $useCustomEntryPoint
     * @param bool $useStoreCode
     * @param string $expected
     * @dataProvider getBaseUrlForCustomEntryPointDataProvider
     * @magentoAppIsolation enabled
     */
    public function testGetBaseUrlForCustomEntryPoint($type, $useCustomEntryPoint, $useStoreCode, $expected)
    {
        /* config operations require store to be loaded */
        $this->_model->load('default');
        \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            'Magento\Framework\App\Config\MutableScopeConfigInterface'
        )->setValue(
            \Magento\Store\Model\Store::XML_PATH_USE_REWRITES,
            false,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            'Magento\Framework\App\Config\MutableScopeConfigInterface'
        )->setValue(
            \Magento\Store\Model\Store::XML_PATH_STORE_IN_URL,
            $useStoreCode,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // emulate custom entry point
        $_SERVER['SCRIPT_FILENAME'] = 'custom_entry.php';
        if ($useCustomEntryPoint) {
            $property = new \ReflectionProperty($this->_model, '_isCustomEntryPoint');
            $property->setAccessible(true);
            $property->setValue($this->_model, $useCustomEntryPoint);
        }
        $actual = $this->_model->getBaseUrl($type);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getBaseUrlForCustomEntryPointDataProvider()
    {
        return array(
            array(\Magento\Framework\UrlInterface::URL_TYPE_LINK, false, false, 'http://localhost/custom_entry.php/'),
            array(
                \Magento\Framework\UrlInterface::URL_TYPE_LINK,
                false,
                true,
                'http://localhost/custom_entry.php/default/'
            ),
            array(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true, false, 'http://localhost/index.php/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true, true, 'http://localhost/index.php/default/'),
            array(
                \Magento\Framework\UrlInterface::URL_TYPE_DIRECT_LINK,
                false,
                false,
                'http://localhost/custom_entry.php/'
            ),
            array(
                \Magento\Framework\UrlInterface::URL_TYPE_DIRECT_LINK,
                false,
                true,
                'http://localhost/custom_entry.php/'
            ),
            array(\Magento\Framework\UrlInterface::URL_TYPE_DIRECT_LINK, true, false, 'http://localhost/index.php/'),
            array(\Magento\Framework\UrlInterface::URL_TYPE_DIRECT_LINK, true, true, 'http://localhost/index.php/')
        );
    }

    public function testGetDefaultCurrency()
    {
        /* currency operations require store to be loaded */
        $this->_model->load('default');
        $this->assertEquals($this->_model->getDefaultCurrencyCode(), $this->_model->getDefaultCurrency()->getCode());
    }

    /**
     * @todo refactor \Magento\Store\Model\Store::getPriceFilter, it can return two different types
     */
    public function testGetPriceFilter()
    {
        $this->_model->load('default');
        $this->assertInstanceOf('Magento\Directory\Model\Currency\Filter', $this->_model->getPriceFilter());
    }

    public function testIsCanDelete()
    {
        $this->assertFalse($this->_model->isCanDelete());
        $this->_model->load(1);
        $this->assertFalse($this->_model->isCanDelete());
        $this->_model->setId(100);
        $this->assertTrue($this->_model->isCanDelete());
    }

    public function testGetCurrentUrl()
    {
        $this->_model->load('admin');
        $this->_model->expects($this->any())->method('getUrl')->will($this->returnValue('http://localhost/index.php'));
        $this->assertStringEndsWith('default', $this->_model->getCurrentUrl());
        $this->assertStringEndsNotWith('default', $this->_model->getCurrentUrl(false));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea adminhtml
     */
    public function testCRUD()
    {
        $this->_model->setData(
            array(
                'code' => 'test',
                'website_id' => 1,
                'group_id' => 1,
                'name' => 'test name',
                'sort_order' => 0,
                'is_active' => 1
            )
        );

        /* emulate admin store */
        $crud = new \Magento\TestFramework\Entity($this->_model, array('name' => 'new name'));
        $crud->testCrud();
    }

    /**
     * @param array $badStoreData
     *
     * @dataProvider saveValidationDataProvider
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @expectedException \Magento\Framework\Model\Exception
     */
    public function testSaveValidation($badStoreData)
    {
        $normalStoreData = array(
            'code' => 'test',
            'website_id' => 1,
            'group_id' => 1,
            'name' => 'test name',
            'sort_order' => 0,
            'is_active' => 1
        );
        $data = array_merge($normalStoreData, $badStoreData);

        $this->_model->setData($data);

        /* emulate admin store */
        $this->_model->save();
    }

    /**
     * @return array
     */
    public static function saveValidationDataProvider()
    {
        return array(
            'empty store name' => array(array('name' => '')),
            'empty store code' => array(array('code' => '')),
            'invalid store code' => array(array('code' => '^_^'))
        );
    }

    /**
     * @dataProvider isUseStoreInUrlDataProvider
     */
    public function testIsUseStoreInUrl($isInstalled, $storeInUrl, $disableStoreInUrl, $expectedResult)
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $configMock = $this->getMock('Magento\Framework\App\Config\ReinitableConfigInterface');
        $appStateMock = $this->getMock('Magento\Framework\App\State', array(), array(), '', false, false);
        $appStateMock->expects($this->any())->method('isInstalled')->will($this->returnValue($isInstalled));

        $params = $this->_modelParams;
        $params['context'] = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            'Magento\Framework\Model\Context',
            array('appState' => $appStateMock)
        );

        $configMock->expects(
            $this->any()
        )->method(
            'getValue'
        )->with(
            $this->stringContains(\Magento\Store\Model\Store::XML_PATH_STORE_IN_URL)
        )->will(
            $this->returnValue($storeInUrl)
        );
        $params['config'] = $configMock;
        $model = $objectManager->create('Magento\Store\Model\Store', $params);
        $model->setDisableStoreInUrl($disableStoreInUrl);
        $this->assertEquals($expectedResult, $model->isUseStoreInUrl());
    }

    /**
     * @see self::testIsUseStoreInUrl;
     * @return array
     */
    public function isUseStoreInUrlDataProvider()
    {
        return array(
            array(true, true, null, true),
            array(false, true, null, false),
            array(true, false, null, false),
            array(true, true, true, false),
            array(true, true, false, true)
        );
    }
}
