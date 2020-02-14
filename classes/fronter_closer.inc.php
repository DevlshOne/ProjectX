<? /***************************************************************
 *    Fronter/Closer Report - Replacement for vicidial to support PX/cross cluster
 *    Written By: Jonathan Will
 ***************************************************************/

$_SESSION['fronter_closer'] = new FronterCloser;


class FronterCloser
{


    function FronterCloser()
    {


        ## REQURES DB CONNECTION!
        $this->handlePOST();
    }


    function handlePOST()
    {


    }

    function handleFLOW()
    {
        if (!checkAccess('fronter_closer')) {


            accessDenied("Fronter/Closer Report");

            return;

        } else {

            $this->makeReport();

        }

    }


    function getQueueAvgWait($cluster_idx, $start_time, $end_time, $campaign_id = 0)
    {

        // CONNECT TO VICI CLUSTER
        connectViciDB($cluster_idx);

        $sql = "SELECT AVG(queue_seconds) FROM vicidial_closer_log " .
            " WHERE start_epoch BETWEEN '$start_time' AND '$end_time' " .
            (($campaign_id) ? " AND campaign_id='$campaign_id' " : "") . "";


        //echo $sql;

        list($wait_avg) = queryROW($sql);
        return $wait_avg;
    }


    /**
     * Returns an array of (fronters, closers), each containing more arrays() because, yo dawg.
     */
    function generateData($date, $campaign_id = 0, $cluster_idx = -1, $verifier_cluster_idx = -1, $user_group = null)
    {

        connectPXDB();

        // PARSE DATE INTO UNIX TIMESTAMP
        $time = strtotime($date);

        // START/END TIME FRAMEWORK BUILT
        $stime = mktime(0, 0, 0, date("m", $time), date("d", $time), date("Y", $time));
        $etime = $stime + 86399;

        $ofcsql = "";

        // OFFICE RESTRICTION/SEARCH ABILITY //&&(count($_SESSION['assigned_offices']) > 0)
        if (
            ($_SESSION['user']['priv'] < 5) &&
            ($_SESSION['user']['allow_all_offices'] != 'yes')
        ) {

            $ofcsql = " AND `office` IN(";
            $x = 0;
            foreach ($_SESSION['assigned_offices'] as $ofc) {

                if ($x++ > 0) $ofcsql .= ',';

                $ofcsql .= intval($ofc);
            }

            $ofcsql .= ") ";

        } else {

        }

        $sql = "SELECT * FROM transfers " .
            " WHERE xfer_time BETWEEN '$stime' AND '$etime' " .
            (($cluster_idx >= 0) ? " AND agent_cluster_id='" . $_SESSION['site_config']['db'][$cluster_idx]['cluster_id'] . "' " : "") .


            (($verifier_cluster_idx >= 0) ? " AND verifier_cluster_id='" . $_SESSION['site_config']['db'][$verifier_cluster_idx]['cluster_id'] . "' " : "") .

            (($user_group != null) ? " AND call_group='" . $user_group . "' " : "") .

            (($ofcsql) ? $ofcsql : '') .

            (($campaign_id) ? " AND campaign_id='" . $campaign_id . "' " : "") .
            " ";


        //echo $sql;


        $res = $_SESSION['dbapi']->ROquery($sql);


        $fronters = array();
        $closers = array();
        $totals = array();
        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {

            $agent_username = strtolower($row['agent_username']);
            $verifier_username = strtolower($row['verifier_username']);
            $sale_successful = preg_match("/sale|paidcc/", strtolower($row['verifier_dispo']));

//print_r($row);

            /** FRONTER INFORMATION GATHERING **/
            if (!is_array($fronters[$agent_username])) {

                // FLESH OUT THE ARRAY, TO GENERATE THE REPORT FROM
                $fronters[$agent_username] = array();
                $fronters[$agent_username]['total_amount'] = $row['agent_amount'];
                $fronters[$agent_username]['total_sales'] = ($sale_successful) ? 1 : 0;
                $fronters[$agent_username]['total_xfers'] = 1;
                $fronters[$agent_username]['total_calls'] = 1;
                $fronters[$agent_username]['total_drops'] = 0;
                $fronters[$agent_username]['records'][] = $row;

            } else {

                // INCREMENT VALUES AND SUCH
                $fronters[$agent_username]['total_amount'] += $row['agent_amount'];
                $fronters[$agent_username]['total_sales'] += ($sale_successful) ? 1 : 0;
                $fronters[$agent_username]['total_xfers']++;
                $fronters[$agent_username]['records'][] = $row;

            }

            if (!$sale_successful) {

                $dispo = strtolower($row['verifier_dispo']);
                //echo "DISPO: ".$dispo;


                if (!trim($dispo)) {

                    $fronters[$agent_username]['total_drops']++;

                    // SUBTRACT DROPS!
                    $fronters[$agent_username]['total_xfers']--;


                } else {

                    $fronters[$agent_username]['total_others']++;

                }
            }


            /** CLOSER INFORMATION GATHERING **/
            if (!is_array($closers[$verifier_username])) {

                // CREATE THE FRAME AND POPULATE CLOSER DATA
                $closers[$verifier_username] = array();
                $closers[$verifier_username]['total_amount'] = $row['verifier_amount'];
                $closers[$verifier_username]['total_sales'] = ($sale_successful) ? 1 : 0;
                $closers[$verifier_username]['total_calls'] = 1;
                $closers[$verifier_username]['total_others'] = 0;
                $closers[$verifier_username]['records'][] = $row;

            } else {

                // INCREMENT THE CLOSER DATA
                $closers[$verifier_username]['total_amount'] += $row['verifier_amount'];
                $closers[$verifier_username]['total_sales'] += ($sale_successful) ? 1 : 0;
                $closers[$verifier_username]['total_calls']++;
                $closers[$verifier_username]['records'][] = $row;


            }


        } // END WHILE(db records)


        // GENERATE PERCENTAGES FOR FRONTER SUCCESS
        foreach ($fronters as $user => $row) {

            $fronters[$user]['success_percent'] = ($row['total_xfers'] <= 0) ? 0 : round(((($row['total_sales'] * 100) / ($row['total_xfers'] * 100)) * 100), 2);

            // GENERATE TOTALS
            $totals['total_fronters']++;

            $totals['total_fronter_xfers'] += $row['total_xfers'];
            $totals['total_fronter_sales'] += $row['total_sales'];


            $totals['total_fronter_drops'] += $row['total_drops'];
            $totals['total_fronter_others'] += $row['total_others'];

        }


        // GENERATE CONVERSION PERCENTAGE FOR CLOSER SUCCESS
        foreach ($closers as $user => $row) {
            $closers[$user]['conversion_percent'] = round(((($row['total_sales'] * 100) / ($row['total_calls'] * 100)) * 100), 2);


            $totals['total_closers']++;
            $totals['total_closer_calls'] += $row['total_calls'];
            $totals['total_closer_sales'] += $row['total_sales'];


        }


        // SORTING
        ksort($fronters);
        ksort($closers);
        ksort($totals);


//print_r($fronters);
//
//print_r($closers);

//print_r($totals);

        return array($fronters, $closers, $totals);


//
//
//		$res = query("SELECT * FROM lead_tracking ".
//				" WHERE `time` BETWEEN '$stime' AND '$etime' ");
//

//
//		$x=0;
//		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
//
//			//$rowarr[$x] = $row;
//
//			if(!isset($fronters[$row['agent_username']])){
//
//				$fronters[$row['agent_username']] = array();
//
//			}
//
//
//			// LOWERCASED FOR CONSISTANCY
//			switch(strtolower($row['dispo'])){
//			default:
//
//				// IGNORE OTHER STATUSES (DNC, ANSWERING MACHINES, ETC)
//
//				break;
//			case 'sale':
//
//
//				break;
//
//			case 'drop':
//			case 'dropx':
//
//				//	$fronters[$row['agent_username']]
//
//				break;
//
//			case '':
//
//				break;
//			}
//
//
//			$x++;
//		}


    }

