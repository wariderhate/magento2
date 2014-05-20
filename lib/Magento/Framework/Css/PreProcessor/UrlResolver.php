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
namespace Magento\Framework\Css\PreProcessor;

use Magento\Framework\View\Asset\PreProcessor\PreProcessorInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface;

/**
 * Css pre-processor url resolver
 */
class UrlResolver implements PreProcessorInterface
{
    /**
     * Temporary directory prefix
     */
    const TMP_RESOLVER_DIR = 'resolver';

    /**
     * Root directory
     *
     * @var WriteInterface
     */
    protected $rootDirectory;

    /**
     * Related file
     *
     * @var \Magento\Framework\View\RelatedFile
     */
    protected $relatedFile;

    /**
     * Helper to process css content
     *
     * @var \Magento\Framework\View\Url\CssResolver
     */
    protected $cssUrlResolver;

    /**
     * Publisher
     *
     * @var \Magento\Framework\View\Publisher
     */
    protected $publisher;

    /**
     * Logger
     *
     * @var \Magento\Framework\Logger
     */
    protected $logger;

    /**
     * Publisher file factory
     *
     * @var \Magento\Framework\View\Publisher\FileFactory
     */
    protected $fileFactory;

    /**
     * @param \Magento\Framework\App\Filesystem $filesystem
     * @param \Magento\Framework\View\RelatedFile $relatedFile
     * @param \Magento\Framework\View\Url\CssResolver $cssUrlResolver
     * @param \Magento\Framework\View\Publisher $publisher
     * @param \Magento\Framework\Logger $logger
     * @param \Magento\Framework\View\Publisher\FileFactory $fileFactory
     */
    public function __construct(
        \Magento\Framework\App\Filesystem $filesystem,
        \Magento\Framework\View\RelatedFile $relatedFile,
        \Magento\Framework\View\Url\CssResolver $cssUrlResolver,
        \Magento\Framework\View\Publisher $publisher,
        \Magento\Framework\Logger $logger,
        \Magento\Framework\View\Publisher\FileFactory $fileFactory
    ) {
        $this->rootDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem::ROOT_DIR);
        $this->relatedFile = $relatedFile;
        $this->cssUrlResolver = $cssUrlResolver;
        $this->publisher = $publisher;
        $this->logger = $logger;
        $this->fileFactory = $fileFactory;
    }

    /**
     * Process LESS file content
     *
     * @param \Magento\Framework\View\Publisher\FileInterface $publisherFile
     * @param \Magento\Framework\Filesystem\Directory\WriteInterface $targetDirectory
     * @return \Magento\Framework\View\Publisher\FileInterface
     */
    public function process(\Magento\Framework\View\Publisher\FileInterface $publisherFile, $targetDirectory)
    {
        if (!$publisherFile->isPublicationAllowed()) {
            return $publisherFile;
        }
        $filePath = $publisherFile->getFilePath();
        $sourcePath = $publisherFile->getSourcePath();
        $content = $this->rootDirectory->readFile($this->rootDirectory->getRelativePath($sourcePath));
        $params = $publisherFile->getViewParams();

        $callback = function ($fileId) use ($filePath, $params) {
            $relatedPathPublic = $this->publishRelatedViewFile($fileId, $filePath, $params);
            return $relatedPathPublic;
        };
        try {
            $content = $this->cssUrlResolver->replaceCssRelativeUrls(
                $content,
                $sourcePath,
                $publisherFile->buildPublicViewFilename(),
                $callback
            );
        } catch (\Magento\Framework\Exception $e) {
            $this->logger->logException($e);
        }

        $tmpFilePath = Composite::TMP_VIEW_DIR .
            '/' .
            self::TMP_RESOLVER_DIR .
            '/' .
            $publisherFile->buildUniquePath();
        $targetDirectory->writeFile($tmpFilePath, $content);

        $processedFile = $this->fileFactory->create(
            $publisherFile->getFilePath(),
            $params,
            $targetDirectory->getAbsolutePath($tmpFilePath)
        );

        return $processedFile;
    }

    /**
     * Publish file identified by $fileId basing on information about parent file path and name.
     *
     * @param string $fileId URL to the file that was extracted from $parentFilePath
     * @param string $parentFileName original file name identifier that was requested for processing
     * @param array $params theme/module parameters array
     * @return string
     */
    protected function publishRelatedViewFile($fileId, $parentFileName, $params)
    {
        $relativeFilePath = $this->relatedFile->buildPath($fileId, $parentFileName, $params);
        return $this->publisher->getPublicFilePath($relativeFilePath, $params);
    }
}
