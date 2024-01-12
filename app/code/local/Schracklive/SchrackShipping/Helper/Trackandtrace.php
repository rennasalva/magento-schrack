<?php

class Schracklive_SchrackShipping_Helper_Trackandtrace extends Mage_Core_Helper_Abstract {
    // 50 Guntramsdorf (SLG)
    // 51 Wien (GSW)
    // 52 Tschechien (Prag)
    // 53 Polen (Warschau)
    // 54 Linz
    // 55 Belgien (kommt noch! --> Stand: 23.07.2015)
    private $_snClientIds = array(2050, 2051, 2052, 2053, 2054, 2055);
    
    private $_statusMap;
    private $_detailedStatusMap;
    private $_carrierCache;
    private $_unknownStatus;
    private $_useUserDescription;

    
    public function __construct() {            
        $this->_carrierCache = array();
        $this->_statusMap = array(
            // 'DOS' => array('name' => 'Delivery Obstacle', 'description' => 'There is a delivery obstacle') // Zustellhindernis - temp. deactivated by user request
            'DVD' => array('name' => 'Delivered', 'description' => 'Collo is delivered'), // Zugestellt
            'EDS' => array('name' => 'End Depot', 'description' => 'Collo is in enddepot scan'), // im Zustelldepot
            // 'SDS' => array('name' => 'Startdepot scan', 'description' => 'Collo is in startdepot scan'), // im Startdepot - temp. deactivated
            'LSC' => array('name' => 'Loadingscan', 'description' => 'Collo is in loading scan'), // Verladescan
            'IMP' => array('name' => 'Imported', 'description' => 'Collo is imported'), // Verpackt
            'MAN' => array('name' => 'Manually created in shipping.net', 'description' => 'Collo is manually created in shipping.net'), // Verpackt
            //'NDC' => array('name' => 'New Delivery Collonumber', 'description' => 'Collo has been packaged'), // Verpackt - deactivated by user request
        );
                        
        $this->_detailedStatusMap = array(
            'ACP' => 'Accepted',
            'ALP' => 'Acquisitionlist printed',
            'APP' => 'Approved',
            'APS' => 'Allow partial shipment',
            'ARR' => 'Arrived',
            'ASI' => 'Assigned to invoice',
            'ATM' => 'Attached to master',
            'ATS' => 'Added to stock',
            'AVC' => 'Aviso completed',
            'BRM' => 'Busreservation-Mail created',
            'CAL' => 'Calculated',
            'CAS' => 'Collo atteched to a shipment',
            'CDC' => 'Collo data changed',
            'CDS' => 'Collo detached from a shipment',
            'CEX' => 'Carrierexport canceled',
            'CLM' => 'Reclamation',
            'CMP' => 'Completed',
            'CON' => 'Consolidation',
            'DCC' => 'Delivery carrier changed',
            'DCE' => 'Device Communication Error',
            'DDF' => 'Delivery time fixed',
            'DDM' => 'Dispo date missing',
            'DEC' => 'Declined',
            'DEP' => 'Sent',
            'DIS' => 'Dispatched',
            'DMG' => 'Damaged',
            'DOS' => 'Delivery Obstacle',
            'DPC' => 'Dispo changed',
            'DPF' => 'Disposition fixed',
            'DRD' => 'Delivery refused',
            'DSC' => 'Deliveryservice changed',
            'DVD' => 'Delivered',
            'EDS' => 'Enddepot scan',
            'ENT' => 'Entered',
            'ERR' => 'Error',
            'EXP' => 'Exported',
            'FDS' => 'First Depot Scan',
            'FLP' => 'Freightlist printed, shipment will be picked',
            'GIS' => 'Goods Issue Scan',
            'GOT' => 'Geoposition out of Tolerance',
            'GRC' => 'Goods receipt completed',
            'GRU' => 'Undo goods receipt',
            'HLT' => 'Halt',
            'HTO' => 'Heading To',
            'HUS' => 'Hubscan',
            'ICC' => 'Inventory counting completed',
            'ICP' => 'Incomplete',
            'IMP' => 'Imported',
            'INF' => 'Information',
            'LAC' => 'Loadingaddress Changed',
            'LAS' => 'WL locked for add to stock',
            'LCP' => 'Loadingscan completed',
            'LCS' => 'delivery notification scanned',
            'LOG' => 'Logging',
            'LOK' => 'Locked',
            'LPS' => 'WL locked for picking',
            'LRS' => 'WL locked for remove from stock',
            'LSC' => 'Loadingscan',
            'LSM' => 'Loadingscan missing',
            'MAN' => 'Manually created in shipping.net',
            'MIS' => 'Missing',
            'NDC' => 'New Delivery Collonumber',
            'NGS' => 'No valid Geoposition',
            'NLP' => 'Notificationletter prited',
            'NSC' => 'Not System Conform',
            'OAP' => 'Order attached to picklist',
            'OAS' => 'Order assigned to shipment',
            'OCH' => 'Order structure changed',
            'OCN' => 'Customer Notified',
            'ODA' => 'Order dispo automatically',
            'ODC' => 'Order dispo completed',
            'ODE' => 'Order dispo external',
            'OIM' => 'Order in manufacturing',
            'OPA' => 'Orderpos atteched',
            'OPC' => 'OrderPos Structure Changed',
            'OPD' => 'Orderpos detached',
            'OSC' => 'Offlinescan',
            'PAC' => 'Picklist packed completely',
            'PCC' => 'Pickup carrier changed',
            'PIP' => 'Picklist in picking',
            'PNT' => 'Printed',
            'POS' => 'Pickup Obstacle',
            'PPC' => 'Picklist picking completed',
            'PRD' => 'Pickup refused',
            'PSC' => 'Pickupservice changed',
            'PUA' => 'Pickup accepted',
            'PUB' => 'Publish',
            'PUC' => 'Pickup completed',
            'PUP' => 'Pickup approved',
            'PUR' => 'Pickup rejected',
            'RCA' => 'Recalculated',
            'RCE' => 'No Routing found',
            'RCV' => 'Received',
            'RDI' => 'Redispatched',
            'RDQ' => 'Resent to dispatch queue',
            'REF' => 'Reference',
            'REP' => 'Replicated',
            'RFC' => 'Released for calculation',
            'RFD' => 'Released for dispatch',
            'RFM' => 'Removed from master',
            'RFP' => 'Ready for processing',
            'ROE' => 'Routing Error',
            'RPT' => 'Released for printing',
            'RTS' => 'Return to sender',
            'RUN' => 'Run',
            'RXP' => 'Released for export',
            'SAT' => 'Shipment atteched to a transport',
            'SBS' => 'shipment behind schedule',
            'SCC' => 'Shipmentcode changed',
            'SCN' => 'Scan',
            'SDQ' => 'Sent to dispatch queue',
            'SDS' => 'Startdepot scan',
            'SDT' => 'Shipment detached from a transport',
            'SNA' => 'shipment not arrived',
            'SPC' => 'Vollständig gepicked',
            'SPN' => 'Supernumerous',
            'SPP' => 'Packlist printed',
            'SRB' => 'Status rolled back',
            'STO' => 'Storno',
            'STR' => 'Tentative Reservation',
            'TCH' => 'Template changed',
            'TLP' => 'Transport Label printed',
            'TOG' => 'Template object generated',
            'TOR' => 'Temperatur out of range',
            'TRC' => 'Transport Completed',
            'TRE' => 'Transport end',
            'TRS' => 'Transport Started',
            'TSP' => 'Transport start preparation',
            'UAC' => 'Unloadingaddress Changed',
            'UAS' => 'WL unlocked for add to stock',
            'UCP' => 'Unloadingscan completed',
            'ULK' => 'Unlocked',
            'UPS' => 'WL unlocked for picking',
            'URS' => 'WL unlocked for remove from stock',
            'USC' => 'Unloadingscan',
            'VCN' => 'VAT check not ok',
            'VCO' => 'VAT check ok ',
            'WAD' => 'Wrong Address',
            'WLP' => 'Warehouse Label Printed',
            'XFC' => 'Excluded from clearing',
        );

        $this->_unknownStatus = array('name' => 'unknown', 'description' => 'unknown');

        $this->_useUserDescription = array(
            'DVD', 'DOS'
        );
    }
    
