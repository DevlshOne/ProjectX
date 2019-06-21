#!/usr/bin/php
<?php

	$base_dir = "/var/www/";

	$host_array = array(
                        "10.101.15.50",
                        "10.101.15.51",
                        "10.101.1.5",
                        "10.101.1.6",
                        "10.101.2.5",
                        "10.101.2.6",
                        "10.101.3.5",
                        "10.101.3.6",
                        "10.101.4.5",
                        "10.101.4.6",
                        "10.101.5.5",
                        "10.101.5.6",
                        "10.101.6.5",
                        "10.101.6.6",
                        "10.101.7.5",
                        "10.101.7.6",
                        "10.101.13.5",
                        "10.101.13.6",
                        "10.101.11.5"
		);


	// INCLUDE XML PARSING FUNCTIONS!
	include_once($base_dir."classes/JXMLP.inc.php");



	$connected_extensions = array();

	$dupe_extensions = array();

	foreach($host_array as $host){

		$url = "http://".$host.":2288/Status/?xml_mode";


		$ch = curl_init($url);
	 	curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$xmldata = curl_exec($ch);
		curl_close($ch);


		$dataarr = $_SESSION['JXMLP']->grabTagArray($xmldata,"PXUser",true);

		if(count($dataarr) > 0){
			foreach($dataarr as $xml){

				$row = $_SESSION['JXMLP']->parseOne($xml,"PXUser",1);

				if(in_array($row['extension'], $connected_extensions)){
					$dupe_extensions[] = $row['extension'];
				}else{
					$connected_extensions[] = $row['extension'];
				}
			}
		}
	}

	echo "Duplicates:\n";

	if(count($dupe_extensions) > 0){
		foreach($dupe_extensions as $ext){

			echo $ext."\n";
		}

	}

