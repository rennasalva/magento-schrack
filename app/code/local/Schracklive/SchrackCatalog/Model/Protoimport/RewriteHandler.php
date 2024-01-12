<?php

class Schracklive_SchrackCatalog_Model_Protoimport_RewriteHandler extends Schracklive_SchrackCatalog_Model_Protoimport_HandlerBase {

    const URL_REWRITE_FIELD_LIST  = "store_id,category_id,product_id,id_path,request_path,target_path,is_system,created_at,options,description";
    const URL_REWRITE_FIELD_QMS   = "?,?,?,?,?,?,?,?,?,?";

    // TODO: remove that old behaviour after 3 months or something of running the new stuff
    public function saveOldStuff () {
        self::log('saving old IDs and URLs...');
        $sql = " SELECT cat.entity_id, attrID.value AS schrack_id FROM catalog_category_entity AS cat"
             . " JOIN catalog_category_entity_varchar attrID ON (cat.entity_id = attrID.entity_id AND attrID.attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id'))"
             . " ORDER BY attrID.value;";
        $res = $this->_readConnection->fetchAll($sql);
        $fileName = $this->getNewIdFileName();
        if ( ($handle = fopen($fileName, "w")) !== false ) {
            foreach ( $res as $row ) {
                $entityID = $row['entity_id'];
                $fullSchrackID = $schrackID = $row['schrack_id'];
                $p = strrpos($schrackID, '/');
                if ( $p !== false ) {
                    $schrackID = substr($schrackID, $p + 1);
                }
                fputcsv($handle,array($schrackID,$entityID,$fullSchrackID));
            }
            fclose($handle);
        } else {
            throw new Exception("Cannot create file $fileName");
        }

        $sql = "SELECT " . self::URL_REWRITE_FIELD_LIST . " FROM core_url_rewrite WHERE product_id IS NULL AND description <> 'created from metadata'";
        $res = $this->_readConnection->fetchAll($sql);
        $fileName = $this->getNewUrlFileName();
        if ( ($handle = fopen($fileName, "w")) !== false ) {
            foreach ( $res as $row ) {
                fputcsv($handle,$this->prepareNull($row));
            }
            fclose($handle);
        } else {
            throw new Exception("Cannot create file $fileName");
        }
        self::log('...saving old IDs and URLs');
    }

    public function createRewriteMetadata () {
        self::log('deleting old metadata...');
        $sql = "DELETE FROM schrack_redirect_rename WHERE created_at < NOW() - INTERVAL 3 MONTH";
        $this->_writeConnection->query($sql);
        self::log('...deleting old metadata');
        self::log('creating rewrite metadata...');
        $sql = "SELECT entity_id, category_schrack_id, product_sku FROM schrack_redirect_rename WHERE children_created = 0";
        $renames = $this->_readConnection->fetchAll($sql);
        if ( count($renames) == 0 ) {
            self::log("no renames happened");
        } else {
            $this->_writeConnection->beginTransaction();
            try {
                foreach ( $renames as $renameRow ) {
                    self::logProgressChar('R');
                    if ( $renameRow['product_sku'] ) {
                        $this->addProductRewriteMetadata($renameRow['entity_id'],$renameRow['product_sku']);
                    } else {
                        $this->addCategoryRewriteMetadata($renameRow['entity_id'],$renameRow['category_schrack_id']);
                    }
                }
                $sql = "UPDATE schrack_redirect_rename SET children_created = 1 WHERE children_created = 0";
                $this->_writeConnection->query($sql);
                $this->_writeConnection->commit();
            } catch ( Exception $ex ) {
                $this->_writeConnection->rollback();
                self::log("creating rewrite metadata fialed with exception: " . $ex->getMessage());
                Mage:
                logException($ex);
            }
        }
        self::log('...creating rewrite metadata');
    }

