<?



class API_Dispo_Log{

	var $xml_parent_tagname = "Dispos";
	var $xml_record_tagname = "Dispo";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){

		if(!checkAccess('dispo_log')){


			$_SESSION['api']->errorOut('Access denied to Dispo Log');

			return;
		}




//		if($_SESSION['user']['priv'] < 5){
//
//
//			$_SESSION['api']->errorOut('Access denied to non admins.');
//
//			return;
//		}

		switch($_REQUEST['action']){
		case 'delete':

			$id = intval($_REQUEST['id']);

			//$row = $_SESSION['dbapi']->campaigns->getByID($id);


			$_SESSION['dbapi']->dispo_log->delete($id);

			logAction('delete', 'dispo_log', $id, "");

			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->dispo_log->getByID($id);




			## BUILD XML OUTPUT
			$out = "<".$this->xml_record_tagname." ";

			foreach($row as $key=>$val){


				$out .= $key.'="'.htmlentities($val).'" ';

			}

			$out .= " />\n";






			///$out .= "</".$this->xml_record_tagname.">";

			echo $out;



			break;


		default:
		case 'list':



			$dat = array();
			$totalcount = 0;
			$pagemode = false;


			$dat['date_mode'] = trim($_REQUEST['s_date_mode']);


			if($dat['date_mode'] == 'daterange'){

				$dat['date1'] = $_REQUEST['s_date_year'].'-'.$_REQUEST['s_date_month'].'-'.$_REQUEST['s_date_day'];
				$dat['date2'] = $_REQUEST['s_date2_year'].'-'.$_REQUEST['s_date2_month'].'-'.$_REQUEST['s_date2_day'];

			}else{


				// RESERVED FOR TIME SEARCH STUFF
				if($_REQUEST['s_date_month']){
	//
	//
	//				$tmp0 = strtotime(]);
	//				$tmp1 = $tmp0 + 86399;

					//echo date("g:i:s m/d/Y", $tmp0).' ';
					//echo date("g:i:s m/d/Y", $tmp1).' ';

	//				$dat['time'] = array($tmp0, $tmp1);

					$dat['date'] = $_REQUEST['s_date_year'].'-'.$_REQUEST['s_date_month'].'-'.$_REQUEST['s_date_day'];

				}else{



					$dat['date'] = date("Y-m-d");


				}


			}




			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}



			if($_REQUEST['s_lead_id']){

				$dat['lead_id'] = intval($_REQUEST['s_lead_id']);

			}

			if($_REQUEST['s_lead_tracking_id']){

				$dat['lead_tracking_id'] = intval($_REQUEST['s_lead_tracking_id']);

			}


			## STATUS SEARCH
			if($_REQUEST['s_dispo']){


				$dat['dispo'] = trim($_REQUEST['s_dispo']);

			}else{

				//$dat['dispo'] = 'REVIEW';
			}




			if($_REQUEST['s_result']){


				$dat['result'] = trim($_REQUEST['s_result']);

			}

			if($_REQUEST['s_username']){


				$dat['agent_username'] = trim($_REQUEST['s_username']);

			}




			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->dispo_log->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->dispo_log->getResults($dat);



	## OUTPUT FORMAT TOGGLE
			switch($_SESSION['api']->mode){
			default:
			case 'xml':


		## GENERATE XML

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

		//print_r($_REQUEST);

		foreach($_REQUEST['special_stack'] as $idx => $data){

			$tmparr = preg_split("/:/",$data);

			//print_r($tmparr);


			switch($tmparr[1]){
			default:

				## ERROR
				$out_stack[$idx] = -1;

				break;
//			case 'voice_name':
//
//				// COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
//				if($tmparr[2] <= 0){
//					$out_stack[$idx] = '-';
//				}else{
//
//					//echo "ID#".$tmparr[2];
//
//					$out_stack[$idx] = $_SESSION['dbapi']->voices->getName($tmparr[2]);
//				}
//
//				break;

			}## END SWITCH




		}



		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		//print_r($out_stack);
		echo $out;

	} ## END HANDLE SECONDARY AJAX


}

