<?php

use com\schrack\queue\protobuf\Message;

/**
 * @author d.laslov
 */
class Schracklive_SchrackCatalog_Model_Protoimport_Dumper {

    const TXT = 'txt';
    const ORDER = 'order';
    const CHILDREN = 'children';
    const PARENT_NOT_AVAILABLE = 'PARENT_NOT_AVAILABLE';
    
    var $_groupsTree;
    
    function dump ( &$binData ) {
        $msg = new Message($binData);
        $binData = null;
        $codec = new \DrSlump\Protobuf\Codec\TextFormat();
        $textData = $codec->encode($msg);
        print($textData.PHP_EOL);
    }
    
    
    function dumpStructure ( &$importMsg ) {
        $inIdToGroupMap = array();
        
        /* @var $group \com\schrack\queue\protobuf\Message\Group */
        foreach ( $importMsg->getGroupsList() as $group ) {
            $id = $group->getId();
            $inIdToGroupMap[$id] = $group;
        }
        $sortedGroupIDs = Schracklive_SchrackCatalog_Model_Protoimport_GroupsHandler::sortIDs($inIdToGroupMap);
        
        $this->_groupsTree = array();
        foreach ( $sortedGroupIDs as $groupId ) {
            $group = $inIdToGroupMap[$groupId];
            $this->addGroup($group);
        }
        /*
        foreach ( $importMsg->getArticles() as $protoArticle ) {
            $this->addArticle($protoArticle);
        }
         */
        foreach ( $importMsg->getArticlegrouprefsList() as $ref ) {
            $this->addRef($ref);
        }
        $this->printTree($this->_groupsTree);
    }
    
    function addGroup ( $group ) {
        $data = array();
        $id = $group->getId();
        $delPrefix = $group->getAction() == 'delete' ? '-' : '';
        $data[self::TXT] = $delPrefix . 'P:' . $group->getStrategicpillar() . ' G:' . $group->getId() . ' N:"' . $group->getTitle() . '" O:' . '' . $group->getOrdernumber();
        $ord = $group->getOrdernumber();
        $data[self::ORDER] = $ord;
        $data[self::CHILDREN] = array();
        $parentId = $group->getParent();
        if ( $parentId == null || strlen($parentId) < 1 ) {
            $this->_groupsTree[$id] = $data;
        } else {
            $this->addData2parentInTree($parentId,$id,$data);
        }
    }
    
    function addRef ( $ref ) {
        $data = array();
        $parentId = $ref->getGroup();
        $id = $ref->getArticle();
        $delPrefix = $ref->getAction() == 'delete' ? '-' : '';
        $data[self::TXT] = $delPrefix . 'SKU:' . $id . ' O:' . $ref->getOrdernumber() . ' P:' . $parentId;
        $ord = $ref->getOrdernumber();
        $data[self::ORDER] = $ord;
        $this->addData2parentInTree($parentId,$id,$data);
    }
    
    function addData2parentInTree ( $parentId, $id, $data ) {
        $res = $this->addData2parentInTree2($this->_groupsTree,$parentId,$id,$data);
        if ( ! $res ) {
            $naKey = self::PARENT_NOT_AVAILABLE . ' ' . $parentId;
            if ( ! isset($this->_groupsTree[$naKey]) ) {
                $this->_groupsTree[$naKey] = array(
                    self::TXT      => $naKey,
                    self::CHILDREN => array(),
                    self::ORDER    => $parentId
                );
            }
            $this->_groupsTree[$naKey][self::CHILDREN][] = $data;
        }
    }
    
    function addData2parentInTree2 ( &$tree, $parentId, $id, $data ) {
        foreach ( $tree as $key => &$val ) {
            if ( (string) $key === (string) $parentId ) {
                $val[self::CHILDREN][$id] = $data;
                return true;
            }
        }
        foreach ( $tree as $key => &$val ) {
            if ( isset($val[self::CHILDREN]) ) {
                $done = $this->addData2parentInTree2($val[self::CHILDREN],$parentId,$id,$data);
                if ( $done ) {
                    return true;
                }
            }
        }
        return false;
    }
    
    function printTree ( &$tree, $indent = '' ) {
        usort($tree,'usortCallback');
        foreach ( $tree as $val ) {
            echo $indent . $val[self::TXT] . PHP_EOL;
            if ( isset($val[self::CHILDREN]) ) {
                $this->printTree($val[self::CHILDREN], $indent . '    ');
            }
        }
    }

    
}

function usortCallback ( $a, $b ) {
    return intval($a[Schracklive_SchrackCatalog_Model_Protoimport_Dumper::ORDER]) - intval($b[Schracklive_SchrackCatalog_Model_Protoimport_Dumper::ORDER]);
}

?>
