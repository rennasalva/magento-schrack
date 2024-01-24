<?php

class Schracklive_SchrackCore_Helper_Array {

	function arrayMergeRecursiveWithKeys() {
		$arrays = func_get_args();
		$base = array_shift($arrays);
		foreach ($arrays as $array) {
			reset($base); //important
			//while (list($key, $value) = @each($array)) {
            foreach ($array  as $key =>$value){
				if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
					$base[$key] = $this->arrayMergeRecursiveWithKeys($base[$key], $value);
				} else {
					$base[$key] = $value;
				}
			}
		}
		return $base;
	}
    
    /**
     * find the minimal value in the array, then return its index
     * 
     * @param array $arr
     * @throws Exception
     * @return index of minimum element with numeric comparison
     */
    public function findIndexOfMinValue(array &$arr) {
        $resI = null;
        for ($i=0; $i <= max(array_keys($arr)); ++$i)
            if (isset($arr[$i]) && ($resI === null || $arr[$resI] > $arr[$i]))
                $resI = $i;
        if ($resI === null)
            throw new Exception('Minimal element not found.');
        return $resI;
    }
    
    public function arrayDefault(&$array, $key, $default = null) {
        if (isset($array[$key]) && strlen($array[$key]))
            return $array[$key];
        else
            return $default;
    }
    
    /**
     * add many messages at once, converting them from string to message
     * @param Mage_Core_Model_Session_Abstract $session
     * @param array $strings
     */
    public function addSuccessesFromStrings(Mage_Core_Model_Session_Abstract $session, array &$strings) {
        foreach ($strings as $string) {
            $session->addSuccess($string);
        }
    }


}