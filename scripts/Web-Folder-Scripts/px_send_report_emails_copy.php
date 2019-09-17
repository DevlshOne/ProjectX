#!/usr/bin/php
<?php
        $basedir = "/var/www/dev/";

        include_once($basedir."db.inc.php");
        include_once($basedir."utils/microtime.php");
        include_once($basedir."utils/format_phone.php");
	include_once($basedir."utils/db_utils.php");
        include_once($basedir."utils/functions.php");
	include_once($basedir."utils/rendertime.php");


	include_once($basedir."dbapi/dbapi.inc.php");


        include_once($basedir."classes/sales_analysiscopy.inc.php");
        include_once($basedir."classes/agent_call_stats.inc.php");

	include_once 'Mail.php';
	include_once 'Mail/mime.php' ;


        $_SESSION['sales_analysis']->sendReportEmails();
        
        
