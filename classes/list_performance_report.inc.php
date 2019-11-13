<?php
/***************************************************************
*	List Performance Report - An LMT tool to see how lists are performing on various dialers, and possibly turn lists on and off.
*	Written By: Jonathan Will
***************************************************************/

$_SESSION['list_performance'] = new ListPerformance;



class ListPerformance{
	
	
	public $max_hourlys_age = 14; // IN DAYS, HOW LONG WE KEEP THE HOURLY DATA, BEFORE ITS CLEANED DOWN TO JUST THE LAST/LARGEST ONE
	
	function ListPerformance(){
		
		
		## REQURES DB CONNECTION!
		$this->handlePOST();
	}
	
	
	function handlePOST(){
		
		//print_r($_SESSION['cached_data']);
	}
	
	function handleFLOW(){
		if(!checkAccess('list_tools')){
			
			
			accessDenied("List Tools");
			
			return;
			
		}else{
			
			$this->makeReport();
			
		}
		
	}
	

	function logHistoryReport(){
		
		$stime = mktime(0, 0, 0);
		
		// CURRENT HOUR TIMESTAMP
		$etime = mktime(date("H"), 0, 0);
		
		
		foreach($_SESSION['site_config']['db'] as $dbidx=>$db){
		
			echo "Generating data for '".$db['name']."' - ".$db['sqlhost'];
			
			$data_arr = $this->generateData($stime, $etime, $db['cluster_id']);
			
			
			$json_data = json_encode($data_arr);
			
			$sql = "INSERT INTO `list_performance_history` (`time`, `vici_cluster_id`, `json_data`) VALUES ".
					"(".$etime.",".$db['cluster_id'].",'".mysqli_real_escape_string($_SESSION['dbapi']->db, $json_data)."') ".
					"ON DUPLICATE KEY UPDATE `json_data`='".mysqli_real_escape_string($_SESSION['dbapi']->db, $json_data)."' ";
			
			echo "..logging...";
			
			echo $_SESSION['dbapi']->execSQL($sql);
			
			
// 			$dat = array();

// 			$dat['time'] = $etime;
// 			$dat['vici_cluster_id'] = $db['cluster_id'];
// 			$dat['json_data'] = $json_data
			

// 			$_SESSION['dbapi']->aadd($dat,'list_performance_history');
			
			echo "Done\n";
		}
	}
	
	
	/**
	 * select * from list_performance_history where id in (
select max(`id`) from list_performance_history where `time` between 1573236000 and 1573237000 group by vici_cluster_id, date(from_unixtime(`time`)))
	 */
	function cleanupOldHourlys(){
		
		$before_time = mktime(0,0,0) - (86400 * $this->max_hourlys_age);
		
		
		$sql = "DELETE FROM `list_performance_history` ".
				" WHERE `time` BETWEEN '$before_time' AND '".($before_time + 86399)."' ".
				" AND `id` NOT IN (".
					"SELECT MAX(`id`) FROM list_performance_history ".
					"WHERE `time` BETWEEN' $before_time' AND '".($before_time + 86399)."' GROUP BY vici_cluster_id, DATE(FROM_UNIXTIME(`time`)))";
		$cnt = execSQL($sql);	
		
		
		echo date("m/d/Y")." - Cleaned up $cnt older records\n";
		
		return $cnt;
		/*$cnt = 0;
		foreach($_SESSION['site_config']['db'] as $dbidx=>$db){
			
			echo "Cleaning up data for '".$db['name']."' - ".$db['sqlhost']." from ".date("m/d/Y", $before_time)."\n";
		
			$cluster_id = intval($db['cluster_id']);
			
			$sql = "DELETE FROM `list_performance_history` WHERE `time` BETWEEN '$before_time' AND '".($before_time + 86399)."'  AND `vici_cluster_id`='$cluster_id' ".
				" AND `id` NOT IN(".
					"SELECT MAX(id) FROM `list_performance_history` ".
					" WHERE `time` BETWEEN '$before_time' AND '".($before_time + 86399)."' AND `vici_cluster_id`='$cluster_id'".			
				")";
		
			$cnt += execSQL($sql);	
				
		}*/
		
	}
	
	
	
