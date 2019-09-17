#!/usr/bin/php
<?php
        $basedir = "/var/www/dev/";

        include_once($basedir."db.inc.php");
        include_once($basedir."utils/db_utils.php");



	$sql = "SELECT * FROM `sales` WHERE sale_time BETWEEN '1508610200' AND '1508618600' AND agent_cluster_id=11 AND (campaign = 'NTPA' AND campaign_code='NPTAC-M') OR (campaign='AFERF' AND campaign_code='AFERFC-M' AND is_paid='yes')";



	$res = query($sql, 1);


	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		echo "Updating lead #".$row['lead_tracking_id']." to ".$row['campaign'].' - '.$row['campaign_code']."\n";

		execSQL ("UPDATE lead_tracking SET campaign='".mysqli_real_escape_string($_SESSION['db'],$row['campaign'])."', campaign_code='".mysqli_real_escape_string($_SESSION['db'],$row['campaign_code'])."' WHERE id='".$row['lead_tracking_id']."'");

	}
