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
 * Magento application for performance tests
 */
namespace Magento\TestFramework;

class Application
{
    /**
     * Area code
     */
    const AREA_CODE = 'install';

    /**
     * Configuration object
     *
     * @var \Magento\TestFramework\Performance\Config
     */
    protected $_config;

    /**
     * Path to shell installer script
     *
     * @var string
     */
    protected $_installerScript;

    /**
     * @var \Magento\Framework\Shell
     */
    protected $_shell;

    /**
     * @var \Magento\Framework\ObjectManager
     */
    protected $_objectManager;

    /**
     * Whether application is installed
     *
     * @var bool
     */
    protected $_isInstalled = false;

    /**
     * List of fixtures applied to the application
     *
     * @var array
     */
    protected $_fixtures = array();

    /**
     * Constructor
     *
     * @param \Magento\TestFramework\Performance\Config $config
     * @param \Magento\Framework\Shell $shell
     * @throws \Magento\Framework\Exception
     */
    public function __construct(\Magento\TestFramework\Performance\Config $config, \Magento\Framework\Shell $shell)
    {
        $installerScript = $config->getApplicationBaseDir() . '/dev/shell/install.php';
        if (!is_file($installerScript)) {
            throw new \Magento\Framework\Exception("File '{$installerScript}' is not found.");
        }
        $this->_installerScript = realpath($installerScript);
        $this->_config = $config;
        $this->_shell = $shell;
    }

    /**
     * Reset application - i.e. cleanup already installed app, or install it otherwise
     *
     * @return \Magento\TestFramework\Application
     */
    protected function _reset()
    {
        if ($this->_config->getInstallOptions()) {
            $this->_uninstall()->_install()->reindex()->_updateFilesystemPermissions();
        } else {
            $this->_isInstalled = true;
        }
        return $this;
    }

    /**
     * Reset application (uninstall, install, reindex, update permissions)
     *
     * @return Application
     */
    public function reset()
    {
        return $this->_reset();
    }

    /**
     * Run reindex
     *
     * @return Application
     */
    public function reindex()
    {
        $this->_shell->execute(
            'php -f ' . $this->_config->getApplicationBaseDir() . '/dev/shell/indexer.php -- reindexall'
        );
        // TODO: remove once Magento\Index module is completely removed (MAGETWO-18168)
        $this->_shell->execute(
            'php -f ' . $this->_config->getApplicationBaseDir() . '/dev/shell/newindexer.php -- reindexall'
        );
        return $this;
    }

    /**
     * Uninstall application
     *
     * @return \Magento\TestFramework\Application
     */
    protected function _uninstall()
    {
        $this->_shell->execute('php -f %s -- --uninstall', array($this->_installerScript));

        $this->_isInstalled = false;
        $this->_fixtures = array();

        return $this;
    }

    /**
     * Install application according to installation options
     *
     * @return \Magento\TestFramework\Application
     * @throws \Magento\Framework\Exception
     */
    protected function _install()
    {
        $installOptions = $this->_config->getInstallOptions();
        if (!$installOptions) {
            throw new \Magento\Framework\Exception('Trying to install Magento, but installation options are not set');
        }

        // Populate install options with global options
        $baseUrl = 'http://' . $this->_config->getApplicationUrlHost() . $this->_config->getApplicationUrlPath();
        $installOptions = array_merge($installOptions, array('url' => $baseUrl, 'secure_base_url' => $baseUrl));
        $adminOptions = $this->_config->getAdminOptions();
        foreach ($adminOptions as $key => $val) {
            $installOptions['admin_' . $key] = $val;
        }

        $installCmd = 'php -f %s --';
        $installCmdArgs = array($this->_installerScript);
        foreach ($installOptions as $optionName => $optionValue) {
            $installCmd .= " --{$optionName} %s";
            $installCmdArgs[] = $optionValue;
        }
        $this->_shell->execute($installCmd, $installCmdArgs);

        $this->_isInstalled = true;
        $this->_fixtures = array();
        return $this;
    }

    /**
     * Update permissions for `var` directory
     */
    protected function _updateFilesystemPermissions()
    {
        /** @var \Magento\Framework\Filesystem\Directory\Write $varDirectory */
        $varDirectory = $this->getObjectManager()->get(
            'Magento\Framework\App\Filesystem'
        )->getDirectoryWrite(
            \Magento\Framework\App\Filesystem::VAR_DIR
        );
        $varDirectory->changePermissions('', 0777);
    }

    /**
     * Bootstrap application, so it is possible to use its resources
     *
     * @return \Magento\TestFramework\Application
     */
    protected function _bootstrap()
    {
        $this->getObjectManager()->configure(
            $this->getObjectManager()->get('Magento\Framework\App\ObjectManager\ConfigLoader')->load(self::AREA_CODE)
        );
        $this->getObjectManager()->get('Magento\Framework\Config\ScopeInterface')->setCurrentScope(self::AREA_CODE);
        return $this;
    }

    /**
     * Bootstrap
     *
     * @return Application
     */
    public function bootstrap()
    {
        return $this->_bootstrap();
    }

    /**
     * Work on application, so that it has all and only $fixtures applied. May require reinstall, if
     * excessive fixtures has been applied before.
     *
     * @param array $fixtures
     */
    public function applyFixtures(array $fixtures)
    {
        if (!$this->_isInstalled || $this->_doFixturesNeedReinstall($fixtures)) {
            $this->_reset();
        }

        // Apply fixtures
        $fixturesToApply = array_diff($fixtures, $this->_fixtures);
        if (!$fixturesToApply) {
            return;
        }

        $this->_bootstrap();
        foreach ($fixturesToApply as $fixtureFile) {
            $this->applyFixture($fixtureFile);
        }
        $this->_fixtures = $fixtures;

        $this->reindex()->_updateFilesystemPermissions();
    }

    /**
     * Apply fixture file
     *
     * @param string $fixtureFilename
     */
    public function applyFixture($fixtureFilename)
    {
        require $fixtureFilename;
    }

    /**
     * Compare list of fixtures needed to be set to the application, with the list of fixtures already in it.
     * Return, whether application reinstall (cleanup) is needed to properly apply the fixtures.
     *
     * @param array $fixtures
     * @return bool
     */
    protected function _doFixturesNeedReinstall($fixtures)
    {
        $excessiveFixtures = array_diff($this->_fixtures, $fixtures);
        return (bool)$excessiveFixtures;
    }

    /**
     * Get object manager
     *
     * @return \Magento\Framework\ObjectManager
     */
    public function getObjectManager()
    {
        if (!$this->_objectManager) {
            $locatorFactory = new \Magento\Framework\App\ObjectManagerFactory();
            $this->_objectManager = $locatorFactory->create(BP, $_SERVER);
            $this->_objectManager->get('Magento\Framework\App\State')->setAreaCode(self::AREA_CODE);
        }
        return $this->_objectManager;
    }

    /**
     * Reset object manager
     *
     * @return \Magento\Framework\ObjectManager
     */
    public function resetObjectManager()
    {
        $this->_objectManager = null;
        return $this;
    }
}
