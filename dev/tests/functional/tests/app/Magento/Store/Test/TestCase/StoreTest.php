<?php
/**
 * Store test
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

namespace Magento\Store\Test\TestCase;

use Mtf\TestCase\Functional;
use Mtf\Factory\Factory;

class StoreTest extends Functional
{
    /**
     * Login into backend area before test
     */
    protected function setUp()
    {
        Factory::getApp()->magentoBackendLoginUser();
    }

    /**
     * @ZephyrId MAGETWO-12405
     */
    public function testCreateNewLocalizedStoreView()
    {
        $storeFixture = Factory::getFixtureFactory()->getMagentoStoreStore();

        $storeListPage = Factory::getPageFactory()->getAdminSystemStore();
        $storeListPage->open();
        $storeListPage->getPageActionsBlock()->addStoreView();

        $newStorePage = Factory::getPageFactory()->getAdminSystemStoreNewStore();
        $newStorePage->getFormBlock()->fill($storeFixture);
        $newStorePage->getPageActionsBlock()->clickSave();
        $storeListPage->getMessagesBlock()->assertSuccessMessage();
        $this->assertContains(
            'The store view has been saved',
            $storeListPage->getMessagesBlock()->getSuccessMessages()
        );
        $this->assertTrue(
            $storeListPage->getGridBlock()->isStoreExists($storeFixture->getName())
        );

        $cachePage = Factory::getPageFactory()->getAdminCache();
        $cachePage->open();
        $cachePage->getActionsBlock()->flushCacheStorage();
        $cachePage->getMessagesBlock()->assertSuccessMessage();

        $configPage = Factory::getPageFactory()->getAdminSystemConfig();
        $configPage->open();
        $configPage->getPageActions()->selectStore(
            ['Main Website', $storeFixture->getData('fields/group/value'), $storeFixture->getName()]
        );
        $configGroup = $configPage->getForm()->getGroup('Locale Options');
        $configGroup->open();
        $configGroup->setValue('select-groups-locale-fields-code-value', 'German (Germany)');
        $configPage->getPageActions()->save();
        $configPage->getMessagesBlock()->assertSuccessMessage();

        $homePage = Factory::getPageFactory()->getCmsIndexIndex();
        $homePage->open();

        $homePage->getStoreSwitcherBlock()->selectStoreView($storeFixture->getName());
        $this->assertTrue($homePage->getSearchBlock()->isPlaceholderContains('Den gesamten Shop durchsuchen'));
    }
}
