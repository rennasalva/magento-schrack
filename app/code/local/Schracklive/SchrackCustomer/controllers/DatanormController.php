<?php

class Schracklive_SchrackCustomer_DatanormController extends Mage_Core_Controller_Front_Action {
    private $_errorMsgs = array('101' => 'Kunde wurde noch nicht initialisiert, Methode funktioniert noch nach altem Schema.');
    
    /**
     * Action predispatch
     *
     * Check customer authentication for all actions
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }

        $session = Mage::getSingleton('customer/session');
        if (!$session->authenticate($this)) {
           $this->setFlag('', 'no-dispatch', true);
        }        
    }
     
    
    public function massuploadAction() {
        $this->loadLayout();
        $session = Mage::getSingleton('customer/session');
        try {
            if ($session->isLoggedIn()) {
                $customer = $session->getCustomer();
                if (!defined('DEBUG') && intval($customer->getGroupId()) !== 4) {
                    throw new Exception('disallowed');
                }
            } else
                throw new Exception('disallowed');
        } catch(Exception $e) {
            Mage::logException($e);
            $session->addError($e->getMessage());
            return $this->_redirect('customer/account');
        }
        $this->renderLayout();
    }
    public function postAction() {
        set_time_limit(0);
        $this->loadLayout();
        $session = Mage::getSingleton('customer/session');

        try {
            if ($this->getRequest()->isPost() && $session->isLoggedIn()) {
                $customer = $session->getCustomer();
                if (!defined('DEBUG') && intval($customer->getGroupId()) !== 4) {
                    throw new Exception('disallowed');
                }

                $emailAddress = $customer->getEmail();
                $emailName = $customer->getFirstname() . ' ' . $customer->getLastname();
                $customerIds = $this->getRequest()->getParam('customerIds');
                $type2functionMap = array( 'datanorm' => Schracklive_Datanorm_Model_Main::EXPORT_FUNCTION_DATANORM,
                                           'csv'      => Schracklive_Datanorm_Model_Main::EXPORT_FUNCTION_CSV,
                                           'xml'      => Schracklive_Datanorm_Model_Main::EXPORT_FUNCTION_XML);
                $exportType = $type2functionMap[$this->getRequest()->getParam('exportType', false)];
                $incImgUrls = $this->getRequest()->getParam('incImgUrls', false);
                $groupArticlesBySchrackStructure = $this->getRequest()->getParam('groupArticlesBySchrackStructure', false);
                $useEdsArticleNumbers = $this->getRequest()->getParam('useEdsArticleNumbers', false);
                $withoutLongText = $this->getRequest()->getParam('withoutLongText', false);
                $withoutUVP = $this->getRequest()->getParam('withoutUVP', false);
                $ctryCode = $this->getRequest()->getParam('ctryCode', 'at');
                $model = Mage::getModel('datanorm/main');
                $results = array();
                foreach (explode("\n", $customerIds) as $customerId) {
                    $customerId = preg_replace('/[\r\n\t ]+/', '', $customerId);           
                    if (strlen($customerId)) {
                        $args = array(
                            'exportType' => $exportType,
                            'wwsCustomerId' => $customerId,
                            'includePictureURLs' => $incImgUrls,
                            'groupArticlesBySchrackStructure' => $groupArticlesBySchrackStructure,
                            'useEdsArticleNumbers' => $useEdsArticleNumbers,
                            'withoutLongText' => $withoutLongText,
                            'withoutResellPrices' => $withoutUVP,
                            'ctryCode' => $ctryCode
                        );
                        $getRes = $model->callGet($args);
                        Mage::log('customerId: ' . $customerId . ', result: ' . $getRes, null, 'datanorm_massupload.log');
                        if (is_string($getRes))
                            $results[$customerId]['url'] = $getRes;
                        else {
                            $results[$customerId]['errorCode'] = $getRes;
                            $results[$customerId]['errorMessage'] = isset($this->_errorMsgs[$getRes]) ? $this->_errorMsgs[$getRes] : 'unknown error';
                        }
                    }
                }
                $this->_sendResultEmail($results, $emailAddress, $emailName);
                die(); // die without a whisper so we never get killed!
                // for debugging, just remove the die() above and you'll see the results
                $l = $this->getLayout();
                $block = $this->getLayout()->getBlock('datanorm_massupload_post');
                $block->assign('results', $results);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $session->addError($e->getMessage());
            return $this->_redirect('customer/account');
        }

        $this->renderLayout();
    }
    

    private function _sendResultEmail($results, $emailAddress, $emailName) {
       $resultTable = '<table><tr><th>Customer ID</th><th>Datanorm Link</th></tr>';
       foreach($results as $customerId => $res) {
           if (isset($res['url'])) {
                $resultTable .= '<tr><td>' . $customerId . '</td><td><a href="' . $res['url'] . '" target="_blank">' . $res['url'] . '</a></td></tr>';
           } else {
                $resultTable .= '<tr><td>' . $customerId . '</td><td>ERROR: ' . $res['errorMessage'] . ' (' . $res['errorCode'] . ')' . '</td></tr>';
           }
       }
       $resultTable .= '</table>';
       
       
       $text = <<<EOF
<p>Hello,</p>
     
<p>these are the results of your datanorm request:</p>

$resultTable
    
<p>Regards,</p>

<p>Schrack Technik/IT Team</p>
EOF;
       
        $mail = new Zend_Mail();
        $tr = new Zend_Mail_Transport_Smtp(Mage::getStoreConfig('system/smtp/host'), array('port' => Mage::getStoreConfig('system/smtp/port')));
        Zend_Mail::setDefaultTransport($tr);
        $mail->setBodyHtml($text);
        $mail->setFrom('noreply@schrack.at', 'Schrack Technik GmbH');
        $mail->addTo($emailAddress, $emailName);
        $mail->setSubject('Datanorm Results');
        $mail->send();      
    }    
}

?>