#!/usr/bin/php
<?php
/**
 * LIST HOPPER CHECK SCRIPT 
 * Written By: Jonathan Will
 *
 *	Checks to see if its time to turn lists on or off, based on rule set
 *
 *
 */

$basedir = "/var/www/html/reports/";

include_once($basedir."db.inc.php");
include_once($basedir."util/microtime.php");
include_once($basedir."utils/format_phone.php");
include_once($basedir."utils/db_utils.php");


include_once($basedir."dbapi/dbapi.inc.php");

include_once($basedir."classes/list_hopper_system.inc.php");



global $process_name;

$process_name = "px_list_hopper_check";


$procid = $_SESSION['dbapi']->process_tracker->logStartProcess($process_name, 'started', implode(" ", $argv));


$process_logs = $_SESSION['list_hopper']->checkHopper();

echo $process_logs;

$_SESSION['dbapi']->process_tracker->logFinishProcess($procid, "completed", $process_logs);




