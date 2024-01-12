<?php
/**
 *
 * @package	Orcamultimedia_Ids
 *
 **/

class Orcamultimedia_Ids_Helper_Data extends Mage_Core_Helper_Abstract {

    public $session;
    private $_customer;
    private $_idsCartExtra;
    private $_shippingMode;
    private $_offerNumber = null;
    private $_cartReturnType = 'Warenkorbrückgabe';


	public function getActionUrl() {
		return $this->session['ids']['hookurl'];
	}


    public function getIdsForm($checkout = false, $wws_order_id = null) {
        // $cartReturnType -> Wenn Bestellung-Rückgabe (nach dem Checkout), dann "Warenkorbrückgabe mit Bestellung"
        $timeout = 60;

        $currentDate          = date('Y-m-d');
        $currentTime          = date('H:i:s');
        $currency             = Mage::getStoreConfig('currency/options/base');
        $idsVersion           = '2.0'; // Default (fallback)
        $readConnection       = Mage::getSingleton('core/resource')->getConnection('core_read');
        $arrSupportedVersions = array("2.0", "2.1", "2.2", "2.3", "2.4", "2.5");

        $session = Mage::getSingleton('customer/session');
        $email = $session->getCustomer()->getEmail();

        $query = "SELECT * FROM schrack_ids_data WHERE email LIKE '". $email . "' and active = 1";
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $queryResult = $readConnection->fetchAll($query);

        if (count($queryResult) > 0) {
            foreach ($queryResult as $index => $recordset) {
                $externalVersion = $recordset['external_version'];
                $idsSelectedShipping = $recordset['selected_shipping'];
            }
        }
        if ($externalVersion && in_array($externalVersion, $arrSupportedVersions)) {
            $idsVersion = $externalVersion;
        }

        if (!$this->_customer) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }

        if ($checkout == true && $wws_order_id) {
            $orderSnippet = '<OrderConfNo>' . $wws_order_id . '</OrderConfNo>';
            $this->_cartReturnType = 'Warenkorbrückgabe mit Bestellung';
            $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
            $order = Mage::getModel('sales/order')->load($orderId);
            $shippingMode = $order->getShippingMethod();
            if (stristr($shippingMode, 'delivery')) {
                $shippingMode = 'Lieferung';
                $this->_shippingMode = 'Lieferung';
            } else {
                $shippingMode = 'Abholung';
            }
        } else {
            $orderSnippet = '';
            $shippingMode = 'Lieferung'; // Default
            $this->_shippingMode = 'Lieferung';
        }

        if ($checkout == true && !$wws_order_id) {
            $quote = $session->getQuote();
            if ($quote == null) {
                $quote = Mage::getSingleton('checkout/session')->loadCustomerQuote();
            }
            // Fallback -> $quote->getShippingAddress() not available:
            if ($quote->getShippingAddress() == null) {
                if ($idsSelectedShipping) {
                    $shippingMode = 'pickup';
                    if ($idsSelectedShipping == 'Lieferung') {
                        $shippingMode = 'delivery';
                    }
                } else {
                    $shippingMode = 'delivery';
                }
            } else {
                $shippingMode = $quote->getShippingAddress()->getShippingMethod();
            }

            Mage::log($shippingMode, null, "ids.log");
            if (stristr($shippingMode, 'delivery')) {
                $shippingMode = 'Lieferung';
                $this->_shippingMode = 'Lieferung';
            } else {
                $shippingMode = 'Abholung';
            }
        }

        // Get hookurl from customer:
        if (!$this->getActionUrl()) {
            $email = $this->_customer->getEmail();

            $idsTable = Mage::getSingleton('core/resource')->getTableName('schrack_ids_data');
            $query = "SELECT hookurl FROM " . $idsTable . " WHERE email LIKE '" . $email . "' and active = 1";
            $queryResult = $readConnection->query($query);
            if ($queryResult->rowCount() > 0) {
                foreach ($queryResult as $recordset) {
                    $this->session['ids']['hookurl'] = $recordset['hookurl'];
                }
            } else {
                $this->session['ids']['hookurl'] = 'No Hook URL found in [WKE] IDS-Connect-Request';
                Mage::log("No Hook URL Found at Customer " . $email, null, "ids.error.log");
            }
        }

        // XML DATA:
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Warenkorb xmlns="http://www.itek.de/Shop-Anbindung/Warenkorb/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.itek.de/Shop-Anbindung/Warenkorb/ http://www.itek.de/Shop-Anbindung/Warenkorb/warenkorb_senden.xsd">
	<Order>
	    ' . $this->getCompleteCartXML($checkout, $wws_order_id) . '
		<OrderInfo>
			<ModeOfShipment>' . $shippingMode . '</ModeOfShipment>' . $orderSnippet . '
			<Cur>' . $currency . '</Cur>
			' . $this->_offerNumber . '
		</OrderInfo>		
	</Order>
	<WarenkorbInfo>
		<Date>' . $currentDate . '</Date>
		<Time>' . $currentTime . '</Time>
		<Version>' . $idsVersion . '</Version>
		<RueckgabeKZ>' . $this->_cartReturnType . '</RueckgabeKZ>
	</WarenkorbInfo>
</Warenkorb>';

