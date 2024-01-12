<?php

class Schracklive_Schrack_ApiController extends Mage_Core_Controller_Front_Action {

    private $_typoserviceurl;
    private $_error;

    // Default settings : should be all "false" by default:
    private $_testing;
    private $_logcurl ;
    private $_activateFetch;
    private $_localTestingExampleUrl;

    private $_writeConnection;
    private $_readConnection;


    public function init () {
        // Do something meanful code here, if there has to be initiated some useful stuff
        $resource = Mage::getSingleton('core/resource');
        $this->_writeConnection = $resource->getConnection('core_write');
        $this->_readConnection = $resource->getConnection('core_read');
    }


    public function getAdvisorSnippetFormAction () {
        if (!$this->getRequest()->isAjax()) {
            die('ajax missing'); // Should not be communicated to foreigners (only internal use)
        }

        $jsonRawBodyData = $this->getRequest()->getRawBody();
        $params = array();
        $paramKeyValues = explode('&', urldecode($jsonRawBodyData));
        if (is_array($paramKeyValues)) {
            foreach ($paramKeyValues as $requestParams) {
                list($param, $paramValue) = explode('=', $requestParams);
                $params[$param] = $paramValue;
            }
        }

        // Getting from browser cookie, name = "schrackliveLogin"
        if (isset($params['customer_id']) && $params['customer_id']) {
            $customer_id = $params['customer_id'];
            $customer = Mage::getModel('customer/customer')->load($customer_id);
        } else {
            $customer = Mage::getModel('customer/customer');
        }

        $sessionCustomer = Mage::getSingleton('customer/session')->getCustomer();
        if (!$sessionCustomer) {
            $sessionCustomer = $customer;
        }
        if ($sessionCustomer) {
            if ($sessionCustomer->getAccount()) {
                $advisor = $sessionCustomer->getAccount()->getAdvisor();
            } else {
                $advisorPrincipalName = Mage::getStoreConfig('schrack/shop/default_advisor');
                if ( $advisorPrincipalName ) {
                    $advisor = Mage::getModel('customer/customer')->loadByUserPrincipalName($advisorPrincipalName);
                }
            }
        } else {
            $advisorPrincipalName = Mage::getStoreConfig('schrack/shop/default_advisor');
            if ( $advisorPrincipalName ) {
                $advisor = Mage::getModel('customer/customer')->loadByUserPrincipalName($advisorPrincipalName);
            }
        }

        $core_session = Mage::getSingleton('core/session', array('name'=>'frontend'));

        $actionUrl = Mage::helper('schrackcore/url')->getUrlWithCurrentProtocol('sd/Api/processAdvisorSnippet');
        $imageURL  = Mage::getStoreConfig('schrack/general/imageserver') . 'mab95/' . $advisor->getEmail() . '.jpg';

        $allHtml  = '<div class="advisor_snippet_container">';
        $allHtml .= '<div id="advisorSnippetForm" class="advisor_snippet" data-action="' . $actionUrl . '">';
        $allHtml .= '<h1 class="advisor_snippet_headline_big">' . $this->__('Start Contacting Us!') . '</h1>';
        $allHtml .= '<div class="advisor_snippet_headline">';
        $allHtml .= $this->__('Our Customer Support Take Care About Your Desire. You Have The Problem - We Have The Solution.');
        $allHtml .= '</div>';
        $allHtml .= '<div class="advisor_image_container">';
        $allHtml .= '    <div class="advisor_image_box">';
        $allHtml .= '        <img class="advisor_image" src="' . $imageURL . '">';
        $allHtml .= '    </div>';
        $advisorFullname = $advisor->getFirstname() . ' ' . $advisor->getLastname();
        $allHtml .= '    <div class="advisor_name">' .  $advisorFullname . '</div>';
        $allHtml .= '    <div class="advisor_standard_text">' . $this->__('Your Personal Contact') . '</div>';
        $allHtml .= '</div>';
        $allHtml .= '<div class="advisor_customerdata_container">';
        $allHtml .= '    <div class="email_customerdata">';
        $allHtml .= '        <label class="label_customer_email" for="customerEmail">' . $this->__('Email') . '*</label>';
        $allHtml .= '        <br><input type="text" id="customerEmail" class="input_customer_email" />';
        $allHtml .= '    </div>';
        $allHtml .= '    <div class="firstname_customerdata">';
        $allHtml .= '        <label class="label_customer_firstname" for="customerFirstname">' . $this->__('Firstname') . '*</label>';
        $allHtml .= '        <br><input type="text" id="customerFirstname" class="input_customer_firstname" />';
        $allHtml .= '    </div>';
        $allHtml .= '    <div class="lastname_customerdata">';
        $allHtml .= '        <label class="label_customer_lastname" for="customerLastnme">' . $this->__('Lastname') . '*</label>';
        $allHtml .= '        <br><input type="text" id="customerLastname" class="input_customer_lastname" />';
        $allHtml .= '    </div>';
        $allHtml .= '</div>';
        $allHtml .= '<div class="advisor_customernotice_container">';
        $allHtml .= '    <div class="notice_customerdata">';
        $allHtml .= '        <label class="label_customer_notice" for="customerNotice">' . $this->__('What Is Your Desire?') . '*</label>';
        $allHtml .= '        <br><textarea id="customerNotice" class="input_customer_notice" rows="4" cols="50"></textarea>';
        $allHtml .= '        <br><div>'. $this->__('* Required Fields') . '</div> ';
        $allHtml .= '    </div>';
        $allHtml .= '    <div class="advisor_form_submit">';
        $allHtml .= '        <div class="advisor_form_submit_child">';
        $allHtml .= '            <button class="advisor_form_submit_button" type="submit">' . $this->__('Send') . '</button>';
        $allHtml .= '        </div>';
        $allHtml .= '    </div>';
        $allHtml .= '</div>';
        $allHtml .= '<div class="clearboth"></div>';
        $allHtml .= '</div>';
        $allHtml .= '</div>';

        $allJS  = '<script>';
        $allJS .= 'function checkFieldEmpty(field_id) {';
        $allJS .= '    if(jQuery("#" + field_id).val() == "") {';
        $allJS .= '        return 0;';
        $allJS .= '    } else {';
        $allJS .= '        return 1;';
        $allJS .= '    }';
        $allJS .= '}';
        $allJS .= '';
        $allJS .= 'jQuery(".advisor_form_submit_button").on("click", function() {';
        $allJS .= '    var check1 = checkFieldEmpty("customerEmail");';
        $allJS .= '    var check2 = checkFieldEmpty("customerFirstname");';
        $allJS .= '    var check3 = checkFieldEmpty("customerLastname");';
        $allJS .= '    var check4 = checkFieldEmpty("customerNotice");';
        $allJS .= '    var checkAll = check1 + check2 + check3 + check4;';
        $allJS .= '    if (checkAll != 4) {';
        $allJS .= '        alert("'. $this->__('Please Fill Out All Required Fields') .'");';
        $allJS .= '    } else {';
        $allJS .= '        var postUrl = jQuery("#advisorSnippetForm").attr("data-action");';
        $allJS .= '        jQuery.ajax(postUrl,{';
        $allJS .= '            "type": "post",';
        $allJS .= '            "dataType": "json",';
        $allJS .= '            "data": {';
        $allJS .= '                "form_key" : "' . $core_session->getFormKey() . '",';
        $allJS .= '                "email" : jQuery("#customerEmail").val(),';
        $allJS .= '                "firstname" : jQuery("#customerFirstname").val(),';
        $allJS .= '                "lastname" : jQuery("#customerLastname").val(),';
        $allJS .= '                "notice" : jQuery("#customerNotice").val(),';
        $allJS .= '            },';
        $allJS .= '            "success": function(data) {';
        $allJS .= '                jQuery("#customerEmail").val("");';
        $allJS .= '                jQuery("#customerFirstname").val("");';
        $allJS .= '                jQuery("#customerLastname").val("");';
        $allJS .= '                jQuery("#customerNotice").val("");';
        $allJS .= '                alert(data.result);';
        $allJS .= '            }';
        $allJS .= '        });';
        $allJS .= '    }';
        $allJS .= '});';
        $allJS .= '</script>';

        $allContent = $allHtml . $allJS;

        echo json_encode(array('advisorSnippet' => $allContent));
    }


