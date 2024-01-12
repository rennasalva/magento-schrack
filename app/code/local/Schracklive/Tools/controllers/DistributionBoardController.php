<?php

require_once "CommonToolsController.php";

class Schracklive_Tools_DistributionBoardController extends Schracklive_Tools_CommonToolsController {
    public function indexAction() {

        // fetching url
        $distributionUrl = Mage::getStoreConfig('schrack/customertools/distribution_board_configurator_url') . "?";
        $urlBase = Mage::getStoreConfig('schrack/typo3/typo3url');
        $url = $urlBase . 'shop/checkout/cart';
        $isLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();

        if($isLoggedIn) {
            // getting user data
            $customerHelper = Mage::helper('schrackcustomer');
            $customer = $customerHelper->getCustomer();
            $user = $customer->getAccount();

            $userFirstname = $customer->getFirstname();
            $userLastname = $customer->getLastname();
            $userEmail = $customer->getEmail();

            $userCompanyName = $user['name1'];
            $userCompanyNumber = $user['wws_customer_id'];

            $userAdvisor = $user['advisor_principal_name'];
            $advisor = explode('/', $userAdvisor);

            $userData = array(
                            'customerfirstname' => rawurlencode($userFirstname),
                            'customerlastname'  => rawurlencode($userLastname),
                            'customeremail'     => rawurlencode($userEmail),
                            'companyname'       => rawurlencode($userCompanyName),
                            'companynumber'     => rawurlencode($userCompanyNumber),
                            'manageremail'      => rawurlencode($advisor[0]),
                            'url'               => rawurlencode($url),
                        );

            // building url parameters
            foreach ($userData as $key => $value) {
                $distributionUrl .= "$key=$value&";
            }
        } else {
            $distributionUrl .= 'url=' . rawurlencode($url);
        }
        Mage::register("distributionurl", $distributionUrl);

        $this->loadLayout();
        $this->renderLayout();
    }
}
