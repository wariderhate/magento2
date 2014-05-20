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

define('PS', PATH_SEPARATOR);
define('BP', dirname(__DIR__));
define('MAGENTO_ROOT', dirname(__DIR__));

class __cli_Magento_Connect
{
    private static $_instance;
    protected $argv;
    public static function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function init($argv)
    {
                $this->argv = $argv;
        $this->setIncludes();
        require_once("Mage/Autoload/Simple.php");
        \Magento\Framework\Autoload\Simple::register();
        chdir(BP . '/downloader/');
        return $this;
    }

    public function setIncludes()
    {
        if (defined('DEVELOPMENT_MODE')) {
            $libPath = PS . dirname(BP) . '/lib';
        } else {
            $libPath = PS . BP . '/downloader/lib';
        }
        $includePath = BP . '/app'
        . $libPath
        . PS . get_include_path();
        set_include_path($includePath);
    }

    public function getCommands()
    {
        return \Magento\Framework\Connect\Command::getCommands();
    }

    public function getFrontend()
    {
        $frontend = \Magento\Framework\Connect\Frontend::getInstance('CLI');
        \Magento\Framework\Connect\Command::setFrontendObject($frontend);
        return $frontend;
    }

    public function getConfig($fileName = 'connect.cfg')
    {
        if (isset($this->config)) {
            return $this->config;
        }
        $config = new \Magento\Framework\Connect\Config($fileName);
        if (empty($config->magento_root)) {
            $config->magento_root = dirname(__DIR__);
        }
        \Magento\Framework\Connect\Command::setConfigObject($config);
        $this->config = $config;
        return $config;
    }

    public function detectCommand()
    {
        $argv = $this->argv;
        if (empty($argv[1])) {
            return false;
        }
        if (in_array($argv[1], $this->validCommands)) {
            list($options,$params) = $this->parseCommandArgs($argv);
            return array('name' => strtolower($argv[1]), 'options'=>$options, 'params'=>$params);
        }
        return false;
    }

    public function parseCommandArgs($argv)
    {
        $a = new \Magento\Framework\System\Args();
        $args = $a->getFiltered();
        array_shift($args);
        return array($a->getFlags(), $args);
    }

    public function runCommand($cmd, $options, $params)
    {
        $c = \Magento\Framework\Connect\Command::getInstance($cmd);
        $c->run($cmd, $options, $params);
    }

    private $_sconfig;
    public function getSingleConfig()
    {
        if (!$this->_sconfig) {
            $this->_sconfig = new \Magento\Framework\Connect\Singleconfig(
                    $this->getConfig()->magento_root . '/' .
                    $this->getConfig()->downloader_path . '/' .
                    \Magento\Framework\Connect\Singleconfig::DEFAULT_SCONFIG_FILENAME
            );
        }
        \Magento\Framework\Connect\Command::setSconfig($this->_sconfig);
        return $this->_sconfig;
    }

    public function run()
    {
        $this->commands = $this->getCommands();
        $this->frontend = $this->getFrontend();
        $this->config = $this->getConfig();
        $this->validCommands = array_keys($this->commands);
        $this->getSingleConfig();
        $cmd = $this->detectCommand();
        if (!$cmd) {
            $this->frontend->outputCommandList($this->commands);
        } else {
            $this->runCommand($cmd['name'], $cmd['options'], $cmd['params']);
        }
    }
}

if (defined('STDIN') && defined('STDOUT') && (defined('STDERR'))) {
    __cli_Magento_Connect::instance()->init($argv)->run();
}
