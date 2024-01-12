<?php

$installer = $this;
/* @var $installer Mage_Sales_Model_Entity_Setup */
$installer->startSetup();

/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

$tableNameOrder              = $installer->getTable('sales/order');
$tableNameOrderItem          = $installer->getTable('sales/order_item');
$tableNameShipment           = $installer->getTable('sales/shipment');
$tableNameShipmentItem       = $installer->getTable('sales/shipment_item');
$tableNameInvoice            = $installer->getTable('sales/invoice');
$tableNameInvoiceItem        = $installer->getTable('sales/invoice_item');
$tableNameCreditMemo         = $installer->getTable('sales/creditmemo');
$tableNameCreditMemoItem     = $installer->getTable('sales/creditmemo_item');
$tableNameOrderIndex         = $installer->getTable('schracksales/order_index');
$tableNameOrderIndexPosition = $installer->getTable('schracksales/order_index_position');

//     ALTER TABLE {$tableNameOrder}      DROP COLUMN schrack_tax_total;

$installer->run("
    ALTER TABLE {$tableNameOrder}      ADD schrack_wws_status            VARCHAR(3) DEFAULT '' NOT NULL AFTER schrack_wws_order_number;
    ALTER TABLE {$tableNameOrder}      ADD schrack_is_complete           INT(1) DEFAULT 1 NOT NULL AFTER schrack_wws_status;
    ALTER TABLE {$tableNameOrder}      ADD schrack_wws_offer_number      VARCHAR(20) AFTER schrack_is_complete;
    ALTER TABLE {$tableNameOrder}      ADD schrack_wws_reference         VARCHAR(40) AFTER schrack_wws_offer_number;
");
$installer->run("
    ALTER TABLE {$tableNameOrderItem}  ADD schrack_position              INT(4) UNSIGNED DEFAULT NULL AFTER schrack_row_total_surcharge;
");
$installer->run("
    ALTER TABLE {$tableNameShipment}   ADD schrack_wws_shipment_number   VARCHAR(20);
    ALTER TABLE {$tableNameShipment}   ADD schrack_wws_order_number      VARCHAR(9);
    ALTER TABLE {$tableNameShipment}   ADD schrack_wws_reference         VARCHAR(40);
");
$installer->run("
    ALTER TABLE {$tableNameShipmentItem}  ADD schrack_position              INT(4) UNSIGNED DEFAULT NULL;
");
$installer->run("
    ALTER TABLE {$tableNameInvoice}    ADD schrack_wws_invoice_number    VARCHAR(20);
    ALTER TABLE {$tableNameInvoice}    ADD schrack_wws_order_number      VARCHAR(9);
    ALTER TABLE {$tableNameInvoice}    ADD schrack_is_collective_doc     INT(1) DEFAULT 0 NOT NULL AFTER schrack_wws_order_number;
    ALTER TABLE {$tableNameInvoice}    ADD schrack_wws_reference         VARCHAR(40);
");
$installer->run("
    ALTER TABLE {$tableNameInvoiceItem}  ADD schrack_position              INT(4) UNSIGNED DEFAULT NULL;
");
$installer->run("
    ALTER TABLE {$tableNameCreditMemo} ADD schrack_wws_creditmemo_number VARCHAR(20);
    ALTER TABLE {$tableNameCreditMemo} ADD schrack_wws_order_number      VARCHAR(9);
    ALTER TABLE {$tableNameCreditMemo} ADD schrack_is_collective_doc     INT(1) DEFAULT 0 NOT NULL AFTER schrack_wws_order_number;
    ALTER TABLE {$tableNameCreditMemo} ADD schrack_wws_reference         VARCHAR(40);
");
$installer->run("
    ALTER TABLE {$tableNameCreditMemoItem}  ADD schrack_position              INT(4) UNSIGNED DEFAULT NULL;
");
$installer->run("
    CREATE TABLE {$tableNameOrderIndex} (
    `entity_id`              INT(10)     UNSIGNED NOT NULL AUTO_INCREMENT ,
    `wws_customer_id`        VARCHAR(6)  NOT NULL ,
    `wws_document_number`    VARCHAR(10) DEFAULT NULL ,
    `order_id`               INT(10)     UNSIGNED NOT NULL ,
    `shipment_id`            INT(10)     UNSIGNED DEFAULT NULL ,
    `invoice_id`             INT(10)     UNSIGNED DEFAULT NULL ,
    `credit_memo_id`         INT(10)     UNSIGNED DEFAULT NULL ,
    `is_offer`               INT(1)      UNSIGNED NOT NULL DEFAULT 0 ,
    `is_order_confirmation`  INT(1)      UNSIGNED NOT NULL DEFAULT 0 ,
    `is_processing`          INT(1)      UNSIGNED NOT NULL DEFAULT 0 ,
    `document_date_time`     DATETIME    DEFAULT NULL ,
    PRIMARY KEY (`entity_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
      
    CREATE  TABLE {$tableNameOrderIndexPosition} (
    `entity_id`          INT(10)      UNSIGNED NOT NULL AUTO_INCREMENT ,
    `parent_id`          INT(10)      UNSIGNED NOT NULL ,
    `position`           INT(4)       UNSIGNED NOT NULL ,
    `position_level`     INT(4)       UNSIGNED DEFAULT NULL ,
    `position_level_num` INT(4)       UNSIGNED DEFAULT NULL ,
    `sku`                VARCHAR(64)  NOT NULL ,
    `description`        VARCHAR(255) NOT NULL ,
    PRIMARY KEY (`entity_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->run("
    UPDATE {$tableNameOrder} SET schrack_wws_status = 'La1' WHERE status = 'schrack_offered';
    UPDATE {$tableNameOrder} SET schrack_wws_status = 'La1' WHERE status = 'pending_payment';
    UPDATE {$tableNameOrder} SET schrack_wws_status = 'La2' WHERE status = 'pending';
    UPDATE {$tableNameOrder} SET schrack_wws_status = 'La3' WHERE status = 'processing';    
");
    
$installer->run("
    INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', '0', 'schrack/mdoc/wsdl', 'http://sevierd3:8091/webshop2mdoc/soap?wsdl');
    INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', '0', 'schrackdev/mdoc/log', '1');
");
    
$installer->endSetup();

/* make sure we have the same setup as all other order fields */
$installer->addAttribute('order', 'schrack_wws_status', array('type'=>'static','required'=>false,'label'=>'WWS Status'));
$installer->addAttribute('order', 'schrack_is_complete', array('type'=>'static','required'=>false,'label'=>'Is Complete'));
$installer->addAttribute('order', 'schrack_wws_offer_number', array('type'=>'static','required'=>false,'label'=>'WWS Offer Number'));
$installer->addAttribute('order', 'schrack_wws_reference', array('type'=>'static','required'=>false,'label'=>'WWS Reference'));
$installer->removeAttribute('order', 'schrack_tax_total');

/* Just 4 creating test data
 * 
$orderModel = Mage::getModel('sales/order');
$orderItemModel = Mage::getModel('sales/order_item');
$orderCollection = $orderModel->getCollection();
$orderCollection->addFieldToFilter('schrack_wws_customer_id','777777');
$orderCollection->getSelect();
foreach(  $orderCollection as $order ) { 
    $custID = $order->getSchrackWwsCustomerId();
    $orderNum = $order->getSchrackWwsOrderNumber();
    $orderId = $order->getEntityId();
    $isOffer = $order->getStatus() === 'schrack_offered' ? 1 : 0;
    $indexModel = Mage::getModel('schracksales/order_index');
    $indexModel->setWwsCustomerId($custID);
    $indexModel->setWwsDocumentNumber($orderNum);
    $indexModel->setOrderId($orderId);
    $indexModel->setIsOffer($isOffer);
    $indexModel->save();
    $itemCollection = $orderItemModel->getCollection();
    $itemCollection->addFieldToFilter('order_id',$orderId);
    $itemCollection->getSelect();
    foreach ( $itemCollection as $item ) {
        $sku = $item->getSku();
        $name = $item->getName();
        $indexPositionModel = Mage::getModel('schracksales/order_index_position');
        $indexPositionModel->setParentId($orderId);
        $indexPositionModel->setSku($sku);
        $indexPositionModel->setDescription($name);
        $indexPositionModel->save();
    }
}
 */

?>
