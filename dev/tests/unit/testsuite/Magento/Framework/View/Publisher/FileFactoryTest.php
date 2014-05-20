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
namespace Magento\Framework\View\Publisher;

use Magento\TestFramework\Helper\ObjectManager as ObjectManagerHelper;

class FileFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\Framework\View\Publisher\FileFactory */
    protected $fileFactory;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\Framework\ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $objectManagerMock;

    protected function setUp()
    {
        $this->objectManagerMock = $this->getMock('Magento\Framework\ObjectManager');

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->fileFactory = $this->objectManagerHelper->getObject(
            'Magento\Framework\View\Publisher\FileFactory',
            array('objectManager' => $this->objectManagerMock)
        );
    }

    /**
     * @param string $filePath
     * @param array $viewParams
     * @param string|null $sourcePath
     * @param string $expectedInstance
     * @dataProvider createDataProvider
     */
    public function testCreate($filePath, $viewParams, $sourcePath, $expectedInstance)
    {
        $fileInstance = $this->getMock($expectedInstance, array(), array(), '', false);
        $this->objectManagerMock->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            $this->equalTo($expectedInstance),
            $this->equalTo(array('filePath' => $filePath, 'viewParams' => $viewParams, 'sourcePath' => $sourcePath))
        )->will(
            $this->returnValue($fileInstance)
        );
        $this->assertInstanceOf($expectedInstance, $this->fileFactory->create($filePath, $viewParams, $sourcePath));
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return array(
            'css' => array(
                'some\file\path.css',
                array('some', 'view', 'params'),
                'some\source\path',
                'Magento\Framework\View\Publisher\CssFile'
            ),
            'other' => array(
                'some\file\path.gif',
                array('some', 'other', 'view', 'params'),
                'some\other\source\path',
                'Magento\Framework\View\Publisher\File'
            )
        );
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage stdClass has to implement the publisher file interface.
     */
    public function testCreateWrongInstance()
    {
        $filePath = 'something';
        $viewParams = array('some', 'array');
        $fileInstance = new \stdClass();
        $this->objectManagerMock->expects(
            $this->once()
        )->method(
            'create'
        )->with(
            $this->equalTo('stdClass'),
            $this->equalTo(array('filePath' => $filePath, 'viewParams' => $viewParams, 'sourcePath' => null))
        )->will(
            $this->returnValue($fileInstance)
        );
        $fileFactory = new FileFactory($this->objectManagerMock, 'stdClass');
        $fileFactory->create($filePath, $viewParams);
    }
}