    public function processAdvisorSnippetAction () {
        if (!$this->getRequest()->isAjax()) {
            die('ajax missing'); // Should not be communicated to foreigners (only internal use)
        }

        $jsonRawBodyData = $this->getRequest()->getRawBody();
        $params = array();
        $paramKeyValues = explode('&', urldecode($jsonRawBodyData));
        if (is_array($paramKeyValues)) {
            foreach ($paramKeyValues as $requestParams) {
                list($param, $paramValue) = explode('=', $requestParams);
                $params[$param] = $paramValue;
            }
        }

        $formkey = "";
        $email = "";
        $firstname = "";
        $lastname = "";
        $notice = "";

        if (isset($params['form_key']) && $params['form_key']) {
            $formkey = $params['form_key'];
        }
        if (isset($params['email']) && $params['email']) {
            $email = $params['email'];
        }
        if (isset($params['firstname']) && $params['firstname']) {
            $firstname = $params['firstname'];
        }
        if (isset($params['lastname']) && $params['lastname']) {
            $lastname = $params['lastname'];
        }
        if (isset($params['notice']) && $params['notice']) {
            $notice = $params['notice'];
        }

        $core_session = Mage::getSingleton('core/session', array('name'=>'frontend'));
        if (!$core_session->validateFormKey($formkey)) {
            die('Wrong Formkey');
        } else {
            // Send Mail to advisor
            if ($email) {
                $customer = Mage::getModel('customer/customer')->loadByEmail($email);
            } else {
                $customer = Mage::getModel('customer/customer');
            }

            if ($customer) {
                if ($customer->getAccount()) {
                    $advisor = $customer->getAccount()->getAdvisor();
                } else {
                    $advisorPrincipalName = Mage::getStoreConfig('schrack/shop/default_advisor');
                    if ( $advisorPrincipalName ) {
                        $advisor = Mage::getModel('customer/customer')->loadByUserPrincipalName($advisorPrincipalName);
                    }
                }
            } else {
                $advisorPrincipalName = Mage::getStoreConfig('schrack/shop/default_advisor');
                if ( $advisorPrincipalName ) {
                    $advisor = Mage::getModel('customer/customer')->loadByUserPrincipalName($advisorPrincipalName);
                }
            }

            $mailText  = $this->__('Email') . " : " . $email. '<br>';
            $mailText .= $this->__('Firstname') . " : " . $firstname. '<br>';
            $mailText .= $this->__('Lastname') . " : " . $lastname. '<br>';
            $mailText .= $this->__('Notice') . " : " . $notice. '<br>';

            $zendMail = new Zend_Mail('utf-8');
            $zendMail->setFrom(Mage::getStoreConfig('web/secure/base_url'))
                      ->setSubject('Landing Page')
                      ->setBodyHtml($mailText)
                      ->addTo($advisor->getEmail())
                      ->send();

            $resultData = array(
                'result' => $this->__('Email Successfully Processed'),
                'mailcontent' => $mailText
            );
            echo json_encode($resultData);
        }
    }


