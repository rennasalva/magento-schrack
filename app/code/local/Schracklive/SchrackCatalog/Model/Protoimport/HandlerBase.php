<?php

use com\schrack\queue\protobuf\Message;

abstract class Schracklive_SchrackCatalog_Model_Protoimport_HandlerBase extends Schracklive_SchrackCatalog_Model_Protoimport_Base {

    protected $_allSchrack2MagentoIdMap;
    protected $_unhandledSchrack2MagentoIdMap;
    protected $_websiteIds;

    protected $_loadedCategoryIdMap;

    private $_old2newCode = array( 
        'schrack_prodgrp' => 'schrack_productgroup',
        'schrack_vklw'    => 'price',
        'schrack_pe'      => 'schrack_priceunit',
        'schrack_me'      => 'schrack_qtyunit',
        'schrack_vpe'     => 'schrack_packingunit'
    );

    function __construct ( $originTimestamp = null ) {
        parent::__construct($originTimestamp);
        $websiteID = Mage::app()->getStore(true)->getWebsite()->getId();
        if ( $websiteID == Mage_Core_Model_App::ADMIN_STORE_ID ) {
            // this is the wrong (!) ID, detecting now the correct one directly from DB:
            self::logDebug("Got wrong website ID from Magento: $websiteID. Trying now to retrieve from DB...");
            $sql = "SELECT website_id FROM core_store WHERE code = 'default'";
            $websiteID = $this->_readConnection->fetchOne($sql);
            if ( ! $websiteID ) {
                // just as an emergency handling
                self::logDebug("Did not get a valid website ID from DB, using emergency constant...");
                $websiteID = Mage_Core_Model_App::DISTRO_STORE_ID;
            }
        }
        self::logDebug("Finally website ID = $websiteID.");
        $this->_websiteIds = array($websiteID);
    }

    protected function cleanup () {}

    public function handle ( Message &$importMsg ) {
        $this->_unhandledSchrack2MagentoIdMap = $this->_allSchrack2MagentoIdMap = $this->getSchrack2MagentoIdMap();

        $this->cleanup();

        $this->doHandle($importMsg);
        
        if ( $importMsg->getPackagetype() === self::PACKAGE_TYPE_FULL && $this->_DO_ONLY_THAT_SKU === null ) {
            $cnt = 0;
            // delete the unhandled things:
            $this->removeAlreadyDeletedFromSchrack2MagentoIdMap($this->_unhandledSchrack2MagentoIdMap);
            foreach ( $this->_unhandledSchrack2MagentoIdMap as $sku => $magentoId ) {
                $this->delete($magentoId);
                if ( ++$cnt % 100 === 0 ) {
                    self::logDebugMem();
                    self::logDebug("calling gc_collect_cycles() now...");
                    gc_collect_cycles();
                    self::logDebugMem();
                }
            }
        }
    }    
    
    protected function mkAsciiUrlString ( $name ) {
        $asciiUrlString = Mage::helper('schrackcore/string')->utf8ToAscii($name);
        // lowercase and all remaining (and not URL compatible) characters replaced with -
        $asciiUrlString = strtolower(preg_replace("/\s+/", '-', preg_replace("/[^A-Za-z0-9]/", ' ', $asciiUrlString)));
        return $asciiUrlString;
    }
    
    protected function removeAlreadyDeletedFromSchrack2MagentoIdMap ( &$map ) {
        // default implementation: do nothing
    }

