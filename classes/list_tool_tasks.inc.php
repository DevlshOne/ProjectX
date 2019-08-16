<?	/***************************************************************
	 *	Names - Handles list/search/import names
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['tasks'] = new ListToolTasks;


class ListToolTasks{

	var $table	= 'tasks';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'task_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'tasknextfrm';

	var $order_prepend = 'task_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING


	var $refresh_interval = 1000; // IN MILLISECONDS - HOW LONG BETWEEN REFRESHES OF THE "VIEW TASK" DIALOG


	var $command_options = array(

		"import_list"=>"Import List",
		"import_dnc_list"=>"Import Do Not Call List",
		"build_list"=>"Build Vici List",
		"move_vici_list"=>"Move Vici List",
		"move_import_list"=>"Move Import List",

		"purge_import"=>"Purge Import Leads",
		"delete_import"=>"Delete Import"

	);


	function ListToolTasks(){


		## REQURES DB CONNECTION!



		$this->handlePOST();
	}


	function handlePOST(){

		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/list_tool_tasks.api.php FOR POST HANDLING!
		// <3 <3 -Jon

	}

	function handleFLOW(){
		# Handle flow, based on query string

		if(!checkAccess('list_tools')){

			accessDenied("List Tools");
			return;

		}




		if(isset($_REQUEST['view_task'])){

			if(isset($_REQUEST['view_details'])){

				$this->makeViewDetails($_REQUEST['view_task']);

			}else{
				$this->makeView($_REQUEST['view_task']);
			}

		}else{
			$this->listEntrys();
		}



	}






	function listEntrys(){


		?><script>

			var task_delmsg = 'Are you sure you want to delete this task?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var TasksTableFormat = [

				['id','align_center'],

				['command','align_center'],

				['status','align_center'],

				['[get:cluster_name:id]','align_center'],
//				['[get:list_ids:id]','align_center'],

				['[get:import_name:source_import_id]','align_center'],

				['[percent:progress]','align_center'],

				['[render:number:starting_count]','align_right'],
				['[render:number:records_affected]','align_right'],

				['[time:time_started]','align_center'],
				['[time:time_ended]','align_center'],


<?/**				['name','align_left'],
				['[get:voice_name:voice_id]','align_center'],
				['filename','align_center'],

				['[delete]','align_center']
				***/?>
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getTasksURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=list_tool_tasks&"+
								"mode=xml&"+

								's_id='+escape(frm.s_id.value)+"&"+

								's_command='+escape(frm.s_command.value)+"&"+
								's_status='+escape(frm.s_status.value)+"&"+

								's_import_id='+escape(frm.s_import_id.value)+"&"+

								's_date_month='+escape(frm.stime_month.value)+"&"+'s_date_day='+escape(frm.stime_day.value)+"&"+'s_date_year='+escape(frm.stime_year.value)+"&"+
								's_date2_month='+escape(frm.etime_month.value)+"&"+'s_date2_day='+escape(frm.etime_day.value)+"&"+'s_date2_year='+escape(frm.etime_year.value)+"&"+
								's_date_mode='+escape(frm.date_mode.value)+"&"+


								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var tasks_loading_flag = false;

			/**
			* Load the name data - make the ajax call, callback to the parse function
			*/
			function loadTasks(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = tasks_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("NAMES ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('tasks_loading_flag = true');
				}

				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				loadAjaxData(getTasksURL(),'parseTasks');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseTasks(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('task',TasksTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('tasks',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadTasks()'
								);

				}else{

					hidePageSystem('tasks');

				}

				eval('tasks_loading_flag = false');
			}


			function handleTaskListClick(id){

				displayViewTaskDialog(id);

			}



			function resetTaskForm(frm){

				frm.s_id.value = '';
				frm.s_command.value = '';
				frm.s_status.value = '';
				frm.s_date_mode.value = 'any';
				frm.s_import_id.value='';

				toggleDateMode( 'any' );
			}


			var tasksrchtog = false;

			function toggleTaskSearch(){
				tasksrchtog = !tasksrchtog;
				ieDisplay('task_search_table', tasksrchtog);
			}


			function toggleDateMode(way){

				if(way == 'daterange'){
					$('#nodate_span').hide();
					$('#date1_span').show();

					// SHOW EXTRA DATE FIELD
					$('#date2_span').show();

				}else if(way == 'any'){

					$('#nodate_span').show();
					$('#date1_span').hide();
					$('#date2_span').hide();

				}else{
					$('#nodate_span').hide();

					$('#date1_span').show();

					// HIDE IT
					$('#date2_span').hide();
				}

			}

		</script><?


		$_SESSION['list_tools']->makeViewTaskGUI();



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadTasks();return false">
			<input type="hidden" name="searching_task">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td width="500">
						Tasks
						<?/**&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Search" onclick="toggleTaskSearch()">
						**/?>
					</td>

					<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadTasks();return false">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="500">500</option>
					</select></td>

					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="tasks_prev_td" class="page_system_prev"></td>
							<td id="tasks_page_td" class="page_system_page"></td>
							<td id="tasks_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" width="100%" id="task_search_table">
			<tr>
				<th class="row2">ID</th>
				<th class="row2">Import</th>
				<th class="row2">Command</th>
				<th class="row2">Status</th>
				<th class="row2">

					<select name="s_date_mode" id="date_mode" onchange="toggleDateMode(this.value);">
						<option value="any"<?=($_REQUEST['s_date_mode']=='any' || !$_REQUEST['s_date_mode'])?' SELECTED ':''?>>ANY</option>
						<option value="date"<?=($_REQUEST['s_date_mode']=='date')?' SELECTED ':''?>>Date</option>
						<option value="daterange"<?=($_REQUEST['s_date_mode']=='daterange')?' SELECTED ':''?>>Date Range</option>

					</select>
				</th>

				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_id" size="5" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>
				<td align="center"><?

					echo $_SESSION['list_tools']->makeImportDD(null,'s_import_id','s_import_id','',"",null,"[All]");

				?></td>
				<td align="center"><?

					echo $this->makeCommandDD('s_command', $_REQUEST['s_command'], "", true);

				?></td>
				<td align="center">

					<select id="s_status" name="s_status">
						<option value="">[ALL]
						<option value="new"<?=(			$_REQUEST['s_status'] == 'new')?" SELECTED ":""?>>New</option>
						<option value="running"<?=(		$_REQUEST['s_status'] == 'running')?" SELECTED ":""?>>Running Now</option>
						<option value="finished"<?=(	$_REQUEST['s_status'] == 'finished')?" SELECTED ":""?>>Finished</option>
						<option value="error"<?=(		$_REQUEST['s_status'] == 'error')?" SELECTED ":""?>>Error</option>
						<option value="canceling"<?=(	$_REQUEST['s_status'] == 'canceling')?" SELECTED ":""?>>Cancellation Pending</option>
						<option value="canceled"<?=(	$_REQUEST['s_status'] == 'canceled')?" SELECTED ":""?>>Canceled</option>

					</select>
				</td>
				<td align="center">



					<span id="date1_span" class="nod"><?
						echo makeTimebar("stime_", 1, null,false,time());
					?></span><?

					?><span id="date2_span" class="nod"><br /><?
						echo makeTimebar("etime_",1,null,false,time());
					?></span>

					<span id="nodate_span">
						ANY/ALL DATES
					</span>

				</td>

				<td><input type="button" value="Reset" onclick="resetTaskForm(this.form);resetPageSystem('<?=$this->index_name?>');loadTasks();"></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="task_table">
			<tr>
				<th class="row2" align="center"><?=$this->getOrderLink('id')?>ID</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('command')?>Command</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('status')?>Status</a></th>



				<th class="row2" align="center">Target Cluster</th>

				<th class="row2" align="center"><?=$this->getOrderLink('source_import_id')?>Import</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('progress')?>Progress</a></th>
				<th class="row2" align="right"><?=$this->getOrderLink('starting_count')?>Starting Count</a></th>
				<th class="row2" align="right"><?=$this->getOrderLink('records_affected')?>Records Affected</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('time_started')?>Time Started</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('time_ended')?>Time Ended</a></th>

				<th class="row2">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			toggleDateMode($('#date_mode').val() );

			$("#dialog-modal-view-task").dialog({
				autoOpen: false,
				width: 700,
				height: 300,
				modal: false,
				draggable:true,
				resizable: true
			});

			loadTasks();

		</script><?

	}


	function makeCommandDD($name, $sel, $onchange, $blank_entry){

		$out = '<select name="'.$name.'" id="'.$name.'" ';

		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';

		$out .= '>';


		if($blank_entry){

			$out .= '<option value="">'.((is_string($blank_entry))?$blank_entry:"[All]").'</option>';

		}

		foreach($this->command_options as $cmd_code=>$command){

			$out .= '<option value="'.$cmd_code.'" ';

			$out .= ($sel == $cmd_code)?' SELECTED ':'';

			$out .= '>'.$command.' ('.$cmd_code.')</option>';


		}

		$out .= '</select>';

		return $out;

	}


	function makeViewDetails($id){

		$id=intval($id);


		if(!$id){

			echo "ERROR: Task ID not specified";
			return;
		}


		// LOAD THE TASK
		$row = $_SESSION['dbapi']->list_tool_tasks->getByID($id);
		$config = $_SESSION['JXMLP']->parseOne($row['config_xml'],"Config", 1);


		?><table border="0" width="100%" style="font-size:12px">
		<tr>
			<th colspan="2" align="left" style="border-bottom:1px solid #000">Task #<?=$id?></th>
		</tr>
		<tr>
			<th>Requested # of leads:</th>
			<td align="right"><?=number_format($config['max_list_size'])?></td>
		</tr>
		<tr>
			<td colspan="2" align="center">

				<table border="0" style="font-size:12px">
				<tr>
					<td align="right"><?


						if($row['details']){

							echo nl2br($row['details']);

						}else{
							echo "No Details";
						}

					?></td>
				</tr>
				</table>

			</td>
		</tr>
		</table><?

	}

	function makeView($id){

		$id=intval($id);


		if(!$id){

			echo "ERROR: Task ID not specified";
			return;
		}


		// LOAD THE TASK
		$row = $_SESSION['dbapi']->list_tool_tasks->getByID($id);

		// LOAD THE RELATED SUB TASKS
		$related_tasks = array();
		if($row['group_id'] > 0){

			$res = query("SELECT * FROM `tasks` WHERE `group_id`='".$row['group_id']."' AND id != '".$row['id']."' ");
			while($r2 = mysqli_fetch_array($res, MYSQLI_ASSOC)){
				$related_tasks[] = $r2;
			}
		}

//echo htmlentities($row['config_xml']);

		$config = $_SESSION['JXMLP']->parseOne($row['config_xml'],"Config", 1);


		$status_error = ($row['status'] == 'error')?true:false;

		$is_finished = ($row['progress'] >= 100 || $row['status'] == 'finished' || $row['status'] == 'canceled')?true:false;

		$has_unstarted = ($row['status'] == 'new' || $row['status'] == 'running')?true:false; // MEANS THIS TASK, OR SUB TASKS, ARE STILL ABLE TO BE CANCELED


		?><script>


			function cancelTask(taskid){

				$.post("api/api.php?get=list_tool_tasks&action=cancel&mode=raw&task_id="+taskid,null,taskCancelHandler);

			}


			function taskCancelHandler(result){

				var cnt = parseInt(result.trim());

				// SUCCESS
				if(cnt > 0){

					alert(((cnt > 1)?cnt+" Tasks have ":"Task has")+" been signaled to cancel.");

					try{
						window.parent.displayViewTaskDialog(<?=$row['id']?>);
					}catch(e){}

					try{
						window.parent.loadTasks();
					}catch(e){}

				// FAILED
				}else{

					alert("Attempt to cancel task has failed. (Check the task status)");
				}

			}

		</script><?


		echo '<span class="big">'.$this->command_options[$row['command']].'</span>';


//print_r($config);exit;

		switch($row['command']){
		default:
		case 'build_list':




			?><table border="0" width="100%" class="lb" cellspacing="0">
			<tr>
				<th class="row2">ID</th>
				<th class="row2" align="left">Source List(s)</th>
				<th class="row2" align="center">Campaign</th>
				<th class="row2" align="left">Destination</th>
				<th class="row2" align="left">Timezone/States</th>
				<th class="row2" align="center">Progress</th>
				<th class="row2" align="left">Log</th>
			</tr>
			<tr valign="top">
				<td class="row0" align="center"><?=$row['id']?></td>
				<td class="row1" align="left"><?

					$source_arr = preg_split("/,/",$config['source_import_id'], -1, PREG_SPLIT_NO_EMPTY);

					foreach($source_arr as $source_id){

						$source = $_SESSION['dbapi']->list_tool_tasks->getSource($source_id);



						echo date("m/d/Y",$source['time']).'-'.ucfirst($source['phone_type']).'<br />'.$source['name'].'<br /><br />';

					}


				?></td>
				<td class="row0" align="center"><?

				//echo $config['campaign_id'];

					connectPXDB();

					echo $_SESSION['dbapi']->campaign_parents->getCodeByID($config['campaign_id']);///$_SESSION['dbapi']->campaigns->getViciID($config['campaign_id']);

					echo '<br /><br />Cross campaign:'.(($config['check_cross_campaign'] > 0)?'Yes':'No');

					echo '<br /><br />Randomize:'.((strtoupper($config['randomize']) == 'YES')?'Yes':'No');

				?></td>
				<td class="row1" align="left"><?

					echo getClusterName($config['target_vici_cluster_id']).'<br />';

					$list_arr = preg_split("/,/",$config['target_list_ids'], -1, PREG_SPLIT_NO_EMPTY);

					foreach($list_arr as $list_id){

						echo $list_id.'<br />';

					}

				?></td>
				<td class="row0" align="left"><?

					// STATES/TZ
					echo $config['tz_offset'].'<br /><br />';

					//echo $config['states'];

					$state_arr = preg_split("/,/",$config['states'], -1, PREG_SPLIT_NO_EMPTY);

					$scnt = 0;
					foreach($state_arr as $state){

						echo $state.',';

						if(++$scnt%8 == 0)echo '<br />';

					}

					echo '<br /><br />Last Call Time: '.$config['last_called_days'].' Days<br />';

					echo '<br /><a href="#" onclick="var w=window.open(\''.stripurl('view_details').'view_details'.'\',\'TaskDetails\',\'width=300,height=200\');if(w)w.focus()"><u>Details</u></a>';
				?></td>
				<td valign="middle" class="row1" align="center"><?

					// SHOW STATUS
					echo ucfirst($row['status']).'<br />';


					// Progress
					echo '<img src="percent.php?percent='.$row['progress'].'" width="100" height="10" border="0" />';

				?></td>
				<td class="row0" align="left"><?

					// LOGS
					echo nl2br($row['result_log']);

					if($row['time_ended'] > 0){

						echo '<br />';
						$duration = 	$row['time_ended'] - $row['time_started'];
						echo 'Run time: '.rendertime( 	$duration );
					}

				?></td>
			</tr><?
			?></table><?

			//$completed = ($row['progress'] < 100)?false:true;



			if(count($related_tasks) > 0){

				?><table border="0" width="100%" class="lb" cellspacing="0">
				<tr>
					<th class="row2">ID</th>
					<th class="row2" align="center">Progress</th>
					<th class="row2" align="left">&nbsp;</th>
				</tr><?

				foreach($related_tasks as $r2){


					$has_unstarted = ($has_unstarted)?$has_unstarted:(($r2['status'] == 'new' || $r2['status'] == 'running')?true:false);

					?><tr valign="top">
						<td align="center"><?=$r2['id']?></td>
						<td align="center"><?

							// SHOW STATUS
							echo ucfirst($r2['status']).'<br />';


							// Progress
							echo '<img src="percent.php?percent='.$r2['progress'].'" width="100" height="10" border="0" />';

						?></td>
						<td align="left">

							<a href="#" onclick="window.parent.displayViewTaskDialog(<?=$r2['id']?>);return false">[VIEW]</a><?

							// LOGS
							//echo nl2br($row['result_log']);

						?></td>
					</tr><?

				//$completed = ($r2['progress'] < 100)?false:$completed;
					$is_finished = ($r2['progress'] >= 100 || $r2['status'] == 'finished' || $r2['status'] == 'canceled')?true:false;
					$status_error = ($r2['status'] == 'error')?true:$status_error;
				}

				?></table><?


			}






			break;

		/**
		 * MOVE VICIDIAL LIST BETWEEN CLUSTERS
		 */
		case 'move_vici_list':






			?><table border="0" width="100%" class="lb" cellspacing="0">
			<tr>
				<th class="row2">ID</th>
				<th class="row2" align="left">Sources</th>
				<th class="row2" align="left">Destination</th>
				<th class="row2" align="left">Statuses</th>
				<th class="row2" align="left">States</th>
				<th class="row2" align="center">Progress</th>
				<th class="row2" align="left">Log</th>
			</tr>
			<tr valign="top">
				<td class="row0" align="center"><?=$row['id']?></td>
				<td class="row1" align="left" nowrap><?


					echo 'Cluster #'.$config['source_cluster_id'].'<br />'.getClusterName($config['source_cluster_id']).'<br />';
					echo '<br />';
					echo $config['source_lists'];

//					$list_arr = preg_split("/,/",$config['source_lists'], -1, PREG_SPLIT_NO_EMPTY);
//					foreach($list_arr as $list_id){
//
//						echo $list_id.'<br />';
//
//					}

				?></td>
				<td class="row1" align="left"  nowrap><?

					echo 'Cluster #'.$config['target_cluster_id'].'<br />'.getClusterName($config['target_cluster_id']).'<br />';
					echo '<br />';
					echo $config['target_list_id'].'<br />';
					echo '<br />';
					echo (($config['max_leads_per_list'] > 0)?'Max '.number_format($config['max_leads_per_list']):'All').' Leads.<br />';


				?></td>
				<td valign="middle" class="row0" align="left"><?

					//echo $config['statuses'];

					$statarr = preg_split("/,/",$config['statuses'], -1, PREG_SPLIT_NO_EMPTY);
					$z=0;
					foreach($statarr as $status){

						//if($z > 0)echo ',';

						echo $status;



						echo '<br />';

					}

				?></td>
				<td valign="middle" class="row0" align="left"><?

					//echo $config['states'];

					$arr = preg_split("/,/",$config['states'], -1, PREG_SPLIT_NO_EMPTY);

					$z=0;
					foreach($arr as $state){


						if($z > 0 && ($z)%3 != 0)echo ',';

						echo $state;


						if(++$z % 3 == 0){
							echo '<br />';
							//$z=0;
						}
					}

				?></td>
				<td valign="middle" class="row1" align="center"><?

					// SHOW STATUS
					echo ucfirst($row['status']).'<br />';


					// Progress
					echo '<img src="percent.php?percent='.$row['progress'].'" width="100" height="10" border="0" />';

				?></td>
				<td class="row0" align="left"><?

					// LOGS
					echo nl2br($row['result_log']);


					if($row['time_ended'] > 0){

						echo '<br />';
						$duration = 	$row['time_ended'] - $row['time_started'];
						echo 'Run time: '.rendertime( 	$duration );
					}

				?></td>
			</tr><?
			?></table><?


			break;

		case 'import_list':


			?><table border="0" width="100%" class="lb" cellspacing="0">
			<tr>
				<th class="row2">ID</th>
				<th class="row2" align="left">Import to List</th>
				<th class="row2" align="left">Filename</th>
				<th class="row2" align="center">Progress</th>
				<th class="row2" align="left">Log</th>
			</tr>
			<tr>
				<td align="center"><?=$row['id']?></td>
				<td><?
					$source_arr = preg_split("/,/",$config['source_import_id'], -1, PREG_SPLIT_NO_EMPTY);

					foreach($source_arr as $source_id){

						$source = $_SESSION['dbapi']->list_tool_tasks->getSource($source_id);



						echo date("m/d/Y",$source['time']).'-'.ucfirst($source['phone_type']).'<br />'.$source['name'].'<br /><br />';

					}
				?></td>
				<td><?

					$parts = pathinfo($config['filename']);

					echo $parts['basename'];

				?></td>
				<td valign="middle" class="row1" align="center"><?

					// SHOW STATUS
					echo ucfirst($row['status']).'<br />';


					// Progress
					echo '<img src="percent.php?percent='.$row['progress'].'" width="100" height="10" border="0" />';

				?></td>
				<td class="row0" align="left"><?

					// LOGS
					echo nl2br($row['result_log']);

					if($row['time_ended'] > 0){

						echo '<br />';
						$duration = 	$row['time_ended'] - $row['time_started'];
						echo 'Run time: '.rendertime( 	$duration );
					}


				?></td>
			</tr>
			</table><?



			break;
		case 'import_dnc_list':

			?><table border="0" width="100%" class="lb" cellspacing="0">
			<tr>
				<th class="row2">ID</th>
				<th class="row2" align="left">Filename</th>
				<th class="row2" align="left">State</th>
				<th class="row2" align="center">Progress</th>
				<th class="row2" align="left">Log</th>
			</tr>
			<tr>
				<td align="center"><?=$row['id']?></td>
				<td><?

					$parts = pathinfo($config['filename']);

					echo $parts['basename'];

				?></td>
				<td align="center"><?

					if(trim($config['state'])){

						echo $config['state'];

					}else{
						echo "Global";
					}

				?></td>
				<td valign="middle" class="row1" align="center"><?

					// SHOW STATUS
					echo ucfirst($row['status']).'<br />';


					// Progress
					echo '<img src="percent.php?percent='.$row['progress'].'" width="100" height="10" border="0" />';

				?></td>
				<td class="row0" align="left"><?

					// LOGS
					echo nl2br($row['result_log']);

					if($row['time_ended'] > 0){

						echo '<br />';
						$duration = 	$row['time_ended'] - $row['time_started'];
						echo 'Run time: '.rendertime( 	$duration );
					}


				?></td>
			</tr>
			</table><?

			break;

		case 'move_import_list':

			?><table border="0" width="100%" class="lb" cellspacing="0">
			<tr>
				<th class="row2">ID</th>
				<th class="row2" align="left">Source Import</th>
				<th class="row2" align="left">Destination</th>
				<th class="row2" align="left">Settings</th>
				<th class="row2" align="center">Progress</th>
				<th class="row2" align="left">Log</th>
			</tr>
			<tr>
				<td align="center"><?=$row['id']?></td>
				<td><?
					//$row['source_import_id']

					$source_arr = preg_split("/,/",$row['source_import_id'], -1, PREG_SPLIT_NO_EMPTY);

					foreach($source_arr as $source_id){

						$source = $_SESSION['dbapi']->list_tool_tasks->getSource($source_id);



						echo date("m/d/Y",$source['time']).'-'.ucfirst($source['phone_type']).'<br />'.$source['name'].'<br /><br />';

					}

				?></td>
				<td><?
					// DESTINATION

					//echo $config['destination_import_id'];


					$dest_arr = preg_split("/,/",$config['destination_import_id'], -1, PREG_SPLIT_NO_EMPTY);

					foreach($dest_arr as $dest_id){

						$source = $_SESSION['dbapi']->list_tool_tasks->getSource($dest_id);
						echo date("m/d/Y",$source['time']).'-'.ucfirst($source['phone_type']).'<br />'.$source['name'].'<br /><br />';

					}


				?></td>
				<td><?
					// SETTINGS

					echo "# of leads: ".((intval($config['num_leads']) <= 0)?"[All]":$config['num_leads'])."<br />\n";
					echo "Last Called Days: ".((intval($config['last_called_days']) <= 0)?"[All]":$config['last_called_days'])."<br />\n";

					echo "Timezone: ".((!trim($config['timezone']))?"[All]":$config['timezone'])."<br />\n";



				?></td>
				<td valign="middle" class="row1" align="center"><?

					// SHOW STATUS
					echo ucfirst($row['status']).'<br />';


					// Progress
					echo '<img src="percent.php?percent='.$row['progress'].'" width="100" height="10" border="0" />';

				?></td>
				<td class="row0" align="left"><?

					// LOGS
					echo nl2br($row['result_log']);

					if($row['time_ended'] > 0){

						echo '<br />';
						$duration = 	$row['time_ended'] - $row['time_started'];
						echo 'Run time: '.rendertime( 	$duration );
					}


				?></td>
			</tr>
			</table><?

			break;


		case 'purge_import':
		case 'delete_import':

			?><table border="0" width="100%" class="lb" cellspacing="0">
			<tr>
				<th class="row2">ID</th>
				<th class="row2" align="left">Source Import</th>
				<th class="row2" align="center">Progress</th>
				<th class="row2" align="left">Log</th>
			</tr>
			<tr>
				<td align="center" width="75"><?=$row['id']?></td>
				<td width="150" nowrap><?
					//$row['source_import_id']

					$source_arr = preg_split("/,/",$row['source_import_id'], -1, PREG_SPLIT_NO_EMPTY);

					foreach($source_arr as $source_id){

						$source = $_SESSION['dbapi']->list_tool_tasks->getSource($source_id);



						echo date("m/d/Y",$source['time']).'-'.ucfirst($source['phone_type']).'<br />'.$source['name'].'<br /><br />';

					}

				?></td>
				<td valign="middle" class="row1" align="center" width="110"><?

					// SHOW STATUS
					echo ucfirst($row['status']).'<br />';


					// Progress
					echo '<img src="percent.php?percent='.$row['progress'].'" width="100" height="10" border="0" />';

				?></td>
				<td class="row0" align="left"><?

					// LOGS
					echo nl2br($row['result_log']);

					if($row['time_ended'] > 0){

						echo '<br />';
						$duration = 	$row['time_ended'] - $row['time_started'];
						echo 'Run time: '.rendertime( 	$duration );
					}


				?></td>
			</tr>
			</table><?

			break;
		}


		?><center><?

			if($has_unstarted){

				?><input type="button" value="Cancel Task(s)" onclick="if(confirm('Are you sure you want to cancel this task?')){cancelTask(<?=$row['id']?>);}">
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?
			}



			?><input type="button" value="Refresh" onclick="window.parent.displayViewTaskDialog(<?=$row['id']?>)">

		</center><?


		if(!$is_finished && !$status_error){
			?><script>

				clearInterval(window.task_refresher);

				window.task_refresher = setTimeout('window.parent.displayViewTaskDialog(<?=$row['id']?>)', <?=$this->refresh_interval?>);

			</script><?
		}


	}




	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadTasks();return false;\">";

		return $var;
	}
}
