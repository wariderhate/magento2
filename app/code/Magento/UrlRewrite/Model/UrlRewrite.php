<?php
/**
 * URL Rewrite Model
 *
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
namespace Magento\UrlRewrite\Model;

/**
 * @method \Magento\UrlRewrite\Model\UrlRewrite setStoreId(int $value)
 * @method int getCategoryId()
 * @method \Magento\UrlRewrite\Model\UrlRewrite setCategoryId(int $value)
 * @method int getProductId()
 * @method \Magento\UrlRewrite\Model\UrlRewrite setProductId(int $value)
 * @method string getIdPath()
 * @method \Magento\UrlRewrite\Model\UrlRewritesetIdPath(string $value)
 * @method string getRequestPath()
 * @method \Magento\UrlRewrite\Model\UrlRewrite setRequestPath(string $value)
 * @method string getTargetPath()
 * @method \Magento\UrlRewrite\Model\UrlRewrite setTargetPath(string $value)
 * @method int getIsSystem()
 * @method \Magento\UrlRewrite\Model\UrlRewrite setIsSystem(int $value)
 * @method string getOptions()
 * @method \Magento\UrlRewrite\Model\UrlRewrite setOptions(string $value)
 * @method string getDescription()
 * @method \Magento\UrlRewrite\Model\UrlRewrite setDescription(string $value)
 */
class UrlRewrite extends \Magento\Framework\Model\AbstractModel
{
    const TYPE_CATEGORY = 1;

    const TYPE_PRODUCT = 2;

    const TYPE_CUSTOM = 3;

