<?php
/**
 *
 * @package	Orcamultimedia_Ids
 *
 **/
require_once "BaseController.php";
class Orcamultimedia_Ids_PunchinController extends Orcamultimedia_Ids_BaseController
{
    private $_customer;
    private $_hookurl;

    public function loginAction()
    {
        $session = $this->_getSession();
        $ids = $this->_setIdsParams();

        if (Mage::getStoreConfig('ids/configuration/logging')) {
            Mage::log(array('GET', $_GET), null, 'ids.get.log');
            Mage::log(array('POST', $_POST), null, 'ids.post.log');
            Mage::log(array('IDS', $ids), null, 'ids.log');
        }

        if ($session->isLoggedIn()) {
            if (Mage::getStoreConfig('ids/configuration/logging')) {
                Mage::log('Is already logged in.', null, 'ids.log');
            }
            $session->setData('ids', $ids);
        }
        $idsRequestData = $this->getRequest()->getParams();
        unset($idsRequestData['pw_kunde']);
        Mage::log($idsRequestData, null, 'ids_request.log', false, false);

        $data['action']   = $this->getRequest()->getParam('action');
        $data['wwsCid']   = $this->getRequest()->getParam('kndnr');
        $data['username'] = $this->getRequest()->getParam('name_kunde');
        $data['sku']      = $this->getRequest()->getParam('ghnummer');
        $password         = $this->getRequest()->getParam('pw_kunde');
        $data['hookurl']  = $this->getRequest()->getParam('hookurl');
        $data['xmlcart']  = $this->getRequest()->getParam('warenkorb');

        if (!empty($data['username']) && !empty($password)) {
            try {
                $session->setData('ids', $ids);
                $session->login($data['username'], $password);
            } catch (Mage_Core_Exception $e) {
                if (Mage::getStoreConfig('ids/configuration/logging'))
                    Mage::log('Login Error.', null, 'ids.log');
                switch ($e->getCode()) {
                    case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                        $value = $this->_getHelper('customer')->getEmailConfirmationUrl($data['username']);
                        $message = $this->_getHelper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                        break;
                    case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                        $message = $e->getMessage();
                        break;
                    default:
                        $message = $e->getMessage();
                }
                $session->addError($message);
                Mage::log($message, null, 'ids.error.log');
                $session->setUsername($data['username']);
            } catch (Exception $e) {
                // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                Mage::log($e->getMessage(), null, 'ids.error.log');
            }
        } else {
            $session->addError($this->__('Login and password are required.'));
            Mage::log('Login and password are required.', null, 'ids.error.log');
        }

        if ($data['action']) {
            $this->_loginPostActions($data);
        } else {
            Mage::log('no action given (WSE, WKE, ADL, etc.).', null, 'ids.error.log');
            echo 'missing action (WSE, WKE, ADL, etc.)';
            die();
        }
    }


