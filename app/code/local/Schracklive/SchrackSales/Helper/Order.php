<?php


class Schracklive_SchrackSales_Helper_Order {

    const DOCTYPE_OFFER       = 1;
    const DOCTYPE_ORDER       = 2;
    const DOCTYPE_SHIPMENT    = 3;
    const DOCTYPE_INVOICE     = 4;
    const DOCTYPE_CREDIT_MEMO = 5;

    const DOCTYPE_INT_2_SOLR_DOC_NO = array( 1 => 'OfferNumber', 2 => 'OrderNumber', 3 => 'ShipmentNumber', 4 => 'InvoiceNumber', 5 => 'CreditmemoNumber' );

    const VIRTUAL_SKUS = array('TRANSPORT-','MANIPULAT-','VERPACKUNG', 'HARBEIT011');

    const LAST_ORDER_LIMIT = 1000;

    private $_customer = null;
    private $_client   = null;
    private $_documentId2wwsDocNumberMap = array();
    private $_documentId2wwsFollowupOrderNumberMap = array();
    private $_queryCache = array();
    private $_avoidOfferMap = null;

    public function getDocType ( $resultLine ) {
        $data = $resultLine->getData();

        switch ( $data['DocumentType'] ) {
            case 'Shipment' : return self::DOCTYPE_SHIPMENT; break;
            case 'Invoice' : return self::DOCTYPE_INVOICE; break;
            case 'Creditmemo' : return self::DOCTYPE_CREDIT_MEMO; break;
            case 'Order' :
                if ( isset($data['order_as_offer']) && $data['order_as_offer'] == 1 ) {
                    return self::DOCTYPE_OFFER;
                }
                break;
        }
        return self::DOCTYPE_ORDER;
    }

    public function getDocumentCustomerID ( $ref, $docType ) {
        $customerID = $this->_getCustomerId();
        $keyPrefix = "FULLDOC";
        $key =  "$keyPrefix-$customerID-$ref-$docType";
        if ( isset($this->_queryCache[$key]) && isset($this->_queryCache[$key]['solr']) ) {
            $doc = $this->_queryCache[$key]['solr'];
            return $doc->getData('CustomerNumber');
        } else {
            $res = $this->getDocumentImpl($ref,null,$docType,"CUSTID","CustomerNumber",false);
            if ( count($res->response->docs) < 1 ) {
                throw new Exception('wrong result' . ' (error: #345257)');
            }
            return $res->response->docs[0]->CustomerNumber;
        }
    }

    public function getFullOrder ( $ref ) {
        return $this->getFullDocument($ref,"Order");
    }

    public function getFullDocument ( $ref, $docType, $orderRef = null ) {
        return $this->getDocumentImpl($ref,$orderRef,$docType,"FULLDOC","*,[child of=DocumentType:<<DocType>> limit=100000]");
    }

    public function getOrderNumberForDocNumber ($docNumber, $docType ) {
        $docType = ucwords($docType);
        $realDocType =  $docType == "Offer" ? "Order" : $docType;
        $customerID = $this->_getCustomerId();
        if ( $customerID == null || intval($customerID) == 0 ) {
            $customerIDqry = '';
        } else {
            $customerIDqry = "+CustomerNumber:$customerID AND ";
        }
        $docNumberName = $docType . 'Number';
        $solrQueryArray = array(
            "q" => $customerIDqry . "+DocumentType:$realDocType AND +$docNumberName:$docNumber",
            "fl" => 'OrderNumber',
            "rows" => 1
        );
        $solrResult = $this->performSolrSearch($solrQueryArray);
        if ( isset($solrResult) && isset($solrResult->response) && $solrResult->response->numFound == 1 ) {
            return $solrResult->response->docs[0]->OrderNumber;
        } else {
            return false;
        }
    }

    private function getDocumentImpl ( $ref, $orderRef, $docType, $keyPrefix, $fieldList, $returnVarienObjects = true ) {
        $asOrderOffer = false;
        if ( $docType == "Offer" ) {
            $docType = "Order";
            $asOrderOffer = true;
        }
        $docType = ucwords($docType);
        $customerID = $this->_getCustomerId();
        $key =  "$keyPrefix-$customerID-$ref-$docType";
        if ( ! isset($this->_queryCache[$key]) || ! isset($this->_queryCache[$key]['solr']) ) {
            if ( !isset($this->_queryCache[$key]) ) {
                $this->_queryCache[$key] = array();
            }
            $this->_queryCache[$key]['solr'] = null;
            $docNumberName = $docType . 'Number';
            $fieldList = str_replace("<<DocType>>", $docType, $fieldList);
            $mainQuery = "+CustomerNumber:$customerID";
            //---------------------------------- get previous customer id if set
            // TODO: extend 'Umfirmierung' for multiple entries with
            //       specialchar seperated string (e.g.: ';')
            //------------------------------------------------------------------
            $account = Mage::getModel('account/account')->loadByWwsCustomerId($customerID);
            //------------------------------------------------------------------
            if ($account->getWwsCustomerIdHistory().'.' != '.') {
                $mainQuery = '+CustomerNumber:(' . $customerID . ' OR ' . $account->getWwsCustomerIdHistory() . ')';
            }
            //------------------------------------------------------------------
            $solrQueryArray = array(
                "q" => $mainQuery ." AND +DocumentType:$docType AND +$docNumberName:$ref",
                "fl" => $fieldList,
                "rows" => 1
            );

            if ( $orderRef ) {
                $solrQueryArray['q'] .= " AND +OrderNumber:$orderRef";
            }

            $solrResult = $this->performSolrSearch($solrQueryArray);
            if ( ! $returnVarienObjects ) {
                $this->_queryCache[$key]['solr'] = $solrResult;
            } else {
                $resArray = $this->prepareSolrResult($solrResult->response->docs);
                $this->removeOrderedOffers($resArray, $customerID);
                if ( count($resArray) !== 1 ) {
                    Mage::log('wrong count of search result for detail view got (B): ' . count($resArray));
                    throw new Exception('wrong result (error: #34515B)');
                }
                $doc = reset($resArray);
                if ( $asOrderOffer ) {
                    $doc->setData('order_as_offer', 1);
                }
                $this->_queryCache[$key]['solr'] = $doc;
            }
        }
        return $this->_queryCache[$key]['solr'];
    }

