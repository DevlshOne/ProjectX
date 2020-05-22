#!/usr/bin/php
<?php


	$basedir = "/var/www/html/reports/";

	include_once($basedir."db.inc.php");
	include_once($basedir."utils/microtime.php");
	include_once($basedir."utils/format_phone.php");
	include_once($basedir."utils/db_utils.php");


//print_r($argv);

	if($argv[1]){



		$tmpdate = strtotime($argv[1]);

		if(!$tmpdate){
			$stime = mktime(0,0,0);
		}else{
			$stime = $tmpdate;
		}

	}else{
		$stime = mktime(0,0,0);
	}



	$etime = $stime + 86400;

	
	
	
	include_once($basedir."dbapi/dbapi.inc.php");
	
	global $process_name;
	
	$process_name = "px_copy_hours_to_ccidata";
	
	$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv), "Hours for ".date("m/d/Y",$stime) );
	
	$process_logs = '';
	
	

	echo "Starting Hours Copy for ".date("m/d/Y",$stime)."...\n";

//exit;

	// CONNECT PX DB
	connectPXDB();

	$res = query("SELECT * FROM activity_log ".
				" WHERE `time_started` BETWEEN '$stime' AND '$etime' ".
				"".
				"");

	$rowarr = array();
	while($row=mysqli_fetch_array($res, MYSQLI_ASSOC)){
		$rowarr[] = $row;
	}



	$str = "Grabbed ".count($rowarr)." employee records, pushing to ccidata.employee_hours...\n";
	
	$process_logs .= $str;
	echo $str;

	// CONNECT TO ANDREWS LABYRINTH
	connectCCIDB();

	// SHOVE DATA INTO THE DB
	// rEPLACE INTO

	$start_sql = "REPLACE INTO `employee_hours` (`agent_id`,`date`,`hours`,`office`, `call_group`) VALUES ";

	$sql = $start_sql;

	$x=0;
	foreach($rowarr as $row){

		$date = date("Y-m-d", $row['time_started']);

		if($x++ > 0) $sql .= ",";

		$sql .= "(".
					"'".addslashes($row['username'])."',".
					"'".addslashes($date)."',".
					"'".addslashes(round((($row['paid_time']+$row['paid_corrections'])/60),2))."',".
					"'".addslashes($row['office'])."',".
					"'".addslashes($row['call_group'])."'".
				")";




		//echo $sql."\n";
	}

//echo $sql."\n";
	$cnt = execSQL($sql);

	$str = "Done. $cnt affected.\n";

	$process_logs .= $str;
	echo $str;
	
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $process_logs);
	
	
