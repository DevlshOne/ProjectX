<? /***************************************************************
 * CallerID Stats Report
 * Written By: Jonathan Will
 ***************************************************************/

    $_SESSION['callerid_stats_report'] = new CallerIDStatsReport;

    class CallerIDStatsReport {


    	var $answering_limit_red = 90;		// PERCENT OF ANSWERING MACHINES BEFORE TRIGGERING WARNING
		var $total_calls_required = 100;	// NUMBER OF CALLS MINIMUM, BEFORE A CID IS CONSIDERED FOR ALERTING
    	
    	
		var $default_days = 7; ## HOW FAR BACK TO LOOK, BY DEFAULT
		
    	
        function CallerIDStatsReport() {

            ## REQURES DB CONNECTION!
            $this->handlePOST();
        }

        function handlePOST() {

        }

        function handleFLOW() {

            if (!checkAccess('callerid_stats_report')) {

                accessDenied("Caller ID stats report");

                return;

            } else {

                $this->makeReport();

            }

        }

        function generateData($cluster_id = 0, $campaign = null, $state = null, $stime=0, $etime=0, $user_group = NULL, $only_bad_numbers = false) {

            $cluster_id = intval($cluster_id);
            $stime = intval($stime);
            $etime = intval($etime);

  
//             if (!$cluster_id) { //|| !$call_group
//                 return NULL;
//             }
            
            
            $tagsql = "";
            
            if($cluster_id > 0){
	            $cluster_row = getClusterRow($cluster_id);
	            if(!$cluster_row){
	            	return NULL;
	            }
	            
	            $tagsql .= " AND d2.tag='".mysqli_real_escape_string($_SESSION['db'], $cluster_row['callerid_tag'])."' ";
            }else{
            	
            	$tagsql .= " AND d2.tag IN (";
            	
            	$x=0;
            	foreach($_SESSION['site_config']['db'] as $idx=>$db){

            		$cluster_row = getClusterRow($db['cluster_id']);
            		if(!$cluster_row){
            			
            			continue;
            		}
            		
            		
            		if($x++ > 0)$tagsql.= ",";
            		
            		
            		$tagsql .= "'".mysqli_real_escape_string($_SESSION['db'], $cluster_row['callerid_tag'])."'";
            		
            	}
            	$tagsql .= ") ";
            }
            
            
            ///echo $tagsql;
            
            
            $user_group_sql = ''; // USED FOR THE VICI PART OF THE QUERY

            if (is_array($user_group)) {
//

                if (php_sapi_name() != "cli") {

                	if ($user_group[0] == '' && (($_SESSION['user']['priv'] < 5) && ($_SESSION['user']['allow_all_offices'] != 'yes'))) {

                		$user_group = $_SESSION['assigned_groups'];

                    }
                }

//print_r($call_group);

                $x = 0;

                foreach ($user_group as $group) {

                    if (trim($group) == '') continue;

                    if ((php_sapi_name() != "cli") && (($_SESSION['user']['priv'] < 5) && ($_SESSION['user']['allow_all_offices'] != 'yes')) && is_array($_SESSION['assigned_groups']) && !in_array($group, $_SESSION['assigned_groups'], false)) {
                        //echo "skipping $group";
                        continue;
                    }

                    if ($x++ == 0) $user_group_sql = " AND ("; else        $user_group_sql .= " OR ";

                    $user_group_sql .= " `user_group`='" . mysqli_real_escape_string($_SESSION['db'], $group) . "' ";

                }

                if ($x > 0) {
                    

                    $user_group_sql .= ")";
                }

            } else if ($user_group) {

            	if ((php_sapi_name() != "cli") && is_array($_SESSION['assigned_groups']) && !in_array($user_group, $_SESSION['assigned_groups'], false)) {
                	$user_group = '';
                }

                
                $user_group_sql = " AND `user_group`='" . mysqli_real_escape_string($_SESSION['db'], $user_group) . "' ";

            } else {

                if ((php_sapi_name() != "cli") && ($_SESSION['user']['priv'] < 5) && ($_SESSION['user']['allow_all_offices'] != 'yes')) {

                    $x = 0;

                    foreach ($_SESSION['assigned_groups'] as $group) {

                        if ($x++ == 0) $user_group_sql = " AND ("; else        $user_group_sql .= " OR ";

                        $user_group_sql .= " `user_group`='" . mysqli_real_escape_string($_SESSION['db'], $group) . "' ";

                    }

                    if ($x > 0) {
                      
                        $user_group_sql .= ")";
                    }

                }

            }

            
            // PX SQL WHERE CLAUSE
            $where = " WHERE 1 ".
              (($stime && $etime) ? " AND `time` BETWEEN '$stime' AND '$etime' " : '').
              (($cluster_id > 0) ? " AND vici_cluster_id='$cluster_id' " : "").
              
              //(($campaign) ? " AND `vici_campaign_id`='$campaign' " : "").
              
              
              $user_group_sql.
              "";
            


/***
 * BRENTS QUERY
 * 
 * INSTRUCTIONS: 
I got two queries for you.
If query #1 produces no results run query#2.
If query #1 produces results DO NOT run query #2.


 * Query #1
SELECT d2.tag, c.name as campaign_name, d.state, d.phone FROM callids.did_lists dl
JOIN dids d ON dl.id = d.did_list_id AND d.deleted_at IS NULL
JOIN campaigns c ON dl.id = c.did_list_id and c.deleted_at IS NULL
JOIN campaign_dialer cd ON c.id = cd.campaign_id
JOIN dialers d2 ON cd.dialer_id = d2.id and d2.deleted_at IS NULL
WHERE dl.deleted_at IS NULL
AND d2.tag = 'cold_2'
AND c.name = 'NPTA'
AND d.state = 'OR';
 * 
 * 
 * Query #2
SELECT d2.tag, c.name as campaign_name, d.state, d.phone FROM callids.did_lists dl
JOIN dids d ON dl.id = d.did_list_id AND d.deleted_at IS NULL
JOIN dialers d2 ON dl.dialer_id = d2.id AND d2.deleted_at IS NULL
JOIN campaign_dialer cd ON d2.id = cd.dialer_id
JOIN campaigns c ON c.id = cd.campaign_id
WHERE dl.dialer_id IS NOT NULL
AND dl.deleted_at IS NULL
AND d2.tag = 'cold_2'
AND c.name = 'NPTA'
AND d.state = 'OR'
ORDER BY dl.dialer_id;
 * 
 * 
Query #1 is the new method. (has been converted over)
Query #2 is the old method. (has not been coverted over)
 * 
 */            
              
			// CONNECT SKUNK DB, CALLER ID DATABASE
			connectCallerIDDB();
			
            $out = array();
            
            
            $cid_sql = "SELECT d2.tag AS tag, c.name as campaign_name, d.state, d.phone FROM callids.did_lists dl ".
						" JOIN dids d ON dl.id = d.did_list_id AND d.deleted_at IS NULL ".
						" JOIN campaigns c ON dl.id = c.did_list_id and c.deleted_at IS NULL ".
						" JOIN campaign_dialer cd ON c.id = cd.campaign_id ".
						" JOIN dialers d2 ON cd.dialer_id = d2.id and d2.deleted_at IS NULL ".
						" WHERE dl.deleted_at IS NULL ".
						//" AND d2.tag = '".mysqli_real_escape_string($_SESSION['db'], $cluster_row['callerid_tag'])."' ".
			            $tagsql.
						(($campaign != null && $campaign != '')?" AND c.name = '".mysqli_real_escape_string($_SESSION['db'], $campaign)."' ":'').
						(($state != null && $state != '')?" AND d.state = '".mysqli_real_escape_string($_SESSION['db'], $state)."'":'');
						
						
						//echo $cid_sql;
						
			$res = query($cid_sql, 1);
			
            if(mysqli_num_rows($res) <= 0){
            	// FALLBACK TO THE OLD CALLER ID METHODS
            	$cid_sql = "SELECT d2.tag AS tag, c.name as campaign_name, d.state, d.phone FROM callids.did_lists dl ".
							" JOIN dids d ON dl.id = d.did_list_id AND d.deleted_at IS NULL ".
							" JOIN dialers d2 ON dl.dialer_id = d2.id AND d2.deleted_at IS NULL ".
							" JOIN campaign_dialer cd ON d2.id = cd.dialer_id ".
							" JOIN campaigns c ON c.id = cd.campaign_id ".
							" WHERE dl.dialer_id IS NOT NULL ".
							" AND dl.deleted_at IS NULL ".
							//" AND d2.tag = '".mysqli_real_escape_string($_SESSION['db'], $cluster_row['callerid_tag'])."' ".
							
			            	$tagsql.
            	
							(($campaign != null && $campaign != '')?" AND c.name = '".mysqli_real_escape_string($_SESSION['db'], $campaign)."' ":'').
							(($state != null && $state != '')?" AND d.state = '".mysqli_real_escape_string($_SESSION['db'], $state)."'":'').
			            	" ORDER BY dl.dialer_id";
				$res = query($cid_sql, 1);
            }
            
            
            
            
            while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
// print_r($row);

            	$out[$row['phone']] = $row;
            	            	
            }
            
            connectPXDB();
            
//            print_r($out);
            
            
            $code_arr = array();
            $re2 = $_SESSION['dbapi']->ROquery("SELECT id,callerid_tag FROM vici_clusters WHERE `status`='enabled'");
            while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){
            	$code_arr[$r2['callerid_tag']] = $r2['id'];
            }
            
            
            foreach($out as $idx=>$cid_row){
            
            	$phone = $cid_row['phone'];
            	
            	$phone = preg_replace("/[^0-9]/",'', $phone);
            	
            	$phwhere = $where . " AND `outbound_phone_num`='$phone' ";
         	
            	//$phwhere.= " AND `vici_cluster_id`='".$code_arr[$cid_row['tag']]."' ";
            	
            	list($out[$idx]['cnt_total']) = $_SESSION['dbapi']->ROqueryROW("SELECT COUNT(*) FROM `lead_tracking` ".$phwhere." ");
            	
            	if($out[$idx]['cnt_total'] > 0){
            		
            		list($out[$idx]['cnt_answer_machine']) = $_SESSION['dbapi']->ROqueryROW("SELECT COUNT(*) FROM `lead_tracking` ".$phwhere." AND `dispo`='A'");
	            	
            		list($out[$idx]['cnt_contacts']) = $_SESSION['dbapi']->ROqueryROW("SELECT COUNT(*) FROM `lead_tracking` ".$phwhere." AND `dispo` NOT IN('A','DC') ");
	            	
	            	//$out[$phone]['cnt_contacts'] = $out[$phone]['cnt_total'] - $out[$phone]['cnt_no_contacts'];
	            
            	// SKIP THE EXTRA QUERIES, IF THE TOTAL COUNT IS ZERO
            	}else{
            		
            		$out[$idx]['cnt_answer_machine'] = 0;
            		//$out[$phone]['cnt_no_contacts'] = 0;
            		$out[$idx]['cnt_contacts'] = 0;
            	}
            }
            
            
            
            if($only_bad_numbers == true){
            	
            	
	            foreach($out as $idx=>$row){
            	
	            	$ans_percent = ($row['cnt_total'] > 0)?round(  (($row['cnt_answer_machine'] / $row['cnt_total']) * 100), 2) : 0;
	            	
	            	if($ans_percent >= $this->answering_limit_red && $row['cnt_total'] >= $this->total_calls_required){
	            		continue;
	            	}else{
	            		
	            		unset($out[$idx]);
	            	}


//$color_red =  ($ans_percent >= $this->answering_limit_red && $row['cnt_total'] >= $this->total_calls_required)?true:false;
    	        }
            }
            
        /*  
            

            //(($stime && $etime) ? " AND `time_started` BETWEEN '$stime' AND '$etime' " : '') . (($cluster_id > 0) ? " AND vici_cluster_id='$cluster_id' " : "") . $extra_sql;
            
           // $sql = "SELECT DISTINCT(`outbound_phone_num`) FROM `lead_tracking` ".


            
           	$res = $_SESSION['dbapi']->ROquery($sql.$where, 1);

//             $stmicro = $stime * 1000;
//             $etmicro = $etime * 1000;

            
            $cid_array = array();
            
            while ($row = mysqli_fetch_array($res, MYSQLI_ARRAY)) {

            	$phone = $row[0];
            	
            	$out[$phone] = array();
            	
            	
            	list($out[$phone]['cnt_total']) = $_SESSION['dbapi']->ROqueryROW("SELECT COUNT(*) FROM `lead_tracking` ".$where." ");
            	
            	list($out[$phone]['cnt_answer_machine']) = $_SESSION['dbapi']->ROqueryROW("SELECT COUNT(*) FROM `lead_tracking` ".$where." AND `dispo`='A'");
            	
            	list($out[$phone]['cnt_no_contacts']) = $_SESSION['dbapi']->ROqueryROW("SELECT COUNT(*) FROM `lead_tracking` ".$where." AND `dispo` NOT IN('A','DC') ");
            	
            	$out[$phone]['cnt_contacts'] = $out[$phone]['cnt_total'] - $out[$phone]['cnt_no_contacts'];
            }*/

