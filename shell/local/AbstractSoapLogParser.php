<?php

ini_set('memory_limit', '128M');

abstract class Schracklive_Shell_AbstractSoapLogParser {
    protected $srcFileNames;
    protected $srcFileName;
    protected $originalSrcFileName;
    protected $verbose = false;
    protected $options = null;
    protected $cntRead = 0;

    abstract protected function showUsage ();
    abstract protected function specificParse ( &$line, &$lastLineRest, $fullInfoLine, $ts );
    protected function getNecessaryAdditionalArgCnt () { return 0; }
    protected function handleAdditionalArgs ( array $argv ) {}
    protected function initWriting () {}
    protected function exitWriting () {}

    public function run ( array $argv ) {
        $this->options = null;
        if ( isset($argv[1]) && substr($argv[1],0,1) === '-' ) {
            $this->options = $argv[1];
            $this->verbose = strchr($this->options,'v') !== false;
            array_shift($argv);
        }
        $neededArgs = $this->getNecessaryAdditionalArgCnt() + 1;
        if ( count($argv) < $neededArgs || $neededArgs > 0 && (! isset($argv[1]) || ! file_exists($argv[1])) ) {
            $this->showUsage();
            die();
        }

        $this->srcFileNames = $this->getFileNames($argv);
        $this->handleAdditionalArgs($argv);


        $this->initWriting();
        foreach ( $this->srcFileNames as $this->srcFileName ) {
            $unPackedFileName = false;
            $ext = pathinfo($this->srcFileName, PATHINFO_EXTENSION);
            $this->originalSrcFileName = $this->srcFileName;
            if ( strcasecmp($ext, 'gz') == 0 ) {
                $unPackedFileName = $this->ungz($this->srcFileName);
                $this->srcFileName = $unPackedFileName;
            }

            $this->printlnVerbose(PHP_EOL . "reading now $this->srcFileName...");
            $fp = fopen($this->srcFileName, 'rt');
            if ( $fp ) {
                $lastLineRest = false;
                while ( ($line = fgets($fp)) !== false ) {
                    $line = trim(str_replace(array("\n", "\r"), '', $line));
                    if ( $lastLineRest ) {
                        $line = $lastLineRest . $line;
                        $lastLineRest = false;
                    }
                    if ( strncmp($line, '<?xml version', 13) == 0 ) {
                        $p = strpos($line, '>');
                        if ( $p < 1 )
                            continue;
                        $line = substr($line, ++$p);
                    }
                    if ( strlen($line) < 1 ) {
                        continue;
                    }
                    if ( (int)substr($line, 0, 4) > 2000 ) {
                        $infoLine = $line;
                        if ( $p = stripos($infoLine, 'UTC') ) {
                            $ts = substr($infoLine, 0, $p - 1);
                        }
                        continue;
                    }

                    $this->cntRead += $this->specificParse($line, $lastLineRest, $infoLine, $ts);
                }
                fclose($fp);
            }
            if ( $unPackedFileName ) {
                unlink($unPackedFileName);
            }
        }
        $this->exitWriting();
        $this->printDone();
    }

    protected function getFileNames ( array &$args ) {
        $res = array();
        $res[] = $args[1];
        array_shift($args);
        array_shift($args);
        return $res;
    }

