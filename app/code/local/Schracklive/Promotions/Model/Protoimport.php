<?php

use com\schrack\queue\protobuf\Message;

class Schracklive_Promotions_Model_Protoimport extends Schracklive_Schrack_Model_ProtoImportBase {

    private $_importMsg;

    function __construct ( $originTimestamp = null ) {
        parent::__construct('SptPromotionToShop.php',$originTimestamp);
    }

    protected function getLogFileBaseName () {
        return 'promotions_import';
    }

    protected function getDumpFileBaseName () {
        return 'promotions';
    }

    public function run ( &$binData, $originTimestamp = null ) {
        self::$_logTrace = true; // TODO: remove me later on...
        self::beginTrace('whole promotion import');
        self::log("start promotion import...");
        $this->_originTimestamp = $originTimestamp;
        $unzippedBinData = null;
        self::beginTrace('unzipping_data');
        $this->unzipMessage($binData,$unzippedBinData);
        self::endTrace('unzipping_data');
        self::beginTrace('parsing_data');
        $this->_importMsg = new com\schrack\queue\protobuf\Message($unzippedBinData);
        self::endTrace('parsing_data');
        unset($unzippedBinData);

        /* @var $mqHelper Schracklive_Schrack_Helper_Mq */
        $mqHelper = Mage::helper("schrack/mq");

        /* @var $promotion com\schrack\queue\protobuf\Message\Promotion */
        $promotion = $this->_importMsg->getPromotion();
        $promoID = $promotion->getId();
        $msgKey = "impPromos=" . $promoID;
        if ( $this->_originTimestamp ) {
            $isLast = $mqHelper->isLatestUpdate($msgKey,$this->_originTimestamp);
            if ( ! $isLast ) {
                self::logDebug("skipping promotion $promoID because message ts too old");
                return;
            }
        }

        $this->_writeConnection->beginTransaction();
        try {
            self::log("importing promotion $promoID - {$promotion->getName()}");
            // first drop the whole existing stuff for that promotion
            self::beginTrace('removing_promotion_stuff');
            $sql = "DELETE FROM schrack_promotion WHERE entity_id = ?";
            $this->_writeConnection->query($sql, $promoID);
            self::endTrace('removing_promotion_stuff');

            self::beginTrace('adding promotion');
            // insert promotion data
            $promotionIsYearlyKab = $promotion->getIsYearlyKab() ? 1 : 0;
            $sql  = " INSERT INTO schrack_promotion SET";
            $sql .= " `entity_id` = " . $promoID . ",";
            $sql .= " `name` = '" . $promotion->getName() . "',";
            $sql .= " `valid_from` = '" . $promotion->getValidFrom() . "',";
            $sql .= " `valid_to` = '" . $promotion->getValidTo(). "',";
            if ($promotion->getMailinglist()) $sql .= " `mailinglist` = " . $promotion->getMailinglist(). ",";
            $sql .= " `type` = '" . self::type2name($promotion->getType()). "',";
            $sql .= " `is_yearly_kab` = " . $promotionIsYearlyKab. ",";
            if ($promotion->getImageUrl()) $sql .= " `image_url` = '" . $promotion->getImageUrl(). "',";
            $typoSnippetIds = implode(',',$promotion->getTypoSnippetIdsList());
            if ($typoSnippetIds) $sql .= " `typo_snippet_ids` = '" . $typoSnippetIds . "',";
            if ($promotion->getOrder()) $sql .= " `order` = " . $promotion->getOrder(). ",";
            $sql .= " `created_at` = '" . date('Y-m-d H:i:s') . "'";

            $this->_writeConnection->query($sql);
            self::endTrace('adding promotion');

            self::beginTrace('adding promotion products');
            $this->addPromotionProducts($promotion);
            self::endTrace('adding promotion products');

            self::beginTrace('adding promotion accounts');
            $this->addPromotionAccounts($promotion);
            self::endTrace('adding promotion accounts');

            $mqHelper->saveLatestUpdate($msgKey,$this->_originTimestamp);
            $this->_writeConnection->commit();
        } catch ( Schracklive_Promotions_Model_EmptyPromotionException $emptyPromoEx ) {
            self::log("ignoring empty promotion (no valid products) $promoID - {$promotion->getName()}");
            $this->_writeConnection->rollback();
        } catch ( Exception $ex ) {
            // Writes promotion data to db :
            Mage::log($sql ,null ,'promotion.err.log');
            $this->_writeConnection->rollback();
            throw $ex;
        }
        self::log("...promotion import finished.");
        self::endTrace('whole promotion import');
    }

