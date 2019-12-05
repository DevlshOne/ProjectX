<?php



class API_ProcessTrackerSchedules{

	var $xml_parent_tagname = "Schedules";
	var $xml_record_tagname = "Schedule";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){

		if(!checkAccess('process_tracker_schedules')){

			$_SESSION['api']->errorOut('Access denied to Process Tracker Schedules');

			return;
		}

		switch($_REQUEST['action']){
		case 'delete':

			$id = intval($_REQUEST['id']);

			$_SESSION['dbapi']->process_tracker->deleteSchedule($id);

			logAction('delete', 'process_tracker_schedules', $id, "");

			$_SESSION['api']->outputDeleteSuccess();


			break;			
		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->process_tracker->getScheduleByID($id);

			## BUILD XML OUTPUT
			$out = "<".$this->xml_record_tagname." ";

			foreach($row as $key=>$val){


				$out .= $key.'="'.htmlentities($val).'" ';

			}

			$out .= " />\n";


			echo $out;



			break;

		case 'edit':

			$id = intval($_POST['adding_schedule']);

			unset($dat);
			$dat['enabled']	 						= (isset($_POST['enabled']))?'yes':'no';
			$dat['schedule_name']					= trim($_POST['schedule_name']);
			$dat['script_process_code']				= trim($_POST['script_process_code']);
			$dat['script_frequency']				= trim($_POST['script_frequency']);

			switch ($dat['script_frequency']) {
				
				case 'hourly':

					# CONVERT START TIME TO 24HR TO STORE IN TABLE
					$time_start_24h = date("H:i", strtotime(trim($_POST['time_starthour']).":".trim($_POST['time_startmin'])." ".trim($_POST['time_starttimemode'])));

					$dat['time_start']				= $time_start_24h;
					$dat['time_end']				= 0;
					$dat['time_dayofweek']			= '';
					$dat['time_dayofmonth']			= 0;

					break;

				case 'daily':

					# CONVERT START AND END TIME TO 24HR TO STORE IN TABLE
					$time_start_24h = date("H:i", strtotime(trim($_POST['time_starthour']).":".trim($_POST['time_startmin'])." ".trim($_POST['time_starttimemode'])));
					$time_end_24h = date("H:i", strtotime(trim($_POST['time_endhour']).":".trim($_POST['time_endmin'])." ".trim($_POST['time_endtimemode'])));

					$dat['time_start']				= $time_start_24h;
					$dat['time_end']				= $time_end_24h;
					$dat['time_dayofweek']			= '';
					$dat['time_dayofmonth']			= 0;

					break;

				case 'weekly':

					# CONVERT START AND END TIME TO 24HR TO STORE IN TABLE
					$time_start_24h = date("H:i", strtotime(trim($_POST['time_starthour']).":".trim($_POST['time_startmin'])." ".trim($_POST['time_starttimemode'])));
					$time_end_24h = date("H:i", strtotime(trim($_POST['time_endhour']).":".trim($_POST['time_endmin'])." ".trim($_POST['time_endtimemode'])));

					$dat['time_start']				= $time_start_24h;
					$dat['time_end']				= $time_end_24h;
					$dat['time_dayofmonth']			= 0;

					# GET DAY OF WEEK FROM CHECKBOX AND STORE COMMA SEPERATED
					if(is_array($_POST['time_dayofweek'])){

						$dat['time_dayofweek']		= implode(",",$_POST['time_dayofweek']);

					}

					break;

				case 'monthly':

					# CONVERT START AND END TIME TO 24HR TO STORE IN TABLE
					$time_start_24h = date("H:i", strtotime(trim($_POST['time_starthour']).":".trim($_POST['time_startmin'])." ".trim($_POST['time_starttimemode'])));
					$time_end_24h = date("H:i", strtotime(trim($_POST['time_endhour']).":".trim($_POST['time_endmin'])." ".trim($_POST['time_endtimemode'])));

					$dat['time_start']				= $time_start_24h;
					$dat['time_end']				= $time_end_24h;
					$dat['time_dayofweek']			= '';

					# GET DAY OF MONTH FROM DROPDOWN
					$dat['time_dayofmonth']			= intval($_POST['time_dayofmonth']);

					break;

			}
			

			$dat['time_margin']						= intval($_POST['time_margin']);
			$dat['notification_email']				= trim($_POST['notification_email']);



			if($id){


				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->process_tracker->schedule_table);

				logAction('edit', 'process_tracker_schedules', $id, "");

			}else{


				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->process_tracker->schedule_table);

				$id = mysqli_insert_id($_SESSION['dbapi']->db);

				logAction('add', 'process_tracker_schedules', $id, "");

			}

			$_SESSION['api']->outputEditSuccess($id);

			break;			

		default:
		case 'list':

			$dat = array();
			$totalcount = 0;
			$pagemode = false;


			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}

			## ENABLED SEARCH
			if($_REQUEST['s_enabled']){

				$dat['enabled'] = trim($_REQUEST['s_enabled']);

			}

			## SCHEDULE NAME SEARCH
			if($_REQUEST['s_schedule_name']){

				$dat['schedule_name'] = trim($_REQUEST['s_schedule_name']);

			}

			## SCRIPT PROCESS CODE SEARCH
			if($_REQUEST['s_script_process_code']){

				$dat['script_process_code'] = trim($_REQUEST['s_script_process_code']);

			}

			## SCRIPT FREQUENCY SEARCH
			if($_REQUEST['s_script_frequency']){

				$dat['script_frequency'] = trim($_REQUEST['s_script_frequency']);

			}
		

			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->process_tracker->getScheduleResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}


			$res = $_SESSION['dbapi']->process_tracker->getScheduleResults($dat);



			## OUTPUT FORMAT TOGGLE
			switch($_SESSION['api']->mode){
			default:

			## GENERATE XML
			case 'xml':

				if($pagemode){

					$out = '<'.$this->xml_parent_tagname." totalcount=\"".intval($totalcount)."\">\n";
				}else{
					$out = '<'.$this->xml_parent_tagname.">\n";
				}

				$out .= $_SESSION['api']->renderResultSetXML($this->xml_record_tagname,$res);

				$out .= '</'.$this->xml_parent_tagname.">";
				break;

			## GENERATE JSON
			case 'json':

				$out = '['."\n";

				$out .= $_SESSION['api']->renderResultSetJSON($this->json_record_tagname,$res);

				$out .= ']'."\n";
				break;
			}


			## OUTPUT DATA!
			echo $out;

		}
	}



	function handleSecondaryAjax(){

		$out_stack = array();


		foreach($_REQUEST['special_stack'] as $idx => $data){

			$tmparr = preg_split("/:/",$data);

			switch($tmparr[1]){
			default:

				## ERROR
				$out_stack[$idx] = -1;

				break;

			case 'time_logged_out':
				
				$timeout = intval($tmparr[2]);
				
				// COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
				if($timeout <= 0){
					$out_stack[$idx] = '[Still logged in]';
				}else{
					
					$out_stack[$idx] = date("g:ia m/d/Y", $timeout);
					
				}
				
				break;

			case 'voice_name':

				// COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{

					$out_stack[$idx] = $_SESSION['dbapi']->voices->getName($tmparr[2]);

				}

				break;

			}## END SWITCH




		}

		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		echo $out;

	} ## END HANDLE SECONDARY AJAX


}