    public function getStatusMap() {
        return $this->_statusMap;
    }
    
    public function getDetailedStatusMap() {
        return $this->_detailedStatusMap;
    }

    /**
	 * Gets a configured instance of a SOAP client
	 *
	 * @throws Schracklive_Wws_Exception|Mage_Core_Exception
	 * @return Schracklive_Schrack_Model_Soap_Client
	 */
	public function createSoapClient() {		
        $wsdl = Mage::getStoreConfig('shipping/trackandtrace/wsdl');
        if ( ! $wsdl ) {
            throw Mage::exception('Schracklive_SchrackShipping', 'No WSDL for TNT connection configured. (shipping/trackandtrace/wsdl)');
        }
        $options = array(
            'schrack_system' => 'shippingnet',
        );
        if ( Mage::getStoreConfig('schrackdev/trackandtrace/log') ) {
            $options['schrack_log_transfer'] = true;
        }
        return Mage::helper('schrack/soap')->createClient($wsdl,$options);
	}
    
    public function fetchResultsForColloNumbers(array $colloNumbers) {
        foreach ( $this->_snClientIds as $clientId ) {
            $results = $this->_fetchResultsForColloNumbersAndClientId($colloNumbers, $clientId);
            if ( isset($results->shipmentList->Shipment) ) {
                break;
            }
        }
        return $results;
    }
    
