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
 * Newsletter subscribe controller
 */
namespace Magento\Newsletter\Controller;

use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Service\V1\CustomerAccountServiceInterface;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Helper\Data as CustomerHelper;

class Subscriber extends \Magento\Framework\App\Action\Action
{
    /**
     * Customer session
     *
     * @var Session
     */
    protected $_customerSession;

    /**
     * Customer Service
     *
     * @var CustomerAccountServiceInterface
     */
    protected $_customerService;

    /**
     * Subscriber factory
     *
     * @var SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CustomerHelper
     */
    protected $_customerHelper;

    /**
     * @param Context $context
     * @param SubscriberFactory $subscriberFactory
     * @param CustomerAccountServiceInterface $customerService
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param CustomerHelper $customerHelper
     */
    public function __construct(
        Context $context,
        SubscriberFactory $subscriberFactory,
        CustomerAccountServiceInterface $customerService,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CustomerHelper $customerHelper
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_customerService = $customerService;
        $this->_customerSession = $customerSession;
        $this->_customerHelper = $customerHelper;
    }

    /**
     * New subscription action
     *
     * @throws \Magento\Framework\Model\Exception
     * @return void
     */
    public function newAction()
    {
        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) {
            $email = (string)$this->getRequest()->getPost('email');

            try {
                $this->validateEmailFormat($email);
                $this->validateGuestSubscription();
                $this->validateEmailAvailable($email);

                $status = $this->_subscriberFactory->create()->subscribe($email);
                if ($status == \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE) {
                    $this->messageManager->addSuccess(__('The confirmation request has been sent.'));
                } else {
                    $this->messageManager->addSuccess(__('Thank you for your subscription.'));
                }
            } catch (\Magento\Framework\Model\Exception $e) {
                $this->messageManager->addException(
                    $e,
                    __('There was a problem with the subscription: %1', $e->getMessage())
                );
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong with the subscription.'));
            }
        }
        $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
    }

    /**
     * Subscription confirm action
     * @return void
     */
    public function confirmAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $code = (string)$this->getRequest()->getParam('code');

        if ($id && $code) {
            /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
            $subscriber = $this->_subscriberFactory->create()->load($id);

            if ($subscriber->getId() && $subscriber->getCode()) {
                if ($subscriber->confirm($code)) {
                    $this->messageManager->addSuccess(__('Your subscription has been confirmed.'));
                } else {
                    $this->messageManager->addError(__('This is an invalid subscription confirmation code.'));
                }
            } else {
                $this->messageManager->addError(__('This is an invalid subscription ID.'));
            }
        }

        $this->getResponse()->setRedirect($this->_storeManager->getStore()->getBaseUrl());
    }

    /**
     * Unsubscribe newsletter
     * @return void
     */
    public function unsubscribeAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $code = (string)$this->getRequest()->getParam('code');

        if ($id && $code) {
            try {
                $this->_subscriberFactory->create()->load($id)->setCheckCode($code)->unsubscribe();
                $this->messageManager->addSuccess(__('You have been unsubscribed.'));
            } catch (\Magento\Framework\Model\Exception $e) {
                $this->messageManager->addException($e, $e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong with the un-subscription.'));
            }
        }
        $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
    }

    /**
     * Validates that the email address isn't being used by a different account.
     *
     * @param string $email
     * @throws \Magento\Framework\Model\Exception
     * @return void
     */
    protected function validateEmailAvailable($email)
    {
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        if ($this->_customerSession->getCustomerDataObject()->getEmail() !== $email
            && !$this->_customerService->isEmailAvailable($email, $websiteId)
        ) {
            throw new \Magento\Framework\Model\Exception(__('This email address is already assigned to another user.'));
        }
    }

    /**
     * Validates that if the current user is a guest, that they can subscribe to a newsletter.
     *
     * @throws \Magento\Framework\Model\Exception
     * @return void
     */
    protected function validateGuestSubscription()
    {
        if ($this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')
                ->getValue(
                    \Magento\Newsletter\Model\Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ) != 1
            && !$this->_customerSession->isLoggedIn()
        ) {
            throw new \Magento\Framework\Model\Exception(
                __(
                    'Sorry, but the administrator denied subscription for guests. '
                    . 'Please <a href="%1">register</a>.',
                    $this->_customerHelper->getRegisterUrl()
                )
            );
        }
    }

    /**
     * Validates the format of the email address
     *
     * @param string $email
     * @throws \Magento\Framework\Model\Exception
     * @return void
     */
    protected function validateEmailFormat($email)
    {
        if (!\Zend_Validate::is($email, 'EmailAddress')) {
            throw new \Magento\Framework\Model\Exception(__('Please enter a valid email address.'));
        }
    }
}
