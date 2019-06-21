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


// INSTEAD OF HARDCODING THE IP ADDRESS TO EVERYTHING
// LIKE SO:   $px_server_ip = "10.101.15.65";
// DETECT THE IP ADDRESS, BASED ON THE LOCAL MACHINES IP ADDRESS/SUBNET/A.B.C.x

	// GET MACHINE IP (10.101.(cluster).11 12, 13, 14
	$ipaddr = getHostByName(getHostName());

	// BREAK INTO ARRAY
    $iparr = preg_split("/[^0-9]/", $ipaddr);

	// IF the last part of the IP is 13/14, send to PX2, else PX1
	// ASSEMBLE IP ADDRESS FROM TEH PARTS OF THE DIALER IP
    if($iparr[3] == 13 || $iparr[3] == 14){
            $px_server_ip = $iparr[0].'.'.$iparr[1].'.'.$iparr[2].'.6';
    }else{
            $px_server_ip = $iparr[0].'.'.$iparr[1].'.'.$iparr[2].'.5';
    }


	## DEBUGGING LOG
	if($file_debug){
		$fh = fopen("/tmp/px-server-relay.log", "a");

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


/**
* OPEN SIPS MODE
Array
(
    [agi_request] => agi-px-hangup.agi
    [agi_channel] => SIP/opensip1-00006763
    [agi_language] => en
    [agi_type] => SIP
    [agi_uniqueid] => 1558125739.215076
    [agi_callerid] => unknown
    [agi_calleridname] => V5171342190478471740
    [agi_callingpres] => 0
    [agi_callingani2] => 0
    [agi_callington] => 0
    [agi_callingtns] => 0
    [agi_dnid] => unknown
    [agi_rdnis] => unknown
    [agi_context] => default
    [agi_extension] => h
    [agi_priority] => 2
    [agi_enhanced] => 0.0
    [agi_accountcode] =>
)

*/

	if( $agivars['agi_type'] == 'SIP' || $agivars['agi_type'] == 'IAX2'){ //preg_match("/SIP\/opensip/", $agivars['agi_channel']) ){

		$callid = $agivars['agi_calleridname'];

		if($file_debug){
			fwrite($fh, "IP of server: ".$ipaddr."\n");
			fwrite($fh, "Call ID hanging up: ".$callid."\n");
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://".$px_server_ip.":2288/HangingUpCall?call_id=".urlencode($callid)."&channel=".urlencode($agivars['agi_channel'])."&vici_server_ip=".urlencode($ipaddr));

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);


		$data = curl_exec($ch);
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$response_error = curl_error($ch);

		## CLOSE CURL SESSION
		curl_close($ch);


/***
* FROM ANOTHER VICI DIALER MODE

Array
(
    [agi_request] => agi-px-hangup.agi
    [agi_channel] => IAX2/vicic3d11-15164
    [agi_language] => en
    [agi_type] => IAX2
    [agi_uniqueid] => 1558373655.93476
    [agi_callerid] => unknown
    [agi_calleridname] => V5201033590473965550
    [agi_callingpres] => 0
    [agi_callingani2] => 0
    [agi_callington] => 0
    [agi_callingtns] => 0
    [agi_dnid] => unknown
    [agi_rdnis] => unknown
    [agi_context] => default
    [agi_extension] => h
    [agi_priority] => 2
    [agi_enhanced] => 0.0
    [agi_accountcode] => IAXvicic3d11
)

*/

	}else if(preg_match("/IAX2\/vici/", $agivars['agi_channel']) ){


		$callid = $agivars['agi_calleridname'];

		if($file_debug){
			fwrite($fh, "IP of server: ".$ipaddr."\n");
			fwrite($fh, "Call ID hanging up: ".$callid."\n");
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://".$px_server_ip.":2288/HangingUpCall?call_id=".urlencode($callid)."&channel=".urlencode($agivars['agi_channel'])."&vici_server_ip=".urlencode($ipaddr));

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);


		$data = curl_exec($ch);
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$response_error = curl_error($ch);

		## CLOSE CURL SESSION
		curl_close($ch);


/*	}else if($agivars['agi_caller_id'] != "unknown" && preg_match("/Local\/9/", $agivars['agi_channel']) ){

		// EXTRACT EXTENSION
		$arr = preg_split("/[^a-zA-Z0-9]/", $agivars['agi_channel']);

		$phone = $arr[1];
		$phone = substr($phone, 4);


		//echo "phone test: ".$phone."\n";

		if($file_debug){
			fwrite($fh, print_r( $agivars, 1));
			//fwrite($fh, print_r( $arr, 1));
			fwrite($fh, "IP of server: ".$ipaddr."\n");
			fwrite($fh, "Phone number hanging up: ".$phone."\n");
		}


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://".$px_server_ip.":2288/HangingUpCall?phone_num=".urlencode($phone)."&channel=".urlencode($agivars['agi_channel'])."&vici_server_ip=".urlencode($ipaddr));

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);


		$data = curl_exec($ch);
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$response_error = curl_error($ch);

		## CLOSE CURL SESSION
		curl_close($ch);

//	}
*/

	}else if( preg_match("/Local\/86/", $agivars['agi_channel']) ){   // || preg_match("/Local\/586/", $agivars['agi_channel']))


		// EXTRACT EXTENSION
		$arr = preg_split("/[^a-zA-Z0-9]/", $agivars['agi_channel']);

		$viciext = $arr[1];

		if($viciext[0] == '5'){

			$viciext = substr($viciext, 1);

		}

		//$ipaddr = getHostByName(getHostName());

		if($file_debug){
			fwrite($fh, print_r( $agivars, 1));

			fwrite($fh, print_r( $arr, 1));

			fwrite($fh, "IP of server: ".$ipaddr."\n");
		}


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"http://".$px_server_ip.":2288/HangingUpCall?vici_extension=".$viciext."&vici_server_ip=".$ipaddr);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);


		$data = curl_exec($ch);
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$response_error = curl_error($ch);

		## CLOSE CURL SESSION
		curl_close($ch);

	}


if($file_debug){
	fclose($fh);
}

//fwrite(STDOUT, "HANGUP\n");

?>
