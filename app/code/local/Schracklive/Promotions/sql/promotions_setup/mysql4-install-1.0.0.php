<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE schrack_promotion (
	`entity_id` 		int(10) unsigned	NOT NULL,
    `name` 				varchar(256) 		NOT NULL,
    `valid_from` 		date 				NOT NULL,
    `valid_to` 			date 				NOT NULL,
    `mailinglist` 		int(10) 			DEFAULT NULL,
    `type` 				varchar(16) 		NOT NULL,
    `is_yearly_kab` 	int(1) 				NOT NULL,
    `image_url` 		varchar(256) 		DEFAULT NULL,
    `typo_snippet_ids` 	varchar(64) 		DEFAULT NULL,
    `order` 			int(10) 			DEFAULT NULL,
    PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    
CREATE TABLE schrack_promotion_account (
   `entity_id` 			int(10) unsigned	NOT NULL AUTO_INCREMENT,
   `account_id`			int(10) unsigned	NOT NULL,
   `promotion_id`		int(10) unsigned	NOT NULL,
   `wws_customer_id` 	varchar(6) 			DEFAULT NULL,
    PRIMARY KEY (`entity_id`),
    KEY `IDX_SCHRACK_PROMOTION_ACCOUNT_ACCOUNT_ID` (`account_id`),
    KEY `IDX_SCHRACK_PROMOTION_ACCOUNT_PROMOTION_ID` (`promotion_id`),
    CONSTRAINT `FK_SCHRACK_PROMOTION_ACCOUNT_ACCOUNT_ID` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_SCHRACK_PROMOTION_ACCOUNT_PROMOTION_ID` FOREIGN KEY (`promotion_id`) REFERENCES `schrack_promotion` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE schrack_promotion_account_customer (
	`entity_id` 			int(10) unsigned 	NOT NULL AUTO_INCREMENT,
	`promotion_account_id`	int(10) unsigned	NOT NULL,
	`customer_id`			int(10) unsigned	NOT NULL,
	`pdf_url`				varchar(256)		DEFAULT NULL,
	PRIMARY KEY (`entity_id`),
    KEY `IDX_SCHRACK_PROMOTION_ACCOUNT_CUSTOMER_CUSTOMER_ID` (`customer_id`),
    KEY `IDX_SCHRACK_PROMOTION_ACCOUNT_CUSTOMER_PROMOTION_ACCOUNT_ID` (`promotion_account_id`),
    CONSTRAINT `FK_SCHRACK_PROMOTION_ACCOUNT_CUSTOMER_PROMOTION_ACCOUNT_ID` FOREIGN KEY (`promotion_account_id`) REFERENCES `schrack_promotion_account` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_SCHRACK_PROMOTION_ACCOUNT_CUSTOMER_CUSTOMER_ID` FOREIGN KEY (`customer_id`) REFERENCES `customer_entity` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE schrack_promotion_product (
	`promotion_id` 		int(10) unsigned	NOT NULL,
	`product_id` 		int(10) unsigned	NOT NULL,
	`order`				int(10) unsigned	NOT NULL,
    PRIMARY KEY (`promotion_id`, `product_id`),
    KEY `IDX_SCHRACK_PROMOTION_PRODUCT_PROMOTION_ID` (`promotion_id`),
    KEY `IDX_SCHRACK_PROMOTION_PRODUCT_PRODUCT_ID` (`product_id`),
    KEY `IDX_SCHRACK_PROMOTION_PRODUCT_ORDER` (`order`),
    CONSTRAINT `FK_SCHRACK_PROMOTION_PRODUCT_PROMOTION_ID` FOREIGN KEY (`promotion_id`) REFERENCES `schrack_promotion` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_SCHRACK_PROMOTION_PRODUCT_PRODUCT_ID` FOREIGN KEY (`product_id`) REFERENCES `catalog_product_entity` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE schrack_promotion_account_product (
	`promotion_account_id`	int(10) unsigned	NOT NULL,
	`product_id` 			int(10) unsigned	NOT NULL,
	`order`					int(10) unsigned	NOT NULL,
    PRIMARY KEY (`promotion_account_id`, `product_id`),
    KEY `IDX_SCHRACK_PROMOTION_ACCOUNT_PRODUCT_PROMOTION_ACCOUNT_ID` (`promotion_account_id`),
    KEY `IDX_SCHRACK_PROMOTION_ACCOUNT_PRODUCT_PRODUCT_ID` (`product_id`),
    KEY `IDX_SCHRACK_PROMOTION_ACCOUNT_PRODUCT_ORDER` (`order`),
    CONSTRAINT `FK_SCHRACK_PROMOTION_ACCOUNT_PRODUCT_PROMOTION_ACCOUNT_ID` FOREIGN KEY (`promotion_account_id`) REFERENCES `schrack_promotion_account` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_SCHRACK_PROMOTION_ACCOUNT_PRODUCT_PRODUCT_ID` FOREIGN KEY (`product_id`) REFERENCES `catalog_product_entity` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();
