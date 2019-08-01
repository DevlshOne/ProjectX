<<<<<<< utils/functions.php
<?php


	function filter09($str, $max_length = 0){

		$out = preg_replace("/[^0-9]/",'',$str);

		if($max_length && $max_length > 0){

			$out = substr($out,0, $max_length);
		}

		return $out;
	}


	function filterAZ09($str, $max_length = 0){

		$out = preg_replace("/[^a-zA-Z0-9-]/",'',$str);

		if($max_length && $max_length > 0){

			$out = substr($out,0, $max_length);
		}

		return $out;
	}

	function filterName($str, $max_length = 0){

		$out = preg_replace('/[^a-zA-Z0-9._-\'\/\\#$ ]/g', '' , $str);

		if($max_length && $max_length > 0){

			$out = substr($out,0, $max_length);
		}


		return $out;
	}

	function paidSorter($a, $b){

		$val1 = floatval($a['paid_hr']);
		$val2 = floatval($b['paid_hr']);
		if($val1 == $val2){

			return strnatcmp($a['agent_username'], $b['agent_username']);

			//return 0;
		}

		return ($val1 < $val2)? 1 : -1;

	}



	function generateRandomString($length = 10) {
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}



	function recordCompare($old, $new){

		$out = '';

//print_r($old);
//print_r($new);

		// GO THROUGH THE OLD ONE, COMPARE KEYS TO NEW
		foreach($old as $okey=>$oval){

//			echo $okey.' - '.$old[$okey]." vs ".$new[$okey]."\n";

			// IF A KEY IS IN THE OLD STACK THAT ISNT IN THE NEW ONE, KEY/FIELD WAS REMOVED
			//if($oval != null && !isset($new[$okey])){
			if(!array_key_exists($okey, $new)){
				$out .= "$okey removed.\n";
			}

			// IF THE NEW VALUE DOESN'T MATCH THE OLD VALUE
			if($new[$okey] != $oval){

				$out .= "'$okey' changed from \"".$oval."\" to \"".$new[$okey]."\"\n";

			}

		}

		foreach($new as $nkey=>$nval){


			// IF A KEY IS IN THE NEW STACK THAT ISNT IN THE OLD ONE, KEY/FIELD WAS ADDED
			//if($nval != null && !isset($old[$nkey])){
			if(!array_key_exists($nkey, $old)){
				$out .= "$nkey added.\n";
			}
		}


//echo $out;





		return $out;
	}

	function logAction($action, $area, $record_id, $description = null, $orig_record = null, $new_record = null){

		$dat = array();
		$dat['time'] = time();
		$dat['user_id'] = $_SESSION['user']['id'];
		$dat['user'] = $_SESSION['user']['username'];
		$dat['action'] = $action;
		$dat['area'] = $area;
		$dat['record_id'] = intval($record_id);

		// SANITY CHECK FOR OUT OF BOUNDS SQL ERROR
		if($dat['record_id'] < 0){
			$dat['record_id'] = 0;
		}

		$dat['description'] = $description;


		// IF THE ORIG RECORD IS PASSED, WE CAN COMPARE WITH THE CURRENT ONE

		if($orig_record != null && $new_record != null){

			$differences = recordCompare($orig_record, $new_record);

			if($differences != null && strlen(trim($differences)) > 0){

				$dat['changes_tracked'] = $differences;
			}
		}


//			switch($area){
//			default:
//				// IGNORE/SKIP
//				break;
//
//
//			case 'scripts':
//
//				// LOAD THE NEW SCRIPT RECORD
//				$row = $_SESSION['dbapi']->scripts->getByID($dat['record_id']);
//
//				// COMPARE WITH THE ORIG
//				$differences = recordCompare($orig_record, $row);
//
//				// LOG THE CHANGES
//				if($differences != null && strlen(trim($differences)) > 0){
//
//					$dat['changes_tracked'] = $differences;
//
//				}
//
//				break;
//			}
//		}
//print_r($dat);

		return $_SESSION['dbapi']->aadd($dat, 'action_log');
	}



	function accessDenied($missing_priv){

		echo "ERROR: You lack the ability to access this section.\n<br />Access to '$missing_priv' is denied.\n";

	}


	// REQUIRES DB ACCESS!
	function lookupUserGroup($user_id, $vici_cluster_id){

		connectPXDB();

		list($group) = queryROW("SELECT group_name FROM user_group_translations WHERE user_id='$user_id' AND cluster_id='$vici_cluster_id'");

		return $group;
	}





	//http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
	function startsWith($haystack, $needle) {
	    // search backwards starting from haystack length characters from the end
	    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}
	function endsWith($haystack, $needle) {
	    // search forward starting from end minus needle length characters
	    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}




	function curl_get_file($url){

		// create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

		return $output;
	}

	function curl_write_temp_file($url, $file_prefix = "TempFile-"){

		$data = curl_get_file($url);

		$temp_file = tempnam(sys_get_temp_dir(), $file_prefix);

		file_put_contents($temp_file, $data);

		return $temp_file;
	}



	function zeropad($in, $length){


		$numzeros = $length - strlen($in);
		$out = '';

		if($numzeros > 0){
			for($x=0;$x < $numzeros;$x++){
				$out .= '0';
			}
		}

		$out .= $in;

		return $out;
	}

    /**
     * @param $s integer Start time as timestamp
     * @param $e integer End time as timestamp
     * @param $f string format of output (see date->format for options)
     */
	function calculateDuration($s, $e, $f) {
        $d1 = new DateTime();
        $d2 = new DateTime();
        $d1->setTimestamp($s);
        $d2->setTimestamp($e);
        $i = $d1->diff($d2);
        #echo __METHOD__ . var_dump($d1, $d2, $i) . "<br />";
        return $i->format($f);
    }
