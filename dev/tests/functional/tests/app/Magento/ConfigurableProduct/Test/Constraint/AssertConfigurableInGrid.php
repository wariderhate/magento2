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

namespace Magento\ConfigurableProduct\Test\Constraint;

use Mtf\Constraint\AbstractConstraint;
use Magento\ConfigurableProduct\Test\Fixture\CatalogProductConfigurable;
use Magento\Catalog\Test\Page\Adminhtml\CatalogProductIndex;

/**
 * Class AssertConfigurableInGrid
 *
 */
class AssertConfigurableInGrid extends AbstractConstraint
{
    /**
     * Constraint severeness
     *
     * @var string
     */
    protected $severeness = 'high';

    /**
     * Assert product availability in Products Grid
     *
     * @param CatalogProductConfigurable $configurable
     * @param CatalogProductIndex $productPageGrid
     * @return void
     */
    public function processAssert(CatalogProductConfigurable $configurable, CatalogProductIndex $productPageGrid)
    {
        $filter = ['sku' => $configurable->getSku()];
        $productPageGrid->open();
        \PHPUnit_Framework_Assert::assertTrue(
            $productPageGrid->getProductGrid()->isRowVisible($filter),
            'Product with sku \'' . $configurable->getSku() . '\' is absent in Products grid.'
        );
    }

    /**
     * @inheritdoc
     */
    public function toString()
    {
        return 'Product is present in Products grid.';
    }
}
