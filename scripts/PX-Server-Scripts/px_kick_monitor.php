#!/usr/bin/php
<?php
/**
 * PX KICK MONITORS - Used to stop recording when a call has ended
 * 
 */


	if(count($argv) < 2){
		die("Missing args. Please provide extension.\n");
	}
	
	$extension = intval($argv[1]);
	
	
	if($extension < 10241000){
		
		die("Invalid arg: Extension too small of a number.\n");
		
	}
	
	
	$meetmeroom = $extension;


	$asterisk_cmd = 'asterisk -rx "meetme list '.$meetmeroom.'" ';
	
	
	$results = `$asterisk_cmd`;
	
	
	$lines = preg_split("/\r\n|\r|\n/", $results, -1, PREG_SPLIT_NO_EMPTY );
	
	//print_r($lines);
	
	
	foreach($lines as $line){
		
		$chunks = preg_split("/\s/", $line, -1, PREG_SPLIT_NO_EMPTY);
		
		if(count($chunks) < 6)continue;
		
		
		for($x=5;$x < count($chunks);$x++){
			
			if($chunks[$x] == "Channel:"){
				$x++;
				break;
			}
		}

		$channel = $chunks[$x];
		
		if(!preg_match('/Local\/'.$meetmeroom.'@px-record/', $channel)){
			continue;
		}
		
		$uidx = $chunks[2];
		
		$asterisk_cmd = 'asterisk -rx "meetme kick '.$meetmeroom.' '.$uidx.'"';
		
		echo $asterisk_cmd."\n";
		
		echo `$asterisk_cmd`;
		
		//print_r($chunks);
	}
	