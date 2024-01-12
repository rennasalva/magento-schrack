<?php

if ( ! isset($argv[1]) || ! isset($argv[2]) ) {
    help("Missing parameter(s)!");
}

$protoFilePath = $argv[1];
$phpFilePath = $argv[2];

if ( ! is_file($protoFilePath) ) {
    help("Not a file: '$protoFilePath'");
}
if ( ! is_file($phpFilePath) ) {
    help("Not a file: '$phpFilePath'");
}

echo "FixDrSlump..." . PHP_EOL;
$data = parseProtobuf($protoFilePath);
patchPhpFile($phpFilePath,$data);
echo "...done." . PHP_EOL;
die();

function parseProtobuf ( $path ) {
    $res = array();
    $msgStack = array();
    $lines = file($path);
    foreach ( $lines as $line ) {
        $line = trim($line);
        $toks = getProtobufTokens($line);
        $tokCnt = count($toks);
        if ( $tokCnt < 1 ) {
            continue;
        }
        if ( $toks[0] == "message" || $toks[0] == "enum" ) {
            $msgStack[] = $toks[1];
        } else if ( $toks[0] == "}" ) {
            array_pop($msgStack);
        } else if ( $tokCnt > 7 && $toks[1] == 'bool' && $toks[5] == 'default' && $toks[7] == 'false' ) {
            // $key = implode('.',$msgStack);
            $key = end($msgStack); // no nested type diversification yet
            if ( ! isset($res[$key]) ) {
                $res[$key] = array();
            }
            $res[$key][$toks[2]] = 'false';
        }
    }
    return $res;
}

function getProtobufTokens ( $s ) {
    if ( ($p = strpos($s,'//')) !== false ) {
        $s = substr($s,0,$p);
    }
    $s = str_replace('=',' = ',$s);
    $s = str_replace('{',' { ',$s);
    $s = str_replace('}',' } ',$s);
    $res = array();
    $delis = " \t\r\n[]";
    $tok = strtok($s,$delis);
    while ( $tok !== false ) {
        $res[] = $tok;
        $tok = strtok($delis);
    }
    return $res;
}

function patchPhpFile ( $path, $data ) {
    $className = '';
    $lines = file($path);
    $newLines = array();
    foreach ( $lines as $line ) {
        $toks = getPhpTokens($line);
        $tokCnt = count($toks);
        if ( $tokCnt < 1 ) {
            $newLines[] = $line;
            continue;
        }
        if ( $toks[0] == 'class' ) {
            $className = $toks[1];
        } else if ( $tokCnt > 3 && $toks[0] == 'public' && $toks[3] == 'true' ) {
            $protoName = substr($toks[1],1);
            if ( isset($data[$className]) && isset($data[$className][$protoName]) ) {
                $line = str_replace($toks[3],$data[$className][$protoName],$line);
                echo "Fixing: $line";
            }
        }
        $newLines[] = $line;
    }
    $data2write = implode('',$newLines);
    file_put_contents($path,$data2write);
}

function getPhpTokens ( $s ) {
    if ( ($p = strpos($s,'//')) !== false ) {
        $s = substr($s,0,$p);
    }
    $res = array();
    $delis = " \t\r\n;";
    $tok = strtok($s,$delis);
    while ( $tok !== false ) {
        $res[] = $tok;
        $tok = strtok($delis);
    }
    return $res;
}


function help ( $msg = null ) {
    if ( $msg ) {
        echo PHP_EOL . $msg . PHP_EOL . PHP_EOL;
    }
    echo "Usage: php FixDrSlump.php <protobuf definition file> <generated php source file>" . PHP_EOL . PHP_EOL . PHP_EOL;
    die();
}
