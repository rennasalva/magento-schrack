<?php

class Schracklive_MoCalc_Helper_Data extends Mage_Core_Helper_Abstract {

    const MOCALC_CACHE_LIFETIME = 6 * 60 * 60; // lifetime 6 hours
    const SESSION_DATA_KEY      = 'mocalc';
    const REGISTRY_DATA_KEY     = 'mocalc_data';
    const REGISTRY_STEP_KEY     = 'mocalc_step';

    const PROPERTY_LABELS = array(
        'icu'           => 'Icu',
        'rated_current' => 'Rated current',
        'build_size'    => 'Frame size',
        'pole_count'    => 'Number of poles',
        'mount_type'    => 'Type of mounting'
    );

    /** @var Magento_Db_Adapter_Pdo_Mysql $dbConnection */
    private $dbConnection;

    private $data;

    private $defaultBaseAccessories;

    private $part2priceMap;
    private $part2nameMap;
    private $properties;
    private $propertyOptions;
    private $baseAccessoryTypes;
    private $baseAccessories;
    private $optionalAccessories;
    private $availableLanguages;

    private $coreHelper;

    public function __construct () {
        $this->coreHelper = Mage::helper('core');
        $this->dbConnection = Mage::getSingleton('core/resource')->getConnection('common_db');
        $this->dbConnection->query('set names utf8;');
        $this->loadAvailableLanguages();
        $this->loadPrices();
        $this->loadNames();
        $this->loadProperties();
        $this->loadPropertyOptions();
        $this->loadBaseAccessoryTypes();
        $this->loadBaseAccessories();
        $this->loadOptionalAccessories();
    }

    public function reset () {
        $session = Mage::getSingleton('customer/session');
        $session->setData(Schracklive_MoCalc_Helper_Data::SESSION_DATA_KEY,null);
        $this->loadOrCreateData();
    }

    public function loadOrCreateData () {
        $session = Mage::getSingleton('customer/session');
        $this->data = $session->getData(Schracklive_MoCalc_Helper_Data::SESSION_DATA_KEY);
        if ( ! is_array($this->data) ) {
            $this->defaultBaseAccessories = array(
                'overcurrent_release'   => array( 'label' => 'Overcurrent release', 'part_no' => 'MO890000' ),
                'actuation'             => array( 'label' => 'Drive',               'part_no' => 'MO891000' ),
                'aux_relay_1'           => array( 'label' => 'Auxiliary release 1', 'part_no' => 'MO890A00' ),
                'aux_relay_2'           => array( 'label' => 'Auxiliary release 2', 'part_no' => 'MO8900A0' ),
                'aux_contact'           => array( 'label' => 'Auxiliary contact',   'part_no' => 'MO890002' )
            );
            foreach ( $this->defaultBaseAccessories as $key => $baseAccessory ) {
                $price = $this->part2priceMap[$baseAccessory['part_no']];
                $this->defaultBaseAccessories[$key]['price'] = $this->formatPrice($price);
                $this->defaultBaseAccessories[$key]['price_cent'] = $this->getCentPrice($price);
                $this->defaultBaseAccessories[$key]['name'] = $this->part2nameMap[$baseAccessory['part_no']];
            }
            $this->data = array(
                'schrack_part_number'   => 'MO______',
                'foreign_part_number'   => '3WL1___-_____-____-Z',
                'main_price'            => $this->formatPrice('0.00'),
                'main_price_cent'       => 0,
                'properties'            => array(),
                'base_accessories'      => array(
                    'overcurrent_release'   => $this->defaultBaseAccessories['overcurrent_release'],
                    'actuation'             => $this->defaultBaseAccessories['actuation'],
                    'aux_relay_1'           => $this->defaultBaseAccessories['aux_relay_1'],
                    'aux_relay_2'           => $this->defaultBaseAccessories['aux_relay_2'],
                    'aux_contact'           => $this->defaultBaseAccessories['aux_contact']
                ),
                'optional_accessories'  => array(),
                'total_price'           => $this->formatPrice('0.00'),
                'discount'              => 0,
                'final_price'           => $this->formatPrice('0.00')
            );
            $sql = "SELECT property_name FROM tx_schrackcbconf_acb_properties ORDER BY sort_value";
            $dbRes = $this->dbConnection->fetchCol($sql);
            foreach ( $dbRes as $propName ) {
                $this->data['properties'][$propName] = array(
                    'label'     => self::PROPERTY_LABELS[$propName],
                    'sel_value' => null,
                    'name'      => ''
                );
            }
            foreach ( $this->data['base_accessories'] as $key => $baseAccessory ) {
                $this->applyBaseAccessoryToForeignPartNumber($key,$baseAccessory['part_no']);
            }
        }
        Mage::unregister(self::REGISTRY_DATA_KEY);
        Mage::register(self::REGISTRY_DATA_KEY,$this->data);
    }

