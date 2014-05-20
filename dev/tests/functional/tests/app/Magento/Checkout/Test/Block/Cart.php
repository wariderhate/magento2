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
namespace Magento\Checkout\Test\Block;

use Exception;
use Mtf\Block\Block;
use Mtf\Factory\Factory;
use Mtf\Client\Element\Locator;
use Magento\Catalog\Test\Fixture\Product;
use Magento\Catalog\Test\Fixture\SimpleProduct;
use Magento\Catalog\Test\Fixture\ConfigurableProduct;
use Magento\Checkout\Test\Block\Onepage\Link;

/**
 * Class Cart
 * Shopping cart block
 *
 */
class Cart extends Block
{
    /**
     * Proceed to checkout block
     *
     * @var string
     */
    protected $onepageLinkBlock = '.action.primary.checkout';

    /**
     * 'Clear Shopping Cart' button
     *
     * @var string
     */
    protected $clearShoppingCart = '#empty_cart_button';

    /**
     * Cart item sub-total xpath selector
     *
     * @var string
     */
    protected $itemSubTotalSelector = '//td[@class="col subtotal excl tax"]//span[@class="price"]';

    /**
     * Cart item unit price xpath selector
     *
     * @var string
     */
    protected $itemUnitPriceSelector = '//td[@class="col price excl tax"]//span[@class="price"]';

    /**
     * Unit Price value
     *
     * @var string
     */
    protected $cartProductPrice = '//tr[string(td/div/strong/a)="%s"]/td[@class="col price excl tax"]/span/span';

    /**
     * Get sub-total for the specified item in the cart
     *
     * @param SimpleProduct $product
     * @return string
     */
    public function getCartItemSubTotal($product)
    {
        $selector = '//tr[normalize-space(td)="' . $this->getProductName(
            $product
        ) . '"]' . $this->itemSubTotalSelector;
        return $this->_rootElement->find($selector, Locator::SELECTOR_XPATH)->getText();
    }

    /**
     * Get unit price for the specified item in the cart
     *
     * @param Product $product
     * @param string $currency
     *
     * @return float
     */
    public function getCartItemUnitPrice($product, $currency = '$')
    {
        $selector = '//tr[normalize-space(td)="' . $this->getProductName(
            $product
        ) . '"]' . $this->itemUnitPriceSelector;

        $prices = explode("\n", trim($this->_rootElement->find($selector, Locator::SELECTOR_XPATH)->getText()));
        if (count($prices) == 1) {
            return floatval(trim($prices[0], $currency));
        }
        return $this->formatPricesData($prices, $currency);
    }

    /**
     * Get product options in the cart
     *
     * @param Product $product
     * @return array|string
     */
    public function getCartItemOptions($product)
    {
        $selector = '//tr[string(td/div/strong/a)="' . $this->getProductName($product)
            . '"]//dl[@class="cart item options"]';

        $optionsBlock = $this->_rootElement->find($selector, Locator::SELECTOR_XPATH);
        if (!$optionsBlock->isVisible()) {
            return '';
        }
        return $optionsBlock->getText();
    }

    /**
     * Get proceed to checkout block
     *
     * @return Link
     */
    public function getOnepageLinkBlock()
    {
        return Factory::getBlockFactory()->getMagentoCheckoutOnepageLink(
            $this->_rootElement->find($this->onepageLinkBlock, Locator::SELECTOR_CSS)
        );
    }

    /**
     * Press 'Check out with PayPal' button
     */
    public function paypalCheckout()
    {
        $this->_rootElement->find('[data-action=checkout-form-submit]', Locator::SELECTOR_CSS)->click();
    }

    /**
     * Returns the total discount price
     *
     * @var string
     * @return string
     * @throws Exception
     */
    public function getDiscountTotal()
    {
        $element = $this->_rootElement->find(
            '//table[@id="shopping-cart-totals-table"]' .
            '//tr[normalize-space(td)="Discount"]' .
            '//td[@class="amount"]//span[@class="price"]',
            Locator::SELECTOR_XPATH
        );
        if (!$element->isVisible()) {
            throw new Exception('Error could not find the Discount Total in the HTML');
        }
        return $element->getText();
    }

    /**
     * Clear shopping cart
     */
    public function clearShoppingCart()
    {
        $clearShoppingCart = $this->_rootElement->find($this->clearShoppingCart);
        if ($clearShoppingCart->isVisible()) {
            $clearShoppingCart->click();
        }
    }

    /**
     * Check if a product has been successfully added to the cart
     *
     * @param Product $product
     * @return boolean
     */
    public function isProductInShoppingCart($product)
    {
        return $this->_rootElement->find(
            '//tr[normalize-space(td)="' . $this->getProductName($product) . '"]',
            Locator::SELECTOR_XPATH
        )->isVisible();
    }

    /**
     * Return the name of the specified product.
     *
     * @param Product $product
     * @return string
     */
    private function getProductName($product)
    {
        $productName = $product->getProductName();
        if ($product instanceof ConfigurableProduct) {
            $productOptions = $product->getProductOptions();
            if (!empty($productOptions)) {
                $productName = $productName . ' ' . key($productOptions) . ' ' . current($productOptions);
            }
        }
        return $productName;
    }

    /**
     * Get product price "Unit Price" by product name
     *
     * @param $productName
     * @return string
     */
    public function getProductPriceByName($productName)
    {
        $priceSelector = sprintf($this->cartProductPrice, $productName);
        return $this->_rootElement->find($priceSelector, Locator::SELECTOR_XPATH)->getText();
    }
}
