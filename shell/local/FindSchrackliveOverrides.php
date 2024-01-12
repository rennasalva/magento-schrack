<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_FindSchrackliveOverrides extends Mage_Shell_Abstract {
    var $fileContent = '';
    var $fileName = '';
    var $className = '';
    var $verbose = false;

	public function run() {
        set_include_path(get_include_path() . ':' . dirname(dirname(dirname(__FILE__))));
        $schrackliveDir = Mage::getRoot() . DS . 'code' . DS . 'local' . DS . 'Schracklive';
        $this->walkDir($schrackliveDir);
        echo PHP_EOL . 'done.' . PHP_EOL;
    }

    private function walkDir ( $dir ) {
        $files = scandir($dir);
        foreach ( $files as $file ) {
            if ( $file == '.' || $file == '..' || $file == 'MockWws' || $file == 'ProtobufController.php' || $file == 'Cookie.php' ) continue;
            $fullFilePath = $dir . DS . $file;
            if ( is_dir($fullFilePath) ) {
                // echo 'DIR: ' . $fullFilePath . PHP_EOL;
                $this->walkDir($fullFilePath);
            } else if ( $this->isPhpFile($file) ) {
                // echo 'FILE: ' . $fullFilePath . PHP_EOL;
                $this->workFile($fullFilePath);
            }
        }
    }

    private function isPhpFile ( $file ) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        return strcasecmp($ext,'php') === 0;
    }

    private function workFile ( $file ) {
        $this->fileName = $file;
        $this->fileContent = file_get_contents($file);
        $this->fileContent = str_replace("\r",'',$this->fileContent);
        $this->removeComments();
        $this->className = $this->getClassName();
        if ( ! $this->className ) {
            return;
        }
        require_once $this->fileName;
        $ref = new ReflectionClass($this->className);
        $this->checkClass($ref);
    }

    private function getClassName () {
        $p = strpos($this->fileContent,'class Schracklive_');
        if ( $p !== false ) {
            $p += 6;
            $res = substr($this->fileContent,$p);
            $x = strpbrk($res," \t\n\r{");
            if ( $x === false ) {
                $f = $this->fileName;
                throw new Exception("Error parsing class name in file $f !");
            }
            $q = strpos($res,$x);
            $res = substr($res,0,$q);
            return $res;
        } else {
            return false;
        }
    }

    private function removeComments () {
        while ( ($p = strpos($this->fileContent,'/*')) !== false ) {
            $q = strpos($this->fileContent,'*/',$p);
            if ( $q !== false ) {
                $this->fileContent = substr($this->fileContent,0,$p) . substr($this->fileContent,$q + 2);
            } else {
                $this->fileContent = substr($this->fileContent,0,$p);
            }
        }
        while ( ($p = strpos($this->fileContent,'//')) !== false ) {
            $q = strpos($this->fileContent,"\n",$p);
            if ( $q !== false ) {
                $this->fileContent = substr($this->fileContent,0,$p) . substr($this->fileContent,$q);
            } else {
                $this->fileContent = substr($this->fileContent,0,$p);
            }
        }
    }

    private function checkClass ( ReflectionClass $ref ) {
        $baseClass = $ref->getParentClass();
        if ( $baseClass && stristr($ref->getName(),'Schracklive') ) {
            $methods = $this->getDeclaringMethods($ref);
            $this->checkBaseClass($baseClass,$methods,$ref);
        }
    }

    private function checkBaseClass ( ReflectionClass $baseClass, array $subClassMethods, ReflectionClass $subClass ) {
        $methods = $this->getDeclaringMethods($baseClass);
        if ( strpos($baseClass->getName(),"Schracklive_") === false ) {
            foreach ( $subClassMethods as $subClassMethod ) {
                foreach ( $methods as $method ) {
                    if ( $subClassMethod->getName() == $method->getName() && ! $method->isAbstract() && ! $method->isPrivate() && ! $method->isConstructor() ) {
                        $this->checkMethod($subClass, $subClassMethod);
                    }
                }
            }
        }
        $baseBaseClass = $baseClass->getParentClass();
        if ( $baseBaseClass ) {
            $this->checkBaseClass($baseBaseClass,$subClassMethods,$subClass);
        }
    }

    private function checkMethod ( ReflectionClass $class, ReflectionMethod $method ) {
        $methodSource = $this->getMethodSource($method);
        $searchStr = "parent::" . $method->getName();
        $p = strpos($methodSource,$searchStr);
        if ( $p === false ) {
            echo ">>> No ::parent call for function " . $class->getName() . '::' . $method->getName() . "\n";
            if ( $this->verbose ) {
                echo $methodSource;
                echo "<<<\n";
            }
        } else if ( $this->verbose ) {

        }
    }

    private function getMethodSource ( ReflectionMethod $func ) {
        $filename = $func->getFileName();
        $start_line = $func->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
        $end_line = $func->getEndLine();
        $length = $end_line - $start_line;
        $source = file($filename);
        $body = implode("", array_slice($source, $start_line, $length));
        return $body;
    }

    private function getDeclaringMethods ( ReflectionClass $class ) {
        $res = array();
        $methods = $class->getMethods();
        foreach ( $methods as $method ) {
            if (  $method->getDeclaringClass() == $class ) {
                $res[] = $method;
            }
        }
        return $res;
    }
}

$shell = new Schracklive_Shell_FindSchrackliveOverrides();
$shell->run();