    protected function getRandomString($n) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }

        return hash("sha256", $randomString);
    }


    abstract public function getSchrack2MagentoIdMap ();

    abstract protected function doHandle ( Message &$importMsg );
    abstract protected function delete ( $magentoId );
    
    protected function addRedirectRename ( $oldName, $newName, $schrackCategoryId = null, $productSku = null ) {
        if ( ($schrackCategoryId == null && $productSku == null) || ($schrackCategoryId != null && $productSku != null) ) {
            throw new Exception("addRedirectRename() needs either schrackCategoryId or productSku param");
        }
        $sql = " INSERT INTO schrack_redirect_rename (old_name, new_name, category_schrack_id, product_sku) VALUES(?,?,?,?)";
        //------------------------------------------------- prevent import error
        /*
         *  Integrity constraint violation:
         *  1048 Column 'new_name' cannot be null, query was:
         *  INSERT INTO schrack_redirect_rename .........
         * */
        if(is_null($newName) || $newName."." == "."){
            $randomString = getRandomString(10);
            $newName = "No new name was defined for SKU[".$productSku."] - Auto-ID:".$randomString;
        }

        $this->_writeConnection->query($sql,array($oldName,$newName,$schrackCategoryId,$productSku));
    }

    protected function loadCategoryMap () {
        $this->_loadedCategoryIdMap = array();
        $sql = " SELECT groupid.value AS groupid, name.value AS name, groupid.entity_id FROM catalog_category_entity_varchar groupid"
             . " JOIN catalog_category_entity_varchar name ON groupid.entity_id = name.entity_id"
             . " 	AND name.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 3)"
             . " WHERE groupid.entity_id > 2"
             . "    AND groupid.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'schrack_group_id')";
        $dbRes = $this->_readConnection->fetchAll($sql);
        foreach ( $dbRes as $row ) {
            $this->_loadedCategoryIdMap[$row['groupid']] = [ 'entity_id' => $row['entity_id'], 'name' => $row['name'] ];
        }
    }

    public function getLoadedOldCategoryMap () {
        return $this->_loadedCategoryIdMap;
    }
    
    public function setLoadedOldCategoryMap ( $map ) {
        $this->_loadedCategoryIdMap = $map;
    }

    protected function getLoadedCategoryIdForScharckId ( $schrackCategoryId ) {
        if ( isset($this->_loadedCategoryIdMap[$schrackCategoryId]) ) {
            return $this->_loadedCategoryIdMap[$schrackCategoryId]['entity_id'];
        }
        return false;
    }

    protected function getLoadedCategorySchrackToMagentoIdMap ( array $schrackCategoryIDs ) {
        $res = array();
        foreach ( $schrackCategoryIDs as $schrackId ) {
            if ( isset($this->_loadedCategoryIdMap[$schrackId]) ) {
                $res[$this->_loadedCategoryIdMap[$schrackId]['entity_id']] = $schrackId;
            }
        }
        return $res;
    }

    protected function getLoadedCategoryMagentoToSchrackIdMapWhereSchrackIdStartsWith ( $startsWith ) {
        $res = array();
        $len = strlen($startsWith);
        foreach ( $this->_loadedCategoryIdMap as $schrackID => $rec ) {
            if ( strncmp($startsWith,$schrackID,$len) === 0 ) {
                $res[$rec['entity_id']] = $schrackID;
            }
        }
        return $res;
    }

    protected function getLoadedCategoryNameForScharckId ( $schrackCategoryId ) {
        if ( isset($this->_loadedCategoryIdMap[$schrackCategoryId]) ) {
            return $this->_loadedCategoryIdMap[$schrackCategoryId]['name'];
        }
        return false;
    }

    protected function name2newCode ( $name ) {
        $code = $this->name2code($name);
        if ( isset($this->_old2newCode[$code]) ) {
            $code = $this->_old2newCode[$code];
        }
        return $code;
    }
    
    protected function name2code ( $name, $magentoProduct = null ) {
        $res = 'schrack_';
        $pointer = 0;
        while ( ($c = $this->nextchar($name,$pointer)) !== false ) {
            switch ( $c ) {
                case 'Ä' : 
                case 'ä' :
                    $c = 'ae';
                    break;
                case 'Ö' : 
                case 'ö' :
                    $c = 'oe';
                    break;
                case 'Ü' : 
                case 'ü' :
                    $c = 'ue';
                    break;
                case 'ß' :
                    $c = 'ss';
                    break;
                case '-' :
                    // no change with that
                    break;
                default :
                    if ( $c >= 'A' && $c <= 'Z' ) {
                        $c = chr(ord($c) + 32);
                    }
                    else if (    $c < '0' 
                              || ($c > '9' && $c < 'a')
                              || $c > 'z'               ) {
                       $c = '_';
                    }
            }
            $res .= $c;
        }
        if ( strlen($res) > 29 ) {
            $res = substr($res,0,29);
        }
        return $res;
    }

    private function nextchar ( $string, &$pointer ){
        if(!isset($string[$pointer])) return false;
        $char = ord($string[$pointer]);
        if($char < 128){
            return $string[$pointer++];
        }else{
            if($char < 224){
                $bytes = 2;
            }elseif($char < 240){
                $bytes = 3;
            }elseif($char < 248){
                $bytes = 4;
            }elseif($char = 252){
                $bytes = 5;
            }else{
                $bytes = 6;
            }
            $str =  substr($string, $pointer, $bytes);
            $pointer += $bytes;
            return $str;
        }
    }

}

?>
