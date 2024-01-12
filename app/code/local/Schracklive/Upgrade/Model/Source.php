<?php

class Schracklive_Upgrade_Model_Source {

	protected $_tokens;
	protected $_filename;
	protected $_position;
	protected $_functions;

	public function __construct($source, $filename='') {
		if (is_array($source)) {
			$filename = $source[1];
			$source = $source[0];
		}
		$this->_tokens = token_get_all($source);
		$this->_filename = $filename;
	}

	public function listFunctions(array $functionNames=array()) {
		foreach ($this->getFunctions() as $functionName => $functionSource) {
			if (!$functionNames || in_array($functionName, $functionNames)) {
				echo $functionSource, "\n";
			}
		}
	}

	public function getFunctions() {
		if (!is_array($this->_functions)) {
			$this->_functions = $this->_extractFunctions();
		}
		return $this->_functions;
	}

	protected function _extractFunctions() {
		$functions = array();
		$n = count($this->_tokens);
		for ($this->_position = 0; $this->_position < $n; $this->_position++) {
			if (in_array($this->_tokens[$this->_position][0], array(T_FUNCTION, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC))) {
				try {
					$function = $this->_readFunction();
				} catch (RuntimeException $e) {
					// skip variable declaration
					if ($this->_tokens[$this->_position][0] != T_VARIABLE) {
						throw $e;
					}
					continue;
				}
				$functions[$function['name']] = $function['source'];
			}
		}
		$this->_position = NULL;
		return $functions;
	}

	protected function _readFunction() {
		$source = '';

		// get function declaration
		$source .= $this->_seekToToken(T_FUNCTION,
						array(T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT));
		// get function name
		$this->_position++;
		$source .= $this->_seekToToken(T_STRING, array(T_WHITESPACE, '&', T_COMMENT, T_DOC_COMMENT));
		$name = $this->_tokens[$this->_position][1];
		// get function arguments
		$this->_position++;
		$source .= $this->_collectBracedPart('(', ')');
		// get function body
		$this->_position++;

		// collapse all whitespace before { to a single space
		$this->_seekToToken('{', array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT));
		$source .= ' ';

		$source .= $this->_collectBracedPart('{', '}');

		return array('name' => $name, 'source' => $source);
	}

	protected function _seekToToken($findToken, array $skipTokens) {
		$source = '';
		$lineNumber = 0;
		do {
			// not an array for braces, commas, etc
			if (is_array($this->_tokens[$this->_position])) {
				$lineNumber = $this->_tokens[$this->_position][2];
			}
			if ($this->_tokens[$this->_position][0] == $findToken) {
				if (isset($this->_tokens[$this->_position][1])) {
					$source .= $this->_tokens[$this->_position][1];
				} else {
					$source .= $this->_tokens[$this->_position][0];
				}
				return $source;
			}
			if (!in_array($this->_tokens[$this->_position][0], $skipTokens)) {
				$tokenName = is_int($this->_tokens[$this->_position][0]) ? token_name($this->_tokens[$this->_position][0]) : $this->_tokens[$this->_position][0];
				throw new RuntimeException('Parse error - unexpected token '.$tokenName.' in '.$this->_filename.' at line '.$lineNumber);
			}
			if (isset($this->_tokens[$this->_position][1])) {
				$source .= $this->_tokens[$this->_position][1];
			} else {
				$source .= $this->_tokens[$this->_position][0];
			}
			$this->_position++;
		} while (isset($this->_tokens[$this->_position]));
		throw new RuntimeException('Parse error - unexpected end of file of '.$this->_filename);
	}

	protected function _collectBracedPart($openingBrace, $closingBrace) {
		$source = '';
		$source = $this->_seekToToken($openingBrace, array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT));
		$this->_position++;
		$curlyStackCounter = 0;
		do {
			if ($this->_tokens[$this->_position][0] == $closingBrace) {
				if ($curlyStackCounter == 0) {
					return $source.$closingBrace;
				} else {
					$curlyStackCounter--;
				}
			} elseif ($this->_tokens[$this->_position][0] == $openingBrace) {
				$curlyStackCounter++;
			}
			if (isset($this->_tokens[$this->_position][1])) {
				// @todo reformatting should not be hardcoded
				if ($this->_tokens[$this->_position][0] == T_WHITESPACE) {
					// 4 spaces => 1 tabulator
					$source .= str_replace('    ', "\t", $this->_tokens[$this->_position][1]);
				} else {
					$source .= $this->_tokens[$this->_position][1];
				}
			} else {
				$source .= $this->_tokens[$this->_position][0];
			}
			$this->_position++;
		} while (isset($this->_tokens[$this->_position]));
		throw new RuntimeException('Parse error - unexpected end of file'.$this->_filename);
	}

	public function generateClass(array $functionSources=array()) {
		$source = '';
		$n = count($this->_tokens);
		for ($this->_position = 0; $this->_position < $n; $this->_position++) {
			if (in_array($this->_tokens[$this->_position][0], array(T_FUNCTION, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC))) {
				$currentPosition = $this->_position;
				try {
					$function = $this->_readFunction();
				} catch (RuntimeException $e) {
					// read variable declaration
					if ($this->_tokens[$this->_position][0] != T_VARIABLE) {
						throw $e;
					}
					for ($i=$currentPosition; $i<=$this->_position; $i++) {
						if (isset($this->_tokens[$i][1])) {
							$source .= $this->_tokens[$i][1];
						} else {
							$source .= $this->_tokens[$i][0];
						}
					}
					continue;
				}
				if (isset($functionSources[$function['name']])) {
					$source .= $functionSources[$function['name']];
				} else {
					$source .= $function['source'];
				}
			} else {
				if (isset($this->_tokens[$this->_position][1])) {
					$source .= $this->_tokens[$this->_position][1];
				} else {
					$source .= $this->_tokens[$this->_position][0];
				}
			}
		}
		$this->_position = NULL;
		return $source;
	}

}

