#!/usr/bin/php
<?php
	$basedir = "/var/www/html/reports/";

	include_once($basedir."site_config.php");
	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");



	$stime = mktime(0,0,0);
	$etime = mktime(23,59,59);

	
	
	include_once($basedir."dbapi/dbapi.inc.php");
	
	global $process_name;
	
	$process_name = "px_import_all_vici_user_groups";
	
	$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv));
	
	
	

	echo "Starting Bulk Vici User GROUP Import script on ".date("m/d/Y",$stime)."...\n";


	// CONNECT PX DB
	connectPXDB();


	$res = query("SELECT * FROM vici_clusters WHERE status='enabled' ",1);
	$clusters = array();
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$clusters[$row['id']] = $row;

	}


	$offices = array();

	$res = query("SELECT * FROM offices WHERE status='enabled'", 1);
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$offices[$row['id']] = $row;

	}





	$process_logs = '';
	
	
	$master_groups = array();
	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($clusters as $cluster_id => $vicirow ){

		// LOCATE WHICH DB INDEX IT IS
		$dbidx = getClusterIndex($cluster_id);

		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);

		echo "Cluster ID# ".$cluster_id." - ".$vicirow['name']."\n";


		// PULL THE USER GROUPS
		$res = query("SELECT * FROM `vicidial_user_groups` ", 1);



		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			// SHIT THEM OUT SO I CAN COMPARE
		//	print_r($row);

			$master_groups[] = $row;

		}



	}



	connectPXDB();


	foreach($master_groups as $group){


		list($exists) = queryROW("SELECT id FROM `user_groups_master` WHERE `user_group`='".mysqli_real_escape_string($_SESSION['db'],$group['user_group'])."'");


		if($exists){

		//	echo "USER GROUP '".$group['user_group']."' ALREADY EXISTS, SKIPPING.\n";
			continue;
		}

		$dat = array();

		foreach($group as $key=>$val){

			$dat[$key] = $val;

		}

		// ATTEMPT TO DETECT AM/PM MODE
		if(stripos($group['user_group'],"-PM") > -1){
			$dat['time_shift'] = 'PM';
		}else{
			$dat['time_shift'] = 'AM';
		}


		// ATTEMPT TO DETECT VERIFY GROUPS
		if(stripos($group['user_group'],"verif") > -1){

			$dat['agent_type'] = 'verifier';
		}else if(stripos( $group['user_group'], "train") > -1){

			$dat['agent_type'] = 'training';

		}else if((stripos($group['user_group'], "admin") > -1) || (stripos($group['user_group'], "special") > -1) ){

			$dat['agent_type'] = 'admin';

		}else if(stripos($group['user_group'],"manager") > -1){

			$dat['agent_type'] = 'manager';


		}else if(stripos($group['user_group'],"monitor") > -1){

			$dat['agent_type'] = 'monitor';


		// ATTEMPT TO DETECT TAPS vs COLD (less for us to manage later)
		}else if(stripos($group['user_group'],"tap") > -1){

			$dat['agent_type'] = 'taps';
		}else{
			$dat['agent_type'] = 'cold';
		}

		//print_r($dat);


		// ATTEMPT TO DETERMINE WHAT THE "company_id" IS, BASED ON WHICH OFFICE IS SET


		if($offices[$dat['office']]['company_id'] > 0){

			$dat['company_id'] = $offices[$dat['office']]['company_id'];

		}



		$str = "Adding group '".$group['user_group']."' type:".$dat['agent_type']." shift:".$dat['time_shift']."\n";

		$process_logs .= $str;
		
		echo $str;
		
		aadd($dat, 'user_groups_master');



	}


	
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $process_logs);
	
