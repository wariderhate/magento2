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
namespace Magento\Framework\View;

/**
 * Builds URLs for publicly accessible files
 */
class Url
{
    /**
     * XPath for configuration setting of signing static files
     */
    const XML_PATH_STATIC_FILE_SIGNATURE = 'dev/static/sign';

    /**
     * File system
     *
     * @var \Magento\Framework\App\Filesystem
     */
    protected $_filesystem;

    /**
     * View service
     *
     * @var \Magento\Framework\View\Service
     */
    protected $_viewService;

    /**
     * Publisher
     *
     * @var \Magento\Framework\View\Publisher
     */
    protected $_publisher;

    /**
     * Deployed file manager
     *
     * @var \Magento\Framework\View\DeployedFilesManager
     */
    protected $_deployedFileManager;

    /**
     * URL builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * Config
     *
     * @var \Magento\Framework\View\Url\ConfigInterface
     */
    protected $_config;

    /**
     * Map urls to app dirs
     *
     * @var array
     */
    protected $_fileUrlMap;

    /**
     * View file system
     *
     * @var \Magento\Framework\View\FileSystem
     */
    protected $_viewFileSystem;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Filesystem $filesystem
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\View\Url\ConfigInterface $config
     * @param \Magento\Framework\View\Service $viewService
     * @param \Magento\Framework\View\Publisher $publisher
     * @param \Magento\Framework\View\DeployedFilesManager $deployedFileManager
     * @param \Magento\Framework\View\FileSystem $viewFileSystem
     * @param array $fileUrlMap
     */
    public function __construct(
        \Magento\Framework\App\Filesystem $filesystem,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\View\Url\ConfigInterface $config,
        \Magento\Framework\View\Service $viewService,
        \Magento\Framework\View\Publisher $publisher,
        \Magento\Framework\View\DeployedFilesManager $deployedFileManager,
        \Magento\Framework\View\FileSystem $viewFileSystem,
        array $fileUrlMap = array()
    ) {
        $this->_filesystem = $filesystem;
        $this->_urlBuilder = $urlBuilder;
        $this->_config = $config;
        $this->_viewService = $viewService;
        $this->_publisher = $publisher;
        $this->_deployedFileManager = $deployedFileManager;
        $this->_viewFileSystem = $viewFileSystem;
        $this->_fileUrlMap = $fileUrlMap;
    }

    /**
     * Retrieve view file URL
     *
     * Get URL to file base on theme file identifier.
     * Publishes file there, if needed.
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = array())
    {
        $isSecure = isset($params['_secure']) ? (bool)$params['_secure'] : null;
        unset($params['_secure']);

        $publicFilePath = $this->getViewFilePublicPath($fileId, $params);
        $url = $this->getPublicFileUrl($publicFilePath, $isSecure);

        return $url;
    }

    /**
     * Get public file path
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFilePublicPath($fileId, array $params = array())
    {
        $this->_viewService->updateDesignParams($params);
        $filePath = $this->_viewService->extractScope($this->_viewFileSystem->normalizePath($fileId), $params);

        $publicFilePath = $this->_getFilesManager()->getPublicFilePath($filePath, $params);

        return $publicFilePath;
    }

    /**
     * Get url to public file
     *
     * @param string $publicFilePath
     * @param bool|null $isSecure
     * @return string
     * @throws \Magento\Framework\Exception
     */
    public function getPublicFileUrl($publicFilePath, $isSecure = null)
    {
        foreach ($this->_fileUrlMap as $urlMap) {
            $dir = $this->_filesystem->getPath($urlMap['value']);
            $publicFilePath = str_replace('\\', '/', $publicFilePath);
            if (strpos($publicFilePath, $dir) === 0) {
                $relativePath = ltrim(substr($publicFilePath, strlen($dir)), '\\/');
                $url = $this->_urlBuilder->getBaseUrl(
                    array('_type' => $urlMap['key'], '_secure' => $isSecure)
                ) . $relativePath;

                if ($this->_isStaticFilesSigned() && $this->_viewService->isViewFileOperationAllowed()) {
                    $directory = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem::ROOT_DIR);
                    $fileMTime = $directory->stat($directory->getRelativePath($publicFilePath))['mtime'];
                    $url .= '?' . $fileMTime;
                }
                return $url;
            }
        }

        throw new \Magento\Framework\Exception(
            "Cannot build URL for the file '{$publicFilePath}' because it does not reside in a public directory."
        );
    }

    /**
     * Check if static files have to be signed
     *
     * @return bool
     */
    protected function _isStaticFilesSigned()
    {
        return (bool)$this->_config->getValue(self::XML_PATH_STATIC_FILE_SIGNATURE);
    }

    /**
     * Get files manager that is able to return file public path
     *
     * @return \Magento\Framework\View\PublicFilesManagerInterface
     */
    protected function _getFilesManager()
    {
        if ($this->_viewService->isViewFileOperationAllowed()) {
            $filesManager = $this->_publisher;
        } else {
            $filesManager = $this->_deployedFileManager;
        }

        return $filesManager;
    }
}
