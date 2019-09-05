#!/usr/bin/php
<?php



	$before_silence_duration = 0.2;
	$after_silence_duration = 0.2;

	$silence_noise_level = "1%";


	$output_folder = "./trimmed/";


	/***********************/

	// ARGUMENT CHECK
	if(count($argv) < 2){
		die("Error, missing args.\n".$argv[0]." input.wav input2.wav ....\n");
	}


	// MAKE THE DIRECTORY IF IT DOESNT EXIST
	if(!is_dir($output_folder)){
		$cmd = "mkdir $output_folder";
		echo `$cmd`;

		echo "Created output folder: $output_folder\n";
	}




	foreach($argv as $idx=>$file){

		$outfile = $output_folder.$file;


		$cmd = "sox ".escapeshellarg($file)." ".escapeshellarg($outfile)." silence 1 ".$before_silence_duration." ".$silence_noise_level." 1 ".$after_silence_duration." ".$silence_noise_level;

		echo $cmd."\n";
		echo `$cmd`;

	}

	echo "Done. \n";



