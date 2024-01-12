<?php

$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$tableName = $installer->getTable('cataloginventory/stock');
$connection->addColumn($tableName,'stock_number', 'SMALLINT(3) NOT NULL UNIQUE DEFAULT \'0\''); 
$connection->addColumn($tableName,'is_pickup', 'TINYINT(1) NOT NULL DEFAULT \'0\'');
$connection->addColumn($tableName,'is_delivery', 'TINYINT(1) NOT NULL DEFAULT \'0\'');
$connection->addColumn($tableName,'delivery_hours', 'TINYINT(3) NOT NULL DEFAULT \'0\'');
$connection->addColumn($tableName,'xml_address', 'TEXT');

$tableName = $installer->getTable('cataloginventory/stock_item');
$connection->addColumn($tableName,'pickup_sales_unit', 'DECIMAL(5,2) NOT NULL DEFAULT \'1\'');
$connection->addColumn($tableName,'pickup_icon_state', 'TINYINT(1) NOT NULL DEFAULT \'1\'');
$connection->addColumn($tableName,'delivery_sales_unit', 'DECIMAL(5,2) NOT NULL DEFAULT \'1\'');
$connection->addColumn($tableName,'delivery_icon_state', 'TINYINT(1) NOT NULL DEFAULT \'1\'');
$connection->addColumn($tableName,'is_valid', 'TINYINT(1) NOT NULL DEFAULT \'1\'');
$connection->addColumn($tableName,'is_on_request', 'TINYINT(1) NOT NULL DEFAULT \'0\'');

$installer->endSetup(); // switch on foreign key check

$storeModel = Mage::getModel('cataloginventory/stock');

$pickupData = array();
$deliveryData = array();

$selectCoreConfig = $connection->select()
                ->from('core_config_data')
                ->where("path like 'carriers/schrack%'");

foreach ( $connection->fetchAll($selectCoreConfig) as $row ) {
    $path = $row['path'];
    $value = $row['value'];
    if ( substr($path,0,22) == 'carriers/schrackpickup' && is_numeric(substr($path,-1)) ) {
        $number = substr($path,-2);
        if ( ! is_numeric($number) ) {
            $number = substr($path,-1);
            $subpath = substr($path,0,strlen($path)-1);
        }
        else {
            $subpath = substr($path,0,strlen($path)-2);
        }
        if ( substr($subpath,-2) == 'id' ) {
            $pickupData[$number]['id'] = $value;
        } else if ( substr($subpath,-4) == 'name' ) {
            $pickupData[$number]['name'] = $value;
        } else if ( substr($subpath,-7) == 'address' ) {
            $pickupData[$number]['address'] = $value;
        }

    } else if ( substr($path,0,24) == 'carriers/schrackdelivery' ) {
        if ( substr($path,-2) == 'id' ) {
            $deliveryData['id'] = $value;
        } else if ( substr($path,-4) == 'name' ) {
            $deliveryData['name'] = $value;
        }
    }
}

foreach ( $pickupData as $num => $data ) {
    $id = $data['id'];
    $storeModel->setData(array()); // clear attributes
    $storeModel->setStockNumber($id);
    $storeModel->setStockName($data['name']);
    $storeModel->setIsPickup(1);
    $storeModel->setDeliveryHours(0);
    $storeModel->setXmlAddress($data['address']);
    if ( isset($deliveryData) && $deliveryData['id'] == $id ) {
        $storeModel->setIsDelivery(1);
        $deliveryData = null;
    }
    $storeModel->save();
}

if ( isset($deliveryData) ) {
    $storeModel->setData(array()); // clear attributes
    $storeModel->setStockNumber($deliveryData['id']);
    $storeModel->setStockName($deliveryData['name']);
    $storeModel->setDeliveryHours(48);
    $storeModel->setIsDelivery(1);
    $storeModel->save();
}

if ( $deliveryData['id'] != 80 ) {
    $storeModel->setData(array()); // clear attributes
    $storeModel->setStockNumber(80);
    $storeModel->setStockName('Central Delivery Stock Guntramsdorf, Austria');
    $storeModel->setDeliveryHours(72);
    $storeModel->setIsDelivery(1);
    $storeModel->save();
}

$storeModel->setData(array()); // clear attributes
$storeModel->setStockNumber(999);
$storeModel->setStockName('3rd Party Stock');
$storeModel->setDeliveryHours(96);
$storeModel->setIsDelivery(1);
$storeModel->save();