=======
<?php


	function filter09($str, $max_length = 0){

		$out = preg_replace("/[^0-9]/",'',$str);

		if($max_length && $max_length > 0){

			$out = substr($out,0, $max_length);
		}

		return $out;
	}


	function filterAZ09($str, $max_length = 0){

		$out = preg_replace("/[^a-zA-Z0-9-]/",'',$str);

		if($max_length && $max_length > 0){

			$out = substr($out,0, $max_length);
		}

		return $out;
	}

	function filterName($str, $max_length = 0){

		$out = preg_replace('/[^a-zA-Z0-9._-\'\/\\#$ ]/g', '' , $str);

		if($max_length && $max_length > 0){

			$out = substr($out,0, $max_length);
		}


		return $out;
	}

	function paidSorter($a, $b){

		$val1 = floatval($a['paid_hr']);
		$val2 = floatval($b['paid_hr']);
		if($val1 == $val2){

			return strnatcmp($a['agent_username'], $b['agent_username']);

			//return 0;
		}

		return ($val1 < $val2)? 1 : -1;

	}



	function generateRandomString($length = 10) {
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}



	function recordCompare($old, $new){

		$out = '';

//print_r($old);
//print_r($new);

		// GO THROUGH THE OLD ONE, COMPARE KEYS TO NEW
		foreach($old as $okey=>$oval){

//			echo $okey.' - '.$old[$okey]." vs ".$new[$okey]."\n";

			// IF A KEY IS IN THE OLD STACK THAT ISNT IN THE NEW ONE, KEY/FIELD WAS REMOVED
			//if($oval != null && !isset($new[$okey])){
			if(!array_key_exists($okey, $new)){
				$out .= "$okey removed.\n";
			}

			// IF THE NEW VALUE DOESN'T MATCH THE OLD VALUE
			if($new[$okey] != $oval){

				$out .= "'$okey' changed from \"".$oval."\" to \"".$new[$okey]."\"\n";

			}

		}

		foreach($new as $nkey=>$nval){


			// IF A KEY IS IN THE NEW STACK THAT ISNT IN THE OLD ONE, KEY/FIELD WAS ADDED
			//if($nval != null && !isset($old[$nkey])){
			if(!array_key_exists($nkey, $old)){
				$out .= "$nkey added.\n";
			}
		}


//echo $out;





		return $out;
	}

	function logAction($action, $area, $record_id, $description = null, $orig_record = null, $new_record = null){

		$dat = array();
		$dat['time'] = time();
		$dat['user_id'] = $_SESSION['user']['id'];
		$dat['user'] = $_SESSION['user']['username'];
		$dat['action'] = $action;
		$dat['area'] = $area;
		$dat['record_id'] = intval($record_id);

		// SANITY CHECK FOR OUT OF BOUNDS SQL ERROR
		if($dat['record_id'] < 0){
			$dat['record_id'] = 0;
		}

		$dat['description'] = $description;


		// IF THE ORIG RECORD IS PASSED, WE CAN COMPARE WITH THE CURRENT ONE

		if($orig_record != null && $new_record != null){

			$differences = recordCompare($orig_record, $new_record);

			if($differences != null && strlen(trim($differences)) > 0){

				$dat['changes_tracked'] = $differences;
			}
		}


//			switch($area){
//			default:
//				// IGNORE/SKIP
//				break;
//
//
//			case 'scripts':
//
//				// LOAD THE NEW SCRIPT RECORD
//				$row = $_SESSION['dbapi']->scripts->getByID($dat['record_id']);
//
//				// COMPARE WITH THE ORIG
//				$differences = recordCompare($orig_record, $row);
//
//				// LOG THE CHANGES
//				if($differences != null && strlen(trim($differences)) > 0){
//
//					$dat['changes_tracked'] = $differences;
//
//				}
//
//				break;
//			}
//		}
//print_r($dat);

		return $_SESSION['dbapi']->aadd($dat, 'action_log');
	}



	function accessDenied($missing_priv){

		echo "ERROR: You lack the ability to access this section.\n<br />Access to '$missing_priv' is denied.\n";

	}


	// REQUIRES DB ACCESS!
	function lookupUserGroup($user_id, $vici_cluster_id){

		connectPXDB();

		list($group) = queryROW("SELECT group_name FROM user_group_translations WHERE user_id='$user_id' AND cluster_id='$vici_cluster_id'");

		return $group;
	}





	//http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
	function startsWith($haystack, $needle) {
	    // search backwards starting from haystack length characters from the end
	    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
	}
	function endsWith($haystack, $needle) {
	    // search forward starting from end minus needle length characters
	    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}




	function curl_get_file($url){

		// create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

		return $output;
	}

	function curl_write_temp_file($url, $file_prefix = "TempFile-"){

		$data = curl_get_file($url);

		$temp_file = tempnam(sys_get_temp_dir(), $file_prefix);

		file_put_contents($temp_file, $data);

		return $temp_file;
	}



	function zeropad($in, $length){


		$numzeros = $length - strlen($in);
		$out = '';

		if($numzeros > 0){
			for($x=0;$x < $numzeros;$x++){
				$out .= '0';
			}
		}

		$out .= $in;

		return $out;
	}
>>>>>>> utils/functions.php
