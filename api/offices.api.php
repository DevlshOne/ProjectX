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
		case 'delete':

			$id = intval($_REQUEST['id']);

			$_SESSION['dbapi']->offices->delete($id);

			logAction('delete', 'offices', $id, "");

			$_SESSION['api']->outputDeleteSuccess();


			break;
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

		case 'edit':

			$id = intval($_POST['adding_office']);

			$newcmpyname = filterName(trim($_POST['new_company_name']));
			
			
			// ADD A NEW COMPANY RECORD TOO
			if($newcmpyname){
				
				$dat = array(
						"status"	=> 'enabled',
						'name' 		=> $newcmpyname
				);
				
				$_SESSION['dbapi']->aadd($dat, 'companies');
				
				$company_id = mysqli_insert_id($_SESSION['dbapi']->db);
				
				
			}else{
				$company_id = intval($_POST['company_id']);
			}
			
			unset($dat);
			$dat['enabled']	 				= (isset($_POST['enabled']))?'yes':'no';
			$dat['company_id']				= $company_id;
			$dat['name']					= trim($_POST['name']);
			$dat['status']					= trim($_POST['status']);
			$dat['contact_info']			= trim($_POST['contact_info']);
			$dat['contact_number']			= trim($_POST['contact_number']);
			$dat['notes']					= trim($_POST['notes']);
			
			$dat['id'] = intval($_POST['office_id']);
			
			
			if($id){


				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->offices->table);

				logAction('edit', 'offices', $id, "");

			}else{


				
				
				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->offices->table);

				$id = mysqli_insert_id($_SESSION['dbapi']->db);

				logAction('add', 'offices', $id, "");

			}
			

			// IF THE ID CHANGED, KICK TO THE NEW ID
			if($id != $dat['id']){
				$id = $dat['id'];
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

			if($_REQUEST['s_status']){
				
				$dat['status'] = trim($_REQUEST['s_status']);
				
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


}

