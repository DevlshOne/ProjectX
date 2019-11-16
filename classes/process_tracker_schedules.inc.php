<?	/***************************************************************
	 *	Process Tracker Schedules GUI
	 ***************************************************************/

$_SESSION['process_tracker_schedules'] = new ProcessTrackerSchedules;


class ProcessTrackerSchedules{


	var $table		= 'process_tracker_schedules';		## Class main table to operate on
	var $orderby	= 'script_process_code';							## Default Order field
	var $orderdir	= 'DESC';							## Default order direction


	## Page  Configuration
	var $pagesize	= 20;						## Adjusts how many items will appear on each page
	var $index		= 0;						## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'pts_list';		## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name 	= 'ptsnextfrm';

	var $order_prepend = 'pts_';		## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING


	function ProcessTrackerSchedule(){

		include_once("db.inc.php");


		$this->handlePOST();

	}



	function handlePOST(){

	}

	function handleFLOW(){


		if(!checkAccess('process_tracker_schedules')){


			accessDenied("Process Tracker Schedules GUI");

			return;

		}else{

			# Handle flow, based on query string
			if(isset($_REQUEST['add_schedule'])){

				$this->makeAdd($_REQUEST['add_schedule']);

			}else{

				$this->listEntrys();

			}

		}

	}

	function makeProcessCodeDD($name, $sel, $class, $onchange, $blank_option = 1){

		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out.= ($class)?' class="'.$class.'" ':'';
		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= '>';

		if($blank_option){
			$out .= '<option value="" '.(($sel == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"").'</option>';
		}

		$res = query("SELECT DISTINCT(`process_code`) AS `process_code` FROM `process_tracker` ORDER BY `process_code` ASC", 1);


		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


			$out .= '<option value="'.htmlentities($row['process_code']).'" ';
			$out .= ($sel == $row['process_code'])?' SELECTED ':'';
			$out .= '>'.htmlentities($row['process_code']).'</option>';


		}

		$out .= '</select>';
		return $out;
	}


