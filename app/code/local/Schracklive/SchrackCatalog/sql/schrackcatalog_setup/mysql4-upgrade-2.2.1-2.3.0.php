<?php

/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();
$productTableName = $this->getTable('catalog_product_entity');

$installer->run("
    CREATE TABLE schrack_redirect_rename (
        entity_id           int(10) unsigned	NOT NULL AUTO_INCREMENT,
        old_name            varchar(255)        NOT NULL,
        new_name            varchar(255)        NOT NULL,
        created_at    		timestamp           NOT NULL DEFAULT CURRENT_TIMESTAMP,
        category_schrack_id varchar(255)        DEFAULT NULL,
        product_sku         varchar(64)         DEFAULT NULL,
        children_created    int(1)              NOT NULL DEFAULT 0,        
        PRIMARY KEY (entity_id),
        KEY IDX_SCHRACK_REDIRECT_CREATED_AT (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE schrack_redirect_rename_url_to_ids (
        entity_id           int(10) unsigned	NOT NULL AUTO_INCREMENT,
        rename_id           int(10) unsigned	NOT NULL,
        request_path        varchar(255)        NOT NULL,
        category_schrack_id varchar(255)        DEFAULT NULL,
        product_id          int(10) unsigned	DEFAULT NULL,
        PRIMARY KEY (entity_id),
        CONSTRAINT FK_SCHRACK_REDIRECT_RENAME_URL_TO_IDS_RENAME_ID FOREIGN KEY (rename_id) REFERENCES schrack_redirect_rename (entity_id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    CREATE TABLE schrack_core_url_rewrite_copy (
       url_rewrite_id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Rewrite Id',
       store_id smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Store Id',
       category_id int(10) unsigned DEFAULT NULL COMMENT 'Category Id',
       product_id int(10) unsigned DEFAULT NULL COMMENT 'Product Id',
       id_path varchar(255) DEFAULT NULL COMMENT 'Id Path',
       request_path varchar(255) DEFAULT NULL COMMENT 'Request Path',
       target_path varchar(255) DEFAULT NULL COMMENT 'Target Path',
       is_system smallint(5) unsigned DEFAULT '1' COMMENT 'Defines is Rewrite System',
       created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
       options varchar(255) DEFAULT NULL COMMENT 'Options',
       description varchar(255) DEFAULT NULL COMMENT 'Deascription',
       PRIMARY KEY (url_rewrite_id),
       UNIQUE KEY UNQ_SCHRACK_CORE_URL_REWRITE_COPY_REQUEST_PATH_STORE_ID (request_path,store_id),
       KEY IDX_SCHRACK_CORE_URL_REWRITE_COPY_TARGET_PATH_STORE_ID (target_path,store_id),
       KEY IDX_SCHRACK_CORE_URL_REWRITE_COPY_STORE_ID (store_id),
       KEY FK_SCHRACK_CORE_URL_REWRITE_COPY_PRODUCT_ID (product_id),
       KEY FK_SCHRACK_CORE_URL_REWRITE_COPY_CTGR_ID_CAT_CTGR_ENTT_ENTT_ID (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Url Rewrites';
");

$installer->endSetup();

