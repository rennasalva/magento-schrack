<?php
	/* @var $installer Mage_Core_Model_Resource_Setup */
	$resource = Mage::getSingleton('core/resource');
	$readConnection  = $resource->getConnection('core_read');

	$query = "SELECT value FROM core_config_data WHERE path LIKE 'schrack/general/country'";
	$queryResult = $readConnection->query($query);
	foreach ($queryResult as $recordset) {
        $country = strtoupper($recordset['value']);
	}

	$installer = $this;
	$installer->startSetup();

	// Productive-Template-ID's (taken from PROD-DB):
	$configTemplateCountries = array(
		'AT' => '8',
		'CZ' => '8',
		'SI' => '8',
		'BA' => '6',
		'BE' => '6',
		'BG' => '6',
		'CO' => '6',
		'DE' => '6',
		'HU' => '6',
		'RU' => '6',
		'SA' => '6',
		'SK' => '6',
		'PL' => '9',
		'RO' => '11',
		'HR' => '',
		'RS' => '',
	);

	$installer->setConfigData('schrack/shop/paypal_fraud_email_templateid', $configTemplateCountries[$country]);
	// Enable logging by default for the first two weeks after deployment -> Should be disabled on 20.12.2016 !!!:
	$installer->setConfigData('paypal/wpp/intensive_logging_flag', 1);

	$installer->endSetup();
?>
