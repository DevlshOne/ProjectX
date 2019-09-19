#!/usr/bin/php
<?php
/**
 * IMPORT CAMPAIGN SPECIFIC DNC TO PX'S LIST TOOL CAMPAIGN DNC MASTER LIST
 * Written By: Jonathan Will
 */
	$base_dir = "/var/www/html/dev2/";

	$max_insert_count = 1000; // HOW MANY RECORDS AT A TIME TO INSERT


	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."utils/db_utils.php");
	include_once($base_dir."utils/microtime.php");


	// CONNECT PX DB FIRST
	connectPXDB();



	$vici_groups = array();


	$timer_start = microtime_float();

	$run_time = time();

	$cluster_total = 0;




	$vici_list_cache = array();

	connectCCIDB();

//	// LOOP THROUGH STACK OF VICIDIAL SERVERS
//	foreach($_SESSION['site_config']['db'] as $dbidx=>$vicidb){
//
//		// CONNECT TO VICIDIAL DB
//		connectViciDB($dbidx);


	echo "Connected to CCIDATA DB\n";




/***
 * PULL FROM CAMPAIGN DNC LIST
 */				 //	`Project` as `campaign_id`,
	$res = query("SELECT `Phone Number` as `phone_number` FROM ccidata.dnc_list WHERE `DNC all` = 1", 1);

	$rowarr = array();
	$running_cnt = 0;

	while($row = mysqli_fetch_array($res,MYSQLI_ASSOC)){

		if(trim($row['phone_number']) == '')continue;

		//$campaign_id = $row['campaign_id'];

		$rowarr[] = array(
			'phone' => $row['phone_number'],
			//'campaign_code' => null,//$campaign_id,//$row['campaign_id'],
			'time_added'	=> $run_time
		);



		// ONCE THE INSERT LIMIT HAS BEEN REACHED, DO A BULK INSERT
		if(count($rowarr) >= $max_insert_count){

//print_r($rowarr);
			connectListDB();

			echo "Pushing ".number_format(count($rowarr))." Campaign DNC numbers (".number_format($running_cnt).")...\n";

			// DO BULK INSERT
			$ecnt = bulkAdd($rowarr, 'dnc_campaign_list', true);

			$running_cnt += $ecnt;

			if($ecnt < count($rowarr)){
				echo "Partial insert: ".number_format($ecnt)." out of ".number_format(count($rowarr))." rows.\n";
			}

			// THEN RESET THE ROWARR STACK AND COUNTER
			$rowarr = array();


			//connectViciDB($dbidx);
		}
	}


	// DO THE REMAINING RECORDS
	if(count($rowarr) > 0){


		connectListDB();


		echo "Pushing ".number_format(count($rowarr))." Campaign DNC numbers (".number_format($running_cnt).")\n";

		// DO BULK INSERT
		$ecnt = bulkAdd($rowarr, 'dnc_campaign_list', true);

		$running_cnt += $ecnt;

		//echo "Bulk inserted: ".number_format($ecnt)." rows.\n";

		// THEN RESET THE ROWARR STACK AND COUNTER
		$rowarr = array();
	}

	$cluster_total += $running_cnt;


	echo "Done. Total=".number_format($running_cnt)."\n";


	$timer_end = microtime_float();
	$runtime = $timer_end - $timer_start;


	echo "Run time: ".$runtime." seconds\n";

