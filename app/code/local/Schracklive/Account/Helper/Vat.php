<?php

class Schracklive_Account_Helper_Vat extends Mage_Core_Helper_Abstract {

	const VAT_OK = 0;
	const VAT_BROKEN = 1;
	const VAT_UNCHECKED = 2;
	const VAT_INVALID = 3;
	const VAT_UNDEFINED = 9;

    const VAT_CHECKTYPE_VIES = 1;
    const VAT_CHECKTYPE_STRUCTURE = 2;
    const VAT_CHECKTYPE_CHECKSUM = 3;

    private $_client = null;
    
	protected $_countryConfig = array(
	    "AD" => array(
            "name" => "andorra",
            "pattern" => array("/^[0-9]{3,20}$/"),
            "allowed_in" => array('CO'),
	    ),
		"AT" => array(
			"name" => "austria",
			"pattern" => array("/^U[0-9]{8}$/"),
			"multipliers" => array(1, 2, 1, 2, 1, 2, 1),
            "allowed_in" => array('AT', 'LI'),
		),
        "BA" => array(
            "name" => "andorra",
            "pattern" => array("/^[0-9]{3,20}$/"),
            "allowed_in" => array('BA'),
        ),
		"BE" => array(
			"name" => "belgium",
			"pattern" => array("/^0?[0-9]{9}$/"),
            "allowed_in" => array('BE'),
		),
        "BG" => array(
            "name" => "andorra",
            "pattern" => array("/^[0-9]{3,20}$/"),
            "allowed_in" => array('BG'),
        ),
		"CY" => array(
			"name" => "cyprus",
			"pattern" => array("/^[0-9]{8}[A-Z]$/"),
            "allowed_in" => array('CO'),
		),
		"CZ" => array(
			"name" => "czech republic",
			"pattern" => array("/^[0-9]{8,10}$/"),
			"multipliers" => array(8, 7, 6, 5, 4, 3, 2),
            "allowed_in" => array('CZ'),
		),
		"DE" => array(
			"name" => "germany",
			"pattern" => array("/^[0-9]{9}$/"),
            "allowed_in" => array('DE'),
		),
		"DK" => array(
			"name" => "denmark",
			"pattern" => array("/^[0-9]{8}$/"),
			"multipliers" => array(2, 7, 6, 5, 4, 3, 2, 1),
            "allowed_in" => array('CO'),
		),
		"EE" => array(
			"name" => "estonia",
			"pattern" => array("/^[0-9]{9}$/"),
			"multipliers" => array(3, 7, 1, 3, 7, 1, 3, 7),
            "allowed_in" => array('CO'),
		),
		"EL" => array(
			"name" => "greece",
			"pattern" => array("/^[0-9]{9}$/"),
			"multipliers" => array(256, 128, 64, 32, 16, 8, 4, 2),
            "allowed_in" => array('CO'),
		),
		"ES" => array(
			"name" => "spain",
			"pattern" => array("/^[0-9A-Z][0-9]{7}[0-9A-Z]$/"),
			"multipliers" => array(2, 1, 2, 1, 2, 1, 2),
            "allowed_in" => array('CO'),
		),
		"FI" => array(
			"name" => "finland",
			"pattern" => array("/^[0-9]{8}$/"),
			"multipliers" => array(7, 9, 10, 5, 8, 4, 2),
            "allowed_in" => array('CO'),
		),
		"FR" => array(
			"name" => "france",
			"pattern" => array("/^[0-9]{11}$/"),
            "allowed_in" => array('CO'),
		),
		"GB" => array(
			"name" => "united kingdom",
			"pattern" => array(
				'/^\d{9}$/',
				'/^\d{12}$/',
				'/^GD\d{3}$/',
				'/^HA\d{3}$/'
			),
            "allowed_in" => array('CO','AT'),
		),
        "HR" => array(
            "name" => "andorra",
            "pattern" => array("/^[0-9]{3,20}$/"),
            "allowed_in" => array('HR'),
        ),
		"HU" => array(
			"name" => "hungary",
			"pattern" => array("/^[0-9]{8}$/"),
			"multipliers" => array(9, 7, 3, 1, 9, 7, 3),
            "allowed_in" => array('HU'),
		),
		"IE" => array(
			"name" => "ireland",
			"pattern" => array("/^[0-9][A-Z0-9\+\*][0-9]{5}[A-Z]$/"),
			"multipliers" => array(8, 7, 6, 5, 4, 3, 2),
            "allowed_in" => array('CO'),
		),
		"IT" => array(
			"name" => "italy",
			"pattern" => array("/[0-9]{11}$/"),
			"multipliers" => array(1, 2, 1, 2, 1, 2, 1, 2, 1, 2),
            "allowed_in" => array('CO'),
		),
        "LI" => array(
            "name" => "luxembourg",
            "pattern" => array("/^[0-9]{3,20}$/"),
            "allowed_in" => array('CO'),
        ),
		"LT" => array(
			"name" => "lithuania",
			"pattern" => array("/^([0-9]{9}|[0-9]{12})$/"),
			"multipliers" => array(3, 4, 5, 6, 7, 8, 9, 1),
            "allowed_in" => array('CO'),
		),
		"LU" => array(
			"name" => "luxembourg",
			"pattern" => array("/^[0-9]{8}$/"),
            "allowed_in" => array('CO'),
		),
		"LV" => array(
			"name" => "latvia",
			"pattern" => array("/^[0-9]{11}$/"),
			"multipliers" => array(9, 1, 4, 8, 3, 10, 2, 5, 7, 6),
            "allowed_in" => array('CO'),
		),
        "MC" => array(
            "name" => "monaco",
            "pattern" => array("/^[0-9]{3,20}$/"),
            "allowed_in" => array('CO'),
        ),
		"MT" => array(
			"name" => "malta",
			"pattern" => array("/^[0-9]{8}$/"),
            "allowed_in" => array('CO'),
		),
		"NL" => array(
			"name" => "netherlands",
			"pattern" => array("/^[0-9]{9}B[0-9]{2}$/"),
            "allowed_in" => array('CO','NL'),
		),
        "RO" => array(
            "name" => "romania",
            "pattern" => array("/^[0-9]{2,10}$/"),
            "allowed_in" => array('RO'),
        ),
		"PL" => array(
			"name" => "poland",
			"pattern" => array("/^[0-9]{10}$/"),
			"multipliers" => array(6, 5, 7, 2, 3, 4, 5, 6, 7, -1),
            "allowed_in" => array('PL'),
		),
		"PT" => array(
			"name" => "portugal",
			"pattern" => array("/^[0-9]{9}$/"),
            "allowed_in" => array('CO'),
		),
		"SE" => array(
			"name" => "sweden",
			"pattern" => array("/^[0-9]{12}$/"),
            "allowed_in" => array('CO'),
		),
		"SI" => array(
			"name" => "slovenia",
			"pattern" => array("/^[0-9]{8}$/"),
			"multipliers" => array(8, 7, 6, 5, 4, 3, 2),
            "allowed_in" => array('SI'),
		),
		"SK" => array(
			"name" => "slovakia",
			"pattern" => array(
				"/^[1-9]{1}[0-9]{1}[2,3,4,7,8,9]{1}[0-9]{7}$/",
				"/^[0-9]{10}$/",
			),
			"multipliers" => array(8, 7, 6, 5, 4, 3, 2),
            "allowed_in" => array('SK'),
		),
        "SM" => array(
            "name" => "san marino",
            "pattern" => array("/^[0-9]{3,20}$/"),
            "allowed_in" => array('CO'),
        ),
        "VA" => array(
            "name" => "vatican city",
            "pattern" => array("/^[0-9]{3,20}$/"),
            "allowed_in" => array('CO'),
        ),
	);
	protected $_resultCodes = array(
		self::VAT_OK => 'VAT identifictation number is ok.',
		self::VAT_BROKEN => 'VAT identifictation number format is invalid.',
		self::VAT_UNCHECKED => 'VAT identifictation number could not be checked.',
		self::VAT_INVALID => 'VAT identifictation number is invalid.',
		self::VAT_UNDEFINED => 'Internal error!',
	);
	protected $_lastResult;
	protected $_lastCountryCode;
    protected $_lastCheckType;

