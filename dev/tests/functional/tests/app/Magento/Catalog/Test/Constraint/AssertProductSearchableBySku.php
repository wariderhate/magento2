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
use Magento\CatalogSearch\Test\Page\CatalogsearchResult;
use Magento\Cms\Test\Page\CmsIndex;
use Magento\Catalog\Test\Fixture\CatalogProductSimple;

/**
 * Class AssertProductSearchableBySku
 *
 */
class AssertProductSearchableBySku extends AbstractConstraint
{
    /**
     * Constraint severeness
     *
     * @var string
     */
    protected $severeness = 'low';

    /**
     * Assert that product can be searched via Quick Search using searchable product attributes (Search by SKU).
     *
     * @param CatalogsearchResult $catalogSearchResult
     * @param CmsIndex $cmsIndex
     * @param CatalogProductSimple $product
     * @return void
     */
    public function processAssert(
        CatalogsearchResult $catalogSearchResult,
        CmsIndex $cmsIndex,
        CatalogProductSimple $product
    ) {
        $cmsIndex->open();
        $cmsIndex->getSearchBlock()->search($product->getSku());
        \PHPUnit_Framework_Assert::assertTrue(
            $catalogSearchResult->getListProductBlock()->isProductVisible($product->getName()),
            'Product was not found by SKU.'
        );
    }

    /**
     * Text of Searchable assert
     *
     * @return string
     */
    public function toString()
    {
        return "Product is searchable by SKU.";
    }
}
