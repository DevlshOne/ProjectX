#!/usr/bin/php
<?
/**
 * Pull from all the VICI DB's
 *
 */
/** Everything is in one database now

	$vici_dbs = array(

		array("10.101.15.205", "ccidata", "cci", "MuW94uPe")

	);
**/
	/*
	* Now that we are only using 1 database we need to setup the varibles to connect.
	*/
	$user_xml_file = "/srv/www/htdocs/sales/xmldata/users.xml";
	$dbhost ="127.0.0.1";
	$dbuser ="cci";
	$dbpass ="MuW94uPe";
	$dbname ="ccidata";


/***************************************************/

	require_once("site_config.php");
	require_once("utils/report_utils.php");


	$user_stack = array();

	/**
	 * Gather users from all databases
	 */
	#NOPE foreach($vici_dbs as $info){

	#NOPE	list($dbhost,$dbname,$dbuser,$dbpass) = $info;

		// CONNECT TO DATABASE/ SELECT DB
		$db = mysql_connect($dbhost,$dbuser,$dbpass) or die("MYSQL ERROR: Cannot connect to db: ".mysql_error()."\n");
		mysql_select_db($dbname,$db) or die("MYSQL ERROR: Cannot select database '$dbname'\n");

		// Set counter = 0 for userid since we dont have them in the database
		$recount=0;

		$res  = mysql_query("SELECT `agent_id` as 'user',`agent_name` as 'full_name',1 as 'user_level', `call_group` as 'user_group' FROM employees where to_days(curdate())-to_days(employeedate)<=60 ", $db);

		while($row = mysql_fetch_array($res, MYSQL_ASSOC)){
			$recount=$recount+1;
			if(!array_key_exists(strtoupper($row['user']), $user_stack)){
				$user_stack[$row['user']] = array();
			}

			// THROW THE USERS ON THE STACK
			$user_stack[$row['user']]['user_id'] = $recount;
			$user_stack[$row['user']]['user'] = $row['user'];
			$user_stack[$row['user']]['full_name'] = $row['full_name'];
			$user_stack[$row['user']]['user_level'] = $row['user_level'];
			$user_stack[$row['user']]['user_group'] = $row['user_group'];
		}

	#NOPE }

	$output = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
				"<Users>";

	/**
	 * WRITE THE XML FILE
	 */
	$x=0;
	foreach($user_stack as $user){


		$output .= "<User ";

		foreach($user as $key=>$val){

			$output .= $key.'="'.htmlentities($val).'" ';

		}


		$output .= " />\n";
		$x++;
	}

	$output .= "</Users>\n";


	if(file_put_contents($user_xml_file, $output) === FALSE){
		die("ERROR: Failed to write xml file '$user_xml_file'\n");
	}else{

		echo "Successfully wrote $x records to '$user_xml_file'\n";
	}



