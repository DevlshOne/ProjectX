<?



	function format_phone($phone){

		return "(".substr($phone,0,3).") ".substr($phone,3,3)."-".substr($phone, 6);

	}


	function format_phone_hyphen($phone){

		return substr($phone,0,3)."-".substr($phone,3,3)."-".substr($phone, 6);

	}