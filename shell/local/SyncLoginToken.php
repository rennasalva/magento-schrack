<?php

require_once 'shell.php';

class SyncLoginToken extends Schracklive_Shell {
	var $write, $read, $readCommon, $writeCommon, $countryId;

	function __construct() {
		parent::__construct();

		$this->write       = Mage::getSingleton('core/resource')->getConnection('core_write');
		$this->read        = Mage::getSingleton('core/resource')->getConnection('core_read');
		$this->readCommon  = Mage::getSingleton('core/resource')->getConnection('commondb_read');
		$this->writeCommon = Mage::getSingleton('core/resource')->getConnection('commondb_write');
		$this->countryId   = Mage::getStoreConfig('schrack/general/country');
	}

	public function run () {
		$sql = "SELECT email FROM customer_entity WHERE NOT email LIKE '%@live.schrack.com' AND NOT email LIKE '%@schracklive.com'";
		$rows = $this->read->fetchCol($sql);
        $emailsInCountry = array();
		foreach ( $rows as $email ) {
            $emailsInCountry[$email] = true;
			$sql = "SELECT max(country_id) as country, count(*) as cnt FROM login_token WHERE email = '$email';";
			$dbRes = $this->readCommon->fetchAll($sql);
			$row = reset($dbRes); // we get only one row back...
            $cnt = $row['cnt'];
            $commonCountry = $row['country'];
			if ( $cnt < 1 ) {
                // ============================================================================> doest not exist: create
				echo '+' . $email . PHP_EOL;
                $sql = "INSERT INTO login_token (email, country_id) VALUES(?,?);";
                $this->writeCommon->query($sql,[$email,$this->countryId]);
            } else if ( strstr($email,'@schrack.') === false ) { // ********************************> normal user:
                if ( $cnt == 1 ) {
                    if ( $commonCountry != $this->countryId ) {
                        // =======================> normal user once but wrong country: ensure country, remove entity_id
                        echo "%$email '$commonCountry' => '{$this->countryId}'\n";
                        $sql = "UPDATE login_token SET country_id = ?, entity_id = null WHERE email = ?";
                        $this->writeCommon->query($sql, [$this->countryId, $email]);
                    }
                } else {
                    // =============================> normal user exist multiple: delete all, create current country new
                    echo ">$email $cnt => 1 country\n";
                    $this->writeCommon->beginTransaction();
                    $sql = "DELETE FROM login_token WHERE  email = ?";
                    $this->writeCommon->query($sql,$email);
                    $sql = "INSERT INTO login_token (email, country_id) VALUES(?,?);";
                    $this->writeCommon->query($sql,[$email,$this->countryId]);
                    $this->writeCommon->commit();
                }
            } else { // *********************************************************************************> schrack user:
                // =========> schrack user exists for some countires: check if exists for this country and if not create
                $sql = "SELECT count(*) FROM login_token WHERE email = ? AND country_id = ?";
                $cnt = $this->readCommon->fetchOne($sql,[$email,$this->countryId]);
                if ( $cnt < 1 ) {
                    echo '+' . $email . PHP_EOL;
                    $sql = "INSERT INTO login_token (email, country_id) VALUES(?,?);";
                    $this->writeCommon->query($sql,[$email,$this->countryId]);
                }
            }
		}
		$sql = "SELECT email FROM login_token WHERE country_id = '{$this->countryId}'";
        $rows = $this->readCommon->fetchCol($sql);
        $emailsInCommonToDelete = array();
		foreach ( $rows as $email ) {
            if ( ! $emailsInCountry[$email] ) {
                $emailsInCommonToDelete[] = $email;
            }
        }
        foreach ( $emailsInCommonToDelete as $email ) {
            echo '-' . $email . PHP_EOL;
            $sql = "DELETE FROM login_token WHERE email = ? AND country_id = ?";
            $this->writeCommon->query($sql,[$email,$this->countryId]);
        }
		echo 'done.';
	}
}

$shell = new SyncLoginToken();
$shell->run();
