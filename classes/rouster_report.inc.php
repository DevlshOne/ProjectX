<?php
/***************************************************************
 * Rouster Report
 * Written By: Jonathan Will
 ***************************************************************/

$_SESSION['rouster_report'] = new RousterReport;

class RousterReport
{

    var $close_percent_limit = 76;

    var $dead_time_limit = 120;

    var $time_limit = 27000; // 7.5hrs aka 7:30

    var $talk_lower_limit = 70;
    var $talk_upper_limit = 80;

    var $pause_limit = 1800;

    var $report_order_field = 'paidcc_per_hour';
    var $report_order_dir = "DESC";

    function RousterReport()
    {

        $this->handlePOST();

    }

    function handlePOST()
    {

    }

    function handleFLOW()
    {

        if (!checkAccess('rouster_report')) {

            accessDenied("Rouster Report");

            return;

        } else {

            $this->makeReport();

        }

    }


    function generateData($cluster_id, $user_team_id, $stime, $etime, $call_group = NULL, $use_archive_by_default = false, $ignore_arr = NULL, $source_cluster_id = 0, $ignore_source_cluster_id = 0, $source_user_group = NULL, $combine_users = false)
    {

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

            if (php_sapi_name() != "cli") {

                if ($call_group[0] == '' && (($_SESSION['user']['priv'] < 5) && ($_SESSION['user']['allow_all_offices'] != 'yes'))) {

                    $call_group = $_SESSION['assigned_groups'];

                }
            }

            $x = 0;

            foreach ($call_group as $group) {

                if (trim($group) == '') continue;

                if ((php_sapi_name() != "cli") && (($_SESSION['user']['priv'] < 5) && ($_SESSION['user']['allow_all_offices'] != 'yes')) && is_array($_SESSION['assigned_groups']) && !in_array($group, $_SESSION['assigned_groups'], false)) {

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

        connectPXDB();

        $use_team = false;
        $sql_user_team_list = array();


        if ($user_team_id) {
            $use_team = true;
            $sql_user_team_list = $_SESSION['dbapi']->user_teams->getTeamMembers($user_team_id);
        }


        $sql = "SELECT DISTINCT(username) FROM `logins` " . " WHERE result='success' AND section IN('rouster','roustersys') " . (($stime && $etime) ? " AND `time` BETWEEN '$stime' AND '$etime' " : '') . (($cluster_id > 0) ? " AND cluster_id='$cluster_id' " : "") . $user_group_sql;

//		$sql = "SELECT DISTINCT(agent_username) FROM lead_tracking WHERE 1".
//						(($stime && $etime)?" AND `time` BETWEEN '$stime' AND '$etime' ":'').
//						(($cluster_id > 0)?" AND vici_cluster_id='$cluster_id' ":"").
//						$user_group_sql.
//						"";
// 		echo $sql;
// 		exit;
//
        $res = $_SESSION['dbapi']->ROquery($sql);
        $userzstack = array();
        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {


            if ($use_team) {

                if (!in_array($row['username'], $sql_user_team_list, false)) {

                    //echo "Skipping " . $tmp . " --> not in selected team.<br />";

                    continue;
                }
            }

            $userzstack[] = strtoupper($row['username']);
        }

        $agent_array = array();
        $stmicro = $stime * 1000;
        $etmicro = $etime * 1000;


        $startofday = mktime(0, 0, 0, date("m", $stime), date("d", $stime), date("Y", $stime));
        $endofday = mktime(23, 59, 59, date("m", $etime), date("d", $etime), date("Y", $etime));

        foreach ($userzstack as $uname) {

            $sql = "SELECT *, seconds_INCALL+seconds_READY+seconds_QUEUE+seconds_PAUSED as TotalTime,seconds_INCALL+seconds_READY+seconds_QUEUE as DailyActivityTime  FROM `activity_log` WHERE 1 " .

                " AND `username`='" . mysqli_real_escape_string($_SESSION['db'], $uname) . "' " .

                //(($stime && $etime) ? " AND `time_started` BETWEEN '$stime' AND '$etime' " : '') .

                (($stime && $etime) ? " AND `time_started` BETWEEN '$startofday' AND '$endofday' " : '') .


                " ORDER BY `time_started` ASC ";
            // GET A LIST OF TEH AGENTS
            // ACTIVITY LOG: THE AGENTS THAT ARE WORKING TODAY
            $res = $_SESSION['dbapi']->ROquery($sql, 1);

            while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {

                $username = strtoupper($row['username']);


                if ($combine_users == true && $username[strlen($username) - 1] == '2') {

                    $username = substr($username, 0, strlen($username) - 1);

                }

                if ($ignore_arr != NULL && is_array($ignore_arr)) {

                    // SKIPP THEM!!!!
                    if (in_array($username, $ignore_arr)) {

                        continue;
                    }

                }

                // USER ON THE STACK ALREADY
                if (isset($agent_array[$username]) && $agent_array[$username]) {

                    $agent_array[$username]['activity_time'] += $row['activity_time'];
                    $agent_array[$username]['paid_time'] += $row['paid_time'];
                    $agent_array[$username]['paid_corrections'] += $row['paid_corrections'];
                    
                    $agent_array[$username]['agent_count']++;

                    $agent_array[$username]['seconds_INCALL'] += $row['seconds_INCALL'];
                    $agent_array[$username]['seconds_READY'] += $row['seconds_READY'];
                    $agent_array[$username]['seconds_QUEUE'] += $row['seconds_QUEUE'];
                    $agent_array[$username]['seconds_PAUSED'] += $row['seconds_PAUSED'];

                    $curdate = date("m/d/Y", $row['time_started']);

                    if (!isset($agent_array[$username]['total_activity_date_time_array'][$curdate])) {

                        //seconds_INCALL+seconds_READY+seconds_QUEUE+seconds_PAUSED as TotalTime
                        $agent_array[$username]['total_activity_date_time_array'][$curdate] = intval($row['TotalTime']);
                        //seconds_INCALL+seconds_READY+seconds_QUEUE as DailyActivityTime
                        $agent_array[$username]['total_activity_date_daily_array'][$curdate] = intval($row['DailyActivityTime']);

                    } else {

                        $agent_array[$username]['total_activity_date_time_array'][$curdate] = ($agent_array[$username]['total_activity_date_time_array'][$curdate] > intval($row['TotalTime'])) ? $agent_array[$username]['total_activity_date_time_array'][$curdate] : intval($row['TotalTime']);//max(intval($row['TotalTime']), $agent_array[$username]['total_activity_date_time_array'][date("m/d/Y", $row['time_started'])]);///
                        $agent_array[$username]['total_activity_date_daily_array'][$curdate] = ($agent_array[$username]['total_activity_date_daily_array'][$curdate] > intval($row['DailyActivityTime'])) ? $agent_array[$username]['total_activity_date_daily_array'][$curdate] : intval($row['DailyActivityTime']); // max(intval($row['DailyActivityTime']), $agent_array[$username]['total_activity_date_daily_array'][date("m/d/Y", $row['time_started'])]); //

                    }
                    continue;
                }

                $curdate = date("m/d/Y", $row['time_started']);

                $agent_array[$username] = $row;
                $agent_array[$username]['agent_count'] = 1;

                $agent_array[$username]['total_activity_date_time_array'] = array();
                $agent_array[$username]['total_activity_date_daily_array'] = array();
                $agent_array[$username]['total_activity_date_time_array'][$curdate] = intval($row['TotalTime']);
                $agent_array[$username]['total_activity_date_daily_array'][$curdate] = intval($row['DailyActivityTime']);
                // GET TOTAL SALES COUNTS FROM PX
                $sql = "SELECT COUNT(`id`) FROM `sales` " .
                    " WHERE `sale_time` BETWEEN '$stime' AND '$etime' " .
                    (($combine_users) ? " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "'  OR agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "2') " : " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "') ") .
                    (($cluster_id > 0)?" AND (agent_cluster_id='$cluster_id') ":'') .
                    $extra_sql;

                //echo $sql."\n";

                list($cnt) = $_SESSION['dbapi']->ROqueryROW($sql);

                // echo $sql." AND `is_paid` IN('yes','roustedcc') \n";

                list($paid_sales_cnt) = $_SESSION['dbapi']->ROqueryROW($sql . " AND `is_paid` IN('yes','roustedcc') ");

                // GET TOTAL SALES AMOUNTS FOR PAID SALES
                $sql = "SELECT SUM(amount) FROM `sales` " .
                    " WHERE `sale_time` BETWEEN '$stime' AND '$etime' " .

                    (($combine_users) ? " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "'  OR agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "2') " : " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "') ") .
                    (($cluster_id > 0)?" AND (agent_cluster_id='$cluster_id') ":'') .
                    " AND `is_paid` IN('yes','roustedcc') " .
                    $extra_sql;//" AND `call_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."'";

                //   echo $sql."\n";

                list($paid_sales_amount) = $_SESSION['dbapi']->ROqueryROW($sql);

                $xfer_where = "WHERE xfer_time BETWEEN '$stime' AND '$etime' " . //	" AND `account_id`='".$_SESSION['account']['id']."' ".
                    //						" AND (agent_username='".mysqli_real_escape_string($_SESSION['db'],$username)."' )".

                    (($combine_users) ? " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "'  OR agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "2') " : " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "') ") .
                    $extra_sql .
                    (($cluster_id > 0)?" AND (agent_cluster_id='$cluster_id') ":'') .

                    //						(($source_cluster_id > 0)?" AND agent_cluster_id='$source_cluster_id' ":'').
                    //						(($ignore_source_cluster_id > 0)?" AND agent_cluster_id != '$ignore_source_cluster_id' ":'')
                    "";

                $lead_where = "WHERE time BETWEEN '$stime' AND '$etime' " .
                    (($combine_users) ? " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "'  OR agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "2') " : " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "') ") .
                    (($cluster_id > 0)?" AND (vici_cluster_id='$cluster_id') ":''). ///." AND (vici_cluster_id='$cluster_id') " .
                    $user_group_sql .
                    "";

                ## GET ALL TRANSFERS FOR THE AGENT/TIMEFRAME
                //			$sql = "SELECT COUNT(`id`) FROM `transfers` ".
                //						$xfer_where;
                //			list($call_cnt) = queryROW($sql);

                ## ROUSTERS HAVE TO BASE IT OFF LEAD TRACKING INSTEAD

                ## GET TOTAL CALL COUNT
                $sql = "SELECT COUNT(`id`) FROM `lead_tracking` " . $lead_where;
                list($call_cnt) = $_SESSION['dbapi']->ROqueryROW($sql);

                ## HANGUPS - OUT OF ALL THE TRANSFERS, HOW MANY WHERE HANGUPS
                $sql = "SELECT COUNT(`id`) FROM `lead_tracking` " . $lead_where . " AND dispo='hangup' ";
                list($hangup_cnt) = $_SESSION['dbapi']->ROqueryROW($sql);


                ## GET TOTAL DISPO COUNT FOR USE IN CONTACT %
                $sql = "SELECT COUNT(`id`) FROM `lead_tracking` " . $lead_where . " AND (dispo IN('NI', 'VOID', 'DNC', 'SALE', 'PAIDCC', 'SALECC')) ";
                list($contact_cnt) = $_SESSION['dbapi']->ROqueryROW($sql);

                ## DECLINES - OUT OF ALL THE TRANSFERS, HOW MANY WHERE DECLINED
                $sql = "SELECT COUNT(`id`) FROM `lead_tracking` " . $lead_where . " AND dispo='DEC' ";
                list($decline_cnt) = $_SESSION['dbapi']->ROqueryROW($sql);

                ## ANSWERING MACHINE COUNT - HOW MANY ANSWERING MACHINE TRANSFERS?
                $sql = "SELECT COUNT(`id`) FROM `lead_tracking` " . $lead_where . " AND (`dispo`='A' OR `dispo`='a') ";
                list($ans_cnt) = $_SESSION['dbapi']->ROqueryROW($sql);

                ## CALLS/HR COUNT - FORMULA PULLED FROM SALES ANALYSIS REPORT
                $calls_hour_where = "WHERE time_started BETWEEN '$stime' AND '$etime' " .

                    (($combine_users) ? " AND (username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "'  OR username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "2') " : " AND (username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "') ") . //" AND (vici_cluster_id='$cluster_id') ".
                    "";

                $sql = "SELECT SUM(activity_time),SUM(calls_today) FROM `activity_log` " . $calls_hour_where;
                list($activity_wrkd, $activity_num_calls) = $_SESSION['dbapi']->ROqueryROW($sql);

                $activity_worked = $activity_wrkd / 60;
                $worked_calls_hr = ($activity_worked <= 0) ? 0 : (($activity_num_calls) / $activity_worked);

                //			$sql = "SELECT COUNT(`id`) FROM `transfers` ".
                //						$xfer_where.
                //						" AND verifier_dispo='DEC' ";
                //			list($decline_cnt) = queryROW($sql);

                //			$sql = "SELECT SUM(amount) FROM `lead_tracking` ".
                //						$lead_where.
                //						" AND (dispo IN('PAIDCC',  ) ";
                //			list($agent_amount_total, $verifier_amount_total) = queryROW($sql);

                $sql = "SELECT SUM(agent_amount),SUM(verifier_amount) FROM `transfers` " . $xfer_where . " AND (verifier_dispo IN('PAIDCC','SALECC')) "; //verifier_dispo='SALE' OR
                list($agent_amount_total, $verifier_amount_total) = $_SESSION['dbapi']->ROqueryROW($sql);

                $sql = "SELECT SUM(agent_amount),SUM(verifier_amount) FROM `transfers` " . $xfer_where . " AND verifier_amount > agent_amount " . " AND (verifier_dispo IN('PAIDCC','SALECC')) "; //verifier_dispo='SALE' OR
                list($positive_agent_amount_total, $positive_verifier_amount_total) = $_SESSION['dbapi']->ROqueryROW($sql);

                $sql = "SELECT COUNT(id) FROM `transfers` " . $xfer_where . " AND (verifier_dispo IN('PAIDCC','SALECC')) " . " AND verifier_amount > agent_amount ";

                list($bump_count) = $_SESSION['dbapi']->ROqueryROW($sql);

                list($agent_array[$username]['reviewcnt']) = $_SESSION['dbapi']->ROqueryROW("SELECT COUNT(`id`) FROM `dispo_log` " . " WHERE `dispo` = 'REVIEW' " .

                    (($combine_users) ? " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "'  OR agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "2') " : " AND (agent_username='" . mysqli_real_escape_string($_SESSION['db'], $username) . "') ") .

                    //`agent_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$row['username'])."' ".
                    //			" AND `account_id`='".$_SESSION['account']['id']."' ".
                    " AND `micro_time` BETWEEN '$stmicro' AND '$etmicro' " . " AND `result`='success' ");


                $agent_array[$username]['sale_cnt'] = $cnt;
                $agent_array[$username]['paid_sale_total'] = $paid_sales_amount;
                $agent_array[$username]['paid_sale_cnt'] = $paid_sales_cnt;
                $agent_array[$username]['call_cnt'] = $call_cnt;
                $agent_array[$username]['hangup_cnt'] = $hangup_cnt;
                $agent_array[$username]['contact_cnt'] = $contact_cnt;
                $agent_array[$username]['decline_cnt'] = $decline_cnt;
                $agent_array[$username]['ans_cnt'] = $ans_cnt;
                $agent_array[$username]['ans_percent'] = ($call_cnt <= 0) ? 0 : round(($ans_cnt / $call_cnt) * 100, 2);
                $agent_array[$username]['worked_calls_hr'] = $worked_calls_hr;

                //			$agent_array[$username]['total_amount'] = '';

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


                // CONVERSION PERCENTAGE MATH
                //basically (PAIDCC+SALECC)/(VOID,NI,DNC)
                $agent_array[$username]['conversion_percent'] = 0;
                try {

                    //echo $username." Calculating Conversion %... ";

                    //echo "Sales=$cnt Contacts=$contact_cnt\n";

                    $doodooheads = ($contact_cnt - $cnt);
                    $agent_array[$username]['conversion_percent'] = ($doodooheads > 0) ? round(floatval(($cnt / $doodooheads) * 100), 2) : 0;


                    //$agent_array[$username]['conversion_percent'] = ($agent_array[$username]['conversion_percent'] < )

                } catch (Exception $ex) {
                }


                $agent_array[$username]['conversion_percent'] = (floatval($agent_array[$username]['conversion_percent']) <= 0 || $agent_array[$username]['conversion_percent'] == 'NaN') ? 0 : $agent_array[$username]['conversion_percent'];

                //echo "Sales=$cnt Contacts=$contact_cnt Result: ".$agent_array[$username]['conversion_percent']." \n";


            }

        }

        // GET THE HOURS FROM VICI CLUSTER NOW
        /// RESOLVE DB IDX FROM CLUSTER ID
        $vici_idx = getClusterIndex($cluster_id);

        // CONNECT VICI CLUSTER BY IDX
        connectViciDB($vici_idx);

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

            $sql = "SELECT * FROM `vicidial_agent_log_archive` " . " WHERE `dispo_epoch` BETWEEN '$stime' AND '$etime' " .//`user` = '".mysqli_real_escape_string($_SESSION['db'],$username)."' ".

                (($combine_users) ? " AND (`user`='" . mysqli_real_escape_string($_SESSION['db'], $username) . "'  OR `user`='" . mysqli_real_escape_string($_SESSION['db'], $username) . "2') " : " AND (`user`='" . mysqli_real_escape_string($_SESSION['db'], $username) . "') ") .

                //  (($call_group != null)?" AND `user_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."' ":'').

                (($call_group != NULL) ? $user_group_sql : '') .

