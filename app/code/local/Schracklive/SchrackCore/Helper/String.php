<?php

class Schracklive_SchrackCore_Helper_String extends Mage_Core_Helper_String {
	const MAPPING_ONE_CHAR = 1;
	const MAPPING_TWO_CHARS = 2;

	public function encryptMailto($email, $crypt = false) {
		if(!$crypt) {
			$email=str_replace('@',' [at] ',$email);
			$email=str_replace('.',' [dot] ',$email);
			return $email;
		}
		$encryptedEmail='';
		$email='mailto:'.$email;
		for( $i=0; $i < strlen($email); ++$i) {
			$n = ord($email[$i]);
			if( $n >= 8364 ) {
				$n = 128;
			}
			$encryptedEmail .= chr($n+1);
		}
		return $encryptedEmail;
	}

	public function utf8ToAscii($str, $mapping = self::MAPPING_TWO_CHARS) {
		if ($mapping == self::MAPPING_TWO_CHARS) {
			$mappingFile = Mage::getConfig()->getModuleDir('etc', 'Schracklive_SchrackCore').DS.'utf8_to_ascii.tbl';
		} else {
			$mappingFile = Mage::getConfig()->getModuleDir('etc', 'Schracklive_SchrackCore').DS.'utf8_to_ascii_single.tbl';
		}
		$mapping = array();
		if (@is_file($mappingFile)) {
			$mapping = unserialize(file_get_contents($mappingFile));
		}
		$out = '';
		for ($i = 0; isset($str[$i]); $i++) {
			$c = ord($str[$i]);
			if (!($c & 0x80)) { // single-byte (0xxxxxx)
				$mbc = $str[$i];
			}
			elseif (($c & 0xC0) == 0xC0) { // multi-byte starting byte (11xxxxxx)
				for ($bc = 0; $c & 0x80; $c = $c << 1) {
					$bc++;
				} // calculate number of bytes
				$mbc = substr($str, $i, $bc);
				$i += $bc - 1;
			}

			if (isset($mapping[$mbc])) {
				$out .= $mapping[$mbc];
			} else {
				$out .= $mbc;
			}
		}		
		return $out;
	}
	
	public function shortify($str, $maxLength) {
		$maxLength = abs(intval($maxLength));
		
		if (strlen($str) > $maxLength) {
			$str = substr($str, 0, $maxLength);
			$trunkAt = strrpos($str, ' ');
			if ($trunkAt) {
				$str = substr($str, 0, $trunkAt);
			}
			$str .= '...';
		}
		
		return $str;
	}

	public function numberFormat($number, $decimals = 0, $dec_point = '.', $thousands_sep = ',') {
		/* @todo: using info from localeconv() does not work right now
		*/

		return is_null($number) ? '' : number_format($number, $decimals, ',', '.');
	}

    public function safeDivision($dividend, $divisor, $default = 1) {
        $dividend = doubleval($dividend);
        $divisor = doubleval($divisor);
        if ( !$divisor || $divisor === 0 ) {
            $divisor = doubleval($default);
        }
        return $dividend / $divisor;
    }


}
