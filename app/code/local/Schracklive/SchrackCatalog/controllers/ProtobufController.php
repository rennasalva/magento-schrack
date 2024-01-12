<?php

use com\schrack\queue\protobuf\Message;

class Schracklive_SchrackCatalog_ProtobufController extends Mage_Core_Controller_Front_Action {
 
    function __construct( $request, $response ) {
        parent::__construct($request, $response);
        ini_set('max_execution_time', '18000');
        ini_set('memory_limit', '-1');
        ini_set('display_errors',0);
    }
    
    public function dumpAction() {
        Schracklive_SchrackCatalog_Model_Protoimport_Base::initProtobuf();
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
        $fileName = '/tmp/sts2ws.'.$ctry.'.protobuf';
        file_put_contents($fileName.'.bin',$data);
        $data2 = file_get_contents($fileName.'.bin');
        file_put_contents($fileName.'.txt',$textData);
    }

    public function importAction () {
        set_error_handler("exception_error_handler",E_ALL & ~E_NOTICE & ~E_USER_NOTICE);
        register_shutdown_function("fatal_handler");        
        ob_start();
        try {
            Mage::register('isSecureArea', true, true);          
            /* @var $importer Schracklive_SchrackCatalog_Model_Protoimport */
    		$importer = Mage::getModel('schrackcatalog/protoimport');
            $data = @file_get_contents('php://input');
            $importer->dump2file($data);
            $importer->run($data);
            sleep(1);
            ob_flush();
        }
        catch ( Exception $ex ) {
            Mage::logException($ex);
            $msg = $ex->getMessage();
            header("Status: 599 " . $msg,true,599);
            header("HTTP/1.1 599 " . $msg);
            ob_clean();
            echo $msg . PHP_EOL;
            die();
        }
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
