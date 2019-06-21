#!/usr/bin/php
<?php
	$basedir = "/var/www/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");


	$stime = mktime(0,0,0, date("m"), date("j"), date("Y") );
	$etime = $stime + 86399;



	// LOOP THROUGH CLUSTERS
	foreach($_SESSION['site_config']['db'] as $idx=>$db){

		$cnt = 0;

		connectViciDB($idx);

		// GRAB ITS DISPO TOTALS

		$res = query("SELECT user,sum(dispo_sec) as dispo_sec FROM asterisk.vicidial_agent_log WHERE unix_timestamp(event_time) BETWEEN '$stime' AND '$etime' GROUP BY user",1);
		$users = array();
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			if(!isset($users[$row['user']]))$users[$row['user']] = 0;

			$users[$row['user']] += $row['dispo_sec'];

		}

//print_r($users);

		// UPDATE PX activity_log

		connectPXDB();

		foreach($users as $username=>$dispo_secs){

			$cnt += execSQL("UPDATE `activity_log` SET seconds_DISPO='".intval($dispo_secs)."' WHERE username='".addslashes($username)."' AND time_started BETWEEN '$stime' AND '$etime' ");

		}


		echo $cnt." records updated on vici: ".$db['name']."\n";

		// CHECK TO MAKE SURE THE TABLE HASNT BEEN PURGED


	}
