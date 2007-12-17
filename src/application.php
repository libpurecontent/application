<?php

/*
 * Coding copyright Martin Lucas-Smith, University of Cambridge, 2003-7
 * Version 1.1.48
 * Distributed under the terms of the GNU Public Licence - www.gnu.org/copyleft/gpl.html
 * Requires PHP 4.1+ with register_globals set to 'off'
 * Download latest from: http://download.geog.cam.ac.uk/projects/application/
 */


# Ensure the pureContent framework is loaded and clean server globals
require_once ('pureContent.php');


# Class containing general application support static methods
class application
{
	# Constructor
	function application ($applicationName, $errors, $administratorEmail)
	{
		# Make inputs global
		$this->applicationName = $applicationName;
		$this->errors = $errors;
		$this->administratorEmail = $administratorEmail;
	}
	
	
	# Function to merge the arguments; note that $errors returns the errors by reference and not as a result from the method
	function assignArguments (&$errors, $suppliedArguments, $argumentDefaults, $functionName, $subargument = NULL, $handleErrors = false)
	{
		# Merge the defaults: ensure that arguments with a non-null default value are set (throwing an error if not), or assign the default value if none is specified
		$arguments = array ();
		foreach ($argumentDefaults as $argument => $defaultValue) {
			if (is_null ($defaultValue)) {
				if (!isSet ($suppliedArguments[$argument])) {
					$errors['absent' . ucfirst ($functionName) . ucfirst ($argument)] = "No '<strong>$argument</strong>' has been set in the '<strong>$functionName</strong>' specification.";
					$arguments[$argument] = $functionName;
				} else {
					$arguments[$argument] = $suppliedArguments[$argument];
				}
				
			# If a subargument is supplied, deal with subarguments
			} elseif ($subargument && ($argument == $subargument)) {
				foreach ($defaultValue as $subArgument => $subDefaultValue) {
					if (is_null ($subDefaultValue)) {
						if (!isSet ($suppliedArguments[$argument][$subArgument])) {
							$errors['absent' . ucfirst ($fieldType) . ucfirst ($argument) . ucfirst ($subArgument)] = "No '<strong>$subArgument</strong>' has been set for a '<strong>$argument</strong>' argument in the $fieldType specification.";
							$arguments[$argument][$subArgument] = $fieldType;
						} else {
							$arguments[$argument][$subArgument] = $suppliedArguments[$argument][$subArgument];
						}
					} else {
						$arguments[$argument][$subArgument] = (isSet ($suppliedArguments[$argument][$subArgument]) ? $suppliedArguments[$argument][$subArgument] : $subDefaultValue);
					}
				}
				
			# Otherwise assign argument as normal
			} else {
				$arguments[$argument] = (isSet ($suppliedArguments[$argument]) ? $suppliedArguments[$argument] : $defaultValue);
			}
		}
		
		# Handle the errors directly if required if any arise
		if ($handleErrors) {
			if ($errors) {
				echo application::showUserErrors ($errors);
				return false;
			}
		}
		
		# Return the arguments
		return $arguments;
	}
	
	
	# Function to deal with errors
	function throwError ($number, $diagnosisDetails = '')
	{
		# Define the default error message if the specified error number does not exist
		$errorMessage = (isSet ($this->errors[$number]) ? $this->errors[$number] : "A strange yet unknown error (#$number) has occurred.");
		
		# Show the error message
		$userErrors[] = 'Error: ' . $errorMessage . ' The administrator has been notified of this problem.';
		echo application::showUserErrors ($userErrors);
		
		# Assemble the administrator's error message
		if ($diagnosisDetails != '') {$errorMessage .= "\n\nFurther information available: " . $diagnosisDetails;}
		
		# Mail the admininistrator
		$subject = '[' . ucfirst ($this->applicationName) . '] error';
		$message = 'The ' . $this->applicationName . " has an application error: please investigate. Diagnostic details are given below.\n\nApplication error $number:\n" . $errorMessage;
		application::sendAdministrativeAlert ($this->administratorEmail, $this->applicationName, $subject, $message);
	}
	
	
	# Generalised support function to display errors
	function showUserErrors ($errors, $parentTabLevel = 0, $heading = '', $nested = false)
	{
		# Convert the error(s) to an array if it is not already
		$errors = application::ensureArray ($errors);
		
		# Build up a list of errors if there are any
		$html = '';
		if (count ($errors) > 0) {
			if (!$nested) {$html .= "\n" . str_repeat ("\t", ($parentTabLevel)) . '<div class="error">';}
			if ($heading != '') {$html .= ((!$nested) ? "\n" . str_repeat ("\t", ($parentTabLevel + 1)) . '<p>' : '') . $heading . ((!$nested) ? '</p>' : '');}
			$html .= "\n" . str_repeat ("\t", ($parentTabLevel + 1)) . '<ul>';
			foreach ($errors as $error) {
				$html .= "\n" . str_repeat ("\t", ($parentTabLevel + 2)) . '<li>' . $error . '</li>';
			}
			$html .= "\n" . str_repeat ("\t", ($parentTabLevel + 1)) . '</ul>';
			if (!$nested) {$html .= "\n" . str_repeat ("\t", ($parentTabLevel)) . '</div>' . "\n";}
		}
		
		# Return the result
		return $html;
	}
	
	
	# Function to get the base URL (non-slash terminated)
	function getBaseUrl ()
	{
		# Return the value
		return dirname (ereg_replace ("^{$_SERVER['DOCUMENT_ROOT']}", '', $_SERVER['SCRIPT_FILENAME']));
	}
	
	
	# Function to send an HTTP header such as a 404; note that output buffering must have been switched on at server level
	function sendHeader ($statusCode, $location = false)
	{
		# Select the appropriate header
		switch ($statusCode) {
			
			case '301':
				header ('HTTP/1.1 301 Moved Permanently');
				header ("Location: {$location}");
				break;
				
			case '302':
				header ("Location: {$location}");
				break;
				
			case '404':
				header ('HTTP/1.0 404 Not Found');
				break;
				
			case '410':
				header ('HTTP/1.0 410 Gone');
				break;
		}
	}
	
	
	# Generalised support function to allow quick dumping of form data to screen, for debugging purposes
	function dumpData ($data, $hide = false, $return = false)
	{
		# Start the HTML
		$html = '';
		
		# Show the data
		if ($hide) {$html .= "\n<!--";}
		$html .= "\n" . '<pre class="debug">DEBUG: ';
		if (is_array ($data)) {
			$html .= print_r ($data, true);
		} else {
			$html .= $data;
		}
		$html .= "\n</pre>";
		if ($hide) {$html .= "\n-->";}
		
		# Return or show the HTML
		if (!$return) {
			echo $html;
		} else {
			return $html;
		}
	}
	
	
	# Function to present an array with arrows (like print_r but better formatted)
	function printArray ($array)
	{
		# If the input is not an array, convert it
		$array = application::ensureArray ($array);
		
		# Loop through each item
		$hash = array ();
		foreach ($array as $key => $value) {
			if ($value === false) {$value = '0';}
			$hash[] = "$key => $value";
		}
		
		# Assemble the text as a single string
		$text = implode (",\n", $hash);
		
		# Return the text
		return $text;
	}
	
	
	# Function to check whether all elements of an array are empty
	function allArrayElementsEmpty ($array)
	{
		# Ensure the variable is an array if not already
		$array = application::ensureArray ($array);
		
		# Return false if a non-empty value is found
		foreach ($array as $key => $value) {
			if (!empty ($value)) {
				return false;
			}
		}
		
		# Return true if no values have been found
		return true;
	}
	
	
	# Generalised support function to ensure a variable is an array
	function ensureArray ($variable)
	{
		# If the initial value is empty, convert it to an empty array
		if ($variable == '') {$variable = array ();}
		
		# Convert the initial value(s) to an array if it is not already
		if (!is_array ($variable)) {
			$temporaryArray = $variable;
			unset ($variable);
			$variable[] = $temporaryArray;
		}
		
		# Return the array
		return $variable;
	}
	
	
	# Function to check whether an array is associative, i.e. whether any keys are not numeric
	function isAssociativeArray ($array)
	{
		# Return false if not an array
		if (!is_array ($array)) {return false;}
		
		# Loop through each and return true if any non-integer keys are found
		foreach ($array as $key => $value) {
			if (!is_int ($key)) {
				return true;
			}
		}
		
		# Otherwise return false as all keys are numeric
		return false;
	}
	
	
	# Function to determine if an array is multidimensional; returns 1 if all are multidimensional, 0 if not at all, -1 if mixed
	function isMultidimensionalArray ($array)
	{
		# Return NULL if not an array
		if (!is_array ($array)) {return NULL;}
		
		# Loop through the array and find cases where the elements are multidimensional or non-multidimensional
		$multidimensionalFound = false;
		$nonMultidimensionalFound = false;
		foreach ($array as $key => $value) {
			if (is_array ($value)) {
				$multidimensionalFound = true;
			} else {
				$nonMultidimensionalFound = true;
			}
		}
		
		# Return the outcome
		if ($multidimensionalFound && $nonMultidimensionalFound) {return -1;}	// Mixed array (NB: a check for if(-1) evaluates to TRUE)
		if ($multidimensionalFound) {return 1;}	// All elements multi-dimensional
		if ($nonMultidimensionalFound) {return 0;}	// Non-multidimensional
	}
	
	
	# Iterative function to ensure a hierarchy of values (for either a simple array or a one-level multidimensional array) is arranged associatively
	function ensureValuesArrangedAssociatively ($originalValues, $forceAssociative, $canIterateFurther = true)
	{
		# Loop through each value and determine whether the non-multidimensional elements should be treated as associative or not
		$scalars = array ();
		foreach ($originalValues as $key => $value) {
			if (!is_array ($value)) {
				$scalars[$key] = $value;
			}
		}
		$scalarsAreAssociative = ($forceAssociative || application::isAssociativeArray ($scalars));
		
		# Loop through each value
		$values = array ();
		foreach ($originalValues as $key => $value) {
			
			# If the value is an array but further iteration is disallowed, return false
			#!# This could be supported if iteratively applied and then display is supported higher up in the class hierarchy
			if (is_array ($value) && !$canIterateFurther) {return false;}
			
			# If the value is not an array, assign the index or the value to be used as the key, and add the value to the array
			if (!is_array ($value)) {
				$key = ($scalarsAreAssociative ? $key : $value);
				$values[$key] = $value;
			} else {
				
				# For an array, iterate to obtain the values, carrying back any thrown error
				if (!$value = application::ensureValuesArrangedAssociatively ($value, $forceAssociative, false)) {
					return false;
				}
			}
			
			# Add the value (or array of subvalues) to the array, in the same structure
			$values[$key] = $value;
		}
		
		# Return the values
		return $values;
	}
	
	
	# Function to flatten a one-level multidimensional array
	#!# This could be made properly iterative
	function flattenMultidimensionalArray ($values)
	{
		# Arrange the values as a simple associative array
		foreach ($values as $key => $value) {
			if (!is_array ($value)) {
				$flattenedValues[$key] = $value;
			} else {
				foreach ($value as $subKey => $subValue) {
					$flattenedValues[$subKey] = $subValue;
				}
			}
		}
		
		# Return the flattened version
		return $flattenedValues;
	}
	
	
	/*
	# Function to get the longest key name length in an array
	function longestKeyNameLength ($array)
	{
		# Assign 0 as the initial longest length
		$longestLength = 0;
		
		# Loop through each array item and reassign the longest length if it's longer
		foreach ($array as $key => $data) {
			$keyLength = strlen ($key);
			if ($keyLength > $longestLength) {
				$longestLength = $keyLength;
			}
		}
		
		# Return the value
		return $longestLength;
	}
	*/
	
	
	# String highlighting, based on http://aidanlister.com/repos/v/function.str_highlight.php
	function str_highlight ($text, $needle, $forceWordBoundaryIfNo = false)
	{
		# Default highlighting
		$highlight = '<strong>\1</strong>';
		
		# Pattern
		$pattern = '#(%s)#';
		
		# Apply case insensitivity
		$pattern .= 'i';
		
		# Escape characters
		$needle = preg_quote ($needle);
		
		# Escape needle with whole word check
		if (!$forceWordBoundaryIfNo || ($forceWordBoundaryIfNo && !substr_count ($needle, $forceWordBoundaryIfNo))) {
			$needle = '\b' . $needle . '\b';
		}
		
		# Perform replacement
		$regex = sprintf ($pattern, $needle);
		$text = preg_replace ($regex, $highlight, $text);
		
		# Return the text
		return $text;
	}
	
	
	# Function to return the start,previous,next,end items in an array
	function getPositions ($keys, $item)
	{
		# Reindex the keys
		$new = array ();
		$i = 0;
		foreach ($keys as $key => $value) {
			$new[$i] = $value;
			$i++;
		}
		$keys = $new;
		
		# Ensure that the value exists in the array
		if (!in_array ($item, $keys)) {
			return NULL;
		}
		
		# Get the index position of the current value
		foreach ($keys as $key => $value) {
			if ($value == $item) {
				$index['current'] = (int) $key;
				break;
			}
		}
		
		# Assign the index positions of the other types
		$index['previous'] = (array_key_exists (($index['current'] - 1), $keys) ? ($index['current'] - 1) : NULL);
		$index['next'] = (array_key_exists (($index['current'] + 1), $keys) ? ($index['current'] + 1) : NULL);
		$index['start'] = 0;
		$index['end'] =  count ($keys) - 1;
		
		# Change the index with the actual value
		$result = array ();
		foreach ($index as $type => $position) {
			$result[$type] = ($position !== NULL ? $keys[$position] : NULL);
		}
		
		# Return the result
		return $result;
	}
	
	
	# Function to ksort an array recursively
	function ksortRecursive (&$array)
	{
		ksort ($array);
		$keys = array_keys ($array);
		foreach ($keys as $key) {
			if (is_array ($array[$key])) {
				application::ksortRecursive ($array[$key]);
			}
		}
	}
	
	
	# Function to natsort an array by key; note that this does not return by reference (unlike natsort)
	function knatsort ($array)
	{
		$keys = array_keys ($array);
		natsort ($keys);
		$items = array ();
		foreach ($keys as $key) {
			$items[$key] = $array[$key];
		}
		
		# Return the sorted list
		return $items;
	}
	
	
	# Function to trim all values in an array; recursive values are also handled
	function arrayTrim ($array, $trimKeys = false)
	{
		# Return the value if not an array
		if (!is_array ($array)) {return $array;}
		
		# Loop and replace
		$cleanedArray = array ();
		foreach ($array as $key => $value) {
			
			# Deal with recursive arrays
			if (is_array ($value)) {$value = application::arrayTrim ($value);}
			
			# Trim the key if requested
			if ($trimKeys) {$key = trim ($key);}
			
			# Trim value
			$cleanedArray[$key] = trim ($value);
		}
		
		# Return the new array
		return $cleanedArray;
	}
	
	
	# Function to get the name of the nth key in an array (first is 1, not 0)
	function arrayKeyName ($array, $number = 1, $multidimensional = false)
	{
		# Convert to multidimensional if not already
		if (!$multidimensional) {
			$dataset[] = $array;
		}
		
		# Loop through the multidimensional array
		foreach ($array as $index => $data) {
			
			# Return false if not an array
			if (!is_array ($data)) {return $array;}
			
			# Ensure the number is not greater than the number of keys
			$totalFields = count ($data);
			if ($number > $totalFields) {return false;}
			
			# Loop through the data and construct
			$i = 0;
			foreach ($data as $key => $value) {
				$i++;
				if ($i == $number) {
					return $key;
				}
			}
		}
	}
	
	
	# Function to return a correctly supplied URL value
	function urlSuppliedValue ($urlArgumentKey, $available)
	{
		# If the $urlArgumentKey is defined in the URL and it exists in the list of available items, return its value
		if (isSet ($_GET[$urlArgumentKey])) {
			if (in_array ($_GET[$urlArgumentKey], $available)) {
				return $_GET[$urlArgumentKey];
			}
		}
		
		# Otherwise return an empty string
		return '';
	}
	
	
	# Function to clean up text
	function cleanText ($record, $htmlEntitiesConversion = true)
	{
		# Define conversions
		$convertFrom = "\x82\x83\x84\x85\x86\x87\x89\x8a\x8b\x8c\x8e\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9a\x9b\x9c\x9e\x9f";
		$convertTo = "'f\".**^\xa6<\xbc\xb4''\"\"---~ \xa8>\xbd\xb8\xbe";
		
		# If not an array, clean the item
		#!# Convert to using htmlentitiesArrayRecursive
		if (!is_array ($record)) {
			$record = strtr ($record, $convertFrom, $convertTo);
			if ($htmlEntitiesConversion) {$record = htmlentities ($record, ENT_COMPAT, 'UTF-8');}
		} else {
			
			# If an array, clean each item
			foreach ($record as $name => $details) {
				$record[$name] = strtr ($details, $convertFrom, $convertTo);
				if ($htmlEntitiesConversion) {$record[$name] = htmlentities ($record[$name], ENT_COMPAT, 'UTF-8');}
			}
		}
		
		# Return the record
		return $record;
	}
	
	
	# Recursive function to do htmlentities conversion through a tree
	function htmlentitiesArrayRecursive ($array, $convertKeys = true)
	{
		# Loop through the array and convert both key and value to entity-safe characters
		foreach ($array as $key => $value) {
			if ($convertKeys) {$key = htmlentities ($key, ENT_COMPAT, 'UTF-8');}
			$value = (is_array ($value) ? application::htmlentitiesArrayRecursive ($value) : str_replace ('', '&Egrave;', $value));
			$cleanedArray[$key] = $value;
		}
		
		# Return the cleaned array
		return $cleanedArray;
	}
	
	
	# Unicode, numeric entity conversion, basically a hack because PHP doesn't support UTF8 in get_html_translation_table(); from http://uk3.php.net/manual/en/function.htmlentities.php#54927 and http://uk2.php.net/manual/en/function.get-html-translation-table.php#76564, http://radekhulan.cz/item/php-script-to-convert-x-html-entities-to-decimal-unicode-representation/category/apache-php
	function htmlentitiesNumericUnicode ($input)
	{
		$input = htmlentities ($input, ENT_COMPAT, 'UTF-8');
		$htmlEntities = array_values (get_html_translation_table (HTML_ENTITIES, ENT_COMPAT));
		$htmlEntities[chr(130)] = '&sbquo;';    // Single Low-9 Quotation Mark
		$htmlEntities[chr(131)] = '&fnof;';    // Latin Small Letter F With Hook
		$htmlEntities[chr(132)] = '&bdquo;';    // Double Low-9 Quotation Mark
		$htmlEntities[chr(133)] = '&hellip;';    // Horizontal Ellipsis
		$htmlEntities[chr(134)] = '&dagger;';    // Dagger
		$htmlEntities[chr(135)] = '&Dagger;';    // Double Dagger
		$htmlEntities[chr(136)] = '&circ;';    // Modifier Letter Circumflex Accent
		$htmlEntities[chr(137)] = '&permil;';    // Per Mille Sign
		$htmlEntities[chr(138)] = '&Scaron;';    // Latin Capital Letter S With Caron
		$htmlEntities[chr(139)] = '&lsaquo;';    // Single Left-Pointing Angle Quotation Mark
		$htmlEntities[chr(140)] = '&OElig;    ';    // Latin Capital Ligature OE
		$htmlEntities[chr(145)] = '&lsquo;';    // Left Single Quotation Mark
		$htmlEntities[chr(146)] = '&rsquo;';    // Right Single Quotation Mark
		$htmlEntities[chr(147)] = '&ldquo;';    // Left Double Quotation Mark
		$htmlEntities[chr(148)] = '&rdquo;';    // Right Double Quotation Mark
		$htmlEntities[chr(149)] = '&bull;';    // Bullet
		$htmlEntities[chr(150)] = '&ndash;';    // En Dash
		$htmlEntities[chr(151)] = '&mdash;';    // Em Dash
		$htmlEntities[chr(152)] = '&tilde;';    // Small Tilde
		$htmlEntities[chr(153)] = '&trade;';    // Trade Mark Sign
		$htmlEntities[chr(154)] = '&scaron;';    // Latin Small Letter S With Caron
		$htmlEntities[chr(155)] = '&rsaquo;';    // Single Right-Pointing Angle Quotation Mark
		$htmlEntities[chr(156)] = '&oelig;';    // Latin Small Ligature OE
		$htmlEntities[chr(159)] = '&Yuml;';    // Latin Capital Letter Y With Diaeresis
		$entitiesDecoded = array_keys (get_html_translation_table (HTML_ENTITIES, ENT_COMPAT));
		$num = count ($entitiesDecoded);
		for ($u = 0; $u < $num; $u++) {
			$utf8Entities[$u] = '&#'.ord($entitiesDecoded[$u]).';';
		}
		$input = str_replace ($htmlEntities, $utf8Entities, $input);
		
		$entity_to_decimal = array(
			'&nbsp;' => '&#160;',
			'&iexcl;' => '&#161;',
			'&cent;' => '&#162;',
			'&pound;' => '&#163;',
			'&curren;' => '&#164;',
			'&yen;' => '&#165;',
			'&brvbar;' => '&#166;',
			'&sect;' => '&#167;',
			'&uml;' => '&#168;',
			'&copy;' => '&#169;',
			'&ordf;' => '&#170;',
			'&laquo;' => '&#171;',
			'&not;' => '&#172;',
			'&shy;' => '&#173;',
			'&reg;' => '&#174;',
			'&macr;' => '&#175;',
			'&deg;' => '&#176;',
			'&plusmn;' => '&#177;',
			'&sup2;' => '&#178;',
			'&sup3;' => '&#179;',
			'&acute;' => '&#180;',
			'&micro;' => '&#181;',
			'&para;' => '&#182;',
			'&middot;' => '&#183;',
			'&cedil;' => '&#184;',
			'&sup1;' => '&#185;',
			'&ordm;' => '&#186;',
			'&raquo;' => '&#187;',
			'&frac14;' => '&#188;',
			'&frac12;' => '&#189;',
			'&frac34;' => '&#190;',
			'&iquest;' => '&#191;',
			'&Agrave;' => '&#192;',
			'&Aacute;' => '&#193;',
			'&Acirc;' => '&#194;',
			'&Atilde;' => '&#195;',
			'&Auml;' => '&#196;',
			'&Aring;' => '&#197;',
			'&AElig;' => '&#198;',
			'&Ccedil;' => '&#199;',
			'&Egrave;' => '&#200;',
			'&Eacute;' => '&#201;',
			'&Ecirc;' => '&#202;',
			'&Euml;' => '&#203;',
			'&Igrave;' => '&#204;',
			'&Iacute;' => '&#205;',
			'&Icirc;' => '&#206;',
			'&Iuml;' => '&#207;',
			'&ETH;' => '&#208;',
			'&Ntilde;' => '&#209;',
			'&Ograve;' => '&#210;',
			'&Oacute;' => '&#211;',
			'&Ocirc;' => '&#212;',
			'&Otilde;' => '&#213;',
			'&Ouml;' => '&#214;',
			'&times;' => '&#215;',
			'&Oslash;' => '&#216;',
			'&Ugrave;' => '&#217;',
			'&Uacute;' => '&#218;',
			'&Ucirc;' => '&#219;',
			'&Uuml;' => '&#220;',
			'&Yacute;' => '&#221;',
			'&THORN;' => '&#222;',
			'&szlig;' => '&#223;',
			'&agrave;' => '&#224;',
			'&aacute;' => '&#225;',
			'&acirc;' => '&#226;',
			'&atilde;' => '&#227;',
			'&auml;' => '&#228;',
			'&aring;' => '&#229;',
			'&aelig;' => '&#230;',
			'&ccedil;' => '&#231;',
			'&egrave;' => '&#232;',
			'&eacute;' => '&#233;',
			'&ecirc;' => '&#234;',
			'&euml;' => '&#235;',
			'&igrave;' => '&#236;',
			'&iacute;' => '&#237;',
			'&icirc;' => '&#238;',
			'&iuml;' => '&#239;',
			'&eth;' => '&#240;',
			'&ntilde;' => '&#241;',
			'&ograve;' => '&#242;',
			'&oacute;' => '&#243;',
			'&ocirc;' => '&#244;',
			'&otilde;' => '&#245;',
			'&ouml;' => '&#246;',
			'&divide;' => '&#247;',
			'&oslash;' => '&#248;',
			'&ugrave;' => '&#249;',
			'&uacute;' => '&#250;',
			'&ucirc;' => '&#251;',
			'&uuml;' => '&#252;',
			'&yacute;' => '&#253;',
			'&thorn;' => '&#254;',
			'&yuml;' => '&#255;',
			'&fnof;' => '&#402;',
			'&Alpha;' => '&#913;',
			'&Beta;' => '&#914;',
			'&Gamma;' => '&#915;',
			'&Delta;' => '&#916;',
			'&Epsilon;' => '&#917;',
			'&Zeta;' => '&#918;',
			'&Eta;' => '&#919;',
			'&Theta;' => '&#920;',
			'&Iota;' => '&#921;',
			'&Kappa;' => '&#922;',
			'&Lambda;' => '&#923;',
			'&Mu;' => '&#924;',
			'&Nu;' => '&#925;',
			'&Xi;' => '&#926;',
			'&Omicron;' => '&#927;',
			'&Pi;' => '&#928;',
			'&Rho;' => '&#929;',
			'&Sigma;' => '&#931;',
			'&Tau;' => '&#932;',
			'&Upsilon;' => '&#933;',
			'&Phi;' => '&#934;',
			'&Chi;' => '&#935;',
			'&Psi;' => '&#936;',
			'&Omega;' => '&#937;',
			'&alpha;' => '&#945;',
			'&beta;' => '&#946;',
			'&gamma;' => '&#947;',
			'&delta;' => '&#948;',
			'&epsilon;' => '&#949;',
			'&zeta;' => '&#950;',
			'&eta;' => '&#951;',
			'&theta;' => '&#952;',
			'&iota;' => '&#953;',
			'&kappa;' => '&#954;',
			'&lambda;' => '&#955;',
			'&mu;' => '&#956;',
			'&nu;' => '&#957;',
			'&xi;' => '&#958;',
			'&omicron;' => '&#959;',
			'&pi;' => '&#960;',
			'&rho;' => '&#961;',
			'&sigmaf;' => '&#962;',
			'&sigma;' => '&#963;',
			'&tau;' => '&#964;',
			'&upsilon;' => '&#965;',
			'&phi;' => '&#966;',
			'&chi;' => '&#967;',
			'&psi;' => '&#968;',
			'&omega;' => '&#969;',
			'&thetasym;' => '&#977;',
			'&upsih;' => '&#978;',
			'&piv;' => '&#982;',
			'&bull;' => '&#8226;',
			'&hellip;' => '&#8230;',
			'&prime;' => '&#8242;',
			'&Prime;' => '&#8243;',
			'&oline;' => '&#8254;',
			'&frasl;' => '&#8260;',
			'&weierp;' => '&#8472;',
			'&image;' => '&#8465;',
			'&real;' => '&#8476;',
			'&trade;' => '&#8482;',
			'&alefsym;' => '&#8501;',
			'&larr;' => '&#8592;',
			'&uarr;' => '&#8593;',
			'&rarr;' => '&#8594;',
			'&darr;' => '&#8595;',
			'&harr;' => '&#8596;',
			'&crarr;' => '&#8629;',
			'&lArr;' => '&#8656;',
			'&uArr;' => '&#8657;',
			'&rArr;' => '&#8658;',
			'&dArr;' => '&#8659;',
			'&hArr;' => '&#8660;',
			'&forall;' => '&#8704;',
			'&part;' => '&#8706;',
			'&exist;' => '&#8707;',
			'&empty;' => '&#8709;',
			'&nabla;' => '&#8711;',
			'&isin;' => '&#8712;',
			'&notin;' => '&#8713;',
			'&ni;' => '&#8715;',
			'&prod;' => '&#8719;',
			'&sum;' => '&#8721;',
			'&minus;' => '&#8722;',
			'&lowast;' => '&#8727;',
			'&radic;' => '&#8730;',
			'&prop;' => '&#8733;',
			'&infin;' => '&#8734;',
			'&ang;' => '&#8736;',
			'&and;' => '&#8743;',
			'&or;' => '&#8744;',
			'&cap;' => '&#8745;',
			'&cup;' => '&#8746;',
			'&int;' => '&#8747;',
			'&there4;' => '&#8756;',
			'&sim;' => '&#8764;',
			'&cong;' => '&#8773;',
			'&asymp;' => '&#8776;',
			'&ne;' => '&#8800;',
			'&equiv;' => '&#8801;',
			'&le;' => '&#8804;',
			'&ge;' => '&#8805;',
			'&sub;' => '&#8834;',
			'&sup;' => '&#8835;',
			'&nsub;' => '&#8836;',
			'&sube;' => '&#8838;',
			'&supe;' => '&#8839;',
			'&oplus;' => '&#8853;',
			'&otimes;' => '&#8855;',
			'&perp;' => '&#8869;',
			'&sdot;' => '&#8901;',
			'&lceil;' => '&#8968;',
			'&rceil;' => '&#8969;',
			'&lfloor;' => '&#8970;',
			'&rfloor;' => '&#8971;',
			'&lang;' => '&#9001;',
			'&rang;' => '&#9002;',
			'&loz;' => '&#9674;',
			'&spades;' => '&#9824;',
			'&clubs;' => '&#9827;',
			'&hearts;' => '&#9829;',
			'&diams;' => '&#9830;',
			'&quot;' => '&#34;',
			'&amp;' => '&#38;',
			'&lt;' => '&#60;',
			'&gt;' => '&#62;',
			'&OElig;' => '&#338;',
			'&oelig;' => '&#339;',
			'&Scaron;' => '&#352;',
			'&scaron;' => '&#353;',
			'&Yuml;' => '&#376;',
			'&circ;' => '&#710;',
			'&tilde;' => '&#732;',
			'&ensp;' => '&#8194;',
			'&emsp;' => '&#8195;',
			'&thinsp;' => '&#8201;',
			'&zwnj;' => '&#8204;',
			'&zwj;' => '&#8205;',
			'&lrm;' => '&#8206;',
			'&rlm;' => '&#8207;',
			'&ndash;' => '&#8211;',
			'&mdash;' => '&#8212;',
			'&lsquo;' => '&#8216;',
			'&rsquo;' => '&#8217;',
			'&sbquo;' => '&#8218;',
			'&ldquo;' => '&#8220;',
			'&rdquo;' => '&#8221;',
			'&bdquo;' => '&#8222;',
			'&dagger;' => '&#8224;',
			'&Dagger;' => '&#8225;',
			'&permil;' => '&#8240;',
			'&lsaquo;' => '&#8249;',
			'&rsaquo;' => '&#8250;',
			'&euro;' => '&#8364;'
		);
		
		# Convert additional entities, replacing unknown with space for safety
		$input = preg_replace ('/&[A-Za-z]+;/', ' ', strtr ($input, $entity_to_decimal));
		
		# Return the result
		return $input;
	}
	
	
	# Function to format free text
	function formatTextBlock ($text, $paragraphClass = NULL)
	{
		# Remove any windows line breaks
		$text = str_replace ("\r\n", "\n", $text);
		
		# Perform the conversion
		$text = trim ($text);
		
		$text = str_replace ("\n\n", '</p><p' . ($paragraphClass ? " class=\"{$paragraphClass}\"" : '' ) .'>', $text);
		$text = str_replace ("\n", '<br />', $text);
		$text = str_replace (array ('</p>', '<br />'), array ("</p>\n", "<br />\n"), $text);
		$text = '<p' . ($paragraphClass ? " class=\"{$paragraphClass}\"" : '' ) .">$text</p>";
		
		# Return the text
		return $text;
	}
	
	
	# Generic function to convert a box with URL[whitespace]description lines to a list
	function urlReferencesBox ($string)
	{
		# Loop through each line
		$lines = explode ("\n", $string);
		foreach ($lines as $index => $line) {
			
			# Default to the line as-is
			$list[$index] = $line;
			
			# Explode by the first space (after the first URL) if it exists
			$parts = preg_split ("/[\s]+/", $line, 2);
			if (count ($parts) == 2) {
				$list[$index] = "<a href=\"{$parts[0]}\" target=\"_blank\">{$parts[1]}</a>";
			}
		}
		
		# Compile the list
		$html  = application::htmlUl ($list);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to rawurlencode a path but leave the / slash characters in tact
	function rawurlencodePath ($path)
	{
		# Do the encoding
		$encoded = implode ('/', array_map ('rawurlencode', explode ('/', $path)));
		
		# Return the encoded path
		return $encoded;
	}
	
	
	# Function to format a minimised URL (e.g. www.site.com/subdirectory rather than http://www.site.com/subdirectory/)
	function urlPresentational ($url)
	{
		# Trim whitespace
		$url = trim ($url);
		
		# Remove trailing slash if there is only one subdirectory (or none)
		if (substr_count ($url, '/') <= 4) {
			if (substr ($url, -1) == '/') {$url = substr ($url, 0, -1);}
		}
		
		# Remove http:// from the start if followed by www
		if (substr ($url, 0, 10) == 'http://www') {$url = substr ($url, 7);}
		
		# Replace %20 with a space
		$url = str_replace ('%20', ' ', $url);
		
		# Return the result
		return $url;
	}
	
	
	# Function to send administrative alerts
	function sendAdministrativeAlert ($administratorEmail, $applicationName, $subject, $message, $cc = false)
	{
		# Define standard e-mail headers
		$mailheaders = "From: $applicationName <" . $administratorEmail . ">\n";
		if ($cc) {$mailheaders .= "Cc: {$cc}\n";}
		
		# Send the message
		mail ($administratorEmail, $subject, wordwrap ($message), $mailheaders);
	}
	
	
	# Function to check that an e-mail address (or all addresses) are valid
	#!# Consider a more advanced solution like www.linuxjournal.com/article/9585 which is more RFC-compliant
	function validEmail ($email, $domainPartOnly = false)
	{
		# Define the regexp; regexp taken from www.zend.com/zend/spotlight/ev12apr.php
		$regexp = '^' . ($domainPartOnly ? '[@]?' : '[_a-z0-9\$-\+]+(\.[_a-z0-9\$-\+]+)*@') . '[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})$';
		
		# If not an array, perform the check and return the result
		if (!is_array ($email)) {
			return eregi ($regexp, $email);
		}
		
		# If an array, check each and return the flag
		$allValidEmail = true;
		foreach ($email as $value) {
			if (!eregi ($regexp, $value)) {
				$allValidEmail = false;
				break;
			}
		}
		return $allValidEmail;
	}
	
	
	# Function to provide a mail quoting algorithm
	function emailQuoting ($message, $quoteString = '> ')
	{
		# Start an array of lines to hold the quoted message
		$quotedMessage = array ();
		
		# Wordwrap the message
		$message = wordwrap ($message, (75 - strlen ($quoteString) - 1));
		
		# Explode the message and add quote marks
		$lines = explode ("\n", $message);
		foreach ($lines as $line) {
			$quotedMessage[] = $quoteString . $line;
		}
		
		# Reassemble the message
		$quotedMessage = implode ("\n", $quotedMessage);
		
		# Return the quoted message
		return $quotedMessage;
	}
	
	
	# Function to encode an e-mail address
	function encodeEmailAddress ($email)
	{
		# Return the string
		return str_replace ('@', '<span>&#64;</span>', $email);
	}
	
	
	# Function to make links clickable: from www.totallyphp.co.uk/code/convert_links_into_clickable_hyperlinks.htm
	function makeClickableLinks ($text, $addMailto = false, $replaceVisibleUrlWithText = false)
	{
		$text = eregi_replace ('(((ftp|http|https)://)[-a-zA-Z0-9@:%_\+.~#?&//=;]+)', '<a target="_blank" href="\\1">' . ($replaceVisibleUrlWithText ? $replaceVisibleUrlWithText : '\\1') . '</a>', $text);
		$text = eregi_replace ('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&//=;]+)', '\\1<a target="_blank" href="http://\\2">' . ($replaceVisibleUrlWithText ? $replaceVisibleUrlWithText : '\\2') . '</a>', $text);
		if ($addMailto) {$text = eregi_replace ('([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})',    '<a href="mailto:\\1">\\1</a>', $text);}
		return $text;
	}
	
	
	# Function to generate a password
	function generatePassword ($length = 6, $numeric = false)
	{
		# Generate a numeric password if that is what is required
		if ($numeric) {
			
			# Start a string and build up the password
			$password = '';
			for ($i = 0; $i < $length; $i++) {
				$password .= rand (0, 9);
			}
		} else {
			
			# Otherwise do an alphanumeric password
			$password = substr (md5 (time ()), 0, $length);
		}
		
		# Return the result
		return $password;
	}
	
	
	# Function to check that a URL-supplied activation key is valid
	function validPassword ($length = 6)
	{
		# Check that the URL contains an activation key string of exactly 6 lowercase letters/numbers
		return (preg_match ('/^[a-z0-9]{' . $length . '}$/D', (trim ($_SERVER['QUERY_STRING']))));
	}
	
	
	# Function to perform a very basic check whether a URL is valid
	#!# Consider developing this further
	function urlSyntacticallyValid ($url)
	{
		# Return true if the URL is valid following basic checks
		return eregi ('^(ftp|http|https)', $url);
	}
	
	
	# Function to determine whether a URL is internal to the site
	function urlIsInternal ($url)
	{
		# Return true if the full URL starts with the site URL
		return eregi ('^' . $_SERVER['_SITE_URL'], $url);
	}
	
	
	# This function extracts the title of the page in question by opening the first $startingCharacters characters of the file and extracting what's between the <$tag> tags
	function getTitleFromFileContents ($contents, $startingCharacters = 100, $tag = 'h1')
	{
		# Define the starting and closing tags
		$startingTag = "<$tag";
		$closingTag = "</$tag>";
		
		# Search through the contents case-insensitively
		$html = stristr ($contents, $startingTag);
		
		# Extract what is between the $startingTag and the $closingTag
		$title = '';
		$result = eregi ("($startingTag.+$closingTag)", $html, $temporary);
		if ($result) {
			eregi ("([^>]*$closingTag)", $temporary[0], $out);
			$title = trim ($out[0]); 
		}
		$title = str_replace ($closingTag, '', $title);
		
		# Send the title back as the result
		return $title;
	}
	
	
	# Function to dump data from an associative array to a table
	function htmlTable ($array, $tableHeadingSubstitutions = array (), $class = 'lines', $showKey = true, $uppercaseHeadings = false, $allowHtml = false, $showColons = false, $addCellClasses = false, $addRowKeys = false, $onlyFields = array ())
	{
		# Check that the data is an array
		if (!is_array ($array)) {return $html = "\n" . '<p class="warning">Error: the supplied data was not an array.</p>';}
		
		# Return nothing if no data
		if (empty ($array)) {return '';}
		
		# Assemble the data cells
		$dataHtml = '';
		foreach ($array as $key => $value) {
			if (!$value) {continue;}
			$headings = $value;
			$dataHtml .= "\n\t" . '<tr' . ($addRowKeys ? ' class="' . htmlentities ($key, ENT_COMPAT, 'UTF-8') . '"' : '') . '>';
			if ($showKey) {
				$dataHtml .= "\n\t\t" . ($addCellClasses ? "<td class=\"{$key}\">" : '<td>') . "<strong>{$key}</strong></td>";
			}
			$i = 0;
			foreach ($value as $valueKey => $valueData) {
				if ($onlyFields && !in_array ($valueKey, $onlyFields)) {continue;}	// Skip if not in the list of onlyFields if that is supplied
				$i++;
				$data = $array[$key][$valueKey];
				$dataHtml .= "\n\t\t" . ($i == 1 ? ($addCellClasses ? "<td class=\"{$valueKey} key\">" : '<td class="key">') : ($addCellClasses ? "<td class=\"{$valueKey}\">" : '<td>')) . application::encodeEmailAddress (!$allowHtml ? htmlentities ($data, ENT_COMPAT, 'UTF-8') : $data) . (($showColons && ($i == 1) && $data) ? ':' : '') . '</td>';
			}
			$dataHtml .= "\n\t" . '</tr>';
		}
		
		# Construct the heading HTML
		$headingHtml  = '';
		if ($tableHeadingSubstitutions !== false) {
			$headingHtml .= "\n\t" . '<tr>';
			if ($showKey) {$headingHtml .= "\n\t\t" ."<th></th>";}
			$columns = array_keys ($headings);
			foreach ($columns as $column) {
				if ($onlyFields && !in_array ($column, $onlyFields)) {continue;}	// Skip if not in the list of onlyFields if that is supplied
				$columnTitle = (empty ($tableHeadingSubstitutions) ? $column : (isSet ($tableHeadingSubstitutions[$column]) ? $tableHeadingSubstitutions[$column] : $column));
				$headingHtml .= "\n\t\t" . ($addCellClasses ? "<th class=\"{$column}\">" : '<th>') . ($uppercaseHeadings ? ucfirst ($columnTitle) : $columnTitle) . '</th>';
			}
			$headingHtml .= "\n\t" . '</tr>';
		}
		
		
		# Construct the overall heading
		$html  = "\n\n" . "<table class=\"$class\">";
		$html .= $headingHtml;
		$html .= $dataHtml;
		$html .= "\n" . '</table>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create a keyed HTML table; $dlFormat is PRIVATE and should not be used externally
	function htmlTableKeyed ($array, $keySubstitutions = array (), $omitEmpty = true, $class = 'lines', $allowHtml = false, $showColons = true, $dlFormat = false)
	{
		# Check that the data is an array
		if (!is_array ($array)) {return $html = "\n" . '<p class="warning">Error: the supplied data was not an array.</p>';}
		
		# Ensure key substitution is an array
		if (!$keySubstitutions) {$keySubstitutions = array ();}
		
		# Perform conversions
		foreach ($array as $key => $value) {
			
			# Skip keys in the array
			if ($keySubstitutions && array_key_exists ($key, $keySubstitutions) && $keySubstitutions[$key] === NULL) {
				unset ($array[$key]);
				continue;
			}
			
			# Omit empty or substitute for a string (as required) if there is no value
			if ($omitEmpty && !$value) {
				if (is_string ($omitEmpty)) {
					$array[$key] = $omitEmpty;
					$value = $omitEmpty;
				} else {
					unset ($array[$key]);
					continue;
				}
			}
		}
		
		# Return if no data
		if (!$array) {
			return false;
		}
		
		# Construct the table and add the data in
		$html  = "\n\n<" . ($dlFormat ? 'dl' : 'table') . " class=\"$class\">";
		foreach ($array as $key => $value) {
			if (!$dlFormat) {$html .= "\n\t" . '<tr>';}
			$html .= "\n\t\t" . ($dlFormat ? '<dt>' : "<td class=\"key\">") . (array_key_exists ($key, $keySubstitutions) ? $keySubstitutions[$key] : $key) . ($showColons && $key ? ':' : '') . ($dlFormat ? '</dt>' : '</td>');
			$html .= "\n\t\t" . ($dlFormat ? '<dd>' : "<td class=\"value\">") . (!$allowHtml ? nl2br (htmlentities ($value, ENT_COMPAT, 'UTF-8')) : $value) . ($dlFormat ? '</dd>' : '</td>');
			if (!$dlFormat) {$html .= "\n\t" . '</tr>';}
		}
		$html .= "\n" . ($dlFormat ? '</dl>' : '</table>');
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create a definition list
	function htmlDl ($array, $keySubstitutions = array (), $omitEmpty = true, $class = 'lines', $allowHtml = false, $showColons = true)
	{
		return application::htmlTableKeyed ($array, $keySubstitutions, $omitEmpty, $class, $allowHtml, $showColons, $dlFormat = true);
	}
	
	
	# Function to convert Unicode to ISO; see http://www.php.net/manual/en/function.mb-convert-encoding.php#78033
	function unicodeToIso ($string)
	{
		# Return the string without alteration if the multibyte extension is not present
		if (!function_exists ('mb_convert_encoding')) {return $string;}
		
		# Return the result
		return mb_convert_encoding ($string, 'ISO-8859-1', mb_detect_encoding ($string, 'UTF-8, ISO-8859-1, ISO-8859-15', true));
	}
	
	
	# Function to write data to a file (first creating it if it does not exist); returns true if successful or false if there was a problem
	function writeDataToFile ($data, $file, $unicodeToIso = true)
	{
		# Down-conversion from Unicode to (Excel-readable) ISO
		if ($unicodeToIso) {
			$data = application::unicodeToIso ($data);
		}
		
		# Use file_put_contents if using PHP5
		$isPhp5 = version_compare (PHP_VERSION, '5', '>=');
		if ($isPhp5) {
			return file_put_contents ($file, $data, FILE_APPEND);
		}
		
		# Attempt to open the file in read+write mode (in binary-safe mode, as recommended by the PHP developers) the actual results to it
		if (!$fileHandle = fopen ($file, 'a+b')) {
			return false;
		} else {
			
			# Attempt to write the data
			#!# For some reason this returns false just after the first time trying to create the file
			if (!fwrite ($fileHandle, $data)) {
				fclose ($fileHandle);
				return false;
			} else {
				
				# Close the successfully-written-to file and return a positive result
				fclose ($fileHandle);
				return true;
			}
		}
	}
	
	
	# Function to create a file based on a full path supplied
	function createFileFromFullPath ($file, $data, $addStamp = false)
	{
		# Determine the new file's directory location
		$newDirectory = dirname ($file);
		
		# Iteratively create the directory if it doesn't already exist
		if (!is_dir ($newDirectory)) {
			umask (0);
			if (strstr (PHP_OS, 'WIN')) {$newDirectory = str_replace ('/', '\\', $newDirectory);}
			if (!mkdir ($newDirectory, 0775, $recursive = true)) {
				#!# Consider better error handling here
				#echo "<p class=\"error\">There was a problem creating folders in the filestore.</p>";
				return false;
			}
		}
		
		# Add either '.old' (for '.old') or username.timestamp (for true) to the file if required
		#!# {$this->user is completely bogus but works when this method is being run from with an application which sets it}
		if ($addStamp) {
			$file .= ($addStamp === '.old' ? '.old' : '.' . date ('Ymd-His') . (isSet ($this->user) ? ".{$this->user}" : ''));
		}
		
		# Write the file
		#!# The @ is acceptable assuming all calling programs eventually log this problem somehow; it is put here because those which do will end up having errors thrown into the logs when they are actually being handled
		if (!@file_put_contents ($file, $data)) {
			#!# Consider better error handling here; the following line also removed
			#!# echo "<p class=\"error\">There was a problem creating a new file in the filestore.</p>";
			return false;
		}
		
		# Return the filename (which will equate to boolean true) if everything worked
		return $file;
	}
	
	
	# Function to check whether an area is writable; provides facilities additional to is_writable
	function directoryIsWritable ($root, $location = '/')
	{
		# If there is a trailing slash, remove it
		if (substr ($location, -1) == '/') {$location = substr ($location, 0, -1);}
		
		# Split the directories up
		$directories = explode ('/', $location);
		
		# Loop through the directories in the list
		while (count ($directories)) {
			
			# Re-compile the location
			$directory = $root . implode ('/', $directories);
			
			# If the directory exists, test for its writability; this will get called at least once because the root location will get tested at some point
			if (is_dir ($directory)) {
				if (!is_writable ($directory)) {
					return false;
				}
			}
			
			# Remove the last directory in the list
			array_pop ($directories);
		}
		
		# Otherwise return true
		return true;
	}
	
	
	# Function to create a case-insensitive version of in_array
	function iin_array ($needle, $haystack)
	{
		# Return true if the needle is in the haystack
		foreach ($haystack as $item) {
			if (strtolower ($item) == strtolower ($needle)) {
				return true;
			}
		}
		
		# Otherwise return false
		return false;
	}
	
	
	# Function to move an array item to the start
	function array_move_to_start ($array, $newFirstName)
	{
		# Check whether the array is associative
		if (application::isAssociativeArray ($array)) {
			
			# Extract the first item
			$firstItem[$newFirstName] = $array[$newFirstName];
			
			# Unset the item from the main array
			unset ($array[$newFirstName]);
			
			# Reinsert the item at the start of the main array
			$array = $firstItem + $array;
			
		# If not associative, loop through until the item is found, remove then reinsert it
		#!# This assumes there is only one instance in the array
		} else {
			foreach ($array as $key => $value) {
				if ($value == $newFirstName) {
					unset ($array[$key]);
					array_unshift ($array, $newFirstName);
					break;
				}
			}
		}
		
		# Return the reordered array
		return $array;
	}
	
	
	# Function to add an ordinal suffix to a number from 0-99 [from http://forums.devshed.com/t43304/s.html]
	function ordinalSuffix ($number)
	{
		# Obtain the last character in the number
		$last = substr ($number, -1);
		
		# Obtain the penultimate number
		if (strlen ($number) < 2) {
			$penultimate = 0;
		} else {
			$penultimate = substr ($number, -2);
		}
		
		# Assign the suffix
		if ($penultimate >= 10 && $penultimate < 20) {
			$suffix = 'th';
		} else if ($last == 1) {
			$suffix = 'st';
		} else if ($last == 2) {
			$suffix = 'nd';
		} else if ($last == 3) {
			$suffix = 'rd';
		} else {
			$suffix = 'th';
		}
		
		# Return the result
		return number_format ($number) . $suffix;
	}
	
	
	# Function to convert camelCase to standard text
	#!# Accept an array so it loops through all
	function changeCase ($string)
	{
		# Special case certain words
		$replacements = array (
			'url' => 'URL',
			'email' => 'e-mail',
		);
		
		# Perform the conversion; based on www.php.net/ucwords#49303
		$string = ucfirst ($string);
		$bits = preg_split ('/([A-Z])/', $string, false, PREG_SPLIT_DELIM_CAPTURE);
		$words = array ();
		array_shift ($bits);
		for ($i = 0; $i < count ($bits); ++$i) {
			if ($i % 2) {
				$word = strtolower ($bits[$i - 1] . $bits[$i]);
				$word = str_replace (array_keys ($replacements), array_values ($replacements), $word);
				$words[] = $word;
			}
		}
		
		# Compile the words
		$string = ucfirst (implode (' ', $words));
		
		# Return the string
		return $string;
	}
	
	
	# Function to convert an ini_ setting size to bytes
	function convertSizeToBytes ($string)
	{
		# Split the supplied size into a number and a unit
		$parts = array ();
		preg_match ('/^(\d+)([bkm]*)$/iD', $string, $parts);
		
		# Convert the size to a double and the unit to lower-case
		$size = (double) $parts[1];
		$unit = strtolower ($parts[2]);
		
		# Convert the number based on the unit
		switch ($unit) {
			case 'm':
				return ($size * (double) 1048576);
			case 'k':
				return ($size * (double) 1024);
			case 'b':
			default:
				return $size;
		}
	}
	
	
	# Function to regroup a data set into separate groups
	function regroup ($data, $regroupByColumn, $removeGroupColumn = true)
	{
		# Return the data unmodified if not an array or empty
		if (!is_array ($data) || empty ($data)) {return $data;}
		
		# Rearrange the data
		$rearrangedData = array ();
		foreach ($data as $key => $values) {
			$grouping = $values[$regroupByColumn];
			if ($removeGroupColumn) {
				unset ($data[$key][$regroupByColumn]);
			}
			$rearrangedData[$grouping][$key] = $data[$key];
		}
		
		# Return the data
		return $rearrangedData;
	}
	
	
	# Function to create an unordered HTML list
	function htmlUl ($array, $parentTabLevel = 0, $className = NULL, $ignoreEmpty = true, $sanitise = false, $nl2br = false, $liClass = false, $selected = false)
	{
		# Return an empty string if no items
		if (!is_array ($array) || empty ($array)) {return '';}
		
		# Prepare the tab string
		$tabs = str_repeat ("\t", ($parentTabLevel));
		
		# Build the list
		$html = "\n$tabs<ul" . ($className ? " class=\"$className\"" : '') . '>';
		foreach ($array as $key => $item) {
			
			# Skip an item if the item is empty and the flag is set to ignore these
			if (($ignoreEmpty) && (empty ($item))) {continue;}
			
			# Add the item to the HTML
			if ($sanitise) {$item = htmlentities ($item, ENT_COMPAT, 'UTF-8');}
			if ($nl2br) {$item = nl2br ($item);}
			
			# Determine a class
			$class = array ();
			if ($selected && ($selected == $key)) {$class[] = 'selected';}
			if ($liClass) {
				$class[] = ($liClass === true ? $key : $liClass);
			}
			$class = ($class ? ' class="' . implode (' ', $class) . '"' : '');
			
			# Assign the HTML
			$html .= "\n$tabs\t<li" . $class . '>' . $item . '</li>';
		}
		$html .= "\n$tabs</ul>";
		
		# Return the result
		return $html;
	}
	
	
	# Function to create an ordered HTML list
	function htmlOl ($array, $parentTabLevel = 0, $className = NULL, $ignoreEmpty = true, $sanitise = false, $nl2br = false, $liClass = false)
	{
		# Get the HTML as an unordered list
		$html = application::htmlUl ($array, $parentTabLevel = 0, $className = NULL, $ignoreEmpty = true, $sanitise = false, $nl2br = false, $liClass = false);
		
		# Convert to an ordered list
		$html = str_replace (array ('<ul', '</ul>'), array ('<ol', '</ol>'), $html);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to convert a hierarchy into a hierarchical list; third argument will produce level:text (if set to true) or will carry over text as textFrom0:textFrom1:textFrom2 ... as the link (if set to false)
	function htmlUlHierarchical ($unit, $class = 'pde', $carryOverQueryText = false, $lastOnly = true, $lowercaseLinks = true, $level = 0)
	{
		# Work out the tab HTML
		$tabs = str_repeat ("\t", $level);
		
		# Start the HTML
		$class = ($class ? " class=\"{$class}\"" : '');
		$html  = "\n{$tabs}<ul{$class}>";
		
		# Loop through each level, assembling either the query text or level:text as the link
		foreach ($unit as $name => $contents) {
			$last = $lastOnly && is_array ($contents) && (empty ($contents));
			$queryText = ($last ? '' : ($carryOverQueryText ? ($level != 0 ? $carryOverQueryText . ':' : '') : ($level + 1) . ':')) . str_replace (' ', '+', strtolower ($name));
			$link = ($last /*(substr ($name, 0, 1) != '<')*/ ? "<a href=\"{$this->baseUrl}/category/{$queryText}\">" : '');
			$html .= "\n\t{$tabs}<li>{$link}" . htmlentities ($name, ENT_COMPAT, 'UTF-8') . ($link ? '</a>' : '');
			if (is_array ($contents) && (!empty ($contents))) {
				$html .= application::htmlUlHierarchical ($contents, false, ($carryOverQueryText ? $queryText : false), $lastOnly, $lowercaseLinks, ($level + 1));
			}
			$html .= '</li>';
		}
		
		# Complete the HTML
		$html .= "\n{$tabs}</ul>";
		
		# Return the HTML
		return $html;
	}
	
	
	# Generalised support function to check whether a filename is valid given a list of disallowed and allowed extensions
	function filenameIsValid ($name, $disallowedExtensions = array (), $allowedExtensions = array ())
	{
		# Check whether it's a disallowed extension
		foreach ($disallowedExtensions as $disallowedExtension) {
			if (eregi ($disallowedExtension . '$', $name)) {
				return false;
			}
		}
		
		# Check whether it's an allowed extension if a list has been supplied
		if (!empty ($allowedExtensions)) {
			foreach ($allowedExtensions as $allowedExtension) {
				if (eregi ($allowedExtension . '$', $name)) {
					return true;
				}
			}
			
			# Otherwise return false if not found
			return false;
		}
		
		# Otherwise pass
		return true;
	}
	
	
	#!# Not finished - needs file handling
	# Wrapper function to get CSV data
	function getCsvData ($filename, $getHeaders = false, $assignKeys = false)
	{
		# Make sure the file exists
		if (!is_readable ($filename)) {return false;}
		
		# Open the file
		if (!$fileHandle = fopen ($filename, 'rb')) {return false;}
		
		# Get the column names
		if (!$mappings = fgetcsv ($fileHandle, filesize ($filename))) {return false;}
		
		# Start a counter if assigning keys
		if ($assignKeys) {$assignedKey = 0;}
		
		# Loop through each line of data
		$data = array ();
		while ($csvData = fgetcsv ($fileHandle, filesize ($filename))) {
			
			# Check the first item exists and set it as the row key then unset it
			if ($firstRowCell = $csvData[0]) {
				if (!$assignKeys) {unset ($csvData[0]);}
				
				# Determine the key name to use
				$rowKey = ($assignKeys ? $assignedKey++ : $firstRowCell);
				
				# Loop through each item of data
				foreach ($csvData as $key => $value) {
					
					# Assign the entry into the table
					if (isSet ($mappings[$key])) {$data[$rowKey][$mappings[$key]] = $value;}
				}
			}
		}
		
		# Close the file
		fclose ($fileHandle);
		
		# Return the result
		return $data;
	}
	
	
	# Wrapper function to turn a (possibly multi-dimensional) associative array into a correctly-formatted CSV format (including escapes)
	function arrayToCsv ($array, $delimiter = ',', $nestParent = false)
	{
		# Start an array of headers and the data
		$headers = array ();
		$data = array ();
		
		# Loop through each key value pair
		foreach ($array as $key => $value) {
			
			# If the associative array is multi-dimensional, iterate and thence add the sub-headers and sub-values to the array
			if (is_array ($value)) {
				list ($subHeaders, $subData) = application::arrayToCsv ($value, $delimiter, $key);
				
				# Merge the headers and subkeys
				$headers[] = $subHeaders;
				$data[] = $subData;
				
			# If the associative array is multi-dimensional, assign directly
			} else {
				
				# In nested mode, prepend the each key name with the parent name
				if ($nestParent) {$key = "$nestParent: $key";}
				
				# Add the key and value to arrays of the headers and data
				$headers[] = application::csvSafeDataCell ($key, $delimiter);
				$data[] = application::csvSafeDataCell ($value, $delimiter);
			}
		}
		
		# Compile the header and data lines, placing a delimeter between each item
		$headerLine = implode ($delimiter, $headers) . (!$nestParent ? "\n" : '');
		$dataLine = implode ($delimiter, $data) . (!$nestParent ? "\n" : '');
		
		# Return the result
		return array ($headerLine, $dataLine);
	}
	
	
	# Called function to make a data cell CSV-safe
	function csvSafeDataCell ($data, $delimiter = ',')
	{
		#!# Consider a flag for HTML entity cleaning so that e.g. " rather than &#8220; appears in Excel
		
		# Double any quotes existing within the data
		$data = str_replace ('"', '""', $data);
		
		# Strip carriage returns to prevent textarea breaks appearing wrongly in a CSV file opened in Windows in e.g. Excel
		$data = str_replace ("\r", '', $data);
		#!# Try replacing the above line with the more correct
		# $data = str_replace ("\r\n", "\n", $data);
		
		# If an item contains the delimiter or line breaks, surround with quotes
		if ((strpos ($data, $delimiter) !== false) || (strpos ($data, "\n") !== false) || (strpos ($data, '"') !== false)) {$data = '"' . $data . '"';}
		
		# Return the cleaned data cell
		return $data;
	}
	
	
	// create a cloud tag; based on comment posted under http://www.hawkee.com/snippet.php?snippet_id=1485
	function tagCloud ($tags, $classBase = 'tagcloud', $sizes = 5)
	{
		# End if no tags
		if (!$tags) {return false;}
		
		# Sort the tags
		asort ($tags);
		
		// Start with the sorted list of tags and divide by the number of font sizes (buckets). Then proceed to put an even number of tags into each bucket. The only restriction is that tags of the same count can't span 2 buckets, so some buckets may have more tags than others. Because of this, the sorted list of remaining tags is divided by the remaining 'buckets' to evenly distribute the remainder of the tags and to fill as many 'buckets' as possible up to the largest font size.
		$total_tags = count ($tags);
		$min_tags = $total_tags / $sizes;
		
		$bucket_count = 1;
		$bucket_items = 0;
		$tags_set = 0;
		foreach ($tags as $tag => $count) {
			
			// If we've met the minimum number of tags for this class and the current tag does not equal the last tag, we can proceed to the next class.
			if (($bucket_items >= $min_tags) && ($last_count != $count) && ($bucket_count < $sizes)) {
				$bucket_count++;
				$bucket_items = 0;
				
				// Calculate a new minimum number of tags for the remaining classes.
				$remaining_tags = $total_tags - $tags_set;
				$min_tags = $remaining_tags / $bucket_count;
			}
			
			// Set the tag to the current class.
			$finalised[$tag] = $classBase . $bucket_count;
			$bucket_items++;
			$tags_set++;
			
			$last_count = $count;
		}
		
		# Sort the list
		ksort ($finalised);
		
		# Return the list
		return $finalised;
	}
	
	
	# Function to enable pagination - based on www.phpnoise.com/tutorials/9/1
	function getPagerData ($items, $limit, $page)
	{
		# Take the number of items
		$items = (int) $items;
		
		# Ensure the limit is at least 1
		$limit = max ((int) $limit, 1);
		
		# Ensure the page is at least 1
		$page = max ((int) $page, 1);
		
		# Get the total number of pages (items divided by the number of pages, rounded up to catch the last (potentially incomplete) page)
		$totalPages = ceil ($items / $limit);
		
		# Ensure the page is no more than the number of pages
		$page = min ($page, $totalPages);
		
		# Define the offset, taking page 1 (rather than 0) as the first page
		$offset = ($page - 1) * $limit;
		
		# Return the result
		return array ($totalPages, $offset, $items, $limit, $page);
	}
	
	
	# Function to create a URL slug
	#!# Solution based on www.thinkingphp.org/2006/10/19/title-to-url-slug-conversion/ ; consider instead using something more like Wordpress' sanitize_title, as here: http://trac.wordpress.org/browser/trunk/wp-includes/functions-formatting.php?rev=1481
	function createUrlSlug ($string)
	{
		# Trim the string
		$string = trim ($string);
		
		# Lower-case the string
		$string = strtolower ($string);
		
		# Define the main conversions
		$unPretty = array ('/ä/', '/ö/', '/ü/', '/Ä/', '/Ö/', '/Ü/', '/ß/', '/\s?-\s?/', '/\s?_\s?/', '/\s?\/\s?/', '/\s?\\\s?/', '/\s/', '/"/', '/\'/', '/!/', '/\./');
		$pretty   = array ('ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', '-', '-', '-', '-', '-', '', '', '', '');
		$string = preg_replace ($unPretty, $pretty, $string);
		
		# Convert any remaining characters
		$string = preg_replace ('|[^a-z0-9-]|', '-', $string);
		
		# Replace double-hyphens
		while (substr_count ($string, '--')) {
			$string = str_replace ('--', '-', $string);
		}
		
		# Remove hyphens from the start or end
		$string = ereg_replace ('(^-|-$)', '', $string);
		
		# Return the value
		return $string;
	}
}


# Ensure that the file_put_contents function exists - taken from http://cvs.php.net/co.php/pear/PHP_Compat/Compat/Function/file_put_contents.php?r=1.9
if (!function_exists('file_put_contents'))
{
    function file_put_contents ($filename, $content)
    {
        $bytes = 0;

        if (($file = fopen($filename, 'w+')) === false) {
            return false;
        }

        if (($bytes = fwrite($file, $content)) === false) {
            return false;
        }

        fclose($file);

        return $bytes;
    }
}

?>
