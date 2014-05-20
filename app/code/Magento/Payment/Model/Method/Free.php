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
namespace Magento\Payment\Model\Method;

/**
 * Free payment method
 */
class Free extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * XML Paths for configuration constants
     */
    const XML_PATH_PAYMENT_FREE_ACTIVE = 'payment/free/active';

    const XML_PATH_PAYMENT_FREE_ORDER_STATUS = 'payment/free/order_status';

    const XML_PATH_PAYMENT_FREE_PAYMENT_ACTION = 'payment/free/payment_action';

    /**
     * Payment Method features
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Payment code name
     *
     * @var string
     */
    protected $_code = 'free';

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Construct
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Logger\AdapterFactory $logAdapterFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Logger\AdapterFactory $logAdapterFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = array()
    ) {
        parent::__construct($eventManager, $paymentData, $scopeConfig, $logAdapterFactory, $data);
        $this->_storeManager = $storeManager;
    }

    /**
     * Check whether method is available
     *
     * @param \Magento\Sales\Model\Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return parent::isAvailable(
            $quote
        ) && !empty($quote) && $this->_storeManager->getStore()->roundPrice(
            $quote->getGrandTotal()
        ) == 0;
    }

    /**
     * Get config payment action, do nothing if status is pending
     *
     * @return string|null
     */
    public function getConfigPaymentAction()
    {
        return $this->getConfigData('order_status') == 'pending' ? null : parent::getConfigPaymentAction();
    }
}
