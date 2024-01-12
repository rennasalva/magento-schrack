<?php

class Schracklive_SchrackCatalogInventory_Helper_Stock extends Mage_Core_Helper_Abstract {
    
    const CACHE_TIME_SECONDS   = 120;
    const CACHE_KEY            = 'SchrackCatalogInventory_Helper_Stock_Data';
    const LOCAL_DELIVERY_KEY   = 'localDeliveryStock';
    const FOREIGN_DELIVERY_KEY = 'foreignDeliveryStock';
    const THIRD_PARTY_KEY      = 'thirdPartyStock';
    const PICKUP_KEY           = 'pickupStocks';

    private $_data = null;
    private $_number2stockMap = null;
    
    public function __construct() {
        /* DL: temporary removement of caching, because of ongoing problems in mobile handler (!)... 
        $cache = Mage::getSingleton('core/cache');
        $ser = $cache->load(self::CACHE_KEY);
        if ( $ser ) {
            $this->_data = unserialize($ser);
        }
        else {
         */
            $this->_data = array();
            $this->_data[self::PICKUP_KEY] = array();
            $this->_number2stockMap = array();
            $model = Mage::getModel('cataloginventory/stock');
            $collection = $model->getCollection();
            foreach( $collection as $stock ) { 
                $number = $stock->getStockNumber();
                $this->_number2stockMap[$number] = $stock;
                $location = $stock->getStockLocation();
                $isDelivery = $stock->getIsDelivery();
                $isPickup =  $stock->getIsPickup();
                if ( $number <= 1 ) {
                    continue;
                }
                else if ( $number == 999 ) {
                    $this->_data[self::THIRD_PARTY_KEY][$location] = $stock;
                } 
                else if ( $isDelivery ) {
                    if ( $number == 80 ) {
                        $this->_data[self::FOREIGN_DELIVERY_KEY] = $stock;
                    } 
                    else {
                        $this->_data[self::LOCAL_DELIVERY_KEY] = $stock;
                    }
                }
                if ( $isPickup ) {
                    $this->_data[self::PICKUP_KEY][$number] = $stock;
                }
            }
            if ( isset($this->_data[self::FOREIGN_DELIVERY_KEY]) && ! isset($this->_data[self::LOCAL_DELIVERY_KEY]) ) {
                $this->_data[self::LOCAL_DELIVERY_KEY] = $this->_data[self::FOREIGN_DELIVERY_KEY];
                $this->_data[self::FOREIGN_DELIVERY_KEY] = null;
            }
            /*
            $ser = serialize($this->_data);
            $cache->save($ser,self::CACHE_KEY,array(),self::CACHE_TIME_SECONDS);
        }
             */
    }
    
    public function getLocalDeliveryStock () {
        $res = $this->_data[self::LOCAL_DELIVERY_KEY];
        return $res;
    }

    public function hasForeignDeliveryStock () {
        return isset($this->_data[self::FOREIGN_DELIVERY_KEY]);
    }

    public function getForeignDeliveryStock () {
        $res = $this->_data[self::FOREIGN_DELIVERY_KEY];
        return $res;
    }

    public function getThirdPartyDeliveryStocks () {
        $res = $this->_data[self::THIRD_PARTY_KEY];
        return $res;
    }

    public function getThirdPartyDeliveryStockNumber () {
        return 999;
    }

    public function getPickupStocks () {
        $res = $this->_data[self::PICKUP_KEY];
        return $res;
    }

    public function hasPickupStocks () {
        return count($this->getPickupStocks()) > 0;
    }

    /*
     * get pickup stocks in correct order, i.e.: customer pickup stock first, the rest in numerical order
     */
    public function getOrderedPickupStocks( $customer ) {
        $stocks = $this->getPickupStocks();
        $custPickNum = $this->getCustomerPickupStockNumber($customer);
        uasort($stocks, function($s1, $s2) use ($custPickNum) {
            if ($s1->getStockNumber() === $custPickNum || $s2->getStockNumber() === $custPickNum) {
                return ( $s1->getStockNumber() === $custPickNum ? -1 : 
                    ( $s2->getStockNumber() === $custPickNum ? 1 : 0 )
                );
            }
            return ( $s1->getStockNumber() < $s2->getStockNumber() ? -1 : ( $s1->getStockNumber() > $s2->getStockNumber() ? 1 : 0 ) );
        });
        return $stocks;
    }
    
