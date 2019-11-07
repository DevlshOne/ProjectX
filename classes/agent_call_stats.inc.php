<? /** @noinspection ALL */
    /***************************************************************
     * Agent Call Stat Report (AKA VERIFIER CALL STATS REPORT)
     * Written By: Jonathan Will
     ***************************************************************/

    $_SESSION['agent_call_stats'] = new AgentCallStats;

    class AgentCallStats {

        var $close_percent_limit = 76;

        var $dead_time_limit = 120;

        var $time_limit = 27000; // 7.5hrs aka 7:30

        var $talk_lower_limit = 70;
        var $talk_upper_limit = 80;

        var $pause_limit = 1800;

        function AgentCallStats() {

//		include_once("site_config.php");
//		include_once("dbapi/dbapi.inc.php");
//		include_once("db.inc.php");
//		include_once('utils/db_utils.php');
            ## REQURES DB CONNECTION!
            $this->handlePOST();
        }

        function handlePOST() {

        }

        function handleFLOW() {

            if (!checkAccess('agent_call_stats')) {

                accessDenied("Agent Call Stats");

                return;

            } else {

                $this->makeReport();

            }

        }

        function generateData($cluster_id, $stime, $etime, $call_group = NULL, $use_archive_by_default = false, $ignore_arr = NULL, $source_cluster_id = 0, $ignore_source_cluster_id = 0, $source_user_group = NULL) {

            $cluster_id = intval($cluster_id);
            $stime = intval($stime);
            $etime = intval($etime);

            $source_cluster_id = intval($source_cluster_id);
            $ignore_source_cluster_id = intval($ignore_source_cluster_id);

            if (!$cluster_id) { //|| !$call_group
                return NULL;
            }

            $extra_sql = '';

            $user_group_sql = ''; // USED FOR THE VICI PART OF THE QUERY

            if (is_array($call_group)) {
//

                if (php_sapi_name() != "cli") {

                    if ($call_group[0] == '' && (($_SESSION['user']['priv'] < 5) && ($_SESSION['user']['allow_all_offices'] != 'yes'))) {

                        $call_group = $_SESSION['assigned_groups'];

                    }
                }

//print_r($call_group);

                $x = 0;

                foreach ($call_group as $group) {

                    if (trim($group) == '') continue;

                    if ((php_sapi_name() != "cli") && (($_SESSION['user']['priv'] < 5) && ($_SESSION['user']['allow_all_offices'] != 'yes')) && is_array($_SESSION['assigned_groups']) && !in_array($group, $_SESSION['assigned_groups'], false)) {
                        //echo "skipping $group";
                        continue;
                    }

                    if ($x == 0) $user_group_sql = " AND ("; else        $user_group_sql .= " OR ";

                    if ($x++ == 0) $extra_sql = " AND ("; else        $extra_sql .= " OR ";

                    $user_group_sql .= " `user_group`='" . mysqli_real_escape_string($_SESSION['db'], $group) . "' ";

                    $extra_sql .= " `call_group`='" . mysqli_real_escape_string($_SESSION['db'], $group) . "' ";
                }

                if ($x > 0) {
                    $extra_sql .= ")";

                    $user_group_sql .= ")";
                }

            } else if ($call_group) {

                if ((php_sapi_name() != "cli") && is_array($_SESSION['assigned_groups']) && !in_array($call_group, $_SESSION['assigned_groups'], false)) {
                    $call_group = '';
                }

                $extra_sql = " AND `call_group`='" . mysqli_real_escape_string($_SESSION['db'], $call_group) . "' ";
                $user_group_sql = " AND `user_group`='" . mysqli_real_escape_string($_SESSION['db'], $call_group) . "' ";

            } else {

                if ((php_sapi_name() != "cli") && ($_SESSION['user']['priv'] < 5) && ($_SESSION['user']['allow_all_offices'] != 'yes')) {

                    $x = 0;

                    foreach ($_SESSION['assigned_groups'] as $group) {

                        if ($x == 0) $user_group_sql = " AND ("; else        $user_group_sql .= " OR ";

                        if ($x++ == 0) $extra_sql = " AND ("; else        $extra_sql .= " OR ";

                        $user_group_sql .= " `user_group`='" . mysqli_real_escape_string($_SESSION['db'], $group) . "' ";

                        $extra_sql .= " `call_group`='" . mysqli_real_escape_string($_SESSION['db'], $group) . "' ";
                    }

                    if ($x > 0) {
                        $extra_sql .= ")";

                        $user_group_sql .= ")";
                    }

                }

            }

            $source_user_group_sql = "";

            if ($source_user_group != NULL && count($source_user_group) > 0) {

                $x = 0;

                //print_r($source_user_group);

                if (count($source_user_group) >= 1 && trim($source_user_group[0]) == '') {

                } else {

                    foreach ($source_user_group as $group) {

                        //
                        //					if($x == 0)$user_group_sql = " AND (";
                        //					else		$user_group_sql .= " OR ";
                        //
                        // IF ITS NOT RUNNING FROM CLI/COMMAND LINE (NIGHTLY REPORTS)
                        // AND IF USER IS ASSIGNED GROUPS
                        if ((php_sapi_name() != "cli") && ($_SESSION['user']['priv'] < 5) && ($_SESSION['user']['allow_all_offices'] != 'yes')) {

                            $found = false;
                            foreach ($_SESSION['assigned_groups'] as $tgrp) {

                                if ($group == $tgrp) {
                                    $found = true;
                                    break;
                                }
                            }

                            if (!$found) {
                                echo "Not assigned to see group '" . htmlentities($group) . "'<br />\n";
                                continue;
                            }

                        }

                        if ($x++ == 0) $source_user_group_sql = " AND ("; else        $source_user_group_sql .= " OR ";

                        $source_user_group_sql .= " `call_group`='" . mysqli_real_escape_string($_SESSION['db'], $group) . "' ";

                        //					$user_group_sql .= " `user_group`='".mysqli_real_escape_string($_SESSION['db'],$group)."' ";
                        //
                        //					$extra_sql .= " `call_group`='".mysqli_real_escape_string($_SESSION['db'],$group)."' ";
                    }

                    if ($x > 0) {
                        $source_user_group_sql .= ")";
                    }
                }
            }

            connectPXDB();

            $sql = "SELECT * FROM `activity_log` WHERE 1 " . (($stime && $etime) ? " AND `time_started` BETWEEN '$stime' AND '$etime' " : '') . (($cluster_id > 0) ? " AND vici_cluster_id='$cluster_id' " : "") . $extra_sql;

//		$sql = "SELECT * FROM `activity_log` WHERE `account_id`='".$_SESSION['account']['id']."' ".
//						(($stime && $etime)?" AND `time_started` BETWEEN '$stime' AND '$etime' ":'').
//						(($cluster_id > 0)?" AND vici_cluster_id='$cluster_id' ":"").
//						$extra_sql;

            //echo $sql;exit;

            // GET A LIST OF TEH AGENTS
            // ACTIVITY LOG: THE AGENTS THAT ARE WORKING TODAY
            $res = $_SESSION['dbapi']->ROquery($sql

                //(($call_group)?" AND call_group='".mysql_real_escape_string($call_group)."' ":"")
                , 1);

            $stmicro = $stime * 1000;
            $etmicro = $etime * 1000;

            $agent_array = array();
            while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {

//print_r($row);
//echo '<br />';

                $username = strtoupper($row['username']);

                if ($ignore_arr != NULL && is_array($ignore_arr)) {

                    // SKIPP THEM!!!!
                    if (in_array($username, $ignore_arr)) {
                        continue;
                    }

                }

                // USER ON THE STACK ALREADY
                if (isset($agent_array[$username]) && $agent_array[$username]) {

                    //echo "Agent $username found in stack.<br />\n";

                    $agent_array[$username]['activity_time'] += $row['activity_time'];
                    $agent_array[$username]['paid_time'] += $row['paid_time'];

                    $agent_array[$username]['seconds_INCALL'] += $row['seconds_INCALL'];
                    $agent_array[$username]['seconds_READY'] += $row['seconds_READY'];
                    $agent_array[$username]['seconds_QUEUE'] += $row['seconds_QUEUE'];
                    $agent_array[$username]['seconds_PAUSED'] += $row['seconds_PAUSED'];

                    continue;
                }

                $agent_array[$username] = $row;

                // GET TOTAL SALES COUNTS FROM PX
                $sql = "SELECT COUNT(`id`) FROM `sales` " . " WHERE `sale_time` BETWEEN '$stime' AND '$etime' " . //	" AND `account_id`='".$_SESSION['account']['id']."' ".
                    " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "' OR verifier_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "')" . " AND (agent_cluster_id='$cluster_id' OR verifier_cluster_id='$cluster_id') " .

                    // EXCLUDE ANYTHING ROUSTING RELATED
                    " AND `is_paid` != 'roustedcc' " .

                    (($source_cluster_id > 0) ? " AND agent_cluster_id='$source_cluster_id' " : '') . (($ignore_source_cluster_id > 0) ? " AND agent_cluster_id != '$ignore_source_cluster_id' " : '') .

                    $source_user_group_sql .

                    "";//" AND `call_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."'";
//			echo $sql."\n";
                list($cnt) = $_SESSION['dbapi']->ROqueryROW($sql);
                list($paid_sales_cnt) = $_SESSION['dbapi']->ROqueryROW($sql . " AND `is_paid`='yes' ");

                // GET TOTAL SALES AMOUNTS FOR PAID SALES
                $sql = "SELECT SUM(amount) FROM `sales` " . " WHERE `sale_time` BETWEEN '$stime' AND '$etime' " . //	" AND `account_id`='".$_SESSION['account']['id']."' ".
                    " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "' OR verifier_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "')" . " AND (agent_cluster_id='$cluster_id' OR verifier_cluster_id='$cluster_id') " .

                    // EXCLUDE ANYTHING ROUSTING RELATED
                    " AND `is_paid` != 'roustedcc' " .

                    (($source_cluster_id > 0) ? " AND agent_cluster_id='$source_cluster_id' " : '') . (($ignore_source_cluster_id > 0) ? " AND agent_cluster_id != '$ignore_source_cluster_id' " : '') . $source_user_group_sql . " AND `is_paid`='yes' ";//" AND `call_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."'";
                //echo $sql."\n";
                list($paid_sales_amount) = $_SESSION['dbapi']->ROqueryROW($sql);

                $xfer_where = "WHERE xfer_time BETWEEN '$stime' AND '$etime' " . //	" AND `account_id`='".$_SESSION['account']['id']."' ".
                    " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "' OR verifier_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "')" . " AND (agent_cluster_id='$cluster_id' OR verifier_cluster_id='$cluster_id') " . $source_user_group_sql . (($source_cluster_id > 0) ? " AND agent_cluster_id='$source_cluster_id' " : '') . (($ignore_source_cluster_id > 0) ? " AND agent_cluster_id != '$ignore_source_cluster_id' " : '');

                ## GET ALL TRANSFERS FOR THE AGENT/TIMEFRAME
                $sql = "SELECT COUNT(`id`) FROM `transfers` " . $xfer_where;
                list($call_cnt) = $_SESSION['dbapi']->ROqueryROW($sql);

                ## HANGUPS - OUT OF ALL THE TRANSFERS, HOW MANY WHERE HANGUPS
                $sql = "SELECT COUNT(`id`) FROM `transfers` " . $xfer_where . " AND verifier_dispo='hangup' ";
                list($hangup_cnt) = $_SESSION['dbapi']->ROqueryROW($sql);

                ## DECLINES - OUT OF ALL THE TRANSFERS, HOW MANY WHERE DECLINED
                $sql = "SELECT COUNT(`id`) FROM `transfers` " . $xfer_where . " AND verifier_dispo='DEC' ";
                list($decline_cnt) = $_SESSION['dbapi']->ROqueryROW($sql);

                $sql = "SELECT SUM(agent_amount),SUM(verifier_amount) FROM `transfers` " . $xfer_where . " AND (verifier_dispo='PAIDCC') "; //verifier_dispo='SALE' OR
                list($agent_amount_total, $verifier_amount_total) = $_SESSION['dbapi']->ROqueryROW($sql);

                $sql = "SELECT SUM(agent_amount),SUM(verifier_amount) FROM `transfers` " . $xfer_where . " AND verifier_amount > agent_amount " . " AND (verifier_dispo='PAIDCC') "; //verifier_dispo='SALE' OR
                list($positive_agent_amount_total, $positive_verifier_amount_total) = $_SESSION['dbapi']->ROqueryROW($sql);

                $sql = "SELECT COUNT(id) FROM `transfers` " . $xfer_where . " AND (verifier_dispo='PAIDCC') " . " AND verifier_amount > agent_amount ";
                //echo $sql;
                list($bump_count) = $_SESSION['dbapi']->ROqueryROW($sql);

                //	echo $username.' '.$agent_amount_total.' '.$verifier_amount_total."<br>\n";

                list($agent_array[$username]['reviewcnt']) = $_SESSION['dbapi']->ROqueryROW("SELECT COUNT(`id`) FROM `dispo_log` " . " WHERE `agent_username`='" . mysqli_real_escape_string($_SESSION['dbapi']->db, $row['username']) . "' " . //			" AND `account_id`='".$_SESSION['account']['id']."' ".
                    " AND `micro_time` BETWEEN '$stmicro' AND '$etmicro' " . " AND `dispo` = 'REVIEW' " . " AND `result`='success' ");

