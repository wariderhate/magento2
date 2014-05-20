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
namespace Magento\Wishlist\Controller;

use Magento\Framework\App\Action\Context;

/**
 * Wishlist Abstract Front Controller Action
 */
abstract class AbstractController extends \Magento\Framework\App\Action\Action
{
    /**
     * Filter to convert localized values to internal ones
     *
     * @var \Zend_Filter_LocalizedToNormalized
     */
    protected $_localFilter = null;

    /**
     * @var \Magento\Core\App\Action\FormKeyValidator
     */
    protected $_formKeyValidator;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @param Context $context
     * @param \Magento\Core\App\Action\FormKeyValidator $formKeyValidator
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        Context $context,
        \Magento\Core\App\Action\FormKeyValidator $formKeyValidator,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->_formKeyValidator = $formKeyValidator;
        $this->_customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * Processes localized qty (entered by user at frontend) into internal php format
     *
     * @param string $qty
     * @return float|int|null
     */
    protected function _processLocalizedQty($qty)
    {
        if (!$this->_localFilter) {
            $this->_localFilter = new \Zend_Filter_LocalizedToNormalized(
                array('locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocaleCode())
            );
        }
        $qty = $this->_localFilter->filter((double)$qty);
        if ($qty < 0) {
            $qty = null;
        }
        return $qty;
    }

    /**
     * Retrieve current wishlist instance
     *
     * @return \Magento\Wishlist\Model\Wishlist|false
     */
    abstract protected function _getWishlist();

    /**
     * Add all items from wishlist to shopping cart
     *
     * @return void
     */
    public function allcartAction()
    {
        $wishlist = $this->_getWishlist();
        if (!$wishlist) {
            $this->_forward('noroute');
            return;
        }
        $isOwner = $wishlist->isOwner($this->_customerSession->getCustomerId());

        $messages = array();
        $addedItems = array();
        $notSalable = array();
        $hasOptions = array();

        $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
        $collection = $wishlist->getItemCollection()->setVisibilityFilter();

        $qtys = $this->getRequest()->getParam('qty');
        foreach ($collection as $item) {
            /** @var \Magento\Wishlist\Model\Item */
            try {
                $disableAddToCart = $item->getProduct()->getDisableAddToCart();
                $item->unsProduct();

                // Set qty
                if (isset($qtys[$item->getId()])) {
                    $qty = $this->_processLocalizedQty($qtys[$item->getId()]);
                    if ($qty) {
                        $item->setQty($qty);
                    }
                }
                $item->getProduct()->setDisableAddToCart($disableAddToCart);
                // Add to cart
                if ($item->addToCart($cart, $isOwner)) {
                    $addedItems[] = $item->getProduct();
                }
            } catch (\Magento\Framework\Model\Exception $e) {
                if ($e->getCode() == \Magento\Wishlist\Model\Item::EXCEPTION_CODE_NOT_SALABLE) {
                    $notSalable[] = $item;
                } elseif ($e->getCode() == \Magento\Wishlist\Model\Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
                    $hasOptions[] = $item;
                } else {
                    $messages[] = __('%1 for "%2".', trim($e->getMessage(), '.'), $item->getProduct()->getName());
                }

                $cartItem = $cart->getQuote()->getItemByProduct($item->getProduct());
                if ($cartItem) {
                    $cart->getQuote()->deleteItem($cartItem);
                }
            } catch (\Exception $e) {
                $this->_objectManager->get('Magento\Framework\Logger')->logException($e);
                $messages[] = __('We cannot add this item to your shopping cart.');
            }
        }

        if ($isOwner) {
            $indexUrl = $this->_objectManager->get('Magento\Wishlist\Helper\Data')->getListUrl($wishlist->getId());
        } else {
            $indexUrl = $this->_objectManager->create(
                'Magento\Framework\UrlInterface'
            )->getUrl(
                'wishlist/shared',
                array('code' => $wishlist->getSharingCode())
            );
        }
        if ($this->_objectManager->get('Magento\Checkout\Helper\Cart')->getShouldRedirectToCart()) {
            $redirectUrl = $this->_objectManager->get('Magento\Checkout\Helper\Cart')->getCartUrl();
        } elseif ($this->_redirect->getRefererUrl()) {
            $redirectUrl = $this->_redirect->getRefererUrl();
        } else {
            $redirectUrl = $indexUrl;
        }

        if ($notSalable) {
            $products = array();
            foreach ($notSalable as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $messages[] = __(
                'We couldn\'t add the following product(s) to the shopping cart: %1.',
                join(', ', $products)
            );
        }

        if ($hasOptions) {
            $products = array();
            foreach ($hasOptions as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $messages[] = __(
                'Product(s) %1 have required options. Each product can only be added individually.',
                join(', ', $products)
            );
        }

        if ($messages) {
            $isMessageSole = count($messages) == 1;
            if ($isMessageSole && count($hasOptions) == 1) {
                $item = $hasOptions[0];
                if ($isOwner) {
                    $item->delete();
                }
                $redirectUrl = $item->getProductUrl();
            } else {
                foreach ($messages as $message) {
                    $this->messageManager->addError($message);
                }
                $redirectUrl = $indexUrl;
            }
        }

        if ($addedItems) {
            // save wishlist model for setting date of last update
            try {
                $wishlist->save();
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t update wish list.'));
                $redirectUrl = $indexUrl;
            }

            $products = array();
            foreach ($addedItems as $product) {
                $products[] = '"' . $product->getName() . '"';
            }

            $this->messageManager->addSuccess(
                __('%1 product(s) have been added to shopping cart: %2.', count($addedItems), join(', ', $products))
            );

            // save cart and collect totals
            $cart->save()->getQuote()->collectTotals();
        }
        $this->_objectManager->get('Magento\Wishlist\Helper\Data')->calculate();

        $this->getResponse()->setRedirect($redirectUrl);
    }
}
