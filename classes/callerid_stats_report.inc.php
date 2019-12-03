<? /***************************************************************
 * CallerID Stats Report
 * Written By: Jonathan Will
 ***************************************************************/

    $_SESSION['callerid_stats_report'] = new CallerIDStatsReport;

    class CallerIDStatsReport {


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

        function generateData($cluster_id, $campaign = null, $state = null, $stime=0, $etime=0, $user_group = NULL) {

            $cluster_id = intval($cluster_id);
            $stime = intval($stime);
            $etime = intval($etime);

  
            if (!$cluster_id) { //|| !$call_group
                return NULL;
            }
            
            $cluster_row = getClusterRow($cluster_id);
            if(!$cluster_row){
            	return NULL;
            }
            
            

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
            
            
            $cid_sql = "SELECT d2.tag, c.name as campaign_name, d.state, d.phone FROM callids.did_lists dl ".
						" JOIN dids d ON dl.id = d.did_list_id AND d.deleted_at IS NULL ".
						" JOIN campaigns c ON dl.id = c.did_list_id and c.deleted_at IS NULL ".
						" JOIN campaign_dialer cd ON c.id = cd.campaign_id ".
						" JOIN dialers d2 ON cd.dialer_id = d2.id and d2.deleted_at IS NULL ".
						" WHERE dl.deleted_at IS NULL ".
						" AND d2.tag = '".mysqli_real_escape_string($_SESSION['db'], $cluster_row['callerid_tag'])."' ".
						(($campaign != null && $campaign != '')?" AND c.name = '".mysqli_real_escape_string($_SESSION['db'], $campaign)."' ":'').
						(($state != null && $state != '')?" AND d.state = '".mysqli_real_escape_string($_SESSION['db'], $state)."'":'');
       
			$res = query($cid_sql, 1);
			
            if(mysqli_num_rows($res) <= 0){
            	// FALLBACK TO THE OLD CALLER ID METHODS
            	$cid_sql = "SELECT d2.tag, c.name as campaign_name, d.state, d.phone FROM callids.did_lists dl ".
							" JOIN dids d ON dl.id = d.did_list_id AND d.deleted_at IS NULL ".
							" JOIN dialers d2 ON dl.dialer_id = d2.id AND d2.deleted_at IS NULL ".
							" JOIN campaign_dialer cd ON d2.id = cd.dialer_id ".
							" JOIN campaigns c ON c.id = cd.campaign_id ".
							" WHERE dl.dialer_id IS NOT NULL ".
							" AND dl.deleted_at IS NULL ".
							" AND d2.tag = '".mysqli_real_escape_string($_SESSION['db'], $cluster_row['callerid_tag'])."' ".
							(($campaign != null && $campaign != '')?" AND c.name = '".mysqli_real_escape_string($_SESSION['db'], $campaign)."' ":'').
							(($state != null && $state != '')?" AND d.state = '".mysqli_real_escape_string($_SESSION['db'], $state)."'":'').
			            	" ORDER BY dl.dialer_id";
				$res = query($cid_sql, 1);
            }
            
            
            
            
            while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {

            	$out[$row['phone']] = $row;
            	            	
            }
            
            connectPXDB();
            
            
            foreach($out as $phone=>$cid_row){
            
            	$phone = preg_replace("/[^0-9]/",'', $phone);
            	
            	$phwhere = $where . " AND `outbound_phone_num`='$phone' ";
            	
            	list($out[$phone]['cnt_total']) = $_SESSION['dbapi']->ROqueryROW("SELECT COUNT(*) FROM `lead_tracking` ".$phwhere." ");
            	
            	if($out[$phone]['cnt_total'] > 0){
            		
	            	list($out[$phone]['cnt_answer_machine']) = $_SESSION['dbapi']->ROqueryROW("SELECT COUNT(*) FROM `lead_tracking` ".$phwhere." AND `dispo`='A'");
	            	
	            	list($out[$phone]['cnt_contacts']) = $_SESSION['dbapi']->ROqueryROW("SELECT COUNT(*) FROM `lead_tracking` ".$phwhere." AND `dispo` NOT IN('A','DC') ");
	            	
	            	//$out[$phone]['cnt_contacts'] = $out[$phone]['cnt_total'] - $out[$phone]['cnt_no_contacts'];
	            
            	// SKIP THE EXTRA QUERIES, IF THE TOTAL COUNT IS ZERO
            	}else{
            		
            		$out[$phone]['cnt_answer_machine'] = 0;
            		//$out[$phone]['cnt_no_contacts'] = 0;
            		$out[$phone]['cnt_contacts'] = 0;
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
        
        function makeHTMLReport($cluster_id, $campaign = null, $state = null, $stime=0, $etime=0, $user_group = NULL) {

        	$data = $this->generateData($cluster_id, $campaign, $state, $stime, $etime, $user_group);
        	//generateData($cluster_id, $stime, $etime, $user_group);

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
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="center">CallerID Campaign</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="center">CallerID State</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Total Calls</th>
								<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right"># Answering</th>
								<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Contacts</th>
                            </tr>
                            </thead>
                        <tbody>
                        <?

                        $running_calls = 0;
                        $running_ans = 0;
                        $running_contact = 0;
                              
                                foreach ($data as $phone=>$row) {
/**	cnt_total
            		$out[$phone]['cnt_answer_machine'] = 0;
            		$out[$phone]['cnt_no_contacts'] = 0;
            		$out[$phone]['cnt_contacts'] = 0;*/
                                	
                                	
                                	$ans_percent = ($row['cnt_total'] > 0)?round(  (($row['cnt_answer_machine'] / $row['cnt_total']) * 100), 2) : 0;
                                	
                                	$con_percent = ($row['cnt_total'] > 0)?round(  (($row['cnt_contacts'] / $row['cnt_total']) * 100), 2) : 0;
								?><tr>
                                    
                                    <td style="border-right:1px dotted #CCC;padding-right:3px"><?=$phone?></td>

                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="center"><?=$row['campaign_name']?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="center"><?=$row['state']?></td>
                                    
                                    
                                    
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?=number_format($row['cnt_total'])?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?=number_format($row['cnt_answer_machine'])?>(<?=$ans_percent?>%)</td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?=number_format($row['cnt_contacts'])?> (<?=$con_percent?>%)</td>
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
                               	<th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="right"><?=number_format($running_ans)?> (<?=$t_ans_percent?>%)</th>
                                <th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="right"><?=number_format($running_contact)?> (<?=$t_con_percent?>%)</th>
                                
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
            if ($tcount > 0) return $data; else
                return NULL;

        }

        function makeReport() {

            /*if (isset($_REQUEST['generate_callerid_report'])) {
                if ($_REQUEST['timeFilter']) {
                    $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " " . $_REQUEST['strt_time_hour'] . ":" . $_REQUEST['strt_time_min'] . $_REQUEST['strt_time_timemode']);
                    $timestamp2 = strtotime($_REQUEST['end_date_month'] . "/" . $_REQUEST['end_date_day'] . "/" . $_REQUEST['end_date_year'] . " " . $_REQUEST['end_time_hour'] . ":" . $_REQUEST['end_time_min'] . $_REQUEST['end_time_timemode']);
                } else {
                    $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " 00:00:00");
                    $timestamp2 = strtotime($_REQUEST['end_date_month'] . "/" . $_REQUEST['end_date_day'] . "/" . $_REQUEST['end_date_year'] . " 23:59:59");
                }
            } else {*/
                $timestamp = mktime(0, 0, 0);
                $timestamp2 = mktime(23, 59, 59);
           // }

            $cluster_id = intval($_REQUEST['s_cluster_id']);
            $cluster_id = ($cluster_id) ? $cluster_id : 3; // DEFAULT TO TAPS CLUSTER

            $user_group = $_REQUEST['s_user_group'];

            $campaign = trim($_REQUEST['s_campaign']);
            $state = trim($_REQUEST['s_state']);
            
            
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
               /* $(function () {
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
                });*/
            </script>
            <?

            if (!isset($_REQUEST['no_nav'])) {

                ?>
                <tr>
                    <td height="40" class="ui-widget-header pad_left">CallerID Stats Report - Today/Current</td>
                </tr>
                <tr>
                <td>
                    <form id="calleridfrm" method="POST" action="<?= stripurl() ?>" onsubmit="return genReport(this,'callstats')">

                        <input type="hidden" name="generate_callerid_report">

                        <table border="0">

						<tr>
							<th class="bl" align="center" colspan="2">Caller ID Settings</th>
						</tr>
						
						<tr>
							<th>Cluster</th>
							<td><?
							
								echo makeClusterDD('s_cluster_id', $cluster_id, "", '', 0);
							
							?></td>
						</tr>
						
						<tr>
							<th>State:</th>
							<td><input type="text" name="s_state" id="s_state" size="3" value="<?=htmlentities($state)?>" /><?
							
								//echo makeClusterDD('s_cluster_id', $cluster_id, "", '', 0);
							
							?></td>
						</tr>
						<tr>
							<th>Campaign:</th>
							<td><?
							
								echo $this->makeCallerIDCampaignDD('s_campaign', $campaign, '', "", 1);
								
							?></td>
						</tr>

						<tr>
							<th class="bl" align="center" colspan="2">Report Settings</th>
						</tr>
                            <tr>
                                <th>User Group</th>
                                <td><?

                                        echo makeViciUserGroupDD('s_user_group[]', $_REQUEST['s_user_group'], "", '', 8, 1);

                                    ?></td>
                            </tr><?
                            
                            /*
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

                            
                    $report = $this->makeHTMLReport($cluster_id, $campaign, $state, $timestamp, $timestamp2, $_REQUEST['s_user_group']);
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

                                "lengthMenu": [[-1, 20, 50, 100, 500], ["All", 20, 50, 100, 500]]


                            });


                        });
                    </script><?
                }

            } // END IF GENERATE REPORT

            ?></table><?

        }

    } // END OF CLASS
