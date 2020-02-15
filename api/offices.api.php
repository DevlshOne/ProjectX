<?php



class API_Offices{

	var $xml_parent_tagname = "Offices";
	var $xml_record_tagname = "Office";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){

		if(!checkAccess('offices')){

			$_SESSION['api']->errorOut('Access denied to Offices');

			return;
		}

		switch($_REQUEST['action']){
		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->offices->getByID($id);

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



			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}

			## NAME SEARCH
			if($_REQUEST['s_name']){

				$dat['name'] = trim($_REQUEST['s_name']);

			}


			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->offices->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}


			$res = $_SESSION['dbapi']->offices->getResults($dat);



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

			}## END SWITCH




		}

		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		echo $out;

	} ## END HANDLE SECONDARY AJAX


}

