<?php



class API_UserGroups{

	var $xml_parent_tagname = "Usergroups";
	var $xml_record_tagname = "Usergroup";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";



	function syncGroupToVici($group_id){


		$row = $_SESSION['dbapi']->user_groups->getByID($group_id);


		// connect to the approperate VICI cluster
		$dbidx = getClusterIndex($row['vici_cluster_id']);

		connectViciDB($dbidx);

		// LOOK UP GROUP BY NAME

		list($test) = queryROW("SELECT user_group FROM vicidial_user_groups WHERE user_group LIKE '".mysqli_real_escape_string($_SESSION['db'],$row['user_group'])."'");


		$dat = array();

		$dat['group_name']	= $row['name'];
		$dat['office']		= $row['office'];

		// ADD IF NOT EXISTS
		if(!$test){


			$dat['user_group'] = $row['user_group'];

			aadd($dat, 'vicidial_user_groups');





		}else{
		// EDIT IF IT DOES EXIST ALREADY


			aeditByField('user_group',$row['user_group'],$dat,'vicidial_user_groups');

		}

		
		connectPXDB();
		
		// SYNC MASTER GROUP TOO?
		list($master_id) = queryROW("SELECT `id` FROM `user_groups_master` WHERE `user_group`='".mysqli_real_escape_string($_SESSION['db'],$row['user_group'])."' ");
		
		if($master_id > 0){
			
			
			
			$dat = array();
			$dat['group_name']	= $row['name'];
			$dat['office']		= $row['office'];
			
			
			list($company_id) = queryROW("SELECT company_id FROM `offices` WHERE id='".mysqli_real_escape_string($_SESSION['db'],$dat['office'])."'");
			if($company_id > 0){
				
				$dat['company_id'] = $company_id;
			}
			
			
			aedit($master_id, $dat,'user_groups_master');
			
		}


	}




	function handleAPI(){


		if(!checkAccess('users')){


			$_SESSION['api']->errorOut('Access denied to User Groups');

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

			// DELETE FROM VICI, THEN FROM PX, USING THE ACTION PACKED, EDGE OF YOUR SEAT, ALL-IN-WONDER FUNCTION, delete()
			$_SESSION['dbapi']->user_groups->delete($id);


			logAction('delete', 'user_groups', $id, "");

			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->user_groups->getByID($id);




			## BUILD XML OUTPUT
			$out = "<".$this->xml_record_tagname." ";

			foreach($row as $key=>$val){

				if($key == 'password')continue;

				$out .= $key.'="'.htmlentities($val).'" ';

			}

			$out .= " >\n";






			$out .= "</".$this->xml_record_tagname.">";

			echo $out;



			break;


		case 'edit':

			$id = intval($_POST['adding_user_group']);


			unset($dat);

			$dat['vici_cluster_id'] = intval($_POST['vici_cluster_id']);
			$dat['name'] = trim($_POST['name']);
			$dat['office'] = trim($_POST['office']);



			if($id){



				$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->user_groups->table);


				logAction('edit', 'user_groups', $id, "");


			}else{

				$dat['user_group'] = trim($_POST['user_group']);

				$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->user_groups->table);
				$id = mysqli_insert_id($_SESSION['dbapi']->db);

				logAction('add', 'user_groups', $id, "");

			}


			$this->syncGroupToVici($id);



			$_SESSION['api']->outputEditSuccess($id);



			break;

		default:
		case 'list':



			$dat = array();
			$totalcount = 0;
			$pagemode = false;



			## AGENT NAME SEARCH
			if($_REQUEST['s_name']){

				$dat['name'] = trim($_REQUEST['s_name']);

			}


			## GROUP NAME
			if($_REQUEST['s_group_name']){

				$dat['group_name'] = trim($_REQUEST['s_group_name']);

			}


			## CLUSTER ID
			if($_REQUEST['s_cluster_id']){

				$dat['vici_cluster_id'] = trim($_REQUEST['s_cluster_id']);

			}

			##





			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->user_groups->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->user_groups->getResults($dat);



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

			case 'cluster_name':

				// vici_cluster_id

				if($tmparr[2] <= 0){
					$out_stack[$idx] = '-';
				}else{

					//echo "ID#".$tmparr[2];

					$out_stack[$idx] = getClusterName($tmparr[2]);
				}
//



				break;
			}## END SWITCH




		}



		$out = $_SESSION['api']->renderSecondaryAjaxXML('Data',$out_stack);

		//print_r($out_stack);
		echo $out;

	} ## END HANDLE SECONDARY AJAX




}


