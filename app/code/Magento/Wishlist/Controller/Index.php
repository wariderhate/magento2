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
 * Wishlist front controller
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Wishlist\Controller;

use Magento\Framework\App\Action\NotFoundException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;

class Index extends \Magento\Wishlist\Controller\AbstractController implements
    \Magento\Catalog\Controller\Product\View\ViewInterface
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileResponseFactory;

    /**
     * @var \Magento\Wishlist\Model\Config
     */
    protected $_wishlistConfig;

    /**
     * If true, authentication in this controller (wishlist) could be skipped
     *
     * @var bool
     */
    protected $_skipAuthentication = false;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Customer\Helper\View
     */
    protected $_customerHelperView;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Core\App\Action\FormKeyValidator $formKeyValidator
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Wishlist\Model\Config $wishlistConfig
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileResponseFactory
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Customer\Helper\View $customerHelperView
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Core\App\Action\FormKeyValidator $formKeyValidator,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Wishlist\Model\Config $wishlistConfig,
        \Magento\Framework\App\Response\Http\FileFactory $fileResponseFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Customer\Helper\View $customerHelperView
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_wishlistConfig = $wishlistConfig;
        $this->_fileResponseFactory = $fileResponseFactory;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->_customerHelperView = $customerHelperView;
        parent::__construct($context, $formKeyValidator, $customerSession);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Magento\Framework\App\Action\NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->_skipAuthentication && !$this->_objectManager->get(
            'Magento\Customer\Model\Session'
        )->authenticate(
            $this
        )
        ) {
            $this->_actionFlag->set('', 'no-dispatch', true);
            $customerSession = $this->_customerSession;
            if (!$customerSession->getBeforeWishlistUrl()) {
                $customerSession->setBeforeWishlistUrl($this->_redirect->getRefererUrl());
            }
            $customerSession->setBeforeWishlistRequest($request->getParams());
        }
        if (!$this->_objectManager->get(
            'Magento\Framework\App\Config\ScopeConfigInterface'
        )->isSetFlag(
            'wishlist/general/active'
        )
        ) {
            throw new NotFoundException();
        }
        return parent::dispatch($request);
    }

    /**
     * Set skipping authentication in actions of this controller (wishlist)
     *
     * @return $this
     */
    public function skipAuthentication()
    {
        $this->_skipAuthentication = true;
        return $this;
    }

    /**
     * Retrieve wishlist object
     *
     * @param int $wishlistId
     * @return \Magento\Wishlist\Model\Wishlist|false
     */
    protected function _getWishlist($wishlistId = null)
    {
        $wishlist = $this->_coreRegistry->registry('wishlist');
        if ($wishlist) {
            return $wishlist;
        }

        try {
            if (!$wishlistId) {
                $wishlistId = $this->getRequest()->getParam('wishlist_id');
            }
            $customerId = $this->_customerSession->getCustomerId();
            /* @var \Magento\Wishlist\Model\Wishlist $wishlist */
            $wishlist = $this->_objectManager->create('Magento\Wishlist\Model\Wishlist');
            if ($wishlistId) {
                $wishlist->load($wishlistId);
            } else {
                $wishlist->loadByCustomerId($customerId, true);
            }

            if (!$wishlist->getId() || $wishlist->getCustomerId() != $customerId) {
                $wishlist = null;
                throw new \Magento\Framework\Model\Exception(__("The requested wish list doesn't exist."));
            }

            $this->_coreRegistry->register('wishlist', $wishlist);
        } catch (\Magento\Framework\Model\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Wish List could not be created.'));
            return false;
        }

        return $wishlist;
    }

    /**
     * Display customer wishlist
     *
     * @return void
     * @throws NotFoundException
     */
    public function indexAction()
    {
        if (!$this->_getWishlist()) {
            throw new NotFoundException();
        }
        $this->_view->loadLayout();

        $session = $this->_customerSession;
        $block = $this->_view->getLayout()->getBlock('customer.wishlist');
        $referer = $session->getAddActionReferer(true);
        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
            if ($referer) {
                $block->setRefererUrl($referer);
            }
        }

        $this->_view->getLayout()->initMessages();

        $this->_view->renderLayout();
    }

    /**
     * Adding new item
     *
     * @return void
     * @throws NotFoundException
     */
    public function addAction()
    {
        $wishlist = $this->_getWishlist();
        if (!$wishlist) {
            throw new NotFoundException();
        }

        $session = $this->_customerSession;

        $requestParams = $this->getRequest()->getParams();

        if ($session->getBeforeWishlistRequest()) {
            $requestParams = $session->getBeforeWishlistRequest();
            $session->unsBeforeWishlistRequest();
        }

        $productId = isset($requestParams['product']) ? (int)$requestParams['product'] : null;

        if (!$productId) {
            $this->_redirect('*/');
            return;
        }

        $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($productId);
        if (!$product->getId() || !$product->isVisibleInCatalog()) {
            $this->messageManager->addError(__('We can\'t specify a product.'));
            $this->_redirect('*/');
            return;
        }

        try {
            $buyRequest = new \Magento\Framework\Object($requestParams);

            $result = $wishlist->addNewItem($product, $buyRequest);
            if (is_string($result)) {
                throw new \Magento\Framework\Model\Exception($result);
            }
            $wishlist->save();

            $this->_eventManager->dispatch(
                'wishlist_add_product',
                array('wishlist' => $wishlist, 'product' => $product, 'item' => $result)
            );

            $referer = $session->getBeforeWishlistUrl();
            if ($referer) {
                $session->setBeforeWishlistUrl(null);
            } else {
                $referer = $this->_redirect->getRefererUrl();
            }

            /**
             *  Set referer to avoid referring to the compare popup window
             */
            $session->setAddActionReferer($referer);

            /** @var $helper \Magento\Wishlist\Helper\Data */
            $helper = $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();
            $message = __(
                '%1 has been added to your wishlist. Click <a href="%2">here</a> to continue shopping.',
                $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($product->getName()),
                $this->_objectManager->get('Magento\Framework\Escaper')->escapeUrl($referer)
            );
            $this->messageManager->addSuccess($message);
        } catch (\Magento\Framework\Model\Exception $e) {
            $this->messageManager->addError(
                __('An error occurred while adding item to wish list: %1', $e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred while adding item to wish list.'));
            $this->_objectManager->get('Magento\Framework\Logger')->logException($e);
        }

        $this->_redirect('*', array('wishlist_id' => $wishlist->getId()));
    }

    /**
     * Action to reconfigure wishlist item
     *
     * @return void
     * @throws NotFoundException
     */
    public function configureAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        try {
            /* @var $item \Magento\Wishlist\Model\Item */
            $item = $this->_objectManager->create('Magento\Wishlist\Model\Item');
            $item->loadWithOptions($id);
            if (!$item->getId()) {
                throw new \Magento\Framework\Model\Exception(__('We can\'t load the wish list item.'));
            }
            $wishlist = $this->_getWishlist($item->getWishlistId());
            if (!$wishlist) {
                throw new NotFoundException();
            }

            $this->_coreRegistry->register('wishlist_item', $item);

            $params = new \Magento\Framework\Object();
            $params->setCategoryId(false);
            $params->setConfigureMode(true);
            $buyRequest = $item->getBuyRequest();
            if (!$buyRequest->getQty() && $item->getQty()) {
                $buyRequest->setQty($item->getQty());
            }
            if ($buyRequest->getQty() && !$item->getQty()) {
                $item->setQty($buyRequest->getQty());
                $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();
            }
            $params->setBuyRequest($buyRequest);
            $this->_objectManager->get(
                'Magento\Catalog\Helper\Product\View'
            )->prepareAndRender(
                $item->getProductId(),
                $this,
                $params
            );
        } catch (\Magento\Framework\Model\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('*');
            return;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('We can\'t configure the product.'));
            $this->_objectManager->get('Magento\Framework\Logger')->logException($e);
            $this->_redirect('*');
            return;
        }
    }

    /**
     * Action to accept new configuration for a wishlist item
     *
     * @return void
     */
    public function updateItemOptionsAction()
    {
        $session = $this->_customerSession;
        $productId = (int)$this->getRequest()->getParam('product');
        if (!$productId) {
            $this->_redirect('*/');
            return;
        }

        $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($productId);
        if (!$product->getId() || !$product->isVisibleInCatalog()) {
            $this->messageManager->addError(__('We can\'t specify a product.'));
            $this->_redirect('*/');
            return;
        }

        try {
            $id = (int)$this->getRequest()->getParam('id');
            /* @var \Magento\Wishlist\Model\Item */
            $item = $this->_objectManager->create('Magento\Wishlist\Model\Item');
            $item->load($id);
            $wishlist = $this->_getWishlist($item->getWishlistId());
            if (!$wishlist) {
                $this->_redirect('*/');
                return;
            }

            $buyRequest = new \Magento\Framework\Object($this->getRequest()->getParams());

            $wishlist->updateItem($id, $buyRequest)->save();

            $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();
            $this->_eventManager->dispatch(
                'wishlist_update_item',
                array('wishlist' => $wishlist, 'product' => $product, 'item' => $wishlist->getItem($id))
            );

            $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();

            $message = __('%1 has been updated in your wish list.', $product->getName());
            $this->messageManager->addSuccess($message);
        } catch (\Magento\Framework\Model\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred while updating wish list.'));
            $this->_objectManager->get('Magento\Framework\Logger')->logException($e);
        }
        $this->_redirect('*/*', array('wishlist_id' => $wishlist->getId()));
    }

    /**
     * Update wishlist item comments
     *
     * @return ResponseInterface|void
     * @throws NotFoundException
     */
    public function updateAction()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->_redirect('*/*/');
        }
        $wishlist = $this->_getWishlist();
        if (!$wishlist) {
            throw new NotFoundException();
        }

        $post = $this->getRequest()->getPost();
        if ($post && isset($post['description']) && is_array($post['description'])) {
            $updatedItems = 0;

            foreach ($post['description'] as $itemId => $description) {
                $item = $this->_objectManager->create('Magento\Wishlist\Model\Item')->load($itemId);
                if ($item->getWishlistId() != $wishlist->getId()) {
                    continue;
                }

                // Extract new values
                $description = (string)$description;

                if ($description == $this->_objectManager->get('Magento\Wishlist\Helper\Data')->defaultCommentString()
                ) {
                    $description = '';
                } elseif (!strlen($description)) {
                    $description = $item->getDescription();
                }

                $qty = null;
                if (isset($post['qty'][$itemId])) {
                    $qty = $this->_processLocalizedQty($post['qty'][$itemId]);
                }
                if (is_null($qty)) {
                    $qty = $item->getQty();
                    if (!$qty) {
                        $qty = 1;
                    }
                } elseif (0 == $qty) {
                    try {
                        $item->delete();
                    } catch (\Exception $e) {
                        $this->_objectManager->get('Magento\Framework\Logger')->logException($e);
                        $this->messageManager->addError(__('Can\'t delete item from wishlist'));
                    }
                }

                // Check that we need to save
                if ($item->getDescription() == $description && $item->getQty() == $qty) {
                    continue;
                }
                try {
                    $item->setDescription($description)->setQty($qty)->save();
                    $updatedItems++;
                } catch (\Exception $e) {
                    $this->messageManager->addError(
                        __(
                            'Can\'t save description %1',
                            $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($description)
                        )
                    );
                }
            }

            // save wishlist model for setting date of last update
            if ($updatedItems) {
                try {
                    $wishlist->save();
                    $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();
                } catch (\Exception $e) {
                    $this->messageManager->addError(__('Can\'t update wish list'));
                }
            }

            if (isset($post['save_and_share'])) {
                $this->_redirect('*/*/share', array('wishlist_id' => $wishlist->getId()));
                return;
            }
        }
        $this->_redirect('*', array('wishlist_id' => $wishlist->getId()));
    }

    /**
     * Remove item
     *
     * @return void
     * @throws NotFoundException
     */
    public function removeAction()
    {
        $id = (int)$this->getRequest()->getParam('item');
        $item = $this->_objectManager->create('Magento\Wishlist\Model\Item')->load($id);
        if (!$item->getId()) {
            throw new NotFoundException();
        }
        $wishlist = $this->_getWishlist($item->getWishlistId());
        if (!$wishlist) {
            throw new NotFoundException();
        }
        try {
            $item->delete();
            $wishlist->save();
        } catch (\Magento\Framework\Model\Exception $e) {
            $this->messageManager->addError(
                __('An error occurred while deleting the item from wish list: %1', $e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred while deleting the item from wish list.'));
        }

        $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();

        $url = $this->_redirect->getRedirectUrl($this->_url->getUrl('*/*'));
        $this->getResponse()->setRedirect($url);
    }

    /**
     * Add wishlist item to shopping cart and remove from wishlist
     *
     * If Product has required options - item removed from wishlist and redirect
     * to product view page with message about needed defined required options
     *
     * @return ResponseInterface
     */
    public function cartAction()
    {
        $itemId = (int)$this->getRequest()->getParam('item');

        /* @var $item \Magento\Wishlist\Model\Item */
        $item = $this->_objectManager->create('Magento\Wishlist\Model\Item')->load($itemId);
        if (!$item->getId()) {
            return $this->_redirect('*/*');
        }
        $wishlist = $this->_getWishlist($item->getWishlistId());
        if (!$wishlist) {
            return $this->_redirect('*/*');
        }

        // Set qty
        $qty = $this->getRequest()->getParam('qty');
        if (is_array($qty)) {
            if (isset($qty[$itemId])) {
                $qty = $qty[$itemId];
            } else {
                $qty = 1;
            }
        }
        $qty = $this->_processLocalizedQty($qty);
        if ($qty) {
            $item->setQty($qty);
        }

        /* @var $session \Magento\Framework\Session\Generic */
        $session = $this->_objectManager->get('Magento\Wishlist\Model\Session');
        $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');

        $redirectUrl = $this->_url->getUrl('*/*');

        try {
            $options = $this->_objectManager->create(
                'Magento\Wishlist\Model\Item\Option'
            )->getCollection()->addItemFilter(
                array($itemId)
            );
            $item->setOptions($options->getOptionsByItem($itemId));

            $buyRequest = $this->_objectManager->get(
                'Magento\Catalog\Helper\Product'
            )->addParamsToBuyRequest(
                $this->getRequest()->getParams(),
                array('current_config' => $item->getBuyRequest())
            );

            $item->mergeBuyRequest($buyRequest);
            $item->addToCart($cart, true);
            $cart->save()->getQuote()->collectTotals();
            $wishlist->save();

            if (!$cart->getQuote()->getHasError()) {
                $message = __(
                    'You added %1 to your shopping cart.',
                    $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($item->getProduct()->getName())
                );
                $this->messageManager->addSuccess($message);
            }

            $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();

            if ($this->_objectManager->get('Magento\Checkout\Helper\Cart')->getShouldRedirectToCart()) {
                $redirectUrl = $this->_objectManager->get('Magento\Checkout\Helper\Cart')->getCartUrl();
            } else {
                $refererUrl = $this->_redirect->getRefererUrl();
                if ($refererUrl &&
                    ($refererUrl != $this->_objectManager->get('Magento\Framework\UrlInterface')
                            ->getUrl('*/*/configure/', array('id' => $item->getId()))
                    )
                ) {
                    $redirectUrl = $refererUrl;
                }
            }
            $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();
        } catch (\Magento\Framework\Model\Exception $e) {
            if ($e->getCode() == \Magento\Wishlist\Model\Item::EXCEPTION_CODE_NOT_SALABLE) {
                $this->messageManager->addError(__('This product(s) is out of stock.'));
            } elseif ($e->getCode() == \Magento\Wishlist\Model\Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
                $this->messageManager->addNotice($e->getMessage());
                $redirectUrl = $this->_url->getUrl('*/*/configure/', array('id' => $item->getId()));
            } else {
                $this->messageManager->addNotice($e->getMessage());
                $redirectUrl = $this->_url->getUrl('*/*/configure/', array('id' => $item->getId()));
            }
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Cannot add item to shopping cart'));
        }

        $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();

        return $this->getResponse()->setRedirect($redirectUrl);
    }

    /**
     * Add cart item to wishlist and remove from cart
     *
     * @return \Zend_Controller_Response_Abstract
     * @throws NotFoundException
     */
    public function fromcartAction()
    {
        $wishlist = $this->_getWishlist();
        if (!$wishlist) {
            throw new NotFoundException();
        }
        $itemId = (int)$this->getRequest()->getParam('item');

        /* @var \Magento\Checkout\Model\Cart $cart */
        $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
        $session = $this->_objectManager->get('Magento\Checkout\Model\Session');

        try {
            $item = $cart->getQuote()->getItemById($itemId);
            if (!$item) {
                throw new \Magento\Framework\Model\Exception(__("The requested cart item doesn't exist."));
            }

            $productId = $item->getProductId();
            $buyRequest = $item->getBuyRequest();

            $wishlist->addNewItem($productId, $buyRequest);

            $productIds[] = $productId;
            $cart->getQuote()->removeItem($itemId);
            $cart->save();
            $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();
            $productName = $this->_objectManager->get('Magento\Framework\Escaper')
                ->escapeHtml($item->getProduct()->getName());
            $wishlistName = $this->_objectManager->get('Magento\Framework\Escaper')
                ->escapeHtml($wishlist->getName());
            $this->messageManager->addSuccess(__("%1 has been moved to wish list %2", $productName, $wishlistName));
            $wishlist->save();
        } catch (\Magento\Framework\Model\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t move the item to the wish list.'));
        }

        return $this->getResponse()->setRedirect(
            $this->_objectManager->get('Magento\Checkout\Helper\Cart')->getCartUrl()
        );
    }

    /**
     * Prepare wishlist for share
     *
     * @return void
     */
    public function shareAction()
    {
        $this->_getWishlist();
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }

    /**
     * Share wishlist
     *
     * @return ResponseInterface|void
     * @throws NotFoundException
     */
    public function sendAction()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->_redirect('*/*/');
        }

        $wishlist = $this->_getWishlist();
        if (!$wishlist) {
            throw new NotFoundException();
        }

        $sharingLimit = $this->_wishlistConfig->getSharingEmailLimit();
        $textLimit = $this->_wishlistConfig->getSharingTextLimit();
        $emailsLeft = $sharingLimit - $wishlist->getShared();
        $emails = explode(',', $this->getRequest()->getPost('emails'));
        $error = false;
        $message = (string)$this->getRequest()->getPost('message');
        if (strlen($message) > $textLimit) {
            $error = __('Message length must not exceed %1 symbols', $textLimit);
        } else {
            $message = nl2br(htmlspecialchars($message));
            if (empty($emails)) {
                $error = __('Email address can\'t be empty.');
            } else {
                if (count($emails) > $emailsLeft) {
                    $error = __('This wishlist can be shared %1 more times.', $emailsLeft);
                } else {
                    foreach ($emails as $index => $email) {
                        $email = trim($email);
                        if (!\Zend_Validate::is($email, 'EmailAddress')) {
                            $error = __('Please input a valid email address.');
                            break;
                        }
                        $emails[$index] = $email;
                    }
                }
            }
        }

        if ($error) {
            $this->messageManager->addError($error);
            $this->_objectManager->get(
                'Magento\Wishlist\Model\Session'
            )->setSharingForm(
                $this->getRequest()->getPost()
            );
            $this->_redirect('*/*/share');
            return;
        }

        $this->inlineTranslation->suspend();

        $sent = 0;

        try {
            $customer = $this->_customerSession->getCustomerDataObject();
            $customerName = $this->_customerHelperView->getCustomerName($customer);
            /*if share rss added rss feed to email template*/
            if ($this->getRequest()->getParam('rss_url')) {
                $rss_url = $this->_view->getLayout()->createBlock(
                    'Magento\Wishlist\Block\Share\Email\Rss'
                )->setWishlistId(
                    $wishlist->getId()
                )->toHtml();
                $message .= $rss_url;
            }
            $wishlistBlock = $this->_view->getLayout()->createBlock(
                'Magento\Wishlist\Block\Share\Email\Items'
            )->toHtml();

            $emails = array_unique($emails);
            $sharingCode = $wishlist->getSharingCode();

            try {
                $scopeConfig = $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
                $storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
                foreach ($emails as $email) {
                    $transport = $this->_transportBuilder->setTemplateIdentifier(
                        $scopeConfig->getValue(
                            'wishlist/email/email_template',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        )
                    )->setTemplateOptions(
                        array(
                            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                            'store' => $storeManager->getStore()->getStoreId()
                        )
                    )->setTemplateVars(
                        array(
                            'customer' => $customer,
                            'customerName' => $customerName,
                            'salable' => $wishlist->isSalable() ? 'yes' : '',
                            'items' => $wishlistBlock,
                            'addAllLink' => $this->_url->getUrl('*/shared/allcart', array('code' => $sharingCode)),
                            'viewOnSiteLink' => $this->_url->getUrl('*/shared/index', array('code' => $sharingCode)),
                            'message' => $message,
                            'store' => $storeManager->getStore()
                        )
                    )->setFrom(
                        $scopeConfig->getValue(
                            'wishlist/email/email_identity',
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        )
                    )->addTo(
                        $email
                    )->getTransport();

                    $transport->sendMessage();

                    $sent++;
                }
            } catch (\Exception $e) {
                $wishlist->setShared($wishlist->getShared() + $sent);
                $wishlist->save();
                throw $e;
            }
            $wishlist->setShared($wishlist->getShared() + $sent);
            $wishlist->save();

            $this->inlineTranslation->resume();

            $this->_eventManager->dispatch('wishlist_share', array('wishlist' => $wishlist));
            $this->messageManager->addSuccess(__('Your wish list has been shared.'));
            $this->_redirect('*/*', array('wishlist_id' => $wishlist->getId()));
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->messageManager->addError($e->getMessage());
            $this->_objectManager->get(
                'Magento\Wishlist\Model\Session'
            )->setSharingForm(
                $this->getRequest()->getPost()
            );
            $this->_redirect('*/*/share');
        }
    }

    /**
     * Custom options download action
     *
     * @return void
     */
    public function downloadCustomOptionAction()
    {
        $option = $this->_objectManager->create(
            'Magento\Wishlist\Model\Item\Option'
        )->load(
            $this->getRequest()->getParam('id')
        );

        if (!$option->getId()) {
            return $this->_forward('noroute');
        }

        $optionId = null;
        if (strpos($option->getCode(), \Magento\Catalog\Model\Product\Type\AbstractType::OPTION_PREFIX) === 0) {
            $optionId = str_replace(
                \Magento\Catalog\Model\Product\Type\AbstractType::OPTION_PREFIX,
                '',
                $option->getCode()
            );
            if ((int)$optionId != $optionId) {
                return $this->_forward('noroute');
            }
        }
        $productOption = $this->_objectManager->create('Magento\Catalog\Model\Product\Option')->load($optionId);

        if (!$productOption ||
            !$productOption->getId() ||
            $productOption->getProductId() != $option->getProductId() ||
            $productOption->getType() != 'file'
        ) {
            return $this->_forward('noroute');
        }

        try {
            $info = unserialize($option->getValue());
            $filePath = $this->_objectManager->get(
                'Magento\Framework\App\Filesystem'
            )->getPath(
                \Magento\Framework\App\Filesystem::ROOT_DIR
            ) . $info['quote_path'];
            $secretKey = $this->getRequest()->getParam('key');

            if ($secretKey == $info['secret_key']) {
                $this->_fileResponseFactory->create(
                    $info['title'],
                    array('value' => $filePath, 'type' => 'filename'),
                    \Magento\Framework\App\Filesystem::ROOT_DIR
                );
            }
        } catch (\Exception $e) {
            $this->_forward('noroute');
        }
        exit(0);
    }

    /**
     * Add all items from wishlist to shopping cart
     *
     * @return void
     */
    public function allcartAction()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->_forward('noroute');
            return;
        }

        parent::allcartAction();
    }
}
