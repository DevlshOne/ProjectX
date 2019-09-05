#!/usr/bin/php
<?php
/**
 * WTF HAPPENED? A script to help track down the call flow and see who hung up first/how long shit took/etc
 * Written by: Jonathan Will(PHP) and William G-funk Gisaku (Bash scripting)
 */
    $basedir = "/var/www/dev/";

    include_once($basedir."db.inc.php");
    include_once($basedir."util/microtime.php");
    include_once($basedir."util/db_utils.php");


	connectPXDB();


	function extractDialerLogs($dialer_ip, $phone){

		echo "Extracting dialer logs from $dialer_ip for PH# ".$phone."\n";

		//$williamvoodoo = //'phonny=\''.$phone.'\'; thissy=$(grep "${phonny}" /var/log/asterisk/messages|grep answered|grep -v \' was \'|awk -F\'/\' \'{print $2}\'|awk -F\' \' \'{print $1}\'); egrep "${thissy}|${phonny}" /var/log/asterisk/messages; unset phonny thissy';

		$williamvoodoo = 'ssh -i /root/.ssh/id_rsa '.$dialer_ip.' <<EOF grep "'.$phone.'" /var/log/asterisk/messages|grep answered|grep -v \' was \'|awk -F\'/\' \'{print $2}\'|awk -F\' \' \'{print $1}\'';
		$williamvoodoo .= "\nEOF\n";

		$channel = trim(`$williamvoodoo`);

		echo "CHANNEL NAME: ".$channel."\n";


		$cmd = 'ssh -i /root/.ssh/id_rsa '.$dialer_ip.'  \'egrep "'.$phone.'|'.$channel.'" /var/log/asterisk/messages\'';


		$logs = `$cmd`;

		return $logs;
	}


	function lookupDialerIP($phone){



		$phone = preg_replace("/[^0-9]/",'', trim($phone));


		echo "Looking up Dialer for $phone...\n";

		// FIND THE MOST RECENT LEAD FOR THE PHONE NUMBER
		$lead = querySQL("SELECT * FROM `lead_tracking` WHERE `phone_num`='$phone' ORDER BY `id` DESC LIMIT 1");

		if(!$lead){

			echo "Phone # ".$phone." : Lead not found.\n";

			return null;
		}else{

			echo "Phone # ".$phone." : Found lead #".$lead['id']." Date:".date("g:i:sa m/d/Y", $lead['time'])."\n";
		}


		echo "Connecting to cluster: ".getClusterName($lead['vici_cluster_id'])." #".$lead['vici_cluster_id']."\n";

		// CONNECT TO THE VICI CLUSTER
		$dbidx = getClusterIndex($lead['vici_cluster_id']);
		connectViciDB($dbidx);



		// GRAB THE DIALER IP
		$carrierlog = querySQL("SELECT * FROM asterisk.vicidial_carrier_log WHERE channel like '%".$phone."%'");

		if(!$carrierlog){

			echo "Phone # ".$phone." : Carrier log not found on ".getClusterName($lead['vici_cluster_id'])." #".$lead['vici_cluster_id']."\n";
			return null;
		}


		$dialer_ip = $carrierlog['server_ip'];


		echo "Dialer IP: ".$dialer_ip."\n";

		return $dialer_ip;
	}


	if(!isset($argv[1])){
		echo "Missing Args! try ".$argv[0]." <phone number>\n";
		exit;
	}



	$phone = preg_replace("/[^0-9]/",'', $argv[1]);


	if($phone){


		if(strlen($phone) < 10){

			echo "Phone number too short '$phone'";


		// DO THE DAMN THING
		}else{

			$dialer_ip = lookupDialerIP($phone);

			$logs = extractDialerLogs($dialer_ip, $phone);


			echo $logs."\n";

		}


	}else{

		echo "Invalid phone provided.";

	}


