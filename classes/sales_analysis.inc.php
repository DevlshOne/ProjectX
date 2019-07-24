<?php	/***************************************************************
     *	Sales Analysis
     *	Written By: Jonathan Will
     ***************************************************************/

$_SESSION['sales_analysis'] = new SalesAnalysis;



class AgentInfo
{
    public $username;
    public $cluster_array;

    
    // FALSE by default, for nightly reports
    var $skip_answeringmachines = false;
    

    public function AgentInfo($user, $cluster_id)
    {
        $this->username = strtoupper($user);
        $this->cluster_array = array(0 => $cluster_id );
    }

    public function findCluster($cluster_id)
    {
        foreach ($this->cluster_array as $cid) {
            if ($cid == $cluster_id) {
                return true;
            }
        }

        return false;
    }

    public function addClusterID($cluster_id)
    {
        $this->cluster_array[] = $cluster_id;
    }
}



class SalesAnalysis
{
    public function SalesAnalysis()
    {


        ## REQURES DB CONNECTION!
        $this->handlePOST();
    }


    public function handlePOST()
    {

//print_r($_SESSION['cached_data']);
    }

    public function handleFLOW()
    {
        if (!checkAccess('sales_analysis')) {
            accessDenied("Sales Analysis");

            return;
        } else {
            $this->makeReport();
        }
    }


    public function findAgent($agent_array, $user)
    {
        $user = strtoupper($user);
        foreach ($agent_array as $idx=>$agentobj) {
            if ($agentobj->username == $user) {
                return $idx;
            }
        }

        return -1;
    }