    public function getMessageBarTypoSnippetAction() {
        $messageBarFilledContent = array();
        if ( $this->getRequest()->isPost() && $this->getRequest()->isAjax() ) {
            $this->init();
            $snippetNumbers           = array();
            $override_cache           = '';
            $customer_email           = '';
            $message                  = '';
            $messageBarUid            = null;
            $customerStatusisProspect = "false";

            // Some rules for AJAX:
            if($this->getRequest()->getParam('snippet_numbers')) {
                $snippetNumbers = $this->getRequest()->getParam('snippet_numbers');
            }
            if($this->getRequest()->getParam('override_cache')) {
                $override_cache = $this->getRequest()->getParam('override_cache');
            }
            if($this->getRequest()->getParam('customer_email')) {
                $customer_email = $this->getRequest()->getParam('customer_email');
            }

            // Get it from the database (message_bar_snippets):
            if ($override_cache == '') {
                $snippetChunks = $this->getMessageBarTypoSnippetsBase($snippetNumbers);
            } else {
                // ...or fetch it freshly from the Typo-Service (and also inserts it into database and remove old entries for the same day):
                $this->_error                  = 0;
                $this->_logcurl                = intval(Mage::getStoreConfig('schrack/typo3/message_bars_logging'));
                $this->_activateFetch          = intval(Mage::getStoreConfig('schrack/typo3/message_bars_fetch_active'));
                $this->_testing                = intval(Mage::getStoreConfig('schrack/typo3/message_bars_testing'));
                $this->_localTestingExampleUrl = 'https://test-ba.schrack.com/?id=1130&m=getMessageBarMessages';
                $typo_url                      = Mage::getStoreConfig('schrack/typo3/typo3url');
                $typo_page_id_suffix           = Mage::getStoreConfig('schrack/typo3/message_bars_service_url');

                if ($this->_testing == 1) {
                    $this->_typoserviceurl = $this->_localTestingExampleUrl;
                    Mage::log('typoserviceurl = ' . $this->_typoserviceurl, null, "message_bars.test.log", false, false);
                } else {
                    $this->_typoserviceurl = $typo_url . $typo_page_id_suffix;
                }

                if ($this->_typoserviceurl && $this->_activateFetch == 1) {
                    // Try to use cURL to fetch the content:
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $this->_typoserviceurl);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    $response = curl_exec($ch);
                    curl_close($ch);

                    if ( ! $response ) {
                        $this->_error = 2;
                        Mage::log('Curl Error #1', null, "message_bars_cron.err.log");
                    } else {
                        $responseAsArray = json_decode($response, true);
                        if ($responseAsArray && is_array($responseAsArray)) {
                            if ($this->_logcurl == true) {
                                Mage::log($responseAsArray, null, "message_bars_cron_response.log", false, false);
                            }
                            foreach($responseAsArray as $index => $messageBarData) {
                                // Insert or Update all messageBar related data into database:
                                $pid = $messageBarData['pid'];
                                $accountType = $messageBarData['accountType'];
                                $body = base64_encode($messageBarData['body']);
                                $branchId = $messageBarData['branchId'];
                                if (!$branchId) {
                                    $branchId = 'null';
                                }
                                $campaignName = $messageBarData['campaignName'];
                                $loginState = $messageBarData['loginState'];
                                $active = 1; // TODO : this could be a condition of different rules!!
                                $type = $messageBarData['type'];
                                if (!$type) {
                                    $type = 'null';
                                }
                                $uid = $messageBarData['uid'];


                                $query  = "INSERT INTO schrack_message_bars SET";
                                $query .= " uid = " . $uid . ",";
                                $query .= " pid = " . $pid . ",";
                                $query .= " accountType = " . $accountType . ",";
                                $query .= " body = '" . $body . "',";
                                $query .= " branchId = " . $branchId . ",";
                                $query .= " campaignName = '" . $campaignName . "',";
                                $query .= " loginState = " . $loginState . ",";
                                $query .= " type = " . $type . ",";
                                $query .= " active = " . $active . ",";
                                $query .= " created_at = '" . date("Y-m-d H:i:s") . "'";
                                $query .= " ON DUPLICATE KEY UPDATE";
                                $query .= " pid = " . $pid . ",";
                                $query .= " accountType = " . $accountType . ",";
                                $query .= " body = '" . $body . "',";
                                $query .= " branchId = " . $branchId . ",";
                                $query .= " campaignName = '" . $campaignName . "',";
                                $query .= " loginState = " . $loginState . ",";
                                $query .= " active = " . $active . ",";
                                $query .= " created_at = '" . date("Y-m-d H:i:s") . "'";

                                if ($this->_logcurl == true) {
                                    Mage::log($query, null, "message_bars_cron_response.log", false, false);
                                }

                                $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                                $writeConnection->query($query);
                            }
                        }
                    }
                }

                $snippetChunks = $this->getMessageBarTypoSnippetsBase($snippetNumbers);
            }

            // Do some replacement of the TYPO3 placeholders:
            if (is_array($snippetChunks) && !empty($snippetChunks)) {
                // Init:
                $customerStatus = array();
                // Default values:
                $customerStatus['loggedIn']   = false;
                $customerStatus['branchId']   = 0;
                $customerStatus['isProspect'] = null;

                $loggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
                if ($customer_email == '') {
                    $sessionCustomerEmail = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
                } else {
                    $sessionCustomerEmail = $customer_email;
                    $loggedIn = true;
                }

                $customer = Mage::getModel('customer/customer')->loadByEmail($sessionCustomerEmail);

                if($loggedIn) {
                    $customerStatus['loggedIn'] = true;
                }

                if ($customer) {
                    $query = "SELECT schrack_account_id FROM customer_entity WHERE email LIKE '" . $sessionCustomerEmail . "'";
                    $accountId = $this->_readConnection->fetchOne($query);
                    $account = Mage::getModel('account/account')->load($accountId, 'account_id');

                    $customerStatus['branchId'] = $account->getWwsBranchId();
                    $prospectType = $customer->getSchrackCustomerType();
                    if (stristr($prospectType, 'prospect')) {
                        $customerStatus['isProspect'] = true;
                    }
                }

                foreach ($snippetChunks as $index => $messageBar) {
                    $loginState = intval($messageBar['loginState']);
                    if ($loginState == 2 && $customerStatus['loggedIn'] == false) {
                        continue;
                    }
                    if ($loginState == 3 && $customerStatus['loggedIn'] == true) {
                        continue;
                    }

                    $branchId = intval($messageBar['branchId']);
                    // $branchId = null -> alle Geschäftsstellen!
                    if ($branchId > 0) {
                        if ($branchId != $customerStatus['branchId']) {
                            continue;
                        }
                    } else {
                        // Any Branch-Id is valid
                    }

                    $accountType = intval($messageBar['accountType']);
                    // Only Contacts:
                    if ($accountType == 2 && $customerStatus['isProspect'] == true) {
                        continue;
                    }
                    // Only Prospects:
                    if ($accountType == 3 && $customerStatus['isProspect'] == false) {
                        continue;
                    }

                    if ($customerStatus['isProspect'] == true) {
                        $customerStatusisProspect = "true";
                    }

                    $innerText = $messageBar['body'];

                    if ($customerStatus['loggedIn'] == true) {
                        $innerText = str_replace('[[salutatory]]', $customer->getSchrackSalutatory(), $innerText);
                        $innerText = str_replace('[[last_name]]', $customer->getLastname(), $innerText);
                        $innerText = str_replace('[[first_name]]', $customer->getFirstname(), $innerText);
                        $innerText = str_replace('[[title]]',  $customer->getPrefix(), $innerText);
                        $innerText = str_replace('[[gender]]', $customer->getGender(), $innerText);
                    } else {
                        if (preg_match('([[salutatory]]|[[last_name]]|[[first_name]]|[[title]]|[[gender]])', $innerText) === 1) {
                            // It makes no sense to replace placeholders for a non-logged-in user -> just override it:
                            continue;
                        }
                        if ($messageBar['type'] == 2 || $messageBar['type'] == 3) {
                            // Offers and Promotions are not available for non-logged-in users -> just override it:
                            continue;
                        }
                    }

                    $baseClass = 'messagebar_html';
                    // Themes:
                    // yellow -> text (1)
                    // orange -> promotions (2)
                    // blue -> offers (3)
                    if ($messageBar['type'] == 1) {
                        $themeClass = ' message_bar_yellow_theme_panel';
                    }

                    if ($messageBar['type'] == 2) {
                        $themeClass = ' message_bar_orange_theme_panel';
                    }

                    if ($messageBar['type'] == 3) {
                        $themeClass = ' message_bar_blue_theme_panel';
                    }

                    $messageBarUid = $messageBar['uid'];

                    if ($messageBar['link'] && $messageBar['linkText']) {
                        $linkTargetButton = 'linkTargetMessageBar';
                    } else {
                        $linkTargetButton = '';
                    }

                    // Building all complete HTML
                    $cssClasses = $baseClass . $themeClass;
                    $completeHTML  = '<div class="' . $cssClasses . '">';
                    if ($linkTargetButton) {
                        $completeHTML .= '<div class="innerTextMessageBarMedium">';
                    } else {
                        if ($messageBar['link']) {
                            $completeHTML .= '<div class="innerTextMessageBarBig linkTargetMessageBar">';
                        } else {
                            $completeHTML .= '<div class="innerTextMessageBarBig">';
                        }
                    }
                    $completeHTML .= $innerText;
                    $completeHTML .= '</div>';
                    if ($linkTargetButton) {
                        $completeHTML .= '<div class="linkButtonMessageBarContainer">';
                        $completeHTML .= '<button class="linkButtonMessageBar ' . $linkTargetButton . '">';
                        $completeHTML .= $messageBar['linkText'];
                        $completeHTML .= '</button>';
                        $completeHTML .= '</div>';
                    }
                    $completeHTML .= '<div id="closeMessageBar" class="closeMessageBar">';
                    $completeHTML .= '<span id="closeMessageBarSymbol" class="closeMessageBarSymbol"';
                    $completeHTML .= ' data-uid="' . $messageBarUid . '">&times;</span>';
                    $completeHTML .= '</div>';
                    $completeHTML .= '<div style="clear: both;"></div>';
                    $completeHTML .= '</div>';

                    $message = "AccountType = " . $accountType . " and Prospect = " . $customerStatusisProspect . " (uid = " . $messageBarUid . "). ";
                    $messageBarFilledContent[$messageBarUid]['message']      = $message;
                    $messageBarFilledContent[$messageBarUid]['body']         = $completeHTML;
                    $messageBarFilledContent[$messageBarUid]['campaignName'] = $messageBar['campaignName'];
                    $messageBarFilledContent[$messageBarUid]['type']         = $messageBar['type'];
                    $messageBarFilledContent[$messageBarUid]['link']         = $messageBar['link'];
                    $messageBarFilledContent[$messageBarUid]['linkText']     = $messageBar['linkText'];
                    $messageBarFilledContent[$messageBarUid]['login']        = $messageBar['loginState'];
                }
            }
        }

