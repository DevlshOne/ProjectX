#!/usr/bin/php
<?
/**
 * GENERATE vicihours.xml, BASED ON PX'S ACTIVITY LOG
 * Written By: Jonathan Will
 */

date_default_timezone_set('America/Los_Angeles');

	function getBaseDir(){$out = dirname($_SERVER["SCRIPT_NAME"]); if($out[strlen($out)-1] != '/')$out .= '/'; return $out; }

	$basedir = getBaseDir();

	require_once($basedir."/../site_config.php");
	//require_once("../site_config.php");
	require_once($_SESSION['site_config']['basedir']."/db.inc.php");
	require_once($_SESSION['site_config']['basedir']."/utils/report_utils.php");
	require_once($_SESSION['site_config']['basedir']."/utils/stripurl.php");


	$hours_file = $_SESSION['site_config']['xml_dir']."vicihours.xml";


	$stime = mktime(0,0,0, date("m"), date("d"), date("Y"));
	$etime = $stime + 86399;

	//$user_arr = array();
	$sql = "SELECT username,SUM(activity_time) AS activity_time_total FROM activity_log WHERE time_started BETWEEN '$stime' AND '$etime' GROUP BY `username` ";

	//$sql = "SELECT username,SUM(seconds_INCALL+seconds_READY+seconds_QUEUE) AS activity_time_total FROM activity_log WHERE time_started BETWEEN '$stime' AND '$etime' GROUP BY `username` ";

	//echo $sql;

	$res = query($sql, 1);

	$output = "<"."?xml version=\"1.0\" standalone=\"yes\"?".">\n<DocumentElement>\n";



	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

		//$user_arr[$row['username']] = $row['activity_time_total'];

		$hours = $row['activity_time_total'] / 60;
		//$hours = $row['activity_time_total'] / 3600;

		$output .= "<vicihours>\n";
		$output .= "\t<user>".htmlentities($row['username'])."</user>\n";
		$output .= "\t<hours>".htmlentities($hours)."</hours>\n";
		$output .= "</vicihours>\n";

	}


$output .= "</DocumentElement>\n";



$fh = fopen($hours_file, "w");
fwrite($fh, $output, strlen($output));
fclose($fh);

//echo $output;


