<?php
/**
 *
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
namespace Magento\Framework\App\Request;

use Magento\Framework\App\Request\Http as Request;

class HttpTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_routerListMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_infoProcessorMock;

    protected function setUp()
    {
        $this->_routerListMock = $this->getMock('Magento\Framework\App\Route\ConfigInterface');
        $this->_infoProcessorMock = $this->getMock('Magento\Framework\App\Request\PathInfoProcessorInterface');
        $this->_infoProcessorMock->expects($this->any())->method('process')->will($this->returnArgument(1));
    }

    public function testGetOriginalPathInfoWithTestUri()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock, 'http://test.com/value');
        $this->assertEquals('/value', $this->_model->getOriginalPathInfo());
    }

    public function testGetOriginalPathInfoWithEmptyUri()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock, null);
        $this->assertEmpty($this->_model->getOriginalPathInfo());
    }

    public function testSetPathInfoWithNullValue()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock, null);
        $actual = $this->_model->setPathInfo();
        $this->assertEquals($this->_model, $actual);
    }

    public function testSetPathInfoWithValue()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock, null);
        $expected = 'testPathInfo';
        $this->_model->setPathInfo($expected);
        $this->assertEquals($expected, $this->_model->getPathInfo());
    }

    public function testSetPathInfoWithQueryPart()
    {
        $this->_model = new Request(
            $this->_routerListMock,
            $this->_infoProcessorMock,
            'http://test.com/node?queryValue'
        );
        $this->_model->setPathInfo();
        $this->assertEquals('/node', $this->_model->getPathInfo());
    }

    public function testRewritePathInfoWithNewValue()
    {
        $expected = '/other/path';
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock, 'http://test.com/one/two');
        $this->_model->rewritePathInfo($expected);
        $this->assertEquals($expected, $this->_model->getPathInfo());
    }

    public function testRewritePathInfoWithSameValue()
    {
        $expected = '/one/two';
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock, 'http://test.com' . $expected);
        $this->_model->rewritePathInfo($expected);
        $this->assertEquals($expected, $this->_model->getPathInfo());
    }

    public function testGetBasePathWithPath()
    {

        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->_model->setBasePath('http:\/test.com\one/two');
        $this->assertEquals('http://test.com/one/two', $this->_model->getBasePath());
    }

    public function testGetBasePathWithoutPath()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->_model->setBasePath();
        $this->assertEquals('/', $this->_model->getBasePath());
    }

    public function testGetBaseUrlWithUrl()
    {

        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->_model->setBaseUrl('http:\/test.com\one/two');
        $this->assertEquals('http://test.com/one/two', $this->_model->getBaseUrl());
    }

    public function testGetBaseUrlWithEmptyUrl()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->_model->setBaseUrl();
        $this->assertEmpty($this->_model->getBaseUrl());
    }

    public function testSetRouteNameWithRouter()
    {
        $router = $this->getMock('\Magento\Framework\App\Router\AbstractRouter', array(), array(), '', false);
        $this->_routerListMock->expects($this->any())->method('getRouteFrontName')->will($this->returnValue($router));
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->_model->setRouteName('RouterName');
        $this->assertEquals('RouterName', $this->_model->getRouteName());
    }

    public function testSetRouteNameWithNullRouterValue()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->_routerListMock->expects($this->once())->method('getRouteFrontName')->will($this->returnValue(null));
        $this->_model->setRouteName('RouterName');
    }

    public function testGetFrontName()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock, 'http://test.com/one/two');
        $this->assertEquals('one', $this->_model->getFrontName());
    }

    public function testGetAliasWhenAliasExists()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->_model->setAlias('AliasName', 'AliasTarget');
        $this->assertEquals('AliasTarget', $this->_model->getAlias('AliasName'));
    }

    public function testGetAliasWhenAliasesIsNull()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->assertNull($this->_model->getAlias('someValue'));
    }

    public function testGetAliasesWhenAliasSet()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->_model->setAlias('AliasName', 'AliasTarget');
        $this->assertEquals(array('AliasName' => 'AliasTarget'), $this->_model->getAliases());
    }

    public function testGetAliasesWhenAliasAreEmpty()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->assertEmpty($this->_model->getAliases());
    }

    public function testGetRequestedRouteNameWhenRequestedRouteIsSet()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->_model->setRoutingInfo(array('requested_route' => 'ExpectedValue'));
        $this->assertEquals('ExpectedValue', $this->_model->getRequestedRouteName());
    }

    public function testGetRequestedRouteNameWithNullValueRouteName()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->_model->setRouteName('RouteName');
        $this->assertEquals('RouteName', $this->_model->getRequestedRouteName());
    }

    public function testGetRequestedRouteNameWithRewritePathInfo()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $expected = 'TestValue';
        $this->_model->setPathInfo($expected);
        $this->_model->rewritePathInfo($expected . '/other');
        $this->_routerListMock->expects(
            $this->once()
        )->method(
            'getRouteByFrontName'
        )->with(
            $expected
        )->will(
            $this->returnValue($expected)
        );
        $this->assertEquals($expected, $this->_model->getRequestedRouteName());
    }

    public function testGetRequestedRouteNameWithoutRewritePathInfo()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $expected = 'RouteName';
        $this->_model->setRouteName($expected);
        $this->assertEquals($expected, $this->_model->getRequestedRouteName());
    }

    public function testGetRequestedControllerNameWithRequestedController()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $expected = array('requested_controller' => 'ControllerName');
        $this->_model->setRoutingInfo($expected);
        $test = $this->_model->getRequestedControllerName();
        $this->assertEquals($expected['requested_controller'], $test);
    }

    public function testGetRequestedControllerNameWithRewritePathInfo()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $path = 'one/two/';
        $this->_model->setPathInfo($path);
        $this->_model->rewritePathInfo($path . '/last');
        $this->assertEquals('two', $this->_model->getRequestedControllerName());
    }

    public function testGetRequestedActionNameWithRoutingInfo()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->_model->setRoutingInfo(array('requested_action' => 'ExpectedValue'));
        $this->assertEquals('ExpectedValue', $this->_model->getRequestedActionName());
    }

    public function testGetRequestedActionNameWithRewritePathInfo()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $path = 'one/two/three';
        $this->_model->setPathInfo($path);
        $this->_model->rewritePathInfo($path . '/last');
        $this->assertEquals('three', $this->_model->getRequestedActionName());
    }

    public function testIsStraightWithTrueValue()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->assertTrue($this->_model->isStraight(true));
    }

    public function testIsStraightWithDefaultValue()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $this->assertFalse($this->_model->isStraight());
    }

    public function testGetFullActionName()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        /* empty request */
        $this->assertEquals('__', $this->_model->getFullActionName());
        $this->_model->setRouteName('test')->setControllerName('controller')->setActionName('action');
        $this->assertEquals('test/controller/action', $this->_model->getFullActionName('/'));
    }

    public function testInitForward()
    {
        $expected = $this->_initForward();
        $this->assertEquals($expected, $this->_model->getBeforeForwardInfo());
    }

    public function testGetBeforeForwardInfo()
    {
        $beforeForwardInfo = $this->_initForward();
        $this->assertNull($this->_model->getBeforeForwardInfo('not_existing_forward_info_key'));
        foreach (array_keys($beforeForwardInfo) as $key) {
            $this->assertEquals($beforeForwardInfo[$key], $this->_model->getBeforeForwardInfo($key));
        }
        $this->assertEquals($beforeForwardInfo, $this->_model->getBeforeForwardInfo());
    }

    /**
     * Initialize $_beforeForwardInfo
     *
     * @return array Contents of $_beforeForwardInfo
     */
    protected function _initForward()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);
        $beforeForwardInfo = [
            'params' => ['one' => '111', 'two' => '222'],
            'action_name' => 'ActionName',
            'controller_name' => 'ControllerName',
            'module_name' => 'ModuleName',
            'route_name' => 'RouteName'
        ];
        $this->_model->setParams($beforeForwardInfo['params']);
        $this->_model->setActionName($beforeForwardInfo['action_name']);
        $this->_model->setControllerName($beforeForwardInfo['controller_name']);
        $this->_model->setModuleName($beforeForwardInfo['module_name']);
        $this->_model->setRouteName($beforeForwardInfo['route_name']);
        $this->_model->initForward();
        return $beforeForwardInfo;
    }

    public function testIsAjax()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);

        $this->assertFalse($this->_model->isAjax());

        $this->_model->clearParams();
        $this->_model->setParam('ajax', 1);
        $this->assertTrue($this->_model->isAjax());

        $this->_model->clearParams();
        $this->_model->setParam('isAjax', 1);
        $this->assertTrue($this->_model->isAjax());

        $this->_model->clearParams();
        $server = $_SERVER;
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue($this->_model->isAjax());
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'NotXMLHttpRequest';
        $this->assertFalse($this->_model->isAjax());
        $_SERVER = $server;
    }

    public function testSetPost()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);

        $post = ['one' => '111', 'two' => '222'];
        $this->_model->setPost($post);
        $this->assertEquals($post, $_POST);

        $this->_model->setPost([]);
        $this->assertEmpty($_POST);

        $_POST = ['post_var' => 'post_value'];
        $this->_model->setPost('post_var 2', 'post_value 2');
        $this->assertEquals(['post_var' => 'post_value', 'post_var 2' => 'post_value 2'], $_POST);
    }

    public function testGetFiles()
    {
        $this->_model = new Request($this->_routerListMock, $this->_infoProcessorMock);

        $_FILES = ['one' => '111', 'two' => '222'];
        $this->assertEquals($_FILES, $this->_model->getFiles());

        foreach ($_FILES as $key => $value) {
            $this->assertEquals($value, $this->_model->getFiles($key));
        }

        $this->assertNull($this->_model->getFiles('no_such_file'));

        $this->assertEquals('default', $this->_model->getFiles('no_such_file', 'default'));
    }
}
