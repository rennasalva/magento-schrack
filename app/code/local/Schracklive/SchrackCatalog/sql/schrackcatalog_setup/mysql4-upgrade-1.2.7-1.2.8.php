<?php

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

	$configValuesMap = array(
		'schrack/shop/related_products' => '6',
	);

	foreach ($configValuesMap as $configPath=>$configValue) {
	    $installer->setConfigData($configPath, $configValue);
	}

$installer->endSetup(); 

?>