        $form  = '<form name="IdsForm" id="IdsForm"; method="post"';
        $form .= ' style="display: none;" action="' . $this->getActionUrl() . '">';
        $form .= '<textarea name="warenkorb" cols="150" rows="50">';
        $form .= $xml;
        $form .= '</textarea>';
        $form .= '</form>';
        //Mage::log($this->getActionUrl(), null, 'ids.log');
        Mage::log($form, null, 'ids.shopform.log', false, false);

        return $form;
    }

    //======================================================= getCompleteCartXML
    private function getCompleteCartXML($checkout = false, $wws_order_id = false) {
    //==========================================================================
        $completeCartXML = '';
        $metalSurchargesXmlList = "";

        $session = Mage::getSingleton('checkout/session');
        $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

        $cartItems = array();
        $index     = 1;
        $position  = 1;
        $idsAction = 'Not Available';
        $shippingData = array();

        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $idsTable = Mage::getSingleton('core/resource')->getTableName('schrack_ids_data');
        $query = "SELECT * FROM " . $idsTable . " WHERE email LIKE '" . $email . "' and active = 1";
        $queryResult = $readConnection->query($query);
        if ($queryResult->rowCount() > 0) {
            foreach ($queryResult as $recordset) {
                $idsAction        = $recordset['current_action'];
                $idsCartNormal    = unserialize($recordset['cart_normal']);
                $idsCartExtra     = unserialize($recordset['cart_extra']);
                $idsCartPositions = unserialize($recordset['ids_wks_positions']);
            }
        }
        if ($idsAction == 'WKS') {
            Mage::log($idsCartNormal, null, 'ids.product.log');
            Mage::log($idsCartExtra, null, 'ids.product.log');
            Mage::log($idsCartPositions, null, 'ids.product.log');
        }

        if (is_array($idsCartNormal) && !empty($idsCartPositions)) {
            $idsCartPositionsFlipped = array_flip($idsCartPositions);
        }

        if ($checkout == false) {
            $quote = $session->getQuote();
            if ($quote == null) {
                $quote = Mage::getSingleton('checkout/session')->loadCustomerQuote();
            }
            $quoteItems = $quote->getAllVisibleItems();
        } else {
            if ($wws_order_id) {
                $orderId           = Mage::getSingleton('checkout/session')->getLastOrderId();
                $order             = Mage::getModel('sales/order')->load($orderId);
                $lastActiveQuoteId = $order->getQuoteId();
                $lastActiveQuote   = Mage::getModel("sales/quote")->loadByIdWithoutStore($lastActiveQuoteId);
                $methodCode        = $order->getPayment()->getMethod();
                $shippingAddress   = $order->getShippingAddress();
                //Mage::log('Order-ID=' . $orderId . '  / Quote-ID=' . $lastActiveQuoteId, null, 'ids.shopform.log');
            } else {
                $lastActiveQuote = $session->getQuote();
                if ($lastActiveQuote == null) {
                    $lastActiveQuote = Mage::getSingleton('checkout/session')->loadCustomerQuote();
                }
                $shippingAddress = $lastActiveQuote->getShippingAddress();
                $methodCode      = $lastActiveQuote->getPayment()->getMethod();
            }

            $quote = $lastActiveQuote;
            $quoteItems = $lastActiveQuote->getAllVisibleItems();
            //Mage::log($quote, null, 'ids.shopform.log'); ATTENTION: very big output in logfile!!

            // Get order payment and save to IDS data:
            if ($methodCode) {
                $updateIdsCustomerData  = "UPDATE " . $idsTable;
                $updateIdsCustomerData .= " SET selected_payment = '" . $methodCode . "'";
                $updateIdsCustomerData .= " WHERE email LIKE ? and active = 1";
                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                $result = $writeConnection->query($updateIdsCustomerData, $email);
            }

            // Collecting delivery address data:
            if ($this->_shippingMode == 'Lieferung') {
                $customerTable = Mage::getSingleton('core/resource')->getTableName('customer_entity');
                $query = "SELECT * FROM " . $customerTable . " WHERE email LIKE '" . $email . "'";
                $queryResult = $readConnection->query($query);
                if ($queryResult->rowCount() > 0) {
                    foreach ($queryResult as $recordset) {
                        $accountId = $recordset['schrack_account_id'];
                    }
                }

                if ($accountId) {
                    $accountTable = Mage::getSingleton('core/resource')->getTableName('account');
                    $query = "SELECT * FROM " . $accountTable . " WHERE account_id LIKE " . $accountId;
                    $queryResult = $readConnection->query($query);
                    if ($queryResult->rowCount() > 0) {
                        foreach ($queryResult as $recordset) {
                            $shippingData['company_name'] = $recordset['name1'];
                        }
                    }
                }


                $street                   = $shippingAddress->getStreet();
                if (is_array($street)) {
                    $street = $street[0];
                }
                $shippingData['street']   = $street;
                $shippingData['postcode'] = $shippingAddress->getPostcode();
                $shippingData['city']     = $shippingAddress->getCity();
                $countryName              = $this->getCountry($shippingAddress->getCountry());
                $shippingData['country']  = $countryName;
                if ($shippingAddress->getName3()) {
                    $shippingData['contact_person'] = $shippingAddress->getName3();
                }
                if ($quote->getSchrackAddressPhone()) {
                    $shippingData['contact_phone'] = $quote->getSchrackAddressPhone();
                }

                //Mage::log($shippingData, null, 'ids.log');
            }

            $updateIdsCustomerData  = "UPDATE " . $idsTable;
            $updateIdsCustomerData .= " SET selected_shipping = '" . $this->_shippingMode . "'";
            $updateIdsCustomerData .= " WHERE email LIKE ? and active = 1";
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $result = $writeConnection->query($updateIdsCustomerData, $email);
        }

        foreach ($quoteItems as $item) {
            $readConnection      = Mage::getSingleton('core/resource')->getConnection('core_read');
            $customerPosition    = "";
            $customerSubPosition = "";
            $skuIndex            = null;
            $sku                 = $item->getSku();
            $product = array();
            $materials = array(
                "CU" => "schrack_cu-gew___kg_km_",
                "AL" => "schrack_algewicht",
            );

            foreach($materials as $k => $v) {
                $query = "SELECT prod.entity_id, prod.sku, attr.value FROM catalog_product_entity AS prod";
                $query .= " JOIN catalog_product_entity_varchar attr ON prod.entity_id = attr.entity_id";
                $query .= " WHERE prod.sku = '" . $sku . "' AND attr.attribute_id IN";
                $query .= " (SELECT attribute_id FROM eav_attribute";
                $query .= " WHERE entity_type_id = 4 AND attribute_code = '" . $v . "')";
                $queryResult = $readConnection->query($query);

                if ($queryResult->rowCount() > 0) {
                    foreach ($queryResult as $recordset) {
                        if ($recordset['value']) {
                            $metalInformation = Array(
                                "sku" => $sku,
                                "gewichtsanteilswert" =>$recordset['value'],
                                "material" => $k,
                                "isCableProduct" => 1
                            );
                            $product[] = $metalInformation;
                        }
                    }
                }
            }


            // Create special XML Structure, if article is a cable:
            for ($counter = 0; $counter < count($product); $counter++) {

                if ($product[$counter]['isCableProduct']) {
                    $metalSurchargesXml = '';
                    $gewichtsanteilseinheit = 'KGM';
                    $basiseinheit = 'MTR';
                    $basiswert = 100;
                    $metalInfoData = array();
                    $currentDELNotation = '';
                    $basisNotierung = array(
                        "AT" => array("CU" => 130, "AL" => 100),
                        "DE" => array("CU" => 100, "AL" => 100)
                    );
                    $country = strtoupper(Mage::getStoreConfig('schrack/general/country'));
                    $basisNotierungCountryData = $basisNotierung[$country];

                    Mage::log($sku . " is Cable = yes", null, 'get_surcharges.log');
                    $system = Mage::getStoreConfig('schrack/general/platform');
                    if ($system == 'LIVE') {
                        // WWS LIVE System
                        $url = 'http://seeuro1:8080/wsa/soap/wsdl?targetURI=urn:schrack:www2wws';
                        $countryCode = 'AT';
                    } else {
                        // WWS TEST System
                        $url = 'http://10.25.4.3:8080/wsa/soap/wsdl?targetURI=urn:schrack:www2wws';
                        $countryCode = 'test_AT';
                    }

                    try {
                        $client = new SoapClient($url, array("trace" => 1, "exception" => 0));
                        $result = $client->__soapCall("get_surcharges", array(
                            "get_surcharges" => array(
                                "ctry_code" => $countryCode,
                                "sender_id" => "p2n4shop2wws,TX=5458c4f8ae6b9",
                                "user_aut" => "p2n",
                                "user_pwd" => "uh18HGNFRNZT"
                            )
                        ));

                        //Mage::log($result, null, 'get_surcharges.log', false, false);


                        if (is_object($result)) {
                            $metalInfo = $result->tt_surcharges->tt_surchargesRow;
                            //Mage::log($metalInfo, null, 'get_surcharges.log', false, false);
                            if (is_array($metalInfo) && !empty($metalInfo)) {
                                foreach ($metalInfo as $index => $metalInfoObject) {
                                    $metalInfoData[$metalInfoObject->SurchargeID] = $metalInfoObject->Amount;
                                }
                            } else {
                                Mage::log("metalInfo for SKU = " . $sku . " not available", null, 'get_surcharges.err.log');
                            }
                        } else {
                            Mage::log("WWS Service for SKU = " . $sku . " not working as expected", null, 'get_surcharges.err.log');
                        }

                        //Mage::log($gewichtsanteilswert, null, 'get_surcharges.log', false, false);
                        //Mage::log($material, null, 'get_surcharges.log', false, false);
                        if (is_array($metalInfoData) && !empty($metalInfoData) && isset($metalInfoData[$product['material']])) {
                            $currentDELNotation = $metalInfoData[$product['material']] * $basiswert;
                            //Mage::log($metalInfoData[$material], null, 'get_surcharges.log', false, false);
                        }
                        $gewichtsanteilswert = str_replace('kg/km', '', $product[$counter]['gewichtsanteilswert']);
                        $gewichtsanteilswert = floatval(str_replace(',', '.', $gewichtsanteilswert));
                        $gewichtsanteilswert = $gewichtsanteilswert / 10; // Macht aus Kilometern dann 100 Meter Unit


                        $metalSurchargesXml .= '<Rohstoffanteil>'
                            . '<Rohstoff>' . $product[$counter]['material'] . '</Rohstoff>'
                            . '<Gewichtsanteilswert>' . $gewichtsanteilswert . '</Gewichtsanteilswert>'
                            . '<Basiseinheit>' . $basiseinheit . '</Basiseinheit>'
                            . '<Basiswert>' . $basiswert . '</Basiswert>'
                            . '<Gewichtsanteilseinheit>' . $gewichtsanteilseinheit . '</Gewichtsanteilseinheit>'
                            . '<NotierungAktuell>' . $currentDELNotation . '</NotierungAktuell>'
                            . '<Basisnotierung>' . $basisNotierungCountryData[$product[$counter]['material']] . '</Basisnotierung>'
                            . '</Rohstoffanteil>';

                        $metalSurchargesXmlList .= $metalSurchargesXml;

//                        $completeCartXML .= $metalSurchargesXml;
                    } catch (Exception $e) {
                        Mage::log($e->getMessage(), null, 'get_surcharges.err.log');
                    }
                } else {
                    Mage::log($sku . " is Cable = No", null, 'get_surcharges.log');
                    //Mage::log($item->getProduct(), null, 'get_surcharges.log');
                }
            }
            //------------------------------------------------ get quantity unit
            $schrackQtyUnit = $item->getProduct()->getData('schrack_qtyunit');
            //-------------- translate unit for ids Special Case #1 (Stk -> PCE)
            if ($schrackQtyUnit == "Stk") {
                $schrackQtyUnit = 'PCE';
            }
            //---------------- translate unit for ids Special Case #2 (m -> MTR)
            if ($schrackQtyUnit == "m") {
                $schrackQtyUnit = 'MTR';
            }
            //$priceFactor = $item->getQty() / $item->getProduct()->getData('schrack_priceunit');
            $unformattedPrice = str_replace( array(',', '.'), '',
                                             Mage::helper('schrackcheckout')->formatPrice( $item->getProduct(),
                                             number_format( $item->getRowTotal(), 2 ) ) );
            if ($item->getSchrackOfferReference()) {
                $offerNumber = null;
                if ($item->getSchrackOfferNumber()) {
                    $offerNumber = intval($item->getSchrackOfferNumber());
                    $this->_offerNumber = '<OfferNo>' . $offerNumber . '</OfferNo>';
                }
                $unformattedOfferPrice = $item->getSchrackOfferPricePerUnit();
                $surcharges_per_unit = 0;

                if ($item->getSchrackOfferSurcharge() > 0) {
                    $surcharges_per_unit = number_format($item->getSchrackOfferSurcharge(), 2);
                }
                $itemOfferPrice = number_format($unformattedOfferPrice, 2, '.', '') + $surcharges_per_unit;
                if (!stristr($itemOfferPrice, '.')) {
                    $itemOfferPrice .= '.00';
                }
                //------------------------------------------ Offer item quantity
                $itemQuantity = number_format($item->getQty(), 2, '.', '');
                //---------------------------------------------- Offer net price
                $netPrice = $itemOfferPrice;
                if (!stristr($netPrice, '.')) {
                    $netPrice .= '.00';
                }
            } else {
                // Normal net price (-> offerPrice not available, because it is no offer):
                $netPrice = substr_replace($unformattedPrice, '.', -2, 0);
            }
            if (is_array($idsCartPositionsFlipped) && !empty($idsCartPositionsFlipped)) {
                //Mage::log($idsCartPositionsFlipped, null, 'ids.product.log');
                //Mage::log($sku, null, 'ids.product.log');
                if (isset($idsCartPositionsFlipped[$sku])) {
                    $skuIndex = $idsCartPositionsFlipped[$sku];
                    Mage::log($skuIndex, null, 'ids.product.log');
                }
                Mage::log($idsCartNormal, null, 'ids.product.log');
                if (isset($idsCartNormal['Order']['OrderItem'][$skuIndex]['RefItems']['Customer'])) {
                    $customerPosition = $idsCartNormal['Order']['OrderItem'][$skuIndex]['RefItems']['Customer'];
                }
                if (isset($idsCartNormal['Order']['OrderItem'][$skuIndex]['RefItems']['CustomerSubNo'])) {
                    $customerSubPosition = $idsCartNormal['Order']['OrderItem'][$skuIndex]['RefItems']['CustomerSubNo'];
                }
            }

            $itemChara = 'normal';
            $completeCartXML .= '<OrderItem>';
//            $completeCartXML .= $metalSurchargesXmlList;
            if ($metalSurchargesXmlList) {
                $completeCartXML .= $metalSurchargesXmlList;
                $metalSurchargesXmlList     = '';
            }
            $completeCartXML .= '<ItemChara>' . $itemChara . '</ItemChara>';
            $completeCartXML .= '<RefItems>';
            if ($customerPosition) {
                $completeCartXML .= '<Customer>' . $customerPosition . '</Customer>';
            }
            if ($customerSubPosition) {
                $completeCartXML .= '<CustomerSubNo>' . $customerSubPosition . '</CustomerSubNo>';
            }
            $completeCartXML .= '<Supplier>' . $position . '</Supplier>';
            // -> gibt's bei uns nicht!! $completeCartXML .= '<SupplierSubNo></SupplierSubNo>';
            $completeCartXML .= '</RefItems>';


            $productHelper = Mage::helper('schrackcatalog/product');
            $priceProductInfo = $productHelper->getPriceProductInfo(array($sku));

           # Mage::log($priceProductInfo, null, "productPrice.log");

           # $listPrice = $priceProductInfo[$sku]['listprice']; // --> Listenpreis aus der STS
           # $listPrice = number_format($priceProductInfo[$sku]['listprice'], 2, '.', '');

            $listPriceTemp = (float)(str_replace(',', '.', $priceProductInfo[$sku]['listprice'])); // --> Listenpreis aus der STS
            $listPrice = $listPriceTemp;
            $offerPrice = (float)(str_replace(',', '.', $priceProductInfo[$sku]['price'])); // --> Listenpreis aus der STS
            # ListPRiceTemp already fomatted and is formatted again below -> error by two decimals
            #$listPrice = number_format($listPriceTemp, 2, '.', '');


            $availibilityProductInfo = $productHelper->getAvailibilityProductInfo(array($sku));
            if (isset($availibilityProductInfo[$sku]) && isset($availibilityProductInfo[$sku]['salesUnitQty'])) {
                $priceBase = $availibilityProductInfo[$sku]['salesUnitQty'];
            } else {
                $priceBase = $item->getQty();
            }
            if (isset($priceProductInfo[$sku]['qtyunit'])) {
                $listPriceUnitQty = $priceProductInfo[$sku]['qtyunit'];
                $netPriceUnitQty = $priceBase;
                $message = $sku . ' ---> Listenpreis-Basis (Menge) = ' . $listPriceUnitQty . ' / Nettopreis-Basis (Menge) = ' . $netPriceUnitQty;

                Mage::log($message, null, 'IDS_qty_unit.log');
            }


            //------------------------------------------------- Rabattberechnung
            $rabatt = 0.00; //--------------------------- Fallback in rare cases
            //------ calculate discount if list price is higher than netto price
            $zuschlag = number_format((($listPrice) - $offerPrice) / ($listPrice/100), 4, '.', '') * -1; // rabatt

            //------------------------------------------------------------------
            $completeCartXML .= '<ArtNo>' . $sku . '</ArtNo>'
                .  '<Qty>' . $item->getQty() . '</Qty>'
                .  '<QU>' . $schrackQtyUnit . '</QU>'
                .  '<PriceBasis>' . $priceProductInfo[$sku]['priceunit'] . '</PriceBasis>'
//                .  '<CuttingCosts>' . $priceProductInfo[$sku]['cuttingcosts'] .'</CuttingCosts>'
//                .  '<Surcharge>' . $priceProductInfo[$sku]['surcharge'] .'</Surcharge>'
                .  '<Kurztext>' . substr($this->cleanString($item->getName()), 0, 99) . '</Kurztext>'
                //$completeCartXML .= '<Langtext>' . $this->cleanString($item->getSchrackLongTextAddition()) . '</Langtext>';
//                .  '<ListPrice>' . $listPrice .'</ListPrice>'
                .  '<NetPrice>' . $netPrice .'</NetPrice>'
                .  '<OfferPrice>' . $offerPrice .'</OfferPrice>'
//                .  '<PricePlusSurcharge>' . $priceProductInfo[$sku]['priceplussurcharge'] .'</PricePlusSurcharge>'
                .  '<Zuschlag>' . $zuschlag .'</Zuschlag>'
                .  '</OrderItem>';
            //------------------------------------------------------------------
            $position++;
        }
        //------------------------------------- check for existing shipping data
        if (is_array($shippingData) && !empty($shippingData)) {
            //-------------------------------------- add contact_person if found
            $contactPerson = '';
            if (isset($shippingData['contact_person'])) {
                $contactPerson = '<Contact>' . $shippingData['contact_person'] . '</Contact>';
            }
            //--------------------------------------- add contact_phone if found
            $contactPhone = '';
            if (isset($shippingData['contact_phone'])) {
                $contactPhone = '<Phone>' . $shippingData['contact_phone'] . '</Phone>';
            }
            //------------------------------------------------------------------
            $completeCartXML .= '<DeliveryPlaceInfo>'
                             .     '<Address>'
                             .        '<Name1>' . $shippingData['company_name'] . '</Name1>'
                             .        '<Street>' . $shippingData['street'] . '</Street>'
                             .        '<PCode>' . $shippingData['postcode'] . '</PCode>'
                             .        '<City>' . $shippingData['city'] . '</City>'
                             .        '<Country>' . $shippingData['country'] . '</Country>'
                             .        $contactPerson
                             .        $contactPhone
                             .    '</Address>'
                             .  '</DeliveryPlaceInfo>';
        }
        //--------------------------------------------------------------- RETURN
        return $completeCartXML;
    } //=========================================== getCompleteCartXML ***END***



    public function getTarget() {
		if (isset($this->session['ids']['returntarget']) && !empty($this->session['ids']['returntarget'])) {
			return $this->session['ids']['returntarget'];
        }

		return false;
	}


	public function externalArticlesExists() {
        $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $idsTable = Mage::getSingleton('core/resource')->getTableName('schrack_ids_data');
        $query = "SELECT * FROM " . $idsTable . " WHERE email LIKE '" . $email . "' and active = 1";
        $queryResult = $readConnection->query($query);
        if ($queryResult->rowCount() > 0) {
            foreach ($queryResult as $recordset) {
                if ($recordset['cart_extra']) {
                    $this->_idsCartExtra = unserialize($recordset['cart_extra']);
                }
            }
        }

        if ($this->_idsCartExtra) {
            return true;
        } else {
            return false;
        }
	}


    public function getExternalArticlesHTML() {
        $arrIdsCartExtra = $this->_idsCartExtra;

        if (is_array($arrIdsCartExtra) && !empty($arrIdsCartExtra)) {
            $completeExternalCartHTML = '<hr><h3>' . $this->__('External Articles Not Found In Schrack-Shop') . ':</h3><hr>';
            foreach ($arrIdsCartExtra['Order']['OrderItem'] as $shopPosNumber => $recordset) {
                $completeExternalCartHTML .= '<div class="ext_product_item">';
                $completeExternalCartHTML .= '<div style="width: 60%; float: left;">';
                // Single external product:
                if (isset($recordset['ArtNo'])) {
                    $completeExternalCartHTML .= '<div>SKU : ' . $recordset['ArtNo'] . '</div>';
                }
                if (isset($recordset['Qty'])) {
                    $completeExternalCartHTML .= '<div>QTY : ' . $recordset['Qty'] . '</div>';
                }
                if (isset($recordset['QU'])) {
                    $completeExternalCartHTML .= '<div>QU : ' . $recordset['QU'] . '</div>';
                }
                if (isset($recordset['Kurztext'])) {
                    $completeExternalCartHTML .= '<div>Short-Name : ' . $recordset['Kurztext'] . '</div>';
                }
                if (isset($recordset['Langtext'])) {
                    $completeExternalCartHTML .= '<div>Long-Name : ' . $recordset['Langtext'] . '</div>';
                }
                if (isset($recordset['NetPrice'])) {
                    $completeExternalCartHTML .= '<div>Price : ' . $recordset['NetPrice'] . '</div>';
                }
                $pos = '';
                if (isset($recordset['RefItems']['Customer'])) {
                    $pos = $recordset['RefItems']['Customer'];
                    $completeExternalCartHTML .= '<div>Position : ' . $pos . '</div>';
                }
                $subPos = '';
                if (isset($recordset['RefItems']['CustomerSubNo'])) {
                    $subPos = $recordset['RefItems']['CustomerSubNo'];
                    $completeExternalCartHTML .= '<div>Sub-Position : ' . $subPos . '</div>';
                }
                $completeExternalCartHTML .= '</div>';
                $totalPos = '';
                if ($pos && $subPos) {
                    $totalPos = $pos . '-' . $subPos;
                }
                if ($pos && $subPos == '') {
                    $totalPos = $pos;
                }

                $id = 'id="old_sku-' . $totalPos . '"';
                $value = 'value="' . $recordset['ArtNo'] . '"';
                $completeExternalCartHTML .= '<input type="hidden"' .$id . $value . '>';

                if ($totalPos) {
                    $completeExternalCartHTML .= '<div style="width: 20%; float: left;">';
                    $completeExternalCartHTML .= '  <div style="margin-top: 35px;">';
                    $attr1 = 'id="search-totalpos-' . $totalPos . '"';
                    $attr1 .= ' autocomplete="off"';
                    $attr1 .= ' class="replaceWKSArticleSku" data-pos="totalpos-' . $totalPos . '"';
                    $completeExternalCartHTML .= '    <input type="text" ' . $attr1 . '>';
                    $completeExternalCartHTML .= '  </div>';

                    $completeExternalCartHTML .= '  <div class="autocomplete-listitems">';
                    $idAttr = 'id="autocomplete-totalpos-' . $totalPos . '"';
                    $completeExternalCartHTML .= '  <ul ' . $idAttr . ' class="autocomplete-listitem-container"></ul>';
                    $completeExternalCartHTML .= '  </div>';

                    $completeExternalCartHTML .= '</div>';
                }

                if ($totalPos) {
                    $completeExternalCartHTML .= '<div style="width: 20%; float: left;">';
                    $completeExternalCartHTML .= '  <div style="margin-top: 35px;">';
                    $attr2  = 'class="replaceExternalWKSArticle replace_button_inactive"';
                    $attr2 .= ' disabled="disabled"';
                    $attr2 .= ' id="button-totalpos-' . $totalPos . '"';
                    $attr2 .= ' data-pos="' . $totalPos . '"';
                    $completeExternalCartHTML .= '    <button type="button" ' . $attr2 . '>';
                    $completeExternalCartHTML .=  $this->__('Replace');
                    $completeExternalCartHTML .= '    </button>';
                    $completeExternalCartHTML .= '  </div>';
                    $completeExternalCartHTML .= '</div>';
                }

                $completeExternalCartHTML .= '<div style="clear: both;">';
                $completeExternalCartHTML .= '</div>';

                $completeExternalCartHTML .= '</div>';
            }

            return $completeExternalCartHTML;
        }
    }


    public function getIdsDeliveryAddressData() {
        $email               = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        $readConnection      = Mage::getSingleton('core/resource')->getConnection('core_read');
        $idsTable            = Mage::getSingleton('core/resource')->getTableName('schrack_ids_data');
        $deliveryAddressData = null;
        $mockData            = false; // Default = false

        if ($mockData == true) {
            $mockAddressData = $this->getMockedAdressData();
            $query  = "UPDATE " . $idsTable . " SET delivery_address = '" . serialize($mockAddressData) . "'";
            $query .= " WHERE email LIKE '" . $email . "' and active = 1";
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            //Mage::log($query, null, "ids.log");
            $queryResult = $writeConnection->query($query);
        }

        $query = "SELECT delivery_address FROM " . $idsTable . " WHERE email LIKE '" . $email . "' and active = 1";
        $queryResult = $readConnection->query($query);
        if ($queryResult->rowCount() > 0) {
            foreach ($queryResult as $recordset) {
                $deliveryAddressDataRaw = unserialize($recordset['delivery_address']);
                if (is_array($deliveryAddressDataRaw) && !empty($deliveryAddressDataRaw)) {
                    if (isset($deliveryAddressDataRaw['DeliveryPlaceInfo'])) {
                        $deliveryAddressData = $deliveryAddressDataRaw['DeliveryPlaceInfo'];
                    }
                }
            }
        }

        return $deliveryAddressData;
    }


    public function getExternalOrderNumber() {
        $email               = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        $readConnection      = Mage::getSingleton('core/resource')->getConnection('core_read');
        $idsTable            = Mage::getSingleton('core/resource')->getTableName('schrack_ids_data');
        $externalOrderNumber = null;

        $query = "SELECT * FROM " . $idsTable . " WHERE email LIKE '" . $email . "' and active = 1";
        $queryResult = $readConnection->query($query);
        if ($queryResult->rowCount() > 0) {
            foreach ($queryResult as $recordset) {
                if ($recordset['external_ordernumber']) {
                    $externalOrderNumber = $recordset['external_ordernumber'];
                }
            }
        }

        return $externalOrderNumber;
    }


    public function getCountry($code) {
        $code = strtoupper($code);

        $countryList = array(
            'AF' => 'Afghanistan',
            'AX' => 'Aland Islands',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas the',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BV' => 'Bouvet Island (Bouvetoya)',
            'BR' => 'Brazil',
            'IO' => 'British Indian Ocean Territory (Chagos Archipelago)',
            'VG' => 'British Virgin Islands',
            'BN' => 'Brunei Darussalam',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos (Keeling) Islands',
            'CO' => 'Colombia',
            'KM' => 'Comoros the',
            'CD' => 'Congo',
            'CG' => 'Congo the',
            'CK' => 'Cook Islands',
            'CR' => 'Costa Rica',
            'CI' => 'Cote d\'Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FO' => 'Faroe Islands',
            'FK' => 'Falkland Islands (Malvinas)',
            'FJ' => 'Fiji the Fiji Islands',
            'FI' => 'Finland',
            'FR' => 'France, French Republic',
            'GF' => 'French Guiana',
            'PF' => 'French Polynesia',
            'TF' => 'French Southern Territories',
            'GA' => 'Gabon',
            'GM' => 'Gambia the',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GG' => 'Guernsey',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HM' => 'Heard Island and McDonald Islands',
            'VA' => 'Holy See (Vatican City State)',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IM' => 'Isle of Man',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JE' => 'Jersey',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KP' => 'Korea',
            'KR' => 'Korea',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyz Republic',
            'LA' => 'Lao',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libyan Arab Jamahiriya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MK' => 'Macedonia',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'YT' => 'Mayotte',
            'MX' => 'Mexico',
            'FM' => 'Micronesia',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MS' => 'Montserrat',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'AN' => 'Netherlands Antilles',
            'NL' => 'Netherlands the',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestinian Territory',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PN' => 'Pitcairn Islands',
            'PL' => 'Poland',
            'PT' => 'Portugal, Portuguese Republic',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RE' => 'Reunion',
            'RO' => 'Romania',
            'RU' => 'Russian Federation',
            'RW' => 'Rwanda',
            'BL' => 'Saint Barthelemy',
            'SH' => 'Saint Helena',
            'KN' => 'Saint Kitts and Nevis',
            'LC' => 'Saint Lucia',
            'MF' => 'Saint Martin',
            'PM' => 'Saint Pierre and Miquelon',
            'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome and Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia (Slovak Republic)',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia, Somali Republic',
            'ZA' => 'South Africa',
            'GS' => 'South Georgia and the South Sandwich Islands',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard & Jan Mayen Islands',
            'SZ' => 'Swaziland',
            'SE' => 'Sweden',
            'CH' => 'Switzerland, Swiss Confederation',
            'SY' => 'Syrian Arab Republic',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TC' => 'Turks and Caicos Islands',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States of America',
            'UM' => 'United States Minor Outlying Islands',
            'VI' => 'United States Virgin Islands',
            'UY' => 'Uruguay, Eastern Republic of',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'WF' => 'Wallis and Futuna',
            'EH' => 'Western Sahara',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe'
        );

        if (!$countryList[$code]) {
            return $code;
        } else {
            return $countryList[$code];
        }
    }


    private function getMockedAdressData() {
        $arrAddressData = array();
        $arrAddressData['DeliveryPlaceInfo']['company_name']   = 'test_company_name';
        $arrAddressData['DeliveryPlaceInfo']['company_name2']  = 'test_company_name2';
        $arrAddressData['DeliveryPlaceInfo']['street']         = 'test_street';
        $arrAddressData['DeliveryPlaceInfo']['postcode']       = '12345';
        $arrAddressData['DeliveryPlaceInfo']['city']           = 'Berlin';
        $arrAddressData['DeliveryPlaceInfo']['country']        = strtoupper(Mage::getStoreConfig('schrack/general/country'));
        $arrAddressData['DeliveryPlaceInfo']['contact_person'] = 'test_contact_person';
        $arrAddressData['DeliveryPlaceInfo']['contact_phone']  = 'test_contact_phone';
        return $arrAddressData;
    }


	public function cleanString($data) {
		$data = preg_replace('/<br\s*\/?>/i', "\n", $data);
		$data = str_replace("&nbsp;"," ",$data);
		$data = str_replace("&uuml;","ü",$data);
		$data = str_replace("&Uuml;","Ü",$data);
		$data = str_replace("&auml;","ä",$data);
		$data = str_replace("&Auml;","Ä",$data);
		$data = str_replace("&ouml;","ö",$data);
		$data = str_replace("&Ouml;","Ö",$data);
		$data = str_replace("&szlig;","ß",$data);

		$data = $this->stripTags($data, null, true);
		// $data = utf8_encode($data);
		
		return $data;
	}
	
	
	public function isIdsSession() {
        if (intval(Mage::getStoreConfig('ids/active/state')) == 1) {
            $session = Mage::getSingleton('customer/session');
            if (strlen(Mage::getStoreConfig('ids/always_active_for_testing/email')) > 1) {
                $email = $session->getCustomer()->getEmail();
                $testEmail = Mage::getStoreConfig('ids/always_active_for_testing/email');
                if ($email && $testEmail && $email == $testEmail) {
                    return true;
                }
            }
            if (isset($session['ids']['hookurl']) && !empty($session['ids']['hookurl'])) {
                return true;
            }

            return false;
        } else {
            return false;
        }
    }


    public function isWKSAction() {
        if ($this->isIdsSession()) {
            $session = Mage::getSingleton('customer/session');
            $email = $session->getCustomer()->getEmail();

            $query = "SELECT current_action FROM schrack_ids_data WHERE email LIKE '" . $email . "' and active = 1";
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $currentAction = $readConnection->fetchOne($query);
            if ($currentAction == 'WKS') {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    public function prepareString($string) {
        $string = strip_tags($string);
        $string = html_entity_decode($string);
        $quotes = array('"', "'");
        $string = str_replace($quotes, '', $string);

        // $string = utf8_encode($string);
        if (Mage::getStoreConfig('ids/configuration/transliteration')){
            setlocale(LC_CTYPE, 'de_DE');
            $string = iconv('UTF-8', 'UTF-8//TRANSLIT', $string);
        }

        return $string;
    }



}
