<?php
require_once 'shell.php';

class Schracklive_Shell_BotAccess extends Schracklive_Shell {
    private $zipDate = false;
    private $totalByCountry = array();
    private $totalByCountryAndHour = array();
    private $totalByCountryAndBot = array();
    private $totalByCountryAndBotAndHour = array();
    private $botToUserAgent = array();
    private $knownBots = array('semrush', 'yandex', 'metajob', 'slack', 'telegram', 'bitly', 'implisense', 'coccoc', 'msn', 'google', 'bing');
    private $hostName = '';

    function __construct() {
        parent::__construct();
        // $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->zipDate = $this->getArg('date');
        $this->hostName = gethostname();
    }

	public function run () {
        if ( ! $this->zipDate ) {
            die($this->usageHelp());
        }
        $filesFound = false;
        $baseDir = '/var/log/schracklive';
        $files = scandir($baseDir);
        foreach ( $files as $file ) {
            if ( strlen($file) == 2 && is_dir($baseDir . DS . $file) ) {
                // /var/log/schracklive/at/nginx/access.log-20181203.gz
                $accLogFile = $baseDir . DS . $file . DS . 'nginx' .DS . 'access.log-' . $this->zipDate . '.gz';
                if ( is_file($accLogFile) ) {
                    $this->handleFile($accLogFile,$file);
                    $filesFound = true;
                }
            }
        }
        if ( ! $filesFound ) {
            echo "no access files with date '{$this->zipDate}' found." . PHP_EOL;
        } else {
            $this->mkOutputFile("totalByCountry",$this->totalByCountry);
            $this->mkOutputFile("totalByCountryAndHour",$this->totalByCountryAndHour);
            $this->mkOutputFile("totalByCountryAndBot",$this->totalByCountryAndBot);
            $this->mkOutputFile("totalByCountryAndBotAndHour",$this->totalByCountryAndBotAndHour);
            $this->mkOutputFile("botToUserAgent",$this->botToUserAgent);

            echo "done. output files can be found as '/tmp/BotAccess_*_{$this->zipDate}_{$this->hostName}.csv'" . PHP_EOL;
        }
    }

    private function mkOutputFile ( $name, array $data ) {
        $fileName = '/tmp/BotAccess_' . $name . '_' . $this->zipDate . '_' . $this->hostName . '.csv';
        $fpOut = fopen($fileName,"w");
        foreach ( $data as $k => $v ) {
            $this->writeCsvLines($fpOut,$k,$v);
        }
        fclose($fpOut);
    }

    private function writeCsvLines ( $fpOut, $startOfLine, $data ) {
        if ( is_array($data) ) {
            foreach ( $data as $k => $v ) {
                $this->writeCsvLines($fpOut, $startOfLine . ';' . $k, $v);
            }
        } else {
            $line = $startOfLine . ';' . $data . PHP_EOL;
            fputs($fpOut, $line);
        }
    }

    private function handleFile ( $file, $country ) {
        echo $file . PHP_EOL;
        $ungz = $this->ungz($file);
        $fpIn = fopen($ungz,"r");
        $fpOut = fopen($this->outputFile,"a");
        $line = false;
        while ( ( $line = fgets($fpIn) ) !== false ) {
            $line = trim($line);
            // 40.77.167.57 - - [03/Dec/2018:01:16:35 +0100] "GET /shop/catalog/category/view/id/1308779 HTTP/1.1" 404 140331 "-" "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)"
            $l = strlen($line);
            $inBrackets = $inQuotes = false;
            for ( $i = 0; $i < $l; ++$i ) {
                $c = $line[$i];
                if ( $c == '"' ) {
                    $inQuotes = !$inQuotes;
                } else if ( $c == '[' ) {
                    $inBrackets = true;
                } else if ( $c == ']' ) {
                    $inBrackets = false;
                } else if ( $c == ' ' && ! $inQuotes && ! $inBrackets ) {
                    $line[$i] = chr(9);
                }
            }
            $fields = explode(chr(9),$line);
            if ( $this->isPotentialBot($fields) ) {
                $ts = $this->parseTimestamp($fields[3]);
                $hour = date("y.m.d H",$ts);
                $bot = $this->getBotName($fields);
                // $outLine = $country . ";" . $fields[0] . ";" . $fields[3] . ";" .  $fields[8] . PHP_EOL;
                // fputs($fpOut,$outLine);
                $this->totalByCountry[$country]                             = isset($this->totalByCountry[$country]) ? $this->totalByCountry[$country] + 1 : 1;
                $this->totalByCountryAndHour[$country][$hour]               = isset($this->totalByCountryAndHour[$country][$hour]) ? $this->totalByCountryAndHour[$country][$hour] + 1 : 1;
                $this->totalByCountryAndBot[$country][$bot]                 = isset($this->totalByCountryAndBot[$country][$bot]) ? $this->totalByCountryAndBot[$country][$bot] + 1 : 1;
                $this->totalByCountryAndBotAndHour[$country][$bot][$hour]   = isset($this->totalByCountryAndBotAndHour[$country][$bot][$hour]) ? $this->totalByCountryAndBotAndHour[$country][$bot][$hour] + 1 : 1;
                $this->botToUserAgent[$bot][$fields[8]]                     = true;
            }
        }
        fclose($fpIn);
        fclose($fpOut);
        unlink($ungz);
    }

    private function getBotName ( $fields ) {
        $userAgent = $fields[8];
        foreach ( $this->knownBots as $bot ) {
            if ( stripos($userAgent,$bot) !== false ) {
                return $bot;
            }
        }
        return $userAgent;
    }

    private function parseTimestamp ( $ts ) {
        //  [03/Dec/2018:01:16:12 +0100]
        // '03 Dec 2018 01:16:04'
        $ts = substr($ts,1);
        $p = strpos($ts,' ');
        $ts = substr($ts,0,$p);
        $p = strpos($ts,':');
        $ts[$p] = ' ';
        $ts = str_replace('/',' ',$ts);
        return strtotime($ts);
    }

    private function isPotentialBot ( $fields ) {
        $userAgent = $fields[8];
        return     $userAgent == '""'
                || $userAgent == '"-"'
                || $userAgent == ''
                || (
                        ($p = stripos($userAgent,'bot')) !== false
                     && stripos($userAgent,'cubot ') != $p - 2
                   );
    }

    public function usageHelp () {
        return <<<USAGE
Usage:  php -f BotAccess.php --date <YYYYMMDD>

USAGE;
    }
};

(new Schracklive_Shell_BotAccess())->run();