    public function printCSV () {
        $this->loadOrCreateData();
        $data = $this->getData();
        $csvData = array();
        $csvData[] = [ $this->__('Partnumber'), $data['schrack_part_number'], '', self::csvPrice($data['main_price']) ];
        foreach ( $data['properties'] as $prop ) {
            if ( isset($prop['sel_value']) ) {
                $csvData[] = [ $this->__($prop['label']), '', str_replace('<br />',"\n", $prop['name']), '' ];
            }
        }
        foreach ( $data['base_accessories'] as $acc ) {
            $csvData[] = [ $this->__($acc['label']), $acc['part_no'], $acc['name'], self::csvPrice($acc['price']) ];
        }
        foreach ( $data['optional_accessories'] as $acc ) {
            $csvData[] = [ $this->__($acc['label']), $acc['part_no'], $acc['name'], self::csvPrice($acc['price']) ];
        }
        $csvData[] = [ $this->__('Total price'), '', '', self::csvPrice($data['total_price']) ];
        if ( $data['discount'] > 0 ) {
            $csvData[] = [ $this->__('Discount'), '', '', $data['discount'] . "%" ];
            $csvData[] = [ $this->__('Net Price'), '', '', self::csvPrice($data['final_price']) ];
        }
        $csvHelper = Mage::helper('schrack/csv');
        $csvHelper->createCsvDownloadFromArray($csvData,'configuration.csv');
    }

    private static function csvPrice ( $price ) {
        $price = str_replace('--','00',$price);
        $len = strlen($price);
        $p = $len - 7;
        if ( $len > 6 && ($price[$p] > '9' || $price[$p] < '0' ) ) {
            $price = substr($price,0,$p) . substr($price,$p + 1);
        }
        return $price;
    }

    public function getData () {
        if ( ! is_array($this->data) ) {
            $this->data = Mage::registry(self::REGISTRY_DATA_KEY);
        }
        return $this->data;
    }

    public function saveData () {
        $session = Mage::getSingleton('customer/session');
        $session->setData(Schracklive_MoCalc_Helper_Data::SESSION_DATA_KEY,$this->data);
    }

    private function recalcTotalPrice () {
        $price = $this->data['main_price_cent'];
        foreach ( $this->data['base_accessories'] as $acc ) {
            $price += $acc['price_cent'];
        }
        foreach ( $this->data['optional_accessories'] as $acc ) {
            $price += $acc['price_cent'];
        }
        $strPrice = sprintf("%d.%02d",$price / 100,$price % 100);
        $this->data['total_price'] = $this->formatPrice($strPrice);
        if ( $this->data['discount'] > 0 ) {
            $finalPriceCent = intval(round($price / 100 * (100 - $this->data['discount'])));
            $strPrice = sprintf("%d.%02d",$finalPriceCent / 100,$finalPriceCent % 100);
            $this->data['final_price'] = $this->formatPrice($strPrice);
        } else {
            $this->data['final_price'] = $this->data['total_price'];
        }
    }

    public function getPossibleProperties ( $name ) {
        return $this->getPossibleThings($this->propertyOptions[$name]);
    }

    public function getPossibleBaseAccessories  ( $name ) {
        return $this->getPossibleThings($this->baseAccessories[$name]);
    }

    public function getPossibleOptionalAccessories () {
        return $this->getPossibleThings($this->optionalAccessories);
    }

