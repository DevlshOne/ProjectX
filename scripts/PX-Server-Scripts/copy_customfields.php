#!/usr/bin/php
<?

	require_once("/var/www/html/dev/db.inc.php");



	$copy_from = 4;

	$copy_to = 163;

	$res = query("SELECT * FROM custom_fields WHERE campaign_id='$copy_from'", 1);



	while($row= mysqli_fetch_array($res, MYSQLI_ASSOC)){

		unset($row['id']);

		$row['campaign_id'] = $copy_to;

		aadd($row, 'custom_fields');


	}

