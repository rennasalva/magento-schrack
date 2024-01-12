<?php
/**
 * Anowave Google Tag Manager Enhanced Ecommerce (UA) Tracking
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2016 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */
 
$installer = $this;
 
$installer->startSetup();

$sql = array();

$sql[] = "SET foreign_key_checks = 0";

$sql[] = "CREATE TABLE IF NOT EXISTS " . Mage::getConfig()->getTablePrefix() . "anowave_ab (ab_id int(6) NOT NULL AUTO_INCREMENT,ab_experiment varchar(255) DEFAULT NULL,ab_experiment_theme varchar(255) DEFAULT NULL,PRIMARY KEY (ab_id)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
$sql[] = "CREATE TABLE IF NOT EXISTS " . Mage::getConfig()->getTablePrefix() . "anowave_ab_store (ab_primary_id int(6) NOT NULL AUTO_INCREMENT,ab_id int(6) NOT NULL,ab_store_id int(6) NOT NULL,PRIMARY KEY (ab_primary_id),UNIQUE KEY ab_id (ab_id,ab_store_id),CONSTRAINT anowave_ab_store_ibfk_1 FOREIGN KEY (ab_id) REFERENCES  " . Mage::getConfig()->getTablePrefix() . "anowave_ab (ab_id) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
$sql[] = "CREATE TABLE IF NOT EXISTS " . Mage::getConfig()->getTablePrefix() . "anowave_ab_data (data_id bigint(21) NOT NULL AUTO_INCREMENT,data_ab_id int(6) DEFAULT NULL,data_product_id int(10) unsigned NOT NULL,data_attribute_code varchar(255) DEFAULT NULL,data_attribute_content text,PRIMARY KEY (data_id),KEY data_product_id (data_product_id),KEY data_ab_id (data_ab_id),CONSTRAINT anowave_ab_data_ibfk_1 FOREIGN KEY (data_product_id) REFERENCES " . Mage::getConfig()->getTablePrefix() . "catalog_product_entity (entity_id) ON DELETE CASCADE ON UPDATE CASCADE,CONSTRAINT anowave_ab_data_ibfk_2 FOREIGN KEY (data_ab_id) REFERENCES " . Mage::getConfig()->getTablePrefix() . "anowave_ab (ab_id) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";

$sql[] = "SET foreign_key_checks = 1";

foreach ($sql as $query)
{
	$installer->run($query);
}

$installer->endSetup();