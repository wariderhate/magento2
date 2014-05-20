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
namespace Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Super;

/**
 * @magentoAppArea adminhtml
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @magentoAppIsolation enabled
     */
    public function testGetSelectedAttributesForSimpleProductType()
    {
        /** @var $objectManager \Magento\TestFramework\ObjectManager */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $objectManager->get(
            'Magento\Framework\Registry'
        )->register(
            'current_product',
            $objectManager->create('Magento\Catalog\Model\Product')
        );
        /** @var $block \Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Super\Config */
        $block = $objectManager->get(
            'Magento\Framework\View\LayoutInterface'
        )->createBlock(
            'Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Super\Config'
        );
        $this->assertEquals(array(), $block->getSelectedAttributes());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     */
    public function testGetSelectedAttributesForConfigurableProductType()
    {
        /** @var $objectManager \Magento\TestFramework\ObjectManager */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $objectManager->get(
            'Magento\Framework\Registry'
        )->register(
            'current_product',
            $objectManager->create('Magento\Catalog\Model\Product')->load(1)
        );
        $objectManager->get('Magento\Framework\View\LayoutInterface')
            ->createBlock('Magento\Framework\View\Element\Text', 'head');
        $usedAttribute = $objectManager->get(
            'Magento\Catalog\Model\Entity\Attribute'
        )->loadByCode(
            \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
                'Magento\Eav\Model\Config'
            )->getEntityType(
                'catalog_product'
            )->getId(),
            'test_configurable'
        );
        /** @var $block \Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Super\Config */
        $block = $objectManager->get(
            'Magento\Framework\View\LayoutInterface'
        )->createBlock(
            'Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Super\Config'
        );
        $selectedAttributes = $block->getSelectedAttributes();
        $this->assertEquals(array($usedAttribute->getId()), array_keys($selectedAttributes));
        $selectedAttribute = reset($selectedAttributes);
        $this->assertEquals('test_configurable', $selectedAttribute->getAttributeCode());
    }
}
