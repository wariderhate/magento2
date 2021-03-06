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

namespace Magento\User\Test\Page\Backend;

use Mtf\Page\Page;
use Mtf\Factory\Factory;
use Mtf\Client\Element\Locator;

/**
 * Class UserEdit
 *
 */
class UserEdit extends Page
{
    /**
     * URL for new admin user
     */
    const MCA = 'admin/user/edit/user_id/';

    /**
     * Form for admin user creation
     *
     * @var string
     */
    protected $editFormBlock = 'page:main-container';

    /**
     * Global messages block
     *
     * @var string
     */
    protected $messagesBlock = '#messages .messages';

    /**
     * Role Grid Block
     * var @string
     */
    protected $roleGridBlock = 'permissionsUserRolesGrid';

    /**
     * Grid page actions block
     *
     * @var string
     */
    protected $pageActionsBlock = '.page-main-actions';

    /**
     * Custom constructor
     */
    protected function _init()
    {
        $this->_url = $_ENV['app_backend_url'] . self::MCA;
    }

    /**
     * Get form for admin user creation/edit
     *
     * @return \Magento\User\Test\Block\User\Edit\Form
     */
    public function getEditFormBlock()
    {
        return Factory::getBlockFactory()->getMagentoUserUserEditForm(
            $this->_browser->find($this->editFormBlock, Locator::SELECTOR_ID)
        );
    }

    /**
     * Get global messages block
     *
     * @return \Magento\Core\Test\Block\Messages
     */
    public function getMessagesBlock()
    {
        return Factory::getBlockFactory()->getMagentoCoreMessages(
            $this->_browser->find($this->messagesBlock)
        );
    }

    /**
     * Get Role grid
     *
     * @return \Magento\User\Test\Block\User\Edit\Tab\Roles
     */
    public function getRoleGridBlock()
    {
        return Factory::getBlockFactory()->getMagentoUserUserEditTabRoles(
            $this->_browser->find($this->roleGridBlock, Locator::SELECTOR_ID)
        );
    }

    /**
     * Get Grid page actions block
     *
     * @return \Magento\User\Test\Block\Backend\UserEditPageActions
     */
    public function getPageActionsBlock()
    {
        return Factory::getBlockFactory()->getMagentoUserBackendUserEditPageActions(
            $this->_browser->find($this->pageActionsBlock)
        );
    }
}