        echo json_encode($messageBarFilledContent);
        die();
    }


    private function getMessageBarTypoSnippetsBase($snippetNumbers = array()) {
        if (is_array($snippetNumbers)) {
            if ($snippetNumbers == array()) {
                $query = "SELECT * FROM schrack_message_bars";
            } else {
                $queryValues = '';
                foreach($snippetNumbers as $number) {
                    $queryValues .= $number . ',';
                }
                $queryValues = substr($queryValues, 0, -1);

                $query  = "SELECT * FROM schrack_message_bars WHERE uid IN(" . $queryValues . ");";
            }

            $queryResult = $this->_readConnection->query($query);

            $snippetChunks = array();
            if ($queryResult->rowCount() > 0) {
                foreach ($queryResult as $index => $recordset) {
                    $snippetChunks[$index]['uid']          = $recordset['uid'];
                    $snippetChunks[$index]['pid']          = $recordset['pid'];
                    $snippetChunks[$index]['accountType']  = $recordset['accountType'];
                    $snippetChunks[$index]['body']         = base64_decode($recordset['body']);
                    $snippetChunks[$index]['branchId']     = $recordset['branchId'];
                    $snippetChunks[$index]['campaignName'] = $recordset['campaignName'];
                    $snippetChunks[$index]['loginState']   = $recordset['loginState'];
                    $snippetChunks[$index]['type']         = $recordset['type'];
                    $snippetChunks[$index]['link']         = $recordset['link'];
                    $snippetChunks[$index]['linkText']     = $recordset['linkText'];
                }
            }
        }

        return $snippetChunks;
    }

}
