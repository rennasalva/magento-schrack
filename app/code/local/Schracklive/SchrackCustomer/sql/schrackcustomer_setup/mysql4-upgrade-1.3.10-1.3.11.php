<?php //

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('schrackcustomer/tracking')};
");

$conn = Mage::getSingleton('core/resource')->getConnection('common_db');

$conn->query("
DROP TABLE IF EXISTS {$this->getTable('schrackcustomer/tracking')};
");

$trackingTable = $conn->newTable($installer->getTable('schrackcustomer/tracking'))
        ->addColumn('tracking_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
                'identity'  => true,
                'auto_increment' => true,
                'nullable'  => false,
                'primary'   => true,
                'unsigned'  => true,
            ), 'Primary Key ID')
        ->addColumn('session_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
                'nullable'  => true,
                'default' => null,
            ))
        ->addColumn('country_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 2, array(
                'nullable'  => false,
            ))
        ->addColumn('schrack_wws_customer_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 6, array(
                'nullable'  => true,
                'unsigned'  => true,
                'default'   => null,
            ))
        ->addColumn('schrack_wws_contact_number', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
                'nullable'  => true,
                'default'   => null,
            ))
        ->addColumn('sku', Varien_Db_Ddl_Table::TYPE_VARCHAR, 64, array(
                'nullable'  => false,
            ))
        ->addColumn('cnt', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
                'nullable'  => false,
                'default'   => 1,
            ))
        ->addIndex('IDX_CUSTOMERTRACKING_SESSION_ID', array('session_id'))
        ->addIndex('IDX_CUSTOMERTRACKING_SKU', array('country_id', 'schrack_wws_customer_id', 'schrack_wws_contact_number', 'sku')) // NOT unique - otherwise we can never drop the old session ids
        /*
        ->addForeignKey('FK_CUSTOMERTRACKING_CUSTOMER_ID', 'schrack_wws_customer_id', $installer->getTable('customer_entity'), 'schrack_wws_customer_id', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey('FK_CUSTOMERTRACKING_SKU', 'sku', $installer->getTable('catalog/product'), 'sku', Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
         * aaargl, we cannot add those foreign keys because the keys in the parent tables are non-unique
         */
;        
$conn->createTable($trackingTable);
$conn->changeColumn($installer->getTable('schrackcustomer/tracking'), 'tracking_id', 'tracking_id', 'INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT'); // Duh! Varien_Db_Ddl_Table HAS an option for auto-increment, but the Mysql adapter does not heed it
$conn->addColumn($installer->getTable('schrackcustomer/tracking'), 'created_at', 'datetime default NULL AFTER sku'); // duh, there is no datetime in Varien_Db_Ddl_Table



// "common" db
$conn->query(<<<EOF
CREATE TABLE `login_token` (
  `email` varchar(255) NOT NULL,
  `country_id` varchar(2) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `token_created` datetime DEFAULT NULL,
  PRIMARY KEY (`email`,`country_id`),
  KEY `token_created` (`token_created`),
  KEY `token` (`token`)
) ENGINE=InnoDB
EOF
);

// "local" db
$installer->run(<<<EOF
 CREATE TRIGGER loginswitch_insert_customer BEFORE INSERT ON customer_entity 
     FOR EACH ROW 
        INSERT INTO magento_common.login_token (email, country_id) VALUES(new.email, (select value from core_config_data where path='schrack/general/country'));
EOF
);


$installer->endSetup(); 