    private function addProductRewriteMetadata ( $renameId, $sku ) {
        $sql = "SELECT entity_id FROM catalog_product_entity WHERE sku = ?";
        $productId = $this->_readConnection->fetchOne($sql,$sku);

        // get schrack IDs for categories:
        $sql = " SELECT value FROM catalog_category_entity_varchar"
             . " WHERE entity_id IN (SELECT category_id FROM catalog_category_product WHERE product_id = ?)"
             . " AND attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'schrack_group_id')";
        $catSchrackIDs = $this->_readConnection->fetchCol($sql,$productId);

        // get old entity_ids
        $catOldMagentoToSchrackIdMap = $this->getLoadedCategorySchrackToMagentoIdMap($catSchrackIDs);

        // get old request paths and cat IDs
        $sql = " SELECT request_path, category_id FROM schrack_core_url_rewrite_copy cur"
             . " WHERE store_id = ? AND is_system = 1"
             . " AND product_id = ?"
             . " AND (category_id IS NULL"
             . (count($catOldMagentoToSchrackIdMap) > 0 ? " OR category_id IN (" .  implode(',',array_keys($catOldMagentoToSchrackIdMap))  . ")" : "")
             . ")";
        $dbRes = $this->_readConnection->fetchAll($sql,array(self::DEFAULT_STORE_ID,$productId));
        foreach ( $dbRes as $row ) {
            self::logProgressChar('p');
            $sql = " INSERT INTO schrack_redirect_rename_url_to_ids (rename_id, request_path, category_schrack_id, product_id)"
                 . " VALUES(?,?,?,?)";
            $this->_writeConnection->query($sql,array($renameId,$row['request_path'],$catOldMagentoToSchrackIdMap[$row['category_id']],$productId));
        }
    }

    private function addCategoryRewriteMetadata ( $renameId, $categorySchrackId ) {
        // get old Magento IDs for sub categories
        $catId2schrackIdMap = $this->getLoadedCategoryMagentoToSchrackIdMapWhereSchrackIdStartsWith($categorySchrackId . '#');
        // add current one
        $catId2schrackIdMap[$this->getLoadedCategoryIdForScharckId($categorySchrackId)] = $categorySchrackId;

        // search all
        $sql = " SELECT request_path, category_id, product_id FROM schrack_core_url_rewrite_copy cur"
             . " WHERE store_id = ? AND is_system = 1"
             . " AND category_id IN (" . implode(",",array_keys($catId2schrackIdMap)) . ")";
        $dbRes = $this->_readConnection->fetchAll($sql,self::DEFAULT_STORE_ID);
        foreach ( $dbRes as $row ) {
            self::logProgressChar('c');
            $sql = " INSERT INTO schrack_redirect_rename_url_to_ids (rename_id, request_path, category_schrack_id, product_id)"
                 . " VALUES(?,?,?,?)";
            $schrackCatId = $catId2schrackIdMap[$row['category_id']];
            $this->_writeConnection->query($sql,array($renameId,$row['request_path'],$schrackCatId,$row['product_id']));
        }
    }