    private function getPossibleThings ( $allThings ) {
        $currentRawPartNo = $this->getCurrentSchrackRawPartNumber();
        $res = array();
        foreach ( $allThings as $thing ) {
            if ( $this->matchConstraints($thing['constraints'],$currentRawPartNo) ) {
                $res[] = $thing;
            }
        }
        return $res;
    }

    public function handlePropertyChange ( $property, $value ) {
        if ( ! isset($value) ) {
            return;
        }
        $allOptions = $this->propertyOptions[$property];
        foreach ( $allOptions as $propertyOption ) {
            if ( $propertyOption['sel_value'] == $value ) {
                break;
            }
        }
        $property = $this->properties[$property];
        $this->setProperty($propertyOption,$property);
    }

    public function handleBaseAccessoryChange ( $type, $partNo ) {
        $this->getData();
        $acc = $this->baseAccessories[$type][$partNo];
        $this->data['base_accessories'][$type]['part_no'] = $acc['part_number'];
        $this->data['base_accessories'][$type]['name'] = $acc['description'];
        $this->data['base_accessories'][$type]['price'] = $acc['price'];
        $this->data['base_accessories'][$type]['price_cent'] = $acc['price_cent'];
        $this->applyBaseAccessoryToForeignPartNumber($type,$partNo);
        $this->reCheckAll();
        $this->recalcTotalPrice();
        $this->saveData();
    }

    public function handleOptionalAccessories ( $partNumbers ) {
        $this->getData();
        $this->data['optional_accessories'] = array();
        foreach ( $partNumbers as $partNumber ) {
            $rec = $this->optionalAccessories[$partNumber];
            $this->data['optional_accessories'][$partNumber] = array(
                'part_no'       => $rec['part_number'],
                'name'          => $rec['description'],
                'price'         => $rec['price'],
                'price_cent'    => $rec['price_cent']
            );
        }
        $this->addOptionalAccessoriesToForeignPartNumber();
        $this->reCheckAll();
        $this->recalcTotalPrice();
        $this->saveData();
    }

    public function handleDiscount ( $discount ) {
        if ( ! is_numeric($discount) || $discount > 99 || $discount < 0 ) {
            return;
        }
        $this->getData();
        $this->data['discount'] = $discount;
        $this->recalcTotalPrice();
        $this->saveData();
    }

    private function setProperty ( $propertyOption, $property ) {
        $this->getData();
        $this->setSchrackPartNumberPart($propertyOption,$property);
        $this->setForeignPartNumberPart($propertyOption,$property);
        $propertyName = $property['property_name'];
        $this->data['properties'][$propertyName]['sel_value'] = $propertyOption['sel_value'];
        $this->data['properties'][$propertyName]['name'] = $propertyOption['caption'];
        if ( strpos($this->data['schrack_part_number'],'_') === false ) {
            $price = $this->part2priceMap[$this->data['schrack_part_number']];
            $this->data['main_price'] = $this->formatPrice($price);
            $this->data['main_price_cent'] = $this->getCentPrice($price);
        }
        $this->reCheckAll();
        $this->recalcTotalPrice();
        $this->saveData();
    }

    private function setSchrackPartNumberPart ( $propertyOption, $property ) {
        $value = $propertyOption['sel_value'];
        $mask = $property['code_mask'];
        $rawNumber = $this->getCurrentSchrackRawPartNumber();
        $newRawNumber = $this->setRawPartNumberPart($rawNumber,$value,$mask,6);
        $this->setCurrentSchrackRawPartNumber($newRawNumber);
    }

    private function setForeignPartNumberPart ( $propertyOption, $property ) {
        $value = $propertyOption['sel_value_foreign'];
        $mask = $property['foreign_code_mask'];
        $rawNumber = $this->getCurrentForeignRawPartNumber();
        $newRawNumber = $this->setRawPartNumberPart($rawNumber,$value,$mask,12);
        $this->setCurrentForeignRawPartNumber($newRawNumber);
    }

