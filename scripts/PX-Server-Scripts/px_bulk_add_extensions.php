#!/usr/bin/php
<?php
/**
 * PX Add BULK extensions
 * Written By: Jonathan Will
 *
 *
 *	USAGE: 		./px_add_bulk_extensions.php <START EXTENSION> <COUNT> <VICI SERVER IP> <FULLNAME> <OPTIONAL IAX PHONE PASSWORD>
 *	EXAMPLE:	./px_add_bulk_extensions.php 9100 10 10.100.0.107 asdfkjasdfkljsdfjlksdf
 */


	/***
	 * CLUSTER options:
	 *  "cold1"
	 *  "cold2"
	 *  "cold3"
	 *  "taps"
	 */

	include_once("config.php");

	$cluster = "cold1";
	
	$clusters = array(
			'cold1' => array('db_host'=>"10.101.1.2",'db_port'=>"3306",'db_user'=>"vicidb",'db_pass'=>"vuy4Re4EneFre9R",'db_name'=>"asterisk"),
	);
	
	


	$default_iax_password = $iax_password;	// THE PASSWORD VICI AND PX USE, TO CONNECT THE VICI SESSION TO THE PX CONFERENCE, VIA IAX

	$phone_login = $vici_phone_password;		// THE PASSWORD FOR THE PHONE, THAT THEY TYPE INTO VICIDIAL



	$protocol = "IAX2";			// NORMALLY "IAX2", "SIP" is an option, but can break shit.

	$phone_type = "";			// THERE ARE 3 OPTIONS THAT IM AWARE OF, BLANK, "sipcompressx", and "sipcompress"

	$add_script = "/ProjectX-Server/scripts/px_add_extension_new.php";

/*****************************/






	function missingArgs($program){
		die("Missing Parameters. Please specify the start extension, count, vici server ip, full name for extensions, and optional IAX phone password.\n".
			"Example: ".$program." 12345 10 10.100.0.107 FULLNAME\n".
			"Or:      ".$program." 12345 10 10.100.0.107 FULLNAME MYPHRESHPASSWORD\n\n");
	}




/****/
	if(count($argv) < 4){

		missingArgs($argv[0]);

	}

	$ext_start = intval($argv[1]);
	$count = intval($argv[2]);
	$server_ip = trim($argv[3]);


	$fullname = trim($argv[4]);





	// OPTIONAL IAX PASSWORD OVERRIDE
	if(count($argv) > 5){	$iax_password = trim($argv[5]);}
	else{			$iax_password = $default_iax_password;}


	$vici = $clusters[$cluster];


	echo "Connecting to vicidial db (".$vici['db_host'].":".$vici['db_port'].")...\n";


    $db	= $_SESSION['db'] = mysqli_connect($vici['db_host'].':'.$vici['db_port'], $vici['db_user'], $vici['db_pass'],$vici['db_name']) or die(mysqli_error($db)."Connection to MySQL Failed.");
