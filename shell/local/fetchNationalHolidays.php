<?php
    require_once 'shell.php';

    class Schracklive_Shell_FetchNationalHolidays extends Schracklive_Shell {

        private $_entrypoint;
        private $_accesskey;
        private $_secretkey;
        private $_serviceVersion;
        private $_country;
        private $_year;
        private $_types;


        public function __construct () {
            parent::__construct();
            $this->_entrypoint     = Mage::getStoreConfig('schrack/cutofftimes/holiday_service_url');
            $this->_accesskey      = Mage::getStoreConfig('schrack/cutofftimes/holiday_service_accesskey');
            $this->_secretkey      = Mage::getStoreConfig('schrack/cutofftimes/holiday_service_secretkey');
            $this->_service        = 'holidays';
            $this->_year           = date("Y");
            $this->_country        = strtolower(Mage::getStoreConfig('schrack/general/country'));
            // $this->_country        = 'ro'; // TODO : only for test
            $this->_serviceVersion = 2;
            $this->_types          = 'federal';
        }


        public function run() {
            $importType = 'missing import method';

            $args['accesskey'] = $this->_accesskey;
            $args['secretkey'] = $this->_secretkey;
            $args['version']   = $this->_serviceVersion;
            $args['types']     = $this->_types;
            $args['country']   = $this->_country;
            $args['year']      = $this->_year;

            $url = $this->_entrypoint . "/" . $this->_service . "?" . http_build_query($args);

            if ($this->getArg('service')) {
                $importType = 'service';
                $this->importHolidaysToDatabase('service', $url);
            } elseif ($this->getArg('file')) {
                $importType = 'file';
                $this->importHolidaysToDatabase('file');
            } elseif ($this->getArg('database')) {
                $importType = 'database';
                $this->importHolidaysToDatabase('database');
            } elseif ($this->getArg('geturl')) {
                $importType = 'geturl';
                echo $url . "\n";
            } else {
                echo $this->usageHelp();
            }

            if (intval(Mage::getStoreConfig('schrack/cutofftimes/cutofftimes_module_activated'), 10) === 1) {
                Mage::log(date('Y-m-d H:i:s') . ' fetchNationalHolidays.php ' . $importType . ' (ACTIVE) -> run::start', null, '/fetchNationalHolidays.log');
            } else {
                Mage::log(date('Y-m-d H:i:s') . ' fetchNationalHolidays.php ' . $importType . ' (NOT ACTIVE)', null, '/fetchNationalHolidays.log');
            }
        }


        private function importHolidaysToDatabase($jsonImportMethod, $url = false) {
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

            if ($jsonImportMethod == 'service') {
                //$ch = curl_init($url);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2");
                $result = curl_exec($ch);

                if (curl_errno($ch)) {
                    Mage::log(date('Y-m-d H:i:s') . ' [ERROR] CURL FAILED with error ' . curl_errno($ch) . ': ' . curl_error($ch), null, '/fetchNationalHolidays.log');
                }

                curl_close($ch);

                $this->_requestData = $result;

                if (!$this->_requestData) {
                    Mage::log(date('Y-m-d H:i:s') . ' [ERROR] Service Import Call results null', null, '/fetchNationalHolidays.log');
                } else {
                    Mage::log(date('Y-m-d H:i:s') . ' Service Import Call success', null, '/fetchNationalHolidays.log');
                }
            }

            if ($jsonImportMethod == 'file') {
                if (file_exists('fetchNationalHolidays.json')) {
                    $this->_requestData = file_get_contents('fetchNationalHolidays.json');
                    if (!$this->_requestData) {
                        Mage::log(date('Y-m-d H:i:s') . ' [ERROR] File Import Call results null', null, '/fetchNationalHolidays.log');
                    } else {
                        Mage::log(date('Y-m-d H:i:s') . ' File Import Call success', null, '/fetchNationalHolidays.log');
                    }
                } else {
                    echo '[ERROR] Please move into the directory where the import-file resides' . "\n";
                }
            }

            if ($jsonImportMethod == 'database') {
                // Fetch holiday data from database, which has been pasted manually before:
                $query = "SELECT json_data FROM national_holidays_import_data";
                $resultJSONString = $readConnection->fetchOne($query);

                if ($resultJSONString) {
                    $this->_requestData = $resultJSONString;
                }

                if (!$this->_requestData) {
                    Mage::log(date('Y-m-d H:i:s') . ' [ERROR] Database Import Call results null', null, '/fetchNationalHolidays.log');
                } else {
                    Mage::log(date('Y-m-d H:i:s') . ' Database Import Call success', null, '/fetchNationalHolidays.log');
                }
            }

            if ($this->_requestData) {
                // Clean table from old data:
                $truncate_query = "DELETE FROM national_holidays_import_data";
                $writeConnection->query($truncate_query);

                $import_query  = "INSERT INTO national_holidays_import_data SET ";
                $import_query .= " id = 1,";
                $import_query .= " json_data = '" . addslashes($this->_requestData) . "',";
                $import_query .= " json_data_import_datetime = '" . date('Y-m-d H:i:s') . "'";

                $writeConnection->query($import_query);

                $jsonHolidayData = json_decode($this->_requestData, true);

                if (is_array($jsonHolidayData) && !empty($jsonHolidayData) && is_array($jsonHolidayData['holidays']) && !empty($jsonHolidayData['holidays'])) {
                    $tempContainer = array();

                    foreach ($jsonHolidayData['holidays'] as $item => $section) {
                        if(isset($section['date']['iso'])) {
                            $tempContainer[] = $section['date']['iso'];
                        }
                    }

                    if (is_array($tempContainer) && !empty($tempContainer) && count($tempContainer) > 5) {#
                        // Clean table from old data:
                        $delete_query = "DELETE FROM national_holidays";
                        $writeConnection->query($delete_query);

                        foreach($tempContainer as $index => $datetime) {
                            $id = $index + 1;
                            $insert_query  = "INSERT INTO national_holidays SET ";
                            $insert_query .= " id = " . $id . ",";
                            $insert_query .= " holiday_datetime = '" . $datetime . "',";
                            $insert_query .= " import_datetime = '" . date('Y-m-d H:i:s') . "'";

                            $writeConnection->query($insert_query);
                        }
                    }
                }
            }
        }


        public function usageHelp() {
            return <<<USAGE
Usage:  php fetchNationalHolidays.php [options]

  geturl                      Only outputs the generatzed URL for get the data manually (could be pasted to database or file)
  service                     Import holiday data from service (firewall should be opened for this)
  file                        Import holiday data from JSON file (insert JSON-Content to standard import file in this dir: fetchNationalHolidays.json)
  database                    Import holiday data from database column 'manual_import' (pasted manually before)
  help                        This help

USAGE;
        }
    }

$shell = new Schracklive_Shell_FetchNationalHolidays();
$shell->run();
