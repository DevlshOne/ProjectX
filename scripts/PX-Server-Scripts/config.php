<?php
	global $meetme_prefix;
	global $sip_password;
	global $iax_password;
	global $iax_host;
	global $vici_phone_password;
	global $local_gmt;
	global $server_id;
	global $config_dir;
	global $config_gen_cmd;
	
	global $basedir;
	
	// BASE DIRECTORY, WHERE The "site_config.php" and code base is.
	$basedir = "/var/www/html/";
	
	$server_id_file = "/etc/px-server-id";
	
	$sip_password = "hr439t8hJ8t9uTAa";
	$iax_password = "cyQoEku81oDE4GU";
	$px_sip_pass = "t1g3rstyl3";	// THE PASSWORD PX LINPHONE USES TO REGISTER (as px-system user)
	$meetme_prefix = "1024";
	$vici_phone_password = "drlv";		// THE PASSWORD FOR THE PHONE, THAT THEY TYPE INTO VICIDIAL
	
	$local_gmt = "-8.00";		// MIGHT NEED TO ADJUST FOR THEIR TIMEZONE
	
	$config_dir = "/etc/asterisk/"; // change this if you want to test it first in another dir
	$config_gen_cmd = "/ProjectX-Server/scripts/px_config_generator.php";
	
	
	// LOAD SERVER ID FROM TEH FILE
	$server_id = intval(trim(file_get_contents($server_id_file))); // CHANGE AS NECESSARY
	
	
	
	