	/**
	 * 
	 * William:  "run this after the columns are created in the sales table and it will pull the info from lead_tracking for any sales it can find. "
	 * update sales inner join lead_tracking on sales.lead_tracking_id = lead_tracking.id
set sales.list_id = lead_tracking.list_id, sales.vici_campaign_id = lead_tracking.vici_campaign_id
where sale_time > unix_timestamp(curdate() - interval 35 day);
	 * 
	 * 
	 * 
	 * @param unknown $stime
	 * @param unknown $etime
	 * @param unknown $vici_cluster_id
	 * @return array[]|array
	 */
	function generateData($stime, $etime, $vici_cluster_id ){
		$stime = intval($stime);
		$etime = intval($etime);
		// PULL DISTINCT LIST OF LIST_ID'S IN PX...
		$rowarr = $_SESSION['dbapi']->fetchAllAssoc(
				"SELECT DISTINCT(`list_id`) FROM `lead_tracking` ".
				" WHERE `time` BETWEEN '$stime' AND '$etime' ".
				" AND vici_cluster_id='".intval($vici_cluster_id)."' "
				);
		
		$vici_idx = getClusterIndex($vici_cluster_id);
		
		// CONNECT VICI CLUSTER BY IDX
		connectViciDB($vici_idx);
		
		// PULL VICIDIALS LISTS AND STATUS OF THE LISTS
		$viciarr = fetchAllAssoc("SELECT * FROM `vicidial_lists` ORDER BY  `list_id` ASC"); //ORDER BY `list_lastcalldate` DESC"); //
		
		$out = array();
		
		foreach($viciarr as $vrow){
			$out[$vrow['list_id']] = array();
			$out[$vrow['list_id']]['vici'] = $vrow;
		}
		

		
		
		foreach($rowarr as $row){
			
			$list_id = intval($row['list_id']);
			
			$dat = array('list_id' => $list_id);
			
			
			$lead_where = " WHERE `time` BETWEEN '$stime' AND '$etime' AND `list_id`='".$list_id."' AND `lead_id` > 0 AND vici_cluster_id='".intval($vici_cluster_id)."' ";
			
			
			
			list($dat['time_first_call'],$dat['time_last_call']) = $_SESSION['dbapi']->queryROW("SELECT min(`time`), max(`time`) FROM `lead_tracking` ".$lead_where );

					
		/*	list($dat['time_first_call']) = $_SESSION['dbapi']->queryROW("SELECT `time` FROM `lead_tracking` ".
					$lead_where.
					" ORDER BY `time` ASC LIMIT 1");
			
			list($dat['time_last_call']) = $_SESSION['dbapi']->queryROW("SELECT `time` FROM `lead_tracking` ".
					$lead_where.
					" ORDER BY `time` DESC LIMIT 1");*/
			
			$dat['list_run_time'] = $dat['time_last_call'] - $dat['time_first_call'];
			
			
			list($dat['num_agents']) = $_SESSION['dbapi']->queryROW("SELECT COUNT(DISTINCT(agent_username)) FROM `lead_tracking` ".
					$lead_where);
			
			
			
			list($dat['total_cnt']) = $_SESSION['dbapi']->queryROW("SELECT COUNT(*) FROM `lead_tracking` ".
					$lead_where);
			
			
// 			list($dat['sale_cnt']) = $_SESSION['dbapi']->queryROW("SELECT COUNT(1) FROM `sales` ".
// 					" JOIN `lead_tracking` ON `sales`.lead_tracking_id = `lead_tracking`.id ".
// 					" WHERE `lead_tracking`.`time` BETWEEN '$stime' AND '$etime' ".
// ///					" AND `sales`.`sale_time` BETWEEN '$stime' AND '$etime' ".
// 					" AND `lead_tracking`.`list_id`='".$list_id."' AND `lead_tracking`.`lead_id` > 0 ".
// 					" AND `lead_tracking`.vici_cluster_id='".intval($vici_cluster_id)."' ");

			list($dat['sale_cnt']) = $_SESSION['dbapi']->queryROW("SELECT COUNT(1) FROM `sales` ".
					
					" WHERE `sales`.`sale_time` BETWEEN '$stime' AND '$etime' ".
					" AND `sales`.`list_id`='".$list_id."'  ". //AND `sales`.`agent_lead_id` > 0
					" AND `sales`.agent_cluster_id='".intval($vici_cluster_id)."' ");
			
			
			list($dat['answer_cnt']) = $_SESSION['dbapi']->queryROW("SELECT COUNT(*) FROM `lead_tracking` ".
					$lead_where.
					" AND `dispo`='A' "

					);
			
			list($dat['not_interested_cnt']) = $_SESSION['dbapi']->queryROW("SELECT COUNT(*) FROM `lead_tracking` ".
					$lead_where.
					" AND `dispo`='NI' "
					);
			
			list($dat['contacts_cnt']) = $_SESSION['dbapi']->queryROW("SELECT COUNT(*) FROM `lead_tracking` ".
					$lead_where.
					" AND `dispo` NOT IN('A','DC') "
					);
			
			$out[$list_id]['data'] = $dat;
		}

		return $out;
	}
	
	
	
	
	
