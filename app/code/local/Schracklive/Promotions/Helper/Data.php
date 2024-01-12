<?php

class Schracklive_Promotions_Helper_Data extends Mage_Core_Helper_Abstract {

    const SKUS_2_PROMO_IDS = 'sku2promotionId';
    const PROMO_IDS_2_SKUS = 'promotionId2sku';
    const SORTING_MAP      = 'sortmap';
    const PROMOTION_CACHE_LIFETIME = 6 * 60 * 60; // lifetime 6 hours

    /* @var $_writeConnection Magento_Db_Adapter_Pdo_Mysql */
    private $_writeConnection;
    /* @var $_readConnection Magento_Db_Adapter_Pdo_Mysql */
    private $_readConnection;


    function __construct () {
        $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
    }


    public function getPromotionSKUsForPromotion ( $promoID, $catIDs = null ) {
        $promoData = $this->getPromotionData();
        if ( ! is_array($catIDs) || count($catIDs) < 1 ) {
            if ( $promoID < 1 ) {
                if ($promoID != 0) {
                    // Hint: Bsp.: $promoData['sku2promotionId'][sku][0/1/...][PromoID]
                    // $promoData cleanup:
                    $tmpPromoData = $promoData;
                    foreach($tmpPromoData[self::SKUS_2_PROMO_IDS] as $sku => $skuField) {
                        $promoIdFound = false;
                        foreach ($skuField as $index => $promotiondId) {
                            if ($promotiondId == $promoID) {
                                $promoIdFound = true;
                            }
                        }
                        if ($promoIdFound == false) {
                            // Remove products, which are not inside the selected promotion
                            unset($promoData[self::SKUS_2_PROMO_IDS][$sku]);
                        }
                    }
                }
                $res = array_keys($promoData[self::SKUS_2_PROMO_IDS]);
            } else {
                if ( isset($promoData[self::PROMO_IDS_2_SKUS][$promoID]) ) {
                    $res = $promoData[self::PROMO_IDS_2_SKUS][$promoID];
                } else {
                    $res = [];
                }
            }
        } else {
            $filterData = $this->getAllFilterDataForPromotion($promoID);
            $res = array();
            foreach ( $filterData as $id => $skus ) {
                if ( in_array($id,$catIDs) ) {
                    $res = array_merge($res,$skus);
                }
            }
            $res = array_unique($res);
            $overallSortingMap = $promoData[self::SORTING_MAP];
            usort($res,function ($a,$b) use ($overallSortingMap) {
                return $overallSortingMap[$a] - $overallSortingMap[$b];
            });
        }
        return $res;
    }


    // This function fetches all promotions which are available (promoID = 0 -> Request from Template)
    public function getAllPromotionsWithPDFs () {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ( ! is_object($customer) || ! $customer->getSchrackWwsCustomerId() ) {
            return array();
        }

        $wwsCustomerId = $customer->getSchrackWwsCustomerId();

        if ($this->_localDockerWwsId) {
            $wwsCustomerId = $this->_localDockerWwsId;
        }

        $customerEntId = $customer->getEntityId();
        $customerAccId = $customer->getSchrackAccountId();

        if ($this->_localDockerCustEntId) {
            $customerEntId = $this->_localDockerCustEntId;
        }
        if ($this->_localDockerCustAccId) {
            $customerAccId = $this->_localDockerCustAccId;
        }

        /** @var Zend_Cache_Core $cache */
        $cache = Mage::app()->getCache();
        // cache per user because of the PDFs
        $cacheID = 'promotions_per_user_' . $customerEntId;
        if ( $this->_localDisableCache == false && $cacheRes = $cache->load($cacheID) ) {
            $response = unserialize($cacheRes);
        } else {
            $sql = " SELECT sp.*, spac.pdf_url FROM schrack_promotion sp"
                 . " JOIN schrack_promotion_account spa ON sp.entity_id = spa.promotion_id"
                 . " LEFT JOIN schrack_promotion_account_customer spac ON spa.entity_id = spac.promotion_account_id AND spac.customer_id = ?"
                 . " WHERE spa.account_id = ? AND sp.valid_from <= CURDATE() AND sp.valid_to >= CURDATE()"
                 . " ORDER BY IF(sp.type = 'KAB',1,0) DESC, sp.is_yearly_kab DESC, sp.`order`";

            $dbRes = $this->_readConnection->fetchAll( $sql, array($customerEntId, $customerAccId) );
            // build map for quick access
            $res = array();
            foreach ( $dbRes as $row ) {
                $row['name'] = str_replace('_',' ',$row['name']);
                $res[$row['entity_id']] = $row;
            }
            $cache->save(serialize($res),$cacheID,array(),self::PROMOTION_CACHE_LIFETIME);
            $response = $res;
        }

        if ($wwsCustomerId && $wwsCustomerId == $this->getLogWwsCustomerId()) {
            $this->writeLog($response);
        }
        return $response;
    }


