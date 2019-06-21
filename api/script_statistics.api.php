<?



class API_Script_Statistics{

	var $xml_parent_tagname = "Scripts";
	var $xml_record_tagname = "Script";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";





	function handleAPI(){


		if(!checkAccess('script_statistics')){


			$_SESSION['api']->errorOut('Access denied to Script Statistics');

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


		case 'reset_script':



			$id = intval($_REQUEST['script_id']);


			$_SESSION['dbapi']->execSQL("UPDATE `scripts` SET hit_counter=0, hit_last_reset=UNIX_TIMESTAMP() WHERE id='$id'");


			logAction('reset_script', 'script_statistics', $id, "");

			$_SESSION['api']->outputEditSuccess($id);


			break;

		case 'reset_all_scripts':


			$_SESSION['dbapi']->execSQL("UPDATE `scripts` SET hit_counter=0, hit_last_reset=UNIX_TIMESTAMP() WHERE 1");

			logAction('reset_all_scripts', 'script_statistics', -1, "");

			$_SESSION['api']->outputEditSuccess(0);




			break;


		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->scripts->getByID($id);




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



			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}


			if($_REQUEST['s_name']){

				$dat['name'] = trim($_REQUEST['s_name']);

			}


			if($_REQUEST['s_campaign_id']){

				$dat['campaign_id'] = intval($_REQUEST['s_campaign_id']);

			}

			if($_REQUEST['s_voice_id']){

				$dat['voice_id'] = intval($_REQUEST['s_voice_id']);

			}

			// AGENT USERNAME
			if($_REQUEST['s_key']){

				$dat['key'] = trim($_REQUEST['s_key']);

			}


			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->scripts->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->scripts->getResults($dat);



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

			case 'campaign_name':

				// vici_cluster_id

				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{

					//echo "ID#".$tmparr[2];

					$out_stack[$idx] = $_SESSION['dbapi']->lead_management->getCampaignName($tmparr[2]);
				}

				break;

			case 'voice_name':

				// vici_cluster_id

				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{

					//echo "ID#".$tmparr[2];

					$out_stack[$idx] = $_SESSION['dbapi']->voices->getName($tmparr[2]);
				}

				break;


			}## END SWITCH




		}



		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		//print_r($out_stack);
		echo $out;

	} ## END HANDLE SECONDARY AJAX


}

