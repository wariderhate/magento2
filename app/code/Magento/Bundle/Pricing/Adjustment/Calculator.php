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

namespace Magento\Bundle\Pricing\Adjustment;

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Object\SaleableInterface;
use Magento\Framework\Pricing\Amount\AmountFactory;
use Magento\Bundle\Pricing\Price\BundleSelectionFactory;
use Magento\Framework\Pricing\Adjustment\Calculator as CalculatorBase;
use Magento\Bundle\Model\Product\Price;
use Magento\Bundle\Pricing\Price\BundleOptionPrice;

/**
 * Bundle price calculator
 */
class Calculator implements BundleCalculatorInterface
{
    /**
     * @var CalculatorBase
     */
    protected $calculator;

    /**
     * @var AmountFactory
     */
    protected $amountFactory;

    /**
     * @var BundleSelectionFactory
     */
    protected $selectionFactory;

    /**
     * @param CalculatorBase $calculator
     * @param AmountFactory $amountFactory
     * @param BundleSelectionFactory $bundleSelectionFactory
     * @return Calculator
     */
    public function __construct(
        CalculatorBase $calculator,
        AmountFactory $amountFactory,
        BundleSelectionFactory $bundleSelectionFactory
    ) {
        $this->calculator = $calculator;
        $this->amountFactory = $amountFactory;
        $this->selectionFactory = $bundleSelectionFactory;
    }

    /**
     * Get amount for current product which is included price of existing options with minimal price
     *
     * @param float|string $amount
     * @param SaleableInterface $saleableItem
     * @param null|string $exclude
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    public function getAmount($amount, SaleableInterface $saleableItem, $exclude = null)
    {
        return $this->getOptionsAmount($saleableItem, $exclude, true, $amount);
    }

    /**
     * Get amount for current product which is included price of existing options with maximal price
     *
     * @param float $amount
     * @param Product $saleableItem
     * @param null|string $exclude
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    public function getMaxAmount($amount, Product $saleableItem, $exclude = null)
    {
        return $this->getOptionsAmount($saleableItem, $exclude, false, $amount);
    }

    /**
     * Option amount calculation for bundle product
     *
     * @param Product $saleableItem
     * @param null|string $exclude
     * @param bool $searchMin
     * @param float $baseAmount
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    public function getOptionsAmount(
        Product $saleableItem,
        $exclude = null,
        $searchMin = true,
        $baseAmount = 0.
    ) {
        return $this->calculateBundleAmount(
            $baseAmount,
            $saleableItem,
            $this->getSelectionAmounts($saleableItem, $searchMin),
            $exclude
        );
    }

    /**
     * Filter all options for bundle product
     *
     * @param Product $bundleProduct
     * @param bool $searchMin
     * @return array
     */
    protected function getSelectionAmounts(Product $bundleProduct, $searchMin)
    {
        // Flag shows - is it necessary to find minimal option amount in case if all options are not required
        $shouldFindMinOption = false;
        if ($searchMin
            && $bundleProduct->getPriceType() == Price::PRICE_TYPE_DYNAMIC
            && !$this->hasRequiredOption($bundleProduct)
        ) {
            $shouldFindMinOption = true;
        }
        $canSkipRequiredOptions = $searchMin && !$shouldFindMinOption;

        $currentPrice = false;
        $priceList = [];
        foreach ($this->getBundleOptions($bundleProduct) as $option) {
            if ($this->canSkipOption($option, $canSkipRequiredOptions)) {
                continue;
            }
            $selectionPriceList = $this->createSelectionPriceList($option, $bundleProduct);
            $selectionPriceList = $this->processOptions($option, $selectionPriceList, $searchMin);

            $lastValue = end($selectionPriceList)->getAmount()->getValue();
            if ($shouldFindMinOption
                && (!$currentPrice || $lastValue < $currentPrice->getAmount()->getValue())
            ) {
                $currentPrice = end($selectionPriceList);
            } elseif (!$shouldFindMinOption) {
                $priceList = array_merge($priceList, $selectionPriceList);
            }
        }
        return $shouldFindMinOption ? [$currentPrice] : $priceList;
    }

    /**
     * Check this option if it should be skipped
     *
     * @param \Magento\Bundle\Model\Option $option
     * @param bool $canSkipRequiredOption
     * @return bool
     */
    protected function canSkipOption($option, $canSkipRequiredOption)
    {
        return !$option->getSelections() || ($canSkipRequiredOption && !$option->getRequired());
    }

    /**
     * Check the bundle product for availability of required options
     *
     * @param Product $bundleProduct
     * @return bool
     */
    protected function hasRequiredOption($bundleProduct)
    {
        $options = array_filter(
            $this->getBundleOptions($bundleProduct),
            function ($item) {
                return $item->getRequired();
            }
        );
        return !empty($options);
    }

