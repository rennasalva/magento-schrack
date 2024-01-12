<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

const SRC_DIR                   = '/tmp/restore_customers/';
const CSV_FILE                  = 'reactivate_customers.csv';
const SQL_FILE_WITHOUT_COUNTRY  = '_customer_all_dump.sql';
const CHECK_MISSING             = 'missing';
const CHECK_EQUAL               = 'equal';
const CHECK_DIFFERENT           = 'different';
const CHECK_RECREATED           = 'recreated';
const CHECK_CUSTNO_MISMATCH     = 'custno_msimatch';
const CHECK_DIFFERENT_EMAIL     = 'different_email';
const CHECK_INACTIVE            = 'email_inactive';
const CHECK_DIFFERENT_CONTACTNO = 'different_contactno';
const CHECK_ACCOUNT_MISSING     = 'account_missing';
const CHECK_ACCOUNT_CUSTNO      = 'account_different_custno';


class Schracklive_Shell_RestoreLostCustomers extends Mage_Shell_Abstract {

    var $readConnection, $writeConnection, $country, $control, $data, $tablesToUse, $idsToChange, $idsToUpdate, $commit = false, $commitCount = 2;

    public function __construct () {
        parent::__construct();
        if ( $this->getArg('commit') ) {
            $this->commit = true;
        }

        if ( ($cnt = $this->getArg('commit_count')) ) {
            $this->commitCount = $cnt;
        }

    }


	public function run() {
        $this->tablesToUse = array( 'customer_entity'           => array('entity_id','entity_type_id','attribute_set_id','website_id','email','group_id','increment_id','store_id','created_at','updated_at','is_active','created_by','updated_by','schrack_account_id','schrack_wws_customer_id','schrack_wws_contact_number','schrack_user_principal_name','schrack_customer_type','schrack_newsletter','disable_auto_group_change','schrack_default_payment_shipping','schrack_default_payment_pickup'),
                                    'customer_entity_int'       => array('value_id','entity_type_id','attribute_id','entity_id','value'),
                                    'customer_entity_text'      => array('value_id','entity_type_id','attribute_id','entity_id','value'),
                                    'customer_entity_varchar'   => array('value_id','entity_type_id','attribute_id','entity_id','value')
        //                            'account'                   => array('account_id','created_at','updated_at','wws_customer_id','wws_branch_id','prefix','name1','name2','name3','advisor_principal_name','advisors_principal_names','delivery_block','email','homepage','created_by','gtc_accepted','updated_by','match_code','description','information','crm_status','vat_identification_number','company_registration_number','sales_area','rating','enterprise_size','account_type','vat_local_number')
        );
		$this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->country = strtolower(Mage::getStoreConfig('general/country/default'));
        if ( $this->country == 'dk' ) {
            $this->country = 'co';
        }
        $this->control = array();
        $this->idsToChange = array();
        $this->idsToUpdate = array();

        $this->readCsvFile();
        $this->readSqlFile();

        $this->checkData();

        $this->changeData();

		echo 'done.' . PHP_EOL;
	}

	private function changeData () {
        foreach ( $this->idsToChange as $entityId ) {
            if ( $this->commitCount-- < 1 ) {
                return;
            }
            if ( ! isset($this->data['customer_entity'][$entityId]) ) {
                echo "skipping entity_id $entityId because no entity record" .PHP_EOL;
            }
            if ( $this->commit ) {
                $this->writeConnection->beginTransaction();
            }
            try {
                foreach ( $this->data as $tableName => $tableData ) {
                    if ( $tableName == 'account' ) {
                        continue;
                    }
                    foreach ( $tableData[$entityId] as $record ) {
                        if ( $tableName == 'customer_entity' ) {
                            if ( $this->idsToUpdate[$entityId] ) {
                                $dispString = !$this->commit ? 'would update: ' : 'update now: ';
                            } else {
                                $dispString = !$this->commit ? 'would insert: ' : 'insert now: ';
                            }
                            $dispString .= $record['email'];
                            echo $dispString . PHP_EOL;
                            if ( $this->commit ) {
                                $state = Schracklive_Crm_Helper_Data::STATUS_ACCOUNT_PENDING;
                                $sql = "UPDATE account SET crm_status = '$state' WHERE account_id = {$record['schrack_account_id']}";
                                echo $sql . PHP_EOL;
                                $this->writeConnection->query($sql);
                            }
                        }
                        if ( $this->commit ) {
                            if ( $this->idsToUpdate[$entityId] ) {
                                $this->update($tableName,$record);
                            } else {
                                $this->insert($tableName,$record);
                            }
                        }
                    }
                }
                if ( $this->commit ) {
                    $this->writeConnection->commit();
                }
            } catch ( Exception $ex ) {
                if ( $this->commit ) {
                    $this->writeConnection->rollback();
                }
                echo $ex;
                die(-1);
            }
        }
    }

