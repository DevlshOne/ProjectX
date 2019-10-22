#!/usr/bin/php
<?
	$basedir = "/var/www/dev/";

        include_once($basedir."db.inc.php");
	//include_once($basedir."util/db_utils.php");
        include_once($basedir."utils/microtime.php");
        include_once($basedir."classes/ringing_calls.inc.php");


//print_r($_SESSION);exit;

        $_SESSION['ringing_calls']->processOpenSipsRecords();
