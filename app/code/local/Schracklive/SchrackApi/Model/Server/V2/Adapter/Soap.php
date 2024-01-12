<?php

class Schracklive_SchrackApi_Model_Server_V2_Adapter_Soap extends Mage_Api_Model_Server_V2_Adapter_Soap {
    
    protected $doLog = false;
    protected $id = '(non)';
    protected $clientIp = '(unknown)';
    protected $request = null;
    
    public function __construct() {
        parent::__construct();
        $this->doLog = Mage::getStoreConfig('schrackdev/api_v2_soap/log');
    }
    
    protected function _getWsdlConfig() {
        // fixing bug in Magento 1.9 with unset handler on WSDL generation
        $this->setData('handler', 'Mage_Api_Model_Server_V2_Handler');
        return parent::_getWsdlConfig();
    }

    public function run()
    {
        $apiConfigCharset = Mage::getStoreConfig("api/config/charset");

        if ($this->getController()->getRequest()->getParam('wsdl') !== null) {
            $this->getController()->getResponse()
                ->clearHeaders()
                ->setHeader('Content-Type','text/xml; charset='.$apiConfigCharset)
                ->setBody(
                      preg_replace(
                        '/<\?xml version="([^\"]+)"([^\>]+)>/i',
                        '<?xml version="$1" encoding="'.$apiConfigCharset.'"?>',
                        $this->wsdlConfig->getWsdlContent()
                    )
                );
        } else {
            $content = null;
            if ( $this->doLog ) {
                $this->id = time();
                Mage::register('soap_request_id',$this->id);
                $this->clientIp = $this->getController()->getRequest()->getClientIp();
                $this->_logSoapRequest($this->_getRequest());
            }
            try {
                $this->_instantiateServer();

                $content = str_replace(
                    '><',
                    ">\n<",
                    preg_replace(
                        '/<\?xml version="([^\"]+)"([^\>]+)>/i',
                        '<?xml version="$1" encoding="' . $apiConfigCharset . '"?>',
                        $this->_soap->handle()
                    )
                );
                $this->getController()->getResponse()
                    ->clearHeaders()
                    ->setHeader('Content-Type', 'text/xml; charset=' . $apiConfigCharset)
                    ->setHeader('Content-Length', strlen($content), true)
                    ->setBody($content);
            } catch( Zend_Soap_Server_Exception $e ) {
                $this->_logSoapFault($e);
                $this->fault( $e->getCode(), $e->getMessage() );
            } catch( Exception $e ) {
                $this->_logSoapFault($e);
                $this->fault( $e->getCode(), $e->getMessage() );
            }
            $this->_logSoapResponse($content);
        }

        return $this;
    }
    
    private function _logSoapRequest($xml) {
        if ( $this->doLog )
            $this->_logSoap('request',$xml);
    }
    
    private function _logSoapResponse($xml) {
        if ( $this->doLog )
            $this->_logSoap('response',$xml);
    }
    
    private function _logSoapFault($exception) {
        if ( $this->doLog ) {
            $xml = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
                    <SOAP-ENV:Body>
                    <SOAP-ENV:Fault>
                    <faultcode>' . $exception->getCode() . '</faultcode>
                    <faultstring>' . $exception->getMessage() . '</faultstring>
                    </SOAP-ENV:Fault>
                    </SOAP-ENV:Body>
                    </SOAP-ENV:Envelope>';
            $this->_logSoap('response',$xml);
        }
    }
    
    public function _logSoap($direction,$xml) {
        if ( $this->doLog ) {
            $startDateTime = date('Y-m-d H:i:s T',time());
            $flieName = Mage::getBaseDir('var').DS.'log'.DS.'schracklive_soap_server_api_v2_'.$direction.'.log';
            $headerLine = $startDateTime.' id='.$this->id.', client IP ='.$this->clientIp.chr(10);
            $fileHandel = @fopen($flieName,'a');
            if ( $fileHandel ) {
                @fwrite($fileHandel, $headerLine);
                @fwrite($fileHandel, $xml);
                @fwrite($fileHandel, chr(10).chr(10));
                @fclose($fileHandel);
            }
        }
    }
    
    private function _getRequest () {
        if ( $this->request == null )
            $this->request = file_get_contents('php://input');
        return $this->request;
    }
}
?>
