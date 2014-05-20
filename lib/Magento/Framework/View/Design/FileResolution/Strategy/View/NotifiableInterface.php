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
namespace Magento\Framework\View\Design\FileResolution\Strategy\View;

use Magento\Framework\View\Design\FileResolution\Strategy\Fallback\CachingProxy;
use Magento\Framework\View\Design\ThemeInterface;

/**
 * Notifiable Interface
 *
 * Interface for a view strategy to be notifiable, that file location has changed
 */
interface NotifiableInterface
{
    /**
     * Set view file path to map
     *
     * Notify the strategy, that file has changed its location, and next time should be resolved to this
     * new location.
     *
     * @param string $area
     * @param ThemeInterface $themeModel
     * @param string $locale
     * @param string|null $module
     * @param string $file
     * @param string $newFilePath
     * @return CachingProxy
     */
    public function setViewFilePathToMap($area, ThemeInterface $themeModel, $locale, $module, $file, $newFilePath);
}
