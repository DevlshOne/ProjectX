#!/usr/bin/php
<?php
/* PX - QUIZ EXPORT - Extract TSV formatted DATA for the PX Simulators/Quiz
 * Written By: Jonathan Will
 * Created on Jan 26, 2018
 */


	$seperator = "\t";

	$basedir = "/var/www/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."dbapi/dbapi.inc.php");
	include_once($basedir."utils/db_utils.php");


	connectPXDB();


	if(!$argv[1]){

		die("Missing Arguments\n");
	}



	$quiz_id = intval($argv[1]);

	list($quiz_name) = queryROW("SELECT name FROM quiz WHERE id='$quiz_id'");

	echo "Quiz".$seperator."Question".$seperator."Answer Key".$seperator."File\n";


	$res = query("SELECT * FROM quiz_questions WHERE quiz_id='$quiz_id' ORDER BY `answer` ASC",1);

	while($row = mysql_fetch_array($res, MYSQL_ASSOC)){


		echo 	$quiz_name.$seperator.
				$row['question'].$seperator.
				$row['answer'].$seperator.
				$row['file']."\n";



	}