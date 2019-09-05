<?



class API_Ringing_Calls{

	var $xml_parent_tagname = "Rings";
	var $xml_record_tagname = "Ring";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){


		if(!checkAccess('ringing_calls')){


			$_SESSION['api']->errorOut('Access denied to Ring Reports');

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


			$_SESSION['dbapi']->ringing_calls->delete($id);

			logAction('delete', 'ringing_calls', $id, "");

			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->ringing_calls->getByID($id);




			## BUILD XML OUTPUT
			$out = "<".$this->xml_record_tagname." ";

			foreach($row as $key=>$val){


				$out .= $key.'="'.htmlentities($val).'" ';

			}

			$out .= " />\n";






			///$out .= "</".$this->xml_record_tagname.">";

			echo $out;



			break;
		case 'edit':

			$id = intval($_POST['adding_ringing_call']);


			unset($dat);


			$dat['status'] = trim($_POST['status']);


			if($id){

				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->ringing_calls->table);


				logAction('edit', 'ringing_calls', $id, "Status=".$dat['status']);

			}

//echo $id.' '.$dat['status']."\n";exit;


			$_SESSION['api']->outputEditSuccess($id);



			break;

		default:
		case 'list':



			$dat = array();
			$totalcount = 0;
			$pagemode = false;


			// RESERVED FOR TIME SEARCH STUFF
			if($_REQUEST['s_date']){


				$tmp0 = strtotime($_REQUEST['s_date']);
				$tmp1 = $tmp0 + 86399;

				//echo date("g:i:s m/d/Y", $tmp0).' ';
				//echo date("g:i:s m/d/Y", $tmp1).' ';

				$dat['time'] = array($tmp0, $tmp1);

			// DEFAULT TO DAILY
			}else{



				$dat['time'] = array(mktime(0,0,0), mktime(23,59,59));


			}


			## STATUS SEARCH
			if($_REQUEST['s_status']){

				if($_REQUEST['s_status'] == -1){
					// SHOW ALL STATUS
					unset($dat['status']);

				}else{
					$dat['status'] = trim($_REQUEST['s_status']);
				}

			/// DEFAULT STATUS IS "review"
			}else{

				$dat['status'] = 'review';
			}



			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}

			if($_REQUEST['s_uniqueid']){

				$dat['uniqueid'] = intval($_REQUEST['s_uniqueid']);

			}

			if($_REQUEST['s_lead_id']){

				$dat['lead_id'] = intval($_REQUEST['s_lead_id']);

			}




			if($_REQUEST['s_phone']){

				$dat['phone_number'] = trim($_REQUEST['s_phone']);

			}


			if($_REQUEST['s_carrier']){

				$dat['carrier_prefix'] = trim($_REQUEST['s_carrier']);

			}


			if($_REQUEST['s_cluster_id']){

				$dat['cluster_id'] = trim($_REQUEST['s_cluster_id']);

			}

			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->ringing_calls->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->ringing_calls->getResults($dat);



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

