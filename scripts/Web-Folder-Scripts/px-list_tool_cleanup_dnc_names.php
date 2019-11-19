#!/usr/bin/php
<?php
/**
 * PX List Tool - Cleanup DNC NAMES from ALL VICIDIAL CLUSTERS
 * Written By: Jonathan Will
 */
	$base_dir = "/var/www/html/reports/";

	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."utils/db_utils.php");
	include_once($base_dir."utils/microtime.php");

	include_once($base_dir."dbapi/dbapi.inc.php");
	
	global $process_name;
	
	$process_name = "px-list_tool_cleanup_dnc_names";
	
	$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv));
	
	$process_logs = '';
	
	
	// CONNECT PX DB FIRST
	connectPXDB();


	/**
	 * CONNECT TO COLD CLUSTERS AND PUSH NULL-CAMPAIGN DNC AND THE PER-CAMPAIGN DNC
	 * (taps will be done seperately,below this stuff)
	 */
	$res = query("SELECT * FROM vici_clusters WHERE `status`='enabled' ".
				//(($cold_only_mode == true)?" AND `cluster_type` IN ('cold','coldtaps','all')":"").

			//	" AND id='1' ".

				" ORDER BY `name` ASC",1);
	$clusters = array();
	while($row = mysqli_fetch_array($res)){

		$clusters[$row['id']] = $row;

	}

	$timer_start = microtime_float();
	$run_time = time();
	$cluster_total = 0;






	// GRAB THE DNC DATA FROM PX
	connectListDB();

	// FIRST GRAB THE GLOBAL DNC'S AND NIX'S (FOR COLD ONLY)
	$dnc_names = array();
	$res = query("SELECT `first_name`,`last_name` FROM `dnc_name_list` WHERE 1", 1);
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
		$dnc_names[] = $row;
	}

	

	$str = "Done loading DNC Names to memory, ".
			number_format(count($dnc_names)).
			" NAME BASED DNCs total.\n";

	$process_logs .= $str;
	echo $str;

	// STRATIGICALLY LOOP THROUGH IT TO DO MANAGABLE CHUNKS AT A TIME

	$base_sql = "DELETE FROM `vicidial_list` WHERE ";
	$x=0;
	foreach($dnc_names as $row){
		
		
		$base_sql .=($x++ > 0)?" OR ":'';
		
		$base_sql .= "( `last_name`='".mysqli_real_escape_string($_SESSION['db'], $row['last_name'])."' ".(($row['first_name'] != null)?" AND `first_name`='".mysqli_real_escape_string($_SESSION['db'],$row['first_name'])."' ":"").")";
		
		
	}

	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($clusters as $cluster_id => $vicidb ){

		$all_campaigns = array();

		$vici_cluster_id = $cluster_id;

		// LOCATE WHICH DB INDEX IT IS
		$dbidx = getClusterIndex($cluster_id);


		if($dbidx < 0){
			echo "ERROR WITH CLUSTER ID#".$cluster_id." - ".$vicidb['ip_address']." - Cannot locate cluster on site_config/cluster stack, SKIPPING\n";
			continue;
		}

		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);


		echo date("H:i:s m/d/Y")." Connected to CLUSTER ID#".$cluster_id." - ".$vicidb['ip_address']."\n";

		
		
		
		echo date("H:i:s m/d/Y")." Running - ".$base_sql."\n";
		
		$cnt = execSQL($base_sql);
		//$cnt = 1;

		$cluster_total += $cnt;

	}// END FOREACH(vici cluster)














	$timer_end = microtime_float();
	$runtime = $timer_end - $timer_start;

	$str = "Total DNC NAME leads removed: ".number_format($cluster_total)."\n";

	$str .= "Run time: ".$runtime." seconds\n";

	$process_logs .= $str;
	echo $str;
	
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $process_logs);
	

