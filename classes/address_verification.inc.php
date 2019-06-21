<?	/***************************************************************
	 *	Names - Handles list/search/import names
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['address_verification'] = new AddressVerification;



class AddressVerification{

	var $auth_id = "5c575c0e-9955-19d1-73dd-71990d486246";

	var $auth_token = "LnxPWDlnVlXO11Epj6jR";

	var $max_batch_size = 100;

	// ENABLE APPENDING THE EXTRA 4 ZIPCODE DIGITS TO THE ADDRESS
	var $zip_plus4 = false;



	function AddressVerification(){


		## REQURES DB CONNECTION!
		$this->handlePOST();



	}

	function getCleanAddressFromRow($row){

		$jobj = $this->apiGetData($row['address1'], $row['address2'], $row['city'], $row['state'], $row['zip_code']);

		if($jobj != null && isset($jobj[0])){

//			if($jobj[0]->components->street_name == "PO Box"){
//
//				$row['address1'] = $jobj[0]->components->street_name.' '.$jobj[0]->components->primary_number.((isset($jobj[0]->components->street_suffix))?' '.$jobj[0]->components->street_suffix:'');//$jobj[0]->components->street_suffix;
//
//			}else{
//				$row['address1'] = 	$jobj[0]->components->primary_number.' '.
//									((isset($jobj[0]->components->street_predirection))?$jobj[0]->components->street_predirection.' ':'').
//
//									$jobj[0]->components->street_name.' '.
//
//									((isset($jobj[0]->components->street_postdirection))?$jobj[0]->components->street_postdirection.' ':'').
//
//									((isset($jobj[0]->components->street_suffix))?$jobj[0]->components->street_suffix:'');
//			}
//			$row['address2'] =  ((isset($jobj[0]->components->secondary_number) || isset($jobj[0]->components->secondary_designator))?$jobj[0]->components->secondary_designator.' '.$jobj[0]->components->secondary_number:'');

			$row['address1'] = $jobj[0]->delivery_line_1;
			$row['address2'] = (isset($jobj[0]->delivery_line_2))?$jobj[0]->delivery_line_2:'';

			$row['city'] =  $jobj[0]->components->city_name;
			$row['state'] =  $jobj[0]->components->state_abbreviation;
			$row['zip_code'] =  $jobj[0]->components->zipcode.(($this->zip_plus4 == true && $jobj[0]->components->plus4_code)?$jobj[0]->components->plus4_code:'');
		}
		//	'zipplus4'] =  $jobj[0]->components->plus4_code

		return $row;
	}



	function getCleanAddressesFromRows($rowarr){

		echo "getCleanAddressesFromRows() Processing ".count($rowarr)." rows...\n";

//print_r($rowarr);

		$tmparr = array();

		$base_index = 0;


		if(count($rowarr) > $this->max_batch_size){


			echo "getCleanAddressesFromRows() Batch Processing ".$this->max_batch_size." rows at a time...\n";

			for($x=0, $y=0;$x < count($rowarr);$x++){

				$tmparr[$y++] = $rowarr[$x];

				if($y >= $this->max_batch_size){

					echo "getCleanAddressesFromRows() Batch Processing ".$base_index." base index\n";

					// PINCH OFF THE TURD
					$jobj = $_SESSION['address_verification']->apiGetBulkData($tmparr);

					if($jobj != null && count($jobj) > 0){

						foreach($jobj as $obj){

							$idx = ($base_index + $obj->input_index);

							$rowarr[$idx]['address1'] = $obj->delivery_line_1;
							$rowarr[$idx]['address2'] = (isset($obj->delivery_line_2))?$obj->delivery_line_2:'';

							$rowarr[$idx]['city'] =  $obj->components->city_name;
							$rowarr[$idx]['state'] =  $obj->components->state_abbreviation;
							$rowarr[$idx]['zip_code'] =  $obj->components->zipcode.(($this->zip_plus4 == true && $obj->components->plus4_code)?$obj->components->plus4_code:'');

						}
					}


					// RESET/CLEANUP
					$tmparr = array();
					$y=0;
					$base_index = $x+1;
				}

			}

			// FINAL PASS TO CLEANUP STRAGGLERS
			if(count($tmparr) > 0){

				echo "getCleanAddressesFromRows() Batch Processing ".$base_index." base index final pass of ".count($tmparr)."\n";

				// PINCH OFF THE TURD
				$jobj = $_SESSION['address_verification']->apiGetBulkData($tmparr);

				if($jobj != null && count($jobj) > 0){

					foreach($jobj as $obj){

						$idx = ($base_index + $obj->input_index);

						$rowarr[$idx]['address1'] = $obj->delivery_line_1;
						$rowarr[$idx]['address2'] = (isset($obj->delivery_line_2))?$obj->delivery_line_2:'';

						$rowarr[$idx]['city'] =  $obj->components->city_name;
						$rowarr[$idx]['state'] =  $obj->components->state_abbreviation;
						$rowarr[$idx]['zip_code'] =  $obj->components->zipcode.(($this->zip_plus4 == true && $obj->components->plus4_code)?$obj->components->plus4_code:'');

					}
				}


				// RESET/CLEANUP
				$tmparr = array();
				$y=0;
			}



		}else{
			$jobj = $_SESSION['address_verification']->apiGetBulkData($rowarr);

			if($jobj != null && count($jobj) > 0){

				foreach($jobj as $obj){

					$idx = $obj->input_index;

					$rowarr[$idx]['address1'] = $obj->delivery_line_1;
					$rowarr[$idx]['address2'] = (isset($obj->delivery_line_2))?$obj->delivery_line_2:'';

					$rowarr[$idx]['city'] =  $obj->components->city_name;
					$rowarr[$idx]['state'] =  $obj->components->state_abbreviation;
					$rowarr[$idx]['zip_code'] =  $obj->components->zipcode.(($this->zip_plus4 == true && $obj->components->plus4_code)?$obj->components->plus4_code:'');

				}
			}

		}

//echo "FINISHED CLEANING:\n";
//print_r($rowarr);

		return $rowarr;
	}

	function apiGetBulkData($rowarr){

		$url = "https://us-street.api.smartystreets.com/street-address?".
				"auth-id=".$this->auth_id."&".
				"auth-token=".$this->auth_token."&".
				"candidates=1&";

		$addr_array = array();

		foreach($rowarr as $idx=>$row){

//			($address1)?"street=".urlencode($address1)."&":'').
//				(($address2)?"street2=".urlencode($address2)."&":'').
//				(($city)?"city=".urlencode($city)."&":'').
//				(($state)?"state=".urlencode($state)."&":'').
//				(($zip)?"zipcode=".urlencode($zip)."&":'').

			$addr_array[$idx] = array();
			$addr_array[$idx]['street'] = $row['address1'];
			$addr_array[$idx]['street2'] = $row['address2'];
			$addr_array[$idx]['city'] = $row['city'];
			$addr_array[$idx]['state'] = $row['state'];
			$addr_array[$idx]['zipcode'] = $row['zip_code'];



		}


		$payload = json_encode($addr_array);
//print_r($payload);



		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//		curl_setopt($ch, CURLOPT_HEADER, 0);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/json',
		    'Content-Length: ' . strlen($payload))
		);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	    $data = curl_exec($ch);
	    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $response_error = curl_error($ch);

	    ## CLOSE CURL SESSION
	    curl_close($ch);
//		echo "\n";
//		echo "Response Code: ".$response_code.' '.$response_error."\n\n";
//		echo "Response: ".$data."\n\n";

		$jobj = json_decode($data);



//print_r($jobj);

		return $jobj;

	}

	function apiGetData($address1, $address2, $city, $state, $zip){
		$url = "https://us-street.api.smartystreets.com/street-address?".
				"auth-id=".$this->auth_id."&".
				"auth-token=".$this->auth_token."&".
				"candidates=1&".
				(($address1)?"street=".urlencode($address1)."&":'').
				(($address2)?"street2=".urlencode($address2)."&":'').
				(($city)?"city=".urlencode($city)."&":'').
				(($state)?"state=".urlencode($state)."&":'').
				(($zip)?"zipcode=".urlencode($zip)."&":'').
				"";

//echo $url;

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
//		echo "\n";
//		echo "Response Code: ".$response_code.' '.$response_error."\n\n";
//		echo "Response: ".$data."\n\n";

		$jobj = json_decode($data);

		return $jobj;
	}

	function getCleanAddress($address1, $address2, $city, $state, $zip){


		$jobj = $this->apiGetData($address1, $address2, $city, $state, $zip);

//		print_r($jobj);

		$obj = array(

			'address1'	=> $jobj[0]->delivery_line_1,//$jobj[0]->components->primary_number.' '.$jobj[0]->components->street_name.' '.$jobj[0]->components->street_suffix,

			'address2'	=> (isset($jobj[0]->delivery_line_2))?$jobj[0]->delivery_line_2:'',//((isset($jobj[0]->components->secondary_number) || isset($jobj[0]->components->secondary_designator))?$jobj[0]->components->secondary_designator.' '.$jobj[0]->components->secondary_number:''),


			'city' 		=> $jobj[0]->components->city_name,
			'state'		=> $jobj[0]->components->state_abbreviation,
			'zip_code'		=> $jobj[0]->components->zipcode,
			'zipplus4'	=> $jobj[0]->components->plus4_code


		);

//		print_r($obj);

		return $obj;
	}


	function handlePOST(){

	}

	function handleFLOW(){

	}



}
