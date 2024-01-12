<?php


class Schracklive_Tools_Helper_Articles {

    public function getAdditionalArticleData ( array $skus ) {
        $skus = array_unique($skus);

        $sql = " SELECT p.sku, p.schrack_qtyunit AS qtyunit, p.schrack_priceunit AS priceunit, n.value AS name,"
             . "        d.value as descr, a.url AS image, u.request_path AS url, p.schrack_sts_statuslocal AS status,"
             . "        p.schrack_sts_main_vpe_size AS vpesize FROM catalog_product_entity AS p"
             . " JOIN catalog_product_entity_varchar n ON p.entity_id = n.entity_id AND n.attribute_id"
             . "  = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 4)"
             . " LEFT JOIN catalog_product_entity_text d ON p.entity_id = d.entity_id AND d.attribute_id"
             . "    = (SELECT attribute_id FROM eav_attribute"
             . "        WHERE attribute_code = 'schrack_long_text_addition' AND entity_type_id = 4)"
             . " LEFT JOIN catalog_attachment a ON p.entity_id = a.entity_id"
             . "                              AND a.entity_type_id = 4 AND a.filetype = 'foto'"
             . " JOIN core_url_rewrite u ON u.product_id = p.entity_id AND category_id IS NULL"
             . " WHERE sku IN ('" . implode("','",$skus) . "')";

        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $dbRes = $conn->fetchAll($sql);

        $skus4prices = array();
        $badStatusArray = array('tot' => true, 'strategic_no' => true,'unsaleable' => true);
        foreach ( $dbRes as $row ) {
            if ( ! isset($badStatusArray[$row['status']]) ) {
                $skus4prices[$row['sku']] = true;
            }
        }

        $productHelper = Mage::helper('schrackcatalog/product');
        $prices = $productHelper->getPriceProductInfo(array_keys($skus4prices));

        $imageBaseUrl = Mage::getStoreConfig('schrack/general/imageserver') . '110x130';
        $res = array();
        $doneMap = array();
        foreach ( $dbRes as $row ) {
            if ( isset($badStatusArray[$row['status']]) ) {
                continue;
            }
            $sku = $row['sku'];
            if ( isset($doneMap[$sku]) ) { // ignoring multiple images or urls
                continue;
            }
            $doneMap[$sku] = true;
            $rec = new stdClass();
            $rec->name = $row['name'];
            $rec->description = $row['descr'] ? $row['descr'] : '';
            $rec->price = $prices[$sku]['price'];
            $rec->priceFloat = floatval(str_replace(',','.',str_replace('.','',$rec->price)));
            $rec->priceunit = $row['priceunit'] . ' ' . $row['qtyunit'];
            $rec->priceunitInt = intval($row['priceunit']);
            $rec->qtyunit = $row['qtyunit'];
            $rec->vpesizeInt = intval($row['vpesize']);
            if ( $row['image'] > '' ) {
                $rec->image = str_replace('foto', $imageBaseUrl, $row['image']);
            } else {
                $rec->image = Schracklive_SchrackCatalog_Helper_Image::getFullPlaceholderUrl();
            }
            $rec->url = Mage::getBaseUrl() . $row['url'];
            $res[$sku] = $rec;
        }
        return $res;
    }

    public function getArticleDataExtended ( array $skus, array $fieldOptions ) {
        $skus = array_unique($skus);
        $articles = $this->getAdditionalArticleData($skus);
        $labels = array();

        // getting attribute stuff:
        $sql = " SELECT attribute_code, attribute_id, backend_type, frontend_input, frontend_label FROM eav_attribute"
             . " WHERE attribute_code IN ('" . implode("','",array_keys($fieldOptions)) . "')";
        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $dbRes = $conn->fetchAll($sql);
        $attributeDefinitions = array(); $text = array(); $textMulti = array(); $varchar = array(); $varcharMulti = array();
        foreach ( $dbRes as $row ) {
            $attributeCode = $row['attribute_code'];
            $attributeID = $row['attribute_id'];
            $attributeDefinitions[$attributeID] = $row;
            if ( $row['backend_type'] == 'text' ) {
                if ( $row['frontend_input'] == 'multiselect' ) {
                    $textMulti[] = $attributeID;
                } else {
                    $text[] = $attributeID;
                }
            } else { // must be varchar
                if ( $row['frontend_input'] == 'multiselect' ) {
                    $varcharMulti[] = $attributeID;
                } else {
                    $varchar[] = $attributeID;
                }
            }
            $labels[$attributeCode] = $row['frontend_label'];
        }

        $this->addSingelAttributes($articles,$skus,$text,'catalog_product_entity_text',$attributeDefinitions,$fieldOptions);
        $this->addSingelAttributes($articles,$skus,$varchar,'catalog_product_entity_varchar',$attributeDefinitions,$fieldOptions);
        $this->addMultiAttributes($articles,$skus,$textMulti,'catalog_product_entity_text',$attributeDefinitions,$fieldOptions);
        $this->addMultiAttributes($articles,$skus,$varcharMulti,'catalog_product_entity_varchar',$attributeDefinitions,$fieldOptions);

        return array(
            'articles' => $articles,
            'labels' => $labels
        );
    }