	function makeReport(){
		
		
		
		//echo $this->makeHTMLReport('1430377200', '1430463599', 'BCSFC', -1, 1,null , array("SYSTEM-TRNG-SOUTH", "SYSTEM-TRNG","SYS-TRNG-SOUTH-AM")) ;
		
		if(isset($_POST['generate_report'])){
			
// 			$timestamp = strtotime($_REQUEST['stime_month']."/".$_REQUEST['stime_day']."/".$_REQUEST['stime_year']);
// 			$timestamp2 = strtotime($_REQUEST['etime_month']."/".$_REQUEST['etime_day']."/".$_REQUEST['etime_year']);

			
// 			if($_REQUEST['timeFilter']){
				
			
// 				$timestamp = strtotime($_REQUEST['strt_date_month']."/".$_REQUEST['strt_date_day']."/".$_REQUEST['strt_date_year']." ".$_REQUEST['strt_time_hour'].":".$_REQUEST['strt_time_min'].$_REQUEST['strt_time_timemode']);
// 				$timestamp2 = strtotime($_REQUEST['end_date_month']."/".$_REQUEST['end_date_day']."/".$_REQUEST['end_date_year']." ".$_REQUEST['end_time_hour'].":".$_REQUEST['end_time_min'].$_REQUEST['end_time_timemode']);
// 			}else{
				
			if($_REQUEST['report_time_mode'] == 'history'){
				
				$timestamp = strtotime($_REQUEST['date_month']."/".$_REQUEST['date_day']."/".$_REQUEST['date_year']." 00:00:00");
				$timestamp2 = $timestamp + 86399;
				
			}else{
				
				$timestamp = mktime(0,0,0);
				$timestamp2 = mktime(23,59,59);
			}
				//$timestamp2 = strtotime($_REQUEST['end_date_month']."/".$_REQUEST['end_date_day']."/".$_REQUEST['end_date_year']." 23:59:59");
// 			}
			
			
			
		}else{
			
			$timestamp = mktime(0,0,0);
			$timestamp2 = mktime(23,59,59);
			
			
		}
		
		
		
		if(!isset($_REQUEST['no_nav'])){
			?><script>

			function togHistSearch(way){
				if(way == 'history'){
					ieDisplay('most_recent_cell', 0);
					ieDisplay('historical_cell', 1);
					
				}else{
					ieDisplay('most_recent_cell', 1);
					ieDisplay('historical_cell', 0);
				}//report_time_mode
			}
			
			</script>
			<form id="listperf_report" method="POST" action="<?=$_SERVER['PHP_SELF']?>?area=list_tools&tool=performance_reports&no_script=1" onsubmit="return genReport(this, 'list_performance')">

				<input type="hidden" name="generate_report">


			<table border="0" width="100%">
			<tr>
				<td colspan="2" height="40" class="pad_left ui-widget-header">

					List Performance Report

				</td>
			</tr><?
			
			
			/***
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
		</tr>**/
		?><tr>
			<td colspan="2" align="left">
				<table border="0" class="lb" cellpadding="1" cellspacing="1" width="300">
				<tr>
					<th height="30">Agent Cluster:</th>
					<td><?php
					
					
						//makeClusterDD($name, $selected, $css, $onchange)
						echo $this->makeClusterDD("agent_cluster_idx", 
												(!isset($_REQUEST['agent_cluster_idx']) || intval($_REQUEST['agent_cluster_idx']) < 0)?-1:$_REQUEST['agent_cluster_idx'], 
												'', 
													""); 
						?>
					</td>
				</tr>
				<tr>
					<td height="30" colspan="2" align="left">
						<input type="radio" name="report_time_mode" value="current"<?=($_REQUEST['report_time_mode'] != 'history')?' CHECKED ':''?> onclick="togHistSearch(this.value)">Today/Most current
					</td>
				</tr>
				<tr>
					<td height="30" colspan="2" align="center" id="most_recent_cell" <?=($_REQUEST['report_time_mode'] == 'history')?' class="nod" ':''?>>
					
						<table border="0" align="center" width="100%">
						<tr>
							<td align="center">
								<input type="checkbox" name="force_fresh_pull" value="1" /> Force fresh data pull (Slower)
							</td>
						</tr>
						</table>
					
					</td>
				</tr>
				<tr>
					<td height="30" colspan="2" align="left">
						<input type="radio" name="report_time_mode" value="history" <?=($_REQUEST['report_time_mode'] == 'history')?' CHECKED ':''?> onclick="togHistSearch(this.value)">Historical 
					</td>
				</tr>
				<tr>
					<td height="30" colspan="2" align="center" id="historical_cell"<?=($_REQUEST['report_time_mode'] != 'history')?' class="nod" ':''?>>
					
						<table border="0">
						<tr>
							<th>Date:</th>
							<td>
          						<?php  echo makeTimebar("date_", 1, null, false, $timestamp); ?>
          						
            				</td>
						</tr>

						</table>
						
					</td>
				</tr>
				<tr>
					<th height="50" colspan="2">

						<span id="list_performance_loading_plx_wait_span" class="nod"><img src="images/ajax-loader.gif" border="0" /> Loading, Please wait...</span>
						
						<span id="list_performance_submit_report_button">
							<input type="submit" value="Load">
						</span>

							
					</th>
				</tr>
					
				</table>


			</td>
		</tr>

		</table>
		</form>
			<br /><br /><?php
		}else{

			?><meta charset="UTF-8">
			<meta name="google" content="notranslate">
			<meta http-equiv="Content-Language" content="en"><?php
        }



        if (isset($_POST['generate_report'])) {
            $time_started = microtime_float();

// print_r($_REQUEST);exit;
            ## TIME

            $stime = $timestamp;
            $etime = $timestamp2;
            
            ## AGENT CLUSTER
            $agent_cluster_id = $_SESSION['site_config']['db'][intval($_REQUEST['agent_cluster_idx'])]['cluster_id'];

            
			$force_fresh_pull = ($_REQUEST['force_fresh_pull'])?true:false;
            
            ## GENERATE AND DISPLAY REPORT
			$html = $this->makeHTMLReport($stime, $etime, $agent_cluster_id, $force_fresh_pull);


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

					    $('#listperf_table').DataTable({

							"lengthMenu": [[ -1, 20, 50, 100, 500], ["All", 20, 50, 100,500 ]],
							"order": [[12, "desc"]]

					    });



					} );

