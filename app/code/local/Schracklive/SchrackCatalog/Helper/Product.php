<?php

class Schracklive_SchrackCatalog_Helper_Product extends Mage_Catalog_Helper_Product {

	protected $_info;
    private $_prepareCache = array();

    public function initProduct($productId, $controller, $params = null)
    {
        // Prepare data for routine
        if (!$params) {
            $params = new Varien_Object();
        }

        // Init and load product
        Mage::dispatchEvent('catalog_controller_product_init_before', array(
            'controller_action' => $controller,
            'params' => $params,
        )); // Nagarro added new params from 1.9.x core

        if (!$productId) {
            return false;
        }

        $product = Mage::registry('product');
        if ( ! $product ) {
            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($productId);     // Nagarro replaced load by attribute from load from 1.9.x core
            Mage::register('product',$product);
        }

        if (!$this->canShow($product)) {
            return false;
        }
        if (!in_array(Mage::app()->getStore()->getWebsiteId(), $product->getWebsiteIds())) {
            return false;
        }

        // Load product current category
        $categoryId = $params->getCategoryId();
        if (!$categoryId && ($categoryId !== false)) {
            $lastId = Mage::getSingleton('catalog/session')->getLastVisitedCategoryId();
            if ($product->canBeShowInCategory($lastId)) {
                $categoryId = $lastId;
            }
        } elseif (!$product->canBeShowInCategory($categoryId)) {    // Nagarro addition from 1.9.x core
            $categoryId = null;
        }

        if ($categoryId) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $product->setCategory($category);
            Mage::register('current_category', $category);
        }

        // Register current data and dispatch final events
        Mage::register('current_product', $product);

        try {
            Mage::dispatchEvent('catalog_controller_product_init', array('product' => $product));
            Mage::dispatchEvent('catalog_controller_product_init_after',
                array('product' => $product,
                    'controller_action' => $controller
                )
            );
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return false;
        }

