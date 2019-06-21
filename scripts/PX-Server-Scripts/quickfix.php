<?php


	include("/var/www/html/dev/db.inc.php");



	$res = query("SELECT * FROM quiz_questions WHERE `file` LIKE '/playback/quiz/13%'",1);

	while($row = mysqli_fetch_array($res)){
///playback/quiz/1365598707
		$newname = '/playback/quiz/'.substr($row['file'],25);

		echo $newname."\n";

		execSQL("UPDATE quiz_questions SET `file`='$newname' WHERE id='".$row['id']."'");
	}
