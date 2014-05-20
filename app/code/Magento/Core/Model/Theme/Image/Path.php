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
 * Theme Image Path
 */
namespace Magento\Core\Model\Theme\Image;

class Path implements \Magento\Framework\View\Design\Theme\Image\PathInterface
{
    /**
     * Default theme preview image
     */
    const DEFAULT_PREVIEW_IMAGE = 'Magento_Core::theme/default_preview.jpg';

    /**
     * Filesystem instance
     *
     * @var \Magento\Framework\App\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\View\Url
     */
    protected $viewUrl;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Initialize dependencies
     * 
     * @param \Magento\Framework\App\Filesystem $filesystem
     * @param \Magento\Framework\View\Url $viewUrl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Filesystem $filesystem,
        \Magento\Framework\View\Url $viewUrl,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->filesystem = $filesystem;
        $this->viewUrl = $viewUrl;
        $this->_storeManager = $storeManager;
    }

    /**
     * Get preview image directory url
     *
     * @return string
     */
    public function getPreviewImageDirectoryUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . self::PREVIEW_DIRECTORY_PATH . '/';
    }

    /**
     * Return default themes preview image url
     *
     * @return string
     */
    public function getPreviewImageDefaultUrl()
    {
        return $this->viewUrl->getViewFileUrl(self::DEFAULT_PREVIEW_IMAGE);
    }

    /**
     * Get directory path for preview image
     *
     * @return string
     */
    public function getImagePreviewDirectory()
    {
        return $this->filesystem->getPath(\Magento\Framework\App\Filesystem::MEDIA_DIR)
            . '/' . self::PREVIEW_DIRECTORY_PATH;
    }

    /**
     * Temporary directory path to store images
     *
     * @return string
     */
    public function getTemporaryDirectory()
    {
        return $this->filesystem->getPath(\Magento\Framework\App\Filesystem::MEDIA_DIR) . '/theme/origin';
    }
}
