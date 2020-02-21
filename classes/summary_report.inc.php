<?php
/***************************************************************
 *    Summary Report - A report of group totals for cold,taps, and verifiers
 *    Written By: Jonathan Will
 ***************************************************************/

$_SESSION['summary_report'] = new SummaryReport;

class SummaryReport
{


    function SummaryReport()
    {


        ## REQURES DB CONNECTION!
        $this->handlePOST();
    }


    function handlePOST()
    {


    }

    function handleFLOW()
    {


        include_once($_SESSION['site_config']['basedir'] . "classes/agent_call_stats.inc.php");

        include_once($_SESSION['site_config']['basedir'] . "classes/sales_analysis.inc.php");

        //	include_once($_SESSION['site_config']['basedir']."classes/rouster_report.inc.php");
//		if(!checkAccess('sales_analysis')){
//
//
//			accessDenied("Sales Analysis");
//
//			return;
//
//		}else{

        $this->makeReport();

//		}

    }

    function generateCompanyData($stime, $etime, $stack)
    {

        $stime = intval($stime);
        $etime = intval($etime);
        $output = array();


        foreach ($stack as $company_id => $company_row) {
            $output[$company_id] = array(
                'company_row' => $company_row
            );

            $output[$company_id]['user_groups'] = array();

            $sql = "SELECT * FROM `user_groups_master` WHERE company_id='" . intval($company_id) . "' AND `agent_type` != 'verifier' ORDER BY `user_group` ASC";
            //	echo $sql;

            $res = $_SESSION['dbapi']->ROquery($sql, 1);

            //
            $x = 0;
            while ($group = mysqli_fetch_array($res, MYSQLI_ASSOC)) {

                $output[$company_id]['user_groups'][$x] = $group;


                //generateData($stime, $etime, $campaign_code, $agent_cluster_id, $combine_users, $user_group, $ignore_group, $vici_campaign_id='',$ignore_arr = null)
                // new args
                // generateData($stime, $etime, $campaign_code, $agent_cluster_id, $user_team_id, $combine_users, $user_group, $ignore_group, $vici_campaign_code = '', $ignore_arr = NULL, $vici_campaign_id = '') {

                $output[$company_id]['user_groups'][$x]['data'] = $_SESSION['sales_analysis']->generateData($stime, $etime, null, -1, 0, true, $group['user_group'], null);


                if ($output[$company_id]['user_groups'][$x]['data'] == null) {

                    // SKIP GROUPS WITH NO DATA/ZEROS
                    unset($output[$company_id]['user_groups'][$x]);

                }

                $x++;
            }


        }

        return $output;

    }


    function generateAgentData($stime, $etime, $stack)
    {

        $stime = intval($stime);
        $etime = intval($etime);

        $output = array();

//echo date("m/d/Y H:i:s", $stime).' to '.date("m/d/Y H:i:s", $etime).'<br />';

        foreach ($stack as $cluster_id => $empty_array) {

            $cluster_idx = getClusterIndex($cluster_id);

            //list($output_array, $totals) = $_SESSION['sales_analysis']->generateData($stime, $etime, null, $cluster_idx, 1);
            //generateData($stime, $etime, $campaign_code, $agent_cluster_idx, $user_team_id,$combine_users, $user_group, $ignore_group, $vici_campaign_code='',$ignore_arr = null, $vici_campaign_id='')

            $cluster_out = $_SESSION['sales_analysis']->generateData($stime, $etime, null, $cluster_idx, 0, 1); //

            // returns array($output_array, $totals);

//echo "<br /><br />".
//	"CLUSTER ID#".$cluster_id." (".getClusterName($cluster_id).")<br />";
//echo nl2br(print_r($totals,1));

            $output[$cluster_id] = $cluster_out;//array();
        }


        return $output;
    }

    function generateVerifierData($stime, $etime, $stack)
    {


        $stime = intval($stime);
        $etime = intval($etime);

        $output = array();

        foreach ($stack as $cluster_id => $empty_array) {


            $cluster_out = $_SESSION['agent_call_stats']->generateData($cluster_id, $stime, $etime);

            // ADD UP AGENTS NUMBERS INTO A TOTAL
            $output[$cluster_id] = array();

            $output[$cluster_id]['sale_cnt'] = 0;
            $output[$cluster_id]['paid_sale_cnt'] = 0;
            $output[$cluster_id]['paid_sale_total'] = 0;
            $output[$cluster_id]['call_cnt'] = 0;
            $output[$cluster_id]['hangup_cnt'] = 0;
            $output[$cluster_id]['decline_cnt'] = 0;
            $output[$cluster_id]['reviewcnt'] = 0;

            $output[$cluster_id]['paid_time'] = 0;
            $output[$cluster_id]['t_time'] = 0;
            $output[$cluster_id]['t_pause'] = 0;
            $output[$cluster_id]['t_talk'] = 0;
            $output[$cluster_id]['t_dead'] = 0;
            $output[$cluster_id]['t_call_count'] = 0;


            $output[$cluster_id]['agent_amount_total'] = 0;
            $output[$cluster_id]['verifier_amount_total'] = 0;
//			$output[$cluster_id]['bump_amount'] = 0;
//			$output[$cluster_id]['bump_percent'] = 0;
            $output[$cluster_id]['bump_count'] = 0;


            $output[$cluster_id]['seconds_INCALL'] = 0;
            $output[$cluster_id]['seconds_READY'] = 0;
            $output[$cluster_id]['seconds_QUEUE'] = 0;
            $output[$cluster_id]['seconds_PAUSED'] = 0;


            // GET THE HOURS FROM VICI CLUSTER NOW
            /// RESOLVE DB IDX FROM CLUSTER ID
            $vici_idx = getClusterIndex($cluster_id);

            // CONNECT VICI CLUSTER BY IDX
            connectViciDB($vici_idx);

            $t_call_count = 0;

            foreach ($cluster_out as $agent) {
//echo nl2br(print_r($agent,1));
                $output[$cluster_id]['sale_cnt'] += $agent['sale_cnt'];


                $output[$cluster_id]['paid_sale_cnt'] += $agent['paid_sale_cnt'];
                $output[$cluster_id]['paid_sale_total'] += $agent['paid_sale_total'];

                $output[$cluster_id]['call_cnt'] += $agent['call_cnt'];
                $output[$cluster_id]['hangup_cnt'] += $agent['hangup_cnt'];
                $output[$cluster_id]['decline_cnt'] += $agent['decline_cnt'];
                $output[$cluster_id]['reviewcnt'] += $agent['agent']['reviewcnt'];


                $output[$cluster_id]['paid_time'] += $agent['paid_time'];

                $output[$cluster_id]['t_time'] += $agent['t_time'];
                $output[$cluster_id]['t_pause'] += $agent['t_pause'];
                $output[$cluster_id]['t_talk'] += $agent['t_talk'];
                $output[$cluster_id]['t_dead'] += $agent['t_dead'];
                $output[$cluster_id]['t_call_count'] += $agent['t_call_count'];

                $output[$cluster_id]['agent_amount_total'] += $agent['agent']['agent_amount_total'];
                $output[$cluster_id]['verifier_amount_total'] += $agent['agent']['verifier_amount_total'];
                $output[$cluster_id]['bump_count'] += $agent['agent']['bump_count'];

                $output[$cluster_id]['seconds_INCALL'] += $agent['agent']['seconds_INCALL'];
                $output[$cluster_id]['seconds_READY'] += $agent['agent']['seconds_READY'];
                $output[$cluster_id]['seconds_QUEUE'] += $agent['agent']['seconds_QUEUE'];
                $output[$cluster_id]['seconds_PAUSED'] += $agent['agent']['seconds_PAUSED'];

            }


            //$output[$cluster_id] = $cluster_out;
        }

//print_r($output);

        return $output;
    }