//echo $username." REVIEW CNT: ".$agent_array[$username]['reviewcnt']."<br />";

                $agent_array[$username]['sale_cnt'] = $cnt;
                $agent_array[$username]['paid_sale_total'] = $paid_sales_amount;
                $agent_array[$username]['paid_sale_cnt'] = $paid_sales_cnt;
                $agent_array[$username]['call_cnt'] = $call_cnt;
                $agent_array[$username]['hangup_cnt'] = $hangup_cnt;
                $agent_array[$username]['decline_cnt'] = $decline_cnt;

                $agent_array[$username]['agent_amount_total'] = $agent_amount_total;
                $agent_array[$username]['verifier_amount_total'] = $verifier_amount_total;

                $agent_array[$username]['bump_amount'] = $verifier_amount_total - $agent_amount_total;

                $agent_array[$username]['bump_percent'] = ($agent_amount_total <= 0) ? 0 : round(($verifier_amount_total / $agent_amount_total) * 100, 2);

                $agent_array[$username]['positive_agent_amount_total'] = $positive_agent_amount_total;
                $agent_array[$username]['positive_verifier_amount_total'] = $positive_verifier_amount_total;
                $agent_array[$username]['pos_bump_amount'] = $positive_verifier_amount_total - $positive_agent_amount_total;
                $agent_array[$username]['pos_bump_percent'] = ($positive_agent_amount_total <= 0) ? 0 : round(($positive_verifier_amount_total / $positive_agent_amount_total) * 100, 2);

                //$agent_array[$username]['bump_percent'] = ($paid_sales_cnt > 0)?round( (($bump_count/$paid_sales_cnt)*100) , 2):0;

                $agent_array[$username]['bump_count'] = $bump_count;

            }

