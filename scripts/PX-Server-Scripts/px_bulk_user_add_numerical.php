#!/usr/bin/php
<?php
/**
* Bulk add user tool, mainly for dev/testing purposes
**/

	include_once("/var/www/html/dev/db.inc.php");


	function missingArgs($argv){
		die("Error: Please supply the correct arguments\n".
			"Usage: ".$argv[0]." <Starting number/extension> <count> <vici_group> <password>\n".
			"Example: ".$argv[0]." 9001 10 SYSTEM-BCRSF ccilv\n".
			"\n");
	}



	if(count($argv) < 5){	missingArgs($argv);	}

	$userbase = intval($argv[1]);
	$count = intval($argv[2]);
	$vici_group = trim($argv[3]);
	$password = trim($argv[4]);

	if($userbase < 1 || $count < 1 || strlen($password) < 1) missingArgs($argv);


	echo "Creating $count users starting with '$userbase', correct?\n".
		"(ctrl-c to say no, enter key to continue)\n";

	fgetc(STDIN);

	echo "Starting!\n";

	for($x=0; $x < $count; $x++,$userbase++){

		$newuser = $userbase;

		echo $newuser."\n";

		list($test) = queryROW("SELECT id FROM users WHERE username LIKE '".addslashes($newuser)."' ");

		if($test > 0){

			echo "Skipping $newuser, user exists!\n";
			continue;
		}


		$dat = array();
		$dat['username'] = $newuser;
		$dat['password'] = md5($password);

		$dat['enabled'] = 'yes';
		$dat['login_code'] = md5($newuser.uniqid().$x);

		$dat['user_group'] = $vici_group;
		$dat['priv'] = 2;

		$dat['createdby_time'] = time();

		$uid = aadd($dat, 'users');

		echo "Added user $newuser as ID# $uid\n";
	}

