<?php
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();
// $value = Mage::getStoreConfig('schrack/mobile/article_email_template');
$connection = $installer->getConnection();
$result_set = $connection->select()
			->from('core_config_data')
			->where("path like 'schrack/mobile/article_email_template'")
			->limit(1);
$path = null;
if( $result_set ) {
	foreach ( $connection->fetchAll($result_set) as $row ) {
		$path = $row['path'];
		$value = $row['value'];
	}
}
if( !$value ) {
	$configValuesMap = array(
		'schrack/mobile/article_email_template' =>
		'schrack_mobile_article_email_template',
	);
	foreach ($configValuesMap as $configPath=>$configValue) {
	    $installer->setConfigData($configPath, $configValue);
	}	
}
$installer->endSetup();
?>
