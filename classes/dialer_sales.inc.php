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
            unset($agent_cluster_id);
            unset($area_code);
            if(isset($_REQUEST['agent_cluster_id'])) {
                $agent_cluster_id = intval($_REQUEST['agent_cluster_id']);
            }
            if(isset($_REQUEST['area_code'])) {
                $area_code = $_REQUEST['area_code'];
            }
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
            $sql = "SELECT `s`.`agent_cluster_id`, `v`.`name`, LEFT(`s`.`phone`,3) AS `area_code`, SUM(`s`.`amount`) AS `total_sales` FROM `sales` AS `s` JOIN `vici_clusters` AS `v` ON `s`.`agent_cluster_id` = `v`.`id` WHERE `sale_time` BETWEEN '" . $stime . "' AND '" . $etime . "' ";
            if ($agent_cluster_id > 0) {
                if (is_array($agent_cluster_id)) {
                    $sql .= " AND ( ";
                    $x = 0;
                    foreach ($agent_cluster_id as $cidx) {
                        if ($x++ > 0) {
                            $sql .= " OR ";
                        }
                        $sql .= " `s`.`agent_cluster_id` = " . $agent_cluster_id;
                    }
                    $sql .= ") ";
                    if ($x == 0) {
                        $sql .= "";
                    }
                } else {
                    $sql .= " AND `s`.`agent_cluster_id` = " . $agent_cluster_id;
                }
            }
            if ($area_code > 0) {
                $sql .= " AND `s`.`phone` LIKE '" . $area_code . "%'";
            }
            $sql .= " GROUP BY `s`.`agent_cluster_id`, `area_code`";
            #echo PHP_EOL . var_dump($sql) . PHP_EOL;
            return array($_SESSION['dbapi']->getResult($sql));
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
                            Sales per Dialer
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
                                            echo makeClusterDD("agent_cluster_id", (!isset($_REQUEST['agent_cluster_id']) || intval($_REQUEST['agent_cluster_id']) < 0) ? -1 : $_REQUEST['agent_cluster_id'], '', ""); ?></td>
                                </tr>
                                <tr>
                                    <th>Area Code :</th>
                                    <td><?php
                                            echo makeAreaCodeDD("area_code", (!isset($_REQUEST['area_code']) || intval($_REQUEST['area_code']) < 0) ? -1 : $_REQUEST['area_code'], '', ""); ?></td>
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

                            $('#dialer_sales_table').DataTable({

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
            list($sales_data_arr) = $this->generateData($stime, $etime, $agent_cluster_id, $area_code);
            if (count($sales_data_arr) < 1) {
                return NULL;
            }
            // ACTIVATE OUTPUT BUFFERING
            ob_start();
            ob_clean();
            echo "<h1>" . PHP_EOL;
           echo "Area Code Sales By Dialer - ";
            if (date("m-d-Y", $stime) == date("m-d-Y", $etime)) {
                echo date("m-d-Y", $stime);
            } else {
                echo date("m-d-Y", $stime) . ' to ' . date("m-d-Y", $etime);
            }
            echo "</h1>" . PHP_EOL;
            ?>
            <table id="dialer_sales_table" style="width:100%" border="0" cellspacing="1">
            <thead>
            <tr>
                <th class="centery">Agent Cluster</th>
                <th class="centery" title="Area code">Area Code</th>
                <th class="righty" title="Total sales for period">Total Sales</th>
            </tr>
            </thead>
            <tbody>
            <?
                foreach ($sales_data_arr as $dialer_data) {
                    ?>
                    <tr>
                    <td class="centery"><?= $dialer_data['name'] ?></td>
                    <td class="centery">(<?= $dialer_data['area_code'] ?>)</td>
                    <td class="righty">$<?= number_format($dialer_data['total_sales'], 2) ?></td>
                    </tr><?php
                } ?></tbody>
            </table><?php

            // GRAB DATA FROM BUFFER
            $data = ob_get_contents();

            // TURN OFF OUTPUT BUFFERING, WITHOUT OUTPUTTING
            ob_end_clean();

            // RETURN HTML
            return $data;
        }
    } // END OF CLASS