    public function generateData($stime, $etime, $campaign_code, $agent_cluster_id, $combine_users, $user_group, $ignore_group, $vici_campaign_id='')
    {
        $campaign_id = 0;
        //echo "Calling Sales Analysis->generateData($stime, $etime, $campaign_code, $agent_cluster_id, $combine_users, $user_group, $ignore_group)<br />\n";

        connectPXDB();

        $sql_campaign = "";
        $sql_cluster = "";
        $sql_user_group = "";
        $sql_ignore_group = "";
        $ofcsql = "";

        if (php_sapi_name() != "cli") {

            // Not in cli-mode




            // OFFICE RESTRICTION/SEARCH ABILITY
            if (
                ($_SESSION['user']['priv'] < 5) &&
                ($_SESSION['user']['allow_all_offices'] != 'yes')
                ) {
                $ofcsql = " AND `office` IN(";
                $x=0;
                foreach ($_SESSION['assigned_offices'] as $ofc) {
                    if ($x++ > 0) {
                        $ofcsql.= ',';
                    }

                    $ofcsql.= intval($ofc);
                }

                $ofcsql.= ") ";
            } else {
            }
        }


        /**
         * Campaign SQL Generation
         */
        $sql_campaign = '';
        if ($campaign_code) {
            if (is_array($campaign_code)) {
                $sql_campaign = " AND (";

                $x=0;
                foreach ($campaign_code as $code) {
                    list($campaign_id) = $_SESSION['dbapi']->queryROW("SELECT id FROM campaigns WHERE vici_campaign_id='".mysqli_real_escape_string($_SESSION['db'], $code)."' ");

                    if ($x++ > 0) {
                        $sql_campaign .= " OR ";
                    }

                    $sql_campaign .= " campaign_id='".$campaign_id."' ";
                }



                $sql_campaign.= ") ";

                // AVOID SQL ERRORS WITH EMPTY QUERY " AND () "
                if ($x == 0) {
                    $sql_campaign = "";
                }

                // SINGULAR
            } else {
                list($campaign_id) = $_SESSION['dbapi']->queryROW("SELECT id FROM campaigns WHERE vici_campaign_id='".mysqli_real_escape_string($_SESSION['db'], $campaign_code)."' ");

                $sql_campaign = " AND campaign_id='".$campaign_id."' ";
            }
        }


        if ($vici_campaign_id) {
            $sql_campaign .= " AND campaign_code='".mysqli_real_escape_string($_SESSION['db'], $vici_campaign_id)."' ";
        }


        if ($agent_cluster_id > -1) {
            if (is_array($agent_cluster_id)) {
                $sql_cluster = " AND ( ";
                $x=0;
                foreach ($agent_cluster_id as $cidx) {
                    if ($x++ > 0) {
                        $sql_cluster .= " OR ";
                    }

                    $sql_cluster .= " agent_cluster_id='".$_SESSION['site_config']['db'][$cidx]['cluster_id']."' ";
                }

                $sql_cluster .= ") ";


                if ($x == 0) {
                    $sql_cluster .= "";
                }
            } else {
                $sql_cluster = " AND agent_cluster_id='".$_SESSION['site_config']['db'][$agent_cluster_id]['cluster_id']."' ";
            }
        }


        if ($user_group) {

//print_r($user_group);

            if (is_array($user_group)) {
                $x=0;

                if (count($user_group) > 0 && trim($user_group[0]) != '') {
                    $sql_user_group = " AND ( ";


                    foreach ($user_group as $group) {
                        if ($x++ > 0) {
                            $sql_user_group .= " OR ";
                        }

                        $sql_user_group .= " call_group='".mysqli_real_escape_string($_SESSION['db'], $group)."' ";
                    }

                    $sql_user_group .= ")";
                }

                if ($x == 0) {
                    $sql_user_group = "";
                }
            } else {
                $sql_user_group = " AND call_group='".mysqli_real_escape_string($_SESSION['db'], $user_group)."' ";
            }
        }



        if ($ignore_group) {
            if (is_array($ignore_group)) {
                $x=0;

                if (count($ignore_group) > 0 && trim($ignore_group[0]) != '') {
                    $sql_ignore_group = " AND ( ";

                    foreach ($ignore_group as $group) {
                        if ($x++ > 0) {
                            $sql_ignore_group .= " AND ";
                        }

                        $sql_ignore_group .= " call_group != '".mysqli_real_escape_string($_SESSION['db'], $group)."' ";
                    }

                    $sql_ignore_group .= ")";
                }



                if ($x == 0) {
                    $sql_ignore_group = "";
                }
            } else {
                $sql_ignore_group = " AND call_group != '".mysqli_real_escape_string($_SESSION['db'], $ignore_group)."' ";
            }
        }

        $where = " WHERE sale_time BETWEEN '$stime' AND '$etime' ".

                // EXCLUDE ANYTHING ROUSTING RELATED
                " AND `is_paid` != 'roustedcc' ".

                //	" AND `account_id`='".$_SESSION['account']['id']."' ".
                        $sql_campaign.
                        $sql_cluster.
                        $sql_user_group.
                        $sql_ignore_group.
                        $ofcsql.
//							(($verifier_cluster_id > -1)?" AND verifier_cluster_id='".$_SESSION['site_config']['db'][$verifier_cluster_id]['cluster_id']."' ":"").
                        "";

        //echo $where;







        $user_group_array = array();




        // DATA ARRAY TO RETURN!
        $output_array = array();

        $agent_array = array();
        $cluster_array=array();


        $sql = "SELECT DISTINCT(agent_username), agent_cluster_id FROM transfers ".
                        " WHERE xfer_time BETWEEN '$stime' AND '$etime' AND agent_cluster_id > 0".

                        // EXCLUDE ANYTHING ROUSTING RELATED
                        " AND verifier_dispo != 'SALECC' ".

                        //" AND `account_id`='".$_SESSION['account']['id']."' ".
                        $sql_cluster.
                        $sql_campaign.
                        $sql_user_group.
                        $sql_ignore_group.
                        $ofcsql.
                        " ORDER BY agent_username ASC";
        //echo $sql;
        $res = $_SESSION['dbapi']->query($sql);
        //$res = query("SELECT DISTINCT(agent_username),agent_cluster_id FROM sales ".$where." ORDER BY agent_username ASC");
        while ($row = mysqli_fetch_row($res)) {
            $tmp =  strtoupper($row[0]);


            // USER GROUP FILTER
            // IGNORE GROUP FILTER


            if ($combine_users) {

                // JPW vs JPW2
                // SKIPS THE "2" users (IF YOU CAN FIND THERE MAIN USER
                if (endsWith($tmp, "2")) {

//					if($this->findAgent($agent_array, substr($tmp,0,strlen($tmp)-1) ) > -1   ){
//
                    //						echo "SKIPPING $tmp : ".substr($tmp,0,strlen($tmp)-1)."<br />\n";
//
                    //						continue;
                    //					}else{
                    $tmp = substr($tmp, 0, strlen($tmp)-1);

                    //						echo "TRIMMING $tmp<br />\n";
//					}
                }
            }



            $idx = $this->findAgent($agent_array, $tmp);

            if ($idx < 0) {

//				echo "Pushing user $tmp ".$row[1]."<br />\n";

                $agent_array[] = new AgentInfo($tmp, $row[1]);
            } else {

                // FIND BY CLUSTER
                // SKIP IF CLUSTER ALREADY EXISTS
                if (!$agent_array[$idx]->findCluster($row[1])) {

//					echo "Pushing cluster ".$row[1]."<br />\n";
                    $agent_array[$idx]->addClusterID($row[1]);
                }
            }
        }


        //		print_r($agent_array);
        //		exit;



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


        $total_AnswerMachines = 0;

        $ox = 0;

        //print_r($agent_array);

        foreach ($agent_array as $idx=>$agentobj) {
            $running_amount = 0;
            $running_salecnt= 0;
            $running_num_NI = 0;
            $running_num_XFER = 0;
            $running_activity_paid = 0;
            $running_activity_wrkd = 0;
            $running_activity_num_calls = 0;

            $running_paid_amount = 0;
            $running_paid_salecnt = 0;


            foreach ($agentobj->cluster_array as $cluster_id) {
                $sql = "SELECT SUM(amount) AS amount,count(id) AS salecount FROM sales ".
                                            $where.

                                            (
                                                ($combine_users)?
                                                // GET USER AND USER2
                                                " AND (agent_username IN ('".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."','".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."2') ) "
                                                :
                                                // ELSE JUST GET THE SPECIFIED USER
                                                " AND agent_username='".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."' "
                                            ).
                                            " AND agent_cluster_id='$cluster_id' ";

                //	echo "\n".$sql."\n";
                //if(stripos($agentobj->username,"2")> -1){
//
                //	echo "<br />POOOP(".$agentobj->username.")!<br />\n";
                //}

                $paidsql = $sql." AND is_paid='yes' ";
                $unpaidsql = $sql." AND is_paid='no' ";

                // GET THE UNPAID DEALS
                list($amount, $salecnt) = $_SESSION['dbapi']->queryROW($unpaidsql);


                $running_amount += $amount;
                $running_salecnt += $salecnt;

                // GET THE PAID DEALS
                list($amount, $salecnt) = $_SESSION['dbapi']->queryROW($paidsql);

                // ADDING TO THE MAIN NUMBERS
                $running_amount += $amount;
                $running_salecnt += $salecnt;
                // BUT ALSO TRACKING THEM SEPERATE
                $running_paid_amount += $amount;
                $running_paid_salecnt += $salecnt;
//
//				$testsql = "EXPLAIN ".$sql;
//				$row = querySQL($testsql);
//
//				print_r($row);
            }


            //echo $amount.' '.$salecnt."\n";

            // TOTAL CALLZ!
            $sql = "SELECT COUNT(id) FROM lead_tracking ".
                                    " WHERE `time` BETWEEN '$stime' AND '$etime' ".

                                    // EXCLUDE ANYTHING ROUSTING RELATED
                                    " AND dispo != 'SALECC' ".

                                //	" AND `account_id`='".$_SESSION['account']['id']."' ".
                                    " AND lead_id > 0 ".
                                    (
                                        ($combine_users)?
                                            // GET USER AND USER2
                                            " AND (agent_username IN ('".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."','".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."2') ) "
                                            :
                                            // ELSE JUST GET THE SPECIFIED USER
                                            " AND agent_username='".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."' "
                                        ).
                                    $ofcsql.
                                    (($campaign_id)?" AND campaign_id='".$campaign_id."' ":"");

            list($num_total_calls_px) = $_SESSION['dbapi']->queryROW($sql);



            $sql = "SELECT COUNT(id) FROM lead_tracking ".

//									" FORCE INDEX (time) ".
                                    //" USE INDEX (time) ".

                                    " WHERE `time` BETWEEN '$stime' AND '$etime' ".
                                //	" AND `account_id`='".$_SESSION['account']['id']."' ".

                                    // EXCLUDE ANYTHING ROUSTING RELATED
                                    " AND dispo != 'SALECC' ".

                                    " AND lead_id > 0 ".
                                    (
                                        ($combine_users)?
                                            // GET USER AND USER2
                                            " AND (agent_username IN('".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."','".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."2') )"
                                            :
                                            // ELSE JUST GET THE SPECIFIED USER
                                            " AND agent_username='".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."' "
                                        ).
                                    " AND (`dispo`='NI' OR `dispo`='ni') ".
                                    $ofcsql.
                                    (($campaign_id)?" AND campaign_id='".$campaign_id."' ":"");

            //echo "\n".$sql."\n";


            // NOT INTERESTED STATS
            list($num_NI) = $_SESSION['dbapi']->queryROW(
                $sql
                                    );



            // ANSWERING MACHINE STATS
            
            
            if($this->skip_answeringmachines == false){
                $sql = "SELECT COUNT(id) FROM lead_tracking ".

//									" FORCE INDEX (time) ".
                                    //" USE INDEX (time) ".

                                    " WHERE `time` BETWEEN '$stime' AND '$etime' ".
                                //	" AND `account_id`='".$_SESSION['account']['id']."' ".


                                    // EXCLUDE ANYTHING ROUSTING RELATED
                                    " AND dispo != 'SALECC' ".

                                    " AND lead_id > 0 ".
                                    (
                                        ($combine_users)?
                                            // GET USER AND USER2
                                            " AND (agent_username IN ('".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."','".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."2') ) "
                                            :
                                            // ELSE JUST GET THE SPECIFIED USER
                                            " AND agent_username='".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."' "
                                        ).
                                    " AND (`dispo`='A' OR `dispo`='a') ".
                                    $ofcsql.
                                    (($campaign_id)?" AND campaign_id='".$campaign_id."' ":"");
                //echo "\n".$sql."\n";
                // ANSWERING MACHINE STATS
                list($num_AnswerMachines) = $_SESSION['dbapi']->queryROW($sql);
            }else{
                $num_AnswerMachines = -1;
            }

            $sql = "SELECT COUNT(id) FROM transfers ".
                    " WHERE xfer_time BETWEEN '$stime' AND '$etime' ".
                //	" AND `account_id`='".$_SESSION['account']['id']."' ".
                    " AND (verifier_dispo IS NOT NULL  AND verifier_dispo != 'DROP' AND `verifier_dispo` != 'SALECC') ".
                    $ofcsql.
                    (
                        ($combine_users)?
                            // GET USER AND USER2
                            " AND (agent_username IN ('".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."','".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."2') ) "
                            :
                            // ELSE JUST GET THE SPECIFIED USER
                            " AND agent_username='".mysqli_real_escape_string($_SESSION['db'], $agentobj->username)."' "
                        ).
                    (($campaign_id > 0)?" AND campaign_id='".intval($campaign_id)."' ":"");



            //			echo $sql."<br />\n";

            list($num_XFER) = $_SESSION['dbapi']->queryROW(
                $sql
                                    );



            $activity_paid = 0;
            $activity_wrkd = 0;
            $activity_num_calls = 0;


            if ($combine_users) {
                list($activity_paid, $activity_wrkd, $activity_num_calls)  =
                            $_SESSION['dbapi']->queryROW(
                                "SELECT SUM(paid_time), SUM(activity_time),SUM(calls_today) FROM activity_log ".
                                "WHERE `time_started` BETWEEN '$stime' AND '$etime' ".
                            //	" AND `account_id`='".$_SESSION['account']['id']."' ".
                                " AND `username`='".mysqli_real_escape_string($_SESSION['db'], strtolower($agentobj->username))."' "
                                //" AND `vici_cluster_id`='".$cluster_array[$idx]."' ".
                                //(($campaign_code)?" AND campaign='".mysqli_real_escape_string($_SESSION['db'],$campaign_code)."' ":"")

                                );

                //" AND (username='".mysql_real_escape_string($agent)."' OR username='".mysql_real_escape_string($agent)."2') "
                list($activity_paid2, $activity_wrkd2, $activity_num_calls2)  =
                            $_SESSION['dbapi']->queryROW(
                                "SELECT SUM(paid_time), SUM(activity_time),SUM(calls_today) FROM activity_log ".
                                "WHERE `time_started` BETWEEN '$stime' AND '$etime' ".
                            //	" AND `account_id`='".$_SESSION['account']['id']."' ".
                                " AND `username`='".mysqli_real_escape_string($_SESSION['db'], strtolower($agentobj->username))."2' "
                                //" AND `vici_cluster_id`='".$cluster_array[$idx]."' ".
                                //(($campaign_code)?" AND campaign='".mysqli_real_escape_string($campaign_code)."' ":"")

                                );

                // PER STEVE, DONT COMBINE HOURS WORKED
                //$activity_paid += $activity_paid2;
                //$activity_wrkd += $activity_wrkd2;
                $activity_num_calls += $activity_num_calls2;
            } else {
                // GET AGENT ACTIVITY TIMER
                list($activity_paid, $activity_wrkd, $activity_num_calls)  =
                            $_SESSION['dbapi']->queryROW(
                                "SELECT SUM(paid_time), SUM(activity_time),SUM(calls_today) FROM activity_log ".
                                "WHERE `time_started` BETWEEN '$stime' AND '$etime' ".
                                //" AND `account_id`='".$_SESSION['account']['id']."' ".
                                " AND `username`='".mysqli_real_escape_string($_SESSION['db'], strtolower($agentobj->username))."' ".
                                //" AND `vici_cluster_id`='".$cluster_array[$idx]."' ".
                                (($campaign_code)?" AND campaign='".mysqli_real_escape_string($_SESSION['db'], $campaign_code)."' ":"")

                                );
            }





            /// $activity_num_calls






            $paid_hrs = $avtivity_paid = $activity_paid/60;
            $active_hrs= $activity_worked = $activity_wrkd/60;


            $closing_percent = ($num_XFER <= 0)?0:(($running_salecnt / $num_XFER) * 100);

            $conversion_percent = (($num_NI + $running_salecnt) <= 0)?0: (($running_salecnt / ($num_NI + $running_salecnt)) * 100);


            $avg_sale = ($running_salecnt <= 0)?0:($running_amount / $running_salecnt);


            $yes2all = ($activity_num_calls <= 0)?0: ($running_salecnt / $activity_num_calls) * 100;

            $paid_hr = ($paid_hrs <= 0)?0:($running_amount / $paid_hrs);

            $wrkd_hr = ($active_hrs <= 0)?0:($running_amount / $active_hrs);




            $contacts_hr = ($paid_hrs <= 0)?0:(($num_NI + $num_XFER)/$avtivity_paid);
            $calls_hr = ($paid_hrs <= 0)?0:(($activity_num_calls)/$avtivity_paid);


            $worked_contacts_hr = ($activity_worked <= 0)?0:(($num_NI + $num_XFER)/$activity_worked);
            $worked_calls_hr = ($activity_worked <= 0)?0:(($activity_num_calls)/$activity_worked);


            $output_array[$ox++] = array(

                        'agent_username'=>$agentobj->username,
                        'cluster_id'=>$agentobj->cluster_array,

                        'activity_paid'=>$avtivity_paid,
                        'activity_wrkd'=>$activity_worked,
                        'calls_today'=>$num_total_calls_px, //$activity_num_calls,

                        'num_NI'		=> $num_NI,
                        'num_XFER'	=> $num_XFER,

                        'num_AnswerMachines' => $num_AnswerMachines,

                        'contacts_per_paid_hour' => $contacts_hr,
                        'calls_per_paid_hour' => $calls_hr,

                        'contacts_per_worked_hour' => $worked_contacts_hr,
                        'calls_per_worked_hour' => $worked_calls_hr,

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

            $total_AnswerMachines += $num_AnswerMachines;

            $total_sale_cnt += $running_salecnt;
            $total_amount += $running_amount;

            $total_paid_sale_cnt += $running_paid_salecnt;
            $total_paid_amount += $running_paid_amount;
        }


        //print_r($output_array);

        if (count($output_array) > 0) {
            $total_closing = ($total_xfer <= 0)?0: (($total_sale_cnt / $total_xfer) * 100);

            $total_conversion = (($total_ni + $total_sale_cnt) <= 0)?0: (($total_sale_cnt / ($total_ni + $total_sale_cnt)) * 100);

            $total_yes2all = ($total_calls <= 0)?0:(($total_sale_cnt / $total_calls) * 100);

            $total_avg = ($total_sale_cnt <= 0)?0:($total_amount / $total_sale_cnt);

            $total_paid_hr = ($total_paid_hrs <= 0)?0:($total_amount / $total_paid_hrs);

            $total_wrkd_hr = ($total_active_hrs <= 0)?0:($total_amount / $total_active_hrs);


            ////
            $total_contacts_hr = ($total_paid_hrs <= 0)?0:(($total_ni + $total_xfer)/$total_paid_hrs);
            $total_calls_hr = ($total_paid_hrs <= 0)?0:(($total_calls)/$total_paid_hrs);
            //
            $total_worked_contacts_hr = ($total_active_hrs <= 0)?0:(($total_ni + $total_xfer)/$total_active_hrs);
            $total_worked_calls_hr = ($total_active_hrs <= 0)?0:(($total_calls)/$total_active_hrs);
            ////
        }

        $totals = array(

        // ADDED IN THE LOOP
            'total_activity_paid_hrs' => $total_paid_hrs,
            'total_activity_wrkd_hrs' => $total_active_hrs,
            'total_calls' => $total_calls,
            'total_NI' => $total_ni,
            'total_XFER' => $total_xfer,

            'total_AnswerMachines' => $total_AnswerMachines,

            'total_contacts_per_paid_hour' => $total_contacts_hr,
            'total_calls_per_paid_hour' => $total_calls_hr,

            'total_contacts_per_worked_hour' => $total_worked_contacts_hr,
            'total_calls_per_worked_hour' => $total_worked_calls_hr,

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

        //print_r($totals);


        // SORTING MOTHERFUCKER
        uasort($output_array, 'paidSorter');//($a, $b)


        return array($output_array, $totals);
    }





    public function makeReport()
    {



        //echo $this->makeHTMLReport('1430377200', '1430463599', 'BCSFC', -1, 1,null , array("SYSTEM-TRNG-SOUTH", "SYSTEM-TRNG","SYS-TRNG-SOUTH-AM")) ;

        if (isset($_POST['generate_report'])) {
            $timestamp = strtotime($_REQUEST['strt_date_month']."/".$_REQUEST['strt_date_day']."/".$_REQUEST['strt_date_year']." ".$_REQUEST['strt_time_hour'].":".$_REQUEST['strt_time_min'].$_REQUEST['strt_time_timemode']);
            $timestamp2 = strtotime($_REQUEST['end_date_month']."/".$_REQUEST['end_date_day']."/".$_REQUEST['end_date_year']." ".$_REQUEST['end_time_hour'].":".$_REQUEST['end_time_min'].$_REQUEST['end_time_timemode']);
        } else {
            $timestamp = mktime(0,0,0);
            $timestamp2 = mktime(23,59,59);
        }



        if (!isset($_REQUEST['no_nav'])) {
            ?><form id="saleanal_report" method="POST" action="<?=$_SERVER['PHP_SELF']?>?area=sales_analysis&no_script=1" onsubmit="return genReport(this, 'sales')">

				<input type="hidden" name="generate_report">


			<table border="0" width="100%">
			<tr>
				<td height="40" class="pad_left ui-widget-header">

					Daily Sales Analysis Report

				</td>
			</tr>
			<tr>
				<td colspan="2">

<script>
$(function() {
  let timeFields = $('#startTimeFilter, #endTimeFilter');
  let retainTime = '<? echo $_REQUEST['timeFilter'] === "on"; ?>';
  if(retainTime) {
    $(timeFields).show();
    $('#timeFilter').prop('checked', true);
  } else {
  $(timeFields).hide();
      $('#timeFilter').prop('checked', false);
}
  $('#timeFilter').on('click', function() {
    $(timeFields).toggle();
  });
});
</script>

					<table border="0">
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
					<tr>
						<th>Agent Cluster:</th>
						<td><?php

                            echo $this->makeClusterDD("agent_cluster_id", (!isset($_REQUEST['agent_cluster_id']) || intval($_REQUEST['agent_cluster_id']) < 0)?-1:$_REQUEST['agent_cluster_id'], '', ""); ?></td>
					</tr>
					<?/*<tr>
						<th>Verifier Cluster:</th>
						<td><?php

                            echo $this->makeClusterDD("verifier_cluster_id", $_REQUEST['verifier_cluster_id'], '', ""); ?></td>
					</tr>**/?>
					<tr>
						<th>PX Campaign ID:</th>
						<td><?php

                            echo makeCampaignDD("campaign_id", $_REQUEST['campaign_id'], '', ""); ?></td>
					</tr>

					<tr>
						<th>VICI Campaign:</th>
						<td><?php

                            echo $this->makeViciCampaignDD('vici_campaign_code', $_REQUEST['vici_campaign_code'], '', ""); ?></td>
					</tr>

					<tr>
						<th>User Group:</th>
						<td><?php

                            //echo $this->makeViciUserGroupDD("user_group", $_REQUEST['user_group'], '', "");
                            echo makeViciUserGroupDD("user_group[]", $_REQUEST['user_group'], '', "", 7)
                        ?></td>
					</tr>
					<tr>
						<th>Ignore User Group:</th>
						<td><?php

                            //echo $this->makeViciUserGroupDD("ignore_group", $_REQUEST['ignore_group'], '', "");
                            echo makeViciUserGroupDD("ignore_group[]", $_REQUEST['ignore_group'], '', "", 7, "[None]"); ?></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							<input type="hidden" name="combine_users" value="<?=($_REQUEST['combine_users'] > 0 || !isset($_REQUEST['combine_users']))?1:0?>">
							<input type="checkbox" id="combiner" onclick="this.form.combine_users.value = (this.checked)?1:0" <?=($_REQUEST['combine_users'] > 0 || !isset($_REQUEST['combine_users']))?" CHECKED ":''?> >
							Combine Left/Right Users
						</td>
					</tr>
					<tr>
						<th>Include Answering Machine stats</th>
						<td>
							<input type="checkbox" name="include_answer_machines" value="1" <?=($_REQUEST['include_answer_machines'])?' CHECKED ':'' ?>/>
						</td>
					</tr>
					<tr>
						<th colspan="2">

							<span id="sales_loading_plx_wait_span" class="nod"><img src="images/ajax-loader.gif" border="0" /> Loading, Please wait...</span>

							<span id="sales_submit_report_button">
								<input type="button" value="Generate PRINTABLE" onclick="genReport(getEl('saleanal_report'), 'sales', 1)">

								&nbsp;&nbsp;&nbsp;&nbsp;

								<input type="submit" value="Generate">


							</span>

						</th>
					</tr>
					</table>


				</td>
			</tr>

			</table>
			</form>
			<br /><br /><?php
        } else {
            ?><meta charset="UTF-8">
			<meta name="google" content="notranslate">
			<meta http-equiv="Content-Language" content="en"><?php
        }



        if (isset($_POST['generate_report'])) {
            $time_started = microtime_float();


            ## TIME
            $timestamp = strtotime($_REQUEST['strt_date_month']."/".$_REQUEST['strt_date_day']."/".$_REQUEST['strt_date_year']." ".$_REQUEST['strt_time_hour'].":".$_REQUEST['strt_time_min'].$_REQUEST['strt_time_timemode']);
            $timestamp2 = strtotime($_REQUEST['end_date_month']."/".$_REQUEST['end_date_day']."/".$_REQUEST['end_date_year']." ".$_REQUEST['end_time_hour'].":".$_REQUEST['end_time_min'].$_REQUEST['end_time_timemode']);
            /*
            $timestamp = strtotime($_REQUEST['strt_date_month']."/".$_REQUEST['strt_date_day']."/".$_REQUEST['strt_date_year']);
            $timestamp2 = strtotime($_REQUEST['end_date_month']."/".$_REQUEST['end_date_day']."/".$_REQUEST['end_date_year']);
            */

            ## TIMEFRAMES
            if (!isset($_REQUEST['strt_time_hour'])) {
                $stime = mktime(0, 0, 0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));
                $etime = mktime(23, 59, 59, date("m", $timestamp2), date("d", $timestamp2), date("Y", $timestamp2));
                #echo "Human Start : " . date("r", $stime) . PHP_EOL;
                #echo "Human End : " . date("r", $etime) . PHP_EOL;
            } else {
                $stime = mktime(date("H", $timestamp), date("i", $timestamp), 0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));
                $etime = mktime(date("H", $timestamp2), date("i", $timestamp2), 59, date("m", $timestamp2), date("d", $timestamp2), date("Y", $timestamp2));
                #echo "Human Start : " . date("r", $stime) . PHP_EOL;
                #echo "Human End : " . date("r", $etime) . PHP_EOL;
            }

            ## AGENT CLUSTER
            $agent_cluster_id = intval($_REQUEST['agent_cluster_id']);


            ## CAMPAIGN
            $campaign_code = trim($_REQUEST['campaign_id']);

            $combine_users = (intval($_REQUEST['combine_users']) > 0)?true:false;

            //			$user_group = trim($_REQUEST['user_group']);
            //			$ignore_group = trim($_REQUEST['ignore_group']);


            $vici_campaign_code = trim($_REQUEST['vici_campaign_code']);

            
            
            
            if($_REQUEST['include_answer_machines']){
                $this->skip_answeringmachines = false;
            }else{
                $this->skip_answeringmachines = true;
            }
            
            
            
            ## GENERATE AND DISPLAY REPORT
            $html = $this->makeHTMLReport($stime, $etime, $campaign_code, $agent_cluster_id, $combine_users, $_REQUEST['user_group'], $_REQUEST['ignore_group'], $vici_campaign_code);


            /*?><div style="border:1px dotted #999;padding:5px;margin:5px;width:950px"><?*/

            if ($html == null) {
                echo '<span style="font-size:14px;font-style:italic;">No results found, for the specified values.</span><br />';
            } else {
                echo $html;
            }

            /*?></div><?*/

            $time_ended = microtime_float();


            $time_taken = $time_ended - $time_started;


            echo '<br /><span style="float:bottom;color:#fff">Load time: '.$time_taken.'</span>';

            if (!isset($_REQUEST['no_nav'])) {
                ?><script>
					$(document).ready( function () {

					    $('#sales_anal_table').DataTable({

							"lengthMenu": [[ -1, 20, 50, 100, 500], ["All", 20, 50, 100,500 ]]


					    });



					} );
				</script><?php
            }
        }
    }


