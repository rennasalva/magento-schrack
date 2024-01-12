<?php

// SolrSalesEntity

class Schracklive_SchrackSales_Model_SolrSalesEntity extends Varien_Object {

    protected function getStaticValueMap () {
        return array();
    }

    protected function getSolrAttributeMap () {
        return array();
    }

    protected function createAdditionalAttributes ( $solrJsonData ) {
    }

    protected function transformAttributeValue ( $solrName, $solrVal ) {
        switch ( $solrName ) {
            case 'Date'             : return date("d.m.Y",strtotime($solrVal));
            case 'ColloNumbers'     : return $solrVal[0];
        }
        return $solrVal;
    }

    protected function hasRealData () {
        $map = $this->getSolrAttributeMap();
        foreach ( $this->getData() as $k => $V ) {
            if ( isset($map[$k]) ) {
                return true;
            }
        }
        return false;
    }

    protected static function addAttributes ( $subClassAttributes ) {
        return self::$solr2orderMap;
    }

    public static function createAndMapJson2varienObject ( &$resultArray, $jsonObject ) {
        switch ( $jsonObject->DocumentType ) {
            case "Order"                :
                $res = new Schracklive_SchrackSales_Model_SolrSalesOrder();
                break;

            case "Shipment"             :
            case "Invoice"              :
            case "Creditmemo"           :
                $res = new Schracklive_SchrackSales_Model_SolrSalesContainerEntity();
                break;

            case "OrderStatusPosition"  :
            case "ShipmentPosition"     :
            case "CreditmemoPosition"   :
            case "InvoicePosition"      :
                $res = new Schracklive_SchrackSales_Model_SolrSalesPosition();
                $res->setIsSubPosition(false);
                break;
            case "PartsItem"            :
            case "PriceItem"            :
                $res = new Schracklive_SchrackSales_Model_SolrSalesPosition();
                $res->setIsSubPosition(true);
                break;

            case "Reference"            :
            case "Text"                 :
                return new Varien_Object();

            default :
                return new Varien_Object();
        }

        foreach ( $res->getStaticValueMap() as $k => $v ) {
            $res->setData($k, $v);
        }

        $order2solrMap = $res->getSolrAttributeMap();
        $jsonAttrs = get_object_vars($jsonObject);
        $allAttrs = $jsonAttrs;
        foreach ( $jsonAttrs as $k => $v ) {
            $v = $res->transformAttributeValue($k,$v);
            if ( isset($order2solrMap[$k]) ) {
                foreach ( $order2solrMap[$k] as $k2 ) {
                    $allAttrs[$k2] = $v;
                }
            }
        }
        // TODO: remove the solr attributes later on
        $res->addData($allAttrs);

        $res->createAdditionalAttributes($jsonObject);

        if ( $res->getIsSubPosition() ) {
            $id = $res->getId();
            $middlePart = explode('.',$id)[1];
            $parentPosNumber = (int) explode('_',$middlePart)[1];
            $oldPosNumber = (int) $res->getData('PositionNumber');
            $newPosNumber = ($parentPosNumber * 1000) + $oldPosNumber;
            $res->setData('PositionNumber',$newPosNumber);
        }

        if ( $res->hasRealData() ) {
            $resultArray[$jsonObject->id] = $res;
            return $res;
        }
        return false;
    }

    public static function createSolrSalesEntityResult ( array $solrResult ) {
        $recordResult   = array();
        $positions      = array();
        $items          = array();
        $references     = array();
        $texts          = array();

        foreach ( $solrResult as $parent ) {
            $docType = $parent->DocumentType;
            switch ( $docType ) {
                case 'Order'        : self::createAndMapJson2varienObject($recordResult,$parent); break;
                case 'Shipment'     : self::createAndMapJson2varienObject($recordResult,$parent); break;
                case 'Invoice'      : self::createAndMapJson2varienObject($recordResult,$parent); break;
                case 'Creditmemo'   : self::createAndMapJson2varienObject($recordResult,$parent); break;
            }
            $childDocuments = self::getChildDocuments($parent);
            if ( is_array($childDocuments) ) {
                foreach ( $childDocuments as $child ) {
                    $docType = $child->DocumentType;
                    switch ( $docType ) {
                        case 'OrderStatusPosition'  : self::createAndMapJson2varienObject($positions, $child); break;
                        case 'ShipmentPosition'     : self::createAndMapJson2varienObject($positions, $child); break;
                        case 'InvoicePosition'      : self::createAndMapJson2varienObject($positions, $child); break;
                        case 'CreditmemoPosition'   : self::createAndMapJson2varienObject($positions, $child); break;

                        case 'PartsItem'            : self::createAndMapJson2varienObject($items, $child); break;
                        case 'PriceItem'            : self::createAndMapJson2varienObject($items, $child); break;

                        // ignore case 'Reference'            : $references[$child->id] = $child; break;
                        // ignore case 'Text'                 : $texts[$child->id]      = $child; break;
                    }
                }
            }
        }
        $parent = null;

        foreach ( $items as $id => $item ) {
            $parentId = self::getParentId($id);
            $ndx = self::getIndexNo($id);
            if ( ! isset($positions[$parentId]) || ! is_object($positions[$parentId]) ) {
                continue;
            }
            $parent = $positions[$parentId];
            $itemArray = $parent->getAllItems();
            if ( ! $itemArray ) {
                $itemArray = array();
            }
            $itemArray[$ndx] = $item;
            $parent->setAllItems($itemArray);
        }

        foreach ( $positions as $id => $position ) {
            $parentId = self::getParentId($id);
            $ndx = self::getIndexNo($id);
            if ( ! isset($recordResult[$parentId]) ) {
                continue; // skip the wrong children
            }
            if ( $position->getData('AdditionalOfferPosition') || $position->getData('Alternative') ) {
                continue; // skip alternative and additional positions
            }
            $parent = $recordResult[$parentId];
            if ( $itemArray = $position->getAllItems() ) {
                foreach ( $itemArray as $item ) {
                    $parent->addItem($item);
                }
            }
            $position->setAllItems();
            $parent->addItem($position);
        }

        return $recordResult;
    }

    private static function getParentId ( $id ) {
        $p = strrpos($id,'.');
        return substr($id,0,$p);
    }

    private static function getIndexNo ( $id ) {
        $p = strrpos($id,'_');
        return intval(substr($id,$p + 1));
    }

    private static function getChildDocuments ( $parent ) {
        $possibleNames = [ 'OrderStatusPosition', 'ShipmentPosition', 'InvoicePosition', 'CreditmemoPosition', '_childDocuments_' ];
        foreach ( $possibleNames as $name ) {
            if ( isset($parent->$name) ) {
                if ( is_array($parent->$name) ) {
                    return $parent->$name;
                } else if ( is_object($parent->$name) ) { // bloody solr returns now single children without array...
                    return [ $parent->$name ];
                }
            }
        }
        return false;
    }

}