//			      " AND  ".
                " UNION " . "SELECT * FROM `vicidial_agent_log` " . " WHERE `dispo_epoch` BETWEEN '$stime' AND '$etime' " . (($combine_users) ? " AND (`user`='" . mysqli_real_escape_string($_SESSION['db'], $username) . "'  OR `user`='" . mysqli_real_escape_string($_SESSION['db'], $username) . "2') " : " AND (`user`='" . mysqli_real_escape_string($_SESSION['db'], $username) . "') ") . //`user` = '".mysqli_real_escape_string($_SESSION['db'],$username)."' ".
                //(($call_group != null)?" AND `user_group`='".mysqli_real_escape_string($_SESSION['db'],$call_group)."' ":'').
                (($call_group != NULL) ? $user_group_sql : '') . "";

            $res = query($sql, 1);

            $t_max = 0;
            $t_max2 = 0;
            while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {

                $tmpttime = ($row['pause_sec'] + $row['talk_sec'] + $row['dead_sec'] + $row['dispo_sec'] + $row['wait_sec']);

                $t_time += $tmpttime;

                if ($use_team) {
                    if (!in_array($tmp, $sql_user_team_list)) {
                        //                        echo "Skipping " . $tmp . " --> not in selected team." . PHP_EOL;
                        continue;
                    }
                }
                if ($combine_users) {

                    // FIRST USER
                    if (strtolower(trim($row['user'])) == strtolower(trim($username))) {
                        $t_max += $tmpttime;
                    } else {
                        $t_max2 += $tmpttime;
                    }

                } else {
                    $t_max += $tmpttime;
                }

//				if($t_max <= 0){
//					$t_max = $tmpttime;
//				}else{
//					if($tmpttime > $t_max){
//						$t_max = $tmpttime;
//					}
//				}

                $t_pause += $row['pause_sec'];
                $t_talk += $row['talk_sec'];
                $t_dead += $row['dead_sec'];

                $t_call_count++;
            }

            $out[$x] = array();
            $out[$x]['username'] = $username;
            $out[$x]['agent'] = $agent;
            $out[$x]['sale_cnt'] = $agent['sale_cnt'];

            $out[$x]['paid_sale_cnt'] = $agent['paid_sale_cnt'];
            $out[$x]['paid_sale_total'] = $agent['paid_sale_total'];

            $out[$x]['conversion_percent'] = $agent['conversion_percent'];

            $out[$x]['call_cnt'] = $agent['call_cnt'];
            $out[$x]['hangup_cnt'] = $agent['hangup_cnt'];
            $out[$x]['contact_cnt'] = $agent['contact_cnt'];
            $out[$x]['decline_cnt'] = $agent['decline_cnt'];
            $out[$x]['ans_cnt'] = $agent['ans_cnt'];
            $out[$x]['ans_percent'] = $agent['ans_percent'];

            $out[$x]['worked_calls_hr'] = $agent['worked_calls_hr'];

            $out[$x]['paid_time'] = ($agent['paid_time'] + $agent['paid_corrections']);

            // THE LARGER OF THE 2 RECORDS FOR TALK TIME, WHEN COMBINING HANDS
            $out[$x]['t_time_max'] = ($t_max > $t_max2) ? $t_max : $t_max2;

            $out[$x]['t_time'] = $t_time;
            $out[$x]['t_pause'] = $t_pause;
            $out[$x]['t_talk'] = $t_talk;
            $out[$x]['t_dead'] = $t_dead;
            $out[$x]['t_call_count'] = $t_call_count;

            $x++;
        }
        
        
        
        
        
        
        $running_total_calls = 0;
        $running_total_sales = 0;
        $running_total_hangups = 0;
        $running_total_declines = 0;
        $running_total_ans = 0;
        $running_total_contacts = 0;
        $running_total_reviews = 0;
        $running_total_paid_sales = 0;
        $running_total_activity_time = 0;
        $running_total_total_time = 0;
        
        $running_total_convert_percent = 0;
        
        $running_total_bumps = 0;
        $running_total_pos_bump_agent_amount = 0;
        $running_total_pos_bump_verifier_amount = 0;
        
        $running_total_incall_time = 0;
        $running_paid_time = 0;
        $running_t_max = 0;
        $running_t_time = 0;
        $running_t_dead = 0;
        $running_total_talk_time = 0;
        $running_total_paid_sales = 0;
        $running_total_paid_sales_amount = 0;
        
        $running_total_paid_avg_per_hour = 0;
        $running_total_worked_avg_per_hour = 0;
        
        $running_total_calls_per_hour = 0;
        
        foreach ($out as $row) {
        	
        	//print_r($row);
        	
        	## RUN CALCULATIONS ON REPORT DATA BEFORE ADDING TO REPORT DATA ARRAY
        	
        	## ACTIVITY AND TOTAL ACTIVITY TIME
        	$activity_time = 0;
        	$act_total_time = 0;
        	
        	foreach ($row['agent']['total_activity_date_time_array'] as $tdate => $ttime) {
        		$activity_time += $ttime;
        	}
        	
        	foreach ($row['agent']['total_activity_date_daily_array'] as $tdate => $ttime) {
        		$act_total_time += $ttime;
        	}
        	
        	## PAIDCC PER HOUR
        	//$paidcc_per_hour = ($row['paid_time'] <= 0)?0:($row['paid_sale_cnt'] / ($row['paid_time']/60));//($row['t_time'] / 3600);
        	$paidcc_per_hour = ($row['paid_time'] <= 0) ? 0 : ($row['paid_sale_total'] / ($row['paid_time'] / 60));//($row['t_time'] / 3600);
        	
        	## PAIDCC PER WORKED HOUR
        	// I feel like we're going back and forth here, so I'm marking the request/date/time
        	// NICOLE: can we make it use activity for worked/hr (8/13/2019)
        	$paidcc_per_worked_hour = ($act_total_time <= 0) ? 0 : ($row['paid_sale_total'] / ($activity_time / 3600));
        	//$paidcc_per_worked_hour = ($act_total_time <= 0)?0:($row['paid_sale_total'] / ($act_total_time/3600));
        	

        	
        	## TOTAL INCALL TIME
        	$incall_time = $row['agent']['seconds_INCALL'];


        	//echo $row['contact_cnt'].' vs sales:'.$row['sale_cnt'].'<br />';


        	## TOTAL TALK TIME
        	$tmptalktime = intval($row['t_talk']);

        	
        	## CONTACT %
//         	$contact_percent = ($row['call_cnt'] <= 0) ? 0 : number_format(round((($row['contact_cnt']) / ($row['call_cnt'])) * 100, 2), 2);
        	$running_total_contacts += $row['contact_cnt'];
        	
        	$running_total_calls_per_hour += $row['worked_calls_hr'];
        	
        	if ($combine_users) {
        		
        		$row['t_time'] = $row['t_time_max'];
        		
        	}
        	
        	$running_total_total_time += $act_total_time;
        	
        	$running_total_activity_time += $activity_time;
        	
        	$running_total_incall_time += $incall_time;
        	
        	$running_t_dead += $row['t_dead'];
        	
        	$running_total_talk_time += $tmptalktime;
        	
//         	$close_percent = ($row['call_cnt'] <= 0) ? 0 : number_format(round((($row['sale_cnt']) / ($row['call_cnt'])) * 100, 2), 2);
        	
//         	$adjusted_close_percent = (($row['call_cnt'] - $row['hangup_cnt']) <= 0) ? 0 : number_format(round((($row['sale_cnt']) / ($row['call_cnt'] - $row['hangup_cnt'])) * 100, 2), 2);
        	
//         	$hangup_percent = ($row['call_cnt'] <= 0) ? 0 : number_format(round((($row['hangup_cnt']) / ($row['call_cnt'])) * 100, 2), 2);
        	
        	$reviewcnt = $row['reviewcnt'];
        	
        	$running_total_calls += $row['call_cnt'];
        	$running_total_ans += $row['ans_cnt'];
        	$running_total_sales += ($row['sale_cnt']);
        	$running_total_paid_sales += $row['paid_sale_cnt'];
        	$running_total_reviews += $reviewcnt;
        	
        	//$running_total_convert_percent += $row['conversion_percent'];
        	
        	$running_paid_time += $row['paid_time'];
        	
//         	$percent_paidcc_calls = ($row['sale_cnt'] <= 0) ? 0 : ($row['paid_sale_cnt'] / $row['sale_cnt']) * 100;
        	
        	$running_total_paid_sales_amount += $row['paid_sale_total'];
        	
        	$running_t_time += $row['t_time'];
        	
        	$running_t_max += $row['t_time_max'];
        	
        	$running_total_paid_avg_per_hour += $paidcc_per_hour;
        	$running_total_worked_avg_per_hour += $paidcc_per_worked_hour;
        	
        	$running_total_bumps += $row['agent']['bump_count'];
        	
        	$running_total_pos_bump_agent_amount += $row['agent']['positive_agent_amount_total'];
        	$running_total_pos_bump_verifier_amount += $row['agent']['positive_verifier_amount_total'];
        	
        	
        }
        
        //echo $running_total_contacts.' vs sales:'.$running_total_sales.'<br />';
        
        $doodooheads = ($running_total_contacts - $running_total_sales);
        $t_conversion_percent = ($doodooheads > 0) ? round(floatval(($running_total_sales / $doodooheads) * 100), 2) : 0;
        
        
        $totals = array(
        		
        		'total_calls'				=> $running_total_calls,
        		'total_sales'				=> $running_total_sales,
        		'total_hangups'				=> $running_total_hangups,
        		'total_declines'			=> $running_total_declines,
        		'total_ans'					=> $running_total_ans,
        		'total_contacts'			=> $running_total_contacts,
        		'total_reviews'				=> $running_total_reviews,
        		'total_paid_sales'			=> $running_total_paid_sales,
        		'total_paid_sales_amount'	=> $running_total_paid_sales_amount,
        		'total_activity_time'		=> $running_total_activity_time,
        		'total_total_time'			=> $running_total_total_time,
        		
        		'total_incall_time'			=> $running_total_incall_time,
        		
        		'total_convert_percent'		=> $t_conversion_percent,//round( (($running_total_convert_percent / count($out))*100),2),
        		
        		'total_bumps'				=> $running_total_bumps,
        		'total_pos_bump_agent_amount'	=> $running_total_pos_bump_agent_amount,
        		'total_pos_bump_verifier_amount'=> $running_total_pos_bump_verifier_amount,
        		
        		'paid_time'	=> $running_paid_time,
        		't_max'		=> $running_t_max,
        		't_time'	=> $running_t_time,
        		't_dead'	=> $running_t_dead,
        		'total_talk_time'	=> $running_total_talk_time,
        		'total_paid_sales'	=> $running_total_paid_sales,
        		
        		'total_paid_avg_per_hour'	=> $running_total_paid_avg_per_hour ,
        		'total_worked_avg_per_hour'	=> $running_total_worked_avg_per_hour,
        		
        		'total_calls_per_hour'		=> $running_total_calls_per_hour
        		
        		
        );
        
        
        
        
        
        
        
		// MODIFIED TO RETURN TOTALS ARRAY TOO
        return array($out, $totals);
    }

    function makeHTMLReport($stime, $etime, $cluster_id, $user_team_id, $user_group, $ignore_users, $source_cluster_id = 0, $ignore_source_cluster_id = 0, $source_user_group = NULL, $combine_users = false)
    {

        list($data, $totals) = $this->generateData($cluster_id, $user_team_id, $stime, $etime, $user_group, false, $ignore_users, $source_cluster_id, $ignore_source_cluster_id, $source_user_group, $combine_users);

        if (count($data) <= 0){
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
        <a name="anc_rouster_report">
            <table border="0" width="100%">
                <tr>
                    <td style="border-bottom:1px solid #000;font-size:18px;font-weight:bold">

                        <br/>

                        Rouster Call Status Report - <?= date("m/d/Y", $stime) ?> - <?= htmlentities(($user_group == NULL || $user_group[0] == '') ? "All Groups" : "Selected Group" . ((count($user_group) > 1) ? "s" : " : " . ((is_array($user_group)) ? $user_group[0] : $user_group))) ?>

                    </td>
                </tr>
                <tr>
                    <th height="25" align="left" style="font-size:14px;font-weight:bold">
                        <?
                        echo $user_group_str . '<br />';
                        echo '<i>Generated on: ' . date("g:ia m/d/Y") . '</i>';
                        ?>
                    </th>
                </tr>
                <tr>
                    <td>
                        <table id="verifier_report_table" border="0" width="900" class="table table-bordered table-striped table-vcenter js-dataTable-full" style="color:#000">
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
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">A</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">%Ans</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Calls/hr</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Contact%</th>
                                <?/*<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Sales</th>*/ ?>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">PaidCC</th>
                                <?
                                /**<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">%PaidCC</th>**/ ?>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Paid/$Hour</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Worked/$Hour</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">$PaidCC</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Convert%</th>
                                <?
                                /**<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Hangups</th>
                                 * <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Declines</th>**/ ?>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Activity</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">In Call</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Time</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Paid Time</th>

                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Pause</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Talk Avg</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Dead</th>

                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Bump $</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right">Bump %</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right"># of Bumps</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right" title="Positive Bump Amount">Pos.Bump $</th>
                                <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="right" title="Positive Bump Percent">Pos.Bump %</th>

                            </tr>
                            </thead>
                            <tbody><?

                            $stmicro = $stime * 1000;
                            $etmicro = $etime * 1000;

                            $running_total_calls = 0;
                            $running_total_sales = 0;
                            $running_total_hangups = 0;
                            $running_total_declines = 0;
                            $running_total_ans = 0;
                            $running_total_contacts = 0;
                            $running_total_reviews = 0;
                            $running_total_paid_sales = 0;
                            $running_total_activity_time = 0;
                            $running_total_total_time = 0;

                            $running_total_convert_percent = 0;

                            $running_total_bumps = 0;
                            $running_total_pos_bump_agent_amount = 0;
                            $running_total_pos_bump_verifier_amount = 0;

                            $running_paid_time = 0;
                            $running_t_max = 0;
                            $running_t_time = 0;
                            $running_t_dead = 0;
                            $running_total_talk_time = 0;
                            $running_total_paid_sales = 0;

                            $running_total_paid_avg_per_hour = 0;
                            $running_total_worked_avg_per_hour = 0;

                            $running_total_calls_per_hour = 0;
                            
                            $tcount = 0;
                            $x1 = 0;

                            ## REPORT DATA ARRAY HANDLING
                            ## THIS WAS CREATED SO WE CAN SORT THE DATA BEFORE IT GOES INTO THE DATATABLE
                            ## USE $report_order_field FOR DEFAULT SORT FIELD AND $report_order_dir FOR DIRECTION (ASC/DESC)

                            ## CREATE NEW ARRAY TO USE FOR REPORT DATA
                            $report_data = array();

                            ## LOOP THROUGH GENERATED REPORT DATA AS USUAL BUT PUT DATATABLE VALUES INTO AN ARRAY
                            foreach ($data as $row) {

                                ## RUN CALCULATIONS ON REPORT DATA BEFORE ADDING TO REPORT DATA ARRAY

                                ## ACTIVITY AND TOTAL ACTIVITY TIME
                                $activity_time = 0;
                                $act_total_time = 0;

                                foreach ($row['agent']['total_activity_date_time_array'] as $tdate => $ttime) {
                                	
                                	// TAKE THE LARGER OF THE 2
                                	//if($ttime > $activity_time){
                                	//	$activity_time  = $ttime;
                                	//}
                                    $activity_time += $ttime;
                                    
                                }

                                foreach ($row['agent']['total_activity_date_daily_array'] as $tdate => $ttime) {
                                    $act_total_time += $ttime;
                                }

                                ## PAIDCC PER HOUR
                                //$paidcc_per_hour = ($row['paid_time'] <= 0)?0:($row['paid_sale_cnt'] / ($row['paid_time']/60));//($row['t_time'] / 3600);
                                $paidcc_per_hour = ($row['paid_time'] <= 0) ? 0 : ($row['paid_sale_total'] / ($row['paid_time'] / 60));//($row['t_time'] / 3600);

                                ## PAIDCC PER WORKED HOUR
                                // I feel like we're going back and forth here, so I'm marking the request/date/time
                                // NICOLE: can we make it use activity for worked/hr (8/13/2019)
                                $paidcc_per_worked_hour = ($act_total_time <= 0) ? 0 : ($row['paid_sale_total'] / ($activity_time / 3600));
                                //$paidcc_per_worked_hour = ($act_total_time <= 0)?0:($row['paid_sale_total'] / ($act_total_time/3600));

                                ## TOTAL ACTIVITY TIME
                                $tmphours = floor($activity_time / 3600);
                                $tmpmin = floor(($activity_time - ($tmphours * 3600)) / 60);
                                $total_activity_time = $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                ## TOTAL INCALL TIME
                                $incall_time = $row['agent']['seconds_INCALL'];
                                $tmphours = floor($incall_time / 3600);
                                $tmpmin = floor(($incall_time - ($tmphours * 3600)) / 60);
                                $total_incall_time = $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                ## TOTAL TIME
                                $tmphours = floor($act_total_time / 3600);
                                $tmpmin = floor(($act_total_time - ($tmphours * 3600)) / 60);
                                $total_time = $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                ## PAID TIME
                                $ptime = ($row['paid_time']);
                                $tmpmin = floor($ptime / 60);
                                $tmpsec = ($ptime % 60);
                                $total_ptime = $tmpmin . ':' . (($tmpsec < 10) ? '0' . $tmpsec : $tmpsec);

                                ## TOTAL PAUSE TIME
                                $tmpmin = floor($row['t_pause'] / 60);
                                $tmpsec = ($row['t_pause'] % 60);
                                $total_pause = $tmpmin . ':' . (($tmpsec < 10) ? '0' . $tmpsec : $tmpsec);

                                ## TOTAL TALK TIME
                                $tmptalktime = intval($row['t_talk']);
                                $talktimeavg = ($row['call_cnt'] <= 0) ? 0 : ($tmptalktime / intval($row['call_cnt']));
                                $total_talk = renderTimeFormatted($talktimeavg);

                                ## TOTAL DEAD TIME
                                $total_dead = renderTimeFormatted($row['t_dead']);

                                ## CONTACT %
                                $contact_percent = ($row['call_cnt'] <= 0) ? 0 : number_format(round((($row['contact_cnt']) / ($row['call_cnt'])) * 100, 2), 2);
                                $running_total_contacts += $row['contact_cnt'];
                                
                                $running_total_calls_per_hour += $row['worked_calls_hr'];
                                
                                ## ADD AGENT REPORT DATA TO REPORT DATA ARRAY
                                $report_data[$x1]['agent_username'] = $row['username'];
                                $report_data[$x1]['call_cnt'] = $row['call_cnt'];
                                $report_data[$x1]['ans_cnt'] = $row['ans_cnt'];
                                $report_data[$x1]['ans_percent'] = $row['ans_percent'];
                                $report_data[$x1]['worked_calls_hr'] = $row['worked_calls_hr'];
                                $report_data[$x1]['contact_percent'] = $contact_percent;
                                $report_data[$x1]['conversion_percent'] = $row['conversion_percent'];
                                $report_data[$x1]['paid_sale_cnt'] = $row['paid_sale_cnt'];
                                $report_data[$x1]['paidcc_per_hour'] = $paidcc_per_hour;
                                $report_data[$x1]['paidcc_per_worked_hour'] = $paidcc_per_worked_hour;
                                $report_data[$x1]['paid_sale_total'] = $row['paid_sale_total'];
                                $report_data[$x1]['total_activity_time'] = $total_activity_time;
                                $report_data[$x1]['total_incall_time'] = $total_incall_time;
                                $report_data[$x1]['total_time'] = $total_time;
                                $report_data[$x1]['total_ptime'] = $total_ptime;
                                $report_data[$x1]['total_pause'] = $total_pause;
                                $report_data[$x1]['total_talk'] = $total_talk;
                                $report_data[$x1]['total_dead'] = $total_dead;
                                $report_data[$x1]['agent_bump_amount'] = $row['agent']['bump_amount'];
                                $report_data[$x1]['agent_bump_percent'] = $row['agent']['bump_percent'];
                                $report_data[$x1]['agent_bump_count'] = $row['agent']['bump_count'];
                                $report_data[$x1]['agent_pos_bump_amount'] = $row['agent']['pos_bump_amount'];
                                $report_data[$x1]['agent_pos_bump_percent'] = $row['agent']['pos_bump_percent'];

                                if ($combine_users) {

                                    $row['t_time'] = $row['t_time_max'];

                                }

//                                 $running_total_total_time += $act_total_time;

//                                 $running_total_activity_time += $activity_time;

//                                 $running_total_incall_time += $incall_time;

//                                 $running_t_dead += $row['t_dead'];

//                                 $running_total_talk_time += $tmptalktime;

//                                 $close_percent = ($row['call_cnt'] <= 0) ? 0 : number_format(round((($row['sale_cnt']) / ($row['call_cnt'])) * 100, 2), 2);

//                                 $adjusted_close_percent = (($row['call_cnt'] - $row['hangup_cnt']) <= 0) ? 0 : number_format(round((($row['sale_cnt']) / ($row['call_cnt'] - $row['hangup_cnt'])) * 100, 2), 2);

//                                 $hangup_percent = ($row['call_cnt'] <= 0) ? 0 : number_format(round((($row['hangup_cnt']) / ($row['call_cnt'])) * 100, 2), 2);

//                                 $reviewcnt = $row['reviewcnt'];

//                                 $running_total_calls += $row['call_cnt'];
//                                 $running_total_ans += $row['ans_cnt'];
//                                 $running_total_sales += ($row['sale_cnt']);
//                                 $running_total_paid_sales += $row['paid_sale_cnt'];
//                                 $running_total_reviews += $reviewcnt;

//                                 $running_total_convert_percent += $row['conversion_percent'];

//                                 $running_paid_time += $row['paid_time'];

//                                 $percent_paidcc_calls = ($row['sale_cnt'] <= 0) ? 0 : ($row['paid_sale_cnt'] / $row['sale_cnt']) * 100;

//                                 $running_total_paid_sales_amount += $row['paid_sale_total'];

//                                 $running_t_time += $row['t_time'];

//                                 $running_t_max += $row['t_time_max'];

//                                 $running_total_paid_avg_per_hour += $paidcc_per_hour;
//                                 $running_total_worked_avg_per_hour += $paidcc_per_worked_hour;

//                                 $running_total_bumps += $row['agent']['bump_count'];

//                                 $running_total_pos_bump_agent_amount += $row['agent']['positive_agent_amount_total'];
//                                 $running_total_pos_bump_verifier_amount += $row['agent']['positive_verifier_amount_total'];

                                $tcount++;
                                $x1++;

                            }

                            ## REPORT DATA ARRAY SORT HANDLING
                            ## TAKE $report_order_field AND $report_order_dir AS OPTIONS TO SORT THE REPORT DATA ARRAY

                            # SORT BASED ON CLASS VARIABLE FOR ORDER DIRECTION
                            switch ($this->report_order_dir) {

                                # DEFAULT TO DESCENDING (LARGE TO SMALL)
                                default:
                                case "DESC":

                                    # RUN THE SORT WITH CLASS VARIABLE FOR ARRAY ORDER FIELD
                                    usort($report_data, function ($item1, $item2) {
                                        return $item2[$this->report_order_field] <=> $item1[$this->report_order_field];
                                    });
                                    break;

                                # ASCENDING (SMALL TO LARGE)
                                case "ASC":

                                    # RUN THE SORT WITH CLASS VARIABLE FOR ARRAY ORDER FIELD
                                    usort($report_data, function ($item1, $item2) {
                                        return $item1[$this->report_order_field] <=> $item2[$this->report_order_field];
                                    });
                                    break;

                            }

                            ## OLD COMMENTED OUT CODE JUST INCASE WE MAY NEED IT LATER
                            ## THESE WERE IN THE FORLOOP ABOVE FOR VARIOUS ROW CALCULATIONS

                            //				$row['agent']['total_activity_date_time_array'][date("m/d/Y", $row['time_started'])]
                            //				$row['agent']['total_activity_date_daily_array'][date("m/d/Y", $row['time_started'])]

                            // 				$tmphours = floor($row['agent']['total_activity_time'] / 3600);
                            //				$tmpmin = floor( ($row['agent']['total_activity_time'] - ($tmphours * 3600)) / 60 );

                            //				$tmphours = floor($row['t_time'] / 3600);
                            //				$tmpmin = floor( ($row['t_time'] - ($tmphours * 3600)) / 60 );

                            //				$close_percent = number_format( round( (($row['sale_cnt']) / ($row['t_call_count'])) * 100, 2), 2);

                            //				list($reviewcnt) = $_SESSION['dbapi']->queryROW("SELECT COUNT(`id`) FROM `dispo_log` ".
                            //										" WHERE `agent_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$row['username'])."' ".
                            //										" AND `micro_time` BETWEEN '$stmicro' AND '$etmicro' ".
                            //										" AND `dispo` = 'REVIEW' ".
                            //										" AND `result`='success' ");

                            //				$running_total_hangups += $row['hangup_cnt'];
                            //				$running_total_declines += $row['decline_cnt'];

                            // 				echo $paidcc_per_hour.' vs '.$paidcc_per_worked_hour."<br />";
                            // 				echo 'Paid total: '.$row['paid_sale_total'].'<br />';
                            // 				echo ($row['paid_time']/60).' vs '.($act_total_time/3600).'<br /><br />';

                            //				if($combine_users){
                            //
                            //					$paidcc_per_worked_hour = ($activity_time <= 0)?0:($row['paid_sale_total'] / (($row['t_time']/intval($row['agent']['agent_count']))/3600));
                            //
                            //				}else{
                            //
                            //					$paidcc_per_worked_hour = ($activity_time <= 0)?0:($row['paid_sale_total'] / ($row['t_time']/3600));
                            //
                            //				}

                            ## OLD COMMENTED OUT CODE JUST INCASE WE MAY NEED IT LATER
                            ## THESE WERE IN THE TABLE BELOW FOR DIFFERENT COLUMNS/OUTPUTS

                            /*<td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?=number_format(($row['sale_cnt']-$row['paid_sale_cnt']))?></td>*/ /*<td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?=number_format($percent_paidcc_calls)?> %</td>*/ /**
                             * <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?=number_format($row['hangup_cnt'])?></td>
                             * <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?=number_format($row['decline_cnt'])?></td>
                             */

                            //						if($row['t_time'] >= $this->time_limit){
                            //						}else{
                            //							echo '<span style="background-color:yellow">'.$total_time.'</span>';
                            //
                            //
                            //						}

                            //						if($row['t_pause'] <= $this->pause_limit){
                            //						}else{

                            //							echo '<span style="background-color:yellow">'.$total_pause.'</span>';
                            //						}

                            //echo $talktimeavg.' vs '.$this->talk_lower_limit.' ';

                            //						if($talktimeavg >= $this->talk_lower_limit && $talktimeavg <= $this->talk_upper_limit){

                            //						}else{

                            //							echo '<span style="background-color:yellow">'.$total_talk.'</span>';
                            //						}

                            //						if($row['t_dead'] > $this->dead_time_limit){
                            //
                            //							echo '<span style="background-color:yellow">'.$total_dead.'</span>';
                            //						}else{
                            //						}

                            /**
                             * <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?
                             *
                             * //                        if(intval($close_percent) >= $this->close_percent_limit){
                             *
                             * echo '<span style="background-color:transparent">'.$close_percent.'%</span>';
                             * //                        }else{
                             *
                             * //                            echo '<span style="background-color:yellow">'.$close_percent.'%</span>';
                             *
                             * //                        }
                             *
                             * ?></td>
                             * <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?
                             *
                             * //                        if(intval($adjusted_close_percent) >= $this->close_percent_limit){
                             *
                             * echo '<span style="background-color:transparent">'.$adjusted_close_percent.'%</span>';
                             *
                             * //                        }else{
                             * //                            echo '<span style="background-color:yellow">'.$adjusted_close_percent.'%</span>';
                             * //                        }
                             *
                             * ?></td>
                             *
                             * <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?
                             *
                             * //if(intval($adjusted_close_percent) >= $this->close_percent_limit){
                             *
                             * echo '<span style="background-color:transparent">'.$hangup_percent.'%</span>';
                             * //}else{
                             * //    echo '<span style="background-color:yellow">'.$adjusted_close_percent.'%</span>';
                             * //}
                             *
                             * ?></td>
                             *
                             * <td align="right"><?
                             *
                             * echo number_format($reviewcnt);
                             *
                             * ?></td>
                             **/

                            /*
                            $agent_array[$username]['positive_agent_amount_total'] = $positive_agent_amount_total;
                            $agent_array[$username]['positive_verifier_amount_total'] = $positive_verifier_amount_total;
                            $agent_array[$username]['pos_bump_amount'] = $positive_verifier_amount_total - $positive_agent_amount_total;
                            $agent_array[$username]['pos_bump_percent'] = ($positive_agent_amount_total <= 0)?0:round(($positive_verifier_amount_total / $positive_agent_amount_total) * 100, 2);
                            */

                            ##$tcount++;

                            ## LOOP THROUGH REPORT DATA ARRAY AND OUTPUT DATA FOR DATATABLE
                            ## NO NEED TO DO ANY CALCULATIONS, WE DID THOSE ABOVE!

                            foreach ($report_data as $report_data_row) {

                                ?>
                                <tr style="color:#000"><?

                                // CHECK FOR THIS, TO MAKE SURE ITS NOT THE EMAIL REPORT RUNNING
                                if ($_SESSION['user']['priv'] > 3) {

                                    ?>
                                    <td style="border-right:1px dotted #CCC;padding-right:3px">

                                    <a href="#" onclick="addUserToIgnore('<?= htmlentities(strtoupper($report_data_row['agent_username'])) ?>');return false;">[Ignore]</a>

                                    </td><?
                                }

                                ?>
                                <td style="border-right:1px dotted #CCC;padding-right:3px"><?= strtoupper($report_data_row['agent_username']) ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= number_format($report_data_row['call_cnt']) ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= number_format($report_data_row['ans_cnt']) ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                    echo number_format($report_data_row['ans_percent'], 2) . '%';

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= number_format($report_data_row['worked_calls_hr']) ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                    echo number_format($report_data_row['contact_percent'], 2) . '%';

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= number_format($report_data_row['paid_sale_cnt']) ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right">$<?= number_format($report_data_row['paidcc_per_hour'], 2) ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right">$<?= number_format($report_data_row['paidcc_per_worked_hour'], 2) ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right">$<?= number_format($report_data_row['paid_sale_total'], 2) ?></td>

                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= number_format($report_data_row['conversion_percent'], 2) ?>%</td>


                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= $report_data_row['total_activity_time'] ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= $report_data_row['total_incall_time'] ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                    echo '<span style="background-color:transparent">' . $report_data_row['total_time'] . '</span>';

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?= $report_data_row['total_ptime'] ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                    echo '<span style="background-color:transparent">' . $report_data_row['total_pause'] . '</span>';

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                    echo '<span style="background-color:transparent">' . $report_data_row['total_talk'] . '</span>';

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;padding-right:3px" align="right"><?

                                    echo '<span style="background-color:transparent">' . $report_data_row['total_dead'] . '</span>';

                                    ?></td>
                                <td align="right"><?

                                    echo '$' . number_format($report_data_row['agent_bump_amount']);

                                    ?></td>
                                <td align="right"><?

                                    echo number_format($report_data_row['agent_bump_percent'], 2) . '%';

                                    ?></td>
                                <td align="right"><?

                                    echo number_format($report_data_row['agent_bump_count']);

                                    ?></td>

                                <td align="right"><?

                                    echo '$' . number_format($report_data_row['agent_pos_bump_amount']);

                                    ?></td>
                                <td align="right"><?

                                    echo number_format($report_data_row['agent_pos_bump_percent'], 2) . '%';

                                    ?></td>
                                </tr><?

                            }

                            ?></tbody><?


                            $total_close_percent = (($totals['total_calls'] <= 0) ? 0 : number_format(round((($totals['total_sales']) / ($totals['total_calls'])) * 100, 2), 2));

                            $total_adj_close_percent = ((($totals['total_calls'] - $totals['total_hangups']) <= 0) ? 0 : number_format(round((($totals['total_sales']) / ($totals['total_calls'] - $totals['total_hangups'])) * 100, 2), 2));

                            $total_hangup_percent = (($totals['total_calls'] <= 0) ? 0 : number_format(round((($totals['total_hangups']) / ($totals['total_calls'])) * 100, 2), 2));

                            $total_contact_percent = (($totals['total_calls'] <= 0) ? 0 : number_format(round((($totals['total_contacts']) / ($totals['total_calls'])) * 100, 2), 2));

                            $total_ans_percent = (($totals['total_calls'] <= 0) ? 0 : number_format(round((($totals['total_ans']) / ($totals['total_calls'])) * 100, 2), 2));

                            $total_percent_paidcc_calls = (($totals['total_sales'] <= 0) ? 0 : ($totals['total_paid_sales'] / $totals['total_sales']) * 100);

                            $total_talk_time_avg = (($totals['total_calls'] <= 0) ? 0 : ($totals['total_talk_time'] / $totals['total_calls']));

                            $total_pos_bump_amount = $totals['total_pos_bump_verifier_amount'] - $totals['total_pos_bump_agent_amount'];
                            $total_pos_bump_percent = ($totals['total_pos_bump_agent_amount']<= 0) ? 0 : round(($totals['total_pos_bump_verifier_amount'] / $totals['total_pos_bump_agent_amount']) * 100, 2);

                            $total_paidcc_per_hour = ($totals['paid_time'] <= 0) ? 0 : ($totals['total_paid_sales_amount'] / ($totals['paid_time'] / 60));

                            // NICOLE: CAN WE USE ACTIVITY TIME (8/13/2019)
                            //			$total_paidcc_per_worked_hour = ($running_total_activity_time <= 0)?0:($running_total_paid_sales_amount / ($running_t_time/3600));
                            //			$total_paidcc_per_worked_hour = ($running_total_total_time <= 0)?0:($running_total_paid_sales_amount / ($running_total_total_time/3600));//($running_t_max/3600));
                            $total_paidcc_per_worked_hour = ($totals['total_activity_time'] <= 0) ? 0 : ($totals['total_paid_sales_amount'] / ($totals['total_activity_time'] / 3600));//($running_t_max/3600));


                            //$total_convert_percent = ($totals['total_convert_percent'] <= 0) ? 0 : round(($totals['total_convert_percent'] / count($report_data)), 2);
                            
                            $total_convert_percent = $totals['total_convert_percent'];
                            
                            // TOTALS ROW
                            ?>
                            <tfoot>
                            <tr><?
                                // CHECK FOR THIS, TO MAKE SURE ITS NOT THE EMAIL REPORT RUNNING

                                if ($_SESSION['user']['priv'] > 3) {

                                    ?><th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="left">Totals:</th>
                                    <th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="right" title="Total number of agents">Agents:<?
                                    
                                   		echo number_format(count($report_data));
                                    
                                    
                                    ?></th><?

                                } else {

                                    /**?><th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="left">Totals:</th><?**/
                                	
                                	?><th style="border-right:1px dotted #CCC;border-top:1px solid #000" align="right" title="Total number of agents">Agents:<?
                                	
                                	echo number_format(count($report_data));
                                	
                                	
                                	?></th><?

                                }

                                ?>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($totals['total_calls']) ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($totals['total_ans']) ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= $total_ans_percent ?>%</td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?=number_format($totals['total_calls_per_hour'])?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= $total_contact_percent ?>%</td>
                                <?/*<td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?=number_format(($running_total_sales-$running_total_paid_sales))?></td>*/ ?>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($totals['total_paid_sales']) ?></td>
                                <?
                                /**<td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?=number_format($total_percent_paidcc_calls)?>%</td>**/ ?>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right">$<?= number_format($total_paidcc_per_hour, 2) ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right">$<?= number_format($total_paidcc_per_worked_hour, 2) ?></td>


                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right">$<?= number_format($totals['total_paid_sales_amount'], 2) ?></td>

                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($total_convert_percent, 2) ?>%</td>
                                <?
                                /**
                                 * <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?=number_format($running_total_hangups)?></td>
                                 * <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?=number_format($running_total_declines)?></td>
                                 **/ ?>

                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?

                                	$tmphours = floor($totals['total_activity_time'] / 3600);
                               		$tmpmin = floor(($totals['total_activity_time'] - ($tmphours * 3600)) / 60);
                                    echo $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?

                                    //$running_total_incall_time
									$tmphours = floor($totals['total_incall_time'] / 3600);
									$tmpmin = floor(($totals['total_incall_time'] - ($tmphours * 3600)) / 60);
                                    echo $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"><?

                               		$tmphours = floor($totals['t_time'] / 3600);
                               		$tmpmin = floor(($totals['t_time'] - ($tmphours * 3600)) / 60);
                                    echo $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);

                                    //	echo renderTimeFormatted($running_t_time/60);

                                    ?></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right">
                                    <?

                                    // PAID TIME
                                    $ptime = ($totals['paid_time']);
                                    $tmpmin = floor($ptime / 60);
                                    $tmpsec = ($ptime % 60);
                                    $total_ptime = $tmpmin . ':' . (($tmpsec < 10) ? '0' . $tmpsec : $tmpsec);
                                    echo $total_ptime;

                                    //echo renderTimeFormatted($running_paid_time * 60);

                                    ?>
                                </td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000">&nbsp;</td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="right"></td>
                                <td style="border-right:1px dotted #CCC;border-top:1px solid #000">
                                    <? echo '<span style="background-color:transparent">' . renderTimeFormatted($totals['t_dead']) . '</span>'; ?>
                                </td>
                                <td style="border-top:1px solid #000;padding-right:3px" align="right">&nbsp;</td>
                                <td style="border-top:1px solid #000;padding-right:3px" align="right">&nbsp;</td>
                                <td style="border-top:1px solid #000;padding-right:3px" align="right"><?= number_format($totals['total_bumps']) ?></td>
                                <td style="border-top:1px solid #000;padding-right:3px" align="right">$<?= number_format($total_pos_bump_amount) ?></td>
                                <td style="border-top:1px solid #000;padding-right:3px" align="right"><?= $total_pos_bump_percent ?>%</td>
                            </tr>
                            </tfoot>
                        </table>
                    </td>
                </tr>


            </table>
        </a><?

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

    function makeReport()
    {

        if (isset($_REQUEST['generate_agent_stat_report'])) {
            if ($_REQUEST['timeFilter']) {
                $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " " . $_REQUEST['strt_time_hour'] . ":" . $_REQUEST['strt_time_min'] . $_REQUEST['strt_time_timemode']);
                $timestamp2 = strtotime($_REQUEST['end_date_month'] . "/" . $_REQUEST['end_date_day'] . "/" . $_REQUEST['end_date_year'] . " " . $_REQUEST['end_time_hour'] . ":" . $_REQUEST['end_time_min'] . $_REQUEST['end_time_timemode']);
            } else {
                $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " 00:00:00");
                $timestamp2 = strtotime($_REQUEST['end_date_month'] . "/" . $_REQUEST['end_date_day'] . "/" . $_REQUEST['end_date_year'] . " 23:59:59");
            }
        } else {
            $timestamp = mktime(0, 0, 0);
            $timestamp2 = mktime(23, 59, 59);
        }


        //echo "Timeframe: ".date("m/d/Y g:ia", $timestamp).' to '.date("m/d/Y g:ia", $timestamp2).'<br />';

        $cluster_id = intval($_REQUEST['s_cluster_id']);
        $cluster_id = ($cluster_id) ? $cluster_id : 3; // DEFAULT TO VERIFIER CLUSTER

        $user_group = $_REQUEST['s_user_group'];

        $source_user_group = $_REQUEST['s_source_user_group'];

        $source_cluster_id = intval($_REQUEST['s_source_cluster_id']);

        $ignore_source_cluster_id = intval($_REQUEST['s_ignore_source_cluster_id']);
        $user_team_id = intval($_REQUEST['user_team_id']);
        ?>
        <script>
            function toggleDateSearchMode(way) {
                if (way == 'daterange') {
                    $('#end_date_row').show();
                } else {
                    $('#end_date_row').hide();
                }
            }

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
        <div class="block">
            <?
            if (!isset($_REQUEST['no_nav'])) {
            ?>
            <div class="block-header bg-primary-light">
                <h4 class="block-title">Rouster Call Stats</h4>
            </div>
            <div class="block-content">
                <table class="tightTable">
                    <tr>
                        <td>
                            <form id="agentstatfrm" method="POST" action="<?= stripurl() ?>" onsubmit="return genReport(this,'callstats')">
                                <input type="hidden" name="generate_agent_stat_report">
                                <table border="0">
                                    <tr>
                                        <th height="30">Cluster</th>
                                        <td>
                                            <?=makeClusterDD('s_cluster_id', $cluster_id, "form-control custom-select-sm", '', 0);?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th height="30">User Group</th>
                                        <td>
                                            <?=makeViciUserGroupDD('s_user_group[]', $_REQUEST['s_user_group'], "form-control custom-select-sm", '', 10, 1);?>
                                        </td>
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
                                        <th>Use Time?</th>
                                        <td>
                                            <input type="checkbox" name="timeFilter" id="timeFilter">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>User Team:</th>
                                        <td>
                                            <?= makeTeamsDD("user_team_id", (!isset($_REQUEST['user_team_id']) || intval($_REQUEST['user_team_id']) < 0) ? -1 : $_REQUEST['user_team_id'], 'form-control custom-select-sm', ""); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th height="30">Ignore Users:<br/>(<a href="#" onclick="alert('Ignore users in the report, if they appear. Seperate the usernames with Commas');return false">help?</a>)</th>

                                        <td>
                                            <input class="form-control" type="text" size="30" name="ignore_users_list" id="ignore_users_list" value="<?= htmlentities($_REQUEST['ignore_users_list']) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" align="center">

                                            <input type="checkbox" name="combine_users" value="1" <?= ($_REQUEST['combine_users']) ? " checked " : '' ?>> Combine Users

                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" align="right" style="padding-top:5px">
                                            <span id="callstats_loading_plx_wait_span" class="nod"><img src="images/ajax-loader.gif" border="0"/> Loading, Please wait...</span>
                                            <span id="callstats_submit_report_button" class="input-group-sm">
                                                <button type="button" class="btn btn-sm btn-primary" title="Generate PRINTABLE" onclick="genReport(getEl('agentstatfrm'),'callstats',1)">Generate PRINTABLE</button>
                                                <button type="submit" class="btn btn-sm btn-success" title="Generate Report" onclick="this.form.target='';">Generate</button>
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
                        <meta http-equiv="Content-Language" content="en">
                        <?
                    }
                    if (isset($_REQUEST['generate_agent_stat_report'])) {
                        ?>
                        <tr>
                            <td>
                                <?
                                $ignore_arr = preg_split("/,|;|:| /", $_REQUEST['ignore_users_list'], -1, PREG_SPLIT_NO_EMPTY);
                                $source_cluster_id = intval($_REQUEST['s_source_cluster_id']);
                                $report = $this->makeHTMLReport($timestamp, $timestamp2, $cluster_id, $user_team_id, $_REQUEST['s_user_group'], $ignore_arr, $source_cluster_id, $ignore_source_cluster_id, $source_user_group, (($_REQUEST['combine_users']) ? true : false));
                                if (!$report) {
                                    echo "No data";
                                } else {
                                    echo $report;
                                }
                                ?>
                            </td>
                        </tr>
                        <script>
                            toggleDateSearchMode('<?=$_REQUEST['date_mode']?>');
                        </script>
                    <?

                    if (!isset($_REQUEST['no_nav'])) {
                    	
                    	$page_title ='Rouster Call Status Report - '.date("m/d/Y", $timestamp).' - '.htmlentities(($_REQUEST['s_user_group'] == NULL || $_REQUEST['s_user_group'][0] == '') ? "All Groups" : "Selected Group" . ((count($_REQUEST['s_user_group']) > 1) ? "s" : " : " . ((is_array($_REQUEST['s_user_group'])) ? $_REQUEST['s_user_group'][0] : $_REQUEST['s_user_group'])));
                    
                    	
                    ?>
                        <script>
                            $(document).ready(function () {
                                $('#verifier_report_table').DataTable({
                                    "lengthMenu": [[-1, 20, 50, 100, 500], ["All", 20, 50, 100, 500]],
                                    dom: 'Bfrtip',
                                    buttons: [
                                    	{
                                        	extend: 'print',
                                        	messageTop: '<?=addslashes($page_title)?>'
                                    	},
                                        {extend: 'copy', header: false, footer: false}
                                    ],

                                });
                                go('#anc_rouster_report');
                            });
                        </script>
                        <?
                    }
                    } // END IF GENERATE REPORT
                    ?>
                </table>
            </div>
        </div>
        <?
    }
} // END OF CLASS
