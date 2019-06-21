<?



class API_VerifierTestingTool{

	var $xml_parent_tagname = "Tools";
	var $xml_record_tagname = "Tool";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	var $testing_phone = "7021112222";



	function handleAPI(){


		if(!checkAccess('users')){


			$_SESSION['api']->errorOut('Access denied to Users');

			return;
		}



		switch($_REQUEST['action']){
		default:
		case 'test':

			$px_ip = trim($_REQUEST['px_server_ip']);
			$username = trim($_REQUEST['username']);
			$extension = trim($_REQUEST['extension']);
			$campaign = trim($_REQUEST['campaign']);


			list($testingid) = queryROW("SELECT id FROM lead_tracking WHERE phone_num='".mysqli_real_escape_string($_SESSION['db'],$this->testing_phone)."'");


			$dat = array();
			$dat['phone_num'] = $this->testing_phone;
			$dat['extension'] = $extension;
			$dat['campaign'] = $campaign;
			$dat['campaign_code'] = $campaign;


			$dat['first_name'] = "VerifierTestTool";
			$dat['last_name'] = "Testing";


			// ADD NEW FAKE RECORD
			if(!$testingid){

				$testingid = aadd($dat, 'lead_tracking');

			// BE A HIPPY AND RECYCLE IT
			}else{

				aedit($testingid, $dat, 'lead_tracking');

			}


			## CURL HIT THE FAKE URL
			$url = "http://".$px_ip.":2288/VerifierPass?lead_id=".urlencode($testingid)."&campaign=".urlencode($campaign)."&phone_num=".urlencode($this->testing_phone)."&user=".urlencode($username)."&channel=".urlencode($extension)."&comments=TESTING%20YAY&recording_id=110101010&verifier_call_id=VTHEREISNTONE&entry_list_id=0";


			$ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		    $data = curl_exec($ch);
		    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		    $response_error = curl_error($ch);

		    ## CLOSE CURL SESSION
		    curl_close($ch);

			if($response_code != 200){

				echo "0:Error - ".$response_code." ".$response_error."\n";

			}else{

				echo "1:Success\n";

			}

			break;

		}

	}






}


