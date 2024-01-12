<?php

ini_set('memory_limit', '-1');

require_once dirname(dirname(__FILE__)).'/abstract.php';

class ShowHelpException extends Exception {
    public function __construct () {
        parent::__construct();
    }
}

abstract class Schracklive_Shell extends Mage_Shell_Abstract {

	public function __construct () {
		parent::__construct();
	}

	protected function _applyPhpVariables() {
		// do NOT apply .htaccess configurations
	}

	protected function _getParametrizedArg($name) {
		$argument = $this->getArg($name);
		if ($argument && gettype($argument) == 'string') {
			return $argument;
		}
		return false;
	}

	protected function _getArguments() {
		return $this->_args;
	}

	protected function _getFrontendUrl ( $path = '', $params = array(), $paramsAsQuery = true ) {
        $helper = Mage::helper('schrack/backend');
        return $helper->getFrontendUrl($path,$params,$paramsAsQuery);
    }

    protected function getCurrentTimeInSeconds () {
        $helper = Mage::helper('schrack/time');
        return $helper->getCurrentTimeInSeconds();
    }

    protected function getConfigTimeInSeconds ( $path ) {
        $helper = Mage::helper('schrack/time');
        return $helper->getConfigTimeInSeconds ($path);
    }

    protected function getTimeInSecondsHMS ( $h, $m, $s ) {
        $helper = Mage::helper('schrack/time');
        return $helper->getTimeInSecondsHMS($h,$m,$s);
    }

    protected static function custom_print ( $msg, $ts = true, $eol = true ) {
        if ( $ts )
            $msg = "[".date("y.m.d H:i:s")."] " . $msg;
        if ( $eol )
            $msg .= PHP_EOL;
        echo $msg;
    }

    protected function ungz ( $srcFilePath ) {
        $bufSize = 4096;
        if ( ! is_dir('/tmp') ) {
            mkdir('/tmp');
        }
        $destFilePath = '/tmp/'.basename(str_replace('.gz','',$srcFilePath));

        $srcFile = gzopen($srcFilePath,'rb');
        $outFile = fopen($destFilePath,'wb');

        while( ! gzeof($srcFile) ) {
            fwrite($outFile, gzread($srcFile, $bufSize));
        }

        fclose($outFile);
        gzclose($srcFile);

        return $destFilePath;
    }
}