    public function addOldPathsAsPermanentRedirects () {
        $this->addOldPathsAsPermanentRedirectsOldIMpl(); // TODO: remove that old behaviour after 3 months or something of running the new stuff
        self::log('create redirects from metadata...');

        $sql = " CREATE TEMPORARY TABLE tmp_reqpat_and_ids"
             . " SELECT meta.request_path, catattr.entity_id as category_id, meta.product_id, meta.entity_id as meta_id FROM schrack_redirect_rename_url_to_ids meta"
             . " LEFT JOIN catalog_category_entity_varchar catattr ON catattr.value = meta.category_schrack_id"
             . " AND catattr.attribute_id =(SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'schrack_group_id')";
        $this->_readConnection->query($sql);

        $dbRes = array();
        $sql = " SELECT tmp.request_path, cur.request_path as target_path, cur.category_id, cur.product_id, tmp.meta_id FROM tmp_reqpat_and_ids tmp"
             . " JOIN core_url_rewrite cur ON cur.category_id = tmp.category_id AND cur.product_id = tmp.product_id AND cur.store_id = 1"
             . " WHERE tmp.request_path <> cur.request_path";
        $dbRes1 = $this->_readConnection->query($sql);
        foreach ( $dbRes1 as $row ) {
            $dbRes[] = $row;
        }
        $sql = " SELECT tmp.request_path, cur.request_path as target_path, cur.category_id, cur.product_id, tmp.meta_id FROM tmp_reqpat_and_ids tmp"
             . " JOIN core_url_rewrite cur ON cur.category_id = tmp.category_id AND cur.product_id IS NULL AND tmp.product_id IS NULL AND cur.store_id = 1"
             . " WHERE tmp.request_path <> cur.request_path";
        $dbRes2 = $this->_readConnection->query($sql);
        foreach ( $dbRes2 as $row ) {
            $dbRes[] = $row;
        }
        $sql = " SELECT tmp.request_path, cur.request_path as target_path, cur.category_id, cur.product_id, tmp.meta_id FROM tmp_reqpat_and_ids tmp"
             . " JOIN core_url_rewrite cur ON cur.category_id IS NULL AND tmp.category_id IS NULL AND cur.product_id = tmp.product_id AND cur.store_id = 1"
             . " WHERE tmp.request_path <> cur.request_path";
        $dbRes3 = $this->_readConnection->query($sql);
        foreach ( $dbRes3 as $row ) {
            $dbRes[] = $row;
        }

        $this->_readConnection->query("DROP TABLE tmp_reqpat_and_ids");

        $cnt = 0;
        foreach ( $dbRes as $row ) {
            ++$cnt;
            self::logProgressChar('.');
            $sql = " INSERT INTO core_url_rewrite (store_id,category_id,product_id,id_path,request_path,target_path,is_system,options,description)"
                 . " VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE url_rewrite_id = url_rewrite_id";
            $catID = $row['category_id'];
            $prodID = $row['product_id'];
            $metaID = $row['meta_id'];
            $idPath = ($catID ? $catID : '') . '_' . ($prodID ? $prodID : '') . '_' . $metaID;
            $this->_writeConnection->query($sql,array(self::DEFAULT_STORE_ID,$catID,$prodID,$idPath,$row['request_path'],
                                                      $row['target_path'],0,"RP","created from metadata"));
        }
        self::log("$cnt permanent redirects created");
        self::log("...create redirects from metadata");
    }

    // TODO: remove that old behaviour after 3 months or something of running the new stuff
    private function addOldPathsAsPermanentRedirectsOldIMpl () {
        self::log('restore URLs to new IDs...');
        $manualRedirOld2newSchrackIdMap = array();
        $fileName = $this->getManualRedirectFileName();
        if ( ($handle = fopen($fileName, "r")) !== false ) {
            while ( ($data = fgetcsv($handle, 1000, ",")) !== false ) {
                $oldSchrackID = $data[0];
                $newSchrackID = $data[1];
                $manualRedirOld2newSchrackIdMap[$oldSchrackID] = $newSchrackID;
            }
            fclose($handle);
        }

        $old2newEntityIdMap = array();
        $fileName = $this->getLastIdFileName();
        if ( ($handle = fopen($fileName, "r")) !== false ) {
            while ( ($data = fgetcsv($handle, 1000, ",")) !== false ) {
                $oldSchrackID = $data[0];
                $newSchrackID = isset($manualRedirOld2newSchrackIdMap[$oldSchrackID]) ? $manualRedirOld2newSchrackIdMap[$oldSchrackID] : false;
                $searchSchrackID = $newSchrackID ? $newSchrackID : $oldSchrackID;
                $oldEntityID = $data[1];
                $sql = " SELECT entity_id"
                     . " FROM catalog_category_entity_varchar"
                     . " WHERE attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id')"
                     . " AND value LIKE '%/$searchSchrackID'";
                $res = $this->_readConnection->fetchCol($sql);
                foreach ( $res as $newEntityID ) {
                    $old2newEntityIdMap[$oldEntityID] = $newEntityID;
                    break; // we take always first now...
                }
            }
            fclose($handle);
        }

        $newId2pathMap = array();
        $sql = "SELECT category_id, request_path FROM core_url_rewrite WHERE product_id IS NULL AND is_system = 1";
        $res = $this->_readConnection->fetchAll($sql);
        foreach ( $res as $row ) {
            $newId2pathMap[$row['category_id']] = $row['request_path'];
        }

        $fileName = $this->getLastUrlFileName();
        if ( ($handle = fopen($fileName, "r")) !== false ) {
            while ( ($data = fgetcsv($handle, 1000, ",")) !== false ) {
                $oldEntityID = $data[1];
                if ( isset($old2newEntityIdMap[$oldEntityID]) ) {
                    $newID = $old2newEntityIdMap[$oldEntityID];
                    $newPath = $newId2pathMap[$newID];
                    $this->handleRecord($this->unprepareNull($data), $oldEntityID,$newID,$newPath);
                }
            }
            fclose($handle);
        }
        self::log('...restore URLs to new IDs');
    }

