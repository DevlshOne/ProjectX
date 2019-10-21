#!/usr/bin/php
<?php
/**
 * Answering Machines - Extract the ones in AM, put into a PM list, and vice versa
 * Written By: Jonathan Will

 */

	$basedir = "/var/www/html/reports/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/db_utils.php");
	include_once($basedir."classes/answering_machines.inc.php");

	include_once($basedir."dbapi/dbapi.inc.php");
	
	global $process_name;
	
	$process_name = "px-answering-machine-am-pm-rotation";
	
	$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv));
	
	

	echo date("H:i:s m/d/Y")." - Starting Answering machine extraction/rotation...\n";

//exit;

	$days = 1;
	
	
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
	
	
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed");
	
	
