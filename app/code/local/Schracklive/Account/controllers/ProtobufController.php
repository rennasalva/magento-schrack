<?php

use com\schrack\queue\protobuf\AccountTransfer\AccountMessage;
use com\schrack\queue\protobuf\ContactTransfer\ContactMessage;

class Schracklive_Account_ProtobufController extends Mage_Core_Controller_Front_Action {

    const DEBUG = false;

 
    function __construct( $request, $response ) {
        parent::__construct($request, $response);
        ini_set('max_execution_time', '18000');
        // ini_set('memory_limit', '-1');
        // ini_set('display_errors',0);
    }

    public function createAction() {
        header('Content-type: text/plain');

        Schracklive_Account_Model_Protoimport_Base::initProtobuf();
        $account = new \com\schrack\queue\protobuf\AccountTransfer\AccountMessage\Account();
        $account->setWwsCustumerId('979001');
        $account->setWwsBranchId(7);
        $account->setMatch('aha');
        $account->setSalutation('Herr');
        $account->setName1('erster');
        $account->setName2('zweiter');
        $account->setName3('dritter');
        $account->setCurrencyCode('EUR');

        $msg = new AccountMessage();
        $msg->setAccount($account);
        
        $codec = new \DrSlump\Protobuf\Codec\Binary();
        $data = $msg->serialize($codec);
        $base = base64_encode($data);
        $fh = fopen('/mnt/data-exchange/account.proto.bin', 'w');
        fwrite($fh, $data);
        fclose($fh);
        die($base);
    }
    
    public function dumpAction() {
        Schracklive_Account_Model_Protoimport_Base::initProtobuf();
        $data = @file_get_contents('php://input');
        $l = strlen($data);
        if ( $l < 1 ) {
            return;
        }
        echo "object with size {$l} got. ========================================== <br>".PHP_EOL;
        $msg = new Message($data);
        $codec = new \DrSlump\Protobuf\Codec\TextFormat();
        $textData = $codec->encode($msg);
        print($textData.PHP_EOL);
        $ctry = $msg->getShop();
        $fileName = '/tmp/s4y2ws.'.$ctry.'.protobuf';
        file_put_contents($fileName.'.bin',$data);
        $data2 = file_get_contents($fileName.'.bin');
        file_put_contents($fileName.'.txt',$textData);
    }

    public function importAccountAction() {
        return $this->_importMessage(Schracklive_Account_Helper_Protobuf::TYPE_ACCOUNT, '/mnt/data-exchange/account.proto.bin');
    }

    public function importContactAction() {
        return $this->_importMessage(Schracklive_Account_Helper_Protobuf::TYPE_CONTACT, '/mnt/data-exchange/contact.proto.bin');
    }

    public function importAddressAction() {
        return $this->_importMessage(Schracklive_Account_Helper_Protobuf::TYPE_ADDRESS, '/mnt/data-exchange/address.proto.bin');
    }

    private function _importMessage($type, $message, $filename = null) {
        set_error_handler("exception_error_handler", E_ALL & ~E_NOTICE & ~E_USER_NOTICE);
        register_shutdown_function("fatal_handler");
        ob_start();

        Mage::register('isSecureArea', true, true);

        try {
            Mage::helper('account/protobuf')->importMessage($type, $message, $filename);
        } catch (Exception $ex) {
            Mage::logException($ex);
            $msg = $ex->getMessage();
            header("Status: 599 " . $msg, true, 599);
            header("HTTP/1.1 599 " . $msg);
            ob_clean();
            echo $msg . PHP_EOL;
            die();
        }
        sleep(1);
        ob_flush();
    }

    function microDateTime () {
        $time =microtime(true);
        $micro_time=sprintf("%06d",($time - floor($time)) * 1000000);
        $date=new DateTime( date('Y-m-d H:i:s.'.$micro_time,$time) );
        return $date->format("Ymd_His.u");
    }
}

function exception_error_handler ( $errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr,0,$errno,$errfile,$errline);
}

function fatal_handler() {
    $error = error_get_last();
    if ( $error !== NULL ) {
        $errno   = $error["type"];
        if ( $errno === E_ERROR ) {
            $errfile = $error["file"];
            $errline = $error["line"];
            $errstr  = $error["message"];
            $msg = "Error# $errno in $errfile at line $errline : $errstr ";
            header("HTTP/1.1 599 " . $msg);
            ob_clean();
            echo  $msg.PHP_EOL;
            die();
        }
    }
}

?>
