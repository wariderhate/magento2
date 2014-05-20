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

namespace Magento\ConfigurableProduct\Test\TestCase\Product;

use Mtf\TestCase\Injectable;
use Magento\Catalog\Test\Fixture\Category;
use Magento\ConfigurableProduct\Test\Fixture\CatalogProductConfigurable;
use Magento\Catalog\Test\Page\Product\CatalogProductNew;
use Magento\Catalog\Test\Page\Adminhtml\CatalogProductIndex;

/**
 * Test Coverage for CreateConfigurableProductEntity
 *
 * General Flow:
 * 1. Log in to Backend.
 * 2. Navigate to Products > Catalog.
 * 3. Start to create new product.
 * 4. Fill in data according to data set.
 * 5. Save product.
 * 6. Verify created product.
 *
 * @ticketId MAGETWO-20024
 */
class CreateConfigurableEntityTest extends Injectable
{
    /**
     * @var Category
     */
    protected $category;

    /**
     * @var CatalogProductIndex
     */
    protected $productPageGrid;

    /**
     * @var CatalogProductNew
     */
    protected $newProductPage;

    /**
     * @param Category $category
     * @return array
     */
    public function __prepare(Category $category)
    {
        $category->persist();

        return [
            'category' => $category
        ];
    }

    /**
     * @param Category $category
     * @param CatalogProductIndex $productPageGrid
     * @param CatalogProductNew $newProductPage
     */
    public function __inject(
        Category $category,
        CatalogProductIndex $productPageGrid,
        CatalogProductNew $newProductPage
    ) {
        $this->category = $category;
        $this->productPageGrid = $productPageGrid;
        $this->newProductPage = $newProductPage;
    }

    /**
     * @param CatalogProductConfigurable $configurable
     * @param Category $category
     */
    public function testCreate(CatalogProductConfigurable $configurable, Category $category)
    {
        // Steps
        $this->productPageGrid->open();
        $this->productPageGrid->getProductBlock()->addProduct('configurable');

        $productBlockForm = $this->newProductPage->getConfigurableBlockForm();

        $productBlockForm->setCategory($category);
        $productBlockForm->fill($configurable);
        $productBlockForm->save($configurable);
    }
}
