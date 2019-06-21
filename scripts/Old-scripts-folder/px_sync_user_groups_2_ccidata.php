#!/usr/bin/php
<?php
	$basedir = "/var/www/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");


	// CONNECT TO PX AND BUILD A DISTINCT LIST OF ALL USER GROUPS
	connectPXDB();


	$res = query("SELECT DISTINCT(user_group),office FROM user_groups", 1);


	$group_arr = array();
	while($row=mysql_fetch_array($res, MYSQL_ASSOC)){

		$group_arr[] = array($row['user_group'], $row['office']);

	}


	// CONNECT TO THE CCIDB, AND INSERT IGNORE CHANGES
	connectCCIDB();

	$sql = "INSERT IGNORE INTO `callgroups`(`group_id`, `office`, `description`, `vici_group_id`, `excel_group`) VALUES ";

	$x=0;
	foreach($group_arr as $data){

		list($group,$office) = $data;

		if($x++ > 0)$sql .= ",";

		$sql .= "('".addslashes($group)."','".addslashes($office)."','".addslashes($group)."','".addslashes($group)."',0) ";


	}

	$cnt = execSQL($sql);

	echo $cnt." records inserted";

