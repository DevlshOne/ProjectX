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

        public function generateData($stime, $etime, $agent_cluster_id, $area_code, $shift_hours)
        {
            unset($agent_cluster_id);
            unset($area_code);
//            unset($shift_hours);
            if (isset($_REQUEST['agent_cluster_id'])) {
                $agent_cluster_id = intval($_REQUEST['agent_cluster_id']);
            }
            if (isset($_REQUEST['area_code'])) {
                $area_code = $_REQUEST['area_code'];
            }
            $sql = "SELECT `s`.`agent_cluster_id`, `v`.`name`, LEFT(`s`.`phone`,3) AS `area_code`, SUM(`s`.`amount`) AS `total_sales`, (SUM(`s`.`amount`) / " . $shift_hours . ") AS `sales_per_shift` FROM `sales` AS `s` JOIN `vici_clusters` AS `v` ON `s`.`agent_cluster_id` = `v`.`id` WHERE `sale_time` BETWEEN '" . $stime . "' AND '" . $etime . "' ";
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
            #echo "<br /><div style='font-weight:800;font-size:larger;color:red;'>" . $sql . "</div><br />";
            return array($_SESSION['dbapi']->getResult($sql));
        }

        public function makeReport()
        {
            $timeOptionMode = (isset($_REQUEST['timeOptions']) ? intval($_REQUEST['timeOptions']) : 1);
            if (isset($_POST['generate_report'])) {
                switch ($timeOptionMode) {
                    case '1' :
                        $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " 00:00:00");
                        $timestamp2 = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " 23:59:59");
                        break;
                    case '2' :
                        $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " 00:00:00");
                        $timestamp2 = strtotime($_REQUEST['end_date_month'] . "/" . $_REQUEST['end_date_day'] . "/" . $_REQUEST['end_date_year'] . " 23:59:59");
                        break;
                    case '3' :
                        $timestamp = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " " . $_REQUEST['strt_time_hour'] . ":" . $_REQUEST['strt_time_min'] . $_REQUEST['strt_time_timemode']);
                        $timestamp2 = strtotime($_REQUEST['strt_date_month'] . "/" . $_REQUEST['strt_date_day'] . "/" . $_REQUEST['strt_date_year'] . " " . $_REQUEST['end_time_hour'] . ":" . $_REQUEST['end_time_min'] . $_REQUEST['end_time_timemode']);
                        break;
                }
            } else {
                $timestamp = mktime(0, 0, 0);
                $timestamp2 = mktime(23, 59, 59);
            }
            //echo $this->makeHTMLReport('1430377200', '1430463599', 'BCSFC', -1, 1,null , array("SYSTEM-TRNG-SOUTH", "SYSTEM-TRNG","SYS-TRNG-SOUTH-AM")) ;
            if (!isset($_REQUEST['no_nav'])) {
                ?>
                <script type="text/javascript" src="js/table2CSV.js"></script>
        <div class="block">
            <input type="hidden" name="generate_report">
            <div class="block-header bg-primary-light">
                <h4 class="block-title">Area Code Sales by Dialer</h4>
                <button type="button" class="btn btn-sm btn-primary" title="Generate PRINTABLE" onclick="genReport(getEl('dialersales_report'), 'sales', 1)">Generate PRINTABLE</button>
                <button type="button" class="btn btn-sm btn-secondary" id="btnGenCSV" title="Download CSV" onclick="genCSV(getEl('dialer_sales_table'))">Download CSV</button>
                <button type="submit" class="btn btn-sm btn-success" title="Generate Report">Generate</button>
            </div>
            <div class="block-content">
                <form id="dialersales_report" method="POST"
                      action="<?= $_SERVER['PHP_SELF'] ?>?area=dialer_sales&no_script=1"
                      onsubmit="return genReport(this, 'sales')">
                    <input type="hidden" name="generate_report">
                    <table class="tightTable">
                        <tr>
                            <td colspan="2">
                                <script>
                                    $(function () {
                                        let timeFields = $('#startTimeFilter, #endTimeFilter');
                                        let retainTime = '<? echo $_REQUEST['timeFilter'] === "on"; ?>';
                                        let singleDateMode =
                                            '<th>Date :</th>\n' +
                                            '<td>\n' +
                                            '<?php echo makeTimebar("strt_date_", 1, NULL, false, $timestamp); ?>\n' +
                                            '<input type="hidden" name="timeFilter" id="timeFilter" value="off" />' +
                                            '</td>\n';
                                        let dateRangeMode1 =
                                            '<th>Date Start :</th>\n' +
                                            '<td>\n' +
                                            '<?php echo makeTimebar("strt_date_", 1, NULL, false, $timestamp); ?>\n' +
                                            '<input type="hidden" name="timeFilter" id="timeFilter" value="off" />' +
                                            '</td>\n';
                                        let dateRangeMode2 =
                                            '<th>Date End :</th>\n' +
                                            '<td>\n' +
                                            '<?php echo makeTimebar("end_date_", 1, NULL, false, $timestamp2); ?>\n' +
                                            '</td>\n';
                                        let dateTimeRangeMode1 =
                                            '<th>Date :</th>\n' +
                                            '<td>\n' +
                                            '<?php echo makeTimebar("strt_date_", 1, NULL, false, $timestamp); ?>\n' +
                                            '</td>\n';
                                        let dateTimeRangeMode2 =
                                            '<th>Start Time :</th>\n' +
                                            '<td>\n' +
                                            '<?php echo makeTimebar("strt_time_", 2, NULL, false, $timestamp); ?>\n' +
                                            '</td>\n';
                                        let dateTimeRangeMode3 =
                                            '<th>End Time :</th>\n' +
                                            '<td>\n' +
                                            '<?php echo makeTimebar("end_time_", 2, NULL, false, $timestamp2); ?>\n' +
                                            '<input type="hidden" name="timeFilter" id="timeFilter" value="on" />\n' +
                                            '</td>\n';
                                        function changeDateFilters(t) {
                                            //console.log('Changing date/time mode : ' + t);
                                            switch (t) {
                                                case '1' :
                                                    $('#timeFilterModeR1').empty().html(singleDateMode);
                                                    $('#timeFilterModeR2').empty();
                                                    $('#timeFilterModeR3').empty();
                                                    $('#shiftHours').show();
                                                    break;
                                                default :
                                                case '2' :
                                                    $('#timeFilterModeR1').empty().html(dateRangeMode1);
                                                    $('#timeFilterModeR2').empty().html(dateRangeMode2);
                                                    $('#timeFilterModeR3').empty();
                                                    $('#shiftHours').show();
                                                    break;
                                                case '3' :
                                                    $('#timeFilterModeR1').empty().html(dateTimeRangeMode1);
                                                    $('#timeFilterModeR2').empty().html(dateTimeRangeMode2);
                                                    $('#timeFilterModeR3').empty().html(dateTimeRangeMode3);
                                                    $('#shiftHours').hide();
                                                    break;
                                            }
                                        }
                                        if (retainTime) {
                                            $(timeFields).show();
                                            $('#timeFilter').prop('checked', true);
                                            $('#shift_hours').val(null);
                                            $('#shiftHours').hide();
                                        } else {
                                            $(timeFields).hide();
                                            $('#timeFilter').prop('checked', false);
                                            $('#shiftHours').show();
                                        }
                                        $('#timeFilter').on('click', function () {
                                            $(timeFields).toggle();
                                            $('#shiftHours').toggle();
                                        });
                                        $('#timeOptions').on('change', function () {
                                            let newMode = $('#timeOptions option:selected').val();
                                            changeDateFilters(newMode);
                                        }).change();
                                    });
                                </script>
                                <table border="0" id="filterTable">
                                    <tr>
                                        <th>Date Mode :</th>
                                        <td>
                                            <div class="lefty" id="timeOptions">
                                                <select class="form-control custom-select-sm" id="timeOptions" name="timeOptions">
                                                    <option value="1" <? echo ($timeOptionMode == 1) ? ' selected' : '' ?>>
                                                        Single Date
                                                    </option>
                                                    <option value="2" <? echo ($timeOptionMode == 2) ? ' selected' : '' ?>>
                                                        Date Range
                                                    </option>
                                                    <option value="3" <? echo ($timeOptionMode == 3) ? ' selected' : '' ?>>
                                                        Date w/Time Range
                                                    </option>
                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr id="timeFilterModeR1"></tr>
                                    <tr id="timeFilterModeR2"></tr>
                                    <tr id="timeFilterModeR3"></tr>
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
                                                echo makeClusterDD("agent_cluster_id", (!isset($_REQUEST['agent_cluster_id']) || intval($_REQUEST['agent_cluster_id']) < 0) ? -1 : $_REQUEST['agent_cluster_id'], 'form-control custom-select-sm', ""); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Area Code :</th>
                                        <td><?php
                                                echo makeAreaCodeDD("area_code", (!isset($_REQUEST['area_code'])) ? 0 : $_REQUEST['area_code'], 'form-control custom-select-sm', ""); ?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="2">
                                            <div id="sales_loading_plx_wait_span" class="nod"><img
                                                        src="images/ajax-loader.gif" border="0"/> Loading, Please
                                                wait...
                                            </div>
                                        </th>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </form>
                </div>
        </div>
                <?php
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
                }
            }
        }

        public function makeHTMLReport($stime, $etime, $agent_cluster_id, $area_code, $shift_hours, $time_option)
        {
            #echo __METHOD__ . ":: inside makeHTMLReport(" . $stime . ", " . $etime . ", " . $agent_cluster_id . ", " . $area_code . ", " . $shift_hours . ");<br />" . PHP_EOL;
            echo '<span style="font-size:9px">makeHTMLReport(' . "$stime, $etime, $agent_cluster_id, $area_code, $shift_hours) called</span><br /><br />\n";
            $dataResults = $this->generateData($stime, $etime, $agent_cluster_id, $area_code, $shift_hours);
            list($sales_data_arr) = $dataResults;
            if (sizeof($dataResults) < 1) {
                return NULL;
            }
            switch ($time_option) {
                case '1' :
                    $dateDisp = date('m/d/Y', $stime);
                    break;
                case '2' :
                    $dateDisp = date('m/d/Y', $stime) . " - " . date('m/d/Y', $etime);
                    break;
                case '3' :
                    $dateDisp = date('m/d/Y H:i', $stime) . " - " . date('H:i', $etime);
                    break;
            }
            // ACTIVATE OUTPUT BUFFERING
            ob_start();
            ob_clean();
            echo "<h1 id='reportTitle'>Area Code Sales By Dialer - " . $dateDisp . "</h1>";
            ?>
            <table id="dialer_sales_table" style="width:100%" border="0" cellspacing="1">
            <thead>
            <tr>
                <th class="centery">Agent Cluster</th>
                <th class="centery" title="Area code">Area Code</th>
                <th class="righty" title="Total sales for period">Total Sales</th>
                <th class="righty" title="Sales per hour (as specified)">Sales / Hour</th>
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
                    <td class="righty">$<?= number_format($dialer_data['sales_per_shift'], 2) ?></td>
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