    /**
     * Cache tag for clear cache in after save and after delete
     *
     * @var array|string|boolean
     */
    protected $_cacheTag = false;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Stdlib\Cookie
     */
    protected $_cookie;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $_httpContext;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\Cookie $cookie
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Framework\Model\Resource\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\Db $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\Cookie $cookie,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Model\Resource\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\Db $resourceCollection = null,
        array $data = array()
    ) {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_cookie = $cookie;
        $this->_storeManager = $storeManager;
        $this->_httpContext = $httpContext;
    }

    /**
     * Initialize corresponding resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magento\UrlRewrite\Model\Resource\UrlRewrite');
    }

    /**
     * Clean cache for front-end menu
     *
     * @return  $this
     */
    protected function _afterSave()
    {
        if ($this->hasCategoryId()) {
            $this->_cacheTag = array(
                \Magento\Catalog\Model\Category::CACHE_TAG,
                \Magento\Store\Model\Group::CACHE_TAG
            );
        }

        parent::_afterSave();

        return $this;
    }

    /**
     * Load rewrite information for request
     * If $path is array - we must load possible records and choose one matching earlier record in array
     *
     * @param   mixed $path
     * @return  $this
     */
    public function loadByRequestPath($path)
    {
        $this->setId(null);
        $this->_getResource()->loadByRequestPath($this, $path);
        $this->_afterLoad();
        $this->setOrigData();
        $this->_hasDataChanges = false;
        return $this;
    }

    /**
     * @param int $path
     * @return $this
     */
    public function loadByIdPath($path)
    {
        $this->setId(null)->load($path, 'id_path');
        return $this;
    }

    /**
     * @param mixed $tags
     * @return $this
     */
    public function loadByTags($tags)
    {
        $this->setId(null);

        $loadTags = is_array($tags) ? $tags : explode(',', $tags);

        $search = $this->getResourceCollection();
        foreach ($loadTags as $k => $t) {
            if (!is_numeric($k)) {
                $t = $k . '=' . $t;
            }
            $search->addTagsFilter($t);
        }
        if (!is_null($this->getStoreId())) {
            $search->addStoreFilter($this->getStoreId());
        }

        $search->setPageSize(1)->load();

        if ($search->getSize() > 0) {
            foreach ($search as $rewrite) {
                $this->setData($rewrite->getData());
            }
        }

        return $this;
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function hasOption($key)
    {
        $optArr = explode(',', $this->getOptions());

        return array_search($key, $optArr) !== false;
    }

    /**
     * @param mixed $tags
     * @return $this
     */
    public function addTag($tags)
    {
        $curTags = $this->getTags();

        $addTags = is_array($tags) ? $tags : explode(',', $tags);

        foreach ($addTags as $k => $t) {
            if (!is_numeric($k)) {
                $t = $k . '=' . $t;
            }
            if (!in_array($t, $curTags)) {
                $curTags[] = $t;
            }
        }

        $this->setTags($curTags);

        return $this;
    }

    /**
     * @param mixed $tags
     * @return $this
     */
    public function removeTag($tags)
    {
        $curTags = $this->getTags();

        $removeTags = is_array($tags) ? $tags : explode(',', $tags);

        foreach ($removeTags as $k => $t) {
            if (!is_numeric($k)) {
                $t = $k . '=' . $t;
            }

            $key = array_search($t, $curTags);
            if ($key) {
                unset($curTags[$key]);
            }
        }

        $this->setTags(',', $curTags);

        return $this;
    }

    /**
     * Perform custom url rewrites
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    public function rewrite(\Magento\Framework\App\RequestInterface $request = null)
    {
        if (!$this->_appState->isInstalled()) {
            return false;
        }
        if (is_null($this->getStoreId()) || false === $this->getStoreId()) {
            $this->setStoreId($this->_storeManager->getStore()->getId());
        }

        /**
         * We have two cases of incoming paths - with and without slashes at the end ("/somepath/" and "/somepath").
         * Each of them matches two url rewrite request paths - with and without slashes at the end
         * ("/somepath/" and "/somepath").
         * Choose any matched rewrite, but in priority order that depends on same presence of slash and query params.
         */
        $requestCases = array();
        $pathInfo = $request->getPathInfo();
        $origSlash = substr($pathInfo, -1) == '/' ? '/' : '';
        $requestPath = trim($pathInfo, '/');

        // If there were final slash - add nothing to less priority paths. And vice versa.
        $altSlash = $origSlash ? '' : '/';

        $queryString = $this->_getQueryString();
        // Query params in request, matching "path + query" has more priority
        if ($queryString) {
            $requestCases[] = $requestPath . $origSlash . '?' . $queryString;
            $requestCases[] = $requestPath . $altSlash . '?' . $queryString;
        }
        $requestCases[] = $requestPath . $origSlash;
        $requestCases[] = $requestPath . $altSlash;

        $this->loadByRequestPath($requestCases);

        /**
         * Try to find rewrite by request path at first, if no luck - try to find by id_path
         */
        if (!$this->getId() && isset($_GET['___from_store'])) {
            try {
                $fromStoreId = $this->_storeManager->getStore($_GET['___from_store'])->getId();
            } catch (\Exception $e) {
                return false;
            }

            $this->setStoreId($fromStoreId)->loadByRequestPath($requestCases);
            if (!$this->getId()) {
                return false;
            }
            $currentStore = $this->_storeManager->getStore();
            $this->setStoreId($currentStore->getId())->loadByIdPath($this->getIdPath());

            $this->_cookie->set(\Magento\Store\Model\Store::COOKIE_NAME, $currentStore->getCode(), true);
            $targetUrl = $request->getBaseUrl() . '/' . $this->getRequestPath();

            $this->_sendRedirectHeaders($targetUrl, true);
        }

        if (!$this->getId()) {
            return false;
        }


        $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $this->getRequestPath());
        $external = substr($this->getTargetPath(), 0, 6);
        $isPermanentRedirectOption = $this->hasOption('RP');
        if ($external === 'http:/' || $external === 'https:') {
            $destinationStoreCode = $this->_storeManager->getStore($this->getStoreId())->getCode();
            $this->_cookie->set(\Magento\Store\Model\Store::COOKIE_NAME, $destinationStoreCode, true);
            $this->_sendRedirectHeaders($this->getTargetPath(), $isPermanentRedirectOption);
        } else {
            $targetUrl = $request->getBaseUrl() . '/' . $this->getTargetPath();
        }
        $isRedirectOption = $this->hasOption('R');
        if ($isRedirectOption || $isPermanentRedirectOption) {
            if ($this->_scopeConfig->getValue(
                'web/url/use_store',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) && ($storeCode = $this->_storeManager->getStore()->getCode())
            ) {
                $targetUrl = $request->getBaseUrl() . '/' . $storeCode . '/' . $this->getTargetPath();
            }

            $this->_sendRedirectHeaders($targetUrl, $isPermanentRedirectOption);
        }

        if ($this->_scopeConfig->getValue(
            'web/url/use_store',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) && ($storeCode = $this->_storeManager->getStore()->getCode())
        ) {
            $targetUrl = $request->getBaseUrl() . '/' . $storeCode . '/' . $this->getTargetPath();
        }

        $queryString = $this->_getQueryString();
        if ($queryString) {
            $targetUrl .= '?' . $queryString;
        }

        $request->setRequestUri($targetUrl);
        $request->setPathInfo($this->getTargetPath());

        return true;
    }

    /**
     * @return bool|string
     */
    protected function _getQueryString()
    {
        if (!empty($_SERVER['QUERY_STRING'])) {
            $queryParams = array();
            parse_str($_SERVER['QUERY_STRING'], $queryParams);
            $hasChanges = false;
            foreach ($queryParams as $key => $value) {
                if (substr($key, 0, 3) === '___') {
                    unset($queryParams[$key]);
                    $hasChanges = true;
                }
            }
            if ($hasChanges) {
                return http_build_query($queryParams);
            } else {
                return $_SERVER['QUERY_STRING'];
            }
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->_getData('store_id');
    }

    /**
     * Add location header and disable browser page caching
     *
     * @param string $url
     * @param bool $isPermanent
     * @return void
     */
    protected function _sendRedirectHeaders($url, $isPermanent = false)
    {
        if ($isPermanent) {
            header('HTTP/1.1 301 Moved Permanently');
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Location: ' . $url);
        exit;
    }
}
