<?php

/**
 * Stub implementation (the real thing is in the WWS module)
 * @see Schracklive_Wws_Helper_SchrackCatalog_Info
 */
class Schracklive_SchrackCatalog_Helper_Info extends Mage_Core_Helper_Abstract {

    private $_productModel   = null;
    private $_stockItemModel = null;
    private $_availibilities = array();
    private $_cableInfoCache = array();

	public function getDrumsBySkusAndStocks ( array $skus, array $stocks ) {
        return array();
    }

    public function preloadProductsInfo($products, $customer = null, $getAll = false, $qtys = array(), $forceAvailibilityRequests = false, $fetchPrices = true, $fetchAvailibilities = true, $fetchDrums = true, $performanceFormkey = '') {
        if ($performanceFormkey) {
            Mage::log('app/code/local/Schracklive/SchrackCatalog/Helper/Info.php -> ' . $performanceFormkey, null, 'performance.log');
        }
        return;
    }

    public function getTierPriceForCustomer(Schracklive_SchrackCatalog_Model_Product $product, $qty, Schracklive_SchrackCustomer_Model_Customer $customer) {
        return $product->getTierPrice($qty);
    }

    public function getBasicTierPriceForCustomer(Schracklive_SchrackCatalog_Model_Product $product, $qty, Schracklive_SchrackCustomer_Model_Customer $customer) {
        return $product->getPrice($qty);
    }

    public function getGraduatedPricesForCustomer(Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer) {
        return array();
    }

	public function getCurrencyForCustomer(Schracklive_SchrackCatalog_Model_Product $product, $qty, Schracklive_SchrackCustomer_Model_Customer $customer) {
		return 'EUR';
	}

    public function getDeliveryQuantity(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId, $stockLocation = null ) {
        return 0;
    }

    public function getPickupQuantity(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId) {
        return 0;
    }

    public function getSurcharge(Schracklive_SchrackCatalog_Model_Product $product) {
        return 0.0;
    }

    public function getAvailableDrums(Schracklive_SchrackCatalog_Model_Product $product, array $warehouseIds, $qty = 1) {
        return array();
    }

    public function getPossibleDrums(Schracklive_SchrackCatalog_Model_Product $product, array $warehouseIds) {
        return array();
    }

    public function getDeliveryState(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId) {
        return 0;
    }

    public function getPickupState(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId) {
        return 0;
    }

    public function getDeliverySalesUnit(Schracklive_SchrackCatalog_Model_Product $product) {
        return 1;
    }

    public function getPickupSalesUnit(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId) {
        return 1;
    }

    public function getSummarizedDeliveryQuantities(Schracklive_SchrackCatalog_Model_Product $product) {
        return 0;
    }
    
    public function getAvailableValidDeliveryStockNumbers(Schracklive_SchrackCatalog_Model_Product $product) {
        return array();
    }
    
    public function hasValidStockQty(Schracklive_SchrackCatalog_Model_Product $product, $stockNo) {
        return false;
    }
    
    public function getSummarizedCustomerQuantities(Schracklive_SchrackCatalog_Model_Product $product, $pickupWarehouseId) {
        return 0;
    }
    
    public function getDeliveryHours(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId) {
        return 0;
    }

    public function getPromotionSKUs () {
        return array();
    }

    public function getRegularPrice ( Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer ) {
        return 0;
    }

    public function getPromoValidTo ( Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer ) {
        return null;
    }

    protected function _preloadAvailabilityInfo($skus, $warehouses) {
        $res = array();
        foreach ( $skus as $sku ) {
            if ( ! isset($this->_availibilities[$sku]) ) {
                $this->_fetchAvailibilities4sku($sku);
            }
            $res[$sku] = array();
            foreach ( $this->_availibilities[$sku] as $stockNumber => $stockInfo ) {
                if ( !isset($warehouses) || in_array(0, $warehouses) || array_search($stockNumber,$warehouses) !== false ) {
                    $res[$sku][$stockNumber] = $stockInfo;
                }
            }
        }
        return $res;
    }
    
