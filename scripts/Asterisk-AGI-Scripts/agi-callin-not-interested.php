#!/usr/bin/php -q
<?php

	error_reporting(0);
	ini_set('display_errors', 0);

	/**
	* WHERE TO FORWARD THE HANGUP NOTIFICATIONS TO
	*/

	$file_debug = false;

//exit;


/*******************************************/


  set_time_limit(30);



	## DEBUGGING LOG
	if($file_debug){
		$fh = fopen("/tmp/agi-callin-not-interested.log", "a");

	//       fwrite($fh, print_r($_REQUEST,1));
	//	fwrite($fh, print_r($argv,1));
	//	fwrite($fh, print_r( $_ENV ,1 ));

	}


	$agivars = array();
	while (!feof(STDIN)) {
    		$agivar = trim(fgets(STDIN));
    		if ($agivar === '') {
        		break;
		}
		$agivar = explode(':', $agivar);
		$agivars[$agivar[0]] = trim($agivar[1]);
	}

//	$agivars = $agi->request;

//

//	fwrite(STDOUT, "ANSWER\n");

	if($file_debug){
		fwrite($fh, print_r( $agivars, 1));

	}



//
//	$ch = curl_init();
//	curl_setopt($ch, CURLOPT_URL,"http://".$px_server_ip.":2288/HangingUpCall?vici_extension=".$viciext."&vici_server_ip=".$ipaddr);
//
//	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//	curl_setopt($ch, CURLOPT_HEADER, 0);
//
//
//	$data = curl_exec($ch);
//	$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//	$response_error = curl_error($ch);
//
//	## CLOSE CURL SESSION
//	curl_close($ch);


if($file_debug){
	fclose($fh);
}

//fwrite(STDOUT, "HANGUP\n");

?>
