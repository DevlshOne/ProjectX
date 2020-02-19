#!/usr/bin/php
<?php
/**
 * EXPORT PX's CAMPAIGN SPECIFIC DNC TO EVERY VICI CLUSTER
 * Written By: Jonathan Will
 */
	$base_dir = "/var/www/html/reports/";

	$max_insert_count = 1000; // HOW MANY RECORDS AT A TIME TO INSERT


	$cold_only_mode = true;	// ONLY EXPORT TO CLUSTERS MARKED COLD OR CONTAINING COLD ('cold','coldtaps','all')


	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."utils/db_utils.php");
	include_once($base_dir."utils/microtime.php");

	include_once($base_dir."dbapi/dbapi.inc.php");
	
	global $process_name;
	
	$process_name = "px-export-vici-campaign-dnc";
	
	$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv));
	
	$process_logs = '';
	
	
	// CONNECT PX DB FIRST
	connectPXDB();


	/**
	 * CONNECT TO COLD CLUSTERS AND PUSH NULL-CAMPAIGN DNC AND THE PER-CAMPAIGN DNC
	 * (taps will be done seperately,below this stuff)
	 */
	$res = query("SELECT * FROM vici_clusters WHERE `status`='enabled' ".
				(($cold_only_mode == true)?" AND `cluster_type` IN ('cold','coldtaps','all')":"").

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
//	connectListDB();

	// FIRST GRAB THE GLOBAL DNC'S AND NIX'S (FOR COLD ONLY)
	$dnc_all_campaigns = array();
	$res = query("SELECT `phone` FROM `dnc_campaign_list` WHERE campaign_code='[ALL]' AND dnc_type IN('DNC','NIX')", 1);
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
		$dnc_all_campaigns[]['phone_number'] = $row['phone'];
	}

	
	
	$dnc_by_campaign = array();
	$res = query("SELECT `campaign_code`, `phone` FROM `dnc_campaign_list` WHERE campaign_code != '[ALL]'", 1);
	$cnt = 0;
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
//		if(!array_key_exists($row['campaign_code'], $dnc_by_campaign)){//!is_array($dnc_by_campaign[$row['campaign_code']])){
//			$dnc_by_campaign[$row['campaign_code']] = array();
//		}

		$dnc_by_campaign[$cnt] = array();
		$dnc_by_campaign[$cnt]['phone_number'] = $row['phone'];
		$dnc_by_campaign[$cnt]['campaign_id'] = $row['campaign_code'];

		$cnt++;
	}

	$str = "Done loading DNCs to memory, ".
			number_format(count($dnc_all_campaigns))." all-campaign DNCs, ".
			number_format($cnt)." campaign specific dncs total.\n";

	$process_logs .= $str;
	echo $str;

	// STRATIGICALLY LOOP THROUGH IT TO DO MANAGABLE CHUNKS AT A TIME



	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($clusters as $cluster_id => $vicidb ){

		$all_campaigns = array();

		$vici_cluster_id = $cluster_id;

		// LOCATE WHICH DB INDEX IT IS
		$dbidx = getClusterIndex($cluster_id);


		if($dbidx < 0){
			echo date("H:i:s m/d/Y")." - ERROR WITH CLUSTER ID#".$cluster_id." - ".$vicidb['ip_address']." - Cannot locate cluster on site_config/cluster stack, SKIPPING\n";
			continue;
		}

		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);


		echo date("H:i:s m/d/Y")." - Connected to CLUSTER ID#".$cluster_id." - ".$vicidb['ip_address']."\n";


