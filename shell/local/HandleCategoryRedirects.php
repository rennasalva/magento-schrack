<?php
require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_HandleCategoryRedirects extends Mage_Shell_Abstract
{
    private $readConnection, $writeConnection;
    private $file;
    private $restore = false;
    private $storeId;

    function __construct () {
        parent::__construct();
        if ( ! ($this->file = $this->getArg('save')) ) {
            if ( $this->file = $this->getArg('restore') ) {
                $this->restore = true;
            } else {
                die($this->usageHelp());
            }
        }
        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->storeId = Mage::app()->getStore('default')->getStoreId();
    }

    public function run () {
        if ( $this->restore ) {
            $this->doRestore();
        } else {
            $this->doSave();
        }
        echo "done." . PHP_EOL;
    }

    private function doSave () {
        if ( file_exists($this->file) ) {
            die("File '" . $this->file . "' already exists. Please move or delete it first!" . PHP_EOL . PHP_EOL);
        }
        $fp = fopen($this->file,"w");
        fputcsv($fp,array('request_path','target_path','id','created_at'));
        $sql = " SELECT request_path, target_path, id.value AS id, created_at FROM core_url_rewrite rw"
             . " JOIN catalog_category_entity_varchar id ON id.entity_id = rw.category_id AND id.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id')"
             . " WHERE options = 'RP' AND product_id IS NULL"
             . " ORDER BY created_at ASC;";
        $dbRes = $this->readConnection->fetchAll($sql);
        foreach ( $dbRes as $row ) {
            fputcsv($fp,$row);
        }
        fclose($fp);
    }

    private function doRestore () {
        if ( ! file_exists($this->file) ) {
            die("File '" . $this->file . "' not found!" . PHP_EOL . PHP_EOL);
        }
        $fp = fopen($this->file,"r");
        $csv = fgetcsv($fp);
        while ( $csv = fgetcsv($fp) ) {
            $requestPath = $csv[0];
            $targetPath = $csv[1];
            $id = $csv[2];
            if ( $this->checkExistingRedirect($requestPath) ) {
                echo 'o'; continue;
            }
            if ( $this->tryApplyTargetPath($requestPath,$targetPath) ) {
                echo '+'; continue;
            }
            if ( $this->tryApplyId($requestPath,$id) ) {
                echo 'x'; continue;
            }
            echo '-';
        }
        fclose($fp);
    }

    private function checkExistingRedirect ( $requestPath ) {
        $sql = "SELECT count(*) FROM core_url_rewrite WHERE request_path = ?";
        $cnt = $this->readConnection->fetchOne($sql,$requestPath);
        return $cnt > 0;
    }

    private function tryApplyTargetPath ( $requestPath, $targetPath ) {
        $sql = "SELECT * FROM core_url_rewrite WHERE request_path = ?";
        $dbRes = $this->readConnection->fetchAll($sql,$targetPath);
        if ( count($dbRes) < 1 ) {
            return false;
        }
        $row = $dbRes[0];
        $categoryId = $row['category_id'];
        $targetPath = $row['request_path'];
        $this->insertRedirect($requestPath,$targetPath,$categoryId);
        return true;
    }

    private function tryApplyId ( $requestPath, $id ) {
        $sql = " SELECT entity_id FROM catalog_category_entity_varchar"
             . " WHERE attribute_id = (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id')"
             . " AND value = ?";
        $categoryId = $this->readConnection->fetchOne($sql,$id);
        if ( ! $categoryId ) {
            return false;
        }
        $sql = "SELECT request_path FROM core_url_rewrite WHERE category_id = ? AND product_id IS NULL AND options IS NULL";
        $targetPath = $this->readConnection->fetchOne($sql,$categoryId);
        if ( ! $targetPath ) {
            return false;
        }

        $this->insertRedirect($requestPath,$targetPath,$categoryId);
        return true;
    }

    private function insertRedirect ( $requestPath, $targetPath, $categoryId ) {
        Schracklive_SchrackCatalog_Model_Protoimport_RewriteHandler::URL_REWRITE_FIELD_LIST;
        Schracklive_SchrackCatalog_Model_Protoimport_RewriteHandler::URL_REWRITE_FIELD_QMS;
        $row = array(
            // url_rewrite_id
            $this->storeId,                                     // store_id
            $categoryId,                                        // category_id
            null,                                               // product_id
            microtime(true) . '_' . $categoryId,     // id_path
            $requestPath,                                       // request_path
            $targetPath,                                        // target_path
            0,                                                  // is_system
            null,                                               // created_at
            'RP',                                               // options
            null                                                // description

        );
        $sql = "INSERT INTO core_url_rewrite (" . Schracklive_SchrackCatalog_Model_Protoimport_RewriteHandler::URL_REWRITE_FIELD_LIST . ") VALUES(" . Schracklive_SchrackCatalog_Model_Protoimport_RewriteHandler::URL_REWRITE_FIELD_QMS . ");";
        $this->writeConnection->query($sql,$row);
    }


    public function usageHelp ()
    {
        return <<<USAGE

       php HandleCategoryRedirects.php --save <File>
       
or

       php HandleCategoryRedirects.php --restore <File>


This script does not delete any existing redirects or other url-rewrites.


USAGE;
    }
}

(new Schracklive_Shell_HandleCategoryRedirects())->run();
