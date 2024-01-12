<?php

use com\schrack\queue\protobuf\Message;

/**
 * Description of GroupHandler
 *
 * @author d.laslov
 */
class Schracklive_SchrackCatalog_Model_Protoimport_GroupsHandler extends Schracklive_SchrackCatalog_Model_Protoimport_HandlerBase
{
    const FACET_SHOW = 'show';
    const FACET_SEARCH = 'search';
    const FACET_BOTH = 'both';

    const DISCONTINUED_CATEGORY_NAME = 'Discontinued';

    private static $_staticIdMap;

    /* @var _categoryModelTemplate Schracklive_SchrackCatalog_Model_Category */
    var $_categoryModelTemplate;

    var $_categoryId2pathMap = array("2" => "1/2");

    var $_alreadyUsedGroupHashes;

    var $_importMsg;

    var $_fullIdToNewProtoGroupMap = array();

    function __construct ( $originTimestamp = null ) {
        parent::__construct($originTimestamp);
        $this->_categoryModelTemplate = Mage::getModel('catalog/category')->addData(array(
            'custom_design' => '',
            'custom_use_parent_settings' => 0,
            'custom_apply_to_products' => 0,
            'custom_layout_update' => '',
            'custom_design_from' => '',
            'custom_design_to' => '',
        ));
    }

    protected function cleanup () {
    }

    private function buildGroupMapWithTemporaryTreeFromImportMessage () {
        self::log('building group tree from message...');
        $parents2children = array();
        $rootNodes = array();
        $msgGroups = $this->_importMsg->getGroupsList();
        /** @var $inGroup \com\schrack\queue\protobuf\Message\Group * */
        foreach ( $msgGroups as $group ) {
            if ( $group->getAction() === self::ACTION_DELETE ) {
                continue; // we do not trust the actuality of that information -> deletes will result from diff
            }
            if ( $group->getNotforwebshop() || substr($group->getId(), 0, 2) === '99' ) {
                continue; // ignoring groups dedicated to datanorm only
            }
            $parent = $group->getParent();
            if ( ! $parent ) {
                $rootNodes[] = $group;
            } else {
                if ( isset($parents2children[$parent]) ) {
                    $parents2children[$parent][] = $group;
                } else {
                    $parents2children[$parent] = array( $group );
                }
            }
        }

        $tree = array();
        foreach ( $rootNodes as $rootNode ) {
            $node = $this->createTreeNode($rootNode,null);
            $this->addChildren($node,$parents2children);
            $tree[] = $node;
        }

        self::log('...building group tree from message');
        unset($tree);
    }

    private function addChildren ( &$node, &$parents2children ) {
        if(array_key_exists($node->id, $parents2children)) {
            $children = $parents2children[$node->id];
            foreach ( $children as $childGroup ) {
                $childNode = $this->createTreeNode($childGroup,$node->group->getId());
                $node->children[] = $childNode;
            }
            foreach ( $node->children as $childNode ) {
                $this->addChildren($childNode,$parents2children);
            }
        }
    }

    private function createTreeNode ( \com\schrack\queue\protobuf\Message\Group &$group, $parentID ) {
        $fullId = $id = $group->getId();
        if ( ($p = strrpos($id,self::ID_SEPARATOR)) !== false ) {
            $id = substr($id,$p + 1);
        }
        if ( $parentID > '' ) {
            $fullId = $parentID . self::ID_SEPARATOR . $id;
        }
        if ( isset($this->_fullIdToNewProtoGroupMap[$fullId]) ) {
            if ( $group->getTitle() != $this->_fullIdToNewProtoGroupMap[$fullId]->getTitle() ) {
                self::log("ERROR: Different groups with same full ID occured! Full ID: $fullId");
                die();
            }
            // self::log("Duplicate group data got: $fullId");
            return;
        }
        $node = new stdClass();
        $node->children = array();
        $node->id = $id;
        $node->fullId = $fullId;
        $hash = spl_object_hash($group);
        if ( isset($this->_alreadyUsedGroupHashes[$hash]) ) {
            $node->group = clone $group;
        } else {
            $node->group = $group;
            $this->_alreadyUsedGroupHashes[$hash] = true;
        }
        if ( $parentID ) {
            $node->group->setParent($parentID);
            $node->group->setId($fullId);
        }
        $this->_fullIdToNewProtoGroupMap[$fullId] = $node->group;
        return $node;
    }

