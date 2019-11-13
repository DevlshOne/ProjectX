<?	/***************************************************************
	 *	Process Tracker Schedules GUI
	 ***************************************************************/

$_SESSION['process_tracker_schedules'] = new ProcessTrackerSchedules;


class ProcessTrackerSchedules{


	var $table		= 'process_tracker_schedules';		## Class main table to operate on


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


		echo "ayooo";


	}

	
	function makeAdd($id){

		$id=intval($id);


		if($id){

			$row = $_SESSION['dbapi']->process_tracker->getScheduleByID($id);

		}

		?>
		<form method="POST" id="pts_add_frm" action="<?=stripurl('')?>" autocomplete="off" onsubmit="">
			
			<input type="hidden" id="adding_schedule" name="adding_schedule" value="<?=$id?>">

			<table border="0" width="100%">
			<tr>
				<td colspan="2" class="ui-widget-header pad_left" height="40">Add Process Tracker Schedule</td>
			</tr>
			<tr>
				<th align="left" height="30">Enabled:</th>
				<td><input type="checkbox" name="enabled" value="yes" <?=($row['enabled'] == 'yes')?" CHECKED ":''?>></td>
			</tr>
			<tr>
				<th align="left" height="30">Schedule Name:</th>
				<td><input name="name" type="text" size="50" value="<?=htmlentities($row['schedule_name'])?>"></td>
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

}