    private function applyBaseAccessoryToForeignPartNumber ( $accType, $accPartNumber ) {
        $value = $this->baseAccessories[$accType][$accPartNumber]['sel_value_foreign'];
        $mask  = $this->baseAccessoryTypes[$accType]['foreign_code_mask'];
        $rawNumber = $this->getCurrentForeignRawPartNumber();
        $newRawNumber = $this->setRawPartNumberPart($rawNumber,$value,$mask,12);
        $this->setCurrentForeignRawPartNumber($newRawNumber);
    }

    private function setRawPartNumberPart ( $rawNumber, $value, $mask, $len ) {
        $strMask = decbin($mask);
        $strMask = str_pad($strMask,$len,'0',STR_PAD_LEFT);
        $value = (string) $value;
        $valueLen = strlen($value);
        $i = strpos($strMask,'1');
        for ( $j = 0; $j < $valueLen && $strMask[$i] == '1'; ++$i, ++$j ) {
            $rawNumber[$i] = $value[$j];
        }
        return $rawNumber;
    }

    private function matchConstraints ( $pattern, $partNo ) {
        $res = preg_match("/$pattern/", $partNo);
        if ( $res === false ) {
            $errNo = preg_last_error();
            if ( PREG_NO_ERROR != $errNo ) {
                Mage::log("preg_match caused error $errNo");
            }
            return 1;
        }
        return $res;
    }

    private function reCheckAll () {
        $hasChanges = false;
        $currentRawPartNo = $this->getCurrentSchrackRawPartNumber();
        $currentForeignRawPartNo = $this->getCurrentForeignRawPartNumber();
        foreach ( $this->data['properties'] as $key => $prop ) {
            if ( ! isset($prop['sel_value']) ) {
                continue;
            }
            $constraints = $this->propertyOptions[$key][$prop['sel_value']]['constraints'];
            if ( ! $this->matchConstraints($constraints,$currentRawPartNo) ) {
                $propDef = $this->properties[$key];
                $currentRawPartNo = $this->setRawPartNumberPart($currentRawPartNo,'__',$propDef['code_mask'],6);
                $currentForeignRawPartNo = $this->setRawPartNumberPart($currentForeignRawPartNo,'__',$propDef['foreign_code_mask'],12);
                $this->data['properties'][$key]['sel_value'] = null;
                $hasChanges = true;
            }
        }
        if ( $hasChanges ) {
            $this->data['main_price']       = $this->formatPrice('0.00');
            $this->data['main_price_cent']  = 0;
        }
        foreach ( $this->data['base_accessories'] as $key => $acc ) {
            $constraints = $this->baseAccessories[$key][$acc['part_no']]['constraints'];
            propertyOptions[$key][$prop['sel_value']]['constraints'];
            if ( ! $this->matchConstraints($constraints,$currentRawPartNo) ) {
                $mask = $this->baseAccessoryTypes[$key]['foreign_code_mask'];
                $currentForeignRawPartNo = $this->setRawPartNumberPart($currentForeignRawPartNo,'__',$mask,12);
                $hasChanges = true;
            }
        }
        $removeList = array();
        foreach ( $this->data['optional_accessories'] as $key => $acc ) {
            $constraints = $this->optionalAccessories[$key]['constraints'];
            if ( ! $this->matchConstraints($constraints,$currentRawPartNo) ) {
                $removeList[] = $key;
                $hasChanges = true;
            }
        }
        foreach ( $removeList as $key ) {
            unset($this->optionalAccessories[$key]);
        }
        if ( $hasChanges ) {
            $this->setCurrentSchrackRawPartNumber($currentRawPartNo);
            $this->setCurrentForeignRawPartNumber($currentForeignRawPartNo);
        }
    }

    private function getCurrentSchrackRawPartNumber () {
        return substr($this->getData()['schrack_part_number'],2);
    }

    private function getCurrentForeignRawPartNumber () {
//  'foreign_part_number'   => '3WL1___-_____-____-Z',
//  'foreign_part_number'   => '3WL1___-_____-____-ZABC+DEF+GHI',
        $x = $this->getData()['foreign_part_number'];
        $x = substr($x,0,20);
        $x = substr($x,4, strlen($x) - 6);
        return str_replace('-','',$x);
    }

