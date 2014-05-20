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
namespace Magento\Customer\Block\Adminhtml\Edit\Tab;

use Magento\Catalog\Model\Product;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Directory\Model\Currency;

/**
 * Adminhtml customer orders grid block
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Cart extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\Data\CollectionFactory
     */
    protected $_dataCollectionFactory;

    /**
     * @var \Magento\Sales\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var string
     */
    protected $_parentTemplate;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Sales\Model\QuoteFactory $quoteFactory
     * @param \Magento\Framework\Data\CollectionFactory $dataCollectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Sales\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\Data\CollectionFactory $dataCollectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = array()
    ) {
        $this->_dataCollectionFactory = $dataCollectionFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_quoteFactory = $quoteFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setUseAjax(true);
        $this->_parentTemplate = $this->getTemplate();
        $this->setTemplate('tab/cart.phtml');
    }

    /**
     * Prepare grid
     *
     * @return void
     */
    protected function _prepareGrid()
    {
        $this->setId('customer_cart_grid' . $this->getWebsiteId());
        parent::_prepareGrid();
    }

    /**
     * Prepare collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $customerId = $this->getCustomerId();
        $storeIds = $this->_storeManager->getWebsite($this->getWebsiteId())->getStoreIds();

        $quote = $this->_quoteFactory->create()->setSharedStoreIds($storeIds)->loadByCustomer($customerId);

        if ($quote) {
            $collection = $quote->getItemsCollection(false);
        } else {
            $collection = $this->_dataCollectionFactory->create();
        }

        $collection->addFieldToFilter('parent_item_id', array('null' => true));

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array('header' => __('ID'), 'index' => 'product_id', 'width' => '100px'));

        $this->addColumn(
            'name',
            array(
                'header' => __('Product'),
                'index' => 'name',
                'renderer' => 'Magento\Customer\Block\Adminhtml\Edit\Tab\View\Grid\Renderer\Item'
            )
        );

        $this->addColumn('sku', array('header' => __('SKU'), 'index' => 'sku', 'width' => '100px'));

        $this->addColumn(
            'qty',
            array('header' => __('Quantity'), 'index' => 'qty', 'type' => 'number', 'width' => '60px')
        );

        $this->addColumn(
            'price',
            array(
                'header' => __('Price'),
                'index' => 'price',
                'type' => 'currency',
                'currency_code' => (string)$this->_scopeConfig->getValue(
                    Currency::XML_PATH_CURRENCY_BASE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )
        );

        $this->addColumn(
            'total',
            array(
                'header' => __('Total'),
                'index' => 'row_total',
                'type' => 'currency',
                'currency_code' => (string)$this->_scopeConfig->getValue(
                    Currency::XML_PATH_CURRENCY_BASE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )
        );

        $this->addColumn(
            'action',
            array(
                'header' => __('Action'),
                'index' => 'quote_item_id',
                'renderer' => 'Magento\Customer\Block\Adminhtml\Grid\Renderer\Multiaction',
                'filter' => false,
                'sortable' => false,
                'actions' => array(
                    array(
                        'caption' => __('Configure'),
                        'url' => 'javascript:void(0)',
                        'process' => 'configurable',
                        'control_object' => $this->getJsObjectName() . 'cartControl'
                    ),
                    array(
                        'caption' => __('Delete'),
                        'url' => '#',
                        'onclick' => 'return ' . $this->getJsObjectName() . 'cartControl.removeItem($item_id);'
                    )
                )
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * Gets customer assigned to this block
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getGridUrl()
    {
        return $this->getUrl('customer/*/cart', array('_current' => true, 'website_id' => $this->getWebsiteId()));
    }

    /**
     * Gets grid parent html
     *
     * @return string
     */
    public function getGridParentHtml()
    {
        $templateName = $this->_viewFileSystem->getFilename($this->_parentTemplate, array('_relative' => true));
        return $this->fetchView($templateName);
    }

    /**
     * {@inheritdoc}
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('catalog/product/edit', array('id' => $row->getProductId()));
    }
}