    protected function doHandle ( Message &$importMsg )
    {
        if ( count($importMsg->getGroupsList()) < 1 ) {
            return;
        }
        $this->_importMsg = $importMsg;

        $this->buildGroupMapWithTemporaryTreeFromImportMessage();

        self::log('loading old ID map...');
        $this->loadCategoryMap();
        self::log('...loading old ID map');

        self::log('building diffs...');
        $fullIDs2delete = array();
        $fullIDs2insert = array();
        $fullIDs2update = array();
        foreach ( $this->_fullIdToNewProtoGroupMap as $id => $node ) {
            if ( isset($this->_loadedCategoryIdMap[$id]) ) {
                $fullIDs2update[] = $id;
            } else {
                $fullIDs2insert[] = $id;
            }
        }
        foreach ( $this->_loadedCategoryIdMap as $id => $cat ) {
            if ( ! isset($this->_fullIdToNewProtoGroupMap[$id]) ) {
                $fullIDs2delete[] = $id;
            }
        }
        self::log('...building diffs');
        $cntIns = count($fullIDs2insert);
        $cntUpd = count($fullIDs2update);
        $cntDel = count($fullIDs2delete);
        $cntAllNew = count($this->_importMsg->getGroupsList());
        $cntAllOld = count($this->_loadedCategoryIdMap);
        self::log("DIFFS: insert: $cntIns, update: $cntUpd, delete: $cntDel, all new: $cntAllNew, all old: $cntAllOld");

        if ( $cntDel > 0 ) {
            self::log('deleting not longer used groups...');
            $sql = "DELETE FROM catalog_category_entity WHERE entity_id IN"
                . "    (SELECT entity_id FROM catalog_category_entity_varchar"
                . "     WHERE attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'schrack_group_id')"
                . "     AND value IN ('" . implode("','", $fullIDs2delete) . "'))";
            $this->_writeConnection->query($sql);
            self::log('...deleting not longer used groups');
        }

        if ( $cntIns > 0 ) {
            self::log('inserting new groups...');
            sort($fullIDs2insert);
            foreach ( $fullIDs2insert as $fullID ) {
                $protoGroup = $this->_fullIdToNewProtoGroupMap[$fullID];
                $this->insertGroup($protoGroup);
            }
            self::log('...inserting new groups');
        }

        if ( $cntUpd > 0 ) {
            self::log('updating existing groups...');
            foreach ( $fullIDs2update as $fullID ) {
                $protoGroup = $this->_fullIdToNewProtoGroupMap[$fullID];
                $this->updateGroup($protoGroup);
            }
            self::log('...updating existing groups');
        }
        
        $this->removeLayouts();
        unset($this->_importMsg);
    }

    private function insertGroup ( com\schrack\queue\protobuf\Message\Group &$inGroup ) {
        self::logProgressChar('i');
        self::logDebug("INSERT: " . $inGroup->getId() . " - " . $inGroup->getTitle());
        $category = clone $this->_categoryModelTemplate;
        $this->setAndSaveGroup($inGroup, $category);
        $this->_loadedCategoryIdMap[$inGroup->getId()] = [ 'entity_id' => $category->getId(), 'name' => $category->getName() ];
    }