    protected function _loginPostActions($idsData)
    {
        $readConnection  = Mage::getSingleton('core/resource')->getConnection('core_read');
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

        $action = $idsData['action'];

        $xmlHead = '<?xml version="1.0" encoding="UTF-8"?>';

        if ($action == 'LI') {
            $xmlBody  = '<Logininformationen>';
            $xmlBody .= '<Kundennummer_erforderlich>true</Kundennummer_erforderlich>';
            $xmlBody .= '<Benutzername_erforderlich>true</Benutzername_erforderlich>';
            $xmlBody .= '<Passwort_erforderlich>true</Passwort_erforderlich>';
            $xmlBody .= '</Logininformationen>';

            $xml = $xmlHead . $xmlBody;
            header("Content-type: text/xml");

            echo $xml;
            die();
        }

        if ($action == 'SV') {
            $xmlBody  = '<Schnittstellenversionen>';
            $xmlBody .= '<Version>2.0</Version>';
            $xmlBody .= '<Version>2.1</Version>';
            $xmlBody .= '<Version>2.2</Version>';
            $xmlBody .= '<Version>2.3</Version>';
            $xmlBody .= '<Version>2.5</Version>';
            $xmlBody .= '</Schnittstellenversionen>';

            $xml = $xmlHead . $xmlBody;
            header("Content-type: text/xml");

            echo $xml;
            die();
        }

        if ($action == 'ADL') {
            $sku = $idsData['sku'];
            $p = Mage::getModel('catalog/product')->loadBySku($sku);
            if (is_object($p) && $p->getId()) {
                $this->_redirectUrl($p->getProductUrl());
                return;
            }
        }

        if ($action == 'WKE' || $action == 'WKS') {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if ($quote) {
                $quoteId= $quote->getId();
                if ($quoteId) {
                    $query = "DELETE FROM sales_flat_quote_item WHERE `quote_id` = " . $quoteId;
                    $writeConnection->query($query);
                    $query = "UPDATE sales_flat_quote SET items_count = 0, items_qty = 0 WHERE entity_id = " . $quoteId;
                    $writeConnection->query($query);
                }
            }

            $cart = Mage::getSingleton('checkout/cart');
            $cart->truncate();
            $cart->init();
            
            Mage::log('Cart has been truncated!', null, 'ids.log');

            $wwsCustomerId = '';
            $email         = '';

            if (isset($idsData['wwsCid']) && !empty($idsData['wwsCid'])) {
                $wwsCustomerId = $idsData['wwsCid'];
            } else {
                $errMsg = 'kndnr (= WWS Customer ID) was not found -> invalid credentials';
                Mage::log($errMsg, null, 'ids.error.log');
                echo $errMsg;
                die();
            }

            if (isset($idsData['username']) && !empty($idsData['username'])) {
                $email = $idsData['username'];
                $this->_customer = Mage::getModel('customer/customer')->loadByEmail($email);
            } else {
                $errMsg = 'name_kunde (= email address) was not found -> invalid credentials';
                Mage::log($errMsg, null, 'ids.error.log');
                echo $errMsg;
                die();
            }

            if (isset($idsData['hookurl']) && !empty($idsData['hookurl'])) {
                $this->_hookurl = $idsData['hookurl'];
                $session = $this->_getSession();
                $idsSessionData = $session->getData('ids');
                $idsSessionData['ids']['hookurl'] = $this->_hookurl;
                $session->setData('ids', $idsSessionData);
            } else {
                $errMsg = 'hookurl was not found -> invalid credentials';
                Mage::log($errMsg, null, 'ids.error.log');
                echo $errMsg;
                die();
            }

            $idsTable = Mage::getSingleton('core/resource')->getTableName('schrack_ids_data');
            $query = "UPDATE " . $idsTable . " SET active = 0 WHERE email = :email";
            $binds = array(
                'email' => $email,
            );
            $result = $writeConnection->query($query, $binds);

            $query  = "INSERT INTO " . $idsTable . " SET active = 1, ";
            $query .= " created_at = '" . date("Y-m-d H:i:s") . "',";
            $query .= " wws_customer_id = :wws_customer_id, email = :email, current_action = :action, hookurl = :hookurl";
            $binds = array(
                'wws_customer_id' => $wwsCustomerId,
                'email' => $email,
                'action' => $action,
                'hookurl' => $this->_hookurl
            );
            $result = $writeConnection->query($query, $binds);
        }

        if ($action == 'WKS') {
            if (isset($idsData['xmlcart']) && !empty($idsData['xmlcart'])) {
                $xml = simplexml_load_string($idsData['xmlcart'], "SimpleXMLElement", LIBXML_NOCDATA);
                if ($xml === false) {
                    foreach(libxml_get_errors() as $error) {
                        Mage::log("Failed loading XML: ", null, 'ids.error.log');
                        Mage::log($error->message, null, 'ids.error.log');
                    }
                } else {
                    $json = json_encode($xml);
                    $arrIDSCart = json_decode($json,true);
                    $ModeOfShipment = '';
                    if (isset($arrIDSCart['Order']['OrderInfo']['ModeOfShipment'])
                        && !empty($arrIDSCart['Order']['OrderInfo']['Cur'])) {
                        $ModeOfShipmentRaw = $arrIDSCart['Order']['OrderInfo']['ModeOfShipment'];
                        if ($ModeOfShipmentRaw == 'Lieferung') {
                            $ModeOfShipment = 'Lieferung';
                        }
                        if ($ModeOfShipmentRaw == 'Abholung') {
                            $ModeOfShipment = 'Abholung';
                        }

                        $updateIdsCustomerData  = "UPDATE " . $idsTable;
                        $updateIdsCustomerData .= " SET selected_shipping = '" . $ModeOfShipment . "'";
                        $updateIdsCustomerData .= " WHERE email LIKE ? and active = 1";
                        $result = $writeConnection->query($updateIdsCustomerData, $email);
                    }
                    $Currency = Mage::getStoreConfig('currency/options/base');
                    if (isset($arrIDSCart['Order']['OrderInfo']['Cur'])
                        && !empty($arrIDSCart['Order']['OrderInfo']['Cur'])) {
                        $Currency = $arrIDSCart['Order']['OrderInfo']['Cur'];
                    }
                    if ($Currency != Mage::getStoreConfig('currency/options/base')) {
                        Mage::log("Local currency doesn't match received ids currency (" . $Currency . ")", null, 'ids.error.log');
                        $Currency = Mage::getStoreConfig('currency/options/base');
                    }
                    // Case: only one product given:
                    if (isset($arrIDSCart['Order']['OrderItem']['RefItems']['CustomerSubNo'])
                        && $arrIDSCart['Order']['OrderItem']['RefItems']['CustomerSubNo']) {
                        //Mage::log("Sub-Positions are not supported", null, 'ids.error.log');
                        //echo "Sub-Positions are not supported (Unterpositionen werden nicht unterstützt)";
                        //die();
                    }

                    if (isset($arrIDSCart['WarenkorbInfo']['Version'])
                        && !empty($arrIDSCart['WarenkorbInfo']['Version'])) {
                        $externalVersion = (string) $arrIDSCart['WarenkorbInfo']['Version'];
                        $updateIdsCustomerData  = "UPDATE " . $idsTable;
                        $updateIdsCustomerData .= " SET external_version = '" . $externalVersion . "'";
                        $updateIdsCustomerData .= " WHERE email LIKE ? and active = 1";
                        $result = $writeConnection->query($updateIdsCustomerData, $email);
                    }

                    if (isset($arrIDSCart['Order']['OrderInfo']['PartNo'])
                        && !empty($arrIDSCart['Order']['OrderInfo']['PartNo'])) {
                        $externalOrderNumber = $arrIDSCart['Order']['OrderInfo']['PartNo'];
                        $updateIdsCustomerData  = "UPDATE " . $idsTable;
                        $updateIdsCustomerData .= " SET external_ordernumber = '" . $externalOrderNumber . "'";
                        $updateIdsCustomerData .= " WHERE email LIKE ? and active = 1";
                        $result = $writeConnection->query($updateIdsCustomerData, $email);
                    }
                }

                $cartProducts = $arrIDSCart['Order']['OrderItem'];

                $arrayInternalProducts = array();
                $arrayExternalProducts = array();
                $arrayAllPositions     = array();

                Mage::log($cartProducts, null, 'ids.product.log');
                $sku = '';
                if (is_array($cartProducts) && !empty($cartProducts)) {
                    // Only one single product:
                    if (isset($cartProducts['ArtNo'])) {
                        $idsProduct = $cartProducts;
                        $sku = $idsProduct['ArtNo'];
                        $qty = $idsProduct['Qty'];
                        Mage::log('Single SKU = ' . $sku, null, 'ids.product.log');
                        $findArticle = "SELECT * FROM catalog_product_entity WHERE sku LIKE ?";
                        $queryResult = $readConnection->fetchOne($findArticle, $sku);
                        if ($queryResult > 0) {
                            $productModel = Mage::getModel('catalog/product');
                            $product = $productModel->loadBySku($sku);
                            $product_id = $product->getId();
                            $productHelper = Mage::helper('schrackcatalog/product');
                            $priceRes = $productHelper->getPriceProductInfo(array($sku), array($qty));
                            $product->setPrice($priceRes[$sku]['price']);
                            if ( $product->isBestellartikel() ) {
                                $resultQtyData = $product->calculateClosestHigherQuantityAndDifference(intval($qty));
                                if (is_array($resultQtyData) && !empty($resultQtyData)) {
                                    $closestHigherQuantity = $resultQtyData['closestHigherQuantity'];
                                    $qty = $closestHigherQuantity;
                                }
                            } else {
                                $resultQtyData = $product->calculateClosestHigherQuantityAndDifference($qty, true, array(), 'addCartQuantity3');
                                if ( $resultQtyData['invalidQuantity'] == true ) {
                                    $qty = $resultQtyData['closestHigherQuantity'];
                                }
                            }
                            $params = array(
                                'product' => $product_id,
                                'qty' => $qty
                            );
                            // $checkoutHelper = Mage::helper('schrackcheckout/cart'); // Todo:
                            if ($product->isSalable() && $product->isWebshopsaleable() == true) {
                                //$checkAddToCartResult = $checkoutHelper->checkAddToCart($product, $params); // Todo:
                            }
                            $arrayInternalProducts['Order']['OrderItem'][0] = $idsProduct;
                            Mage::log($product->getSku() . ' -> ' . $product->getPrice(), null, 'ids.product.log');
                            $cart->addProduct($product, $params);
                        } else {
                            $arrayExternalProducts['Order']['OrderItem'][0] = $idsProduct;
                        }
                        $arrayAllPositions[0] = $sku;
                    } else {
                        // Multiple products:
                        Mage::log('One of Multiple SKU = ' . $sku, null, 'ids.product.log');
                        foreach ($cartProducts as $index => $idsProduct) {
                            if (isset($idsProduct['RefItems']['CustomerSubNo'])
                                && $idsProduct['RefItems']['CustomerSubNo']) {
                                //Mage::log("Sub-Positions are not supported", null, 'ids.error.log');
                                //echo "Sub-Positions are not supported (Unterpositionen werden nicht unterstützt)";
                                //die();
                            }

                            $sku = $idsProduct['ArtNo'];
                            $qty = $idsProduct['Qty'];

                            $findArticle = "SELECT * FROM catalog_product_entity WHERE sku LIKE ?";
                            $queryResult = $readConnection->fetchOne($findArticle, $sku);
                            if ($queryResult > 0) {
                                $productModel = Mage::getModel('catalog/product');
                                $product = $productModel->loadBySku($sku);
                                $product_id = $product->getId();
                                $productHelper = Mage::helper('schrackcatalog/product');
                                $priceRes = $productHelper->getPriceProductInfo(array($sku), array($qty));
                                $product->setPrice($priceRes[$sku]['price']);
                                if ( $product->isBestellartikel() ) {
                                    $resultQtyData = $product->calculateClosestHigherQuantityAndDifference(intval($qty));
                                    if (is_array($resultQtyData) && !empty($resultQtyData)) {
                                        $closestHigherQuantity = $resultQtyData['closestHigherQuantity'];
                                        $qty = $closestHigherQuantity;
                                    }
                                } else {
                                    $resultQtyData = $product->calculateClosestHigherQuantityAndDifference($qty, true, array(), 'addCartQuantity3');
                                    if ( $resultQtyData['invalidQuantity'] == true ) {
                                        $qty = $resultQtyData['closestHigherQuantity'];
                                    }
                                }
                                $params = array(
                                    'product' => $product_id,
                                    'qty' => $qty
                                );
                                // $checkoutHelper = Mage::helper('schrackcheckout/cart'); // Todo:
                                if ($product->isSalable() && $product->isWebshopsaleable() == true) {
                                    //$checkAddToCartResult = $checkoutHelper->checkAddToCart($product, $params); // Todo:
                                }
                                $arrayInternalProducts['Order']['OrderItem'][$index] = $idsProduct;
                                Mage::log($product->getSku() . ' -> ' . $product->getPrice(), null, 'ids.product.log');
                                $cart->addProduct($product, $params);
                            } else {
                                $arrayExternalProducts['Order']['OrderItem'][$index] = $idsProduct;
                            }
                            $arrayAllPositions[$index] = $sku;
                        }
                    }
                    $cart->save();
                    $quote = Mage::getModel('checkout/session')->getQuote();
                    $quote->collectTotals()->save();

                    if (!empty($arrayInternalProducts)) {
                        $updateIdsCustomerData  = "UPDATE " . $idsTable;
                        $updateIdsCustomerData .= " SET cart_normal = '" . serialize($arrayInternalProducts) . "'";
                        $updateIdsCustomerData .= " WHERE email LIKE ? and active = 1";
                        $result = $writeConnection->query($updateIdsCustomerData, $email);
                    }

                    if (!empty($arrayExternalProducts)) {
                        $updateIdsCustomerData  = "UPDATE " . $idsTable;
                        $updateIdsCustomerData .= " SET cart_extra = '" . serialize($arrayExternalProducts) . "'";
                        $updateIdsCustomerData .= " WHERE email LIKE ? and active = 1";
                        $result = $writeConnection->query($updateIdsCustomerData, $email);
                    }

                    if (!empty($arrayAllPositions)) {
                        $updateIdsCustomerData  = "UPDATE " . $idsTable;
                        $updateIdsCustomerData .= " SET ids_wks_positions = '" . serialize($arrayAllPositions) . "'";
                        $updateIdsCustomerData .= " WHERE email LIKE ? and active = 1";
                        $result = $writeConnection->query($updateIdsCustomerData, $email);
                    }

                    $deliveryAddress = array();
                    if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Street']) &&
                        !empty($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Street'])) {
                        if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Name1'])) {
                            $deliveryAddress['DeliveryPlaceInfo']['company_name'] = $arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Name1'];
                        }
                        if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Name2'])) {
                            $deliveryAddress['DeliveryPlaceInfo']['company_name2'] = $arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Name2'];
                        }
                        if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Street'])) {
                            $deliveryAddress['DeliveryPlaceInfo']['street'] = $arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Street'];
                        }
                        if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['PCode'])) {
                            $deliveryAddress['DeliveryPlaceInfo']['postcode'] = $arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['PCode'];
                        }
                        if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['City'])) {
                            $deliveryAddress['DeliveryPlaceInfo']['city'] = $arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['City'];
                        }
                        if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Country'])) {
                            $deliveryAddress['DeliveryPlaceInfo']['country'] = $arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Country'];

                        }
                        if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Contact'])) {
                            $deliveryAddress['DeliveryPlaceInfo']['contact_person'] = $arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Contact'];
                        }
                        if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Phone'])) {
                            $deliveryAddress['DeliveryPlaceInfo']['contact_phone'] = $arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Phone'];
                        }
                        if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['ILN'])) {
                            $deliveryAddress['DeliveryPlaceInfo']['iln'] = $arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['ILN'];
                        }
                        if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Fax'])) {
                            $deliveryAddress['DeliveryPlaceInfo']['fax'] = $arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Fax'];
                        }
                        if (isset($arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Email'])) {
                            $deliveryAddress['DeliveryPlaceInfo']['email'] = $arrIDSCart['Order']['DeliveryPlaceInfo']['Address']['Email'];
                        }
                    }

                    if (is_array($deliveryAddress) && !empty($deliveryAddress)) {
                        // Write address from WKS into database, if available:
                        $updateIdsCustomerData  = "UPDATE " . $idsTable;
                        $updateIdsCustomerData .= " SET delivery_address = '" . serialize($deliveryAddress) . "'";
                        $updateIdsCustomerData .= " WHERE email LIKE ? and active = 1";
                        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                        $result = $writeConnection->query($updateIdsCustomerData, $email);
                    }
                } else {
                    Mage::log("No articles from IDS received", null, 'ids.error.log');
                }
            }
        }

        if (Mage::getStoreConfig('ids/configuration/logging')) {
            //Mage::log(, null, 'ids.log');
        }

        $this->_redirect('checkout/cart');
    }

    public function getSkuReplacementsAction() {
        if ($this->_validateFormKey()) {
            $searchStringSku = $this->getRequest()->getParam('search_string');
            //Mage::log($searchStringSku, null, 'ids.log');
            $response = array();
            $index = 0;
            $limit = 10;

            $session = Mage::getSingleton('customer/session');
            $email = $session->getCustomer()->getEmail();

            $query = "SELECT ids_wks_positions FROM schrack_ids_data WHERE email LIKE '" . $email . "' and active = 1";
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $arrCSVListSkus = unserialize($readConnection->fetchOne($query));
            $strCSVListSkus = '';

            if (is_array($arrCSVListSkus) && !empty($arrCSVListSkus)) {
                foreach ($arrCSVListSkus as $index => $sku) {
                    $strCSVListSkus .= "'" . $sku . "',";
                }
                $strCSVListSkus = substr($strCSVListSkus, 0, -1);
                //Mage::log($strCSVListSkus, null, 'ids.log');
            }

            $query  = "SELECT sku FROM catalog_product_entity WHERE sku LIKE '" . $searchStringSku . "%'";
            $query .= " AND schrack_sts_statuslocal IN ('std','wirdausl','istausl')";
            $query .= " AND schrack_sts_webshop_saleable = 1";
            $query .= " AND sku NOT IN(" . $strCSVListSkus . ") ORDER BY sku LIMIT ". $limit;
            Mage::log($query, null, 'ids.log');
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $queryResult = $readConnection->fetchAll($query);
            if (count($queryResult) > 0) {
                foreach ($queryResult as $index => $recordset) {
                    $response['skulist'][$index] = $recordset['sku'];
                }
            }

            //Mage::log($response, null, 'ids.log');
            $response['msg'] = 'success';
            //Mage::log($response, null, 'ids.log');
            echo json_encode($response);
        } else {
            echo json_encode(array('msg' => 'error'));
        }
    }

    public function replaceExternalProductAction() {
        if ($this->_validateFormKey()) {
            $oldSku = $this->getRequest()->getParam('old_sku');
            $newSku = $this->getRequest()->getParam('new_sku');

            Mage::log($oldSku . ' -> ' . $newSku, null, 'ids.replace.log');

            $session = Mage::getSingleton('customer/session');
            $email = $session->getCustomer()->getEmail();

            $query = "SELECT * FROM schrack_ids_data WHERE email LIKE '". $email . "' and active = 1";
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $queryResult = $readConnection->fetchAll($query);

            if (count($queryResult) > 0) {
                foreach ($queryResult as $index => $recordset) {
                    $cartNormal      = unserialize($recordset['cart_normal']);
                    $cartExtra       = unserialize($recordset['cart_extra']);
                    $idsWksPositions = unserialize($recordset['ids_wks_positions']);
                }

                // 1. Update ids_wks_positions with new product:

                if (is_array($idsWksPositions) && !empty($idsWksPositions)) {
                    foreach ($idsWksPositions as $index => $position) {
                        if ($position == $oldSku) {
                            $foundIndex = $index;
                        }
                    }
                    $idsWksPositions[$foundIndex] = $newSku;
                }

                $query  = "UPDATE schrack_ids_data SET ids_wks_positions = '" . serialize($idsWksPositions) . "'";
                $query .= " WHERE email LIKE '" . $email . "' and active = 1";
                //Mage::log($query, null, 'ids.replace.log');
                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                $writeConnection->query($query);

                // 2.0 Remove old product-element (with position) from xml structure (cart-extra):
                // 2.1 Get position number and subposition number from cart-extra from the removed sku to use it in the next step:
                $foundReplacementIndex = null;
                if (is_array($cartExtra) && !empty($cartExtra) && isset($cartExtra['Order']['OrderItem'])) {
                    foreach ($cartExtra['Order']['OrderItem'] as $index => $orderItem) {
                        if ($orderItem['ArtNo'] == $oldSku) {
                            $foundReplacementIndex = $index;
                        }
                    }

                    $cartExtra['Order']['OrderItem'][$foundReplacementIndex]['ArtNo'] = $newSku;
                    $foundReplacement = $cartExtra['Order']['OrderItem'][$foundReplacementIndex];
                    unset($cartExtra['Order']['OrderItem'][$foundReplacementIndex]);
//Mage::log($cartExtra['Order']['OrderItem'], null, 'ids.replace.log');
                    // Save in db:
                    if (is_array($cartExtra['Order']['OrderItem']) && !empty($cartExtra['Order']['OrderItem'])) {
                        $data = serialize($cartExtra);
                    } else {
                        $data = '';
                    }
                    $query  = "UPDATE schrack_ids_data SET cart_extra = '" . $data . "'";
                    $query .= " WHERE email LIKE '" . $email . "' and active = 1";
                    $writeConnection->query($query);
                }

                // 3. Append new product-element (with position) to xml structure (cart-normal):
                if (is_array($cartNormal) && !empty($cartNormal) && isset($cartNormal['Order']['OrderItem'])) {
                    foreach ($cartNormal['Order']['OrderItem'] as $index => $orderItem) {
                        $maxIndex = $index;
                    }
                    $cartNormal['Order']['OrderItem'][($maxIndex + 1)] = $foundReplacement;
//Mage::log($cartNormal, null, 'ids.replace.log');
                    // Save in db
                    $query  = "UPDATE schrack_ids_data SET cart_normal = '" . serialize($cartNormal) . "'";
                    $query .= " WHERE email LIKE '" . $email . "' and active = 1";
                    $writeConnection->query($query);
                }

                // 4. Add to cart with default quantity (force add):
                $productModel = Mage::getModel('catalog/product');
                $product = $productModel->loadBySku($newSku);
                $product_id = $product->getId();
                $productHelper = Mage::helper('schrackcatalog/product');
                $minQty = $product->calculateMinimumQuantityPackage();
                $priceRes = $productHelper->getPriceProductInfo(array($newSku), array($minQty));
                $product->setPrice($priceRes[$newSku]['price']);
                $params = array(
                    'product' => $product_id,
                    'qty' => $minQty
                );
//Mage::log($product_id . ' -> ' . $minQty . ' -> ' . $newSku . ' -> ' . $priceRes[$newSku]['price'], null, 'ids.replace.log');
                $cart = Mage::getSingleton('checkout/cart');
                $cart->addProduct($product, $params);
                $cart->save();
                $quote = Mage::getModel('checkout/session')->getQuote();
                $quote->collectTotals()->save();

                echo json_encode(array('msg' => 'success'));
            }
        } else {
            echo json_encode(array('msg' => 'error'));
        }
    }


    protected function _setIdsParams() {
        $ids = array();

        if (isset($_GET['hookurl']) && !empty($_GET['hookurl'])) {
            $ids['hookurl'] = $this->getRequest()->getParam('hookurl');
        } elseif (isset($_POST['hookurl']) && !empty($_POST['hookurl'])) {
            $ids['hookurl'] = $this->getRequest()->getPost('hookurl');
        }

        if (isset($_GET['action']) && !empty($_GET['action'])) {
            $ids['action'] = $this->getRequest()->getParam('action');
        } elseif (isset($_POST['action']) && !empty($_POST['action'])) {
            $ids['action'] = $this->getRequest()->getPost('action');
        }

        return $ids;
    }

}
