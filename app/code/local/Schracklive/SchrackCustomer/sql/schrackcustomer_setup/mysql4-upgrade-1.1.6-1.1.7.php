<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup  */

$installer->updateAttribute('customer_address', 'lastname', array('is_required'=>false));
