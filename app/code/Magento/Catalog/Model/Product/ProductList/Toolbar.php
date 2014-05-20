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
namespace Magento\Catalog\Model\Product\ProductList;

/**
 * Class Toolbar
 */
class Toolbar
{
    /**
     * GET parameter page variable name
     */
    const PAGE_PARM_NAME = 'p';

    /**
     * Sort order cookie name
     */
    const ORDER_COOKIE_NAME = 'product_list_order';

    /**
     * Sort direction cookie name
     */
    const DIRECTION_COOKIE_NAME = 'product_list_dir';

    /**
     * Sort mode cookie name
     */
    const MODE_COOKIE_NAME = 'product_list_mode';

    /**
     * Products per page limit order cookie name
     */
    const LIMIT_COOKIE_NAME = 'product_list_limit';

    /**
     * Cookie
     *
     * @var \Magento\Framework\Stdlib\Cookie
     */
    protected $cookie;

    /**
     * Request
     *
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @param \Magento\Framework\Stdlib\Cookie $cookie
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Framework\Stdlib\Cookie $cookie,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->cookie = $cookie;
        $this->request = $request;
    }

    /**
     * Get sort order
     *
     * @return string|bool
     */
    public function getOrder()
    {
        return $this->cookie->get(self::ORDER_COOKIE_NAME);
    }

    /**
     * Get sort direction
     *
     * @return string|bool
     */
    public function getDirection()
    {
        return $this->cookie->get(self::DIRECTION_COOKIE_NAME);
    }

    /**
     * Get sort mode
     *
     * @return string|bool
     */
    public function getMode()
    {
        return $this->cookie->get(self::MODE_COOKIE_NAME);
    }

    /**
     * Get products per page limit
     *
     * @return string|bool
     */
    public function getLimit()
    {
        return $this->cookie->get(self::LIMIT_COOKIE_NAME);
    }
    /**
     * Return current page from request
     *
     * @return int
     */
    public function getCurrentPage()
    {
        $page = (int) $this->request->getParam(self::PAGE_PARM_NAME);
        return $page ? $page : 1;
    }
}