    public function isPromotionProduct ( $sku ) {
        $promoData = $this->getPromotionData();
        $res = isset($promoData[self::SKUS_2_PROMO_IDS][$sku]);
        return $res;
    }


    public function getSKUsToPromotionFlags ( array $skus ) {
        $promoData = $this->getPromotionData();
        $res = array_fill_keys(array_unique($skus),false);
        foreach ( $promoData[self::SKUS_2_PROMO_IDS] as $sku => $promos ) {
            $res[$sku] = true;
        }
        return $res;
    }


    public function getPromotionSKUs ( array $skus ) {
        $promoData = $this->getPromotionData();
        $promoSKUs = array_keys($promoData[self::SKUS_2_PROMO_IDS]);
        $res = array_intersect($skus,$promoSKUs);
        return $res;
    }


    public function hasPromotions () {
        $promoData = $this->getPromotionData();
        $res = count($promoData[self::SKUS_2_PROMO_IDS]) > 0;
        return $res;
    }


    public function getAllPromotionSKUs () {
        $promoData = $this->getPromotionData();
        $res = array_keys($promoData[self::SKUS_2_PROMO_IDS]);
        return $res;
    }


    public function getFilterForPromotion ( $promoID ) {
        $topLevelCategories = $this->getAllTopLevelCategories();
        $allFilterData = $this->getAllFilterDataForPromotion($promoID);
        $res = array();
        foreach ( $topLevelCategories as $id => $name ) {
            $res[] = array(
                'id' => $id,
                'name' => $name,
                'hasProducts' => (isset($allFilterData[$id]) && count($allFilterData[$id]) > 0)
            );
        }
        return $res;
    }


    private function getAllFilterDataForPromotion ( $promoID ) {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        /** @var Zend_Cache_Core $cache */
        $cache = Mage::app()->getCache();
        $cacheID = 'filter_for_promotion_and_customer_' . $promoID . '_' . $customer->getSchrackWwsCustomerId();
        if ( $this->_localDisableCache == false && $cacheRes = $cache->load($cacheID) ) {
            $filterData = unserialize($cacheRes);
        } else {
            $promoSKUs = $this->getPromotionSKUsForPromotion($promoID);
            $topLevelCategories = $this->getAllTopLevelCategories();
            $id2sku = array();
            $sql = "SELECT entity_id, sku FROM catalog_product_entity WHERE sku in ('" . implode("','",$promoSKUs) ."')";
            $dbRes = $this->_readConnection->fetchAll($sql);
            foreach ( $dbRes as $row ) {
                $id2sku[$row['entity_id']] = $row['sku'];
            }
            $filterData = array();
            if ( count($id2sku) > 0 ) {
                $sql = " SELECT category_id, product_id FROM catalog_category_product_index"
                     . " WHERE category_id IN (" . implode(",", array_keys($topLevelCategories)) . ")"
                     . " AND product_id IN (" . implode(",", array_keys($id2sku)) . ")";
                $dbRes = $this->_readConnection->fetchAll($sql);
                foreach ( $dbRes as $row ) {
                    $catID = $row['category_id'];
                    $prodID = $row['product_id'];
                    if ( !isset($filterData[$catID]) ) {
                        $filterData[$catID] = [];
                    }
                    $filterData[$catID][] = $id2sku[$prodID];
                }
                $sortMap = array_flip($promoSKUs);
                foreach ( $filterData as $catId => $skus ) {
                    usort($skus, function ( $a, $b ) use ($sortMap) {
                       return $sortMap[$a] - $sortMap[$b];
                    });
                    $filterData[$catId] = $skus;
                }
            }
            $cache->save(serialize($filterData),$cacheID,array(),self::PROMOTION_CACHE_LIFETIME);
        }
        return $filterData;
    }


