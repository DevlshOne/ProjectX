#!/usr/bin/php
<?php

	$basedir = "/var/www/html/staging-git/";

	include_once($basedir."db.inc.php");
	include_once($basedir."dbapi/dbapi.inc.php");



	include_once($basedir."classes/list_performance_report.inc.php");
	
	
	
	$_SESSION['list_performance']->logHistoryReport();
	
	
	
	