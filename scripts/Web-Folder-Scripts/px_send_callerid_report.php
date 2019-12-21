#!/usr/bin/php
<?php
//	$basedir = "/var/www/html/reports/";
	$basedir = "/var/www/html/staging-git/";

	include_once($basedir."db.inc.php");
	include_once($basedir."utils/microtime.php");
	include_once($basedir."utils/format_phone.php");
	include_once($basedir."utils/db_utils.php");
	include_once($basedir."utils/functions.php");
	include_once($basedir."utils/rendertime.php");
	
	include_once($basedir."dbapi/dbapi.inc.php");
	
	
	include_once($basedir."classes/callerid_stats_report.inc.php");
// 	include_once($basedir."classes/sales_analysis.inc.php");
// 	include_once($basedir."classes/agent_call_stats.inc.php");
// 	include_once($basedir."classes/summary_report.inc.php");

	include_once 'Mail.php';
	include_once 'Mail/mime.php' ;


	
	$email_to = "support@advancedtci.com";
		
	global $process_name;
	
	$process_name = "px_send_callerid_report";
	
	$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv));
	
	
	// SEND THE "BAD ONLY" PHONE NUMBERS TO THE EMAIL
	$sent_report_total = $_SESSION['callerid_stats_report']->sendReportEmail($email_to, true);
	
	
	///$sent_report_total = $_SESSION['sales_analysis']->sendReportEmails();
    
    
    
    
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $sent_report_total." Total Emails Sent");
        
