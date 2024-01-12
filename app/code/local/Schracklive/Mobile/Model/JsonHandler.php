<?php

	/**
	 * author:	e.ayvere
	 * date:	23.09.2012
	 * 
	 * content:	JSON RESPONSE HANDLER of MOBILE HTTP REQUESTS
	 * 
	 */

	class Schracklive_Mobile_Model_JsonHandler extends Schracklive_Mobile_Model_Handler_Abstract {
 
		/**
		 * @param $article_id
		 * @param $stock_id
		 * @param $drum_id
		 * @param $quantity
		 * @return JSON-ResultFrame 
		 */
		
		public function checkAddToCart( $article_id, $stock_id=null, $drum_id=null, $quantity=1 )
		{


			$message_1 = $this->__("INFORMATION: The article hasn't been added to the cart yet. Please accept/change the amount and/or the packaging. Afterwards press „Buy now“ again to add the article to the cart.");
			$message_2 = $this->__("AMOUNT: The entered amount for the article %s1 is not a multiple of the sales unit. Please enter a multiple of %s2.");
			$message_3 = $this->__("PACKAGING: The selected packaging for the article %s1 was automatically changed to one of the following packagings: %s2. You can find the possible packagings next to the amount field.");
			$message_4 = $this->__("PACKAGING: The selected packaging for the article %s1 can't be fulfilled, the new packaging was selected automatically.");
			$message_5 = $this->__("AMOUNT: Your entered amount for the article %s1 was automatically changed to %s2 (a multiple of the sales unit).");

			$message_6 = $this->__("INFORMATION: Product %s unknown!");

			// --- Variablen und Speicher ----------------------

			$result = array(
				'method' => 'checkAddToCart',
				'version' => '1.0',
				'data' => array('checkAddToCart' => array()),
				'errors' => array()
			);

			$data = array(
				'valid' => false,
				'message' => array(),
				'setNewDrum' => false,
				'newDrumId' => '',
				'setNewQuantity' => false,
				'newQuantity' => ''
			);

			$errors = array();
			$int_max = PHP_INT_MAX;


			// ----- Product -----------------------------------
			if (!$article_id) {

				// FALL 1A :: Parameter Article_ID falsch

				array_push($errors, array('code' => '300', 'text' => 'Missing parameter >>article_id<<!'));
				$data['valid'] = false;
				$result['data']['checkAddToCart'] = $data;
				$result['errors'] = $errors;
				// $result['case'] = 1;
				return $result;
			}

			//TODO: für diesen Fall die Ursache suchen wieso Defaultwert nicht zugewiesen wird beim Methodenaufruf
			if (!$quantity) {
				$quantity = 1;
			}

			if (!$quantity || !is_numeric($quantity) || !(strval(intval($quantity)) == strval($quantity))) {

				// FALL 1B :: Parameter Quantity falsch

				array_push($errors, array('code' => '300', 'text' => 'Missing or wrong parameter >>quantity<<!'));
				$data['valid'] = false;
				$result['data']['checkAddToCart'] = $data;
				$result['errors'] = $errors;
				// $result['case'] = 1;
				return $result;
			}


			$product = Mage::getModel('schrackcatalog/product');
			$productId = $product->getIdBySku($article_id);

			if (!$productId) {
				$productId = $product->getIdByEan($article_id);
			}

			if ($productId) {
				$product = $product->load($productId);
			} else {

				// FALL 2 :: Produkt unbekannt

				array_push($data['message'], array('line' => str_replace('%s', $article_id, $message_6)));
				// array_push( $errors, array( 'code' => '300', 'text' => 'Wrong value for article_id: ' . $article_id ));
				$data['valid'] = false;
				$result['data']['checkAddToCart'] = $data;
				$result['errors'] = $errors;
				// $result['case'] = 2;
				return $result;
			}


			// ------ Stock ------------------------------------						
			$stock_id_customer = Mage::getSingleton('customer/session')->getCustomer()->getSchrackPickup();
			// var_dump( $stock_id_customer);
			$stock_id_delivery = Mage::helper('schrackshipping/delivery')->getWarehouseId();
			// var_dump( $stock_id_delivery);
			$schrack_catalog = Mage::helper('schrackcatalog/info');

			if ( $product->isCable() ) {
				// ------- Drums ----------------------------------
				$drums = $schrack_catalog->getPossibleDrums($product, array($stock_id_delivery, $stock_id_customer));
				// var_dump( $drums);
				$drumCount = 0;
				$lessenAble = false;
				$selectedDrum = null;

				foreach ($drums as $stockId => $warehouseDrums) {
					if ($stockId == $stock_id_delivery) {
						foreach ($warehouseDrums as $drum) {
							if ($drum->getLessenDelivery() == 1) {
								$lessenAble = true;
							}
							$drumCount = $drumCount + 1;
						}
					}
					if ($stockId == $stock_id_customer || $stockId == $stock_id_delivery) {
						if ($drum_id) {
							foreach ($warehouseDrums as $drum) {
								if ($drum->wws_number == $drum_id) {
									$selectedDrum = $drum;
								}
							}
						}
					}
				}


				if ($selectedDrum) {
					$tmpSize = $selectedDrum->getSize() == 0 ? $quantity : $selectedDrum->getSize();
					if (($quantity % $tmpSize) == 0) {

						// FALL 3 :: selektierte Drum passt

						$data['valid'] = true;
						$result['data']['checkAddToCart'] = $data;
						$result['errors'] = $errors;
						// $result['case'] = 3;
						return $result;
					}
				}

				// TODO: ERROR HERE
				$alternativeDrums = (!isset($drums[$stock_id_delivery]) || count($drums[$stock_id_delivery]) == 0)
					? array() : $drums[$stock_id_delivery];
				// var_dump( $alternativeDrums );

				if (!$lessenAble && count($alternativeDrums) == 1) {

					/* @var $alternativeDrum Schracklive_SchrackCatalog_Model_Drum */
					$alternativeDrum = $alternativeDrums[1];

					if ($alternativeDrum->getSize() == 0) {

						// FALL 4 :: genau ein Eintrag aber nicht ablaengbar (dieser Zweig ist ein Ausnahmefall)

						$data['valid'] = true;
						$result['data']['checkAddToCart'] = $data;
						$result['errors'] = $errors;
						// $result['case'] = 4;
						return $result;
					} else if ($quantity % $alternativeDrum->getSize() == 0) {

						// FALL 5 :: genau ein Eintrag aber nicht ablaengbar (? Menge passt ?)

						$data['valid'] = true;
						$result['data']['checkAddToCart'] = $data;
						$result['errors'] = $errors;
						// $result['case'] = 5;
						return $result;
					} else {

						$newQuantity = ceil(floatval(floatval($quantity) / $alternativeDrum->getSize())) * $alternativeDrum->getSize();

						/* @var $selectedDrum Schracklive_SchrackCatalog_Model_Drum */

						if ($selectedDrum == $alternativeDrum) {

							array_push($data['message'], array('line' => str_replace('%s2', $alternativeDrum->size, str_replace('%s1', $product->getSku(), $message_5))));
							array_push($data['message'], array('line' => $message_1));
						} else {
							$data['setNewDrum'] = true;
							$data['newDrumId'] = $alternativeDrum->wws_number;

							array_push($data['message'], array('line' => str_replace('%s2', $alternativeDrum->description, str_replace('%s1', $product->getSku(), $message_3))));
							array_push($data['message'], array('line' => $message_1));
						}

						if ($quantity < 0) {
							$data['setNewQuantity'] = true;
							$data['newQuantity'] = $alternativeDrum->getSize();
						} else {
							$data['setNewQuantity'] = true;
							$data['newQuantity'] = $newQuantity;
						}

						// FALL 6 :: ??? Menge geändert, evtl auch Drum geändert, mit Änderungsvorschlag

						$data['valid'] = false;
						$result['data']['checkAddToCart'] = $data;
						$result['errors'] = $errors;
						// $result['case'] = 6;
						return $result;

					}
				}

				if (count($alternativeDrums) == 0) {

					$salesUnit = Mage::helper('schrackcatalog/product')->getSalesUnit($product); // gets delivery sales unit

					if ($salesUnit == 0) {

						// FALL 7 :: keine Trommeln da und Verkaufseinheit 0

						$data['valid'] = true;
						$result['data']['checkAddToCart'] = $data;
						$result['errors'] = $errors;
						// $result['case'] = 7;
						return $result;
					} else if (($quantity % $salesUnit) == 0) {

						// FALL 8 :: keine Trommeln da

						$data['valid'] = true;
						$result['data']['checkAddToCart'] = $data;
						$result['errors'] = $errors;
						// $result['case'] = 8;
						return $result;
					} else {

						$newQuantity = ceil(floatval(floatval($quantity) / $salesUnit)) * $salesUnit;

						array_push($data['message'], array('line' => str_replace('%s2', $salesUnit, str_replace('%s1', $product->getSku(), $message_5))));
						array_push($data['message'], array('line' => $message_1));

						$data['setNewQuantity'] = true;
						$data['newQuantity'] = $newQuantity;

						// FALL 9 :: keine Trommeln da, aber Menge nach lieferbarer Menge des Produkts geändert

						$data['valid'] = false;
						$result['data']['checkAddToCart'] = $data;
						$result['errors'] = $errors;
						// $result['case'] = 9;
						return $result;

					}
				}


				if (!$lessenAble && count($alternativeDrums) > 1) {

					$possibleDrumsCount = 0;
					$largestPossibleDrum = null;
					$minSalesUnit = $int_max;

					foreach ($alternativeDrums as $altDrum) {
						$minSalesUnit = min($minSalesUnit, $altDrum->getSize());

						if ($altDrum->getSize() == 0) {

							$possibleDrumsCount++;

							if (!$largestPossibleDrum) {
								$largestPossibleDrum = $altDrum;
							}
						} elseif (($quantity % $altDrum->getSize()) == 0) {

							$possibleDrumsCount++;

							if ($largestPossibleDrum) {
								if ($altDrum->getSize() > $largestPossibleDrum->getSize()) {
									$largestPossibleDrum = $altDrum;
								}
							} else {
								$largestPossibleDrum = $altDrum;
							}
						}
					}


					if ($possibleDrumsCount == 1) {

						// FALL 10

						$data['setNewDrum'] = true;
						$data['newDrumId'] = $largestPossibleDrum->wws_number;

						$data['valid'] = true;
						$result['data']['checkAddToCart'] = $data;
						$result['errors'] = $errors;
						// $result['case'] = 10;
						return $result;
					}


					if ($possibleDrumsCount > 1) {

						// FALL 11

						$data['setNewDrum'] = true;
						$data['newDrumId'] = $largestPossibleDrum->wws_number;

						array_push($data['message'], array('line' => str_replace('%s2', $largestPossibleDrum->description, str_replace('%s1', $product->getSku(), $message_3))));
						array_push($data['message'], array('line' => $message_1));

						$data['valid'] = false;
						$result['data']['checkAddToCart'] = $data;
						$result['errors'] = $errors;
						// $result['case'] = 11;
						return $result;
					}


					if ($possibleDrumsCount == 0) {

						if ($quantity > 0) {

							$bestDrum = null;
							$bestQuantity = $int_max;

							foreach ($alternativeDrums as $$altDrum) {

								$newQuantity = ceil(floatval(floatval($quantity) / $altDrum->getSize())) * $altDrum->getSize();

								if ($newQuantity < $bestQuantity) {
									$bestQuantity = $newQuantity;
									$bestDrum = $altDrum;
								}
							}

							// FALL 12

							$data['setNewDrum'] = true;
							$data['newDrumId'] = $bestDrum->wws_number;
							$data['setNewQuantity'] = true;
							$data['newQuantity'] = $bestQuantity;

							array_push($data['message'], array('line' => str_replace('%s2', $bestDrum->size, str_replace('%s1', $product->getSku(), $message_5))));
							array_push($data['message'], array('line' => $message_1));

							$data['valid'] = false;
							$result['data']['checkAddToCart'] = $data;
							$result['errors'] = $errors;
							// $result['case'] = 12;
							return $result;
						} else {

							// FALL 13

							$data['setNewQuantity'] = true;
							$data['newQuantity'] = $minSalesUnit;

							array_push($data['message'], array('line' => str_replace('%s2', $minSalesUnit, str_replace('%s1', $product->getSku(), $message_5))));
							array_push($data['message'], array('line' => $message_1));

							$data['valid'] = false;
							$result['data']['checkAddToCart'] = $data;
							$result['errors'] = $errors;
							// $result['case'] = 13;
							return $result;
						}
					}
				}

				if ($lessenAble) {

					if ($quantity < 0) {

						// FALL 14

						$data['setNewQuantity'] = true;
						$data['newQuantity'] = Mage::helper('schrackcatalog/product')->getSalesUnit($product); // gets delivery sales unit

						array_push($data['message'], array('line' => str_replace('%s2', $data['newQuantity'], str_replace('%s1', $product->getSku(), $message_5))));
						array_push($data['message'], array('line' => $message_1));

						$data['valid'] = false;
						$result['data']['checkAddToCart'] = $data;
						$result['errors'] = $errors;
						// $result['case'] = 14;
						return $result;

					}

					$possibleDrumsCount = 0;
					$largestPossibleDrum = null;

					foreach ($alternativeDrums as $altDrum) {

						if ($altDrum->getSize() == 0) {

							/* -------------------------------------
								$possibleDrumsCount++;
								if( $largestPossibleDrum == null ) {
									$largestPossibleDrum == $altDrum;
								}
								------------------------------------- */

						} else if ((($quantity % $altDrum->getSize()) == 0) && ($altDrum->getSize() > 1)) {

							$possibleDrumsCount++;

							if ($largestPossibleDrum) {
								if ($altDrum->getSize() > $largestPossibleDrum->getSize()) {
									$largestPossibleDrum = $altDrum;
								}
							} else {
								$largestPossibleDrum = $altDrum;
							}
						}
					}

					if ($possibleDrumsCount >= 1) {

						if ($possibleDrumsCount > 1) {

							// FALL 15

							$data['setNewDrum'] = true;
							$data['newDrumId'] = $largestPossibleDrum->wws_number;

!!!							array_push($data['message'], array('line' => str_replace('%s2', $largestPossibleDrum->description, str_replace('%s1', $product->getSku(), $message_3))));
							array_push($data['message'], array('line' => $message_1));

							$data['valid'] = false;
							$result['data']['checkAddToCart'] = $data;
							$result['errors'] = $errors;
							// $result['case'] = 15;
							return $result;

						} else {

							// FALL 16

							$data['valid'] = true;
							$result['data']['checkAddToCart'] = $data;
							$result['errors'] = $errors;
							// $result['case'] = 16;
							return $result;

						}
					}
				}

				// FALL 17

				if (($possibleDrumsCount == 0) && ($quantity > 0)) {
					$data['setNewDrum'] = true;
					$data['newDrumId'] = null;

					$data['valid'] = true;
					$result['data']['checkAddToCart'] = $data;
					$result['errors'] = $errors;
					// $result['case'] = 17;
					return $result;
				}
			} else {
				$resultQuantityData = $product->calculateClosestHigherQuantityAndDifference(intval($quantity), true, array(), 'addCartQuantity4');
				if ($resultQuantityData && array_key_exists('invalidQuantity', $resultQuantityData) && $resultQuantityData['invalidQuantity'] == true) {
					if (array_key_exists('closestHigherQuantity', $resultQuantityData) && $resultQuantityData['closestHigherQuantity'] != $quantity) {

						$data['setNewQuantity'] = true;
						$data['newQuantity'] = $resultQuantityData['closestHigherQuantity']; // gets delivery sales unit

						array_push($data['message'], array('line' => str_replace('%s2', $data['newQuantity'], str_replace('%s1', $product->getSku(), $message_5))));
						array_push($data['message'], array('line' => $message_1));

						$data['valid'] = false;
						$result['data']['checkAddToCart'] = $data;
						$result['errors'] = $errors;
						// $result['case'] = 14;
						return $result;
					}
				}
			}
			// FALL 18

			if ( $product->isDiscontinuation() ) {
				$productHelper = Mage::helper('schrackcatalog/product');
				if ( ! isset($drum_id) ) {
					$drum_id = -1;
				}
				$available = $productHelper->getSummarizedStockQuantities($product,$drum_id);
				if ( $quantity > $available ) {
					if ( $available <= 0 ) {
						$msg1 = $this->__('This item is currently unavailable and can therefore unfortunately not be ordered.');
					} else {
						$formattedQty = $productHelper->formatQty($product,$available);
						if ( $available == 1 ) {
							$msg1 = $this->__("Unfortunately only %s is available.",$formattedQty);
						} else {
							$msg1 = $this->__("Unfortunately only %s are available.",$formattedQty);
						}
						$msg1 .= ' ';
						$msg1 .= $this->__('Please reduce your order quantity.');
					}
					array_push( $data['message'], array( 'line' => $msg1 ));

					$data['valid'] = false;
					$result['data']['checkAddToCart'] = $data;
					$result['errors'] = $errors;
					return $result;
				}
			}

			$data['valid'] = true;
			$result['data']['checkAddToCart'] = $data;
			$result['errors'] = $errors;
			// $result['case'] = 18;
			return $result;


			/* ------------------DRUM INFORMATIONS-----------------------------
			 * $drum->;
			 * $drum->name;
			 * $drum->description;
			 * $drum->type; // if empty than 'F'
			 * $drum->size;
			 * $drum->stock_qty;
			 * (bool)$drum->getLessenDelivery();
			 * (bool)$drum->getLessenPickup();	
			 ------------------DRUM INFORMATIONS--------------------------- */

			// HOW TO DEBUG: Mage::Log( var_dump ( $warehouse_drums ) );
		}

		
        /**
         * @param string $article_id
         * @param string $drum_id
         * @param int $quantity
         * @param int $cart_id
         * @param int $customer_id
         * @param int $partslist_id
         * @return JSON-ResultFrame
         * @throws Mage_Core_Exception
         */
        public function addToCartReturningJson($article_id, $drum_id = null, $quantity = 1, $cart_id = 1, $partslist_id = null, $customer_id = null ) {
            if ($customer_id === null || !strlen($customer_id)) {
                $customer_id = null;
            }
            
            $result = array(
				'method' => 'addToCartReturningJson',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);						
			
            try {                
                $customer = $this->_getCustomer($customer_id);
                if (!$customer) {
                    throw Mage::exception('Mage_Core', 'No customer found for this request.');
                }
                
                $product = $this->_getProduct($article_id, $customer);

                if ($cart_id == 1) {
                    $cart = $this->_getPreparedCart($customer);
                    if ($drum_id) {
                        $cart->addProduct($product, new Varien_Object(array('qty' => $quantity, 'schrack_drum_number' => $drum_id)))->setSchrackDrumNumber($drum_id);
                    } else {
                        $cart->addProduct($product, $quantity);
                    }
                    $cart->collectTotals();
                    $cart->save();
                } elseif ($cart_id == 2) {
                    $wishlist = Mage::getModel('schrackwishlist/wishlist')->loadByCustomer($customer, true);

                    $wishlist->addNewItem($product);
                    $wishlist->save();
                } elseif ($cart_id == 3) {
                    try {
                        if ($partslist_id === null)
                            $partslist = Mage::getModel('schrackwishlist/partslist')->loadActiveListByCustomer($customer);
                        else
                            $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId($customer, $partslist_id);
                        $partslist->addNewItem($product,array('qty' => $quantity));
                        $partslist->save();
                    } catch (Exception $e) {
                        throw Mage::exception('Mage_Core', 'Invalid cart. - ' . $e->getMessage());
                    }
                } else {
                    throw Mage::exception('Mage_Core', 'Invalid cart.');
                }
                $result['data']['addToCartReturningJson'] = $this->_createCart($cart_id, $customer, $partslist_id);
                return $result;
            } catch (Exception $e) {
                $result['errors'] = array($e->getMessage());
                return $result;
            }

        }
        
	/**
	 * @param string $article_id
	 * @param string $drum_id
	 * @param int $quantity
	 * @param int $cart_id
	 * @param int $customer_id
     * @param int $partslist_id
	 * @return Mage_Core_Model_Abstract|mixed|null
	 * @throws Mage_Core_Exception
	 */
        public function removeFromCartReturningJson($article_id, $drum_id = null, $quantity = 0, $cart_id = 1, $customer_id = null, $partslist_id = null) {
            if ( ! $cart_id ) { // because bloody zend-server-parameter-auto-magic-not-working.
                $cart_id = 1;
            }
            $customer = $this->_getCustomer($customer_id);
            $productId = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore()->getId())->getIdBySku($article_id);

            if ($cart_id == 1) {
                $cart = $this->_getPreparedCart($customer);
                $items = $cart->getAllItems();
                foreach ($items as $item) {
                    if ($item->getProduct()->getId() == $productId) {
                        if ($drum_id) {
                            if ($item->getSchrackDrumNumber() == $drum_id) {
                                $cart->removeItem($item->getId());
                            }
                        } else {
                            $cart->removeItem($item->getId());
                        }
                    }
                }
                $cart->collectTotals();
                $cart->save();
            } elseif ($cart_id == 2) {
                $wishlist = Mage::getModel('schrackwishlist/wishlist')->loadByCustomer($customer, true);
                $items = $wishlist->getItemCollection();
                foreach ($items as $item) {
                    if ($item->getProduct()->getId() == $productId) {
                        $item->delete();
                        break;
                    }
                }
                $wishlist->save();
            } elseif ($cart_id == 3) {                
                if ($partslist_id === null)
                    $partslist = Mage::getModel('schrackwishlist/partslist')->loadActiveListByCustomer($customer);
                else
                    $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId($customer, $partslist_id);
                $items = $partslist->getItemCollection();
                foreach ($items as $item) {
                    if ($item->getProduct()->getId() == $productId) {
                        $item->delete();
                        break;
                    }
                }
                $partslist->save();
            } else {
                throw Mage::exception('Mage_Core', 'Invalid cart.');
            }

            return $this->getCartReturningJson($cart_id, $customer_id, $partslist_id);
        }        
        
        public function getCartReturningJson($cart_id = 1, $customer_id = null, $partslist_id = null) {
            if ($customer_id === null || !strlen($customer_id)) {
                $customer_id = null;
            }
            
            $result = array(
				'method' => 'getCartReturningJson',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);		
            try {
                $customer = $this->_getCustomer($customer_id);
                if (!$customer) {
                    throw Mage::exception('Mage_Core', "No customer '{$customer_id}' found for this request.");
                }

                $result['data']['getCartReturningJson'] = $this->_createCart($cart_id, $customer, $partslist_id);
                return $result;
            } catch (Exception $e) {
                $result['errors'] = array($e->getMessage());
                return $result;
            }
        }

        const APP_NAME_CREATED_AT          = 'created_at';
        const APP_NAME_CURRENCY            = 'currency';
        const APP_NAME_DESCRIPTION         = 'description';
        const APP_NAME_DOC_TYPE            = 'doc_type';
        const APP_NAME_DOCUMENT_DATE_TIME  = 'document_date_time';
        const APP_NAME_EMAIL_SENT          = 'email_sent';
        const APP_NAME_IS_COMPLETE         = 'is_complete';
        const APP_NAME_NAME                = 'name';
        const APP_NAME_ORDER_ENTITY_ID     = 'order_entity_id';
        const APP_NAME_PRICE               = 'price';
        const APP_NAME_QTY                 = 'qty';
        const APP_NAME_ROW_TOTAL           = 'row_total';
        const APP_NAME_SKU                 = 'sku';
        const APP_NAME_UPDATED_AT          = 'updated_at';
        const APP_NAME_WWS_DOCUMENT_DATE   = 'wws_document_date';
        const APP_NAME_WWS_CREATION_DATE   = 'wws_creation_date';
        const APP_NAME_WWS_DOCUMENT_NUMBER = 'wws_document_number';
        const APP_NAME_WWS_ORDER_NUMBER    = 'wws_order_number';
        const APP_NAME_WWS_REFERENCE       = 'wws_reference';
		const APP_NAME_WWS_STATUS          = 'wws_status';
		const APP_NAME_WWS_OFFER_2_ACCEPT  = 'acceptable_offer';
		const APP_NAME_WWS_PARCELS         = 'wws_parcels';

        static $appName2DbNameMap = null;
        
        private function _getAppName2DbNameMap () {
            if ( ! self::$appName2DbNameMap ) {
                self::$appName2DbNameMap = array(
                    self::APP_NAME_CREATED_AT          => 'created_at',
                    self::APP_NAME_CURRENCY            => 'currency',
                    self::APP_NAME_DESCRIPTION         => 'description',
                    self::APP_NAME_DOC_TYPE            => 'doc_type',
                    self::APP_NAME_DOCUMENT_DATE_TIME  => 'document_date_time',
                    self::APP_NAME_EMAIL_SENT          => 'email_sent',
                    self::APP_NAME_IS_COMPLETE         => 'schrack_is_complete',
                    self::APP_NAME_NAME                => 'name',
                    self::APP_NAME_ORDER_ENTITY_ID     => 'OrderNumber',
                    self::APP_NAME_PRICE               => 'price',
                    self::APP_NAME_QTY                 => 'qty',
                    self::APP_NAME_ROW_TOTAL           => 'row_total',
                    self::APP_NAME_SKU                 => 'sku',
                    self::APP_NAME_UPDATED_AT          => 'updated_at',
                    self::APP_NAME_WWS_DOCUMENT_DATE   => 'schrack_wws_document_date',
                    self::APP_NAME_WWS_CREATION_DATE   => 'schrack_wws_creation_date',
                    self::APP_NAME_WWS_DOCUMENT_NUMBER => 'wws_document_number',
                    self::APP_NAME_WWS_ORDER_NUMBER    => 'schrack_wws_order_number',
                    self::APP_NAME_WWS_REFERENCE       => 'schrack_wws_reference',
                    self::APP_NAME_WWS_STATUS          => 'schrack_wws_status',
					self::APP_NAME_WWS_PARCELS         => 'schrack_wws_parcels'
                );                
            }
            return self::$appName2DbNameMap;
        }

        /*
        static $dbName2AppNameMap = null;
        
        private function _getDbName2AppNameMap () {
            if ( ! self::$dbName2AppNameMap ) {
                self::$dbName2AppNameMap = array();
                $a2d = self::getAppName2DbNameMap();
                foreach ( $a2d as $app => $db ) {
                    self::$dbName2AppNameMap[$db] = $app;
                }
            }
            return self::$dbName2AppNameMap;
        }
        */
        
        public function getOrders ( $is_offered = 1, $is_ordered = 1, $is_commissioned = 1, $is_delivered = 1, $is_invoiced = 1, $is_credited = 1,
                                    $get_offer_docs = 1, $get_order_docs = 1, $get_delivery_docs = 1, $get_invoice_docs = 1, $get_credit_memo_docs = 1,
                                    $from_date = null, $to_date = null, $text = null, 
                                    $sort_column_name = null, $is_sort_asc = false, 
                                    $customer_id = null, $perPage = 1000, $page = 1 ) {
            $app2db = $this->_getAppName2DbNameMap();
            if ($customer_id === null || !strlen($customer_id)) {
                $customer_id = null;
            }
            $result = array(
				'method' => 'getOrders',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);		
            $helper = Mage::helper('schracksales/order');
            try {
                $customer = $this->_getCustomer($customer_id);
                if (!$customer) {
                    throw Mage::exception('Mage_Core', "No customer '{$customer_id}' found for this request.");
                }
                $searchParameters = $helper->createSearchParameters();
                $searchParameters->isOffered         = (int) $is_offered;
                $searchParameters->isOrdered         = (int) $is_ordered; 
                $searchParameters->isCommissioned    = (int) $is_commissioned; 
                $searchParameters->isDelivered       = (int) $is_delivered; 
                $searchParameters->isInvoiced        = (int) $is_invoiced; 
                $searchParameters->isCredited        = (int) $is_credited;
                $searchParameters->getOfferDocs      = (int) $get_offer_docs; 
                $searchParameters->getOrderDocs      = (int) $get_order_docs; 
                $searchParameters->getDeliveryDocs   = (int) $get_delivery_docs; 
                $searchParameters->getInvoiceDocs    = (int) $get_invoice_docs; 
                $searchParameters->getCreditMemoDocs = (int) $get_credit_memo_docs;
                $searchParameters->fromDate          = $this->_emptyToNull($from_date); 
                $searchParameters->toDate            = $this->_emptyToNull($to_date); 
                $searchParameters->text              = $this->_emptyToNull($text);
                $searchParameters->sortColumnName    = isset($sort_column_name) ? $app2db[$sort_column_name] : '';
                $searchParameters->isSortAsc         = $is_sort_asc;
                $helperRes = $helper->searchSalesOrdersNew($searchParameters, null, $page, $perPage);

                $result['data']['getOrders'] = array();
                foreach ( $helperRes as $line ) {
                    $type = $helper->getDocType($line);
                    $srcData = $line->getData();
                    $destData = array();
                    
                    $destData[self::APP_NAME_ORDER_ENTITY_ID]     = $srcData[$app2db[self::APP_NAME_ORDER_ENTITY_ID]];
                    $destData[self::APP_NAME_WWS_ORDER_NUMBER]    = $srcData[$app2db[self::APP_NAME_WWS_ORDER_NUMBER]];
                    $destData[self::APP_NAME_WWS_STATUS]          = $srcData[$app2db[self::APP_NAME_WWS_STATUS]];      
                    $destData[self::APP_NAME_IS_COMPLETE]         = 1;
                    $destData[self::APP_NAME_CREATED_AT]          = $this->reformatDate($srcData[$app2db[self::APP_NAME_CREATED_AT]]);
                    $destData[self::APP_NAME_UPDATED_AT]          = $this->reformatDate($srcData[$app2db[self::APP_NAME_UPDATED_AT]]);
                    $destData[self::APP_NAME_DOC_TYPE]            = $type;
                    $destData[self::APP_NAME_WWS_DOCUMENT_NUMBER] = isset($srcData[$app2db[self::APP_NAME_WWS_DOCUMENT_NUMBER]]) ? $srcData[$app2db[self::APP_NAME_WWS_DOCUMENT_NUMBER]]   : '';
                    $destData[self::APP_NAME_DOCUMENT_DATE_TIME]  = isset($srcData[$app2db[self::APP_NAME_DOCUMENT_DATE_TIME]])  ? $srcData[$app2db[self::APP_NAME_DOCUMENT_DATE_TIME]]    : '';
                    $destData[self::APP_NAME_WWS_REFERENCE]       = isset($srcData[$app2db[self::APP_NAME_WWS_REFERENCE]])       ? $srcData[$app2db[self::APP_NAME_WWS_REFERENCE]] : '';

					$destData[self::APP_NAME_WWS_OFFER_2_ACCEPT] = '0';
					if ( strcasecmp($line->getSchrackWwsStatus(),'LA1') == 0 && $line->getSchrackWwsOfferFlagValid() ) {
						$model = Mage::getModel('sales/order')->load($line->getOrderId());
						if ( $model->isOfferAndCanBeOrdered() ) {
							$destData[self::APP_NAME_WWS_OFFER_2_ACCEPT] = '1';
						}
					}

                    $result['data']['getOrders'][] = $destData;
                }
            } catch ( Exception $e ) {
                $result['errors'] = array($e->getMessage());
            }
            return $result;
        }
        
        public function getTrackAndTraceInfo ( $order_entity_id, $parcels, $customer_id = null ) {
            $result = array(
				'method' => 'getTrackAndTraceInfo',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);

            try {
				$customer = $this->_getCustomer($customer_id);
				$this->_checkCustomerOrder($customer,$order_entity_id);
				// ### $this->_checkOrderParcels($order_entity_id,$parcels);

				$helper = Mage::helper('schrackshipping/trackandtrace');
                $colloNumbersArray = explode(",",$parcels);
				$results = $helper->fetchResultsForColloNumbers($colloNumbersArray);

                $helper->reorgResult($results,$colloNumbersArray);
                $newResults = $helper->shrinkResults($results);

				$result['data']['getTrackAndTraceInfo'] = $newResults;
            } catch ( Exception $e ) {
                $result['errors'] = array($e->getMessage());
            }
			return $result;
		}

        public function getSingleOrderData ( $order_entity_id, $wws_document_number = null, $doc_type = null, $customer_id = null ) {
            $app2db = $this->_getAppName2DbNameMap();
            if ($customer_id === null || !strlen($customer_id)) {
                $customer_id = null;
            }
            if ( $doc_type == null ) {
                $doc_type = Schracklive_SchrackSales_Helper_Order::DOCTYPE_ORDER;
            }
            else {
                $doc_type = (int) $doc_type;
            }
            $result = array(
				'method' => 'getSingleOrderData',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);		

            try {
                $document = $this->_getDocument($order_entity_id, $wws_document_number,$doc_type,$customer_id);

                switch ( $doc_type ) {
                    case Schracklive_SchrackSales_Helper_Order::DOCTYPE_OFFER :
                        $docNumberName = 'schrack_wws_offer_number';
                        break;
                    case Schracklive_SchrackSales_Helper_Order::DOCTYPE_ORDER :
                        $docNumberName = 'schrack_wws_order_number';
                        break;
                    case Schracklive_SchrackSales_Helper_Order::DOCTYPE_SHIPMENT :
                        $docNumberName = 'schrack_wws_shipment_number';
                        break;
                    case Schracklive_SchrackSales_Helper_Order::DOCTYPE_INVOICE :
                        $docNumberName = 'schrack_wws_invoice_number';
                        break;
                    case Schracklive_SchrackSales_Helper_Order::DOCTYPE_CREDIT_MEMO :
                        $docNumberName = 'schrack_wws_creditmemo_number';
                        break;
                    break;
                }
                $result['data']['getSingleOrderData'] = array();
                $srcData = $document->getData();
                $destData = array();
                $destData[self::APP_NAME_ORDER_ENTITY_ID]     = $order_entity_id;
                $destData[self::APP_NAME_DOC_TYPE]            = $doc_type;
                $destData[self::APP_NAME_WWS_ORDER_NUMBER]    = $srcData[$app2db[self::APP_NAME_WWS_ORDER_NUMBER]];  
                $destData[self::APP_NAME_WWS_STATUS]          = $srcData[$app2db[self::APP_NAME_WWS_STATUS]];        
                $destData[self::APP_NAME_IS_COMPLETE]         = 1;
                $destData[self::APP_NAME_WWS_DOCUMENT_NUMBER] = $srcData[$docNumberName];
				$creationDate = isset($srcData[$app2db[self::APP_NAME_WWS_CREATION_DATE]]) ? $srcData[$app2db[self::APP_NAME_WWS_CREATION_DATE]] : $srcData['created_at'];
                $destData[self::APP_NAME_WWS_DOCUMENT_DATE]   = isset($srcData[$app2db[self::APP_NAME_WWS_DOCUMENT_DATE]]) ? $srcData[$app2db[self::APP_NAME_WWS_DOCUMENT_DATE]] : $creationDate;
                $destData[self::APP_NAME_WWS_REFERENCE]       = isset($srcData[$app2db[self::APP_NAME_WWS_REFERENCE]]) ? $srcData[$app2db[self::APP_NAME_WWS_REFERENCE]] : '';
				$destData[self::APP_NAME_WWS_PARCELS]         = isset($srcData[$app2db[self::APP_NAME_WWS_PARCELS]])       ? $srcData[$app2db[self::APP_NAME_WWS_PARCELS]] : '';
                $destData[self::APP_NAME_CREATED_AT]          = $srcData[$app2db[self::APP_NAME_CREATED_AT]];
                $destData[self::APP_NAME_UPDATED_AT]          = $srcData[$app2db[self::APP_NAME_UPDATED_AT]];             
                $destData[self::APP_NAME_EMAIL_SENT]          = isset($srcData[$app2db[self::APP_NAME_EMAIL_SENT]]) ? $srcData[$app2db[self::APP_NAME_EMAIL_SENT]] : '';
                $destData[self::APP_NAME_CURRENCY]            = Mage::getStoreConfig('currency/options/base');

				if ( strcasecmp($document->getSchrackWwsStatus(),'LA1') == 0 && $document->getSchrackWwsOfferFlagValid() && $document->isOfferAndCanBeOrdered() ) {
					$destData[self::APP_NAME_WWS_OFFER_2_ACCEPT] = '1';
				} else {
					$destData[self::APP_NAME_WWS_OFFER_2_ACCEPT] = '0';
				}


                $addr = $document->getShippingAddress();
                if ( $addr ) {
                    $destData['shipping_address'] = $this->_filterAddressData($addr->getData());
                }
                else {
                    $destData['shipping_address'] = array();
                }
                $addr = $document->getBillingAddress();
                if ( $addr ) {
                    $destData['billing_address'] = $this->_filterAddressData($addr->getData());
                }                
                else {
                    $destData['billing_address'] = array();
                }
                
                $orderNum = $doc_type == Schracklive_SchrackSales_Helper_Order::DOCTYPE_ORDER ? null : $destData[self::APP_NAME_WWS_ORDER_NUMBER];
                $destData['other_documents'] = $this->_getOtherDucuments($order_entity_id,$orderNum,$destData['wws_document_number']);
                
                $result['data']['getSingleOrderData'] = $destData;
                $result['data']['getSingleOrderData']['items'] = array();
                foreach ( $document->getItemsCollection() as $item ) {
                    $srcData = $item->getData();
                    $destData = array();
                    $destData[self::APP_NAME_SKU]         = $srcData[$app2db[self::APP_NAME_SKU]];         
                    $destData[self::APP_NAME_NAME]        = $srcData[$app2db[self::APP_NAME_NAME]];        
                    $destData[self::APP_NAME_DESCRIPTION] = $srcData[$app2db[self::APP_NAME_DESCRIPTION]]; 
                    $destData[self::APP_NAME_QTY]         = $this->_getQuantityFromItemData($srcData,$doc_type);
                    if ( isset($srcData[$app2db[self::APP_NAME_PRICE]]) ) {
                        $destData[self::APP_NAME_PRICE] = $this->_formatPrice($srcData[$app2db[self::APP_NAME_PRICE]]);
                    }
                    $destData[self::APP_NAME_ROW_TOTAL]   = $this->_formatPrice($srcData[$app2db[self::APP_NAME_ROW_TOTAL]]);
                    $result['data']['getSingleOrderData']['items'][] = $destData;
                }
            } catch ( Exception $e ) {
                $result['errors'] = array($e->getMessage());
            }
			$this->avoidNullValues($result);
            return $result;
        }

		private function avoidNullValues ( array &$subject ) {
			foreach ( $subject as $key => $val ) {
				if ( is_null($val) ) {
					$subject[$key] = '';
				} else if ( is_array($val) ) {
					$this->avoidNullValues($val);
					$subject[$key] = $val;
				}
			}
			return $subject;
		}

        public function getOrderCounts ( $is_offered = 1, $is_ordered = 1, $is_commissioned = 1, $is_delivered = 1, $is_invoiced = 1, $is_credited = 1,
                                         $get_offer_docs = 1, $get_order_docs = 1, $get_delivery_docs = 1, $get_invoice_docs = 1, $get_credit_memo_docs = 1,
                                         $from_date = null, $to_date = null, $text = null, $customer_id = null ) {
            if ($customer_id === null || !strlen($customer_id)) {
                $customer_id = null;
            }
            $result = array(
				'method' => 'getOrderCounts',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);		
            $helper = Mage::helper('schracksales/order');
            try {
                $customer = $this->_getCustomer($customer_id);
                if (!$customer) {
                    throw Mage::exception('Mage_Core', "No customer '{$customer_id}' found for this request.");
                }
                $searchParameters = $helper->createSearchParameters();
                $searchParameters->isOffered         = (int) $is_offered;
                $searchParameters->isOrdered         = (int) $is_ordered; 
                $searchParameters->isCommissioned    = (int) $is_commissioned; 
                $searchParameters->isDelivered       = (int) $is_delivered; 
                $searchParameters->isInvoiced        = (int) $is_invoiced; 
                $searchParameters->isCredited        = (int) $is_credited;
                $searchParameters->getOfferDocs      = (int) $get_offer_docs; 
                $searchParameters->getOrderDocs      = (int) $get_order_docs; 
                $searchParameters->getDeliveryDocs   = (int) $get_delivery_docs; 
                $searchParameters->getInvoiceDocs    = (int) $get_invoice_docs; 
                $searchParameters->getCreditMemoDocs = (int) $get_credit_memo_docs;
                $searchParameters->fromDate          = $this->_emptyToNull($from_date); 
                $searchParameters->toDate            = $this->_emptyToNull($to_date); 
                $searchParameters->text              = $this->_emptyToNull($text);
                
                $result['data']['getOrderCounts'] = array();
                $result['data']['getOrderCounts']['offered']         = $helper->getCountOffers($searchParameters);
                $result['data']['getOrderCounts']['ordered']         = $helper->getCountOrders($searchParameters);
                $result['data']['getOrderCounts']['commissioned']    = $helper->getCountCommissioned($searchParameters);
                $result['data']['getOrderCounts']['delivered']       = $helper->getCountDelivered($searchParameters);
                $result['data']['getOrderCounts']['invoiced']        = $helper->getCountInvoiced($searchParameters);
                $result['data']['getOrderCounts']['offer_docs']      = $helper->getCountOfferDocs($searchParameters);
                $result['data']['getOrderCounts']['order_docs']      = $helper->getCountOrderDocs($searchParameters);
                $result['data']['getOrderCounts']['delivery_docs']   = $helper->getCountDeliveryDocs($searchParameters);
                $result['data']['getOrderCounts']['invoice_docs']    = $helper->getCountInvoiceDocs($searchParameters);
                $result['data']['getOrderCounts']['creditmemo_docs'] = $helper->getCountCreditMemoDocs($searchParameters);
            } catch ( Exception $e ) {
                $result['errors'] = array($e->getMessage());
            }
            return $result;
       }
       
       public function getDocumentURL ( $order_entity_id, $wws_document_number, $doc_type, $customer_id = null ) {
            if ($customer_id === null || !strlen($customer_id)) {
                $customer_id = null;
            }
            $result = array(
				'method' => 'getDocumentURL',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);		
            $helper = Mage::helper('schracksales/order');
            try {
                $baseUrl = Mage::getStoreConfig('web/unsecure/base_url');
                switch ( (int) $doc_type ) {
                    case Schracklive_SchrackSales_Helper_Order::DOCTYPE_OFFER :
                        $stringType = 'offer';
                        break;
                    case Schracklive_SchrackSales_Helper_Order::DOCTYPE_ORDER :
                        $stringType = 'order';
                        break;
                    case Schracklive_SchrackSales_Helper_Order::DOCTYPE_SHIPMENT :
                        $stringType = 'shipment';
                        break;
                    case Schracklive_SchrackSales_Helper_Order::DOCTYPE_INVOICE :
                        $stringType = 'invoice';
                        break;
                    case Schracklive_SchrackSales_Helper_Order::DOCTYPE_CREDIT_MEMO :
                        $stringType = 'creditmemo';
                        break;
                    break;
                    default:
                        throw new Exception("No such type as '".$doc_type."'");
                  
                }
                $result['data']['getDocumentURL'] = 
                    $baseUrl . 'index.php/mobile/index/documentsDownload/id/' . $order_entity_id . '/type/' . $stringType 
                        . '/documentId/' . $wws_document_number . (isset($customer_id) ? ('/customer_id/' . $customer_id) : null);
            } catch ( Exception $e ) {
                $result['errors'] = array($e->getMessage());
            }
            return $result;
       }

        private function _filterAddressData ( $addressData ) {
            return array(
                $this->notNullStr($addressData['firstname']),
                $this->notNullStr($addressData['middlename']),
                $this->notNullStr($addressData['lastname']),
                $this->notNullStr($addressData['street']),
                $this->notNullStr($addressData['city']),
                $this->notNullStr($addressData['postcode']),
                $this->notNullStr($addressData['country_id'])
            );
        }
        
        private function _getOtherDucuments ( $orderEntityId, $orderNumber, $currentDocNumber ) {
            $res = array();
            $offerNum = null;
            $shipmentNums = array();
            $invoiceNums = array();
            $creditMemoNums = array();
            $ndxModel = Mage::getModel('schracksales/order_index');
            $ndxCollection = $ndxModel->getCollection();
            $ndxCollection->addFieldToFilter('order_id',$orderEntityId);
            
            foreach ( $ndxCollection as $row ) {
                $num = $row['wws_document_number'];
                if ( ! isset($num) || $num === $currentDocNumber ) {
                    continue;
                }
                if ( $row['is_offer'] ) {
                    $offerNum = $num;
                } elseif ( isset($row['shipment_id']) ) {
                    $shipmentNums[] = $num;
                } elseif ( isset($row['invoice_id']) ) {
                    $invoiceNums[] = $num;
                } elseif ( isset($row['credit_memo_id']) ) {
                    $creditMemoNums[] = $num;
                }
            }

            if ( isset($offerNum) ) {
                $res['offert'] = $offerNum;
            }
            if ( ! empty($shipmentNums) ) {
                $res['shipments'] = $shipmentNums;
            }
            if ( ! empty($invoiceNums) ) {
                $res['invoices'] = $invoiceNums;
            }
            if ( ! empty($creditMemoNums) ) {
                $res['creditmemos'] = $creditMemoNums;
            }
            if ( $orderNumber ) {
                $res['orders'] = array($orderNumber);
            }
            
            return $res;
        }
        
        
        /**
         * Build a new cart node.
         *
         * @param DOMDocument $document
         * @param integer $cart_id
         * @param Mage_Customer_Model_Customer $customer
         * @return DOMElement
         */
        protected function _createCart($cart_id, $customer, $partslist_id = null) {
            $json_cart = array('id' => $cart_id);
            $catalog_info = Mage::helper('schrackcatalog/info');

            if ($cart_id == 1) {
                // TODO: move this magic to the caller (or a helper)
                $cart = $this->_getPreparedCart($customer);
                $items = $cart->getAllItems();
                $catalog_info->preloadProductsInfo($items, $customer);
                $qtys = array();
                foreach ($items as $item) {
                    $qtys[$item->getSku()] = (int)$item->getQty();
                }
                $catalog_info->preloadProductsInfo($items, $customer, false, $qtys);
                
                $json_cart['cartarticles'] = array();

                foreach ($items as $item) {
                    $cartarticle = $this->_createCartArticle($item, $customer, $cart_id);
                    $json_cart['cartarticles'][] = $cartarticle;
                }

                $vat = (float)Mage::getStoreConfig('schrack/sales/vat');

                // TODO: get tax and grand total from the quote
                $total_price = $cart->getSubtotal();
                $total_tax = $vat / 100 * $total_price;
                $json_cart['totalprice'] = $this->_formatPrice($total_price);
                $json_cart['tax'] = $this->_formatPrice($total_tax);
                $json_cart['grossprice'] = $this->_formatPrice($total_price + $total_tax);
                $json_cart['vat'] = $this->_formatNumber($vat);
                $json_cart['currency'] = Mage::getStoreConfig('currency/options/base');
                return $json_cart;
            } elseif ($cart_id == 2) {
                $wishlist = Mage::getModel('schrackwishlist/wishlist')->loadByCustomer($customer, true);
                $items = $wishlist->getItemCollection();
                $catalog_info->preloadProductsInfo($items, $customer);

                $json_cart['cartarticles'] = array();
                foreach ($items as $item) {                    
                    $cartarticle = $this->_createCartArticle($item, $customer, $cart_id);
                    $json_cart['cartarticles'][] = $cartarticle;
                }
                return $json_cart;
            } elseif ($cart_id == 3) {
                if ($partslist_id === null)
                    $partslist = Mage::getModel('schrackwishlist/partslist')->loadActiveListByCustomer($customer);
                else
                    $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId($customer, $partslist_id);
                $items = $partslist->getItemCollection();
                $catalog_info->preloadProductsInfo($items, $customer);

                $json_cart['cartarticles'] = array();
                foreach ($items as $item) {
                    $cartarticle = $this->_createCartArticle($item, $customer, $cart_id);
                    $json_cart['cartarticles'][] = $cartarticle;
                }
                return $json_cart;
            } else {
                throw new Exception('no such cart_id as '.$cart_id);
            }

            return $node;
        }
        
        protected function _createCartArticle(Mage_Core_Model_Abstract $item, Mage_Customer_Model_Customer $customer, $cart_id) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());

            $cartarticle = array();
            $cartarticle['item_id'] = $item->getId();
            $cartarticle['articleid'] = $item->getProduct()->getSku();
            $cartarticle['name'] = $item->getName();
            if ($item->getSchrackDrumNumber()) {
                $cartarticle['drum_id'] = $item->getSchrackDrumNumber();
            }
            if ($cart_id == 1 || $cart_id == 3) {
                $cartarticle['quantity'] = (int)$item->getQty();
                $cartarticle['price'] = $this->_formatPrice($item->getSchrackBasicPrice());
                $cartarticle['surcharge'] = $this->_formatPrice($item->getSchrackRowTotalSurcharge());                    
                $cartarticle['totalprice'] = $this->_formatPrice($item->getRowTotal());
            }

            if ( $url = $product->getMainImageUrl() ) {
                $cartarticle['icon'] = Schracklive_SchrackCatalog_Helper_Image::getImageUrl($url,Schracklive_SchrackCatalog_Helper_Image::CART_APP);
            }

            $cartarticle['article'] = $this->_createArticle($product, $customer, (int)$item->getQty(), true);
            
            return $cartarticle;
        }


		/**
		 * @param $needle
		 * @return JSON-ResultFrame 
		 */

		public function suggestArticles ( $needle ) {

			$result = array(
				'method' => 'suggestArticles',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);
			
			$suggestedArticles = array();
			$errors = array();

			$productCollection = Mage::getModel('schrackcatalog/product')->getCollection();
            
			$productCollection->addAttributeToFilter( 'sku', array( 'like' => $needle .'%' ));
            $productCollection->addAttributeToFilter( 'status', array ( 'eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED ));
			$productCollection->addAttributeToFilter('schrack_sts_statuslocal',array('nin' => array('tot','strategic_no','unsaleable')));
            
            $productCollection->addAttributeToSelect('description');
            
			$productCollection->addAttributeToSort( 'sku', 'ASC' );

			$check_limit = Mage::getStoreConfig("schrack/mobile/suggested_articles");
			
			if ((is_null($check_limit)) || !(is_numeric($check_limit)) || $check_limit <= 0) {
				$check_limit = 3;
			}
			
			$productCollection->setPageSize($check_limit);
			$sql = $productCollection->getSelect()->__toString();
            // Mage::log($sql,null,"sql.log");

			foreach( $productCollection as $product ) {
				array_push($suggestedArticles, array( 'ArtNr' => $product->getSku(), 'Description' => $product->getDescription()) );
			}

			if( empty( $suggestedArticles ) ) {
                if ( !isset($product) || ! $product ) {
                    $product = Mage::getModel('catalog/product');
                }
                $productId = $product->getIdByEan( $needle );
                if ( $productId ) {
                    $product = Mage::getModel('schrackcatalog/product');
                    $product->load($productId);
    				array_push($suggestedArticles, array( 'ArtNr' => $product->getSku(), 'Description' => $product->getDescription()) );
                }
            }
            
			$result['data'] = array ( 'suggestArticles' => $suggestedArticles );

			if( empty( $suggestedArticles ) ) {
				array_push( $errors, array( 'code' => '400', 'text' => 'Search term: ' . $needle . ' has no resultset!') );
			}
			$result['errors'] = $errors;
			
			return $result;
		}
        
        public function getPartslists($customer_id = null) {
            if ($customer_id === null || !strlen($customer_id))
                $customer_id = null;
            
            $result = array(
				'method' => 'getPartslists',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);
						
			
            try {
                $customer = $this->_getCustomer($customer_id);
                $partslists = Mage::getModel('schrackwishlist/partslist')->loadByCustomer($customer);
                
                $data = array();
                foreach ($partslists as $partslist) {
                    $data[] = array(
                        'partslist_id' => $partslist->getId(),
                        'customer_id' => $partslist->getCustomerId(),
                        'description' => $partslist->getDescription(),
                        'is_active' => $partslist->getIsActive(),
						'item_count' => $partslist->getItemsCount()
                    );
                }
            
                $result['data']['getPartslists'] = $data;
                return $result;
            } catch (Exception $e) {
                $result['errors'] = array($e->getMessage());
                return $result;
            }
        }


        public function getPartslist($partslist_id = null, $customer_id = null) {
            return $this->_getPartslistImpl('getPartslist',$partslist_id,$customer_id);
        }

        private function _getPartslistImpl ( $methodName, $partslist_id = null, $customer_id = null) {
            if ($customer_id === null || !strlen($customer_id)) {
                $customer_id = null;
            }
            
            $result = array(
				'method' => $methodName,
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);						
			
            try {
                $partslistModel = Mage::getModel('schrackwishlist/partslist');
                $customer = $this->_getCustomer($customer_id);
                if ($partslist_id === null || !strlen($partslist_id)) {                    
                    $partslist = $partslistModel->loadActiveListByCustomer($customer, true);
                } else {
                    $partslist = $partslistModel->loadByCustomerAndId($customer, $partslist_id);
                }

                $data = array(
                    'partslist_id' => $partslist->getId(),
                    'customer_id' => $partslist->getCustomerId(),
                    'description' => $partslist->getDescription(),
                    'is_active' => $partslist->getIsActive(),
                    'items' => array()
                );

                $items = $partslist->getItemCollection()->load();

                foreach ($items as $item) {
                    $cartarticle = array(
                        'item_id' => $item->getId(),
                        'articleid' => $item->getProduct()->getSKU(),
                        'name' => $item->getProduct()->getName(),
                        'qty' => $item->getQty()
                    );

                    $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());

                    if ( $url = $product->getMainImageUrl() ) {
                        $cartarticle['icon'] = Schracklive_SchrackCatalog_Helper_Image::getImageUrl($url,Schracklive_SchrackCatalog_Helper_Image::CART_APP);
                    }
                    $cartarticle['article'] = $this->_createArticle($product, $customer, 1, true);

                    $data['items'][] = $cartarticle;
                }
                $result['data'][$methodName] = $data;
                return $result;
            } catch (Exception $e) {
                $result['errors'] = array($e->getMessage());
                return $result;
            }
        }

        public function setPartslistQuantity ( $article_id, $quantity, $customer_id = null, $drum_id = null, $partslist_id=null ) {
            $customer = $this->_getCustomer($customer_id);
            $productId = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore()->getId())->getIdBySku($article_id);

            if ( ! $partslist_id ) {
                $partslist = Mage::getModel('schrackwishlist/partslist')->loadActiveListByCustomer($customer, false);
            } else {
                $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId($customer,$partslist_id);
            }

            if (!$partslist) { // no active parstlist, load first parstlist
                $lists = Mage::getModel('schrackwishlist/partslist')->loadByCustomer($customer);
                if ($lists->count() > 0) {
                    $partslist = $lists->getFirstItem();
                } else {
                    $partslist = null;
                }
            }

            if (!$partslist) {
                throw new Exception('No partslist found.');
            }

            $items = $partslist->getItemCollection();
            foreach ($items as $item) {
                if ($item->getProduct()->getId() == $productId) {
                    if (!$drum_id || ($drum_id && ($item->getSchrackDrumNumber() == $drum_id))) {
                        if ($quantity <= 0) {
                            $item->setQty(0);
                            $item->isDeleted(true);
                            $item->delete();
                        } else {
                            $item->getProduct()->setCustomer($customer); // the getFinalPrice observer needs this
                            $item->setQty($quantity);
                            $item->save();
                        }
                        break;
                    }
                }
            }
            $partslist->save();

            return $this->_getPartslistImpl('setPartslistQuantity',$partslist_id,$customer_id);
        }
        
        public function setActivePartslist($partslist_id) {
            try {
                $customer = $this->_getCustomer();
                $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId($customer, $partslist_id);
                $partslist->activate();
                return $this->getPartslist($partslist_id);
            } catch (Exception $e) {
                $result['errors'] = array($e->getMessage());
                return $result;
            }
            
        }
        
        public function addAllFromPartslistToCart ( $partslist_id, $customer_id = null ) {
            if ($customer_id === null || !strlen($customer_id)) {
                $customer_id = null;
            }
            $result = array(
				'method' => 'addAllFromPartslistToCart',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);						
            try {
                $helper = Mage::helper('schrackwishlist/partslist');
                $customer        = $this->_getCustomer($customer_id);
                $successMsg      = null;
                $errorMsgs       = array();
                $addedItems      = array();
                $notSalableItems = array();
                $hasOptionsItems = array();
                $helper          = Mage::helper('schrackwishlist/partslist');
                $items           = array();

                $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId($customer,$partslist_id);
                if ( ! $partslist ) {
                    throw new Exception('partslist not found!');
                }
                
                $itemCollection = $partslist->getItemCollection()->setVisibilityFilter();
                foreach ( $itemCollection as $item ) {
                    $items[] = $item;
                }
                
                $helper->addPartlistItemsToCart($items,$successMsg,$errorMsgs,$addedItems,$notSalableItems,$hasOptionsItems);
                
                $data = array();
                if ( $successMsg ) {
                    $data['successMessage'] = $successMsg;
                }
                if ( $errorMsgs ) {
                    $data['unSuccessMessages'] = $errorMsgs;
                }
                $result['data']['addAllFromPartslistToCart'] = $data;
            } catch (Exception $e) {
                $result['errors'] = array($e->getMessage());
                return $result;
            }
            return $result;
        }
        
		/**
		 * @param $order_entity_id
		 * @param $wws_document_number
		 * @param $doc_type
		 * @param $cart_id
		 * @param $partslist_id
		 * @param $customer_id
		 * @return JSON-ResultFrame 
		 */
        public function addAllFromDocumentToCart ( $order_entity_id, $wws_document_number = null, $doc_type = null, $cart_id = 1, $partslist_id = null, $customer_id = null ) {
            if ( $cart_id == null ) {
                $cart_id = 1;
            }
            if ($customer_id === null || !strlen($customer_id)) {
                $customer_id = null;
            }
            $customer = $this->_getCustomer($customer_id);
            
            if ( $doc_type == null ) {
                $doc_type = Schracklive_SchrackSales_Helper_Order::DOCTYPE_ORDER;
            }
            else {
                $doc_type = (int) $doc_type;
            }
            $result = array(
				'method' => 'addAllFromDocumentToCart',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);						
            try {
                if ( ! $customer ) {
                    throw new Exception("addAllFromDocumentToCart() can only be used for logged in customers");
                }
                $data = array();
                $document = $this->_getDocument($order_entity_id, $wws_document_number,$doc_type,$customer_id);

                $productHelper = Mage::helper('schrackcatalog/product');

                $sku2qty = array();
                foreach ( $document->getItemsCollection() as $item ) {
                   $sku = $item->getSku(); 
                   $qty = $this->_getQuantityFromItemData($item->getData(),$doc_type);
                   $sku2qty[$sku] = $qty;
                }
                $successMessage = null;
                $unSuccessMessages = null;
                /* @var $productHelper Schracklive_SchrackCatalog_Helper_Product */
                $productHelper->addProductsToCart($sku2qty,$successMessage,$unSuccessMessages,$cart_id,$partslist_id,$customer);
                
                $helper = Mage::helper('schracksales/order');
                
                if ( $successMessage ) {
                    $data['successMessage'] = $successMessage;
                }
                if ( $unSuccessMessages ) {
                    $data['unSuccessMessages'] = $unSuccessMessages;
                }
                $result['data']['addAllFromDocumentToCart'] = $data;
            } catch (Exception $e) {
                $result['errors'] = array($e->getMessage());
                return $result;
            }
            return $result;
        }
        
       
       /**
        * Build a new product node.
        * @param DOMDocument $document
        * @param Mage_Catalog_Model_Product $product
        * @param null|Mage_Customer_Model_Customer $customer
        * @param int $qty
        * @param bool $details
        * @return DOMElement
        */
       protected function _createArticle(Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer = null, $qty = 1, $details = false) {
           if ($customer == null) {
               $customer = $this->_getCustomer();
           }

           $endOfLive = false;
           $predecessor = null;
           $this->_checkEndOfLiveAndPredecessor($product,$endOfLive,$predecessor);

           $article = new StdClass();
           $article->id = $product->getSKU();
           $article->ean = $product->getSchrackEan();
           $article->description = $product->getDescription();
           //get info from WWS
           //START WWS
           try {
               $catalogInfo = Mage::helper('schrackcatalog/info');
               $productHelper = Mage::helper('schrackcatalog/product');
               $stockHelper = Mage::helper('schrackcataloginventory/stock');
               $pickupWarehouseId = Mage::helper('schrackcustomer')->getPickupWarehouseId($customer);

			   $article->isSale               = $product->isSale() ? 1 : 0;
			   $article->isRestricted         = $product->isRestricted() ? 1 : 0;
			   $article->isHideStockQantities = $product->isHideStockQantities() ? 1 : 0;
			   $lab = $this->getPictureLabel($productHelper,$product,$customer);
			   if ( $lab ) {
				   $article->additionalPictureLabel = $lab;
			   }
			   if ( $details && ($product->isSale() || $product->isDead()) ) {
				   $larp = $product->getLastReplacementProduct();
				   if ( $larp ) {
					   $article->replacementProduct = $larp->getSku();
				   }
			   }

               $hasDrumsFlag = $productHelper->hasDrums($product);
               if ($hasDrumsFlag) {
                   $article->hasDrums = 1;
               } else {
                   $article->hasDrums = 0;
               }

               $article->graduated = 0;
               $prices = $catalogInfo->getGraduatedPricesForCustomer($product, $customer);
               if (count($prices) > 0) {
                   $article->graduated = 1;
               }
               if ($article->graduated) {
                   $article->graduated_prices = array();
                   foreach ($prices as $value) {
                       $step = new StdClass();
                       $step->qty = $value['qty'];
                       $step->price = $this->_formatPrice($value['price']);
                       $article->graduated_prices[] = $step;
                   }
               }
               $article->price = $this->_formatPrice($catalogInfo->getBasicTierPriceForCustomer($product, $qty, $customer));

			   $promotionEndDate = $productHelper->getPromotionEndDate($product,$customer);
			   $promotion = $promotionEndDate > '';
			   $article->isPromotion = $promotion ? 1 : 0;
			   if ( $productHelper->isPromotion($product,$customer) ) {
				   $article->regularPrice = $this->_formatPrice($productHelper->getRegularPrice($product, $customer));
				   if ( $promotion ) {
					   $article->promotionValidTo = $promotionEndDate;
				   }
			   }

               $article->delivery_stocks = array();
               $isQuantityNumeric = true;
               $isQuantityNumeric &= $this->_addStockInfos($article->delivery_stocks, $details && $hasDrumsFlag, $product, $stockHelper->getLocalDeliveryStock(), $productHelper, $catalogInfo, true);
               $isQuantityNumeric &= $this->_addStockInfos($article->delivery_stocks, $details && $hasDrumsFlag, $product, $stockHelper->getForeignDeliveryStock(), $productHelper, $catalogInfo, false);
			   if ( ! $product->isSale() ) {
				   foreach ( $stockHelper->getThirdPartyDeliveryStocks() as $stock ) {
					   $isQuantityNumeric &= $this->_addStockInfos($article->delivery_stocks,$details && $hasDrumsFlag,$product,$stock,$productHelper,$catalogInfo,false);
				   }
			   }

               $article->pickup_stocks = array();
               $pkStocks = $stockHelper->getPickupStocks();
               $pkStock = $pkStocks[$pickupWarehouseId];
               $isQuantityNumeric &= $this->_addStockInfos($article->delivery_stocks, $details && $hasDrumsFlag, $product, $pkStock, $productHelper, $catalogInfo, true, false);

               if (!$isQuantityNumeric ) {
                   $qtyUnit = '';
               } else {
                   $qtyUnit = $product->getSchrackQtyunit();
               }

               $article->unit = $qtyUnit;
               $article->priceunit = $product->getSchrackPriceunit($product);
               $article->currency = Mage::getStoreConfig('currency/options/base');
           } catch (Exception $e) {
               Mage::logException($e);
           }

            $mainImageUrl = $product->getMainImageUrl();
            if ( $mainImageUrl ) {
                $article->image = Schracklive_SchrackCatalog_Helper_Image::getImageUrl($mainImageUrl, Schracklive_SchrackCatalog_Helper_Image::PRODUCT_DETAIL_PAGE_MAIN);
                $article->thumbnail = Schracklive_SchrackCatalog_Helper_Image::getImageUrl($mainImageUrl, Schracklive_SchrackCatalog_Helper_Image::PRODUCT_DETAIL_PAGE_THUMBNAIL);
            }

           if ($predecessor) {
               $article->predecessor = $predecessor;
           }
           if ($endOfLive) {
               $article->endOfLifecycle = 1;
           }
           //END WWS
           //Files

           if ($details) {

               $attachments = $product->getAttachments();
               $files = array();
               foreach ($attachments as $attachment) {
                   if ($attachment->getFiletype() != 'thumbnails') {
                       $file = new StdClass();
                       $url = $attachment->getUrl();
                       $file->id = $attachment->getAttachmentId();
                       $file->value = Mage::getStoreConfig('schrack/general/imageserver').$url;
                       $file->name = Mage::helper('schrackcatalog')->__($attachment->getFiletype());
                       $file->fileinfo = $this->_getFileInfo(Mage::getStoreConfig('schrack/general/imageserver').$url);
                       if ($file->fileinfo['filesize']) {
                           $files[] = $file;
                       }
                   }
               }
               
               if (count($files))
                   $article->files = $files;
               
               $article->complete = 1;

               $article->relatedproducts = array();

               $related_products_collection = $product->getRelatedProductCollection()
                       ->addAttributeToSort('position', 'asc')
                       ->addStoreFilter();

               Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($related_products_collection);

               $related_product_limit = Mage::getStoreConfig("schrack/shop/related_products");

               if ((!is_null($related_product_limit)) && (is_numeric($related_product_limit)) && $related_product_limit> 0) {
                   $related_products_collection->setPageSize($related_product_limit);
               }

               $related_products_collection->load();

               foreach ( $related_products_collection as $related_product_item ) {

                   try {
                       $related_product_item->setDoNotUseCategoryId(true);
                       $related_product_item->load($related_product_item->getId());
                       if ( !is_null($related_product_item) && ( $related_product_item->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED )) {
                           $article->relatedproducts[] = $this->_createArticle($related_product_item, $customer, 1, false);
                       }
                   }
                   catch( Exception $e )
                   {
                       Mage::logException($e);
                   }

               }               

           } else {
               $article->complete = 0;
           }
           return $article;
       }
       
        private function _addStockInfos ( array &$parent, $showDrums, $product, $stock, $productHelper, $catalogInfo, $addEmptyStock, $asDelivery = true ) {
            if ( ! isset($stock) )
                return true;

            if ( $asDelivery ) {
				if ( $product->isSale() ) {
					$numQty = $productHelper->getSummarizedStockQuantities($product);
					$strQty = $productHelper->formatQty($product,$numQty);
					$qty = array( $numQty, $strQty );
				} else {
					$qty = $productHelper->getFormattedAndUnformattedDeliveryQuantity($product, $stock->getStockNumber(), false, $stock->getStockLocation());
				}
            }
            else {
                $qty = $productHelper->getFormattedAndUnformattedPickupQuantity($product, $stock->getStockNumber(), false);
            }
            if ( $qty[0] == 0 && ! $addEmptyStock )
                return true;

            $stockEl = new StdClass();
            $stockEl->number = $stock->getStockNumber();
            if ( $asDelivery ) {
				if ( ! $product->isSale() ) {
					$stockEl->delivery_time_abbr = $stock->getDeliveryTimeAbbreviation();
					$stockEl->delivery_hours = $stock->getDeliveryHours();
				}
                $stockEl->state =  $catalogInfo->getDeliveryState($product,$stock->getStockNumber());
                $stockEl->salesunit =  $catalogInfo->getDeliverySalesUnit($product,$stock->getStockNumber());
            }
            else {
				$stockEl->delivery_time_abbr = $stock->getDeliveryTimeAbbreviation();
                $stockEl->state =  $catalogInfo->getPickupState($product,$stock->getStockNumber());
                $stockEl->salesunit =  $catalogInfo->getPickupSalesUnit($product,$stock->getStockNumber());
            }
            $stockEl->quantity_num = $qty[0];
            $stockEl->quantity = $qty[1];

            if ( $showDrums ) {
                $drumsAvailable = $catalogInfo->getAvailableDrums($product, array($stock->getStockNumber()));
                $stockEl->drums_available = array();
                foreach ($drumsAvailable as $warehouseId => $warehouseDrums) {
                    foreach ($warehouseDrums as $drum) {
                        $stockEl->drums_available[] = $this->_createDrum($drum);
                    }
                }                

                $drumsPossible = $catalogInfo->getPossibleDrums($product, array($stock->getStockNumber()));
                $stockEl->drums_possible = array();
                foreach ($drumsPossible as $warehouseId => $warehouseDrums) {
                    foreach ($warehouseDrums as $drum) {
                        $stockEl->drums_possible = $this->_createDrum($drum);
                    }
                }                
            }
            
            $parent[] = $stockEl;


            return is_numeric($qty[0]);
        }
       
       
       protected function _getFileInfo($url) {
            $fileData = Mage::getModel('schrackcatalog/filedata');
            $fileInfo = array();
            $fileInfo['mimetype'] = '';
            $fileInfo['filesize'] = '';

            $fileData->loadByUrl($url);
            if ($fileData->getId()) {
                $fileInfo['mimetype'] = $fileData->getMimetype();
                $fileInfo['filesize'] = $fileData->getFilesize();
                if ($fileInfo['mimetype'] && $fileInfo['filesize']
                ) {
                    return $fileInfo;
                }
            }

            if (($url != null) && ($url != '')) {
                $file = @fopen($url, 'r');
                if ($file) {
                    $headers = stream_get_meta_data($file);
                    $fileData->setUrl($url);
                    foreach ($headers['wrapper_data'] as $header) {
                        if (strpos(strtolower($header), 'content-type') !== FALSE) {
                            $fileInfo['mimetype'] = trim(substr($header, strpos($header, ':') + 1));
                            $fileData->setMimetype($fileInfo['mimetype']);
                        }
                        if (strpos(strtolower($header), 'content-length') !== FALSE) {
                            $fileInfo['filesize'] = trim(substr($header, strpos($header, ':') + 1));
                            $fileData->setFilesize($fileInfo['filesize']);
                        }
                    }
                    $fileData->save();
                }
            }

            return $fileInfo;
        }
        
        /**
         *
         * @param Schracklive_Mobile_Model_Document $document
         * @param type $drum
         * @return type
         */
        protected function _createDrum($drum) {
            $node = new StdClass();
            $node->id = $drum->wws_number;
            $node->name = $drum->name;
            $node->description = $drum->description;
            $node->type = $drum->type ? $drum->type : 'F';
            $node->size = $drum->size;
            $node->qty =  $drum->stock_qty;
            $node->lessen_delivery = ((bool)$drum->getLessenDelivery()) ? '1' : '0';
            $node->lessen_pickup = ((bool)$drum->getLessenPickup()) ? '1' : '0';
            return $node;
        }        
        
        /**
         * try to find product by sku or ean
         * we set the store id for this product
         * if found, we set the customer to that product
         * 
         * @param string $article_id
         * @return Schracklive_SchrackCatalog_Model_Product
         * @throws Exception
         */
        protected function _getProduct($article_id, Mage_Customer_Model_Customer $customer) {
            $product = Mage::getModel('Schracklive_SchrackCatalog_Model_Product')->setStoreId(Mage::app()->getStore()->getId());
            $product_id = $product->getIdBySku($article_id);
            if( ! $product_id ) {
                $product_id = $product->getIdByEan( $article_id );
            }
            if (!$product_id) {
                throw Mage::exception('Mage_Core', 'Product not found.');
            }
            $product = $product->load($product_id);
            if (!$product->getId()) {
                throw Mage::exception('Mage_Core', 'Invalid product.');
            }
            $product->setCustomer($customer); // the getFinalPrice observer needs this   
            return $product;
        }
        
        private function _emptyToNull ( $value ) {
            if ( $value === '' ) {
                $value = null;
            }
            return $value;
        }

		private function _checkCustomerOrder ( $customer, $order_entity_id ) {
			$customerId = $customer->getSchrackWwsCustomerId();
            $helper = Mage::helper('schracksales/order');
            $orderCustomerId = $helper->getDocumentCustomerID($order_entity_id,"Order");
			if ( ! Mage::getStoreConfig('schrack/solr4orders/override_customer_id') && $customerId != $orderCustomerId ) {
                throw Mage::exception('Mage_Core', "Wrong order id.");
			}
		}

		private function _checkOrderParcels ( $order_entity_id, $parcels ) {
			$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
			$order_entity_id = $readConnection->quote($order_entity_id);
			$parcels = $readConnection->quote($parcels);
			$sql = "SELECT count(*) FROM sales_flat_shipment WHERE order_id = $order_entity_id AND schrack_wws_parcels = $parcels;";
			$cnt = $readConnection->fetchOne($sql);
			if ( intval($cnt) <> 1 ) {
                throw Mage::exception('Mage_Core', "Wrong parcels.");
			}
		}
        
        private function _getDocument ( $order_entity_id, $wws_document_number = null, $doc_type = null, $customer_id = null  ) {
            $customer = $this->_getCustomer($customer_id);
            $documentId = $wws_document_number ? $wws_document_number : $order_entity_id;
            $helper = Mage::helper('schracksales/order');
            switch ( $doc_type ) {
                case Schracklive_SchrackSales_Helper_Order::DOCTYPE_OFFER:
                    $document = $helper->getFullDocument($documentId,"Offer");
                    break;
                case Schracklive_SchrackSales_Helper_Order::DOCTYPE_ORDER:
                    $document = $helper->getFullDocument($documentId,"Order");
                    break;
                case Schracklive_SchrackSales_Helper_Order::DOCTYPE_SHIPMENT:
                    $document = $helper->getFullDocument($documentId,"Shipment");
                    break;
                case Schracklive_SchrackSales_Helper_Order::DOCTYPE_INVOICE:
                    $document = $helper->getFullDocument($documentId,"Invoice");
                    break;
                case Schracklive_SchrackSales_Helper_Order::DOCTYPE_CREDIT_MEMO:
                    $document = $helper->getFullDocument($documentId,"Creditmemo");
                    break;
                default:
                    throw new Exception("No such type as '$doc_type'");
            }

            if (     ! Mage::getStoreConfig('schrack/solr4orders/override_customer_id')
                  && $document->getData('CustomerNumber') != $customer->getSchrackWwsCustomerId() ) {
                throw Mage::exception('Mage_Core', "Wrong order id.");
            }

            return $document;
        }
    
        private function _getQuantityFromItemData ( $itemData, $doc_type ) {
            if ( $doc_type == Schracklive_SchrackSales_Helper_Order::DOCTYPE_OFFER || $doc_type == Schracklive_SchrackSales_Helper_Order::DOCTYPE_ORDER ) {
                $qtyName = 'qty_ordered';
            }
            else {
                $qtyName = 'qty';
            }
            return $itemData[$qtyName];
        }
        
        public function createPartslist($name, $comment = null, $customer_id = null) {
            if ($customer_id === null || !strlen($customer_id)) {
                $customer_id = null;
            }
            $customer = $this->_getCustomer($customer_id);
            
            $result = array(
				'method' => 'createPartslist',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);						
			

            $model = Mage::getModel('schrackwishlist/partslist');                      
            try {
                $model->create($customer->getId(), $name, $comment);
                $result['data']['id'] = $model->getId();
			} catch (Exception $e) {
				Mage::logException($e);
				$result['errors'][] = $e->getMessage();
			}
            return $result;
		}
        
        public function deletePartslist($partslist_id, $customer_id = null) {
            if ($customer_id === null || !strlen($customer_id)) {
                $customer_id = null;
            }
            $customer = $this->_getCustomer($customer_id);
            
            $result = array(
				'method' => 'deletePartslist',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);						
			

            $model = Mage::getModel('schrackwishlist/partslist');                      
            try {
                $model->loadByCustomerAndId($customer, $partslist_id);
                $model->delete();
                $result['data']['ok'] = 'true';
			} catch (Exception $e) {
				Mage::logException($e);
				$result['errors'][] = $e->getMessage();
			}
            return $result;
		}

		public function getUserShippingAndPickupAddresses ( $customer_id = null ) {
			$result = array(
				'method' => 'getUserShippingAndPickupAddresses',
				'version' => '1.0',
				'data' => array( 'shipping' => array(), 'pickup' => array() ),
				'errors' => array()
			);
			try {
				$customer = $this->_getCustomer($customer_id);
				$dflt = $customer->getDefaultShippingAddress();
				foreach ($customer->getAddresses() as $address) {
					$addrNo = $address->getSchrackWwsAddressNumber();
					if (    $addrNo > 0
						 && $addrNo != Schracklive_SchrackCustomer_Model_Address::NO_ADDRESS_NUMBER ) {
						$options = array(
							'id' => $addrNo,
							'label' => $address->format('oneline'),
							'default' => ($address->getId() === $dflt->getId())
						);
						$result['data']['shipping'][] = $options;
					}
				}
				$defaultWarehouseId = Mage::helper('schrackcustomer')->getPickupWarehouseId($customer);
				for ( $i = 1 ; ; ++$i ) {
					$id = Mage::getStoreConfig('carriers/schrackpickup/id' . $i);
					$name = Mage::getStoreConfig('carriers/schrackpickup/name' . $i);
					if ( ! $id ) {
						break;
					}
					$options = array(
						'id' => $id,
						'label' => $name,
						'default' => ($id === $defaultWarehouseId)
					);
					$result['data']['pickup'][] = $options;
				}

				$result['data']['ok'] = 'true';
			} catch (Exception $e) {
				Mage::logException($e);
				$result['data']['ok'] = 'false';
				$result['errors'][] = $e->getMessage();
			}
			return $result;
		}

		public function orderOfferNow ( $order_no, $is_pickup, $pickup_address_id, $delivery_address_id, $customer_id = null ) {
			/** @var $order Schracklive_SchrackSales_Model_Order */
			$order = Mage::getModel('sales/order')->load($order_no,'schrack_wws_order_number');
			/** @var $requestHelper Schracklive_Wws_Helper_Request */
			$requestHelper = Mage::helper('wws/request');
			$result = array(
				'method' => 'orderOfferNow',
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);
			try {
				/** @var $customer Schracklive_SchrackCustomer_Model_Customer */
				$customer = $this->_getCustomer($customer_id);
				/** @var $advisor Schracklive_SchrackCustomer_Model_Customer */
				$advisor = $customer->getAccount()->getAdvisor();
				$messages = $requestHelper->orderOfferWithoutQuote($order,$customer,$advisor,$is_pickup ? 1 : 0,$pickup_address_id,$delivery_address_id,'');
				if ( $messages->count(Mage_Core_Model_Message::ERROR) > 0 ) {
					$result['data']['ok'] = 'false';
					foreach ( $messages->getErrors() as $err ) {
						$result['errors'][] = $err->getText();
						echo '';
					}
				} else {
					$result['data']['ok'] = 'true';
				}
			} catch (Exception $e) {
				Mage::logException($e);
				$result['errors'][] = $this->__('ServerError');
			}
			return $result;
		}

        public function getTranslationTimestamp () {
            return $this->_getTranslationImpl('getTranslationTimestamp',true);
        }
        
        public function getTranslations () {
            return $this->_getTranslationImpl('getTranslations',false);
        }
        
        private function _getTranslationImpl ( $method, $timestampOnly = true ) {
            $result = array(
				'method' => $method,
				'version' => '1.0',
				'data' => array(),
				'errors' => array()
			);						
            $localeCode = Mage::app()->getLocale()->getLocaleCode();
            $result['data']['locale'] = $localeCode;
            $fileName = Mage::getBaseDir('locale');
            $fileName.= DS.$localeCode.DS;
            $fileName.= 'local'.DS.'Schracklive_App.csv';
            if ( ! file_exists($fileName) ) {
                throw new Exception("Translation file '$fileName' not found!");
            }
            $ts = filemtime($fileName);
            $result['data']['timestamp'] = $ts;
            if ( ! $timestampOnly ) {
                $result['data']['translations'] = array();
                $file = fopen($fileName,'r');
                while ( ($line = fgetcsv($file)) !== FALSE ) {
                    $key = $line[0];
                    $val = $line[1];
                    $result['data']['translations'][$key] = $val;
                    echo '';
                }
                fclose($file);                
            }
            return $result;
        }

        private function reformatDate ( $src ) {
            if ( $src == null ) {
                return '';
            }
            $src = trim($src);
            $len = strlen($src);
            $p = strpos($src,'.');
            $q = strrpos($src,'.');
            if ( $len < 8 || $len > 10 || $p === false || $p === $q ) {
                return '';
            }
            $destAr = explode('.',$src);
            $dest = $destAr[2] . '-' . $destAr[1] . '-' . $destAr[0] . " 00:00:00";
            return $dest;
        }

    }
?>