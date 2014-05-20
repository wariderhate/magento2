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
namespace Magento\Catalog\Model\Product;

/**
 * Catalog Product Mass Action processing model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Action extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Index indexer
     *
     * @var \Magento\Index\Model\Indexer
     */
    protected $_indexIndexer;

    /**
     * Product website factory
     *
     * @var \Magento\Catalog\Model\Product\WebsiteFactory
     */
    protected $_productWebsiteFactory;

    /**
     * @var \Magento\Indexer\Model\IndexerInterface
     */
    protected $categoryIndexer;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\Product\WebsiteFactory $productWebsiteFactory
     * @param \Magento\Index\Model\Indexer $indexIndexer
     * @param \Magento\Indexer\Model\IndexerInterface $categoryIndexer
     * @param \Magento\Framework\Model\Resource\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\Db $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Product\WebsiteFactory $productWebsiteFactory,
        \Magento\Index\Model\Indexer $indexIndexer,
        \Magento\Indexer\Model\IndexerInterface $categoryIndexer,
        \Magento\Framework\Model\Resource\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\Db $resourceCollection = null,
        array $data = array()
    ) {
        $this->_productWebsiteFactory = $productWebsiteFactory;
        $this->_indexIndexer = $indexIndexer;
        $this->categoryIndexer = $categoryIndexer;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magento\Catalog\Model\Resource\Product\Action');
    }

    /**
     * Return product category indexer object
     *
     * @return \Magento\Indexer\Model\IndexerInterface
     */
    protected function getCategoryIndexer()
    {
        if (!$this->categoryIndexer->getId()) {
            $this->categoryIndexer->load(\Magento\Catalog\Model\Indexer\Product\Category::INDEXER_ID);
        }
        return $this->categoryIndexer;
    }

    /**
     * Retrieve resource instance wrapper
     *
     * @return \Magento\Catalog\Model\Resource\Product\Action
     */
    protected function _getResource()
    {
        return parent::_getResource();
    }

    /**
     * Update attribute values for entity list per store
     *
     * @param array $productIds
     * @param array $attrData
     * @param int $storeId
     * @return $this
     */
    public function updateAttributes($productIds, $attrData, $storeId)
    {
        $this->_eventManager->dispatch(
            'catalog_product_attribute_update_before',
            array('attributes_data' => &$attrData, 'product_ids' => &$productIds, 'store_id' => &$storeId)
        );

        $this->_getResource()->updateAttributes($productIds, $attrData, $storeId);
        $this->setData(
            array('product_ids' => array_unique($productIds), 'attributes_data' => $attrData, 'store_id' => $storeId)
        );

        // register mass action indexer event
        $this->_indexIndexer->processEntityAction(
            $this,
            \Magento\Catalog\Model\Product::ENTITY,
            \Magento\Index\Model\Event::TYPE_MASS_ACTION
        );
        if (!$this->getCategoryIndexer()->isScheduled()) {
            $this->getCategoryIndexer()->reindexList(array_unique($productIds));
        }
        return $this;
    }

    /**
     * Update websites for product action
     *
     * Allowed types:
     * - add
     * - remove
     *
     * @param array $productIds
     * @param array $websiteIds
     * @param string $type
     * @return void
     */
    public function updateWebsites($productIds, $websiteIds, $type)
    {
        if ($type == 'add') {
            $this->_productWebsiteFactory->create()->addProducts($websiteIds, $productIds);
        } else if ($type == 'remove') {
            $this->_productWebsiteFactory->create()->removeProducts($websiteIds, $productIds);
        }

        $this->setData(
            array('product_ids' => array_unique($productIds), 'website_ids' => $websiteIds, 'action_type' => $type)
        );

        // register mass action indexer event
        $this->_indexIndexer->processEntityAction(
            $this,
            \Magento\Catalog\Model\Product::ENTITY,
            \Magento\Index\Model\Event::TYPE_MASS_ACTION
        );
        if (!$this->getCategoryIndexer()->isScheduled()) {
            $this->getCategoryIndexer()->reindexList(array_unique($productIds));
        }
    }
}
