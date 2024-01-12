<?php
class Schracklive_S4s_ApiController extends Mage_Core_Controller_Front_Action {

    public function indexAction () {
        echo "Welcome to S4S API (this page is intended just for simple tests)";
    }

    public function logtestAction () {
        $this->commonRequestHandler('doLogtest');
    }

    public function loginAction () {
        $this->commonRequestHandler('doLogin');
    }

    public function registeruserAction () {
        $this->commonRequestHandler('doRegister');
    }

    public function updateuserprofileAction () {
        $this->commonRequestHandler('doUpdate');
    }

    public function changepasswordAction () {
        $this->commonRequestHandler('doChangePassword');
    }

    public function resetpasswordAction () {
        $this->commonRequestHandler('doResetPassword');
    }

    public function resendconfirmationmailAction () {
        $this->commonRequestHandler('doResendConfirmationMail');
    }

    public function confirmtermsofuseAction () {
        $this->commonRequestHandler('doConfirmTermsOfUse');
    }

    public function requestcountrychangeAction () {
        $this->commonRequestHandler('doRequestCountryChange');
    }

    private function commonRequestHandler ( $currentMethod ) {
        if ( ! $this->getRequest()->isPost() ) {
            $this->_redirect('*/*');
            return;
        }
        // hotfix 2020-10-15: Nobody knows why, but this is necessary with php 7 ??????????
        $this->getResponse()
			->clearHeaders()
			->setHeader('Content-Type','application/json');
        // end hotfix
        $jsonRequest = $this->getRequest()->getRawBody();
        $helper = Mage::helper('s4s');
        $helper->commonRequestHandler($currentMethod,$jsonRequest);
    }
}
?>