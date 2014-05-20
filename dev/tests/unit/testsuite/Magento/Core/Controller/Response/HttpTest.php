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

/**
 * Test class for \Magento\Framework\App\ResponseInterface
 */
namespace Magento\Core\Controller\Response;

class HttpTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for getHeader method
     *
     * @dataProvider headersDataProvider
     * @covers \Magento\Framework\App\Response\Http::getHeader
     * @param string $header
     */
    public function testGetHeaderExists($header)
    {
        $cookieMock = $this->getMock('\Magento\Framework\Stdlib\Cookie', array(), array(), '', false);
        $contextMock = $this->getMock('Magento\Framework\App\Http\Context', array(), array(), '', false);
        $response = new \Magento\Framework\App\Response\Http($cookieMock, $contextMock);
        $response->headersSentThrowsException = false;
        $response->setHeader($header['name'], $header['value'], $header['replace']);
        $this->assertEquals($header, $response->getHeader($header['name']));
    }

    /**
     * Data provider for testGetHeader
     *
     * @return array
     */
    public function headersDataProvider()
    {
        return array(
            array(array('name' => 'X-Frame-Options', 'value' => 'SAMEORIGIN', 'replace' => true)),
            array(array('name' => 'Test2', 'value' => 'Test2', 'replace' => false))
        );
    }

    /**
     * Test for getHeader method. Validation for attempt to get not existing header
     *
     * @covers \Magento\Framework\App\Response\Http::getHeader
     */
    public function testGetHeaderNotExists()
    {
        $cookieMock = $this->getMock('\Magento\Framework\Stdlib\Cookie', array(), array(), '', false);
        $contextMock = $this->getMock('Magento\Framework\App\Http\Context', array(), array(), '', false);
        $response = new \Magento\Framework\App\Response\Http($cookieMock, $contextMock);
        $response->headersSentThrowsException = false;
        $response->setHeader('Name', 'value', true);
        $this->assertFalse($response->getHeader('Wrong name'));
    }
}