//print_r($out);
            return $out;
        }

        function makeCallerIDCampaignDD($name, $selected, $css, $onchange, $blank_option = 1){
        	
        	
        	connectCallerIDDB();
        	
        	$res = query("SELECT `name` FROM `campaigns` WHERE `deleted_at` IS NULL AND `did_list_id` IS NOT NULL ORDER BY `name` ASC", 1 );
        	
        	$out = '<select name="'.$name.'" id="'.$name.'" ';
        	$out .= ($css)?' class="'.$css.'" ':'';
        	$out .= ($onchange)?' onchange="'.$onchange.'" ':'';
        	$out .= '>';
        	if ($blank_option) {
        		$out .= '<option value="" '.(($selected == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
        	}
        	while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
        		$out .= '<option value="'.htmlentities($row['name']).'" ';
        		$out .= ($selected == $row['name'])?' SELECTED ':'';
        		$out .= '>'.htmlentities($row['name']).'</option>';
        	}
        	$out .= '</select>';
        	
        	connectPXDB();
        	
        	return $out;
        }
        
        
        
        function makeHTMLReport($cluster_id, $campaign = null, $state = null, $stime=0, $etime=0, $user_group = NULL, $only_bad = false) {
        	
        	$data = $this->generateData($cluster_id, $campaign, $state, $stime, $etime, $user_group,$only_bad);
        	
        	return $this->makeHTMLReportWithData($data);
        }
        
        
        function makeHTMLReportWithData($data){

        
        	//generateData($cluster_id, $stime, $etime, $user_group);
//print_r($data);

            if (count($data) <= 0) {
                return NULL;
            }

            // ACTIVATE OUTPUT BUFFERING
            ob_start();
            ob_clean();

            if (is_array($user_group)) {

                $user_group_str = "Group" . ((count($user_group) > 1) ? "s" : "") . ": ";

                $x = 0;
                foreach ($user_group as $grp) {

                    if ($x++ > 0) $user_group_str .= ",";

                    $user_group_str .= $grp;
                }

            } else {
                $user_group_str = $user_group;
            }

            ?>
        
            <table border="0" width="100%">
                <tr>
                    <td style="border-bottom:1px solid #000;font-size:18px;font-weight:bold">

                        <br/>

                       Caller ID Report - <?= date("m/d/Y", $stime) ?> - <?= htmlentities(($user_group == NULL || $user_group[0] == '') ? "All Groups" : "Selected Group" . ((count($user_group) > 1) ? "s" : " : " . ((is_array($user_group)) ? $user_group[0] : $user_group))) ?>

                    </td>
                </tr>
                <tr>
                    <th height="25" align="left" style="font-size:14px;font-weight:bold"><?

                            echo $user_group_str . '<br />';

                            echo '<i>Generated on: ' . date("g:ia m/d/Y") . '</i>';

                        ?></th>
                </tr>
                <tr>
                    <td>
                        <table id="callerid_report_table" border="0" width="900">
                            <thead>
                            <tr>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="left">Outbound CallerID</th>
		<?/**	<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="center">Cluster</th>**/?>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="center">CallerID Campaign</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="center">CallerID State</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Total Calls</th>
								<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right"># Answering</th>
								<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Answering %</th>
								<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Contacts</th>
								<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Contact %</th>
                            </tr>
                            </thead>
                        <tbody>
                        <?

                        $running_calls = 0;
                        $running_ans = 0;
                        $running_contact = 0;
                             // print_r($data);
                              
                                foreach ($data as $idx=>$row) {
                                	
                                	$phone = $row['phone'];
/**	cnt_total
            		$out[$phone]['cnt_answer_machine'] = 0;
            		$out[$phone]['cnt_no_contacts'] = 0;
            		$out[$phone]['cnt_contacts'] = 0;*/
                                	
                                	
                                	$ans_percent = ($row['cnt_total'] > 0)?round(  (($row['cnt_answer_machine'] / $row['cnt_total']) * 100), 2) : 0;
                                	
                                	$con_percent = ($row['cnt_total'] > 0)?round(  (($row['cnt_contacts'] / $row['cnt_total']) * 100), 2) : 0;
                                	
                                	
                                	$color_red =  ($ans_percent >= $this->answering_limit_red && $row['cnt_total'] >= $this->total_calls_required)?true:false;

								?><tr>
                                    
                                    <td style="border-right:1px dotted #CCC;padding-right:3px"><?=$phone?></td>
									<?/**<td style="border-right:1px dotted #CCC;padding-right:3px" align="center"><?=$row['tag']?></td>**/?>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="center"><?=$row['campaign_name']?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="center"><?=$row['state']?></td>
                                    
                                    
                                    
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?=number_format($row['cnt_total'])?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?=number_format($row['cnt_answer_machine'])?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px;<?=($color_red)?'background-color:#FF0000;':''?>" align="right"><?=$ans_percent?>%</td>                                    
                                
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?=number_format($row['cnt_contacts'])?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?=$con_percent?>%</td>
                                </tr><?
	                                $running_calls += $row['cnt_total'];
	                                $running_ans += $row['cnt_answer_machine'];
	                                $running_contact += $row['cnt_contacts'];
	                                
	                                
                                    $tcount++;
                                }

                            ?></tbody><?

                               // $total_close_percent = (($running_total_calls <= 0) ? 0 : number_format(round((($running_total_sales) / ($running_total_calls)) * 100, 2), 2));

                              
                                // TOTALS ROW
                                
                            if($running_calls > 0){
	                            $t_ans_percent = round(  (($running_ans / $running_calls) * 100), 2);
                            
    	                        $t_con_percent = round(  (($running_contact / $running_calls) * 100), 2);
                            }else{
                            	$t_ans_percent = $t_con_percent = 0;
                            }
                            ?>
                            <tfoot>
                            <tr>
                            	<th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="left" colspan="3">Totals:</th>
                                
                                <th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="right"><?=number_format($running_calls)?></th>
                               	<th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="right"><?=number_format($running_ans)?></th>
                               	<th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="right"><?=$t_ans_percent?>%</th>
                                <th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="right"><?=number_format($running_contact)?></th>
                                <th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="right"><?=$t_con_percent?>%</th>
                                
                            </tr>
                            </tfoot>
                        </table>
                    </td>
                </tr>


            </table><?

            // GRAB DATA FROM BUFFER
            $data = ob_get_contents();

            // TURN OFF OUTPUT BUFFERING, WITHOUT OUTPUTTING
            ob_end_clean();

            // CONNECT BACK TO PX BEFORE LEAVING
            connectPXDB();

            // RETURN HTML
            if ($tcount > 0){
            	
            	return $data;
            	
            }else{
                return NULL;
            }

        }
        
        
        function writeCSVReportWithData($fh, $data){
        	
        	if($fh == null)return NULL;
        	
        	//generateData($cluster_id, $stime, $etime, $user_group);
        	
        	if (count($data) <= 0) {
        		return NULL;
        	}
        	
        	// ACTIVATE OUTPUT BUFFERING
        	ob_start();
        	ob_clean();
        	
//         	if (is_array($user_group)) {
        		
//         		$user_group_str = "Group" . ((count($user_group) > 1) ? "s" : "") . ": ";
        		
//         		$x = 0;
//         		foreach ($user_group as $grp) {
        			
//         			if ($x++ > 0) $user_group_str .= ",";
        			
//         			$user_group_str .= $grp;
//         		}
        		
//         	} else {
//         		$user_group_str = $user_group;
//         	}
        	
        	
        	$headers = array(
        	
        		"Outbound CallerID",
        		"CallerID Campaign",
        		"CallerID State",
        		"Total Calls",
        		"# Answering",
        		"Answering %",
        		"Contacts",
        		"Contact %",
        	);
        	
        	


            $running_calls = 0;
            $running_ans = 0;
            $running_contact = 0;

            
            $out = array();
            
            $x=0;
            $out[$x++] = $headers;
            
            
            foreach ($data as $idx=>$row) {
           	
				$phone = $row['phone'];
/**	cnt_total
            		$out[$phone]['cnt_answer_machine'] = 0;
            		$out[$phone]['cnt_no_contacts'] = 0;
            		$out[$phone]['cnt_contacts'] = 0;*/
                                	
                                	
				$ans_percent = ($row['cnt_total'] > 0)?round(  (($row['cnt_answer_machine'] / $row['cnt_total']) * 100), 2) : 0;
				                                	
				$con_percent = ($row['cnt_total'] > 0)?round(  (($row['cnt_contacts'] / $row['cnt_total']) * 100), 2) : 0;
				                                	
				                                	
				$color_red =  ($ans_percent >= $this->answering_limit_red && $row['cnt_total'] >= $this->total_calls_required)?true:false;

				
				$out[$x++] = array(
					$phone,
					$row['campaign_name'],
					$row['state'],
					number_format($row['cnt_total']),
					number_format($row['cnt_answer_machine']),
					$ans_percent."%",
					number_format($row['cnt_contacts']),
					$con_percent."%",
				);
			
				$running_calls += $row['cnt_total'];
				$running_ans += $row['cnt_answer_machine'];
				$running_contact += $row['cnt_contacts'];
					                                
					                                
				$tcount++;
				
			} // END FOREACH(rows)

                              
                            // TOTALS ROW
                                
			if($running_calls > 0){
				$t_ans_percent = round(  (($running_ans / $running_calls) * 100), 2);
				$t_con_percent = round(  (($running_contact / $running_calls) * 100), 2);
			}else{
				$t_ans_percent = $t_con_percent = 0;
			}
			
			$out[$x++] = array(); // TO MAKE A BLANK LINE
			$out[$x++] = array(
						"Totals",
						"",
						"",
						number_format($running_calls),
						number_format($running_ans),
						$t_ans_percent.'%',
						number_format($running_contact),
						$t_con_percent.'%',
					
					);
			
			

            // CONNECT BACK TO PX BEFORE LEAVING
            connectPXDB();

            // RETURN HTML
            if ($tcount <= 0) return NULL;
            

            foreach($out as $linearr){
            	
            	fputcsv($fh, $linearr);
            }
            
			return $tcount;
        }
        
        
        
        
        function makeReport() {

            if (isset($_REQUEST['generate_callerid_report'])) {
                if ($_REQUEST['timeFilter']) {
                    $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " " . $_REQUEST['strt_time_hour'] . ":" . $_REQUEST['strt_time_min'] . $_REQUEST['strt_time_timemode']);
                    $timestamp2 = strtotime($_REQUEST['end_date_month'] . "/" . $_REQUEST['end_date_day'] . "/" . $_REQUEST['end_date_year'] . " " . $_REQUEST['end_time_hour'] . ":" . $_REQUEST['end_time_min'] . $_REQUEST['end_time_timemode']);
                } else {
                    $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " 00:00:00");
                    $timestamp2 = strtotime($_REQUEST['end_date_month'] . "/" . $_REQUEST['end_date_day'] . "/" . $_REQUEST['end_date_year'] . " 23:59:59");
                }
            } else {
            	$timestamp = mktime(0, 0, 0) - (86400 * $this->default_days);
                $timestamp2 = mktime(23, 59, 59) - 86400;
            }

            $cluster_id = intval($_REQUEST['s_cluster_id']);
            $cluster_id = (isset($_REQUEST['s_cluster_id'])) ? $cluster_id : 3; // DEFAULT TO TAPS CLUSTER

            $user_group = $_REQUEST['s_user_group'];

            $campaign = trim($_REQUEST['s_campaign']);
            $state = trim($_REQUEST['s_state']);
            
            
            $only_bad = (isset($_REQUEST['s_only_bad']))?true:false;
            
            ?><table border="0" width="100%"><?

            //if(!isset($_REQUEST['no_script'])){

            ?>
            <script>

                function toggleDateSearchMode(way) {

                    if (way == 'daterange') {
                        $('#end_date_row').show();
                    } else {
                        $('#end_date_row').hide();
                    }
                }

            </script>
            <script>
                $(function () {
                    let timeFields = $('#startTimeFilter, #endTimeFilter');
                    let retainTime = '<? echo $_REQUEST['timeFilter'] === "on"; ?>';
                    if (retainTime) {
                        $(timeFields).show();
                        $('#timeFilter').prop('checked', true);
                    } else {
                        $(timeFields).hide();
                        $('#timeFilter').prop('checked', false);
                    }
                    $('#timeFilter').on('click', function () {
                        $(timeFields).toggle();
                    });
                });



                function checkCIDReportForm(frm){

					if(!frm.s_campaign.value){
						return recheck("ERROR: Please specify a campaign.",frm.s_campaign);
					}


					//if(frm.cluster_id.value == ''){
					//	if(!confirm('This report will take a VERY long time, and be hard on the servers. Are you sure you want to run this during production hours?', frm.cluster_id)){
					//		return false;
					//	}
					//}
                	
					return true;
                }
            </script>
            <?

            if (!isset($_REQUEST['no_nav'])) {

                ?>
                <tr>
                    <td height="40" class="ui-widget-header pad_left">CallerID Stats Report - Today/Current</td>
                </tr>
                <tr>
                <td>
                    <form id="calleridfrm" method="POST" action="<?= stripurl() ?>" onsubmit="if(checkCIDReportForm(this)){return genReport(this,'callstats');}else{return false;}">

                        <input type="hidden" name="generate_callerid_report">

                        <table border="0">

						<tr>
							<th class="big bl" align="center" colspan="2" height="25">Caller ID Settings</th>
						</tr>
						<tr>
							<th>Campaign:</th>
							<td><?
							
								echo $this->makeCallerIDCampaignDD('s_campaign', $campaign, '', "", 0);
								
							?></td>
						</tr>
						
						<tr>
							<th>State:</th>
							<td><input type="text" name="s_state" id="s_state" size="3" value="<?=htmlentities($state)?>" /><?
							
								//echo makeClusterDD('s_cluster_id', $cluster_id, "", '', 0);
							
							?></td>
						</tr>
						
						<tr>
							<th>Cluster</th>
							<td><?
							
								echo makeClusterDD('s_cluster_id', $cluster_id, "", '', 1);
							
							?></td>
						</tr>
						
						
						
						<tr>
							<th class="big bl" align="center" colspan="2" height="25">Report Settings</th>
						</tr>
                            <tr>
                                <th>User Group</th>
                                <td><?

                                        echo makeViciUserGroupDD('s_user_group[]', $_REQUEST['s_user_group'], "", '', 8, 1);

                                    ?></td>
                            </tr>
                           
                            
                            <tr>
                                <th>Date Start:</th>
                                <td>
                                    <?php echo makeTimebar("strt_date_", 1, NULL, false, $timestamp); ?>
                                    <div style="float:right; padding-left:6px;" id="startTimeFilter"> <?php echo makeTimebar("strt_time_", 2, NULL, false, $timestamp); ?></div>
                                </td>
                            </tr>
                            <tr>
                                <th>Date End:</th>
                                <td>
                                    <?php echo makeTimebar("end_date_", 1, NULL, false, $timestamp2); ?>
                                    <div style="float:right; padding-left:6px;" id="endTimeFilter"> <?php echo makeTimebar("end_time_", 2, NULL, false, $timestamp2); ?></div>
                                </td>
                            </tr>
                            
                            <tr>
                            	<td colspan="2" align="center" height="30">
                            	                            	
                            		<span title="Only show numbers with <?=$this->answering_limit_red?>% Answering Machines or higher, and at least <?=$this->total_calls_required?> calls.">
                            			<input type="checkbox" name="s_only_bad" value="1" <?=($_REQUEST['s_only_bad'])?' CHECKED ':''?> /> Only show BAD numbers
                            		</span>
                            	
                            	</td>
                            </tr>
                            <?/**<tr>
                                <th>Use Time?</th>
                                <td>
                                    <input type="checkbox" name="timeFilter" id="timeFilter">
                                </td>
                            </tr>**/
                            
                            ?><tr>
                                <td colspan="2" align="right">

                                    <span id="callstats_loading_plx_wait_span" class="nod"><img src="images/ajax-loader.gif" border="0"/> Loading, Please wait...</span>

                                    <span id="callstats_submit_report_button">

										<?
                                /**<input type="submit" name="no_script" value="Generate Printable" onclick="this.form.target='_blank';">**/ ?>
							<input type="button" value="Generate PRINTABLE" onclick="genReport(getEl('calleridfrm'),'callstats',1)">

							&nbsp;&nbsp;&nbsp;&nbsp;

							<input type="submit" value="Generate Now" onclick="this.form.target='';">
			
									</span>
                                </td>
						</tr>

                        </table>
                </td>
                </tr>
                </form>
                <?

            } else {

                ?>
                <meta charset="UTF-8">
                <meta name="google" content="notranslate">
                <meta http-equiv="Content-Language" content="en"><?
            }

            //}

            if (isset($_REQUEST['generate_callerid_report'])) {

                ?>
                <tr>
                    <td><?

                            
                    $report = $this->makeHTMLReport($cluster_id, $campaign, $state, $timestamp, $timestamp2, $_REQUEST['s_user_group'],$only_bad);
                    //$this->makeHTMLReport($timestamp, $timestamp2, $cluster_id, $_REQUEST['s_user_group']);

                            if (!$report) {

                                echo "No data";

                            } else {
                                echo $report;
                            }

                        ?>
                    </td>
                </tr>
            <?

                if (!isset($_REQUEST['no_nav'])) {
                    ?>
                    <script>
                        $(document).ready(function () {

                            $('#callerid_report_table').DataTable({

                           	 	"order": [[ 5, "desc" ]],
                            	 
                                "lengthMenu": [[-1, 20, 50, 100, 500], ["All", 20, 50, 100, 500]]


                            });


                        });
                    </script><?
                }

            } // END IF GENERATE REPORT

            ?></table><?

        }

        
        
        
        
        
        
        
        
        
        
        
        /**
         * Send Report emails - Reads the report email table and determines what reports need to go out
         *
         *
         *
         */
        public function sendReportEmail($email_to, $only_bad = true){
        	        	
        	$curtime = time();
        	
        	// INIT VARIABLES
        	$stime= $etime = 0;
        	$campaign_code = null;

        	
        	$stime = mktime(0, 0, 0) - (86400 * $this->default_days);
        	$etime = mktime(23, 59, 59) - 86400;
        	
        	
        	connectPXDB();

        	
        	echo date("H:i:s m/d/Y")." - Starting callerid_stats_report.sendReportEmail($email_to, $only_bad) - Date Range ".date("m/d/Y",$stime)." thru ".date("m/d/Y",$etime)." ...\n";
        	
        	$sent_report_total = 0;

    
        	$data = $this->generateData(0, null, null, $stime, $etime, null, $only_bad);
        	
        	//$this->makeHTMLReport(0, $campaign = null, $state = null, $stime=0, $etime=0, $user_group = NULL, $only_bad = false);
        	

        	
        	$html = $this->makeHTMLReportWithData($data);
        		
        	if ($html == null) {
        		echo date("H:i:s m/d/Y")." - NOTICE: Skipping sending report, no records found\n";
        		return;
        	}
        	
        	$filename = "callerid-bad-phones_".date("m-d-Y",$stime).'-'.date("m-d-Y",$etime).".csv";
        	
			$tmpfname = tempnam(sys_get_temp_dir(), 'callerid-bad-phones');
			
			$fh = fopen($tmpfname, "w");

        	$csvcnt = $this->writeCSVReportWithData($fh, $data);

        	fclose($fh);
        	
        	
        	$csvdata = file_get_contents($tmpfname);
        	
	        $textdata = "Report is attached - $csvcnt Phone #'s. (View email as HTML/check attachments if you can't see it).";
	
			// REPORT HAS BEEN GENERATED, DO THE EMAIL SHIT HERE
				
			if( ! trim ( $html ) ) {
				echo date ( "H:i:s m/d/Y" ) . " - ERROR: no html was generated to email, skipping!\n";
				return;
			}
	
			// BUILD HTML EMAIL
			$subject = "CID - Bad Phone # List - " . date ("m/d/Y", $stime).' thru '.date("m/d/Y", $etime);
	
			$headers = array (
					"From" => "ATC Reporting <support@advancedtci.com>",
					"Subject" => $subject,
					"X-Mailer" => "ATC Reporting System",
					"Reply-To" => "ATC Reporting <support@advancedtci.com>"
			);
	
			$mime = new Mail_mime ( array (
					'eol' => "\n"
			) );
	
			// SET TEXT AND HTML CONTENT BODIES
			$mime->setTXTBody ( $textdata, false );
			$mime->setHTMLBody ( $html, false );
	
			// ATTACH HTML REPORT AS FILE AS WELL
			$mime->addAttachment ( $csvdata, "text/csv", $filename, false, "quoted-printable", "attachment" );
	
			// BUILD THE EMAIL SHIT
			$mail_body = $mime->get ();
			$mail_header = $mime->headers ( $headers );
	
			$mail = & Mail::factory ( 'mail' );
	
			// SEND IT
			if( $mail->send ( $email_to, $mail_header, $mail_body ) != true ) {
				echo date ( "H:i:s m/d/Y" ) . " - ERROR: Mail::send() call failed sending to " . $email_to;
			} else {
	
				$sent_report_total ++;
	
				echo date ( "H:i:s m/d/Y" ) . " - Successfully emailed " . $email_to . " - " . $subject . "\n";
			}
        		
        	
        	
        	echo date("H:i:s m/d/Y")." - Finished sendReportEmail()\n";
        	
        	
        	return $sent_report_total;
        }
        

        
        
        
    } // END OF CLASS
