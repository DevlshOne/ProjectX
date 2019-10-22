#!/usr/bin/php
<?php
/**
 * CLEANUP/EXPIRE THE PX/LIST TOOLS CAMPAIGN DNC LIST
 * Written By: Jonathan Will
 */
	$base_dir = "/var/www/html/reports/";

	$purge_interval = 1825; // IN DAYS, THE NUMBER OF DAYS BEFORE A NUMBER IS PURGED
							// 1825 = 5 years
							// 365 = 1 year

	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."util/db_utils.php");
	include_once($base_dir."util/microtime.php");


	include_once($base_dir."dbapi/dbapi.inc.php");
	
	global $process_name;
	
	$process_name = "px-cleanup-campaign-dnc";
	
	$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv));
	$process_logs = '';
	
	
	$timer_start = microtime_float();
	$run_time = time();


	// CONNECT TO THE LIST DATABASE
	connectListDB();


	$delete_time = $run_time - ($purge_interval * 86400);


	$sql = "DELETE FROM `dnc_campaign_list` WHERE `time_added` <= '$delete_time' ".
			"AND (`dnc_type`='NIX' OR `campaign_code` != '[ALL]')";

		//" AND ((`campaign_code`='[ALL]' AND `dnc_type`='NIX') OR (`campaign_code` != '[ALL]'))";


//	echo $sql."\n";
//	exit;

	// RUN QUERY
	$cnt = execSQL($sql);



	$str = date("g:i:s m/d/Y")." - Affected rows: ".number_format($cnt)."\n";
	
	$process_logs .= $str;
	
	echo $str;
	



	$timer_end = microtime_float();
	$runtime = $timer_end - $timer_start;

	$str = date("g:i:s m/d/Y")." - Run time: ".round($runtime,4)." seconds\n";

	$process_logs .= $str;
	
	echo $str;
	
	
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $process_logs);
	
	

