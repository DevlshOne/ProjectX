#!/usr/bin/php
<?php
/**
 * Answering Machines - Extract the ones in AM, put into a PM list, and vice versa
 * Written By: Jonathan Will

 */

	$basedir = "/var/www/html/dev2/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/db_utils.php");
	include_once($basedir."classes/answering_machines.inc.php");


	echo date("H:i:s m/d/Y")." - Starting Answering machine extraction/rotation...\n";

//exit;

	// CONNECT PX DB
	connectPXDB();


	$_SESSION['answering_machines']->extractAnswerDispos('AM');





	echo date("H:i:s m/d/Y")." - DONE!\n";