    private function updateGroup ( com\schrack\queue\protobuf\Message\Group &$inGroup ) {
        $schrackID = $inGroup->getId();
        $magentoID = $this->getMagentoId($schrackID);
        $msgKey = 'impGroup=' . $schrackID;
        if ( $this->_originTimestamp ) {
            $isLast = Mage::helper("schrack/mq")->isLatestUpdate($msgKey, $this->_originTimestamp);
            if ( !$isLast ) {
                self::logDebug('skipping group ' . $schrackID . ' because message ts too old');
                return;
            }
        }
        self::logDebug("UPDATE: " . $inGroup->getId() . " - " . $inGroup->getTitle());
        self::logDebug('magento id = ' . $magentoID);
        unset($this->_unhandledSchrack2MagentoIdMap[$schrackID]);
        $category = clone $this->_categoryModelTemplate;
        $category->load($magentoID);
        self::logProgressChar('u');
        $this->setAndSaveGroup($inGroup, $category);
    }

    private function setAndSaveGroup ( com\schrack\queue\protobuf\Message\Group &$inGroup, Schracklive_SchrackCatalog_Model_Category &$category ) {
        $schrackID = $inGroup->getId();
        $magentoID = $category->getId();
        $msgKey = 'impGroup=' . $schrackID;

        $category->disableCache();

        $this->handleBaseData($inGroup, $category, $schrackID);
        $this->handleFacets($inGroup, $category);
        $attachments = $this->handleMediadata($inGroup, $category);

        // finally save and remember id:
        $res = $category->save();
        self::logDebug('group ' . $res->getSchrackGroupId() . ' saved with entity_id ' . $res->getId());
        foreach ( $attachments as $rec ) {
            $rec->setEntityId($category->getId());
            $rec->setEntityTypeId($category->getEntityTypeId());
            $rec->save();
        }

        if ( $this->_originTimestamp ) {
            Mage::helper("schrack/mq")->saveLatestUpdate($msgKey, $this->_originTimestamp);
        } else {
            Mage::helper("schrack/mq")->removeTimestamp($msgKey);
        }
        if ( ! $magentoID ) {
            $magentoID = $res['entity_id'];
            $this->_allSchrack2MagentoIdMap[$schrackID] = $magentoID;
            self::$_staticIdMap[$schrackID] = $magentoID;
        }
        $this->_categoryId2pathMap[$magentoID] = $category->getPath();
    }

    private function handleBaseData ( com\schrack\queue\protobuf\Message\Group &$inGroup, Schracklive_SchrackCatalog_Model_Category &$category, $schrackID )
    {
        $asciiUrlString = $this->mkAsciiUrlString($inGroup->getTitle());
        $category->setUrlKey($asciiUrlString);

        $oldName = $this->getLoadedCategoryNameForScharckId($schrackID);
        if ( $oldName !== false && $oldName != $inGroup->getTitle() ) {
            $this->addRedirectRename($oldName,$inGroup->getTitle(),$schrackID,null);
        }

        $category->setName($inGroup->getTitle());
        $category->setSchrackAddText($inGroup->getAddtext());
        $category->setDescription($inGroup->getDescription());
        $category->setMetaKeywords($inGroup->getKeywords());
        $category->setIsActive(1);
        $parentInId = self::prepareIncommingID($inGroup->getParent());
        $parentMagentoId = 2;
        if ( $parentInId ) {
            $parentMagentoId = $this->getMagentoId($parentInId);
        }
        $category->setParentId($parentMagentoId);
        $category->setStatus(1);
        $category->setStoreId(self::DEFAULT_STORE_ID);
        $category->setSchrackGroupId($schrackID);
        $pillar = $inGroup->getStrategicpillar() === null ? "" : $inGroup->getStrategicpillar();
        if ( strpos($pillar, "Säule") === 0 ) {
            $pillar = substr($pillar, 6);
        }
        $pillar = trim($pillar);
        $category->setSchrackStrategicPillar($pillar);

        $category->setPosition($inGroup->getOrdernumber());

        if ( $parentMagentoId == 2 ) {
            $category->setDisplayMode('PRODUCTS');
            $category->setLandingPage('');
            $category->setPageLayout('three_columns');
        }

        $parentPath = $this->getParentPath($parentMagentoId); // $this->_categoryId2pathMap[$parentMagentoId]; // !!!!!!!
        if ( $category->getId() ) {
            $path = $parentPath . "/" . $category->getId();
        } else {
            $path = $parentPath;
        }
        $category->setPath($path);
    }