    private function _fetchResultsForColloNumbersAndClientId(array $colloNumbers, $clientId) {
        $helper = Mage::helper('schrackshipping/trackandtrace');
        /**
         * @var Zend_Soap_Client
         */
        $client = $this->createSoapClient();
        $colloList = array();
        foreach ($colloNumbers as $num) {
            if (strlen((string)$num))
                array_push($colloList, array('ClientID' => $clientId, 'ColloNumber' => $num));
        }
        $results = $client->GetShipmentListByColloNumbers(array('colloList' => $colloList));
        
        $results = $this->_normalizeResults($results);
                
        return $results;        
    }
    
    /**
     * PHP SoapClient returns an object for a list with only one element; we turn it back into an array for
     * easier handling
     * 
     * @param object $results
     * @return object
     */
    private function _normalizeResults($results) {
        if (isset($results->shipmentList) && is_object($results->shipmentList)) {
            if (isset($results->shipmentList->Shipment)) {
                if (is_object($results->shipmentList->Shipment))
                    $results->shipmentList->Shipment = array($results->shipmentList->Shipment);
                foreach ($results->shipmentList->Shipment as &$shipment) {
                    if (isset($shipment->ColloList) && is_object($shipment->ColloList) && isset($shipment->ColloList->Collo) && is_object($shipment->ColloList->Collo))
                        $shipment->ColloList->Collo = array($shipment->ColloList->Collo);
                }
            }
        }
        
        return $results;
    }
    
    public function sortStatus($statusArray) {
        $newStatusArray = $statusArray;
        usort($newStatusArray, array('Schracklive_SchrackShipping_Helper_Trackandtrace', 'cmpStatusTime'));                
        return $newStatusArray;
    }
    
    
    /**
     * @note: Hannes Bichler wants it sorted by status instead of time
     * 
     * @param type $status1
     * @param type $status2
     * @return type
     */
    public function cmpStatusId($status1, $status2) {
        $keys = array_keys($this->_statusMap);
        $index1 = array_search($status1->StatusID, $keys);
        $index2 = array_search($status2->StatusID, $keys);
        return (($index1 < $index2) ? -1
            : (($index1 > $index2) ? 1
                : 0));
    }
    
    
    /**
     * @note: reversed!
     * 
     * @param type $status1
     * @param type $status2
     * @return type
     */
    public function cmpStatusTime($status1, $status2) {
        $time1 = strtotime($status1->StatusDate);
        $time2 = strtotime($status2->StatusDate);
                
        return (($time1 < $time2) ? 1
            : (($time1 > $time2) ? -1
                : self::cmpStatusId($status1, $status2)));
    }
    
