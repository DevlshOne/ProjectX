#!/usr/bin/php
<?php


	$basedir = "/var/www/html/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");


print_r($argv);

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



	echo "Grabbed ".count($rowarr)." employee records, pushing to ccidata.employee_hours...\n";


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
					"'".addslashes(round($row['paid_time']/60,2))."',".
					"'".addslashes($row['office'])."',".
					"'".addslashes($row['call_group'])."'".
				")";




		//echo $sql."\n";
	}

//echo $sql."\n";
	$cnt = execSQL($sql);

	echo "Done. $cnt affected.\n";