    private function getParentPath ( $parentMagentoId )
    {
        if ( ! $parentMagentoId ) {
            self::log("WARNING: no path for category with entity_id '$parentMagentoId' found. Using root instead.");
            $path = $this->_categoryId2pathMap[2];
            $this->_categoryId2pathMap[$parentMagentoId] = $path;
            return $path;
        }
        if ( !isset($this->_categoryId2pathMap[$parentMagentoId]) ) {
            $sql = "SELECT path FROM catalog_category_entity WHERE entity_id = $parentMagentoId";
            $path = $this->_readConnection->fetchOne($sql);
            if ( !isset($path) || strlen($path) < 1 ) {
                self::log("WARNING: no path for category with entity_id '$parentMagentoId' found. Using root instead.");
                $path = $this->_categoryId2pathMap[2];
            }
            $this->_categoryId2pathMap[$parentMagentoId] = $path;
        }
        return $this->_categoryId2pathMap[$parentMagentoId];
    }

    private function handleFacets ( com\schrack\queue\protobuf\Message\Group &$inGroup, Schracklive_SchrackCatalog_Model_Category &$category )
    {
        $showAttrNames = array();
        $searchAttrNames = array();
        $facetRefs = $inGroup->getFacetreferencesList();

        /* @var facetRef com\schrack\queue\protobuf\Message\FacetReferenceGroup */
        foreach ( $facetRefs as $facetRef ) {
            $facets = $facetRef->getFacetList();
            if ( count($facets) < 1 ) {
                continue;
            }
            $facetId = $facetRef->getId();
            $attrnames = array();
            /* @var $facet com\schrack\queue\protobuf\Message\FacetGroupDefinition */
            foreach ( $facets as $facet ) {
                $name = $facet->getId();
                $name = $this->name2newCode($name);
                $purpose = $facet->getPurpose();
                switch ( $purpose ) {
                    case self::FACET_SHOW :
                        $showAttrNames[] = $name;
                        break;
                    case self::FACET_SEARCH :
                        $searchAttrNames[] = $name;
                        break;
                    case self::FACET_BOTH :
                        $showAttrNames[] = $searchAttrNames[] = $name;
                        break;
                }
            }
        }

        $facetString = implode(',', $searchAttrNames);
        $category->setSchrackFacetList($facetString);
        $facetString = implode(',', $showAttrNames);
        $category->setSchrackPropertyList($facetString);
    }

    private function handleMediadata ( com\schrack\queue\protobuf\Message\Group &$inGroup, Schracklive_SchrackCatalog_Model_Category &$category )
    {
        $mediaDatas = $inGroup->getMediadataList();

        $res = array();
        if ( $category->getId() ) {
            $category->cleanAttachments();
        }
        $category->setSchrackImageUrl(null);
        $category->setSchrackThumbnailUrl(null);

        $foto = false;
        $thumb = false;
        foreach ( $mediaDatas as $mediaData ) {
            /* @var mediaData com\schrack\queue\protobuf\Message\MediaData */
            $mediaDataType = $mediaData->getType();
            $done = false;
            $url = $mediaData->getUrl();
            if ( $mediaDataType === self::MEDIATYPE_FOTO && !$foto ) {
                $category->setSchrackImageUrl($url);
                $done = $foto = true;
            } else if ( self::isMediatypeThumbnail($mediaDataType) && !$thumb ) {
                $category->setSchrackThumbnailUrl($url);
                $done = $thumb = true;
            }
            if ( !$done ) {
                $attachment = Mage::getModel('schrackcatalog/attachment');
                $attachment->setFiletype($mediaDataType);
                $attachment->setUrl($url);
                $mediaDescription = $mediaData->getDescription();
                $attachment->setLabel($mediaDescription);
                self::logDebug('adding attachment: type = ' . $attachment->getFiletype() . ', label = ' . $attachment->getLabel() . ', url = ' . $attachment->getUrl());
                $res[] = $attachment;
            }
        }
        return $res;
    }

