<?
/**
 * User Preferences SQL Functions
 * 
 * 
 * 
 * USAGE:
 * 
 * 
 
/// EASY MODE/BASICS
 
 // LOAD PREFERENCES
 $json_object = $_SESSION['dbapi']->user_prefs->getData("dialer_status");

 // SAVE PREFERENCES (using object/array)
 $_SESSION['dbapi']->user_prefs->updateByArray("dialer_status", $json_object);
 
 
 
/// ADVANCED OPTIONS  

 // LOAD PREFERENCES ROW/DB record
 $row = $_SESSION['dbapi']->user_prefs->get("dialer_status");

 // SAVE PREFERENCES (by raw flat string such as json thats already been encoded, or XML data)
 $_SESSION['dbapi']->user_prefs->update("dialer_status", "{\"dial_level\":\"4.500\",\"trunk_short\":\"0\",...");
 
 
 
 
 * 
 * 
 * 
 * 
 * 
 * 
 */



class UserPreferencesAPI{

	var $table = "user_preferences";




	/**
	 * Removes a users preference by name
	 */
	function delete($section){

		return $_SESSION['dbapi']->execSQL("DELETE FROM `".$this->table."` WHERE user_id='".intval($_SESSION['user']['id'])."' AND `section`='".mysqli_real_escape_string($_SESSION['dbapi']->db, $section)."'");

	}


	/**
	 * Get a user_preference record/row by section name
	 * @param	$section		The section name/preference to grab
	 * @return	assoc-array of the database record
	 */
	function get($section){
		$user_id = intval($_SESSION['user']['id']);

		return $_SESSION['dbapi']->querySQL(
				
				"SELECT * FROM `".$this->table."` ".
				" WHERE user_id='".$user_id."' AND `section`='".mysqli_real_escape_string($_SESSION['dbapi']->db, $section)."'"
		);
	}

	/**
	 * Gets the parsed JSON data direclty, ready to use, by the section name.
	 * @param	$section	String		The section name/preference to grab
	 * @param	$assoc	TRUE/FALSE		When TRUE, returned objects will be converted into associative arrays
	 * @return	Object/Array	The "json_data" field, ran through a JSON parser, and the results returned
	 */
	function getData($section, $assoc = FALSE){
		$user_id = intval($_SESSION['user']['id']);
		
		list($json_str) = $_SESSION['dbapi']->queryROW(
									"SELECT json_data FROM `".$this->table."` ".
									" WHERE user_id='".$user_id."' AND `section`='".mysqli_real_escape_string($_SESSION['dbapi']->db, $section)."'"
							);
		if($json_str != null){
			return json_decode($json_str, $assoc);
		}else{
			return null;
		}
	}



	/**
	 * Update a preference record for current user, by section, and unparsed JSON string
	 * 
	 * @param unknown $section		The section name/preference to grab
	 * @param unknown $json_str		A flat string containing the JSON/String to be saved. 
	 * @return Number of records that were modified by the update
	 */
	function update($section, $json_str){
		
		
		return $_SESSION['dbapi']->execSQL("UPDATE `".$this->table."` ".
				" SET time_updated=UNIX_TIMESTAMP(), json_data='".mysqli_real_escape_string($_SESSION['dbapi']->db, $json_str)."' ".
				" WHERE `user_id`='".intval($_SESSION['user']['id'])."' AND section='".mysqli_real_escape_string($_SESSION['dbapi']->db, $section)."'");
		
	}

	/**
	 * Updates a preference record for current user, by section, and an object/array, that is to be converted to JSON to be saved. 
	 * @param unknown $section		The section name/preference to grab
	 * @param unknown $json_arr		An array/object to be JSON encoded and saved
	 * @param number $json_options	Bitmask consisting of JSON_FORCE_OBJECT, JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, JSON_INVALID_UTF8_IGNORE, JSON_INVALID_UTF8_SUBSTITUTE, JSON_NUMERIC_CHECK, JSON_PARTIAL_OUTPUT_ON_ERROR, JSON_PRESERVE_ZERO_FRACTION, JSON_PRETTY_PRINT, JSON_UNESCAPED_LINE_TERMINATORS, JSON_UNESCAPED_SLASHES, JSON_UNESCAPED_UNICODE, JSON_THROW_ON_ERROR. 
	 * @return Number of records that were modified by the update
	 */
	function updateByArray($section, $json_arr, $json_options=0){
		
		
		return $_SESSION['dbapi']->execSQL("UPDATE `".$this->table."` ".
				" SET time_updated=UNIX_TIMESTAMP(), json_data='".mysqli_real_escape_string($_SESSION['dbapi']->db, json_encode($json_arr, $json_options))."' ".
				" WHERE `user_id`='".intval($_SESSION['user']['id'])."' AND section='".mysqli_real_escape_string($_SESSION['dbapi']->db, $section)."'");
		
	}


}
