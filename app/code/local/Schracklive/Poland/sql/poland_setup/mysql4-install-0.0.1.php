<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$optionValueTable = $this->getTable('eav/attribute_option_value');

$installer->_conn->insert($optionValueTable, array('option_id'=>1, 'store_id'=>1, 'value'=>'Pan'));
$installer->_conn->insert($optionValueTable, array('option_id'=>2, 'store_id'=>1, 'value'=>'Pani'));
