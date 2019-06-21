<?php
/**
 * Makes ordernums increment smoothly, since they all started at zero
 */


	## INCLUDE DATABASE CONNECTION
	include_once("/var/www/site_config.php");
	include_once($_SESSION['site_config']['basedir']."dbapi/dbapi.inc.php");



	$res = $_SESSION['dbapi']->scripts->getResults(array());

	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		$re2 = $_SESSION['dbapi']->voices->getFiles($row['id']);


		$x=0;

		while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

			$dat = array();
			$dat['ordernum'] = $x++;
			$_SESSION['dbapi']->aedit($r2['id'], $dat, $_SESSION['dbapi']->voices->files_table);

		}

	}