    private function setCurrentForeignRawPartNumber ( $number ) {
        $this->data['foreign_part_number']  = '3WL1'
                                            . substr($number,0,3) . '-'
                                            . substr($number,3,5) . '-'
                                            . substr($number,8,4)
                                            . '-Z';
        $this->addOptionalAccessoriesToForeignPartNumber(false);
    }

    private function addOptionalAccessoriesToForeignPartNumber ( $removeOld = true ) {
        $no = $this->data['foreign_part_number'];
        if ( $removeOld ) {
            $no = substr($no,0,20);
        }
        $first = true;
        foreach ( $this->data['optional_accessories'] as $acc ) {
            if ( $first ) {
                $first = false;
            } else {
                $no .= '+';
            }
            $no .= $this->optionalAccessories[$acc['part_no']]['sel_value_foreign'];
        }
        $this->data['foreign_part_number'] = $no;
    }


    private function setCurrentSchrackRawPartNumber ( $number ) {
        $this->data['schrack_part_number'] = 'MO' . $number;
    }

    private function loadProperties () {
        if ( ! is_array($this->properties) ) {
            /** @var Zend_Cache_Core $cache */
            $cache = Mage::app()->getCache();
            $cacheID = 'mocalc_properties';
            if ( $cacheRes = $cache->load($cacheID) ) {
                $this->properties = unserialize($cacheRes);
            } else {
                $this->properties = array();
                $sql = " SELECT * FROM tx_schrackcbconf_acb_properties"
                     . " ORDER BY sort_value;";
                $dbRes = $this->dbConnection->fetchAll($sql);
                foreach ( $dbRes as $row ) {
                    $propName = $row['property_name'];
                    $this->properties[$propName] = $row;
                }
                $cache->save(serialize($this->properties), $cacheID, [], self::MOCALC_CACHE_LIFETIME);
            }
        }
    }
    
    private function loadPropertyOptions () {
        if ( ! is_array($this->propertyOptions) ) {
            /** @var Zend_Cache_Core $cache */
            $cache = Mage::app()->getCache();
            $cacheID = 'mocalc_property_options';
            if ( $cacheRes = $cache->load($cacheID) ) {
                $this->propertyOptions = unserialize($cacheRes);
            } else {
                $lang = $this->getLanguageCode();
                $sql = " SELECT o.property_name, sel_value, sel_value_foreign, constraints, l.caption FROM tx_schrackcbconf_acb_property_options o"
                     . " JOIN tx_schrackcbconf_acb_property_options_lang l ON o.sort_value = l.sort_value AND o.property_name = l.property_name AND lang = '$lang'"
                     . " WHERE active = 'y'"
                     . " ORDER BY o.sort_value;";
                $dbRes = $this->dbConnection->fetchAll($sql);
                $this->propertyOptions = array();
                foreach ( $dbRes as $row ) {
                    $propName = $row['property_name'];
                    $selValue = $row['sel_value'];
                    if ( ! isset($this->propertyOptions[$propName]) ) {
                        $this->propertyOptions[$propName] = array();
                    }
                    $this->propertyOptions[$propName][$selValue] = $row;
                }
                $cache->save(serialize($this->propertyOptions), $cacheID, [], self::MOCALC_CACHE_LIFETIME);
            }
        }
    }

    private function loadBaseAccessoryTypes () {
        if ( ! is_array($this->baseAccessoryTypes) ) {
            /** @var Zend_Cache_Core $cache */
            $cache = Mage::app()->getCache();
            $cacheID = 'mocalc_base_accessory_types';
            if ( $cacheRes = $cache->load($cacheID) ) {
                $this->baseAccessoryTypes = unserialize($cacheRes);
            } else {
                $this->baseAccessoryTypes = [];
                $sql = " SELECT type_id, foreign_code_mask, constraints, sort_value FROM tx_schrackcbconf_acb_base_accessory_types"
                     . " ORDER BY sort_value;";
                $dbRes = $this->dbConnection->fetchAll($sql);
                foreach ( $dbRes as $row ) {
                    $this->baseAccessoryTypes[$row['type_id']] = $row;
                }
                $cache->save(serialize($this->baseAccessoryTypes),$cacheID,array(),self::MOCALC_CACHE_LIFETIME);
            }
        }
    }
        
