<?php

class Schracklive_Mobile_Model_Document extends DOMDocument {

	public function __construct ($version=null, $encoding=null) {
		parent::__construct ($version, $encoding);
		$this->formatOutput = true;
	}

	public function createCdataElement($name, $value) {
		$element = $this->createElement($name);
		$element->appendChild($this->createCDATASection($value));

		return $element;
	}

}