    function generateData($report_type, $stime, $etime)
    {
        $stime = intval($stime);
        $etime = intval($etime);

        connectPXDB();


        $swhere = " WHERE sale_time BETWEEN '$stime' AND '$etime' ";


        // GET A STACK OF AGENT CLUSTERS
        $cold_stack = array();
        $taps_stack = array();
        $verifier_stack = array();
        $stack = array();

        if ($report_type == "verifier") {

            $dres = $_SESSION['dbapi']->ROquery("SELECT DISTINCT(`verifier_cluster_id`) AS `verifier_cluster_id` FROM `sales` " .
                $swhere .
                " AND `verifier_cluster_id` > 0 " .
                " ORDER BY `verifier_cluster_id` ASC", 1);
            while ($row = mysqli_fetch_array($dres, MYSQLI_ASSOC)) {

                $cid = $row['verifier_cluster_id'];

                $verifier_stack[$cid] = array();

            }

        } else if ($report_type == "company") {


            $dres = $_SESSION['dbapi']->ROquery("SELECT * FROM `companies` WHERE `status`='enabled' ORDER BY `name` ASC", 1);
            while ($row = mysqli_fetch_array($dres, MYSQLI_ASSOC)) {

                $cid = $row['id'];

                $stack[$cid] = $row;

            }


        } else {

            $sql = "SELECT DISTINCT(agent_cluster_id) AS agent_cluster_id FROM `sales` " .
                $swhere .
                " AND `agent_cluster_id` > 0 " .
                " ORDER BY `agent_cluster_id` ASC";

            //echo $sql;

            $dres = $_SESSION['dbapi']->ROquery($sql, 1);
            while ($row = mysqli_fetch_array($dres, MYSQLI_ASSOC)) {

                $cid = $row['agent_cluster_id'];

                // DETERMINE WHAT TYPE OF CLUSTER IT IS
                list($type) = $_SESSION['dbapi']->ROqueryROW("SELECT `cluster_type` FROM `vici_clusters` WHERE id='$cid'");

                switch ($type) {
                    default: // ANY UNKNOWN OR MULTIPURPOSE ONES WILL BE TREATED LIKE COLD CLUSTERS
                    case 'all':
                    case 'coldtaps':
                    case 'cold':
                        $cold_stack[$cid] = array();
                        break;
                    case 'taps':
                        $taps_stack[$cid] = array();
                        break;
                    case 'verifier':
                        $verifier_stack[$cid] = array();
                        break;
                }
            }

        }


        switch ($report_type) {
            default:
            case 'cold':
                $stack = $cold_stack;

                return $this->generateAgentData($stime, $etime, $stack);

                break;
            case 'taps':
                $stack = $taps_stack;

                return $this->generateAgentData($stime, $etime, $stack);

                break;
            case 'verifier':
                $stack = $verifier_stack;

                // KICK OFF TO THE VERIFIER GENRATION FUNCTION
                return $this->generateVerifierData($stime, $etime, $stack);

                break;

            case 'company':
                //($report_type == "company"){

                return $this->generateCompanyData($stime, $etime, $stack);


                break;
        }


        /*

            // INIT TOTALS VARIABLES
            $total_paid_hrs = 0;
            $total_active_hrs = 0;
            $total_calls = 0;
            $total_ni = 0;
            $total_xfer = 0;
            $total_sale_cnt = 0;
            $total_amount = 0;

            $total_paid_sale_cnt = 0;
            $total_paid_amount = 0;

            $total_closing = 0;
            $total_conversion = 0;
            $total_yes2all = 00;
            $total_avg = 0;
            $total_paid_hr = 0;
            $total_wrkd_hr = 0;


            foreach($stack as $cluster_id=>$empty_array){

                // RESET THE CLUSTER TOTALS
                $running_amount = 0;
                $running_salecnt= 0;
                $running_num_NI = 0;
                $running_num_XFER = 0;
                $running_activity_paid = 0;
                $running_activity_wrkd = 0;
                $running_activity_num_calls = 0;

                $running_paid_amount = 0;
                $running_paid_salecnt = 0;


                $sql = "SELECT SUM(amount) AS amount,count(id) AS salecount FROM sales ".
                        $swhere.
                        " AND agent_cluster_id='$cluster_id' ";


                $paidsql = $sql." AND is_paid='yes' ";
                $unpaidsql = $sql." AND is_paid='no' ";

    //echo $unpaidsql."<br /><br />\n\n";

                // GET THE UNPAID DEALS
                list($amount,$salecnt) = queryROW($unpaidsql);


                $running_amount += $amount;
                $running_salecnt += $salecnt;

                // GET THE PAID DEALS
                list($amount,$salecnt) = queryROW($paidsql);

                // ADDING TO THE MAIN NUMBERS
                $running_amount += $amount;
                $running_salecnt += $salecnt;
                // BUT ALSO TRACKING THEM SEPERATE
                $running_paid_amount += $amount;
                $running_paid_salecnt += $salecnt;


                $sql = "SELECT COUNT(id) FROM lead_tracking ".
                    " WHERE `time` BETWEEN '$stime' AND '$etime' ".
                    " AND (`dispo`='NI' OR `dispo`='ni') ".
                    " AND `vici_cluster_id`='$cluster_id' ";

    //echo $sql;


                // NOT INTERESTED STATS
                list($num_NI) = queryROW($sql
                                        );


                // XFERS TOTALS
                $sql = "SELECT COUNT(id) FROM transfers ".
                        " WHERE xfer_time BETWEEN '$stime' AND '$etime' ".
                        " AND agent_cluster_id='$cluster_id' ".
                        " AND (verifier_dispo IS NOT NULL  AND verifier_dispo != 'DROP') "
                        ;


            //	echo $sql."\n";

                list($num_XFER) = queryROW($sql);
                $activity_paid = 0;
                $activity_wrkd = 0;
                $activity_num_calls = 0;


                // GET AGENT ACTIVITY TIMER
                list($activity_paid,$activity_wrkd,$activity_num_calls)  = queryROW(
                    "SELECT SUM(paid_time), SUM(activity_time),SUM(calls_today) FROM activity_log ".
                    "WHERE `time_started` BETWEEN '$stime' AND '$etime' ".
                    " AND `vici_cluster_id`='$cluster_id' ".
                    " AND `username` NOT LIKE '%2' "
                );

                list($activity_paid2,$activity_wrkd2,$activity_num_calls2)  = queryROW(
                    "SELECT SUM(paid_time), SUM(activity_time),SUM(calls_today) FROM activity_log ".
                    "WHERE `time_started` BETWEEN '$stime' AND '$etime' ".
                    " AND `vici_cluster_id`='$cluster_id' ".
                    " AND `username` LIKE '%2' "
                );



                $paid_hrs = $activity_paid/60;
                $active_hrs=$activity_wrkd/60;

                $activity_num_calls += $activity_num_calls2;




                $closing_percent = ($num_XFER <= 0)?0:(($running_salecnt / $num_XFER) * 100);

                $conversion_percent = (($num_NI + $running_salecnt) <= 0)?0: (($running_salecnt / ($num_NI + $running_salecnt)) * 100);


                $avg_sale = ($running_salecnt <= 0)?0:($running_amount / $running_salecnt);


                $yes2all = ($activity_num_calls <= 0)?0: ($running_salecnt / $activity_num_calls) * 100;

                $paid_hr = ($paid_hrs <= 0)?0:($running_amount / $paid_hrs);

                $wrkd_hr = ($active_hrs <= 0)?0:($running_amount / $active_hrs);


                $stack[$cluster_id] =  array(

                            'cluster_id'=>$cluster_id,

                            'activity_paid'=>($activity_paid/60),
                            'activity_wrkd'=>($activity_wrkd/60),
                            'calls_today'=>$activity_num_calls,

                            'num_NI'		=> $num_NI,
                            'num_XFER'	=> $num_XFER,
                            'sale_cnt'		=> $running_salecnt,
                            'closing_percent'=> $closing_percent,
                            'conversion_percent'=>$conversion_percent,
                            'yes2all_percent'	=> $yes2all,
                            'sales_total'		=> $running_amount,
                            'paid_sales_total'	=> $running_paid_amount,
                            'paid_sale_cnt'		=> $running_paid_salecnt,
                            'avg_sale'			=> $avg_sale,

                            'paid_hr'=>$paid_hr,
                            'wrkd_hr'=>$wrkd_hr,
                    );

                // TOTALS ADDUP
                $total_paid_hrs += $paid_hrs;
                $total_active_hrs += $active_hrs;
                $total_calls += $activity_num_calls;
                $total_ni += $num_NI;
                $total_xfer += $num_XFER;
                $total_sale_cnt += $running_salecnt;
                $total_amount += $running_amount;

                $total_paid_sale_cnt += $running_paid_salecnt;
                $total_paid_amount += $running_paid_amount;


            }

            $total_closing = ($total_xfer <= 0)?0: (($total_sale_cnt / $total_xfer) * 100);

            $total_conversion = (($total_ni + $total_sale_cnt) <= 0)?0: (($total_sale_cnt / ($total_ni + $total_sale_cnt)) * 100);

            $total_yes2all = ($total_calls <= 0)?0:(($total_sale_cnt / $total_calls) * 100);

            $total_avg = ($total_sale_cnt <= 0)?0:($total_amount / $total_sale_cnt);

            $total_paid_hr = ($total_paid_hrs <= 0)?0:($total_amount / $total_paid_hrs);

            $total_wrkd_hr = ($total_active_hrs <= 0)?0:($total_amount / $total_active_hrs);


            $totals = array(

            // ADDED IN THE LOOP
                'total_activity_paid_hrs' => $total_paid_hrs,
                'total_activity_wrkd_hrs' => $total_active_hrs,
                'total_calls' => $total_calls,
                'total_NI' => $total_ni,
                'total_XFER' => $total_xfer,
                'total_sale_cnt' => $total_sale_cnt,
                'total_sales' => $total_amount,

                'total_paid_sale_cnt' => $total_paid_sale_cnt,
                'total_paid_sales' => $total_paid_amount,

            // MATH GENERATED FROM ABOVE DATA
                'total_closing' => $total_closing,
                'total_conversion' => $total_conversion,
                'total_yes2all' => $total_yes2all,
                'total_avg' => $total_avg,
                'total_paid_hr' => $total_paid_hr,
                'total_wrkd_hr' => $total_wrkd_hr,
            );


            // SORTING MOTHERFUCKER
            ///////uasort($output_array, 'paidSorter');//($a, $b)


            return array($stack, $totals);
            */
    }


