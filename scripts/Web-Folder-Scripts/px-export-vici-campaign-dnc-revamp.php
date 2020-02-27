#!/usr/bin/php
<?php
/**
 * EXPORT PX's CAMPAIGN SPECIFIC DNC TO EVERY VICI CLUSTER
 * Written By: Jonathan Will
 */
	$base_dir = "/var/www/html/reports/";

	$max_insert_count = 1000; // HOW MANY RECORDS AT A TIME TO INSERT


	
	$force_cluster_id = 0; 
	$truncate_first = false; // EMPTY THE CAMPAIGN DNC table in vicidial before rebuilding, to avoid "missing unique indexes causing records to stack issue", on some clusters
	
	
	// FIRST ARGUMENT (FORCE CLUSTER ID) - One cluster at a time
	if(count($argv) > 1 && intval($argv[1]) > 0){
		
		$force_cluster_id = intval($argv[1]);
		
	}
	
	
	
	// SECOND ARGUMENT (TRUNCATE TABLE) - 0 or 1
	if(count($argv) > 2 && trim($argv[2]) == "1"){
		
		$truncate_first = true; // EMPTY THE CAMPAIGN DNC table in vicidial before rebuilding, to avoid "missing unique indexes causing records to stack issue", on some clusters
	}
	
	
	
	
	
	
	
	
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
	$PX_DB = $_SESSION['db']; // SAVE TEH CONNECTION FOR EASY SWITCHING
	
	

	
	
	
	
	
	
	
	if($force_cluster_id){
		
		$res = query("SELECT * FROM vici_clusters WHERE `status`='enabled' ".
				
				" AND id='".$force_cluster_id."' ".
				
				" ORDER BY `name` ASC",1);
	}else{
		
		$res = query("SELECT * FROM vici_clusters WHERE `status`='enabled' ".
				
				" AND `cluster_type` != 'verifier' ".
				
				" ORDER BY `name` ASC",1);
	}
	

	/**
	 * CONNECT TO CLUSTERS AND PUSH NULL-CAMPAIGN DNC AND THE PER-CAMPAIGN DNC
	 */
	
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
	$dnc_all_campaigns_withNIX = array();
	$dnc_all_campaigns_NONIX = array();
	
	echo date("H:i:s m/d/Y")." - Loading ALL (PERM DNC & NIX) to memory...";
	$res = query("SELECT `phone` FROM `dnc_campaign_list` WHERE `campaign_code`='[ALL]' AND `dnc_type` IN('DNC','NIX')", 1);
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
		$dnc_all_campaigns_withNIX[]['phone_number'] = $row['phone'];
	}

	echo number_format(count($dnc_all_campaigns_withNIX))." loaded.\n";
	
	echo date("H:i:s m/d/Y")." - Loading ALL (PERM) DNC to memory...";
	$res = query("SELECT `phone` FROM `dnc_campaign_list` WHERE `campaign_code`='[ALL]' AND `dnc_type`='DNC'", 1);
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
		$dnc_all_campaigns_NONIX[]['phone_number'] = $row['phone'];
	}
	
	echo number_format(count($dnc_all_campaigns_NONIX))." loaded.\n";
	
	
/*	
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
**/
	
	
	// STRATIGICALLY LOOP THROUGH IT TO DO MANAGABLE CHUNKS AT A TIME



	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($clusters as $cluster_id => $vicidb ){

		$all_campaigns = array();



		// LOCATE WHICH DB INDEX IT IS
		$dbidx = getClusterIndex($cluster_id);


		if($dbidx < 0){
			echo date("H:i:s m/d/Y")." - ERROR WITH CLUSTER ID#".$cluster_id." - ".$vicidb['ip_address']." - Cannot locate cluster on site_config/cluster stack, SKIPPING\n";
			continue;
		}

		
		
		connectPXDB();
		
		if($vicidb['cluster_type'] == 'taps'){
			
			$dnc_all_campaigns = $dnc_all_campaigns_NONIX;
		}else{
			
			$dnc_all_campaigns = $dnc_all_campaigns_withNIX;
			
		}

		
		
		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);


		echo date("H:i:s m/d/Y")." - Connected to CLUSTER ID#".$cluster_id." - ".$vicidb['ip_address']."\n";

		
		if($truncate_first){
			
			echo date("H:i:s m/d/Y")." - TRUNCATING existing campaign DNC records, before rebuilding.\n";
			
			execSQL("TRUNCATE TABLE `vicidial_campaign_dnc`;");
		}
		

