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
 * Block for authors
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Connect\Block\Adminhtml\Extension\Custom\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;

class Authors extends \Magento\Connect\Block\Adminhtml\Extension\Custom\Edit\Tab\AbstractTab
{
    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Connect\Model\Session $session
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Connect\Model\Session $session,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $data = array()
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        parent::__construct($context, $registry, $formFactory, $session, $data);
    }

    /**
     * Get Tab Label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Authors');
    }

    /**
     * Get Tab Title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Authors');
    }

    /**
     * Return add author button html
     *
     * @return string
     */
    public function getAddAuthorButtonHtml()
    {
        return $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setType(
            'button'
        )->setClass(
            'add'
        )->setLabel(
            __('Add Author')
        )->setOnClick(
            'addAuthor()'
        )->toHtml();
    }

    /**
     * Return array of authors
     *
     * @return string[]
     */
    public function getAuthors()
    {
        $authors = array();
        if ($this->getData('authors')) {
            $temp = array();
            foreach ($this->getData('authors') as $param => $values) {
                if (is_array($values)) {
                    foreach ($values as $key => $value) {
                        $temp[$key][$param] = $value;
                    }
                }
            }
            foreach ($temp as $key => $value) {
                $authors[$key] = $this->_jsonEncoder->encode($value);
            }
        }
        return $authors;
    }
}