//print_r($agent_array);exit;

            // GET THE HOURS FROM VICI CLUSTER NOW
            /// RESOLVE DB IDX FROM CLUSTER ID
            $vici_idx = getClusterIndex($cluster_id);

            // CONNECT VICI CLUSTER BY IDX
            connectViciDB($vici_idx);
//echo "Cluster ID#".$cluster_id."\n";

            $out = array();

            $x = 0;
            foreach ($agent_array as $agent) {

                $username = strtoupper($agent['username']);

                $t_time = 0;
                $t_pause = 0;
                $t_talk = 0;
                $t_dead = 0;
                $t_call_count = 0;

//
//			if(!$use_archive_by_default){
//
//				$res = query("SELECT * FROM `vicidial_agent_log` ".
//						" WHERE `user` LIKE '".mysqli_real_escape_string($_SESSION['db'],$username)."' ".
//						" AND `user_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."' ".
//						" AND `dispo_epoch` BETWEEN '$stime' AND '$etime' "
//						,1);
//
//				if(mysqli_num_rows($res) == 0){
//
//					//echo "Using archive fallback.\n";
//					// ATTEMPT ARCHIVE TABLE?
//					$res = query("SELECT * FROM `vicidial_agent_log_archive` ".
//							" WHERE `user` LIKE '".mysqli_real_escape_string($_SESSION['db'],$username)."' ".
//							" AND `user_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."' ".
//							" AND `dispo_epoch` BETWEEN '$stime' AND '$etime' "
//							,1);
//				}
//			}else{
//
//				$res = query("SELECT * FROM `vicidial_agent_log_archive` ".
//							" WHERE `user` LIKE '".mysqli_real_escape_string($_SESSION['db'],$username)."' ".
//							" AND `user_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."' ".
//							" AND `dispo_epoch` BETWEEN '$stime' AND '$etime' "
//							,1);
//			}

//			$today = mktime(0,0,0);
//
//
//
//			// IF THE TIMEFRAME INCLUDES TODAY
//			if($today >= $stime && $today <= $etime){
//
//				// GET DATA FROM BOTH TABLES AND COMBINE
//
//
//				$res = query("SELECT * FROM `vicidial_agent_log` ".
//						" WHERE `user` = '".mysqli_real_escape_string($_SESSION['db'],$username)."' ". // INSTEAD OF LIKE, TO APPEASE ANDREW
//						" AND `user_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."' ".
//						" AND `dispo_epoch` BETWEEN '$stime' AND '$etime' "
//						,1);
//
//				// ADD THIS SHIT REAL QUICK, THEN QUERY THE ARCHIVE TABLE FOR THE REST?
//				while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
//
//					$t_time += ($row['pause_sec'] + $row['talk_sec'] + $row['dead_sec'] + $row['dispo_sec'] + $row['wait_sec']);
//
//					$t_pause += $row['pause_sec'];
//					$t_talk += $row['talk_sec'];
//					$t_dead += $row['dead_sec'];
//
//
//					$t_call_count++;
//				}
//
//
//				$res = query("SELECT * FROM `vicidial_agent_log_archive` ".
//						" WHERE `user` = '".mysqli_real_escape_string($_SESSION['db'],$username)."' ". // INSTEAD OF LIKE, TO APPEASE ANDREW
//						" AND `user_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."' ".
//						" AND `dispo_epoch` BETWEEN '$stime' AND '$etime' "
//						,1);
//
//
//			// ANYTHING ELSE BUT TODAY
//			}else{
//
//
//
//				// ATTEMPT ARCHIVE TABLE?
//				$res = query("SELECT * FROM `vicidial_agent_log_archive` ".
//						" WHERE `user` = '".mysqli_real_escape_string($_SESSION['db'],$username)."' ". // INSTEAD OF LIKE, TO APPEASE ANDREW
//						" AND `user_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."' ".
//						" AND `dispo_epoch` BETWEEN '$stime' AND '$etime' "
//						,1);
//
//			}

                $sql = "SELECT * FROM `vicidial_agent_log_archive` " . " WHERE `user` = '" . mysqli_real_escape_string($_SESSION['db'], $username) . "' " .

                    //  (($call_group != null)?" AND `user_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."' ":'').

                    (($call_group != NULL) ? $user_group_sql : '') .

                    " AND `dispo_epoch` BETWEEN '$stime' AND '$etime' " . " UNION " . "SELECT * FROM `vicidial_agent_log` " . " WHERE `user` = '" . mysqli_real_escape_string($_SESSION['db'], $username) . "' " . //(($call_group != null)?" AND `user_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."' ":'').
                    (($call_group != NULL) ? $user_group_sql : '') . " AND `dispo_epoch` BETWEEN '$stime' AND '$etime' ";

//echo $sql;

                $res = query($sql, 1);

                while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {

                    $t_time += ($row['pause_sec'] + $row['talk_sec'] + $row['dead_sec'] + $row['dispo_sec'] + $row['wait_sec']);

                    $t_pause += $row['pause_sec'];
                    $t_talk += $row['talk_sec'];
                    $t_dead += $row['dead_sec'];

                    $t_call_count++;
                }

                $out[$x] = array();
                $out[$x]['username'] = $username;
                $out[$x]['agent'] = $agent;
                $out[$x]['sale_cnt'] = $agent['sale_cnt'];

                $out[$x]['reviewcnt'] = $agent['reviewcnt'];

                $out[$x]['paid_sale_cnt'] = $agent['paid_sale_cnt'];
                $out[$x]['paid_sale_total'] = $agent['paid_sale_total'];

                $out[$x]['call_cnt'] = $agent['call_cnt'];
                $out[$x]['hangup_cnt'] = $agent['hangup_cnt'];
                $out[$x]['decline_cnt'] = $agent['decline_cnt'];

                $out[$x]['paid_time'] = $agent['paid_time'];

                $out[$x]['t_time'] = $t_time;
                $out[$x]['t_pause'] = $t_pause;
                $out[$x]['t_talk'] = $t_talk;
                $out[$x]['t_dead'] = $t_dead;
                $out[$x]['t_call_count'] = $t_call_count;

                $x++;
            }

            return $out;
        }

        function makeHTMLReport($stime, $etime, $cluster_id, $user_group, $ignore_users, $source_cluster_id = 0, $ignore_source_cluster_id = 0, $source_user_group = NULL) {

            $data = $this->generateData($cluster_id, $stime, $etime, $user_group, false, $ignore_users, $source_cluster_id, $ignore_source_cluster_id, $source_user_group);

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
            <script>

                function addUserToIgnore(username) {

                    var str = $('#ignore_users_list').val();

                    if (str.length > 0 && !str.endsWith(",")) str += ",";

                    str += username;

                    $('#ignore_users_list').val(str);
                }

            </script>
            <table border="0" width="100%">
                <tr>
                    <td style="border-bottom:1px solid #000;font-size:18px;font-weight:bold">

                        <br/>

                        Verifier Call Status Report - <?= date("m/d/Y", $stime) ?> - <?= htmlentities(($user_group == NULL || $user_group[0] == '') ? "All Groups" : "Selected Group" . ((count($user_group) > 1) ? "s" : " : " . ((is_array($user_group)) ? $user_group[0] : $user_group))) ?>

                    </td>
                </tr>
                <tr>
                    <th height="25" align="left" style="font-size:14px;font-weight:bold"><?

                            echo $user_group_str . '<br />';

                            if ($source_cluster_id > 0) {

                                echo "Source Cluster: " . getClusterName($source_cluster_id) . '<br />';
                            }

                            if ($ignore_source_cluster_id > 0) {

                                echo "Ignore Source Cluster: " . getClusterName($ignore_source_cluster_id) . '<br />';
                            }

                            echo '<i>Generated on: ' . date("g:ia m/d/Y") . '</i>';

                        ?></th>
                </tr>
                <tr>
                    <td>
                        <table id="verifier_report_table" border="0" width="900">
                            <thead>
                            <tr><?

                                    // CHECK FOR THIS, TO MAKE SURE ITS NOT THE EMAIL REPORT RUNNING
                                    if ($_SESSION['user']['priv'] > 3) {

                                        ?>
                                        <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="left">&nbsp;</th><?
                                    }

                                ?>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="left">Agent</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right"># of Calls</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Sales</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">PaidCC</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">%PaidCC</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">PaidCC/Hour</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">$PaidCC</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Hangups</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Declines</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Activity</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">In Call</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Time</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Paid Time</th>

                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Pause</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Talk Avg</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Dead</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Closing %</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Adj. Closing %</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Hangup %</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Sale Reviews</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Bump $</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Bump %</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right"># of Bumps</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right" title="Positive Bump Amount">Pos.Bump $</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right" title="Positive Bump Percent">Pos.Bump %</th>

                            </tr>
                            </thead>
                            <?
                                /**<tr style="font-style: italic;">
                                 * <th style="border-bottom:1px solid #000" align="left">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">7:30+</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th nowrap style="border-bottom:1px solid #000" align="right">30 or less</th>
                                 * <th nowrap style="border-bottom:1px solid #000" align="right">1:10 - 1:20</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th nowrap style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="center">&nbsp;</th>
                                 *
                                 * <th style="border-bottom:1px solid #000" align="center">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="center">&nbsp;</th>
                                 * <th style="border-bottom:1px solid #000" align="center">&nbsp;</th>
                                 * </tr>**/ ?>

                            <tbody><?
                                //

                                $stmicro = $stime * 1000;
                                $etmicro = $etime * 1000;

                                $running_total_calls = 0;
                                $running_total_sales = 0;
                                $running_total_hangups = 0;
                                $running_total_declines = 0;
                                $running_total_reviews = 0;
                                $running_total_paid_sales = 0;
                                $running_total_activity_time = 0;

                                $running_total_bumps = 0;
                                $running_total_pos_bump_agent_amount = 0;
                                $running_total_pos_bump_verifier_amount = 0;

                                $running_paid_time = 0;
                                $running_t_time = 0;
                                $running_t_dead = 0;
                                $running_total_talk_time = 0;
                                $running_total_paid_sales = 0;
                                $tcount = 0;
                                $x1 = 0;
                                foreach ($data as $row) {

//echo nl2br(print_r($row,1));
                                    $activity_time = ($row['agent']['seconds_INCALL'] + $row['agent']['seconds_READY'] + $row['agent']['seconds_QUEUE']);//+ $row['agent']['seconds_PAUSED']

                                    $running_total_activity_time += $activity_time;

                                    $tmphours = floor($activity_time / 3600);
                                    $tmpmin = floor(($activity_time - ($tmphours * 3600)) / 60);
                                    $total_activity_time = $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                    $incall_time = $row['agent']['seconds_INCALL'];
                                    $tmphours = floor($incall_time / 3600);
                                    $tmpmin = floor(($incall_time - ($tmphours * 3600)) / 60);
                                    $total_incall_time = $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                    $running_total_incall_time += $incall_time;

                                    $running_t_dead += $row['t_dead'];

                                    $tmphours = floor($row['t_time'] / 3600);
                                    $tmpmin = floor(($row['t_time'] - ($tmphours * 3600)) / 60);
                                    $total_time = $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                    $tmpmin = floor($row['t_pause'] / 60);
                                    $tmpsec = ($row['t_pause'] % 60);
                                    $total_pause = $tmpmin . ':' . (($tmpsec < 10) ? '0' . $tmpsec : $tmpsec);

                                    // GOTTA AVG THE TALK TIMES, NOT ADD
                                    $tmptalktime = intval($row['t_talk']);

                                    $running_total_talk_time += $tmptalktime;

                                    //$talktimeavg = $tmptalktime / intval($row['t_call_count']);
                                    $talktimeavg = ($row['call_cnt'] <= 0) ? 0 : ($tmptalktime / intval($row['call_cnt']));

                                    $total_talk = renderTimeFormatted($talktimeavg);

                                    $total_dead = renderTimeFormatted($row['t_dead']);

                                    //$close_percent = number_format( round( (($row['sale_cnt']) / ($row['t_call_count'])) * 100, 2), 2);
                                    $close_percent = ($row['call_cnt'] <= 0) ? 0 : number_format(round((($row['sale_cnt']) / ($row['call_cnt'])) * 100, 2), 2);

                                    $adjusted_close_percent = (($row['call_cnt'] - $row['hangup_cnt']) <= 0) ? 0 : number_format(round((($row['sale_cnt']) / ($row['call_cnt'] - $row['hangup_cnt'])) * 100, 2), 2);

                                    $hangup_percent = ($row['call_cnt'] <= 0) ? 0 : number_format(round((($row['hangup_cnt']) / ($row['call_cnt'])) * 100, 2), 2);

                                    // DISPO LOGGGGGGG
                                    $reviewcnt = $row['reviewcnt'];
//				list($reviewcnt) = $_SESSION['dbapi']->queryROW("SELECT COUNT(`id`) FROM `dispo_log` ".
//										" WHERE `agent_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$row['username'])."' ".
//										" AND `micro_time` BETWEEN '$stmicro' AND '$etmicro' ".
//										" AND `dispo` = 'REVIEW' ".
//										" AND `result`='success' ");

                                    $running_total_calls += $row['call_cnt'];
                                    $running_total_sales += ($row['sale_cnt']);
                                    $running_total_paid_sales += $row['paid_sale_cnt'];
                                    $running_total_reviews += $reviewcnt;

                                    $running_total_hangups += $row['hangup_cnt'];
                                    $running_total_declines += $row['decline_cnt'];

                                    $running_paid_time += $row['paid_time'];

                                    $percent_paidcc_calls = ($row['sale_cnt'] <= 0) ? 0 : ($row['paid_sale_cnt'] / $row['sale_cnt']) * 100;

                                    $running_total_paid_sales_amount += $row['paid_sale_total'];

                                    $running_t_time += $row['t_time'];

//echo $row['paid_sale_cnt']." / ".($row['paid_time']/60);

                                    $paidcc_per_hour = ($row['paid_time'] <= 0) ? 0 : ($row['paid_sale_cnt'] / ($row['paid_time'] / 60));//($row['t_time'] / 3600);

                                    $running_total_bumps += $row['agent']['bump_count'];

                                    $running_total_pos_bump_agent_amount += $row['agent']['positive_agent_amount_total'];
                                    $running_total_pos_bump_verifier_amount += $row['agent']['positive_verifier_amount_total'];

                                    ?>
                                    <tr><?

                                    // CHECK FOR THIS, TO MAKE SURE ITS NOT THE EMAIL REPORT RUNNING
                                    if ($_SESSION['user']['priv'] > 3) {

                                        ?>
                                        <td style="border-right:1px dotted #CCC;padding-right:3px">

                                        <a href="#" onclick="addUserToIgnore('<?= htmlentities(strtoupper($row['username'])) ?>');return false;">[Ignore]</a>

                                        </td><?
                                    }

                                    ?>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px"><?= strtoupper($row['username']) ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                            echo number_format($row['call_cnt'])

                                            //		number_format($row['t_call_count'])
                                        ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= number_format(($row['sale_cnt'] - $row['paid_sale_cnt'])) ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= number_format($row['paid_sale_cnt']) ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= number_format($percent_paidcc_calls) ?> %</td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= number_format($paidcc_per_hour, 2) ?></td>

                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right">$<?= number_format($row['paid_sale_total'], 2) ?></td>

                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= number_format($row['hangup_cnt']) ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= number_format($row['decline_cnt']) ?></td>

                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                            echo $total_activity_time;

                                        ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                            echo $total_incall_time;

                                        ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                            //						if($row['t_time'] >= $this->time_limit){

                                            echo '<span style="background-color:transparent">' . $total_time . '</span>';
                                            //						}else{
                                            //							echo '<span style="background-color:yellow">'.$total_time.'</span>';
                                            //
                                            //
                                            //						}
                                        ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                            // PAID TIME

                                            echo renderTimeFormatted($row['paid_time'] * 60);

                                        ?></td>

                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                            //						if($row['t_pause'] <= $this->pause_limit){

                                            echo '<span style="background-color:transparent">' . $total_pause . '</span>';
                                            //						}else{

                                            //							echo '<span style="background-color:yellow">'.$total_pause.'</span>';
                                            //						}

                                        ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?
                                            $talktimeavg = floor($talktimeavg);

                                            //echo $talktimeavg.' vs '.$this->talk_lower_limit.' ';

                                            //						if($talktimeavg >= $this->talk_lower_limit && $talktimeavg <= $this->talk_upper_limit){

                                            echo '<span style="background-color:transparent">' . $total_talk . '</span>';
                                            //						}else{

                                            //							echo '<span style="background-color:yellow">'.$total_talk.'</span>';
                                            //						}

                                        ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                            //						if($row['t_dead'] > $this->dead_time_limit){
                                            //
                                            //							echo '<span style="background-color:yellow">'.$total_dead.'</span>';
                                            //						}else{
                                            echo '<span style="background-color:transparent">' . $total_dead . '</span>';
                                            //						}

                                        ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                            //						if(intval($close_percent) >= $this->close_percent_limit){

                                            echo '<span style="background-color:transparent">' . $close_percent . '%</span>';
                                            //						}else{

                                            //							echo '<span style="background-color:yellow">'.$close_percent.'%</span>';

                                            //						}

                                        ?></td>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                            //						if(intval($adjusted_close_percent) >= $this->close_percent_limit){

                                            echo '<span style="background-color:transparent">' . $adjusted_close_percent . '%</span>';

                                            //						}else{
                                            //							echo '<span style="background-color:yellow">'.$adjusted_close_percent.'%</span>';
                                            //						}

                                        ?></td>

                                    <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                            //if(intval($adjusted_close_percent) >= $this->close_percent_limit){

                                            echo '<span style="background-color:transparent">' . $hangup_percent . '%</span>';
                                            //}else{
                                            //	echo '<span style="background-color:yellow">'.$adjusted_close_percent.'%</span>';
                                            //}

                                        ?></td>

                                    <td align="right"><?

                                            echo number_format($reviewcnt);

                                        ?></td>


                                    <td align="right"><?

                                            echo '$' . number_format($row['agent']['bump_amount']);

                                        ?></td>


                                    <td align="right"><?

                                            echo number_format($row['agent']['bump_percent'], 2) . '%';

                                        ?></td>
                                    <td align="right"><?

                                            echo number_format($row['agent']['bump_count']);

                                        ?></td>

                                    <td align="right"><?

                                            echo '$' . number_format($row['agent']['pos_bump_amount']);

                                        ?></td>


                                    <td align="right"><?

                                    echo number_format($row['agent']['pos_bump_percent'], 2) . '%';

                                    ?></td><?

                                    /*
                                                $agent_array[$username]['positive_agent_amount_total'] = $positive_agent_amount_total;
                                                $agent_array[$username]['positive_verifier_amount_total'] = $positive_verifier_amount_total;
                                                $agent_array[$username]['pos_bump_amount'] = $positive_verifier_amount_total - $positive_agent_amount_total;
                                                $agent_array[$username]['pos_bump_percent'] = ($positive_agent_amount_total <= 0)?0:round(($positive_verifier_amount_total / $positive_agent_amount_total) * 100, 2);
                                    */

                                    ?></tr><?

                                    $tcount++;
                                }

                            ?></tbody><?

                                $total_close_percent = (($running_total_calls <= 0) ? 0 : number_format(round((($running_total_sales) / ($running_total_calls)) * 100, 2), 2));

                                $total_adj_close_percent = ((($running_total_calls - $running_total_hangups) <= 0) ? 0 : number_format(round((($running_total_sales) / ($running_total_calls - $running_total_hangups)) * 100, 2), 2));

                                $total_hangup_percent = (($running_total_calls <= 0) ? 0 : number_format(round((($running_total_hangups) / ($running_total_calls)) * 100, 2), 2));

                                $total_percent_paidcc_calls = (($running_total_sales <= 0) ? 0 : ($running_total_paid_sales / $running_total_sales) * 100);

                                $total_paidcc_per_hour = ($running_paid_time <= 0) ? 0 : ($running_total_paid_sales / ($running_paid_time / 60));

                                $total_talk_time_avg = (($running_total_calls <= 0) ? 0 : ($running_total_talk_time / $running_total_calls));

                                $total_pos_bump_amount = $running_total_pos_bump_verifier_amount - $running_total_pos_bump_agent_amount;
                                $total_pos_bump_percent = ($running_total_pos_bump_agent_amount <= 0) ? 0 : round(($running_total_pos_bump_verifier_amount / $running_total_pos_bump_agent_amount) * 100, 2);

                                // TOTALS ROW
                            ?>
                            <tfoot>
                            <tr><?
                                    // CHECK FOR THIS, TO MAKE SURE ITS NOT THE EMAIL REPORT RUNNING

                                    if ($_SESSION['user']['priv'] > 3) {

                                        ?>
                                        <td>&nbsp;</td>
                                        <th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="left">Totals:</th><?

                                    } else {

                                        ?>
                                        <th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="left">Totals:</th><?

                                    }

                                ?>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($running_total_calls) ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= number_format(($running_total_sales - $running_total_paid_sales)) ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($running_total_paid_sales) ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($total_percent_paidcc_calls) ?>%</td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($total_paidcc_per_hour, 2) ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right">$<?= number_format($running_total_paid_sales_amount, 2) ?></td>


                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($running_total_hangups) ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($running_total_declines) ?></td>

                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?

                                        $tmphours = floor($running_total_activity_time / 3600);
                                        $tmpmin = floor(($running_total_activity_time - ($tmphours * 3600)) / 60);
                                        echo $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?

                                        //$running_total_incall_time
                                        $tmphours = floor($running_total_incall_time / 3600);
                                        $tmpmin = floor(($running_total_incall_time - ($tmphours * 3600)) / 60);
                                        echo $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?

                                        $tmphours = floor($running_t_time / 3600);
                                        $tmpmin = floor(($running_t_time - ($tmphours * 3600)) / 60);
                                        echo $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                        //	echo renderTimeFormatted($running_t_time/60);

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?

                                        echo renderTimeFormatted($running_paid_time * 60);

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000">&nbsp;</td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?

                                        //						if($total_talk_time_avg >= $this->talk_lower_limit && $total_talk_time_avg <= $this->talk_upper_limit){

                                        echo '<span style="background-color:transparent">' . renderTimeFormatted($total_talk_time_avg) . '</span>';
                                        //						}else{

                                        //							echo '<span style="background-color:yellow">'.renderTimeFormatted($total_talk_time_avg).'</span>';
                                        //						}

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000"><?

                                        echo '<span style="background-color:transparent">' . renderTimeFormatted($running_t_dead) . '</span>';

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?

                                        //						if(intval($total_close_percent) >= $this->close_percent_limit){

                                        echo '<span style="background-color:transparent">' . $total_close_percent . '%</span>';
                                        //						}else{

                                        //							echo '<span style="background-color:yellow">'.$total_close_percent.'%</span>';

                                        //						}

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?

                                        //						if(intval($total_adj_close_percent) >= $this->close_percent_limit){

                                        echo '<span style="background-color:transparent">' . $total_adj_close_percent . '%</span>';
                                        //						}else{

                                        //							echo '<span style="background-color:yellow">'.$total_adj_close_percent.'%</span>';

                                        //						}

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?

                                        //if(intval($total_adj_close_percent) >= $this->close_percent_limit){

                                        echo '<span style="background-color:transparent">' . $total_hangup_percent . '%</span>';

                                        //}else{
                                        //	echo '<span style="background-color:yellow">'.$total_hangup_percent.'%</span>';
                                        //}

                                    ?></td>
                                <td style="border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($running_total_reviews) ?></td>

                                <td style="border-top:1px solid #000;padding-right:3px" align="right">&nbsp;</td>
                                <td style="border-top:1px solid #000;padding-right:3px" align="right">&nbsp;</td>
                                <td style="border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($running_total_bumps) ?></td>
                                <td style="border-top:1px solid #000;padding-right:3px" align="right">$<?= number_format($total_pos_bump_amount) ?></td>
                                <td style="border-top:1px solid #000;padding-right:3px" align="right"><?= $total_pos_bump_percent ?>%</td>
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
            $timeOptionMode = (isset($_REQUEST['timeOptions']) ? intval($_REQUEST['timeOptions']) : 1);
            if (isset($_POST['generate_report'])) {
                if($_REQUEST['timeFilter']){
                    $timestamp = strtotime($_REQUEST['strt_date_month']."/".$_REQUEST['strt_date_day']."/".$_REQUEST['strt_date_year']." ".$_REQUEST['strt_time_hour'].":".$_REQUEST['strt_time_min'].$_REQUEST['strt_time_timemode']);
                    $timestamp2 = strtotime($_REQUEST['end_date_month']."/".$_REQUEST['end_date_day']."/".$_REQUEST['end_date_year']." ".$_REQUEST['end_time_hour'].":".$_REQUEST['end_time_min'].$_REQUEST['end_time_timemode']);
                }else{
                    $timestamp = strtotime($_REQUEST['strt_date_month']."/".$_REQUEST['strt_date_day']."/".$_REQUEST['strt_date_year']." 00:00:00");
                    $timestamp2 = strtotime($_REQUEST['end_date_month']."/".$_REQUEST['end_date_day']."/".$_REQUEST['end_date_year']." 23:59:59");
                }
            } else {
                $timestamp = mktime(0, 0, 0);
                $timestamp2 = mktime(23, 59, 59);
            }
            //echo $this->makeHTMLReport('1430377200', '1430463599', 'BCSFC', -1, 1,null , array("SYSTEM-TRNG-SOUTH", "SYSTEM-TRNG","SYS-TRNG-SOUTH-AM")) ;
            if (!isset($_REQUEST['no_nav'])) {
                ?>
                <script type="text/javascript" src="js/table2CSV.js"></script>
                <form id="agnet_call_stats_report" method="POST"
                      action="<?= $_SERVER['PHP_SELF'] ?>?area=agent_call&no_script=1"
                      onsubmit="return genReport(this, 'sales')">
                    <input type="hidden" name="generate_report">
                    <table border="0" width="100%">
                        <tr>
                            <td height="40" class="pad_left ui-widget-header">
                                Area Code Sales by Dialer
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <script>                    <script>
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
                                </script>
                                <table border="0" id="filterTable">
                                    <tr>
                                        <th>Date Start:</th>
                                        <td>
                                            <?php  echo makeTimebar("strt_date_", 1, null, false, $timestamp); ?>
                                            <div style="float:right; padding-left:6px;" id="startTimeFilter"> <?php  echo makeTimebar("strt_time_", 2, NULL, false, $timestamp); ?></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Date End:</th>
                                        <td>
                                            <?php echo makeTimebar("end_date_", 1, null, false, $timestamp2); ?>
                                            <div style="float:right; padding-left:6px;" id="endTimeFilter"> <?php  echo makeTimebar("end_time_", 2, NULL, false, $timestamp2); ?></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Use Time?</th>
                                        <td>
                                            <input type="checkbox" name="timeFilter" id="timeFilter">
                                        </td>
                                    </tr>
                                    <tr id="shiftHours">
                                        <th>Shift Hours :</th>
                                        <td><?
                                                echo makeNumberDD("shift_hours", (!isset($_REQUEST['shift_hours']) ? 12 : $_REQUEST['shift_hours']), 1, 24, 1);
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Agent Cluster [Dialer] :</th>
                                        <td><?php
                                                echo makeClusterDD("agent_cluster_id", (!isset($_REQUEST['agent_cluster_id']) || intval($_REQUEST['agent_cluster_id']) < 0) ? -1 : $_REQUEST['agent_cluster_id'], '', ""); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Area Code :</th>
                                        <td><?php
                                                echo makeAreaCodeDD("area_code", (!isset($_REQUEST['area_code'])) ? 0 : $_REQUEST['area_code'], '', ""); ?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="2">
                                            <div id="sales_loading_plx_wait_span" class="nod"><img
                                                        src="images/ajax-loader.gif" border="0"/> Loading, Please
                                                wait...
                                            </div>
                                            <div id="dialersales_submit_report_button">
                                                <input type="button" value="Generate PRINTABLE"
                                                       onclick="genReport(getEl('dialersales_report'), 'sales', 1)">
                                                <input type="button" id="btnGenCSV" value="Download CSV"
                                                       onclick="genCSV(getEl('dialer_sales_table'))" disabled aria-disabled="true">
                                                <input type="submit" value="Generate">
                                            </div>
                                        </th>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </form>
                <br/><br/><?php
            } else {
                ?>
                <meta charset="UTF-8">
                <meta name="google" content="notranslate">
                <meta http-equiv="Content-Language" content="en"><?php
            }

            if (isset($_POST['generate_report'])) {
                ?>
                <script>
                    $('#timeOptions').val(<?=$timeOptionMode?>).change();
                    $('#btnGenCSV').prop('disabled', false).prop('aria-disabled', false);
                </script>
                <?
                #echo var_dump($_REQUEST) . "<br />";
                $time_started = microtime_float();
                ## AGENT CLUSTER
                $agent_cluster_id = intval($_REQUEST['agent_cluster_id']);
                ## AREA CODE
                $area_code = intval($_REQUEST['area_code']);
                ## SHIFT HOURS
                $shift_hours = intval($_REQUEST['shift_hours']);
                $timeOptionMode = intval($_REQUEST['timeOptions']);
                switch ($timeOptionMode) {
                    case '1' :
                        $timestamp = strtotime($_POST['strt_date_month'] . "/" . $_POST['strt_date_day'] . "/" . $_POST['strt_date_year'] . " 00:00:00");
                        $stime = mktime(0, 0, 0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));
                        $etime = mktime(23, 59, 59, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));
                        $reportName = "AreaCodeSales_singledate_" . date('m-d-Y', $stime) . "_" . $shift_hours;
                        break;
                    case '2' :
                        $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " 00:00:00");
                        $timestamp2 = strtotime($_REQUEST['end_date_month'] . "/" . $_REQUEST['end_date_day'] . "/" . $_REQUEST['end_date_year'] . " 23:59:59");
                        $stime = mktime(0, 0, 0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));
                        $etime = mktime(23, 59, 59, date("m", $timestamp2), date("d", $timestamp2), date("Y", $timestamp2));
                        $shift_hours = (ceil(($timestamp2 - $timestamp) / 86400) - 1) * $shift_hours;
                        $reportName = "AreaCodeSales_daterange_" . date('m-d-Y', $stime) . "_to_" . date('m-d-Y', $etime) . "_" . $shift_hours;
                        break;
                    case '3' :
