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

/**
 * Currency controller
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\CurrencySymbol\Controller\Adminhtml\System;

class Currency extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry)
    {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Init currency by currency code from request
     *
     * @return $this
     */
    protected function _initCurrency()
    {
        $code = $this->getRequest()->getParam('currency');
        $currency = $this->_objectManager->create('Magento\Directory\Model\Currency')->load($code);

        $this->_coreRegistry->register('currency', $currency);
        return $this;
    }

    /**
     * Currency management main page
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_title->add(__('Currency Rates'));

        $this->_view->loadLayout();
        $this->_setActiveMenu('Magento_CurrencySymbol::system_currency_rates');
        $this->_addContent(
            $this->_view->getLayout()->createBlock('Magento\CurrencySymbol\Block\Adminhtml\System\Currency')
        );
        $this->_view->renderLayout();
    }

    /**
     * Fetch rates action
     *
     * @return void
     * @throws \Exception|\Magento\Framework\Model\Exception
     */
    public function fetchRatesAction()
    {
        /** @var \Magento\Backend\Model\Session $backendSession */
        $backendSession = $this->_objectManager->get('Magento\Backend\Model\Session');
        try {
            $service = $this->getRequest()->getParam('rate_services');
            $this->_getSession()->setCurrencyRateService($service);
            if (!$service) {
                throw new \Exception(__('Please specify a correct Import Service.'));
            }
            try {
                /** @var \Magento\Directory\Model\Currency\Import\ImportInterface $importModel */
                $importModel = $this->_objectManager->get(
                    'Magento\Directory\Model\Currency\Import\Factory'
                )->create(
                    $service
                );
            } catch (\Exception $e) {
                throw new \Magento\Framework\Model\Exception(__('We can\'t initialize the import model.'));
            }
            $rates = $importModel->fetchRates();
            $errors = $importModel->getMessages();
            if (sizeof($errors) > 0) {
                foreach ($errors as $error) {
                    $this->messageManager->addWarning($error);
                }
                $this->messageManager->addWarning(
                    __('All possible rates were fetched, please click on "Save" to apply')
                );
            } else {
                $this->messageManager->addSuccess(__('All rates were fetched, please click on "Save" to apply'));
            }

            $backendSession->setRates($rates);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $this->_redirect('adminhtml/*/');
    }

    /**
     * Save rates action
     *
     * @return void
     */
    public function saveRatesAction()
    {
        $data = $this->getRequest()->getParam('rate');
        if (is_array($data)) {
            try {
                foreach ($data as $currencyCode => $rate) {
                    foreach ($rate as $currencyTo => $value) {
                        $value = abs($this->_objectManager->get('Magento\Framework\Locale\FormatInterface')->getNumber($value));
                        $data[$currencyCode][$currencyTo] = $value;
                        if ($value == 0) {
                            $this->messageManager->addWarning(
                                __('Please correct the input data for %1 => %2 rate', $currencyCode, $currencyTo)
                            );
                        }
                    }
                }

                $this->_objectManager->create('Magento\Directory\Model\Currency')->saveRates($data);
                $this->messageManager->addSuccess(__('All valid rates have been saved.'));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }

        $this->_redirect('adminhtml/*/');
    }

    /**
     * Check if allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_CurrencySymbol::currency_rates');
    }
}
