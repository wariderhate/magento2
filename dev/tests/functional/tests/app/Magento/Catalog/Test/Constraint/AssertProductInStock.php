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

namespace Magento\Catalog\Test\Constraint;

use Mtf\Constraint\AbstractConstraint;
use Magento\Catalog\Test\Page\Product\CatalogProductView;
use Magento\Catalog\Test\Fixture\CatalogProductSimple;

/**
 * Class AssertProductInStock
 *
 */
class AssertProductInStock extends AbstractConstraint
{
    /**
     * Constraint severeness
     *
     * @var string
     */
    protected $severeness = 'low';

    /**
     * Text value for checking Stock Availability
     */
    const STOCK_AVAILABILITY = 'In stock';

    /**
     * Assert that In Stock status is displayed on product page
     *
     * @param CatalogProductView $catalogProductView
     * @param CatalogProductSimple $product
     * @return void
     */
    public function processAssert(CatalogProductView $catalogProductView, CatalogProductSimple $product)
    {
        $catalogProductView->init($product);
        $catalogProductView->open();
        \PHPUnit_Framework_Assert::assertEquals(
            self::STOCK_AVAILABILITY,
            $catalogProductView->getViewBlock()->stockAvailability(),
            'Control \'' . self::STOCK_AVAILABILITY . '\' is not visible.'
        );
    }

    /**
     * Text of In Stock assertion
     *
     * @return string
     */
    public function toString()
    {
        return 'In stock control is visible.';
    }
}
