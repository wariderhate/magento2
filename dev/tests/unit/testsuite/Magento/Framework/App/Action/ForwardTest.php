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
namespace Magento\Framework\App\Action;

class ForwardTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\App\Action\Forward
     */
    protected $_actionAbstract;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $_response;

    protected function setUp()
    {
        $this->_request = $this->getMock('Magento\Framework\App\Request\Http', array(), array(), '', false);
        $this->_response = $this->getMock('\Magento\Framework\App\Response\Http', array(), array(), '', false);

        $this->_actionAbstract = new \Magento\Framework\App\Action\Forward($this->_request, $this->_response);
    }

    public function testDispatch()
    {
        $this->_request->expects($this->once())->method('setDispatched')->with(false);
        $this->_actionAbstract->dispatch($this->_request);
    }

    /**
     * Test for getRequest method
     *
     * @test
     * @covers \Magento\Framework\App\Action\AbstractAction::getRequest
     */
    public function testGetRequest()
    {
        $this->assertEquals($this->_request, $this->_actionAbstract->getRequest());
    }

    /**
     * Test for getResponse method
     *
     * @test
     * @covers \Magento\Framework\App\Action\AbstractAction::getResponse
     */
    public function testGetResponse()
    {
        $this->assertEquals($this->_response, $this->_actionAbstract->getResponse());
    }

    /**
     * Test for getResponse med. Checks that response headers are set correctly
     *
     * @test
     * @covers \Magento\Framework\App\Action\AbstractAction::getResponse
     */
    public function testResponseHeaders()
    {
        $infoProcessorMock = $this->getMock('Magento\Framework\App\Request\PathInfoProcessorInterface');
        $routerListMock = $this->getMock('Magento\Framework\App\Route\ConfigInterface');
        $cookieMock = $this->getMock('Magento\Framework\Stdlib\Cookie', array(), array(), '', false);
        $contextMock = $this->getMock('Magento\Framework\App\Http\Context', array(), array(), '', false);
        $request = new \Magento\Framework\App\Request\Http($routerListMock, $infoProcessorMock);
        $response = new \Magento\Framework\App\Response\Http($cookieMock, $contextMock);
        $response->headersSentThrowsException = false;
        $action = new \Magento\Framework\App\Action\Forward($request, $response);

        $this->assertEquals(array(), $action->getResponse()->getHeaders());
    }
}