        return $product;
    }

    public function getSummarizedStockQuantities ( Schracklive_SchrackCatalog_Model_Product $product ) {
        $stockHelper = Mage::helper('schrackcataloginventory/stock');
        $stockNos = $stockHelper->getAllStockNumbers(false);
        $qty = 0;
        foreach ( $stockNos as $stockNo ) {
            $stockQty = $this->_getInfo()->getPickupQuantity($product,$stockNo);
            $qty += $stockQty;
        }
        return $qty;
    }

    public function checkOrderAvail ( Schracklive_SchrackCatalog_Model_Product $product, $pickup, $qty ) {
        $res = false;
        if ( $pickup ) {
            $stockNo = $this->_getWarehouseId();
            if ( $this->_getInfo()->hasValidStockQty($product,$stockNo) ) {
                $res = $this->_checkOrderAvailInStock($product,$qty,$stockNo,true);
            }
            else {
                $res = false;
            }
        }
        else {
            $deliveryStockNos = $this->_getInfo()->getAvailableValidDeliveryStockNumbers($product);
            foreach ( $deliveryStockNos as $stockNo ) {
                $res = $this->_checkOrderAvailInStock($product,$qty,$stockNo,false);
                if ( $res ) {
                    break;
                }
            }
        }
        return $res;
    }

    public function getDeliveryTime(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId, $prefixDeliverable=false) {
        if (!$warehouseId) {
            $warehouseId = $this->_getStandardDeliveryWarehouseId();
        }
        $hours = $this->_getInfo()->getDeliveryHours($product,$warehouseId);
        //---------------------------------------------------------- INIT return
        $RET = array( "days" => 0, "weeks"=> 0 );
        //----------------------------------------------------------- fill days
        $days = ceil($hours / 24.0);
        $RET["days"] = $days;
        //----------------------------------------------------------- fill weeks
        if ( $days > 9 ) { $RET["weeks"] = ceil($days / 5.0); }
        //--------------------------------------------------------------- RETURN
        return $RET;
    }

    public function getFormattedDeliveryTime(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId, $prefixDeliverable=false) {
        if (!$warehouseId) {
            $warehouseId = $this->_getStandardDeliveryWarehouseId();
        }
        $in = $prefixDeliverable ? $this->__('Deliverable in') : $this->__('in');
        // Retrieve PV Extra Delivery time
        $pvDeliveryTime = $product->getSchrackStsPlusDeliTime();
        $hours = $this->_getInfo()->getDeliveryHours($product,$warehouseId);
        // Check if PV Extra time is set, than add delivery time
        if ($pvDeliveryTime != null && $pvDeliveryTime > 0) {
            $days = ceil($hours / 24.0) + (int) $pvDeliveryTime;
        } else {
            $days = ceil($hours / 24.0);
        }
        if ( $days > 9 ) {
            $weeks = ceil($days / 5.0);
            $tx = $weeks == 1 ? 'week' : 'weeks';
            return $in.' '.$weeks.' '.$this->__($tx);
        }
        else {
            $tx = $days == 1 ? 'workday' : 'workdays';
            return $in.' '.$days.' '.$this->__($tx);
        }
    }

    /**
	 * Will return delivery sales unit unless $pickupWarehouseId is supplied.
	 *
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param int                                      $pickupWarehouseId
	 * @return bool
	 */
	public function getSalesUnit(Schracklive_SchrackCatalog_Model_Product $product, $pickupWarehouseId = 0) {
		if ($pickupWarehouseId) {
			return $this->_getInfo()->getPickupSalesUnit($product, $pickupWarehouseId);
		} else {
			return $this->_getInfo()->getDeliverySalesUnit($product);
		}
	}

	/**
	 * A product has drums if it is a cable and has no sales unit.
	 * Delivery sales unit will be checked unless $pickupWarehouseId is supplied.
	 *
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @param int                                      $pickupWarehouseId
	 * @return bool
	 */
	public function hasDrums(Schracklive_SchrackCatalog_Model_Product $product, $pickupWarehouseId = 0) {
		if ($product->isCable()) {
			return true;
		}
		return false;
	}

    public function isSuppressStockQty ( Schracklive_SchrackCatalog_Model_Product $product ) {
        return $this->_getInfo()->isSuppressStockQty($product);
    }

    public function isAvailInStock(Schracklive_SchrackCatalog_Model_Product $product, $stock) {
        if ( ! $stock )
            return false;
        $warehouseId = $stock->getStockNumber();
        return $this->_getInfo()->hasQuantity($product,$warehouseId);
    }

    public function isAvailInStockNo(Schracklive_SchrackCatalog_Model_Product $product, $stockNo) {
        return $this->_getInfo()->hasQuantity($product,$stockNo);
    }

    public function isAvailInAnyStock(Schracklive_SchrackCatalog_Model_Product $product, $pickupWarehouseId = 0) {
        $res = $this->_getInfo()->getSummarizedCustomerQuantities($product,$pickupWarehouseId);
        return $res > 0;
    }
    
    public function isAvailInAnyDeliveryStock(Schracklive_SchrackCatalog_Model_Product $product) {
        $res = $this->_getInfo()->getSummarizedDeliveryQuantities($product);
        return $res > 0;
    }

    public function isAvailInAnyPickupStock(Schracklive_SchrackCatalog_Model_Product $product) {
        $res = $this->_getInfo()->getSummarizedPickupQuantities($product);
        return $res > 0;
    }

    public function getSummarizedFormattedDeliveryQuantities(Schracklive_SchrackCatalog_Model_Product $product, $addUnit = true) {
        $qty = $this->_getInfo()->getSummarizedDeliveryQuantities($product);
        return $this->formatQty($product,$qty,$addUnit);
    }
    
    public function getSummarizedFormattedPickupQuantities(Schracklive_SchrackCatalog_Model_Product $product, $addUnit = true) {
        $qty = $this->_getInfo()->getSummarizedPickupQuantities($product);
        return $this->formatQty($product,$qty,$addUnit);
    }
    
    public function formatQty ( Schracklive_SchrackCatalog_Model_Product $product, $qty, $addUnit = true ) {
        if ( is_numeric($qty) ) {
            $qty = Mage::helper('schrackcore/string')->numberFormat($qty);
            if ($addUnit) {
                $qty .= ' '.$product->getSchrackQtyunit();
            }
        }
        return $qty;
    }
    
    /*
     * $warehouseId = 0 ---> DEFAULT, nicht ALLE!
     */

	public function getFormattedDeliveryQuantity(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId = 0, $addUnit = true) {
        $res = $this->getFormattedAndUnformattedDeliveryQuantity($product,$warehouseId,$addUnit);
        return $res[1];
    }

    public function getFormattedMaxQuantity ( Schracklive_SchrackCatalog_Model_Product $product ) {
        $delivery = $this->getFormattedAndUnformattedDeliveryQuantity($product);
        $pickup = $this->getFormattedAndUnformattedPickupQuantity($product);
        if ( floatval($delivery[0]) > floatval($pickup[0]) ) {
            return $delivery[1];
        }
        else {
            return $pickup[1];
        }
    }

    /*
     * $warehouseId = 0 ---> DEFAULT, nicht ALLE!
     */
	public function getFormattedAndUnformattedDeliveryQuantity(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId = 0, $addUnit = true, $stockLocation = null) {
        $res = array(0, "");
        $stdDeliveryWarehouseId = $this->_getStandardDeliveryWarehouseId();
        $isDfltDeliveryStock = true;
		if (!$warehouseId) {
			$warehouseId = $stdDeliveryWarehouseId;
		}
        else {
            $isDfltDeliveryStock = $warehouseId == $stdDeliveryWarehouseId;
        }
		try {
			$res[0] = $this->_getInfo()->getDeliveryQuantity($product,$warehouseId,$stockLocation);
			$res[1] = $this->formatQty($product,$res[0],$addUnit);

            $addPlus = false;
            if ( $res[0] > 0 ) {
                if ( ! $isDfltDeliveryStock ) {
                    try {
                        $dfltDeliveryQty = $this->_getInfo()->getDeliveryQuantity($product,$stdDeliveryWarehouseId);
            		} catch (Schracklive_SchrackCatalog_Helper_Info_Exception $e) {
                        if ($e->getCode() == Schracklive_SchrackCatalog_Helper_Info_Exception::QUANTITY_UNAVAILABLE) {
                            $dfltDeliveryQty = 0;
                        } else {
                            throw $e;
                        }
                    }
                    if ( $dfltDeliveryQty > 0 ) {
                        $addPlus = true;
                    }
                    else {
                        $stockHelper = Mage::helper('schrackcataloginventory/stock');
                        $thirdPartyWarehouseNum = $stockHelper->getThirdPartyDeliveryStockNumber();
                        if ( $thirdPartyWarehouseNum == $warehouseId ) {
                            $foreignDeliveryWarehouse = $stockHelper->getForeignDeliveryStock();
                            if ( isset($foreignDeliveryWarehouse) ) {
                                try {
                                    $foreignDeliveryQty = $this->_getInfo()->getDeliveryQuantity($product,$thirdPartyWarehouseNum);
                                } catch (Schracklive_SchrackCatalog_Helper_Info_Exception $e) {
                                    if ($e->getCode() == Schracklive_SchrackCatalog_Helper_Info_Exception::QUANTITY_UNAVAILABLE) {
                                        $foreignDeliveryQty = 0;
                                    } else {
                                        throw $e;
                                    }
                                }
                                if ( $foreignDeliveryQty > 0 ) {
                                    $addPlus = true;
                                }
                            }
                        }
                    }
                }
            }
            if ( $addPlus ) {
                $res[1] = '+'.$res[1];
            }
            return $res;
		} catch (Schracklive_SchrackCatalog_Helper_Info_Exception $e) {
			if ($e->getCode() == Schracklive_SchrackCatalog_Helper_Info_Exception::QUANTITY_UNAVAILABLE) {
				$res[1] = $this->__('not available');
                return $res;
			} else {
				throw $e;
			}
		}
	}
    
	public function getFormattedPickupQuantity(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId = 0, $addUnit = true) {
        $res = $this->getFormattedAndUnformattedPickupQuantity($product,$warehouseId,$addUnit);
        return $res[1];
    }

    public function getPickupQuantity(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId = 0) {
        try {
            return $this->_getInfo()->getPickupQuantity($product, $warehouseId);
        } catch (Exception $e) {
            Mage::logException($e);
            return 0;
        }
    }

	public function getFormattedAndUnformattedPickupQuantity(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId = 0, $addUnit = true) {
        $res = array(0, "");
		if (!$warehouseId) {
			$warehouseId = $this->_getWarehouseId();
		}
		try {
			$res[0] = $this->_getInfo()->getPickupQuantity($product, $warehouseId);
            $res[1] =  $this->formatQty($product,$res[0],$addUnit);
			return $res;
		} catch (Schracklive_SchrackCatalog_Helper_Info_Exception $e) {
			if ($e->getCode() == Schracklive_SchrackCatalog_Helper_Info_Exception::QUANTITY_UNAVAILABLE) {
				$res[1] = $this->__('not available');
                return $res;
			} else {
				throw $e;
			}
		}
	}

    public function getPromotionSKUs () {
        /* @var $promoHelper Schracklive_Promotions_Helper_Data */
        $promoHelper = Mage::helper('promotions');
        return $promoHelper->getAllPromotionSKUs();
    }

    public function getPromotionProductCollection () {
        $skus = $this->getPromotionSKUs();
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addAttributeToFilter('sku', array('in' => $skus));
        return $collection;
    }

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $product
	 * @throws Schracklive_SchrackCatalog_Helper_Info_Exception
	 * @return array [warehouse] => Varien object(warehouse_id,qty)
	 */
	public function getAllPickupQuantities(Schracklive_SchrackCatalog_Model_Product $product) {
		$stockItems = array();
		$this->_getInfo()->preloadProductsInfo(array($product), null, true);
		$warehouseIds = Mage::helper('schrackshipping/pickup')->getWarehouseIds();
		foreach ($warehouseIds as $warehouseId) {
			$qty = 0;
			try {
				$qty = $this->_getInfo()->getPickupQuantity($product, $warehouseId);
			} catch (Schracklive_SchrackCatalog_Helper_Info_Exception $e) {
				if ($e->getCode() != Schracklive_SchrackCatalog_Helper_Info_Exception::QUANTITY_UNAVAILABLE) {
					throw $e;
				}
			}

			$stockItems[$warehouseId] = new Varien_Object();
			$stockItems[$warehouseId]->setWarehouseId($warehouseId);
			$stockItems[$warehouseId]->setQty($qty);
			$stockItems[$warehouseId]->setFormattedQty($this->getFormattedPickupQuantity($product, $warehouseId));
		}
		return $stockItems;
	}

    public function getDeliveryStateText(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId = 0) {
		if (!$warehouseId) {
			$warehouseId = $this->_getStandardDeliveryWarehouseId();
		}
		$res = $this->getDeliveryStateTextFromCode($this->_getInfo()->getDeliveryState($product,$warehouseId));
        return $res;
	}

	public function getDeliveryStateTextFromCode($code) {
		switch ($code) {
			case 1:
				return 'Ready for shipping';
			case 0:
			case 2:
				return 'Currently ready for shipping';
			case 3:
			case 5:
				return 'Currently ready for shipping within one week';
			case 4:
				return 'Ready for shipping within one week';
			case 9:
				return 'Discontinued line';
		}
	}

	public function getPickupStateText(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId = 0) {
		if (!$warehouseId) {
			$warehouseId = $this->_getWarehouseId();
		}
		return $this->getPickupStateTextFromCode($this->_getInfo()->getPickupState($product, $warehouseId));
	}

	public function getPickupStateTextFromCode($code) {
		switch ($code) {
			case 0:
			case 2:
				// Lagermenge = 0
				return 'Currently available for pickup at your store';
			case 1:
				return 'Available for pickup at your store';
			case 9:
				return 'Discontinued line';
		}
	}

	public function getDeliveryStateImage(Schracklive_SchrackCatalog_Model_Product $product,$warehouseId = 0) {
		if (!$warehouseId) {
			$warehouseId = $this->_getStandardDeliveryWarehouseId();
		}
		return $this->getDeliveryStateImageFromCode($this->_getInfo()->getDeliveryState($product,$warehouseId));
	}

	public function getDeliveryStateImageFromCode($code) {
		switch ($code) {
			case 1:
				return 'truck_white_diamond.png';
			case 0:
			case 2:
				return 'truck_white.png';
			case 3:
			case 5:
				return 'truck_blue.png';
			case 4:
				return 'truck_blue_diamond.png';
			case 9:
			default:
				return 'truck_white.png';
		}
	}

	public function getPickupStateImage(Schracklive_SchrackCatalog_Model_Product $product, $warehouseId = 0) {
		if (!$warehouseId) {
			$warehouseId = $this->_getWarehouseId();
		}
		return $this->getPickupStateImageFromCode($this->_getInfo()->getPickupState($product, $warehouseId));
	}

	public function getPickupStateImageFromCode($code) {
		switch ($code) {
			case 1:
				return 'cart_raute1.png';
			case 0:
			case 2:
				return 'cart_raute2.png';
			case 9:
			default:
				return 'cart_raute2.png';
		}
	}

    public function trimLength ( $str, $len ) {
        if ( strlen($str) > $len ) {
            $str = substr($str,0,$len - 2) . "...";
        }
        return $str;
    }
    
	/**
	 * @return Schracklive_SchrackCatalog_Helper_Info
	 */
	public function _getInfo() {
		if (!$this->_info) {
			$this->_info = Mage::helper('schrackcatalog/info');
		}
		return $this->_info;
	}

	/**
	 * @return int
	 */
	protected function _getWarehouseId() {
		return Mage::helper('schrackcustomer')->getPickupWarehouseId(Mage::getSingleton('customer/session')->getCustomer());
	}

	private function _getStandardDeliveryWarehouseId() {
		return Mage::helper('schrackshipping/delivery')->getWarehouseId();
	}

    private function _checkOrderAvailInStock ( $product, $qty, $stockNo, $isPickup ) {
        if ( $isPickup ) {
            $stockQty = $this->_getInfo()->getPickupQuantity($product,$stockNo);
        } 
        else {
            $stockQty = $this->_getInfo()->getDeliveryQuantity($product,$stockNo);
        }
        if ( $stockQty < $qty ) {
            return false;
        }
        
        return true;
    }
