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
namespace Magento\Framework\View\Layout\File\Source\Override;

use Magento\Framework\View\Layout\File\SourceInterface;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\App\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\View\Layout\File\Factory;

/**
 * Source of layout files that explicitly override base files introduced by modules
 */
class Base implements SourceInterface
{
    /**
     * File factory
     *
     * @var Factory
     */
    private $fileFactory;

    /**
     * Themes directory
     *
     * @var ReadInterface
     */
    protected $themesDirectory;

    /**
     * Constructor
     *
     * @param Filesystem $filesystem
     * @param Factory $fileFactory
     */
    public function __construct(Filesystem $filesystem, Factory $fileFactory)
    {
        $this->themesDirectory = $filesystem->getDirectoryRead(Filesystem::THEMES_DIR);
        $this->fileFactory = $fileFactory;
    }

    /**
     * Retrieve files
     *
     * @param ThemeInterface $theme
     * @param string $filePath
     * @return array|\Magento\Framework\View\Layout\File[]
     */
    public function getFiles(ThemeInterface $theme, $filePath = '*')
    {
        $namespace = $module = '*';
        $themePath = $theme->getFullPath();
        $searchPattern = "{$themePath}/{$namespace}_{$module}/layout/override/base/{$filePath}.xml";
        $files = $this->themesDirectory->search($searchPattern);
        $result = array();
        $pattern = "#(?<moduleName>[^/]+)/layout/override/base/" . strtr(
            preg_quote($filePath),
            array('\*' => '[^/]+')
        ) . "\.xml$#i";
        foreach ($files as $file) {
            $filename = $this->themesDirectory->getAbsolutePath($file);
            if (!preg_match($pattern, $filename, $matches)) {
                continue;
            }
            $result[] = $this->fileFactory->create($filename, $matches['moduleName']);
        }
        return $result;
    }
}