//                        $inStart = $_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " " . $_REQUEST['strt_time_hour'] . ":" . $_REQUEST['strt_time_min'] . " " . $_REQUEST['strt_time_timemode'];
//                        $inEnd = $_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " " . $_REQUEST['end_time_hour'] . ":" . $_REQUEST['end_time_min'] . " " . $_REQUEST['end_time_timemode'];
//                        echo __METHOD__ . "::POST PROCESSING:: START POSTED : " . $inStart . "<br />" . PHP_EOL;
//                        echo __METHOD__ . "::POST PROCESSING:: END POSTED : " . $inEnd . "<br />" . PHP_EOL;
                        $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " " . $_REQUEST['strt_time_hour'] . ":" . $_REQUEST['strt_time_min'] . " " . $_REQUEST['strt_time_timemode']);
                        $timestamp2 = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " " . $_REQUEST['end_time_hour'] . ":" . $_REQUEST['end_time_min'] . " " . $_REQUEST['end_time_timemode']);
//                        echo __METHOD__ . "::POST PROCESSING:: START AFTER strtotime() : " . date('m/d/Y H:i A', $timestamp) . "<br />" . PHP_EOL;
//                        echo __METHOD__ . "::POST PROCESSING:: END AFTER strtotime() : " . date('m/d/Y H:i A', $timestamp2) . "<br />" . PHP_EOL;
                        $stime = mktime(date("H", $timestamp), date("i", $timestamp), 0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));
                        $etime = mktime(date("H", $timestamp2), date("i", $timestamp2), 59, date("m", $timestamp2), date("d", $timestamp2), date("Y", $timestamp2));
