<?php

class Schracklive_SchrackCore_Model_Jsonresponse extends Mage_Core_Model_Abstract {
    const STATUS_OK = 'OK';
    const STATUS_ERROR = 'ERROR';

    /**
     * @var string
     */
    private $_status;

    /**
     * @var array
     */
    private $_messages;

    /**
     * @var array
     */
    private $_errors;

    /**
     * @var string
     */
    private $_html;

    public function _construct() {
        parent::_construct();
        $this->_messages = array();
        $this->_errors = array();
    }

    public function setStatus($status) {
        if (!in_array($status, array(self::STATUS_OK, self::STATUS_ERROR))) {
            throw new Exception('No such status: ' . $status);
        }
        $this->_status = $status;
    }

    public function addMessage($msg) {
        $this->_messages[] = $msg;
    }

    public function addError($msg) {
        $this->_errors[] = $msg;
    }

    public function encode() {
        $obj = new StdClass();
        $obj->status = $this->_status;
        if (count($this->_messages) > 0) {
            $obj->messages = $this->_messages;
        }
        if (count($this->_errors) > 0) {
            $obj->errors = $this->_errors;
        }

        foreach ($this->getData() as $key => $value) {
            $obj->$key = $value;
        }

        return json_encode($obj);
    }

    public function encodeAndDie() {
        die($this->encode());
    }
} 