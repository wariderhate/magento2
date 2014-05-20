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
namespace Magento\Framework\Connect\Channel;

use Magento\Framework\Connect\Validator;

class VO implements \Iterator
{
    /**
     * @var Validator
     */
    private $_validator = null;

    /**
     * @var array
     */
    protected $properties = array('name' => '', 'uri' => '', 'summary' => '');

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
     * @return mixed
     */
    public function key()
    {
        return key($this->properties);
    }

    /**
     * @return mixed
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
     * @param string|null $value
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
        return array('channel' => $this->properties);
    }

    /**
     * @param array $arr
     * @return void
     */
    public function fromArray(array $arr)
    {
        foreach ($arr as $k => $v) {
            $this->{$k} = $v;
        }
    }

    /**
     * @return Validator
     */
    private function validator()
    {
        if (is_null($this->_validator)) {
            $this->_validator = new Validator();
        }
        return $this->_validator;
    }

    /**
     * Stub for validation result
     *
     * @return bool
     */
    public function validate()
    {
        $v = $this->validator();
        if (!$v->validatePackageName($this->name)) {
            return false;
        }
        return true;
    }
}