	protected function _setLastResult($num) {
		if (array_key_exists($num, $this->_resultCodes)) {
			$this->_lastResult = $num;
		} else {
			$this->_lastResult = self::VAT_UNDEFINED;
		}
	}

    public function getLastCheckType() {
        return $this->_lastCheckType;
    }

	public function getLastResult() {
		return $this->_resultCodes[$this->_lastResult];
	}

	public function getLastCountryCode() {
		return $this->_lastCountryCode;
	}

	public function checkLastCountryCode() {
        $candidateCountry = strtoupper($this->getLastCountryCode());
        if (!in_array(strtoupper(Mage::getStoreConfig('schrack/general/country')), array('COM', 'DE', 'RU', 'SA'))) {
            $tmp = strtoupper((string) Mage::getStoreConfig('schrack/general/country'));
        } else {
            $tmp = strtoupper((string) Mage::getStoreConfig('general/country/allow'));
        }
        $allowedCountries = explode(',', $tmp); 
        foreach ( $allowedCountries as $allowedCountry ) {
            if ( $candidateCountry === $allowedCountry ) {
                return true;
            }
        }
        return false;
	}

	public function getCountryName($vatno) {
		$code = $this->getCountryCode($vatno);
		if (array_key_exists($code, $this->_countryConfig)) {
			return $this->_countryConfig[$code]['name'];
		}
		return false;
	}

