#!/usr/bin/php
<?php

	$alert_email = "support@advancedtci.com";//"jon@revenantlabs.net";
	$from_email  = "support@advancedtci.com";
	$max_seconds_behind_master = 600;


	// PX DATABASE INFO
	$px_db_hosts = array(



		array(
			'host' => "10.101.15.51",
			'port' => "3306",
			'user' => "reppx",
			'pass' => "bnUpZ7GqmhrBWYYP",
		),

		array(
			'host' => "10.101.15.50",
			'port' => "3306",
			'user' => "reppx",
			'pass' => "bnUpZ7GqmhrBWYYP",
		),


	);



	$error_msg = null;


	foreach($px_db_hosts as $db){

		$dbc = mysqli_connect($db['host'].':'.$db['port'], $db['user'], $db['pass']);

		if($dbc === FALSE){

			$error_msg .= "ERROR CONNECTING TO HOST ".$db['host'].':'.$db['port'].' as '.$db['user']."\n";

			continue;
		}

		$res = mysqli_query($dbc,"SHOW SLAVE STATUS");

		if(($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) !== FALSE){


			if($row['Slave_IO_Running'] != 'Yes'){

				$error_msg .= "Host ".$db['host']." SLAVE IO THREAD NOT RUNNING!\n";

			}

			if($row['Slave_SQL_Running'] != 'Yes'){

				$error_msg .= "Host ".$db['host']." SLAVE SQL THREAD NOT RUNNING!\n";

			}


			if(intval($row['Seconds_Behind_Master']) >= $max_seconds_behind_master){

				$error_msg .= "Host ".$db['host']." IS ".intval($row['Seconds_Behind_Master'])." SECONDS BEHIND MASTER!\n";
			}

			//print_r($row);

			/**
			 *    Array
(
    [Slave_IO_State] => Waiting for master to send event
    [Master_Host] => 10.100.0.73
    [Master_User] => reppx
    [Master_Port] => 3306
    [Connect_Retry] => 60
    [Master_Log_File] => mysql-bin.000008
    [Read_Master_Log_Pos] => 27205
    [Relay_Log_File] => mysqld-relay-bin.000012
    [Relay_Log_Pos] => 27099
    [Relay_Master_Log_File] => mysql-bin.000008
***    [Slave_IO_Running] => Yes
***    [Slave_SQL_Running] => Yes
    [Replicate_Do_DB] => projectx
    [Replicate_Ignore_DB] =>
    [Replicate_Do_Table] =>
    [Replicate_Ignore_Table] =>
    [Replicate_Wild_Do_Table] =>
    [Replicate_Wild_Ignore_Table] =>
    [Last_Errno] => 0
    [Last_Error] =>
    [Skip_Counter] => 0
    [Exec_Master_Log_Pos] => 27205
    [Relay_Log_Space] => 27359
    [Until_Condition] => None
    [Until_Log_File] =>
    [Until_Log_Pos] => 0
    [Master_SSL_Allowed] => No
    [Master_SSL_CA_File] =>
    [Master_SSL_CA_Path] =>
    [Master_SSL_Cert] =>
    [Master_SSL_Cipher] =>
    [Master_SSL_Key] =>
***    [Seconds_Behind_Master] => 0
    [Master_SSL_Verify_Server_Cert] => No
    [Last_IO_Errno] => 0
    [Last_IO_Error] =>
    [Last_SQL_Errno] => 0
    [Last_SQL_Error] =>
    [Replicate_Ignore_Server_Ids] =>
    [Master_Server_Id] => 3
)


			 */

		}



	}



	if($error_msg != null){

		echo $error_msg;

		// SEND EMAIL

		$boundary = md5(uniqid(time()));
		$extra_sendmail_parms = " -f$from_email ";

		$headers = "From: $from_email \n";
		$headers .= "Return-Path: $from_email \n";
		$headers .= "Date: ".date("r")."\n";
		$headers .= 'Content-Type: text/plain; charset=ISO-8859-1' ."\n";
		$headers .= 'Content-Transfer-Encoding: 8bit'. "\n\n";



		$subject = "PX REPLICATION MONITOR";

		$headers .= "PX REPLICATION MONITOR\n\n".$error_msg."\n\n";

		## SEND MAIL TO MANAGERS
		if(mail($alert_email,$subject, '',$headers,$extra_sendmail_parms)){

			echo date("r")." EMAIL Successfully sent\n";

		}else{

			echo date("r")." ERROR SENDING EMAIL\n";
		}
	}