    private function insert ( $tableName, $record ) {
        $fields = $values = null;
        foreach ( $record as $fieldname => $value ) {
            if ( ($tableName == 'customer_entity' && $fieldname == 'entity_id') || ($tableName != 'customer_entity' && $fieldname == 'value_id') ) {
                continue;
            }
            if ( strpos(trim($value,"'"),"'") ) {
                $value = "'" . str_replace("'","\\'",trim($value,"'")) . "'";
            }
            if ( ! $fields ) {
                $fields = $fieldname;
                $values = $value;
            } else {
                $fields .= ',';
                $values .= ',';
                $fields .= $fieldname;
                $values .= $value;
            }
        }
        $sql = "INSERT INTO $tableName ($fields) VALUES($values)";
        echo $sql . PHP_EOL;
        $this->writeConnection->query($sql);
    }

    private function update ( $tableName, $record ) {
        $sql = "UPDATE $tableName SET ";
        $first = true;
        foreach ( $record as $fieldname => $value ) {
            if ( strpos(trim($value,"'"),"'") ) {
                $value = "'" . str_replace("'","\\'",trim($value,"'")) . "'";
            }
            if ( $first )
                $first = false;
            else
                $sql .= ',';
            $sql .= $fieldname;
            $sql .= '=';
            $sql .= $value;
        }
        if ( $tableName == 'customer_entity' ) {
            $sql .= " WHERE entity_id = " . $record['entity_id'];
        } else {
            $sql .= " WHERE value_id = " . $record['value_id'];
        }
        echo $sql . PHP_EOL;
        $this->writeConnection->query($sql);
    }

	private function checkData () {
        foreach ( $this->control as $entityId => $email ) {
            foreach ( $this->data as $tableName => $tableData ) {
                if ( $tableName == 'account' ) {
                    continue;
                }
                foreach ( $tableData[$entityId] as $record ) {
                    $checkResult = $this->checkRecord($tableName,$record,$entityId);
                    $dispString = $checkResult  . ':' . chr(9) . $tableName . chr(9) . $record['entity_id'] . chr(9) . $email;
                    if ( $tableName == 'customer_entity' ) {
                        $dispString .= chr(9);
                        $dispString .= $record['schrack_wws_customer_id'];
                        if ( $checkResult == CHECK_MISSING ) {
                            $this->idsToChange[] = $record['entity_id'];
                            $dispString .= chr(9);
                            $dispString .= 'RE-INSERT!';
                        } else if ( $checkResult == CHECK_INACTIVE ) {
                            $this->idsToChange[] = $record['entity_id'];
                            $this->idsToUpdate[$record['entity_id']] = true;
                            $dispString .= chr(9);
                            $dispString .= 'UPDATE!';
                        }
                        echo $dispString . PHP_EOL;
                    }
                }
            }
        }
    }

    private function checkRecord ( $tableName, $record, $entityId ) {
        $sql = "SELECT * FROM $tableName WHERE entity_id = $entityId";
        $dbRes = $this->readConnection->fetchAll($sql);
        if ( ! $dbRes ) {
            // TODO look for email
            if ( $tableName == 'customer_entity' ) {
                $sql = "SELECT * FROM customer_entity WHERE email = {$record['email']}";
                $dbRes = $this->readConnection->fetchAll($sql);
                if ( $dbRes ) {
                    foreach ( $dbRes as $dbRec ) {
                        if ( $dbRec['schrack_wws_customer_id'] == $record['schrack_wws_customer_id'] ) {
                            return CHECK_RECREATED;
                        } else {
                            return CHECK_CUSTNO_MISMATCH;
                        }
                    }
                }
            }
            return CHECK_MISSING;
        }
        foreach ( $dbRes as $dbRec ) {
            if ( $tableName == 'customer_entity' ) {
                if ( $dbRec['email'] != trim($record['email'], "'") ) {
                    if ( substr($dbRec['email'],0,9) == 'inactive+' ) {
                        return CHECK_INACTIVE;
                    } else {
                        return CHECK_DIFFERENT_EMAIL;
                    }
                } else if ( $dbRec['schrack_wws_contact_number'] != $record['schrack_wws_contact_number'] ) {
                    return CHECK_DIFFERENT_CONTACTNO;
                } else {
                    $sql = "SELECT wws_customer_id FROM account WHERE account_id = {$record['schrack_account_id']}";
                    $accountCustId = $this->readConnection->fetchOne($sql);
                    if ( !$accountCustId ) {
                        return CHECK_ACCOUNT_MISSING;
                    } else if ( $accountCustId != trim($record['schrack_wws_customer_id'],"'") ) {
                        return CHECK_ACCOUNT_CUSTNO;
                    }
                }
            }
            foreach ( $dbRec as $dbField => $dbValue ) {
                if ( ! isset($record[$dbField]) ) {
                    throw new Exception("Missing field $dbField in record for table $tableName");
                }
                if ( $dbValue != trim($record[$dbField],"'") ) {
                    return CHECK_DIFFERENT;
                }
            }
        }
        return CHECK_EQUAL;
    }

