<?php

use com\schrack\queue\protobuf\Message;

/**
 * @author d.laslov
 *                                                                          Schracklive_SchrackCatalog_Model_Protoimport_Base
 */
class Schracklive_SchrackCatalog_Model_Protoimport_RelationsHandler extends Schracklive_SchrackCatalog_Model_Protoimport_Base {

    var $_groupsIdMap;
    var $_small2largeIdMap;
    var $_articleIdMap;
    var $_enabledProductIdMap;

    var $_catalogCategoryProductTabName;

    var $_unhandledTree;
    var $_fullTree;

    function __construct ( $groupsIdMap, $articleIdMap, $originTimestamp = null ) {
        parent::__construct($originTimestamp);
        $this->_groupsIdMap = $groupsIdMap;
        $this->_articleIdMap = $articleIdMap;
        $this->_catalogCategoryProductTabName = Mage::getSingleton('core/resource')->getTableName('catalog_category_product');

        if ( ! is_array($this->_groupsIdMap) || count($this->_groupsIdMap) < 1 ) {
            $this->_groupsIdMap = array();
            $sql = " SELECT entity_id, value FROM catalog_category_entity_varchar WHERE attribute_id = ("
                 . "   SELECT attribute_id FROM eav_attribute WHERE entity_type_id = ("
                 . "     SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'catalog_category'"
                 . "   ) AND attribute_code = 'schrack_group_id'"
                 . " );";
            $res = $this->_readConnection->fetchAll($sql);
            foreach ( $res as $row ) {
                $this->_groupsIdMap[$row['value']] = $row['entity_id'];
            }
        }

        if ( ! is_array($this->_articleIdMap) || count($this->_articleIdMap) < 1 ) {
            $this->_articleIdMap = array();
            $sql = "SELECT sku, entity_id FROM catalog_product_entity";
            $res = $this->_readConnection->fetchAll($sql);
            foreach ( $res as $row ) {
                $this->_articleIdMap[$row['sku']] = $row['entity_id'];
            }
        }

        $this->_small2largeIdMap = array();
        foreach ( $this->_groupsIdMap as $schrackID => $magentoID ) {
            if ( ($p = strrpos($schrackID,self::ID_SEPARATOR)) === false ) {
                $smallID = $schrackID;
            } else {
                $smallID = substr($schrackID,$p + 1);
            }
            if ( ! isset($this->_small2largeIdMap[$smallID]) ) {
                $this->_small2largeIdMap[$smallID] = array();
            }
            $this->_small2largeIdMap[$smallID][] = $magentoID;
        }

        $this->_unhandledTree = array();
        $query = 'SELECT * FROM ' . $this->_catalogCategoryProductTabName;
        $results = $this->_readConnection->fetchAll($query);
        foreach ( $results as $rec ) {
            $cat = $rec['category_id'];
            $prd = $rec['product_id'];
            $pos = $rec['position'];
            $isAcc = $rec['schrack_sts_is_accessory'];
            if ( ! isset($this->_unhandledTree[$cat]) ) {
                $this->_unhandledTree[$cat] = array();
            }
            $this->_unhandledTree[$cat][$prd] = array('position' => (int) $pos, 'isAccessory' => $isAcc == 0 ? false : true);
        }
        $this->_fullTree = $this->_unhandledTree;
    }

    public function getCategoryProductIdTree () {
        return $this->_fullTree;
    }

