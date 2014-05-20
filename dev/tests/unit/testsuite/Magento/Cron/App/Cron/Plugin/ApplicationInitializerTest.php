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
namespace Magento\Cron\App\Cron\Plugin;

class ApplicationInitializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Cron\App\Cron\Plugin\ApplicationInitializer
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $appStateMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sidResolverMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectMock;

    protected function setUp()
    {
        $this->appStateMock = $this->getMock('Magento\Framework\App\State', array(), array(), '', false);
        $this->sidResolverMock = $this->getMock(
            '\Magento\Framework\Session\SidResolverInterface',
            array(),
            array(),
            '',
            false
        );
        $this->subjectMock = $this->getMock('Magento\Framework\App\Cron', array(), array(), '', false);
        $this->model = new ApplicationInitializer(
            $this->appStateMock,
            $this->sidResolverMock
        );
    }

    public function testBeforeExecutePerformsRequiredChecks()
    {
        $this->appStateMock->expects($this->once())->method('isInstalled')->will($this->returnValue(true));
        $this->sidResolverMock->expects($this->once())->method('setUseSessionInUrl')->with(false);
        $this->model->beforeLaunch($this->subjectMock);
    }
}
