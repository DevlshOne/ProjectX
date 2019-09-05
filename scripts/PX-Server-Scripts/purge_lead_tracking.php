#!/usr/bin/php
<?php

	require_once("/var/www/html/dev/db.inc.php");



	// 30 DAY TIMEFRAME FOR THE LEAD TRACKING TABLE CLEANUP
	$time = time() - 2592000; // 30 days in the past


	// WEEK TIMEFRAME, IN MILISECONDS - FOR DISPO LOG
	$weektime = time() - 604800; // 7 days in the past
	$mstime = $weektime * 1000;  // TIME IN MILISECONDS



	echo "Time(lead_tracking) $time MSTIME(dispo_log): $mstime\n";


	$cnt = execSQL("DELETE FROM lead_tracking WHERE time < '$time'");
	echo "Lead tracking done, $cnt records\n";


	$cnt = execSQL("DELETE FROM dispo_log WHERE micro_time < '$mstime'");
	echo "Dispo log done, $cnt records\n";
