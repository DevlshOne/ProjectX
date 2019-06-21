#!/usr/bin/php
<?php
        $basedir = "/var/www/dev/";

        include_once($basedir."db.inc.php");
        include_once($basedir."util/microtime.php");
        include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");
        include_once($basedir."util/functions.php");
	include_once($basedir."util/rendertime.php");


	include_once($basedir."dbapi/dbapi.inc.php");


        include_once($basedir."classes/sales_analysiscopy.inc.php");
        include_once($basedir."classes/agent_call_stats.inc.php");

	include_once 'Mail.php';
	include_once 'Mail/mime.php' ;


        $_SESSION['sales_analysis']->sendReportEmails();
        
        
