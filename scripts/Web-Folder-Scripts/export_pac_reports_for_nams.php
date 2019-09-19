#!/usr/bin/php
<?php

	$base_dir = "/var/www/html/dev2/";


	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");

	include_once($base_dir."util/db_utils.php");

	include_once($base_dir."dbapi/dbapi.inc.php");
	include_once($base_dir."classes/pac_reports.inc.php");


	connectPXDB();

	$stime = 0;
	$etime = 0;

	$output = $_SESSION['pac_reports']->exportNams($stime, $etime);



	echo $output;