    protected function rrmdir ( $dir ) {
        if (is_dir($dir)) {
            $this->printlnVerbose("deleting previous dir $dir");
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") {
                        $this->rrmdir($dir."/".$object);
                    }
                    else {
                        unlink($dir."/".$object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    private function ungz ( $srcFilePath ) {
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

    protected function printlnVerbose ( $msg ) {
        if ( $this->verbose ) {
            echo $msg . PHP_EOL;
        }
    }
    protected function printDone () {
        $this->printlnVerbose(PHP_EOL."done, $this->cntRead read.");
    }
}

// =================================================================================================================
// =================================================================================================================
// =================================================================================================================

abstract class Schracklive_Shell_AbstractSoapLogDocParser extends Schracklive_Shell_AbstractSoapLogParser {

    abstract protected function handleDocument ( DOMDocument $doc, $fullInfoLine, $ts );

    protected function specificParse ( &$line, &$lastLineRest, $fullInfoLine, $ts ) {
        if ( strncmp(strtoupper(substr($line,0,18)),'<SOAP-ENV:ENVELOPE',18) == 0 ) {
            $tmp = stristr($line,'</SOAP-ENV:Envelope>');
            if ( ! $tmp ) {
                $lastLineRest = $line;
                return 0;
            }
            if ( strlen($tmp) > 20 ) {
                $lastLineRest = substr($tmp,20);
                $p = strpos($line,$tmp);
                $line = substr($line,0,$p+20);
            }
            $xmlLine = '<?xml version="1.0" encoding="UTF-8"?><log_entry meta_info="'.$fullInfoLine.'">'.$line."</log_entry>";
            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = true;
            try {
                $doc->loadXML($xmlLine);
                $this->handleDocument($doc, $fullInfoLine, $ts);
            } catch ( Exception $ex ) {
                echo $ex->getTraceAsString() . PHP_EOL;
            }
            return 1;
        }
        return 0;
    }

    protected function getWebserviceFunctionName ( DOMDocument $doc ) {
        $fn = '';
        $el = $doc->getElementsByTagName("log_entry")->item(0); // log_entry
        if ( isset($el) ) {
            $fn = $el->tagName;
            $el = $el->getElementsByTagName("Envelope")->item(0);  // SOAP-ENV:Envelope
            if ( isset($el) ) {
                $fn = $el->tagName;
                $el = $el->getElementsByTagName("Body")->item(0);  // SOAP-ENV:Body
                if ( isset($el) ) {
                    $fn = $el->tagName;
                    $el = $el->getElementsByTagName("*")->item(0);  // function
                    if ( isset($el) ) {
                        $fn = $el->tagName;
                    }
                }
            }
        }
        $p = strpos($fn,':');
        if ( $p > 0 ) {
            $fn = substr($fn,++$p);
        }
        return $fn;
    }
}

// =================================================================================================================
// =================================================================================================================
// =================================================================================================================

abstract class Schracklive_Shell_AbstractSoapLogElementParser extends Schracklive_Shell_AbstractSoapLogParser {
    private $startElement, $endElement;
    protected $found = 0;

    protected function __construct ( $elementName ) {
        $this->startElement = '<' . $elementName . '>';
        $this->endElement   = '</' . $elementName . '>';
    }

    protected function specificParse ( &$line, &$lastLineRest, $fullInfoLine, $ts ) {
        $cnt = 0;
        while ( ($p = strpos($line,$this->startElement)) !== false ) {
            $q = strpos($line,$this->endElement);
            if ( $q === false ) {
                $lastLineRest = $line;
                return $cnt;
            }
            if ( $q <= $p ) {
                echo 'ERROR: parsing error in XML after line: ' . $fullInfoLine . PHP_EOL;
                $lastLineRest = $line = null;
                return $cnt;
            }
            $q += 14;
            $element = substr($line,$p,$q - $p);
            $line = substr($line,$q);
            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = true;
            $doc->loadXML($element);
            $root = $doc->documentElement;
            $this->handleElement($root,$fullInfoLine,$ts);
            ++$cnt;
        }
        return $cnt;
    }

    protected function handleElement ( DOMElement $element, $fullInfoLine, $ts ) {
        if ( ! $this->baseFilterElement($element) ) {
            $this->printElement($element,$fullInfoLine, $ts);
        }
    }

    private final function baseFilterElement ( DOMElement $element ) {
        $res = $this->filterElement($element);
        if ( ! $res ) {
            ++$this->found;
        }
        return $res;
    }

    protected function filterElement ( DOMElement $element ) {
        return false;
    }

    protected function printElement ( DOMElement $element, $fullInfoLine, $ts ) {
        echo 'ts: ' . $ts;
        foreach ( $element->childNodes as $node ) {
            echo ', ' . $node->nodeName . ': ' . $node->nodeValue;
        }
        echo PHP_EOL;
    }
}