	private function readCsvFile () {
        $fileName = SRC_DIR . CSV_FILE;
        $fp = fopen($fileName,'r');
        $record = null;
        while ( ($record = fgetcsv($fp,null,';',"'")) ) {
            if ( $record[0] == $this->country ) {
                $email = $record[5];
                $entityId = $record[1];
                // echo $email . PHP_EOL;
                $this->control[$entityId] = $email;
            }
        }
        fclose($fp);
    }

	private function readSqlFile () {
        $fileName = SRC_DIR . $this->country . SQL_FILE_WITHOUT_COUNTRY;
        $fp = fopen($fileName,'r');
        $line = null;
        while ( ($line = fgets($fp)) ) {
            // 01234567890123456789012345678901234567890123456789
            // INSERT INTO `customer_address_entity` VALUES (273,2,0,NULL,489,'2010-09-07 01:06:28','2017-02-03 14:51:45',1,'','mqimport',NULL,0),(27
            $start = substr($line,0,50);
            $tableName = $this->getTableName($start);
            if ( isset($this->tablesToUse[$tableName]) ) {
                echo " reading $tableName ..." . PHP_EOL;
                $p = strpos($line,'VALUES ');
                $line = substr($line,$p + 7);
                $this->parseTableData($tableName,$line);
            }
        }
        fclose($fp);
    }

    private function getTableName ( $line ) {
        $res = explode(' ',$line)[2];
        $res = str_replace('`','',$res);
        return $res;
    }

    private function parseTableData ( $tableName, $line ) {
        $line = trim($line,"() \t\n\r\0\x0B");
        $records = explode('),(',$line);
        foreach ( $records as $record ) {
            $this->parseRecord($tableName, $record);
        }
    }

    private function parseRecord ( $tableName, $recordString ) {
        $fieldNames = $this->tablesToUse[$tableName];
        $values = explode(',',$recordString);
        if ( count($fieldNames) <> count($values) ) {
            $values = $this->slowParse($recordString);
            if ( count($fieldNames) <> count($values) ) {
                throw new Exception("Different count of fields and values for table $tableName");
            }
        }
        $record = array_combine($fieldNames,$values);
        if ( isset($record['entity_id']) ) {
            $id = $record['entity_id'];
            if ( isset($this->control[$id]) ) {
                $this->addRecord($tableName,$record,$id);
            }
        } else {
            $this->addRecord($tableName,$record,$record['account_id']);
        }
    }

    private function addRecord ( $tableName, $record, $id ) {
        if ( ! isset($this->data[$tableName]) ) {
            $this->data[$tableName] = array();
        }
        if ( ! isset($this->data[$tableName][$id]) ) {
            $this->data[$tableName][$id] = array();
        }
        $this->data[$tableName][$id][] = $record;
    }

    private function slowParse ( $recordString ) {
        $res = array();
        $buf = '';
        $chars = str_split($recordString);
        $inString = false;
        $escape = false;
        foreach ( $chars as $char ) {
            if ( $char == ',' && ! $inString ) {
                $res[] = $buf;
                $buf = '';
            } else {
                if ( $char == '\\' ) {
                    $escape = true;
                } else {
                    if ( $char == "'" && !$escape ) {
                        $inString = ! $inString;
                    }
                    $buf .= $char;
                    $escape = false;
                }
            }
        }
        $res[] = $buf;
        return $res;
    }
}

$shell = new Schracklive_Shell_RestoreLostCustomers();
$shell->run();