    public static function sortIDs ( &$inIdToGroupMap )
    {
        $tree = array();
        /* @var $group com\schrack\queue\protobuf\Message\Group */
        foreach ( $inIdToGroupMap as $id => $group ) {
            self::addGroupIds($tree, $group, $inIdToGroupMap);
        }
        $res = array();
        self::walkIdNode($res, $tree);
        return $res;
    }

    private static function walkIdNode ( &$targetArray, &$tree )
    {
        foreach ( $tree as $id => $node ) {
            $targetArray[] = (string)$id;
            self::walkIdNode($targetArray, $node);
        }
    }

    private static function &addGroupIds ( &$root, &$group, &$inIdToGroupMap )
    {
        $node2insert = null;
        $parentId = self::prepareIncommingID($group->getParent());
        if ( $parentId ) {
            if ( isset($inIdToGroupMap[$parentId]) ) { // otherwise parent group will not be updated and must already exist
                $parentGroup = $inIdToGroupMap[$parentId];
                $node2insert =& self::addGroupIds($root, $parentGroup, $inIdToGroupMap);
            } else {
                $node2insert =& $root;
            }
        } else {
            $node2insert =& $root;
        }
        $id = self::prepareIncommingID($group->getId());
        if ( !isset($node2insert[$id]) ) {
            $node2insert[$id] = array();
        }
        return $node2insert[$id];
    }

    public function getSchrack2MagentoIdMap ()
    {
        /*
        if ( true || !self::$_staticIdMap ) {
            self::log('building now category id map...');
            $category = Mage::getModel('catalog/category');
            $collection = $category->getCollection();
            // $collection->addFieldToFilter('is_active',1);
            $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns('entity_id');
            $collection->addAttributeToSelect('is_active')
                ->addAttributeToSelect('schrack_group_id');

            self::$_staticIdMap = array();
            foreach ( $collection as $cat ) {
                $schrackId = $cat->getSchrackGroupId();
                $magentoId = $cat->getId();
                self::$_staticIdMap[$schrackId] = $magentoId;
            }
            self::log('...category id map done');
        }
        */
        self::$_staticIdMap = array();
        return self::$_staticIdMap;
    }

    protected function delete ( $magentoID )
    {
        $category = Mage::getModel('catalog/category');
        $category->load($magentoID);
        if ( $category->getLevel() < 2 ) {
            return; // we may not delete the hidden top level categories!
        }

        // delete sub categories first:
        $sql = "SELECT entity_id FROM catalog_category_entity WHERE parent_id = ?";
        $col = $this->_readConnection->fetchCol($sql,$magentoID);
        foreach ( $col as $childMagentoID ) {
            $this->delete($childMagentoID);
        }

        $schrackID = $category->getSchrackGroupId();
        unset($this->_unhandledSchrack2MagentoIdMap[$schrackID]);
        $category->delete();
        self::logProgressChar('d');
    }

    private function getMagentoId ( $schrackID )
    {
        return $this->_loadedCategoryIdMap[$schrackID]['entity_id'];
    }

    private function removeLayouts () {
        // don't know where they came from, I think they must be added manually...
        $sql = "DELETE FROM catalog_category_entity_varchar WHERE attribute_id = (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = (SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'catalog_category') AND attribute_code = 'page_layout');";
        $this->_writeConnection->query($sql);
    }

    private function findExistingLongIDs ( $stsID ) {
        $delim = strpos($stsID,'/') !== false ? "#" : "/#";
        $res = array();
        $sql = " SELECT value from catalog_category_entity_varchar"
             . " WHERE attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id')"
             . " AND value LIKE '%" . $stsID . "%';";
        $col = $this->_readConnection->fetchCol($sql);
        foreach ( $col as $val ) {
            $tok = strtok($val,$delim);
            while ( $tok !== false ) {
                if ( $tok == $stsID ) {
                    $res[] = $val;
                    break;
                }
                $tok = strtok($delim);
            }
        }
        return $res;
    }
}

?>

