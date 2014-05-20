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
use Magento\Framework\Exception;

/**
 * Source of layout files that explicitly override files of ancestor themes
 */
class Theme implements SourceInterface
{
    /**
     * Themes directory
     *
     * @var ReadInterface
     */
    protected $themesDirectory;

    /**
     * File factory
     *
     * @var Factory
     */
    private $fileFactory;

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
     * @throws \Magento\Framework\Exception
     */
    public function getFiles(ThemeInterface $theme, $filePath = '*')
    {
        $namespace = $module = '*';
        $themePath = $theme->getFullPath();
        $searchPattern = "{$themePath}/{$namespace}_{$module}/layout/override/theme/*/{$filePath}.xml";
        $files = $this->themesDirectory->search($searchPattern);

        if (empty($files)) {
            return array();
        }

        $themes = array();
        $currentTheme = $theme;
        while ($currentTheme = $currentTheme->getParentTheme()) {
            $themes[$currentTheme->getCode()] = $currentTheme;
        }
        $result = array();
        $pattern = "#/(?<module>[^/]+)/layout/override/theme/(?<themeName>[^/]+)/" . strtr(
            preg_quote($filePath),
            array('\*' => '[^/]+')
        ) . "\.xml$#i";
        foreach ($files as $file) {
            $filename = $this->themesDirectory->getAbsolutePath($file);
            if (!preg_match($pattern, $filename, $matches)) {
                continue;
            }
            $moduleFull = $matches['module'];
            $ancestorThemeCode = $matches['themeName'];
            if (!isset($themes[$ancestorThemeCode])) {
                throw new Exception(
                    sprintf(
                        "Trying to override layout file '%s' for theme '%s', which is not ancestor of theme '%s'",
                        $filename,
                        $ancestorThemeCode,
                        $theme->getCode()
                    )
                );
            }
            $result[] = $this->fileFactory->create($filename, $moduleFull, $themes[$ancestorThemeCode]);
        }
        return $result;
    }
}