	public function getCountryCode($vatno) {
		return strtoupper( substr( str_replace(" ", "", $vatno), 0, 2 ) );
	}

    /**
     * @param string $vatno
     * @return bool
     */
	protected function _checkStructure($vatno) {
        $this->_lastCheckType = self::VAT_CHECKTYPE_STRUCTURE;
        Mage::log('this->_lastCheckType = ' . $this->_lastCheckType, null, 'vies.log');
        $countryCode = $this->getCountryCode($vatno);
        Mage::log('vatno = ' . $vatno, null, 'vies.log');
        // Only check format if we got a pattern to check against
        Mage::log('this->_countryConfig[countryCode]' . serialize($this->_countryConfig[$countryCode]), null, 'vies.log');
        Mage::log('this->_countryConfig[countryCode][pattern]' . serialize($this->_countryConfig[$countryCode]['pattern']), null, 'vies.log');
        if (isset($this->_countryConfig[$countryCode])
                && is_array($this->_countryConfig[$countryCode])
                && isset($this->_countryConfig[$countryCode]['pattern'])) {
            if (preg_match("/^([A-Z]{2})/", $vatno, $regs)) {
                $vatno = substr($vatno, 2);
                foreach ($this->_countryConfig[$regs[1]]['pattern'] as $pattern) {
                    Mage::log('GEO Patterns = ' . $pattern, null, 'vies.log');
                    if (preg_match($pattern, $vatno)) {
                        return true;
                    }
                }
            }
            Mage::log('_checkStructure Failure CASE 1', null, 'vies.log');
            return false;
        }
        // Return default false if we can't check the format
        Mage::log('_checkStructure Failure CASE 2', null, 'vies.log');
        return false;
	}

    /**
     * @param string $vatno
     * @return bool
     */
	public function checkVat($vatno) {
		$this->_lastCountryCode = $this->getCountryCode($vatno);
		$vatNoOrg = $vatno;
		$vatno = str_replace(' ', '', strtoupper($vatno));

        $currentCountryCode = strtoupper(Mage::getStoreConfig('schrack/general/country'));
        if ($currentCountryCode == 'COM') $currentCountryCode = 'CO';

		// Check Country first
		if ($this->_lastCountryCode && isset($this->_countryConfig[$this->_lastCountryCode]['allowed_in'])) {
            $allowedCountryList = $this->_countryConfig[$this->_lastCountryCode]['allowed_in'];
        } else {
            Mage::log('VAT-Org : ' . $vatNoOrg . ' --- Country-Code-Check 1 -> this->_lastCountryCode:' . $this->_lastCountryCode . ' SHOP-Country: ' . $currentCountryCode . ' - Received VAT-Number' . $vatno, null, 'vies.log');
        }

		if (is_array($allowedCountryList)) {
            if (!in_array($currentCountryCode, $allowedCountryList)) {
                Mage::log('VAT-Org : ' . $vatNoOrg . ' --- Country-Code-Check 2 -> VAT:' . $vatno . ' SHOP-Country: ' . $currentCountryCode . ' - Received VAT-Country : ' . $this->_lastCountryCode, null, 'vies.log');
                return false;
            }
        } else {
            Mage::log('VAT-Org : ' . $vatNoOrg . ' --- Country-Code-Check 3 -> VAT:' . $vatno . ' -- allowedCountryList: ' . serialize($allowedCountryList) . ' --- SHOP-Country: ' . $currentCountryCode, null, 'vies.log');
        }

		if ($this->_checkStructure($vatno)) {
			if (Mage::getStoreConfig('schrack/vatcheck/online')) {
				if ($this->_checkVatVies($vatno)) {
                    return true;
                } else {
                    if ($this->_lastResult === self::VAT_UNCHECKED) { // gracefully degrade to checksum test if server does not respond
                        return $this->_checkVatChecksum($vatno);
                    }
                }
			} else {
				return $this->_checkVatChecksum($vatno);
			}
		} else {
            Mage::log('VAT-Org : ' . $vatNoOrg . ' --- SOFT-Check - Last-Result: VAT STRUCTURE INVALID (' . $vatno . ')', null, 'vies.log');
		}
        Mage::log('VAT-Org : ' . $vatNoOrg . ' --- SOFT-Check - Last-Result: VAT_BROKEN (' . $vatno . ')', null, 'vies.log');
		$this->_setLastResult(self::VAT_BROKEN);
		return false;
	}

    public function vatExists($vatno) {
        return ( Mage::getModel('account/account')->countByVatIdentificationNumber($vatno) > 0 );
    }

