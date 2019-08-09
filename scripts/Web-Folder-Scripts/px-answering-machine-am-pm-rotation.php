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

	$days = 2;
	
	
	$stime = mktime(0,0,0);
	$etime = mktime(23,59,59);
	
	// MOVE IT BACK 1 DAY!
	$stime = $stime - ($days * 86400);
	$etime = $etime - ($days * 86400);
	

	// CONNECT PX DB
	connectPXDB();

	// ROTATE AM TO PM LIST
	$_SESSION['answering_machines']->extractAnswerDispos('AM', $stime, $etime);

	// ROTATE PM TO AM LIST
	$_SESSION['answering_machines']->extractAnswerDispos('PM', $stime, $etime);



	echo date("H:i:s m/d/Y")." - DONE!\n";

