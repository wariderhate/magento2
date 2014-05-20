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
namespace Magento\Downloadable\Test\Fixture;

use Mtf\Factory\Factory;
use Magento\Catalog\Test\Fixture\Product;

class DownloadableProduct extends Product
{
    const GROUP = 'downloadable_information';

    const LINK_IS_SHAREABLE_NO_VALUE = 0;
    const LINK_IS_SHAREABLE_YES_VALUE = 1;
    const LINK_IS_SHAREABLE_USE_CONFIG_VALUE = 2;

    /**
     * {inheritdoc}
     */
    protected function _initData()
    {
        parent::_initData();
        $this->_dataConfig = array(
            'constraint' => 'Success',
            'grid_filter' => array('name'),
            'create_url_params' => array(
                'type' => 'downloadable',
                'set' => static::DEFAULT_ATTRIBUTE_SET_ID,
            ),
            'input_prefix' => 'product'
        );

        $data = array(
            'is_virtual' => ['value' => '', 'group' => null], // needed for CURL handler
            'price' => [
                'value' => '1',
                'group' => static::GROUP_PRODUCT_DETAILS
            ],
            'downloadable_link_purchase_type' => [
                'value' => 'Yes',
                'input_value' => '1',
                'group' => static::GROUP,
                'input' => 'select',
                'input_name' => 'links_purchased_separately'
            ],
        );

        $this->_data['fields'] = $data + $this->_data['fields'];

        $this->_repository = Factory::getRepositoryFactory()
            ->getMagentoDownloadableDownloadableProduct($this->_dataConfig, $this->_data);
    }

    /**
     * Create product
     */
    public function persist()
    {
        $id = Factory::getApp()->magentoDownloadableCreateDownloadable($this);
        $this->_data['fields']['id']['value'] = $id;
    }
}
