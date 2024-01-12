<?php

class Schracklive_SchrackCatalog_Model_Import_Parser {

	/*
	Elements ant their children:
	propdef (propdefs)
	attrdef (attrdefs)
	value (values,attribute)
	url (urls)
	article (group)
	group (catalog,group)
	property (properties)
	? (references)
	 */

	function xml2array($contents, $getAttributes=true) {
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		if (!xml_parse_into_struct($parser, $contents, $xmlStruct)) {
            $errmsg = sprintf("XML parse error %d '%s' at line %d, column %d (byte index %d)",
                              xml_get_error_code($parser),
                              xml_error_string(xml_get_error_code($parser)),
                              xml_get_current_line_number($parser),
                              xml_get_current_column_number($parser),
                              xml_get_current_byte_index($parser));
            throw new RuntimeException("Invalid import data: ".$errmsg);
		}
		xml_parser_free($parser);

		if (!$xmlStruct) {
			return;
		}

		$xmlArray = array();
		$current = &$xmlArray;
		$parent = array();

		//Go through the tags.
		foreach ($xmlStruct as $data) {
			unset($attributes, $value); // Remove existing values, or there will be trouble

			// "extract" imports the array keys as variables into the current name space:
			// (string)tag, (string)type, (int)level, (string)value, (array)attributes.
			extract($data);

			$result = '';
			if ($getAttributes) { // The second argument of the function decides this.
				$result = array();
				if (isset($value)) $result['value'] = $value;

				// Set the attributes too.
				if (isset($attributes)) {
					foreach ($attributes as $attr => $val) {
						$result['attr'][$attr] = $val; // Set all the attributes in a array called 'attr'
						//  TODO: should we change the key name to '_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too
					}
				}
			} elseif (isset($value)) {
				$result = $value;
			}

			// See tag status and do the needed.
			if ($type == "open") { // Start of element '<tag>'
				$parent[$level - 1] = &$current;

				if (!is_array($current) or (!in_array($tag, array_keys($current)))) { // Insert New tag
					$current[$tag] = $result;
					$current = &$current[$tag];
				} else { // There was another element with the same tag name
					if (isset($current[$tag][0])) {
						array_push($current[$tag], $result);
					} else {
						$current[$tag] = array($current[$tag], $result);
					}
					$last = count($current[$tag]) - 1;
					$current = &$current[$tag][$last];
				}
			} elseif ($type == "complete") { // Empty element '<tag />'
				// See if the key is already taken.
				if (!isset($current[$tag])) { //New Key
					$current[$tag] = $result;
				} else { // If taken, put all things inside a list(array)
					if ((is_array($current[$tag]) and $getAttributes == 0) // If it is already an array...
							or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $getAttributes == 1)) {
						array_push($current[$tag], $result); // ...push the new element into that array.
					} else { // If it is not an array...
						$current[$tag] = array($current[$tag], $result); //...Make it an array using using the existing value and the new value
					}
				}
			} elseif ($type == 'close') { // End of element '</tag>'
				$current = &$parent[$level - 1];
			}
		}

		return($xmlArray);
	}

}