    private function _fetchAvailibilities4sku ( $sku ) {
        $this->_availibilities[$sku] = array();

        if ( ! isset($this->_productModel) ) {
            $this->_productModel = Mage::getModel('catalog/product'); 
        }
        if ( ! isset($this->_stockItemModel) ) {
            $this->_stockItemModel = Mage::getModel('cataloginventory/stock_item');
        }

        $productId = $this->_productModel->getIdBySku($sku);
        $stockItemCollection = $this->_stockItemModel->getCollection();
        $stockItemCollection->addFieldToFilter('product_id',$productId);
        $stockItemCollection->getSelect()->join(array('t2' => 'cataloginventory_stock'),'main_table.stock_id = t2.stock_id','t2.*');

        foreach( $stockItemCollection as $stockItem ) {
            $haznbrod = $stockItem->getIsPickup();
            $stockNumber = $stockItem->getStockNumber();
            $stockLocation = $stockItem->getStockLocation();
            if ( intval($stockNumber) > 0 ) {
                $this->_availibilities[$sku][$stockNumber] = array(
                    'qty' => $stockItem->getQty(),
                    'stockLocation' => $stockLocation
                );
                if ( $stockItem->getIsPickup()) {
                    $this->_availibilities[$sku][$stockNumber]['pickup'] = array(
                        'salesunit' => $stockItem->getPickupSalesUnit()
                    );
                }
                if ( $stockItem->getIsDelivery() ) {
                    $this->_availibilities[$sku][$stockNumber]['delivery'] = array( 
                        'salesunit' => $stockItem->getDeliverySalesUnit(),
                        'deliveryHours' => $stockItem->getDeliveryHours()
                    );
                }
            }
        }
    }

    public function saveFetchedAvailabilityInfo ( $info ) {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $writeConnection = $resource->getConnection('core_write');
        $stockHelper = Mage::helper('schrackcataloginventory/stock');

        foreach ( $info as $sku => $warehouses ) {
            $stocks = $stockHelper->getAllStocks($sku);
            $sql = "SELECT entity_id FROM catalog_product_entity WHERE sku = '$sku';";
            $prodId = $readConnection->fetchOne($sql);
            foreach ( $warehouses as $whNo => $availability ) {
                if ( ! isset($stocks[$whNo]) ) {
                    continue;
                }
                $qty = $availability['qty'];
                $whId = $stocks[$whNo]->getId();
                /*
                $sql =  "UPDATE cataloginventory_stock_item item"
                     . " INNER JOIN cataloginventory_stock stock ON item.stock_id = stock.stock_id"
                     . " INNER JOIN catalog_product_entity prod ON item.product_id = prod.entity_id"
                     . " SET qty = (CASE WHEN qty <> $qty THEN $qty ELSE qty END)"
                     . " WHERE prod.sku = '$sku' AND stock.stock_number = $whNo;";
                */
                $sql =  "UPDATE cataloginventory_stock_item"
                     . " SET qty = (CASE WHEN qty <> $qty THEN $qty ELSE qty END)"
                     . " WHERE product_id = $prodId AND stock_id = $whId;";

                $writeConnection->query($sql);
            }
        }
    }

    // TODO: remove preloadForcedAvailabilityInfo() after ajax reconstruction
    public function preloadForcedAvailabilityInfo ( $sku ) {
        return;
    }


    protected function getCableInfos ( $products ) {
        $skuCables = array();
        $cableInfosToLoadSkus = array();
		foreach ($products as $product) {
            if ( $product instanceof Schracklive_SchrackCatalog_Model_Product ) {
                $sku = $product->getSku();
                $this->_cableInfoCache[$sku] = $skuCables[$sku] = $product->isCable();
            } else {
                if ( is_object($product) ) {
    				$sku = $product->getSku();
                } else {
                    $sku = (string)$product;
                }
                if ( isset($this->_cableInfoCache[$sku]) ) {
                    $skuCables[$sku] = $this->_cableInfoCache[$sku];
                } else {
                    $cableInfosToLoadSkus[] = $sku;
                }
            }
        }
		if ( count($cableInfosToLoadSkus) > 0 ) {
		    $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		    $sql = " SELECT sku, schrack_is_cable FROM catalog_product_entity"
                 . " WHERE sku IN ('" . implode("','",$cableInfosToLoadSkus) . "');";
		    $dbRes = $readConnection->fetchAll($sql);
		    foreach ( $dbRes as $row ) {
		        $sku = $row['sku'];
		        $this->_cableInfoCache[$sku] = $skuCables[$sku] = (int) $row['schrack_is_cable'];
            }
        }
	    return $skuCables;
    }
}
