#!/usr/bin/php
<?php
/****
 * Runs SQL statements against the PX DB - Used for running updates after hours
 * Written By: Jonathan Will - 1-9-2017
 */
	//$basedir = "/var/www/dev/"; // FOR PRODUCTION
	$basedir = "/var/www/html/dev/"; // FOR DEV

	include_once($basedir."db.inc.php");
	include_once($basedir."utils/db_utils.php");

	connectPXDB();


	$sql_array = array(

		"CREATE INDEX `office_time` ON lead_tracking (office,time)",
		// "", // MORE SQL COULD GO HERE

	);



	echo date("g:i:s m/d/Y")." - Executing SQL command(s)!\n";



	foreach($sql_array as $sql){

		echo "Running: ".$sql."\n";

		$cnt = execSQL($sql);

		echo "Done - Result: ".$cnt."\n";

	}




	echo date("g:i:s m/d/Y")." - Done!\n";