    private function handleRecord ( array $row, $oldID, $newID, $newPath ) {
        if ( intval($newID) < 1 ) {
            return;
        }
        if ( $row[4] == $newPath ) { // request_path
            return;
        }
        $sql = "SELECT count(*) FROM core_url_rewrite WHERE request_path = ?";
        $cnt = $this->_readConnection->fetchOne($sql,$row[4]);
        if ( $cnt > 0 ) {
            return;
        }
        $this->patchID($row,1,$oldID,$newID);
        $this->patchID($row,3,$oldID,$newID);
        $this->patchID($row,5,$oldID,$newID);
        $row[3] = '' . microtime(true) . '_' . $row[1]; // id_path
        $row[5] = $newPath;                             // target_path
        $row[6] = 0;                                    // is_system
        $row[8] = 'RP';                                 // options
        $sql = "INSERT INTO core_url_rewrite (" . self::URL_REWRITE_FIELD_LIST . ") VALUES(" . self::URL_REWRITE_FIELD_QMS . ");";
        $this->_writeConnection->query($sql,$row);
    }

    private function patchID ( array &$row, $ndx, $oldID, $newID ) {
        if ( $row[$ndx] == $oldID ) {
            $row[$ndx] = $newID;
        } else {
            $p = strrpos($row[$ndx],'/');
            if ( $p !== false && substr($row[$ndx],$p + 1) == $oldID ) {
                $row[$ndx] = substr($row[$ndx],0,$p + 1) . $newID;
            }
        }
    }

    private function getNewIdFileName () {
        return $this->backupIfExists($this->getStorePath('ids','csv'));
    }
    private function getLastIdFileName () {
        return $this->getStorePath('ids','csv');
    }
    private function getNewUrlFileName () {
        return $this->backupIfExists($this->getStorePath('urls','csv'));
    }
    private function getLastUrlFileName () {
        return $this->getStorePath('urls','csv');
    }

    private function getManualRedirectFileName () {
        return $this->getStorePath('manual_redirects','csv');
    }

    private function prepareNull ( array &$ar ) {
        foreach ( $ar as $k => $v ) {
            if ( $v == null ) {
                $ar[$k] = '{{null}}';
            }
        }
        return $ar;
    }

    private function unprepareNull ( array &$ar ) {
        foreach ( $ar as $k => $v ) {
            if ( $v == '{{null}}' ) {
                $ar[$k] = null;
            }
        }
        return $ar;
    }

    public function getSchrack2MagentoIdMap () {
        // TODO: Implement getSchrack2MagentoIdMap() method.
    }

    protected function doHandle ( \com\schrack\queue\protobuf\Message &$importMsg ) {
        // TODO: Implement doHandle() method.
    }

    protected function delete ( $magentoId ) {
        // TODO: Implement delete() method.
    }
}