    function makeCampaignDD($name, $selected, $css, $onchange)
    {

        //connectPXDB();

        $res = $_SESSION['dbapi']->ROquery("SELECT name, id FROM campaigns WHERE `status`='active'");


        $out = '<select name="' . $name . '" id="' . $name . '" ';

        $out .= ($css) ? ' class="' . $css . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= '>';


        while ($row = mysqli_fetch_array($res)) {

            $out .= '<option value="' . $row['id'] . '" ';
            $out .= ($selected == $row['id']) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['name']) . '</option>';

        }


        $out .= '<option value=""' . (($selected == '') ? ' SELECTED ' : '') . '>[All]</option>';


        $out .= '</select>';

        return $out;

    }


    function makeClusterDD($name, $selected, $css, $onchange)
    {

        $out = '<select name="' . $name . '" id="' . $name . '" ';

        $out .= ($css) ? ' class="' . $css . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= '>';


        foreach ($_SESSION['site_config']['db'] as $dbidx => $db) {

            $out .= '<option value="' . $dbidx . '" ';
            $out .= ($selected == $dbidx) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($db['name']) . '</option>';
        }

        $out .= '<option value="-1" ' . (($selected == '-1') ? ' SELECTED ' : '') . '>[All]</option>';


        $out .= '</select>';

        return $out;
    }


    function makeReport()
    {

        if (isset($_POST['generate_report'])) {

            $timestamp = strtotime($_REQUEST['time_month'] . "/" . $_REQUEST['time_day'] . "/" . $_REQUEST['time_year']);

        } else {

            $timestamp = time();

        }

        ?>
        <div class="block">
    <form id="fcreport_frm" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?area=fronter_closer&no_script=1" onsubmit="return genReport(this,'frontercloser')">
        <input type="hidden" name="generate_report">
        <div class="block-header bg-primary-light">
            <h4 class="block-title">Fronter / Closer Report</h4>
        </div>
        <div class="block-content">
        <table border="0">
            <tr>
                <th>Date:</th>
                <td>
                    <?= makeTimebar("time_", 1, null, false, $timestamp); ?>
                </td>
            </tr>
            <tr>
                <th>Agent Cluster:</th>
                <td>
                    <?= $this->makeClusterDD("cluster_id", (isset($_REQUEST['cluster_id'])) ? $_REQUEST['cluster_id'] : -1, '', ""); ?>
                </td>
            </tr>
            <tr>
                <th>Verifier Cluster:</th>
                <td><?= $this->makeClusterDD("verifier_cluster_id", (isset($_REQUEST['verifier_cluster_id'])) ? $_REQUEST['verifier_cluster_id'] : -1, '', ""); ?></td>
            </tr>
            <tr>
                <th>Campaign ID:</th>
                <td><?

                    echo $this->makeCampaignDD("campaign_id", $_REQUEST['campaign_id'], '', "");

                    ?></td>
            </tr>
            <tr>
                <th>User Group:</th>
                <td><?
                    echo makeViciUserGroupDD("call_group", $_REQUEST['call_group'], '', "");
                    ?></td>
            </tr>
            <tr>
                <th colspan="2">
                    <span id="frontercloser_loading_plx_wait_span" class="nod"><img src="images/ajax-loader.gif" border="0"/> Loading, Please wait...</span>

                    <span id="frontercloser_submit_report_button">
							<input type="submit" value="Generate">
						</span>
                </th>
            </tr>
        </table>
        <?
        if (isset($_POST['generate_report'])) {
            //print_r($_POST);
            list($fronters, $closers, $totals) = $this->generateData(date("m/d/Y", $timestamp), $_REQUEST['campaign_id'], $_REQUEST['cluster_id'], $_REQUEST['verifier_cluster_id'], trim($_REQUEST['call_group']));
            ?>
            <table border="0" class="lb" width="500">
                <tr>
                    <th class="ui-widget-header" colspan="6">Fronter Stats</th>
                </tr>
                <tr>
                    <th align="left">Agent</th>
                    <th align="right">Success/Sales</th>
                    <th align="right">Transfers</th>
                    <th align="right" width="100">Success %</th>
                    <th align="right">Drop</th>
                    <th align="right">Other</th>
                </tr><?

                $color = 0;
                foreach ($fronters as $user => $row) {

                    $class = 'row' . ($color++ % 2);

                    ?>
                    <tr>
                    <td class="<?= $class ?>" align="left"><?= $user ?></td>
                    <td class="<?= $class ?>" align="right"><?= number_format($row['total_sales']) ?></td>
                    <td class="<?= $class ?>" align="right"><?= number_format($row['total_xfers']) ?></td>
                    <td class="<?= $class ?>" align="right"><img src="percent.php?percent=<?= $row['success_percent'] ?>" width="100" height="10" border="0" alt="<?= $row['success_percent'] ?>%"></td>
                    <td class="<?= $class ?>" align="right"><?= number_format($row['total_drops']) ?></td>
                    <td class="<?= $class ?>" align="right"><?= number_format($row['total_others']) ?></td>
                    </tr><?

                }


                $total_percent = ($totals['total_fronter_xfers'] <= 0) ? 0 : ((($totals['total_fronter_sales'] * 100) / ($totals['total_fronter_xfers'] * 100)) * 100);

                ?>
                <tr>
                    <th class="tl" align="left">Total Fronters: &nbsp;&nbsp;&nbsp;&nbsp;<?= number_format($totals['total_fronters']) ?></th>
                    <th class="tl" align="right"><?= number_format($totals['total_fronter_sales']) ?></th>
                    <th class="tl" align="right"><?= number_format($totals['total_fronter_xfers']) ?></th>
                    <th class="tl" align="right"><?= number_format($total_percent, 2) ?>%</th>
                    <td class="tl" class="<?= $class ?>" align="right"><?= number_format($totals['total_fronter_drops']) ?></td>
                    <td class="tl" class="<?= $class ?>" align="right"><?= number_format($totals['total_fronter_others']) ?></td>
                </tr><?

                if ($_REQUEST['cluster_id'] != -1) {

                    ?>
                    <tr>
                    <th class="tl" colspan="5" align="left">Average time in Queue for customers(<?= $_SESSION['site_config']['db'][$_REQUEST['cluster_id']]['name'] ?>):</th>
                    <td class="tl" align="right"><?

                        echo number_format($this->getQueueAvgWait($_REQUEST['cluster_id'], $timestamp, $timestamp + 86399, $_REQUEST['campaign_id']), 2) . ' sec';

                        ?></td>
                    </tr><?

                } else {

                    foreach ($_SESSION['site_config']['db'] as $dbidx => $db) {

                        ?>
                        <tr>
                        <th class="tl" colspan="5" align="left">Average time in Queue for customers(<?= $db['name'] ?>):</th>
                        <td class="tl" align="right"><?

                            echo number_format($this->getQueueAvgWait($dbidx, $timestamp, $timestamp + 86399, $_REQUEST['campaign_id']), 2) . ' sec';

                            ?></td>
                        </tr><?
                    }

                }


                ?>
            </table>
            <br/><br/>
            <table border="0" class="lb" width="500">
                <tr>
                    <th class="ui-widget-header" colspan="6">Closer Stats</th>
                </tr>
                <tr>
                    <th align="left">Agent</th>
                    <th align="right">Calls</th>
                    <th align="right">Sales</th>
                    <th align="right">Drop</th>
                    <th align="right">Other</th>
                    <th align="right" width="100">Conv %</th>
                </tr><?

                $color = 0;
                foreach ($closers as $user => $row) {

                    $class = 'row' . ($color++ % 2);

                    ?>
                    <tr>
                    <td class="<?= $class ?>" align="left"><?= $user ?></td>
                    <td class="<?= $class ?>" align="right"><?= number_format($row['total_calls']) ?></td>
                    <td class="<?= $class ?>" align="right"><?= number_format($row['total_sales']) ?></td>
                    <td class="<?= $class ?>" align="right">-</td>
                    <td class="<?= $class ?>" align="right">-</td>
                    <td class="<?= $class ?>" align="right"><img src="percent.php?percent=<?= $row['conversion_percent'] ?>" width="100" height="10" border="0" alt="<?= $row['conversion_percent'] ?>%"></td>

                    </tr><?

                }


                $total_percent = (($totals['total_closer_calls'] <= 0) ? 0 : ((($totals['total_closer_sales'] * 100) / ($totals['total_closer_calls'] * 100)) * 100));

                ////[14:05] <@phreak> formula is percent = sales / (calls - drops)

                ?>
                <tr>
                    <th class="tl" align="left">Total Closers: &nbsp;&nbsp;&nbsp;&nbsp;<?= number_format($totals['total_closers']) ?></th>
                    <th class="tl" align="right"><?= number_format($totals['total_closer_calls']) ?></th>
                    <th class="tl" align="right"><?= number_format($totals['total_closer_sales']) ?></th>
                    <td class="tl" class="<?= $class ?>" align="right">-</td>
                    <td class="tl" class="<?= $class ?>" align="right">-</td>
                    <th class="tl" align="right"><img src="percent.php?percent=<?= number_format($total_percent, 2) ?>" width="100" height="10" border="0" alt="<?= number_format($total_percent, 2) ?>%"></th>
                </tr>
            </table>
            </div>
            </form>
            </div>

            <?

        }

    }


} // END OF CLASS