//                        echo __METHOD__ . "::POST PROCESSING:: START AFTER mktime() : " . date('m/d/Y H:i A', $stime) . "<br />" . PHP_EOL;
//                        echo __METHOD__ . "::POST PROCESSING:: END AFTER mktime() : " . date('m/d/Y H:i A', $etime) . "<br />" . PHP_EOL;
                        $shift_hours = ceil(($timestamp2 - $timestamp) / 3600);
//                        echo __METHOD__ . "::POST PROCESSING:: shiftHours calculated = " . $shift_hours . "<br />" . PHP_EOL;
                        $reportName = "AreaCodeSales_datetimerange_" . date('m-d-Y_H:i', $stime) . "_to_" . date('m-d-Y_H:i', $etime) . "_" . $shift_hours;
                        break;
                }
                echo "<input type='hidden' id='reportTitle' value='" . $reportName . "'>" . PHP_EOL;
                ## GENERATE AND DISPLAY REPORT
                #echo __METHOD__ . "::POST PROCESSING:: timeOptionMode = " . $timeOptionMode . "<br />" . PHP_EOL;
                #echo "Start Time (timestamp) = " . var_dump($timestamp) . "<br />" . PHP_EOL;
                #echo "End Time (timestamp2) = " . var_dump($timestamp2) . "<br />" . PHP_EOL;
                #echo __METHOD__ . "::POST PROCESSING:: about to call makeHTMLReport(" . $stime . ", " . $etime . ", " . $agent_cluster_id . ", " . $area_code . ", " . $shift_hours . ");<br />" . PHP_EOL;
                $html = $this->makeHTMLReport($stime, $etime, $agent_cluster_id, $area_code, $shift_hours, $timeOptionMode);
                if ($html == NULL) {
                    echo '<span style="font-size:14px;font-style:italic;">No results found, for the specified values.</span><br />';
                } else {
                    echo $html;
                }
                $time_ended = microtime_float();
                $time_taken = $time_ended - $time_started;
                echo '<br /><span style="float:bottom;color:#fff">Load time: ' . $time_taken . '</span>';
                if (!isset($_REQUEST['no_nav'])) {
                    ?>
                    <script>
                        $(document).ready(function () {
                            $('#dialer_sales_table').DataTable({
                                "lengthMenu": [[-1, 20, 50, 100, 500], ["All", 20, 50, 100, 500]]
                            });
                        });
                    </script><?php

                    if (isset($_REQUEST['generate_agent_stat_report'])) {

                        if ($_REQUEST['date_mode'] == 'daterange') {

                            $stime = mktime(0, 0, 0, $_REQUEST['s_date_month'], $_REQUEST['s_date_day'], $_REQUEST['s_date_year']);
                            $etime = mktime(23, 59, 59, $_REQUEST['e_date_month'], $_REQUEST['e_date_day'], $_REQUEST['e_date_year']);

                        } else {

                            $stime = mktime(0, 0, 0, $_REQUEST['s_date_month'], $_REQUEST['s_date_day'], $_REQUEST['s_date_year']);
                            $etime = $stime + 86399;
                        }

                    } else {
                        $stime = mktime(0, 0, 0);
                        $etime = mktime(23, 59, 59);

                    }

                    $cluster_id = intval($_REQUEST['s_cluster_id']);
                    $cluster_id = ($cluster_id) ? $cluster_id : 9; // DEFAULT TO VERIFIER CLUSTER

                    $user_group = $_REQUEST['s_user_group'];

                    $source_user_group = $_REQUEST['s_source_user_group'];

                    $source_cluster_id = intval($_REQUEST['s_source_cluster_id']);

                    $ignore_source_cluster_id = intval($_REQUEST['s_ignore_source_cluster_id']);

                    ?>
                    <table border="0" width="100%"><?

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
                        <?

                            if (!isset($_REQUEST['no_nav'])) {

                                ?>
                                <tr>
                                    <td height="40" class="ui-widget-header pad_left">Verifier Call Stats</td>
                                </tr>
                                <tr>
                                <td>
                                    <form id="agentstatfrm" method="POST" action="<?= stripurl() ?>" onsubmit="return genReport(this,'callstats')">

                                        <input type="hidden" name="generate_agent_stat_report">

                                        <table border="0">


                                            <tr>
                                                <th>Cluster</th>
                                                <td><?

                                                        echo makeClusterDD('s_cluster_id', $cluster_id, "", '', 0);

                                                    ?></td>
                                            </tr>


                                            <tr>
                                                <th>User Group</th>
                                                <td><?

                                                        echo makeViciUserGroupDD('s_user_group[]', $_REQUEST['s_user_group'], "", '', 8, 1);

                                                    ?></td>
                                            </tr>
                                            <tr>
                                                <th>Date Mode:</th>
                                                <td><select name="date_mode" onchange="toggleDateSearchMode(this.value)">
                                                        <option value="date">Date</option>
                                                        <option value="daterange"<?= ($_REQUEST['date_mode'] == 'daterange') ? ' SELECTED' : '' ?>>Date Range</option>
                                                    </select></td>
                                            </tr>
                                            <tr>
                                                <th>Date:</th>
                                                <td><?

                                                        echo makeTimebar("s_date_", 1, NULL, false, $stime, "");

                                                    ?></td>
                                            </tr>
                                            <tr id="end_date_row" class="nod">
                                                <th>End Date:</th>
                                                <td><?

                                                        echo makeTimebar("e_date_", 1, NULL, false, $etime, "");

                                                    ?></td>
                                            </tr>

                                            <tr>
                                                <th>SOURCE Cluster (<a href="#" onclick="alert('Only show deals that come FROM this cluster.');return false;">help?</a>)</th>
                                                <td><?

                                                        echo makeClusterDD('s_source_cluster_id', $source_cluster_id, "", '', 1);

                                                    ?></td>
                                            </tr>

                                            <tr>
                                                <th>Ignore SOURCE Cluster (<a href="#" onclick="alert('Skip the deals that come FROM this cluster.');return false;">help?</a>)</th>
                                                <td><?

                                                        echo makeClusterDD('s_ignore_source_cluster_id', $ignore_source_cluster_id, "", '', 1);

                                                    ?></td>
                                            </tr>
                                            <tr>
                                                <th>SOURCE User Group</th>
                                                <td><?

                                                        echo makeViciUserGroupDD('s_source_user_group[]', $_REQUEST['s_source_user_group'], "", '', 8, 1);

                                                    ?></td>
                                            </tr>

                                            <tr>
                                                <th>Ignore Users: (<a href="#" onclick="alert('Ignore users in the report, if they appear. Seperate the usernames with Commas');return false">help?</a>)</th>

                                                <td>
                                                    <input type="text" size="30" name="ignore_users_list" id="ignore_users_list" value="<?= htmlentities($_REQUEST['ignore_users_list']) ?>">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" align="right">

                                                    <span id="callstats_loading_plx_wait_span" class="nod"><img src="images/ajax-loader.gif" border="0"/> Loading, Please wait...</span>

                                                    <span id="callstats_submit_report_button">

							<?
                                /**<input type="submit" name="no_script" value="Generate Printable" onclick="this.form.target='_blank';">**/ ?>
							<input type="button" value="Generate PRINTABLE" onclick="genReport(getEl('agentstatfrm'),'callstats',1)">

							&nbsp;&nbsp;&nbsp;&nbsp;

							<input type="submit" value="Generate Now" onclick="this.form.target='';">

						</span>
                                                </td>
                                            </tr>

                                        </table>
                                </td>
                                </tr><?

                                ?></form><?

                            } else {

                                ?>
                                <meta charset="UTF-8">
                                <meta name="google" content="notranslate">
                                <meta http-equiv="Content-Language" content="en"><?
                            }

                            //}

                            if (isset($_REQUEST['generate_agent_stat_report'])) {

                                ?>
                                <tr>
                                    <td><?

                                            $ignore_arr = preg_split("/,|;|:| /", $_REQUEST['ignore_users_list'], -1, PREG_SPLIT_NO_EMPTY);

                                            $source_cluster_id = intval($_REQUEST['s_source_cluster_id']);

                                            $report = $this->makeHTMLReport($stime, $etime, $cluster_id, $_REQUEST['s_user_group'], $ignore_arr, $source_cluster_id, $ignore_source_cluster_id, $source_user_group);

                                            if (!$report) {

                                                echo "No data";

                                            } else {
                                                echo $report;
                                            }

                                        ?></td>
                                </tr>
                                <script>

                                    toggleDateSearchMode('<?=$_REQUEST['date_mode']?>');

                                </script><?

                                if (!isset($_REQUEST['no_nav'])) {
                            ?>
                                <script>
                                    $(document).ready(function () {

                                        $('#verifier_report_table').DataTable({

                                            "lengthMenu": [[-1, 20, 50, 100, 500], ["All", 20, 50, 100, 500]]


                                        });


                                    });
                                </script><?
                            }

                            } // END IF GENERATE REPORT

                        ?></table><?

                }

            }
        }
    }
// END OF CLASS