    /**
     * @param $vatno
     * @return bool
     * @see https://www.bmf.gv.at/EGovernment/FINANZOnline/InformationenfrSoft_3165/ZusammenfassendeMel_3204/Konstruktionsregeln_Stand_Oktober_2008.pdf
     */
	protected function _checkVatChecksum($vatno) {
        $this->_lastCheckType = self::VAT_CHECKTYPE_CHECKSUM;

		$countryCode = $this->getCountryCode($vatno);
		$funcName = '_check' . $countryCode;
		if (method_exists($this, $funcName)) {
			if ($this->{$funcName}($vatno, $countryCode)) {
                Mage::log('SOFT-Check (funcName -> ' . $funcName . ' / $countryCode = ' . $countryCode . ') (VAT = ' . $vatno . ') - VALID: OK', null, 'vies.log');
				$this->_setLastResult(self::VAT_OK);
				return true;
			} else {
                Mage::log('SOFT-Check ((funcName -> ' . $funcName . ' / $countryCode = ' . $countryCode . ') (VAT = ' . $vatno . ') - VALID: NOT OK', null, 'vies.log');
				$this->_setLastResult(self::VAT_INVALID);
				return false;
			}
		} else {
            // Simple check on VAT --> no checksum-process for some countries implemented yet:
            if (is_array($this->_countryConfig[$countryCode]) && isset($this->_countryConfig[$countryCode]['pattern'])) {
                $patternArray = $this->_countryConfig[$countryCode]['pattern'];
                if ( ! is_array($patternArray) ) {
                    $patternArray = array($patternArray);
                }
                $ok = false;
                $vatnoDigits = substr($vatno,2);
                foreach ( $patternArray as $pattern ) {
                    if ( preg_match($pattern,$vatnoDigits) ) {
                        $ok = true;
                        break;
                    }
                }
                if ( $ok ) {
                    Mage::log('SIIMPLE SOFT-Check SUCCESS (countryConfig[countryCode][pattern] = ' . $pattern . ') (VAT = ' . $vatno . ') - VALID: OK', null, 'vies.log');
                    $this->_setLastResult(self::VAT_OK);
                    return true;
                } else {
                    Mage::log('SIIMPLE SOFT-Check FAILED (countryConfig[countryCode][pattern] = ' . implode(',',$patternArray) . ') (VAT = ' . $vatno . ') - VALID: NOT OK', null, 'vies.log');
                    $this->_setLastResult(self::VAT_INVALID);
                    return false;
                }
            }

			// Always return false if we have no way to check the format
            Mage::log('SOFT-Check FALLBACK-FAIL (VAT = ' . $vatno . ') - VALID: NOT OK (FALLBACK)', null, 'vies.log');
			$this->_setLastResult(self::VAT_INVALID);
			return false;
		}
	}

	/**
	 *  Checks if austrian vat number is valid
	 * $vatno : VAT number
	 * $countryISO : ISO2 country code
	 */
	protected function _checkAT($vatno, $countryISO) {
		$multipliers = ($this->_countryConfig[$countryISO]['multipliers']);
		$vatno = substr($vatno, 3);

		// Extract the next digit and multiply by the appropriate multiplier.
        $total = 0;
		for ($i = 0; $i < 7; $i++) {
			$tmp = (int)substr($vatno, $i, 1) * $multipliers[$i];

			if ($tmp > 9) {
				$total = $total + floor($tmp / 10) + $tmp % 10;
			} else {
				$total = $total + $tmp;
			}
		}

		$checkdigit = 10 - ($total + 4) % 10;

		if ($total == 10) {
			$checkdigit = 0;
		}

		if ($checkdigit == substr($vatno, 7, 8)) {
            Mage::log('SOFT-Check SUCCESS -> VALID: ' . 'countryCode -> ' . $countryISO . ' || vatNumber -> ' . $vatno, null, 'vies.log');
			return true;
		} else {
            Mage::log('SOFT-Check FAILED -> INVALID: ' . 'countryCode -> ' . $countryISO . ' || vatNumber -> ' . $vatno, null, 'vies.log');
			return false;
		}
	}

