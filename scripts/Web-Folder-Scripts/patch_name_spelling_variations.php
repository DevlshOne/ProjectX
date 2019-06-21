#!/usr/bin/php
<?php

	global $name_array, $file_array, $folder;


	## INCLUDE DATABASE CONNECTION
	include_once("/var/www/site_config.php");
	include_once($_SESSION['site_config']['basedir']."db.inc.php");




	if(!$argv[1]){

		die("Missing arguments. \n\nPlease supply the folder location of the NAMES to patch.\nExample: ".$argv[0]." /playback/names/voice-1\n");

	}


	if(!is_dir($argv[1])){

		die("Folder provided: ".$argv[1]." is not a directory.\n");

	}


	$folder = $argv[1];


	$skip_array = array("GENERIC.wav", ".", ".."); // SKIP THESE ITEMS AUTOMATICALLY

	class FileInfoCls{

		var $filename;
		var $name;

		function FileInfoCls($filename){

			$this->filename = $filename;

			$this->name = strtolower( substr($filename, 0, strpos($filename,".")) );
		}

	}


	$file_array = array();



	echo "Loading input files from $folder...";

    if ($dh = opendir($folder)) {
        while (($file = readdir($dh)) !== false) {


			if(in_array($file, $skip_array))continue;


			$file_array[] = new FileInfoCls($file);

        //	echo "filename: $file\n";// : filetype: " . filetype($dir . $file) . "\n";

        }
    }else{

    	die("Error opening directory ".$folder);
    }


	echo "Loaded ".count($file_array)." from folder: ".$folder."\n";

//	print_r($file_array);
//	exit;




/**
 * findFileByName($name)
 * Searches the file list for the name specified.
 * @return Returns the File Index number, to locate it on the array/stack, or -1 if not found.
 */
	function findFileByName($name){
		global $file_array; // DECLARE TO PULL IT INTO THIS FUNCTION, FOR LOCAL USE

		$name = strtolower($name); // LOWERCASE ALL OF THE THINGS, FOR COMPARISON


		foreach($file_array as $x=>$fileobj){

			if($fileobj->name == $name) return $x;


		}

		return -1;
	}


/**
 * findName($name)
 * Searches the "master" name list, and then its variations, to find a name
 * @return Returns the "Master" index number (0 or greater) of the name it matched, or -1 if not found at all.
 */
	function findName($name){
		global $name_array; // DECLARE TO PULL IT INTO THIS FUNCTION, FOR LOCAL USE

		$name = strtolower($name); // LOWERCASE ALL OF THE THINGS, FOR COMPARISON

		foreach($name_array as $x=>$nameobj){


			// FIRST CHECK THE MASTER NAME, SEE IF WE CAN HEADSHOT THIS NOOB
			if($nameobj->name == $name) return $x;

			// DAMN IT, ALRIGHT, CHECK VARIATIONS NEXT
			foreach($nameobj->variations as $y=>$subobj){

				if($subobj->name == $name) return $x; // WE ARE RETURNING THE MAIN INDEX TO THE NAME
			}

		}

		return -1;
	}



/**
 * findFirstExistingFile($nameobj)
 * Find the first name, who's file exists, to use as the "main" name, to symlink from
 * @return Returns the name of the first name to exist, or null if name not found at all.
 */
	function findFirstExistingFile($nameobj){
		global $folder; // PULL THIS IN HERE

		$tmpfile = $folder.'/'.$nameobj->name.".wav";

		if(!file_exists($tmpfile)){

			// CHECK VARIATIONS
			foreach($nameobj->variations as $y=>$subobj){
				$tmpfile =  $folder.'/'.$subobj->name.".wav";
				if(file_exists($tmpfile)){
					return $subobj->name;
				}
			}

			return null;
		}else{

			// MASTER NAME FOUND
			return $nameobj->name;
		}
	}



/**
 * THE MAIN NAME OBJECT, USED FOR BOTH MASTER NAMES, AND VARIATIONS
 */
	class NameObj{

		var $id;
		var $name;
		var $variations;

		function NameObj($id, $name){
			$this->id = $id;
			$this->name = strtolower($name); // FORCE LOWERCASE

			$this->variations = array();
		}
	}


	echo "Loading Master names...";

	$name_array = array();

	$res = query("SELECT * FROM names_master WHERE `type`='master'");
	$x = 0;
	$total_variations = 0;
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$name_id = $row['id'];

		$name_array[$x] = new NameObj($name_id, $row['name']);


		$re2 = query("SELECT * FROM names_master WHERE `primary_name_id`='$name_id'");
		$y = 0;
		while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

			$name_array[$x]->variations[$y] = new NameObj($r2['id'], $r2['name']);

			$y++;

			$total_variations++;
		}


		$x++;
	}

	echo "Loaded ".number_format($x)." MASTER names, and ".number_format($total_variations)." variations.\n";


	echo "Running... Please wait...\n";


	$fix_array = array();
	$missing_array = array();

	$total_missing = 0;
	foreach($name_array as $nameobj){

		$found = false;
		$partially_missing = false;


		// LOOK FOR THE MASTER NAME FIRST
		$idx = findFileByName($nameobj->name);

		if($idx > -1){

			$found = true;

		}else{

			//echo "Master name: ".$nameobj->name." not found in files. Checking variations...\n";
			$total_missing++;
			$partially_missing = true;
		}


		foreach($nameobj->variations as $y=>$subobj){

			$idx = findFileByName($subobj->name);

			if($idx > -1){
				$found = true;

			}else{

				$partially_missing = true;
				$total_missing++;


			}


		}

		//eecho "Variations for name: ".$nameobj->name." not found in files\n";



		if(!$found){

			$missing_array[] = $nameobj;

		}else{

			if($partially_missing){

				$fix_array[] = $nameobj;

			}


		}

	}

	echo "Processing Complete! ".count($missing_array)." COMPLETELY MISSING, ".count($fix_array)." Patchable master names.($total_missing Total missing)\n\n";

//	echo "MISSING:\n";

//	print_r($missing_array);

//	echo "PARTIALLY MISSING:\n";

//	print_r($fix_array);


	foreach($missing_array as $nameobj){

		echo $nameobj->name."\t\tNeeds recorded.\n";

	}




	echo "\nPatching Fixable names...\n";




	foreach($fix_array as $nameobj){


		$main_name = findFirstExistingFile($nameobj); // THE FILENAME TO LINK TO THE OTHER NAMES

		if($main_name == null){
			echo "ERROR: no name files found for master name: ".$nameobj->name."\n";
			continue;
		}

		$main_name_file = $folder.'/'.$main_name.'.wav';

		$tmpname = $folder.'/'.$nameobj->name.".wav";
		if(!file_exists($tmpname)){
			echo "*  Patching missing master name ".$nameobj->name."($tmpname) to ".$main_name_file."\n";

			$cmd = "ln -sf \"".$main_name_file."\" \"".($tmpname)."\" ";

			echo $cmd."\n";
			echo `$cmd`;
		}


		foreach($nameobj->variations as $subobj){

			$tmpname = $folder.'/'.$subobj->name.".wav";
			if(!file_exists($tmpname)){
				echo "*  Patching missing variation name ".$subobj->name." ($tmpname) to ".$main_name_file."\n";

				$cmd = "ln -sf \"".$main_name_file."\" \"".($tmpname)."\" ";

				echo $cmd."\n";
				echo `$cmd`;
			}
		}




	}















