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
namespace Magento\Test\Tools\View\Generator;


require_once realpath(
    __DIR__ . '/../../../../../../../../'
) . '/tools/Magento/Tools/View/Generator/ThemeDeployment.php';
class ThemeDeploymentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\View\Url\CssResolver
     */
    protected $_cssUrlResolver;

    /**
     * @var string
     */
    protected $_tmpDir;

    /**
     * @var \Magento\Framework\App\Filesystem | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $filesystemAdapter;

    protected function setUp()
    {
        $methods = array('getDirectoryWrite', 'getPath', '__wakeup');
        $this->filesystem = $this->getMock('Magento\Framework\App\Filesystem', $methods, array(), '', false);
        $this->filesystem->expects(
            $this->any()
        )->method(
            'getPath'
        )->with(
            \Magento\Framework\App\Filesystem::ROOT_DIR
        )->will(
            $this->returnValue(str_replace('\\', '/', BP))
        );

        $viewFilesystem = $this->getMock(
            'Magento\Framework\View\Filesystem',
            array('normalizePath'),
            array(),
            '',
            false
        );
        $viewFilesystem->expects($this->any())->method('normalizePath')->will($this->returnArgument(0));

        $this->_cssUrlResolver = new \Magento\Framework\View\Url\CssResolver($this->filesystem, $viewFilesystem);
        $this->_tmpDir = TESTS_TEMP_DIR . '/tool_theme_deployment';

        $this->filesystemAdapter = new \Magento\Framework\Filesystem\Driver\File();
        $this->filesystemAdapter->createDirectory($this->_tmpDir, 0777);
    }

    protected function tearDown()
    {
        $this->filesystemAdapter->deleteDirectory($this->_tmpDir);
    }

    /**
     * @param string $permitted
     * @param string $forbidden
     * @param string $exceptionMessage
     * @dataProvider constructorExceptionDataProvider
     */
    public function testConstructorException($permitted, $forbidden, $exceptionMessage)
    {
        $this->setExpectedException('Magento\Framework\Exception', $exceptionMessage);
        $this->_createThemeDeployment($permitted, $forbidden);
    }

    public static function constructorExceptionDataProvider()
    {
        $conflictPermitted = __DIR__ . '/_files/ThemeDeployment/constructor_exception/permitted.php';
        $conflictForbidden = __DIR__ . '/_files/ThemeDeployment/constructor_exception/forbidden.php';
        return array(
            'no permitted config' => array(
                'non_existing_config.txt',
                $conflictForbidden,
                'Config file does not exist: non_existing_config.txt'
            ),
            'no forbidden config' => array(
                $conflictPermitted,
                'non_existing_config.txt',
                'Config file does not exist: non_existing_config.txt'
            ),
            'config conflicts' => array(
                $conflictPermitted,
                $conflictForbidden,
                'Conflicts: the following extensions are added both to permitted and forbidden lists: ' .
                'conflict1, conflict2'
            )
        );
    }

    public function testRun()
    {
        $permitted = __DIR__ . '/_files/ThemeDeployment/run/permitted.php';
        $forbidden = __DIR__ . '/_files/ThemeDeployment/run/forbidden.php';
        $fixture = include __DIR__ . '/_files/ThemeDeployment/run/fixture.php';

        $object = $this->_createThemeDeployment($permitted, $forbidden);
        $object->run($fixture['copyRules']);

        // Verify expected paths
        $actualPaths = $this->_getRelativePaths($this->_tmpDir);
        $actualPaths = $this->_canonizePathArray($actualPaths);
        $expectedPaths = $this->_canonizePathArray($fixture['expectedRelPaths']);
        $this->assertEquals($expectedPaths, $actualPaths, "Actual result of copying is different from expected paths");

        // Verify content of files
        foreach ($fixture['expectedFileContent'] as $relFile => $expectedContent) {
            $actualContent = trim(file_get_contents($this->_tmpDir . '/' . $relFile));
            $this->assertEquals($expectedContent, $actualContent, "Actual content is wrong in file {$relFile}");
        }
    }

    /**
     * Recursively go through directory and compose relative paths of all its files
     *
     * @param string $dir
     * @return array
     */
    protected function _getRelativePaths($dir)
    {
        $dirLen = strlen($dir);
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $result = array();
        foreach ($files as $file) {
            $result[] = substr($file, $dirLen + 1);
        }
        return $result;
    }

    /**
     * Process $paths, sorting them and making system separator in them
     *
     * @param array $paths
     * @return array
     */
    protected function _canonizePathArray($paths)
    {
        rsort($paths, SORT_STRING);
        foreach ($paths as &$path) {
            $path = str_replace(array('/', '\\'), '/', $path);
        }
        return $paths;
    }

    public function testRunInDryRun()
    {
        $permitted = __DIR__ . '/_files/ThemeDeployment/run/permitted.php';
        $forbidden = __DIR__ . '/_files/ThemeDeployment/run/forbidden.php';
        $fixture = include __DIR__ . '/_files/ThemeDeployment/run/fixture.php';

        $object = $this->_createThemeDeployment($permitted, $forbidden, true);
        $object->run($fixture['copyRules']);

        $actualPaths = $this->_getRelativePaths($this->_tmpDir);
        $this->assertEmpty($actualPaths, 'Nothing must be copied/created in dry-run mode');
    }

    /**
     * @expectedException \Magento\Framework\Exception
     * @expectedExceptionMessage The file extension "php" must be added either to the permitted or forbidden list
     */
    public function testRunWithUnknownExtension()
    {
        $permitted = __DIR__ . '/_files/ThemeDeployment/run/permitted.php';
        $forbidden = __DIR__ . '/_files/ThemeDeployment/run/forbidden_without_php.php';
        $fixture = include __DIR__ . '/_files/ThemeDeployment/run/fixture.php';

        $object = $this->_createThemeDeployment($permitted, $forbidden, true);
        $object->run($fixture['copyRules']);
    }

    public function testRunWithCasedExtension()
    {
        $permitted = __DIR__ . '/_files/ThemeDeployment/run/permitted_cased_js.php';

        $object = $this->_createThemeDeployment($permitted);
        $copyRules = array(
            array(
                'source' => __DIR__ . '/_files/ThemeDeployment/run/source_cased_js',
                'destinationContext' => array(
                    'area' => 'frontend',
                    'locale' => 'not_important',
                    'themePath' => 'theme_path',
                    'module' => null
                )
            )
        );
        $object->run($copyRules);
        $this->assertFileExists($this->_tmpDir . '/frontend/theme_path/file.JS');
    }

    /**
     * Create Theme Deployment instance
     *
     * @param string $permitted
     * @param string|null $forbidden
     * @param bool $isDryRun
     * @return \Magento\Tools\View\Generator\ThemeDeployment
     */
    protected function _createThemeDeployment($permitted, $forbidden = null, $isDryRun = false)
    {
        $filesystem = $this->getMock('Magento\Framework\App\Filesystem', array(), array(), '', false);
        $preProcessor = $this->getMock(
            'Magento\Framework\View\Asset\PreProcessor\PreProcessorInterface',
            array(),
            array(),
            '',
            false
        );
        $fileFactory = $this->getMock('Magento\Framework\View\Publisher\FileFactory', array(), array(), '', false);
        $appState = $this->getMock('Magento\Framework\App\State', array(), array(), '', false);
        $themeFactory = $this->getMock('Magento\Core\Model\Theme\DataFactory', array('create'), array(), '', false);

        $object = new \Magento\Tools\View\Generator\ThemeDeployment(
            $this->_cssUrlResolver,
            $filesystem,
            $preProcessor,
            $fileFactory,
            $appState,
            $themeFactory,
            $this->_tmpDir,
            $permitted,
            $forbidden,
            $isDryRun
        );

        $fileObject = $this->getMock('Magento\Framework\View\Publisher\File', array(), array(), '', false);
        $fileFactory->expects($this->any())->method('create')->will($this->returnValue($fileObject));
        $appState->expects($this->any())->method('emulateAreaCode')->will($this->returnValue($fileObject));
        $fileObject->expects($this->any())->method('getSourcePath')->will($this->returnValue(false));

        return $object;
    }
}