/**
     * Return loaded product instance
     *
     * @param  int|string $productId (SKU or ID)
     * @param  int $store
     * @param  string $identifierType
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct($productId, $store, $identifierType = null) {
        $loadByIdOnFalse = false;
        if ($identifierType == null) {
            if (is_string($productId) && !preg_match("/^[+-]?[1-9][0-9]*$|^0$/", $productId)) {
                $identifierType = 'sku';
                $loadByIdOnFalse = true;
            } else {
                $identifierType = 'id';
            }
        }

        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product');
        if ($store !== null) {
            $product->setStoreId($store);
        }
        if ($identifierType == 'sku') {
            $idBySku = $product->getIdBySku($productId);
            if ($idBySku) {
                $productId = $idBySku;
                $identifierType = 'id';
            }
            if ($loadByIdOnFalse) {
                $identifierType = 'id';
            }
        }
        
        if ($identifierType == 'id' && is_numeric($productId)) {
            $productId = !is_float($productId) ? (int) $productId : 0;
            $product->load($productId);
        } else {
            throw new Exception('incorrect identifier type or load by id not set');
        }

        return $product;
    }
    
    public function addProductsToCart ( $mapSKUsToQuantities, &$successMessage, &$unsuccessMessages, $cartId = 1, $partslistId = null, $customer = null ) {
        $successMessage = null;
        $unsuccessMessages = array();
        $cart = $wishlist = $partslist = null;
        if ( $cartId == 1 ) {
            $cart = Mage::getSingleton('checkout/cart');
        } elseif ( $cartId == 2 ) {
            $wishlist = Mage::getModel('schrackwishlist/wishlist')->loadByCustomer($customer, true);
        } elseif ( $cartId == 3 ) {
            try {
                $model = Mage::getModel('schrackwishlist/partslist');
                if ( $partslistId === null ) {
                    $partslist = $model->loadActiveListByCustomer($customer);
                } else {
                    $partslist = $model->loadByCustomerAndId($customer, $partslistId);
                }
            } catch (Exception $e) {
                throw Mage::exception('Mage_Core', 'Invalid cart. - ' . $e->getMessage());
            }
        } else {
            throw Mage::exception('Mage_Core', 'Invalid cart.');
        }
        
        $addedSkus = array();
        $addedNames = array();
        $notAddedNames = array();
        $customerorderHelper = Mage::helper('schrackcustomer/order');
        foreach ( $mapSKUsToQuantities as $sku => $qty ) {
            $product = Mage::getModel('schrackcatalog/product')->loadBySku($sku);
            if ( ! $product ) {
                $unsuccessMessages[] = $this->__('Product %s not found.',$sku);
                continue;
            }
            $name = $product->getName();
            if ( $product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED ) {
                $notAddedNames[] = $name;
                continue;
            }
            if ( ! $product->isSalable() ) {
                $notAddedNames[] = $name;
                continue;
            }         
            $categories = $product->getCategoryIds();
            if ( ! $categories ) {
                $notAddedNames[] = $name;
                continue;
            }         
            if ( ! $customerorderHelper->productCanBeAddedToLists($product) ) {
                $notAddedNames[] = $name;
                continue;
            }         
            try {
                if ( $cartId == 1 ) {
                    $resultQuantityData = $product->calculateClosestHigherQuantityAndDifference($qty, true, array(), 'addCartQuantity6');
                    if ($resultQuantityData && array_key_exists('invalidQuantity', $resultQuantityData) && $resultQuantityData['invalidQuantity'] == true) {
                        if (array_key_exists('closestHigherQuantity', $resultQuantityData) && $resultQuantityData['closestHigherQuantity'] != $qty) {
                            $minimumQuantity = $product->calculateMinimumQuantityPackage();

                            $notValidAmountMessage = $this->__("AMOUNT: The entered amount for the article %s1 is not a multiple of the sales unit. Please enter a multiple of %s2.");
                            $unsuccessMessages[] = str_replace('%s2', $minimumQuantity, str_replace('%s1', $product->getName(), $notValidAmountMessage));
                            $unsuccessMessages[] = $this->__('Unable to add the following product(s) to shopping cart: %s.', $product->getName());
                        }
                    } else {
                        $cart->addProduct($product, array('qty' => $qty));
                    }
                } elseif ( $cartId == 2 ) {
                    $wishlist->addNewItem($product);
                } elseif ( $cartId == 3 ) {
                    $partslist->addNewItem($product,array('qty' => $qty));
                }                 
                $addedNames[] = $name;
                $addedSkus[]= $sku;
            }
            catch ( Exception $e ) {
                Mage::logException($e);
                $notAddedNames[] = $name;
            }
        }
        try {
            if ( $cartId == 1 ) {
                $cart->save();
                $cart->getQuote()->collectTotals();            
            } elseif ( $cartId == 2 ) {
                $wishlist->save();
            } elseif ( $cartId == 3 ) {
                $partslist->save();
            }                 
        }
        catch ( Exception $e ) {
            Mage::logException($e);
            if ( $cartId == 1 ) {
                $unsuccessMessages = array( $this->__('Cart could not be actualized.'));
            } else {
                $unsuccessMessages = array( $this->__('Partslist could not be actualized.'));
            }
            return false;
        }
        if ( $notAddedNames ) {
            if ( $cartId == 1 ) {
                $unsuccessMessages[] = $this->__('Unable to add the following product(s) to shopping cart: %s.', join(', ', $notAddedNames));
            } else {
                $unsuccessMessages[] = $this->__('Unable to add the following product(s) to partslist: %s.', join(', ', $notAddedNames));
            }
        }
        if ( $addedNames ) {
            if ( $cartId == 1 ) {
                $successMessage = $this->__('%d product(s) have been added to shopping cart: %s.', count($addedNames), join(', ', $addedNames));
            } else {
                $successMessage = $this->__('%d product(s) have been added to partslist: %s.', count($addedNames), join(', ', $addedNames));
            }
        }
        return $addedSkus;
    }

    public function isPromotion ( Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer, $qty = 1 ) {
        $regularPrice = $this->_getInfo()->getRegularPrice($product,$customer,$qty);
        return ! is_null($regularPrice) && floatval($regularPrice) > 0;
    }

    public function getRegularPrice ( Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer ) {
        $regularPrice = $this->_getInfo()->getRegularPrice($product,$customer);
        return $regularPrice;
    }

    public function getPromotionEndDate ( Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer ) {
        $promotionEndDate = $this->_getInfo()->getPromoValidTo($product,$customer);
        if ( ! $promotionEndDate ) {
            return '';
        }
        $locale = Mage::getStoreConfig('general/locale/code');
        return $promotionEndDate->get(Zend_Date::DATE_MEDIUM,$locale);
    }

    public function getPromotionPriceDiff ( Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer ) {
        $regularPrice = $this->_getInfo()->getRegularPrice($product,$customer);
        $promotionPrice = $this->_getInfo()->getBasicTierPriceForCustomer($product,1,$customer);
        $diff = floatval($regularPrice) - floatval($promotionPrice);
        return $diff;
    }

    public function isSale ( Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer ) {
        return $product->isSale();
        // && $this->isPromotion($product,$customer); // not longer taking care of the reduced price...
    }

    public function sendDiscontinuationInquiryEmail ( $name, $email, $phone, $company, $customerNo, $country, $branch, $text, $advisor, $sku, $qty ) {
        $block = Mage::getBlockSingleton('core/template');
        $block->setTemplate('catalog/product/email_discontinuationinquiry.phtml');
        $block->assign('name', $name);
        $block->assign('email', $email);
        $block->assign('phone', $phone);
        $block->assign('text', $text);
        $block->assign('advisor', $advisor);
        $block->assign('sku', $sku);
        $block->assign('qty', $qty);
        $block->assign('company',$company);
        $block->assign('customerNo',$customerNo);
        $block->assign('country',$country);
        $block->assign('branch',$branch);
        $html = $block->toHtml();
        $mailHelper = Mage::helper('wws/mailer');
        $toAddress = $advisor->getEmail();
        if (isset($toAddress)) {
            $args = array('subject' => $this->__('Request for product') . ' ' . $sku,
                'to' => $toAddress,
                'cc' => null,
                'bcc' => null,
                'body' => $html,
                'templateVars' => array()
            );
            $mailHelper->send($args);
        } else {
            throw new Exception('no receiver for checkout request email given');
        }
    }

    public function getAllProductInfo ( $skus, $forceRequests = false ) {
		$res = $this->_getInfo()->preloadProductsInfo($skus,$this->getLoginCustomer(),true,array(),$forceRequests);
        $this->prepareAvailibilityInfos($res['availibility']);
        $this->prepareDrumInfos($res['drums']);
        return $res;
    }

    public function getPriceProductInfo ( array $skus, array $qtys = array(), $performanceFormkey = '' ) {
        if ($performanceFormkey != '') {
            $strLog1 = '***************** getPriceProductInfo  ' . $performanceFormkey . '  ********************';
            $strLog2 = 'getPriceProductInfo -> preloadProductsInfo (start) : ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s');
        }
		$res = $this->_getInfo()->preloadProductsInfo($skus, $this->getLoginCustomer(),true, $qtys,true, true, false, false, $performanceFormkey);
        if ($performanceFormkey != '') {
            $strLog3 = 'getPriceProductInfo -> preloadProductsInfo (end) : ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s');
            $strLog4 = 'getPriceProductInfo -> preparePriceInfos (start) : ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s');
        }

        $cart = Mage::getSingleton('checkout/cart');
        $items = $cart->getQuote()->getAllItems();
        foreach ($items as $item){
            // If SAP_OCI active on offers, fetch different prices:
            if ($item->getSchrackOfferReference()) {
                $productOfferSku   = $item->getSku();
                $productOfferPrice = $item->getSchrackOfferPricePerUnit();
                $res['prices'][$productOfferSku]['price'] = $productOfferPrice;
                $res['prices'][$productOfferSku]['currentprice'] = $productOfferPrice;
                $res['prices'][$productOfferSku]['amount'] = $item->getQty() * $productOfferPrice;
            }
        }

        $this->preparePriceInfos($res['prices']);

        if ($performanceFormkey != '') {
            $strLog5 = 'getPriceProductInfo -> preparePriceInfos (end) : ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s');
            Mage::log($strLog1, null, 'performance.log');
            Mage::log($strLog2, null, 'performance.log');
            Mage::log($strLog3, null, 'performance.log');
            Mage::log($strLog4, null, 'performance.log');
            Mage::log($strLog5, null, 'performance.log');
        }

        return $res['prices'];
    }

    public function getAvailibilityProductInfo ( array $skus, $forceRequests = false ) {
		$res = $this->_getInfo()->preloadProductsInfo($skus,$this->getLoginCustomer(),true,array(),$forceRequests,false,true,true);
        $this->prepareAvailibilityInfos($res['availibility'],$res['drums']);
        return $res['availibility'];
    }

    public function getPriceAndAvailabilityProductInfo ( array $skus, array $qtys = array(), $forceAvailibilityRequests, $performanceFormkey = '' ) {
        if ($performanceFormkey != '') {
            $strLog1 = '***************** getPriceProductInfo  ' . $performanceFormkey . '  ********************';
            $strLog2 = 'getPriceProductInfo -> preloadProductsInfo (start) : ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s');
        }
        $res = $this->_getInfo()->preloadProductsInfo($skus, $this->getLoginCustomer(),true, $qtys, $forceAvailibilityRequests, true, true, false, $performanceFormkey);
        if ($performanceFormkey != '') {
            $strLog3 = 'getPriceProductInfo -> preloadProductsInfo (end) : ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s');
            $strLog4 = 'getPriceProductInfo -> preparePriceInfos (start) : ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s');
        }
        $this->preparePriceInfos($res['prices']);
        if ($performanceFormkey != '') {
            $strLog5 = 'getPriceProductInfo -> preparePriceInfos (end) : ' . $performanceFormkey . '  ' . date('Y-m-d H:i:s');
            Mage::log($strLog1, null, 'performance.log');
            Mage::log($strLog2, null, 'performance.log');
            Mage::log($strLog3, null, 'performance.log');
            Mage::log($strLog4, null, 'performance.log');
            Mage::log($strLog5, null, 'performance.log');
        }
        $this->prepareAvailibilityInfos($res['availibility'],$res['drums']);
        $mergedResult = array('prices' => $res['prices'], 'availibility' => $res['availibility']);
        return $mergedResult;
    }

    public function getDrumProductInfo ( array $skus ) {
		$res = $this->_getInfo()->preloadProductsInfo($skus,$this->getLoginCustomer(),true,array(),true,false,false,true);
        $this->prepareDrumInfos($res['drums']);
        return $res['drums'];
    }

	public function getDrumsBySkusAndStocks ( array $skus, array $stocks ) {
        return $this->_getInfo()->getDrumsBySkusAndStocks($skus,$stocks);
    }

    private function getLoginCustomer () {
        $customer = null;
        $session = Mage::getSingleton('customer/session');
        if ( $session->isLoggedIn() ) {
            $customer = $session->getCustomer();
        }
        return $customer;
    }

    private function preparePriceInfos ( array &$priceInfos ) {
        foreach ( $priceInfos as $sku => &$info ) {
            $this->preparePriceInfo($sku,$info);
        }
    }

    private function prepareAvailibilityInfos ( array &$availibilityInfos, array &$drumInfos ) {
        foreach ( $availibilityInfos as $sku => &$info ) {
            $availableDrumInfo = isset($drumInfos) && isset($drumInfos[$sku]) && isset($drumInfos[$sku]['available'])
                               ? $drumInfos[$sku]['available']
                               : array();
            $this->prepareAvailibilityInfo($sku,$info,$availableDrumInfo);
        }
    }

    private function prepareDrumInfos ( array &$drumInfos ) {
        foreach ( $drumInfos as $sku => &$info ) {
            $this->prepareDrumInfo($sku,$info['available'],true);
            $this->prepareDrumInfo($sku,$info['possible'],false);
        }
    }

    private function getPreparedProductBySku ( $sku ) {
        $product = false; // Init variable
        if (isset($this->_prepareCache[$sku])) {
            $product = $this->_prepareCache[$sku];
        }
        if ( is_object($product) ) {
            return $product;
        }
        $product = Mage::registry('product');
        if ( ! is_object($product) || $product->getSku() !== $sku || ! $product->getPrice() ) {
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku,'price');
        }
        $this->_prepareCache[$sku] = $product;
        return $product;
    }

    private function preparePriceInfo ( $sku, array &$priceInfo ) {
        $maySeePrices = Mage::helper('geoip')->maySeePrices();
        $priceInfo['mayseeprices'] = $maySeePrices;
        $coreHelper = Mage::helper('core');
        // format valid to
        $isPromo = false;
        if ( is_object($priceInfo['promovalidto']) ) {
            $locale = Mage::getStoreConfig('general/locale/code');
            $priceInfo['promovalidto'] = $priceInfo['promovalidto']->get(Zend_Date::DATE_MEDIUM,$locale);
            $isPromo = true;
        }
        $product = $this->getPreparedProductBySku($sku);
        if (!is_object($product)) {
            Mage::log('Product-Object not existent for SKU = "' . $sku . '" (' . __FILE__ . ' LINE ' . __LINE__ . ')', null, 'system.log');
        }
        $showListPrice = Mage::helper('schrackcatalog/price')->doShowListPrice();
        if ( $showListPrice ) {
            $priceInfo['listprice'] = floatval($product->getPrice());
        }
        $priceInfo['saleable'] = $product->isWebshopsaleable();
        $priceInfo['statuslocal'] = $product->getSchrackStsStatuslocal();
        $priceInfo['isRestricted'] = $product->isRestricted();
        $priceInfo['qtyunit'] = $product->getSchrackQtyUnit();
        // Get PV Transport Rate
        $priceInfo['pvrate'] = $product->getSchrackStsTransportRatePv();
        // Get Battery Transport Rate
        $priceInfo['batteryrate'] = $product->getSchrackStsTransRateBat();
        $priceInfo['formattedPriceunit'] = "{$priceInfo['priceunit']}&nbsp;{$priceInfo['qtyunit']}";
        $currentPrice = $priceInfo['price'];
        if ( isset($priceInfo['prices']) && is_array($priceInfo['prices']) ) {
            $removeScales = array();
            foreach ( $priceInfo['prices'] as $ndx => $scalePrice ) {
                if ( $scalePrice['price'] <= $currentPrice ) {
                    $currentPrice = $scalePrice['price'];
                } else {
                    $removeScales[] = $ndx;
                }
            }
            foreach ( $removeScales as $ndx ) {
                unset($priceInfo['prices'][$ndx]);
            }
        }
        //------------------------------------------------------------ vtc check
        $availabilityInfo = $this->getAvailibilityProductInfo([$sku]);
        $nearestDeliveryQty = $availabilityInfo[$sku]['nearestDeliveryQty'];
        if(isset($nearestDeliveryQty['providerName']) && $nearestDeliveryQty['providerName'] == "VTC"){
            $priceInfo['vtcMaxQty'] = $availabilityInfo[$sku]['deliveryQtySum'];
        }
        //----------------------------------------------------------------------
        $priceInfo['currentprice'] =  $currentPrice;
        if ( floatval($priceInfo['regularprice']) > 0 ) {
            $priceInfo['promotype'] = $isPromo ? 'promotion' : 'sales';
            $priceInfo['saving'] = floatval($priceInfo['regularprice']) - floatval($priceInfo['currentprice']);
        } else {
            $priceInfo['promotype'] = 'normal';
            $priceInfo['saving'] = 0.0;
        }
        if ( isset($priceInfo['surcharge']) && floatval($priceInfo['surcharge']) > 0.0 ) {
            $priceInfo['priceplussurcharge'] = $coreHelper->formatPrice(floatval($currentPrice) + floatval($priceInfo['surcharge']));
            $priceInfo['surcharge']          = $coreHelper->formatPrice($priceInfo['surcharge']);
        } else {
            unset($priceInfo['surcharge']);
        }
        $altText = $maySeePrices ? false : $this->__('On Request');
        $priceInfo['price']           = $altText ? $altText : $coreHelper->formatPrice($priceInfo['price']);
        if ( $showListPrice ) {
            $priceInfo['listprice']   = $altText ? $altText : $coreHelper->formatPrice($priceInfo['listprice']);
        }
        $priceInfo['regularprice']    = $altText ? $altText : $coreHelper->formatPrice($priceInfo['regularprice']);
        $priceInfo['currentprice']    = $altText ? $altText : $coreHelper->formatPrice($priceInfo['currentprice']);
        $priceInfo['saving']          = $altText ? $altText : $coreHelper->formatPrice($priceInfo['saving']);
        $priceInfo['amount']          = $altText ? $altText : $coreHelper->formatPrice($priceInfo['amount']);
        if ( isset($priceInfo['cuttingcosts']) ) {
            $priceInfo['cuttingcosts'] = $coreHelper->formatPrice($priceInfo['cuttingcosts']);
        }
        if ( isset($priceInfo['prices']) && is_array($priceInfo['prices']) ) {
            foreach ( $priceInfo['prices'] as $ndx => $scalePrice ) {
                $priceInfo['prices'][$ndx]['price'] = $altText ? $altText : $coreHelper->formatPrice($scalePrice['price']);
            }
        }
    }

    public function isAvailableRegardingInventory ($product, $availibilityInfo) {
        if(intval($product->isSchrackStsNotAvailable()) == 1) {
            // What kind of Article ? Bestellartikel / Normaler Artikel:
            $stockHelper = Mage::helper('schrackcataloginventory/stock');
            if ($product->isBestellArtikel()) {
                // Get inventory of LOCAL stock (if = 0, then return false => not available):
                // Get local stock number:
                $localStockNumber = $stockHelper->getLocalDeliveryStock()->getStockNumber();
                $localQuantity = $availibilityInfo[$localStockNumber]['qty'];
                if ($localQuantity == 0) {
                    return 0;
                }
            } else {
                // Get inventory of ALL stocks (if = 0, then return false => not available):$stockHelper = Mage::helper('schrackcataloginventory/stock');
                $pickupWarehouseIds   = array();
                $deliveryWarehouseIds = array();
                $pickupWarehouseIds   = $stockHelper->getPickupStockNumbers();
                $deliveryWarehouseIds = $stockHelper->getAllDeliveryStockNumbers();
                if (is_array($pickupWarehouseIds) && is_array($deliveryWarehouseIds)) {
                    $allWarehouseIds = array_merge($pickupWarehouseIds, $deliveryWarehouseIds);
                }
                $allQuantity = 0;
                foreach($allWarehouseIds as $index => $warehouseId) {
                    if (isset($availibilityInfo[$warehouseId])) {
                        $allQuantity = $allQuantity + $availibilityInfo[$warehouseId]['qty'];
                    }
                }

                if ($allQuantity == 0) {
                    return 0;
                }
            }
            // Fallback:
            return 1;
        } else {
            return 1;
        }
    }

    private function prepareAvailibilityInfo ( $sku, array &$availibilityInfo, array &$availableDrumInfo ) {
        uksort($availibilityInfo,function ($a,$b) { // bloody avail infos are not longer sorted...
            if ( $a == 80 ) $a = 998;
            if ( $b == 80 ) $b = 998;
            return $a-$b;
        });
        $product = $this->getPreparedProductBySku($sku);
        $stockHelper = Mage::helper('schrackcataloginventory/stock');
        $defaultPickupStockNo = (int) $stockHelper->getCustomerPickupStockNumber(null);
        $localDeliveryStockNo = (int) $stockHelper->getLocalDeliveryStock()->getStockNumber();
        $pickupCnt = 0;
        $deliveryCnt = 0;
        $overallQty = 0;
        $addDeliveryPlus = false;
        $nearestDeliveryQty = array("local" => 0, "central" => 0, "provider" => 0);
        //-------------------------- loop through available warehouses ( $whNo )

        foreach ( $availibilityInfo as $whNo => &$info ) {
            $info['stockNo'] = $whNo;
            $info['formattedQty'] = $this->formatQty($product,$info['qty'],true);
            if ( isset($info['pickup']) ) {
                $info['pickup']['isDefaultPickup'] = ($whNo == $defaultPickupStockNo);
                $info['pickup']['stockName'] = $stockHelper->getStockByNumber($whNo)->getStockName();
                $pickupCnt += $info['qty'];
            }
            //------------------------------- check if stock is a delivery stock
            if ( isset($info['delivery']) ) {
                //--------------------------------------------- check stock type
                if ( $whNo == $localDeliveryStockNo ) { //---------------- local
                    $info['delivery']['deliveryStockType'] = 'local';
                    if($info['qty'] > 0) {
                        $nearestDeliveryQty['local'] = array(
                            "stockNo" => $whNo,
                            "qty" => $info['qty'],
                            "formattedQty" => $info['formattedQty'],
                            "formattedDeliveryTime" => $this->getFormattedDeliveryTime($product, $whNo),
                            "deliveryTime" => $this->getDeliveryTime($product, $whNo)
                        );
                    }
                //------------------------------------------------------ central
                } else if ( $stockHelper->hasForeignDeliveryStock() && $whNo == $stockHelper->getForeignDeliveryStock()->getStockNumber() ) {
                    $info['delivery']['deliveryStockType'] = 'central';
                    if($info['qty'] > 0) {
                        $nearestDeliveryQty['central'] = array(
                            "stockNo" => $whNo,
                            "qty" => $info['qty'],
                            "formattedQty" => $info['formattedQty'],
                            "formattedDeliveryTime" => $this->getFormattedDeliveryTime($product, $whNo),
                            "deliveryTime" => $this->getDeliveryTime($product, $whNo)
                        );
                    }
                } else { //---------------------------------- 3rd party provider
                    $allStocks = $stockHelper->getAllStocks($sku);
                    $thirdPartyStock = $allStocks[$stockHelper->getThirdPartyDeliveryStockNumber()];

                    $info['delivery']['deliveryStockType'] = 'provider';
                    if($info['qty'] > 0 && is_object($thirdPartyStock)) {
                        $nearestDeliveryQty['provider'] = array(
                            "stockNo" => $whNo,
                            "qty" => $info['qty'],
                            "formattedQty" => $info['formattedQty'],
                            "formattedDeliveryTime" => $this->getFormattedDeliveryTime($product, $whNo),
                            "deliveryTime" => $this->getDeliveryTime($product, $whNo)
                        );
                    }
                    //------------------------------------------------ VTC check
                    if ( is_object($thirdPartyStock) ) {
                        $nearestDeliveryQty['providerName'] = $thirdPartyStock->getStockLocation();
                        $info['delivery']['thirdPartyLocation'] = $thirdPartyStock->getStockLocation();
                    }
                }
                $info['delivery']['formattedDeliveryTime'] = $this->getFormattedDeliveryTime($product,$whNo);
                if ( $addDeliveryPlus ) {
                    $info['formattedQty'] = '+' . $info['formattedQty'];
                } else if ( $info['qty'] > 0 ) {
                    $addDeliveryPlus = true;
                }
                //--------------------------------------------------------------
                $deliveryCnt += $info['qty'];
            }
            $overallQty += $info['qty'];
        }

        $availibilityInfo['hideQuantities'         ] = $product->isHideStockQantities();
        $availibilityInfo['isForcedOrder'          ] = $product->isBestellArtikel();
        $availibilityInfo['pickupQtySum'           ] = $pickupCnt;
        $availibilityInfo['nearestDeliveryQty'     ] = $nearestDeliveryQty;
        $availibilityInfo['deliveryQtySum'         ] = $deliveryCnt;
        $availibilityInfo['overallQtySum'          ] = $overallQty;
        $availibilityInfo['formattedPickupQtySum'  ] = $this->formatQty($product,$pickupCnt,true);
        $availibilityInfo['formattedDeliveryQtySum'] = $this->formatQty($product,$deliveryCnt,true);
        $availibilityInfo['formattedOverallQtySum' ] = $this->formatQty($product,$overallQty,true);
        $availibilityInfo['isDiscontinuation'      ] = $product->isDiscontinuation();
        $availibilityInfo['isStsAvailable'         ] = $this->isAvailableRegardingInventory($product, $availibilityInfo);
        $availibilityInfo['shopCountry'            ] = strtoupper(Mage::getStoreConfig('schrack/general/country'));

        if ( $product->isBestellArtikel() && $product->getCumulatedPickupableAndDeliverableQuantities(true) < 1 ) {
            $salesUnitQty = $product->getBatchSizeFromSupplier();
            $mninOrderQty = $product->getMinQtyFromSupplier();
        } else {
            $salesUnitQty = $mninOrderQty = $product->getSchrackStsMainVpeSize();
        }

        $availibilityInfo['minOrderQty']  = $mninOrderQty;
        $availibilityInfo['salesUnitQty'] = $salesUnitQty;
        $availibilityInfo['formattedMinOrderQty']  = $this->formatQty($product,$mninOrderQty,true);
        $availibilityInfo['formattedSalesUnitQty'] = $this->formatQty($product,$salesUnitQty,true);

        $availibilityInfo['leavings'] = array();

        if ( is_array($availableDrumInfo) && isset($availableDrumInfo[$localDeliveryStockNo]) ) {
            $standardSizes = Schracklive_SchrackCatalog_Model_Product::getSubProductStandardSizeFlagArrayForSku($sku);
            foreach ( $availableDrumInfo[$localDeliveryStockNo] as $drum ) {
                $qty = intval($drum->getStockQty());
                if ( $qty > 0 && ($drum->getSize() < 1 || $qty < $drum->getSize()) && ! isset($standardSizes[$qty]) ) {
                    $leaving = array(
                        'qty'           => $qty,
                        'formattedQty'  => $this->formatQty($product,$qty,true),
                        'drumNo'        => $drum->getWwsNumber(),
                        'drumName'      => $drum->getName()
                    );
                    $availibilityInfo['leavings'][] = $leaving;
                }
            }
        }
    }

    private function prepareDrumInfo ( $sku, array &$drumInfo, $available ) {
        foreach ( $drumInfo as $whNo => $modelArray ) {
            foreach ( $modelArray as $ndx => $modelObject ) {
                $rec = $modelObject->getData();
                if ( ! $available ) {
                    unset($rec['stock_qty']);
                }
                $drumInfo[$whNo][$ndx] = $rec;
            }
        }
    }

    public function getDynamicProductAttributeValue ( $entityId, $attributeCode ) {
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
        $sql = "SELECT attribute_id, backend_type FROM eav_attribute WHERE entity_type_id = $entityTypeId AND attribute_code = '$attributeCode' LIMIT 1;";
        $results = $readConnection->fetchAll($sql);
        if ( count($results) !== 1 ) {
            throw new Exception("Wrong attribute code '$attributeCode'.");
        }
        $attributeId = $backendType = mull;
        foreach ( $results AS $row ) {
            $attributeId = $row['attribute_id'];
            $backendType = $row['backend_type'];
        }
        if ( $backendType == 'static' ) {
            throw new Exception("Attribute type 'static' not supported!");
        }
        $sql = "SELECT value FROM catalog_product_entity_$backendType WHERE entity_id = $entityId AND attribute_id = $attributeId;";
        $res = $readConnection->fetchOne($sql);
        return $res;
    }

    // TODO: remove preloadForcedAvailabilityInfo() after ajax reconstruction
    public function preloadForcedAvailabilityInfo ( $sku ) {
        $this->_getInfo()->preloadForcedAvailabilityInfo($sku);
    }

    public function loadSingleStaticAttributeVal ( $sku, $dbField ) {
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "SELECT $dbField FROM catalog_product_entity WHERE sku = ?";
        $res = $readConnection->fetchOne($sql,$sku);
        return $res;
    }

    public function getSchrackHersteller ( $sku ) {
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $sql  = "SELECT DISTINCT optval.value FROM catalog_product_entity_varchar attr";
        $sql .= " JOIN eav_attribute eav ON attr.attribute_id = eav.attribute_id";
        $sql .= " JOIN catalog_product_entity prod ON attr.entity_id = prod.entity_id";
        $sql .= " JOIN eav_attribute_option opt ON opt.attribute_id = attr.attribute_id";
        $sql .= " JOIN eav_attribute_option_value optval ON optval.option_id = opt.option_id AND optval.option_id = attr.value";
        $sql .= " WHERE prod.sku = ?";
        $sql .= " AND eav.attribute_code LIKE 'schrack_hersteller'";

        $res = $readConnection->fetchOne($sql, $sku);
        return $res;
    }

    public function getProductIDsForSKUs ( array $skus ) {
        if ( count($skus) < 1 ) {
            return array();
        }
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql  = "SELECT entity_id FROM catalog_product_entity WHERE sku in ('" . implode("','",$skus) . "')";
        $res = $readConnection->fetchCol($sql);
        return $res;
    }

    public function preloadVpes ( array $skus ) {
        Mage::unregister('sku2vpeMap');
        if ( count($skus) < 1 ) {
            return;
        }
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql  = " SELECT sku, value as vpes FROM catalog_product_entity p"
              . " JOIN catalog_product_entity_text a ON a.entity_id = p.entity_id AND "
              . " a.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'schrack_vpes')"
              . " WHERE sku in ('" . implode("','",$skus) . "')";
        $dbRes = $readConnection->fetchAll($sql);
        $vpeMap = [];
        foreach ( $dbRes as $row ) {
            $vpeMap[$row['sku']] = $row['vpes'];
        }
        Mage::register('sku2vpeMap',$vpeMap);
    }
}

