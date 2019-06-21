#!/usr/bin/php
<?php

        $base_dir = "/var/www/html/dev/";

        include_once($base_dir."site_config.php");
        include_once($base_dir."db.inc.php");


        connectPXDB();


		echo date("H:i:s m/d/Y")." Starting campaign_code update\n";


		$res = query("SELECT DISTINCT(campaign_code), account_id FROM transfers", 1);

		$insertSQL = "INSERT IGNORE INTO `campaign_codes` (campaign_code, account_id, time_added) VALUES ";

		echo date("H:i:s m/d/Y")." Building insert query\n";

		$x=0;
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			if(!trim($row['campaign_code']))continue;

			if($x++ > 0)$insertSQL .= ',';

			$insertSQL .= "('".$row['campaign_code']."','".$row['account_id']."','".time()."')";
		}

		if($x > 0){

			echo date("H:i:s m/d/Y")." Running the insert query...";

			$cnt = execSQL($insertSQL);

			echo $cnt." records added.\n";
		}


		echo date("H:i:s m/d/Y")." Done\n";