				</script><?php
            }
        }
    }

    function getRecentSnapshot($stime, $etime, $vici_cluster_id){
    	$stime = intval($stime);
    	$etime = intval($etime);
    	$vici_cluster_id = intval($vici_cluster_id);
    	
    	$sql = "SELECT * FROM `list_performance_history` ".
      	" WHERE `time` BETWEEN '$stime' AND '$etime' AND vici_cluster_id='$vici_cluster_id'".
      	" ORDER BY `time` DESC LIMIT 1";
    	
    	//echo $sql;
    	
    	$row = $_SESSION['dbapi']->querySQL($sql);
    	
    	if($row){
    		
    		return $row;
    		
    	}else{
    		return null;
    	}
    	
    }

    function makeHTMLReport($stime, $etime, $vici_cluster_id, $force_fresh_pull = false){
    	
    	$report_stime = time();
    	
    	echo '<span style="font-size:9px">makeHTMLReport('."$stime, $etime, $vici_cluster_id, $force_fresh_pull) called</span><br /><br />\n";
    	
    	
    	if($force_fresh_pull){
    	
    		//generateData($stime, $etime, $vici_cluster_id )
    		$data_arr = $this->generateData($stime, $etime, $vici_cluster_id);
    		
    		
    		// SAVE THE DATA
    		$json_data = json_encode($data_arr);
    		
    		$sql = "INSERT INTO `list_performance_history` (`time`, `vici_cluster_id`, `json_data`) VALUES ".
      				"(".$report_stime.",".$vici_cluster_id.",'".mysqli_real_escape_string($_SESSION['dbapi']->db, $json_data)."') ".
      				"ON DUPLICATE KEY UPDATE `json_data`='".mysqli_real_escape_string($_SESSION['dbapi']->db, $json_data)."' ";
    		
  
    		$_SESSION['dbapi']->execSQL($sql);
    		
    		
    	}else{
    	
    		// LOAD FROM MOST RECENT SNAPSHOT
    		$row = $this->getRecentSnapshot($stime, $etime, $vici_cluster_id);
    		
    		if($row == null){
    			
    			$data_arr = $this->generateData($stime, $etime, $vici_cluster_id);
    			
    			
    			// SAVE THE DATA
    			$json_data = json_encode($data_arr);
    			
    			$sql = "INSERT INTO `list_performance_history` (`time`, `vici_cluster_id`, `json_data`) VALUES ".
      			"(".$etime.",".$vici_cluster_id.",'".mysqli_real_escape_string($_SESSION['dbapi']->db, $json_data)."') ".
      			"ON DUPLICATE KEY UPDATE `json_data`='".mysqli_real_escape_string($_SESSION['dbapi']->db, $json_data)."' ";
    			
    			
    			$_SESSION['dbapi']->execSQL($sql);
    			
    		}else{
    			$data_arr = json_decode($row['json_data'], TRUE);
    		}
    		
    	}
    	
    	$vici_cluster_idx = getClusterIndex( $vici_cluster_id);
    	
  //  	print_r($data_arr);exit;
    	
    	if (count($data_arr) < 1) {
            return null;
        }

        // ACTIVATE OUTPUT BUFFERING
        ob_start();
        ob_clean(); 
        
        
        ?><h1><?php

			if($campaign_code){
				echo $campaign_code.' ';
			}

			echo "List Performance - ";

// 			if($agent_cluster_id >= 0){

			echo $_SESSION['site_config']['db'][$vici_cluster_idx]['name'].' - ';
// 			}

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


			if(date("m-d-Y", $stime) == date("m-d-Y", $etime)){

				echo date("m-d-Y", $stime);

			}else{
				echo date("m-d-Y", $stime).' to '.date("m-d-Y", $etime);
        	}
        	
        	
        	
        ?></h1><?
        
        if($row['time'] > 0){
        	
        	echo '<h2 align="center">(Cached, as of '.date("h:i:s T, m/d/Y", $row['time']).')</h2>';
        }else{
        	echo '<h2 align="center">(as of '.date("h:i:s T, m/d/Y", $report_stime).')</h2>';
        }
        

		


		?><table id="listperf_table" style="width:100%" border="0"  cellspacing="1">
		<thead>
		<tr>
			<th title="The LIST ID in Vicidial.">LIST ID</th>
			<th title="The name of the list, in the dialer.">LIST Name</th>
			<th title="The campaign ID of the list, in the dialer.">Campaign ID</th>
			<th title="Total number of calls taken for the specified date(s)">Total calls</th>

			<th title="Number of Answering machines">Answering Machines</th>
			<th title="Percentage of Answering machines to Total Calls">AnsMach %</th>
			<th title="Total number of SALES for the specified date(s)">Total Sales</th>
			<th title="Contacts per hour, and Calls per Worked hour">Contacts</th>
			<th title="The maximum unique/distinct agents that touched this list today"># Agents</th>
			<th title="In Hours - Approx. How long the list ran for, based on first and last lead for today." align="right">List Run Time</th>
			<th title="The Number of contacts per agent, per hour, using the approx list run time, and total distinct agents that touched it today.">Contacts/agent/hr</th>
			<th align="right" title="Conversion Percentage">CONV %</th>	
			<th align="right" title="The last time we called this list.">Last Call Date</th>
		</tr>
		</thead>
		<tbody><?



		foreach($data_arr as $list_id => $data){


			$ans_percent = ($data['data']['total_cnt'] > 0)?round(  (($data['data']['answer_cnt'] / $data['data']['total_cnt']) * 100), 2).'%':'-';

			$conversion_percent = (($data['data']['not_interested_cnt'] + $data['data']['sale_cnt']) <= 0)?0: (($data['data']['sale_cnt'] / ($data['data']['not_interested_cnt'] + $data['data']['sale_cnt'])) * 100);

			$list_run_hours = round(($data['data']['list_run_time'] / 3600), 2);
			
			
			$contacts_agents_hours = ($data['data']['num_agents'] > 0 && $list_run_hours > 0)?round(($data['data']['contacts_cnt'] / $data['data']['num_agents'] / $list_run_hours), 2):0;
			
			?><tr><?

					if($data['vici']['active'] == 'Y'){
						?><td  class="greenbg" title="List is currently ACTIVE"><?
					}else{
						echo '<td>';
					}
				
					echo htmlentities($list_id);
					echo '</td>';
				
				?><td><?=htmlentities($data['vici']['list_name'])?></td>
				<td><?=htmlentities($data['vici']['campaign_id'])?></td>
				<td align="center"><?=number_format($data['data']['total_cnt'])?></td>
				
				<td align="center"><?=number_format($data['data']['answer_cnt'])?></td>
				<td align="center"><?=$ans_percent?></td>
				<td align="center"><?=number_format($data['data']['sale_cnt'])?></td>
				<td align="center"><?=number_format($data['data']['contacts_cnt'])?></td>
				
				<td align="center"><?=number_format($data['data']['num_agents'])?></td>
				<td align="center" align="right"><?=$list_run_hours?> hrs</td>			
				<td align="center"><?=$contacts_agents_hours?></td>				
				
				<td align="center"><?=number_format($conversion_percent,2)?>%</td>
				<td align="center" nowrap><?=$data['vici']['list_lastcalldate']?></td>
			</tr><?

		}

		?></tbody><?


/**		$t_ans_percent = round(  (($totals['total_AnswerMachines'] / $totals['total_calls']) * 100), 2);

		?><tfoot>
		<tr><?
				// CHECK FOR THIS, TO MAKE SURE ITS NOT THE EMAIL REPORT RUNNING

				if($_SESSION['user']['priv'] > 3){

					?><th colspan="2" style="border-top:1px solid #000" align="left">Total Agents: <?=count($agent_data_arr)?></th><?

				}else{

					?><th style="border-top:1px solid #000" align="left">Total Agents: <?=count($agent_data_arr)?></th><?

				}


			?><th style="border-top:1px solid #000"><?=number_format($totals['total_activity_paid_hrs'],2)?></th>
			<th style="border-top:1px solid #000"><?=number_format($totals['total_activity_wrkd_hrs'],2)?></th>
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
			<th style="border-top:1px solid #000" align="right"><?=number_format($paid_sale_percent,2)?>%</th>
			<th style="border-top:1px solid #000" align="right"><?=number_format($paid_sale_amount_percent,2)?>%</th>


			<th style="border-top:1px solid #000" align="center"><?=number_format(($totals['total_sale_cnt']-$totals['total_paid_sale_cnt']))?></th>
			<th style="border-top:1px solid #000" align="right"><?=number_format($unpaid_sale_percent,2)?>%</th>


			<th style="border-top:1px solid #000" align="right"><?=number_format($totals['total_closing'], 2)?>%</th>
			<th style="border-top:1px solid #000" align="right"><?=number_format($totals['total_conversion'], 2)?>%</th>
			<th style="border-top:1px solid #000" align="right"><?=number_format($totals['total_yes2all'],2)?>%</th>

			<th style="border-top:1px solid #000" align="right">$<?=number_format($totals['total_sales'])?></th>

			<th style="border-top:1px solid #000" align="right">$<?=number_format($totals['total_avg'], 2)?></th>
			<th style="border-top:1px solid #000" align="right">$<?=number_format($totals['total_paid_hr'], 2)?></th>
			<th style="border-top:1px solid #000" align="right">$<?=number_format($totals['total_wrkd_hr'], 2)?></th>

		</tr>
		</tfoot>**/
		
		
		?></table>
		<?php

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

		//$out .= '<option value="-1" '.(($selected == '-1')?' SELECTED ':'').'>[All]</option>';


		foreach($_SESSION['site_config']['db'] as $dbidx=>$db){

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

            $res = $_SESSION['dbapi']->ROquery("SELECT campaign_code FROM campaign_codes WHERE 1 ORDER by campaign_code ASC"); //account_id='".$_SESSION['account']['id']."'

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
    public function sendReportEmails(){
    	
    	
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

        $sent_report_total = 0;
        
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

            case 2: // VERIFIER CALL STATS

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

            case 3: // SUMMARY REPORT

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
                
                
            case 4: // ROUSTER REPORT
            	
            	
            	include_once($_SESSION['site_config']['basedir'].'classes/rouster_report.inc.php');
            	
            	
            	
            	$html = $_SESSION['rouster_report']->makeHTMLReport($stime, $etime, $cluster_id, $user_group, null, $source_cluster_id, $ignore_source_cluster_id, $source_user_group, $combine_users);
            	//									            	makeHTMLReport($stime, $etime, $cluster_id, $user_group, $ignore_users, $source_cluster_id = 0, $ignore_source_cluster_id = 0, $source_user_group = null, $combine_users = false){
            	
            	
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

			}else{
				
				$sent_report_total++;
				
				echo date("H:i:s m/d/Y")." - Successfully emailed ".$row['email_address']." - ".$subject."\n";

				// UPDATE last_ran TIME

				$dat = array();
				$dat['last_ran'] = $curtime;
				aedit($row['id'], $dat, "report_emails");


			}



		} // END WHILE (report emails)


		echo date("H:i:s m/d/Y")." - Finished sendReportEmails()\n";

		
		return $sent_report_total;
	}







} // END OF CLASS