    private function loadBaseAccessories () {
        if ( ! is_array($this->baseAccessories) ) {
            /** @var Zend_Cache_Core $cache */
            $cache = Mage::app()->getCache();
            $cacheID = 'mocalc_base_accessories';
            if ( $cacheRes = $cache->load($cacheID) ) {
                $this->baseAccessories = unserialize($cacheRes);
            } else {
                $this->baseAccessories = [];
                $lang = $this->getLanguageCode();
                $sql = " SELECT a.part_number, a.type_id, a.price, a.constraints, a.sel_value_foreign, a.sort_value, l.description, l2.description AS default_description FROM tx_schrackcbconf_acb_base_accessories as a"
                     . " LEFT JOIN tx_schrackcbconf_acb_base_accessories_lang l ON  l.part_number = a.part_number AND l.lang = '$lang'"
                     . " JOIN tx_schrackcbconf_acb_base_accessories_lang l2 ON  l2.part_number = a.part_number AND l2.lang = 'default'"
                     . " ORDER BY a.sort_value;";
                $dbRes = $this->dbConnection->fetchAll($sql);
                foreach ( $dbRes as $row ) {
                    $tid = $row['type_id'];
                    $row['part_number'] = $pn = 'MO' . $row['part_number'];
                    if ( ! isset($this->baseAccessories[$tid]) ) {
                        $this->baseAccessories[$tid] = array();
                    }
                    if ( isset($this->part2priceMap[$pn]) ) {
                        $row['price'] = $this->part2priceMap[$pn];
                    }
                    $price = $row['price'];
                    $row['price'] = $this->formatPrice($price);
                    $row['price_cent'] = $this->getCentPrice($price);
                    if ( $row['description'] == null ) {
                        $row['description'] = $row['default_description'];
                    }
                    unset($row['default_description']);
                    $this->baseAccessories[$tid][$pn] = $row;
                }
                $cache->save(serialize($this->baseAccessories),$cacheID,array(),self::MOCALC_CACHE_LIFETIME);
            }
        }
    }

    private function loadOptionalAccessories () {
        if ( ! is_array($this->optionalAccessories) ) {
            /** @var Zend_Cache_Core $cache */
            $cache = Mage::app()->getCache();
            $cacheID = 'mocalc_optional_accessories';
            if ( $cacheRes = $cache->load($cacheID) ) {
                $this->optionalAccessories = unserialize($cacheRes);
            } else {
                $this->optionalAccessories = array();
                $lang = $this->getLanguageCode();
                $sql = " SELECT a.part_number, a.price, a.constraints, a.sel_value_foreign, a.sort, a.single_select, l.description, l.special_info, l2.description as default_description, l2.special_info as default_special_info"
                     . " FROM tx_schrackcbconf_acb_optional_accessories a"
                     . " LEFT JOIN tx_schrackcbconf_acb_optional_accessories_lang l ON l.part_number = a.part_number AND l.lang = '$lang'"
                     . " JOIN tx_schrackcbconf_acb_optional_accessories_lang l2 ON l2.part_number = a.part_number AND l2.lang = 'default'"
                     . " ORDER BY sort";
                $dbRes = $this->dbConnection->fetchAll($sql);
                foreach ( $dbRes as $row ) {
                    $pn = $row['part_number'];
                    if ( isset($this->part2priceMap[$pn]) ) {
                        $row['price'] = $this->part2priceMap[$pn];
                    }
                    $price = $row['price'];
                    $row['price'] = $this->formatPrice($price);
                    $row['price_cent'] = $this->getCentPrice($price);
                    if ( $row['description'] == null ) {
                        $row['description'] = $row['default_description'];
                    }
                    if ( $row['special_info'] == null ) {
                        $row['special_info'] = $row['default_special_info'];
                    }
                    unset($row['default_special_info']);
                    unset($row['default_description']);
                    $this->optionalAccessories[$pn] = $row;
                }
                $cache->save(serialize($this->optionalAccessories),$cacheID,array(),self::MOCALC_CACHE_LIFETIME);
            }
        }
    }

