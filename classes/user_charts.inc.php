<?	/***************************************************************
	 * USER CHARTS
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['user_charts'] = new UserCharts;


class UserCharts{



	function UserCharts(){

		## REQURES DB CONNECTION!
		$this->handlePOST();
	}


	function handlePOST(){


	}

	function handleFLOW(){

		if(!checkAccess('user_charts')){


			accessDenied("User Charts");

			return;

		}else{



			$this->makeReport();

		}

	}



	/**
	 * @param	$time_frame		day/week/month/year		The timeframe of the report/chart to generate
	 * @param	$stime			(int)					Timestamp of the first second of the timeframe you want to generate
	 * @param	$max_mode		true/false				Get the max values for the timeframe, instead of the Average (default)
	 */
	function generateData($time_frame,  $stime, $max_mode = false, $short_mode = false){

		$px_server_id = intval($px_server_id);
		$stime = intval($stime);



		if(!$stime){
			return null;
		}


		$servers_data = array();

		connectPXDB();

		$data = array();

		switch($time_frame){
		default:
		case 'day':

			$etime = $stime + 86399;

			// HOURLY CHART


			for($x = 0;$x < 96;$x++){

				$tmpstime = $stime + ($x * 900);
				$tmpetime = $tmpstime + 899;

// QUERY ANALISYS CRAP
//				$tmpbutts = querySQL("EXPLAIN SELECT ".((!$max_mode)?' AVG(num_users) ':' MAX(num_users)')." FROM `server_logs` ".
//								" WHERE `time` BETWEEN '$tmpstime' AND '$tmpetime' ".
//								" GROUP BY server_id");
//				print_R($tmpbutts);

				$res = query(	"SELECT ".((!$max_mode)?' AVG(num_users) ':' MAX(num_users)')." FROM `server_logs` ".
								" WHERE `time` BETWEEN '$tmpstime' AND '$tmpetime' ".
								" GROUP BY server_id"
									//(($px_server_id > 0)?" AND server_id='$px_server_id' ":"")
						,1);

				$tmparr = array( (($x%4 == 0)?date(  (($short_mode)?"g":"ga")  , $tmpstime):''), $x/4, 0);
				$idx = 2;
				while($row = mysqli_fetch_row($res)){

					list($users_cnt) = $row;

					$tmparr[$idx] += intval($users_cnt);

				}


				$data[$x] = $tmparr;
			}


			return $data;




			break;
		case 'week':

			$etime = $stime + 604800;


			for($x = 0;$x < 28;$x++){

//				$tmpstime = $stime + ($x * 86400);
//				$tmpetime = $tmpstime + 86399;
				$tmpstime = $stime + ($x * 21600);
				$tmpetime = $tmpstime + 21599;

				$diw = floor($x / 7);

// QUERY ANALISYS CRAP
//				$tmpbutts = querySQL("EXPLAIN SELECT ".((!$max_mode)?' AVG(num_users) ':' MAX(num_users)')." FROM `server_logs` ".
//								" WHERE `time` BETWEEN '$tmpstime' AND '$tmpetime' ".
//								" GROUP BY server_id");
//				print_R($tmpbutts);

				$res = query(	"SELECT ".((!$max_mode)?' AVG(num_users) ':' MAX(num_users)')." FROM `server_logs` ".
								" WHERE `time` BETWEEN '$tmpstime' AND '$tmpetime' ".
								" GROUP BY server_id"
									//(($px_server_id > 0)?" AND server_id='$px_server_id' ":"")
						,1);

				$tmparr = array( (($x%4 == 0)?date("D jS", $tmpstime):''), $x/4, 0);
				$idx = 2;
				while($row = mysqli_fetch_row($res)){

					list($users_cnt) = $row;

					$tmparr[$idx] += intval($users_cnt);

				}


				$data[$x] = $tmparr;
			}


			return $data;


			break;
		case 'month':

			// SANITIZE STIME INPUT
			$stime = mktime(0,0,0,date("m", $stime),1, date("Y", $stime));

			$dim = date('t', $stime);




			for($x = 0;$x < ($dim * 4);$x++){

//				$tmpstime = $stime + ($x * 86400);
//				$tmpetime = $tmpstime + 86399;

				$tmpstime = $stime + ($x * 21600);
				$tmpetime = $tmpstime + 21599;

// QUERY ANALISYS CRAP
//				$tmpbutts = querySQL("EXPLAIN SELECT ".((!$max_mode)?' AVG(num_users) ':' MAX(num_users)')." FROM `server_logs` ".
//								" WHERE `time` BETWEEN '$tmpstime' AND '$tmpetime' ".
//								" GROUP BY server_id");
//				print_R($tmpbutts);

				$res = query(	"SELECT ".((!$max_mode)?' AVG(num_users) ':' MAX(num_users)')." FROM `server_logs` ".
								" WHERE `time` BETWEEN '$tmpstime' AND '$tmpetime' ".
								" GROUP BY server_id"
									//(($px_server_id > 0)?" AND server_id='$px_server_id' ":"")
						,1);

				$tmparr = array((($x%4==0)?date("j", $tmpstime):''), $x/4, 0);
				$idx = 2;
				while($row = mysqli_fetch_row($res)){

					list($users_cnt) = $row;

					$tmparr[$idx] += intval($users_cnt);

				}


				$data[$x] = $tmparr;
			}


			return $data;



			break;
		case 'year':
			// SANITIZE STIME INPUT
			$stime = mktime(0,0,0,1,1, date("Y", $stime));

			//$dim = date('t', $stime);



			$tmpmonth = 0;

			for($x = 0;$x < 52;$x++){

				// FIGURE OUT WHAT MONTH IT IS, BY THE WEEK
				$curday = ($x * 7);
				//



				$tmpstime = mktime(0,0,0, 1, $curday+1,  date("Y", $stime) );//$stime + ($x * 86400);
				$tmpetime = mktime(23,59,59, 1, (($x+1) * 7)-1, date("Y",$tmpstime) );


				$show_label = ($tmpmonth != date("m", $tmpstime))?true:false;

				$tmpmonth = date("m", $tmpstime);

				//$tmpetime = mktime(23,59,59, $x, date("t", $tmpstime), date("Y",$tmpstime) );

// QUERY ANALISYS CRAP
//				$tmpbutts = querySQL("EXPLAIN SELECT ".((!$max_mode)?' AVG(num_users) ':' MAX(num_users)')." FROM `server_logs` ".
//								" WHERE `time` BETWEEN '$tmpstime' AND '$tmpetime' ".
//								" GROUP BY server_id");
//				print_R($tmpbutts);

				$res = query(	"SELECT ".((!$max_mode)?' AVG(num_users) ':' MAX(num_users)')." FROM `server_logs` ".
								" WHERE `time` BETWEEN '$tmpstime' AND '$tmpetime' ".
								" GROUP BY server_id"
									//(($px_server_id > 0)?" AND server_id='$px_server_id' ":"")
						,1);

				$tmparr = array( (($show_label)?date("M", $tmpstime):'-') , $x, 0);
				$idx = 2;
				while($row = mysqli_fetch_row($res)){

					list($users_cnt) = $row;

					$tmparr[$idx] += intval($users_cnt);

				}


				$data[$x] = $tmparr;
			}


			return $data;


			break;
		}
/**

		// GET A LIST OF TEH AGENTS
		// ACTIVITY LOG: THE AGENTS THAT ARE WORKING TODAY
		$res = query("SELECT * FROM `server_logs` WHERE 1 ".
						(($stime && $etime)?" AND `time` BETWEEN '$stime' AND '$etime' ":'').
						(($px_server_id > 0)?" AND server_id='$px_server_id' ":"").
						" ORDER BY id ASC "
						,1);

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$servers_data[$row['server_id']][] = array($row['time'], $row['num_users']);

		}

		$data = array();

		// COMBINE SERVERS INFO
		if($px_server_id <= 0){



		}else{



		}

**/


//		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
//
//			$data[] = array($row['time'], $row['num_users']);
//
//		}

		return $data;
	}


	function makeReport(){

		if(isset($_REQUEST['generate_agent_stat_report'])){


			if($_REQUEST['date_mode'] == 'daterange'){

				$stime = mktime(0,0,0, $_REQUEST['s_date_month'], $_REQUEST['s_date_day'], $_REQUEST['s_date_year'] );
				$etime = mktime(23,59,59, $_REQUEST['e_date_month'], $_REQUEST['e_date_day'], $_REQUEST['e_date_year'] );

			}else{

				$stime = mktime(0,0,0, $_REQUEST['s_date_month'], $_REQUEST['s_date_day'], $_REQUEST['s_date_year'] );
				$etime = $stime + 86399;
			}

		}else{
			$stime = mktime(0,0,0);
			$etime = mktime(23,59,59);


		}

		$cluster_id = intval($_REQUEST['s_cluster_id']);
		$cluster_id = ($cluster_id)?$cluster_id : 9; // DEFAULT TO VERIFIER CLUSTER

		$user_group = trim($_REQUEST['s_user_group']);




		?><table border="0" width="100%"><?


		//if(!isset($_REQUEST['no_script'])){

		?><script>

			function toggleDateSearchMode(way){

//				if(way == 'daterange'){
//					$('#end_date_row').show();
//				}else{
//					$('#end_date_row').hide();
//				}
			}


			function loadGraphImage(frm){

				$('#graph_image').show();

				$('#graph_image').attr("src", "images/ajax-laoder.gif");


				$('#graph_image').attr("src", "graph.php?area=user_charts&time_frame="+frm.time_frame.value+"&max_mode="+frm.max_mode.value+"&start_date="+frm.s_date_month.value+"/"+frm.s_date_day.value+"/"+frm.s_date_year.value);
			}

		</script>
		<tr>
			<td height="40" class="ui-widget-header pad_left">User Charts</td>
		</tr>
		<tr>
			<td>
			<form method="POST" action="<?=stripurl()?>" onsubmit="loadGraphImage(this);return false;">

				<input type="hidden" name="generate_agent_stat_report">

			<table border="0">
			<tr>
				<th>Time frame:</th>
				<td><select name="time_frame" onchange="toggleDateSearchMode(this.value)">
					<option value="day">Day Report</option>
					<option value="week"<?=($_REQUEST['time_frame'] == 'week')?' SELECTED':''?>>Week Report</option>
					<option value="month"<?=($_REQUEST['time_frame'] == 'month')?' SELECTED':''?>>Month Report</option>
					<option value="year"<?=($_REQUEST['time_frame'] == 'year')?' SELECTED':''?>>Year Report</option>
				</select></td>
			</tr>
			<tr>
				<th>Date:</th>
				<td><?

					echo makeTimebar("s_date_",1,null,false, $stime,"");

				?></td>
			</tr>
			<tr>
				<th>Total Mode:</th>
				<td><select name="max_mode" >
					<option value="0">Average Users</option>
					<option value="1"<?=($_REQUEST['max_mode'] == '1')?' SELECTED':''?>>Max Users</option>
				</select></td>
			</tr>
			<tr>
				<td colspan="2" align="right">


					<input type="submit" value="Generate Now">
				</td>
			</tr>

			</table>

			</td>
		</tr>


		<tr>
			<td align="center">
				<img id="graph_image" src="" border="0" class="nod">
			</td>
		</tr><?


		?></form><?

		//}




		/*if(isset($_REQUEST['generate_agent_stat_report'])) {


			$data = $this->generateData($cluster_id, $stime, $etime, $user_group);





		?><script>

			toggleDateSearchMode('<?=$_REQUEST['date_mode']?>');

		</script>

		<tr>
			<td style="border-bottom:1px solid #000;font-size:18px;font-weight:bold">

				<br />

				Verifier Call Status Report - <?=date("m/d/Y", $stime)?> - <?=htmlentities($_REQUEST['s_user_group'])?>

			</td>
		</tr>
		<tr>
			<td><table border="0" width="900">
			<tr>
				<th align="left">Agent</th>
				<th align="right"># of Calls</th>
				<th align="right">Sales</th>
				<th align="right">Hangups</th>
				<th align="right">Declines</th>
				<th align="right">Time</th>
				<th align="right">Pause</th>
				<th align="right">Talk Avg</th>
				<th align="right">Dead</th>
				<th align="right">Closing %</th>
				<th align="right">Adj. Closing %</th>
				<th align="right">Hangup %</th>
				<th align="right">Sale Reviews</th>
			</tr>
			<tr style="font-style: italic;">
				<th style="border-bottom:1px solid #000" align="left">&nbsp;</th>
				<th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
				<th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
				<th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
				<th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
				<th style="border-bottom:1px solid #000" align="right">7:30+</th>
				<th style="border-bottom:1px solid #000" align="right">30 or less</th>
				<th style="border-bottom:1px solid #000" align="right">1:10 - 1:20</th>
				<th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
				<th style="border-bottom:1px solid #000" align="right">76% or above</th>
				<th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
				<th style="border-bottom:1px solid #000" align="right">&nbsp;</th>
				<th style="border-bottom:1px solid #000" align="center">&nbsp;</th>
			</tr><?

			$stmicro = $stime * 1000;
			$etmicro = $etime * 1000;

			$running_total_calls = 0;
			$running_total_sales = 0;
			$running_total_hangups = 0;
			$running_total_declines = 0;
			$running_total_reviews = 0;
			foreach($data as $row){




				$tmphours = floor($row['t_time'] / 3600);
				$tmpmin = floor( ($row['t_time'] - ($tmphours * 3600)) / 60 );
				$total_time = $tmphours.':'.(($tmpmin < 9)?'0'.$tmpmin:$tmpmin);


				$tmpmin = floor($row['t_pause']/60);
				$tmpsec = ($row['t_pause']%60);
				$total_pause = $tmpmin.':'.(($tmpsec < 10)?'0'.$tmpsec:$tmpsec);


				// GOTTA AVG THE TALK TIMES, NOT ADD
				$tmptalktime = intval($row['t_talk']);

				//$talktimeavg = $tmptalktime / intval($row['t_call_count']);
				$talktimeavg = $tmptalktime / intval($row['call_cnt']);

				$total_talk = renderTimeFormatted($talktimeavg);

				$total_dead = renderTimeFormatted($row['t_dead']);


				//$close_percent = number_format( round( (($row['sale_cnt']) / ($row['t_call_count'])) * 100, 2), 2);
				$close_percent = number_format( round( (($row['sale_cnt']) / ($row['call_cnt'])) * 100, 2), 2);

				$adjusted_close_percent = number_format( round( (($row['sale_cnt']) / ($row['call_cnt']-$row['hangup_cnt'])) * 100, 2), 2);


				$hangup_percent = number_format( round( (($row['hangup_cnt']) / ($row['call_cnt'])) * 100, 2), 2);

				// DISPO LOGGGGGGG
				list($reviewcnt) = $_SESSION['dbapi']->queryROW("SELECT COUNT(`id`) FROM `dispo_log` ".
										" WHERE `agent_username`='".addslashes($row['username'])."' ".
										" AND `micro_time` BETWEEN '$stmicro' AND '$etmicro' ".
										" AND `dispo` = 'REVIEW' ".
										" AND `result`='success' ");



				$running_total_calls += $row['call_cnt'];
				$running_total_sales += $row['sale_cnt'];
				$running_total_reviews += $reviewcnt;

				$running_total_hangups += $row['hangup_cnt'];
				$running_total_declines += $row['decline_cnt'];

				?><tr>
					<td><?=strtoupper($row['username'])?></td>
					<td align="right"><?

						echo number_format($row['call_cnt'])

					//		number_format($row['t_call_count'])
					?></td>
					<td align="right"><?=number_format($row['sale_cnt'])?></td>
					<td align="right"><?=number_format($row['hangup_cnt'])?></td>
					<td align="right"><?=number_format($row['decline_cnt'])?></td>
					<td align="right"><?

						if($row['t_time'] >= $this->time_limit){

							echo '<span style="background-color:transparent">'.$total_time.'</span>';
						}else{
							echo '<span style="background-color:yellow">'.$total_time.'</span>';


						}
					?></td>
					<td align="right"><?

						if($row['t_pause'] <= $this->pause_limit){

							echo '<span style="background-color:transparent">'.$total_pause.'</span>';
						}else{

							echo '<span style="background-color:yellow">'.$total_pause.'</span>';
						}



					?></td>
					<td align="right"><?
						$talktimeavg = floor($talktimeavg);

						//echo $talktimeavg.' vs '.$this->talk_lower_limit.' ';


						if($talktimeavg >= $this->talk_lower_limit && $talktimeavg <= $this->talk_upper_limit){

							echo '<span style="background-color:transparent">'.$total_talk.'</span>';
						}else{

							echo '<span style="background-color:yellow">'.$total_talk.'</span>';
						}




					?></td>
					<td align="right"><?

//						if($row['t_dead'] > $this->dead_time_limit){
//
//							echo '<span style="background-color:yellow">'.$total_dead.'</span>';
//						}else{
							echo '<span style="background-color:transparent">'.$total_dead.'</span>';
//						}




					?></td>
					<td align="right"><?

						if(intval($close_percent) >= $this->close_percent_limit){

							echo '<span style="background-color:transparent">'.$close_percent.'%</span>';
						}else{

							echo '<span style="background-color:yellow">'.$close_percent.'%</span>';

						}

					?></td>
					<td align="right"><?

						if(intval($adjusted_close_percent) >= $this->close_percent_limit){

							echo '<span style="background-color:transparent">'.$adjusted_close_percent.'%</span>';
						}else{

							echo '<span style="background-color:yellow">'.$adjusted_close_percent.'%</span>';

						}

					?></td>

					<td align="right"><?

						//if(intval($adjusted_close_percent) >= $this->close_percent_limit){

							echo '<span style="background-color:transparent">'.$hangup_percent.'%</span>';
						//}else{
						//	echo '<span style="background-color:yellow">'.$adjusted_close_percent.'%</span>';
						//}

					?></td>

					<td align="right"><?

						echo number_format($reviewcnt);

					?></td>
				</tr><?

			}



			$total_close_percent = number_format( round( (($running_total_sales) / ($running_total_calls)) * 100, 2), 2);

			$total_adj_close_percent = number_format( round( (($running_total_sales) / ($running_total_calls-$running_total_hangups)) * 100, 2), 2);

			$total_hangup_percent = number_format( round( (($running_total_hangups) / ($running_total_calls)) * 100, 2), 2);

			// TOTALS ROW
			?><tr>
				<th style="border-top:1px solid #000" align="left">Totals:</th>
				<td style="border-top:1px solid #000" align="right"><?=number_format($running_total_calls)?></td>
				<td style="border-top:1px solid #000" align="right"><?=number_format($running_total_sales)?></td>
				<td style="border-top:1px solid #000" align="right"><?=number_format($running_total_hangups)?></td>
				<td style="border-top:1px solid #000" align="right"><?=number_format($running_total_declines)?></td>

				<td style="border-top:1px solid #000" colspan="4">&nbsp;</td>

				<td style="border-top:1px solid #000" align="right"><?

						if(intval($total_close_percent) >= $this->close_percent_limit){

							echo '<span style="background-color:transparent">'.$total_close_percent.'%</span>';
						}else{

							echo '<span style="background-color:yellow">'.$total_close_percent.'%</span>';

						}


				?></td>
				<td style="border-top:1px solid #000" align="right"><?

						if(intval($total_adj_close_percent) >= $this->close_percent_limit){

							echo '<span style="background-color:transparent">'.$total_adj_close_percent.'%</span>';
						}else{

							echo '<span style="background-color:yellow">'.$total_adj_close_percent.'%</span>';

						}


				?></td>
				<td style="border-top:1px solid #000" align="right"><?

						//if(intval($total_adj_close_percent) >= $this->close_percent_limit){

							echo '<span style="background-color:transparent">'.$total_hangup_percent.'%</span>';

						//}else{
						//	echo '<span style="background-color:yellow">'.$total_hangup_percent.'%</span>';
						//}


				?></td>
				<td style="border-top:1px solid #000" align="right"><?=number_format($running_total_reviews)?></td>
			</tr>
			</table></td>
		</tr>
		<tr>
			<td style="font-size:10px">
				<br />
				<i>Generated on: <?=date("g:ia m/d/Y")?></i>
			</td>
		</tr><?
		} // END IF GENERATE REPORT
*****/



		?></table><?



	}



} // END OF CLASS