//	mysql_select_db($vici['db_name'], $db) or die("Could not select database ".$vici['db_name']);

	echo "Connected. Processing...\n";



	for($x=0, $curext = $ext_start;$x < $count;$x++,$curext++){


		$insert_cmd = "";

		$cmd = $add_script.' '.$curext.' '.$server_ip.' "'.$iax_password.'"';

		echo "Running: $cmd\n";
		echo `$cmd`."\n";


		echo "Generating and inserting vicidial phone...\n";
			$insert_cmd_start = "INSERT INTO asterisk.phones(`extension`, `dialplan_number`, `voicemail_id`, `phone_ip`, `computer_ip`, `server_ip`,".
						" `login`, `pass`, `status`, `active`, `phone_type`, `fullname`, `company`, `picture`, `messages`, `old_messages`, `protocol`,".
						" `local_gmt`, `ASTmgrUSERNAME`, `ASTmgrSECRET`, `login_user`, `login_pass`, `login_campaign`, `park_on_extension`,".
						" `conf_on_extension`, `VICIDIAL_park_on_extension`, `VICIDIAL_park_on_filename`, `monitor_prefix`, `recording_exten`,".
						" `voicemail_exten`, `voicemail_dump_exten`, `ext_context`, `dtmf_send_extension`, `call_out_number_group`, `client_browser`,".
						" `install_directory`, `local_web_callerID_URL`, `VICIDIAL_web_URL`, `AGI_call_logging_enabled`, `user_switching_enabled`, ".
						" `conferencing_enabled`, `admin_hangup_enabled`, `admin_hijack_enabled`, `admin_monitor_enabled`, `call_parking_enabled`, ".
						" `updater_check_enabled`, `AFLogging_enabled`, `QUEUE_ACTION_enabled`, `CallerID_popup_enabled`, `voicemail_button_enabled`,".
						" `enable_fast_refresh`, `fast_refresh_rate`, `enable_persistant_mysql`, `auto_dial_next_number`, `VDstop_rec_after_each_call`,".
						" `DBX_server`, `DBX_database`, `DBX_user`, `DBX_pass`, `DBX_port`, `DBY_server`, `DBY_database`,`DBY_user`, `DBY_pass`, `DBY_port`,".
						" `outbound_cid`, `enable_sipsak_messages`, `email`, `template_id`, `conf_override`, `phone_context`, `phone_ring_timeout`,".
						" `conf_secret`, `delete_vm_after_email`, `is_webphone`, `use_external_server_ip`, `codecs_list`, `codecs_with_template`,".
						" `webphone_dialpad`, `on_hook_agent`, `webphone_auto_answer`, `voicemail_timezone`, `voicemail_options`, `user_group`,".
						" `voicemail_greeting`, `voicemail_dump_exten_no_inst`, `voicemail_instructions`) VALUES ";

		$insert_cmd = $insert_cmd_start. " (".

		$insert_cmd.= "'$curext','$curext','$curext','','',"; // phone_ip and computer_ip blank

		// SERVER IP, USER, PHONE PASSWORD
		$insert_cmd.= "'$server_ip','$curext','".addslashes($phone_login)."',";

		// ACTIVE, ACTIVE, PHONE TYPE, FULLNAME
		$insert_cmd.= "'ACTIVE','Y', '".addslashes($phone_type)."', '".addslashes(($fullname)?$fullname:$curext)."', ";


		// COMPANY, PICTURE, MESSAGES, OLD MESSAGES, PROTOCOL
		$insert_cmd.= "'','','0','0','$protocol',";

		// LOCAL GMT, THEN A BUNCH OF DEFAULT BULLSHIT
		$insert_cmd.= "'$local_gmt','cron','1234',NULL,NULL,NULL,'8301','8302','8301','park','8612', '8309', '8501','85026666666666', ";

		// MORE DEFAULT BULLSHIT
		$insert_cmd.= "'default','local/8500998@default','Zap/g2/','/usr/bin/mozilla','/usr/local/perl_TK',";

		$insert_cmd.= "'http://astguiclient.sf.net/test_callerid_output.php','http://astguiclient.sf.net/test_VICIDIAL_output.php',";

		// PERMISSIONS (AGI_call_logging_enabled, user_switching_enabled, conferencing_enabled, admin_hangup_enabled, admin_hijack_enabled, admin_monitor_enabled, call_parking_enabled, updater_check_enabled, AFLogging_enabled, QUEUE_ACTION_enabled, CallerID_popup_enabled, voicemail_button_enabled, enable_fast_refresh)
		$insert_cmd.= "'1','1','1','0','0','1','1','1','1','1','1','1','0',";

		// REFRESH FREQ, FEW MORE DEFAULT FIELDS FOR CUSTOM DB CRAP OR SOMETHING
		$insert_cmd.= "'1000','0','1','1',NULL,'asterisk','cron','1234','3306',NULL,'asterisk','cron','1234','3306',";

		// OUTBOUND CALLER ID, bla bla bla ring timeout
		$insert_cmd.= "'$curext','0',NULL,'',NULL,'default',60,";

		// IAX PASSWORD!
		$insert_cmd.= "'".addslashes($iax_password)."', 'N','N','N','','0','Y','N','Y',";

		// VOICEMAIL TIMEZONE, VM OPTIONS, USER GROUP, greeting, dump ext, vm instructions
		$insert_cmd.="'pacific','','---ALL---','','85026666666667','Y'";

		$insert_cmd.= "); ";


		//echo $insert_cmd."\n\n";


		mysqli_query($_SESSION['db'],$insert_cmd) or die("ERROR EXECUTING SQL STATEMENT: ".$insert_cmd."\n\n");

	}


	$final_sql = "UPDATE asterisk.servers SET rebuild_conf_files='Y'";

	mysqli_query($_SESSION['db'],$final_sql) or die("ERROR EXECUTING SQL STATEMENT: ".$final_sql."\n\n");




	echo "Added ".$x." new phone records to ".$cluster." on server ".$server_ip."\n";