// GRAB ARRAY OF DISINCT CAMPAIGNS (FOR THE _ALL_ CAMPAIGNS OPTION (NULL CAMPAIGN SETTING)
		// POPULATE $all_campaigns WITH THE CAMPAIGNS ON TEH CLUSTER (FOR THE GLOBAL/ALL CAMPAIGN DNCs)
		$res = query("SELECT campaign_id FROM `vicidial_campaigns`", 1); // WHERE active='Y' ??
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			$all_campaigns[] = $row['campaign_id'];
		}



		// ADD THE GLOBAL DNC TO EVERY CAMPAIGN THAT THE CLUSTER HAS
		foreach($all_campaigns as $campaign){

			// PUSH (OR OVERWRITE) THE CAMPAIGN CODE ONTO THE STACK
			foreach($dnc_all_campaigns as $idx=>$row){
				//[]['phone_number']
				$dnc_all_campaigns[$idx]['campaign_id'] = $campaign;
			}

//print_r($dnc_all_campaigns);

			$cluster_total += bulkAddChunks($dnc_all_campaigns, 'vicidial_campaign_dnc', $max_insert_count, true);

			//$dnc_all_campaigns
		}

//print_r($dnc_by_campaign);

		// THEN ADD THE CAMPAIGN SPECIFIC DNCS
		$cluster_total += bulkAddChunks($dnc_by_campaign, 'vicidial_campaign_dnc', $max_insert_count, true);










	}// END FOREACH(vici cluster)














	$timer_end = microtime_float();
	$runtime = $timer_end - $timer_start;

	$str = date("H:i:s m/d/Y")." - Cold Cluster(s) Total: ".number_format($cluster_total)."\n";

	$str .= "Run time: ".$runtime." seconds\n";


	$process_logs .= $str;
	echo $str;
	
	echo "Preparing to process TAPS based servers...\n";


	echo "Loading Global DNC's (skipping NIX)...\n";

	// GRAB THE DNC DATA FROM PX
//	connectListDB();

	// USE THE PX DB INSTEAD OF LIST TOOL
	connectPXDB();

	$dnc_all_campaigns = array();
	$res = query("SELECT `phone` FROM `dnc_campaign_list` WHERE campaign_code='[ALL]' AND dnc_type='DNC'", 1);
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
		$dnc_all_campaigns[]['phone_number'] = $row['phone'];
	}




	/**
	 * PREPARE TO PUSH TAPS NULL-CAMPAIGN DNCs
	 */

	$timer_start = microtime_float();
	$run_time = time();
	$cluster_total = 0;

	$res = query("SELECT * FROM vici_clusters WHERE `status`='enabled' ".
				" AND `cluster_type` IN ('taps')".

			//	" AND id='1' ".

				" ORDER BY `name` ASC",1);
	$clusters = array();
	while($row = mysqli_fetch_array($res)){

		$clusters[$row['id']] = $row;

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


		echo date("H:i:s m/d/Y")." - Connected to TAPS CLUSTER ID#".$cluster_id." - ".$vicidb['ip_address']."\n";


// GRAB ARRAY OF DISINCT CAMPAIGNS (FOR THE _ALL_ CAMPAIGNS OPTION (NULL CAMPAIGN SETTING)
		// POPULATE $all_campaigns WITH THE CAMPAIGNS ON TEH CLUSTER (FOR THE GLOBAL/ALL CAMPAIGN DNCs)
		$res = query("SELECT campaign_id FROM `vicidial_campaigns`", 1); // WHERE active='Y' ??
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			$all_campaigns[] = $row['campaign_id'];
		}



		// ADD THE GLOBAL DNC TO EVERY CAMPAIGN THAT THE CLUSTER HAS
		foreach($all_campaigns as $campaign){

			// PUSH (OR OVERWRITE) THE CAMPAIGN CODE ONTO THE STACK
			foreach($dnc_all_campaigns as $idx=>$row){
				//[]['phone_number']
				$dnc_all_campaigns[$idx]['campaign_id'] = $campaign;
			}

//print_r($dnc_all_campaigns);

			$cluster_total += bulkAddChunks($dnc_all_campaigns, 'vicidial_campaign_dnc', $max_insert_count, true);

			//$dnc_all_campaigns
		}

	}



	$timer_end = microtime_float();
	$runtime = $timer_end - $timer_start;

	$str = date("H:i:s m/d/Y")." - TAPS Cluster(s) Total: ".number_format($cluster_total)."\n";

	$str .="Run time: ".$runtime." seconds\n";

	
	$process_logs .= $str;
	echo $str;
	
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $process_logs);
	