     private function getAllTopLevelCategories () {
        /** @var Zend_Cache_Core $cache */
        $cache = Mage::app()->getCache();
        $cacheID = 'top_level_categories_4_promotions';
        if ( $this->_localDisableCache == false && $cacheRes = $cache->load($cacheID) ) {
            return unserialize($cacheRes);
        } else {
            $sql = " SELECT cat.entity_id, attrName.value AS name FROM catalog_category_entity AS cat"
                 . " JOIN catalog_category_entity_varchar attrName ON (cat.entity_id = attrName.entity_id AND attrName.store_id = 0 AND attrName.attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'name'))"
                 . " JOIN catalog_category_entity_varchar attrID ON (cat.entity_id = attrID.entity_id  AND attrID.attribute_id  IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id'))"
                 . " WHERE level = 2 AND attrID.value NOT IN ('87-01-12','_PROMOS_');";
            $dbRes = $this->_readConnection->fetchAll($sql);
            $res = array();
            foreach ( $dbRes as $row ) {
                $name = $row['name'];
                if ( ($p = mb_strpos($name,'(')) !== false ) {
                    $name = mb_substr($name,0,$p-1);
                }
                $name = trim($name);
                if ( mb_strlen($name) > 36 ) {
                    $name = mb_substr($name,0,33) . '...';
                }
                $res[$row['entity_id']] = $name;
            }
            $cache->save(serialize($res),$cacheID,array(),self::PROMOTION_CACHE_LIFETIME);
            return $res;
        }
    }


   private function getPromotionData () {
        $res = array( self::SKUS_2_PROMO_IDS => array(), self::PROMO_IDS_2_SKUS => array() );
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ( ! is_object($customer) || ! $customer->getSchrackWwsCustomerId() ) {
            return $res;
        }
        /** @var Zend_Cache_Core $cache */
        $cache = Mage::app()->getCache();
        $cacheID = 'promotion_customer_data_' . $customer->getSchrackWwsCustomerId();
        if ( $this->_localDisableCache == false && $cacheRes = $cache->load($cacheID) ) {
            return unserialize($cacheRes);
        } else {
            try {
                $this->retrievePromotionData($customer, $res);
            } catch ( Exception $ex ) {
                Mage::logException($ex);
                return [];
            }
            if ( count($res) > 0 ) {
                $cache->save(serialize($res), $cacheID, array(), self::PROMOTION_CACHE_LIFETIME);
            }
            return $res;
        }
   }