	protected function _checkBE($vatno, $countryISO) {
		//BE0476905052
		$vatno = substr($vatno, 2);

		// First character of 10 digit numbers should be 0
		if (strlen($vatno) == 10 && substr($vatno, 0, 1) != 0) {
			return false;
		}

		// Nine digit numbers have a 0 inserted at the front.
		if (strlen($vatno) == 9) {
			$vatno = "0".$vatno;
		}

		// Modulus 97 check on last nine digits
		if (97 - substr($vatno, 0, 8) % 97 == substr($vatno, 8, 10)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkBA($vatno, $countryISO) {
		// Checks the check digits of a Bulgarian VAT number.

		if (strlen($vatno) == 9 || strlen($vatno) == 10) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkCY($vatno, $countryISO) {
		// Checks the check digits of a Cypriot VAT number.
		$vatno = substr($vatno, 2);

		// Extract the next digit and multiply by the counter.
		$total = 0;
		for ($i = 0; $i < 8; $i++) {
			$temp = (int)substr($vatno, $i, 1);
			if ($i % 2 == 0) {
				switch ($temp) {
					case 0:
						$temp = 1;
						break;
					case 1:
						$temp = 0;
						break;
					case 2:
						$temp = 5;
						break;
					case 3:
						$temp = 7;
						break;
					case 4:
						$temp = 9;
						break;
					default:
						$temp = $temp * 2 + 3;
				}
			}
			$total = $total + $temp;
		}

		// Establish check digit using modulus 26, and translate to char. equivalent.
		$total = $total % 26;
		$total = chr($total + 65);

		// Check to see if the check digit given is correct
		if ($total == substr($vatno, 8, 1)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkCZ($vatno, $countryISO) {
		$vatno = substr($vatno, 2);
		// Checks the check digits of a Czech Republic VAT number.

		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		// Only do check digit validation for standard VAT numbers
		if (strlen($vatno) != 8) return true;

		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 7; $i++) {
			$total = $total + (int)substr($vatno, $i, 1) * $multipliers[$i];
		}

		// Establish check digit.
		$total = 11 - ($total % 11);
		if ($total == 10) {
			$total = 0;
		}
		if ($total == 11) {
			$total = 1;
		}

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 7, 8)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkDE($vatno, $countryISO) {
		$vatno = substr($vatno, 2);
		// Checks the check digits of a German VAT number.

		$product = 10;
		$sum = 0;
		$checkdigit = 0;
		for ($i = 0; $i < 8; $i++) {

			// Extract the next digit and implement perculiar algorithm!.
			$sum = ((int)substr($vatno, $i, 1) + $product) % 10;
			if ($sum == 0) {
				$sum = 10;
			}
			$product = (2 * $sum) % 11;
		}

		// Establish check digit.
		if (11 - $product == 10) {
			$checkdigit = 0;
		} else {
			$checkdigit = 11 - $product;
		}

		// Compare it with the last two characters of the VAT number. If the same,
		// then it is a valid check digit.
		if ($checkdigit == substr($vatno, 8, 9)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkDK($vatno, $countryISO) {
		$vatno = substr($vatno, 2);

		// Checks the check digits of a Danish VAT number.

		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 8; $i++) {
			$total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i]);
		}

		// Establish check digit.
		$total = $total % 11;

		// The remainder should be 0 for it to be valid..
		if ($total == 0) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkEE($vatno, $countryISO) {
		$vatno = substr($vatno, 2);
		// Checks the check digits of an Estonian VAT number.

		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 8; $i++) {
			$total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i]);
		}

		// Establish check digits using modulus 10.
		$total = 10 - $total % 10;
		if ($total == 10) {
			$total = 0;
		}

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 8, 9)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkEL($vatno, $countryISO) {
		$vatno = substr($vatno, 2);
		// Checks the check digits of a Greek VAT number.

		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		//eight character numbers should be prefixed with an 0.
		if (strlen($vatno) == 8) {
			$vatno = "0".$vatno;
		}

		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 8; $i++) {
			$total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i]);
		}

		// Establish check digit.
		$total = $total % 11;
		if ($total > 9) {
			$total = 0;
		}

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 8, 9)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkES($vatno, $countryISO) {
		$vatno = substr($vatno, 2);
		// Checks the check digits of a Spanish VAT number.

		$total = 0;
		$temp = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		$regex = array();
		$regex[] = "/^[A-H]\d{8}$/";
		$regex[] = "/^[N|P|Q|S]\d{7}[A-Z]$/";
		$regex[] = "/^[0-9]{8}[A-Z]$/";
		$i = 0;

		// With profit companies
		if (preg_match($regex[0], $vatno)) {
			// Extract the next digit and multiply by the counter.
			for ($i = 0; $i < 7; $i++) {
				//$temp = Number($vatno.charAt(i+1)) * multipliers[i];
				$temp = ((int)substr($vatno, $i + 1, 1) * $multipliers[$i]);
				if ($temp > 9) {
					$total = $total + floor($temp / 10) + $temp % 10;
				} else {
					$total = $total + $temp;
				}
			}

			// Now calculate the check digit itself.
			$total = 10 - $total % 10;
			if ($total == 10) {
				$total = 0;
			}

			// Compare it with the last character of the VAT number. If it is the same,
			// then it's a valid check digit.
			if ($total == substr($vatno, 8, 9)) {
				return true;
			} else {
				return false;
			}
		} elseif (preg_match($regex[1], $vatno)) {  // Non-profit companies
			// Extract the next digit and multiply by the counter.
			for ($i = 0; $i < 7; $i++) {
				$temp = ((int)substr($vatno, $i + 1, 1) * $multipliers[$i]);
				if ($temp > 9) {
					$total = $total + floor($temp / 10) + $temp % 10;
				} else {
					$total = $total + $temp;
				}
			}

			// Now calculate the check digit itself.
			$total = 10 - $total % 10;
			$total = chr($total + 64);

			// Compare it with the last character of the VAT number. If it is the same,
			// then it's a valid check digit.
			if ($total == substr($vatno, 8, 9)) {
				return true;
			} else {
				return false;
			}
		} elseif (preg_match($regex[2], $vatno)) { // Personal number (NIF) 
			//return substr($vatno,8,1) == 'TRWAGMYFPDXBNJZSQVHLCKE'.chr(substr($vatno,0, 8) % 23);
			//return substr($vatno,8,1) == 'TRWAGMYFPDXBNJZSQVHLCKE'.substr($vatno,substr($vatno,0, 8) % 23,1);
			//return vatnumber.charAt(8) == 'TRWAGMYFPDXBNJZSQVHLCKE'.charAt(Number(vatnumber.substring(0, 8)) % 23);
			print substr($vatno, 8, 1);
			print "---".substr('TRWAGMYFPDXBNJZSQVHLCKE', substr($vatno, (substr($vatno, 0, 8) % 23), 1), 1);
			return substr($vatno, 8, 1) == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr($vatno, (substr($vatno, 0, 8) % 23), 1), 1);
		} else {
			return true;
		}
	}

	/* not tested - missig valid number */

	protected function _checkFI($vatno, $countryISO) {
		$vatno = substr($vatno, 2);
		// Checks the check digits of a Finnish VAT number.

		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 7; $i++) {
			$total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i]);
		}

		// Establish check digit.
		$total = 11 - $total % 11;
		if ($total > 9) {
			$total = 0;
		};

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 7, 8)) {
			return true;
		} else {
			return false;
		}
	}

	/* number not valid or code wrong */

	protected function _checkFR($vatno, $countryISO) {
		$vatno = substr($vatno, 2);

		// Checks the check digits of a French VAT number.
		//if (!(/^\d{11}$/).test(vatnumber)) return true;

		if (!preg_match("/^\d{11}$/", $vatno)) {
			return true;
		}

		// Extract the last nine digits as an integer.
		$total = substr($vatno, 2);

		// Establish check digit.
		$total = (($total * 100) + 12) % 97;

		print 'xxx'.$total.'vvv';

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 0, 2)) {
			return true;
		} else {
			return false;
		}
	}

    protected function _checkHU($vatno, $countryISO) {
		/*
		  lt. P2N - WT -> check not possible
		  http://tracker.plan2.net/view.php?id=7707#c35163
		 */
		return true;

		$vatno = substr($vatno, 2);

		// Checks the check digits of a Hungarian VAT number.

		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 7; $i++) {
			$total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i]);
		}

		// Establish check digit.
		$total = 10 - $total % 10;
		if ($total == 10) {
			$total = 0;
		}

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 7, 8)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkIE($vatno, $countryISO) {
		$vatno = substr($vatno, 2);

		// Checks the check digits of an Irish VAT number.

		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];


		// If the code is in the old format, we need to convert it to the new.
		if (preg_match("/^\d[A-Z\*\+]/", $vatno)) {
			$vatno = "0".substr($vatno, 2, 7).substr($vatno, 0, 1).substr($vatno, 7, 8);
		}
		/*
		  from js
		  if (/^\d[A-Z\*\+]/.test(vatnumber)) {
		  vatnumber = "0" + vatnumber.substring(2,7) + vatnumber.substring(0,1) + vatnumber.substring(7,8);
		  } */

		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 7; $i++) {
			$total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i]);
		}

		// Establish check digit using modulus 23, and translate to char. equivalent.
		$total = $total % 23;
		if ($total == 0) {
			$total = "W";
		} else {
			$total = chr($total + 64);
		}

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 7, 8)) {
			return true;
		} else {
			return false;
		}
	}

	/* seems to be wrong code */

	protected function _checkIT($vatno, $countryISO) {
		$vatno = substr($vatno, 2);

		// Checks the check digits of an Italian VAT number.

		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		// The last three digits are the issuing office, and cannot exceed more 201
		$temp = substr($vatno, 0, 7);
		if ($temp == 0) {
			return false;
		}
		$temp = substr($vatno, 7, 10);
		if (($temp < 1) || ($temp > 201)) {
			print 'err';
			return false;
		}

		// Extract the next digit and multiply by the appropriate
		for ($i = 0; $i < 10; $i++) {
			$temp = ((int)substr($vatno, $i, 1) * $multipliers[$i]);
			if ($temp > 9) {
				$total = $total + floor($temp / 10) + $temp % 10;
			} else {
				$total = $total + $temp;
			}
		}

		// Establish check digit.
		$total = 10 - ($total % 10);
		if ($total > 9) {
			$total = 0;
		};

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 10, 11)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkLT($vatno, $countryISO) {
		$vatno = substr($vatno, 2);

		// Checks the check digits of a Lithuanian VAT number.
		// Only do check digit validation for standard VAT numbers
		if (strlen($vatno) != 9) {
			return true;
		}

		// Extract the next digit and multiply by the counter+1.
		$total = 0;
		for ($i = 0; $i < 8; $i++) {
			$total = $total + substr($vatno, $i, 1) * ($i + 1);
		}

		// Can have a double check digit calculation!
		if ($total % 11 == 10) {
			$multipliers = $this->_countryConfig[$countryISO]['multipliers'];
			$total = 0;
			for ($i = 0; $i < 8; $i++) {
				$total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i]);
			}
		}

		// Establish check digit.
		$total = $total % 11;
		if ($total == 10) {
			$total = 0;
		};

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 8, 9)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkLU($vatno, $countryISO) {
		$vatno = substr($vatno, 2);
		// Checks the check digits of a Luxembourg VAT number.

		if (substr($vatno, 0, 6) % 89 == substr($vatno, 6, 8)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkLV($vatno, $countryISO) {
		$vatno = substr($vatno, 2);

		// Checks the check digits of a Latvian VAT number.
		// Only check the legal bodies
		//if ((/^[0-3]/).test(vatnumber)) return true;

		if (preg_match("/^[0-3]/", $vatno)) {
			return true;
		}


		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 10; $i++) {
			$total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i]);
		}

		// Establish check digits by getting modulus 11.
		if ($total % 11 == 4 && substr($vatno, 0, 1) == 9) {
			$total = $total - 45;
		}
		if ($total % 11 == 4) {
			$total = 4 - $total % 11;
		} elseif ($total % 11 > 4) {
			$total = 14 - $total % 11;
		} elseif ($total % 11 < 4) {
			$total = 3 - $total % 11;
		}

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 10, 11)) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkPL($vatno, $countryISO) {
		$vatno = substr($vatno, 2);

		// Checks the check digits of a Polish VAT number.

		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 9; $i++) {
			$total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i]);
		}

		// Establish check digits subtracting modulus 11 from 11.
		$total = $total % 11;
		if ($total > 9) {
			$total = 0;
		}

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 9, 10)) {
			return true;
		} else {
			return false;
		}
	}

    protected function _checkRO($vatno, $countryISO) {
        $vatno = substr($vatno, 2);

        // Checks the check digits of a Polish VAT number.

        $total = 0;
        $multipliers = $this->_countryConfig[$countryISO]['multipliers'];

        // Extract the next digit and multiply by the counter.
        for ($i = 0; $i < 9; $i++) {
            $total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i]);
        }

        // Establish check digits subtracting modulus 11 from 11.
        $total = $total % 11;
        if ($total > 9) {
            $total = 0;
        }

        // Compare it with the last character of the VAT number. If it is the same,
        // then it's a valid check digit.
        if ($total == substr($vatno, 9, 10)) {
            return true;
        } else {
            return false;
        }
    }

	protected function _checkSK_js($vatno, $countryISO) {
		$vatno = substr($vatno, 2);

		// Checks the check digits of a Slovak VAT number.

		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		// Extract the next digit and multiply by the counter.
		for ($i = 3; $i < 9; $i++) {
			$total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i - 3]);
		}

		// Establish check digits by getting modulus 11.
		$total = 11 - $total % 11;
		if ($total > 9) {
			$total = $total - 10;
		}

		// Compare it with the last character of the VAT number. If it is the same,
		// then it's a valid check digit.
		if ($total == substr($vatno, 9, 10)) {
			return true;
		} else {
			return false;
		}
	}

	/* implemented following rules from bmf pdf -> js version seems wrong */

	protected function _checkSK($vatno, $countryISO) {
		$vatno = substr($vatno, 2);

		// Checks the check digits of a Slovak VAT number.

		$total = 0;
		$total = $vatno % 11;

		if ($total == 0) {
			return true;
		} else {
			return false;
		}
	}

	protected function _checkSI($vatno, $countryISO) {
		$vatno = substr($vatno, 2);
		// Checks the check digits of a Slovenian VAT number.

		$total = 0;
		$multipliers = $this->_countryConfig[$countryISO]['multipliers'];

		// Extract the next digit and multiply by the counter.
		for ($i = 0; $i < 7; $i++) {
			$total = $total + ((int)substr($vatno, $i, 1) * $multipliers[$i]);
		}

		// Establish check digits by subtracting 97 from total until negative.
		$total = 11 - $total % 11;
		if ($total > 9) {
			$total = 0;
		}

		// Compare the number with the last character of the VAT number. If it is the
		// same, then it's a valid check digit.
		if ($total == substr($vatno, 7, 8)) {
			return true;
		} else {
			return false;
		}
	}

    /**
     * @param string $vatno
     * @return bool
     * @see http://ec.europa.eu/taxation_customs/vies/faqvies.do
     */
	protected function _checkVatVies($vatno) {
	    // return true;  // hack for working local...
        set_time_limit(0);
        $this->_lastCheckType = self::VAT_CHECKTYPE_VIES;

		$countryCode = strtoupper(substr($vatno, 0, 2));
		$vatNo = substr($vatno, 2);
        $completeVat = $countryCode . $vatNo;

        // Check our own local ressources first, before stressing the EU VIES Server:
        $readConnection  = Mage::getSingleton('core/resource')->getConnection('core_read');
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

        $vatTable = Mage::getSingleton('core/resource')->getTableName('schrack_vat_vies_cache');
        $query = "SELECT * FROM " . $vatTable . " WHERE vat LIKE ?";

        $queryResult = $readConnection->fetchAll($query, $completeVat);

        if (count($queryResult) > 0) {
            foreach ($queryResult as $index => $recordset) {
                $valid = $recordset['valid'];
                Mage::log($completeVat . " -> " . $valid, null, 'vies_check_local.log');
            }

            if ($valid == 1) {
                Mage::log('SUCCESS: LOCAL-Checked-VALID: countryCode -> ' . $countryCode . ' || vatNumber -> ' . $vatNo, null, 'vies.log');
                return true;
            } else {
                Mage::log('ERROR: LOCAL-Checked-INVALID: countryCode -> ' . $countryCode . ' || vatNumber -> ' . $vatNo, null, 'vies.log');
                return false;
            }
        } else {
            try {
                $soapClient = $this->_getSoapClient();
                $response = $soapClient->checkVat(array(
                    'countryCode' => $countryCode,
                    'vatNumber' => $vatNo,
                        ));
                if ($response->valid) {
                    $this->_setLastResult(self::VAT_OK);

                    // Write invalid VAT to local cache DB (valid-case):
                    $query = "INSERT INTO " . $vatTable . " SET valid = 1,";
                    $query .= " vat = :uid,";
                    $query .= " created_at = '" . date("Y-m-d H:i:s") . "'";
                    $binds = array(
                        'uid' => $completeVat,
                    );
                    $queryResult = $writeConnection->query($query, $binds);

                    Mage::log('VALID (EU VIES Server): countryCode -> ' . $countryCode . ' || vatNumber -> ' . $vatNo, null, 'vies.log');

                    return true;
                } else {
                    // Write invalid VAT to local cache DB (invalid-case):
                    $query = "INSERT INTO " . $vatTable . " SET valid = 0,";
                    $query .= " vat = :uid,";
                    $query .= " created_at = '" . date("Y-m-d H:i:s") . "'";
                    $binds = array(
                        'uid' => $completeVat,
                    );
                    $queryResult = $writeConnection->query($query, $binds);

                    Mage::log('ERROR -> IN-VALID (EU VIES Server): ' . 'countryCode -> ' . $countryCode . ' || vatNumber -> ' . $vatNo, null, 'vies.log');
                }
            } catch (Exception $e) {
                Mage::log('VIES ERROR  (EU VIES Server): ' . ' SOAP-ResponseMessage: ' . $e->getMessage() . ' +++ countryCode -> ' . $countryCode . ' || vatNumber -> ' . $vatNo, null, 'vies.log');
                $this->_setLastResult(self::VAT_UNCHECKED);
                return false;
            }
            Mage::log('GENERALLY INVALID: ' . 'countryCode -> ' . $countryCode . ' || vatNumber -> ' . $vatNo, null, 'vies.log');
            $this->_setLastResult(self::VAT_INVALID);
            return false;
        }
	}

    /**
	 * @return Zend_Soap_Client
	 */
	private function _getSoapClient() {
		if ( !$this->_client ) {
			$options = array(
				'schrack_system' => 'vatcheck',
                'schrack_log_transfer' => true,
			);
            $proxyHost = Mage::getStoreConfig('schrack/general/proxy_host');
            $proxyPort = Mage::getStoreConfig('schrack/general/proxy_port');
            if ( $proxyHost && $proxyPort ) {
                $options['proxy_host'] = $proxyHost;
                $options['proxy_port'] = $proxyPort;
            }
            $wsdl = Mage::getStoreConfig('schrack/vatcheck/wsdl_url');
			$this->_client = Mage::helper('schrack/soap')->createClient($wsdl, $options);
			if (Mage::getStoreConfig('schrack/vatcheck/timeout') && is_int(Mage::getStoreConfig('schrack/vatcheck/timeout'))) {
                $this->_client->setConnectionTimeout(Mage::getStoreConfig('schrack/vatcheck/timeout'));
            }
		}
		return $this->_client;
	}
    
}
