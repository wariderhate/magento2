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

namespace Magento\Catalog\Test\Repository;

use Mtf\Repository\AbstractRepository;

/**
 * Class CatalogProductSimple
 *
 */
class CatalogProductSimple extends AbstractRepository
{
    public function __construct(array $defaultConfig = [], array $defaultData = [])
    {
        $this->_data['SimpleProduct_sku_516169631'] = [
            'sku' => 'SimpleProduct_sku_516169631',
            'name' => 'SimpleProduct 516169631',
            'type_id' => 'simple',
            'attribute_set_id' => '4',
            'price' => ['value' => 3, 'preset' => '-'],
            'id' => '1',
            'mtf_dataset_name' => 'SimpleProduct_sku_516169631'
        ];

        $this->_data['SimpleProduct_sku_1947585255'] = [
            'sku' => 'SimpleProduct_sku_1947585255',
            'name' => 'SimpleProduct 1947585255',
            'type_id' => 'simple',
            'attribute_set_id' => '4',
            'price' => ['value' => 4, 'preset' => '-'],
            'id' => '2',
            'mtf_dataset_name' => 'SimpleProduct_sku_1947585255'
        ];

        $this->_data['100_dollar_product'] = [
            'sku' => '100_dollar_product',
            'name' => '100_dollar_product',
            'type_id' => 'simple',
            'attribute_set_id' => '4',
            'price' => ['value' => 100, 'preset' => '-'],
            'id' => '2',
            'mtf_dataset_name' => '100_dollar_product'
        ];

        $this->_data['40_dollar_product'] = [
            'sku' => '40_dollar_product',
            'name' => '40_dollar_product',
            'type_id' => 'simple',
            'attribute_set_id' => '4',
            'price' => ['value' => 40, 'preset' => '-'],
            'id' => '2',
            'mtf_dataset_name' => '40_dollar_product'
        ];

        $this->_data['MAGETWO-23036'] = [
            'sku' => 'MAGETWO-23036',
            'name' => 'simple_with_category',
            'type_id' => 'simple',
            'attribute_set_id' => '4',
            'price' => ['value' => 100, 'preset' => 'MAGETWO-23036'],
            'id' => '3',
            'category_ids' => ['presets' => 'default'],
            'mtf_dataset_name' => 'simple_with_category',
        ];
    }
}
