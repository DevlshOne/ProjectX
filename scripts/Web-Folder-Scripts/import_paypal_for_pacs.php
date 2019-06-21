#!/usr/bin/php
<?php

	$base_dir = "/var/www/html/dev2/";


	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."util/db_utils.php");

	include_once($base_dir."dbapi/dbapi.inc.php");
	include_once($base_dir."classes/pac_reports.inc.php");


//	global $rejects_pile;
//	global $format_mode;
//	global $project;


	$_SESSION['pac_reports']->project = "";


	// METHOD 0: SKIP THE PAYMENT GATEWAY FIELD (19 fields)
	// METHOD 1: INCLUDE THE PAYMENT GATEWAY AS THE [12] INDEX (20 fields)
	// METHOD 2: DOESN'T HAVE THE PAYMENT GATEWAY, OR THE VERIFICATION OF PHONE NUMBER (18 fields)
	$_SESSION['pac_reports']->format_mode = 1;






	connectPXDB();



	if(count($argv) < 2 || !trim($argv[1])){

		echo "Missing CSV file argument.\n";
		exit;
	}


	$output = $_SESSION['pac_reports']->parsePacsFile($argv[1]);


	$cnt = $_SESSION['pac_reports']->pushPacsToDB($output);


	echo "Rows affected: ".$cnt."\n";


	//print_r($rejects_pile);






