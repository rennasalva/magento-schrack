<?php
/**
 * MageDeveloper TYPO3connect Module
 * ---------------------------------
 *
 * @category    Mage
 * @package    MageDeveloper_TYPO3connect
 */
require_once Mage::getModuleDir('controllers', 'Mage_Customer') . DS . 'AccountController.php';
require_once Mage::getModuleDir('controllers', 'Orcamultimedia_Sapoci') . DS . 'AccountController.php';
require_once Mage::getModuleDir('controllers', 'Orcamultimedia_Ids') . DS . 'AccountController.php';

class MageDeveloper_TYPO3connect_AccountController extends Orcamultimedia_Sapoci_AccountController
{
 	/**
     * Login post action
	 * This is the action for the magento login
	 * when user logs in when is currently located
	 * at magento
     */
    public function loginPostAction()
    {
        return parent::loginPostAction();
    }
	
    /**
     * Customer logout action
     */
    public function logoutAction()
    {
    	// True session logout
        $this->_getSession()->logout()->setBeforeAuthUrl(Mage::getUrl());

		// Possible redirect settings				
		$redirSetting = $this->getRequest()->getParam('redir');	
		$source	      = $this->getRequest()->getParam('source');

        $params = array('logintype' => 'logout',
            'redir'		=> 'config',
            'source'	=> urlencode($source)
        );
        $url = Mage::helper('typo3connect')->getTypo3BaseUrl();

		$this->_redirectUrl( $this->_addParameterToUrl($params, $url) );
    }
	
