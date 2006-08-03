<?php

/*
 * Coding copyright Martin Lucas-Smith, University of Cambridge, 2003-6
 * Version 1.1.26
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
	function assignArguments (&$errors, $suppliedArguments, $argumentDefaults, $functionName, $subargument = NULL)
	{
		# Merge the defaults: ensure that arguments with a non-null default value are set (throwing an error if not), or assign the default value if none is specified
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
			
			case '302':
				header ("Location: $location");
				break;
				
			case '404':
				header ('HTTP/1.0 404 Not Found');
				break;
		}
	}
	
	
	# Generalised support function to allow quick dumping of form data to screen, for debugging purposes
	function dumpData ($data, $hide = false)
	{
		# Show the data
		if ($hide) {echo "\n<!--";}
		echo "\n" . '<pre class="debug">DEBUG: ';
		if (is_array ($data)) {
			print_r ($data);
		} else {
			echo $data;
		}
		echo "\n</pre>";
		if ($hide) {echo "\n-->";}
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
	
	
	# Function to return the start,previous,next,end items in an array
	function getPositions ($keys, $item)
	{
		# Ensure that the value exists in the array
		if (!in_array ($item, $keys)) {
			return NULL;
		}
		
		# Move to the position in the array of the current item
		foreach ($keys as $key => $value) {
			if ($value == $item) {
				break;
			}
		}
		if (!current ($keys)) {
			end ($keys);
		} else {
			prev ($keys);
		}
		
		# Get the previous item in the array (then move back), resetting to the start if there is no previous
		if (!$position['previous'] = prev ($keys)) {
			reset ($keys);
		} else {
			next ($keys);
		}
		
		# Assign the next item in the array (not bothering to move back) and other positions
		$position['next'] = next ($keys);
		$position['start'] = reset ($keys);
		$position['end'] = end ($keys);
		
		# Return the position
		return $position;
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
	function cleanText ($record)
	{
		# Define conversions
		$convertFrom = "\x82\x83\x84\x85\x86\x87\x89\x8a\x8b\x8c\x8e\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9a\x9b\x9c\x9e\x9f";
		$convertTo = "'f\".**^\xa6<\xbc\xb4''\"\"---~ \xa8>\xbd\xb8\xbe";
		
		# If not an array, clean the item
		#!# Convert to using htmlentitiesArrayRecursive
		if (!is_array ($record)) {
			$record = htmlentities (strtr ($record, $convertFrom, $convertTo));
		} else {
			
			# If an array, clean each item
			foreach ($record as $name => $details) {
				$record[$name] = htmlentities (strtr ($details, $convertFrom, $convertTo));
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
			if ($convertKeys) {$key = htmlentities ($key);}
			$value = (is_array ($value) ? application::htmlentitiesArrayRecursive ($value) : str_replace ('È', '&Egrave;', $value));
			$cleanedArray[$key] = $value;
		}
		
		# Return the cleaned array
		return $cleanedArray;
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
	function sendAdministrativeAlert ($administratorEmail, $applicationName, $subject, $message)
	{
		# Define standard e-mail headers
		$mailheaders = "From: $applicationName <" . $administratorEmail . '>';
		
		# Send the message
		mail ($administratorEmail, $subject, wordwrap ($message), $mailheaders);
	}
	
	
	# Function to check that an e-mail address (or all addresses) are valid
	function validEmail ($email, $domainPartOnly = false)
	{
		# Define the regexp; regexp taken from www.zend.com/zend/spotlight/ev12apr.php
		$regexp = '^' . ($domainPartOnly ? '[@]?' : '[_a-z0-9\$-]+(\.[_a-z0-9\$-]+)*@') . '[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})$';
		
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
	
	
	# Function to encode an e-mail address
	function encodeEmailAddress ($email)
	{
		# Return the string
		return str_replace ('@', '<span>&#64;</span>', $email);
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
		return (preg_match ('/^[a-z0-9]{' . $length . '}$/', (trim ($_SERVER['QUERY_STRING']))));
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
	function htmlTable ($array, $tableHeadingSubstitutions = array (), $class = 'lines', $showKey = true, $uppercaseHeadings = false, $allowHtml = false, $showColons = false)
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
			$dataHtml .= "\n\t" . '<tr>';
			if ($showKey) {
				$dataHtml .= "\n\t\t" . "<td><strong>{$key}</strong></td>";
			}
			$i = 0;
			foreach ($value as $valueKey => $valueData) {
				$i++;
				$data = $array[$key][$valueKey];
				$dataHtml .= "\n\t\t" . ($i == 1 ? '<td class="key">' : '<td>') . application::encodeEmailAddress (!$allowHtml ? htmlentities ($data) : $data) . (($showColons && ($i == 1) && $data) ? ':' : '') . '</td>';
			}
			$dataHtml .= "\n\t" . '</tr>';
		}
		
		# Construct the heading HTML
		$headingHtml  = "\n\t" . '<tr>';
		if ($showKey) {$headingHtml .= "\n\t\t" . "<th></th>";}
		if ($tableHeadingSubstitutions !== false) {
			$columns = array_keys ($headings);
			foreach ($columns as $column) {
				$columnTitle = (empty ($tableHeadingSubstitutions) ? $column : (isSet ($tableHeadingSubstitutions[$column]) ? $tableHeadingSubstitutions[$column] : $column));
				$headingHtml .= "\n\t\t" . '<th>' . ($uppercaseHeadings ? ucfirst ($columnTitle) : $columnTitle) . '</th>';
			}
		}
		$headingHtml .= "\n\t" . '</tr>';
		
		# Construct the overall heading
		$html  = "\n\n" . "<table class=\"$class\">";
		$html .= $headingHtml;
		$html .= $dataHtml;
		$html .= "\n" . '</table>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Compatibility function
	function dumpDataToTable ($array, $tableHeadingSubstitutions = array (), $class = 'lines', $showKey = true, $uppercaseHeadings = false, $allowHtml = false)
	{
		return application::htmlTable ($array, $tableHeadingSubstitutions, $class, $showKey, $uppercaseHeadings, $allowHtml);
	}
	
	
	# Function to create a definition list
	function htmlDl ($array, $keySubstitutions = array (), $omitEmpty = true, $class = 'lines', $allowHtml = false, $showColons = true)
	{
		return application::htmlTableKeyed ($array, $keySubstitutions, $omitEmpty, $class, $allowHtml, $showColons, $dlFormat = true);
	}
	
	
	# Function to create a keyed HTML table; $dlFormat is PRIVATE and should not be used externally
	function htmlTableKeyed ($array, $keySubstitutions = array (), $omitEmpty = true, $class = 'lines', $allowHtml = false, $showColons = true, $dlFormat = false)
	{
		# Check that the data is an array
		if (!is_array ($array)) {return $html = "\n" . '<p class="warning">Error: the supplied data was not an array.</p>';}
		
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
			
			# Perform substitutions
			if (array_key_exists ($key, $keySubstitutions)) {
				$newKeyName = $keySubstitutions[$key];
				$array[$newKeyName] = $value;
				unset ($array[$key]);
				continue;
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
			$html .= "\n\t\t" . ($dlFormat ? '<dt>' : "<td class=\"key\">") . $key . ($showColons && $key ? ':' : '') . ($dlFormat ? '</dt>' : '</td>');
			$html .= "\n\t\t" . ($dlFormat ? '<dd>' : "<td class=\"value\">") . $value . ($dlFormat ? '</dd>' : '</td>');
			if (!$dlFormat) {$html .= "\n\t" . '</tr>';}
		}
		$html .= "\n" . ($dlFormat ? '</dl>' : '</table>');
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to write data to a file (first creating it if it does not exist); returns true if successful or false if there was a problem
	function writeDataToFile ($data, $file)
	{
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
		preg_match ('/^(\d+)([bkm]*)$/i', $string, $parts);
		
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
	
	
	# Function to create an unordered HTML list
	function htmlUl ($array, $parentTabLevel = 0, $className = NULL, $ignoreEmpty = true, $sanitise = false, $nl2br = false, $liClass = false)
	{
		# Return an empty string if no items
		if (empty ($array)) {return '';}
		
		# Prepare the tab string
		$tabs = str_repeat ("\t", ($parentTabLevel));
		
		# Build the list
		$html = "\n$tabs<ul" . ($className ? " class=\"$className\"" : '') . '>';
		foreach ($array as $item) {
			
			# Skip an item if the item is empty and the flag is set to ignore these
			if (($ignoreEmpty) && (empty ($item))) {continue;}
			
			# Add the item to the HTML
			if ($sanitise) {$item = htmlentities ($item);}
			if ($nl2br) {$item = nl2br ($item);}
			$html .= "\n$tabs\t<li" . ($liClass ? " class=\"{$liClass}\"" : '') . '>' . $item . '</li>';
		}
		$html .= "\n$tabs</ul>";
		
		# Return the result
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
			$html .= "\n\t{$tabs}<li>{$link}" . htmlentities ($name) . ($link ? '</a>' : '');
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
	function getCsvData ($filename, $getHeaders = false)
	{
		# Open the file
		if (!$fileHandle = fopen ($filename, 'rb')) {return false;}
		
		# Get the column names
		if (!$mappings = fgetcsv ($fileHandle, filesize ($filename))) {return false;}
		
		# Loop through each line of data
		$data = array ();
		while ($csvData = fgetcsv ($fileHandle, filesize ($filename))) {
			
			# Check the first item exists and set it as the row key then unset it
			if ($rowKey = $csvData[0]) {
				unset ($csvData[0]);
				
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