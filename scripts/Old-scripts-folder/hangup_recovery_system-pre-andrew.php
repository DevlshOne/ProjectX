#!/usr/bin/php
<?php
	session_start();

	global $delimiter;


	$basedir = "/var/www/dev/";

	$push_to_cluster_id = 3; // 3 == TAPS CLUSTER

	$push_to_list_id = "88888888";

	$delimiter = "\t";

	$finshed_dispo = "RECOVR";

/*********************/

	include_once($basedir."site_config.php");
	include_once($basedir."db.inc.php");
	include_once($basedir."utils/db_utils.php");

	function escapeCSV($input){
		global $delimiter;

		return preg_replace("/".$delimiter."/", "_", $input);

	}


	$stime = mktime (0,0,0);
	$etime = $stime + 86399;


	echo date("H:i:s m/d/Y")." - Processing Hangup Recovery ...\n";

	// CONNECT TO PX
	connectPXDB();


	// LOAD DESTINATION CLUSTER INFO
	$cluster = querySQL("SELECT * FROM vici_clusters WHERE `id`='".intval($push_to_cluster_id)."' ");



	// PULL THE LEADS THAT ARE CLASSIFIED HANGUP ("hangup" or "NOVERI" dispo)
	$res = query("SELECT * FROM lead_tracking ".
				" WHERE `dispo` IN ('hangup','NOVERI') ".
				" AND `list_id` != '".mysql_real_escape_string($push_to_list_id)."' ".
				" AND `time` BETWEEN '$stime' AND '$etime'"
				, 1);

	// WRITE THEM TO A TEMP FILE
	$tmpfname = tempnam(sys_get_temp_dir(), 'HangupRecovery'); // good

	$fh = fopen($tmpfname, "w");

	$completed_id_stack = array();

	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

		if(!trim($row['phone_num']))continue;

/**
Vendor Lead Code - shows up in the Vendor ID field of the GUI
Source Code - internal use only for admins and DBAs
List ID - the list number that these leads will show up under
Phone Code - the prefix for the phone number - 1 for US, 44 for UK, 61 for AUS, etc
Phone Number - must be at least 8 digits long
Title - title of the customer - Mr. Ms. Mrs, etc...
First Name
Middle Initial
Last Name
Address Line 1
Address Line 2
Address Line 3
City
State - limited to 2 characters
Province
Postal Code
Country
Gender
Date of Birth
Alternate Phone Number
Email Address
Security Phrase
Comments
Rank
Owner

					 */


		$line = '""'.$delimiter.							// VENDOR LEAD CODE
				'"'.escapeCSV($row['id']).'"'.$delimiter.				// SOURCE CODE (lead tracking ID in this case)
				'"'.escapeCSV($push_to_list_id).'"'.$delimiter.		// LIST ID TO PUSH TO
				'"1"'.$delimiter.							// PHONE CODE
				'"'.escapeCSV($row['phone_num']).'"'.$delimiter.		// PHONE NUMBER
				'""'.$delimiter.							// TITLE
				'"'.escapeCSV($row['first_name']).'"'.$delimiter.		// FIRST NAME
				'"'.escapeCSV($row['middle_initial']).'"'.$delimiter.	// MIDDLE INITIAL
				'"'.escapeCSV($row['last_name']).'"'.$delimiter.		// LAST NAME

				'"'.escapeCSV($row['address1']).'"'.$delimiter.		// ADDRESS LINE 1
				'"'.escapeCSV($row['address2']).'"'.$delimiter.		// ADDRESS LINE 2
				'"'.escapeCSV($row['address3']).'"'.$delimiter.		// ADDRESS LINE 3

				'"'.escapeCSV($row['city']).'"'.$delimiter.			// CITY
				'"'.escapeCSV($row['state']).'"'.$delimiter.			// STATE
				'"'.escapeCSV($row['province']).'"'.$delimiter.		// PROVINCE
				'"'.escapeCSV($row['zip_code']).'"'.$delimiter.		// POSTAL CODE
				'"USA"'.$delimiter.							// MURICA

				'""'.$delimiter.		// GENDER
				'""'.$delimiter.		// DoB
				'""'.$delimiter.		// ALT PHONE
				'""'.$delimiter.		// EMAIL ADR
				'""'.$delimiter.		// SECURITY PHRASE

				'"'.escapeCSV($row['comments']).'"'.$delimiter. 	// COMMENTS
				'""'.$delimiter.		// RANK
				'""'."\n";		// OWNER

//		echo "Writing line: ".$line."\n";

		fwrite($fh, $line);

		$completed_id_stack[] = $row['id'];


	}


	// CLOSE THE FILE WHEN FINISHED WRITING THE LEADS
	fclose($fh);




	// BUILD THE POST ARRAY
	$post = array(

		'leadfile_name' => 			$tmpfname,
		'DB' => 					"",
		'list_id_override' =>		"in_file",
		'phone_code_override' =>	"in_file",
		'file_layout' => 			"standard",
		'template_id' =>			"",

		'dupcheck'	=>				"DUPLIST",
		'dedupe_statuses[]' =>		"NEW",

		'usacan_check' =>			"NONE",
		'postalgmt'	=>				"POSTAL",

		'OK_to_process' =>			"1",

		'leadfile' => '@' . realpath($tmpfname)

	);

//	print_r($post);exit;


//	$fields_string = http_build_query($post);

	//$url = "http://10.101.15.51/dev2/test.php";
	$url = 'http://'.$cluster['web_ip'].'/vicidial/admin_listloader_fourth_gen.php';


//print_r($cluster);

	echo "Preparing to CURL POST TO: ".$url."\n";
//echo $fields_string."\n";
//exit;

	// PUSH THE LEADS TO VICI VIA CURL/API
	$process = curl_init($url);
//	curl_setopt($process, CURLOPT_URL,	);
	//curl_setopt($process, CURLOPT_HTTPHEADER, array('Content-Type: application/xml', $additionalHeaders));
//	curl_setopt($process, CURLOPT_HEADER, 1);
	curl_setopt($process, CURLOPT_USERPWD, $cluster['web_api_user'] . ":" . $cluster['web_api_pass']);
	curl_setopt($process, CURLOPT_TIMEOUT, 30);
	curl_setopt($process, CURLOPT_POST, 1);
	curl_setopt($process, CURLOPT_POSTFIELDS, $post);//$fields_string);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
	$return = curl_exec($process);
	curl_close($process);


	//echo "CURL Results: ".$return."\n";

	if(count($completed_id_stack) > 0){
		//$sql = "UPDATE `lead_tracking` SET `dispo`='".mysql_real_escape_string($finshed_dispo)."' WHERE `id` IN(";

		$sql = "UPDATE `lead_tracking` SET `list_id`='".mysql_real_escape_string($push_to_list_id)."' WHERE `id` IN(";

		$x=0;
		foreach($completed_id_stack as $sid){
			$sql .= ($x++ > 0)?",":'';

			$sql .= $sid;
		}

		if($x > 0){

			$sql .= ")";

			echo "Updating `lead_tracking` to mark leads as recycled\n";

//			echo $sql."\n";
			execSQL($sql);

		}

	}


