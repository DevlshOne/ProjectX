#!/usr/bin/php
<?php
/**
 * IMPORT CAMPAIGN SPECIFIC DNC TO PX'S LIST TOOL CAMPAIGN DNC MASTER LIST
 * Written By: Jonathan Will
 */
	$base_dir = "/var/www/html/dev2/";

	$max_insert_count = 1000; // HOW MANY RECORDS AT A TIME TO INSERT

	$cold_only_mode = true;


	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."utils/db_utils.php");
	include_once($base_dir."utils/microtime.php");


	// CONNECT PX DB FIRST
	connectPXDB();



	$vici_groups = array();

	$res = query("SELECT * FROM vici_clusters WHERE `status`='enabled' ".
				(($cold_only_mode == true)?" AND `cluster_type` IN ('cold','coldtaps','all')":"").
				" ORDER BY `name` ASC",1);
	$clusters = array();
	while($row = mysqli_fetch_array($res)){

		$clusters[$row['id']] = $row;

	}

	$timer_start = microtime_float();

	$run_time = time();

	$cluster_total = 0;



	// LOOP THROUGH STACK OF VICIDIAL SERVERS
	foreach($clusters as $cluster_id => $vicidb ){

		$vici_list_cache = array();

		$vici_cluster_id = $cluster_id;

		// LOCATE WHICH DB INDEX IT IS
		$dbidx = getClusterIndex($cluster_id);

		// CONNECT TO VICIDIAL DB
		connectViciDB($dbidx);


//	// LOOP THROUGH STACK OF VICIDIAL SERVERS
//	foreach($_SESSION['site_config']['db'] as $dbidx=>$vicidb){
//
//		// CONNECT TO VICIDIAL DB
//		connectViciDB($dbidx);


		echo "Connected to ViciDB @ ".$vicidb['ip_address'].' - '.$vicidb['name']."\n";

		// LOAD THE LIST CAMPAIGN CACHE
		$res = query("SELECT `list_id`, `campaign_id` FROM vicidial_lists ", 1 );

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$vici_list_cache[$row['list_id']] = $row['campaign_id'];
		}


/***
 * PULL FROM CAMPAIGN DNC LIST
 */
		$res = query("SELECT * FROM vicidial_campaign_dnc", 1);

		$rowarr = array();
		$running_cnt = 0;

		while($row = mysqli_fetch_array($res,MYSQLI_ASSOC)){

			if(trim($row['phone_number']) == '')continue;


			// LOOK UP PROPER CAMPAIGN CODE VIA "vicidial_list" AND CUSTOM FIELDS
			list($list_id, $lead_id)= queryROW("SELECT list_id,lead_id FROM vicidial_list WHERE phone_number='".$row['phone_number']."'");

			if(!$list_id){
				// NOT FOUND? TRY LOGS AND ARCHIVE LISTS?
				// FALLBACK TO LOOKING UP CAMPAIGN CODE VIA PX

				//echo "Skipping ".$row['phone_number'].", lead not found.\n";
				continue;
			}

			// WORST CASE, ITS AT LEAST SET TO SOMETHING
			$campaign_id = $row['campaign_id'];

			echo "Checking for lead $lead_id on custom list ".$list_id.": ";

			try{
				list($cid) = queryROW("SELECT `campaign` FROM custom_".addslashes($list_id)." WHERE lead_id='$lead_id'");

				if($cid){
					$campaign_id = $cid;
					echo "FOUND ".$campaign_id."\n";

				}else{

					echo "NOT FOUND.\n";
				}

			}catch(Exception $e){
				echo "NOT FOUND (table).\n";
			}


		// OLD ATTEMPT, VIA LIST'S CAMPAIGN, ATTEMPTING CUSTMO FIELDS LOOKUIP NOW
//			echo "Checking for list ".$list_id.": ";
//
//			if($vici_list_cache[$list_id]){
//				echo "FOUND ".$vici_list_cache[$list_id]."\n";
//			}else{
//				echo "Nope using ".$row['campaign_id']."\n";
//
//			}
//
//			$campaign_id = ($vici_list_cache[$list_id])? $vici_list_cache[$list_id]:$row['campaign_id'];

			if(stripos($campaign_id, "_90_") > -1){
				$campaign_id = substr($campaign_id, 4);
			}


			$rowarr[] = array(
				'phone' => $row['phone_number'],
				'campaign_code' => $campaign_id,//$row['campaign_id'],
				'time_added'	=> $run_time
			);



			// ONCE THE INSERT LIMIT HAS BEEN REACHED, DO A BULK INSERT
			if(count($rowarr) >= $max_insert_count){

//print_r($rowarr);
				connectListDB();

				echo $vicidb['ip_address'].' - '.$vicidb['name']. ": Pushing ".number_format(count($rowarr))." Campaign DNC numbers (".number_format($running_cnt).")...\n";

				// DO BULK INSERT
				$ecnt = bulkAdd($rowarr, 'dnc_campaign_list', true);

				$running_cnt += $ecnt;

				if($ecnt < count($rowarr)){
					echo "Partial insert: ".number_format($ecnt)." out of ".number_format(count($rowarr))." rows.\n";
				}

				// THEN RESET THE ROWARR STACK AND COUNTER
				$rowarr = array();


				connectViciDB($dbidx);
			}
		}


		// DO THE REMAINING RECORDS
		if(count($rowarr) > 0){


			connectListDB();


			echo $vicidb['ip_address'].' - '.$vicidb['name']. ": Pushing ".number_format(count($rowarr))." Campaign DNC numbers (".number_format($running_cnt).")\n";

			// DO BULK INSERT
			$ecnt = bulkAdd($rowarr, 'dnc_campaign_list', true);

			$running_cnt += $ecnt;

			//echo "Bulk inserted: ".number_format($ecnt)." rows.\n";

			// THEN RESET THE ROWARR STACK AND COUNTER
			$rowarr = array();
		}

		$cluster_total += $running_cnt;


		echo $vicidb['ip_address'].' - '.$vicidb['name']. ": Done. Total=".number_format($running_cnt)."\n";


	}// END FOREACH (cluster)

	$timer_end = microtime_float();
	$runtime = $timer_end - $timer_start;

	echo "Cluster Total: ".number_format($cluster_total)."\n";

	echo "Run time: ".$runtime." seconds\n";

