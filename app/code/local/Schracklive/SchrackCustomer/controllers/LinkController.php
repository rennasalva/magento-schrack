<?php

/**
 * Class Schracklive_SchrackCustomer_RedirectedlinkController
 * module to fetch a link from a non-loggedin state to a loggedin state
 */
class Schracklive_SchrackCustomer_LinkController extends Mage_Core_Controller_Front_Action {

    private $_key;
    private $_fileInfos = array();

    public function _construct() {
        parent::_construct();
        $this->_key = pack('H*', '805803aec1893ab8c71482a64eeecfeadb57ad33971644062071f56951396409');
    }

    public function preDispatch() {
        parent::preDispatch();
        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * fetch the encrypted url - i.e., basically act as a very primitive proxy by fetching-and-outputting
     * the contents of the url
     */
    public function fetchAction() {
        $url = $this->getRequest()->getParam('url');
        if ( !isset($url) ) {
            throw new Exception('No URL given.');
        }

        $url = $this->_decrypt($this->_decodeUrlBase64($url));

        $session = Mage::getSingleton('customer/session');
        if ( $session->isLoggedIn() ) {
            $this->_safeRedirect($url);
        } else {
            $this->_redirect('customer/account/login');
        }
    }

    /**
     * redirect to the given encrypted url
     */
    public function redirectAction() {
        $url = $this->getRequest()->getParam('url');
        if ( !isset($url) ) {
            throw new Exception('No URL given.');
        }

        $url = $this->_decrypt($this->_decodeUrlBase64($url));

        $session = Mage::getSingleton('customer/session');
        if ( $session->isLoggedIn() ) {
            $this->_safeRedirect($url);
        } else {
            $this->_redirect('customer/account/login');
        }
    }

    private function _safeRedirect($url) {
        if ( $this->_isUrlValid( $url) ) {
            header('Location: ' . $url);
            die;
        } else {
            throw new Exception('Invalid URL given.');
        }
    }

    private function _isUrlValid($url) {
        if ( preg_match('#//' . $_SERVER['HTTP_HOST'] . '#',  $url) ) {
            return true;
        }
        return ( Mage::helper('schrackcore/http')->isSchrackHostUrl($url) || defined('DEBUG') );
    }

    /**
     * encrypt an url, sent in the parameter "t"
     */
    public function eAction() {
        $session = Mage::getSingleton('customer/session');
        if ( !$session->isLoggedIn() ) {
            die('go away');
        }

        $text = $this->getRequest()->getParam('t');

        if (strlen($text)) {
            $cypher = $this->_encrypt($text);
            $targetUrl = Mage::getUrl('customer/link/fetch') . '?url=' . $this->_encodeUrlBase64($cypher);
            echo "Here's your address: <br/><a href=\"$targetUrl\">$targetUrl</a><br/>";
        } else {
            echo <<<EOF
<html>
<body>
<form method="post">
enter url address:
<input type="text" name="t" size="255" maxlength="255"/>
<input type="submit" value="create encrypted url"/>
</form>
</body>
</html>
EOF;

        }
        die;
    }

    /**
     * decrypt an url, sent in the parameter "c"
     */
    public function dAction() {
        $session = Mage::getSingleton('customer/session');
        if ( !$session->isLoggedIn() ) {
            die('go away');
        }
        $ciphertext = $this->getRequest()->getParam('c');
        $cleartext = $this->_decrypt($this->_decodeUrlBase64($ciphertext));
        echo "cleartext: " . $cleartext . "\n";
        die;
    }

    private function _encrypt($cleartext) {
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        $encrypted = @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->_key, $cleartext, MCRYPT_MODE_CBC,$iv);
        return $iv . $encrypted;
    }

