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
namespace Magento\SalesRule\Helper;

use Magento\Sales\Model\Quote\Item\AbstractItem;

/**
 * SalesRule data helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Set store and base price which will be used during discount calculation to item object
     *
     * @param AbstractItem $item
     * @param float $basePrice
     * @param float $price
     * @return $this
     */
    public function setItemDiscountPrices(AbstractItem $item, $basePrice, $price)
    {
        $item->setDiscountCalculationPrice($price);
        $item->setBaseDiscountCalculationPrice($basePrice);
        return $this;
    }

    /**
     * Add additional amounts to discount calculation prices
     *
     * @param AbstractItem $item
     * @param float $basePrice
     * @param float $price
     * @return $this
     */
    public function addItemDiscountPrices(AbstractItem $item, $basePrice, $price)
    {
        $discountPrice = $item->getDiscountCalculationPrice();
        $baseDiscountPrice = $item->getBaseDiscountCalculationPrice();

        if ($discountPrice || $baseDiscountPrice || $basePrice || $price) {
            $discountPrice = $discountPrice ? $discountPrice : $item->getCalculationPrice();
            $baseDiscountPrice = $baseDiscountPrice ? $baseDiscountPrice : $item->getBaseCalculationPrice();
            $this->setItemDiscountPrices($item, $baseDiscountPrice + $basePrice, $discountPrice + $price);
        }
        return $this;
    }
}
