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
namespace Magento\Store\App\FrontController\Plugin;

class DispatchExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Store\App\FrontController\Plugin\DispatchExceptionHandler
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_filesystemMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectMock;

    /**
     * @var \Closure
     */
    protected $closureMock;

    protected function setUp()
    {
        $this->_storeManagerMock = $this->getMock('\Magento\Store\Model\StoreManager', array(), array(), '', false);
        $this->_filesystemMock = $this->getMock('\Magento\Framework\App\Filesystem', array(), array(), '', false);
        $this->closureMock = function () {
            return 'Expected';
        };
        $this->subjectMock = $this->getMock('Magento\Framework\App\FrontController', array(), array(), '', false);
        $this->_model = new DispatchExceptionHandler($this->_storeManagerMock, $this->_filesystemMock);
    }

    public function testAroundDispatch()
    {
        $requestMock = $this->getMock('Magento\Framework\App\RequestInterface');
        $this->assertEquals(
            'Expected',
            $this->_model->aroundDispatch($this->subjectMock, $this->closureMock, $requestMock)
        );
    }
}