    /**
     * @note: NDC, IMP and MAN are functionally equivalent, we only show NDC if we find it
     * 
     * @param type $statusArray
     */
    public function removeStatusDuplicates($statusArray) {
        if (current(array_filter($statusArray, array('Schracklive_SchrackShipping_Helper_Trackandtrace', 'filterNdcOnly')))) {
            return array_filter($statusArray, array('Schracklive_SchrackShipping_Helper_Trackandtrace', 'filterNdcAndRest'));
        } elseif (current(array_filter($statusArray, array('Schracklive_SchrackShipping_Helper_Trackandtrace', 'filterNdcAlikesOnly')))) {
            return array_filter($statusArray, array('Schracklive_SchrackShipping_Helper_Trackandtrace', 'filterNdcAlikesAndRest'));
        } else {
            return $statusArray;
        }
    }
    
    /***
     * out of MAN, IMP, and NDC, we want only the latter, disregarding all others
     */
    public function filterNdcOnly($status) {
        return ($status->StatusID === 'NDC');
    }
    
    public function filterNdcAndRest($status) {
        return ($status->StatusID === 'NDC' || (!in_array($status->StatusID, array('IMP', 'MAN', 'NDC'))));
    }
    
    /***
     * out of MAN, IMP, and NDC, we want all of these, disregarding all others
     */
    public function filterNdcAlikesAndRest($status) {
        return (in_array($status->StatusID, array('IMP', 'MAN')) || !in_array($status->StatusID, array('IMP', 'MAN', 'NDC')));
    }

    public function filterNdcAlikesOnly($status) {
        return (in_array($status->StatusID, array('IMP', 'MAN')));
    }
    
    
    private function _checkCarrierCache($carrier) {
        if (property_exists($carrier, 'Ref') && isset($this->_carrierCache[$carrier->Ref]))
            $carrier = $this->_carrierCache[$carrier->Ref];
        else if (property_exists($carrier, 'Id'))
            $this->_carrierCache[$carrier->Id] = $carrier;
        return $carrier;
    }
    public function extractOUDeliveryCarrierName($shipment) {
        if (property_exists($shipment, 'OUDeliveryCarrier') && isset($shipment->OUDeliveryCarrier)) {
            $carrier = $shipment->OUDeliveryCarrier;
            $carrier = $this->_checkCarrierCache($carrier);
            if (property_exists($carrier, 'Name')) {
                return $carrier->Name;
            } else {
                return null;
            }
        } else
            return null;
                
    }
    
    public function extractOUPickupCarrierName($shipment) {
        if (property_exists($shipment, 'OUPickupCarrier') && isset($shipment->OUPickupCarrier)) {
            $carrier = $shipment->OUPickupCarrier;
            $carrier = $this->_checkCarrierCache($carrier);
            if (property_exists($carrier, 'Name')) {
                return $carrier->Name;
            } else {
                return null;
            }
        } else
            return null;
        
    }

    public function extractOUContainerCarrierName($shipment) {
        if (property_exists($shipment, 'OUContainerCarrier') && isset($shipment->OUContainerCarrier)) {
            $carrier = $shipment->OUContainerCarrier;
            $carrier = $this->_checkCarrierCache($carrier);
            if (property_exists($carrier, 'Name')) {
                return $carrier->Name;
            } else {
                return null;
            }
        } else
            return null;

    }