// GRAB ARRAY OF DISINCT CAMPAIGNS (FOR THE _ALL_ CAMPAIGNS OPTION (NULL CAMPAIGN SETTING)
		// POPULATE $all_campaigns WITH THE CAMPAIGNS ON TEH CLUSTER (FOR THE GLOBAL/ALL CAMPAIGN DNCs)
		$res = query("SELECT campaign_id FROM `vicidial_campaigns` WHERE active='Y'", 1); // WHERE active='Y' ??
		$campaign_in_str = '';
		$t=0;
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			
			$campaign_in_str .= ($t++ > 0)?',':'';
			
			$campaign_in_str .= "'".addslashes($row['campaign_id'])."'";
			
			$all_campaigns[] = $row['campaign_id'];

		}


		// ADD THE GLOBAL DNC TO EVERY CAMPAIGN THAT THE CLUSTER HAS
		foreach($all_campaigns as $campaign){

			
			// PUSH (OR OVERWRITE) THE CAMPAIGN CODE ONTO THE STACK
			foreach($dnc_all_campaigns as $idx=>$row){
				//[]['phone_number']
				$dnc_all_campaigns[$idx]['campaign_id'] = $campaign;
			}


			
			$cluster_total += bulkAddChunks($dnc_all_campaigns, 'vicidial_campaign_dnc', $max_insert_count, true);

			echo "Pushing batch of ALL DNC's for ($campaign), Processed: ".count($dnc_all_campaigns).", total added ".number_format($cluster_total)."\n";
			//$dnc_all_campaigns
		}
		
		
		$VICI_DB = $_SESSION['db'];
		
		
		$_SESSION['db'] = $PX_DB;
		
		
		
		// THEN ADD THE CAMPAIGN SPECIFIC DNCS
		$sql = "SELECT `campaign_code`, `phone` FROM `dnc_campaign_list` WHERE `campaign_code` IN ($campaign_in_str) ";
	
		//echo $sql;
		$res = query($sql, 1);
		$x=0;
		$dnc_by_campaign = array();
		
		echo date("H:i:s m/d/Y")." - Processing ".number_format(mysqli_num_rows($res))." Campaign DNC records for '".$vicidb['name']."'\n";
		
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			$dnc_by_campaign[$x] = array();
			$dnc_by_campaign[$x]['phone_number'] = $row['phone'];
			$dnc_by_campaign[$x]['campaign_id'] = $row['campaign_code'];
			
			$x++;
			
			
			if($x >= $max_insert_count){

				$_SESSION['db'] = $VICI_DB;
				
				
				
				$cluster_total += bulkAddChunks($dnc_by_campaign, 'vicidial_campaign_dnc', $max_insert_count, true);
				
				//echo date("H:i:s m/d/Y")." - Pushing batch of Campaign DNC's, Processed: ".count($dnc_by_campaign).", total added ".number_format($cluster_total)."\n";
				
				echo '.';
				
				$_SESSION['db'] = $PX_DB;
				
				$dnc_by_campaign = array();
				$x=0;
			}
		}
		
		// HANDFUL OF REMAINING RECORDS
		if($x > 0){
			
			$_SESSION['db'] = $VICI_DB;
			
			$cluster_total += bulkAddChunks($dnc_by_campaign, 'vicidial_campaign_dnc', $max_insert_count, true);
			
		//	echo date("H:i:s m/d/Y")." - Pushing last batch of Campaign DNC's, Processed: ".count($dnc_by_campaign).", total added ".number_format($cluster_total)."\n";
			
			
			$_SESSION['db'] = $PX_DB;
			
			$dnc_by_campaign = array();
			$x=0;
		}
		
		echo "\n";

//print_r($dnc_by_campaign);

		
		


	}// END FOREACH(vici cluster)



	$timer_end = microtime_float();
	$runtime = $timer_end - $timer_start;

	$str = date("H:i:s m/d/Y")." - Cluster(s) Total: ".number_format($cluster_total)."\n";

	$str .= "Run time: ".$runtime." seconds\n";


	$process_logs .= $str;
	echo $str;
	
	$_SESSION['db'] = $PX_DB;
	
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $process_logs);
	

