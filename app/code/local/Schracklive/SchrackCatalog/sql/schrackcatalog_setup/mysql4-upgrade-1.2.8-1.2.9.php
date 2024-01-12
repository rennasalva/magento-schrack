<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

	$configValuesMap = array(
		'schrack/shop/product_attribute_exclude_codes' => 'schrack_spec_kabel',
	);

	foreach ($configValuesMap as $configPath=>$configValue) {
	    $installer->setConfigData($configPath, $configValue);
	}

$installer->endSetup(); 

?>