    function makeReport()
    {


        //echo $this->makeHTMLReport('1430377200', '1430463599', 'BCSFC', -1, 1,null , array("SYSTEM-TRNG-SOUTH", "SYSTEM-TRNG","SYS-TRNG-SOUTH-AM")) ;

        if (isset($_POST['generate_report'])) {

            $timestamp = strtotime($_REQUEST['stime_month'] . "/" . $_REQUEST['stime_day'] . "/" . $_REQUEST['stime_year']);
            $timestamp2 = strtotime($_REQUEST['etime_month'] . "/" . $_REQUEST['etime_day'] . "/" . $_REQUEST['etime_year']);
        } else {

            $timestamp = $timestamp2 = time();


        }

        $type = preg_replace("/[^a-zA-Z0-9]/", '', $_REQUEST['report_type']);
        //echo $type;
        ?>
        <div class="block">
        <form id="summary_report" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?area=summary_report&no_script=1" onsubmit="return genReport(this, 'summary');">
        <input type="hidden" name="generate_report">
        <div class="block-header bg-primary-light">
            <h4 class="block-title">Summary Report</h4>
        </div>
        <div class="bg-info-light" id="name_search_table">
            <div class="form-group row mb-0">
                <input type="hidden" name="searching_report"/>
                <label class="col-3 col-form-label" for="stime">Start Date</label>
                <div class="col-5">
                    <?= makeTimebar("stime_", 1, null, false, $timestamp); ?>
                </div>
            </div>
            <div class="form-group row mb-0">
                <label class="col-3 col-form-label" for="etime">End Date</label>
                <div class="col-5">
                    <?= makeTimebar("etime_", 1, null, false, $timestamp2); ?>
                </div>
            </div>
            <div class="form-group row mb-0">
                <label class="col-3 col-form-label" for="report_type">Report Type</label>
                <div class="col-5">
                    <select class="custom-select-sm" name="report_type">
                        <option value="cold"<?= ($type == 'cold') ? " SELECTED" : "" ?>>Cold</option>
                        <option value="taps"<?= ($type == 'taps') ? " SELECTED" : "" ?>>Taps</option>
                        <option value="verifier"<?= ($type == 'verifier') ? " SELECTED" : "" ?>>Verifier</option>
                        <option value="company"<?= ($type == 'company') ? " SELECTED" : "" ?>>Sub-Company and Group</option>
                    </select>
                </div>
            </div>
            <div class="form-group row mb-0">
                <div id="summary_submit_report_button">
                    <button type="submit" class="btn btn-sm btn-success" title="Generate Report">Generate</button>
                </div>
            </div>
        </div>
        <div class="block-content">
        <?
        if (isset($_POST['generate_report'])) {
            $time_started = microtime_float();
            ## TIME
            $timestamp = strtotime($_REQUEST['stime_month'] . "/" . $_REQUEST['stime_day'] . "/" . $_REQUEST['stime_year']);
            $timestamp2 = strtotime($_REQUEST['etime_month'] . "/" . $_REQUEST['etime_day'] . "/" . $_REQUEST['etime_year']);
            ## TIMEFRAMES
            $stime = mktime(0, 0, 0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));
            $etime = mktime(23, 59, 59, date("m", $timestamp2), date("d", $timestamp2), date("Y", $timestamp2));
            ## GENERATE AND DISPLAY REPORT
            $html = $this->makeHTMLReport($type, $stime, $etime);
            if ($html == null) {
                echo '<span style="font-size:14px;font-style:italic;">No results found, for the specified values.</span><br />';
            } else {
                echo $html;
            }
            $time_ended = microtime_float();
            $time_taken = $time_ended - $time_started;
            echo "<div class='small text-right'>Load time: " . $time_taken . "</div>";
        }
    }

    function makeHTMLReport($report_type, $stime, $etime)
    {
        echo '<div class="small text-left">makeHTMLReport(\'' . $report_type . '\', ' . "$stime, $etime) called</div>\n";
        if ($report_type == 'verifier') {
            $cluster_data = $this->generateData($report_type, $stime, $etime);
            if (count($cluster_data) < 1) {
                return null;
            }
            // ACTIVATE OUTPUT BUFFERING
            ob_start();
            ob_clean();
            echo "<h1>Verifier Summary Report - ";
            if (date("m-d-Y", $stime) == date("m-d-Y", $etime)) {
                echo date("m-d-Y", $stime);
            } else {
                echo date("m-d-Y", $stime) . ' to ' . date("m-d-Y", $etime);
            }
            echo "</h1>";
            ?>
            <table class="table table-sm table-striped">
                <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                <tr>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-left">Cluster</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right"># of Calls</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Sales</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">PaidCC</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">%PaidCC</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">PaidCC/Hour</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">$PaidCC</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Hangups</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Declines</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Activity</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">In Call</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Time</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Paid Time</th>

                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Pause</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Talk Avg</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Dead</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Closing %</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Adj. Closing %</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Hangup %</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Sale Reviews</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Bump $</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right">Bump %</th>
                    <th nowrap style="border-bottom:1px dotted #000;padding-left:3px" class="text-right"># of Bumps</th>

                </tr><?

                $stmicro = $stime * 1000;
                $etmicro = $etime * 1000;

                $running_total_calls = 0;
                $running_total_sales = 0;
                $running_total_hangups = 0;
                $running_total_declines = 0;
                $running_total_reviews = 0;
                $running_total_paid_sales = 0;
                $running_total_paid_sales_amount = 0;
                $running_total_activity_time = 0;

                $running_total_bumps = 0;

                $running_paid_time = 0;
                $running_t_time = 0;
                $running_total_talk_time = 0;

                $running_total_incall_time = 0;


                $tcount = 0;
                $x1 = 0;

                foreach ($cluster_data as $cluster => $data) {


                    //echo nl2br(print_r($row,1));
                    $activity_time = ($data['seconds_INCALL'] + $data['seconds_READY'] + $data['seconds_QUEUE']);//+ $row['agent']['seconds_PAUSED']

                    $running_total_activity_time += $activity_time;

                    $tmphours = floor($activity_time / 3600);
                    $tmpmin = floor(($activity_time - ($tmphours * 3600)) / 60);
                    $total_activity_time = $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin); //renderTimeFormatted($activity_time/60);//

                    $incall_time = $data['seconds_INCALL'];
                    $tmphours = floor($incall_time / 3600);
                    $tmpmin = floor(($incall_time - ($tmphours * 3600)) / 60);
                    $total_incall_time = $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin); //renderTimeFormatted($incall_time/60);//

                    $running_total_incall_time += $incall_time;


                    $tmphours = floor($data['t_time'] / 3600);
                    $tmpmin = floor(($data['t_time'] - ($tmphours * 3600)) / 60);
                    $total_time = renderTimeFormatted($data['t_time'] / 60);//$tmphours.':'.(($tmpmin <= 9)?'0'.$tmpmin:$tmpmin); //renderTimeFormatted($data['t_time']/60);//


                    $tmpmin = floor($data['t_pause'] / 60);
                    $tmpsec = ($data['t_pause'] % 60);
                    $total_pause = $tmpmin . ':' . (($tmpsec < 10) ? '0' . $tmpsec : $tmpsec); //renderTimeFormatted($data['t_pause']/60);//


                    // GOTTA AVG THE TALK TIMES, NOT ADD
                    $tmptalktime = intval($data['t_talk']);

                    $running_total_talk_time += $tmptalktime;


                    //$talktimeavg = $tmptalktime / intval($row['t_call_count']);
                    $talktimeavg = ($data['call_cnt'] <= 0) ? 0 : ($tmptalktime / intval($data['call_cnt']));

                    $total_talk = renderTimeFormatted($talktimeavg);

                    $total_dead = renderTimeFormatted($data['t_dead']);


                    //$close_percent = number_format( round( (($row['sale_cnt']) / ($row['t_call_count'])) * 100, 2), 2);
                    $close_percent = ($data['call_cnt'] <= 0) ? 0 : number_format(round((($data['sale_cnt']) / ($data['call_cnt'])) * 100, 2), 2);

                    $adjusted_close_percent = (($data['call_cnt'] - $data['hangup_cnt']) <= 0) ? 0 : number_format(round((($data['sale_cnt']) / ($data['call_cnt'] - $data['hangup_cnt'])) * 100, 2), 2);


                    $hangup_percent = ($data['call_cnt'] <= 0) ? 0 : number_format(round((($data['hangup_cnt']) / ($data['call_cnt'])) * 100, 2), 2);

                    // DISPO LOGGGGGGG
//					list($reviewcnt) = $_SESSION['dbapi']->queryROW("SELECT COUNT(`id`) FROM `dispo_log` ".
//											" WHERE `agent_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$row['username'])."' ".
//											" AND `micro_time` BETWEEN '$stmicro' AND '$etmicro' ".
//											" AND `dispo` = 'REVIEW' ".
//											" AND `result`='success' ");

                    $reviewcnt = $data['reviewcnt'];


                    $running_total_calls += $data['call_cnt'];
                    $running_total_sales += ($data['sale_cnt']);
                    $running_total_paid_sales += $data['paid_sale_cnt'];
                    $running_total_reviews += $reviewcnt;

                    $running_total_hangups += $data['hangup_cnt'];
                    $running_total_declines += $data['decline_cnt'];

                    $running_paid_time += $data['paid_time'];

                    $percent_paidcc_calls = ($data['sale_cnt'] <= 0) ? 0 : ($data['paid_sale_cnt'] / $data['sale_cnt']) * 100;

                    $running_total_paid_sales_amount += $data['paid_sale_total'];

                    $running_t_time += $data['t_time'];

                    //echo $row['paid_sale_cnt']." / ".($row['paid_time']/60);

                    $paidcc_per_hour = ($data['paid_time'] <= 0) ? 0 : ($data['paid_sale_cnt'] / ($data['paid_time'] / 60));//($row['t_time'] / 3600);


                    $running_total_bumps += $data['bump_count'];

                    $bump_amount = $data['verifier_amount_total'] - $data['agent_amount_total'];
                    $bump_percent = ($data['agent_amount_total'] <= 0) ? 0 : round(($data['verifier_amount_total'] / $data['agent_amount_total']) * 100, 2);

                    ?>
                    <tr>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" nowrap><?= strtoupper(getClusterName($cluster)) ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        echo number_format($data['call_cnt'])

                        //		number_format($row['t_call_count'])
                        ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?= number_format(($data['sale_cnt'] - $data['paid_sale_cnt'])) ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?= number_format($data['paid_sale_cnt']) ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?= number_format($percent_paidcc_calls) ?>%</td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?= number_format($paidcc_per_hour, 2) ?></td>

                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right">$<?= number_format($data['paid_sale_total'], 2) ?></td>

                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?= number_format($data['hangup_cnt']) ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?= number_format($data['decline_cnt']) ?></td>

                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        echo $total_activity_time;

                        ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        echo $total_incall_time;

                        ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        //						if($row['t_time'] >= $this->time_limit){

                        echo '<span style="background-color:transparent">' . $total_time . '</span>';
                        //						}else{
                        //							echo '<span style="background-color:yellow">'.$total_time.'</span>';
                        //
                        //
                        //						}
                        ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        // PAID TIME

                        echo renderTimeFormatted($data['paid_time'] * 60);


                        ?></td>

                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        //						if($row['t_pause'] <= $this->pause_limit){

                        echo '<span style="background-color:transparent">' . $total_pause . '</span>';
                        //						}else{

                        //							echo '<span style="background-color:yellow">'.$total_pause.'</span>';
                        //						}


                        ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?
                        $talktimeavg = floor($talktimeavg);

                        //echo $talktimeavg.' vs '.$this->talk_lower_limit.' ';


                        //						if($talktimeavg >= $this->talk_lower_limit && $talktimeavg <= $this->talk_upper_limit){
                        //renderTimeFormatted($running_t_time/60);
                        echo '<span style="background-color:transparent">' . $total_talk . '</span>';
                        //						}else{

                        //							echo '<span style="background-color:yellow">'.$total_talk.'</span>';
                        //						}


                        ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        //						if($row['t_dead'] > $this->dead_time_limit){
                        //
                        //							echo '<span style="background-color:yellow">'.$total_dead.'</span>';
                        //						}else{
                        echo '<span style="background-color:transparent">' . $total_dead . '</span>';
                        //						}


                        ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        //						if(intval($close_percent) >= $this->close_percent_limit){

                        echo '<span style="background-color:transparent">' . $close_percent . '%</span>';
                        //						}else{

                        //							echo '<span style="background-color:yellow">'.$close_percent.'%</span>';

                        //						}

                        ?></td>
                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        //						if(intval($adjusted_close_percent) >= $this->close_percent_limit){

                        echo '<span style="background-color:transparent">' . $adjusted_close_percent . '%</span>';

                        //						}else{
                        //							echo '<span style="background-color:yellow">'.$adjusted_close_percent.'%</span>';
                        //						}

                        ?></td>

                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        //if(intval($adjusted_close_percent) >= $this->close_percent_limit){

                        echo '<span style="background-color:transparent">' . $hangup_percent . '%</span>';
                        //}else{
                        //	echo '<span style="background-color:yellow">'.$adjusted_close_percent.'%</span>';
                        //}

                        ?></td>

                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        echo number_format($data['reviewcnt']);

                        ?></td>


                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        echo '$' . number_format($bump_amount);

                        ?></td>


                    <td style="border-right:1px dotted #CCC;padding-right:3px" class="text-right"><?

                        echo number_format($bump_percent, 2) . '%';

                        ?></td>
                    <td class="text-right"><?

                        echo number_format($data['bump_count']);

                        ?></td>


                    </tr><?


                    $tcount++;
                }


                $total_close_percent = (($running_total_calls <= 0) ? 0 : number_format(round((($running_total_sales) / ($running_total_calls)) * 100, 2), 2));

                $total_adj_close_percent = ((($running_total_calls - $running_total_hangups) <= 0) ? 0 : number_format(round((($running_total_sales) / ($running_total_calls - $running_total_hangups)) * 100, 2), 2));

                $total_hangup_percent = (($running_total_calls <= 0) ? 0 : number_format(round((($running_total_hangups) / ($running_total_calls)) * 100, 2), 2));


                $total_percent_paidcc_calls = (($running_total_sales <= 0) ? 0 : ($running_total_paid_sales / $running_total_sales) * 100);

                $total_paidcc_per_hour = ($running_paid_time <= 0) ? 0 : ($running_total_paid_sales / ($running_paid_time / 60));


                $total_talk_time_avg = (($running_total_calls <= 0) ? 0 : ($running_total_talk_time / $running_total_calls));


                // TOTALS ROW
                ?>
                <tr>
                    <th style="border-right:1px dotted #CCC;border-top:1px solid #000" class="text-left">Totals:</th>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?= number_format($running_total_calls) ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?= number_format(($running_total_sales - $running_total_paid_sales)) ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?= number_format($running_total_paid_sales) ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?= number_format($total_percent_paidcc_calls) ?>%</td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?= number_format($total_paidcc_per_hour, 2) ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right">$<?= number_format($running_total_paid_sales_amount, 2) ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?= number_format($running_total_hangups) ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?= number_format($running_total_declines) ?></td>

                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?


                        $tmphours = floor($running_total_activity_time / 3600);
                        $tmpmin = floor(($running_total_activity_time - ($tmphours * 3600)) / 60);
                        echo $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);


                        ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?

                        //$running_total_incall_time
                        $tmphours = floor($running_total_incall_time / 3600);
                        $tmpmin = floor(($running_total_incall_time - ($tmphours * 3600)) / 60);
                        echo $tmphours . ':' . (($tmpmin <= 9) ? '0' . $tmpmin : $tmpmin);


                        ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?


                        echo renderTimeFormatted($running_t_time / 60);


                        ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?


                        echo renderTimeFormatted($running_paid_time * 60);


                        ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000">&nbsp;</td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?


                        //						if($total_talk_time_avg >= $this->talk_lower_limit && $total_talk_time_avg <= $this->talk_upper_limit){

                        echo '<span style="background-color:transparent">' . renderTimeFormatted($total_talk_time_avg) . '</span>';
                        //						}else{

                        //							echo '<span style="background-color:yellow">'.renderTimeFormatted($total_talk_time_avg).'</span>';
                        //						}


                        ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000">&nbsp;</td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?

                        //						if(intval($total_close_percent) >= $this->close_percent_limit){

                        echo '<span style="background-color:transparent">' . $total_close_percent . '%</span>';
                        //						}else{

                        //							echo '<span style="background-color:yellow">'.$total_close_percent.'%</span>';

                        //						}


                        ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?

                        //						if(intval($total_adj_close_percent) >= $this->close_percent_limit){

                        echo '<span style="background-color:transparent">' . $total_adj_close_percent . '%</span>';
                        //						}else{

                        //							echo '<span style="background-color:yellow">'.$total_adj_close_percent.'%</span>';

                        //						}


                        ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?

                        //if(intval($total_adj_close_percent) >= $this->close_percent_limit){

                        echo '<span style="background-color:transparent">' . $total_hangup_percent . '%</span>';

                        //}else{
                        //	echo '<span style="background-color:yellow">'.$total_hangup_percent.'%</span>';
                        //}


                        ?></td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?= number_format($running_total_reviews) ?></td>

                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right">&nbsp;</td>

                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right">&nbsp;</td>
                    <td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" class="text-right"><?= number_format($running_total_bumps) ?></td>
                </tr>
            </table>
            </div>
            <div class="block-header bg-info-light">
                <i class="si si-clock"></i>Generated on: <?= date("g:ia m/d/Y") ?>
            </div>
            </form>
            </div>
            <?

            // GRAB DATA FROM BUFFER
            $data = ob_get_contents();

            // TURN OFF OUTPUT BUFFERING, WITHOUT OUTPUTTING
            ob_end_clean();

            // CONNECT BACK TO PX BEFORE LEAVING
            connectPXDB();

            // RETURN HTML
            if ($tcount > 0)
                return $data;
            else
                return null;


            /******* REPORT TYPE : SUB COMPANY AND USER GROUP ********/
        } else if ($report_type == 'company') {
            $company_data = $this->generateData($report_type, $stime, $etime);
            if (count($company_data) < 1) {
                return null;
            }
            $gcount = 0;
            // ACTIVATE OUTPUT BUFFERING
            ob_start();
            ob_clean();
            echo "<h1>Sub Company - Summary Report - ";
            if (date("m-d-Y", $stime) == date("m-d-Y", $etime)) {
                echo date("m-d-Y", $stime);
            } else {
                echo date("m-d-Y", $stime) . ' to ' . date("m-d-Y", $etime);

            }
            echo "</h1>";
            ?>
            <table class="table table-sm table-striped">
                <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                <tr>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-left">Company</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">PD HRS</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">WRKD HRS</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">Total Calls</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">NI</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">XFERS</th>

                    <th style="border-bottom:1px solid #000;padding-left:5px">TOTAL SALES</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">PAID SALES</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">PAID %</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">PAID $</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">UNPAID SALES</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">UNPAID %</th>

                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">CLOSING %</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">CONVERSION %</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">YES 2 ALL %</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">TOTAL SALES</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">AVG SALE</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">PD $/HR</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">WRKD $/HR</th>
                </tr><?


                $company_totals = array();
                $company_totals['total_paid_sale_cnt'] = 0;
                $company_totals['total_sale_cnt'] = 0;
                $company_totals['total_activity_paid_hrs'] = 0;
                $company_totals['total_activity_wrkd_hrs'] = 0;
                $company_totals['total_calls'] = 0;
                $company_totals['total_NI'] = 0;
                $company_totals['total_XFER'] = 0;
                $company_totals['total_sales'] = 0;
                $company_totals['total_paid_sales'] = 0;


                // STUFF FOR AVERAGING
                $company_totals['total_closing_num'] = 0;
                $company_totals['total_closing_cnt'] = 0;
                $company_totals['total_conversion_num'] = 0;
                $company_totals['total_conversion_cnt'] = 0;
                $company_totals['total_yes2all_num'] = 0;
                $company_totals['total_yes2all_cnt'] = 0;
                $company_totals['total_avg_num'] = 0;
                $company_totals['total_avg_cnt'] = 0;
                $company_totals['total_paid_hr_num'] = 0;
                $company_totals['total_paid_hr_cnt'] = 0;
                $company_totals['total_wrkd_hr_num'] = 0;
                $company_totals['total_wrkd_hr_cnt'] = 0;


                $colspan = 19;

                foreach ($company_data as $company_id => $compdata) {

                    // $compdata['company_row'] = (company db record)
                    // $compdata['user_groups'] = array of user groups, with data
                    // $compdata['user_groups']['data'] = Array of (agent data, totals)


                    ?>
                    <tr>
                    <th class="text-left" style="font-weight:bold"><?= $compdata['company_row']['name'] ?></th>
                    <td colspan="<?= ($colspan - 1) ?>">&nbsp;</td>
                    </tr><?

                    if (count($compdata['user_groups']) <= 0) {

                        ?>
                        <tr>
                        <td colspan="<?= $colspan ?>" class="text-center">No records found.</td>
                        </tr><?
                        continue;
                    }

                    $running_totals = array();
                    $running_totals['total_paid_sale_cnt'] = 0;
                    $running_totals['total_sale_cnt'] = 0;
                    $running_totals['total_activity_paid_hrs'] = 0;
                    $running_totals['total_activity_wrkd_hrs'] = 0;
                    $running_totals['total_calls'] = 0;
                    $running_totals['total_NI'] = 0;
                    $running_totals['total_XFER'] = 0;
                    $running_totals['total_sales'] = 0;
                    $running_totals['total_paid_sales'] = 0;


                    // STUFF FOR AVERAGING
                    $running_totals['total_closing_num'] = 0;
                    $running_totals['total_closing_cnt'] = 0;
                    $running_totals['total_conversion_num'] = 0;
                    $running_totals['total_conversion_cnt'] = 0;
                    $running_totals['total_yes2all_num'] = 0;
                    $running_totals['total_yes2all_cnt'] = 0;
                    $running_totals['total_avg_num'] = 0;
                    $running_totals['total_avg_cnt'] = 0;
                    $running_totals['total_paid_hr_num'] = 0;
                    $running_totals['total_paid_hr_cnt'] = 0;
                    $running_totals['total_wrkd_hr_num'] = 0;
                    $running_totals['total_wrkd_hr_cnt'] = 0;

                    foreach ($compdata['user_groups'] as $ugidx => $group_row) {

                        // SKIP EMPTY GROUPS
                        if ($group_row['data'] == null) continue;

                        $totals = $group_row['data'][1];

                        // SKIP GROUPS WITH ZERO FOR THE IMPORTANT TOTALS
                        if ($totals['total_calls'] == 0 && $totals['total_sales'] == 0 && $totals['total_activity_wrkd_hrs'] == 0) {
                            continue;
                        }


                        $gcount++;


//					echo "GROUP: ".$group_row['user_group']."<br />\n";

//					echo nl2br(print_r($totals,1));

                        $paid_sale_percent = round(((float)$totals['total_paid_sale_cnt'] / $totals['total_sale_cnt']) * 100, 2);

                        $unpaid_sale_percent = 100 - $paid_sale_percent;

                        ?>
                        <tr>
                        <td style="padding-left:10px" nowrap><?= htmlentities(strtoupper($group_row['user_group'])) ?></td>
                        <td class="text-center"><?= number_format($totals['total_activity_paid_hrs'], 2) ?></td>
                        <td class="text-center"><?= number_format($totals['total_activity_wrkd_hrs'], 2) ?></td>
                        <td class="text-center"><?= number_format($totals['total_calls']) ?></td>
                        <td class="text-center"><?= number_format($totals['total_NI']) ?></td>
                        <td class="text-center"><?= number_format($totals['total_XFER']) ?></td>


                        <td class="text-center"><?= number_format($totals['total_sale_cnt']) ?></td>


                        <td class="text-center"><?= number_format($totals['total_paid_sale_cnt']) ?></td>
                        <td class="text-right"><?= number_format($paid_sale_percent, 2) ?>%</td>

                        <td class="text-right">$<?= number_format($totals['total_paid_sales']) ?></td>


                        <td class="text-center"><?= number_format(($totals['total_sale_cnt'] - $totals['total_paid_sale_cnt'])) ?></td>
                        <td class="text-right"><?= number_format($unpaid_sale_percent, 2) ?>%</td>


                        <td class="text-right"><?= number_format($totals['total_closing'], 2) ?>%</td>
                        <td class="text-right"><?= number_format($totals['total_conversion'], 2) ?>%</td>
                        <td class="text-right"><?= number_format($totals['total_yes2all'], 2) ?>%</td>
                        <td class="text-right">$<?= number_format($totals['total_sales']) ?></td>

                        <td class="text-right">$<?= number_format($totals['total_avg'], 2) ?></td>
                        <td class="text-right">$<?= number_format($totals['total_paid_hr'], 2) ?></td>
                        <td class="text-right">$<?= number_format($totals['total_wrkd_hr'], 2) ?></td>
                        </tr><?

                        $running_totals['total_paid_sale_cnt'] += $totals['total_paid_sale_cnt'];
                        $running_totals['total_sale_cnt'] += $totals['total_sale_cnt'];
                        $running_totals['total_activity_paid_hrs'] += $totals['total_activity_paid_hrs'];
                        $running_totals['total_activity_wrkd_hrs'] += $totals['total_activity_wrkd_hrs'];
                        $running_totals['total_calls'] += $totals['total_calls'];


                        $running_totals['total_NI'] += $totals['total_NI'];
                        $running_totals['total_XFER'] += $totals['total_XFER'];
                        $running_totals['total_paid_sales'] += $totals['total_paid_sales'];
                        $running_totals['total_sales'] += $totals['total_sales'];


                        // CLOSING PERCENTAGE AVERAGING
                        $running_totals['total_closing_num'] += $totals['total_closing'];
                        $running_totals['total_closing_cnt']++;

                        // CONVERSION PERCENTAGE AVERAGING
                        $running_totals['total_conversion_num'] += $totals['total_conversion'];
                        $running_totals['total_conversion_cnt']++;

                        // YES2ALL PERCENTAGE AVERAGING
                        $running_totals['total_yes2all_num'] += $totals['total_yes2all'];
                        $running_totals['total_yes2all_cnt']++;

                        // AVERAGE-SALE PERCENTAGE AVERAGING
                        $running_totals['total_avg_num'] += $totals['total_avg'];
                        $running_totals['total_avg_cnt']++;


                        // PAID PER HOUR AVERAGING
                        $running_totals['total_paid_hr_num'] += $totals['total_paid_hr'];
                        $running_totals['total_paid_hr_cnt']++;


                        // WORKED PER HOUR AVERAGING
                        $running_totals['total_wrkd_hr_num'] += $totals['total_wrkd_hr'];
                        $running_totals['total_wrkd_hr_cnt']++;

                    }


                    // OUTPUT COMPANY TOTALS LINE


                    $paid_sale_percent = round(((float)$running_totals['total_paid_sale_cnt'] / $running_totals['total_sale_cnt']) * 100, 2);

                    $unpaid_sale_percent = 100 - $paid_sale_percent;

                    $running_totals['total_closing'] = ($running_totals['total_closing_cnt'] <= 0) ? 0 : $running_totals['total_closing_num'] / $running_totals['total_closing_cnt'];
                    $running_totals['total_conversion'] = ($running_totals['total_conversion_cnt'] <= 0) ? 0 : $running_totals['total_conversion_num'] / $running_totals['total_conversion_cnt'];
                    $running_totals['total_yes2all'] = ($running_totals['total_yes2all_cnt'] <= 0) ? 0 : $running_totals['total_yes2all_num'] / $running_totals['total_yes2all_cnt'];
                    $running_totals['total_avg'] = ($running_totals['total_avg_cnt'] <= 0) ? 0 : $running_totals['total_avg_num'] / $running_totals['total_avg_cnt'];
                    $running_totals['total_paid_hr'] = ($running_totals['total_paid_hr_cnt'] <= 0) ? 0 : $running_totals['total_paid_hr_num'] / $running_totals['total_paid_hr_cnt'];
                    $running_totals['total_wrkd_hr'] = ($running_totals['total_wrkd_hr_cnt'] <= 0) ? 0 : $running_totals['total_wrkd_hr_num'] / $running_totals['total_wrkd_hr_cnt'];


                    ?>
                    <tr>
                        <th style="border-top:1px solid #000;padding:3px" class="text-left" nowrap>Sub-Total:</th>
                        <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_activity_paid_hrs'], 2) ?></th>
                        <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_activity_wrkd_hrs'], 2) ?></th>
                        <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_calls']) ?></th>
                        <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_NI']) ?></th>
                        <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_XFER']) ?></th>


                        <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_sale_cnt']) ?></th>

                        <th style="border-top:1px solid #000;padding:3px" class="text-center"><?= number_format($running_totals['total_paid_sale_cnt']) ?></th>
                        <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($paid_sale_percent, 2) ?>%</th>

                        <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($running_totals['total_paid_sales']) ?></th>


                        <th style="border-top:1px solid #000;padding:3px" class="text-center"><?= number_format(($running_totals['total_sale_cnt'] - $running_totals['total_paid_sale_cnt'])) ?></th>
                        <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($unpaid_sale_percent, 2) ?>%</th>


                        <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($running_totals['total_closing'], 2) ?>%</th>
                        <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($running_totals['total_conversion'], 2) ?>%</th>
                        <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($running_totals['total_yes2all'], 2) ?>%</th>

                        <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($running_totals['total_sales']) ?></th>

                        <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($running_totals['total_avg'], 2) ?></th>
                        <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($running_totals['total_paid_hr'], 2) ?></th>
                        <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($running_totals['total_wrkd_hr'], 2) ?></th>

                    </tr>
                    <?


                    // ADD CURRENT COMPANY TOTALS TO GRAND TOTALS
                    $company_totals['total_paid_sale_cnt'] += $running_totals['total_paid_sale_cnt'];
                    $company_totals['total_sale_cnt'] += $running_totals['total_sale_cnt'];
                    $company_totals['total_activity_paid_hrs'] += $running_totals['total_activity_paid_hrs'];
                    $company_totals['total_activity_wrkd_hrs'] += $running_totals['total_activity_wrkd_hrs'];
                    $company_totals['total_calls'] += $running_totals['total_calls'];

                    $company_totals['total_NI'] += $running_totals['total_NI'];
                    $company_totals['total_XFER'] += $running_totals['total_XFER'];
                    $company_totals['total_paid_sales'] += $running_totals['total_paid_sales'];
                    $company_totals['total_sales'] += $running_totals['total_sales'];

                    // CLOSING PERCENTAGE AVERAGING
                    $company_totals['total_closing_num'] += $running_totals['total_closing_num'];
                    $company_totals['total_closing_cnt'] += $running_totals['total_closing_cnt'];

                    // CONVERSION PERCENTAGE AVERAGING
                    $company_totals['total_conversion_num'] += $running_totals['total_conversion_num'];
                    $company_totals['total_conversion_cnt'] += $running_totals['total_conversion_cnt'];

                    // YES2ALL PERCENTAGE AVERAGING
                    $company_totals['total_yes2all_num'] += $running_totals['total_yes2all_num'];
                    $company_totals['total_yes2all_cnt'] += $running_totals['total_yes2all_cnt'];

                    // AVERAGE-SALE PERCENTAGE AVERAGING
                    $company_totals['total_avg_num'] += $running_totals['total_avg_num'];
                    $company_totals['total_avg_cnt'] += $running_totals['total_avg_cnt'];


                    // PAID PER HOUR AVERAGING
                    $company_totals['total_paid_hr_num'] += $running_totals['total_paid_hr_num'];
                    $company_totals['total_paid_hr_cnt'] += $running_totals['total_paid_hr_cnt'];


                    // WORKED PER HOUR AVERAGING
                    $company_totals['total_wrkd_hr_num'] += $running_totals['total_wrkd_hr_num'];
                    $company_totals['total_wrkd_hr_cnt'] += $running_totals['total_wrkd_hr_cnt'];

                }


                // OUTPUT MAIN/FINAL TOTALS LINE


                $paid_sale_percent = round(((float)$company_totals['total_paid_sale_cnt'] / $company_totals['total_sale_cnt']) * 100, 2);

                $unpaid_sale_percent = 100 - $paid_sale_percent;

                $company_totals['total_closing'] = ($company_totals['total_closing_cnt'] <= 0) ? 0 : $company_totals['total_closing_num'] / $company_totals['total_closing_cnt'];
                $company_totals['total_conversion'] = ($company_totals['total_conversion_cnt'] <= 0) ? 0 : $company_totals['total_conversion_num'] / $company_totals['total_conversion_cnt'];
                $company_totals['total_yes2all'] = ($company_totals['total_yes2all_cnt'] <= 0) ? 0 : $company_totals['total_yes2all_num'] / $company_totals['total_yes2all_cnt'];
                $company_totals['total_avg'] = ($company_totals['total_avg_cnt'] <= 0) ? 0 : $company_totals['total_avg_num'] / $company_totals['total_avg_cnt'];
                $company_totals['total_paid_hr'] = ($company_totals['total_paid_hr_cnt'] <= 0) ? 0 : $company_totals['total_paid_hr_num'] / $company_totals['total_paid_hr_cnt'];
                $company_totals['total_wrkd_hr'] = ($company_totals['total_wrkd_hr_cnt'] <= 0) ? 0 : $company_totals['total_wrkd_hr_num'] / $company_totals['total_wrkd_hr_cnt'];


                ?>
                <tr>
                    <th style="border-top:1px solid #000;padding:3px" class="text-left" nowrap>Grand Total:</th>
                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($company_totals['total_activity_paid_hrs'], 2) ?></th>
                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($company_totals['total_activity_wrkd_hrs'], 2) ?></th>
                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($company_totals['total_calls']) ?></th>
                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($company_totals['total_NI']) ?></th>
                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($company_totals['total_XFER']) ?></th>


                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($company_totals['total_sale_cnt']) ?></th>

                    <th style="border-top:1px solid #000;padding:3px" class="text-center"><?= number_format($company_totals['total_paid_sale_cnt']) ?></th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($paid_sale_percent, 2) ?>%</th>

                    <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($company_totals['total_paid_sales']) ?></th>


                    <th style="border-top:1px solid #000;padding:3px" class="text-center"><?= number_format(($company_totals['total_sale_cnt'] - $company_totals['total_paid_sale_cnt'])) ?></th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($unpaid_sale_percent, 2) ?>%</th>


                    <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($company_totals['total_closing'], 2) ?>%</th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($company_totals['total_conversion'], 2) ?>%</th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($company_totals['total_yes2all'], 2) ?>%</th>

                    <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($company_totals['total_sales']) ?></th>

                    <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($company_totals['total_avg'], 2) ?></th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($company_totals['total_paid_hr'], 2) ?></th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($company_totals['total_wrkd_hr'], 2) ?></th>

                </tr>
            </table>
            </div>
            <div class="block-header bg-info-light">
                <i class="si si-clock"></i>Generated on: <?= date("g:ia m/d/Y") ?>
            </div>
            </form>
            </div>
            <?

            // GRAB DATA FROM BUFFER
            $data = ob_get_contents();

            // TURN OFF OUTPUT BUFFERING, WITHOUT OUTPUTTING
            ob_end_clean();

            // CONNECT BACK TO PX BEFORE LEAVING
            connectPXDB();

            // RETURN HTML
            if ($gcount > 0)
                return $data;
            else
                return null;


            /******* REPORT TYPE: COLD OR TAPS ************/
        } else {
            $cluster_data = $this->generateData($report_type, $stime, $etime);
            if (count($cluster_data) < 1) {
                return null;
            }
            // ACTIVATE OUTPUT BUFFERING
            ob_start();
            ob_clean();
            echo "<h1>Summary Report - ";
            if (date("m-d-Y", $stime) === date("m-d-Y", $etime)) {
                echo date("m-d-Y", $stime);
            } else {
                echo date("m-d-Y", $stime) . ' to ' . date("m-d-Y", $etime);
            }
            echo "</h1>";
            ?>
            <table class="table table-sm table-striped">
                <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                <tr>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-left">CLUSTER</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" title="Number of hours being Paid for">PD HRS</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" title="Number of hours of Activity tracked">WRKD HRS</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" title="Total number of calls for the day">Total Calls</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" title="Number of Calls that were NOT INTERESTED">NI</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" title="Number of Transfers">XFERS</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" title="Number of Answering Machine calls">A</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" title="Percentage of calls that are Answering Machines">%ANS</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" title="Contacts per Worked hour, and Calls per Worked hour">CON&amp;CALLS/HR</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">TOTAL SALES</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">PAID SALES</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">PAID %</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">PAID $</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">UNPAID SALES</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px">UNPAID %</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">CLOSE %</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">CON%</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">YES 2 ALL %</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">TOTAL SALES</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">AVG SALE</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">PD $/HR</th>
                    <th style="border-bottom:1px solid #000;padding-left:5px" class="text-right">WRKD $/HR</th>
                </tr>
                <?
                $running_totals = array();
                $running_totals['total_paid_sale_cnt'] = 0;
                $running_totals['total_sale_cnt'] = 0;
                $running_totals['total_activity_paid_hrs'] = 0;
                $running_totals['total_activity_wrkd_hrs'] = 0;
                $running_totals['total_calls'] = 0;
                $running_totals['total_NI'] = 0;
                $running_totals['total_XFER'] = 0;
                $running_totals['total_sales'] = 0;
                $running_totals['total_paid_sales'] = 0;

                // STUFF FOR AVERAGING
                $running_totals['total_closing_num'] = 0;
                $running_totals['total_closing_cnt'] = 0;
                $running_totals['total_conversion_num'] = 0;
                $running_totals['total_conversion_cnt'] = 0;
                $running_totals['total_yes2all_num'] = 0;
                $running_totals['total_yes2all_cnt'] = 0;
                $running_totals['total_avg_num'] = 0;
                $running_totals['total_avg_cnt'] = 0;
                $running_totals['total_paid_hr_num'] = 0;
                $running_totals['total_paid_hr_cnt'] = 0;
                $running_totals['total_wrkd_hr_num'] = 0;
                $running_totals['total_wrkd_hr_cnt'] = 0;
                $running_totals['total_AnswerMachines'] = 0;
                foreach ($cluster_data as $cluster_id => $agent_data_stack) {
                    //echo nl2br(print_r($agent_data_stack,1));
                    list($agent_data, $totals) = $agent_data_stack;
//				echo nl2br(print_r($totals,1));
                    $paid_sale_percent = round(((float)$totals['total_paid_sale_cnt'] / $totals['total_sale_cnt']) * 100, 2);
                    $unpaid_sale_percent = 100 - $paid_sale_percent;
                    $t_ans_percent = round((($totals['total_AnswerMachines'] / $totals['total_calls']) * 100), 2);
                    ?>
                    <tr>
                    <td><?= htmlentities(strtoupper(getClusterName($cluster_id))) ?></td>
                    <td class="text-center"><?= number_format($totals['total_activity_paid_hrs'], 2) ?></td>
                    <td class="text-center"><?= number_format($totals['total_activity_wrkd_hrs'], 2) ?></td>
                    <td class="text-center"><?= number_format($totals['total_calls']) ?></td>
                    <td class="text-center"><?= number_format($totals['total_NI']) ?></td>
                    <td class="text-center"><?= number_format($totals['total_XFER']) ?></td>

                    <th><?= number_format($totals['total_AnswerMachines']) ?></th>
                    <th><?= $t_ans_percent ?>%</th>
                    <th><?= number_format($totals['total_contacts_per_worked_hour'], 2) . ' - ' . number_format($totals['total_calls_per_worked_hour'], 2) ?></th>


                    <td class="text-center"><?= number_format($totals['total_sale_cnt']) ?></td>


                    <td class="text-center"><?= number_format($totals['total_paid_sale_cnt']) ?></td>
                    <td class="text-right"><?= number_format($paid_sale_percent, 2) ?>%</td>

                    <td class="text-right">$<?= number_format($totals['total_paid_sales']) ?></td>


                    <td class="text-center"><?= number_format(($totals['total_sale_cnt'] - $totals['total_paid_sale_cnt'])) ?></td>
                    <td class="text-right"><?= number_format($unpaid_sale_percent, 2) ?>%</td>


                    <td class="text-right"><?= number_format($totals['total_closing'], 2) ?>%</td>
                    <td class="text-right"><?= number_format($totals['total_conversion'], 2) ?>%</td>
                    <td class="text-right"><?= number_format($totals['total_yes2all'], 2) ?>%</td>
                    <td class="text-right">$<?= number_format($totals['total_sales']) ?></td>

                    <td class="text-right">$<?= number_format($totals['total_avg'], 2) ?></td>
                    <td class="text-right">$<?= number_format($totals['total_paid_hr'], 2) ?></td>
                    <td class="text-right">$<?= number_format($totals['total_wrkd_hr'], 2) ?></td>
                    </tr><?

                    $running_totals['total_paid_sale_cnt'] += $totals['total_paid_sale_cnt'];
                    $running_totals['total_sale_cnt'] += $totals['total_sale_cnt'];
                    $running_totals['total_activity_paid_hrs'] += $totals['total_activity_paid_hrs'];
                    $running_totals['total_activity_wrkd_hrs'] += $totals['total_activity_wrkd_hrs'];
                    $running_totals['total_calls'] += $totals['total_calls'];


                    $running_totals['total_NI'] += $totals['total_NI'];
                    $running_totals['total_XFER'] += $totals['total_XFER'];

                    $running_totals['total_AnswerMachines'] += $totals['total_AnswerMachines'];

                    $running_totals['total_paid_sales'] += $totals['total_paid_sales'];
                    $running_totals['total_sales'] += $totals['total_sales'];


                    // CLOSING PERCENTAGE AVERAGING
                    $running_totals['total_closing_num'] += $totals['total_closing'];
                    $running_totals['total_closing_cnt']++;

                    // CONVERSION PERCENTAGE AVERAGING
                    $running_totals['total_conversion_num'] += $totals['total_conversion'];
                    $running_totals['total_conversion_cnt']++;

                    // YES2ALL PERCENTAGE AVERAGING
                    $running_totals['total_yes2all_num'] += $totals['total_yes2all'];
                    $running_totals['total_yes2all_cnt']++;

                    // AVERAGE-SALE PERCENTAGE AVERAGING
                    $running_totals['total_avg_num'] += $totals['total_avg'];
                    $running_totals['total_avg_cnt']++;


                    // PAID PER HOUR AVERAGING
                    $running_totals['total_paid_hr_num'] += $totals['total_paid_hr'];
                    $running_totals['total_paid_hr_cnt']++;


                    // WORKED PER HOUR AVERAGING
                    $running_totals['total_wrkd_hr_num'] += $totals['total_wrkd_hr'];
                    $running_totals['total_wrkd_hr_cnt']++;


                    $totals = null;
                }


                $paid_sale_percent = round(((float)$running_totals['total_paid_sale_cnt'] / $running_totals['total_sale_cnt']) * 100, 2);

                $unpaid_sale_percent = 100 - $paid_sale_percent;

                $running_totals['total_closing'] = ($running_totals['total_closing_cnt'] <= 0) ? 0 : $running_totals['total_closing_num'] / $running_totals['total_closing_cnt'];
                $running_totals['total_conversion'] = ($running_totals['total_conversion_cnt'] <= 0) ? 0 : $running_totals['total_conversion_num'] / $running_totals['total_conversion_cnt'];
                $running_totals['total_yes2all'] = ($running_totals['total_yes2all_cnt'] <= 0) ? 0 : $running_totals['total_yes2all_num'] / $running_totals['total_yes2all_cnt'];
                $running_totals['total_avg'] = ($running_totals['total_avg_cnt'] <= 0) ? 0 : $running_totals['total_avg_num'] / $running_totals['total_avg_cnt'];
                $running_totals['total_paid_hr'] = ($running_totals['total_paid_hr_cnt'] <= 0) ? 0 : $running_totals['total_paid_hr_num'] / $running_totals['total_paid_hr_cnt'];
                $running_totals['total_wrkd_hr'] = ($running_totals['total_wrkd_hr_cnt'] <= 0) ? 0 : $running_totals['total_wrkd_hr_num'] / $running_totals['total_wrkd_hr_cnt'];

                $total_worked_contacts_hr = ($running_totals['total_activity_wrkd_hrs'] <= 0) ? 0 : (($running_totals['total_NI'] + $running_totals['total_XFER']) / $running_totals['total_activity_wrkd_hrs']);
                $total_worked_calls_hr = ($running_totals['total_activity_wrkd_hrs'] <= 0) ? 0 : (($running_totals['total_calls']) / $running_totals['total_activity_wrkd_hrs']);

                $t_ans_percent = round((($running_totals['total_AnswerMachines'] / $running_totals['total_calls']) * 100), 2);

                ?>
                <tr>
                    <th style="border-top:1px solid #000;padding:3px" class="text-left" nowrap><?= count($cluster_data) ?> Clusters:</th>
                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_activity_paid_hrs'], 2) ?></th>
                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_activity_wrkd_hrs'], 2) ?></th>
                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_calls']) ?></th>
                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_NI']) ?></th>
                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_XFER']) ?></th>


                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_AnswerMachines']) ?></th>
                    <th style="border-top:1px solid #000;padding:3px"><?= $t_ans_percent ?>%</th>
                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($total_worked_contacts_hr, 2) . ' - ' . number_format($total_worked_calls_hr, 2) ?></th>


                    <th style="border-top:1px solid #000;padding:3px"><?= number_format($running_totals['total_sale_cnt']) ?></th>

                    <th style="border-top:1px solid #000;padding:3px" class="text-center"><?= number_format($running_totals['total_paid_sale_cnt']) ?></th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($paid_sale_percent, 2) ?>%</th>

                    <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($running_totals['total_paid_sales']) ?></th>


                    <th style="border-top:1px solid #000;padding:3px" class="text-center"><?= number_format(($running_totals['total_sale_cnt'] - $running_totals['total_paid_sale_cnt'])) ?></th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($unpaid_sale_percent, 2) ?>%</th>


                    <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($running_totals['total_closing'], 2) ?>%</th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($running_totals['total_conversion'], 2) ?>%</th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right"><?= number_format($running_totals['total_yes2all'], 2) ?>%</th>

                    <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($running_totals['total_sales']) ?></th>

                    <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($running_totals['total_avg'], 2) ?></th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($running_totals['total_paid_hr'], 2) ?></th>
                    <th style="border-top:1px solid #000;padding:3px" class="text-right">$<?= number_format($running_totals['total_wrkd_hr'], 2) ?></th>

                </tr>
            </table>
            <div class="block-header bg-info-light">
                <i class="si si-clock"></i>Generated on: <?= date("g:ia m/d/Y") ?>
            </div>
            </form>
            </div>
            <?
            // GRAB DATA FROM BUFFER
            $data = ob_get_contents();
            // TURN OFF OUTPUT BUFFERING, WITHOUT OUTPUTTING
            ob_end_clean();
            // RETURN HTML
            return $data;
        }
    }
} // END OF CLASS
