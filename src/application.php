<?php

/*
 * Coding copyright Martin Lucas-Smith, University of Cambridge, 2003-4
 * Version 1.13
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
	
	
	# Generalised support function to allow quick dumping of form data to screen, for debugging purposes
	function dumpData ($data)
	{
		echo "\n<pre>";
		print_r ($data);
		echo "\n</pre>";
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
	
	
	# Function to send administrative alerts
	function sendAdministrativeAlert ($administratorEmail, $applicationName, $subject, $message)
	{
		# Define standard e-mail headers
		$mailheaders = "From: $applicationName <" . $administratorEmail . '>';
		
		# Send the message
		mail ($administratorEmail, $subject, wordwrap ($message), $mailheaders);
	}
	
	
	#!# Scrap this eventually!
	# Function to initialise form input
	function initialiseFormInput ($formName, $fields)
	{
		# Initialise each array value
		foreach ($fields as $field) {
			$form[$field] = (isSet ($_POST[$formName][$field]) ? htmlentities (trim ($_POST[$formName][$field])) : '');
		}
		
		# Return the initialise array
		return $form;
	}
	
	
	# Function to check that an e-mail address is valid
	function validEmail ($email)
	{
		# Perform the check and return the result; regexp from www.zend.com/zend/spotlight/ev12apr.php
		return eregi ('^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$', $email);
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
		
		# Search through the file case-insensitively
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
	function dumpDataToTable ($array, $tableHeadingSubstitutions = array ())
	{
		# Check that the data is an array
		if (!is_array ($array)) {return $html = "\n" . '<p class="warning">Error: the supplied data was not an array.</p>';}
		
		# Assemble the data cells
		$dataHtml = '';
		foreach ($array as $key => $value) {
			$dataHtml .= "\n\t" . '<tr>';
			$dataHtml .= "\n\t\t" . "<td><strong>$key</strong></td>";
			foreach ($value as $valueKey => $valueData) {
				$dataHtml .= "\n\t\t" . '<td>' . application::encodeEmailAddress (htmlentities ($array[$key][$valueKey])) . '</td>';
			}
			$dataHtml .= "\n\t" . '</tr>';
		}
		
		# Obtain the column headings
		$columns = array_keys ($value);
		
		# Construct the database and add the data in
		$html  = "\n\n" . '<table>';
		$html .= "\n\t" . '<tr>';
		$html .= "\n\t\t" . "<th></th>";
		foreach ($columns as $column) {
			$columnTitle = (empty ($tableHeadingSubstitutions) ? $column : (isSet ($tableHeadingSubstitutions[$column]) ? $tableHeadingSubstitutions[$column] : $column));
			$html .= "\n\t\t" . '<th>' . $columnTitle . '</th>';
		}
		$html .= "\n\t" . '</tr>';
		$html .= $dataHtml;
		$html .= "\n" . '</table>';
		
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
				
				# Merge the headers
				$temporaryArray = array_merge ($headers, $subHeaders);
				$headers = $temporaryArray;
				
				# Merge the subkeys
				$temporaryArray = array_merge ($data, $subData);
				$data = $temporaryArray;
				
			# If the associative array is multi-dimensional, assign directly
			} else {
				
				# In nested mode, prepend the each key name with the parent name
				if ($nestParent != false) {$key = "$nestParent: " . $key;}
				
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
		#!# Research further: adding " round a string which includes "" seems to be optional for Excel
		if ((strpos ($data, $delimiter) !== false) || (strpos ($data, "\n") !== false)) {$data = '"' . $data . '"';}
		
		# Return the cleaned data cell
		return $data;
	}
	
	
	# Function to produce a date array
	function getDateTimeArray ($value)
	{
		# Obtain an array of time components
		list (
			$datetime['year'],
			$datetime['month'],
			$datetime['day'],
			$datetime['hour'],
			$datetime['minute'],
			$datetime['second'],
		) = sscanf (($value == 'timestamp' ? date ('Y-m-d H:i:s') : $value), '%4s-%2s-%2s %2s:%2s:%2s');
		
		# Construct a combined time formatted string
		$datetime['time'] = $datetime['hour'] . ':' . $datetime['minute'] . ':' . $datetime['second'];
		
		# Construct a combined SQL-format datetime formatted string
		$datetime['datetime'] = $datetime['year'] . '-' . $datetime['month'] . '-' . $datetime['day'] . ' ' . $datetime['time'];
		
		# Return the array
		return $datetime;
	}
	
	
	# Function to parse a string for the time and return a correctly-formatted SQL version of it
	function parseTime ($input)
	{
		# 1a. Remove any surrounding whitespace from the input
		$time = strtolower (trim ($input));
		
		# 2a. Collapse all white space (i.e. allow excess internal whitespace) to a single space
		$time = preg_replace ("/\s+/",' ', $time);
		
		# 2b. Collapse whitespace next to a colon or a dot
		$time = str_replace (array (': ', '. ', ' :', ' .'), ' ', $time);
		
		# 2b. Convert allowed separators to a space
		$allowedSeparators = array (':', '.', ' ');
		foreach ($allowedSeparators as $allowedSeparator) {
			$time = str_replace ($allowedSeparator, ' ', $time);
		}
		
		# 3. Return false if a starting (originally non-whitespace) separator has been found
		if (ereg ('^ ', $time)) {return false;}
		
		# 4. Return false if two adjacent whitespaces are found (i.e. do not allow multiples of originally non-whitespace allowed characters)
		if (ereg ('  ', $time)) {return false;}
		
		# 5. Remove any trailing separator
		$time = trim ($time);
		
		# 6b. Throw error if string contains other than: 0-9, whitespace separator, or the letters a m p
		#!# This could ideally be improved to weed out more problems earlier on
		if (ereg ('[^0-9\ amp]+', $time)) {return false;}
		
		# 7a. Adjust am and pm for the possibility of a.m. or a.m or p.m. or p.m having been entered
		$time = str_replace ('a m', 'am', $time);
		$time = str_replace ('p m', 'pm', $time);
		
		# 7b. If string ends with am or pm then strip that off and hold it for later use
		if ((eregi ('am$', $time)) || (eregi ('pm$', $time))) {
			$timeParts['meridiem'] = substr ($time, -2);
			$time = substr ($time, 0, -2);
		}
		
		# 8. Throw error if string contains other than: 0-9 or space
		if (ereg ('[^0-9\ ]+', $time)) {return false;}
		
		# 9. Remove any trailing separator
		$time = trim ($time);
		
		# 11. Throw error if string contains more than 5 or 6 numeric digits
		$numericOnlyString = str_replace (' ', '', $time);
		$numbersInString = strlen ($numericOnlyString);
		if ($numbersInString > 6) {return false;}
		
		# 10a. Throw error if string contains other than 0, 1, or 2 separators
		if (substr_count ($time, ' ') > 2) {return false;}
		
		# 12. Check whether string contains 5 or 6 numeric digits; if so, run several checks:
		if ($numbersInString == 5 || $numbersInString == 6) {
			
			# Throw error if not either 1 or 2 separators (i.e. if it contains 0 since it is already known that the string contains 0, 1, or 2
			if (substr_count ($time, ' ') == 0) {return false;}
			
			# Throw error if there are not 2 digits after last separator
			$temporary = explode (' ', $time);
			$timeParts['seconds'] = $temporary [(count ($temporary) - 1)];
			if (strlen ($timeParts['seconds']) != 2) {return false;}
			
			# Throw error IF last two digits are not valid seconds, i.e. 0(0)-59
			if ($timeParts['seconds'] > 59) {return false;}
			
			# Strip off the seconds and separator from the string
			$time = trim (substr ($time, 0, -2));
		}
		
		# 13. Allow for special case of .0 as meaning 0 minutes and resubstitute 0 for 0
		if (substr ($time, -2) === ' 0') {
			$timeParts['minutes'] = '00';
			
			# If so, then strip off the separator-zero from the string
			$time = trim (substr ($time, 0, -2));
			
		} else {
			
			# 10b. Throw error if string contains 3 or more numeric characters but starts with number-space-number-space, e.g. 1 1 00
			if (($numbersInString > 3) && (ereg ('^[0-9]\ [0-9]\ ', $time))) {return false;}
			
			# Throw error if string ends with space-number-space
			if (ereg ('\ [0-9]$', $time)) {return false;}
			
			# Recalculate the number of numeric digits in the string
			$numericOnlyString = str_replace (' ', '', $time);
			$numbersInString = strlen ($numericOnlyString);
			
			# 14. Check whether string contains 3 or 4 numeric digits; if so, run several checks:
			if ($numbersInString == 3 || $numbersInString == 4) {
				
				# Make sure the last two characters form a number between 00-59
				if (!ereg ('[0-5][0-9]$', $time)) {return false;}
				
				# Extract the minutes
				$timeParts['minutes'] = substr ($time, -2);
				
				# Strip off the minutes (and trim again, although that should not be necessary)
				$time = trim (substr ($time, 0, -2));
			}
		}
		
		# 15a. Check that there is no whitespace left (all that should remain is hours)
		if (!ereg ('[0-9]{1,2}', $time)) {return false;}
		
		# 15b. Validate the hour figure; firstly check that the hours are not above 23 (they cannot be less than 0 because - is not an allowable character)
		if ($time > 23) {return false;}
		
		# 15c. Run checks based on the existence of a meridiem
		if (isSet ($timeParts['meridiem'])) {
			
			# 15d. If the meridiem is am and the time is 12-23 then exit
			if (($timeParts['meridiem'] == 'am') && ($time > 12)) {return false;}
			
			# 15e. Replace 12am with 0
			if (($timeParts['meridiem'] == 'am') && ($time == 12)) {$time = 0;}
			
			# 15f. If the meridium is pm and the time is 0
			if (($timeParts['meridiem'] == 'pm') && ($time == 0)) {return false;}
			
			# 15g. Add 12 hours to the time if it's pm and hours is less than 
			if (($timeParts['meridiem'] == 'pm') && ($time < 12)) {$time += 12;}
		}
		
		# 16 Assign the hours, padding out hours to two digits if necessary
		$timeParts['hours'] = str_pad ($time, 2, '0', STR_PAD_LEFT);
		
		# Finally, assemble the time string using the allocated array parts, in the SQL format of 00:00:00
		$time = 
			(isSet ($timeParts['hours']) ? $timeParts['hours'] : '00') . ':' .
			(isSet ($timeParts['minutes']) ? $timeParts['minutes'] : '00') . ':' .
			(isSet ($timeParts['seconds']) ? $timeParts['seconds'] : '00');
		
		# Return the assembled and validated string
		return $time;
	}
	
	
	# Function to convert a two-character year to a four-character year
	function convertYearToFourCharacters ($year, $autoCenturyConversationLastYear = 69)
	{
		# Check that the value given is an integer
		if (!is_numeric ($year)) {return false;}
		
		# If the value is empty, return empty
		if ($year == '') {return $year;}
		
		# If $add is true, use the function to add leading figures
		if (strlen ($year) == 2) {
			
			# Add either 19 or 20 as considered appropriate
			$year = (($year <= $autoCenturyConversationLastYear) ? '20' : '19') . $year;
		}
		
		# Return the result
		return ($year);
	}
}

?>