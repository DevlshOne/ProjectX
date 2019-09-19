<?

    class API_DialerStatus {
        private $cacheDir = 'fcache';

        function curlClusterData($webIP, $groups, $user_groups, $curlUP) {
            $curl = curl_init();
            $curlURL = 'http://' . $webIP . '/vicidial/AST_timeonVDADall.php?RTajax=1&AGENTtimeSTATS=1' . $groups . $user_groups;
            
           //echo $curlURL;exit;
            
            curl_setopt($curl, CURLOPT_URL, $curlURL);
            curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_HEADER => true, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_USERPWD => $curlUP]);
            $data = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            switch ($code) {
                case 200:
                    break;
                case 401:
                    $data = "<HTML><div class='clusterError'>Unable to Authorize - please <a style='color:white;text-decoration:underline' href='#' onclick='$(\"#dialog-modal-vici-credentials\").dialog(\"open\");'>re-enter your VICI username and password</a></div></HTML>";
                    break;
                default:
                    $data = "<HTML><div class='clusterError'>" . curl_error($curl) . "</div></HTML>";
                    break;
            }
            curl_close($curl);
//            if (!is_dir($this->cacheDir)) {
//                mkdir($this->cacheDir, 777);
//                clearstatcache();
//            }
//            $fileOut = [];
//            $cacheFile = fopen($_SESSION['site_config']['basedir'] . $this->cacheDir . "/" . str_replace('.', '', $webIP) . ".json", "w") or print_r(error_get_last());
//            $fileOut['groups'] = $groups;
//            $fileOut['usergroups'] = $user_groups;
//            $fileOut['data'] = $data;
//            fwrite($cacheFile, json_encode($fileOut, JSON_PRETTY_PRINT));
//            fclose($cacheFile);
            return $data;
        }

        function handleAPI() {
//            if ($_SESSION['user']['priv'] >= 5) {
//                $_SESSION['api']->errorOut('Access denied to Dialer Status');
//                return;
//            }
            switch ($_REQUEST['action']) {
                default:
                case 'getClusterData':
                    $strGroups = '';
                    $strUserGroups = '';
                    $webip = trim($_REQUEST['webip']);
                    $groups = $_REQUEST['groups'];
                    if (isset($groups)) {
                        foreach ($groups as $v) {
                            $strGroups .= '&groups[]=' . urlencode($v);
                        }
                    }else{
                    	
                    	$strGroups .= '&groups[]=ALL-ACTIVE';
                    	
                    }
                    
                    
                    $user_group_filters = $_REQUEST['user_group_filter'];
                    if (isset($user_group_filters)) {
                        foreach ($user_group_filters as $v) {
                            $strUserGroups .= '&user_group_filter[]=' . urlencode($v);
                        }
                    }else{
                    	
                    	$strUserGroups .= '&user_group_filter[]=ALL-GROUPS';
                    }
                    $curlUP = (($_SESSION['user']['vici_username']) ? $_SESSION['user']['vici_username'] : $_SESSION['user']['username']) . ":" . $_SESSION['user']['vici_password'];
                    $out = $this->curlClusterData($webip, $strGroups, $strUserGroups, $curlUP);
                    break;
                case 'getClusterDataByUserPrefs':
                    
                	$user_preferences = json_decode($_SESSION['dbapi']->user_prefs->getRaw("dialer_status"), true);

                    $desiredCluster = trim($_REQUEST['c']);

                    // POP THE GLOBAL SETTINGS OFF THE END, BEFORE RENDERING
                    /// NO NEED TO POP ANYMORE, JUST MADE IT SKIP IF THE 'cluster_id' ISNT SPECIFIED
                    ///array_pop($user_preferences);
                    	
                 
                    
                    // print_r($user_preferences);exit;
                    
                    foreach ($user_preferences as $k => $v) {
                    	
                    	if(!$v['cluster_id']) continue;
                    	
                        $strGroups = '';
                        $strUserGroups = '';
                        $cluster_id = $v['cluster_id'];
                        if ($desiredCluster !== $cluster_id) continue;
                        $webip = getClusterWebHost($cluster_id);
                        
                        if(isset($v['groups']) && count($v['groups']) > 0){
	                        foreach ($v['groups'] as $w) {
	                            $strGroups .= '&groups[]=' . urlencode($w);
	                        }
                        }else{
                        	$strGroups .= '&groups[]=ALL-ACTIVE';
                        }
                        
                        if(isset($v['user_group_filter']) && count($v['user_group_filter']) > 0){
                        	
                        
                        	foreach ($v['user_group_filter'] as $w) {
                            	$strUserGroups .= '&user_group_filter[]=' . urlencode($w);
	                        }
                        }else{
                        	
                        	
                        	$strUserGroups .= '&user_group_filter[]=ALL-GROUPS';
                        	
                        }
                        $curlUP = (($_SESSION['user']['vici_username']) ? $_SESSION['user']['vici_username'] : $_SESSION['user']['username']) . ":" . $_SESSION['user']['vici_password'];
                        $out = json_encode($this->curlClusterData($webip, $strGroups, $strUserGroups, $curlUP));
                    }
                    break;
                case 'getAvailableCampaigns':
                    $clusterid = trim($_REQUEST['clusterid']);
                    $vici_idx = getClusterIndex($clusterid);
                    connectViciDB($vici_idx);
                    $res = query("SELECT `campaign_name` FROM `vicidial_campaigns` WHERE `active` = 'Y'", 3);
                    $out = json_encode($res);
                    connectPXDB();
                    break;
                case 'forceHopperReset':
                    $clusterid = trim($_REQUEST['clusterid']);
                    $vici_idx = getClusterIndex($clusterid);
                    connectViciDB($vici_idx);
                    $res = query("DELETE FROM `vicidial_hopper` WHERE `status` IN ('READY','QUEUE','DONE')");
                    $out = '';
                    connectPXDB();
                    logAction('flush_hopper', 'dialer_status', $clusterid);
                    break;
                case 'stopDialer':
                    $clusterid = trim($_REQUEST['clusterid']);
                    $vici_idx = getClusterIndex($clusterid);
                    connectViciDB($vici_idx);
                    $res = query("UPDATE `asterisk`.`vicidial_lists` SET active='N' WHERE 1");
                    $res = query("DELETE FROM `vicidial_hopper` WHERE `status` IN ('READY','QUEUE','DONE')");
                    $out = '';
                    connectPXDB();
                    logAction('stop_dialer', 'dialer_status', $clusterid);
                    break;
                case 'getAvailableUserGroups':
                    $clusterid = trim($_REQUEST['clusterid']);
                    $res = query("SELECT DISTINCT (`group_name`) FROM `user_group_translations` WHERE `cluster_id` = " . $clusterid, 3);
                    $out = json_encode($res);
                    break;
                case 'setViciCreds':
                    if (isset($_REQUEST['vici_username'])) {
                        $vUsername = trim($_REQUEST['vici_username']);
                        $_SESSION['user']['vici_username'] = $vUsername;
                    }
                    if (isset($_REQUEST['vici_password'])) {
                        $vPassword = trim($_REQUEST['vici_password']);
                        $_SESSION['user']['vici_password'] = $vPassword;
                    }
                    $out = '';
                    break;
                case 'saveUserPrefs':
                    $json_str = $_REQUEST['prefs'];
                    $_SESSION['dbapi']->user_prefs->update('dialer_status', $json_str);
                    $out = '';
                    break;
                case 'loadUserPrefs':
                	
                    $out = $_SESSION['dbapi']->user_prefs->getRaw("dialer_status");
                    
                    // CREATE DEFAULT PREFERENCES
                    if(!$out){
                    	
                    	$user_preferences = array();
                    	
                    	
                    	foreach (getClusterIDs() as $i => $v) {
                    		
                    		$user_preferences[] = array(
                    				'cluster_id' 		=> 	$v,
                    				'groups' 			=> array(0 => "ALL-ACTIVE"),
                    				'user_group_filter'	=> array(0 => "ALL-GROUPS")
                    		);
                    		
                    	}
                    	
                    	$user_preferences[] = array(
                    			
                    			'refreshInterval' => 40,
                    			'refreshEnabled' => true,
                    			'highContrast' => false
                    			
                    	);
                    	
                    	
                    	$out = json_encode($user_preferences);
                    	$_SESSION['dbapi']->user_prefs->update('dialer_status', $out);
                    	
                    }
                    break;
            }
            echo $out;
        }
    }