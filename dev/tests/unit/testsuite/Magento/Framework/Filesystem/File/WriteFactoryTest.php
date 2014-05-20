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
namespace Magento\Framework\Filesystem\File;

/**
 * Class WriteFactoryTest
 */
class WriteFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\Filesystem\DriverFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $driverFactory;

    /**
     * @var WriteFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->driverFactory = $this->getMock('Magento\Framework\Filesystem\DriverFactory', [], [], '', false);
        $this->factory = new WriteFactory($this->driverFactory);
    }

    /**
     * @dataProvider createProvider
     * @param string|null $protocol
     */
    public function testCreate($protocol)
    {
        $path = 'path';
        $directoryDriver = $this->getMockForAbstractClass('Magento\Framework\Filesystem\DriverInterface');
        $mode = 'a+';

        if ($protocol) {
            $this->driverFactory->expects($this->once())
                ->method('get')
                ->with($protocol, get_class($directoryDriver))
                ->will($this->returnValue($directoryDriver));
        } else {
            $this->driverFactory->expects($this->never())
                ->method('get');
        }

        $this->assertInstanceOf(
            'Magento\Framework\Filesystem\File\Write',
            $this->factory->create($path, $protocol, $directoryDriver, $mode)
        );
    }

    public function createProvider()
    {
        return [
            [null],
            ['custom_protocol']
        ];
    }
}