    public function makeHTMLReport($stime, $etime, $campaign_code, $agent_cluster_id, $combine_users, $user_group, $ignore_group, $vici_campaign_code='')
    {
        echo '<span style="font-size:9px">makeHTMLReport('."$stime, $etime, $campaign_code, $agent_cluster_id, $combine_users, $user_group, $ignore_group, $vici_campaign_code) called</span><br /><br />\n";


        list($agent_data_arr, $totals) = $this->generateData($stime, $etime, $campaign_code, $agent_cluster_id, $combine_users, $user_group, $ignore_group, $vici_campaign_code);

        if (count($agent_data_arr) < 1) {
            return null;
        }

        // ACTIVATE OUTPUT BUFFERING
        ob_start();
        ob_clean(); ?><h1><?php

            if ($campaign_code) {
                echo $campaign_code.' ';
            }

        echo "Sales Analysis - ";

        if ($agent_cluster_id >= 0) {
            echo $_SESSION['site_config']['db'][$agent_cluster_id]['name'].' - ';
        }

        //			if($user_group){
//
        //				if(is_array($user_group)){
//
        //					if(trim($user_group[0]) != ''){
//
        //						echo implode($user_group,' | ');
        //						echo " - ";
        //					}
//
//
        //				}else{
        //					echo $user_group.' - ';
        //				}
        //			}


        if (date("m-d-Y", $stime) == date("m-d-Y", $etime)) {
            echo date("m-d-Y", $stime);
        } else {
            echo date("m-d-Y", $stime).' to '.date("m-d-Y", $etime);
        } ?></h1>
		<h3><?php

            if ($user_group) {
                if (is_array($user_group)) {
                    if (trim($user_group[0]) != '') {
                        echo '<b>User Groups:</b>'.implode($user_group, ' | ');
                        echo "<br />";
                    }
                } else {
                    echo '<b>User Group:</b>'.$user_group."<br />";
                }
            }


        if ($ignore_group) {
            if (is_array($ignore_group)) {
                if (trim($ignore_group[0]) != '') {
                    echo '<b>Ignoring Groups:</b> '.implode($ignore_group, ' | ');
                    echo "<br />";
                }
            } else {
                echo '<b>Ignoring Group:</b> '.$ignore_group.'<br />';
            }
        } ?></h3>
		<table id="sales_anal_table" style="width:100%" border="0"  cellspacing="1">
		<thead>
		<tr>
			<th align="left">Agent</th>
			<th title="Number of hours being Paid for">PD HRS</th>
			<th title="Number of hours of Activity tracked">WRKD HRS</th>
			<th title="Total number of calls for the day">Total Calls</th>
			<th title="Number of Calls that were NOT INTERESTED">NI</th>
			<th title="Number of Transfers">XFERS</th>
			<?php 
			
			if($this->skip_answeringmachines == false){
			     ?><th title="Number of Answering Machine calls">A</th>
				<th title="Percentage of calls that are Answering Machines">%ANS</th><?php 
			}
			?>
			<th title="Contacts per Worked hour, and Calls per Worked hour">Con&amp;Calls/hr</th>


			<th>TOTAL SALES</th>
			<th>PAID SALES</th>
			<th>PAID %</th>
			<th>UNPAID SALES</th>
			<th>UNPAID %</th>

			<th align="right" title="Closing Percentage">CLOSE %</th>
			<th align="right" title="Conversion Percentage">CON%</th>
			<th align="right">YES 2 ALL %</th>
			<th align="right">TOTAL SALES</th>
			<th align="right">AVG SALE</th>
			<th align="right">PD $/HR</th>
			<th align="right">WRKD $/HR</th>
		</tr>
		</thead>
		<tbody><?php



        foreach ($agent_data_arr as $agent_data) {
            $paid_sale_percent = ($agent_data['sale_cnt'] <= 0)?0:round(((float)$agent_data['paid_sale_cnt'] / $agent_data['sale_cnt']) * 100, 2);

            $unpaid_sale_percent = 100 - $paid_sale_percent;


            $ans_percent = round((($agent_data['num_AnswerMachines'] / $agent_data['calls_today']) * 100), 2); ?><tr>
				<td><?=htmlentities(strtoupper($agent_data['agent_username']))?></td>
				<td align="center"><?=number_format($agent_data['activity_paid'], 2)?></td>
				<td align="center"><?=number_format($agent_data['activity_wrkd'], 2)?></td>
				<td align="center"><?=number_format($agent_data['calls_today'])?></td>
				<td align="center"><?=number_format($agent_data['num_NI'])?></td>
				<td align="center"><?=number_format($agent_data['num_XFER'])?></td><?
				
				if($this->skip_answeringmachines == false){
				    ?><td align="center"><?=number_format($agent_data['num_AnswerMachines'])?></td>
					<?/** PER PAID HOUR <td align="center"><?=number_format($agent_data['contacts_per_paid_hour'], 2)?>&nbsp;/&nbsp;<?=number_format($agent_data['calls_per_paid_hour'], 2)?></td> **/?>

					<td align="center"><?=$ans_percent?>%</td><?
				}
				?>

				<td align="center"><?=number_format($agent_data['contacts_per_worked_hour'], 2)?>&nbsp;/&nbsp;<?=number_format($agent_data['calls_per_worked_hour'], 2)?></td>







				<td align="center"><?=number_format($agent_data['sale_cnt'])?></td>


				<td align="left">

					<?=number_format($agent_data['paid_sale_cnt'])?> ($<?=number_format($agent_data['paid_sales_total'])?>)

				</td>
				<td align="right"><?=number_format($paid_sale_percent, 2)?>%</td>


				<td align="center"><?=number_format(($agent_data['sale_cnt']-$agent_data['paid_sale_cnt']))?></td>
				<td align="right"><?=number_format($unpaid_sale_percent, 2)?>%</td>


				<td align="right"><?=number_format($agent_data['closing_percent'], 2)?>%</td>
				<td align="right"><?=number_format($agent_data['conversion_percent'], 2)?>%</td>
				<td align="right"><?=number_format($agent_data['yes2all_percent'], 2)?>%</td>
				<td align="right">$<?=number_format($agent_data['sales_total'])?></td>

				<td align="right">$<?=number_format($agent_data['avg_sale'], 2)?></td>
				<td align="right">$<?=number_format($agent_data['paid_hr'], 2)?></td>
				<td align="right">$<?=number_format($agent_data['wrkd_hr'], 2)?></td>
			</tr><?php
        } ?></tbody><?php


        $paid_sale_percent = round(((float)$totals['total_paid_sale_cnt'] / $totals['total_sale_cnt']) * 100, 2);

        $unpaid_sale_percent = 100 - $paid_sale_percent;

        $t_ans_percent = round((($totals['total_AnswerMachines'] / $totals['total_calls']) * 100), 2); ?><tfoot>
		<tr>
			<th style="border-top:1px solid #000" align="left">Total Agents: <?=count($agent_data_arr)?></th>
			<th style="border-top:1px solid #000"><?=number_format($totals['total_activity_paid_hrs'], 2)?></th>
			<th style="border-top:1px solid #000"><?=number_format($totals['total_activity_wrkd_hrs'], 2)?></th>
			<th style="border-top:1px solid #000"><?=number_format($totals['total_calls'])?></th>
			<th style="border-top:1px solid #000"><?=number_format($totals['total_NI'])?></th>
			<th style="border-top:1px solid #000"><?=number_format($totals['total_XFER'])?></th>
			<?
				
				if($this->skip_answeringmachines == false){
				    ?><th style="border-top:1px solid #000"><?=number_format($totals['total_AnswerMachines'])?></th>
					<th style="border-top:1px solid #000"><?=$t_ans_percent?>%</th><?
				}
			?>
			<th style="border-top:1px solid #000"><?=number_format($totals['total_contacts_per_worked_hour'], 2).' - '.number_format($totals['total_calls_per_worked_hour'], 2)?></th>



			<th style="border-top:1px solid #000"><?=number_format($totals['total_sale_cnt'])?></th>

			<th style="border-top:1px solid #000" align="left"><?=number_format($totals['total_paid_sale_cnt'])?> ($<?=number_format($totals['total_paid_sales'])?>)</th>
			<th style="border-top:1px solid #000" align="right"><?=number_format($paid_sale_percent, 2)?>%</th>

			<th style="border-top:1px solid #000" align="center"><?=number_format(($totals['total_sale_cnt']-$totals['total_paid_sale_cnt']))?></th>
			<th style="border-top:1px solid #000" align="right"><?=number_format($unpaid_sale_percent, 2)?>%</th>


			<th style="border-top:1px solid #000" align="right"><?=number_format($totals['total_closing'], 2)?>%</th>
			<th style="border-top:1px solid #000" align="right"><?=number_format($totals['total_conversion'], 2)?>%</th>
			<th style="border-top:1px solid #000" align="right"><?=number_format($totals['total_yes2all'], 2)?>%</th>

			<th style="border-top:1px solid #000" align="right">$<?=number_format($totals['total_sales'])?></th>

			<th style="border-top:1px solid #000" align="right">$<?=number_format($totals['total_avg'], 2)?></th>
			<th style="border-top:1px solid #000" align="right">$<?=number_format($totals['total_paid_hr'], 2)?></th>
			<th style="border-top:1px solid #000" align="right">$<?=number_format($totals['total_wrkd_hr'], 2)?></th>

		</tr>
		</tfoot>
		</table><?php

        // GRAB DATA FROM BUFFER
        $data = ob_get_contents();

        // TURN OFF OUTPUT BUFFERING, WITHOUT OUTPUTTING
        ob_end_clean();

        // RETURN HTML
        return $data;
    }