    private function retrievePromotionData ( $customer, &$res ) {
        $wwsCustomerId = $customer->getSchrackWwsCustomerId();
        $url = Mage::getStoreConfig('schrack/promotions/service_url');

        if ($this->_localDockerServiceUser) {
            $user = $this->_localDockerServiceUser;
        } else {
            $user = Mage::getStoreConfig('schrack/promotions/service_user');
        }
        if ($this->_localDockerServiceUserPass) {
            $password = $this->_localDockerServiceUserPass;
        } else {
            $password = Mage::getStoreConfig('schrack/promotions/service_password');
        }
        if ( ! $url || ! $user || ! $password ) {
            throw new Exception("Price Proxy Service for Promotions not properly configured!");
        }

        $countryCode = strtoupper(Mage::getStoreConfig('schrack/general/country'));
        if ($this->_localDockerCountry) {
            $countryCode = $this->_localDockerCountry;
        }
        if ($this->_localDockerWwsId) {
            $wwsCustomerId = $this->_localDockerWwsId;
        }
        if ( $countryCode == 'CO' || $countryCode == 'DK' ) {
            $countryCode = 'COM';
        }
        $headers = array(
            'type:promotions',
            'businessunit:' . $countryCode,
            'customernr:' . $wwsCustomerId
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $httpRes = curl_exec($ch);
        if ( ! $httpRes ) {
            if ( ($httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE)) >= 400 ) {
                curl_close($ch);
                throw new Exception("Curl request to Price Proxy failed! HTTP response code was  '$httpResponseCode'.");
            } else if ( ($errNo = curl_errno($ch)) ) {
                $err = curl_error($ch);
                curl_close($ch);
                throw new Exception("Curl request to Price Proxy failed! Curl returned error '$err' (#$errNo).");
            } else {
                $info = curl_getinfo($ch);
                curl_close($ch);
                throw new Exception("Curl request to Price Proxy failed, no errno given! Curl info returned: " . print_r($info, true));
            }
        }
        curl_close($ch);
        $jsonRes = substr($httpRes,strpos($httpRes,'{'));
        if ( $wwsCustomerId && $wwsCustomerId == $this->getLogWwsCustomerId() ) {
            $this->writeLog('================== JSON Response from Price Proxy (Start) =============================');
            $this->writeLog("Response from price proxy for customer " . $wwsCustomerId . " :");
            $this->writeLog(json_decode($jsonRes, true));
            $this->writeLog('================== JSON Response from Price Proxy (End) =============================');
        }
        // Raw json response from price-proxy (as associative PHP array):
        $data = json_decode($jsonRes, true);

        // Simplify json response from price-proxy (to new simplier PHP array structure)
        $data = $this->simplifyJSON($data);
        // Cleanup product structure (remove product doublettes from equally-named promotions)
        $this->cleanupPromotions($data);
        //$data = $this->cleanupPromotions($data); TODO : not implemented

        $res = array();

        $allPromos = $this->getAllPromotionsWithPDFs();
        $handledPromos = array_fill_keys(array_keys($allPromos),true);
        $allValidSKUs = $this->getValidSkuMap();

        $overallSortingMap = array();
        foreach ( $data['promotions'] as $promoID => $products ) {
            if ( $handledPromos[$promoID] ) {
                $sortingMap = $this->getPromotionSortingMap(intval($promoID));
                $order = 0;
                foreach ( $products as $sku => $position ) {
                    if ( ! isset($allValidSKUs[$sku]) ) {
                        continue; // ignore not saleable and not existing products
                    }
                    if ( !isset($res[self::SKUS_2_PROMO_IDS][$sku]) ) {
                        $res[self::SKUS_2_PROMO_IDS][$sku] = [$promoID];
                    } else {
                        $res[self::SKUS_2_PROMO_IDS][$sku][] = $promoID;
                    }
                    $order = isset($sortingMap[$sku]) ? $sortingMap[$sku] : $order + 1;
                    if ( !isset($res[self::PROMO_IDS_2_SKUS][$promoID]) ) {
                        $res[self::PROMO_IDS_2_SKUS][$promoID] = [$order => $sku];
                    } else {
                        $res[self::PROMO_IDS_2_SKUS][$promoID][$order] = $sku;
                    }
                    $overallSortingMap[$sku] = $order;
                }
                ksort($res[self::PROMO_IDS_2_SKUS][$promoID]);
                $handledPromos[$promoID] = false;
            } else {
                Mage::log("Got data for unknown promotion $promoID",null,'promos.log');
            }
        }
        uksort($res[self::SKUS_2_PROMO_IDS],function ($a,$b) use ($overallSortingMap) {
            return $overallSortingMap[$a] - $overallSortingMap[$b];
        });
        $res[self::SORTING_MAP] = $overallSortingMap;
        foreach ( $handledPromos as $promoID => $flag ) {
            if ( $flag ) {
                Mage::log("Did not get data for active promotion $promoID " . $allPromos[$promoID]['name'], null, 'promos.log');
            }
        }
    }


    private function getValidSkuMap () {
        $sql = "SELECT sku FROM catalog_product_entity WHERE schrack_sts_statuslocal IN ('std','wirdausl','istausl')";
        $dbRes = $this->_readConnection->fetchCol($sql);
        $res = [];
        foreach ( $dbRes as $sku ) {
            $res[$sku] = true;
        }
        return $res;
    }


    private function getPromotionSortingMap ( int $promoID ) {
        $query = "SELECT `type` FROM schrack_promotion WHERE entity_id = " . $promoID;
        $promoType = $this->_readConnection->fetchOne($query);
        if ($promoType == 'KAB') {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $wws_customer_id = $customer->getSchrackWwsCustomerId();

            $select  = "SELECT entity_id FROM schrack_promotion_account WHERE promotion_id = " . $promoID;
            $select .= " AND wws_customer_id LIKE '" . $wws_customer_id . "'";
            $promoAccountID = $this->_readConnection->fetchOne($select);

            $sql  = " SELECT spap.`order`, p.sku FROM schrack_promotion_account_product spap";
            $sql .= " JOIN catalog_product_entity p ON p.entity_id = spap.product_id";
            $sql .= " WHERE promotion_account_id = ? ORDER BY `order`";
            $dbRes = $this->_readConnection->fetchAll($sql, $promoAccountID);
        } else {
            $sql = " SELECT pp.`order`, p.sku FROM schrack_promotion_product pp";
            $sql .=  " JOIN catalog_product_entity p ON p.entity_id = pp.product_id";
            $sql .=  " WHERE promotion_id = ? ORDER BY `order`";
            $dbRes = $this->_readConnection->fetchAll($sql, $promoID);
        }
        $res = array();
        foreach ( $dbRes as $row ) {
            $res[$row['sku']] = (int) $row['order'];
        }
        return $res;
    }


