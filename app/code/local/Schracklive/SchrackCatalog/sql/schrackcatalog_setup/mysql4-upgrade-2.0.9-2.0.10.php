<?php
$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('core_url_rewrite'), 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `is_system`');
$installer->endSetup();

?>