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
 * Theme data helper
 */
namespace Magento\Core\Helper;

class Theme extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Filesystem facade
     *
     * @var \Magento\Framework\App\Filesystem
     */
    protected $_filesystem;

    /**
     * Layout merge factory
     *
     * @var \Magento\Framework\View\Layout\ProcessorFactory
     */
    protected $_layoutProcessorFactory;

    /**
     * Theme collection model
     *
     * @var \Magento\Core\Model\Resource\Theme\Collection
     */
    protected $_themeCollection;

    /**
     * @var \Magento\Framework\View\FileSystem
     */
    protected $_viewFileSystem;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Filesystem $filesystem
     * @param \Magento\Framework\View\Layout\ProcessorFactory $layoutProcessorFactory
     * @param \Magento\Core\Model\Resource\Theme\Collection $themeCollection
     * @param \Magento\Framework\View\FileSystem $viewFileSystem
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Filesystem $filesystem,
        \Magento\Framework\View\Layout\ProcessorFactory $layoutProcessorFactory,
        \Magento\Core\Model\Resource\Theme\Collection $themeCollection,
        \Magento\Framework\View\FileSystem $viewFileSystem
    ) {
        $this->_filesystem = $filesystem;
        $this->_layoutProcessorFactory = $layoutProcessorFactory;
        $this->_themeCollection = $themeCollection;
        $this->_viewFileSystem = $viewFileSystem;
        parent::__construct($context);
    }

    /**
     * Get CSS files of a given theme
     *
     * Returned array has a structure
     * array(
     *   'Magento_Catalog::widgets.css' => 'http://mage2.com/pub/static/frontend/_theme15/en_US/Magento_Cms/widgets.css'
     * )
     *
     * @param \Magento\Framework\View\Design\ThemeInterface $theme
     * @return array
     */
    public function getCssFiles($theme)
    {
        /** @var $layoutProcessor \Magento\Framework\View\Layout\ProcessorInterface */
        $layoutProcessor = $this->_layoutProcessorFactory->create(array('theme' => $theme));
        $layoutElement = $layoutProcessor->getFileLayoutUpdatesXml();

        /**
         * XPath selector to get CSS files from layout added for HEAD block directly
         */
        $xpathSelectorBlocks = '//block[@class="Magento\Theme\Block\Html\Head"]' .
            '/block[@class="Magento\Theme\Block\Html\Head\Css"]/arguments/argument[@name="file"]';

        /**
         * XPath selector to get CSS files from layout added for HEAD block using reference
         */
        $xpathSelectorRefs = '//referenceBlock[@name="head"]' .
            '/block[@class="Magento\Theme\Block\Html\Head\Css"]/arguments/argument[@name="file"]';

        $elements = array_merge(
            $layoutElement->xpath($xpathSelectorBlocks) ?: array(),
            $layoutElement->xpath($xpathSelectorRefs) ?: array()
        );

        $params = array('area' => $theme->getArea(), 'themeModel' => $theme, 'skipProxy' => true);

        $rootDirectory = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem::ROOT_DIR);
        $files = array();
        foreach ($elements as $fileId) {
            $fileId = (string)$fileId;
            $path = $this->_viewFileSystem->getViewFile($fileId, $params);
            $file = array('id' => $fileId, 'path' => $path);
            $file['safePath'] = $rootDirectory->getRelativePath($file['path']);

            //keys are used also to remove duplicates
            $files[$fileId] = $file;
        }

        return $files;
    }

    /**
     * Get CSS files by group
     *
     * @param \Magento\Framework\View\Design\ThemeInterface $theme
     * @return array
     * @throws \LogicException
     */
    public function getGroupedCssFiles($theme)
    {
        $jsDir = $this->_filesystem->getPath(\Magento\Framework\App\Filesystem::PUB_LIB_DIR);
        $codeDir = $this->_filesystem->getPath(\Magento\Framework\App\Filesystem::MODULES_DIR);
        $designDir = $this->_filesystem->getPath(\Magento\Framework\App\Filesystem::THEMES_DIR);

        $groups = array();
        $themes = array();
        foreach ($this->getCssFiles($theme) as $file) {
            $this->_detectTheme($file, $designDir);
            $this->_detectGroup($file, $designDir, $jsDir, $codeDir);

            if (isset($file['theme']) && $file['theme']->getThemeId()) {
                $themes[$file['theme']->getThemeId()] = $file['theme'];
            }

            if (!isset($file['group'])) {
                throw new \LogicException(__('Group is missed for file "%1"', $file['safePath']));
            }
            $group = $file['group'];
            unset($file['theme']);
            unset($file['group']);

            if (!isset($groups[$group])) {
                $groups[$group] = array();
            }
            $groups[$group][] = $file;
        }

        if (count($themes) > 1) {
            $themes = $this->_sortThemesByHierarchy($themes);
        }

        $order = array_merge(
            array($codeDir, $jsDir),
            array_map(
                function ($fileTheme) {
                    /** @var $fileTheme \Magento\Framework\View\Design\ThemeInterface */
                    return $fileTheme->getThemeId();
                },
                $themes
            )
        );
        $groups = $this->_sortArrayByArray($groups, $order);

        $labels = $this->_getGroupLabels($themes, $jsDir, $codeDir);
        foreach ($groups as $key => $group) {
            usort($group, array($this, '_sortGroupFilesCallback'));
            $groups[$labels[$key]] = $group;
            unset($groups[$key]);
        }
        return $groups;
    }

    /**
     * Detect theme view file belongs to and set it to file data under "theme" key
     *
     * @param array &$file
     * @param string $designDir
     * @return $this
     * @throws \LogicException
     */
    protected function _detectTheme(&$file, $designDir)
    {
        //TODO use cache here, so files of the same theme share one model

        $isInsideDesignDir = substr($file['path'], 0, strlen($designDir)) == $designDir;
        if (!$isInsideDesignDir) {
            return $this;
        }

        $relativePath = substr($file['path'], strlen($designDir));

        $area = strtok($relativePath, '/');
        $theme = strtok('/');

        if ($area === false || $theme === false) {
            throw new \LogicException(__('Theme path "%1/%2" is incorrect', $area, $theme));
        }
        $themeModel = $this->_themeCollection->getThemeByFullPath(
            $area . \Magento\Framework\View\Design\ThemeInterface::PATH_SEPARATOR . $theme
        );

        if (!$themeModel || !$themeModel->getThemeId()) {
            throw new \LogicException(__('Invalid theme loaded by theme path "%1/%2"', $area, $theme));
        }

        $file['theme'] = $themeModel;

        return $this;
    }

    /**
     * Detect group where file should be placed and set it to file data under "group" key
     *
     * @param array &$file
     * @param string $designDir
     * @param string $jsDir
     * @param string $codeDir
     * @return $this
     * @throws \LogicException
     */
    protected function _detectGroup(&$file, $designDir, $jsDir, $codeDir)
    {
        $group = null;
        if (substr($file['path'], 0, strlen($designDir)) == $designDir) {
            if (!isset($file['theme']) || !$file['theme']->getThemeId()) {
                throw new \LogicException(__('Theme is missed for file "%1"', $file['safePath']));
            }
            $group = $file['theme']->getThemeId();
        } elseif (substr($file['path'], 0, strlen($jsDir)) == $jsDir) {
            $group = $jsDir;
        } elseif (substr($file['path'], 0, strlen($codeDir)) == $codeDir) {
            $group = $codeDir;
        } else {
            throw new \LogicException(__('Invalid view file directory "%1"', $file['safePath']));
        }
        $file['group'] = $group;

        return $this;
    }

    /**
     * Sort themes according to their hierarchy
     *
     * @param array $themes
     * @return array
     */
    protected function _sortThemesByHierarchy(array $themes)
    {
        uasort($themes, array($this, '_sortThemesByHierarchyCallback'));
        return $themes;
    }

    /**
     * Sort one associative array according to another array
     *
     * $groups = array(
     *     b => item2,
     *     a => item1,
     *     c => item3,
     * );
     * $order = array(a,b,c);
     * result: array(
     *     a => item1,
     *     b => item2,
     *     c => item3,
     * )
     *
     * @param array $groups
     * @param array $order
     * @return array
     */
    protected function _sortArrayByArray(array $groups, array $order)
    {
        $ordered = array();
        foreach ($order as $key) {
            if (array_key_exists($key, $groups)) {
                $ordered[$key] = $groups[$key];
                unset($groups[$key]);
            }
        }
        return $ordered + $groups;
    }

    /**
     * Get group labels
     *
     * @param array $themes
     * @param string $jsDir
     * @param string $codeDir
     * @return array
     */
    protected function _getGroupLabels(array $themes, $jsDir, $codeDir)
    {
        $labels = array($jsDir => (string)__('Library files'), $codeDir => (string)__('Framework files'));
        foreach ($themes as $theme) {
            /** @var $theme \Magento\Framework\View\Design\ThemeInterface */
            $labels[$theme->getThemeId()] = (string)__('"%1" Theme files', $theme->getThemeTitle());
        }
        return $labels;
    }

    /**
     * Callback for sorting files inside group
     *
     * Return "1" if $firstFile should go before $secondFile, otherwise return "-1"
     *
     * @param array $firstFile
     * @param array $secondFile
     * @return int
     */
    protected function _sortGroupFilesCallback(array $firstFile, array $secondFile)
    {
        $hasContextFirst = strpos($firstFile['id'], '::') !== false;
        $hasContextSecond = strpos($secondFile['id'], '::') !== false;

        if ($hasContextFirst && $hasContextSecond) {
            $result = strcmp($firstFile['id'], $secondFile['id']);
        } elseif (!$hasContextFirst && !$hasContextSecond) {
            $result = strcmp($firstFile['id'], $secondFile['id']);
        } elseif ($hasContextFirst) {
            //case when first item has module context and second item doesn't
            $result = 1;
        } else {
            //case when second item has module context and first item doesn't
            $result = -1;
        }
        return $result;
    }

    /**
     * Sort themes by hierarchy callback
     *
     * @param \Magento\Framework\View\Design\ThemeInterface $firstTheme
     * @param \Magento\Framework\View\Design\ThemeInterface $secondTheme
     * @return int
     */
    protected function _sortThemesByHierarchyCallback($firstTheme, $secondTheme)
    {
        $parentTheme = $firstTheme->getParentTheme();
        while ($parentTheme) {
            if ($parentTheme->getThemeId() == $secondTheme->getThemeId()) {
                return -1;
            }
            $parentTheme = $parentTheme->getParentTheme();
        }
        return 1;
    }
}
