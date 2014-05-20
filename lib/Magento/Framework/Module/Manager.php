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
 * Module statuses manager
 */
namespace Magento\Framework\Module;

class Manager
{
    /**
     * @var ConfigInterface
     */
    private $_outputConfig;

    /**
     * @var ModuleListInterface
     */
    private $_moduleList;

    /**
     * @var array
     */
    private $_outputConfigPaths;

    /**
     * @param Output\ConfigInterface $outputConfig
     * @param ModuleListInterface $moduleList
     * @param array $outputConfigPaths
     */
    public function __construct(
        Output\ConfigInterface $outputConfig,
        ModuleListInterface $moduleList,
        array $outputConfigPaths = array()
    ) {
        $this->_outputConfig = $outputConfig;
        $this->_moduleList = $moduleList;
        $this->_outputConfigPaths = $outputConfigPaths;
    }

    /**
     * Whether a module is enabled in the configuration or not
     *
     * @param string $moduleName Fully-qualified module name
     * @return boolean
     */
    public function isEnabled($moduleName)
    {
        return !!$this->_moduleList->getModule($moduleName);
    }

    /**
     * Whether a module output is permitted by the configuration or not
     *
     * @param string $moduleName Fully-qualified module name
     * @return boolean
     */
    public function isOutputEnabled($moduleName)
    {
        if (!$this->isEnabled($moduleName)) {
            return false;
        }
        if (!$this->_isCustomOutputConfigEnabled($moduleName)) {
            return false;
        }
        if ($this->_outputConfig->isEnabled($moduleName)) {
            return false;
        }
        return true;
    }

    /**
     * Whether a configuration switch for a module output permits output or not
     *
     * @param string $moduleName Fully-qualified module name
     * @return boolean
     */
    protected function _isCustomOutputConfigEnabled($moduleName)
    {
        if (isset($this->_outputConfigPaths[$moduleName])) {
            $configPath = $this->_outputConfigPaths[$moduleName];
            if (defined($configPath)) {
                $configPath = constant($configPath);
            }
            return $this->_outputConfig->isSetFlag($configPath);
        }
        return true;
    }
}
