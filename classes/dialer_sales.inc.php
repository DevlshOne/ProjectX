<?php /***************************************************************
 *    Dialer Sales
 *    Written By: Dave
 ***************************************************************/

    $_SESSION['dialer_sales'] = new DialerSales;


    class DialerSales
    {
        public function DialerSales()
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
            if (!checkAccess('dialer_sales')) {
                accessDenied("Dialer Sales");
                return;
            } else {
                $this->makeReport();
            }
        }

        public function generateData($stime, $etime, $agent_cluster_id, $area_code)
        {
            if (php_sapi_name() != "cli") {
                // Not in cli-mode
                // OFFICE RESTRICTION/SEARCH ABILITY
                if (($_SESSION['user']['priv'] < 5) && ($_SESSION['user']['allow_all_offices'] != 'yes')) {
                    $ofcsql = " AND `office` IN(";
                    $x = 0;
                    foreach ($_SESSION['assigned_offices'] as $ofc) {
                        if ($x++ > 0) {
                            $ofcsql .= ',';
                        }
                        $ofcsql .= intval($ofc);
                    }
                    $ofcsql .= ") ";
                } else {
                }
            }
            $sql = "SELECT `agent_cluster_id`, LEFT(`phone`,3) AS `area_code`, SUM(`amount`) AS `total_sales` FROM `sales` WHERE `sale_time` BETWEEN '" . $stime . "' AND '" . $etime . "' ";
            if ($agent_cluster_id > -1) {
                if (is_array($agent_cluster_id)) {
                    $sql .= " AND ( ";
                    $x = 0;
                    foreach ($agent_cluster_id as $cidx) {
                        if ($x++ > 0) {
                            $sql .= " OR ";
                        }
                        $sql .= " `agent_cluster_id`='" . $_SESSION['site_config']['db'][$cidx]['cluster_id'] . "' ";
                    }
                    $sql .= ") ";
                    if ($x == 0) {
                        $sql .= "";
                    }
                } else {
                    $sql .= " AND `agent_cluster_id`='" . $_SESSION['site_config']['db'][$agent_cluster_id]['cluster_id'] . "' ";
                }
            }
            if ($area_code > 0) {
                $sql .= " AND `phone` LIKE '" . $area_code . "%'";
            }
            $res = $_SESSION['dbapi']->query($sql);

            // SORTING MOTHERFUCKER
            uasort($output_array, 'paidSorter');//($a, $b)

            return array($output_array, $totals);
        }

        public function makeReport()
        {
            //echo $this->makeHTMLReport('1430377200', '1430463599', 'BCSFC', -1, 1,null , array("SYSTEM-TRNG-SOUTH", "SYSTEM-TRNG","SYS-TRNG-SOUTH-AM")) ;
            if (isset($_POST['generate_report'])) {
                $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " " . $_REQUEST['strt_time_hour'] . ":" . $_REQUEST['strt_time_min'] . $_REQUEST['strt_time_timemode']);
                $timestamp2 = strtotime($_REQUEST['end_date_month'] . "/" . $_REQUEST['end_date_day'] . "/" . $_REQUEST['end_date_year'] . " " . $_REQUEST['end_time_hour'] . ":" . $_REQUEST['end_time_min'] . $_REQUEST['end_time_timemode']);
            } else {
                $timestamp = mktime(0, 0, 0);
                $timestamp2 = mktime(23, 59, 59);
            }
            if (!isset($_REQUEST['no_nav'])) {
                ?>
                <form id="dialersales_report" method="POST"
                      action="<?= $_SERVER['PHP_SELF'] ?>?area=dialer_sales&no_script=1"
                      onsubmit="return genReport(this, 'sales')">
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
                            <table border="0">
                                <tr>
                                    <th>Date Start:</th>
                                    <td>
                                        <?php echo makeTimebar("strt_date_", 1, NULL, false, $timestamp); ?>
                                        <div style="float:right; padding-left:6px;"
                                             id="startTimeFilter"> <?php echo makeTimebar("strt_time_", 2, NULL, false, $timestamp); ?></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Date End:</th>
                                    <td>
                                        <?php echo makeTimebar("end_date_", 1, NULL, false, $timestamp2); ?>
                                        <div style="float:right; padding-left:6px;"
                                             id="endTimeFilter"> <?php echo makeTimebar("end_time_", 2, NULL, false, $timestamp2); ?></div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Use Time?</th>
                                    <td>
                                        <input type="checkbox" name="timeFilter" id="timeFilter">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Agent Cluster [Dialer] :</th>
                                    <td><?php
                                            echo $this->makeDialerDD("agent_cluster_id", (!isset($_REQUEST['agent_cluster_id']) || intval($_REQUEST['agent_cluster_id']) < 0) ? -1 : $_REQUEST['agent_cluster_id'], '', ""); ?></td>
                                </tr>
                                <tr>
                                    <th>Area Code :</th>
                                    <td><?php
                                            echo $this->makeAreaCodeDD("area_code", (!isset($_REQUEST['area_code']) || intval($_REQUEST['area_code']) < 0) ? -1 : $_REQUEST['area_code'], '', ""); ?></td>
                                </tr>
                                <tr>
                                    <th colspan="2">
                                        <div id="sales_loading_plx_wait_span" class="nod"><img src="images/ajax-loader.gif" border="0"/> Loading, Please wait...</div>
                                        <div id="sales_submit_report_button">
								<input type="button" value="Generate PRINTABLE" onclick="genReport(getEl('dialersales_report'), 'sales', 1)">
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
                $time_started = microtime_float();

                ## TIME
                $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " " . $_REQUEST['strt_time_hour'] . ":" . $_REQUEST['strt_time_min'] . $_REQUEST['strt_time_timemode']);
                $timestamp2 = strtotime($_REQUEST['end_date_month'] . "/" . $_REQUEST['end_date_day'] . "/" . $_REQUEST['end_date_year'] . " " . $_REQUEST['end_time_hour'] . ":" . $_REQUEST['end_time_min'] . $_REQUEST['end_time_timemode']);
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
                $area_code = intval($_REQUEST['area_code']);

                ## GENERATE AND DISPLAY REPORT
                $html = $this->makeHTMLReport($stime, $etime, $agent_cluster_id, $area_code);

                if ($html == NULL) {
                    echo '<span style="font-size:14px;font-style:italic;">No results found, for the specified values.</span><br />';
                } else {
                    echo $html;
                }

                /*?></div><?*/

                $time_ended = microtime_float();

                $time_taken = $time_ended - $time_started;

                echo '<br /><span style="float:bottom;color:#fff">Load time: ' . $time_taken . '</span>';

                if (!isset($_REQUEST['no_nav'])) {
                    ?>
                    <script>
                        $(document).ready(function () {

                            $('#sales_anal_table').DataTable({

                                "lengthMenu": [[-1, 20, 50, 100, 500], ["All", 20, 50, 100, 500]]


                            });


                        });
                    </script><?php
                }
            }
        }

        public function makeHTMLReport($stime, $etime, $agent_cluster_id, $area_code)
        {
            echo '<span style="font-size:9px">makeHTMLReport(' . "$stime, $etime, $agent_cluster_id, $area_code) called</span><br /><br />\n";
            list($sales_data_arr, $totals) = $this->generateData($stime, $etime, $agent_cluster_id, $area_code);

            if (count($sales_data_arr) < 1) {
                return NULL;
            }

            // ACTIVATE OUTPUT BUFFERING
            ob_start();
            ob_clean(); ?><h1><?php

            if ($campaign_code) {
                echo $campaign_code . ' ';
            }

            echo "Sales Analysis - ";

            if ($agent_cluster_id >= 0) {
                echo $_SESSION['site_config']['db'][$agent_cluster_id]['name'] . ' - ';
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
                echo date("m-d-Y", $stime) . ' to ' . date("m-d-Y", $etime);
            } ?></h1>
            <h3><?php

                    if ($user_group) {
                        if (is_array($user_group)) {
                            if (trim($user_group[0]) != '') {
                                echo '<b>User Groups:</b>' . implode($user_group, ' | ');
                                echo "<br />";
                            }
                        } else {
                            echo '<b>User Group:</b>' . $user_group . "<br />";
                        }
                    }

                    if ($ignore_group) {
                        if (is_array($ignore_group)) {
                            if (trim($ignore_group[0]) != '') {
                                echo '<b>Ignoring Groups:</b> ' . implode($ignore_group, ' | ');
                                echo "<br />";
                            }
                        } else {
                            echo '<b>Ignoring Group:</b> ' . $ignore_group . '<br />';
                        }
                    } ?></h3>
            <table id="sales_anal_table" style="width:100%" border="0" cellspacing="1">
            <thead>
            <tr>
                <th align="left">Agent</th>
                <th title="Number of hours being Paid for">PD HRS</th>
                <th title="Number of hours of Activity tracked">WRKD HRS</th>
                <th title="Total number of calls for the day">Total Calls</th>
                <th title="Number of Calls that were NOT INTERESTED">NI</th>
                <th title="Number of Transfers">XFERS</th>
                <th title="Number of Answering Machine calls">A</th>
                <th title="Percentage of calls that are Answering Machines">%ANS</th>
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

                foreach ($sales_data_arr as $agent_data) {
                    $paid_sale_percent = ($agent_data['sale_cnt'] <= 0) ? 0 : round(((float)$agent_data['paid_sale_cnt'] / $agent_data['sale_cnt']) * 100, 2);

                    $unpaid_sale_percent = 100 - $paid_sale_percent;

                    $ans_percent = round((($agent_data['num_AnswerMachines'] / $agent_data['calls_today']) * 100), 2); ?>
                    <tr>
                    <td><?= htmlentities(strtoupper($agent_data['agent_username'])) ?></td>
                    <td align="center"><?= number_format($agent_data['activity_paid'], 2) ?></td>
                    <td align="center"><?= number_format($agent_data['activity_wrkd'], 2) ?></td>
                    <td align="center"><?= number_format($agent_data['calls_today']) ?></td>
                    <td align="center"><?= number_format($agent_data['num_NI']) ?></td>
                    <td align="center"><?= number_format($agent_data['num_XFER']) ?></td>
                    <td align="center"><?= number_format($agent_data['num_AnswerMachines']) ?></td>

                    <? /** PER PAID HOUR <td align="center"><?=number_format($agent_data['contacts_per_paid_hour'], 2)?>&nbsp;/&nbsp;<?=number_format($agent_data['calls_per_paid_hour'], 2)?></td> **/ ?>

                    <td align="center"><?= $ans_percent ?>%</td>

                    <td align="center"><?= number_format($agent_data['contacts_per_worked_hour'], 2) ?>
                        &nbsp;/&nbsp;<?= number_format($agent_data['calls_per_worked_hour'], 2) ?></td>


                    <td align="center"><?= number_format($agent_data['sale_cnt']) ?></td>


                    <td align="left">

                        <?= number_format($agent_data['paid_sale_cnt']) ?>
                        ($<?= number_format($agent_data['paid_sales_total']) ?>)

                    </td>
                    <td align="right"><?= number_format($paid_sale_percent, 2) ?>%</td>


                    <td align="center"><?= number_format(($agent_data['sale_cnt'] - $agent_data['paid_sale_cnt'])) ?></td>
                    <td align="right"><?= number_format($unpaid_sale_percent, 2) ?>%</td>


                    <td align="right"><?= number_format($agent_data['closing_percent'], 2) ?>%</td>
                    <td align="right"><?= number_format($agent_data['conversion_percent'], 2) ?>%</td>
                    <td align="right"><?= number_format($agent_data['yes2all_percent'], 2) ?>%</td>
                    <td align="right">$<?= number_format($agent_data['sales_total']) ?></td>

                    <td align="right">$<?= number_format($agent_data['avg_sale'], 2) ?></td>
                    <td align="right">$<?= number_format($agent_data['paid_hr'], 2) ?></td>
                    <td align="right">$<?= number_format($agent_data['wrkd_hr'], 2) ?></td>
                    </tr><?php
                } ?></tbody><?php

                $paid_sale_percent = round(((float)$totals['total_paid_sale_cnt'] / $totals['total_sale_cnt']) * 100, 2);

                $unpaid_sale_percent = 100 - $paid_sale_percent;

                $t_ans_percent = round((($totals['total_AnswerMachines'] / $totals['total_calls']) * 100), 2); ?>
            <tfoot>
            <tr>
                <th style="border-top:1px solid #000" align="left">Total Agents: <?= count($sales_data_arr) ?></th>
                <th style="border-top:1px solid #000"><?= number_format($totals['total_activity_paid_hrs'], 2) ?></th>
                <th style="border-top:1px solid #000"><?= number_format($totals['total_activity_wrkd_hrs'], 2) ?></th>
                <th style="border-top:1px solid #000"><?= number_format($totals['total_calls']) ?></th>
                <th style="border-top:1px solid #000"><?= number_format($totals['total_NI']) ?></th>
                <th style="border-top:1px solid #000"><?= number_format($totals['total_XFER']) ?></th>
                <th style="border-top:1px solid #000"><?= number_format($totals['total_AnswerMachines']) ?></th>
                <th style="border-top:1px solid #000"><?= $t_ans_percent ?>%</th>
                <th style="border-top:1px solid #000"><?= number_format($totals['total_contacts_per_worked_hour'], 2) . ' - ' . number_format($totals['total_calls_per_worked_hour'], 2) ?></th>


                <th style="border-top:1px solid #000"><?= number_format($totals['total_sale_cnt']) ?></th>

                <th style="border-top:1px solid #000" align="left"><?= number_format($totals['total_paid_sale_cnt']) ?>
                    ($<?= number_format($totals['total_paid_sales']) ?>)
                </th>
                <th style="border-top:1px solid #000" align="right"><?= number_format($paid_sale_percent, 2) ?>%</th>

                <th style="border-top:1px solid #000"
                    align="center"><?= number_format(($totals['total_sale_cnt'] - $totals['total_paid_sale_cnt'])) ?></th>
                <th style="border-top:1px solid #000" align="right"><?= number_format($unpaid_sale_percent, 2) ?>%</th>


                <th style="border-top:1px solid #000" align="right"><?= number_format($totals['total_closing'], 2) ?>%
                </th>
                <th style="border-top:1px solid #000" align="right"><?= number_format($totals['total_conversion'], 2) ?>
                    %
                </th>
                <th style="border-top:1px solid #000" align="right"><?= number_format($totals['total_yes2all'], 2) ?>%
                </th>

                <th style="border-top:1px solid #000" align="right">$<?= number_format($totals['total_sales']) ?></th>

                <th style="border-top:1px solid #000" align="right">$<?= number_format($totals['total_avg'], 2) ?></th>
                <th style="border-top:1px solid #000" align="right">
                    $<?= number_format($totals['total_paid_hr'], 2) ?></th>
                <th style="border-top:1px solid #000" align="right">
                    $<?= number_format($totals['total_wrkd_hr'], 2) ?></th>

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
    } // END OF CLASS
