<?php
/**
 * Unit test for model \Magento\User\Model\User
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

/**
 * Test class for \Magento\User\Model\User testing
 */
namespace Magento\User\Model;

class UserTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\User\Model\User */
    protected $_model;

    /** @var \Magento\User\Helper\Data */
    protected $_userData;

    /** @var \Magento\Core\Helper\Data */
    protected $_coreData;

    /** @var \Magento\Framework\Mail\Template\TransportBuilder|PHPUnit_Framework_MockObject_MockObject */
    protected $_transportBuilderMock;

    /** @var \Magento\Framework\Model\Context|PHPUnit_Framework_MockObject_MockObject */
    protected $_contextMock;

    /** @var \Magento\User\Model\Resource\User|PHPUnit_Framework_MockObject_MockObject */
    protected $_resourceMock;

    /** @var \Magento\Framework\Data\Collection\Db|PHPUnit_Framework_MockObject_MockObject */
    protected $_collectionMock;

    /** @var \Magento\Framework\Mail\TransportInterface|PHPUnit_Framework_MockObject_MockObject */
    protected $_transportMock;

    /** @var \Magento\Store\Model\StoreManagerInterface|PHPUnit_Framework_MockObject_MockObject */
    protected $_storeManagerMock;

    /** @var \Magento\Store\Model\Store|PHPUnit_Framework_MockObject_MockObject */
    protected $_storetMock;

    /** @var \Magento\Backend\App\ConfigInterface */
    protected $_configMock;

    /**
     * Set required values
     */
    protected function setUp()
    {
        $this->_userData = $this->getMockBuilder(
            'Magento\User\Helper\Data'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();
        $this->_coreData = $this->getMockBuilder(
            'Magento\Core\Helper\Data'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();
        $this->_contextMock = $this->getMockBuilder(
            'Magento\Framework\Model\Context'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();
        $this->_resourceMock = $this->getMockBuilder(
            'Magento\User\Model\Resource\User'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();
        $this->_collectionMock = $this->getMockBuilder(
            'Magento\Framework\Data\Collection\Db'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();
        $coreRegistry = $this->getMockBuilder(
            'Magento\Framework\Registry'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();
        $eventManagerMock = $this->getMockBuilder(
            'Magento\Framework\Event\ManagerInterface'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();
        $objectFactoryMock = $this->getMockBuilder(
            'Magento\Framework\Validator\ObjectFactory'
        )->disableOriginalConstructor()->setMethods(
            array('create')
        )->getMock();
        $roleFactoryMock = $this->getMockBuilder(
            'Magento\User\Model\RoleFactory'
        )->disableOriginalConstructor()->setMethods(
            array('create')
        )->getMock();
        $this->_transportMock = $this->getMockBuilder(
            'Magento\Framework\Mail\TransportInterface'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();
        $this->_transportBuilderMock = $this->getMockBuilder(
            '\Magento\Framework\Mail\Template\TransportBuilder'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();
        $this->_storetMock = $this->getMockBuilder(
            '\Magento\Store\Model\Store'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();
        $this->_storeManagerMock = $this->getMockBuilder(
            '\Magento\Store\Model\StoreManagerInterface'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();

        $this->_configMock = $this->getMockBuilder(
            '\Magento\Backend\App\ConfigInterface'
        )->disableOriginalConstructor()->setMethods(
            array()
        )->getMock();

        $helper = new \Magento\TestFramework\Helper\ObjectManager($this);
        $this->_model = $helper->getObject(
            'Magento\User\Model\User',
            array(
                'eventManager' => $eventManagerMock,
                'userData' => $this->_userData,
                'coreData' => $this->_coreData,
                'context' => $this->_contextMock,
                'registry' => $coreRegistry,
                'resource' => $this->_resourceMock,
                'resourceCollection' => $this->_collectionMock,
                'validatorObjectFactory' => $objectFactoryMock,
                'roleFactory' => $roleFactoryMock,
                'transportBuilder' => $this->_transportBuilderMock,
                'storeManager' => $this->_storeManagerMock,
                'config' => $this->_configMock
            )
        );
    }

    public function testSendPasswordResetNotificationEmail()
    {
        $storeId = 0;
        $email = 'test@example.com';
        $firstName = 'Foo';
        $lastName = 'Bar';

        $this->_model->setEmail($email);
        $this->_model->setFirstname($firstName);
        $this->_model->setLastname($lastName);

        $this->_configMock->expects(
            $this->at(0)
        )->method(
            'getValue'
        )->with(
            \Magento\User\Model\User::XML_PATH_RESET_PASSWORD_TEMPLATE
        )->will(
            $this->returnValue('templateId')
        );
        $this->_configMock->expects(
            $this->at(1)
        )->method(
            'getValue'
        )->with(
            \Magento\User\Model\User::XML_PATH_FORGOT_EMAIL_IDENTITY
        )->will(
            $this->returnValue('sender')
        );
        $this->_transportBuilderMock->expects($this->once())->method('setTemplateOptions')->will($this->returnSelf());
        $this->_transportBuilderMock->expects(
            $this->once()
        )->method(
            'setTemplateVars'
        )->with(
            array('user' => $this->_model, 'store' => $this->_storetMock)
        )->will(
            $this->returnSelf()
        );
        $this->_transportBuilderMock->expects(
            $this->once()
        )->method(
            'addTo'
        )->with(
            $this->equalTo($email),
            $this->equalTo($firstName . ' ' . $lastName)
        )->will(
            $this->returnSelf()
        );
        $this->_transportBuilderMock->expects(
            $this->once()
        )->method(
            'setFrom'
        )->with(
            'sender'
        )->will(
            $this->returnSelf()
        );
        $this->_transportBuilderMock->expects(
            $this->once()
        )->method(
            'setTemplateIdentifier'
        )->with(
            'templateId'
        )->will(
            $this->returnSelf()
        );
        $this->_transportBuilderMock->expects(
            $this->once()
        )->method(
            'getTransport'
        )->will(
            $this->returnValue($this->_transportMock)
        );
        $this->_transportMock->expects($this->once())->method('sendMessage');

        $this->_storeManagerMock->expects(
            $this->once()
        )->method(
            'getStore'
        )->with(
            $storeId
        )->will(
            $this->returnValue($this->_storetMock)
        );


        $this->_model->sendPasswordResetNotificationEmail();
    }

    public function testSendPasswordResetConfirmationEmail()
    {
        $storeId = 0;
        $email = 'test@example.com';
        $firstName = 'Foo';
        $lastName = 'Bar';

        $this->_model->setEmail($email);
        $this->_model->setFirstname($firstName);
        $this->_model->setLastname($lastName);

        $this->_configMock->expects(
            $this->at(0)
        )->method(
            'getValue'
        )->with(
            \Magento\User\Model\User::XML_PATH_FORGOT_EMAIL_TEMPLATE
        )->will(
            $this->returnValue('templateId')
        );
        $this->_configMock->expects(
            $this->at(1)
        )->method(
            'getValue'
        )->with(
            \Magento\User\Model\User::XML_PATH_FORGOT_EMAIL_IDENTITY
        )->will(
            $this->returnValue('sender')
        );
        $this->_transportBuilderMock->expects($this->once())->method('setTemplateOptions')->will($this->returnSelf());
        $this->_transportBuilderMock->expects(
            $this->once()
        )->method(
            'setTemplateVars'
        )->with(
            array('user' => $this->_model, 'store' => $this->_storetMock)
        )->will(
            $this->returnSelf()
        );
        $this->_transportBuilderMock->expects(
            $this->once()
        )->method(
            'addTo'
        )->with(
            $this->equalTo($email),
            $this->equalTo($firstName . ' ' . $lastName)
        )->will(
            $this->returnSelf()
        );
        $this->_transportBuilderMock->expects(
            $this->once()
        )->method(
            'setFrom'
        )->with(
            'sender'
        )->will(
            $this->returnSelf()
        );
        $this->_transportBuilderMock->expects(
            $this->once()
        )->method(
            'setTemplateIdentifier'
        )->with(
            'templateId'
        )->will(
            $this->returnSelf()
        );
        $this->_transportBuilderMock->expects(
            $this->once()
        )->method(
            'getTransport'
        )->will(
            $this->returnValue($this->_transportMock)
        );
        $this->_transportMock->expects($this->once())->method('sendMessage');

        $this->_storeManagerMock->expects(
            $this->once()
        )->method(
            'getStore'
        )->with(
            $storeId
        )->will(
            $this->returnValue($this->_storetMock)
        );


        $this->_model->sendPasswordResetConfirmationEmail();
    }
}
