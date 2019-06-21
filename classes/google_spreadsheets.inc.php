<?php
/***
 * Google Spreadsheet API wrapper
 */


class GoogleSpreadSheets{

	var $spreadsheet_id;

	var $sheet_name;

	var $range;
	var $client;
	var $valueInputOption = "USER_ENTERED"; // "USER_ENTERED" or "RAW"

	var $application_name = "ATC Google API Interface";

	var $credential_file = "credentials.json";
	var $token_file = 'token.json';


	function GoogleSpreadSheets($sheet_id, $sheet_name){

		$this->spreadsheet_id = $sheet_id;
		$this->sheet_name = $sheet_name;

		$this->autoSetRange();
	}


	function getClientReadOnly(){

 		$client = new Google_Client();
	    $client->setApplicationName($this->application_name);
	    $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
	    $client->setAuthConfig($this->credential_file);
	    $client->setAccessType('offline');
	    $client->setPrompt('select_account consent');

	    // Load previously authorized token from a file, if it exists.
	    // The file token.json stores the user's access and refresh tokens, and is
	    // created automatically when the authorization flow completes for the first
	    // time.
	    $tokenPath = $this->token_file;
	    if (file_exists($tokenPath)) {
	        $accessToken = json_decode(file_get_contents($tokenPath), true);
	        $client->setAccessToken($accessToken);
	    }

	    // If there is no previous token or it's expired.
	    if ($client->isAccessTokenExpired()) {
	        // Refresh the token if possible, else fetch a new one.
	        if ($client->getRefreshToken()) {
	            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
	        } else {
	            // Request authorization from the user.
	            $authUrl = $client->createAuthUrl();
	            printf("Open the following link in your browser:\n%s\n", $authUrl);
	            print 'Enter verification code: ';
	            $authCode = trim(fgets(STDIN));

	            // Exchange authorization code for an access token.
	            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
	            $client->setAccessToken($accessToken);

	            // Check to see if there was an error.
	            if (array_key_exists('error', $accessToken)) {
	                throw new Exception(join(', ', $accessToken));
	            }
	        }
	        // Save the token to a file.
	        if (!file_exists(dirname($tokenPath))) {
	            mkdir(dirname($tokenPath), 0700, true);
	        }
	        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
	    }
	    return $client;
	}



	/**
	 * Returns an authorized API client.
	 * @return Google_Client the authorized client object
	 */
	function getClientReadWrite(){

	    $client = new Google_Client();
	    $client->setApplicationName($this->application_name);
	    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
	    $client->setAuthConfig($this->credential_file);
	    $client->setAccessType('offline');
	    $client->setPrompt('select_account consent');

	    // Load previously authorized token from a file, if it exists.
	    // The file token.json stores the user's access and refresh tokens, and is
	    // created automatically when the authorization flow completes for the first
	    // time.
	    $tokenPath = $this->token_file;
	    if (file_exists($tokenPath)) {
	        $accessToken = json_decode(file_get_contents($tokenPath), true);
	        $client->setAccessToken($accessToken);
	    }

	    // If there is no previous token or it's expired.
	    if ($client->isAccessTokenExpired()) {
	        // Refresh the token if possible, else fetch a new one.
	        if ($client->getRefreshToken()) {
	            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
	        } else {
	            // Request authorization from the user.
	            $authUrl = $client->createAuthUrl();
	            printf("Open the following link in your browser:\n%s\n", $authUrl);
	            print 'Enter verification code: ';
	            $authCode = trim(fgets(STDIN));

	            // Exchange authorization code for an access token.
	            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
	            $client->setAccessToken($accessToken);

	            // Check to see if there was an error.
	            if (array_key_exists('error', $accessToken)) {
	                throw new Exception(join(', ', $accessToken));
	            }
	        }
	        // Save the token to a file.
	        if (!file_exists(dirname($tokenPath))) {
	            mkdir(dirname($tokenPath), 0700, true);
	        }
	        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
	    }
	    return $client;
	}


	function appendValues($values){

		// Get the API client and construct the service object.
		if(!$this->client){
			$this->client = $this->getClientReadWrite();
		}
		$service = new Google_Service_Sheets($this->client);


		$data = new Google_Service_Sheets_ValueRange(array(
		    'range' => $this->range,
		    'values' => $values,

		));

		$args = array(
			'valueInputOption' => $this->valueInputOption,
		);

		$response = $service->spreadsheets_values->append($this->spreadsheet_id, $this->range, $data, $args);

		echo '<pre>', var_export($response, true), '</pre>', "\n";

		return $response;

	}






	function readSheet(){

		// READING EXAMPLE
		//$response = $service->spreadsheets_values->get($spreadsheetId, $range);
		//$values = $response->getValues();
		//
		//if (empty($values)) {
		//    print "No data found.\n";
		//} else {
		//   // print "Name, Major:\n";
		//    foreach ($values as $row) {
		//        // Print columns A and E, which correspond to indices 0 and 4.
		//      //  printf("%s, %s\n", $row[0], $row[4]);
		//
		//      print_r($row);
		//    }
		//}
	}



	function autoSetRange(){
		$this->range = $this->sheet_name.'!A:ZZ';
	}

	function setSheetName($sheet_name){
		$this->sheet_name = $sheet_name;
	}

	function setRange($range){
		$this->range = $this->sheet_name.'!'.$range;
	}
	function setRangeRaw($range){
		$this->range = $range;
	}

}