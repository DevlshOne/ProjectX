<?
/**
 * Phone Lookup SQL Functions
 */



class PhoneLookupAPI{


	/**
	 *
	 * @param string $phone					String of the phone number
	 * @param boolean $include_archive		Perform the archive lookup as well (MUCH SLOWER)
	 * @param string $force_cluster_ids		Comma seperated string of cluster ID's
	 */
	function generateViciData($phone, $include_archive = false, $force_cluster_ids = null){

		## TRIM EVERYTHING BUT NUMBERS
		$phone = preg_replace('/[^0-9]/', '', $phone);

		## PHONE MUST BE 10 DIGITS EXACTLY
		if(strlen($phone) != 10){
			return null;
		}


		$clusters = array();

		connectPXDB();

		$res = query("SELECT * FROM vici_clusters WHERE `status`='enabled' ".

				(($force_cluster_ids)?" AND `id` IN(".mysqli_real_escape_string($_SESSION['db'],$force_cluster_ids).")":'').

					//" AND `cluster_type` != 'verifier' ".
					" ORDER BY `name` ASC",1);

		$clusters = array();
		while($row = mysqli_fetch_array($res)){

			$clusters[$row['id']] = $row;

		}


		$output = array();

		/**
		 * LOOP THROUGH ALL ACTIVE VICIDIAL CLUSTERS
		 */
		foreach($clusters as $cluster_id=>$vicidb){


			// LOCATE WHICH DB INDEX IT IS
			$dbidx = getClusterIndex($cluster_id);


			if($dbidx < 0){
				echo date("H:i:s m/d/Y")." - ERROR WITH CLUSTER ID#".$cluster_id." - ".$vicidb['ip_address']." - Cannot locate cluster on site_config/cluster stack, SKIPPING\n";
				continue;
			}


			// CONNECT TO VICIDIAL DB
			connectViciDB($dbidx);

			$output[$cluster_id] = array( 'cluster_name' => $vicidb['name'], 'cluster_ip' => $vicidb['ip_address']);

			$output[$cluster_id]['vici_log'] = fetchAllAssoc("SELECT 'Vici Log' as location, '{$connection->url}' as url, 'no' as `archive`, lead_id,campaign_id,call_date,status,user,list_id,length_in_sec,alt_dial from vicidial_log where phone_number='$phone'");
//

		}


		return $output;
	}
    function deepSearchPhone($phone) {
	    $out = [];
	    /* ADD TO THIS ARRAY FOR MORE TABLES $tableFieldArray[xxtablexx] = array('table' => 'xxtablexx', 'field' => 'xxphonexx'} */
//        $tableFieldArray['transfers'] = array('table' => 'transfers', 'field' => 'phone_num');
        $tableFieldArray['lead_tracking'] = array('table' => 'lead_tracking', 'field' => 'phone_num');
        $tableFieldArray['sales'] = array('table' => 'sales', 'field' => 'phone');
        $tableFieldArray['dnc_list'] = array('table' => 'dnc_list', 'field' => 'phone_number');
        $tableFieldArray['dnc_campaign_list'] = array('table' => 'dnc_campaign_list', 'field' => 'phone');
        foreach($tableFieldArray as $key => $val) {
            $sql = "SELECT * FROM " . $val['table'] . " WHERE `" . $val['field'] . "` = '" . $phone . "'";
            $res = fetchAllAssoc($sql);
            if ($res) {
                $out[$key][] = $res;
            }
        }
        echo json_encode($out);
    }


}