    public function reorgResult ( $results, $colloNumbersArray, &$foundColloOnce = null ) {
        if ($results) {
            $foundColloOnce = false;
            foreach ($results->shipmentList->Shipment as &$shipment) {
                foreach ($shipment->ColloList->Collo as &$collo) {
                    $foundCollo = false;

                    array_unshift($collo->StatusList->ColloStatus, $collo->StatusCurrent);
                    $collo->StatusList->MainStatus = array();

                    foreach ($collo->StatusList->ColloStatus as $status) {
                        $foundColloNumberInDescription = false;

                        if(is_array($colloNumbersArray) && !empty($colloNumbersArray)) {
                            foreach($colloNumbersArray as $key => $value) {
                                if ($value && property_exists($status, 'Description')&& stristr($status->Description, $value)) {
                                    $foundColloNumberInDescription = true;
                                    $collo->ColloNumber = $value;
                                }
                            }
// echo 'colloNumbersArray: <br>'; var_dump($colloNumbersArray); die();
                        }

                        if (property_exists($status, 'StatusID') && $status->StatusID === 'NDC' && $foundColloNumberInDescription) {
                            $collo->ColloNumber = $status->Description;
                            $foundCollo = true;
                            // Marker for remember, that we have found what we need !:
                            $foundColloOnce = true;

// echo 'collo->ColloNumber: <br>'; var_dump($collo->ColloNumber); die();
                        }
                        if (property_exists($status, 'StatusID') && in_array($status->StatusID, array_keys($this->_statusMap))) {
                            array_push($collo->StatusList->MainStatus, $status);

// echo 'collo->StatusList->MainStatus: <br>'; var_dump($collo->StatusList->MainStatus); die();
                        }
                    }

                    if (property_exists($collo, 'CodeList') && is_array($collo->CodeList->ColloCode) && !empty($collo->CodeList->ColloCode)) {
                        $colloCodes = $collo->CodeList->ColloCode;

                        foreach ($colloCodes as $index => $recordSet) {
                            if (is_object($recordSet) &&
                                property_exists($recordSet, 'OUCarrierG') &&
                                is_object($recordSet->OUCarrierG) &&
                                property_exists($recordSet->OUCarrierG, 'ID') &&
                                $recordSet->OUCarrierG->ID != null) {
                                 $correctColloNumber = $recordSet->Code;
//echo 'correctColloNumber: <br>'; var_dump($correctColloNumber);
                            }
                        }

                        if ($correctColloNumber) {
                            $collo->ColloNumber = $correctColloNumber;
                            $foundCollo = true;
                            $foundColloOnce = true;
                        } else {
                            unset($collo->ColloNumber);
                        }
                    }
// echo 'correctColloNumber: <br>'; var_dump($correctColloNumber); die();
                    if (!$foundCollo) {
                        if (property_exists($collo, 'DisplayNumber') && in_array($collo->DisplayNumber, $colloNumbersArray)) {
                            $collo->ColloNumber = $collo->DisplayNumber;
                            $foundColloOnce = true;
                        }
                    }

                    $collo->StatusList->MainStatus = $this->sortStatus($collo->StatusList->MainStatus);
                    $collo->StatusList->MainStatus = $this->removeStatusDuplicates($collo->StatusList->MainStatus);
                    $collo->OUDeliveryCarrierName = $this->extractOUDeliveryCarrierName($shipment);
                    $collo->OUPickupCarrierName = $this->extractOUPickupCarrierName($shipment);
                    $collo->OUContainerCarrierName = $this->extractOUContainerCarrierName($shipment);
                }
            }
        }
        return $results;
    }