    public function makeClusterDD($name, $selected, $css, $onchange)
    {
        $out = '<select name="'.$name.'" id="'.$name.'" ';

        $out .= ($css)?' class="'.$css.'" ':'';
        $out .= ($onchange)?' onchange="'.$onchange.'" ':'';
        $out .= '>';

        $out .= '<option value="-1" '.(($selected == '-1')?' SELECTED ':'').'>[All]</option>';


        foreach ($_SESSION['site_config']['db'] as $dbidx=>$db) {
            $out .= '<option value="'.$dbidx.'" ';
            $out .= ($selected == $dbidx)?' SELECTED ':'';
            $out .= '>'.htmlentities($db['name']).'</option>';
        }



        $out .= '</select>';

        return $out;
    }


    public function makeViciCampaignDD($name, $selected, $css, $onchange)
    {
        $cache_area_name = 'vici_campaign_code';

        if (!$_SESSION['cached_data']) {
            $_SESSION['cached_data'] = array();
        }

        // CHECK IF ITS FIRST TIME RUNNING, OR IF ITS OVERDUE TIME TO REFRESH
        if (!$_SESSION['cached_data'][$cache_area_name] || ($_SESSION['cached_data'][$cache_area_name]['time']+300) < time()) {

            // RESET/REFRESH
            $_SESSION['cached_data'][$cache_area_name] = array();

            $res = $_SESSION['dbapi']->query("SELECT campaign_code FROM campaign_codes WHERE 1 ORDER by campaign_code ASC"); //account_id='".$_SESSION['account']['id']."'

            $_SESSION['cached_data'][$cache_area_name]['data'] = array();

            while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
                $_SESSION['cached_data'][$cache_area_name]['data'][] = $row;
            }

            // RESET LAST UPDATED TIME/START TIMER FOR REFRESH
            $_SESSION['cached_data'][$cache_area_name]['time'] = time();
        }


