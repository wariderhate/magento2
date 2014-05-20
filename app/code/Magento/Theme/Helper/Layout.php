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
namespace Magento\Theme\Helper;

class Layout extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

    /**
     * @var \Magento\Theme\Model\Layout\Config
     */
    protected $_config;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Theme\Model\Layout\Config $config
     * @param \Magento\Framework\View\LayoutInterface $layout
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Theme\Model\Layout\Config $config,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->_layout = $layout;
        $this->_config = $config;
        parent::__construct($context);
    }

    /**
     * Apply page layout handle
     *
     * @param string $pageLayout
     * @return \Magento\Theme\Helper\Layout
     */
    public function applyHandle($pageLayout)
    {
        $pageLayout = $this->_config->getPageLayout($pageLayout);

        if (!$pageLayout) {
            return $this;
        }

        $this->_layout->getUpdate()->addHandle($pageLayout->getLayoutHandle());

        return $this;
    }

    /**
     * Apply page layout template
     * (for old design packages)
     *
     * @param string $pageLayout
     * @return \Magento\Theme\Helper\Layout
     */
    public function applyTemplate($pageLayout = null)
    {
        if ($pageLayout === null) {
            $pageLayout = $this->getCurrentPageLayout();
        } else {
            $pageLayout = $this->_config->getPageLayout($pageLayout);
        }

        if (!$pageLayout) {
            return $this;
        }

        if ($this->_layout->getBlock('root') && !$this->_layout->getBlock('root')->getIsHandle()) {
            // If not applied handle
            $this->_layout->getBlock('root')->setTemplate($pageLayout->getTemplate());
        }

        return $this;
    }

    /**
     * Retrieve current applied page layout
     *
     * @return \Magento\Framework\Object|boolean
     */
    public function getCurrentPageLayout()
    {
        if ($this->_layout->getBlock('root') && $this->_layout->getBlock('root')->getLayoutCode()) {
            return $this->_config->getPageLayout($this->_layout->getBlock('root')->getLayoutCode());
        }

        // All loaded handles
        $handles = $this->_layout->getUpdate()->getHandles();
        // Handles used in page layouts
        $pageLayoutHandles = $this->_config->getPageLayoutHandles();
        // Applied page layout handles
        $appliedHandles = array_intersect($handles, $pageLayoutHandles);

        if (empty($appliedHandles)) {
            return false;
        }

        $currentHandle = array_pop($appliedHandles);

        $layoutCode = array_search($currentHandle, $pageLayoutHandles, true);

        return $this->_config->getPageLayout($layoutCode);
    }
}