    public function shrinkResults ( $results ) {
        $newResults = array();
        if ($results) {
            $i = -1;
            foreach ($results->shipmentList->Shipment as $shipment) {
                $deliveryDateTime = $this->getDateFormatted($shipment->DeliveryDateTimeTo);
                foreach ($shipment->ColloList->Collo as $collo) {
                    $statusList = array();
                    foreach($collo->StatusList->MainStatus as $status) {
                        if (isset($status->ColloID) && $this->isMainStatus($status->StatusID)) {
                            $statusList[] = $status;
                            if ($status->StatusID == 'DVD') {
                                $deliveryDateTime = '';
                            }
                        }
                    }
                    if (isset($collo->ColloNumber)) {
                        $newResults[++$i] = new stdClass();
                        $newResults[$i]->ColloNo = $collo->ColloNumber;

                        $carrierName = '';
                        if(property_exists($collo, 'OUContainerCarrierName') && isset($collo->OUContainerCarrierName)) {
                            $carrierName = $collo->oUContainerCarrierName;
                        } elseif (property_exists($collo, 'OUPickupCarrierName') && isset($collo->OUPickupCarrierName)) {
                            $carrierName = $collo->OUPickupCarrierName;
                        } elseif (property_exists($collo, 'OUDeliveryCarrierName') && isset($collo->OUDeliveryCarrierName)) {
                            $carrierName = $collo->OUDeliveryCarrierName;
                        }
                        $newResults[$i]->Carrier = $carrierName;

                        $currentStatus = array_shift($statusList);
                        if ($this->getStatusFromId($currentStatus->StatusID)) {
                            $newResults[$i]->CurrentStatus = new stdClass();
                            $newResults[$i]->CurrentStatus->Date           = $this->getDateFormatted($currentStatus->StatusDate);
                            $newResults[$i]->CurrentStatus->Time           = $this->getTimeFormatted($currentStatus->StatusDate);
                            $newResults[$i]->CurrentStatus->ShipmentStatus = $this->__($this->getStatusNameFromId($currentStatus->StatusID));
                            $newResults[$i]->CurrentStatus->Description    = $this->__($this->getStatusDescriptionFromId($currentStatus->StatusID, $currentStatus->Description));
                        } else {
                            $newResults[$i]->CurrentStatus = null;
                        }
                        if ( $currentStatus->StatusID != 'DVD' ) {
                            $newResults[$i]->EstimatedDeliveryDateTime = $deliveryDateTime;
                        }
                        $newResults[$i]->HistoricalStatus = array();
                        foreach($statusList as $status) {
                            if (isset($status->ColloID) && $this->isMainStatus($status->StatusID)) {
                                $histStatus = new stdClass();
                                $histStatus->Date           = $this->getDateFormatted($status->StatusDate);
                                $histStatus->Time           = $this->getTimeFormatted($status->StatusDate);
                                $histStatus->ShipmentStatus = $this->__($this->getStatusNameFromId($status->StatusID));
                                $histStatus->Description    = $this->__($this->getStatusDescriptionFromId($status->StatusID, $status->Description));
                                $newResults[$i]->HistoricalStatus[] = $histStatus;
                            }
                        }
                    }
                }
            }
        }
        return $newResults;
    }

    public function getStatusFromId($id) {
        return isset($this->_statusMap[$id]) ? $this->_statusMap[$id] : null;
    }

    public function getStatusNameFromId($id) {
        $status = $this->getStatusFromId($id);
        if ($status) return $status['name'];
        else return $this->_unknownStatus['name'];
    }

    public function getStatusDescriptionFromId($id, $userDescription = null) {
        $status = $this->getStatusFromId($id);
        if ($userDescription !== null && strlen($userDescription) && in_array($id, $this->_useUserDescription))
                $status['description'] = $userDescription;
        if ($status) return $status['description'];
        else return $this->_unknownStatus['description'];
    }

    public function isMainStatus($id) {
        return array_key_exists($id, $this->_statusMap);
    }

    public function getDateFormatted($date) {
        date_default_timezone_set('Europe/Vienna');
        return date('d.m.Y', strtotime($date . ' UTC'));
    }

    public function getTimeFormatted($date) {
        date_default_timezone_set('Europe/Vienna');
        return date('H:i', strtotime($date . ' UTC'));
    }
}

?>