    private function addDbResult ( &$res, $dbRes ) {
        foreach ( $dbRes as $row ) {
            $sku = $row['sku'];
            $promoID = $row['promo_id'];
            if ( ! isset($res[self::SKUS_2_PROMO_IDS][$sku]) ) {
                $res[self::SKUS_2_PROMO_IDS][$sku] = array($promoID);
            } else {
                $res[self::SKUS_2_PROMO_IDS][$sku][] = $promoID;
            }
            if ( ! isset($res[self::PROMO_IDS_2_SKUS][$promoID]) ) {
                $res[self::PROMO_IDS_2_SKUS][$promoID] = array($sku);
            } else {
                $res[self::PROMO_IDS_2_SKUS][$promoID][] = $sku;
            }
        }
    }


    private function getLogWwsCustomerId() {
        $WWSCustomerID = Mage::getStoreConfig('schrack/promotions/log_detailed_customer_id');
        return $WWSCustomerID;
    }


    private function writeLog($message, $showWWSCustomerID = false, $source = '') {
        $WWSCustomerID = Mage::getStoreConfig('schrack/promotions/log_detailed_customer_id');
        if ($WWSCustomerID && $message) {
            if ($showWWSCustomerID == true) {
                Mage::log($WWSCustomerID . $source, null, 'promotions_detailed.log');
            }
            Mage::log($message, null, 'promotions_detailed.log');
        }
    }


    // TODO : not yet implemented:
    private function cleanupPromotions($allPromotions) {
        $resultFromComparison = array();
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $wwsCustomerId = $customer->getSchrackWwsCustomerId();
        $formerName = '';

        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $query  = "SELECT * FROM schrack_promotion ORDER BY name";
        $queryResult = $readConnection->query($query);

        if ($queryResult->rowCount() > 0) {
            foreach ($queryResult as $index => $recordset) {
                if ($recordset['entity_id'] > 0) {
                    if ($formerName == strtolower($recordset['name'])) {
                        $resultFromComparison[$formerName][$formerPromoId] = 'doubled';
                        $resultFromComparison[$formerName][$recordset['entity_id']] = 'doubled';
                    }
                    $formerName = strtolower($recordset['name']);
                    $formerPromoId = strtolower($recordset['entity_id']);
                }
            }

            if ($wwsCustomerId && $wwsCustomerId == $this->getLogWwsCustomerId()) {
                //$this->writeLog('=======================  Doublettes (Start)  ===============================');
                //$this->writeLog('Doublettes:');
                //$this->writeLog($resultFromComparison);
                //$this->writeLog('=======================  Doublettes (End)  =================================');
            }

            foreach ($resultFromComparison as $name => $promotionIds) {
                $compareLeft = array();
                $compareRight = array();
                $counter = 0;

                foreach ($promotionIds as $promotionId => $someTag) {
                    if ($counter > 0) {
                        $compareLeft = $allPromotions['promotions'][$formerPromotionId];
                        $compareRight = $this->array_clone($allPromotions['promotions'][$promotionId]);
                        foreach ($compareLeft as $sku => $position) {
                            if (array_key_exists($sku, $compareRight)) {
                                $this->writeLog('Deleted SKU (' . $promotionId . ') -> ' . $sku);
                                unset($allPromotions['promotions'][$promotionId][$sku]);
                            }
                        }
                    }
                    $formerPromotionId = $promotionId;
                    $counter++;
                }
            }

            return $allPromotions;
        }
    }


    function array_clone($array) {
        return array_map(function($element) {
            return ((is_array($element))
                ? array_clone($element)
                : ((is_object($element))
                    ? clone $element
                    : $element
                )
            );
        }, $array);
    }


    private function simplifyJSON($promoData) {
        $simplifiedJSONData = array();
        $tempPromotions     = array();
        $newPromotionsInner = array();

        $tempPromotions = $promoData['promotions'];
        unset($promoData['promotions']);
        foreach ($tempPromotions as $index => $promotion) {
            foreach($promotion['products'] as $position => $sku) {
                $newPromotionsInner[$sku] = $position;
            }
            // Assign products to sku-indexed array:
            $simplifiedJSONData[$promotion['promotion-id']] = $newPromotionsInner;
            // Reset sub-array of products for new structures:
            $newPromotionsInner = array();
        }
        $promoData['promotions'] = $simplifiedJSONData;
        $this->writeLog('================== New Simplified Structure (Start) =============================');
        $this->writeLog($promoData);
        $this->writeLog('================== New Simplified Structure (End) =============================');

        return $promoData;
    }

}
