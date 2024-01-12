<?php

class Schracklive_Schrack_AjaxDispatcherController extends Mage_Core_Controller_Front_Action {
    /* Usage in template:

      <button id="getAjaxDispatherData">ClickMe</button>

      <script type="text/javascript">//<![CDATA[

      jQuery(document).ready(function() {

      jQuery('#getAjaxDispatherData').on('click', function() {
      var ajaxUrl = '<?php echo Mage::helper('schrackcore/url')->getUrlWithCurrentProtocol('sd/AjaxDispatcher/setGetData'); ?>';
      jQuery.ajax(ajaxUrl, {
      'dataType' : 'json',
      'type': 'POST',
      'data': {
      'form_key' : '<?php echo Mage::getSingleton('core/session')->getFormKey(); ?>',
      'get_function_name1' : {'data' : {'param11' : 'value11', 'param12' : 'value12'}},
      'get_function_name2' : {'data' : {'param21' : 'value21', 'param22' : 'value22}'}}
      },
      'success': function (data) {
      var parsedData = data;
      // TODO : do something here with processed response data!
      }
      });
      });

      });

      //]]></script>

     */

    const PRODUCT_ROW_LIMIT = 21;
    const PRODUCT_LIST_LIMIT = 100;

    const DASHBOARD_ROW_LIMIT = 10;        // Added by Nagarro to Define Dashboard Per Page Record Limit

    private $_readConnection;
    private $_writeConnection;
    private $_storeId;
    private $_id2productMap;
    private $_logForUser = null;
    private $_siteheaderinfostatus;
    private $_siteheaderinfolink;
    private $_formkey;
    private $_trackAdvisorData = false;
    private $_advisorLog = false;

    public function testajaxAction () {
        $ajaxUrl = Mage::helper('schrackcore/url')->getUrlWithCurrentProtocol('sd/AjaxDispatcher/setGetData');
        $formKey = Mage::getSingleton('core/session')->getFormKey();
        $pageSize = 10;

        echo <<<EOF
<html>
<head>
<script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.1.1.min.js"></script>
</head> 
<body>
 <button id="getAjaxDispatcherData">ClickMe</button>
 <textarea id="textctrl" cols="128" name="note" placeholder="waiting..." rows="20" style="width: 100%" readonly>
 Press the button to get a result...
 </textarea>
 <script type="text/javascript">//<![CDATA[

    jQuery(document).ready(function() {
        jQuery('#getAjaxDispatcherData').on('click', function() {
             var ajaxUrl = '$ajaxUrl';
            jQuery('#textctrl').val('');
             jQuery.ajax(ajaxUrl, {
                'dataType' : 'json',
                'type': 'POST',
                'data': {
                    'form_key' : '$formKey',
                    // 'getCartItemCount' : '',
                    // 'getOffers'   : { 'data' : {'filter' : { 'status_offered' : 1 }}},
                    // 'getAdvisorData' : '',
                    // 'getMegaMenuImpl' : '',
                    'getCustomerInformation' : '',
                    // 'getSearchBarCategories' : '',
                    // 'getOrders' : { 'data' : {'filter' : { 'status_invoiced' : 1, 'status_credited' : 1 }, 'sort' : { 'field' : 'orderNumber', 'ASC' : 0},  'pagination' : { 'page_size' : $pageSize, 'page' : 1 } }}
                    // 'getLastViewedProducts' : ''
                    // 'getLastPurchasedProducts' : '',
                    // 'getPromotionProducts' : '',
                    // 'getProductPrices' : {'data' : {'skus' : ['BM018102--', 'AS201040-5'], 'quantities' : [100, 100]}},
                    // 'getProductAvailabilities' : {'data' : {'skus' : ['BM018102--', 'AS201040-5'], 'forceRequest' : 0 }},
                    // 'getCableLeavings' : {'data' : {'sku' : 'XC100423JE'}},
                    // 'getProductList' : {'data' : {'category': 68071, 'output' : { 'facets' : 0, 'products' : 1, 'categories' : 0 }}},
                    // 'getRenderedCategoryBlocks' : {'data' : { 'category' : 62929 } } // PCP
                    // 'getRenderedCategoryBlocks' : {'data' : { 'category' : 62930, 'start': 0, 'limit': 10  } } // PLP
                    // 'getRelatedProductsForProduct' : {'data' : {'sku' : 'BM018104--' }},
                    // 'getAccessoriesForProduct' : {'data' : {'sku' : 'BM018104--' }}
                    // 'setCartItemQuantity' : {'data' : { 'item_id' : 3123189, 'quantity' : 0 }}
                    // 'setCartEmpty' : '',
                    // 'getLastOffers' : '',
                    // 'getLastOrders' : '',
                    // 'searchDocuments' : { 'data' : {'filter' : { 'creditmemo_documents' : 1 }}}
                    // 'setDeleteAddress' : { 'data' : {'address_id' : 40527 }}
                    // 'getLastPurchasedProductSKUs' : ''
                    // 'getLastPurchasedProducts' : ''
                    // 'setBatchAddToCart' :    { 'data' : {
                    //                              forceAdd : 1,
                    //                              items : [
                    //                                  {'sku' : 'BM018102--', 'quantity' : 3 },
                    //                                  {'sku' : 'XC01010101', 'quantity' : 200 }
                    //                              ]
                    //                          }
                    //                      }
                    // 'getIsPromotionSKU' : {'data' : {'sku' : 'LILE0033--' }},
                    // 'getPromotionSKUs' : {'data' : {'skus' : ['LILE0033--', 'LIHGWA46--', 'LILE0053--', 'XC01010101'] }},
                    // 'getProductsForPromoID' : {'data' : { 'promo_id' : 0, 'start': 0, 'limit': 100 }},
                    // 'validateEmailAddress' : { 'data' : { 'email_address' : 'obelix@schrack.c' }}
                },
                'success': function (data) {
                    var parsedData = data;
                    debugger;
                    // TODO : do something here with processed response data!
                    var str = JSON.stringify(parsedData, null, 2);
                    jQuery('#textctrl').val(str);
                },
                'error': function (data) {
                    var parsedData = data;
                    debugger;
                }                
            });
            /*
             jQuery.ajax(ajaxUrl, {
                'dataType' : 'json',
                'type': 'POST',
                'data': {
                    'form_key' : '$formKey',
                    'getOrders' : { 'data' : {'filter' : { 'status_invoiced' : 1, 'status_credited' : 1 }, 'sort' : { 'field' : 'orderNumber', 'ASC' : 0},  'pagination' : { 'page_size' : $pageSize, 'page' : 2 } }}
                },
                'success': function (data) {
                    var parsedData = data;
                    debugger;
                    // TODO : do something here with processed response data!
                },
                'error': function (data) {
                    var parsedData = data;
                    debugger;
                }                
            });
            */
        });
    });

    //]]></script>
</body>
</html>
EOF;
    }

    public function init () {
        $this->_storeId = Mage::app()->getStore()->getStoreId();
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_siteheaderinfostatus = Mage::getStoreConfig('schrack/websiteheader/site_header_info_status');    // Site header info icon status
        $this->_siteheaderinfolink = Mage::getStoreConfig('schrack/websiteheader/site_header_info_link');    // Site header info icon link
    }

    public function setGetDataAction () {
        //if (!$this->_validateFormKey()) {
        //$this->_redirect('*/*');
        //return;
        //}

        $timeConsumptionDispatcherLog = Mage::getStoreConfig('schrackdev/development/ajaxdispatcher_log_timeconsumptions');
        $generalDispatcherLog = Mage::getStoreConfig('schrackdev/development/ajaxdispatcher_log_general');
        $dataDispatcherLog = Mage::getStoreConfig('schrackdev/development/ajaxdispatcher_log_general_data');

        if ( $timeConsumptionDispatcherLog ) {
            Mage::log('*******************************************', null, 'AjaxDispatcherTimeConsumptions.log');
        }
        if ( $timeConsumptionDispatcherLog ) {
            Mage::log('********** START Ajax Functions ***********', null, 'AjaxDispatcherTimeConsumptions.log');
        }
        $startOverall = (int)round(microtime(true) * 1000);

        // Init variable:
        $collectedResponseData = [];

        if ( $this->getRequest()->isPost() && $this->getRequest()->isAjax() ) {
            $this->init();
            $completeRequestData = $this->getRequest()->getPost();

            if ( is_array($completeRequestData) && !empty($completeRequestData) ) {
                foreach ( $completeRequestData as $functionName => $data ) {
                    if ( $functionName != 'form_key' ) {
                        if ( isset($this->_logForUser)
                            && Mage::getSingleton('customer/session')->isLoggedIn()
                            && Mage::getSingleton('customer/session')->getCustomer()->getEmail() == $this->_logForUser ) {
                            if ( $generalDispatcherLog ) {
                                Mage::log('Should Execute Function : ' . $functionName, null, 'AjaxDispatcherCall.log');
                            }
                        }
                    } else {
                        $this->_formkey = $data;
                    }
                }
                foreach ( $completeRequestData as $functionName => $data ) {
                    if ( $functionName != 'form_key' ) {
                        try {
                            if ( isset($this->_logForUser)
                                && Mage::getSingleton('customer/session')->isLoggedIn()
                                && Mage::getSingleton('customer/session')->getCustomer()->getEmail() == $this->_logForUser ) {
                                if ( $generalDispatcherLog ) {
                                    Mage::log('Called Function : ' . $functionName, null, 'AjaxDispatcherCall.log');
                                }
                            }
                            if ( $dataDispatcherLog ) {
                                Mage::log($data, null, 'AjaxDispatcherCallGeneralData.log');
                            }
                            $start = (int)round(microtime(true) * 1000);
                            $collectedResponseData[$functionName]['result'] = $this->$functionName($data);
                            $end = (int)round(microtime(true) * 1000);
                            if ( $timeConsumptionDispatcherLog ) {
                                Mage::log('Called Function : ' . $functionName . ' = ' . ($end - $start) . ' ms', null,
                                    'AjaxDispatcherTimeConsumptions.log');
                            }
                        } catch ( Exception $ex ) {
                            Mage::logException($ex);
                            $collectedResponseData[$functionName]['result'] = 'Error: ' . $ex->getMessage();
                            if ( $ex->getMessage() == "Wrong product availibility input!" ) {
                                Mage::log("Complete Request:\n" . print_r($completeRequestData, true), null,
                                    'wrong_product_availibility_input.log');
                            }
                        }
                    } else {
                        $this->_formkey = $data;
                    }
                }
            }
        } else {
            $this->_redirect('*/*');
            $endOverall = (int)round(microtime(true) * 1000);
            if ( $timeConsumptionDispatcherLog ) {
                Mage::log('Alltogether AjaxDispatcherController (Non-AJAX-Call) >>>>> ' . ($endOverall - $startOverall) . ' ms (execution time)',
                    null, 'AjaxDispatcherTimeConsumptions.log');
            }
            if ( $timeConsumptionDispatcherLog ) {
                Mage::log('*******************************************', null, 'AjaxDispatcherTimeConsumptions.log');
            }

            return;
        }

        if ( isset($this->_logForUser)
            && Mage::getSingleton('customer/session')->isLoggedIn()
            && Mage::getSingleton('customer/session')->getCustomer()->getEmail() == $this->_logForUser ) {
            if ( $dataDispatcherLog ) {
                Mage::log('Responsedata : ' . PHP_EOL . print_r($collectedResponseData, true), null,
                    'AjaxDispatcherCallGeneralData.log');
            }
        }
        echo json_encode($collectedResponseData);
        $endOverall = (int)round(microtime(true) * 1000);
        if ( $timeConsumptionDispatcherLog ) {
            Mage::log('Alltogether AjaxDispatcherController >>>>> ' . ($endOverall - $startOverall) . ' ms (execution time)',
                null, 'AjaxDispatcherTimeConsumptions.log');
        }
        if ( $timeConsumptionDispatcherLog ) {
            Mage::log('*******************************************', null, 'AjaxDispatcherTimeConsumptions.log');
        }
        die();
    }

