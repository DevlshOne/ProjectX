<?php
/**
 * DB Utils file, for connecting to all the various databases.
 * Written By: Jonathan Will
 */



	function connectCCIDB(){

		$db = mysql_connect(
			$_SESSION['site_config']['ccidb']['sqlhost'],
			$_SESSION['site_config']['ccidb']['sqllogin'],
			$_SESSION['site_config']['ccidb']['sqlpass']

		);

		if(!$db){

			echo $_SESSION['site_config']['ccidb']['sqlhost'].": Error connecting to ". $_SESSION['site_config']['ccidb']['sqlhost']."\n";
			return false;

		}


		// DB CONNECTED AT THIS POINT
		// SELECT THE DATABASE
		if(!mysql_select_db($_SESSION['site_config']['ccidb']['sqldb'])){

			echo $_SESSION['site_config']['ccidb']['sqlhost'].": Error - Cannot select db.\n";

			return false;
		}



		// SAVE DB TO SESSION, SO THE db.inc.php FUNCTIONS WORK
		$_SESSION['db'] = $db;


		return true;
	}



	function connectListDB(){

		$db = mysql_connect(
			$_SESSION['site_config']['listdb']['sqlhost'],
			$_SESSION['site_config']['listdb']['sqllogin'],
			$_SESSION['site_config']['listdb']['sqlpass']

		);

		if(!$db){

			echo $_SESSION['site_config']['listdb']['sqlhost'].": Error connecting to ". $_SESSION['site_config']['listdb']['sqlhost']."\n";
			return false;

		}


		// DB CONNECTED AT THIS POINT
		// SELECT THE DATABASE
		if(!mysql_select_db($_SESSION['site_config']['listdb']['sqldb'])){

			echo $_SESSION['site_config']['listdb']['sqlhost'].": Error - Cannot select db.\n";

			return false;
		}



		// SAVE DB TO SESSION, SO THE db.inc.php FUNCTIONS WORK
		$_SESSION['db'] = $db;

		return true;
	}


	function connectPXDB(){

		$db = mysql_connect(
			$_SESSION['site_config']['pxdb']['sqlhost'],
			$_SESSION['site_config']['pxdb']['sqllogin'],
			$_SESSION['site_config']['pxdb']['sqlpass']

		);

		if(!$db){

			echo $_SESSION['site_config']['pxdb']['sqlhost'].": Error connecting to ". $_SESSION['site_config']['pxdb']['sqlhost']."\n";
			return false;

		}


		// DB CONNECTED AT THIS POINT
		// SELECT THE DATABASE
		if(!mysql_select_db($_SESSION['site_config']['pxdb']['sqldb'])){

			echo $_SESSION['site_config']['pxdb']['sqlhost'].": Error - Cannot select db.\n";

			return false;
		}



		// SAVE DB TO SESSION, SO THE db.inc.php FUNCTIONS WORK
		$_SESSION['db'] = $db;

		return true;
	}


	function connectViciDB($dbidx){

		$db = mysql_connect(
			$_SESSION['site_config']['db'][$dbidx]['sqlhost'],
			$_SESSION['site_config']['db'][$dbidx]['sqllogin'],
			$_SESSION['site_config']['db'][$dbidx]['sqlpass']

		);

		if(!$db){

			echo $_SESSION['site_config']['db'][$dbidx]['sqlhost'].": Error connecting to ". $_SESSION['site_config']['db'][$dbidx]['sqlhost']."\n";
			return false;

		}


		// DB CONNECTED AT THIS POINT
		// SELECT THE DATABASE
		if(!mysql_select_db($_SESSION['site_config']['db'][$dbidx]['sqldb'])){

			echo $_SESSION['site_config']['db'][$dbidx]['sqlhost'].": Error - Cannot select db.\n";

			return false;
		}


		// SAVE DB TO SESSION, SO THE db.inc.php FUNCTIONS WORK
		$_SESSION['db'] = $db;

		return true;
	}






	/**
	 * Connect to the specified opensips server
	 * NOTICE: THIS ISNT THE SAME $dbidx THAT THE VICI CLUSTERS USE!
	 */
	function connectOpenSipsDB($dbidx){

		$db = mysql_connect(
			$_SESSION['site_config']['opensipsdb'][$dbidx]['sqlhost'],
			$_SESSION['site_config']['opensipsdb'][$dbidx]['sqllogin'],
			$_SESSION['site_config']['opensipsdb'][$dbidx]['sqlpass']

		);

		if(!$db){

			echo $_SESSION['site_config']['opensipsdb'][$dbidx]['sqlhost'].": Error connecting to ". $_SESSION['site_config']['opensipsdb'][$dbidx]['sqlhost']."\n";
			return false;

		}


		// DB CONNECTED AT THIS POINT
		// SELECT THE DATABASE
		if(!mysql_select_db($_SESSION['site_config']['opensipsdb'][$dbidx]['sqldb'])){

			echo $_SESSION['site_config']['opensipsdb'][$dbidx]['sqlhost'].": Error - Cannot select db.\n";

			return false;
		}


		// SAVE DB TO SESSION, SO THE db.inc.php FUNCTIONS WORK
		$_SESSION['db'] = $db;

		return true;
	}

















	function getClusterIndex($vici_cluster_id){


		foreach($_SESSION['site_config']['db'] as $idx=>$db){


			if($db['cluster_id'] == $vici_cluster_id)return $idx;

		}

		return -1;
	}

	/**
	 * Get the cluster name from site config variables
	 */
	function getClusterName($vici_cluster_id){


		foreach($_SESSION['site_config']['db'] as $idx=>$db){


			if($db['cluster_id'] == $vici_cluster_id)return $db['name'];

		}

		return null;
	}


	function getClusterWebHost($vici_cluster_id){


		foreach($_SESSION['site_config']['db'] as $idx=>$db){


			if($db['cluster_id'] == $vici_cluster_id)return $db['web_host'];

		}

		return null;
	}

	function getEditLeadURL($vici_cluster_id, $lead_id){

		$vici_ip = getClusterWebHost($vici_cluster_id);

		$url = "http://".$vici_ip."/vicidial/admin_modify_lead.php?lead_id=".$lead_id."&archive_search=No&archive_log=0";

		return $url;
	}

	function getSearchLeadURL($vici_cluster_id, $phone_num){

		$vici_ip = getClusterWebHost($vici_cluster_id);

		$url = "http://".$vici_ip."/vicidial/admin_search_lead.php?phone=".$phone_num;

		return $url;
	}


	/**
	 * Load all the opensips servers into the session array
	 * 	$_SESSION['site_config']['opensipsdb'][0]['sqlhost']	= "10.100.0.200";
		$_SESSION['site_config']['opensipsdb'][0]['sqllogin']	= "pxreporting";
		$_SESSION['site_config']['opensipsdb'][0]['sqlpass']   	= "nrAesou0rethash";
		$_SESSION['site_config']['opensipsdb'][0]['sqldb'] 		= "opensips";

		AUTOMATICALLY EXECUTED AT THE END OF THIS SCRIPT (util/db_utils.php)

	 */
	function loadOpenSipsDBs(){

		connectPXDB();


		$res = query("SELECT * FROM opensips_servers WHERE enabled='yes' ", 1);

		$x=0;
		while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

			$_SESSION['site_config']['opensipsdb'][$x]['name']		= $row['name'];
			$_SESSION['site_config']['opensipsdb'][$x]['prefix']		= $row['prefix'];
			$_SESSION['site_config']['opensipsdb'][$x]['sqlhost']		= $row['ip_address'];
			$_SESSION['site_config']['opensipsdb'][$x]['sqllogin']		= $row['db_user'];
			$_SESSION['site_config']['opensipsdb'][$x]['sqlpass']   	= $row['db_pass'];
			$_SESSION['site_config']['opensipsdb'][$x]['sqldb'] 		= $row['db_name'];

			$x++;
		}

	}


	function makeClusterDD($name, $selected, $css, $onchange, $blank_option = 1){

		$out = '<select name="'.$name.'" id="'.$name.'" ';

		$out .= ($css)?' class="'.$css.'" ':'';
		$out .= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out .= '>';

		//$out .= '<option value="">[All]</option>';

		if($blank_option){
			$out .= '<option value="" '.(($selected == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
		}

		foreach($_SESSION['site_config']['db'] as $dbidx=>$db){

			$out .= '<option value="'.$db['cluster_id'].'" ';
			$out .= ($selected == $db['cluster_id'])?' SELECTED ':'';
			$out .= '>'.htmlentities($db['name']).'</option>';
		}




		$out .= '</select>';

		return $out;
	}



	function getUserByID($id){
		$id = intval($id);

		return $_SESSION['dbapi']->querySQL("SELECT * FROM `users` ".
						" WHERE id='".$id."' "

					);
	}

	function getUsername($id){
		$id = intval($id);

		list($name) = $_SESSION['dbapi']->queryROW("SELECT name FROM `users` ".
						" WHERE id='".$id."' "

					);
		return $name;
	}




	// AUTO LOAD OPENSIPS DBS
	loadOpenSipsDBs();