    private function addPromotionAccounts ( $promotion ) {
        /* @var $promotion com\schrack\queue\protobuf\Message\Promotion */
        $promoID = $promotion->getId();
        $wwsIDs = array();
        $insertDate = date('Y-m-d H:i:s');
        foreach ( $promotion->getAccountsList() as $promotionAccount ) {
            $wwsIDs[] = $promotionAccount->getWwsCustomerNr();
        }
        $sql = "SELECT wws_customer_id, account_id FROM account WHERE wws_customer_id IN ('" . implode("','",$wwsIDs) . "')";
        unset($wwsIDs);
        $dbRes = $this->_readConnection->fetchAll($sql);
        $wwsId2accountIdMap = $this->buildMapFRom2dbCols($dbRes,'wws_customer_id','account_id');
        unset($dbRes);
        foreach ( $promotion->getAccountsList() as $promotionAccount ) {
            self::logProgressChar('.');
            $wwsID = $promotionAccount->getWwsCustomerNr();
            if ( ! isset($wwsId2accountIdMap[$wwsID]) ) {
                self::log("Account with ID $wwsID not found - skipping!");
                continue;
            }
            $sql = "INSERT INTO schrack_promotion_account (account_id, promotion_id, wws_customer_id, created_at) VALUES(?,?,?,?)";
            $this->_writeConnection->query( $sql,array($wwsId2accountIdMap[$wwsID], $promoID, $wwsID, $insertDate) );
            $newPromotionAccountID = $this->_readConnection->fetchOne("SELECT LAST_INSERT_ID()");
            if ( $promotionAccount->hasProducts() ) {
                $skus = array_unique($promotionAccount->getProductsList());
                $sql = "INSERT INTO schrack_promotion_account_product (promotion_account_id, product_id, `order`, created_at)";
                $this->addProductRelations($sql,$newPromotionAccountID,$skus);
            }
            if ( $promotionAccount->hasContacts() ) {
                $customerS4yIDs = array();
                foreach ( $promotionAccount->getContactsList() as $contact ) {
                    $customerS4yIDs[] = $contact->getContactId();
                }
                $sql = "SELECT entity_id, schrack_s4y_id FROM customer_entity WHERE schrack_s4y_id IN ('" . implode("','",$customerS4yIDs) . "')";
                unset($customerS4yIDs);
                $dbRes = $this->_readConnection->fetchAll($sql);
                $s4y2entityIdMap = $this->buildMapFRom2dbCols($dbRes,'schrack_s4y_id','entity_id');
                unset($dbRes);
                $sql = "INSERT INTO schrack_promotion_account_customer (promotion_account_id, customer_id, pdf_url, created_at) VALUES(?,?,?,?)";
                foreach ( $promotionAccount->getContactsList() as $contact ) {
                    $s4yID = $contact->getContactId();
                    $customerEntityID = $s4y2entityIdMap[$s4yID];
                    if ( isset($customerEntityID) ) {
                        $this->_writeConnection->query( $sql,array($newPromotionAccountID, $customerEntityID, $contact->getPdfUrl(), $insertDate) );
                    } else {
                        self::log("Contact with s4y_id $s4yID not found - skipping!");
                    }
                }
            }
        }
    }

    private function addPromotionProducts ( $promotion ) {
        /* @var $promotion com\schrack\queue\protobuf\Message\Promotion */
        if ( $promotion->hasProducts() ) {
            $promoID = $promotion->getId();
            $skus = array_unique($promotion->getProductsList());
            $sql = "INSERT INTO schrack_promotion_product (promotion_id, product_id, `order`, created_at)";
            $this->addProductRelations($sql,$promoID,$skus);
        }
    }

    private function addProductRelations ( $sql, $firstVal, $skus ) {
        $insertDate = date("Y-m-d H:i:s");
        $sql .= ' VALUES';
        $sku2idMap = $this->buildSku2idMap($skus);
        $i = 0;
        foreach ( $skus as $sku ) {
            if ( isset($sku2idMap[$sku]) ) {
                if ( ++$i > 1 ) {
                    $sql .= ",";
                }
                $sql .= "($firstVal, $sku2idMap[$sku], $i, '" . $insertDate . "')";
            } else {
                self::log("Product with SKU $sku not found - skipping!");
            }
        }
        if ( $i == 0 ) {
            throw new Schracklive_Promotions_Model_EmptyPromotionException();
        }
        $this->_writeConnection->query($sql);
    }

    protected function buildSku2idMap ( $skus ) {
        $sql = "SELECT sku, entity_id FROM catalog_product_entity WHERE sku IN ('" . implode("','",$skus) . "')";
        $dbRes = $this->_writeConnection->fetchAll($sql);
        $sku2idMap = $this->buildMapFRom2dbCols($dbRes,'sku','entity_id');
        return $sku2idMap;
    }
    
    protected function buildMapFrom2dbCols ( $dbRes, $keyName, $valName ) {
        $res = array();
        foreach ( $dbRes as $row ) {
            $res[$row[$keyName]] = $row[$valName];
        }
        return $res;
    }
    
    private static $_type2nameMap = array( 
        0 => 'KAB',
        1 => 'CDM',
        2 => 'WITH_PDF',
        3 => 'WITHOUT_PDF',
        4 => 'SINGLE'
    );
    
    public static function type2name ( $type ) {
        if ( isset(self::$_type2nameMap[$type]) ) {
            return self::$_type2nameMap[$type];
        }
        return '(unknown)';
    }
}