        $out = '<select name="'.$name.'" id="'.$name.'" ';

        $out .= ($css)?' class="'.$css.'" ':'';
        $out .= ($onchange)?' onchange="'.$onchange.'" ':'';
        $out .= '>';


        $out .= '<option value="" '.(($selected == '')?' SELECTED ':'').'>[All]</option>';



        foreach ($_SESSION['cached_data'][$cache_area_name]['data'] as $row) {
            $out .= '<option value="'.$row['campaign_code'].'" ';
            $out .= ($selected == $row['campaign_code'])?' SELECTED ':'';
            $out .= '>'.htmlentities($row['campaign_code']).'</option>';
        }



        $out .= '</select>';

        return $out;
    }

    public function makeViciUserGroupDD($name, $selected, $css, $onchange)
    {
        return makeViciUserGroupDD($name, $selected, $css, $onchange);

//
//		$res = query("SELECT DISTINCT(user_group) AS user_group FROM users WHERE user_group IS NOT NULL");
//
//
//
//		$out = '<select name="'.$name.'" id="'.$name.'" ';
//
//		$out .= ($css)?' class="'.$css.'" ':'';
//		$out .= ($onchange)?' onchange="'.$onchange.'" ':'';
//		$out .= '>';
//
//
//		$out .= '<option value="" '.(($selected == '')?' SELECTED ':'').'>[All]</option>';
//
//
//
//		while($row = mysqli_fetch_array($res)){
//
//			$out .= '<option value="'.$row['user_group'].'" ';
//			$out .= ($selected == $row['user_group'])?' SELECTED ':'';
//			$out .= '>'.htmlentities($row['user_group']).'</option>';
//		}
//
//
//
//		$out .= '</select>';
//
//		return $out;
    }



    /**
     * Send Report emails - Reads the report email table and determines what reports need to go out
     *
     *
     *
     */
    public function sendReportEmails()
    {
        $curtime = time();

        // INIT VARIABLES
        $stime= $etime = 0;
        $campaign_code = null;
        $agent_cluster_idx = -1;
        $agent_cluster_id = 0;
        $combine_users =1;
        $user_group = null;
        $ignore_group = null;

        connectPXDB();

        $res = $_SESSION['dbapi']->query(
            "SELECT * FROM report_emails ".
                    " WHERE enabled='yes' "
                    );



        echo date("H:i:s m/d/Y")." - Starting sendReportEmails() funtime...\n";

        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
            echo date("H:i:s m/d/Y")." - Checking REID#".$row['id']." report id:".$row['report_id']." interval:".$row['interval']." last_ran:".$row['last_ran']."\n";

            // CHECK INTERVAL AND TIME LAST RAN
            switch ($row['interval']) {
            default:
                echo date("H:i:s m/d/Y")." - ERROR: UNKNOWN or NEW/uncompleted interval: ".$row['interval']."\n";
                continue 2;
            case 'daily':

                // GET TODAYS TIME, from 00:00:00
                $tmptime = mktime(0, 0, 0);

                // APPLY TIME OFFSET
                $tmptime += $row['trigger_time'];


                // NOT TIME TO RUN YET TODAY
                if ($curtime < $tmptime) {

                    // IF ITS NOT TIME, SKIP
                    echo date("H:i:s m/d/Y")." - DAILY RE ID#".$row['id']." skipped, not time yet today.\n";
                    continue 2;
                }

                // HAS IT BEEN LONGER THAN A DAY? (With a 3 minute 'grace' period, to be cron friendly)
                if ($curtime < ($row['last_ran'] + 86220)) {

                    // IF ITS NOT TIME, SKIP
                    echo date("H:i:s m/d/Y")." - DAILY RE ID#".$row['id']." skipped, hasn't been a day.\n";
                    continue 2;
                }

                // GRAB REPORT NAME/DATA
                $report = $_SESSION['dbapi']->querySQL("SELECT * FROM reports WHERE id='".$row['report_id']."' ");

                $report_name = $report['name'];

                // SETUP DEFAULT TIME FRAMES
                $stime = mktime(0, 0, 0);
                $etime = $stime + 86399;


                break;
            case 'weekly':

                $diw = date("w");

                // GET TODAYS TIME, from 00:00:00
                $tmptime = mktime(0, 0, 0);

                // SUBTRACT DAY OFFSET, TO GET BEGINNING OF WEEK
                $tmptime -= ($diw * 86400);

                // SAVE THIS FOR LATER
                $startofweek = $tmptime;

                // APPLY TIME OFFSET
                $tmptime += $row['trigger_time'];

                // IS IT TIME TO RUN YET?
                if ($curtime < $tmptime) {

                    // IF ITS NOT TIME, SKIP
                    echo date("H:i:s m/d/Y")." - WEEKLY RE ID#".$row['id']." skipped, not time yet this week.\n";
                    continue 2;
                }

                // HAS IT BEEN LONGER THAN A WEEK?
                if ($curtime < ($row['last_ran'] + 604620)) {

                    // IF ITS NOT TIME, SKIP
                    echo date("H:i:s m/d/Y")." - WEEKLY RE ID#".$row['id']." skipped, hasn't been a week since last run.\n";
                    continue 2;
                }




                // GRAB REPORT NAME/DATA
                $report = $_SESSION['dbapi']->querySQL("SELECT * FROM reports WHERE id='".$row['report_id']."' ");

                $report_name = $report['name'];

                // SETUP DEFAULT TIME FRAMES
                $stime = $tmptime - 604800;
                $stime = mktime(23, 59, 59, date("m", $stime), date("d", $stime), date("Y", $stime)) + 1;

                $etime = $stime + 604799;
    //			$etime = mktime(23,59,59, date("m", $etime), date("d", $etime), date("Y", $etime));

                //$etime = $stime + 604799;

                break;
            case 'monthly':

                // GET FIRST DAY OF THE MONTH
                $tmptime = mktime(0, 0, 0, date("m"), 1, date("Y"));

                // SAVE THE FIRST DAY OF MONTH TIME FOR SEXYTIME LATER
                $firstofthemonth = $tmptime; // WAKE UP, WAKE UP, GET UP, GET UP

                // APPLY TIME OFFSET
                $tmptime += $row['trigger_time'];

                // IS IT TIME TO RUN YET?
                if ($curtime < $tmptime) {

                    // IF ITS NOT TIME, SKIP
                    echo date("H:i:s m/d/Y")." - MONTHLY RE ID#".$row['id']." skipped, not time yet this month.\n";
                    continue 2;
                }


                // HAS IT BEEN LONGER THAN A WEEK? (With a 3 minute 'grace' period, to be cron friendly)

                if (date("m", $row['last_ran']) == date("m", $curtime)) {

                    // IF ITS NOT TIME, SKIP
                    echo date("H:i:s m/d/Y")." - MONTHLY RE ID#".$row['id']." skipped, already ran this month.\n";
                    continue 2;
                }

                // GRAB REPORT NAME/DATA
                $report = $_SESSION['dbapi']->querySQL("SELECT * FROM reports WHERE id='".$row['report_id']."' ");

                $report_name = $report['name'];

                // SETUP DEFAULT TIME FRAMES - THIS MONTH
                $stime = $firstofthemonth;
                $etime = mktime(23, 59, 59, date("m", $curtime), date("t", $curtime), date("Y", $curtime));


                break;
            }

            $cluster_id = 0;
            $source_cluster_id = 0;
            $ignore_source_cluster_id = 0;

            $source_user_group = null;

            $report_type = 'cold';

            // EXECUTE THE REPORT SETTINGS, TO POPULATE OR OVERWRITE REPORT VARIABLES/SETTINGS
            echo date("H:i:s m/d/Y")." - Loading PHP Variables/SETTINGS for report:\n".$row['settings']."\n";

            $eres = eval($row['settings']);


            $html = null;

            // SWITCH REPORT TYPE
            switch (intval($row['report_id'])) {
            default:

                echo date("H:i:s m/d/Y")." - ERROR: report_id: ".$row['report_id']." hasn't been added yet.\n";
                continue;

            case 1:

                if ($agent_cluster_id > 0) {
                    $agent_cluster_idx = getClusterIndex($agent_cluster_id);
                }


                // GENERATE REPORT HTML ( RETURNS NULL IF THERE ARE NO RECORDS TO REPORT ON!)
                // NOTE: THE VARIABLES THAT APPEAR 'uninitialized' ARE LOADED FROM THE 'settings' DB FIELD
                $html = $this->makeHTMLReport($stime, $etime, $campaign_code, $agent_cluster_idx, $combine_users, $user_group, $ignore_group);

                if ($html == null) {
                    echo date("H:i:s m/d/Y")." - NOTICE: Skipping sending report, no records found\n";
                    continue 2;
                }


                $textdata = ucfirst($row['interval']).' '.$report_name."\n\n".

                        "Time frame: ".date("m/d/Y", $stime)." - ".date("m/d/Y", $etime)."\n".
                        (($campaign_code)?"Campaign Code: ".$campaign_code."\n":'').
                        (($agent_cluster_idx)?"Cluster IDX: ".$agent_cluster_idx."\n":'').
                        (($combine_users)?"Combine users: ".$combine_users."\n":'').
                        (($user_group)?" User Group:".$user_group."\n":'').
                        "\nReport is attached (or view email as HTML).";



                break;

            case 2:

                $html = $_SESSION['agent_call_stats']->makeHTMLReport($stime, $etime, $cluster_id, $user_group, null, $source_cluster_id, $ignore_source_cluster_id, $source_user_group);

                if ($html == null) {
                    echo date("H:i:s m/d/Y")." - NOTICE: Skipping sending report, no records found\n";
                    continue 2;
                }

                $textdata = ucfirst($row['interval']).' '.$report_name."\n\n".

                        "Time frame: ".date("m/d/Y", $stime)." - ".date("m/d/Y", $etime)."\n".
                        (($agent_cluster_idx)?"Cluster IDX: ".$agent_cluster_idx."\n":'').
                        (($user_group)?" User Group:".$user_group."\n":'');

                if (count($source_user_group) > 0) {
                    $textdata .= "Source group(s): ";
                    $z=0;
                    foreach ($source_user_group as $sgrp) {
                        $textdata .= ($z++ > 0)?", ":'';
                        $textdata .= $sgrp;
                    }
                    $textdata .= "\n";
                }


                $textdata .=	"\nReport is attached (or view email as HTML).";
                break;

            case 3:

                $html = $_SESSION['summary_report']->makeHTMLReport($report_type, $stime, $etime);

                if ($html == null) {
                    echo date("H:i:s m/d/Y")." - NOTICE: Skipping sending report, no records found\n";
                    continue 2;
                }

                $textdata = ucfirst($row['interval']).' '.$report_name."\n\n".

                        "Time frame: ".date("m/d/Y", $stime)." - ".date("m/d/Y", $etime)."\n".
                        "Report type: ".$report_type."\n"
                        ;




                $textdata .=	"\nReport is attached (or view email as HTML).";
                break;
            }
            // REPORT HAS BEEN GENERATED, DO THE EMAIL SHIT HERE

            if (!trim($html)) {
                echo date("H:i:s m/d/Y")." - ERROR: no html was generated to email, skipping!\n";
                continue;
            }





            // BUILD HTML EMAIL
            $subject = ucfirst($row['interval']).' '.$report_name.' '.$row['subject_append'].' - '.date("m/d/Y", $curtime);

            $filename = "system_report-".date("m-d-Y")."-".preg_replace("/[^a-zA-Z0-9-_]/", "_", ucfirst($row['interval']).'-'.$report_name).".html";

            $headers   = array(
                            "From"		=> "ATC Reporting <support@advancedtci.com>",
                            "Subject"	=> $subject,
                            "X-Mailer"	=> "ATC Reporting System",
                            "Reply-To"	=> "ATC Reporting <support@advancedtci.com>"
                        );

            $mime = new Mail_mime(array('eol' => "\n"));

            // SET TEXT AND HTML CONTENT BODIES
            $mime->setTXTBody($textdata, false);
            $mime->setHTMLBody($html, false);

            // ATTACH HTML REPORT AS FILE AS WELL
            $mime->addAttachment($html, "text/html", $filename, false, "quoted-printable", "attachment");

            // BUILD THE EMAIL SHIT
            $mail_body = $mime->get();
            $mail_header=$mime->headers($headers);

            $mail =& Mail::factory('mail');

            // SEND IT
            if ($mail->send($row['email_address'], $mail_header, $mail_body) != true) {
                echo date("H:i:s m/d/Y")." - ERROR: Mail::send() call failed sending to ".$row['email_address'];
            } else {
                echo date("H:i:s m/d/Y")." - Successfully emailed ".$row['email_address']." - ".$subject."\n";

                // UPDATE last_ran TIME

                $dat = array();
                $dat['last_ran'] = $curtime;
                aedit($row['id'], $dat, "report_emails");
            }
        } // END WHILE (report emails)


        echo date("H:i:s m/d/Y")." - Finished sendReportEmails()\n";
    }
} // END OF CLASS