    /**
     * Get bundle options
     *
     * @param Product $saleableItem
     * @return \Magento\Bundle\Model\Resource\Option\Collection
     */
    protected function getBundleOptions(Product $saleableItem)
    {
        /** @var BundleOptionPrice $bundlePrice */
        $bundlePrice = $saleableItem->getPriceInfo()->getPrice(BundleOptionPrice::PRICE_CODE);
        return $bundlePrice->getOptions();
    }

    /**
     * Calculate amount for bundle product with all selection prices
     *
     * @param float $basePriceValue
     * @param Product $bundleProduct
     * @param \Magento\Bundle\Pricing\Price\BundleSelectionPrice[] $selectionPriceList
     * @param null|string $exclude
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    public function calculateBundleAmount($basePriceValue, $bundleProduct, $selectionPriceList, $exclude = null)
    {
        if ($bundleProduct->getPriceType() == Price::PRICE_TYPE_FIXED) {
            return $this->calculateFixedBundleAmount($basePriceValue, $bundleProduct, $selectionPriceList, $exclude);
        } else {
            return $this->calculateDynamicBundleAmount($basePriceValue, $bundleProduct, $selectionPriceList, $exclude);
        }
    }

    /**
     * Calculate amount for fixed bundle product
     *
     * @param float $basePriceValue
     * @param Product $bundleProduct
     * @param \Magento\Bundle\Pricing\Price\BundleSelectionPrice[] $selectionPriceList
     * @param null|string $exclude
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    protected function calculateFixedBundleAmount($basePriceValue, $bundleProduct, $selectionPriceList, $exclude)
    {
        $fullAmount = $basePriceValue;
        /** @var $option \Magento\Bundle\Model\Option */
        foreach ($selectionPriceList as $selectionPrice) {
            $fullAmount += $selectionPrice->getValue();
        }
        return $this->calculator->getAmount($fullAmount, $bundleProduct, $exclude);
    }

    /**
     * Calculate amount for dynamic bundle product
     *
     * @param float $basePriceValue
     * @param Product $bundleProduct
     * @param \Magento\Bundle\Pricing\Price\BundleSelectionPrice[] $selectionPriceList
     * @param null|string $exclude
     * @return \Magento\Framework\Pricing\Amount\AmountInterface
     */
    protected function calculateDynamicBundleAmount($basePriceValue, $bundleProduct, $selectionPriceList, $exclude)
    {
        $fullAmount = 0.;
        $adjustments = [];
        $amountList = [$this->calculator->getAmount($basePriceValue, $bundleProduct, $exclude)];
        /** @var $option \Magento\Bundle\Model\Option */
        foreach ($selectionPriceList as $selectionPrice) {
            $amountList[] = $selectionPrice->getAmount();
        }
        /** @var \Magento\Framework\Pricing\Amount\AmountInterface $itemAmount */
        foreach ($amountList as $itemAmount) {
            $fullAmount += $itemAmount->getValue();
            foreach ($itemAmount->getAdjustmentAmounts() as $code => $adjustment) {
                $adjustments[$code] = isset($adjustments[$code]) ? $adjustments[$code] + $adjustment : $adjustment;
            }
        }
        if ($exclude && isset($adjustments[$exclude])) {
            $fullAmount -= $adjustments[$exclude];
            unset($adjustments[$exclude]);
        }
        return $this->amountFactory->create($fullAmount, $adjustments);
    }

    /**
     * Create selection price list for the retrieved options
     *
     * @param \Magento\Bundle\Model\Option $option
     * @param Product $bundleProduct
     * @return \Magento\Bundle\Pricing\Price\BundleSelectionPrice[]
     */
    public function createSelectionPriceList($option, $bundleProduct)
    {
        $priceList = [];
        /* @var $selection \Magento\Bundle\Model\Selection|\Magento\Catalog\Model\Product */
        foreach ($option->getSelections() as $selection) {
            if (!$selection->isSalable()) {
                // @todo CatalogInventory Show out of stock Products
                continue;
            }
            $priceList[] = $this->selectionFactory->create($bundleProduct, $selection, $selection->getSelectionQty());
        }
        return $priceList;
    }

    /**
     * Find minimal or maximal price for existing options
     *
     * @param \Magento\Bundle\Model\Option $option
     * @param \Magento\Bundle\Pricing\Price\BundleSelectionPrice[] $selectionPriceList
     * @param bool $searchMin
     * @return \Magento\Bundle\Pricing\Price\BundleSelectionPrice[]
     */
    public function processOptions($option, $selectionPriceList, $searchMin = true)
    {
        $result = [];
        foreach ($selectionPriceList as $current) {
            $currentValue = $current->getAmount()->getValue();
            if (empty($result)) {
                $result = [$current];
            } elseif ($searchMin && end($result)->getAmount()->getValue() > $currentValue) {
                $result = [$current];
            } elseif (!$searchMin && $option->isMultiSelection()) {
                $result[] = $current;
            } elseif (!$searchMin
                && !$option->isMultiSelection()
                && end($result)->getAmount()->getValue() < $currentValue
            ) {
                $result = [$current];
            }
        }
        return $result;
    }
}
