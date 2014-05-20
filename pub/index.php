<?php
/**
 * Public alias for the application entry point
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
require __DIR__ . '/../app/bootstrap.php';

use Magento\Framework\App\Filesystem;

$params = $_SERVER;

$params[Filesystem::PARAM_APP_DIRS][Filesystem::PUB_DIR] = array('uri' => '');
$params[Filesystem::PARAM_APP_DIRS][Filesystem::PUB_LIB_DIR] = array('uri' => 'lib');
$params[Filesystem::PARAM_APP_DIRS][Filesystem::MEDIA_DIR] = array('uri' => 'media');
$params[Filesystem::PARAM_APP_DIRS][Filesystem::STATIC_VIEW_DIR] = array('uri' => 'static');
$params[Filesystem::PARAM_APP_DIRS][Filesystem::PUB_VIEW_CACHE_DIR] = array('uri' => 'cache');
$params[Filesystem::PARAM_APP_DIRS][Filesystem::UPLOAD_DIR] = array('uri' => 'media/upload');
$entryPoint = new \Magento\Framework\App\EntryPoint\EntryPoint(BP, $params);
$entryPoint->run('Magento\Framework\App\Http');
