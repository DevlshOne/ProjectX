#!/usr/bin/php
<?php

	$basedir = "/var/www/dev/html/";

	include_once($basedir."db.inc.php");
	include_once($basedir."utils/microtime.php");
	include_once($basedir."utils/format_phone.php");
	include_once($basedir."utils/db_utils.php");

	// USED FOR DELETE FUNCTIONS
	include_once($basedir."dbapi/dbapi.inc.php");



	$res = query("SELECT * FROM voices_files WHERE 1", 1);


	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		## CHECK IF SCRIPT RECORD EXISTS STILL
		list($tmpid) = queryROW("SELECT id FROM scripts WHERE id='".$row['script_id']."' ");

		if(!$tmpid){

			echo "SCRIPT '".$row['script_id']."' FOR file '".$row['file']."' DOESN'T EXIST!\n";


			// DELETE THE FILE
			$_SESSION['dbapi']->voices->deleteFile($row['id']);

			// DELETE VOICES_FILES REFERENCE
			execSQL("DELETE FROM voices_files WHERE id='".$row['id']."' ");


			continue;
		}
		if(!file_exists($row['file']) ){

			echo "File '".$row['file']."' DOESN'T EXIST!\n";

			// DELETE VOICES_FILES REFERENCE

			execSQL("DELETE FROM voices_files WHERE id='".$row['id']."' ");

			// LEAVE SCRIPT INTACT BECAUSE ITS NOT A PROBLEM

			continue;
		}




	}