    private function loadNames () {
        if ( ! is_array($this->part2nameMap) ) {
            /** @var Zend_Cache_Core $cache */
            $cache = Mage::app()->getCache();
            $cacheID = 'mocalc_names';
            if ( $cacheRes = $cache->load($cacheID) ) {
                $this->part2nameMap = unserialize($cacheRes);
            } else {
                $this->part2nameMap = [];
                $langCode = $this->getLanguageCode();
                $this->addToPartNameMap("SELECT concat('MO',part_number) AS part_number, description FROM tx_schrackcbconf_acb_base_accessories_lang WHERE lang = '$langCode'");
                $this->addToPartNameMap("SELECT concat('MO',part_number) AS part_number, description FROM tx_schrackcbconf_acb_optional_accessories_lang WHERE lang = '$langCode'");
                $cache->save(serialize($this->part2nameMap),$cacheID,array(),self::MOCALC_CACHE_LIFETIME);
            }
        }
    }

    private function loadAvailableLanguages () {
        if ( ! is_array($this->availableLanguages) ) {
            /** @var Zend_Cache_Core $cache */
            $cache = Mage::app()->getCache();
            $cacheID = 'mocalc_available_languages';
            if ( $cacheRes = $cache->load($cacheID) ) {
                $this->availableLanguages = unserialize($cacheRes);
            } else {
                $this->availableLanguages = [];
                $sql = "SELECT DISTINCT lang FROM tx_schrackcbconf_acb_property_options_lang;";
                $dbRes = $this->dbConnection->fetchCol($sql);
                foreach ( $dbRes as $lang ) {
                    $this->availableLanguages[$lang] = true;
                }
                $cache->save(serialize($this->availableLanguages),$cacheID,array(),self::MOCALC_CACHE_LIFETIME);
            }
        }
    }

    private function addToPartNameMap ( $sql ) {
        $dbRes = $this->dbConnection->fetchAll($sql);
        foreach ( $dbRes as $row ) {
            $this->part2nameMap[$row['part_number']] = $row['description'];
        }
    }

    private function loadPrices () {
        if ( ! is_array($this->part2priceMap) ) {
            /** @var Zend_Cache_Core $cache */
            $cache = Mage::app()->getCache();
            $cacheID = 'mocalc_prices';
            if ( $cacheRes = $cache->load($cacheID) ) {
                $this->part2priceMap = unserialize($cacheRes);
            } else {
                $this->part2priceMap = array();
                $this->addToPriceMap("SELECT concat('MO',part_number) AS part_number, price FROM tx_schrackcbconf_acb");
                $this->addToPriceMap("SELECT concat('MO',part_number) AS part_number, price FROM tx_schrackcbconf_acb_base_accessories");
                $langCode = $this->getLanguageCode();
                $this->addToPriceMap("SELECT concat('MO',part_number) AS part_number, price FROM tx_schrackcbconf_prices WHERE lang = '$langCode'");
                $cache->save(serialize($this->part2priceMap),$cacheID,array(),self::MOCALC_CACHE_LIFETIME);
            }
        }
    }

    private function addToPriceMap ( $sql ) {
        $dbRes = $this->dbConnection->fetchAll($sql);
        foreach ( $dbRes as $row ) {
            $this->part2priceMap[$row['part_number']] = $row['price'];
        }
    }

    private function getLanguageCode () {
        $lang = strtolower(substr(Mage::getStoreConfig('general/locale/code'),0,2));
        if ( ! $this->availableLanguages[$lang] ) {
            $lang = 'default';
        }
        return $lang;
    }

    private function formatPrice ( $price ) {
        $res = $this->coreHelper->formatPrice($price);
        if ( substr($res,-2) == '00' ) {
            $res = substr($res,0,strlen($res) - 2) . '--';
        }
        return $res;
    }

    private function getCentPrice ( $price ) {
        if ( ! is_string($price) ) {
            return $price; // SNH
        }
        $p = strpos($price,'.');
        if ( $p === false ) {
            return (int) $price * 100;
        }
        $euro = intval(substr($price,0,$p));
        $cent = intval(substr($price,$p + 1));
        return $euro * 100 + $cent;
    }
}
