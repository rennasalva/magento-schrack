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
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Unserialize
 * @package     Unserialize_Reader
 * @copyright  Copyright (c) 2006-2018 Magento, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Class Amasty_Unserialize_Reader_Null
 */
class Amasty_Unserialize_Reader_Null
{
    /**
     * @var int
     */
    protected $_status;

    /**
     * @var string
     */
    protected $_value;

    const NULL_VALUE = 'null';

    const READING_VALUE = 1;

    /**
     * @param string $char
     * @param string $prevChar
     * @return string|null
     */
    public function read($char, $prevChar)
    {
        if ($prevChar == Amasty_Unserialize_Parser::SYMBOL_SEMICOLON) {
            $this->_value = self::NULL_VALUE;
            $this->_status = self::READING_VALUE;
            return null;
        }

        if ($this->_status == self::READING_VALUE && $char == Amasty_Unserialize_Parser::SYMBOL_SEMICOLON) {
            return $this->_value;
        }
        return null;
    }
}
