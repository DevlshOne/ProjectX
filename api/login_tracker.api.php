<?php



class API_LoginTracker{

	var $xml_parent_tagname = "Logins";
	var $xml_record_tagname = "Login";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){

		if(!checkAccess('login_tracker')){

			$_SESSION['api']->errorOut('Access denied to Login Tracker');

			return;
		}

		switch($_REQUEST['action']){
		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->login_tracker->getByID($id);

			## BUILD XML OUTPUT
			$out = "<".$this->xml_record_tagname." ";

			foreach($row as $key=>$val){


				$out .= $key.'="'.htmlentities($val).'" ';

			}

			$out .= " />\n";


			echo $out;



			break;

		default:
		case 'list':

			$dat = array();
			$totalcount = 0;
			$pagemode = false;


			## CHECK IF WE'RE DOING A CUSTOM SEARCH FROM THE DATA AGGR TABLE
			if($_REQUEST['data_aggr_search']=='true'){

				if($_REQUEST['data_aggr_range']!='false'){

					switch($_REQUEST['data_aggr_range']){

						case '1h':

							$tmp0 = time();
							$tmp1 = $tmp0 - 3600;

							$dat['time'] = array($tmp1, $tmp0);

							break;

						case '24h':

							$tmp0 = time();
							$tmp1 = $tmp0 - 86400;

							$dat['time'] = array($tmp1, $tmp0);

							break;

						case '7d':

							$tmp0 = time();
							$tmp1 = $tmp0 - 604800;

							$dat['time'] = array($tmp1, $tmp0);

							break;

					}

				}


			}elseif($_REQUEST['s_date_mode']){

					if($_REQUEST['s_date_mode'] == 'daterange'){

						$tmp0 = strtotime($_REQUEST['s_date_month'].'/'.$_REQUEST['s_date_day'].'/'.$_REQUEST['s_date_year']);
						$tmp1 = strtotime($_REQUEST['s_date2_month'].'/'.$_REQUEST['s_date2_day'].'/'.$_REQUEST['s_date2_year']);


						$tmp0 = mktime(0,0,0, date("m", $tmp0), date("d", $tmp0), date("Y", $tmp0));
						$tmp1 = mktime(23,59,59, date("m", $tmp1), date("d", $tmp1), date("Y", $tmp1));

					}else{

						$tmp0 = strtotime($_REQUEST['s_date_month'].'/'.$_REQUEST['s_date_day'].'/'.$_REQUEST['s_date_year']);
						$tmp0 = mktime(0,0,0, date("m", $tmp0), date("d", $tmp0), date("Y", $tmp0));

						$tmp1 = $tmp0 + 86399;


					}

					$dat['time'] = array($tmp0, $tmp1);

			}


			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}

			## USERNAME SEARCH
			if($_REQUEST['s_username']){

				$dat['username'] = trim($_REQUEST['s_username']);

			}

			## RESULT SEARCH
			if($_REQUEST['s_result']){

				$dat['result'] = trim($_REQUEST['s_result']);

			}

			## SECTION SEARCH
			if($_REQUEST['s_section']){

				$dat['section'] = trim($_REQUEST['s_section']);

			}
			
			## IP SEARCH
			if($_REQUEST['s_ip']){

				$dat['ip'] = trim($_REQUEST['s_ip']);

			}		
			
			## BROWSER SEARCH
			if($_REQUEST['s_browser']){

				$dat['browser'] = trim($_REQUEST['s_browser']);

			}	

			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->login_tracker->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}


			$res = $_SESSION['dbapi']->login_tracker->getResults($dat);



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

