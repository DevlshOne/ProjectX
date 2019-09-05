#!/usr/bin/php
<?php
/****
 * Runs SQL statements against the PX DB - Used for running updates after hours
 * Written By: Jonathan Will - 1-9-2017
 */
	$basedir = "/var/www/html/dev/"; // FOR PRODUCTION

	include_once($basedir."db.inc.php");
	include_once($basedir."utils/db_utils.php");

	connectPXDB();


	$sql_array = array(

		"TRUNCATE dispo_log"

	//	"ALTER TABLE `lead_tracking` ADD `nams_invoice_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `list_id`, ADD `callback_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `nams_invoice_id`;",
	//"ALTER TABLE `sales` ADD `nams_invoice_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `sale_time`, ADD `callback_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `nams_invoice_id`;"
		//"CREATE INDEX `office_time` ON lead_tracking (office,time)",
		// "", // MORE SQL COULD GO HERE

	);



	echo date("g:i:s m/d/Y")." - Executing SQL command(s)!\n";



	foreach($sql_array as $sql){

		echo "Running: ".$sql."\n";

		$cnt = execSQL($sql);

		echo "Done - Result: ".$cnt."\n";

	}




	echo date("g:i:s m/d/Y")." - Done!\n";