    private function addSingelAttributes ( array &$articles, array $skus, array $attributeIDs, $tableName, array $attributeDefinitions, array $fieldOptions ) {
        if ( count($attributeIDs) < 1 ) {
            return;
        }
        $dbRes = $this->queryAttributeVals($skus,$attributeIDs,$tableName);
        foreach ( $dbRes as $row ) {
            $attributeID = $row['attribute_id'];
            $attributeCode = $attributeDefinitions[$attributeID]['attribute_code'];
            $sku = $row['sku'];
            $val = $row['value'];
            if ( in_array('bool',$fieldOptions[$attributeCode]) ) {
                $val = $this->stringToBoolean($val);
            }
            $article = $articles[$sku];
            if (    ! isset($fieldOptions[$attributeCode]->excludeArticles)
                 || ! in_array($sku,$fieldOptions[$attributeCode]->excludeArticles) ) {
                $article->$attributeCode = $val;
            }
        }
    }

    private function addMultiAttributes ( array &$articles, array $skus, array $attributeIDs, $tableName, array $attributeDefinitions, array $fieldOptions ) {
        if ( count($attributeIDs) < 1 ) {
            return;
        }
        $dbRes = $this->queryAttributeVals($skus,$attributeIDs,$tableName);
        $optionIDs = array();
        foreach ( $dbRes as $row ) {
            $vals = explode(',',$row['value']);
            foreach ( $vals as $val ) {
                $optionIDs[$val] = true;
            }
        }
        $sql = " SELECT DISTINCT option_id, value FROM eav_attribute_option_value"
             . " WHERE option_id IN (" . implode(',',array_keys($optionIDs)) . ")";
        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $dbRes2 = $conn->fetchAll($sql);
        $id2val = array();
        foreach ( $dbRes2 as $row ) {
            $id2val[$row[option_id]] = $row['value'];
        }
        foreach ( $dbRes as $row ) {
            $attributeID = $row['attribute_id'];
            $attributeCode = $attributeDefinitions[$attributeID]['attribute_code'];
            $sku = $row['sku'];
            $val = $row['value'];
            $article = $articles[$sku];
            if ( strpos($val,',') === false ) { // just one entry
                $val = array($id2val[$val]);
            } else {
                $ids = explode(',',$val);
                $val = array();
                foreach ( $ids as $id ) {
                    $val[] = $id2val[$id];
                }
            }
            if ( in_array('bool',$fieldOptions[$attributeCode]) ) {
                $val = $this->stringToBoolean($val);
            }
            if (    ! isset($fieldOptions[$attributeCode]->excludeArticles)
                 || ! in_array($sku,$fieldOptions[$attributeCode]->excludeArticles) ) {
                $article->$attributeCode = $val;
            }
        }
    }

    private function queryAttributeVals ( $skus, $attributeIDs, $tableName ) {
        $sql = " SELECT sku, attribute_id, value FROM catalog_product_entity AS prod"
             . " JOIN $tableName attr ON attr.entity_id = prod.entity_id AND attribute_id IN (" . implode(',',$attributeIDs) . ")"
             . " WHERE sku IN ('" . implode("','",$skus) . "')";
        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $dbRes = $conn->fetchAll($sql);
        return $dbRes;
    }

    private function stringToBoolean ( $stringVal ) {
        return $stringVal == 'ja' || $stringVal == 'Ja';
    }
}

