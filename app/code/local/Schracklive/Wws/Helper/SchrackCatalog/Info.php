<?php

class Schracklive_Wws_Helper_SchrackCatalog_Info extends Schracklive_SchrackCatalog_Helper_Info {

    const STOCK_CLASS_DELIVERY              = 'delivery';
    const STOCK_CLASS_DELIVERY_WO_3ST_PARTY = 'deliveryWo1stParty';
    const STOCK_CLASS_PICKUP                = 'pickup';

	protected $_anonymousCustomer = null;
    private   $_pullMeinhartQty   = true;
    private   $_hideStockQuantity; // Just set to "true", if stock quantity should be shown also for non-logged-id-users

    public function __construct() {
        $this->_pullMeinhartQty = Mage::getStoreConfig("schrack/wws/pullMeinhartQty");
        if (Mage::getStoreConfig("schrack/wws/hideStockQantityForNonLoggedInUsers") == 'on') {
            $this->_hideStockQuantity = true;
        } else {
            $this->_hideStockQuantity = false;
        }
    }

	public function getDrumsBySkusAndStocks ( array $skus, array $stocks ) {
        $skuQtys = array();
        foreach ( $skus as $sku ) {
            $skuQtys[$sku] = 1;
        }
		$drumInfos = $this->_preloadDrumInfo($skuQtys,$stocks,"getDrumsBySkusAndStocks");
        return $drumInfos;
    }

    public function getAvailableValidDeliveryStockNumbers(Schracklive_SchrackCatalog_Model_Product $product) {
        $res = array();
        $skuSrc = $product->getSku();
        $stockHelper = Mage::helper('schrackcataloginventory/stock');
        $infos = $this->_preloadAvailabilityInfo(array($skuSrc),$stockHelper->getAllDeliveryStockNumbers());
        foreach ( $infos as $skuKey => $sku ) {
            foreach ( $sku as $whKey => $wh ) {
                if ( isset($wh['delivery']) ) {
                    $res[] = $whKey;
                }
            }
        }
        return $res;
    }

    public function hasValidStockQty (Schracklive_SchrackCatalog_Model_Product $product, $stockNo) {
        $sku = $product->getSku();
        $infos = $this->_preloadAvailabilityInfo(array($sku),array($stockNo));
        return    isset($infos)
               && isset($infos[$sku])
               && isset($infos[$sku][$stockNo]);
    }

	public function preloadProductsInfo ( $products,
	                                      $customer = null,
                                          $getAll = false,
                                          $qtys = array(),
                                          $forceAvailibilityRequests = false,
                                          $fetchPrices = true,
                                          $fetchAvailibilities = true,
                                          $fetchDrums = true,
                                          $performanceFormkey = '' ) {
        if ($performanceFormkey) {
            Mage::log('app/code/local/Schracklive/Wws/Helper/SchrackCatalog/Info.php -> ' . $performanceFormkey, null, 'performance.log');
            Mage::log('start (Info.php) -> ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s'), null, 'performance.log');
        }
		if (is_null($customer)) {
			if (is_null($this->_anonymousCustomer)) {
				$this->_anonymousCustomer = Mage::getModel('customer/customer');
			}
			$customer = $this->_anonymousCustomer;
		} else {
            // Checks for projectant role (may see only default price):
            $sessionCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
            $aclRoleId = Mage::getModel('customer/customer')->load($sessionCustomerId)->getSchrackAclRoleId();
            $isProjectant = Mage::helper('schrack/acl')->isProjectantRoleId($aclRoleId);
            if ( $isProjectant ) {
                $customer = Mage::getModel('customer/customer');
            }
        }

