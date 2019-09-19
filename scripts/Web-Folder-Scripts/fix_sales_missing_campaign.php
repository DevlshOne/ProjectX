#!/usr/bin/php
<?

	$basedir = "/var/www/dev/";

	include_once($basedir."db.inc.php");
	include_once($basedir."dbapi/dbapi.inc.php");
	include_once($basedir."utils/microtime.php");
	include_once($basedir."utils/format_phone.php");
	include_once($basedir."utils/functions.php");
	include_once($basedir."utils/db_utils.php");

	$stime = mktime(0,0,0,7,5,2016);
	$etime = $stime + 86399;

// CONNECT PX DB
	connectPXDB();

	$res = query("SELECT * FROM sales ".
			" WHERE campaign='' AND agent_lead_id > 0".
			" AND `sale_time` BETWEEN '$stime' AND '$etime'", 1);

	$sale_array = array();

	$cnt = 0;
	while($sale = mysqli_fetch_array($res, MYSQLI_ASSOC)){

		if(!$sale_array[$sale['agent_cluster_id']]){
			$sale_array[$sale['agent_cluster_id']] = array();
		}

		echo "Found missing campaign, PX ID#".$sale['lead_tracking_id']." Lead#".$sale['agent_lead_id']."\n";

		// GRAB DATA FROM VICI
		$sale_array[$sale['agent_cluster_id']][$cnt++] = $sale;
	}


	//print_r($sale_array);

	$fix_array = array();

	// GO THROUGH EACH VICI CLUSTER
	foreach($sale_array as $cluster_id => $sales){

		// CONNECT TO THE SPECIFIC VICI CLUSTER
		$dbidx = getClusterIndex($cluster_id);
		connectViciDB($dbidx);


		foreach($sales as $sale){


			// GRAB LIST ID FROM "vicidial_list"
			list($list_id) = queryROW("SELECT entry_list_id FROM vicidial_list WHERE lead_id='".$sale['agent_lead_id']."' ");

//echo $list_id."\n";

			if($list_id > 0){
				// GRAB "campaign" and "c_list_id" FROM "custom_xxxx"
				list($campaign, $campaign_code) = queryROW("SELECT campaign, c_list_id FROM custom_".$list_id." WHERE lead_id='".$sale['agent_lead_id']."'");
				//$fix_array

				$fix_array[$sale['id']] = array(
											'campaign' => $campaign,
											'campaign_code' => $campaign_code
											);
			}else{

				echo "Failed to find record for lead id# ".$sale['agent_lead_id']." on ".getClusterName($cluster_id)."\n";

			}

		}



	}


	// CONNECT BACK TO PX DB
	connectPXDB();
	$cnt = 0;
	foreach($fix_array as $sale_id => $dat){

		$cnt += aedit($sale_id, $dat, "sales");

	}



	//print_r($fix_array);



	echo "Total Fixed sales: ".number_format($cnt)."\n";
