<?php
/**
 * Scheduled jobs entry point
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
 * @copyright  Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
use Magento\Store\Model\StoreManager;

require dirname(__DIR__) . '/app/bootstrap.php';
umask(0);
$params = array(StoreManager::PARAM_RUN_CODE => 'admin', \Magento\Store\Model\Store::CUSTOM_ENTRY_POINT_PARAM => true);
$entryPoint = new \Magento\Framework\App\EntryPoint\EntryPoint(BP, $params);
$entryPoint->run('Magento\Framework\App\Cron', array('parameters' => array('group::')));
