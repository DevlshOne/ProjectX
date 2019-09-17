#!/usr/bin/php
<?php
		$basedir = "/var/www/html/dev/";

		include_once($basedir."db.inc.php");
		include_once($basedir."utils/db_utils.php");

		include_once($basedir."classes/JXMLP.inc.php");

		connectPXDB();


		$retry_to_verify = true;

	function getURL($url){

		$output = null;

		 // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3); //timeout in seconds

        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

		return $output;
	}






		$servers = array();

		// GET LIST OF SERVERS
		$res = query("SELECT * FROM projectx.servers WHERE running='yes' ORDER BY `name` ASC", 1);
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$servers[] = $row;

		}

		$total_users = 0;
		$total_output = "";
		$usercnt = 0;

		foreach($servers as $sidx=>$server_row){

			$out = '';
			$clustercnt = 0;
			// CURL HIT PX

			$reply = getURL("http://".$server_row['ip_address'].':2288/Status?xml_mode=1');

			$server_info = $_SESSION['JXMLP']->parseOne($reply, "PXUsers", false);

			echo date("H:i:s m/d/Y")." - Processing: ".$server_row['ip_address'].': v'.$server_info['server_version']." with ".$server_info['connections']."\n";


			$px_users = $_SESSION['JXMLP']->grabTagArray($reply, "PXUser", true);

			if(is_array($px_users) && count($px_users) > 0){
				foreach($px_users as $px_user){

					//echo "PXUSER: ".$px_user;

					$user = $_SESSION['JXMLP']->parseOne($px_user, "PXUser", 1);

					//<PXUser username="JPW" extension="9000" ipaddress="192.168.233.4" client_version="2.166"
					//campaign="277" login_duration="0:15" volume_settings="5/5/5" has_callers="Yes" has_vici="No"
					// vici_status="PAUSED" has_linphone="Active" live_call="No" call_duration="-" call_count="0"/>

					if($user['has_vici'] == 'Yes' && $user['live_call'] == 'Yes' &&
						(
							$user['vici_status'] == 'PAUSED' ||
							$user['vici_status'] == 'READY'

						)){

						//echo "DETECTED CALL STAYING GREEN ISSUE FOR USER: ".$px_user."\n";

						echo date("H:i:s m/d/Y")." -- User ".$user['username']." @ ".$user['extension'].' ('.$user['ipaddress'].') running v'.$user['client_version']." HAS GREEN CALL ISSUE\n";

						$usercnt++;
						$clustercnt++;
					}

				}
			}

			if($clustercnt > 0){
				echo date("H:i:s m/d/Y")." - ".$server_row['ip_address']." had ".$clustercnt." agents with the issue.\n";

				if($retry_to_verify){

						sleep(1);

						$reply = getURL("http://".$server_row['ip_address'].':2288/Status?xml_mode=1');

						$server_info = $_SESSION['JXMLP']->parseOne($reply, "PXUsers", false);

						echo date("H:i:s m/d/Y")." - Reprocessing to verify: ".$server_row['ip_address'].': v'.$server_info['server_version']." with ".$server_info['connections']."\n";


						$px_users = $_SESSION['JXMLP']->grabTagArray($reply, "PXUser", true);

						if(is_array($px_users) && count($px_users) > 0){

							$retry_cnt =0;
							foreach($px_users as $px_user){

								//echo "PXUSER: ".$px_user;

								$user = $_SESSION['JXMLP']->parseOne($px_user, "PXUser", 1);

								//<PXUser username="JPW" extension="9000" ipaddress="192.168.233.4" client_version="2.166"
								//campaign="277" login_duration="0:15" volume_settings="5/5/5" has_callers="Yes" has_vici="No"
								// vici_status="PAUSED" has_linphone="Active" live_call="No" call_duration="-" call_count="0"/>

								if($user['has_vici'] == 'Yes' && $user['live_call'] == 'Yes' &&
									(
										$user['vici_status'] == 'PAUSED' ||
										$user['vici_status'] == 'READY'

									)){

									//echo "DETECTED CALL STAYING GREEN ISSUE FOR USER: ".$px_user."\n";

									echo date("H:i:s m/d/Y")." -- User ".$user['username']." @ ".$user['extension'].' ('.$user['ipaddress'].') running v'.$user['client_version']." HAS GREEN CALL ISSUE\n";
									$retry_cnt++;
								}

							}

							if($retry_cnt > 0){

								echo date("H:i:s m/d/Y")." - ".$server_row['ip_address']." ISSUE PERSISTS, PLEASE INVESTIGATE ME! ".$retry_cnt." agents second attempt\n";

							}else{
								echo date("H:i:s m/d/Y")." - ".$server_row['ip_address']." CLEAN on retry.\n";
							}
						}

				}

			}


		}

		if($usercnt > 0){

			echo date("H:i:s m/d/Y")." Total issues detected: ".$usercnt."\n";

		}else{
			echo date("H:i:s m/d/Y")." YAY! Call Green issue not found!\n";
		}


