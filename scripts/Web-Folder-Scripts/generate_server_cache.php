#!/usr/bin/php
<?php
	$basedir = "/var/www/html/reports/";

	
	
	$xmldir = "/var/www/html/download/xml/";
	
	include_once($basedir."db.inc.php");
//	include_once($basedir."utils/microtime.php");
//	include_once($basedir."utils/format_phone.php");
	include_once($basedir."utils/db_utils.php");

	include_once($basedir."classes/JXMLP.inc.php");
	
	// CONNECT PX DB
	connectPXDB();
	
	
	function getCampaignsXML($detailed_mode = false, $selected_campaign_id=0){
		
		
		$out = "<Campaigns use_cache=\"false\">";
		$tmp=null;
		
		$dat = array();
		
		$res = query("SELECT * FROM `campaigns` WHERE (`status`='active') AND `px_hidden`='no' ORDER BY `name` ASC");
					
					
		$tmpcpgnid = -1;
		$reallydoit = false;
		
		
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			
			$out .= "<Campaign ";
			
			
			foreach($row as $key=>$val){
				
				$out .= ($key."=\"".htmlentities($val)."\" ");
			}
			
			$out .= ">";
			
			// ONLY SEND THE NAMES ONCE THEY ARE LOGGED IN
			//				if(detailed_mode){
			//
			//					out += app.names.getXML();
			//
			//				}
			
			$tmpcpgnid = intval($row['id']);
			
			
			$reallydoit = (($detailed_mode && (($selected_campaign_id == $tmpcpgnid) || ($selected_campaign_id == -1)) )?true:false);
			
			
			$out .= getVoicesXML($tmpcpgnid, $reallydoit );
			
			if($reallydoit){
				
				// INCLUDE CUSTOM FIELDS DATA
				$out .= getCustomFieldsXML($tmpcpgnid);
				
				// INCLUDE PROBLEM TYPES DATA
				$out .= getProblemTypesXML($tmpcpgnid);
				
				// DISPOZ MOFUCKAAAA
				$out .= getDispoStatuses($tmpcpgnid);
				
				
				// VICI CLUSTER NAME CACHE DUMP
				$out .= getClusterCacheXMLData();
				
				
				// CAMPAIGN SETTINGS
				$out .= getCampaignSettings($tmpcpgnid);
				
			}
			
			
			$out .= "</Campaign>";
			
		}
		
		
		
					
			
				
		$out .= "</Campaigns>\n";
			
		return $out;
	}
	
	
	/**
	 * THIS LOADS ALL THE SETTINGS FOR THE PARTICULAR CAMPAIGN,
	 * (A PART OF THE CAMPAIGN CACHE REFRESH PROCESS)
	 * @param campaign_id
	 * @return	An set of XML tags in string format, representing the settings for the campaign_id they logged into
	 */
	function getCampaignSettings($campaign_id){
		
		$out = "<CampaignSettings id=\"".$campaign_id."\">";
		
		$res = query("SELECT `campaign_code`,`variables` FROM `campaign_settings` WHERE `campaign_id`='".$campaign_id."'");// WHERE enabled='yes'
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
						
						
			$out .= "<CampaignSetting campaign_code=\"".htmlentities($row['campaign_code'])."\" variables=\"".htmlentities($row['variables'])."\" />";
			
			
		}

		$out .= "</CampaignSettings>";
		return $out;
	}
	
	
	function getVoicesXML($campaign_id, $detailed_mode){
		
		$curtime = time();
		
		echo "Voice Generation Started(".$campaign_id.") @ ".$curtime."\n";
		
		$out = "<Voices>";
		$tmp=null;
		
	
		$vid = -1;
		$cid = -1;
		$sid = -1;
		
		$res = query("SELECT * FROM `voices` WHERE `status`='enabled' ".
						
					(($campaign_id > 0)?" AND `campaign_id`='".$campaign_id."' ":"").
					
					" ORDER BY `name` ASC"
		);
		
							
							
		
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			

			
			$cid = intval($row['campaign_id']);

			
			if($detailed_mode ){
				
				echo ("Campaign ID#".$cid." - Detailed mode");
				
				$out .= "<Voice ";
				
				foreach($row as $key=>$val){
					
					$out .= $key."=\"".htmlentities($val)."\" ";
				}
					
				
				$out .= ">";
				
				
				
				
				// GET SCRIPTS
				$vid = intval($row['id']);
				
				
				$res2 = query("SELECT * FROM `scripts` WHERE `voice_id`='".$vid."' AND `campaign_id`='".$cid."'  ORDER BY `keys` ASC");

							
				$out .= "<Scripts>";
							
				echo "Campaign ID#".$cid." - Getting Scripts...\n";
							
				while($r2 = mysqli_fetch_array($res2, MYSQLI_ASSOC)){
					

					
					$out .= "<Script ";
					
					foreach($r2 as $key=>$val){
						
						$out .= $key."=\"".htmlentities($val)."\" ";
					}
					
					$out .= ">";
					
					
					$sid = intval($r2['id']); // SCRIPT ID
					
					// GET FILES FOR THE SCRIPTS
					$res3 = query("SELECT * FROM `voices_files` WHERE `voice_id`='".$vid."' AND `script_id`='".$sid."' AND `repeat`='no' ORDER BY `ordernum`");
	
						
						
					while($r3 = mysqli_fetch_array($res3, MYSQLI_ASSOC)){
						
						// GET ALL VOICE FILES FOR THIS SCRIPT
						$out .= $_SESSION['JXMLP']->makeXMLFromHash("VoiceFile", $r3);
						
					}

				
					// GET REPEATS
					$res3 = query("SELECT * FROM `voices_files` ".
							"WHERE `voice_id`='".$vid."' AND `script_id`='".$sid."' AND `repeat` != 'no' ORDER BY `ordernum`");
					
						
					while($r3 = mysqli_fetch_array($res3, MYSQLI_ASSOC)){
						
						// GET ALL VOICE FILES FOR THIS SCRIPT
						$out .= $_SESSION['JXMLP']->makeXMLFromHash("RepeatFile", $r3);
						
					}
						
	
					
					
					$out .= "</Script>";
									
				}
			
				$out .= "</Scripts>";
		
				$out .= "</Voice>";
						
			}else{
				
				echo "Campaign ID#".$cid." - Voices loaded, simple version\n";
				
				$out .= $_SESSION['JXMLP']->makeXMLFromHash("Voice", $row);
				
			}
			
		}
		
			
		$out .= "</Voices>";
		
		$endtime = time();
		
		echo "Voice Generation ended(".$campaign_id.") @ ".$endtime." - ".($endtime - $curtime)." ms\n";
		
		return $out;
	}



	
	
	function getCustomFieldsXML($campaign_id){
		
		$out = "<PXCustomFields>";

		$res = query("SELECT * FROM `custom_fields` WHERE `deleted`='no' ".
						
					(($campaign_id > 0)?" AND `campaign_id`='".$campaign_id."' ":"").
					
					" ORDER BY `screen_num` ASC, `field_step` ASC"
		);
						
							
							
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
				

				
			$out .= $_SESSION['JXMLP']->makeXMLFromHash("PXField", $row);
				
			
		}
		
		

		
		$out .= "</PXCustomFields>";

		return $out;
	}
	
	
	
	function getProblemTypesXML($campaign_id){
		
		$out = "<ProblemTypes>";
		
		$res = query("SELECT * FROM `problem_types` WHERE 1 ORDER BY `order_num` ASC");
				
					
					
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			
			$out .= $_SESSION['JXMLP']->makeXMLFromHash("ProblemType", $row);
			
		}
					
		$out .= "</ProblemTypes>";
				
				
		return $out;
	}
	
	
	function getDispoStatuses($campaign_id){
		
		$out = "<DispoStatuses>";
		
		$res = query("SELECT * FROM `dispo_statuses` WHERE `campaign_id`='".$campaign_id."' ORDER BY `key` ASC");
			
			
		// NO DISPOS SPECIFIED FOR THSI CAMPAIGN, LOAD DEFAULTS
		if(mysqli_num_rows($res) <= 0){
			
			// LOAD DEFAULTS, AKA campaign_id='0'
			$res = query("SELECT * FROM `dispo_statuses` WHERE `campaign_id`='0' ORDER BY `key` ASC");
			
		}
			
			
			
			
			
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			
			$out .= $_SESSION['JXMLP']->makeXMLFromHash("DispoStatus", $row);
			
		}
			

		
		$out .= "</DispoStatuses>";
		
		
		return $out;
	}
	
	
	function getClusterCacheXMLData(){
		$out = "<ClusterNames>";
		
		
		$res = query("SELECT `id`,`name` FROM vici_clusters");
		
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$out .= "<ClusterName id=\"".$row['id']."\" name=\"".htmlentities($row['name'])."\" />";
			
		}
		
		
		$out .= "</ClusterNames>";

		
		return $out;
	}
	
	
	
	
	
	
	
	// GENERATE LOGIN CACHE	
	$xml = getCampaignsXML(false, 0);
	
	
	file_put_contents($xmldir."login.xml", $xml);
	
	
	$res = query("SELECT * FROM `campaigns` WHERE `status`='active' AND `px_hidden`='no'");
	
	// GENERATE YOUR ASS OFF
	while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
		
		$campaign_id = $row['id'];
		
		echo "refreshCampaignCache Refreshing Campaign #".$campaign_id."\n";
		
		
		$xml = getCampaignsXML(true, $campaign_id);
		
		echo "refreshCampaignCache Campaign #".$campaign_id." is ".strlen($xml)." bytes in size.\n";
		
		file_put_contents($xmldir.$row['id'].".xml", $xml);
	}
	
	
	
	
	
	
	
	
	
	
	
	