	function listEntrys(){


		?>
		<script>

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;


			var PTSTableFormat = [
				['id','align_left'],
				['enabled','align_left'],
				['schedule_name','align_left'],
				['script_process_code','align_left'],
				['[time:time_start]','align_left']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getPTSURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=process_tracker_schedules&"+
								"mode=xml&"+

								's_id='+escape(frm.s_id.value)+"&"+
								's_enabled='+escape(frm.s_enabled.value)+"&"+
								's_schedule_name='+escape(frm.s_schedule_name.value)+"&"+
								's_script_process_code='+escape(frm.s_script_process_code.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var pts_loading_flag = false;

			/**
			* Load the Process Tracker Schedules data - make the ajax call, callback to the parse function
			*/
			function loadPTS(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = pts_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					return;

				}else{

					eval('pts_loading_flag = true');
				}

				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				$('#total_count_div').html('<img src="images/ajax-loader.gif" border="0">');

				loadAjaxData(getPTSURL(),'parsePTS');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parsePTS(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('schedule',PTSTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('schedules',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadPTS()'
								);

				}else{

					hidePageSystem('schedules');

				}

				eval('pts_loading_flag = false');
			}


			function handleScheduleListClick(id){

				displayViewScheduleDialog(id);

			}


			function displayViewScheduleDialog(id){

				var objname = 'dialog-modal-view-schedule';


				if(id > 0){
					$('#'+objname).dialog( "option", "title", 'Editing Schedule' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Schedule' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=process_tracker_schedules&add_schedule="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');

				$('#'+objname).dialog('option', 'height', '350');
			}

			function resetPTSForm(frm){

				// SET FORM VALUES TO BLANK
				frm.s_id.value = '';
				frm.s_enabled.value = '';
				frm.s_schedule_name.value='';
				frm.s_script_process_code.value='';
			

			
			}



		</script>
		<div id="dialog-modal-view-schedule" title="Viewing Schedule" class="nod">
		<?

		?>
		</div><?



		?>
		<form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadPTS();return false">
		
		<input type="hidden" name="searching_schedule">

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">
				<table border="0" width="100%" >
					<tr>
						<td width="500">
						Process Tracker Schedules
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Add" onclick="displayViewScheduleDialog(0)">
						</td>

						<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadPTS();return false">
							<option value="20">20</option>
							<option value="50">50</option>
							<option value="100">100</option>
							<option value="500">500</option>
						</select></td>

						<td align="right"><?
							/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
							<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
								<tr>
									<td id="schedules_prev_td" class="page_system_prev"></td>
									<td id="schedules_page_td" class="page_system_page"></td>
									<td id="schedules_next_td" class="page_system_next"></td>
								</tr>
							</table></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<table border="0" width="100%" id="schedule_search_table">
					<tr>
						<td rowspan="2" width="100" align="center" style="border-right:1px solid #000">
							<span id="total_count_div"></span>
						</td>
						<th class="row2">ID</th>
						<th class="row2">Enabled</th>
						<th class="row2">Schedule Name</th>
						<th class="row2">Script Process Code</th>
						<td><input type="submit" value="Search" name="the_Search_button"></td>
					</tr>
					<tr>
						<td align="center"><input type="text" name="s_id" size="5" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>
						<td align="center"><select name="s_enabled" id="s_enabled">
								<option value="">[All]</option>

								<option value="yes">yes</option>
								<option value="no">no</option>
							</select></td>
						<td align="center"><input type="text" name="s_schedule_name" size="15" value="<?=htmlentities($_REQUEST['s_schedule_name'])?>"></td>
						<td align="center">

							<?

								echo $this->makeProcessCodeDD('s_script_process_code',$_REQUEST['s_script_process_code'],'','');

							?>

						
						<td><input type="button" value="Reset" onclick="resetPTSForm(this.form);resetPageSystem('<?=$this->index_name?>');loadPTS();"></td>
					</tr>
				</table>
			</td>
		</tr>
		</form>
		<tr>
			<td colspan="2">
				<table border="0" width="100%" id="schedule_table">
					<tr>
						<th class="row2" align="left"><?=$this->getOrderLink('id')?>ID</a></th>
						<th class="row2" align="left"><?=$this->getOrderLink('enabled')?>Enabled</a></th>
						<th class="row2" align="left"><?=$this->getOrderLink('schedule_name')?>Schedule Name</a></th>
						<th class="row2" align="left"><?=$this->getOrderLink('script_process_code')?>Script Process Code</a></th>
						<th class="row2" align="left"><?=$this->getOrderLink('time_start')?>Time Start</a></th>
					</tr>
				</table>
			</td>
		</tr>

		</table>

		<script>

			$("#dialog-modal-view-schedule").dialog({
				autoOpen: false,
				width: 500,
				height: 200,
				modal: false,
				draggable:true,
				resizable: false
			});



			loadPTS();

		</script><?


	}

	
	function makeAdd($id){

		$id=intval($id);


		if($id){

			$row = $_SESSION['dbapi']->process_tracker->getScheduleByID($id);

		}

		?><script>

			function validateScheduleField(name,value,frm){

				switch(name){
				default:

					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;

				case 'schedule_name':

					if(!value)return false;

					return true;

					break;
					
				case 'script_process_code':

					if(!value)return false;

					return true;

					break;
					
				case 'script_frequency':

					if(!value)return false;

					return true;

					break;

				}

				return true;

			}



			function checkScheduleFrm(frm){

				var params = getFormValues(frm,'validateScheduleField');

				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'schedule_name':

						alert("Please enter a name for this schedule.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;
						
					case 'script_process_code':

						alert("Please select a script process code for this schedule.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;
						
					case 'script_frequency':

						alert("Please select a script frequency for this schedule.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;					

					}

				}else{


					$.ajax({
						type: "POST",
						cache: false,
						url: 'api/api.php?get=process_tracker_schedules&mode=xml&action=edit',
						data: params,
						error: function(){
							alert("Error saving process tracker schedule form. Please contact an admin.");
						},
						success: function(msg){

							var result = handleEditXML(msg);
							var res = result['result'];

							if(res <= 0){

								alert(result['message']);

								return;

							}


							loadPTS();


							displayViewScheduleDialog(res);

							alert(result['message']);

						}


					});

				}

				return false;

			}


			function doScheduleFormSubmit(params){

				$.ajax({
					type: "POST",
					cache: false,
					url: 'api/api.php?get=process_tracker_schedules&mode=xml&action=edit',
					data: params,
					error: function(){
						alert("Error saving process tracker schedule form. Please contact an admin.");
					},

					success: function(msg){


						var result = handleEditXML(msg);
						var res = result['result'];

						if(res <= 0){

							alert(result['message']);

							return;

						}


						loadPTS();


						displayViewScheduleDialog(res);

					}

				});

			}

			// SET TITLEBAR
			$('#dialog-modal-view-schedule').dialog( "option", "title", '<?=($id)?'Editing Schedule #'.$id.' - '.htmlentities($row['schedule_name']):'Adding new Schedule'?>' );

		</script>

		<form method="POST" id="pts_add_frm" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkScheduleFrm(this); return false">
			
			<input type="hidden" id="adding_schedule" name="adding_schedule" value="<?=$id?>">

			<table border="0" width="100%">
			<tr>
				<th align="left" height="30">Enabled:</th>
				<td><input type="checkbox" name="enabled" value="yes" <?=($row['enabled'] == 'yes')?" CHECKED ":''?>></td>
			</tr>
			<tr>
				<th align="left" height="30">Schedule Name:</th>
				<td><input name="schedule_name" type="text" size="50" value="<?=htmlentities($row['schedule_name'])?>"></td>
			</tr>
			<tr>
				<th align="left" height="30">Script Process Code:</th>
				<td><?

					echo $this->makeProcessCodeDD('script_process_code',$row['script_process_code'],'','');

				?></td>
			</tr>
			<tr>
				<th align="left" height="30">Script Frequency:</th>
				<td><input type="radio" name="script_frequency" value="hourly" <?=($row['script_frequency'] == 'hourly')?" CHECKED ":''?>>Hourly <input type="radio" name="script_frequency" value="daily" <?=($row['script_frequency'] == 'daily')?" CHECKED ":''?>>Daily <input type="radio" name="script_frequency" value="weekly" <?=($row['script_frequency'] == 'weekly')?" CHECKED ":''?>>Weekly <input type="radio" name="script_frequency" value="monthly" <?=($row['script_frequency'] == 'monthly')?" CHECKED ":''?>>Monthly</td>
			</tr>
			<tr>
				<th align="left" height="30">Start Time:</th>
				<td><?

						echo makeTimebar("time_start",2,null,false,time()," onchange=\"\" ");

				?></td>
			</tr>
			<tr>
				<th align="left" height="30">End Time:</th>
				<td><?

						echo makeTimebar("time_end",2,null,false,time()+3600," onchange=\"\" ");

				?></td>
			</tr>
			<tr>
				<th align="left" height="30">Time Margin:</th>
				<td><select name="time_margin">
					<option value="0">0 minutes</option>
					<option value="5">+5 minutes</option>
					<option value="10">+10 minutes</option>
					<option value="15">+15 minutes</option>
					<option value="30">+30 minutes</option>
				</select></td>
			</tr>
			<tr>
				<th align="left" height="30">Alert Email:</th>
				<td><input name="notification_email" type="text" size="50" value="<?=htmlentities($row['notification_email'])?>"></td>
			</tr>
			<tr>
				<th colspan="2" align="center" height="50">

					<input type="submit" value="Save Changes">

				</th>	
			</tr>
			</table>
		
		</form>
		
		<?


	}

	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadPTS();return false;\">";

		return $var;
	}

}

