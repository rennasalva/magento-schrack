<?php

class Schracklive_Schrack_Model_Abstract {

	protected function _checkArguments(array $arguments, array $argumentDefinitions) {
		$checkedArguments = array();
		reset($arguments);
		if (is_int(key($arguments))) {
			// anonynmous arguments (numeric array)
			$i = 0;
			foreach ($argumentDefinitions as $argumentName => $argumentDefinition) {
				if (is_int($argumentName)) {
					$checkedArguments[$i] = $this->_checkArgument($arguments, $i);
				} else {
					$checkedArguments[$argumentName] = $this->_checkArgument($arguments, $i, $argumentDefinition);
				}
				$i++;
			}
		} else {
			// named arguments (associative array)
			foreach ($argumentDefinitions as $argumentName => $argumentDefinition) {
				if (is_int($argumentName)) {
					$checkedArguments[$argumentDefinition] = $this->_checkArgument($arguments, $argumentDefinition);
				} else {
					$checkedArguments[$argumentName] = $this->_checkArgument($arguments, $argumentName, $argumentDefinition);
				}
			}
		}
		return $checkedArguments;
	}

	protected function _checkArgument(array $arguments, $argumentIndex, $argumentDefinition = null) {
		$hasDefault = false;
		if (is_array($argumentDefinition)) {
			list($type, $default) = $argumentDefinition;
			$hasDefault = true;
		} else {
			$type = $argumentDefinition;
		}
		if (!isset($arguments[$argumentIndex]) || $arguments[$argumentIndex] === '') {
			if ($hasDefault) {
				return $default;
			} else {
				throw new InvalidArgumentException('Argument '.$argumentIndex.' is missing.');
			}
		}
		if (in_array($type, array('array', 'scalar', 'int', 'float', 'bool', 'string', 'numeric', 'object', 'callable', 'resource'))) {
			$testFunction = 'is_'.$type;
			if (!$testFunction($arguments[$argumentIndex])) {
				throw new InvalidArgumentException('Argument '.$argumentIndex.' must be of type '.$type.', '.$this->_reportArgumentType($arguments, $argumentIndex).' given.');
			}
		} elseif (is_string($type)) {
			if (!($arguments[$argumentIndex] instanceof $type)) {
				throw new InvalidArgumentException('Argument '.$argumentIndex.' must be an instance of '.$type.', '.$this->_reportArgumentType($arguments, $argumentIndex).' given.');
			}
		} else {
			if (!is_null($type)) {
				throw new UnexpectedValueException('Unexpected type '.$type.' to check for argument '.$argumentIndex);
			}
		}
		return $arguments[$argumentIndex];
	}

	protected function _reportArgumentType($arguments, $argumentIndex) {
		$type = $this->_getArgumentType($arguments[$argumentIndex]);
		if (is_scalar($arguments[$argumentIndex])) {
			$type .= ' ('.$arguments[$argumentIndex].')';
		}
		return $type;
	}

	protected function _getArgumentType($argument) {
		return is_object($argument) ? get_class($argument) : gettype($argument);
	}

}
