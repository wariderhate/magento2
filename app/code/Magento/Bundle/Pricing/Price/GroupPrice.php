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

namespace Magento\Bundle\Pricing\Price;

use Magento\Customer\Model\Session;

/**
 * Bundle droup price model
 */
class GroupPrice extends \Magento\Catalog\Pricing\Price\GroupPrice
{
    /**
     * Price type group
     */
    const PRICE_CODE = 'group_price';

    /**
     * @return float|bool
     */
    public function getValue()
    {
        if ($this->value !== null) {
            return $this->value;
        }

        $groupPrice = parent::getValue();
        if ($groupPrice) {
            $basePrice = $this->getBasePrice();
            $this->value = $basePrice - $basePrice * ($groupPrice / 100);
        } else {
            $this->value = false;
        }
        return $this->value;
    }

    /**
     * @param null|float $qty
     * @return bool|float
     */
    protected function getBasePrice($qty = null)
    {
        return $this->priceInfo
            ->getPrice(\Magento\Catalog\Pricing\Price\BasePrice::PRICE_CODE)
            ->getValue();
    }
}
