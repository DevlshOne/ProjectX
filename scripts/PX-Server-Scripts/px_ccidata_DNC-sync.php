#!/usr/bin/php
<?php

	$basedir = "/var/www/html/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");



	$max_records_per_insert = 5000;

//	$days = 7;
//	$stime = mktime(0,0,0);
//	$etime = mktime(23,59,59);
//
//	// GO BACK 7 DAYS
//	$stime -= (86400 * $days);




	connectPXDB();


	// GET DNC FROM PX
	$res = query("SELECT phone_num FROM lead_tracking WHERE dispo='DNC'  ", 1); //AND time BETWEEN '$stime' AND '$etime'

	$rowarr = array();
	while($row = mysqli_fetch_row($res)){
		$rowarr[] = $row[0];
	}


	// INSERT TO CCI
	connectCCIDB();
	$x=0;
	$total_cnt = 0;
	$base_sql = "INSERT IGNORE INTO master_dnc(phone) VALUES ";
	$insert_sql = $base_sql;
	foreach($rowarr as $phone){
		if($x++ > 0)$insert_sql .= ",";
		$insert_sql .= "('".addslashes($phone)."')";

		if($x >= $max_records_per_insert){
			$cnt = execSQL($insert_sql);
			// RUN THE QUERY, RESET STRING/VARIABLES
			echo "Inserted ".number_format($cnt)." records.\n";

			$total_cnt += $cnt;

			$x = 0;
			$insert_sql = $base_sql;
		}
	}

	// RUN ANY REMAINING RECORDS THAT ARE < 5000 in count.
	if($x > 0){
		$cnt = execSQL($insert_sql);
		// RUN THE QUERY, RESET STRING/VARIABLES
		echo "Inserted ".number_format($cnt)." records.\n";

		$total_cnt += $cnt;
	}

	echo "Done. Total ".number_format($total_cnt)."\n";


