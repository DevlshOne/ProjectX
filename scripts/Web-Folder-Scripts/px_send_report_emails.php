#!/usr/bin/php
<?php
        $basedir = "/var/www/html/reports/";

        include_once($basedir."db.inc.php");
        include_once($basedir."utils/microtime.php");
        include_once($basedir."utils/format_phone.php");
	include_once($basedir."utils/db_utils.php");
        include_once($basedir."utils/functions.php");
	include_once($basedir."utils/rendertime.php");


	include_once($basedir."dbapi/dbapi.inc.php");


        include_once($basedir."classes/sales_analysis.inc.php");
        include_once($basedir."classes/agent_call_stats.inc.php");
	include_once($basedir."classes/summary_report.inc.php");

	include_once 'Mail.php';
	include_once 'Mail/mime.php' ;

	global $process_name;
	
	$process_name = "px_send_report_emails";
	
	$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv));
	
	
	
	
	$sent_report_total = $_SESSION['sales_analysis']->sendReportEmails();
    
    
    
    
	$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $sent_report_total." Total Emails Sent");
        
