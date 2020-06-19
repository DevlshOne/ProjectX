#!/usr/bin/php
<?php
	$basedir = "/var/www/html/staging-git/";

	include_once($basedir."site_config.php");
	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
// 	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");



	$stime = 0;


	if(count($argv) > 1 && $argv[1] && ($tmptime = strtotime($argv[1])) > 0){
		
		$stime = mktime(0,0,0, date("m", $tmptime), date("d", $tmptime), date("Y", $tmptime));

	}
	
	include_once($basedir."dbapi/dbapi.inc.php");
	
	global $process_name;
	
	$process_name = "px_update_employee_hours";
	
	$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv));
	
	
	

	echo "Starting '$process_name' script on ".date("m/d/Y").(($stime > 0)?" against Date: ".date('m/d/Y', $stime):'')."...\n";


	// CONNECT PX DB
	connectPXDB();

	
	$process_logs = $_SESSION['dbapi']->employee_hours->autoCalcEmployeeHours($stime);
	
	
	echo $process_logs;
	

	
	
	
	
	
	
	
	
	
	
	
	
	
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $process_logs);
	