    // Returns advisor data from given advisor, or returns advisor data from logged in user (if no advisor given)
    // FE-Example 1: 'getAdvisorData' : {'data' : {'advisor_user_pricipal_name' : 'blabla@at.schrack.at'}} ---> returns Active Directory data of a given advisor
    // FE-Example 2: 'getAdvisorData' : {'data' : ''} ---> returns Active Directory data of advisor from a logged in user (or default advisor)
    private function getAdvisorData ( $advisor = null ) {
        $sessionCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $customerLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();

        if ( $advisor ) {
            return $this->getCachedResult('getAdvisorDataImpl',
            'advisor_data_' . $advisor,
            false,
            $advisor);
        } else {
            if ( $customerLoggedIn ) {
                $this->_trackAdvisorData = Mage::getStoreConfig('schrack/shop/track_advisor');
                if ($this->_trackAdvisorData == 'enabled') {
                    $email = $sessionCustomer->getEmail();
                    // Usage: just enter the 2 keys ('track_advisor' + 'schrack/shop/track_advisor_email') and values
                    // into core_config_data database and activate this feature by doing it:
                    // 'schrack/shop/track_advisor' : 'enabled' or null
                    // 'schrack/shop/track_advisor_email' : <EMAIL> (from some customer, who needed to be tracked)
                    $emailTrack = Mage::getStoreConfig('schrack/shop/track_advisor_email');
                    if ($email == $emailTrack) {
                        $this->_advisorLog = true;
                    }
                }
                if ( $sessionCustomer && $sessionCustomer->getSchrackCustomerType() == 'light-prospect'
                || $sessionCustomer->getSchrackCustomerType() == 'full-prospect' ) {
                    $advisor = $sessionCustomer->getAdvisor();
                    if ( !is_object($advisor) ) {
                        if ($this->_advisorLog == true) {
                            Mage::log('AdvisorTrack #1 ' . $email, null, 'track_advisor.log');
                        }
                        return $this->getCachedResult('getAdvisorDataImpl',
                         'advisor_data_anonymous',
                         false,
                         null);
                    } else {
                        if ($this->_advisorLog == true) {
                            Mage::log('AdvisorTrack #2 ' . $email, null, 'track_advisor.log');
                        }
                        return $this->getCachedResult('getAdvisorDataImpl',
                        'advisor_data_4_customer_',
                        true,
                        null,
                        12,
                        false,
                        true);
                    }
                } else {
                    if ($this->_advisorLog == true) {
                        Mage::log('AdvisorTrack #3 ' . $email, null, 'track_advisor.log');
                    }
                    return $this->getCachedResult('getAdvisorDataImpl',
                    'advisor_data_4_customer_',
                    true,
                    null,
                    12,
                    false,
                    true);
                }
            } else {
                if ( intval(Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature')) == 1 ) {
                    return $this->getMultipleAdvisorDataImpl();
                } else {
                    return $this->getCachedResult('getAdvisorDataImpl',
                    'advisor_data_anonymous',
                    false,
                    null);
                }
            }
        }
    }

    private function getMultipleAdvisorDataImpl () {
        $resultData = [];

        $resultData['multiple_advisor_feature'] = 'enabled';

        $emailOne = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_one_email');
        $emailTwo = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_two_email');
        $emailThree = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_three_email');

        if ( $emailOne ) {
            $resultData['advisor_one_name'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_one_name');
            $resultData['advisor_one_title'] = 'Online Sales';
            $resultData['advisor_one_mail'] = $emailOne;
            $resultData['advisor_one_telephonenumber'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_one_phone');
            $resultData['advisor_one_mobile'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_one_mobile');
            $resultData['advisor_one_imageurl'] = Mage::getStoreConfig('schrack/general/imageserver') . 'mab58/' . Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_one_email') . '.jpg';
            $resultData['advisor_one_faxnumber'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_one_fax');
            $resultData['advisor_one_branch'] = $this->__('Branch') . ': ' . Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_one_branch');
        }

        if ( $emailTwo ) {
            $resultData['advisor_two_name'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_two_name');
            $resultData['advisor_two_title'] = 'Online Sales';
            $resultData['advisor_two_mail'] = $emailTwo;
            $resultData['advisor_two_telephonenumber'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_two_phone');
            $resultData['advisor_two_mobile'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_two_mobile');
            $resultData['advisor_two_imageurl'] = Mage::getStoreConfig('schrack/general/imageserver') . 'mab58/' . Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_two_email') . '.jpg';
            $resultData['advisor_two_faxnumber'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_two_fax');
            $resultData['advisor_two_branch'] = $this->__('Branch') . ': ' . Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_two_branch');
        }

        if ( $emailThree ) {
            $resultData['advisor_three_name'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_three_name');
            $resultData['advisor_three_title'] = 'Online Sales';
            $resultData['advisor_three_mail'] = $emailThree;
            $resultData['advisor_three_telephonenumber'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_three_phone');
            $resultData['advisor_three_mobile'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_three_mobile');
            $resultData['advisor_three_imageurl'] = Mage::getStoreConfig('schrack/general/imageserver') . 'mab58/' . Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_three_email') . '.jpg';
            $resultData['advisor_three_faxnumber'] = Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_three_fax');
            $resultData['advisor_three_branch'] = $this->__('Branch') . ': ' . Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_three_branch');
        }

        return $resultData;
    }

    private function getAdvisorDataImpl ( $advisor = null, $userPrinicipalNameAdvisorParam = null ) {
        $resultData = [];

        $domainConfig2 = [
            'host' => Mage::getStoreConfig('schrack/ad/host2'),
            'port' => Mage::getStoreConfig('schrack/ad/port2'),
            'useSsl' => Mage::getStoreConfig('schrack/ad/use_ssl2'),
            'username' => Mage::getStoreConfig('schrack/ad/username2'),
            'password' => Mage::getStoreConfig('schrack/ad/password2'),
            'baseDn' => 'dc=schrack,dc=com',
        ];

        if ( $userPrinicipalNameAdvisorParam ) {
            $userPrinicipalNameAdvisor = $userPrinicipalNameAdvisorParam;
        } else {
            if ( !isset($advisor) || !isset($advisor['data']) || !isset($advisor['data']['advisor_user_pricipal_name']) ) {
                // Get default advisor email:
                $advisor = Mage::getSingleton('customer/session')->getCustomer()->getAdvisor();
                if ( !is_object($advisor) ) {
                    $userPrinicipalNameAdvisor = Mage::getStoreConfig('schrack/shop/default_advisor');
                    if ($this->_advisorLog == true) {
                        Mage::log('AdvisorTrack #3.1 ' . $userPrinicipalNameAdvisor, null, 'track_advisor.log');
                    }
                } else {
                    $userPrinicipalNameAdvisor = $advisor->getSchrackUserPrincipalName();
                    if ($this->_advisorLog == true) {
                        Mage::log('AdvisorTrack #3.2 ' . $userPrinicipalNameAdvisor, null, 'track_advisor.log');
                    }
                }
            } else {
                if ( $advisor['data']['advisor_user_pricipal_name'] ) {
                    $userPrinicipalNameAdvisor = $advisor['data']['advisor_user_pricipal_name'];
                    if ($this->_advisorLog == true) {
                        Mage::log('AdvisorTrack #3.3 ' . $userPrinicipalNameAdvisor, null, 'track_advisor.log');
                    }
                } else {
                    //$advisor = Mage::getSingleton('customer/session')->getCustomer()->getAccount()->getAdvisor();
                    $userPrinicipalNameAdvisor = Mage::getSingleton('customer/session')->getCustomer()->getSchrackAdvisorPrincipalName();
                    if ($this->_advisorLog == true) {
                        Mage::log('AdvisorTrack #3.4 ' . $userPrinicipalNameAdvisor, null, 'track_advisor.log');
                    }

                    if ( !$userPrinicipalNameAdvisor ) {
                        $userPrinicipalNameAdvisor = Mage::getStoreConfig('schrack/shop/default_advisor');
                        if ($this->_advisorLog == true) {
                            Mage::log('AdvisorTrack #3.5 ' . $userPrinicipalNameAdvisor, null, 'track_advisor.log');
                        }
                    }
                    if ( !$userPrinicipalNameAdvisor ) {
                        $resultData['result'] = '[ERROR] no advisor email found';
                        $resultData['status'] = 'error';

                        return $resultData;
                    }
                }
            }
        }

        // Get advisor email from Active Directory:
        $filter = '(&(objectCategory=person)(objectClass=user)(userprincipalname=' . $userPrinicipalNameAdvisor . ')(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
        $attributes = ['mail', 'title', 'givenname', 'sn', 'telephonenumber'];

        // If advisor not found in old domain (*.schrack.lan), just try to find advisor in new domain (schrack.com):
        if ( !isset($resultData[0]) || !isset($resultData[0]['mail']) || !isset($resultData[0]['mail'][0]) || $resultData[0]['mail'][0] == null ) {
            unset($resultData);
            $resultData = [];
            if ( isset($domainConfig2['useSsl']) && $domainConfig2['useSsl'] ) {
                $ldapConnection = ldap_connect('ldaps://' . $domainConfig2['host'] . ":" .  $domainConfig2['port']);
            } else {
                $ldapConnection = ldap_connect($domainConfig2['host'], $domainConfig2['port']);
            }
            ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3); // to get utf-8 encoding
            // ldap_set_option($ldapConnection, LDAP_OPT_NETWORK_TIMEOUT, 20); // timeout after 20s
            $ldapBind = ldap_bind($ldapConnection, $domainConfig2['username'], $domainConfig2['password']);
            $result = ldap_search($ldapConnection, $domainConfig2['baseDn'], $filter, $attributes, null, null, 20);
            $resultData = ldap_get_entries($ldapConnection, $result);
        }

        $data['mail'] = $resultData[0]['mail'][0];
        if ( (!isset($data['mail']) || $data['mail'] == '') && !$userPrinicipalNameAdvisorParam ) {
            // emergency trick when advisor is in shop DB but not longer in AD:
            return $this->getAdvisorDataImpl(null, Mage::getStoreConfig('schrack/shop/default_advisor'));
        }
        $data['title'] = $this->ensureUTF8($resultData[0]['title'][0]);
        $data['firstname'] = $this->ensureUTF8($resultData[0]['givenname'][0]);
        $data['lastname'] = $this->ensureUTF8($resultData[0]['sn'][0]);
        $data['mail'] = $resultData[0]['mail'][0];
        $data['telephonenumber'] = isset($resultData[0]['telephonenumber']) ? $resultData[0]['telephonenumber'][0] : null;
        $data['imageurl'] = str_replace('http:', '',
                Mage::getStoreConfig('schrack/general/imageserver')) . 'mab58/' . $resultData[0]['mail'][0] . '.jpg';

        $magentoAdvisor = Mage::getModel('customer/customer')->loadByEmail($data['mail']);
        if ( $magentoAdvisor->getId() && $magentoAdvisor->getSchrackFax() ) {
            $data['faxnumber'] = $magentoAdvisor->getSchrackFax();
        } else {
            $data['faxnumber'] = Mage::getStoreConfig('general/store_information/schrack_fax');
        }

        return $data;
    }

    // Returns a list of user data from my account (same WWS-ID):
    // FE-Example: 'getAllContactsFromAccount' : ''
    private function getAllContactsFromAccount ( $data = null ) {
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $wws_id = $sessionLoggedInCustomer->getSchrackWwsCustomerId();
        $resultData = [];
        $index = 0;

        if ( is_object($sessionLoggedInCustomer) && $wws_id ) {
            $account = $sessionLoggedInCustomer->getAccount();
            $contacts = $account->getContacts();

            foreach ( $contacts as $contact ) {
                $customer = Mage::getModel('customer/customer');
                $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                $customer->loadByEmail($contact->getEmail());
                $resultData[$index]['firstname'] = $customer->getFirstname();
                $resultData[$index]['lastname'] = $customer->getLastname();
                $resultData[$index]['email'] = $contact->getEmail();
                $resultData[$index]['contact_id'] = $contact->getSchrackWwsContactNumber();
                $index++;
            }
        } else {
            // Error: customer not found or not logged in!
            $resultData['result'] = '[ERROR] no wws-id found or session customer object problem';
            $resultData['status'] = 'error';
        }

        return $resultData;
    }

    // Create a list of my shared partslists and the affected users (e-mail, contact-id, name)
    // FE-Example 1: 'getMyActiveSharedPartslists' : {'data' : {'partsListId' : 14}}  ----> returns only data for one specific partslist
    // FE-Example 2: 'getMyActiveSharedPartslists' : ''  -----> returns data for all shared partslists
    private function getMyActiveSharedPartslists ( $data = null ) {
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $wws_id = $sessionLoggedInCustomer->getSchrackWwsCustomerId();
        $resultData = [];
        $index = 0;
        $item_index = 0;
        $sharedPartslistId = 0;
        $productHelper = Mage::helper('schrackcatalog/product');
        $stockHelper = Mage::helper('schrackcataloginventory/stock');


        if ( isset($data['data']) && isset($data['data']['partsListId']) ) {
            $sharedPartslistId = intval($data['data']['partsListId'], 10);
        }

        if ( is_object($sessionLoggedInCustomer) && $wws_id ) {
            $query = "SELECT * FROM partslist_sharing_map WHERE schrack_wws_customer_id LIKE '" . $wws_id . "'";
            $query .= " AND schrack_wws_contact_number_sharer = " . $sessionLoggedInCustomer->getSchrackWwsContactNumber();
            $query .= " AND active = 1";
            if ( $sharedPartslistId > 0 ) {
                $query .= " AND shared_partslist_id = " . $sharedPartslistId;
            }

            $results = $this->_readConnection->fetchAll($query);

            if ( count($results) ) {
                foreach ( $results as $recordset ) {
                    $resultData[$index]['partslist_id'] = $recordset['shared_partslist_id'];
                    $resultData[$index]['email'] = $recordset['email'];
                    $resultData[$index]['firstname'] = $recordset['firstname_receiver'];
                    $resultData[$index]['lastname'] = $recordset['lastname_receiver'];
                    $resultData[$index]['schrack_wws_contact_number_receiver'] = $recordset['schrack_wws_contact_number_receiver'];
                    $resultData[$index]['last_update_notification'] = $recordset['last_update_notification_at'];
                    $resultData[$index]['last_update_notification_status'] = $recordset['last_update_notification_flag'];
                    $resultData[$index]['last_update_notification_viewed'] = $recordset['last_update_notification_received'];
                    $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId(Mage::getSingleton('customer/session')->getCustomer(),
                        intval($recordset['shared_partslist_id'], 10));
                    $partslistItemCollection = $partslist->getItemCollection();
                    foreach ( $partslistItemCollection as $item ) {
                        if ( $product = $item->getProduct() ) {
                            $resultData[$index][$item_index]['article_number'] = $product->getSKU();
                            $resultData[$index][$item_index]['quantity'] = $item->getQty();
                            $resultData[$index][$item_index]['quantity_unit'] = $product->getSchrackQtyunit();
                            $resultData[$index][$item_index]['article_name'] = $product->getName();
                            $resultData[$index][$item_index]['pic_url'] = Schracklive_SchrackCatalog_Helper_Image::getImageUrl($product->getMainImageUrl(),
                                Schracklive_SchrackCatalog_Helper_Image::PARTLIST_PAGE);
                            $resultData[$index][$item_index]['price'] = $product->getFinalPrice();
                            $resultData[$index][$item_index]['allPickupLocations'] = $productHelper->getAllPickupQuantities($product);
                            $resultData[$index][$item_index]['getAllPickupQuantities'] = $productHelper->getAllPickupQuantities($product);
                            $resultData[$index][$item_index]['getAllDeliveryStockNumbers'] = $stockHelper->getAllDeliveryStockNumbers();
                            $resultData[$index][$item_index]['warehouseIdDefaultDelivery'] = (int)$stockHelper->getLocalDeliveryStock()->getStockNumber();
                            $resultData[$index][$item_index]['warehouseIdDefaultPickup'] = (int)$stockHelper->getCustomerPickupStockNumber(null);
                            $resultData[$index][$item_index]['isAvailInAnyDeliveryStock'] = $productHelper->isAvailInAnyDeliveryStock($product);
                            $resultData[$index][$item_index]['getSummarizedFormattedDeliveryQuantities'] = $productHelper->getSummarizedFormattedDeliveryQuantities($product);
                            $resultData[$index][$item_index]['getFormattedDeliveryQuantity'] = $productHelper->getFormattedDeliveryQuantity($product);
                            $getUnformattedDeliveryQuantity = str_replace('.', '',
                                $productHelper->getFormattedDeliveryQuantity($product));
                            list($deliveryQty, $deliveryQtyUnit) = explode(' ', $getUnformattedDeliveryQuantity);
                            $resultData[$index][$item_index]['getUnformattedDeliveryQuantity'] = $deliveryQty;
                            $resultData[$index][$item_index]['isAvailInAnyPickupStock'] = $productHelper->isAvailInAnyPickupStock($product);
                            $resultData[$index][$item_index]['summarizedFormattedPickupQuantities'] = $productHelper->getSummarizedFormattedPickupQuantities($product);
                            $resultData[$index][$item_index]['getFormattedPickupQuantity'] = $productHelper->getFormattedPickupQuantity($product);
                            $getUnformattedPickupQuantity = str_replace('.', '',
                                $productHelper->getFormattedPickupQuantity($product));
                            list($pickupQty, $pickupQtyUnit) = explode(' ', $getUnformattedPickupQuantity);
                            $resultData[$index][$item_index]['getUnformattedPickupQuantity'] = $pickupQty;
                            $resultData[$index][$item_index]['getFormattedPickupQuantity'] = $productHelper->getFormattedPickupQuantity($product);
                            $hasDrums = $productHelper->hasDrums($product);
                            if ( $hasDrums ) {
                                $resultData[$index][$item_index]['availableDrums'] = Mage::helper('schrackcatalog/info')->getAvailableDrums($product,
                                    $stockHelper->getAllStockNumbers());
                            }
                            $item_index++;
                        }
                    }
                    $index++;
                }
            } else {
                $resultData['result'] = '[ERROR] no partslist found';
                $resultData['status'] = 'error';
            }

            return $resultData;
        }
    }

    // Create a datalist (SKU, amount, price, description, etc.) concerning to a list of requested partslist ID's (expects an array):
    // FE-Example: 'getItemsFromSharedPartslist' : {'data' : {'partsListIds' : [2711, 6158]}}
    private function getItemsFromSharedPartslist ( $data = null ) {
        $resultData = [];
        $itemIndex = 0;

        if ( isset($data['data']) && isset($data['data']['partsListIds']) ) {
            $sharedPartslistIds = $data['data']['partsListIds'];
        } else {
            $resultData['result'] = '[ERROR] no shared partslist-id found';
            $resultData['status'] = 'error';

            return $resultData;
        }

        if ( !is_array($sharedPartslistIds) || empty($sharedPartslistIds) ) {
            $resultData['result'] = '[ERROR] wrong format of partslist-ids found (check for array or empty)';
            $resultData['status'] = 'error';

            return $resultData;
        }

        foreach ( $sharedPartslistIds as $key => $sharedPartslistId ) {
            $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId(Mage::getSingleton('customer/session')->getCustomer(),
                intval($sharedPartslistId, 10));
            $partslistItemCollection = $partslist->getItemCollection();

            if ( count($partslistItemCollection) ) {
                foreach ( $partslistItemCollection as $item ) {
                    $product = $item->getProduct();
                    $resultData[$sharedPartslistId][$itemIndex]['partslist_id'] = $sharedPartslistId;
                    $resultData[$sharedPartslistId][$itemIndex]['partslist_name'] = $partslist->getDescription();
                    $resultData[$sharedPartslistId][$itemIndex]['item_sku'] = $product->getSKU();
                    $resultData[$sharedPartslistId][$itemIndex]['item_name'] = $product->getName();
                    // $itemData[$sharedPartslistId][$itemIndex]['item_price'] = $product->getFinalPrice(); // Set price to response if needed ?
                    $itemIndex++;
                }
            } else {
                $resultData['result'] = '[ERROR] no partslist collection found';
                $resultData['status'] = 'error';

                return $resultData;
            }
        }

        return $resultData;
    }

    // This function returns all partslists, where I am the receiver, not the sharer:
    // FE-Example: 'getMyActiveReceivedSharedPartslists' : ''
    private function getMyActiveReceivedSharedPartslists () {
        $resultData = [];
        $index = 0;

        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $wws_id = $sessionLoggedInCustomer->getSchrackWwsCustomerId();
        $aclroleClass = '';
        $aclrole = $sessionLoggedInCustomer->getSchrackAclRole();
        if ( $aclrole == 'staff' || $aclrole == 'projectant' || $aclrole == 'list_price_customer' ) {
            $aclroleClass = 'hide';
        }

        if ( is_object($sessionLoggedInCustomer) && $wws_id ) {
            $query = "SELECT * FROM partslist_sharing_map WHERE schrack_wws_customer_id LIKE '" . $wws_id . "'";
            $query .= " AND schrack_wws_contact_number_receiver = " . $sessionLoggedInCustomer->getSchrackWwsContactNumber();
            $query .= " AND active = 1 ORDER BY last_update_notification_flag DESC, last_update_notification_at DESC";

            $results = $this->_readConnection->fetchAll($query);

            if ( count($results) ) {
                foreach ( $results as $recordset ) {
                    $emptyWishlistClass = "button_deactivated ";
                    $partlist = Mage::getModel('schrackwishlist/partslist')->load($recordset['shared_partslist_id']);
                    if ( intval($partlist->getItemsCount()) > 0 ) {
                        $emptyWishlistClass = "";
                    }
                    $resultData[$index]['partslist_id'] = $recordset['shared_partslist_id'];
                    $resultData[$index]['email'] = isset($recordset['email']) ? $recordset['email'] : null;
                    $resultData[$index]['name'] = ucwords($recordset['firstname_sharer'] . ' ' . $recordset['lastname_sharer']);
                    $resultData[$index]['lastname'] = $recordset['lastname_sharer'];
                    $resultData[$index]['login_customer_wws_id'] = $wws_id;
                    $resultData[$index]['schrack_wws_contact_number_sharer'] = $recordset['schrack_wws_contact_number_sharer'];
                    $resultData[$index]['schrack_wws_contact_number_receiver'] = $recordset['schrack_wws_contact_number_receiver'];
                    $resultData[$index]['last_update_notification'] = Date('Y-m-d',
                        strtotime($recordset['last_update_notification_at']));
                    $resultData[$index]['last_update_notification_status'] = $recordset['last_update_notification_flag'];
                    $resultData[$index]['read'] = $recordset['last_update_notification_flag'] == 1 ? $this->__('Unread') : $this->__('Read');
                    $resultData[$index]['description'] = '<a class="shared-partlist-line-item" data-id="' . $partlist->getId() . '" href="javascript:void(0)">' . $partlist->getDescription() . '</a>';
                    $resultData[$index]['comment'] = $partlist->getComment();
                    $resultData[$index]['articles'] = $partlist->getItemsCount();
                    $resultData[$index]['action'] = '<div class="partlists-action" style="width: auto;">
                    <a class="delete deleteshared deleteIcon" data-contact="' . $wws_id . '" data-id="' . $recordset['shared_partslist_id'] . '" href="javascript:void(0)"></a>
                    <a href="return false;" data-partlistid="' . $recordset['shared_partslist_id'] . '" class="' . $emptyWishlistClass . 'shared-partlist ajaxdispatcher bttn-sm ' . $aclroleClass . '"><span class="addToCartWhite"></span> ' . $this->__('Add to Cart') . '</a>
                    <div style="clear: both;"></div></div>';
                    $index++;
                }
            } else {
                $resultData['result'] = '[ERROR] no received shared partslists found';
                $resultData['status'] = 'error';

                return $resultData;
            }

            return $resultData;
        } else {
            $resultData['result'] = '[ERROR] no wws-id found or customer object problem';
            $resultData['status'] = 'error';

            return $resultData;
        }

        return $resultData;
    }

    /**
     * Fetches product data from solr
     * FE-Example 1 - Get unfiltered product and facet list for category 8228, with default settings (limit to 10 products, start at 0):
     *      'getProductList' : {'data' : {'category': 8228}}
     * FE-Example 2 - Search for 'lsd' in category 8228, with several facets set, get 20 products, start at 20:
     *      'getProductList' : {'data' : {'query': 'lsd', 'start': 20, 'limit': 20, 'category': 8228, 'facets': {'schrack_nennstrom_ac-1_69': ['100A', '18A'], 'schrack_geraet': ['Sch\u00fctz']}}}
     *
     * @param $request
     * @return array
     */
    private function getProductList ( $request ) {
        try {
            $searchModel = $this->getSearchModel($request);
            $searchModel->setSort('position_intS');
            $searchModel->setSortOrder('asc');
            if ( isset($request['data']['accessory']) ) {
                $searchModel->setAccessory((bool)$request['data']['accessory']);
            }
            $resultData = $searchModel->getProducts();
            if ( isset($request['data']['output']) ) {
                if ( isset($request['data']['output']['facets']) && boolval($request['data']['output']['facets']) === false ) {
                    unset($resultData['facets']);
                }
                if ( isset($request['data']['output']['products']) && boolval($request['data']['output']['products']) === false ) {
                    unset($resultData['products']);
                }
                if ( isset($request['data']['output']['categories']) && boolval($request['data']['output']['categories']) === false ) {
                    unset($resultData['categories']);
                }
            }
        } catch ( Exception $e ) {
            Mage::logException($e);
            $resultData = ['result' => '[ERROR] Could not fetch product data'];
            $resultData['status'] = 'error';
        }

        return $resultData;
    }

    //===============================================  getRenderedCategoryBlocks
    private function getRenderedCategoryBlocks ( $request ) {
    //==========================================================================
        /************************************************************ PARAM INFO
            [request:array] => [::::::::::::::::::::::::::::::::::::::::::::::::
                ["..."],
                ["data"] => [
                    "category" => string,
                    "general_filters": array,
                    "query" => string
                ]
            ]:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
        ***********************************************************************/
        $boolLogProductCollectionSOLR = Mage::getStoreConfig('schrackdev/development/ajaxdispatcher_log_productcollectionsolr');
        $boolLogFacetsSOLR = Mage::getStoreConfig('schrackdev/development/ajaxdispatcher_log_facetssolr');
        Mage::unregister('sort');
        Mage::register('sort',  $request['data']['sort']);
        $categoryId = $request['data']['category'];
        $category = Mage::getModel('catalog/category')->load($categoryId); // TODO: optimize EAV fields to load
        Mage::unregister('current_category');
        Mage::register('current_category', $category);
        //-------------------------------------------------------- get solr data
        $rawSolrData = $this->getProductList($request);
        //-------- if only one subcategory exist call this subcat automatically
        if(count($rawSolrData['categories']) == 1) {
            $tmpID = 0;
            foreach($rawSolrData['categories'] as $key => $value) {
                $tmpID = $key;
            }
            $recall = array();
            $recall['data'] = array(
                "query" => "",
                "start" => 0,
                "limit" => 50,
                "accessory" => 0,
                "category" => $rawSolrData['categories'][$tmpID]['id'],
                "facets" => $request['data']['facets']
            );
            $categoryId = $recall['data']['category'];
            $category = Mage::getModel('catalog/category') ->load($categoryId); // TODO: optimize EAV
            Mage::unregister('current_category');
            Mage::register('current_category', $category);
            $rawSolrData = $this -> getProductList($recall);
        }



        $hasProducts = isset($rawSolrData['products']) && count($rawSolrData['products']) > 0;
        $hasCategories = isset($rawSolrData['categories']) && count($rawSolrData['categories']) > 0;
        $isCatalogCategory = $category->isCatalogCategory();
        if ( $hasProducts && !$isCatalogCategory ) {
            // get prices and stock quantities
            $priceRequest = ['data' => ['skus' => []]];
            foreach ( $rawSolrData['products'] as $product ) {
                $priceRequest['data']['skus'][] = $product['sku'];
            }
            $rawPriceData = $this->getProductPrices($priceRequest, null);
            $rawAvailabilityData = $this->getProductAvailabilities($priceRequest);
            foreach ( $rawSolrData['products'] as $ndx => $product ) {
                $sku = $product['sku'];
                $rawSolrData['products'][$ndx]['priceInfo'] = $rawPriceData[$sku];
                $rawSolrData['products'][$ndx]['availabilityInfo'] = $rawAvailabilityData[$sku];
            }
            $rawSolrData['products'] = $this->loadTemporarilyNotFromSolrCommingProductData($rawSolrData['products']);
        }
        //---------------------------------------- LOGING -  memorize for blocks
        if ( $boolLogProductCollectionSOLR ) {
            Mage::log('  >>>>>>>>>>>>>>>>>>>>>>>>   getRenderedCategporyBlocks -> products (START) >>>>>>>>>>>>>>>>>>>>>>>>',
                null, 'AjaxDispatcherProductCollectionSolr.log');
            //------------------------------------------------------------------
            Mage::log($rawSolrData['products'], null, 'AjaxDispatcherProductCollectionSolr.log');
            //------------------------------------------------------------------
            Mage::log('  >>>>>>>>>>>>>>>>>>>>>>>>   getRenderedCategporyBlocks -> products (END) >>>>>>>>>>>>>>>>>>>>>>>>',
                null, 'AjaxDispatcherProductCollectionSolr.log');
        }
        //----------------------------------------------------------------------
        if ( $boolLogFacetsSOLR ) {
            Mage::log('  >>>>>>>>>>>>>>>>>>>>>>>>   getRenderedCategporyBlocks -> facets (START) >>>>>>>>>>>>>>>>>>>>>>>>',
                null, 'AjaxDispatcherFacetsSolr.log');
            //------------------------------------------------------------------
            Mage::log($rawSolrData['facets'], null, 'AjaxDispatcherFacetsSolr.log');
            //------------------------------------------------------------------
            Mage::log('  >>>>>>>>>>>>>>>>>>>>>>>>   getRenderedCategporyBlocks -> facets (END) >>>>>>>>>>>>>>>>>>>>>>>>',
                null, 'AjaxDispatcherFacetsSolr.log');
        }
        //----------------------------------------------------------------------
        if (!isset($request['data']['general_filters'])) {
            $request['data']['general_filters'] = false;
        }
        //--------------------------------------------- merge all facets/filters
        $tempFacets = $rawSolrData['facets'];
        $tempFacets = array_merge(
            Mage::helper('catalog')->getGeneralFilter(
                $request['data']['general_filters'],
                $rawSolrData['highlightFacets']
            ),
            $tempFacets
        );

        //---------------- BUILD MAGE CACHE VARS for usage in rendered templates
        //------------------------------------------------------------ Filter(s)
        Mage::register('facetsCollectionSolr', $tempFacets);
        //------------------------------------------------------------- Products
        Mage::register('productCollectionSolr', $rawSolrData['products']);
        //----------------------------------------------------------- Categories
        Mage::register('categoryCollectionSolr', $rawSolrData['categories']);
        //---------------------------------------------------- SOLR Query Params
        Mage::register('productStart', $rawSolrData['status']['start']);
        Mage::register('productCount', $rawSolrData['status']['count']);
        Mage::register('productLimit', $rawSolrData['status']['limit']);
        //----------------------------------------------------------------------
        if (isset($priceRequest['data']['skus']) && is_array($priceRequest['data']['skus'])) {
            /* @var $promoHelper Schracklive_Promotions_Helper_Data */
            $promoHelper = Mage::helper('promotions');
            $promoMap = $promoHelper->getSKUsToPromotionFlags($priceRequest['data']['skus']);
            Mage::register('promoMap',$promoMap);
        }
        //--------------------------------------------------- INIT RETURN Values
        $resultData = [];
        //------------------------------------------------------------ RENDERING
        $this->loadLayout();
        //---------------------------------------------------------- Breadcrumbs
        $resultData['breadcrumbsBlock'] = $this->createRenderedBreadcrumbs($rawSolrData);
        //------------------------------------------------------------- Headline
        $resultData['headlineBlock'] = $this->renderHtml('core/template', 'category.headline',
            'catalog/category/headline.phtml');
        //---------------------------------------------------------- Description
        //$resultData['descriptionBlock'] = $this->renderHtml('core/template', 'category.description', 'catalog/category/description.phtml'); //not required
        //--------------------------------------------------------- Filter Boxes
        if ( $isCatalogCategory ) { //--------------------- Filters for catalogs
            $resultData['filterBlock'] = $this->renderHtml('core/template', '',
                'catalog/product/list/catalogue_filter.phtml', ['category_id' => $categoryId]);
        } else { //--------- Default SOLR Filters for subcategories and products
            if ( !isset($request['data']['query']) ) { //-- INIT query parameter
                $request['data']['query'] = false;
            }
            //------------------------------------------------ render Filter Box
            $resultData['filterBlock'] = $this->renderHtml('core/template', '',
                'catalog/product/list/filter_solr.phtml',
                ['category_id' => $categoryId, 'q' => $request['data']['query']]);
        }
        //---------------------------------------------- Render category content
        if ( $hasCategories ) { //------------------------------- Sub categories
            $resultData['subcatsBlock'] = $this->renderHtml('core/template', 'category.subcats',
                'catalog/category/subcats.phtml');
            $resultData['productListBlock'] = null;
        } else { //----------------------------------------- Products / Catalogs
            $resultData['subcatsBlock'] = null;
            if ( $isCatalogCategory ) { //------------------------- Catalog list
                $resultData['productListBlock'] = $this->renderHtml('catalog/product_list', '',
                    'catalog/product/list/catalogue_table.phtml');
            } else { //-------------------------------------------- Product list
                $resultData['productListBlock'] = $this->renderHtml('catalog/product_list', '',
                    'catalog/product/list/table_solr.phtml');
            }
        }
        //--------------------------------- media attachments (maybe deprecated)
        $resultData['attachmentsBlock'] = $this->renderHtml('core/template', 'category.attachments',
            'catalog/category/attachments.phtml');
        //--------------------------------- additional content provided by TYPO3
        $resultData['cmsBlock'] = $this->renderHtml('schracklive_typo3/catalog_category_cmsContent',
            'category.cms_content');
        //---------------------------------------------------- SOLR Query Params
        $resultData['limit'] = $rawSolrData['status']['limit'];
        $resultData['start'] = $rawSolrData['status']['start'];
        $resultData['count'] = $rawSolrData['status']['count'];
        $resultData['isCatalogCategory'] = $isCatalogCategory;
        //------------- Disable the accessory tab handling by always returning 0
        // $resultData['accessoryCount'] = $category->getAccessoryCount(); [DEPR]
        $resultData['accessoryCount'] = 0;
        //------------------------------------------------------ Free MAGE Cache
        Mage::unregister('facetsCollectionSolr');
        Mage::unregister('categoryCollectionSolr');
        Mage::unregister('productStart');
        Mage::unregister('productCount');
        Mage::unregister('productLimit');
        Mage::unregister('promoMap');
        Mage::unregister('sort');
        //--------------------------------------------------------------- RETURN
        return $resultData;
    } //==================================== getRenderedCategoryBlocks ***END***


    //================================================ createRenderedBreadcrumbs
    private function createRenderedBreadcrumbs ( $rawSolrData ) {
    //==========================================================================
        //------------------------------------------------------ INIT breadcrumb
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        //------------------------------------------------------- Add HOME entry
        $breadcrumbs->addCrumb("home", [
            "label" => $this->__("Home"),
            "title" => $this->__("Home"),
            "link" => Mage::getStoreConfig('schrack/typo3/typo3url')
        ]);
        //------------------------------------------- loop through tree (levels)
        $cnt = count($rawSolrData['breadcrumbs']);
        $bcOptions = array();
        foreach ( $rawSolrData['breadcrumbs'] as $bc ) {
            $bcOptions =  [
                "label" => $bc['name'],
                "title" => $bc['name']
            ];
            if ( --$cnt == 0 ) { //----------------------------- Mark last entry
                $bcOptions["last"] = true;
            } else { //----------------------------------------- default entries
                $bcOptions["link"] = Mage::helper('schrackcore/url')->getUrlWithCurrentProtocol() . $bc['path'];
            }
            $breadcrumbs->addCrumb('category'.$bc['id'], $bcOptions);
        }
        //--------------------------------------------------------------- RETURN
        return $breadcrumbs->toHtml();
    } //==================================== createRenderedBreadcrumbs ***END***

    private function renderHtml ( $blockType, $blockName = '', $template = null, $args = [] ) {
        $block = $this->getLayout()->createBlock($blockType, $blockName);
        if ( $template ) {
            $block->setTemplate($template);
        }
        $block->addData($args);
        $html = $block->toHtml();

        return $html;
    }

    /**
     * Fetches search result data from solr
     * FE-Example 1 - Get unfiltered product and facet list for category 8228, with default settings (limit to 10 products, start at 0):
     *      'getSearchResult' : {'data' : {'category': 8228}}
     * FE-Example 2 - Search for 'lsd' in category 8228, with several facets set, get 20 products, start at 20:
     *      'getSearchResult' : {'data' : {'query': 'lsd', 'start': 20, 'limit': 20, 'category': 8228, 'facets': {'schrack_nennstrom_ac-1_69': ['100A', '18A'], 'schrack_geraet': ['Sch\u00fctz']}}}
     *
     * @param $request
     * @return array
     */
    private function getSearchResult ( $request, $skuList = false ) {
        try {
            $searchModel = $this->getSearchModel($request,$skuList);
            if ( isset($request['data']['sort']) ) {
                $searchModel->setSort($request['data']['sort']);
            }
            if ( isset($request['data']['sort_order']) ) {
                $searchModel->setSortOrder($request['data']['sort_order']);
            }
            $resultData = $searchModel->getResults();
            if ( $skuList ) {
                $orderMap = array_flip($skuList);
                usort($resultData['products'], function ($a,$b) use ($orderMap) {
                    return $orderMap[$a['sku']] - $orderMap[$b['sku']];
                });
            }
        } catch ( Exception $e ) {
            Mage::logException($e);
            $resultData = ['result' => '[ERROR] Could not fetch result data'];
            $resultData['status'] = 'error';
        }

        return $resultData;
    }

    //================================================== getRenderedSearchBlocks
    private function getRenderedSearchBlocks ( $request ) {
    //==========================================================================
        /************************************************************ PARAM INFO
            [request:array] => [::::::::::::::::::::::::::::::::::::::::::::::::
                ["..."],
                ["data"] => [
                    "category" => string,
                    "facets" =>  array,
                    "filterChanged" => boolean | null,
                    "general_filters": array,
                    "limit" => int,
                    "pageLimit" => int,
                    "query" => string,
                    "sort" => string,
                    "start" => int,
                    "saleLimit" => int,
                ]
            ]:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
            ********************************************************************
            This function returns search results:
            FE-Example: 'getRenderedSearchBlocks' : {
                'data' : {
                    'filterChanged' : 'true | null',
                    'query': getParameterByName('q'),
                    'sort' : getParameterByName('sort'),
                    'start': 0,
                    'limit': 21,
                    'saleLimit' : 7,
                    'pageLimit' : 20,
                    'category': getParameterByName('cat'),
                    'facets': filterArray,
                    'general_filters': generalFilterArray
                }
            };
        ***********************************************************************/
        //------------------------------------------------------------ INIT VARS
        $singleProduct = [];
        $resultData = [];
        $resultData['skuListOfNormalProducts'] = [];
        $resultData['skuListOfSaleProducts'] = [];
        $resultData['redirectToSingleSearchResult'] = '';
        $blockRedirectToSingleSearchResult = false;
        $timeConsumptionDispatcherLog = Mage::getStoreConfig('schrackdev/development/ajaxdispatcher_log_timeconsumptions');
        $boolLogProductCollectionSOLR = Mage::getStoreConfig('schrackdev/development/ajaxdispatcher_log_productcollectionsolr');
        $countProducts = 0;
        //---------------- Parse search-mode (filter-select/search-address-line)
        if ( $request && isset($request['data']['filterChanged']) && $request['data']['filterChanged'] == 'true' ) {
            $blockRedirectToSingleSearchResult = true;
        }
        //------------------------------------------- Logging Fetching SOLR data
        if ( $timeConsumptionDispatcherLog ) {
            Mage::log('QUERYSTRING: ' . $request['data']['query'], null, 'AjaxDispatcherTimeConsumptions.log');
            $start = (int)round(microtime(true) * 1000);
        }
        //----------------------------------------------------------------------
        $rawSolrData = $this->getSearchResult($request);
        //----------------------------------------------------------------------
        if ( $timeConsumptionDispatcherLog ) {
            $end = (int)round(microtime(true) * 1000);
            Mage::log('+ SOLR >>>>>> ' . ($end - $start) . ' ms', null, 'AjaxDispatcherTimeConsumptions.log');
        }
        //----------------------------------------------------------------------
        $hasProducts = is_array($rawSolrData['products']) && count($rawSolrData['products']) > 0;
        $hasSaleProducts = is_array($rawSolrData['saleProducts']) && count($rawSolrData['saleProducts']) > 0;
        //----------------------------------------------------------------------
        if ( $timeConsumptionDispatcherLog ) {
            $start = (int)round(microtime(true) * 1000);
        }
        //----------------------------------------------------------------------
        if ( $hasProducts ) {
            //---------------------------------- get prices and stock quantities
            $priceRequest = ['data' => ['skus' => []]];
            foreach ( $rawSolrData['products'] as $product ) {
                $priceRequest['data']['skus'][] = $product['sku'];
                $resultData['skuListOfNormalProducts'][] = $product['sku'];
                $singleProduct = $product;
                $countProducts++;
            }
            //------------- Call for "Add to cart", price and availability infos
            $rawPriceData = $this->getProductPrices($priceRequest);
            $rawAvailabilityData = $this->getProductAvailabilities($priceRequest);
            $tmpAvailabilityDatas = array();
            //------------------------------------------------------------------
            foreach ( $rawSolrData['products'] as $ndx => $product ) {
                $sku = $product['sku'];
                $rawSolrData['products'][$ndx]['priceInfo'] = $rawPriceData[$sku];
                //------------------------- for VTech storage exception handling
                $rawSolrData['products'][$ndx]['availabilityInfo'] = $rawAvailabilityData[$sku];
                $tmpAvailabilityDatas[$sku] = $rawAvailabilityData[$sku];
            }
            //------------------------ prepare vtc infos for template and result
            $resultData['vtcDeliveryInfoInitSearch'] = $rawSolrData['pagesStatus']['start'] > 0 ? "no" : "yes";
            foreach($tmpAvailabilityDatas as $k => $v){
                foreach($v as $key => $val){
                    if($key == "nearestDeliveryQty" && (
                        (is_array($tmpAvailabilityDatas[$k]["nearestDeliveryQty"]["provider"]) &&
                          isset($tmpAvailabilityDatas[$k]["nearestDeliveryQty"]["providerName"]) && $tmpAvailabilityDatas[$k]["nearestDeliveryQty"]["providerName"] == "VTC") ||
                        (isset($tmpAvailabilityDatas[$k]["999"]) && $tmpAvailabilityDatas[$k]["999"]["stockLocation"] == "VTC"))){
                        $resultData['vtcDeliveryInfo'][$k] = $tmpAvailabilityDatas[$k]["deliveryQtySum"];
                    }
                }
            }
            if(count($resultData['vtcDeliveryInfo']) == 0){
                $resultData['vtcDeliveryInfo'] = "no";
            }
            //------------------------------------------------------------------
            Mage::unregister('vtcDeliveryInfo');
            Mage::register('vtcDeliveryInfo', $resultData['vtcDeliveryInfo']);
            Mage::unregister('vtcDeliveryInfoInitSearch');
            Mage::register('vtcDeliveryInfoInitSearch', $resultData['vtcDeliveryInfoInitSearch']);

            //------------------------------------------------------------------
            $rawSolrData['products'] = $this->loadTemporarilyNotFromSolrCommingProductData($rawSolrData['products']);
            $rawSolrData['products'] = $this->removeCDatasFromProducts($rawSolrData['products']);
        }
        //----------------------------------------------------------------------
        if (isset($priceRequest['data']['skus']) && is_array($priceRequest['data']['skus'])) {
            /* @var $promoHelper Schracklive_Promotions_Helper_Data */
            $promoHelper = Mage::helper('promotions');
            $promoMap = $promoHelper->getSKUsToPromotionFlags($priceRequest['data']['skus']);
            Mage::register('promoMap',$promoMap);
        }
        //----------------------------------------------------------------------
        if ( $timeConsumptionDispatcherLog ) {
            $start = (int)round(microtime(true) * 1000);
        }
        //----------------------------------------------------------------------
        if ( $hasSaleProducts ) {
            // get prices and stock quantities
            $priceRequest = ['data' => ['skus' => []]];
            foreach ( $rawSolrData['saleProducts'] as $product ) {
                $priceRequest['data']['skus'][] = $product['sku'];
                $resultData['skuListOfSaleProducts'][] = $product['sku'];
                $singleProduct = $product;
            }
            $rawSolrData['saleProducts'] = $this->loadTemporarilyNotFromSolrCommingProductData($rawSolrData['saleProducts']);
            $rawSolrData['saleProducts'] = $this->removeCDatasFromProducts($rawSolrData['saleProducts']);
        }
        //----------------------------------------------------------------------
        if ( $countProducts == 1 && count($rawSolrData['pages']) == 0 && $blockRedirectToSingleSearchResult == false ) {
            $resultData['redirectToSingleSearchResult'] = Mage::helper('schrackcore/url')->getUrlWithCurrentProtocol() . $singleProduct['path'] . '?q=' . $singleProduct['sku'];
        }
        //----------------------------------------------------------------------
        if ( $timeConsumptionDispatcherLog ) {
            $start = (int)round(microtime(true) * 1000);
        }
        // Writing data for deliver to template somewhere (app/design/frontend/schrack/schrackresponsive/template/solrsearch/search.phtml)
        Mage::unregister('productCollectionSolr');
        Mage::register('productCollectionSolr', $rawSolrData['products']);
        if ( $boolLogProductCollectionSOLR ) {
            Mage::log('  >>>>>>>>>>>>>>>>>>>>>>>>   getRenderedSearchBlocks (START) >>>>>>>>>>>>>>>>>>>>>>>>', null,
                'AjaxDispatcherProductCollectionSolr.log');
            Mage::log($rawSolrData['products'], null, 'AjaxDispatcherProductCollectionSolr.log');
            Mage::log('  >>>>>>>>>>>>>>>>>>>>>>>>   getRenderedSearchBlocks (END) >>>>>>>>>>>>>>>>>>>>>>>>', null,
                'AjaxDispatcherProductCollectionSolr.log');
        }
        $tempFacets = $rawSolrData['facets'];
        if ( !isset($request['data']['general_filters']) ) {
            $request['data']['general_filters'] = false;
        }
        if ( $hasProducts || $request['data']['general_filters'] ) {
            $tempFacets = array_merge(
                Mage::helper('catalog')->getGeneralFilter(
                    $request['data']['general_filters'],
                    $rawSolrData['highlightFacets']
                ),
                $tempFacets
            );
        }
        Mage::unregister('facetsCollectionSolr');
        Mage::register('facetsCollectionSolr', $tempFacets);

        Mage::unregister('categoryCollectionSolr');
        Mage::register('categoryCollectionSolr', $rawSolrData['categories']);

        Mage::unregister('saleProductsCollectionSolr');
        Mage::register('saleProductsCollectionSolr', $rawSolrData['saleProducts']);

        Mage::unregister('pagesCollectionSolr');
        Mage::register('pagesCollectionSolr', $rawSolrData['pages']);

        Mage::unregister('productStatus');
        Mage::register('productStatus', $rawSolrData['status']);

        Mage::unregister('saleStatus');
        Mage::register('saleStatus', $rawSolrData['saleStatus']);

        Mage::unregister('pagesStatus');
        Mage::register('pagesStatus', $rawSolrData['pagesStatus']);

        Mage::unregister('suggestions');
        if ( isset($rawSolrData['suggestions']) ) {
            Mage::register('suggestions', $rawSolrData['suggestions']);
        }

        Mage::unregister('queryString');
        Mage::register('queryString',$request['data']['query']);

        if ( is_array($priceRequest) && is_array($priceRequest['data']) && is_array($priceRequest['data']['skus']) ) {
            $promoHelper = Mage::helper('promotions');
            $promoMap = $promoHelper->getSKUsToPromotionFlags($priceRequest['data']['skus']);
        } else {
            $promoMap = array();
        }
        Mage::unregister('promoMap');
        Mage::register('promoMap',$promoMap);

        if ($timeConsumptionDispatcherLog) {
            $end = (int)round(microtime(true) * 1000);
        }
        if ( $timeConsumptionDispatcherLog ) {
            Mage::log('+ Register variable assignments >>>>>> ' . ($end - $start) . ' ms', null,
                'AjaxDispatcherTimeConsumptions.log');
        }

        if ( $timeConsumptionDispatcherLog ) {
            $start = (int)round(microtime(true) * 1000);
        }
        if ( isset($request['data']['category']) ) {
            $categoryId = $request['data']['category'];
            $category = Mage::getModel('catalog/category')->load($categoryId);
            Mage::unregister('current_category');
            Mage::register('current_category', $category);
        }
        if ( $timeConsumptionDispatcherLog ) {
            $end = (int)round(microtime(true) * 1000);
        }
        if ( $timeConsumptionDispatcherLog ) {
            Mage::log('+ Get category >>>>>> ' . ($end - $start) . ' ms', null, 'AjaxDispatcherTimeConsumptions.log');
        }

        if ( $timeConsumptionDispatcherLog ) {
            $start = (int)round(microtime(true) * 1000);
        }
        //------------------------------------------------------------ RENDERING
        $this->loadLayout();
        //--------------------------------------------------------- search.phtml
        $resultData['searchResultBlock'] = $this->renderHtml('core/template', '', 'catalogsearch/search.phtml',
            ['q' => $request['data']['query'], 'sort' => $request['data']['sort']]);

        $resultData['productStatus'] = $rawSolrData['status'];
        $resultData['saleStatus'] = $rawSolrData['saleStatus'];
        $resultData['pagesStatus'] = $rawSolrData['pagesStatus'];
        if ( $timeConsumptionDispatcherLog ) {
            $end = (int)round(microtime(true) * 1000);
        }
        if ( $timeConsumptionDispatcherLog ) {
            Mage::log('+ Render HTML >>>>>> ' . ($end - $start) . ' ms', null, 'AjaxDispatcherTimeConsumptions.log');
        }
        //--------------------------------------------------------------- RETURN
        return $resultData;
    } //====================================== getRenderedSearchBlocks ***END***

    /**
     * @param $request
     * @return Schracklive_Search_Model_Search
     */
    public function getSearchModel ( $request, $paramSkuList = false ) {
        /** @var Schracklive_Search_Model_Search $searchModel */
        $searchModel = Mage::getModel('search/search');
        if ( isset($request['data']['query']) ) {
            $searchModel->setQuery(strtolower($request['data']['query']));
        }
        if ( isset($request['data']['start']) ) {
            $searchModel->setStart((int)$request['data']['start']);
        }
        if ( isset($request['data']['limit']) && $request['data']['limit'] > 0 && $request['data']['limit'] <= 50) {
            $searchModel->setLimit((int)$request['data']['limit']);
        } else {
            $searchModel->setLimit((int)50);
        }
        if ( isset($request['data']['category']) ) {
            $searchModel->setCategory((int)$request['data']['category']);
        }
        if ( isset($request['data']['facets']) ) {
            $searchModel->setFacets($request['data']['facets']);
        }
        if ( isset($request['data']['saleLimit']) ) {
            $searchModel->setSaleLimit((int)$request['data']['saleLimit']);
        }
        if ( isset($request['data']['pageLimit']) ) {
            $searchModel->setPagesLimit((int)$request['data']['pageLimit']);
        }
        $skuList = false;
        if ( isset($request['data']['general_filters']) ) {
            if ( isset($request['data']['general_filters']['last_purchased']) && boolval($request['data']['general_filters']['last_purchased']) ) {
                $skuList = $this->getCachedResult('getLastPurchasedProductSKUs', 'purcharged_product_skus_', true,
                    null);
            }
            if ( isset($request['data']['general_filters']['last_viewed']) && boolval($request['data']['general_filters']['last_viewed']) ) {
                $tmpSKUs = $this->getLastViewedProductSKUs();
                $skuList = is_array($skuList) ? array_intersect($tmpSKUs, $skuList) : $tmpSKUs;
            }
            if ( isset($request['data']['general_filters']['promotions']) && boolval($request['data']['general_filters']['promotions']) ) {
                $tmpSKUs = $this->getCachedResult('getPromotionProductSKUs', 'promotion_skus_', true, null);
                $skuList = is_array($skuList) ? array_intersect($tmpSKUs, $skuList) : $tmpSKUs;
            }
            if ( isset($request['data']['general_filters']['high_availability']) ) {
                $searchModel->setHighAvailability((bool)$request['data']['general_filters']['high_availability']);
            }
            if ( isset($request['data']['general_filters']['sale']) ) {
                $searchModel->setSale((bool)$request['data']['general_filters']['sale']);
            }
        }
        if ( is_array($paramSkuList) ) {
            $skuList = is_array($skuList) ? array_intersect($paramSkuList, $skuList) : $paramSkuList;
        }
        if ( is_array($skuList) ) {
            $searchModel->setSkuList($skuList);
        }

        return $searchModel;
    }

    // This function saves new assignments to my partslist, so that one or multiple receivers will get access to them:
    // FE-Example : 'setShareMyPartslist' : {'data' : {'partsListId' : 2711, 'contact_id_list' : [14, 15, 16]}}
    private function setShareMyPartslist ( $data = null ) {
        $partslistIdToShare = intval($data['data']['partsListId'], 10);
        $sharedPartslistContactsIds = $data['data']['contact_id_list'];
        $resultData = [];
        $partslistOwnershipVerified = false;
        $resultData['message'] = 'Parts list has been shared successfully';
        $resultData['result'] = '[SUCCESS] ' . $resultData['message'];
        $resultData['status'] = 'success';

        if ( !is_array($sharedPartslistContactsIds) || empty($sharedPartslistContactsIds) ) {
            $resultData['message'] = $this->__('Unexpected error occurred') . ' (1)';
            $resultData['result'] = '[ERROR] no shared partslist receiver contact-id found';
            $resultData['status'] = 'error';

            return $resultData;
        }

        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $wws_id = $sessionLoggedInCustomer->getSchrackWwsCustomerId();

        $partslists = Mage::getModel('schrackwishlist/partslist')->loadByCustomer($sessionLoggedInCustomer);

        if ( count($partslists) ) {
            // First of all: check, if given partslist-ID is really assigned to me (ownership-check):
            foreach ( $partslists as $partslist ) {
                if ( $partslist->getId() == $partslistIdToShare ) {
                    $partslistOwnershipVerified = true;
                    break;
                }
            }

            if ( $partslistOwnershipVerified == false ) {
                $resultData['message'] = $this->__('Unexpected error occurred') . ' (2)';
                $resultData['result'] = '[ERROR] partslist assignment denied';
                $resultData['status'] = 'error';

                return $resultData;
            }

            if ( is_object($sessionLoggedInCustomer) && $wws_id ) {
                $account = $sessionLoggedInCustomer->getAccount();
                // Get all contacts to check, if given contacts are really insinde my account:
                $contacts = $account->getContacts();

                foreach ( $contacts as $contact ) {
                    foreach ( $sharedPartslistContactsIds as $index => $sharedPartslistReceiverContactId ) {
                        if ( $contact->getSchrackWwsContactNumber() == $sharedPartslistReceiverContactId ) {
                            // Check first, if contact is already assinged to this partslist from sharer:
                            $query = "SELECT * FROM partslist_sharing_map WHERE schrack_wws_customer_id LIKE '" . $wws_id . "'";
                            $query .= " AND schrack_wws_contact_number_sharer = " . $sessionLoggedInCustomer->getSchrackWwsContactNumber();
                            $query .= " AND schrack_wws_contact_number_receiver = " . $sharedPartslistReceiverContactId;
                            $query .= " AND shared_partslist_id = " . $partslistIdToShare;

                            $result = $this->_readConnection->fetchOne($query);

                            if ( intval($result) > 0 ) {
                                // Already existing shared partslist should not assigned twice (-> ignore assignment):
                                continue;
                            }

                            $customer = Mage::getModel('customer/customer');
                            $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                            $customer->loadByEmail($contact->getEmail());

                            if ( $customer ) {
                                // If not already assigned, create a new assignment of a shared partslist:
                                $query = "INSERT INTO partslist_sharing_map SET schrack_wws_customer_id = '" . $wws_id . "',";
                                $query .= " schrack_wws_contact_number_sharer = " . $sessionLoggedInCustomer->getSchrackWwsContactNumber() . ",";
                                $query .= " shared_partslist_id = " . $partslistIdToShare . ",";
                                $query .= " email_sharer = '" . $sessionLoggedInCustomer->getEmail() . "',";
                                $query .= " firstname_sharer = '" . $sessionLoggedInCustomer->getFirstname() . "',";
                                $query .= " lastname_sharer = '" . $sessionLoggedInCustomer->getLastname() . "',";
                                $query .= " email_receiver = '" . $contact->getEmail() . "',";
                                $query .= " firstname_receiver = '" . $customer->getFirstname() . "',";
                                $query .= " lastname_receiver = '" . $customer->getLastname() . "',";
                                $query .= " schrack_wws_contact_number_receiver = " . $sharedPartslistReceiverContactId . ",";
                                $query .= " last_update_notification_at = '" . date('Y-m-d H:i:s') . "',";
                                $query .= " last_update_notification_flag = 1,";
                                $query .= " last_update_notification_received = 0,";
                                $query .= " created_at = '" . date('Y-m-d H:i:s') . "',";
                                $query .= " updated_at = '" . date('Y-m-d H:i:s') . "',";
                                $query .= " active = 1";

                                $this->_writeConnection->query($query);

                                $this->setEmailNotificationTransferOnSharedPartslistChange($sharedPartslistReceiverContactId,
                                    $partslistIdToShare, 'creation');

                                $resultData['message'] = 'Parts list has been shared successfully';
                                $resultData['result'] = '[SUCCESS] ' . $resultData['message'];
                                $resultData['status'] = 'success';
                            }
                        }
                    }
                }
            } else {
                $resultData['message'] = $this->__('Unexpected error occurred') . ' (3)';
                $resultData['result'] = '[ERROR] no wws-id found or session-customer-object error';
                $resultData['status'] = 'error';
            }
        } else {
            $resultData['message'] = 'no partslist found';
            $resultData['result'] = '[ERROR] ' . $resultData['message'];
            $resultData['status'] = 'error';
        }

        $resultData['message'] = $this->__($resultData['message']);

        return $resultData;
    }

    // Send psartslist as CSV
    // FE-Example : 'setTransferOfPartslistAsCSV' : {'data' : {'partslistId' : 2711, 'emailRecipients' : ['email1@sample.com', 'email2@sample.com', 'email3@sample.com']}}
    private function setTransferOfPartslistAsCSV ( $data = null, $partslistMode = 'creation' ) {
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();

        $emailSharer = $sessionLoggedInCustomer->getEmail();
        $firstnameSharer = $sessionLoggedInCustomer->getFirstname();
        $lastnameSharer = $sessionLoggedInCustomer->getLastname();
        $emailRecipients = $data['data']['emailRecipients']; // array()
        $sharedPartslistId = $data['data']['partslistId'];
        $partslistData = [];
        $index = 0;
        $counter = 0;

        // FLOOD PROTECTION -> ask in database before, if limit is not broken:
        $query = "SELECT * FROM partslist_cart_sharing_csv_counter";
        $query .= " WHERE customer_entity_id_sharer = " . $sessionLoggedInCustomer->getId();
        $query .= " AND sharing_type LIKE 'partlist'";
        $query .= " AND created_at > '" . date('Y-m-d H:i:s', strtotime('- 1 hour')) . "'";
        $query .= " AND created_at < '" . date('Y-m-d H:i:s') . "'";

        $results = $this->_readConnection->fetchAll($query);

        if ( count($results) > Mage::getStoreConfig('schrack/customer/max_share_partlists_as_csv_per_hour') ) {
            $resultData['result'] = '[ERROR] already reached email limit per hour';
            $resultData['status'] = 'error';

            return $resultData;
        } else {
            if ( (count($results) + count($emailRecipients)) > Mage::getStoreConfig('schrack/customer/max_share_partlists_as_csv_per_hour') ) {
                $resultData['result'] = '[ERROR] already reached email limit per hour with the new amount';
                $resultData['status'] = 'error';
                $resultData['message'] = $this->__('already reached email limit per hour with the new amount');

                return $resultData;
            }
        }

        // Check first, if sharer is owner of partslist:
        $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId($sessionLoggedInCustomer,
            intval($sharedPartslistId, 10));

        if ( is_object($partslist) && $partslist->getId() ) {
            $partslistItemCollection = $partslist->getItemCollection();

            // Creating content of CSV file:
            if ( count($partslistItemCollection) ) {
                // First line: set header description:
                $partslistData[$index]['article_number'] = '"' . $this->__('Items') . '"';
                $partslistData[$index]['quantity'] = '"' . $this->__('Amount') . '"';
                $partslistData[$index]['quantity_unit'] = '"' . $this->__('Qty Unit') . '"';
                $partslistData[$index]['article_name'] = '"' . $this->__('Product name') . '"';
                $partslistData[$index]['pic_url'] = '"' . $this->__('Image') . '"';
                $partslistData[$index]['price'] = '"' . $this->__('Price') . '"';
                $index++;
                foreach ( $partslistItemCollection as $item ) {
                    // Additional lines with real data:
                    $product = $item->getProduct();
                    $partslistData[$index]['article_number'] = '"' . $product->getSKU() . '"';
                    $partslistData[$index]['quantity'] = '"' . str_replace('.', ',', $item->getQty()) . '"';
                    $partslistData[$index]['quantity_unit'] = '"' . $product->getSchrackQtyunit() . '"';
                    $partslistData[$index]['article_name'] = '"' . $product->getName() . '"';
                    $partslistData[$index]['pic_url'] = '"' . Schracklive_SchrackCatalog_Helper_Image::getImageUrl($product->getMainImageUrl(),
                            Schracklive_SchrackCatalog_Helper_Image::PARTLIST_PAGE) . '"';
                    $partslistData[$index]['price'] = '"' . Mage::getStoreConfig('currency/options/default') . ' ' . str_replace('.',
                            ',', $product->getFinalPrice()) . '"';
                    $counter = $index;
                    $index++;
                }
            } else {
                $resultData['result'] = '[ERROR] wrong partslist assignment';
                $resultData['status'] = 'error';

                return $resultData;
            }
        } else {
            $resultData['result'] = '[ERROR] partslist problem (object could not be initiated)';
            $resultData['status'] = 'error';

            return $resultData;
        }

        // Creating CSV file:
        if ( !empty($partslistData) ) {
            $csvFilename = '/tmp/shared_partslist_' . $sharedPartslistId . '_' . date('Y_m_d_H_i_s') . '.csv';
            $filePointer = fopen($csvFilename, 'w+');
            foreach ( $partslistData as $index => $field ) {
                $line = $field['article_number'] . ';' . $field['quantity'] . ';' . $field['quantity_unit'] . ';' . $field['article_name'] . ';' . $field['pic_url'] . ';' . $field['price'] . ';';
                if ( $counter > $index ) {
                    $line = $line . "\n";
                }
                fwrite($filePointer, $line);
            }
            fclose($filePointer);
        }

        // Send mail with attached partslist-csv to one or multiple recipients:
        try {
            /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
            $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
            $templateArgs = [
                'sharerSalutation' => $this->__($sessionLoggedInCustomer->getSalutation()),
                'sharerFirstname' => $firstnameSharer,
                'sharerLastname' => $lastnameSharer,
                'sharerEmail' => $emailSharer,
                'back_url' => ''
            ];
            $singleMailApi->setMagentoTransactionalTemplateIDfromConfigPath('schrack/customer/notifySharePartslistCSVEmailId');
            $singleMailApi->setMagentoTransactionalTemplateVariables($templateArgs);
            // Send partslist as CSV attachment:
            $singleMailApi->addAttachement('shared_partslist.csv', file_get_contents($csvFilename));
            $singleMailApi->setToEmailAddresses($emailRecipients);
            $singleMailApi->setFromEmail('general');

            $singleMailApi->createAndSendMail();
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            $resultData['result'] = '[ERROR] ' . $ex->getMessage();
            $resultData['status'] = 'error';

            return $resultData;
        }

        // Write entry (count) to database after successful transfer:
        foreach ( $emailRecipients as $index => $emailRecipient ) {
            $query = "INSERT INTO partslist_cart_sharing_csv_counter";
            $query .= " SET sharing_type = 'partlist',";
            $query .= " customer_entity_id_sharer = " . $sessionLoggedInCustomer->getId() . ",";
            $query .= " email_receiver = ?,";
            $query .= " created_at = '" . date('Y-m-d H:i:s') . "'";

            $this->_writeConnection->query($query, [$emailRecipient]);
        }

        $resultData['result'] = '[SUCCESS] Parts list has been shared successfully including CSV file';
        $resultData['message'] = $this->__('Parts list has been shared successfully including CSV file');
        $resultData['status'] = 'success';

        return $resultData;
    }

    // Set notification flag to all receiver contacts recordset, that something was changed on changed partslist:
    // FE-Example : 'setUpdateNotificationFlagFromPartslist' : {'data' : {'partslistId' : 2711}}
    private function setUpdateNotificationFlagFromPartslist ( $data = null ) {
        // Only the owner of the transferred partslist is allowed to change his partslist and set the transferred partslist as "changed" to all notification receivers:
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $wws_id = $sessionLoggedInCustomer->getSchrackWwsCustomerId();
        $wwsContactNumberSharer = $sessionLoggedInCustomer->getSchrackWwsContactNumber();
        $emailSharer = $sessionLoggedInCustomer->getEmail();
        $sharedPartslistId = $data['data']['partslistId'];
        $resultData = [];

        if ( $sharedPartslistId ) {
            $query = "UPDATE partslist_sharing_map SET last_update_notification_flag = 1,";
            $query .= " last_update_notification_at = '" . date('Y-m-d H:i:s') . "',";
            $query .= " last_update_notification_received = 0,";
            $query .= " updated_at = '" . date('Y-m-d H:i:s') . "'";
            $query .= " WHERE schrack_wws_customer_id LIKE '" . $wws_id . "'";
            $query .= " AND email_sharer LIKE '" . $emailSharer . "'";
            $query .= " AND schrack_wws_contact_number_sharer = " . $wwsContactNumberSharer;
            $query .= " AND shared_partslist_id = " . $sharedPartslistId;

            $this->_writeConnection->query($query);

            $resultData['result'] = '[SUCCESS] update flag and modification-date successfully set to affected contacts';
            $resultData['status'] = 'success';
        } else {
            $resultData['result'] = '[ERROR] no shared partslist-id found';
            $resultData['status'] = 'error';
        }

        return $resultData;
    }

    // Reset flag, that partslist change-info was received (already opened):
    // FE-Example : 'setReceivedUpdateNotificationFlagFromPartslist' : {'data' : {'partslistId' : 2711}}
    private function setReceivedUpdateNotificationFlagFromPartslist ( $data = null ) {
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $wws_id = $sessionLoggedInCustomer->getSchrackWwsCustomerId();
        $wwsContactNumberReceiver = $sessionLoggedInCustomer->getSchrackWwsContactNumber();
        $emailReceiver = $sessionLoggedInCustomer->getEmail();
        $sharedPartslistId = $data['data']['partslistId'];
        $resultData = [];

        if ( !$wws_id || $wws_id == '' ) {
            $resultData['result'] = '[ERROR] no wws-id found';
            $resultData['status'] = 'error';
            $resultData = json_encode(['messages' => [$resultData['result']], "ok" => false]);

            return $resultData;
        }

        if ( !$wwsContactNumberReceiver || $wwsContactNumberReceiver == '' ) {
            $resultData['result'] = '[ERROR] no wws-contact-number found (user not logged in)';
            $resultData['status'] = 'error';
            $resultData = json_encode(['messages' => [$resultData['result']], "ok" => false]);

            return $resultData;
        }

        if ( !$sharedPartslistId || $sharedPartslistId == '' ) {
            $resultData['result'] = '[ERROR] no shared partslist-id found';
            $resultData['status'] = 'error';
            $resultData = json_encode(['messages' => [$resultData['result']], "ok" => false]);

            return $resultData;
        }

        if ( $sharedPartslistId && $wws_id && $wwsContactNumberReceiver ) {
            $query = "UPDATE partslist_sharing_map SET last_update_notification_flag = 0,";
            $query .= " last_update_notification_received = 1,";
            $query .= " updated_at = '" . date('Y-m-d H:i:s') . "'";
            $query .= " WHERE schrack_wws_customer_id LIKE '" . $wws_id . "'";
            $query .= " AND email_receiver LIKE '" . $emailReceiver . "'";
            $query .= " AND schrack_wws_contact_number_receiver = " . $wwsContactNumberReceiver;
            $query .= " AND shared_partslist_id = " . $sharedPartslistId;
            $query .= " AND active = 1";
            $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $this->_writeConnection->query($query);

            $resultData['result'] = '[SUCCESS] update flag and modification-date successfully reset to my affected partslist';
            $resultData['status'] = 'success';
            $resultData = json_encode(['messages' => [$resultData['result']], "ok" => true]);
        }

        return $resultData;
    }

    // Delete shared partslist assignment completely by owner of shared partslist:
    // FE-Example : 'setDeleteMySharedPartslistAssignment' : {'data' : {'partslistId' : 2711, 'contactId' : 15, 'deletionMode' : 'sharer'}}
    // Params-Hint #1: 'deletionMode' : 'sharer' OR 'deletionMode' = 'receiver' (person, who wants to remove the assignment)
    private function setDeleteMySharedPartslistAssignment ( $data = null ) {
        // Only the owner of the transferred partslist is allowed to delete his assigned shared partslist:
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $wws_id = $sessionLoggedInCustomer->getSchrackWwsCustomerId();

        $sharedPartslistId = $data['data']['partslistId'];
        $receiverContactId = $data['data']['contactId'];
        if ( $data && isset($data['data']) && isset($data['data']['deletionMode']) ) {
            $deletionMode = $data['data']['deletionMode'];
        } else {
            $deletionMode = 'sharer';
        }
        $resultData = [];

        if ( !$receiverContactId ) {
            $resultData['message'] = 'no shared partslist-id found';
            $resultData['result'] = '[ERROR] ' . $resultData['message'];
            $resultData['status'] = 'error';
            $resultData['message'] = $this->__($resultData['message']);

            return $resultData;
        }

        if ( $sharedPartslistId ) {
            if ( $deletionMode == 'sharer' ) {
                $wwsContactNumberSharer = $sessionLoggedInCustomer->getSchrackWwsContactNumber();
                $emailSharer = $sessionLoggedInCustomer->getEmail();

                $query = "DELETE FROM partslist_sharing_map";
                $query .= " WHERE schrack_wws_customer_id LIKE '" . $wws_id . "'";
                $query .= " AND email_sharer LIKE '" . $emailSharer . "'";
                $query .= " AND schrack_wws_contact_number_sharer = " . $wwsContactNumberSharer;
                $query .= " AND shared_partslist_id = " . $sharedPartslistId;
                $query .= " AND schrack_wws_contact_number_receiver = " . $receiverContactId;
            }

            if ( $deletionMode == 'receiver' ) {
                $wwsContactNumberReceiver = $sessionLoggedInCustomer->getSchrackWwsContactNumber();
                $emailReceiver = $sessionLoggedInCustomer->getEmail();

                $query = "DELETE FROM partslist_sharing_map";
                $query .= " WHERE schrack_wws_customer_id LIKE '" . $wws_id . "'";
                $query .= " AND email_receiver LIKE '" . $emailReceiver . "'";
                $query .= " AND schrack_wws_contact_number_receiver = " . $wwsContactNumberReceiver;
                $query .= " AND shared_partslist_id = " . $sharedPartslistId;
            }

            $this->_writeConnection->query($query);

            $resultData['message'] = 'partslist-sharing-assignment successfully deleted';
            $resultData['result'] = '[SUCCESS] ' . $resultData['message'];
            $resultData['status'] = 'success';
        } else {
            $resultData['message'] = 'no shared partslist-id found';
            $resultData['result'] = '[ERROR] ' . $resultData['message'];
            $resultData['status'] = 'error';
        }

        $resultData['message'] = $this->__($resultData['message']);

        return $resultData;
    }

// Delete shared partslist assignment completely by owner of shared partslist:
    // FE-Example : 'setDeleteMyReceivedSharedPartslistAssignment' : {'data' : {'partslistId' : 2711}}
    private function setDeleteMyReceivedSharedPartslistAssignment ( $data = null ) {
        // Only the owner of the transferred partslist is allowed to delete his assigned shared partslist:
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $wws_id = $sessionLoggedInCustomer->getSchrackWwsCustomerId();
        $wwsContactNumberReceiver = $sessionLoggedInCustomer->getSchrackWwsContactNumber();
        $emailReceiver = $sessionLoggedInCustomer->getEmail();
        $resultData = [];
        $resultData['message'] = 'action completed without any changes';
        $resultData['result'] = '[SUCCESS] ' . $resultData['message'];
        $resultData['status'] = 'success';

        if ( !isset($data['data']) || !isset($data['data']['partslistId']) ) {
            $resultData['result'] = '[ERROR] no data or shared partslist-id found';
            $resultData['status'] = 'error';

            return $resultData;
        }

        $sharedPartslistId = intval($data['data']['partslistId'], 10);

        if ( $sharedPartslistId ) {
            $query = "DELETE FROM partslist_sharing_map";
            $query .= " WHERE schrack_wws_customer_id LIKE '" . $wws_id . "'";
            $query .= " AND email_receiver LIKE '" . $emailReceiver . "'";
            $query .= " AND schrack_wws_contact_number_receiver = " . $wwsContactNumberReceiver;
            $query .= " AND shared_partslist_id = " . $sharedPartslistId;

            $this->_writeConnection->query($query);

            $resultData['message'] = 'partslist-sharing-assignment successfully deleted from special contact';
            $resultData['result'] = '[SUCCESS] ' . $resultData['message'];
            $resultData['status'] = 'success';
        } else {
            $resultData['message'] = 'no shared partslist-id found';
            $resultData['result'] = '[ERROR] ' . $resultData['message'];
            $resultData['status'] = 'error';
        }

        $resultData['message'] = $this->__($resultData['message']);

        return $resultData;
    }

    // Deactivate shared partslist for hide in view:
    // FE-Example : 'setDeactivateSharedPartslist' : {'data' : {'partslistId' : 2711, 'contactId' : 16}}
    private function setDeactivateSharedPartslist ( $data = null ) {
        // Only the owner of the transferred partslist is allowed to delete his assigned shared partslist:
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $wws_id = $sessionLoggedInCustomer->getSchrackWwsCustomerId();
        $wwsContactNumberSharer = $sessionLoggedInCustomer->getSchrackWwsContactNumber();
        $emailSharer = $sessionLoggedInCustomer->getEmail();
        $sharedPartslistId = $data['data']['partslistId'];
        $receiverContactId = $data['data']['contactId'];
        $resultData = [];

        if ( !$receiverContactId ) {
            $resultData['result'] = '[ERROR] no shared partslist-id found';
            $resultData['status'] = 'error';

            return $resultData;
        }

        if ( $sharedPartslistId ) {
            $query = "UPDATE partslist_sharing_map SET active = 0,";
            $query .= " updated_at = '" . date('Y-m-d H:i:s') . "'";
            $query .= " WHERE schrack_wws_customer_id LIKE '" . $wws_id . "'";
            $query .= " AND email_sharer LIKE '" . $emailSharer . "'";
            $query .= " AND schrack_wws_contact_number_sharer = " . $wwsContactNumberSharer;
            $query .= " AND shared_partslist_id = " . $sharedPartslistId;
            $query .= " AND schrack_wws_contact_number_receiver = " . $receiverContactId;

            $this->_writeConnection->query($query);

            $resultData['result'] = '[SUCCESS] partslist-sharing-assignment successfully deactivated from special contact';
            $resultData['status'] = 'success';
        } else {
            $resultData['result'] = '[ERROR] no shared partslist-id found';
            $resultData['status'] = 'error';
        }

        return $resultData;
    }

    // Reactivate shared partslist for show in view:
    // FE-Example : 'setReactivateSharedPartslist' : {'data' : {'partslistId' : 2711, 'contactId' : 16}}
    private function setReactivateSharedPartslist ( $data = null ) {
        // Only the owner of the transferred partslist is allowed to delete his assigned shared partslist:
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $wws_id = $sessionLoggedInCustomer->getSchrackWwsCustomerId();
        $wwsContactNumberSharer = $sessionLoggedInCustomer->getSchrackWwsContactNumber();
        $emailSharer = $sessionLoggedInCustomer->getEmail();
        $sharedPartslistId = $data['data']['partslistId'];
        $receiverContactId = $data['data']['contactId'];
        $resultData = [];

        if ( !$receiverContactId ) {
            $resultData['result'] = '[ERROR] no shared partslist-id found';
            $resultData['status'] = 'error';

            return $resultData;
        }

        if ( $sharedPartslistId ) {
            $query = "UPDATE partslist_sharing_map SET active = 1,";
            $query .= " updated_at = '" . date('Y-m-d H:i:s') . "'";
            $query .= " WHERE schrack_wws_customer_id LIKE '" . $wws_id . "'";
            $query .= " AND email_sharer LIKE '" . $emailSharer . "'";
            $query .= " AND schrack_wws_contact_number_sharer = " . $wwsContactNumberSharer;
            $query .= " AND shared_partslist_id = " . $sharedPartslistId;
            $query .= " AND schrack_wws_contact_number_receiver = " . $receiverContactId;

            $this->_writeConnection->query($query);

            $resultData['result'] = '[SUCCESS] partslist-sharing-assignment successfully reactivated from special contact';
            $resultData['status'] = 'success';
        } else {
            $resultData['result'] = '[ERROR] no shared partslist-id found';
            $resultData['status'] = 'error';
        }

        return $resultData;
    }

// TODO : Translation-Keys -> "Your Newly Shared Partslist From" / "Your Newly Shared Partslist Information Body"
    // This is for sending email to receiver, that a partslist was shared:
    private function setEmailNotificationTransferOnSharedPartslistChange (
        $emailRecipientContactId,
        $partslistIdToShare,
        $partslistMode = 'creation'
    ) {
        if ( $partslistMode == 'creation' ) {
            // TODO : Here could be an alternate transactional e-mail defined for creation-mode, respectively change!
        }

        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $emailSharer = $sessionLoggedInCustomer->getEmail();
        $firstnameSharer = $sessionLoggedInCustomer->getFirstname();
        $lastnameSharer = $sessionLoggedInCustomer->getLastname();
        $account = $sessionLoggedInCustomer->getAccount();
        $contacts = $account->getContacts();
        $resultData = [];

        $xmlPath = 'schrack/customer/notifySharePartslistChangeEmailId';
        if ( count($contacts) ) {
            /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
            $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
            $singleMailApi->setMagentoTransactionalTemplateIDfromConfigPath($xmlPath);
            $singleMailApi->setMagentoTransactionalTemplateVariables([
                'sharerSalutation' => $this->__($sessionLoggedInCustomer->getSalutation()),
                'sharerFirstname' => $firstnameSharer,
                'sharerLastname' => $lastnameSharer,
                'sharerEmail' => $emailSharer,
                'back_url' => ''
            ]);
            $singleMailApi->setFromEmail('general');
            foreach ( $contacts as $contact ) {
                if ( $contact->getSchrackWwsContactNumber() == $emailRecipientContactId ) {
                    $singleMailApi->addToEmailCrmID($contact->getSchrackS4yId());
                }
            }
            if ( $singleMailApi->getRecipientCount() > 0 ) {
                $singleMailApi->createAndSendMail();
            } else {
                $resultData['result'] = '[ERROR] no matching contacts found';
                $resultData['status'] = 'error';

                return $resultData;
            }
        } else {
            $resultData['result'] = '[ERROR] no contacts found';
            $resultData['status'] = 'error';

            return $resultData;
        }

        $resultData['result'] = '[SUCCESS] email for shared partslist successfully sent to receiver';
        $resultData['status'] = 'success';

        return $resultData;
    }

    // Send cart as CSV
    // FE-Example : 'setTransferOfCartAsCSV' : {'data' : {'emailRecipients' : ['email1@sample.com', 'email2@sample.com', 'email3@sample.com']}}
    private function setTransferOfCartAsCSV ( $data = null ) {
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();

        $emailSharer = $sessionLoggedInCustomer->getEmail();
        $firstnameSharer = $sessionLoggedInCustomer->getFirstname();
        $lastnameSharer = $sessionLoggedInCustomer->getLastname();
        $emailRecipients = $data['data']['emailRecipients']; // array()
        $cartData = [];
        $index = 0;
        $counter = 0;

        // FLOOD PROTECTION -> ask in database before, if limit is not broken:
        $query = "SELECT * FROM partslist_cart_sharing_csv_counter";
        $query .= " WHERE customer_entity_id_sharer = " . $sessionLoggedInCustomer->getId();
        $query .= " AND sharing_type LIKE 'cart'";
        $query .= " AND created_at > '" . date('Y-m-d H:i:s', strtotime('- 1 hour')) . "'";
        $query .= " AND created_at < '" . date('Y-m-d H:i:s') . "'";

        $results = $this->_readConnection->fetchAll($query);

        if ( count($results) > Mage::getStoreConfig('schrack/customer/max_share_cart_as_csv_per_hour') ) {
            $resultData['result'] = '[ERROR] already reached email limit per hour';
            $resultData['status'] = 'error';

            return $resultData;
        } else {
            if ( (count($results) + count($emailRecipients)) > Mage::getStoreConfig('schrack/customer/max_share_cart_as_csv_per_hour') ) {
                $resultData['result'] = '[ERROR] already reached email limit per hour with the new amount';
                $resultData['status'] = 'error';
                $resultData['message'] = $this->__('already reached email limit per hour with the new amount');

                return $resultData;
            }
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();

        if ( !count($quote) ) {
            $resultData['result'] = '[ERROR] no cart available (empty?)';
            $resultData['status'] = 'error';

            return $resultData;
        }

        $quoteId = $quote->getId();

        // First line: set header description:
        $cartData[$index]['article_number'] = '"' . $this->__('Items') . '"';
        $cartData[$index]['quantity'] = '"' . $this->__('Amount') . '"';
        $cartData[$index]['quantity_unit'] = '"' . $this->__('Qty Unit') . '"';
        $cartData[$index]['article_name'] = '"' . $this->__('Product name') . '"';
        $cartData[$index]['pic_url'] = '"' . $this->__('Image') . '"';
        $cartData[$index]['price'] = '"' . $this->__('Price') . '"';
        $index++;
        foreach ( $quote->getAllItems() as $item ) {
            // Additional lines with real data:
            $product = $item->getProduct();
            $cartData[$index]['article_number'] = '"' . $product->getSKU() . '"';
            $cartData[$index]['quantity'] = '"' . str_replace('.', ',', $item->getQty()) . '"';
            $cartData[$index]['quantity_unit'] = '"' . $product->getSchrackQtyunit() . '"';
            $cartData[$index]['article_name'] = '"' . $product->getName() . '"';

            $cartData[$index]['pic_url'] = '"' . Schracklive_SchrackCatalog_Helper_Image::getImageUrl($product->getMainImageUrl(),
                    Schracklive_SchrackCatalog_Helper_Image::PRODUCT_DEFAULT) . '"';
            $cartData[$index]['price'] = '"' . Mage::getStoreConfig('currency/options/default') . ' ' . str_replace('.',
                    ',', $product->getFinalPrice()) . '"';
            $counter = $index;
            $index++;
        }

        // Creating CSV file:
        if ( !empty($cartData) ) {
            $csvFilename = '/tmp/shared_cart_' . $quoteId . '_' . date('Y_m_d_H_i_s') . '.csv';
            $filePointer = fopen($csvFilename, 'w+');
            foreach ( $cartData as $index => $field ) {
                $line = $field['article_number'] . ';' . $field['quantity'] . ';' . $field['quantity_unit'] . ';' . $field['article_name'] . ';' . $field['pic_url'] . ';' . $field['price'] . ';';
                if ( $counter > $index ) {
                    $line = $line . "\n";
                }
                fwrite($filePointer, $line);
            }
            fclose($filePointer);
        }

        /*
                // Send cart as CSV attachment:
                $storeId = $sessionLoggedInCustomer->getStoreId();
                $xmlPath = 'schrack/customer/notifyShareCartCSVEmailId';
                $templateId = Mage::getStoreConfig($xmlPath, $storeId);
        */

        // Send mail with attached cart-csv to one or multiple recipients:
        try {
            /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
            $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
            $templateArgs = [
                'sharerSalutation' => $this->__($sessionLoggedInCustomer->getSalutation()),
                'sharerFirstname' => $firstnameSharer,
                'sharerLastname' => $lastnameSharer,
                'sharerEmail' => $emailSharer,
                'back_url' => ''
            ];
            $singleMailApi->setMagentoTransactionalTemplateIDfromConfigPath('schrack/customer/notifyShareCartCSVEmailId');
            $singleMailApi->setMagentoTransactionalTemplateVariables($templateArgs);
            $singleMailApi->addAttachement('shared_cart.csv', file_get_contents($csvFilename));
            $singleMailApi->setToEmailAddresses($emailRecipients);
            $singleMailApi->setFromEmail('general');

            // Write entry (count) to database after successful transfer:
            if ( is_array($emailRecipients) && !empty($emailRecipients) ) {
                foreach ( $emailRecipients as $index => $emailRecipient ) {
                    $query = "INSERT INTO partslist_cart_sharing_csv_counter";
                    $query .= " SET sharing_type = 'cart',";
                    $query .= " customer_entity_id_sharer = " . $sessionLoggedInCustomer->getId() . ",";
                    $query .= " email_receiver = ?,";
                    $query .= " created_at = '" . date('Y-m-d H:i:s') . "'";

                    $this->_writeConnection->query($query, [$emailRecipient]);
                }
            }

            $singleMailApi->createAndSendMail();
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            $resultData['result'] = '[ERROR] ' . $ex->getMessage();
            $resultData['status'] = 'error';

            return $resultData;
        }

        /*
                $translate = Mage::getSingleton('core/translate');

                $translate->setTranslateInline(false);
                $mailTemplate = Mage::getModel('core/email_template');
                $mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $storeId));

                // Send mail with attached cart-csv to one or multiple recipients:
                try {
                    foreach ($emailRecipients as $index => $emailRecipient) {
                        // attachment for bloody transactional mail hast to be set again for every recipient
                        $mailTemplate->getMail()->createAttachment(
                            file_get_contents($csvFilename),
                            Zend_Mime::TYPE_OCTETSTREAM,
                            Zend_Mime::DISPOSITION_ATTACHMENT,
                            Zend_Mime::ENCODING_BASE64,
                            'shared_cart.csv'
                        );

                        $mailTemplate->sendTransactional(
                            $templateId,
                            'general',
                            $emailRecipient,
                            $firstnameSharer . ' ' . $lastnameSharer,
                            array('sharerSalutation' => $this->__($sessionLoggedInCustomer->getSalutation()), 'sharerFirstname' => $firstnameSharer, 'sharerLastname' => $lastnameSharer,'sharerEmail' => $emailSharer, 'back_url' => ''));
                    }

                    // Write entry (count) to database after successful transfer:
                    foreach ($emailRecipients as $index => $emailRecipient) {
                        $query  = "INSERT INTO partslist_cart_sharing_csv_counter";
                        $query .= " SET sharing_type = 'cart',";
                        $query .= " customer_entity_id_sharer = " . $sessionLoggedInCustomer->getId() . ",";
                        $query .= " email_receiver = ?,";
                        $query .= " created_at = '" . date('Y-m-d H:i:s') . "'";

                        $this->_writeConnection->query($query, array($emailRecipient));
                    }
                } catch (Exception $ex) {
                    Mage::logException($ex);
                    $resultData['message'] = $this->__('Unexpected error occurred');
                    $resultData['result'] = '[ERROR] ' . $ex->getMessage();
                    $resultData['status'] = 'error';
                    return $resultData;
                }
        */

        $resultData['message'] = $this->__('mailtransfer for shared cart (incl. CSV-file) completed');
        $resultData['result'] = '[SUCCESS] ' . $resultData['message'];
        $resultData['status'] = 'success';
        $resultData['message'] = $this->__($resultData['message']);

        return $resultData;
    }

    private function getPrevMessage () {
        $mage = Mage::getSingleton('core/session')->getMessages()->getItems();
        if ( $mage ) {
            return $mage[0]->getText();
        } else {
            return "";
        }
    }

    private function getMyActivePartslist ( $data = null ) {
        $partslistData = [];
        $index = 0;
        $stockHelper = Mage::helper('schrackcataloginventory/stock');
        //$drumHelper    = Mage::helper('schrackcatalog/drum');
        $productHelper = Mage::helper('schrackcatalog/product');
        if ( !empty($data) ) {
            $activePartslist = Mage::getModel('schrackwishlist/partslist')->load($data['data']['id']);
        } else {
            $activePartslist = Mage::helper('schrackwishlist/partslist')->getActivePartslist();
        }
        if ( $activePartslist ) {
            $items = $activePartslist->getItemCollection();
            $skus = array();
            foreach ($items as $item) {
                if ( $product = $item->getProduct() ) {
                    $skus[] = $product->getSku();
                }
            }
            /* @var $promoHelper Schracklive_Promotions_Helper_Data */
            $promoHelper = Mage::helper('promotions');
            $promoMap = $promoHelper->getSKUsToPromotionFlags($skus);

            foreach ( $items as $item ) {
                if ( $product = $item->getProduct() ) {
                    $continueUrl = Mage::helper('core')->urlEncode(Mage::getUrl('wishlist/partslist/view/'));
                    $urlParamName = Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED;
                    $params = [
                        'id' => $activePartslist->getId(),
                        $urlParamName => $continueUrl
                    ];
                    $partslistData[$index]['partslist_id'] = $activePartslist->getId();
                    $partslistData[$index]['partslist_description'] = $activePartslist->getDescription();
                    $isNotSaleable = false;
                    if (in_array($product->getSchrackStsStatuslocal(), array('strategic_no', 'unsaleable', 'gesperrt', 'tot'))) {
                        $isNotSaleable = true;
                    }
                    $partslistData[$index]['isNotSaleable'] = $isNotSaleable;
                    $partslistData[$index]['article_number'] = $product->getSKU();
                    $partslistData[$index]['product_url'] = $product->getProductUrl();
                    $partslistData[$index]['quantity'] = $item->getQty();
                    $partslistData[$index]['quantity_unit'] = $product->getSchrackQtyunit();
                    $partslistData[$index]['quantity_label'] = $product->getQtyLabel();
                    $partslistData[$index]['article_name'] = $product->getName();
                    $partslistData[$index]['main_vpe'] = $product->getMainVpeName();
                    $partslistData[$index]['additional_text'] = $product->getSchrackLongTextAddition(); // ?????????
                    $partslistData[$index]['pic_url'] = Schracklive_SchrackCatalog_Helper_Image::getImageUrl($product->getMainImageUrl(),
                        Schracklive_SchrackCatalog_Helper_Image::PARTLIST_PAGE);
                    $partslistData[$index]['price'] = $product->getFinalPrice();
                    $partslistData[$index]['is_promotion'] = $promoMap[$product->getSKU()];
                    $partslistData[$index]['allPickupLocations'] = $productHelper->getAllPickupQuantities($product);
                    $partslistData[$index]['getAllPickupQuantities'] = $productHelper->getAllPickupQuantities($product);
                    $partslistData[$index]['getAllDeliveryStockNumbers'] = $stockHelper->getAllDeliveryStockNumbers();
                    $partslistData[$index]['warehouseIdDefaultDelivery'] = (int)$stockHelper->getLocalDeliveryStock()->getStockNumber();
                    $partslistData[$index]['warehouseIdDefaultPickup'] = (int)$stockHelper->getCustomerPickupStockNumber(null);
                    $partslistData[$index]['isAvailInAnyDeliveryStock'] = $productHelper->isAvailInAnyDeliveryStock($product);
                    $partslistData[$index]['getSummarizedFormattedDeliveryQuantities'] = $productHelper->getSummarizedFormattedDeliveryQuantities($product);
                    $partslistData[$index]['getFormattedDeliveryQuantity'] = $productHelper->getFormattedDeliveryQuantity($product);
                    $getUnformattedDeliveryQuantity = str_replace('.', '',
                        $productHelper->getFormattedDeliveryQuantity($product));
                    list($deliveryQty, $deliveryQtyUnit) = explode(' ', $getUnformattedDeliveryQuantity);
                    $partslistData[$index]['getUnformattedDeliveryQuantity'] = $deliveryQty;
                    $partslistData[$index]['isAvailInAnyPickupStock'] = $productHelper->isAvailInAnyPickupStock($product);
                    $partslistData[$index]['summarizedFormattedPickupQuantities'] = $productHelper->getSummarizedFormattedPickupQuantities($product);
                    $partslistData[$index]['getFormattedPickupQuantity'] = $productHelper->getFormattedPickupQuantity($product);
                    $getUnformattedPickupQuantity = str_replace('.', '',
                        $productHelper->getFormattedPickupQuantity($product));
                    list($pickupQty, $pickupQtyUnit) = explode(' ', $getUnformattedPickupQuantity);
                    $partslistData[$index]['getUnformattedPickupQuantity'] = $pickupQty;
                    $partslistData[$index]['getFormattedPickupQuantity'] = $productHelper->getFormattedPickupQuantity($product);
                    $hasDrums = $productHelper->hasDrums($product);
                    if ( $hasDrums ) {
                        $partslistData[$index]['availableDrums'] = Mage::helper('schrackcatalog/info')->getAvailableDrums($product,
                            $stockHelper->getAllStockNumbers());
                    }
                    $partslistData[$index]['id'] = $item->getId();
                    $partslistData[$index]['category'] = $item->getProduct()->getCategoryId4googleTagManager();
                    $partslistData[$index]['productid'] = $item->getProduct()->getId();

                    $desc = $item->getDescription();
                    $partslistData[$index]['description'] = empty($desc) ? '' : $desc;
                    $partslistData[$index]['qty'] = $item->getQty() * 1;

                    $partslistData[$index]['removeurl'] = Mage::getUrl(
                        'wishlist/partslist/remove', [
                            'id' => $activePartslist->getId(),
                            'item' => $item->getPartslistItemId()
                        ]
                    );
                    $partslistData[$index]['addtocarturl'] = $this->getAddToCartUrlForPartslist($item,
                        $activePartslist->getId());
                    $index++;
                }
            }
            // $this->_getUrl('wishlist/partslist/remove', array('id' => $partslist->getId()));
            if ( !empty($data) ) {
                echo json_encode($partslistData);
                die;
            } else {
                return $partslistData;
            }
        } else {
            $resultData['result'] = '[ERROR] no active partslist found';
            $resultData['status'] = 'error';

            return $resultData;
        }
    }

    /**
     * Retrieve URL for adding item to shoping cart
     *
     * @param string|Mage_Catalog_Model_Product|Mage_Wishlist_Model_Item $item
     * @param int Partstlist ID
     * @return  string
     */
    private function getAddToCartUrlForPartslist ( $item, $id ) {
        $continueUrl = Mage::helper('core')->urlEncode(Mage::getUrl('wishlist/partslist/view', [
            '_current' => true,
            '_use_rewrite' => true,
            '_store_to_url' => true,
        ]));

        $urlParamName = Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED;
        $params = [
            'id' => $id,
            'item' => is_string($item) ? $item : $item->getPartslistItemId(),
            $urlParamName => $continueUrl
        ];

        return Mage::getUrl('wishlist/partslist/cart', $params);
    }

    private function getCustomerInformation ( $data = null ) {
        $res = new stdClass();
        if ( Mage::getSingleton('customer/session')->isLoggedIn() ) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $res->name = $customer->getName();
            $res->customer_id = $customer->getSchrackWwsCustomerId();
            $res->acl_role = $customer->getSchrackAclRole();
            if ( $customer->getGroupId() != 4 ) {
                $res->company_name = $customer->getAccount()->getName1();
            } else {
                $res->company_name = 'SCHRACK Technik Employee';
            }
            $res->fav_customer_list = $customer->getSchrackCustomerFavoriteList();
            $res->translation_cid = $this->__('Customer ID');
            $res->image = null; // TODO: use real image
        }
        $res->site_header_info_status = $this->_siteheaderinfostatus;
        $res->site_header_info_link = $this->_siteheaderinfolink;

        return $res;
    }

    private function getCartItemCount ( $data = null ) {
        $numberOfDifferentItemsInCart = [];

        foreach ( Mage::getSingleton('checkout/cart')->getQuote()->getAllVisibleItems() as $item ) {
            $product = $item->getProduct();
            $numberOfDifferentItemsInCart[] = $product->getSku();
        }

        return count($numberOfDifferentItemsInCart);
    }

    private function getSearchBarCategories ( $data = null ) {
        return $this->getCachedResult('getSearchBarCategoriesImpl', 'searchbar_categories', false, $data, 24);
    }

    private function getSearchBarCategoriesImpl ( $data = null ) {
        $res = [];
        $sql = " SELECT cat.*, attrName.value AS name, attrUrlPath.value AS url FROM catalog_category_entity AS cat"
            . " LEFT JOIN catalog_category_entity_varchar attrName    ON (cat.entity_id = attrName.entity_id    AND attrName.attribute_id    IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'name'))"
            . " LEFT JOIN catalog_category_entity_varchar attrUrlPath ON (cat.entity_id = attrUrlPath.entity_id AND attrUrlPath.attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'url_path'))"
            . " WHERE attrUrlPath.store_id = {$this->_storeId} AND level = 2"
            . " ORDER BY position;";

        $dbRes = $this->_readConnection->fetchAll($sql);
        foreach ( $dbRes as $row ) {
            if ( $row['name'] == 'PROMOTIONS_TOP' ) {
                continue;
            }
            $rec = new stdClass();
            $rec->name = $row['name'];
            $rec->url = Mage::getUrl($row['url']);
            $rec->id = intval($row['entity_id']);
            $res[] = $rec;
        }

        return $res;
    }

    private function getFeaturedProducts ( $data = null ) {
        return $this->getLastViewedProducts($data);
    }

    private function getBaseUrl ( $data = null ) {
        $_partslistHelper = Mage::helper('schrackwishlist/partslist');

        return $_partslistHelper->getBaseUrl();

        return;
    }

    private function getLastViewedProducts ( $data = null ) {
        $collection = Mage::getSingleton('Mage_Reports_Block_Product_Viewed')->getItemsCollection();
        $res = $this->mkProductListFromCollection($collection);

        return $res;
    }

    private function getLastViewedProductSKUs ( $data = null ) {
        $collection = Mage::getSingleton('Mage_Reports_Block_Product_Viewed')->getItemsCollection();
        $collection->setPageSize(self::PRODUCT_ROW_LIMIT)->setCurPage(1);
        $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns('sku')
            ->group('sku');
        $res = $collection->getColumnValues('sku');

        return $res;
    }

    private function getLastPurchasedProducts ( $data = null ) {
        return $this->getCachedResult('getLastPurchasedProductsImpl', 'top_purchased_products_', true, $data);
    }

    private function getLastPurchasedProductsImpl ( $data = null ) {
        if ( Mage::getSingleton('customer/session')->isLoggedIn() ) {
            $orderHelper = Mage::helper('schracksales/order');
            $productHelper = Mage::helper('schrackcatalog/product');
            $skuList = $orderHelper->getLastPurchasedProductSKUs(self::PRODUCT_ROW_LIMIT);
            $entityIdList = $productHelper->getProductIDsForSKUs($skuList);
            $productList = $this->mkProductList($entityIdList);

            return $productList;
        } else {
            return [];
        }
    }

    private function getLastPurchasedProductSKUs ( $data = null ) {
        return $this->getCachedResult('getLastPurchasedProductSKUsImpl', 'top_purchased_product_skus_', true, $data);
    }

    private function getLastPurchasedProductSKUsImpl ( $data = null ) {
        if ( Mage::getSingleton('customer/session')->isLoggedIn() ) {
            $helper = Mage::helper('schracksales/order');

            return $helper->getLastPurchasedProductSKUs(self::PRODUCT_LIST_LIMIT);
        } else {
            return [];
        }
    }

    private function getPromotionProducts ( $data = null ) {
        return $this->getCachedResult('getPromotionProductsImpl', 'top_promotions_', true, $data);
    }

    private function getPromotionProductsImpl ( $data = null ) {
        $collection = Mage::helper('catalog/product')->getPromotionProductCollection();
        $res = $this->mkProductListFromCollection($collection);

        return $res;
    }

    private function getPromotionProductSKUs ( $data = null ) {
        $res = Mage::helper('catalog/product')->getPromotionSKUs();

        return $res;
    }

    private function getProductPrices ( $data ) {
        $performanceLog = Mage::getStoreConfig('schrackdev/development/ajaxdispatcher_log_price_data');
        if ( $performanceLog == true ) {
            $formkeyLog = $this->_formkey;
        } else {
            $formkeyLog = '';
        }
        $strLog1 = '*******************  getProductPrices  ' . $formkeyLog . '  *****************';
        $strLog2 = 'AjaxDispatch (start) -> ' . $formkeyLog . '  ' . date('Y-m-d H:i:s');
        $res = [];
        $altSkuQtys = [];
        $skus = $data['data']['skus'];
        if ( ! isset($data['data']['quantities']) ) {
            $skus = array_unique($skus);
        }
        if ( isset($data['data']['cart_ids']) ) {
            $sku2qtyCartId = [];
        }
        if ( isset($data['data']['quantities']) ) {
            $tmpQtys = $data['data']['quantities'];
            $l = count($skus);
            $qtys = [];
            for ( $i = 0; $i < $l; ++$i ) {
                $sku = $skus[$i];
                if ( isset($qtys[$sku]) && isset($data['data']['cart_ids']) ) {
                    $altSkuQtys[] = [$sku, $tmpQtys[$i], $data['data']['cart_ids'][$i]];
                } else {
                    $qtys[$sku] = $tmpQtys[$i];
                }
                if ( isset($sku2qtyCartId) ) {
                    $sku2qtyCartId[$sku][] = ['qty' => $tmpQtys[$i], 'cart_id' => $data['data']['cart_ids'][$i]];
                }
            }
        } else {
            $qtys = [];
        }
        $productHelper = Mage::helper('schrackcatalog/product');
        if ( !is_array($skus) ) {
            Mage::log('SKU-List is not an array : "' . $skus . '"', null, 'system.log');
        }

        $res = $productHelper->getPriceProductInfo($skus, $qtys, $formkeyLog);
        if ( isset($sku2qtyCartId) ) {
            foreach ( $res as $sku => $rec ) {
                $sku2qtyCartId[$sku][0]['amount'] = $rec['amount'];
            }

            // Iterate over cart, and remember offer total prices to replace normal total later on, if they were placed to cart from offer.
            // Also remember offer references, prices and surcharges.
            $quoteId2offerGrandTotals = [];
            $sku2offerNums = [];
            $sku2orderNums = [];
            $sku2offerPrices = [];
            $sku2offerSurcharges = [];
            $cart = Mage::getSingleton('checkout/cart');
            foreach ( $cart->getQuote()->getAllItems() as $item ) {
                if ( $item->getSchrackOfferPricePerUnit() ) {
                    $quoteID = $item->getItemId(); // there can be multiple positions and amounts of one article, so use quote ID as key
                    $sku = $item->getSku(); // offerref, price and surcharge are the same for all positions, so sku as key is good
                    $sku2offerNums[$sku] = $item->getSchrackOfferNumber();
                    $sku2orderNums[$sku] = $item->getSchrackOfferReference();
                    $sku2offerPrices[$sku] = $item->getSchrackOfferPricePerUnit();
                    $sku2offerSurcharges[$sku] = $item->getSchrackOfferSurcharge();
                    $totalPrice = $item->getSchrackOfferPricePerUnit() * ($item->getQty() / $item->getSchrackOfferUnit());
                    $totalSurcharge = $item->getSchrackOfferSurcharge() * ($item->getQty() / $item->getSchrackOfferUnit());
                    $quoteId2offerGrandTotals[$quoteID] += ($totalPrice + $totalSurcharge);
                }
            }

            foreach ( $sku2qtyCartId as $sku => $data ) {
                if ( isset($sku2offerNums[$sku]) ) {
                    $offerNum = $sku2offerNums[$sku];
                    $orderNum = $sku2orderNums[$sku];
                    $res[$sku]['offerrefurl']   = Mage::getBaseUrl() . 'customer/account/documentsDetailView/id/' . $orderNum . '/type/offer/documentId/' . $offerNum;
                    $res[$sku]['offerref']      = $offerNum;
                    $res[$sku]['price']         = $this->formatAmount($sku2offerPrices[$sku]);
                    $res[$sku]['surcharge']     = $this->formatAmount($sku2offerSurcharges[$sku]);
                }
                $res[$sku]['amounts'] = [];
                foreach ( $data as $rec ) {
                    $quoteID = $rec['cart_id'];
                    if ( isset($quoteId2offerGrandTotals) && isset($quoteId2offerGrandTotals[$quoteID]) && $quoteId2offerGrandTotals[$quoteID] ) {
                        if ( stristr($quoteId2offerGrandTotals[$quoteID], '.') ) {
                            $quoteId2offerGrandTotals[$quoteID] = (string)$quoteId2offerGrandTotals[$quoteID];
                            $quoteId2offerGrandTotals[$quoteID] = str_replace('.', ',', $quoteId2offerGrandTotals[$quoteID]);
                        }
                        $rec['amount'] = $quoteId2offerGrandTotals[$quoteID];
                    } else {
                        if ( ! isset($rec['amount']) ) {
                            $tmpRes = $productHelper->getPriceProductInfo([$sku], [$sku => $rec['qty']], $formkeyLog);
                            $rec['amount'] = $tmpRes[$sku]['amount'];
                        }
                    }
                    $rec['amount'] = $this->formatAmount($rec['amount']);
                    $res[$sku]['amounts'][$quoteID] = $rec['amount'];
                }
            }
        }
        $strLog3 = 'AjaxDispatch (end) -> ' . $formkeyLog . '  ' . date('Y-m-d H:i:s');
        $strLog4 = '################################################################';
        if ( $performanceLog == true ) {
            Mage::log($strLog1, null, 'performance.log');
            Mage::log($strLog2, null, 'performance.log');
            Mage::log($strLog3, null, 'performance.log');
            Mage::log($strLog4, null, 'performance.log');
        }

        return $res;
    }

    private function getProductAvailabilities ( $data ) {
        $strLog1 = '***************** getProductAvailabilities  ' . $this->_formkey . '  ********************';
        $strLog2 = date('Y-m-d H:i:s');
        $res = [];
        if ( !isset($data) || !isset($data['data']) || !isset($data['data']['skus']) ) {
            Mage::log(print_r($data, true), null, 'wrong_product_availibility_input.log');
            throw new Exception("Wrong product availibility input!");
        }
        $skus = array_unique($data['data']['skus']);
        if ( !isset($data['data']['forceRequest']) ) {
            $data['data']['forceRequest'] = false;
        }
        $forceRequest = $data['data']['forceRequest'] && count($skus) == 1;
        $productHelper = Mage::helper('schrackcatalog/product');
        $res = $productHelper->getAvailibilityProductInfo($skus, $forceRequest);
        $strLog3 = date('Y-m-d H:i:s');
        $strLog4 = '################################################################';
        $performanceLog = Mage::getStoreConfig('schrackdev/development/ajaxdispatcher_log_availabilty_data');
        if ( $performanceLog ) {
            Mage::log($strLog1, null, 'performance.log');
            Mage::log($strLog2, null, 'performance.log');
            Mage::log($strLog3, null, 'performance.log');
            Mage::log($strLog4, null, 'performance.log');
        }

        return $res;
    }

    private function getProductPricesAndAvailabilities ( $data ) {
        $performanceLog = false;
        if ( Mage::getStoreConfig('schrackdev/development/ajaxdispatcher_log_availabilty_data') ||
            Mage::getStoreConfig('schrackdev/development/ajaxdispatcher_log_price_data') ) {
            $performanceLog = true;
        }
        $forceAvailibilityRequests = false;

        if ( $performanceLog == true ) {
            $formkeyLog = $this->_formkey;
        } else {
            $formkeyLog = '';
        }
        $strLog1 = '*******************  getProductPricesAndAvailabilities  ' . $formkeyLog . '  *****************';
        $strLog2 = 'AjaxDispatch (start) -> ' . $formkeyLog . '  ' . date('Y-m-d H:i:s');
        $res = [];
        $altSkuQtys = [];
        $skus = $data['data']['skus'];

        if ( isset($data['data']['forceRequest']) && $data['data']['forceRequest'] && count($skus) == 1 ) {
            $forceAvailibilityRequests = true;
        }

        if ( isset($data['data']['cart_ids']) ) {
            $sku2qtyCartId = [];
        }

        if ( isset($data['data']['quantities']) ) {
            $tmpQtys = $data['data']['quantities'];
            $l = count($skus);
            $qtys = [];
            for ( $i = 0; $i < $l; ++$i ) {
                $sku = $skus[$i];
                if ( isset($qtys[$sku]) && isset($data['data']['cart_ids']) ) {
                    $altSkuQtys[] = [$sku, $tmpQtys[$i], $data['data']['cart_ids'][$i]];
                } else {
                    $qtys[$sku] = $tmpQtys[$i];
                }
                if ( isset($sku2qtyCartId) ) {
                    $sku2qtyCartId[$sku][] = ['qty' => $tmpQtys[$i], 'cart_id' => $data['data']['cart_ids'][$i]];
                }
            }
        } else {
            $qtys = [];
        }
        $productHelper = Mage::helper('schrackcatalog/product');

        if (is_array($skus)) {
            $res = $productHelper->getPriceAndAvailabilityProductInfo($skus, $qtys, $forceAvailibilityRequests,
                $formkeyLog);
            $resPrices = $res['prices'];
            if ( isset($sku2qtyCartId) ) {
                foreach ( $resPrices as $sku => $rec ) {
                    $sku2qtyCartId[$sku][0]['amount'] = $rec['amount'];
                }
                foreach ( $sku2qtyCartId as $sku => $articleData ) {
                    $resPrices[$sku]['amounts'] = [];
                    foreach ( $articleData as $rec ) {
                        if ( !isset($rec['amount']) ) {
                            $tmpRes = $productHelper->getPriceAndAvailabilityProductInfo([$sku], [$sku => $rec['qty']],
                                $forceAvailibilityRequests, $formkeyLog);
                            $rec['amount'] = $tmpRes[$sku]['amount'];
                        }
                        $resPrices[$sku]['amounts'][$rec['cart_id']] = $rec['amount'];
                    }
                }
            }
        } else {
            Mage::log('SKU-List is not an array : "' . $skus . '"', null, 'system.log');
            $resPrices = array();
        }
        $strLog3 = 'AjaxDispatch (end) -> ' . $formkeyLog . '  ' . date('Y-m-d H:i:s');
        $strLog4 = '################################################################';
        if ( $performanceLog == true ) {
            Mage::log($strLog1, null, 'performance.log');
            Mage::log($strLog2, null, 'performance.log');
            Mage::log($strLog3, null, 'performance.log');
            Mage::log($strLog4, null, 'performance.log');
        }
        $res['prices'] = $resPrices;

        return $res;
    }

    private function getCableLeavings ( $data ) {
        $res = [];
        if ( $data && isset($data['data']['sku']) && $data['data']['sku'] ) {
            $sku = $data['data']['sku'];
            $product = $this->loadProductBySku($sku);
            $stockHelper = Mage::helper('schrackcataloginventory/stock');
            $warehouses = $stockHelper->getAllDeliveryStockNumbers();
            $pickupWarehouse = $stockHelper->getCustomerPickupStockNumber(null);
            $warehouses[] = $pickupWarehouse;
            $drums = Mage::helper('schrackcatalog/info')->getAvailableDrums($product, $warehouses, 1);
            $res['data'] = [];
            $res['data']['pickup'] = [];
            $res['data']['delivery'] = [];
            foreach ( $drums as $wh => $drumsPerWh ) {
                foreach ( $drumsPerWh as $drum ) {
                    $key = $drum->getWwsNumber();
                    $rec = $drum->getData();
                    if ( $wh == $pickupWarehouse ) {
                        $res['data']['pickup'][$wh][$key] = $rec;
                    } else {
                        $res['data']['delivery'][$wh][$key] = $rec;
                    }
                }
            }
        } else {
            $res['result'] = '[ERROR] no SKU for getCableLeavings() given.';
            $res['status'] = 'error';
        }

        return $res;
    }

    private function getSolrSearchList ( array $solrSearchList ) {
        $lists = [];
        foreach ( $solrSearchList as $randomId => $list ) {
            $productList = $this->mkProductList($list);
            // Reorder result list to match request order
            foreach ($productList as $sku => $product) {
                $index = array_search($product->id, $list, true);
                if ($index !== false) {
                    $list[$index] = $sku;
                }
            }
            $reorderedList = [];
            foreach ($list as $sku) {
                if (array_key_exists($sku, $productList)) {
                    $reorderedList[$sku] = $productList[$sku];
                    unset($productList[$sku]);
                }
            }
            $lists[$randomId] = $reorderedList;
        }

        return $lists;
    }

    private function mkProductList ( array $entityIDs ) {
        if ( count($entityIDs) == 0 ) {
            return [];
        }
        array_slice($entityIDs, 0, self::PRODUCT_ROW_LIMIT);
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->setStoreId($this->_storeId)
            ->addProductFilter($entityIDs);

        return $this->mkProductListFromCollection($collection);
    }

    private function mkProductListFromCollection ( $collection, $limit = null ) {
        $res = [];
        $skus = [];
        Schracklive_SchrackCatalog_Model_Product::addAdditionalEavAttributeCodesForLists($collection);
        if ( !$limit || !is_numeric($limit) ) {
            $limit = self::PRODUCT_ROW_LIMIT;
        }
        $ajaxSpinnerOverlay = Mage::getDesign()->getSkinUrl('schrackdesign/Public/Images/download_ajax_loader.gif');
        $collection->setPageSize($limit)->setCurPage(1);
        $allProductsAreSaleable = !Mage::getStoreConfig('schrack/general/use_webshop_saleable');
        foreach ( $collection as $product ) {
            $rec = new stdClass();
            $sku = $product->getSku();
            $rec->sku = $sku;
            $rec->name = $product->getName();
            if ( !$rec->name ) {
                $rec->name = $rec->sku;
            }
            $rec->url = $product->getProductUrlWithChapterIfAvail();
            $rec->category = $product->getCategoryId4googleTagManager();
            $rec->image = Schracklive_SchrackCatalog_Helper_Image::getImageUrl($product->getMainImageUrl(),
                Schracklive_SchrackCatalog_Helper_Image::CART);
            $rec->imageLightbox = Schracklive_SchrackCatalog_Helper_Image::getImageUrl($product->getMainImageUrl(),
                Schracklive_SchrackCatalog_Helper_Image::SEARCH_RESULT_PAGE_MOUSEOVER);
            $rec->id = $product->getId();
            $rec->ajaxLoader = $ajaxSpinnerOverlay;
            if ($allProductsAreSaleable || $product->isWebshopsaleable()) {
                $rec->saleable = true;
            } else {
                $rec->saleable = false;
            }
            $rec->statuslocal = $product->getSchrackStsStatuslocal();
            $res[$sku] = $rec;
            $skus[] = $sku;
        }

        return $res;
    }

    private function getCachedResult (
        $impl,
        $key,
        $needsCustomerID,
        $data,
        $lifetimeHours = 1,
        $overrideCache = false,
        $overrideWWSId = false
    ) {
        if ( $needsCustomerID ) {
            if ( !Mage::getSingleton('customer/session')->isLoggedIn() ) {
                $res['result'] = '[ERROR] user NOT logged in.';
                $res['status'] = 'error';

                return $res;
            }
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if ( $overrideWWSId == false ) {
                $customerID = $customer->getSchrackWwsCustomerId();
                if ( !isset($customerID) ) { // must be Schrack employee or prospect (both has no customer ID and so no result)
                    $res['result'] = '[ERROR] user has NO wws-id.';
                    $res['status'] = 'error';

                    return $res;
                }
            } else {
                $customerID = $customer->getId() . $customer->getId(); // Double customer entity id prevents collision of wws-id with magento entizty id
            }
            $key .= $customerID;
        }
        $cache = Mage::app()->getCache();
        $stringRes = $cache->load($key);
        if ( $stringRes && $overrideCache == false ) {
            $res = unserialize($stringRes);
        } else {
            $res = $this->$impl($data);
            try {
                $stringRes = serialize($res);
                $cache->save($stringRes, $key, [], $lifetimeHours * 60 * 60);
            } catch ( Exception $ex ) {
                Mage::logException($ex);
                $res = [];
            }
        }

        return $res;
    }

    // Add items (products) to cart
    // FE-Example : 'setAddToCart' : {'data' : {'sku' : 'LI634777--', 'quantity' : 12 }}
    // FE-Example cable: 'setAddToCart' : {'data' : {'sku' : 'XC01010101', 'quantity' : 100, 'drum' : '15', forceAdd : 1 }}
    private function setAddToCart ( $data ) {
        $resultData = [];

        if ( is_array($data) ) {
            $resultData = $this->setAddToCartImpl($data['data']);
        }

        return $resultData;
    }

    // FE-Example : 'setAddToCartFromSlider' : {'data' : {'sku' : 'LI634777--', 'quantity' : 12, 'sliderClass' : 'tralala' }}
    // FE-Example cable: 'setAddToCartFromSlider' : {'data' : {'sku' : 'XC01010101', 'quantity' : 100, 'drum' : '15', forceAdd : 1, 'sliderClass' : 'tralala' }}
    private function setAddToCartFromSlider ( $data ) {
        $resultData = [];

        if ( is_array($data) && isset($data['data']['sliderClass']) && $data['data']['sliderClass'] ) {
            $realData = $data['data'];
            $sliderClass = $realData['sliderClass'];
            $resultData = $this->setAddToCartImpl($realData, $sliderClass);
            if ( isset($realData['query']) ) {
                $helper = Mage::helper('search/search');
                $helper->logSearchArticleSelection($realData['query'],$realData['sku']);
            }
        }

        return $resultData;
    }

    // Add multiple items (products) to cart
    // FE-Example :
    // 'setBatchAddToCart' :    { 'data' : {
    //                              forceAdd : 1,
    //                              items : [
    //                                  { 'sku' : 'BM018102--', 'quantity' : 3 },
    //                                  { 'sku' : 'XC01010101', 'quantity' : 200 }
    //                              ]
    //                          }}
    //
    private function setBatchAddToCart ( $data ) {
        $resultData = array();
        if (   is_array($data)
            && is_array($data['data'])
            && is_array($data['data']['items'])
            && count($data['data']['items']) > 0 ) {
            $messages = array();
            $statusOkCnt = 0;
            $statusErrCnt = 0;
            $showPopup = false;
            $forceAdd = isset($data['data']['forceAdd']) && isset($data['data']['forceAdd']);
            foreach ( $data['data']['items'] as $item ) {
                if ( ! isset($item['forceAdd']) ) {
                    $item['forceAdd'] = $forceAdd;
                }
                $subRes = $this->setAddToCartImpl($item);
                $messages = array_merge($messages,$subRes['data']['messages']);
                if ( $subRes['status'] == 'success' ) {
                    ++$statusOkCnt;
                } else {
                    ++$statusErrCnt;
                    if ( ! $forceAdd ){
                        $subRes['data']['abortAddToCart'] = true;
                        break;
                    }
                    if ( $subRes['result'] == '[ERROR] Availibility problem.' ) {
                        $msg = sprintf($this->__("Product %s currently not available."),$item['sku']);
                        $messages[] = $msg;
                    }
                }
                if ( $subRes['shoePopup'] ) {
                    $showPopup = true;
                }
            }

            if (Mage::helper('ids')->isIdsSession()) {
                $quote = Mage::getModel('checkout/session')->getQuote();
                $quote->collectTotals()->save();
            }

            $resultData = $subRes; // use last one
            $resultData['data']['messages'] = $messages; // the collected ones
            $resultData['showPopup'] = $showPopup;
            if ( $statusOkCnt > 0 && $statusErrCnt == 0 ) {
                $resultData['status'] = 'success';
                $resultData['result'] = '[SUCCESS] products was added to cart';
            } else if ( $statusOkCnt == 0 && $statusErrCnt > 0 ) {
                $resultData['status'] = 'error';
                $resultData['result'] = '[ERROR] no products was added to cart';
            } else {
                $resultData['status'] = 'mixed';
                $resultData['result'] = '[ERROR/SUCCESS] just some products was added to cart';
            }
        }
        return $resultData;
    }


    private function setAddToCartImpl ( $data, $sliderClass = false ) {
        $resultData = array();
        $resultData['showPopup'] = false;

        if ( isset($data['sku'])
            && $data['sku']
            && isset($data['quantity'])
            && $data['quantity'] ) {
            $sku = $data{'sku'};
            $qty = $data{'quantity'};
            $isCableLeaving = isset($data{'leaving'}) && filter_var($data{'leaving'},FILTER_VALIDATE_BOOLEAN);
            $forceAdd = isset($data{'forceAdd'}) && $data{'forceAdd'};
            $listID = null;
            if (isset($data['list_id'])) {
                $listID = $data['list_id'];
            }

            $product = $this->loadProductByAttribute2valueList(array('sku' => $sku, 'schrack_ean' => $sku));

            if ($product) {
                $sku = $product->getSku();
                if ($product->isSalable() && $product->isWebshopsaleable() == true) {
                    $params = array('qty' => $qty, 'sku' => $sku, 'forceAdd' => $forceAdd, 'leaving' => $isCableLeaving);
                    $checkoutHelper = Mage::helper('schrackcheckout/cart');
                    $availibilityProblemResult = $checkoutHelper->detectAvailabilityProblemAndReturnPopupHtml($product,$qty);
                    if ( $availibilityProblemResult ) {
                        $resultData['result'] = '[ERROR] Availibility problem.';
                        $resultData['status'] = 'error';
                        $resultData['abortAddToCart'] = true;
                        $resultData['showPopup'] = true;
                        $resultData['popupHtml'] = $availibilityProblemResult;
                    } else {
                        $checkAddToCartResult = $checkoutHelper->checkAddToCart($product, $params);
                        if ( isset($checkAddToCartResult['newProduct']) ) {
                            $product = $checkAddToCartResult['newProduct'];
                        }
                        // TODO: use alt product if given...
                        $resultData['data'] = $checkAddToCartResult;
                        if ( isset($checkAddToCartResult['abortAddToCart']) && $checkAddToCartResult['abortAddToCart'] ) {
                            $resultData['result'] = '[SUCCESS] Quantity or drum incorrect - please display the message(s) from the "data["messages"]" array.';
                            $resultData['status'] = 'success';
                        } else {
                            if ( isset($checkAddToCartResult['newQty']) ) {
                                $qty = $checkAddToCartResult['newQty'];
                            }
                            if ( isset($checkAddToCartResult['newDrum']) ) {
                                $drum = $checkAddToCartResult['newDrum'];
                            }
                            $cart = Mage::getSingleton('checkout/cart');

                            // $res = Mage::helper('checkout/cart')->getSummaryCount();
                            $cartCnt = $cart->getSummaryQty();
                            $maxAmount = Mage::helper('schrack')->getMaximumOrderAmount();
                            if ( $cartCnt < $maxAmount ) {
                                $priceRes = $this->getProductPrices(array('data' => array('skus' => array($sku)),array('quantities' => array($qty))));
                                $product->setPrice($priceRes[$sku]['price']);

                                $cart->addProduct($product, $qty);
                                $cart->getQuote()->setDataChanges(true);
                                $cart->save();
                                $cart->getQuote()->resetQtySumCache();
                                Mage::getSingleton('customer/session')->setCartWasUpdated(true);

                                $resultData['result'] = '[SUCCESS] product ' . $sku . ' (quantity = ' . $qty . ') was added to cart  - please display the message(s) from the "data["messages"]" array.';
                                $resultData['status'] = 'success';
                                $resultData['numberOfDifferentItemsInCart'] = $this->getCartItemCount();

                                if ( $msg = $checkoutHelper->getPossiblePackingUnitUpgradeMessage($cart, $product, $qty, $listID) ) {
                                    if ( $sliderClass ) {
                                        $funcExchange = "setQtyToProduct('" . $sku . "', '" . $sliderClass . "', ";
                                        $msg = str_replace('setQty(', $funcExchange, $msg);
                                    }
                                    $resultData['data']["messages"][] = $msg;
                                }
                            } else {
                                $resultData['result'] = '[ERROR] Product cannot be added to cart because maximum cart item count ' . $maxAmount . ' is already reached.';
                                $resultData['status'] = 'error';
                                $resultData['data']["messages"] = [
                                    $this->__('Product cannot be added to cart because maximum item count %d has already been reached.', $maxAmount)
                                ];
                            }
                        }
                    }
                } else {
                    $resultData['result'] = '[ERROR] Product ' . $sku . ' currently not available.';
                    $resultData['status'] = 'error';
                    $resultData['data']["messages"][] = $this->__('Product %s currently not available.', $sku);
                }
            } else {
                $resultData['result'] = '[ERROR] Product number ' . $sku . ' not found.';
                $resultData['status'] = 'error';
                $resultData['data']["messages"][] = $this->__('Product number %s not found.', $sku);
            }
        } else {
            $resultData['result'] = '[ERROR] no SKU and/or QUANTITY for setAddToCart() given.';
            $resultData['status'] = 'error';
            $resultData['data']["messages"][] = $this->__('no SKU and/or QUANTITY for setAddToCart() given.');
        }
        return $resultData;
    }

    private function getCartGrandTotal ( $data ) {
        $resultData = array();
        $cart = Mage::getSingleton('checkout/cart');
        $cart->init();
        $cart->save();
        $grandTotal = 0;
        if (Mage::helper('sapoci')->isSapociCheckout()) {
            // Iterate over cart, and replace normal prices by offer prices, if they were placed to cart from offer:
            foreach ( $cart->getQuote()->getAllItems() as $item ) {
                if ($item->getSchrackOfferPricePerUnit()) {
                    $itemPrice     = $item->getSchrackOfferPricePerUnit() * ($item->getQty() / $item->getSchrackOfferUnit());
                    $itemSurcharge = $item->getSchrackOfferSurcharge() * ($item->getQty() / $item->getSchrackOfferUnit());

                    $grandTotal += $itemPrice + $itemSurcharge;
                } else {
                    $grandTotal = $cart->getQuote()->getGrandTotal();
                }
            }
        } else {
            $grandTotal = $cart->getQuote()->getGrandTotal();
        }

        $formattedGrandTotal = Mage::helper('checkout')->formatPrice($grandTotal);
        $resultData['result'] = "[SUCCESS]";
        $resultData['status'] = 'success';
        $resultData['raw_amounts'] = array('grand_total' => $grandTotal);
        $resultData['formatted_amounts'] = array('grand_total' => $formattedGrandTotal);
        $resultData['online_bonus_text'] = '';
        $resultData['online_bonus_url'] = '';

        $bonusUrl = Mage::getStoreConfig('schrack/general/onlinebonus_url');
        $onlinebonusActive = true;
        $onlineBonusStart  = strtotime(Mage::getStoreConfig('schrack/general/onlinebonus_datestart'));
        $onlineBonusStop   = strtotime(Mage::getStoreConfig('schrack/general/onlinebonus_datestop'));
        $nowDateTime       = strtotime(date('Y-m-d H:i:s'));
        if ($onlineBonusStart) {
            if ($onlineBonusStart <= $nowDateTime && $nowDateTime <= $onlineBonusStop) {
                $onlinebonusActive = true;
            } else {
                $onlinebonusActive = false;
            }
        }
        $bonusMinValue = Mage::getStoreConfig('schrack/general/onlinebonus_minvalue');
        $subTotal = $cart->getQuote()->getSubtotal();
		$diffValue = $bonusMinValue - $subTotal;
		if ( $onlinebonusActive && $bonusMinValue !== '' && intval($bonusMinValue) > 0 && $diffValue > 0 ) {
            $resultData['online_bonus_text'] = $this->__('You are still missing %s to reach your online bonus.', (Mage::getStoreConfig('currency/options/default') . ' ' . Mage::helper('checkout')->formatPrice($diffValue)));
            if ( $bonusUrl !== '' ) {
                $resultData['online_bonus_url'] = $bonusUrl;
            }
        }

        return $resultData;
    }

    // Change quantity of cart item. As key the item_id of the cart item is necessary because
    // FE-Example : 'setCartItemQuantity' : {'data' : { 'item_id' : 3123185, 'quantity' : 4 }}
    // Result example: {"result":"[SUCCESS] product BZT28371-- was changed to quantity 4.","raw_amounts":{"base_price":77,"surcharge":44,"row_total":352},"formatted_amounts":{"base_price":"EUR  77,00","surcharge":"EUR  44,00","row_total":"EUR  352,00"},"cart_item_count":3}
    private function setCartItemQuantity($data) {
        $resultData = array();
        $resultData['showPopup'] = false;


        if ($data && isset($data['data']['item_id']) && $data['data']['item_id'] && isset($data['data']['quantity'])) {
            $id = $data['data']['item_id'];
            $qty = '' . $data['data']['quantity'];
            $cart = Mage::getSingleton('checkout/cart');
            $item = $cart->getQuote()->getItemById($id);
            if (!$item) {
                // item not found
                $resultData['result'] = "[ERROR] no item_id with ID $id in setCartItemQuantity() found.";
                $resultData['status'] = 'error';
                return $resultData;
            }
            $sku = $item->getSku();

            if (intval($item->getQty()) === intval($qty)) {
                // nothing to do
                $resultData['result'] = "[SUCCESS] product $sku same quantity $qty not changed.";
                $resultData['status'] = 'success';
            } else {
                $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId(),Schracklive_SchrackCatalog_Model_Product::getAdditionalEavAttributeCodesForLists());

                $checkoutHelper = Mage::helper('schrackcheckout/cart');
                $availibilityProblemResult = $checkoutHelper->detectAvailabilityProblemAndReturnPopupHtml($product,$qty, 'cart');
                if ( $availibilityProblemResult ) {
                    $resultData['result'] = '[ERROR] Availibility problem.';
                    $resultData['status'] = 'error';
                    $resultData['showPopup'] = true;
                    $resultData['popupHtml'] = $availibilityProblemResult;
                } else {
                    if ( intval($qty) === 0 ) {
                        // remove
                        $checkAddToCartResult = array();
                    } else {
                        $params = array('qty' => $qty, 'sku' => $sku, 'forceAdd' => true, 'ignoreCartQty' => true);
                        $checkAddToCartResult = $checkoutHelper->checkAddToCart($product, $params);
                    }
                    if ( isset($checkAddToCartResult['newProduct']) ) {
                        $product = $checkAddToCartResult['newProduct'];
                        // TODO: use alt product if given...
                    }
                    $resultData = array_merge($resultData,$checkAddToCartResult);
                    if ( ! isset($resultData['messages']) ) {
                        $resultData['messages'] = array();
                    }
                    if ( isset($checkAddToCartResult['abortAddToCart']) && $checkAddToCartResult['abortAddToCart'] ) {
                        $resultData['result'] = '[SUCCESS] Quantity or drum incorrect - please display the message(s) from the "data["messages"]" array.';
                        $resultData['status'] = 'success';
                    } else {
                        if ( isset($checkAddToCartResult['newQty']) ) {
                            $qty = $checkAddToCartResult['newQty'];
                            if ( ! isset($resultData['messages']) || count($resultData['messages']) == 0 ) {
                                $resultData['messages'][] = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, $qty, $product->calculateMinimumQuantityPackage());
                            }
                        }

                        // change qty
                        $cart->updateItems(array($id => array('qty' => $qty)));
                        $cart->getQuote()->setDataChanges(true);
                        $cart->save();
                        // get new amounts
                        $grandTotalResult = $this->getCartGrandTotal('');
                        $resultData = array_merge($resultData,$grandTotalResult);
                        if ( intval($qty) === 0 ) {
                            // remove
                            $resultData['result'] = "[SUCCESS] product $sku was removed from cart.";
                            $resultData['messages'] = array($this->__('Cart was updated'));
                            $resultData['status'] = 'success';
                        } else {
                            // update
                            $customer = Mage::getSingleton('customer/session')->getCustomer();
                            $checkoutHelper = Mage::helper('schrackcheckout');
                            $priceHelper = Mage::helper('schrackcatalog/price');
                            $currency = $priceHelper->getCurrencyForCustomer($product, $customer);
                            $basePrice = $item->getSchrackBasicPrice();
                            $formattedBasePrice = $checkoutHelper->formatPrice($product, $basePrice);
                            $surcharge = $item->getSchrackRowTotalSurcharge();
                            $formattedSurcharge = $checkoutHelper->formatPrice($product, $surcharge);
                            $rowTotal = $item->getRowTotal();
                            $formattedRowTotal = $checkoutHelper->formatPrice($product, $rowTotal);
                            $resultData['result'] = '[SUCCESS] product ' . $sku . ' was changed to quantity ' . $qty . '.';
                            $resultData['messages'][] = $this->__('Cart was updated');
                            $resultData['status'] = 'success';

                            if (Mage::helper('sapoci')->isSapociCheckout()) {
                                if ($item->getSchrackOfferPricePerUnit()) {
                                    $totalPrice     = $item->getSchrackOfferPricePerUnit() * ($item->getQty() / $item->getSchrackOfferUnit());
                                    $totalSurcharge = $item->getSchrackOfferSurcharge()* ($item->getQty() / $item->getSchrackOfferUnit());
                                    $rowTotal = $totalPrice + $totalSurcharge;
                                    $formattedRowTotal = number_format($rowTotal, 2, ',','');
                                    $rowTotal = number_format($rowTotal, 2, ',','');
                                }
                            }

                            $resultData['raw_amounts'] = array('base_price' => $basePrice,
                                'surcharge' => $surcharge,
                                'row_total' => $rowTotal,
                                'grand_total' => $resultData['raw_amounts']['grand_total']);
                            $resultData['formatted_amounts'] = array('base_price' => $formattedBasePrice,
                                'surcharge' => $formattedSurcharge,
                                'row_total' => $formattedRowTotal,
                                'grand_total' => $resultData['formatted_amounts']['grand_total']);
                        }
                        $resultData['cart_item_count'] = $this->getCartItemCount();
                    }
                }
            }
        } else {
            $resultData['result'] = '[ERROR] no item_id and/or quantity for setCartItemQuantity() given.';
            $resultData['status'] = 'error';
        }
        return $resultData;
    }

    // clear cart, no input data needed
    private function setCartEmpty($data = null) {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quote->delete();
        $resultData['result'] = "[SUCCESS] cart is empty.";
        $resultData['status'] = 'success';
        $resultData['cart_item_count'] = $this->getCartItemCount();
        return $resultData;
    }

    // Get the login status of current user
    // FE-Example : 'getLoginStatus' : {'data' : {}}
    private function getLoginStatus($data = null) {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ($customer && $customer->getId()) {
            $resultData['result'] = '[SUCCESS] current customer is logged in.';
            $resultData['status'] = 'success';
        } else {
            $resultData['result'] = '[SUCCESS] current customer is not logged in.';
            $resultData['status'] = 'success';
        }

        return $resultData;
    }

    // Set a list of items and quantity to a given partslist:
    // FE-Example : 'setAddToPartslist' : {'data' : {'partslistId' : 12345, 'itemsSKUList' : [{'ST3P3LC4--' : 12}, {'BM018110--' : 4}, {'LI43019088' : 2}]}}
    private function setAddToPartslist($data) {
        $resultData['result'] = '[SUCCESS] item(s) succesfully added to partslist.';
        $resultData['status'] = 'success';
        $itemsForPartslistToAdd = $data['data']['itemsSKUList']; // sku + number of items
        $selectedPartslistForAddition = $data['data']['partslistId'];

        if (!is_array($itemsForPartslistToAdd)) {
            $resultData['result'] = '[ERROR] given items are not received as array.';
            $resultData['status'] = 'error';
            return $resultData;
        }

        if (!$selectedPartslistForAddition) {
            $resultData['result'] = '[ERROR] no partslist given.';
            $resultData['status'] = 'error';
            return $resultData;
        }

        // Check, if partslist exists and belong to current user:
        $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId(Mage::getSingleton('customer/session')->getCustomer(), $selectedPartslistForAddition);
        if (!$partslist) {
            $resultData['result'] = '[ERROR] partslist not found.';
            $resultData['status'] = 'error';
            return $resultData;
        }

        // Check products by SKU first:
        foreach ($itemsForPartslistToAdd as $sku => $quantity) {
            $product = $this->loadProductBySku($sku);
            if (!($product && $product->getId())) {
                $resultData['result'] = '[ERROR] Cannot specify product.' . $sku;
                $resultData['status'] = 'error';
                return $resultData;
            }
        }

        // Now add all items to selected partslist:
        foreach ($itemsForPartslistToAdd as $sku => $quantity) {
            $product = $this->loadProductBySku($sku);
            $partslist->addNewItem($product, array('qty' => $quantity));
        }

        $partslist->save();

        return $resultData;
    }

    private function getRelatedProductsForProduct($data) {
        if (!isset($data['data']['sku']) || !$data['data']['sku'] || $data['data']['sku'] <= '') {
            $resultData['result'] = "[ERROR] missing parameter sku!";
            $resultData['status'] = 'error';
            return $resultData;
        }
        $sku = $data['data']['sku'];
        return $this->getCachedResult('getRelatedProductsForProductImpl', $sku . '_related_products', false, $data);
    }

    private function getRelatedProductsForProductImpl($data) {
        $sku = $data['data']['sku'];
        $product = $this->loadProductBySku($sku);
        $relatedCollection = $product->getRelatedProductCollection()
                ->addAttributeToSort('position', 'asc')
                ->addStoreFilter();
        $checkLimit = Mage::getStoreConfig("schrack/shop/related_products");
        $res = $this->mkProductListFromCollection($relatedCollection, $checkLimit);
        return $res;
    }

    private function getAccessoriesForProduct($data) {
        if (!isset($data['data']['sku']) || !$data['data']['sku'] || $data['data']['sku'] <= '') {
            $resultData['result'] = "[ERROR] missing parameter sku!";
            $resultData['status'] = 'error';
            return $resultData;
        }
        $sku = $data['data']['sku'];
        return $this->getCachedResult('getAccessoriesForProductImpl', $sku . '_accessory_products', false, $data);
    }

    private function getAccessoriesForProductImpl($data) {
        $sku = $data['data']['sku'];
        $product = $this->loadProductBySku($sku);
        $accessoryCollection = $product->getAccessoryProducts();
        $res = $this->mkProductListFromCollection($accessoryCollection);
        return $res;
    }

    private function loadProductBySku($sku) {
        return $this->loadProductByAttribute2valueList(array('sku' => $sku));
    }

    private function loadProductByAttribute2valueList ( $attributes2values ) {
        $product = null;
        if ( ! $this->_id2productMap ) {
            $this->_id2productMap = array();
        } else if ( isset($attributes2values['sku']) && isset($this->_id2productMap[$attributes2values['sku']]) ) {
            $product = $this->_id2productMap[$attributes2values['sku']];
        }
        if ( ! $product ) {
            $eavAttributesToLoad = array(
                'name',
                'schrack_accessories_necessary',
                'schrack_accessories_optional',
                'schrack_vpes'
            );
            foreach ( $attributes2values as $k => $v ) {
                $product = Mage::getModel('catalog/product')->loadByAttribute($k, $v, $eavAttributesToLoad);
                if ( $product && $product->getId() ) {
                    $this->_id2productMap[$product->getSku()] = $product;
                    break;
                }
            }
        }
        return $product;
    }

    //  Returns a list of partslist-ID's and related partslist-name:
    // FE-Example : 'getAllPartslists' : {'data' : {}}
    private function getAllPartslists($data) {
        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();

        $partslists = Mage::getModel('schrackwishlist/partslist')->loadByCustomer($sessionLoggedInCustomer);
        $resultData = array();

        if (count($partslists)) {
            foreach ($partslists as $partslist) {
                $resultData[$partslist->getId() . "\0"] = $partslist->getDescription();
            }
        } else {
            $resultData['error'] = '[ERROR] no partslist found';
            $resultData['status'] = 'error';
        }

        return $resultData;
    }

    public function offersAction($data = null) {
		Mage::unregister('current_dashboard_partlist');
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return array();
        }
        $offers = $this->getOffers($data);
        $offersUpdated = array();
        $offerAddtoCartAjaxUrl = '';
        foreach ($offers['rows'] as $order) {
            $offerAddtoCartAjaxUrl = Mage::getUrl('customer/account/batchAddDocumentsToCart/documents/' . $order->orderNumber . ':offer');
            $order->offerNumber = '<a class="offer-number number_tracking_information" href="' . $order->detailUrl . '">' . $order->offerNumber . '</a>';
            $order->actions = $this->getDashboardAction(
                'offer',
                $order->downloadUrl,
                $offerAddtoCartAjaxUrl,
                $order->orderNumber,
                $order->name,
                $order->orderNumber,
                $order->status,
                $order->detailUrl
            );
            $offersUpdated[] = $order;
        }

        Mage::register('offersList', $offersUpdated);

        $offersUpdated['rowPerPage'] = self::DASHBOARD_ROW_LIMIT;
        $offersUpdated['filterStatusCounts'] = $offers['counts'];
        $offersUpdated['filterSet'] = $offers['filters'];
        $offersUpdated['desktopHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/offersDesktop.phtml');
        $offersUpdated['mobileHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/offersMobile.phtml');

        return $offersUpdated;
    }

    private function getMobileOffers() {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return array();
        }
        $helper = Mage::helper('schracksales/order');
        $collection = $helper->getLastOffers();
        $res = $this->mkOfferListFromCollection($collection);
        $response = array();
        foreach ($res as $values) {
            $response[] = array('html' => $this->getLayout()->createBlock('core/template')->setTemplate('customer/account/documents/mobile_offers.phtml')->
                        setData('blockdata', $values)->toHtml(),
                'created_at' => $values->creationDateYmd
            );
        }
        return $response;
    }

    public function dashboardAction() {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('customer_account_dash');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        echo $output;
        die;
    }

    public function ordersAction($data = false) {
        try {
            Mage::unregister('current_dashboard_partlist');
            Mage::unregister('dashboardOrderList');

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                return array();
            }

            $orders = $this->getOrders($data);
            $orderDocDownloadAjaxUrl = $orderAddtoCartAjaxUrl = '';
            $ordersUpdated = array();

            foreach ($orders['rows'] as $order) {
                $orderDocDownloadAjaxUrl = $order->downloadUrl;
                $orderAddtoCartAjaxUrl = Mage::getUrl('customer/account/batchAddDocumentsToCart/documents/' . $order->orderNumber . ':order');
                $order->reorder = $this->getDashboardAction('order', $orderDocDownloadAjaxUrl, $orderAddtoCartAjaxUrl, $order->docID, $order->name, $order->orderID, $order->status, $order->detailUrl);
                $ordersUpdated[] = $order;
            }

            Mage::register('dashboardOrders', $ordersUpdated);

            $ordersUpdated['rowPerPage'] = self::DASHBOARD_ROW_LIMIT;
            $ordersUpdated['filterStatusCounts'] = $orders['counts'];
            $ordersUpdated['filterSet'] = $orders['filters'];
            $ordersUpdated['desktopHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/ordersDesktop.phtml');
            $ordersUpdated['mobileHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/ordersMobile.phtml');

            return $ordersUpdated;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    private function getMobileOrders() {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return array();
        }
        $helper = Mage::helper('schracksales/order');
        $collection = $helper->getLastOrders();
        $res = $this->mkOrderListFromCollection($collection);
        //echo "<pre>";
        $response = array();
        foreach ($res as $values) {
            //print_r($value);
            $response[] = array('html' => $this->getLayout()->createBlock('core/template')->setTemplate('customer/account/documents/mobile_orders.phtml')->
                        setData('blockdata', $values)->toHtml(),
                'created_at' => $values->creationDateYmd
            );
        }
        return $response;
    }

    private function getMobile($desktop) {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return array();
        }
        $response = array();
        foreach ($desktop as $values) {
            $response[] = array('html' => $this->getLayout()->createBlock('core/template')->setTemplate('customer/account/documents/mobile_orders.phtml')->
                        setData('blockdata', $values)->toHtml(),
                'created_at' => $values->creationDateYmd
            );
        }
        return $response;
    }

    public function shipmentAction($data = null) {
        try {
            Mage::unregister('current_dashboard_partlist');

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                return array();
            }

            $shipmentOrders = $this->getDeliveries($data);
            $ordersUpdated = array();
            $shipmentAddtoCartAjaxUrl = '';

            foreach ($shipmentOrders['rows'] as $order) {
                $shipmentAddtoCartAjaxUrl = Mage::getUrl('customer/account/batchAddDocumentsToCart/documents/' . $order->documentNumber . ':shipment');
                $order->orderNumber = sprintf('<a class="delivery-number" href="%s">%s</a>', $order->detailUrl, $order->documentNumber);

                $order->trace = '&nbsp;';
                if ($order->trackAndTraceUrl != null) {
                    $order->trace = sprintf('<a class="track-shipments" href="%s">%s</a>', $order->trackAndTraceUrl, $this->__('Track Delivery'));
                }

                $order->actions = $this->getDashboardAction(
                    'shipment',
                    $order->downloadUrl,
                    $shipmentAddtoCartAjaxUrl,
                    $order->documentNumber,
                    $order->name,
                    $order->docID,
                    $order->status,
                    $order->detailUrl
                );
                $ordersUpdated[] = $order;
            }

            Mage::register('shipmentOrders', $ordersUpdated);

            $ordersUpdated['rowPerPage'] = self::DASHBOARD_ROW_LIMIT;
            $ordersUpdated['filterStatusCounts'] = $shipmentOrders['counts'];
            $ordersUpdated['filterSet'] = $shipmentOrders['filters'];
            $ordersUpdated['desktopHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/shipmentsDesktop.phtml');
            $ordersUpdated['mobileHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/shipmentsMobile.phtml');

            return $ordersUpdated;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    public function invoiceAction($data = null) {
        try {
            Mage::unregister('current_dashboard_partlist');

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                return array();
            }

            $invoices = $this->getInvoices($data);
            $invoicesUpdated = array();
            $invoiceAddtoCartAjaxUrl = '';

            foreach ($invoices['rows'] as $invoice) {
                $invoiceAddtoCartAjaxUrl = Mage::getUrl('customer/account/batchAddDocumentsToCart/documents/' . $invoice->documentNumber . ':invoice');
                $invoiceDocNumber = $invoice->documentNumber;
                $invoice->documentNumber = sprintf('<a class="invoice-number" href="%s">%s</a>', $invoice->detailUrl, $invoice->documentNumber);
                $invoice->actions = $this->getDashboardAction(
                    'invoice',
                    $invoice->downloadUrl,
                    $invoiceAddtoCartAjaxUrl,
                    $invoiceDocNumber,
                    $invoice->name,
                    $invoice->docID,
                    $invoice->status,
                    $invoice->detailUrl
                );
                $invoicesUpdated[] = $invoice;
            }

            Mage::register('invoices', $invoicesUpdated);
            $invoicesUpdated['rowPerPage'] = self::DASHBOARD_ROW_LIMIT;
            $invoicesUpdated['filterStatusCounts'] = $invoices['counts'];
            $invoicesUpdated['filterSet'] = $invoices['filters'];
            $invoicesUpdated['desktopHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/invoicesDesktop.phtml');
            $invoicesUpdated['mobileHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/invoicesMobile.phtml');

            return $invoicesUpdated;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    public function creditmemoAction($data = null) {
        try {
            Mage::unregister('current_dashboard_partlist');

            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                return array();
            }

            $creditmemos = $this->getCreditmemos($data);
            $creditmemosUpdated = array();
            $creditmemoAddtoCartAjaxUrl = '';

            foreach ($creditmemos['rows'] as $creditmemo) {
                $creditmemoAddtoCartAjaxUrl = Mage::getUrl('customer/account/batchAddDocumentsToCart/documents/' . $creditmemo->documentNumber . ':creditmemo');
                $creditmemo->orderNumber = '<a class="credit-memo" href="' . $creditmemo->detailUrl . '">' . $creditmemo->orderNumber . '</a>';
                $creditmemo->actions = $this->getDashboardAction(
                    'creditmemo',
                    $creditmemo->downloadUrl,
                    $creditmemoAddtoCartAjaxUrl,
                    $creditmemo->documentNumber,
                    $creditmemo->name,
                    $creditmemo->docID,
                    $creditmemo->status,
                    $creditmemo->detailUrl
                );
                $creditmemosUpdated[] = $creditmemo;
            }

            Mage::register('ordersList', $creditmemosUpdated);

            $creditmemosUpdated['rowPerPage'] = self::DASHBOARD_ROW_LIMIT;
            $creditmemosUpdated['filterStatusCounts'] = $creditmemos['counts'];
            $creditmemosUpdated['filterSet'] = $creditmemos['filters'];
            $creditmemosUpdated['desktopHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/desktop.phtml');
            $creditmemosUpdated['mobileHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/mobile.phtml');

            return $creditmemosUpdated;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    public function detailssearchAction($data = null) {
        try {
			Mage::unregister('current_dashboard_partlist');
            if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
                return array();
            }

            $orders = $this->searchDocuments($data);

            $ordersUpdated = array();
            $detailSrchAddtoCartAjaxUrl = '';
            foreach ($orders['rows'] as $order) {
                $docID = $order->documentTypeUntranslated == 'offer' ? $order->orderID : $order->docID;
                $detailSrchAddtoCartAjaxUrl = Mage::getUrl('customer/account/batchAddDocumentsToCart/documents/' . $docID . ':' . $order->documentTypeUntranslated);
                $order->actions = $this->getDashboardAction(
                    $order->documentTypeUntranslated,
                    $order->downloadUrl,
                    $detailSrchAddtoCartAjaxUrl,
                    $order->documentNumber,
                    $order->name,
                    $docID,
                    $order->status,
                    $order->detailUrl
                );
                $ordersUpdated[] = $order;
            }

            Mage::register('ordersList', $ordersUpdated);

            $ordersUpdated['rowPerPage'] = self::DASHBOARD_ROW_LIMIT;
            $ordersUpdated['filterStatusCounts'] = $orders['counts'];
            $ordersUpdated['filterSet'] = $orders['filters'];
            $ordersUpdated['desktopHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/detailssearchDesktop.phtml');
            $ordersUpdated['mobileHtmlBlock'] = $this->renderHtml('core/template', '', 'customer/account/documents/snippet/detailssearchMobile.phtml');

            return $ordersUpdated;
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    private function getAllPartslistsData() {
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return array();
        }
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $aclroleClass = '';
        $aclrole = $sessionLoggedInCustomer->getSchrackAclRole();
        if($aclrole == 'staff' || $aclrole == 'projectant' || $aclrole == 'list_price_customer'){
            $aclroleClass = 'hide';
        }
        $partslists = Mage::getModel('schrackwishlist/partslist')->loadByCustomer($sessionLoggedInCustomer);
        $allPartslist = array();
        $continueUrl = Mage::helper('core')->urlEncode(Mage::getUrl('wishlist/partslist/view/'));
        $urlParamName = Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED;

        foreach ($partslists as $value) {
            $params = array(
                'id' => $value->getId(),
                $urlParamName => $continueUrl
            );
            $emptyWishlistClass = "button_deactivated ";
            if (intval($value->getItemsCount()) > 0) $emptyWishlistClass = "";
            $value->updated_at = Date('Y-m-d', strtotime($value->updated_at));
            $value->openlink = Mage::getUrl('wishlist/partslist/view', array('id' => $value->getId()));
            $value->description = '<a href="' . $value->openlink . '">' . $value->description . '</a>';
            $value->deletelink = '"if (confirmDeletePartslist()) setLocation(\'' .
                    Mage::getUrl('wishlist/partslist/delete', array('id' => $value->getId(), 'forward' => 'my-partslist-tab'))
                    . '\');return false;"';
            $value->actions = '<div class="partlists-action" style="width: 200px;">
                                        <a data-id="' . $value->getId() . '" class="share share-partlist shareIcon" data-toggle="modal" data-target="#share-modal" href="">
                                        </a>    
                                        <a class="delete deleteIcon" data-id="'.$value->getId().'" onclick="if (confirmDeletePartslist()) setLocation(\'' .
                    Mage::getUrl('wishlist/partslist/delete', array('id' => $value->getId(), 'forward' => 'my-partslist-tab'))
                    . '\');return false;" href="#">                                            
                                        </a>
                                        <a class="' . $emptyWishlistClass . 'mypartlistAddtocart ajaxdispatcher bttn-sm ' . $aclroleClass . '" data-partlistid="' . $value->getId() . '" href="return false;"><span class="addToCartWhite"></span>' . $this->__("Add to Cart") . '</a>
                                </div>';
            /* finds out with whom all the partlist has been shared */
            $query = 'SELECT GROUP_CONCAT(concat(firstname_receiver," ",lastname_receiver)) AS fullname FROM `partslist_sharing_map` WHERE schrack_wws_contact_number_sharer="'.$sessionLoggedInCustomer->getSchrackWwsContactNumber().'" AND shared_partslist_id='.$value->getId();
            $sharedWith = $this->_readConnection->fetchOne($query);
            $allPartslist[] = array_merge(array('articles' => $value->getItemsCount()), $value->toArray(),array('sharedwith'=>$sharedWith));
        }


        $query = 'SELECT count(*) as unread FROM `partslist_sharing_map` where last_update_notification_flag = 1';
        $query .= " AND email_receiver LIKE '" . $sessionLoggedInCustomer->getEmail() . "'";
        $query .= " AND active = 1";

        $count = $this->_readConnection->fetchOne($query);
        $res = array(
            'active' => $this->getMyActivePartslist(),
            'all' => $allPartslist,
            'shared' => $this->getMyActiveReceivedSharedPartslists(),
            'count' => $count
        );
        echo json_encode($res);
        die;
    }

    // get a list of orders according the given filter criterias (non for all)
    // FE-Example: 'getOrders' : { 'data' : {'filter' : { 'status_credited' : 0, 'text' : 'C' }, 'sort' : { 'field' : 'orderNumber', 'ASC' : 0},  'pagination' : { 'page_size' : 12, 'page' : 1 } }}
    // Filter options are:
    // Flags: status_offered, status_open, status_commissioned, status_delivered, status_invoiced, status_credited
    // Dates: date_from, date_to
    // String: text
    private function getOrders($data) {
        $res = array();
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return array();
        }
        $helper = Mage::helper('schracksales/order');
        $searchParameters = $this->mkOrderSearchParameters($data);
        $searchParameters->isOffered = false;
        $searchParameters->getOrderDocs = true;
        $pagination = $this->getPagination($data);
        $collection = $helper->searchSalesOrdersNew($searchParameters,null,$pagination->page,$pagination->pageSize);
        $res['rows'] = $this->mkOrderListFromCollection($collection);
        $res['counts'] = $this->getOrderCounts(false, $searchParameters, true,true,true,true,true,true);
        $res['filters'] = $this->mkFiltersFromCounts($res['counts']);
        return $res;
    }

    private function getPagination ( $data ) {
        $res = new stdClass();
        if ( isset($data['data']['pagination']) && isset($data['data']['pagination']['page_size']) && isset($data['data']['pagination']['page_size']) > 0 ) {
            $res->pageSize = $data['data']['pagination']['page_size'];
            $res->page = isset($data['data']['pagination']['page']) ? $data['data']['pagination']['page'] : 1;
        } else {
            $res->pageSize = -1;
            $res->page = 1;
        }
        return $res;
    }

    // get a list of offers according the given filter criterias (non for all)
    // FE-Example: 'getOffers' : { 'data' : {'filter' : { 'status_credited' : 0, text : 'C' }}}
    // Filter options are:
    // Flags: status_offered, status_open, status_commissioned, status_delivered, status_invoiced, status_credited
    // Dates: date_from, date_to
    // String: text
    private function getOffers($data) {
        $res = array();
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return array();
        }
        $helper = Mage::helper('schracksales/order');
        $searchParameters = $this->mkOrderSearchParameters($data);
        $searchParameters->getOfferDocs = true;
        $filterOffers = isset($data['data']['filter']['status_offered']) && $data['data']['filter']['status_offered'];
        $filterOrders = isset($data['data']['filter']['status_ordered_offers']) && $data['data']['filter']['status_ordered_offers'];
        $filterAll =    (isset($data['data']['filter']['status_all']) && $data['data']['filter']['status_all'])
                     || ($filterOffers == $filterOrders); // anyhow if set or not
        if ( $filterAll ) {
            $searchParameters->isOfferd = $searchParameters->isOrderd = $searchParameters->isCommissioned
                = $searchParameters->isDeliverd = $searchParameters->isInvoiced = $searchParameters->isCredited = true;
        } else {
            $searchParameters->isOffered = $filterOffers;
            $searchParameters->isOrderd = $searchParameters->isCommissioned
                = $searchParameters->isDeliverd = $searchParameters->isInvoiced = $searchParameters->isCredited
                = $filterOrders;
        }
        $pagination = $this->getPagination($data);
        $collection = $helper->searchSalesOrdersNew($searchParameters,null,$pagination->page,$pagination->pageSize);
        $res['rows'] = $this->mkOfferListFromCollection($collection);
        $res['counts'] = $this->getOrderCounts(false, $searchParameters, true, true);
        $res['counts']->orders += ($res['counts']->commissioned + $res['counts']->delivered + $res['counts']->invoiced + $res['counts']->credited);
        $res['counts']->commissioned = $res['counts']->delivered = $res['counts']->invoiced = $res['counts']->credited = 0;
        $res['filters'] = $this->mkFiltersFromCounts($res['counts']);
        foreach ( $res['filters'] as $filter ) {
            if ( $filter->paramName == 'status_open' ) {
                $filter->paramName = 'status_ordered_offers';
            }
        }
        return $res;
    }

    // get a list of deliveries according the given filter criterias (non for all)
    // FE-Example: 'getDeliveries' : { 'data' : {'filter' : { 'status_credited' : 0, text : 'C' }}}
    // Filter options are:
    // Flags: status_offered, status_open, status_commissioned, status_delivered, status_invoiced, status_credited
    // Dates: date_from, date_to
    // String: text
    private function getDeliveries($data) {
        $res = array();
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return array();
        }
        $helper = Mage::helper('schracksales/order');
        $searchParameters = $this->mkOrderSearchParameters($data);
        $searchParameters->getDeliveryDocs = true;
        $pagination = $this->getPagination($data);
        $collection = $helper->searchSalesOrdersNew($searchParameters,null,$pagination->page,$pagination->pageSize);
        $res['rows'] = $this->mkShipmentListFromCollection($collection);
        $res['counts'] = $this->getOrderCounts(false, $searchParameters, false, false);
        $res['filters'] = $this->mkFiltersFromCounts($res['counts']);
        return $res;
    }

    // get a list of invoices according the given filter criterias (non for all)
    // FE-Example: 'getInvoices' : { 'data' : {'filter' : { 'status_credited' : 0, text : 'C' }}}
    // Filter options are:
    // Flags: status_offered, status_open, status_commissioned, status_delivered, status_invoiced, status_credited
    // Dates: date_from, date_to
    // String: text
    private function getInvoices($data) {
        $res = array();
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return array();
        }
        $helper = Mage::helper('schracksales/order');
        $searchParameters = $this->mkOrderSearchParameters($data);
        $searchParameters->getInvoiceDocs = true;
        $pagination = $this->getPagination($data);
        $collection = $helper->searchSalesOrdersNew($searchParameters,null,$pagination->page,$pagination->pageSize);
        $res['rows'] = $this->mkInvoiceListFromCollection($collection);
        $res['counts'] = $this->getOrderCounts(false, $searchParameters, false, false, false);
        $res['filters'] = $this->mkFiltersFromCounts($res['counts']);
        return $res;
    }

    // get a list of credit memos according the given filter criterias (non for all)
    // FE-Example: 'getCreditmemos' : { 'data' : {'filter' : { 'status_offered' : 0, text : 'C' }}}
    // Filter options are:
    // Flags: status_offered, status_open, status_commissioned, status_delivered, status_invoiced, status_credited
    // Dates: date_from, date_to
    // String: text
    private function getCreditmemos($data = null) {
        $res = array();
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return array();
        }
        $helper = Mage::helper('schracksales/order');
        $searchParameters = $this->mkOrderSearchParameters($data);
        $searchParameters->getCreditMemoDocs = true;
        $pagination = $this->getPagination($data);
        $collection = $helper->searchSalesOrdersNew($searchParameters,null,$pagination->page,$pagination->pageSize);
        $res['rows'] = $this->mkCreditmemoListFromCollection($collection);
        $res['counts'] = $this->getOrderCounts(false, $searchParameters, false, false, false, false);
        $res['filters'] = $this->mkFiltersFromCounts($res['counts']);
        return $res;
    }

    // get a list of documents according the given filter criterias (non for all)
    // FE-Example: 'searchDocuments' : { 'data' : {'filter' : { 'status_credited' : 0, 'delivery_documents' : 1, text : 'C' }}}
    // Filter options are:
    // Flags: status_offered, status_open, status_commissioned, status_delivered, status_invoiced, status_credited
    //        offer_documents, order_documents, delivery_documents, invoice_documents, creditmemo_documents
    // Dates: date_from, date_to
    // String: text
    private function searchDocuments($data) {
        $res = array();
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return array();
        }
        $helper = Mage::helper('schracksales/order');
        $searchParameters = $this->mkOrderSearchParameters($data);
        // $searchParameters->setSearchDocs(true);
        $pagination = $this->getPagination($data);
        $collection = $helper->searchSalesOrdersNew($searchParameters,null,$pagination->page,$pagination->pageSize);
        $res['rows'] = $this->mkMixedDocumentListFromCollection($collection);
        $res['counts'] = $this->getOrderCounts(true, $searchParameters);
        $res['filters'] = $this->mkFiltersFromCounts($res['counts']);
        return $res;
    }

    private function getOrderCounts($extended, Schracklive_SchrackSales_Helper_Order_SearchParameters $searchParameters, $flagOrdered = true, $flagComissioned = true, $flagDelivered = true, $flagInvoiced = true, $flagCredited = true, $suppressStatusOffered = false ) {
        $helper = Mage::helper('schracksales/order');
        $res = new stdClass();
        $res->all = $helper->getCountAll($searchParameters,null,$suppressStatusOffered);
        if ( ! $suppressStatusOffered )
            $res->offers = $helper->getCountOffers($searchParameters);
        if ($flagOrdered)
            $res->orders = $helper->getCountOrders($searchParameters);
        if ($flagComissioned)
            $res->commissioned = $helper->getCountCommissioned($searchParameters);
        if ($flagDelivered)
            $res->delivered = $helper->getCountDelivered($searchParameters);
        if ($flagInvoiced)
            $res->invoiced = $helper->getCountInvoiced($searchParameters);
        if ($flagCredited)
            $res->credited = $helper->getCountCredited($searchParameters);
        if ($extended) {
            $res->offerDocCount = $helper->getCountOfferDocs($searchParameters);
            $res->orderDocCount = $helper->getCountOrderDocs($searchParameters);
            $res->deliveryDocCount = $helper->getCountDeliveryDocs($searchParameters);
            $res->invoiceDocCount = $helper->getCountInvoiceDocs($searchParameters);
            $res->creditmemoDocCount = $helper->getCountCreditMemoDocs($searchParameters);
        }
        return $res;
    }

    private $filterDifferedNameMap = array(
        'all' => 'All', 'orders' => 'ordered', 'delivered' => 'shipped', 'offerDocCount' => 'Offers', 'orderDocCount' => 'Orders',
        'deliveryDocCount' => 'Shipments', 'invoiceDocCount' => 'Invoices', 'creditmemoDocCount' => 'Credit Memos'
    );
    private $filterInputNameMap = array(
        'all' => 'status_all', 'offers' => 'status_offered', 'orders' => 'status_open', 'commissioned' => 'status_commissioned',
        'delivered' => 'status_delivered', 'invoiced' => 'status_invoiced', 'credited' => 'status_credited',
        'offerDocCount' => 'offer_documents', 'orderDocCount' => 'order_documents',
        'deliveryDocCount' => 'delivery_documents', 'invoiceDocCount' => 'invoice_documents', 'creditmemoDocCount' => 'creditmemo_documents'
    );

    private function mkFiltersFromCounts ( $counts ) {
        $res = array();
        foreach ( $counts as $key => $value ) {
            if ( $value > 0 ) {
                $x = new stdClass();
                $x->uiName = $this->__(isset($this->filterDifferedNameMap[$key]) ? $this->filterDifferedNameMap[$key] : $key);
                if ( isset($this->filterInputNameMap[$key]) ) {
                    $x->paramName = $this->filterInputNameMap[$key];
                }
                $x->count = $value;
                $res[] = $x;
            }
        }
        return $res;
    }

    private function mkOrderSearchParameters($data) {
        $searchParameters = new Schracklive_SchrackSales_Helper_Order_SearchParameters();
        $searchRequest = isset($data['data']['filter']) ? $data['data']['filter'] : null;
        if (isset($searchRequest)) {
            if (   isset($searchRequest['status_offered'])      || isset($searchRequest['status_open'])
                || isset($searchRequest['status_commissioned']) || isset($searchRequest['status_delivered'])
                || isset($searchRequest['status_invoiced'])     || isset($searchRequest['status_credited'])   )
                // reset first to override default "true" fo all
                $searchParameters->isOffered = $searchParameters->isOrdered     = $searchParameters->isCommissioned
                                             = $searchParameters->isDelivered   = $searchParameters->isInvoiced
                                             = $searchParameters->isCredited    = false;
            if (isset($searchRequest['status_offered']))
                $searchParameters->isOffered = intval($searchRequest['status_offered']) !== 0;
            if (isset($searchRequest['status_open']))
                $searchParameters->isOrdered = intval($searchRequest['status_open']) !== 0;
            if (isset($searchRequest['status_commissioned']))
                $searchParameters->isCommissioned = intval($searchRequest['status_commissioned']) !== 0;
            if (isset($searchRequest['status_delivered']))
                $searchParameters->isDelivered = intval($searchRequest['status_delivered']) !== 0;
            if (isset($searchRequest['status_invoiced']))
                $searchParameters->isInvoiced = intval($searchRequest['status_invoiced']) !== 0;
            if (isset($searchRequest['status_credited']))
                $searchParameters->isCredited = intval($searchRequest['status_credited']) !== 0;

            if (isset($searchRequest['offer_documents']))
                $searchParameters->getOfferDocs = intval($searchRequest['offer_documents']) !== 0;
            if (isset($searchRequest['order_documents']))
                $searchParameters->getOrderDocs = intval($searchRequest['order_documents']) !== 0;
            if (isset($searchRequest['delivery_documents']))
                $searchParameters->getDeliveryDocs = intval($searchRequest['delivery_documents']) !== 0;
            if (isset($searchRequest['invoice_documents']))
                $searchParameters->getInvoiceDocs = intval($searchRequest['invoice_documents']) !== 0;
            if (isset($searchRequest['creditmemo_documents']))
                $searchParameters->getCreditMemoDocs = intval($searchRequest['creditmemo_documents']) !== 0;

            if (isset($searchRequest['date_from']))
                $searchParameters->fromDate = $searchRequest['date_from'];
            if (isset($searchRequest['date_to']))
                $searchParameters->toDate = $searchRequest['date_to'];
            if (isset($searchRequest['text']))
                $searchParameters->text = $searchRequest['text'];
        }
        if ( isset($data['data']['sort']) && isset($data['data']['sort']['field']) ) {
            switch ( $data['data']['sort']['field'] ) {
                case 'orderNumber'      :  $searchParameters->sortColumnName = 'schrack_wws_order_number';      break;
                case 'shipmentNumber'   :  $searchParameters->sortColumnName = 'ShipmentNumber';                break;
                case 'invoiceNumber'    :  $searchParameters->sortColumnName = 'InvoiceNumber';                 break;
                case 'creditmemoNumber' :  $searchParameters->sortColumnName = 'CreditmemoNumber';              break;
                case 'documentNumber'   :  $searchParameters->sortColumnName = 'wws_document_number';           break;
                case 'offerNumber'      :  $searchParameters->sortColumnName = 'schrack_wws_offer_number';      break;
                case 'name'             :  $searchParameters->sortColumnName = 'schrack_wws_reference';         break;
                case 'creationData'     :
                case 'creationDateYmd'  :  $searchParameters->sortColumnName = 'document_date_time';            break;
                case 'validUntil'       :
                case 'validUntilYmd'    :  $searchParameters->sortColumnName = 'schrack_wws_offer_valid_thru';  break;
            }
             $searchParameters->isSortAsc = isset($data['data']['sort']['ASC']) && $data['data']['sort']['ASC'];
        }
        return $searchParameters;
    }

    private function mkOrderListFromCollection($collection) {
        $res = array();
        foreach ($collection as $order) {
            $rec = $this->mkOrderListItem($order, 'order', $order->getOrderId());
            $res[] = $rec;
        }
        return $res;
    }

    private function mkShipmentListFromCollection($collection) {
        $res = array();
        $helper = Mage::helper('schracksales/order');
        foreach ($collection as $order) {
            $rec = $this->mkOrderListItem($order, 'shipment', $order->getShipmentId());
            $rec->trackAndTraceUrl = $helper->getTrackandtraceUrl($order);
            $res[] = $rec;
        }
        return $res;
    }

    private function mkMixedDocumentListFromCollection($collection) {
        $res = array();
        $helper = Mage::helper('schracksales/order');
        foreach ($collection as $order) {
            $type = 'order';
            $id = $order->getOrderId();
            $la = $order->getSchrackWwsStatus();
            if (strtolower($la) == 'la1') {
                $type = 'offer';
                $id = $order->getData('schrack_wws_offer_number');
            } else if ($order->getShipmentId()) {
                $type = 'shipment';
                $id = $order->getShipmentId();
            } else if ($order->getInvoiceId()) {
                $type = 'invoice';
                $id = $order->getInvoiceId();
            } else if ($order->getCreditMemoId()) {
                $type = 'creditmemo';
                $id = $order->getCreditMemoId();
            }
            $rec = $this->mkOrderListItem($order, $type, $id);
            if ($type == 'offer') {
                $rec->documentNumber = $order->getData('schrack_wws_offer_number');
            }
            if ($type == 'shipment') {
                $rec->trackAndTraceUrl = $helper->getTrackandtraceUrl($order);
            }
            $rec->documentTypeUntranslated = $type;
            $rec->documentType = $this->__($type);
            $res[] = $rec;
        }
        return $res;
    }

    private function mkInvoiceListFromCollection($collection) {
        $res = array();
        $helper = Mage::helper('schracksales/order');
        foreach ($collection as $order) {
            $rec = $this->mkOrderListItem($order, 'invoice', $order->getInvoiceId());
            $res[] = $rec;
        }
        return $res;
    }

    private function mkCreditmemoListFromCollection($collection) {
        $res = array();
        $helper = Mage::helper('schracksales/order');
        foreach ($collection as $order) {
            $rec = $this->mkOrderListItem($order, 'creditmemo', $order->getCreditMemoId());
            $res[] = $rec;
        }
        return $res;
    }

    private function mkOrderListItem($order, $type, $documentID) {
        $rec = new stdClass();
        $rec->orderID = $order->getOrderId();
        $rec->docID = $documentID;

        $rec->isLa1 = strtolower($order->getData('schrack_wws_status')) == 'la1';
        $rec->orderNumber = $order->getData('schrack_wws_order_number');
        $rec->documentNumber = $order->getData('wws_document_number');
        $rec->nameProject = $rec->name = $order->getData('schrack_wws_reference');
        $rec->project = $order->getData('schrack_customer_project_info');
        if ( intval(Mage::getStoreConfigFlag('schrack/shop/enable_custom_project_info_in_checkout')) == 1 ) {
            $rec->nameProject = ($rec->name > '' ? $rec->name : '-') . '/' . ($rec->project > '' ? $rec->project : '-');
        }
        $rec->creationDate = $order->getData('document_date_time');
        $rec->creationDateYmd = Date('Y-m-d', strtotime($rec->creationDate));
        if ($rec->creationDate && intval($p = strpos($rec->creationDate, ' ')) > 0) {
            $rec->creationDate = $this->getFormattedDate(strtotime(substr($rec->creationDate, 0, $p)));
        }
        $rec->status = $this->__($this->getDocumentStatus($order));
        $rec->currency = $order->getData('Currency');
        $rec->amount = $order->getData('Amounts_Net');
        $rec->detailUrl = Mage::getUrl('customer/account/documentsDetailView', array('id' => $rec->orderID, 'type' => $type, 'documentId' => $documentID));
        $rec->detailUrlOrder = Mage::getUrl('customer/account/documentsDetailView', array('id' => $rec->orderID, 'type' => 'order', 'documentId' => $rec->orderID));
        $rec->downloadUrl = Mage::getUrl('customer/account/documentsDownload', array('id' => $rec->orderID, 'type' => $type, 'documentId' => $documentID));
        return $rec;
    }

    private function mkOfferListFromCollection($collection) {
        $res = array();
        foreach ($collection as $offer) {
            $rec = new stdClass();
            $rec->orderID = $offer->getOrderId();
            $rec->nameProject = $rec->name = $offer->getData('schrack_wws_reference');
            $rec->project = $offer->getData('schrack_customer_project_info');
            if ( intval(Mage::getStoreConfigFlag('schrack/shop/enable_custom_project_info_in_checkout')) == 1 ) {
                $rec->nameProject = ($rec->name > '' ? $rec->name : '-') . '/' . ($rec->project > '' ? $rec->project : '-');
            }
            $rec->offerNumber = $offer->getData('schrack_wws_offer_number');
            $rec->orderNumber = $offer->getData('schrack_wws_order_number');
            $rec->creationDateYmd = Date('Y-m-d', strtotime($offer->getData('schrack_wws_offer_date')));
            $rec->creationDate = $this->getFormattedDate(strtotime($offer->getData('schrack_wws_offer_date')));
            $rec->validUntilYmd = Date('Y-m-d', strtotime($offer->getData('schrack_wws_offer_valid_thru')));
            $rec->validUntil = $this->getFormattedDate(strtotime($offer->getData('schrack_wws_offer_valid_thru')));
            $rec->status = intval(substr($offer->getData('schrack_wws_status'), 2)) == 1 ? $this->__('offer_new') : $this->__('offer_ordered');
            $orderID = $rec->orderID;
            $type = 'offer';
            $documentID = $rec->offerNumber;
            $rec->docID =$documentID;   // Added by Nagarro to get doc id
            $rec->currency = $offer->getData('Currency');
            $rec->amount = $offer->getData('Amounts_Net');
            $rec->detailUrl = Mage::getUrl('customer/account/documentsDetailView', array('id' => $rec->orderID, 'type' => $type, 'documentId' => $documentID));
            $rec->downloadUrl = Mage::getUrl('customer/account/documentsDownload', array('id' => $orderID, 'type' => $type, 'documentId' => $documentID));
            $res[] = $rec;
        }
        return $res;
    }

    private function getDocumentStatus($document) {
        switch (strtolower($document->getSchrackWwsStatus())) {
            case 'la1':
                return 'offered';
            case 'la0':
            case 'la1online':
                return 'offered online';
            case 'la2':
                return 'ordered';
            case 'la3':
                return 'commissioned';
            case 'la4':
                if ($document->getData('schrack_sum_backorder_qty') > 0) {
                    return 'partly shipped';
                } else {
                    return 'delivered';
                }
            case 'la5':
                if ($document->getData('schrack_sum_backorder_qty') > 0) {
                    return 'partly invoiced';
                } else {
                    return 'invoiced';
                }
            case 'la8':
                return 'credited';
            default:
                return 'unknown';
        }
    }

    // Format time stamp format to date format
    protected function getFormattedDate($timestamp = null) {
        if (!$timestamp) {
            return '';
        }
        $date = new Zend_Date($timestamp);
        return $date->get(Zend_Date::DATE_MEDIUM);
    }

    // Prepare Partlist <li> containers for Dashboard Add to Partlist Action
    protected function getPartslistsContainer($psDocumentType, $documentNumber) {
		$partListData = '';
		$overview = 'overview';
		$partListData .= '<li class="add-to-new-partslist" onclick="checkLsChkBx(this);partslistFE.addCheckedItemsToNewList(\'' . $this->__("New Partlist") . '\', \'rowId' . $documentNumber . '\', \'documentId\', \'type\', \'' . Mage::getSingleton('core/session')->getFormKey() . '\', true, \'' . $overview . '\');"><span class="glyphicon glyphicon-plus-sign plusIcon"></span>&nbsp;' . $this->__("Add to new partslist") . '</li>';
		$sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
		$partslists = Mage::registry('current_dashboard_partlist');
		if ( ! $partslists ) {
            $partslists = Mage::getModel('schrackwishlist/partslist')->loadByCustomer($sessionLoggedInCustomer);
    		Mage::register('current_dashboard_partlist',$partslists);
        }
		foreach ($partslists as $partslist) {
			//$partListDataObj[$partslist->getId()] = $partslist->getDescription();
			$partListData .= '<li onclick="dashAddToPartlistAjaxCall(' . $partslist->getId() . ', \'' . $psDocumentType . '\', this, \'' . Mage::getSingleton('core/session')->getFormKey() . '\', \'overview\');" title="' . $partslist->getDescription() . '">' . $this->__("Add to %s", $partslist->getDescription()) . '</li>';
		}
		return $partListData;
    }

    // Prepare Dashboard Page Action Column HTML
    protected function getDashboardAction($documentType, $docDownloadAjaxUrl, $docAddtoCartAjaxUrl, $documentNumber, $description = ' ', $id, $docStatus, $dashDetailUrl) {
        $mayCheckout = Mage::helper('geoip')->mayPerformCheckout();
		$useMDoc = Mage::getStoreConfig('schrack/mdoc/use_mdoc');	// Added by Nagarro to get statue PDF download icon visible or not in action column
        //Mage::log('Document-Type = ' . $documentType, null, "dashboard_action_document_type.log" );
        $parsedDocumentType = $documentType;
        if (strtolower($documentType) == 'offer') {
            $parsedDocumentType = 'offer';
        }
        //Mage::log('Parsed Document-Type = ' . $parsedDocumentType, null, "dashboard_action_document_type.log" );
        $partListContainer = $this->getPartslistsContainer($parsedDocumentType, $id);
        $aclrole = '';
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $aclrole = $customer->getSchrackAclRole();
        }
        if ( $parsedDocumentType === 'offer' ) {
            $addToCartButton = '';
            if ( $aclrole == 'staff' || $aclrole == 'projectant' || $aclrole == 'list_price_customer' ) {
                $orderNowButton = '';
            } else {
                $orderNowButton = '<a href="' . $dashDetailUrl .'" title="' . $this->__('View Offer') . '"><i class="eyeIcon selection-print-button" style="height: 18px !important; width: 33px !important; margin-top: 6px;"></i></a>';
            }
        } else {
            $orderNowButton = '';
            if ( ! $mayCheckout || $aclrole == 'staff' || $aclrole == 'projectant' || $aclrole == 'list_price_customer' ) {
                $addToCartButton = '';
            } else {
                $addToCartButton = '<a ';
                if ( Mage::getStoreConfig('ec/config/active') ) { //for GTM
                    $addToCartButton .= ' onclick="dashGetProductslistAsSkulistByDocumentAjaxCall(\'' . $parsedDocumentType . '\', \'' . $id . '\', \'' . Mage::getSingleton('core/session')->getFormKey() . '\', \'cart\');documentAddtoCartAjaxCall(\'' . $docAddtoCartAjaxUrl . '\'); return false;" '; //AEC.adddocumenttocart(this,dataLayer);
                } else {
                    $addToCartButton .= ' onclick="documentAddtoCartAjaxCall(\'' . $docAddtoCartAjaxUrl . '\');" ';
                }
                 $addToCartButton .= ' title="' . $this->__('Add to Cart') . '"> <i class="addToCartIcon selection-print-button" style="height: 24px !important; width: 28px !important; margin-top: 3px;"></i></a>';
            }
        }
		if($useMDoc == 1) {
		    $pdfButton = '<a title="' . $this->__('Download PDF') . '" href="javascript:void(0)" onclick="documentDownloadAjaxCall(\'' . $docDownloadAjaxUrl . '\', \'' . $parsedDocumentType . '\')"><i class="pdfIcon selection-print-button" style="color:#878787"></i></a>';
		} else {
		    $pdfButton = '';
        }
        if (strtolower($parsedDocumentType) == 'offer') {
            $realtype = 'offer';
            $csvType  = 'order';
        } else {
            $csvType  = $documentType;
            $realtype = $documentType;
        }
        //Mage::log('Document-Id-1 = ' . $id . ' --- Document-Id-2 = ' . $documentNumber, null, "dashboard_action_document_id.log" );
		$csvUrl = Mage::getUrl("sd/Csv/csvFromDocument/documentId/$id/type/$csvType/");
		$csvButton = '<a class="print_button_overview_csv" title="' . $this->__('Download CSV') . '" href="' . $csvUrl .'"><i class="csvIcon selection-print-button" style="color:#878787"></i></a>';
		$partslistButton = '<span data-toggle="dropdown" title="' . $this->__('Add to partslist') . '" id="parlistdropdownbtn-rowId' . $id. '" '
                                .' aria-haspopup="true" aria-expanded="false"><i class="pin-icon partlistBlueIcon" style="color:#00589d"></i></span>'
                         . ' <ul class="dropdown-list dropdown-menu src_ajax_dispatcher" aria-labelledby="parlistdropdownbtn-rowId' . $id . '" '
                                .' doc-id="'.$id.'">' . $partListContainer . '</ul>';

        $dashActionHtml = '<div class="product-name posRel"><input type="checkbox" class="rowId' . $id . '" id="rowId-' . $id . '" style="display:none;" />'
                        . ' <input type="hidden" id="documentId-' . $id . '" value="' . $id . '" />'
                        . ' <input type="hidden" id="type-' . $id . '" value="' . $csvType . '" />'
                        . ' <input type="hidden" id="realtype-' . $id . '" value="' . $realtype . '" />'
                        . ' <input type="hidden" id="orderId-' . $id . '" value="' . $id . '" />';

        $dashActionHtml .= "$partslistButton $pdfButton $csvButton $addToCartButton $orderNowButton</div>";

        return $dashActionHtml;
    }

    private function getQuickAddPopup($data = null) {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('core/template');
        $block->setTemplate('checkout/cart/quickadd.phtml');
        return $block->toHtml();
    }

    private function getVisitorInfo($data = null) {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('core/template');
        $block->setTemplate('ec/visitor.phtml');
        return $block->toHtml();
    }

    // delete an address with the given (Magento) address_id
    // FE example: 'setDeleteAddress' : { 'data' : {'address_id' : 40527 }}
    private function setDeleteAddress($data) {
        $res = array('result' => '[SUCCESS] address deleted.');
        $resultData['status'] = 'success';
        if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
            $res['result'] = '[ERROR] not logged in.';
            $res['status'] = 'error';
            return $res;
        }
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if (!isset($data['data']['address_id']) || !$data['data']['address_id']) {
            $res['result'] = "[ERROR] missing parameter address_id!";
            $res['status'] = 'error';
            return $res;
        }
        $address = Mage::getModel('customer/address')->load($data['data']['address_id']);
        if (!$address || !$address->getId()) {
            $res['result'] = "[ERROR] address_id not found!";
            $res['status'] = 'error';
            return $res;
        }
        if ($address->getSchrackWwsAddressNumber() === Schracklive_SchrackCustomer_Model_Address::NO_ADDRESS_NUMBER) {
            $res['result'] = "[ERROR] address number not returned from CRM yet!";
            $res['status'] = 'error';
            return $res;
        }
        if (!$address->getCustomer() || $address->getCustomer()->getScrackWwsCustomerId() != $customer->getScrackWwsCustomerId()) {
            $res['result'] = "[ERROR] address does not belong to the logged in customer!";
            $res['status'] = 'error';
            return $res;
        }
        $address->delete();

        return $res;
    }

    public function getFooter() {
        $typo3footer = false;
        $typo3url = Mage::getStoreConfig('schrack/typo3/typo3url');
        if (strlen($typo3url) > 0) {
            $cache = Mage::app()->getCache();
            $stringRes = $cache->load('schrack_typo3_footer');
            if ($stringRes) {
                $typo3footer = unserialize($stringRes);
            } else {
                try {
                    /** @var $typo3helper Schracklive_Typo3_Helper_Data */
                    $typo3helper = Mage::helper('typo3');
                    $response = $typo3helper->getResponse($typo3url . Mage::getStoreConfig('schrack/typo3/typo3menufooter'));
                    $responseStatus = $response->getStatus();
                } catch (Exception $e) {
                    $response = null;
                    $responseStatus = 500;
                    //Mage::logException($e);
                }

                if (is_object($response) && $responseStatus == 200) {
                    $typo3footer = $response->getBody();
                    $stringRes = serialize($typo3footer);
                    $lifetimeHours = 1;
                    $cache->save($stringRes, 'schrack_typo3_footer', array(), $lifetimeHours * 60 * 60);

                }
            }
        }
        $typo3footer = str_replace('data-src', 'src',  $typo3footer);
        return $typo3footer;
    }

    private function ensureUTF8 ( $s ) {
        if ( ! (bool)preg_match('//u', serialize($s)) ) {
            $s = utf8_encode($s);
        }
        return $s;
    }

    private function setCartItemDescription ($data) {
        $itemId                   = $data['data']['item_id'];
        $descriptionTextOrg       = $data['data']['descriptionText'];
        $boolFoundItem            = false;
        $prepareDescriptionForWWS = '';
        $result                   = array();
        $result['result']         = '[ERROR] No Data changed';
        $result['status']         = 'error';

        // Warenkorb (+ Positionen)checken:
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        foreach ($quote->getItemsCollection() as $item) {
            if (intval($itemId) == $item->getId()) {
                $boolFoundItem = true;
            }
        }

        if ($boolFoundItem == true) {
            $descriptionText = preg_replace('/["\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $descriptionTextOrg);
            if (strlen($descriptionText) > 60) {
                $descriptionText1 = substr($descriptionText, 0, 60);
                $descriptionText2 = substr($descriptionText, 60, strlen($descriptionText));
                $prepareDescriptionForWWS = $descriptionText1 . chr(10) . $descriptionText2;
            } else {
                $prepareDescriptionForWWS = $descriptionText;
            }

            // Save description in DB at selected item:
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $query = "UPDATE sales_flat_quote_item SET schrack_item_description = '" . $prepareDescriptionForWWS . "' WHERE item_id = " . intval($itemId);
            $writeConnection->query($query);

            $result = array('result' => '[SUCCESS] cart item desription saved.');
        }

        return $result;
    }

    private function setPartslistItemDescription ($data) {
        $partlistId                         = $data['data']['partslist_id'];
        $itemId                             = $data['data']['item_id'];
        $descriptionTextOrg                 = $data['data']['descriptionText'];
        $result                             = array();
        $result['result']                   = '[ERROR] No Data changed';
        $result['status']                   = 'error';
        $partslistFoundOnExpetectedCustomer = false;

        $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
        $partslists = Mage::getModel('schrackwishlist/partslist')->loadByCustomer($sessionLoggedInCustomer);

        foreach ($partslists as $partslist) {
                if ($partslist->getId() == $partlistId) {
                    $partslistFoundOnExpetectedCustomer = true;
                }
        }

        if ($partslistFoundOnExpetectedCustomer == true) {

            $descriptionText = preg_replace('/["\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $descriptionTextOrg);

            // Save description in DB at selected item:
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $query = "UPDATE partslist_item SET description = '" . $descriptionText . "' WHERE partslist_item_id = " . intval($itemId);

            try {
                $writeConnection->query($query);
                $result = array('result' => '[SUCCESS] partslist item desription saved.');
            } catch (Exception $ex) {
                $result = array('result' => '[ERROR] partslist item text cannot be saved.');
            }
        } else {
            $result = array('result' => '[ERROR] partslist not found.');
        }

        return $result;
    }
    
    private function formatAmount ( $amount ) {
        if ( is_string($amount) ) {
            $pDot = strpos($amount, '.');
            $pKomma = strpos($amount, ',');
            if ( $pDot === false ) {
                $amount = str_replace(',', '.', $amount);
            } else {
                if ( $pKomma === false ) {
                    // do nothing
                } else {
                    if ( $pDot > $pKomma ) {
                        $amount = str_replace(',', '', $amount);
                    } else {
                        $amount = str_replace('.', '', $amount);
                        $amount = str_replace(',', '.', $amount);
                    }
                }
            }
        }
        $amount = floatVal($amount);
        $amount = Mage::helper('core')->currency($amount, true, false);
        return $amount;
    }

    public function getPromotionSKUs ( $data ) {
        $skus = $data['data']['skus'];
        /* @var $helper Schracklive_Promotions_Helper_Data */
        $helper = Mage::helper('promotions');
        $res = $helper->getPromotionSKUs($skus);
        return $res;
    }

    public function getIsPromotionSKU ( $data ) {
        $sku = $data['data']['sku'];
        /* @var $helper Schracklive_Promotions_Helper_Data */
        $helper = Mage::helper('promotions');
        $res = $helper->isPromotionProduct($sku);
        return $res;
    }

    public function getProductsForPromoID ( $data ) {
        try {
            $promoID = $data['data']['promo_id'];
            $catIDs = isset($data['data']['filter_cat_ids']) ? $data['data']['filter_cat_ids'] : null;
            /* @var $helper Schracklive_Promotions_Helper_Data */
            $helper = Mage::helper('promotions');
            $skus = $helper->getPromotionSKUsForPromotion($promoID, $catIDs);
            $request = $data;
            if ( count($skus) == 0 ) {
                $skus[] = '__________'; // using an non existing sku to get a 0 result
                $chunkSkus = $skus;
            } else {
                $start = (int) $request['data']['start'];
                $limit = $request['data']['limit'];
                $chunkSkus = array_slice($skus,$start,$limit); // request only needed skus from solr to keep control over sorting
                $request['data']['start'] = 0; // for Solr
            }
            $rawSolrData = $this->getSearchResult($request, $chunkSkus); // get product data from solr
            $rawSolrData['products'] = $this->loadTemporarilyNotFromSolrCommingProductData($rawSolrData['products']);
            // bring the somehow from solr sorted products back into the order we got from promo helper:
            $skuSortMap = array_flip($chunkSkus);
            $sortedProducts = [];
            foreach ( $rawSolrData['products'] as $product ) {
                $sku = $product['sku'];
                $order = $skuSortMap[$sku];
                $sortedProducts[$order] = $product;
            }
            $rawSolrData['products'] = $sortedProducts;
            // overwrite status from solr (is just for the chunk) with status for all promo products:
            $rawSolrData['status']['count'] = $skus[0] == '__________' ? 0 : count($skus);
            $rawSolrData['status']['start'] = $start;
            $filters = $helper->getFilterForPromotion($promoID);
            $res = ['status' => $rawSolrData['status'], 'products' => $rawSolrData['products'], 'filters' => $filters];
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            $res = ['status' => array(), 'products' => array(), 'filters' => array()];
        }
        return $res;
    }

    private function validateEmailAddress ( $data ) {
        $email = $data['data']['email_address'];
        $res = [
            'email_address' => $email,
            'is_valid' => true,
            'message' => ''
        ];
        $helper = Mage::helper('schrack/email');
        $ok = $helper->validateEmailAddress($email);
        if ( ! $ok ) {
            $res['is_valid'] = false;
            $res['message'] = $this->__('Please enter a valid email address. For example johndoe@domain.com.');
            return $res;
        }
        $duplicate = $helper->emailExistsInCommenDB($email);
        if ( $duplicate ) {
            $res['is_valid'] = false;
            $res['message'] = $this->__('Customer email already exists');
            return $res;
        }
        return $res;
    }
    //============================= loadTemporarilyNotFromSolrCommingProductData
    private function loadTemporarilyNotFromSolrCommingProductData ( array $products ) {
    //==========================================================================
        $skus = array();
        //--------------------- get all sku's for string concatination for query
        foreach ( $products as $product ) { $skus[] = $product['sku']; }
        //---------------------------------------------------------------- query
        $sql = "SELECT sku, schrack_sts_promotion_label FROM " .
               "catalog_product_entity WHERE sku IN " .
               "('" . implode("','",$skus) . "')";
        //----------------------------------------------------------------------
        $dbRes = $this->_readConnection->fetchAll($sql);
        //----------------------------------------------------------------------
        $sku2rowMap = array();
        foreach ( $dbRes as $row ) { $sku2rowMap[$row['sku']] = $row; }
        //--------------------- add schrack_sts_promotion_label info to products
        foreach ( $products as $ndx => $product ) {
            $key = $product['sku'];
            $products[$ndx]['schrackStsPromotionLabel'] = $sku2rowMap[$key]['schrack_sts_promotion_label'];
        }
        //----------------------------------------------------------------------
        return $products;
    } //================= loadTemporarilyNotFromSolrCommingProductData ***END***

    private function removeCDatasFromProducts  ( array $products ) {
        foreach ( $products as $ndx => $product ) {
            $products[$ndx]['name'] = $this->removeCDatasFromString($product['name']);
            $products[$ndx]['detailDescription'] = $this->removeCDatasFRomString($product['detailDescription']);
        }
        return $products;
    }

    private function removeCDatasFromString  ( $str ) {
        $str = str_replace('<![CDATA[','',$str);
        $str = str_replace(']]>','',$str);
        return $str;
    }

}

if (!function_exists('boolval')) {

    function boolval($my_value) {
        return (bool) $my_value;
    }

}