        $stockHelper = Mage::helper('schrackcataloginventory/stock');
		if ($getAll) {
            $warehouses = $stockHelper->getAllStockNumbers();
		} else {
            $warehouses = $stockHelper->getAllCustomerStockNumbers($customer);
		}
		$skuQtys = array();
        $hasCables = 0;
        if ($performanceFormkey) {
            Mage::log('ProductFetcher -> start (Info.php) -> ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s'), null, 'performance.log');
        }
        $skuCables = $this->getCableInfos($products);
        foreach ( $skuCables as $sku => $flag ) {
            $hasCables += $flag;
			$skuQtys[$sku] = isset($qtys[$sku]) ? (int)$qtys[$sku] : 1;
        }
        if ($performanceFormkey) {
            Mage::log('ProductFetcher -> end (Info.php) -> ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s'), null, 'performance.log');
        }
        $res = array();
        if ($performanceFormkey) {
            Mage::log('PriceFetcher -> start (Info.php) -> ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s'), null, 'performance.log');
        }
		$res['prices']       = $fetchPrices ? $this->_preloadPriceInfo($customer,$skuQtys) : array();
        if ($performanceFormkey) {
            Mage::log('PriceFetcher -> end (Info.php) -> ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s'), null, 'performance.log');
        }
        $res['availibility'] = $fetchAvailibilities ? $this->_preloadAvailabilityInfo(array_keys($skuQtys), $warehouses, $forceAvailibilityRequests) : array();
        if ( $hasCables && $fetchPrices ) {
            $cuttingCosts = Mage::getStoreConfig('schrack/general/default_cutting_costs');
            if ( isset($cuttingCosts) && floatval($cuttingCosts) == 0 ) {
                $cuttingCosts = false;
            }
            if ( $cuttingCosts  ) {
                foreach ( $skuCables as $sku => $flag ) {
                    if ( $flag ) {
                        $res['prices'][$sku]['cuttingcosts'] = floatval($cuttingCosts);
                    }
                }
            }
        }
        if ( $hasCables && $fetchDrums ) {
            foreach ( $skuCables as $sku => $flag ) {
                if ( ! $flag ) {
                    unset($skuQtys[$sku]);
                }
            }
            $res['drums'] = $this->_preloadDrumInfo($skuQtys,$warehouses,"preloadProductsInfo");
        } else {
            $res['drums'] = array();
        }
        if ($performanceFormkey) {
            Mage::log('end (Info.php) -> ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s'), null, 'performance.log');
        }
        return $res;
	}

    // TODO: remove preloadForcedAvailabilityInfo() after ajax reconstruction
    public function preloadForcedAvailabilityInfo ( $sku ) {
        $warehouses = Mage::helper('schrackcataloginventory/stock')->getAllStockNumbers();
        $this->_preloadAvailabilityInfo(array($sku),$warehouses,true);
    }

	protected function _preloadPriceInfo($customer, $skuQtys) {
        Varien_Profiler::start('Schracklive_Wws_Helper_SchrackCatalog_Info::_preloadPriceInfo');
		$res =  Mage::getModel('wws/action_cache', array(
					array(
						'customer' => $customer,
						'products' => $skuQtys,
					),
					Mage::getModel('wws/action_fetchprices', array()),
					Mage::helper('wws/cache_priceinfo')
				))->execute();
        Varien_Profiler::stop('Schracklive_Wws_Helper_SchrackCatalog_Info::_preloadPriceInfo');
        return $res;
	}

    public function getPromotionSKUs () {
        if ( ! Mage::getSingleton('customer/session')->isLoggedIn() ) {
            return array();
        }
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $quote = Mage::getModel('sales/quote')->loadByCustomer($customer);
        if( in_array($quote->getSchrackCustomertype(), array('oldLightProspect', 'oldFullProspect', 'newProspect', 'prospect-user', 'guest'))) {
            return array();
        }
        $fetcher = Mage::getModel('wws/action_fetchpromotions', array());
        $cacher = Mage::helper('wws/cache_promotions');
        $cacheModel = Mage::getModel('wws/action_cache', array(
            array(
                'customer' => $customer,
            ),
            $fetcher,
            $cacher
        ));
        $res = $cacheModel->execute();
        $res = array_keys($res);
        return $res;
    }

    protected function _preloadAvailabilityInfo($skus, $warehouses, $forceCurrent = false) {
        if ( $forceCurrent ) {
            $res = Mage::getModel('wws/action_cache', array(
                        array(
                            'products' => $skus,
                            'warehouses' => $warehouses,
                        ),
                        Mage::getModel('wws/action_fetchavailability', array()),
                        Mage::helper('wws/cache_availabilityinfo')
                   ))->execute();
            parent::saveFetchedAvailabilityInfo($res);
            return $this->_prepareAvailabilityInfo($res);
        }
        $res = parent::_preloadAvailabilityInfo($skus, $warehouses);
        $mhProducts = array();
        $mhWarehouses = array();
        foreach ( $res as $skuKey => $sku ) {
            foreach ( $sku as $whKey => $wh ) {
                if ( $this->_pullMeinhartQty && strcasecmp($wh['stockLocation'],'MH') == 0 ) {
                    $mhProducts[] = $skuKey;
                    $mhWarehouses[] = $whKey;
                }
            }
        }
        if ( count($mhProducts) ) {
            $mhWarehouses = array_unique($mhWarehouses);
            $subRes = Mage::getModel('wws/action_cache', array(
                        array(
                            'products' => $mhProducts,
                            'warehouses' => $mhWarehouses,
                        ),
                        Mage::getModel('wws/action_fetchavailability', array()),
                        Mage::helper('wws/cache_availabilityinfo')
                    ))->execute();
            foreach ( $mhProducts as $tmpSku ) {
                if (isset($subRes[$tmpSku])) {
                    foreach ( $subRes[$tmpSku] as $tmpWh => $tmp ) {
                        if (in_array($tmpWh, $mhWarehouses)) {
                            $res[$tmpSku][$tmpWh]['qty'] = $tmp['qty'];
                        }
                    }
                }
            }
        }
        return $this->_prepareAvailabilityInfo($res);
	}

	private function _prepareAvailabilityInfo ( $infos ) {
	    if ($this->_hideStockQuantity == true) {
            if ( ! Mage::getSingleton('customer/session')->isLoggedIn() ) {
                foreach ($infos as $sku => $info) {
                    foreach ($info as $wh => $data) {
                        $infos[$sku][$wh]['qty'] = 0;
                    }
                }
            }
        }
        return $infos;
    }

	protected function _preloadDrumInfo($skuQtys, $warehouseIds, $callerMethodName = '(unknown)') {
        $sql = "SELECT sku, schrack_is_cable, schrack_sts_sub_article_skus FROM catalog_product_entity WHERE sku IN ('"
             . implode("','",array_keys($skuQtys))
             . "')";
        $rows = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);
        foreach ( $rows as $row ) {
            if ( $row['schrack_is_cable']== 0 && $row['schrack_sts_sub_article_skus'] <= '' ) {
                Mage::log("Try to fetch drum infos for non-cable " . $row['sku'] . " called by method $callerMethodName",null,'drumdebug.log');
                ob_start();
                debug_print_backtrace();
                $trace = ob_get_contents();
                ob_end_clean();
                Mage::log("stacktrace: " . $trace,null,'drumdebug.log');
            }
        }
		return Mage::getModel('wws/action_cache', array(
					array(
						'products' => $skuQtys,
						'warehouses' => $warehouseIds,
					),
					Mage::getModel('wws/action_fetchdrums', array()),
					Mage::helper('wws/cache_druminfo')
				))->execute();
	}

    /*
     * NOTE: returns one quantity despite the plural name!
     */
    public function getSummarizedDeliveryQuantities(Schracklive_SchrackCatalog_Model_Product $product, $qty = 1, $ignore3rdPartyStocks = false) {
        return $this->_getSummarizedQuantities($product, $ignore3rdPartyStocks ? self::STOCK_CLASS_DELIVERY_WO_3ST_PARTY : self::STOCK_CLASS_DELIVERY, $qty);
    }

    /**
     * @param Schracklive_SchrackCatalog_Model_Product $product
     * @param string $stockClass 'delivery' or 'pickup'
     */
    private function _getSummarizedQuantities(Schracklive_SchrackCatalog_Model_Product $product, $stockClass, $qty) {
        $stockHelper = Mage::helper('schrackcataloginventory/stock');
        $sku = $product->getSku();
        switch ( $stockClass ) {
            case self::STOCK_CLASS_DELIVERY              :
                $whs = $stockHelper->getAllDeliveryStockNumbers(true);
                break;
            case self::STOCK_CLASS_DELIVERY_WO_3ST_PARTY :
                $whs = $stockHelper->getAllDeliveryStockNumbers(false);
                break;
            case self::STOCK_CLASS_PICKUP                :
                $whs = $stockHelper->getPickupStockNumbers();
                break;
            default :
                throw new Exception('No such stock class as ' . $stockClass);
        }

        $infos = $this->_preloadAvailabilityInfo(array($sku),$whs);
        $res = 0;
        foreach ( $infos as $skuKey => $sku ) {
            foreach ( $sku as $whKey => $wh ) { // ###
                if ( $product->isDiscontinuation() && $stockHelper->getThirdPartyDeliveryStockNumber() == $whKey ) {
                    continue; // don't calculate 3rd party stock qty in when product is discontinued
                }
                if ( isset($wh[$stockClass]) ) {
                    $done = false;
                    if ( Schracklive_SchrackCatalog_Helper_Preparator::PREPARATE_PRODUCTS ) {
                        $preparedQty = Mage::helper('schrackcatalog/preparator')->getStockQuantity($product, $whKey);
                        if ( $preparedQty > -1 ) {
                            $done = true;
                            $res += $preparedQty;
                        }
                    }
                    if ( ! $done ) {
                        $qty = $wh['qty'];
                        if (isset($qty)) {
                            if (!is_numeric($qty)) {
                                throw new Exception('Received invalid qty "' . $qty . '" from WWS.');
                            }
                            $res += $qty;
                        }
                    }
                }
            }
        }
        return $res;
    }

    public function getSummarizedPickupQuantities(Schracklive_SchrackCatalog_Model_Product $product, $qty = 1) {
        return $this->_getSummarizedQuantities($product, self::STOCK_CLASS_PICKUP, $qty);
    }

    public function getSummarizedCustomerQuantities(Schracklive_SchrackCatalog_Model_Product $product, $pickupWarehouseId) {
        $sku = $product->getSku();
        $stockHelper = Mage::helper('schrackcataloginventory/stock');
        $infos = $this->_preloadAvailabilityInfo(array($sku),$stockHelper->getAllStockNumbers());
        $res = 0;
        foreach ( $infos as $skuKey => $sku ) {
            foreach ( $sku as $whKey => $wh ) {
                if ( $product->isDiscontinuation() && Mage::helper('schrackcataloginventory/stock')->getThirdPartyDeliveryStockNumber() == $whKey ) {
                    continue; // don't calculate 3rd party stock qty in when product is discontinued
                }
                if ( isset($wh['delivery']) || $whKey == $pickupWarehouseId || $pickupWarehouseId === 0 ) {
                    $done = false;
                    if ( Schracklive_SchrackCatalog_Helper_Preparator::PREPARATE_PRODUCTS ) {
                        $preparedQty = Mage::helper('schrackcatalog/preparator')->getStockQuantity($product, $whKey);
                        if ($preparedQty > -1) {
                            $done = true;
                            $res += $preparedQty;
                        }
                    }
                    if (!$done) {
                        $qty = $wh['qty'];
                        if (isset($qty)) {
                            if (is_numeric($qty))
                                $res += $qty;
                        }
                    }
                }
            }
        }
        return $res;
    }

	public function getTierPriceForCustomer(Schracklive_SchrackCatalog_Model_Product $product, $qty, Schracklive_SchrackCustomer_Model_Customer $customer) {
		$customerProductInfo = $this->_getProductPriceInfo($product, $customer, $qty);
		if (isset($customerProductInfo['price'])) {
			return $customerProductInfo['price'] + $customerProductInfo['surcharge'];
		} else {
			throw new Schracklive_SchrackCatalog_Helper_Info_Exception('Price not available. SKU=' . $product->getSku(), Schracklive_SchrackCatalog_Helper_Info_Exception::PRICE_UNAVAILABLE);
		}
	}

    public function getBasicTierPriceForCustomer(Schracklive_SchrackCatalog_Model_Product $product, $qty, Schracklive_SchrackCustomer_Model_Customer $customer) {
        $customerProductInfo = $this->_getProductPriceInfo($product, $customer, $qty);
        if ( isset($customerProductInfo['price']) && ! (isset($customerProductInfo['xstatus']) && $customerProductInfo['xstatus'] === 221) ) {
            return $customerProductInfo['price'];
        } else {
            throw new Schracklive_SchrackCatalog_Helper_Info_Exception('Price not available. SKU=' . $product->getSku(), Schracklive_SchrackCatalog_Helper_Info_Exception::PRICE_UNAVAILABLE);
        }
    }

    public function getCurrencyForCustomer(Schracklive_SchrackCatalog_Model_Product $product, $qty, Schracklive_SchrackCustomer_Model_Customer $customer) {
        if ( Mage::getStoreConfig('currency/options/useStoreConfigCurrency') === '1' ) {
            return Mage::getStoreConfig('currency/options/base');
        }

		$customerProductInfo = $this->_getProductPriceInfo($product, $customer, $qty);

        if (isset($customerProductInfo['currency'])) {
            try {
                $zC = new Zend_Currency($customerProductInfo['currency'], Mage::getStoreConfig('general/locale/code'));
                return $zC->getShortName();
            } catch (Exception $e) {
                Mage::logException($e);
                return $customerProductInfo['currency'];
            }
		} else {
            $currency = Mage::getStoreConfig('currency/options/default');
            if ( !$currency ) {
                $currency = 'EUR';
            }
            return $currency;
        }
    }

	public function getGraduatedPricesForCustomer(Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer, $qty = 1) {
		$customerProductInfo = $this->_getProductPriceInfo($product, $customer, $qty);
		if (isset($customerProductInfo['prices']) && is_array($customerProductInfo['prices'])) {
			return $customerProductInfo['prices'];
		} else {
			return array();
		}
	}

    public function getDeliveryHours(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId) {
		$sku = $product->getSku();
		$availabilityInfos = $this->_preloadAvailabilityInfo(array($sku), array($warehouseId));
        if ( isset($availabilityInfos[$sku][$warehouseId]) ) {
            $res = $availabilityInfos[$sku][$warehouseId]['delivery']['deliveryHours'];
        } else {
            $stockHelper = Mage::helper('schrackcataloginventory/stock');
            $stock = $stockHelper->getStockByNumber($warehouseId);
            if ( isset($stock) ) {
                $res = $stock->getDeliveryHours();
            } else {
                // SNH
                $res = 720; // set to ~1 month
            }
        }
        return $res;
    }

	public function getDeliveryQuantity(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId, $stockLocation = null) {
		return $this->_getQuantity($product,$warehouseId,$stockLocation);
	}

	public function getPickupQuantity(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId) {
		return $this->_getQuantity($product, $warehouseId);
	}

	protected function _getQuantity(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId,$stockLocation = null) {
        if ($product->isHideStockQantities()) {
            return $this->__('on request');
        }

        if ( Schracklive_SchrackCatalog_Helper_Preparator::PREPARATE_PRODUCTS ) {
            $res = Mage::helper('schrackcatalog/preparator')->getStockQuantity($product, $warehouseId);
            if ( $res > -1 ) {
                return $res;
            }
        }

        $productInfo = $this->_getProductWarehouseInfo($product,$warehouseId,$stockLocation);
        if (isset($productInfo['qty'])) {
            return $productInfo['qty'];
        }
        return 0;
	}

    public function hasQuantity(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId) {
        return intval($this->_getQuantity($product, $warehouseId)) > 0;
    }

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param                                          $warehouseId
	 * @return int
	 */
	public function getDeliveryState(Schracklive_SchrackCatalog_Model_Product $product,$warehouseId) {
		return $this->_getState($product, $warehouseId, 'delivery', true);
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param                                          $warehouseId
	 * @return int
	 */
	public function getPickupState(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId) {
		return $this->_getState($product, $warehouseId, 'pickup');
	}

	public function _getState(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId, $type, $isDeliveryWarehouse = false) {
        /* states are not longer supported, always 0 (== currently deliverable)
		$productInfo = $this->_getProductWarehouseInfo($product, $warehouseId, $isDeliveryWarehouse);
		if (isset($productInfo[$type]['state'])) {
			return $productInfo[$type]['state'];
		}
        */
		return 0;
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param int                                      $warehouseId
	 * @return int
	 */
	public function getDeliverySalesUnit(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId = 0) {
        if ( $warehouseId == 0 )
            $warehouseId = Mage::helper('schrackshipping/delivery')->getWarehouseId();
		return $this->_getSalesUnit($product, $warehouseId, 'delivery', true);
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param                                          $warehouseId
	 * @return int
	 */
	public function getPickupSalesUnit(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId) {
		return $this->_getSalesUnit($product, $warehouseId, 'pickup');
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param                                          $warehouseId
	 * @param                                          $type
	 * @param bool                                     $isDeliveryWarehouse
	 * @return int
	 */
	public function _getSalesUnit(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId, $type, $isDeliveryWarehouse = false) {
		$productInfo = $this->_getProductWarehouseInfo($product, $warehouseId, $isDeliveryWarehouse);
		if (isset($productInfo[$type]['salesunit'])) {
			$unit = (int)$productInfo[$type]['salesunit'];
			return ($unit > 0) ? $unit : 1;
		} else {
			return 1;
		}
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @return float
	 */
	public function getSurcharge(Schracklive_SchrackCatalog_Model_Product $product) {
		$productInfo = $this->_getProductPriceInfo($product);
		if (isset($productInfo['surcharge'])) {
			return $productInfo['surcharge'];
		} else {
			return 0.0;
		}
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param array                                    $warehouseIds
	 * @param int                                      $qty
	 * @return array keys are warehouse ids, values are drums
	 */
	public function getAvailableDrums(Schracklive_SchrackCatalog_Model_Product $product, array $warehouseIds, $qty = 1) {
		$drumInfo = $this->_getProductDrumInfo($product, $qty);
		if (isset($drumInfo['available'])) {
            $drumInfo['available'] = array_map(function($drums) { return Schracklive_Wws_Helper_SchrackCatalog_Info::_sortDrums($drums); }, $drumInfo['available']); // sort only the VALUES of $drumInfo
			return $this->_filterDrums($drumInfo['available'], $warehouseIds);
		} else {
			return array();
		}
	}

    public function selectRoundMultipleDrums(array $stockDrums) {
        $resDrums = array();
        foreach ($stockDrums as $stockNo => $drums) {
            $resDrums[$stockNo] = array_filter($drums, function($d) {
                $div = ($d->getSize() != 0 ? $d->getStockQty() / $d->getSize() : 0);
                return (floatval($div) == intval($div));
            }); // ==, NOT ===, because the types are different!
        }
        return $resDrums;
    }

    public function selectReadyPackagingDrums(array $stockDrums) {
        $resDrums = array();
        foreach ($stockDrums as $stockNo => $drums) {
            $resDrums[$stockNo] = array_filter($drums, function($d) {
                $div = ($d->getSize() != 0 ? $d->getStockQty() / $d->getSize() : 0);
                return (floatval($div) != intval($div));
            }); // ==, NOT ===, because the types are different!
        }
        return $resDrums;
    }

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param array                                    $warehouseIds
	 * @return array keys are warehouse ids, values are drums
	 */
	public function getPossibleDrums(Schracklive_SchrackCatalog_Model_Product $product, array $warehouseIds) {
		$drumInfo = $this->_getProductDrumInfo($product, 1);
		if (isset($drumInfo['possible'])) {
			return $this->_filterDrums($drumInfo['possible'],$warehouseIds);
		} else {
			return array();
		}
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param                                          $warehouseId
	 * @return array
	 */
	protected function _getProductWarehouseInfo(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId, $stockLocation = null, $forceCurrent = false) {
        if (!($product && $product->getId())) {
            throw new Exception($this->__('No such product'));
        }
        $sku = $product->getSku();
		$availabilityInfos = $this->_preloadAvailabilityInfo(array($sku), array($warehouseId),$forceCurrent);

		$availabilityInfo = array();
		if (   isset($availabilityInfos[$sku])
            && isset($availabilityInfos[$sku][$warehouseId])
            && (   $stockLocation == null
                || (    isset($availabilityInfos[$sku][$warehouseId]['stockLocation'])
                     && $stockLocation == $availabilityInfos[$sku][$warehouseId]['stockLocation']) )
        ) {
            $availabilityInfo = $availabilityInfos[$sku][$warehouseId];
		}
		return $availabilityInfo;
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product   $product
	 * @param Schracklive_SchrackCustomer_Model_Customer $customer
	 * @param int                                        $qty
	 * @return array [sku] => product / [sku][prices] => scaled prices
	 */
	protected function _getProductPriceInfo(Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer = null, $qty = 1) {
		if (is_null($customer)) {
			if (is_null($this->_anonymousCustomer)) {
				$this->_anonymousCustomer = Mage::getModel('customer/customer');
			}
			$customer = $this->_anonymousCustomer;
		} else {
            // Checks for projectant role (may see only default price):
            $sessionCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
            $aclRoleId = Mage::getModel('customer/customer')->load($sessionCustomerId)->getSchrackAclRoleId();
            $isProjectant = Mage::helper('schrack/acl')->isProjectantRoleId($aclRoleId);
            if ($isProjectant) {
                $customer = Mage::getModel('customer/customer');
            }
        }

        $priceInfo = array();
        try {
            $sku = $product->getSku();
            $priceInfos = $this->_preloadPriceInfo($customer, array($sku => $qty));
            if (isset($priceInfos[$sku])) {
                $priceInfo = $priceInfos[$sku];
            }
        } catch (Exception $e) {
            Mage::log('unable to fetch price info: ' . $e->getMessage());
            Mage::logException($e);
        }
		return $priceInfo;
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param                                          $qty
	 * @return array [sku]["available"|"possible"][warehouse] => drum
	 */
	protected function _getProductDrumInfo(Schracklive_SchrackCatalog_Model_Product $product, $qty) {
        $warehouseIds = Mage::helper('schrackcataloginventory/stock')->getAllStockNumbers(true);
		$sku = $product->getSku();
		$drumInfos = $this->_preloadDrumInfo(array($product->getSku() => $qty), $warehouseIds,"_getProductDrumInfo");
		if (isset($drumInfos[$sku])) {
			return $drumInfos[$sku];
		} else {
			return array();
		}
	}

    private function _filterDrums ( array $drumInfos, array $warehouseIDs ) {
        $res = array();
        foreach ( $drumInfos as $whId => $wh ) {
            if ( in_array($whId,$warehouseIDs) ) {
                $res[$whId] = $wh;
            }
        }
        return $res;
    }

    public static function _sortDrums($drums) {
        $sortedDrums = $drums;
        uasort($sortedDrums, function($d1, $d2) {
            return ($d1->getStockQty() < $d2->getStockQty() ? -1 : ($d1->getStockQty() === $d2->getStockQty() ? 0 : 1));
        });
        return $sortedDrums;
    }

    public function getDrumSelectorName($drum) {
        return $drum->getWwsNumber().'|'.$drum->getSize().'|'.($drum->getLessenDelivery() ? 1 : 0);
    }

    public function getRegularPrice ( Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer, $qty = 1 )
    {
        $customerProductInfo = $this->_getProductPriceInfo($product, $customer, $qty);
        if ( isset($customerProductInfo['regularprice']) ) {
            return $customerProductInfo['regularprice'];
        }
        return null;
    }

    public function getPromoValidTo ( Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer ) {
        $customerProductInfo = $this->_getProductPriceInfo($product, $customer, 1);
        if ( isset($customerProductInfo['promovalidto']) ) {
            return $customerProductInfo['promovalidto'];
        }
        return null;
    }

    public function isSuppressStockQty ( Schracklive_SchrackCatalog_Model_Product $product ) {
        $info = $this->_getProductPriceInfo($product);
        return ( isset($info['onrequest']) ? $info['onrequest'] : false );
    }
}
