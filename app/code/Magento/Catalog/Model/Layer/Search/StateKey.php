<?php
/**
 *
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

namespace Magento\Catalog\Model\Layer\Search;


class StateKey extends \Magento\Catalog\Model\Layer\Category\StateKey
{
    /**
     * @var \Magento\CatalogSearch\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\CatalogSearch\Helper\Data $helper
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\CatalogSearch\Helper\Data $helper
    ) {
        $this->helper = $helper;
        parent::__construct($storeManager, $customerSession);
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return string|void
     */
    public function toString($category)
    {
        return 'Q_' . $this->helper->getQuery()->getId() . '_' . parent::toString($category);
    }
}
