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
namespace Magento\Catalog\Controller\Product;

use Magento\Core\App\Action\FormKeyValidator;

/**
 * Catalog compare controller
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Compare extends \Magento\Framework\App\Action\Action
{
    /**
     * Customer id
     *
     * @var null|int
     */
    protected $_customerId = null;

    /**
     * Catalog session
     *
     * @var \Magento\Catalog\Model\Session
     */
    protected $_catalogSession;

    /**
     * Catalog product compare list
     *
     * @var \Magento\Catalog\Model\Product\Compare\ListCompare
     */
    protected $_catalogProductCompareList;

    /**
     * Log visitor
     *
     * @var \Magento\Log\Model\Visitor
     */
    protected $_logVisitor;

    /**
     * Customer session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Item collection factory
     *
     * @var \Magento\Catalog\Model\Resource\Product\Compare\Item\CollectionFactory
     */
    protected $_itemCollectionFactory;

    /**
     * Product factory
     *
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * Compare item factory
     *
     * @var \Magento\Catalog\Model\Product\Compare\ItemFactory
     */
    protected $_compareItemFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var FormKeyValidator
     */
    protected $_formKeyValidator;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Model\Product\Compare\ItemFactory $compareItemFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\Resource\Product\Compare\Item\CollectionFactory $itemCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Log\Model\Visitor $logVisitor
     * @param \Magento\Catalog\Model\Product\Compare\ListCompare $catalogProductCompareList
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param FormKeyValidator $formKeyValidator
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\Product\Compare\ItemFactory $compareItemFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Resource\Product\Compare\Item\CollectionFactory $itemCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Log\Model\Visitor $logVisitor,
        \Magento\Catalog\Model\Product\Compare\ListCompare $catalogProductCompareList,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        FormKeyValidator $formKeyValidator
    ) {
        $this->_storeManager = $storeManager;
        $this->_compareItemFactory = $compareItemFactory;
        $this->_productFactory = $productFactory;
        $this->_itemCollectionFactory = $itemCollectionFactory;
        $this->_customerSession = $customerSession;
        $this->_logVisitor = $logVisitor;
        $this->_catalogProductCompareList = $catalogProductCompareList;
        $this->_catalogSession = $catalogSession;
        $this->_formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    /**
     * Compare index action
     *
     * @return void
     */
    public function indexAction()
    {
        $items = $this->getRequest()->getParam('items');

        $beforeUrl = $this->getRequest()->getParam(self::PARAM_NAME_URL_ENCODED);
        if ($beforeUrl) {
            $this->_catalogSession->setBeforeCompareUrl(
                $this->_objectManager->get('Magento\Core\Helper\Data')->urlDecode($beforeUrl)
            );
        }

        if ($items) {
            $items = explode(',', $items);
            /** @var \Magento\Catalog\Model\Product\Compare\ListCompare $list */
            $list = $this->_catalogProductCompareList;
            $list->addProducts($items);
            $this->_redirect('*/*/*');
            return;
        }

        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }

    /**
     * Add item to compare list
     *
     * @return void
     */
    public function addAction()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->getResponse()->setRedirect($this->_redirect->getRefererUrl());
            return;
        }

        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId && ($this->_logVisitor->getId() || $this->_customerSession->isLoggedIn())) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->_productFactory->create();
            $product->setStoreId($this->_storeManager->getStore()->getId())->load($productId);

            if ($product->getId()) {
                $this->_catalogProductCompareList->addProduct($product);
                $productName = $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($product->getName());
                $this->messageManager->addSuccess(__('You added product %1 to the comparison list.', $productName));
                $this->_eventManager->dispatch('catalog_product_compare_add_product', array('product' => $product));
            }

            $this->_objectManager->get('Magento\Catalog\Helper\Product\Compare')->calculate();
        }
        $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
    }

    /**
     * Remove item from compare list
     *
     * @return void
     */
    public function removeAction()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->_productFactory->create();
            $product->setStoreId($this->_storeManager->getStore()->getId())->load($productId);

            if ($product->getId()) {
                /** @var $item \Magento\Catalog\Model\Product\Compare\Item */
                $item = $this->_compareItemFactory->create();
                if ($this->_customerSession->isLoggedIn()) {
                    $item->setCustomerId($this->_customerSession->getCustomerId());
                } elseif ($this->_customerId) {
                    $item->setCustomerId($this->_customerId);
                } else {
                    $item->addVisitorId($this->_logVisitor->getId());
                }

                $item->loadByProduct($product);
                /** @var $helper \Magento\Catalog\Helper\Product\Compare */
                $helper = $this->_objectManager->get('Magento\Catalog\Helper\Product\Compare');
                if ($item->getId()) {
                    $item->delete();
                    $productName = $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($product->getName());
                    $this->messageManager->addSuccess(
                        __('You removed product %1 from the comparison list.', $productName)
                    );
                    $this->_eventManager->dispatch(
                        'catalog_product_compare_remove_product',
                        array('product' => $item)
                    );
                    $helper->calculate();
                }
            }
        }

        if (!$this->getRequest()->getParam('isAjax', false)) {
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
        }
    }

    /**
     * Remove all items from comparison list
     *
     * @return void
     */
    public function clearAction()
    {
        /** @var \Magento\Catalog\Model\Resource\Product\Compare\Item\Collection $items */
        $items = $this->_itemCollectionFactory->create();

        if ($this->_customerSession->isLoggedIn()) {
            $items->setCustomerId($this->_customerSession->getCustomerId());
        } elseif ($this->_customerId) {
            $items->setCustomerId($this->_customerId);
        } else {
            $items->setVisitorId($this->_logVisitor->getId());
        }

        try {
            $items->clear();
            $this->messageManager->addSuccess(__('You cleared the comparison list.'));
            $this->_objectManager->get('Magento\Catalog\Helper\Product\Compare')->calculate();
        } catch (\Magento\Framework\Model\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong  clearing the comparison list.'));
        }

        $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
    }

    /**
     * Setter for customer id
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        $this->_customerId = $customerId;
        return $this;
    }
}
