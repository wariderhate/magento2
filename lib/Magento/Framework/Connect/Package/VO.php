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
namespace Magento\Framework\Connect\Package;

class VO implements \Iterator
{
    /**
     * @var array
     */
    protected $properties = array(
        'name' => '',
        'package_type_vesrion' => '',
        'cahnnel' => '',
        'extends' => '',
        'summary' => '',
        'description' => '',
        'authors' => '',
        'date' => '',
        'time' => '',
        'version' => '',
        'stability' => 'dev',
        'license' => '',
        'license_uri' => '',
        'contents' => '',
        'compatible' => '',
        'hotfix' => ''
    );

    /**
     * @return void
     */
    public function rewind()
    {
        reset($this->properties);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return current($this->properties) !== false;
    }

    /**
     * @return string
     */
    public function key()
    {
        return key($this->properties);
    }

    /**
     * @return string
     */
    public function current()
    {
        return current($this->properties);
    }

    /**
     * @return void
     */
    public function next()
    {
        next($this->properties);
    }

    /**
     * @param string $var
     * @return null|string
     */
    public function __get($var)
    {
        if (isset($this->properties[$var])) {
            return $this->properties[$var];
        }
        return null;
    }

    /**
     * @param string $var
     * @param null|string $value
     * @return void
     */
    public function __set($var, $value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if (isset($this->properties[$var])) {
            if ($value === null) {
                $value = '';
            }
            $this->properties[$var] = $value;
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->properties;
    }
}
