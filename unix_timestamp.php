<?php

	include_once("utils/DropDowns.php");
	

$curtime = time();

?><form method="get" action="<?=$_SERVER['PHP_SELF']?>" >

	String to time: <input type="text" name="timestr" value="<?=(!$_REQUEST['timestr'])?date("m/d/Y"):htmlentities($_REQUEST['timestr'])?>" /> <input type="submit" value="Calculate" /><br />

	<br />
	Timestamp to Date: <input type="text" name="timestampstr" value="<?=(!$_REQUEST['timestampstr'])?$curtime:htmlentities($_REQUEST['timestampstr'])?>" /> <input type="submit" value="Calculate" /><br />

	<br />
	
	Day-Offset Calculator: <?=makeNumberDD('offset_hour', $_REQUEST['offset_hour'], 0, 23, 1, true)?> : <?=makeNumberDD('offset_min', $_REQUEST['offset_min'], 0, 59, 1, true)?> : <?=makeNumberDD('offset_sec', $_REQUEST['offset_sec'], 0, 59, 1, true)?>
	<input type="submit" name="offset_calc" value="Calculate Offset" />

	
</form><?



if(isset($_REQUEST['offset_calc'])){
	
	$hrs = intval($_REQUEST['offset_hour']);
	$mins= intval($_REQUEST['offset_min']);
	$secs= intval($_REQUEST['osset_sec']);
	
	
	$offset_time = ($hrs * 3600) + ($mins * 60) + $secs;
	
	echo "Day Offset Calculation: ".$offset_time."<br /><br />\n\n";
	
}








$time_arr = array();


if($_REQUEST['timestr']){
	
	$time_arr[] = array(strtotime($_REQUEST['timestr']), "String to Time");
	$time_arr[] = 0;
}

if($_REQUEST['timestampstr']){
	
	$time_arr[] = array(intval($_REQUEST['timestampstr']), "Timestamp to Date");
	$time_arr[] = 0;
}


$time_arr[] = array($curtime, "Current Time");


$time_arr[] = 0;
	
$time_arr[] = array(mktime(0,0,0), "Start of today");
$time_arr[] = array($time_arr[count($time_arr)-1][0] - 86400, "Yesterday" );
$time_arr[] = array($time_arr[count($time_arr)-1][0] - 86400, "2 days ago");
$time_arr[] = array($time_arr[count($time_arr)-1][0] - 86400, "3 days ago");
$time_arr[] = array($time_arr[count($time_arr)-1][0] - 86400, "4 days ago");
$time_arr[] = array($time_arr[count($time_arr)-1][0] - 86400, "5 days ago");
$time_arr[] = array($time_arr[count($time_arr)-1][0] - 86400, "6 days ago");
$time_arr[] = array($time_arr[count($time_arr)-1][0] - 86400, "7 days ago");

$time_arr[] = 0;

$time_arr[] = array(mktime(0,0,0, date("m"),1, date("Y")), "Start of ".date("F") );
$time_arr[] = array(mktime(0,0,0, date("m")-1,1, date("Y")), "Start of last month");
$time_arr[] = array(mktime(0,0,0, date("m")-2,1, date("Y")), "Start of 2 months ago");
$time_arr[] = array(mktime(0,0,0, date("m")-3,1, date("Y")), "Start of 3 months ago");
$time_arr[] = array(mktime(0,0,0, date("m")-4,1, date("Y")), "Start of 4 months ago");
$time_arr[] = array(mktime(0,0,0, date("m")-5,1, date("Y")), "Start of 5 months ago");
$time_arr[] = array(mktime(0,0,0, date("m")-6,1, date("Y")), "Start of 6 months ago");

//print_r($time_arr);

foreach($time_arr as $tmparr){
	
	if(!is_array($tmparr) && intval($tmparr) <= 0){echo '<br />'; continue; }
	
	list($time, $desc) = $tmparr;
	
	
	echo $time."&nbsp;&nbsp;&nbsp;&nbsp;".date("H:i:s m/d/Y T", $time)." (".$desc.")<br />";
	
}