    public function getPickupStockNumbers () {
        $res = array_keys($this->getPickupStocks());
        return $res;
    }
    
    public function getCustomerPickupStock ( $customer ) {
        $pickupID = $this->getCustomerPickupStockNumber($customer);
        if ( ! isset($this->_data[self::PICKUP_KEY]) ||! isset($this->_data[self::PICKUP_KEY][$pickupID]) ) {
            $res = null;
        } else {
            $res = $this->_data[self::PICKUP_KEY][$pickupID];
        }
        return $res;
    }

    public function getCustomerPickupStockNumber ( $customer ) {
        if ( ! $customer ) 
            $customer = Mage::getSingleton('customer/session')->getCustomer();
        $res = Mage::helper('schrackcustomer')->getPickupWarehouseId($customer);    
        return $res;
    }

    public function getAllDeliveryStockNumbers ( $include3rdParty = true ) {
        $res = array();
        if ( isset($this->_data[self::LOCAL_DELIVERY_KEY]) )
            $res[] = (int) $this->_data[self::LOCAL_DELIVERY_KEY]->getStockNumber();
        if ( isset($this->_data[self::FOREIGN_DELIVERY_KEY]) )
            $res[] = (int) $this->_data[self::FOREIGN_DELIVERY_KEY]->getStockNumber();
        if ( $include3rdParty && isset($this->_data[self::THIRD_PARTY_KEY]) )
            $res[] = (int) reset($this->_data[self::THIRD_PARTY_KEY])->getStockNumber();
        return $res;
    }

    public function getAllCustomerStockNumbers ( $customer ) {
        $res = $this->getAllDeliveryStockNumbers();
        $pickupID = $this->getCustomerPickupStockNumber($customer);
        if ( $pickupID )
            $res[] = $pickupID;
        return array_unique($res);
    }

    public function getAllStockNumbers ( $include3rdParty = true ) {
        $res1 = $this->getAllDeliveryStockNumbers($include3rdParty);
        $res2 = $this->getPickupStockNumbers();
        $res = array_merge($res1,$res2 ? $res2 : array());
        return array_unique($res);
    }

    public function ensureValidPickupStockNumber ( $stockNumber ) {
        $stocks = $this->getPickupStocks();
        if ( ! isset($stocks[$stockNumber]) ) {
            $stockNumber = current(array_keys($stocks));
        }
        return $stockNumber;
    }

    public function getStockByNumber ( $number ) {
        return $this->_number2stockMap[$number];
    }

    public function getAllStocks ( $sku ) {
        $res = array();
        foreach ( $this->_data as $key => $data ) {
            if ( ! isset($data) ) {
                continue;
            }
            if ( $key == self::PICKUP_KEY ) {
                foreach ( $data as $stock ) {
                    $res[$stock->getStockNumber()] = $stock;
                }
            } else if ( $key != self::THIRD_PARTY_KEY ) {
                $res[$data->getStockNumber()] = $data;
            }
        }
        // git now the right ThirdParty stock:
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql =  "SELECT stock.stock_id"
             . " FROM cataloginventory_stock_item AS item"
             . " JOIN cataloginventory_stock stock ON item.stock_id=stock.stock_id"
             . " JOIN catalog_product_entity product ON item.product_id=product.entity_id"
             . " WHERE product.sku = '$sku' and stock_number = 999;";
        $dbRes = $readConnection->fetchCol($sql);
        foreach ( $dbRes as $stockId ) {
            foreach ( $this->_data[self::THIRD_PARTY_KEY] as $stock ) {
                if ( $stock->getId() == $stockId )  {
                    $res[$stock->getStockNumber()] = $stock;
                }
            }
        }

        return $res;
    }

}