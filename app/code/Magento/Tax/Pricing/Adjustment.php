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

namespace Magento\Tax\Pricing;

use Magento\Framework\Pricing\Adjustment\AdjustmentInterface;
use Magento\Framework\Pricing\Object\SaleableInterface;
use Magento\Tax\Helper\Data as TaxHelper;

/**
 * Tax pricing adjustment model
 */
class Adjustment implements AdjustmentInterface
{
    /**
     * Adjustment code tax
     */
    const ADJUSTMENT_CODE = 'tax';

    /**
     * @var TaxHelper
     */
    protected $taxHelper;

    /**
     * @var int|null
     */
    protected $sortOrder;

    /**
     * @param TaxHelper $taxHelper
     * @param int $sortOrder
     */
    public function __construct(TaxHelper $taxHelper, $sortOrder = null)
    {
        $this->taxHelper = $taxHelper;
        $this->sortOrder = $sortOrder;
    }

    /**
     * Get adjustment code
     *
     * @return string
     */
    public function getAdjustmentCode()
    {
        return self::ADJUSTMENT_CODE;
    }

    /**
     * Define if adjustment is included in base price
     *
     * @return bool
     */
    public function isIncludedInBasePrice()
    {
        return $this->taxHelper->priceIncludesTax();
    }

    /**
     * Define if adjustment is included in display price
     *
     * @return bool
     */
    public function isIncludedInDisplayPrice()
    {
        return $this->taxHelper->displayPriceIncludingTax() || $this->taxHelper->displayBothPrices();
    }

    /**
     * Extract adjustment amount from the given amount value
     *
     * @param float $amount
     * @param SaleableInterface $saleableItem
     * @return float
     */
    public function extractAdjustment($amount, SaleableInterface $saleableItem)
    {
        if ($this->taxHelper->priceIncludesTax()) {
            $adjustedAmount = $this->taxHelper->getPrice($saleableItem, $amount);
            $result = $amount - $adjustedAmount;
        } else {
            $result = 0.;
        }
        return $result;
    }

    /**
     * Apply adjustment amount and return result value
     *
     * @param float $amount
     * @param SaleableInterface $saleableItem
     * @return float
     */
    public function applyAdjustment($amount, SaleableInterface $saleableItem)
    {
        $includingTax = !$this->taxHelper->priceIncludesTax();
        return $this->taxHelper->getPrice($saleableItem, $amount, $includingTax);
    }

    /**
     * Check if adjustment should be excluded from calculations along with the given adjustment
     *
     * @param string $adjustmentCode
     * @return bool
     */
    public function isExcludedWith($adjustmentCode)
    {
        return $this->getAdjustmentCode() === $adjustmentCode;
    }

    /**
     * Return sort order position
     *
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }
}