    public function handle ( Message &$importMsg ) {
        /* @var $ref \com\schrack\queue\protobuf\Message\ArticleGroup */
        foreach ( $importMsg->getArticlegrouprefsList() as $ref ) {
            $logKey = $ref->getGroup() . ' <-> ' . $ref->getArticle() . ' #' . $ref->getOrdernumber();
            if ( substr($ref->getGroup(),0,2) === '99' ) {
                self::logDebug('skipping relation ' . $logKey);
                continue; // we skip datanorm special chapter
            }
            $msgKey = 'impRel=' . $ref->getGroup() . '-' . $ref->getArticle();
            if ( isset($this->_originTimestamp) ) {
                $isLast = Mage::helper("schrack/mq")->isLatestUpdate($msgKey,$this->_originTimestamp);
                if ( ! $isLast ) {
                    self::logDebug('skipping group ' . $logKey . ' because message ts too old');
                    continue;
                }
            }
            self::logDebug('start relation(s) ' . $logKey);
            $magentoCategoryIDs = $this->_small2largeIdMap[self::prepareIncommingID($ref->getGroup())];
            if (is_array($magentoCategoryIDs)) {
                foreach ( $magentoCategoryIDs as $magentoCategoryId ) {
                    $sku = $ref->getArticle();
                    $magentoProductId = $this->_articleIdMap[$sku];
                    self::logDebug('magento ids are (cat, prod) ' . $magentoCategoryId . ', ' . $magentoProductId);
                    if ( !$magentoCategoryId ) {
                        self::log("WARNING: reference $logKey references an unknown category: " . $ref->getGroup());
                        continue;
                    }
                    if ( !$magentoProductId ) {
                        self::log("WARNING: reference $logKey references an unknown product: " . $sku);
                        continue;
                    }
                    // ###############################################
                    if ( $this->_DO_ONLY_THAT_SKU !== null && strtoupper($sku) !== $this->_DO_ONLY_THAT_SKU )
                        continue;
                    // ###############################################
                    $position = $ref->getOrdernumber();
                    $isAccessory = $ref->getIsaccessory();
                    switch ( $ref->getAction() ) {
                        case 'insert-or-update' :
                            $productEnabled = $this->isProductEnabled($magentoProductId);
                            if ( isset($this->_unhandledTree[$magentoCategoryId][$magentoProductId]) ) {
                                if ( $productEnabled ) {
                                    if (    $this->_unhandledTree[$magentoCategoryId][$magentoProductId]['position'] === $position
                                         && $this->_unhandledTree[$magentoCategoryId][$magentoProductId]['isAccessory'] === $isAccessory ) {
                                        self::logProgressChar('.');
                                    } else {
                                        self::logProgressChar('u');
                                        $this->updateRelation($magentoCategoryId, $magentoProductId, $position, $isAccessory);
                                    }
                                } else {
                                    self::logProgressChar('d');
                                    $this->deleteRelation($magentoCategoryId, $magentoProductId);
                                }
                            } else if ( $productEnabled ) {
                                self::logProgressChar('i');
                                $this->insertRelation($magentoCategoryId, $magentoProductId, $position, $isAccessory);
                            }
                            break;
                        case 'delete' :
                            self::logProgressChar('d');
                            $this->deleteRelation($magentoCategoryId, $magentoProductId);
                            break;
                        default:
                            throw new Exception("Unsupported action {$ref->getAction()}!");
                            break;
                    }
                    if ( isset($this->_originTimestamp) ) {
                        Mage::helper("schrack/mq")->saveLatestUpdate($msgKey, $this->_originTimestamp);
                    } else {
                        Mage::helper("schrack/mq")->removeTimestamp($msgKey);
                    }
                    if ( isset($this->_unhandledTree[$magentoCategoryId][$magentoProductId]) ) {
                        unset($this->_unhandledTree[$magentoCategoryId][$magentoProductId]);
                    }
                }
                self::logDebug('done relation ' . $logKey);
            } else {
                self::logDebug('$magentoCategoryIDs is not an array as expected');
            }
        }

        $this->removeDeadAndStrategicNoRefs();

        self::logDebug('delete unhandled relations:');
        foreach ( $this->_unhandledTree as $catKey => $prodKeyArray ) {
            foreach ( $prodKeyArray as $prodKey => $pos ) {
                if ( isset($pos) ) {
                    self::logProgressChar('d');
                    $this->deleteRelation($catKey,$prodKey);
                }
            }
        }
        self::logDebug('delete unhandled relations done');
    }

    private function removeDeadAndStrategicNoRefs () {
        $sql = "DELETE ccp FROM catalog_category_product ccp"
             . " JOIN catalog_product_entity prod ON ccp.product_id = prod.entity_id"
             . " WHERE prod.schrack_sts_statuslocal IN ('tot','strategic_no','unsaleable');";
        $this->_writeConnection->query($sql);
    }
    
    private function isProductEnabled ( $magentoProductId ) {
        if ( ! $this->_enabledProductIdMap ) {
            $this->loadEnabledProductIdMap();
        }
        return isset($this->_enabledProductIdMap[$magentoProductId]);
    }

    private function loadEnabledProductIdMap () {
        $entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
        $query = "SELECT prod.entity_id FROM catalog_product_entity AS prod"
               . " JOIN catalog_product_entity_int attr ON prod.entity_id = attr.entity_id"
               . " WHERE attr.value = " . Mage_Catalog_Model_Product_Status::STATUS_ENABLED
               . " AND attr.attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = $entityTypeId AND attribute_code = 'status')";
        $results = $this->_readConnection->fetchAll($query);
        $this->_enabledProductIdMap = array();
        foreach ( $results as $rec ) {
            $entityId = $rec['entity_id'];
            $this->_enabledProductIdMap[$entityId] = true;
        }
    }

    private function deleteRelation ( $categoryId, $productId ) {
        $query = "DELETE FROM `{$this->_catalogCategoryProductTabName}` WHERE category_id = '$categoryId' AND product_id = '$productId'";
        $this->_writeConnection->query($query);
        if ( isset($this->_fullTree[$categoryId][$productId]) ) {
            unset($this->_fullTree[$categoryId][$productId]);
        }
    }
    
    private function insertRelation ( $categoryId, $productId, $position, $isAccessory ) {
        $isAccessory = $isAccessory ? 1 : 0;
        $insertQuery = " INSERT INTO `{$this->_catalogCategoryProductTabName}` (category_id,product_id,position,schrack_sts_is_accessory) VALUES ('$categoryId', '$productId', '$position', '$isAccessory')"
                     . " ON DUPLICATE KEY UPDATE position = '$position', schrack_sts_is_accessory = '$isAccessory'";
        $this->_writeConnection->query($insertQuery);
        $this->updateFullTree($categoryId, $productId, $position);
    }

    private function updateRelation ( $categoryId, $productId, $position, $isAccessory ) {
        $isAccessory = $isAccessory ? 1 : 0;
        $updateQuery = "UPDATE `{$this->_catalogCategoryProductTabName}` SET position = '$position', schrack_sts_is_accessory = '$isAccessory' WHERE category_id = '$categoryId' AND product_id = '$productId'";
        $this->_writeConnection->query($updateQuery);
        $this->updateFullTree($categoryId, $productId, $position);
    }

    private function updateFullTree ( $categoryId, $productId, $position ) {
        if ( ! isset($this->_fullTree[$categoryId]) ) {
            $this->_fullTree[$categoryId] = array();
        }
        $this->_fullTree[$categoryId][$productId] = $position;
    }
}

?>
