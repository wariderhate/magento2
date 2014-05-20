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
namespace Magento\Log;

use Magento\Framework\Filesystem\Directory\Write;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_model = null;

    /**
     * @var \ReflectionProperty
     */
    protected $_loggersProperty = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_filesystemMock;

    /**
     * @var Write | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_directory;

    protected function setUp()
    {
        $logDir = TESTS_TEMP_DIR . '/var/log';
        $this->_filesystemMock = $this->getMock('Magento\Framework\App\Filesystem', array(), array(), '', false);
        $this->_directory = $this->getMock('Magento\Framework\Filesystem\Directory\Write', array(), array(), '', false);
        $this->_filesystemMock->expects(
            $this->any()
        )->method(
            'getDirectoryWrite'
        )->with(
            \Magento\Framework\App\Filesystem::LOG_DIR
        )->will(
            $this->returnValue($this->_directory)
        );
        $this->_directory->expects($this->any())->method('create')->will($this->returnValue(true));
        $this->_directory->expects($this->any())->method('getAbsolutePath')->will(
            $this->returnCallback(
                function ($path) use ($logDir) {
                    $path = ltrim($path, '\/');
                    return $logDir . '/' . $path;
                }
            )
        );

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $this->_model = new \Magento\Framework\Logger($this->_filesystemMock);
        $this->_loggersProperty = new \ReflectionProperty($this->_model, '_loggers');
        $this->_loggersProperty->setAccessible(true);
    }

    /**
     * @param string $key
     * @param string $fileOrWrapper
     * @dataProvider addStreamLogDataProvider
     */
    public function testAddStreamLog($key, $fileOrWrapper)
    {
        $this->assertFalse($this->_model->hasLog($key));
        $this->_model->addStreamLog($key, $fileOrWrapper);
        $this->assertTrue($this->_model->hasLog($key));

        $loggers = $this->_loggersProperty->getValue($this->_model);
        $this->assertArrayHasKey($key, $loggers);
        $zendLog = $loggers[$key];
        $this->assertInstanceOf('Zend_Log', $zendLog);

        $writersProperty = new \ReflectionProperty($zendLog, '_writers');
        $writersProperty->setAccessible(true);
        $writers = $writersProperty->getValue($zendLog);
        $this->assertArrayHasKey(0, $writers);
        $stream = $writers[0];
        $this->assertInstanceOf('Zend_Log_Writer_Stream', $writers[0]);

        $streamProperty = new \ReflectionProperty($stream, '_stream');
        $streamProperty->setAccessible(true);
        $fileOrWrapper = $streamProperty->getValue($stream);
        $this->assertInternalType('resource', $fileOrWrapper);
        $this->assertEquals('stream', get_resource_type($fileOrWrapper));
    }

    /**
     * @return array
     */
    public function addStreamLogDataProvider()
    {
        return array(array('test', 'php://output'), array('test', 'custom_file.log'), array('test', ''));
    }

    /**
     * @covers \Magento\Framework\Logger::hasLog
     */
    public function testAddLogWithSpecificKey()
    {
        $key = uniqid();
        $this->_model->addStreamLog($key);
        $this->assertTrue($this->_model->hasLog($key));
    }

    public function testLog()
    {
        $messageOne = uniqid();
        $messageTwo = uniqid();
        $messageThree = uniqid();
        $this->expectOutputRegex(
            '/' . 'DEBUG \(7\).+?' . $messageTwo . '.+?' . 'CRIT \(2\).+?' . $messageThree . '/s'
        );
        $this->_model->addStreamLog('test', 'php://output');
        $this->_model->log($messageOne);
        $this->_model->log($messageTwo, \Zend_Log::DEBUG, 'test');
        $this->_model->log($messageThree, \Zend_Log::CRIT, 'test');
    }

    public function testLogComplex()
    {
        $this->expectOutputRegex('/Array\s\(\s+\[0\] => 1\s\).+stdClass Object/s');
        $this->_model->addStreamLog(\Magento\Framework\Logger::LOGGER_SYSTEM, 'php://output');
        $this->_model->log(array(1));
        $this->_model->log(new \StdClass());
    }

    public function testLogDebug()
    {
        $message = uniqid();
        /** @var $model \Magento\Framework\Logger|\PHPUnit_Framework_MockObject_MockObject */
        $model = $this->getMock('Magento\Framework\Logger', array('log'), array(), '', false);
        $model->expects($this->at(0))
            ->method('log')
            ->with($message, \Zend_Log::DEBUG, \Magento\Framework\Logger::LOGGER_SYSTEM);
        $model->expects(
            $this->at(1)
        )->method(
            'log'
        )->with(
            $message,
            \Zend_Log::DEBUG,
            \Magento\Framework\Logger::LOGGER_EXCEPTION
        );
        $model->logDebug($message);
        $model->logDebug($message, \Magento\Framework\Logger::LOGGER_EXCEPTION);
    }

    public function testLogException()
    {
        $exception = new \Exception();
        $expected = "\n{$exception}";
        /** @var $model \Magento\Framework\Logger|\PHPUnit_Framework_MockObject_MockObject */
        $model = $this->getMock('Magento\Framework\Logger', array('log'), array(), '', false);
        $model->expects(
            $this->at(0)
        )->method(
            'log'
        )->with(
            $expected,
            \Zend_Log::ERR,
            \Magento\Framework\Logger::LOGGER_EXCEPTION
        );
        $model->logException($exception);
    }
}
