<?php

class Schracklive_Schrack_Helper_Email extends Mage_Core_Helper_Abstract {

    public function validateEmailAddress ( $emailAddress ) {
        if ( ! is_string($emailAddress) || strlen($emailAddress) < 5 ) {
            return false;
        }
        $active = intval(Mage::getStoreConfig('schrack/email/eyepin_check_url_active'));
        if (1 == $active) {
            $url = Mage::getStoreConfig('schrack/email/eyepin_check_url');
            $user = Mage::getStoreConfig('schrack/email/eyepin_check_user');
            $password = Mage::getStoreConfig('schrack/email/eyepin_check_password');
            if ( ! $url || ! $user || ! $password ) {
                throw new Exception("Eyepin email validation not properly configured!");
            }
            $xmlRequest = '<?xml version="1.0"?><check-email dns="1"><email><![CDATA[' . $emailAddress . ']]></email></check-email>';
            $headers = array(
                'Content-Type: application/x-www-form-urlencoded'
            );
            $httpRes = null;
            try {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_TIMEOUT, 25);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, 'xml=' . $xmlRequest);
                $httpRes = curl_exec($ch);
                if (!$httpRes) {
                    if (($httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE)) >= 400) {
                        curl_close($ch);
                        throw new Exception("Curl request to Eyepin email validation failed! HTTP response code was  '$httpResponseCode'.");
                    } else if (($errNo = curl_errno($ch))) {
                        $err = curl_error($ch);
                        curl_close($ch);
                        throw new Exception("Curl request to Eyepin email validation failed! Curl returned error '$err' (#$errNo).");
                    } else {
                        $info = curl_getinfo($ch);
                        curl_close($ch);
                        throw new Exception("Curl request to Eyepin email validation failed, no errno given! Curl info returned: " . print_r($info, true));
                    }
                }
                curl_close($ch);
                /** @var SimpleXMLElement $response */
                $response = new SimpleXMLElement($httpRes);
                if (!is_object($response->code) || (int)$response->code !== 2000) {
                    self::log("Check of email address $emailAddress failed. Resposne from Eyepin was: $httpRes");
                    return false;
                }
            } catch ( Exception $ex ) {
                self::logException($ex);
                if ( $httpRes !== null ) {
                    self::log("Eyepin response was:\n" . $httpRes);
                }
                $this->sendDeveloperMail('Eyepin email validation request failed in country',$ex->getMessage());
                return $this->emergencyFallback($emailAddress);
            }
        }

        return true;
    }

    public function emailExistsInCommenDB ( $emailAddress ) {
        $commonConn = Mage::getSingleton('core/resource')->getConnection('common_db');
        $query = "SELECT count(*) FROM login_token WHERE email LIKE ?";
        $queryResult = $commonConn->fetchOne($query,$emailAddress);
        return (int) $queryResult > 0;
    }

    private function emergencyFallback ( $emailAddress ) {
        self::log("using now emergency fallback to validate '$emailAddress'");
        if ( ! is_string($emailAddress) || ($len = strlen($emailAddress)) < 5 ) { // null or too short
            self::log("check 1 failed!");
            return false;
        }
        $firstAtPos = strpos($emailAddress,'@');
        $lastDotPos = strrpos($emailAddress,'.');
        if ( $firstAtPos === false || $lastDotPos == false  ) { // no '@' or no '.'
            self::log("check 2 failed!");
            return false;
        }
        if ( $firstAtPos == 0 || $lastDotPos == $len - 1 ) { // '@' on begin or '.' on end
            self::log("check 3 failed!");
            return false;
        }
        if ( $lastDotPos < $firstAtPos ) { // something like 'aaa.bbb@ccc'
            self::log("check 4 failed!");
            return false;
        }
        $lastAtPos = strrpos($emailAddress,'@');
        if ( $lastAtPos !== $firstAtPos ) { // 2 or more '@'
            self::log("check 5 failed!");
            return false;
        }
        self::log("all checks passed, email '$emailAddress' seems to be valid");
        return true;
    }

    public function sendDeveloperMail ( $subject, $message, $attachmentData = null, $attachmentFilename = null, $attachmentType = null ) {
        $mail = new Zend_Mail('utf-8');
        $countryCode = Mage::getStoreConfig('schrack/general/country');
        $to = Mage::getStoreConfig('schrackdev/alertmails/recipients');
        if ( strpos($to,',') !== false ) {
            $to = explode(',',$to);
        }
        $mail->setFrom(' shop@schrack.' . $countryCode, 'Yours Webshop :-)')
            ->setSubject($subject . ' ' . $countryCode)
            ->setBodyHtml($message)
            ->addTo($to);

        if ( $attachmentData != null && $attachmentFilename != null ) {
            $attachment = new Zend_Mime_Part($attachmentData);
            $attachment->type = $attachmentType ? $attachmentType : 'text/csv';
            $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
            $attachment->encoding = Zend_Mime::ENCODING_BASE64;
            $attachment->filename = $attachmentFilename;
            $mail->addAttachment($attachment);

        }

        $mail->send();
    }

    private static function logException ( $exception ) {
        Mage::logException($exception);
        self::log(get_class($exception) . ": " . $exception->getMessage() . " (see exception.log for stack trace)");
    }

    private static function log ( $string ) {
        Mage::log($string,null,'email_validation.log');
    }
}

