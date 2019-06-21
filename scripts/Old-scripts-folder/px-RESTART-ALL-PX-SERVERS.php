#!/usr/bin/php
<?php
/****
 * Remotely restart all PX server processes and their associated asterisk services
 * Written By: Jonathan Will - 1-7-2017
 *
 * Uses SSH to trigger a "px_full_restart" on each px server in the "servers" table
 *
 * (Mostly used for triggered a mass auto-update)
 *
 *
 */
	$basedir = "/var/www/dev/"; // FOR PRODUCTION

	include_once($basedir."db.inc.php");
	include_once($basedir."utils/db_utils.php");
	include_once($basedir."classes/JXMLP.inc.php");

	connectPXDB();


	$ssh_cmd = "";

	$res = query("SELECT * FROM `servers` WHERE 1  ORDER BY `name` ASC"); //`running`='yes'

	echo date("g:i:s m/d/Y")." - Restarting ".mysql_num_rows($res)." servers!\n";

	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){

		$cmd = "ssh -t root@".$row['ip_address']." /ProjectX-Server/scripts/px_full_restart.sh";

		echo "Server ".$row['name']." - Running: ".$cmd."\n";

		$reply = exec(escapeshellcmd($cmd));

		echo "cmd reply: ".$reply."\n";

	}


	echo date("g:i:s m/d/Y")." - Done!\n";