#!/usr/bin/php
<?

    require_once("/var/www/db.inc.php");


	$time = time() - 2592000; // 30 days in the past


	$mstime = $time * 1000;  // TIME IN MILISECONDS

echo "Time $time MSTIME: $mstime\n";

	$cnt = execSQL("DELETE FROM lead_tracking WHERE time < '$time'");

	echo "Lead tracking done, $cnt records\n";


	$cnt = execSQL("DELETE FROM dispo_log WHERE micro_time < '$mstime'");

	echo "Dispo log done, $cnt records\n";