    public function getRelatedDocumentsForOrder ( $orderNumber ) {
        $customerID = $this->_getCustomerId();
        $key =  "RELATED-$customerID-$orderNumber";
        if ( ! isset($this->_queryCache[$key]) || ! isset($this->_queryCache[$key]['solr']) ) {
            if ( !isset($this->_queryCache[$key]) ) {
                $this->_queryCache[$key] = array();
            }
            $this->_queryCache[$key]['solr'] = null;

            $solrQueryArray = array(
                "q" => "+CustomerNumber:$customerID AND +DocumentType:(Shipment Invoice Creditmemo) AND +OrderNumber:$orderNumber",
                "fl" => "*",
                "rows" => 1000
            );

            $solrResult = $this->performSolrSearch($solrQueryArray);
            $resArray = $this->prepareSolrResult($solrResult->response->docs);
            $this->removeOrderedOffers($resArray,$customerID);
            $groupedRes = array( "Shipment" => array(), "Invoice" => array(), "Creditmemo" => array() );
            foreach ( $resArray as $row ) {
                $groupedRes[$row->getDocumentType()][] = $row;
            }
            $this->_queryCache[$key]['solr'] = $groupedRes;
        }
        return $this->_queryCache[$key]['solr'];
    }

    public function getBackorderPositions ( $sortField = false, $sortASC = false, $text = null,
                                            $orderedFrom = null, $orderedTo = null, $expectedFrom = null, $expectedTo = null, $getAll = false ) {
        $showProjectInfo = intval(Mage::getStoreConfigFlag('schrack/shop/enable_custom_project_info_in_checkout')) == 1;
        $customerID = $this->_getCustomerId();
        $orderQuery = $getAll ? "DocumentType:Order" : "+CustomerNumber:$customerID AND DocumentType:Order";
        $positionQuery = '+BackorderQuantity:[1 TO *]';
        if ( $orderedFrom && $orderedTo ) {
            $orderQuery .= " AND Date:[{$orderedFrom}T00:00:00Z TO {$orderedTo}T00:00:00Z]";
        }
        if ( $expectedFrom && $expectedTo ) {
            $expectedFromYear = substr($expectedFrom,0,4);
            $expectedToYear = substr($expectedTo,0,4);
            $expectedFromCalWeek = sprintf('%02d',idate('W', strtotime($expectedFrom)));
            $expectedToCalWeek = sprintf('%02d',idate('W', strtotime($expectedTo)));
            $positionQuery .= " AND ExpectedDeliveryYear:[$expectedFromYear TO $expectedToYear] AND ExpectedDeliveryWeek:[$expectedFromCalWeek TO $expectedToCalWeek]";
        }
        $solrQueryArray = array(
            'q'     => $orderQuery . ' AND {!parent which=\'-_nest_path_:* *:*\' v=$posQ}',
            'posQ'  => $positionQuery,
            'fl'    => '*,[child of=DocumentType:Order childFilter=DocumentType:OrderStatusPosition limit=100000]',
            'rows'  => '10000'
        );

        $solrResult = $this->performSolrSearch($solrQueryArray);
        $orderArray = $this->prepareSolrResult($solrResult->response->docs);
        $positionArray = array();
        $skus = array();
        // collect backorder positions:
        foreach ( $orderArray as $order ) {
            $cstomer = $order->getData('CustomerNumber');
            $orderNum = $order->getSchrackWwsOrderNumber();
            $orderDate = $order->getSchrackWwsCreationDate();
            $schrackWwsReference = $order->getSchrackWwsReference();
            if ( $showProjectInfo ) {
                if ( is_null($schrackWwsReference) || $schrackWwsReference <= ' ' ) {
                    $schrackWwsReference = '-';
                }
                $schrackCustomerProjectInfo = $order->getSchrackCustomerProjectInfo();
                if ( is_null($schrackCustomerProjectInfo) || $schrackCustomerProjectInfo <= ' ' ) {
                    $schrackCustomerProjectInfo = '-';
                }
                $schrackWwsReference = $schrackWwsReference . '/' . $schrackCustomerProjectInfo;
            }
            foreach ( $order->getAllItems() as $item ) {
                if (    $text && $text > ''
                    && ! (    stripos($item->getSku(),$text) !== false
                        || stripos($item->getDescription(),$text) !== false
                        || stripos($schrackWwsReference,$text) !== false
                        || stripos($orderNum,$text) !== false                   ) ) {
                    continue;
                }
                if ( intval($item->getData('BackorderQuantity')) == 0 ) {
                    continue;
                }
                if ( $expectedFrom && $expectedTo ) {
                    $week = intval($item->getData('ExpectedDeliveryWeek'));
                    $year = intval($item->getData('ExpectedDeliveryYear'));
                    if ( $week < $expectedFromCalWeek || $week > $expectedToCalWeek || $year < $expectedFromYear || $year > $expectedToYear ) {
                        continue;
                    }
                }
                $skus[] = $item->getSku();
                $item->setSchrackWwsOrderNumber($orderNum);
                $item->setSchrackWwsCreationDate($orderDate);
                $item->setDisplayReference($schrackWwsReference);
                // http://www.schrack.at.local/shop/customer/account/documentsDetailView/id/390110821/type/order/documentId/390110821/
                $item->setOrderUrl(Mage::getUrl('customer/account/documentsDetailView',array('id' => $orderNum, 'type' => 'order', 'documentId' => $orderNum)));
                if ( $getAll ) {
                    $item->setCustomerNumber($cstomer);
                }
                $positionArray[] = $item;
            }
        }
        // get link urls:
        if ( count($skus) > 0 ) {
            $sku2urlMap = array();
            $sql = " SELECT sku, schrack_sts_statuslocal, request_path FROM catalog_product_entity p"
                . " JOIN core_url_rewrite u ON (p.entity_id = u.product_id) "
                . " WHERE sku IN('" . implode("','",$skus) ."');";
            $dbRes = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);
            foreach ( $dbRes as $row ) {
                if (    in_array($row['sku'],array('TRANSPORT-','MANIPULAT-','VERPACKUNG'))
                    || in_array($row['schrack_sts_statuslocal'],array('tot','strategic_no','gesperrt','unsaleable')) ) {
                    continue;
                }
                $url = Mage::getUrl($row['request_path']);
                if ( strrpos($url,".html") === false ) {
                    continue;
                }
                $sku2urlMap[$row['sku']] = $url;
            }
            foreach ( $positionArray as $item ) {
                if ( isset($sku2urlMap[$item->getSku()]) ) {
                    $item->setProductUrl($sku2urlMap[$item->getSku()]);
                }
            }
        }
        // sorting:
        if ( count($positionArray) && $sortField ) {
            $this->sortField = $sortField;
            $this->sortASC = $sortASC;
            uasort($positionArray, function ( $a, $b ) {
                if ( $this->sortField == 'expected' ) {
                    if ( $a->getExpectedAt() == '' )
                        $a = 0;
                    else
                        $a = intval($a->getData('ExpectedDeliveryYear')) * 100 + intval($a->getData('ExpectedDeliveryWeek'));
                    if ( $b->getExpectedAt() == '' )
                        $b = 0;
                    else
                        $b = intval($b->getData('ExpectedDeliveryYear')) * 100 + intval($b->getData('ExpectedDeliveryWeek'));
                } else {
                    $a = $a->getData($this->sortField);
                    $b = $b->getData($this->sortField);
                    if ( $this->sortField == 'schrack_wws_creation_date' ) {
                        $a = strtotime($a);
                        $b = strtotime($b);
                    }
                }
                if ( $a == $b ) return 0;
                if ( $this->sortASC ) {
                    return ($a < $b) ? -1 : 1;
                } else {
                    return ($a > $b) ? -1 : 1;
                }
            });
        }
        return $positionArray;
    }

    public function getLastOrders ( $customer = null ) {
        return $this->_getLastOrdersForStatus('La2',$customer);
    }

    public function getLastOffers ( $customer = null ) {
        return $this->_getLastOrdersForStatus('La1',$customer);
    }

    public function getCountOpen ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return   $this->getCountOffers($searchParams,$customer)
            + $this->getCountOrders($searchParams,$customer);
    }

    public function getCountOffers ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return $this->_getCountForStatus('La1',$searchParams,$customer);
    }

    public function getCountOrders ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return $this->_getCountForStatus('La2',$searchParams,$customer);
    }

    public function getCountCommissioned ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return $this->_getCountForStatus('La3',$searchParams,$customer);
    }

    public function getCountDelivered ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return $this->_getCountForStatus('La4',$searchParams,$customer);
    }

    public function getCountInvoiced ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return $this->_getCountForStatus('La5',$searchParams,$customer);
    }

    public function getCountCredited ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return $this->_getCountForStatus('La8',$searchParams,$customer);
    }

    public function getCountOfferDocs ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return $this->_getCountForDocs("Offer",$searchParams,$customer);
    }

    public function getCountOrderDocs ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return $this->_getCountForDocs("Order",$searchParams,$customer);
    }

    public function getCountDeliveryDocs ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return $this->_getCountForDocs("Shipment",$searchParams,$customer);
    }

    public function getCountInvoiceDocs ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return $this->_getCountForDocs("Invoice",$searchParams,$customer);
    }

    public function getCountCreditMemoDocs ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        return $this->_getCountForDocs("Creditmemo",$searchParams,$customer);
    }

    public function createSearchParameters () {
        return new Schracklive_SchrackSales_Helper_Order_SearchParameters();
    }

    public function annotateOrderedOffers ( &$collection, $customerId ) {
        if ( ! $this->_avoidOfferMap ) {
            $this->getAvoidOfferMap($customerId);
        }
        foreach ( $collection as $item ) {
            $itemOrderNum = $item->getData('OrderNumber');
            if ( $item->getSchrackWwsStatus() == 'La1' && isset($this->_avoidOfferMap[$itemOrderNum]) ) {
                $item->setSchrackWwsStatus('La2');
            }
        }
    }

    public function searchSalesOrders ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams, Schracklive_SchrackCustomer_Model_Customer $customer = null, $limit = -1, $source = 'default' ) {
        $newRes = $this->searchSalesOrdersSolr($searchParams,$customer);
        return $newRes; // $this->searchSalesOrdersNew($searchParams, $customer, 1, $limit);
    }

    public function searchSalesOrdersNew ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams, Schracklive_SchrackCustomer_Model_Customer $customer = null, $page = 1, $pageSize = -1 ) {
        $newRes = $this->searchSalesOrdersSolr($searchParams,$customer,$page,$pageSize);
        return $newRes;
    }


    private function _cloneSearchParametersWithoutStatus ( Schracklive_SchrackSales_Helper_Order_SearchParameters $src = null) {
        if ($src === null)
            $dest = new Schracklive_SchrackSales_Helper_Order_SearchParameters();
        else
            $dest = clone $src;
        $dest->isOffered = $dest->isOrdered = $dest->isCommissioned = $dest->isDelivered = $dest->isInvoiced = $dest->isCredited = false;
        return $dest;
    }


    private function _prepareSearchParametersWithoutDocs ( Schracklive_SchrackSales_Helper_Order_SearchParameters $src = null ) {
        if ($src === null)
            $dest = new Schracklive_SchrackSales_Helper_Order_SearchParameters();
        else
            $dest = clone $src;
        $dest->getOfferDocs =  $dest->getOrderDocs = $dest->getDeliveryDocs = $dest->getInvoiceDocs = $dest->getCreditMemoDocs = false;
        return $dest;
    }


    private function _getCountForStatus ( $status, Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null ) {
        $res = $this->searchSalesOrdersSolrImpl($searchParams,$customer);
        if ( ! $res || ! isset($res['facets']) || ! isset($res['facets']['states']) || ! isset($res['facets']['states'][$status]) ) {
            return 0;
        }
        return $res['facets']['states'][$status];
    }

    public function getCountAll ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams = null, $customer = null, $suppressStatusOffered = false ) {
        $res = $this->searchSalesOrdersSolrImpl($searchParams,$customer);
        if ( ! $res || ! isset($res['facets']) ) {
            return 0;
        }
        $facets = $res['facets'];
        if ( $suppressStatusOffered && isset($facets['states']['La1']) ) {
            return $facets['all'] - $facets['states']['La1'];
        } else {
            return $facets['all'];
        }
    }

    private function _getCountForDocs ( $docType, Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams, $customer = null, $distinct = false ) {
        $res = $this->searchSalesOrdersSolrImpl($searchParams,$customer);
        if ( ! $res || ! isset($res['facets']) || ! isset($res['facets']['docs']) || ! isset($res['facets']['docs'][$docType]) ) {
            return 0;
        }
        return $res['facets']['docs'][$docType];
    }

    private function _getLastOrdersForStatus ( $status, $customer = null ) {
        /** @var Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams */
        $searchParams = $this->createSearchParameters();
        if ( $status === 'La1' ) {
            $searchParams->getOfferDocs = true;
            $searchParams->isOffered = true;
            $searchParams->isCommissioned = $searchParams->isCredited = $searchParams->isDelivered
                = $searchParams->isInvoiced = $searchParams->isOrdered = false;
        }
        else if ( $status === 'La2' ) {
            $searchParams->getOrderDocs = true;
        }
        $searchParams->sortColumnName = 'schrack_wws_creation_date';
        $searchParams->isSortAsc = false;
        $orderCollection = $this->searchSalesOrders($searchParams, $customer, self::LAST_ORDER_LIMIT, 'SchrackSales-Helper-Order.php #3');
        return $orderCollection;
    }

    private function _getCustomerId ( $customer = null ) {
        /*
                if ( $overrideCustomerId = Mage::getStoreConfig('schrack/solr4orders/override_customer_id') ) {
                    return $overrideCustomerId; // !!!! ### remove ASAP !!!!
                }
        */
        if ( ! $customer ) {
            $customer = $this->_getCustomer();
        }
        $wwsId = $customer->getSchrackWwsCustomerId();
        if ( ! $wwsId ) {
            $wwsId = '000000';
        }
        return $wwsId;
    }

    private function _getCustomer () {
        if ( ! $this->_customer ) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
            if ( (! $this->_customer) /* || (! $this->_customer->getSchrackWwsCustomerId()) */ ) { // did not work on prospects in de shop
                throw new Exception("Could not determine logged-in customer.",1000,null);
            }
        }
        return $this->_customer;
    }


    /**
     * @return Zend_Soap_Client
     */
    private function _getSoapClient () {
        if ( !$this->_client ) {
            $options = array(
                'schrack_system' => 'mdoc',
            );
            if ( Mage::getStoreConfig('schrackdev/mdoc/log') ) {
                $options['schrack_log_transfer'] = true;
            }
            $wsdl = Mage::getStoreConfig('schrack/mdoc/wsdl');
            $this->_client = Mage::helper('schrack/soap')->createClient($wsdl,$options);
            $this->_client->setResponseLogFilter(new Schracklive_SchrackSales_Helper_Order_ResponseLogFilter());
        }
        return $this->_client;
    }

    public function getDocumentByIDs ( $orderId, $documentId, $docType ) {
        return $this->getDocument($orderId,$documentId,$docType);
    }

    public function getDocument ( $orderNumber, $docNumber, $docType ) {
        if ( $this->_getCustomerId() === '666666' ) {
            return array('error' => 'Customer ID = 666666');
        }
        $useMDoc = Mage::getStoreConfig('schrack/mdoc/use_mdoc');
        if ( isset($useMDoc) && ! $useMDoc ) {
            return array('error' => 'MDoc is not allowed by Mage-Backend');
        }

        $cacheKey = "MDOC-$orderNumber-$docNumber-$docType";
        if ( false !== ($data = Mage::app()->getCache()->load($cacheKey)) ) {
            $res = unserialize($data);
        } else {
            $isCollectiveDoc = $this->_getIsCollectiveDoc($orderNumber, $docNumber, $docType);
            if ( $isCollectiveDoc ) {
                $reqKey = $docNumber;
                switch ( $docType ) {
                    case self::DOCTYPE_INVOICE:
                        $reqType = $this->_getMDocDocType('collective_invoice', 'SARE');
                        break;
                    case self::DOCTYPE_CREDIT_MEMO:
                        $reqType = $this->_getMDocDocType('collective_creditmemo', 'SARE');
                        break;
                    default :
                        Mage::log('Unexpected doctype ' . $docType);
                        return array('error' => 'Unexpected doctype (collective doc)' . $docType);
                }
            } else {
                $followupOrderNumber = $this->_getFollowupOrderNumber($docNumber,$docType);
                if ( is_string($followupOrderNumber) && strlen($followupOrderNumber) > 0 ) {
                    $reqKey = $followupOrderNumber;
                } else {
                    $reqKey = $orderNumber;
                }
                switch ( $docType ) {
                    case self::DOCTYPE_OFFER:
                        $reqType = $this->_getMDocDocType('offer', 'AN');
                        break;
                    case self::DOCTYPE_ORDER:
                        $reqType = $this->_getMDocDocType('order', 'AB');
                        break;
                    case self::DOCTYPE_SHIPMENT:
                        $reqType = $this->_getMDocDocType('shipment', 'LSRS');
                        break;
                    case self::DOCTYPE_INVOICE:
                        $reqType = $this->_getMDocDocType('invoice', 'REGU');
                        break;
                    case self::DOCTYPE_CREDIT_MEMO:
                        $reqType = $this->_getMDocDocType('creditmemo', 'REGU');
                        break;
                    default :
                        Mage::log('Unexpected doctype ' . $docType);
                        return array('error' => 'Unexpected doctype (non-collectice doc)' . $docType);
                }
            }

            $reqCountry = strtoupper(Mage::getStoreConfig('schrack/mdoc/mdoc_country'));
            if ( !$reqCountry || $reqCountry <= '' ) {
                $reqCountry = strtoupper(Mage::getStoreConfig('schrack/general/country'));
            }
            $client = $this->_getSoapClient();
            $res = $client->GetDocumentByKey(array('country' => $reqCountry, 'DocumentType' => $reqType, 'DocumentKey' => $reqKey));
            $res = $res->GetDocumentByKeyResult;
            if ( $res->Code !== 0 ) {
                return array('error' => 'GetDocumentByKeyResult()->Code !== 0 (' . $res->Code . ')');
            }
            Mage::app()->getCache()->save(serialize($res),$cacheKey,array(),60);
        }
        if ( isset($reqType) ) {
            $res->FileName = $reqType . '_' . $res->FileName;
        } else {
            Mage::log("Order Helper getDocument(): undefined reqType! docType = |$docType|");
        }
        return $res;
    }

    public function getTrackandtraceUrl ( $document ) {
        $parcels = $document->getSchrackWwsParcels();
        $helper = Mage::helper('schrackcore/url');
        $url = $helper->getUrlWithCurrentProtocol('shipping/trackandtrace');
        if ( isset($parcels) && strlen($parcels) && isset($url) && strlen($url) ) {
            return $url . '?colloNumbers=' . $parcels;
        }
        return null;
    }

    private function _getMDocDocType ( $keyPart, $default ) {
        $cfgVal = Mage::getStoreConfig('schrack/mdoc/doctype_name_'.$keyPart);
        if ( isset($cfgVal) && strlen($cfgVal) > 0 ) {
            return $cfgVal;
        }
        return $default;
    }

    private function _getIsCollectiveDoc ( $orderNumber, $docNumber, $docType ) {
        if ( $docType == self::DOCTYPE_INVOICE || $docType == self::DOCTYPE_CREDIT_MEMO ) {
            if ( $docType == self::DOCTYPE_INVOICE ) {
                $solrQueryArray = array(
                    "q" => "InvoiceNumber:$docNumber AND DocumentType:Invoice",
                    "fl" => "IsCollectiveInvoice",
                );
            } else {
                $solrQueryArray = array(
                    "q" => "CreditmemoNumber:$docNumber AND DocumentType:Creditmemo",
                    "fl" => "IsCollectiveCreditmemo",
                );
            }
            $solrQueryArray["rows"] = 1;
            $res = $this->performSolrSearch($solrQueryArray);
            return     isset($res->response->docs[0])
                && (    isset($res->response->docs[0]->IsCollectiveInvoice)    && $res->response->docs[0]->IsCollectiveInvoice
                    || isset($res->response->docs[0]->IsCollectiveCreditmemo) && $res->response->docs[0]->IsCollectiveCreditmemo
                );
        } else {
            return false;
        }
    }

    private function _getFollowupOrderNumber ( $docNumber, $docType ) {
        $solrQueryArray = array(
            "q"     => self::DOCTYPE_INT_2_SOLR_DOC_NO[$docType].':'.$docNumber,
            "fl"    => "FollowupOrderNumber",
            "rows"  => 1
        );
        $res = $this->performSolrSearch($solrQueryArray);
        if ( isset($res->response->docs[0]) && isset($res->response->docs[0]->FollowupOrderNumber) ) {
            return $res->response->docs[0]->FollowupOrderNumber;
        } else {
            return false;
        }
    }

    public function getLastPurchasedProductSKUs ( $count = 100 ) {
        $customerId = $this->_getCustomerId();
        // we need to search for orders (and their items) because items do not have information about customer and date
        $solrQueryArray = array(
            'q' => "+CustomerNumber:$customerId AND +DocumentType:Order AND NOT OrderStatus:La1", // no unordered offers
            'fl' => "*,[child of=DocumentType:Order limit=100000]",
            'sort' => "Date desc",
            'rows' => $count // must be enough orders anyhow
        );
        $solrResult = $this->performSolrSearch($solrQueryArray);
        $resultObjects = $this->prepareSolrResult($solrResult->response->docs);
        $skuFlagArray = array(); // using that as a "set" to collect each sku only once

        foreach ( $resultObjects as $order ) {
            foreach ( $order->getAllItems() as $item ) {
                $sku = $item->getSku();
                if (   ( $sku[0] != 'D' || ! is_numeric($sku[1]) )          // skip DMAT
                    && strlen($sku) > 9                                    // regular SKUS have length 10
                    && ! in_array($sku, self::VIRTUAL_SKUS) ) {    // skip MANIPULAT- and others
                    $skuFlagArray[$sku] = true;
                    if ( count($skuFlagArray) >= $count ) {
                        break 2; // break both loops
                    }
                }
            }
        }
        $resultSkuArray = array_keys($skuFlagArray);

        return $resultSkuArray;
    }


    public function searchSalesOrdersSolr ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams, Schracklive_SchrackCustomer_Model_Customer $customer = null, $page = 1, $pageSize = 1000 ) {
        $res = $this->searchSalesOrdersSolrImpl($searchParams,$customer,$page,$pageSize);
        if ( $res && isset($res['records']) ) {
            return $res['records'];
        } else {
            return array();
        }
    }

    private function searchSalesOrdersSolrImpl ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams, Schracklive_SchrackCustomer_Model_Customer $customer = null, $page = 1, $pageSize = 10 ) {
        $customerID = $this->_getCustomerId($customer);
        // $customerID = 714990; use office@elektro-kargl.at instead
        $key =  $customerID . '_' . $searchParams->__toString();
        if ( ! isset($this->_queryCache[$key]) || ! isset($this->_queryCache[$key]['solr']) ) {
            if ( !isset($this->_queryCache[$key]) ) {
                $this->_queryCache[$key] = array();
            }
            $this->_queryCache[$key]['solr'] = array('records' => null, 'facets' => null);
            $solrResult = $this->requestSolr($searchParams,$customerID,$page,$pageSize);
            $this->_queryCache[$key]['solr']['records'] = $this->prepareSolrResult($solrResult->response->docs);
            if ( $searchParams->doSearchOffersWithMultipleStates() ) {
                $this->annotateOrderedOffers($this->_queryCache[$key]['solr']['records'],$customerID);
            }
            if ( $searchParams->doSearchNoOffers() ) {
                $this->removeOrderedOffers($this->_queryCache[$key]['solr']['records'],$customerID);
            }
            $this->_queryCache[$key]['solr']['facets']  = $this->prepareSolrFacets($solrResult->facets);
        }###
        return $this->_queryCache[$key]['solr'];
    }

    private function prepareSolrFacets ( $facets ) {
        if ( ! $facets ) {
            return array();
        }
        $states = array();
        if ( isset($facets->states) && isset($facets->states->buckets) ) {
            foreach ( $facets->states->buckets as $state ) {
                if ( isset($state->val)  && isset($state->count) ) {
                    $states[$state->val] = $state->count;
                }
            }
        }
        if ( isset($facets->offernumbers) && isset($facets->offernumbers->allBuckets) && isset($facets->offernumbers->allBuckets->count) ) {
            $docs = array('Offer' => $facets->offernumbers->allBuckets->count);
        } else {
            $docs = array('Offer' => 0);
        }
        if ( isset($facets->doctypes) && isset($facets->doctypes->buckets) ) {
            foreach ( $facets->doctypes->buckets as $doc ) {
                if ( isset($doc->val) && isset($doc->count) ) {
                    $docs[$doc->val] = $doc->count;
                }
            }
        }
        $allCount = isset($facets->count) ? $facets->count : 0;
        $res = array('all' => $allCount, 'states' => $states, 'docs' => $docs );

        return $res;
    }

    private function requestSolr ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams, $customerId, $page = 1, $pageSize = 10 ){
        $queryArray = $this->createSolrQuery($searchParams, $customerId);
        $queryArray['fl'] = '*';
        $queryArray['json.facet'] = '{ states:{ terms:{ field : OrderStatus } }, doctypes:{ terms:{ field : DocumentType } }, offernumbers:{ terms:{ field : OfferNumber, allBuckets : true } } }';
        $queryArray['rows'] = $pageSize;
        $queryArray['start'] = ($page - 1) * $pageSize;
        if ( ($sort = $this->createSolrSort($searchParams)) ) {
            $queryArray['sort'] = $sort;
        }
        $res = $this->performSolrSearch($queryArray);
        return $res;
    }

    private function performSolrSearch ( array $queryArray ) {
        $queryArray['wt'] = 'json';
        $url = Mage::getStoreConfig('schrack/solr4orders/solrserver');
        $user = Mage::getStoreConfig('schrack/solr4orders/solruser');
        $password = Mage::getStoreConfig('schrack/solr4orders/solrpassword');
        if ( substr($url, -1, 1) !== '/' ) {
            $url .= '/';
        }
        // $url .= 'bjqfacet'; check that!
        $url .= 'query';
        $post = true;
        try {
            if ( $post ) {
                self::logSolr("Query: " . print_r($queryArray, true));
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST,true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($queryArray));
            } else {
                $first = true;
                foreach ( $queryArray as $k => $v ) {
                    if ( $first ) {
                        $url .= '?';
                        $first = false;
                    } else {
                        $url .= '&';
                    }
                    $url .= ($k . '=' . $v);
                }
                $url = str_replace(' ', '%20', $url);
                self::logSolr("Query: $url");
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            }
            if ( $user && $password ) {
                curl_setopt($ch, CURLOPT_USERPWD, "$user:$password");
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            }
            // execute!
            $jsonResult = curl_exec($ch);
            if ( ! $jsonResult ) {
                if ( ($httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE)) >= 400 ) {
                    curl_close($ch);
                    throw new Exception("Curl request to Solr failed! HTTP response code was  '$httpResponseCode'.");
                } else if ( ($errNo = curl_errno($ch)) ) {
                    $err = curl_error($ch);
                    curl_close($ch);
                    throw new Exception("Curl request to Solr failed! Curl returned error '$err' (#$errNo).");
                } else {
                    $info = curl_getinfo($ch);
                    curl_close($ch);
                    throw new Exception("Curl request to Solr failed, no errno given! Curl info returned: " . print_r($info, true));
                }
            }
            curl_close($ch);

            // self::logSolr("Result: $jsonResult");
            $result = json_decode($jsonResult);
            if ( isset($result->error) ) {
                $msg = $result->error->msg;
                throw new Exception("Solr returned error: '$msg'.");
            }
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            $res = new stdClass();
            $res->response = new stdClass();
            $res->response->docs = array();
            $res->facets = null;
            return $res;
        }
        return $result;
    }

    private function createSolrSort ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams ) {
        if ( ! isset($searchParams->sortColumnName) || $searchParams->sortColumnName <= '' ) {
            return false;
        }
        $fieldName = Schracklive_SchrackSales_Model_SolrSalesOrder::mapOrderFieldNameToSolr($searchParams->sortColumnName);
        if ( ! $fieldName ) {
            return false;
        }
        $ascDesc = $searchParams->isSortAsc ? 'asc' : 'desc';
        return "$fieldName $ascDesc";
    }

    private function createSolrQuery ( Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParams, $customerId ) {
        $searchParams = clone($searchParams);
        //----------------------------------------------------------------------
        if ( ! $searchParams->doSearchDocs() ) { // if none,
            $searchParams->setSearchDocs(true);  // then all
        }
        //----------------------------------------------------------------------
        $res = array();
        $mainQuery = '+CustomerNumber:' . $customerId;
        //-------------------------------------- get previous customer id if set
        // TODO: extend 'Umfirmierung' for multiple entries with
        //       specialchar seperated string (e.g.: ';')
        //----------------------------------------------------------------------
        $account = Mage::getModel('account/account')->loadByWwsCustomerId($customerId);
        //----------------------------------------------------------------------
        if ($account->getWwsCustomerIdHistory().'.' != '.') {
            $mainQuery = '+CustomerNumber:(' . $customerId . ' OR ' . $account->getWwsCustomerIdHistory() . ')';
        }
        //--------------------------------------------------------------- state:
        $stateArray = array();
        //----------------------------------------------------------------------
        if ( $searchParams->isOffered )         $stateArray[] = "La1";
        if ( $searchParams->isOrdered )         $stateArray[] = "La2";
        if ( $searchParams->isCommissioned )    $stateArray[] = "La3";
        if ( $searchParams->isDelivered )       $stateArray[] = "La4";
        if ( $searchParams->isInvoiced )        $stateArray[] = "La5";
        if ( $searchParams->isCredited )        $stateArray[] = "La8";
        //----------------------------------------------------------------------
        if ( count($stateArray) > 0 && count($stateArray) < 6 ) {
            $this->addQueryPart($mainQuery,"OrderStatus:(" . implode(" OR ",$stateArray) . ")",'AND');
        }
        //---------------------- if only open offers, avoid already ordered ones
        if ( count($stateArray) == 1 && $searchParams->isOffered ) {
            $avoidOrderNums = $this->getAvoidOfferMap($customerId);
            if ( count($avoidOrderNums) > 0 ) {
                $this->addQueryPart($mainQuery, "NOT OrderNumber:(" . implode(" OR ", array_keys($avoidOrderNums)) . ")", 'AND');
            }
        }
        //------------------------------------------------------------ doctypes:
        $docTypeQuery = '';
        $docTypeArray = array();
        //----------------------------------------------------------------------
        if ( $searchParams->getDeliveryDocs)    $docTypeArray[] = "Shipment";
        if ( $searchParams->getInvoiceDocs)     $docTypeArray[] = "Invoice";
        if ( $searchParams->getCreditMemoDocs)  $docTypeArray[] = "Creditmemo";
        //----------------------------------------------------------------------
        if ( count($docTypeArray) ) {
            $this->addQueryPart($docTypeQuery,"DocumentType:(" . implode(" OR ",$docTypeArray) . ")",'AND');
        }
        //----------------------------------------------------------------------
        if ( $searchParams->getOfferDocs || $searchParams->getOrderDocs ) {
            $orderTypeQuery = 'DocumentType:Order';
            if ( ! $searchParams->getOfferDocs ) {
                $this->addQueryPart($orderTypeQuery,"NOT OrderStatus:La1",'AND');
            } else if ( ! $searchParams->getOrderDocs ) {
                $this->addQueryPart($orderTypeQuery,"+OfferNumber:*",'AND');
            }
            $this->addQueryPart($docTypeQuery,$orderTypeQuery,'OR',true);
        }
        //----------------------------------------------------------------------
        if ( $docTypeQuery > '' ) {
            $this->addQueryPart($mainQuery,$docTypeQuery,'AND',true);
        }
        //---------------------------------------------------------- date range:
        if ( $searchParams->fromDate !== null || $searchParams->toDate !== null ) {
            $dateRangeQueryPart = '';
            if ( $searchParams->fromDate == null ) {
                $dateRangeQueryPart = 'Date:[* TO ' . $searchParams->toDate . 'T00:00:00Z]';
            } else if ( $searchParams->fromDate == null ) {
                $dateRangeQueryPart = 'Date:[' . $searchParams->fromDate . 'T00:00:00Z TO NOW]';
            } else {
                $dateRangeQueryPart = 'Date:[' . $searchParams->fromDate . 'T00:00:00Z TO '. $searchParams->toDate . 'T00:00:00Z]';
            }
            $this->addQueryPart($mainQuery,$dateRangeQueryPart,'AND');
        }
        //---------------------------------------------------------------- text:
        if ( $searchParams->text !== null && $searchParams->text > '' ) {
            $textOrderQueryPart = '';
            //------------------------------------------------------------------
            $originalText = trim($searchParams->text);
            $leftRightExactText = "(\"$originalText\" OR $originalText* OR *$originalText)";
            //------------------------------------------------------------------
            $this->addQueryPart($textOrderQueryPart,'CustomerOrderInfo:' . $leftRightExactText,'OR');
            $this->addQueryPart($textOrderQueryPart,'CustomerProjectInfo:' . $leftRightExactText,'OR');
            $this->addQueryPart($textOrderQueryPart,'OrderNumber:' . $originalText,'OR');
            //------------------------------------------------------------------
            if ( $searchParams->getOfferDocs )      $this->addQueryPart($textOrderQueryPart,'OfferNumber:' . $originalText,'OR');
            if ( $searchParams->getDeliveryDocs )   $this->addQueryPart($textOrderQueryPart,'ShipmentNumber:' . $originalText,'OR');
            if ( $searchParams->getInvoiceDocs )    $this->addQueryPart($textOrderQueryPart,'InvoiceNumber:' . $originalText,'OR');
            if ( $searchParams->getCreditMemoDocs ) $this->addQueryPart($textOrderQueryPart,'CreditmemoNumber:' . $originalText,'OR');
            //------------------------------------------------------------------
            $textItemQueryPart = '';
            $this->addQueryPart($textItemQueryPart,'{!parent which=\'-_nest_path_:* *:*\' v=$PosQ}','OR');
            //------------------------------------------------------------------
            $textQuery = '';
            $this->addQueryPart($textQuery,$textOrderQueryPart,'OR',true);
            $this->addQueryPart($textQuery,$textItemQueryPart,'OR',true);
            $this->addQueryPart($mainQuery,$textQuery,'AND',true);
            //------------------------------------------------------------------
            $posQuery = '';
            $this->addQueryPart($posQuery,'Description:' . $leftRightExactText,'OR');
            $this->addQueryPart($posQuery,'ItemID:' . $leftRightExactText,'OR');
            $res['PosQ'] = $posQuery;
        }
        //----------------------------------------------------------------------
        $res['q'] = $mainQuery;
        return $res;
    }

    private function addQueryPart ( &$query, $part, $operator = 'OR', $surroundWithBrackets = false ) {
        if ( $query > '' ) {
            $query .= (' ' . $operator . ' ');
        }
        if ( $surroundWithBrackets ) {
            $query .= ('(' . $part . ')');
        } else {
            $query .= $part;
        }
    }

    private function getAvoidOfferMap ( $customerId ) {
        if ( ! $this->_avoidOfferMap ) {
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $sql = "SELECT order_number FROM sales_schrack_ordered_offer WHERE customer_number = ?";
            $avoidOrderNums = $readConnection->fetchCol($sql, array($customerId));
            $this->_avoidOfferMap = array_flip($avoidOrderNums);
        }
        return $this->_avoidOfferMap;
    }

    private static function logSolr ( $msg ) {
        // TODO: make controllable in backend!
        Mage::log($msg,null,'solar_orders.log');
    }

    private function prepareSolrResult ( array $solrResult ) {
        return Schracklive_SchrackSales_Model_SolrSalesEntity::createSolrSalesEntityResult($solrResult);
    }

    private function removeOrderedOffers ( $rows, $customerID ) {
        $orderNums = array();
        foreach ( $rows as $row ) {
            if ( $row->getData("OrderStatus") > "La1" ) {
                $orderNums[$row->getData("OrderNumber")] = true;
            }
        }
        if ( count($orderNums) > 0 ) {
            $orderNumStr = "'" . implode("','",array_keys($orderNums)) . "'";
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $sql = "DELETE FROM sales_schrack_ordered_offer WHERE customer_number = $customerID AND order_number IN ($orderNumStr)";
            $writeConnection->query($sql);
        }
    }
}


