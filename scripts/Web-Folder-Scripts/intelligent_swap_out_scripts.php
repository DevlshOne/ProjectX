#!/usr/bin/php
<?php
/**
 * 	Intelligent swapout scripts
 *  Be able to pass in a chunk of new WAVs with standardized naming convention,
 *  and specify the folder to push shit to, and have it detect what files to swap and do it.
 *
 * Made by Jon, to make Cassie's life easier :)
 */





/**********/

	// CODE BASE FOLDER
	$base_dir = "/var/www/html/reports/";

	include_once($base_dir."site_config.php");
	include_once($base_dir."db.inc.php");
	include_once($base_dir."util/db_utils.php");


	connectPXDB();


	function missingArgs($argv){
		echo 	"Missing Arguments!\nPlease Specify the source files, and the target folder!\n\n".
				"Example:\n".
				$argv[0]." (files.wav ...) (target folder)\n".
				$argv[0]." 100010.wav 101020.wav 120023.wav /playback/voice-5/\n\n";
		exit;
	}




	$cntargs = count($argv);
	$last_arg = $cntargs - 1;


	// REQUIRE VALID ARGUMENTS/PARAMETERS
	if(!$argv[1] || $cntargs < 3){

		missingArgs($argv);

	}

	$target_dir = $argv[$last_arg];

	echo "Target: ".$target_dir."\n";


	$source_stack = array();
	$replacement_stack = array();

	// LOOP THROUGH THE FILE ARGUMENTS
	for($x=1,$y=0;$x < $cntargs - 1;$x++){

		$path_parts = pathinfo($argv[$x]);



		// EXTRACT FILE CODE
		$fname_arr = preg_split("/[^a-zA-Z0-9]/", $path_parts['basename'], -1, PREG_SPLIT_NO_EMPTY);

		// BREAK FILENAME DOWN TO THE FIRST PART
		$file_code = $fname_arr[0];


	//	echo "File ".$x." : ".$argv[$x]." CODE: ".$file_code." \n";

		// INIT THE ARRAYS
		$source_stack[$y] = $argv[$x];
		$replacement_stack[$y] = array();


		// SCAN DESTINATION FOLDER FOR SIMILAR FILES
		$filelist = scandir($target_dir);

		//print_r($filelist);
		foreach($filelist as $filename){

			// SKIP DOT FILES
			if($filename[0] == '.')continue;

			$fname_arr = preg_split("/[^a-zA-Z0-9]/", $filename, -1, PREG_SPLIT_NO_EMPTY);
			$tmpcode = $fname_arr[0];


			// IF THE FILE CODE MATCHES, ADD TO THE REPLACEMENT STACK
			if($tmpcode == $file_code){
				$replacement_stack[$y][] = $target_dir.'/'.$filename;
			}
		}

		$y++;


	} // END OF FOR LOOP (gathering files to replace)


	// DUMP INFORMATION ABOUT WHAT IS BEING REPLACED
	foreach($source_stack as $idx=>$source_file){

		//echo "File ".$idx." : ".$source_file." CODE: ".$file_code." \n";


		if(count($replacement_stack[$idx]) <= 0){

			echo "Source file: ".$source_file." HAS NO FILES TO REPLACE!\n";

		}else{

			echo "Source file: ".$source_file." will replace the following:\n";

			foreach($replacement_stack[$idx] as $replace_file){

				echo $replace_file."\n";

			}

		}
		echo "\n";
	}

	// PROMPT THE USER FOR YES/NO INPUT
	echo "Ready to proceed? (Y/N) ";
	$c = fread(STDIN, 1);

	// ANYTHIGN THAT ISN'T YES, FAIL/CANCEL
	if(strtolower($c) != 'y'){
		echo "Cancelled.\n";
		exit;


	}


	// PROCEED WITH THE COPIES!
	echo "Proceeding, standby!\n";

	// GO THROUGH EACH SOURCE FILE
	foreach($source_stack as $idx=>$source_file){

		//echo "File ".$idx." : ".$source_file." CODE: ".$file_code." \n";

		$sourcepath = pathinfo($source_file);

		// LOOP THROUGH EACH FILE THIS SOURCE IS SUPPOSED TO REPLACE
		foreach($replacement_stack[$idx] as $replace_file){

			// BUILD THE SHELL COMMAND TO MAKE THE COPY
			$cmd = "cp ".escapeshellarg($sourcepath['dirname'].'/'.$sourcepath['basename'])." ".escapeshellarg($replace_file);

			// EXECUTE THE COPY COMMAND
			echo $cmd."\n";
			echo `$cmd`;

		}
		//echo "\n";
	}

	echo "Done!\n";