    private function _decrypt($ciphertext) {
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $ivDec = substr($ciphertext, 0, $ivSize);
        $ciphertextDec = substr($ciphertext, $ivSize);
        $cleartext = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->_key, $ciphertextDec, MCRYPT_MODE_CBC,$ivDec);
        if ( ! $cleartext || ! filter_var($cleartext = rtrim($cleartext),FILTER_VALIDATE_URL) ) {
            $cleartext = $this->decryptDeprecated($this->_key,$ciphertext);
        }
        $url = rtrim($cleartext);
        $host = parse_url($url, PHP_URL_HOST);
        if ( strncmp($host,"www.schrack",11) == 0  && strncmp($host,"www.schrack.at.local",20) != 0 ) {
            $url = str_replace("http://", "https://", $url);
        }
        return $url;
    }

    private function _decodeUrlBase64($url) {
        if ( preg_match('/[\-_,]/', $url) ) { // we didn't do it right from the start, so we must check for it... @TODO remove if needed no more
            $url = strtr($url, '-_,', '+/=');
        }
        return base64_decode($url);
    }

    private function _encodeUrlBase64($url) {
        return strtr(base64_encode($url), '+/=', '-_,');
    }

    private static $deprecatedLinkMap = array(
        "9eevAMKhw2oTEem2BJhSMpdd8LsE/ne4pE0Dtu5kzuFbOD/7wx5OCrXgIvUquGLqkZSnFg3TPHTQHNlU6XvnUQ=="
            => "http://image.schrack.com/schrack-cad-live/schrackcad.exe",
        "9eevAMKhw2oTEem2BJhSMpdd8LsE/ne4pE0Dtu5kzuFKsDW5QcihATy/0OgRjV6Eac1HoHHDdVJrwvR2PyU9Og=="
            => "http://image.schrack.com/schrackdesign/schrack_design.exe",
        "NS5trptSBaLLMntChSrdqAlFBqbQwQOpbcCDLhLYGDjPxpbRBd3fzH1q6D+hN6Dqfp3YUSqJQh4uSybFFZzM6andAXl3ooywe0DQFdE9TqR4VJ/I7iN2Gl515sUFrWa+"
            => "http://www.schrack.hu/fileadmin/f/hu/Downloadcenter/SCHRACK_strukturalt_halozat_mertezo.xltx",
        "NS5trptSBaLLMntChSrdqAlFBqbQwQOpbcCDLhLYGDjPxpbRBd3fzH1q6D+hN6Dqfp3YUSqJQh4uSybFFZzM6andAXl3ooywe0DQFdE9TqS8sMjNAhIEmq3D50Q00XGp82txN2bj6KbvAHQLZaRIqQ=="
            => "http://www.schrack.hu/fileadmin/f/hu/Downloadcenter/SCHRACK_strukturalt_halozat_mertezo_toolless.xltx",
        "NS5trptSBaLLMntChSrdqAlFBqbQwQOpbcCDLhLYGDjPxpbRBd3fzH1q6D+hN6DqiN+Hw+Wig9BDAm5eGnxW7xGxeLj6il5N7IbwZnC/sHo="
            => "http://www.schrack.hu/fileadmin/f/hu/Downloadcenter/SCHRACK_zarlatszamitas.xltx",
        "NS5trptSBaLLMntChSrdqBvjA/yEWKBvdvl/lmdHRhILcNUrHVW/QhJ/jMj/Izk/GbtuA6FI/j27XDcrhaCuoFvZgKARDCiyiWM1hFcCd8LnGasLbirs3QE7Vlz9b0DdiwEvFzuxtxG/IrAys74Eaw=="
            =>  "http://www.schrack-technik.de/fileadmin/f/de/bilder/photovoltaik/Photovotaik_Rechner_-_Schrack_Digital_.xlsx",
        "NS5trptSBaLLMntChSrdqBvjA/yEWKBvdvl/lmdHRhISuUfAFo5blQ+SXI9mBeXeibe0PU46yyk+ffstoGc76NTyoD+RWA4NHXPl+yEwKb7RTkXDjX7M0R3u96ve3VcWeJz3JM8+PdOKQvTzld5sVb2Y5GpEmFZT2I16Sjtha3pqGnY9h+EXv6zO1WgZK4Gb6BiEtrHCmGj8PZvBLzGW+A=="
            => "http://www.schrack-technik.de/fileadmin/f/at/bilder/produkte-shop/Gebaeudetechnik/Sprechanlagen_Kalkulator/Schrack_Sprechanlagen_Kalkulator_V1.5.xlsm",
        "NS5trptSBaLLMntChSrdqCIXS1JBfC/WmC1bE0U0yQFxC9PXTfXcUwso3dkfZI+sN42cyHApFcFEf6SGYWChcg=="
            => "http://www.schrack-technik.de/shop/promotions/promotions.html/",
        "NS5trptSBaLLMntChSrdqIlAbg3wFDfHsx6KAnetp0FOzT3SStoZtSg+fMDpJHV+nFzhcpBpVs73/zDLb7EW/BEXCeNdwXkUawUAeEApz3Z4of9sAlRBY3fZcgOvg5sgPPFmVSm1Gi0cVLfj0bA3+yMPjr9Toxq11qqzo+qpxFg="
            => "http://www.schrack.at/fileadmin/f/at/bilder/produkte-shop/photovoltaik/Unabhaengigkeitsrechner_Schrack_Technik.xlsx",
        "NS5trptSBaLLMntChSrdqIlAbg3wFDfHsx6KAnetp0FOzT3SStoZtSg+fMDpJHV+nFzhcpBpVs73/zDLb7EW/G8fKZ9Q17bpovSwU/Xy+Nop93FU3RcCxgdcr93+H9tgLfGoQ5G9JjYTvwb1+mhL289dYxZt97qjfm1dNqznqvs="
            => "http://www.schrack.at/fileadmin/f/at/bilder/produkte-shop/photovoltaik/Inselanlagen/Schrack_Technik_Photovotaik_Rechner.xlsx",
        "NS5trptSBaLLMntChSrdqIlAbg3wFDfHsx6KAnetp0FOzT3SStoZtSg+fMDpJHV+vSLqJYtANKtwXv83KpxWG04cCWw0eLFIIDXIgOxnCW+BWwKw4Rp2wISZqvG7xaQzRxr8P2OA65Tp1w+cJh4Eq2VVp1xIBB9FcMyodMGQhT/8+EF3szb2vgc522upwRjb"
            => "http://www.schrack.at/fileadmin/f/at/bilder/produkte-shop/Gebaeudetechnik/Sprechanlagen_Kalkulator/Schrack_Sprechanlagen_Kalkulator_V1.5.xlsm",
        "NS5trptSBaLLMntChSrdqLZwpOTG2ag5571GYdmn1EEYwRPOXD9qiFTuyyOjuZQw"
            => "http://www.schrack.at/shop/datanorm/"
    );

    private function decryptDeprecated ( $key, $text ) {
        $text = base64_encode($text);
        if ( isset(self::$deprecatedLinkMap[$text]) ) {
            Mage::log("Link found: $text",null,'decrypt_deprecated.log');
            return self::$deprecatedLinkMap[$text];
        }
        Mage::log("LINK NOT FOUND: $text",null,'decrypt_deprecated.log');
        return '';
    }

    private function getRemoteEtag ( $url ) {
        $info = $this->getFileInfo($url);
        if ( $info['ETag'] ) {
            return $info['ETag'];
        } else {
            return '(no etag got from remote)';
        }
    }

    private function saveLocalEtag ( $path, $remoteEtag ) {
        $fullPath = $this->getLocalEtagPath($path);
        $dir = dirname($fullPath);
        if ( ! is_dir($dir) ) {
            mkdir($dir,0755,true);
        }
        file_put_contents($fullPath,$remoteEtag);
    }

    private function getLocalEtag ( $path ) {
        $fullPath = $this->getLocalEtagPath($path);
        if ( ! file_exists($fullPath) ) {
            return "(no local etag)";
        } else {
            return file_get_contents($fullPath);
        }
    }

    private function getLocalEtagPath ( $path ) {
        return Mage::getBaseDir() . DS . 'bigfiles' . $path . '.etag';;
    }

    private function getFileInfo ( $url ) {
        if ( ! isset($this->_fileInfos[$url]) ) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            $output = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $info['ETag'] = $this->parseHeader($output,'ETag');
            $this->_fileInfos[$url] = $info;
        }
        return $this->_fileInfos[$url];
    }

    private function parseHeader ( $allHeaders, $headerName ) {
        $res = false;
        if ( ($p = strpos($allHeaders,$headerName . ':' )) !== false ) {
            if ( ($p = strpos($allHeaders, '"', $p)) !== false ) {
                ++$p;
                if ( ($q = strpos($allHeaders, '"', $p)) !== false ) {
                    $res = substr($allHeaders,$p,$q - $p);
                }
            }
        }
        return $res;
    }
}