	/**
	 * forgotPasswordAjaxAction
	 * Ajax Action for the forgot password function
	 * Displays on string data and uses posted vars
	 */
	public function forgotPasswordAjaxAction()
	{
        $email = (string) $this->getRequest()->getPost('email');

        if ($email) {
			$emailVerifyExpression = (bool)preg_match('/^[^@]+@[^@]+\.[a-zA-Z]{2,}$/', trim($email) );
			//if (!Zend_Validate::is($this->getEmail(), 'EmailAddress')) { --> Zend verification is deprecated
			if (!$emailVerifyExpression) {
                $this->_response( $this->__('Invalid email address.') );
				return;
            }

            /** @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);

            if ($customer->getId()) {
                try {
                    $newResetPasswordLinkToken = Mage::helper('customer')->generateResetPasswordLinkToken();
                    $customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                    $customer->sendPasswordResetConfirmationEmail();
                } catch (Exception $exception) {
                	$this->_response( $this->__('Error sending reset-password link.') );
                    return;
                }
            }
			$this->_response( Mage::helper('customer')->__('If there is an account associated with %s you will receive an email with a link to reset your password.', Mage::helper('customer')->escapeHtml($email) ) );
            return;
        } else {
        	$this->_response( $this->__('Please enter your email.') );
            return;
        }
	}

	/**
	 * loginConnectAction
	 * Action for TYPO3connect gateway
	 * This action is for the user who comes from
	 * the login form on TYPO3
	 */
	public function loginConnectAction()
	{
		/*
		// Check if user is currently logged in and
		// log out if he is already logged in
		if ($this->_getSession()->isLoggedIn()) {
			$this->_getSession()->logout();
        }
		
		// Params
		$helper = Mage::helper('typo3connect');
		
        $username 		= urldecode( $helper->secGP( $this->getRequest()->getParam('username') ) );
        $password	 	= urldecode( $helper->secGP( $this->getRequest()->getParam('password') ) );
        $typo3baseUrl 	= Mage::helper('typo3connect')->getTypo3LoginUrl();
		$finalRedirect 	= urldecode( $helper->secGP( $this->getRequest()->getParam('source') ) );
		
		
		if( $username == NULL || empty($username) || $password == NULL || empty($password)) {
			$error = $this->__('Please fill out all credentials');
			$this->_redirectUrl($this->_createErrorRedirectUrl($typo3baseUrl, $error));
		}     
		$credentials = $this->_authEncryptedLogin($username, $password);
		
		if ( is_array($credentials) )
        {
			try {
					
				$this->_getSession()->login($credentials['username'], $credentials['password']);
			
			} catch (Mage_Core_Exception $e) {
				
				switch ($e->getCode()) {
					case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
						$error = $this->__('Wrong email or password.');
						$this->_redirectUrl($this->_createErrorRedirectUrl($typo3baseUrl, $error));
						break;
							
					default:
						$error = $this->__('Unknown error.' . $e->getCode());
						$this->_redirectUrl($this->_createErrorRedirectUrl($typo3baseUrl, $error));
						break;
				}
				
			} catch (Exception $e) { 
				$error = $this->__('Unknown error.' . $e);
				$this->_redirectUrl($this->_createErrorRedirectUrl($typo3baseUrl, $error));
			}
        }
		
		// Check if user is logged in
		if ($this->_getSession()->isLoggedIn()) {
					
			$auth = Mage::helper('typo3connect/authentication');
			$divider = ':-:-:';
			
			$_dRedir =	$auth->getEncrypted( $finalRedirect );
			$_dTime  =	$auth->getEncrypted( time() );
			$_dLogin =	$auth->getEncrypted( $username );
			$_dPass  =	$auth->getEncrypted( $password );
						
			$data = $_dRedir . $divider .
					$_dTime  . $divider .
					$_dLogin . $divider .
					$_dPass  . $divider;
			
			// Code for redirect html post form	
			$outputHtml = '
				<script type="text/javascript">
		 			function submitForm()
		 			{
		 				document.getElementById(\'TYPO3connect\').submit();
		 			}
				</script>
				<body onLoad="submitForm();">
					
				<form action="'.$typo3baseUrl.'" id="TYPO3connect" method="POST">
					<input type="hidden" name="data" id="data" value="'. $data .'" />
					<input type="hidden" name="username" id="username" value="'.$username.'" />
					<input type="hidden" name="password" id="password" value="'.$password.'" />
				</form>
					
	 			<script type="text/javascript">
	 				document.getElementById(\'TYPO3connect\').submit();
	 			</script>
			';
			
			// If no template has to be shown
			if (!Mage::helper('typo3connect')->canShowLoginTemplate()) {
				// Output as plain html	
				$outputHtml = '
					<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
							"http://www.w3.org/TR/html4/loose.dtd">
					<html>
					<head>
						<title>'.Mage::helper('typo3connect')->__('Redirecting ...').'</title>
					</head>
					<body>
						'.$outputHtml.'
					</body>
					</html>
				';
				$this->_response($outputHtml);
			} else {
				$this->loadLayout();
				$this->getLayout()->getBlock('typo3connect_login')
						  		  ->setData('form_html',$outputHtml);
				$this->renderLayout();
			}
		} else {
			$error = $this->__('Wrong email or password.');
			$this->_redirectUrl($this->_createErrorRedirectUrl($typo3baseUrl, $error));
        }
		*/
	}

	
	/**
	 * _createErrorRedirectUrl
	 * Creates the redirect url for 
	 * a given error
	 * 
	 * @param string $baseUrl
	 * @param string $errormessage
	 * @return string
	 */
	protected function _createErrorRedirectUrl($baseUrl, $errormessage)
	{
		$errorStr = '';
		$parsedUrl = parse_url($baseUrl);
		
		if (array_key_exists('query', $parsedUrl))
			$errorStr = '&error=';
		else
			$errorStr = '?error=';
		
		$auth = Mage::helper('typo3connect/authentication');
		return $baseUrl . $errorStr . urlencode( $auth->getEncrypted($errormessage) );
	}
	
	/**
	 * _createSuccessRedirectUrl
	 * Creates the redirect url
	 */
	protected function _createSuccessRedirectUrl($string)
	{
		$auth = Mage::helper('typo3connect/authentication');
		return 'data=' . urlencode( $auth->getEncrypted($string) );
	}
	
	/**
	 * authEncryptedLogin
	 * Authorize an encrypted login and check if the login
	 * exists in system
	 * 
	 * @param RIJNDAEL $encryptedUsername
	 * @param RIJNDAEL $encryptedPassword
	 */
	protected function _authEncryptedLogin($encryptedUsername, $encryptedPassword)
	{
		// Load authentication helper
		$auth = Mage::helper('typo3connect/authentication');
		
		// Initialize posted login data
		return $auth->init($encryptedUsername, $encryptedPassword);		
	}

	/**
	 * _response
	 * Responds a blank output with
	 * given string
	 * 
	 * @param string $string Text to Output
	 * @param string $contentType Type of the response content
	 */
	private function _response($string, $contentType = 'text/html')
	{
		$this->getResponse()
			 ->clearHeaders()
			 ->setHeader('Content-Type', $contentType)
			 ->setBody($string);
		return;
	}

	/**
	 * error
	 * Displays an error directly to the frontend
	 * 
	 * @param string $message
	 */
	private function error($message)
	{
		echo "<pre>\n\n";
		echo "   <strong><u>  " . "TYPO3connect " . $this->__('Error') . "  </u></strong>\n\n\n";
		echo "   <strong>Message:</strong>\n";
		echo "   - " . $message . "\n\n\n";
		echo "   <strong>" . $this->__('Timestamp') . "</strong>\n";
		echo "   - " . time() . "\n";
		echo "</pre>\n";
		
		return;
	}
	
	/**
	 * Get the before auth url
	 * 
	 * @return string
	 */
	protected function _getBeforeAuthUrl()
	{
        $session = $this->_getSession();

        if (!$session->getBeforeAuthUrl() || $session->getBeforeAuthUrl() == Mage::getBaseUrl()) {
            // Set default URL to redirect customer to
            $session->setBeforeAuthUrl(Mage::helper('customer')->getAccountUrl());
            // Redirect customer to the last page visited after logging in
            if ($session->isLoggedIn()) {
                if (!Mage::getStoreConfigFlag('customer/startup/redirect_dashboard')) {
                    $referer = $this->getRequest()->getParam(Mage_Customer_Helper_Data::REFERER_QUERY_PARAM_NAME);
                    if ($referer) {
                        // Rebuild referer URL to handle the case when SID was changed
                        $referer = Mage::getModel('core/url')
                            ->getRebuiltUrl(Mage::helper('core')->urlDecode($referer));
                        if ($this->_isUrlInternal($referer)) {
                            $session->setBeforeAuthUrl($referer);
                        }
                    }
                } else if ($session->getAfterAuthUrl()) {
                    $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
                }
            } else {
                $session->setBeforeAuthUrl(Mage::helper('customer')->getLoginUrl());
            }
        } else if ($session->getBeforeAuthUrl() == Mage::helper('customer')->getLogoutUrl()) {
            $session->setBeforeAuthUrl(Mage::helper('customer')->getDashboardUrl());
        } else {
            if (!$session->getAfterAuthUrl()) {
                $session->setAfterAuthUrl($session->getBeforeAuthUrl());
            }
            if ($session->isLoggedIn()) {
                $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
            }
        }
		return $session->getBeforeAuthUrl(true);
	}
	
	protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }	
	
	/**
	 * _addParameterToUrl
	 * 
	 * @param array $params
	 * @param string $url
	 * @return string
	 */
	private function _addParameterToUrl($params, $url)
	{
		if (is_array($params))
		{
			$parsedUrl = parse_url($url);
			
			if (!array_key_exists('query', $parsedUrl)) {
				$firstParam = true;
			} else {
				$firstParam = false;
			}
				
			foreach ($params as $param=>$value) {
				$paramSign = ($firstParam == true)? '?':'&';
				$url .= $paramSign . $param . '=' . $value;
				$firstParam = false;
			}
			return $url;	
		}
		return false;
	}	
		
}
