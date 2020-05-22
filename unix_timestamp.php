<?php


?><form method="get" action="<?=$_SERVER['PHP_SELF']?>" >

	String to time: <input type="text" name="timestr" value="<?=(!$_REQUEST['timestr'])?date("m/d/Y"):htmlentities($_REQUEST['timestr'])?>" /> <input type="submit" value="Calculate" />


</form><?

$time_arr = array();


if($_REQUEST['timestr']){
	
	$time_arr[] = strtotime($_REQUEST['timestr']);
	$time_arr[] = 0;
}


$time_arr[] = time();


$time_arr[] = 0;
	
$time_arr[] = mktime(0,0,0);
$time_arr[] = $time_arr[count($time_arr)-1] - 86400;
$time_arr[] = $time_arr[count($time_arr)-1] - 86400;
$time_arr[] = $time_arr[count($time_arr)-1] - 86400;
$time_arr[] = $time_arr[count($time_arr)-1] - 86400;
$time_arr[] = $time_arr[count($time_arr)-1] - 86400;
$time_arr[] = $time_arr[count($time_arr)-1] - 86400;
$time_arr[] = $time_arr[count($time_arr)-1] - 86400;

$time_arr[] = 0;

$time_arr[] = mktime(0,0,0, date("m"),1, date("Y"));
$time_arr[] = mktime(0,0,0, date("m")-1,1, date("Y"));
$time_arr[] = mktime(0,0,0, date("m")-2,1, date("Y"));
$time_arr[] = mktime(0,0,0, date("m")-3,1, date("Y"));
$time_arr[] = mktime(0,0,0, date("m")-4,1, date("Y"));
$time_arr[] = mktime(0,0,0, date("m")-5,1, date("Y"));
$time_arr[] = mktime(0,0,0, date("m")-6,1, date("Y"));


foreach($time_arr as $time){
	
	if(intval($time) <= 0){echo '<br />'; continue; }
	
	echo $time."&nbsp;&nbsp;&nbsp;&nbsp;".date("H:i:s m/d/Y T", $time)."<br />";
	
}


