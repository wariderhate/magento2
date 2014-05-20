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
namespace Magento\Customer\Helper;

class AddressTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\Customer\Helper\Address|\PHPUnit_Framework_MockObject_MockObject */
    protected $helper;

    /** @var \Magento\Framework\App\Helper\Context */
    protected $context;

    /** @var \Magento\Framework\View\Element\BlockFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $blockFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManager;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $scopeConfig;

    /** @var \Magento\Customer\Service\V1\CustomerMetadataServiceInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerMetadataService;

    /** @var \Magento\Customer\Model\Address\Config|\PHPUnit_Framework_MockObject_MockObject */
    protected $addressConfig;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder('Magento\Framework\App\Helper\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->blockFactory = $this->getMockBuilder(
            'Magento\Framework\View\Element\BlockFactory'
        )->disableOriginalConstructor()->getMock();
        $this->storeManager = $this->getMockBuilder(
            'Magento\Store\Model\StoreManagerInterface'
        )->disableOriginalConstructor()->getMock();
        $this->scopeConfig = $this->getMockBuilder(
            'Magento\Framework\App\Config\ScopeConfigInterface'
        )->disableOriginalConstructor()->getMock();
        $this->customerMetadataService = $this->getMockBuilder(
            'Magento\Customer\Service\V1\CustomerMetadataServiceInterface'
        )->disableOriginalConstructor()->getMock();
        $this->addressConfig = $this->getMockBuilder(
            'Magento\Customer\Model\Address\Config'
        )->disableOriginalConstructor()->getMock();

        $objectManager = new \Magento\TestFramework\Helper\ObjectManager($this);
        $this->helper = $objectManager->getObject(
            'Magento\Customer\Helper\Address',
            array(
                'context' => $this->context,
                'blockFactory' => $this->blockFactory,
                'storeManager' => $this->storeManager,
                'scopeConfig' => $this->scopeConfig,
                'customerMetadataService' => $this->customerMetadataService,
                'addressConfig' => $this->addressConfig
            )
        );
    }

    /**
     * @param int $numLines
     * @param int $expectedNumLines
     * @dataProvider providerGetStreetLines
     */
    public function testGetStreetLines($numLines, $expectedNumLines)
    {
        $attributeMock = $this->getMockBuilder(
            'Magento\Customer\Service\V1\Data\Eav\AttributeMetadata'
        )->disableOriginalConstructor()->getMock();
        $attributeMock->expects($this->any())->method('getMultilineCount')->will($this->returnValue($numLines));

        $this->customerMetadataService->expects(
            $this->any()
        )->method(
            'getAttributeMetadata'
        )->will(
            $this->returnValue($attributeMock)
        );

        $store = $this->getMockBuilder('Magento\Store\Model\Store')->disableOriginalConstructor()->getMock();
        $this->storeManager->expects($this->any())->method('getStore')->will($this->returnValue($store));

        $this->assertEquals($expectedNumLines, $this->helper->getStreetLines());
    }

    public function providerGetStreetLines()
    {
        return array(
            array(-1, 2),
            array(0, 2),
            array(1, 1),
            array(2, 2),
            array(3, 3),
            array(4, 4),
            array(5, 4),
            array(10, 4)
        );
    }
}
