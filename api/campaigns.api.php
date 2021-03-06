<?php



class API_Campaigns{

	var $xml_parent_tagname = "Campaigns";
	var $xml_record_tagname = "Campaign";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){


		if(!checkAccess('campaigns')){


			$_SESSION['api']->errorOut('Access denied to Campaigns');

			return;
		}




		switch($_REQUEST['action']){
		case 'delete':

			$id = intval($_REQUEST['id']);

			//$row = $_SESSION['dbapi']->campaigns->getByID($id);


			$_SESSION['dbapi']->campaigns->delete($id);

			logAction('delete', 'campaigns', $id, "");


			$_SESSION['api']->outputDeleteSuccess();


			break;

		case 'view':


			$id = intval($_REQUEST['id']);

			$row = $_SESSION['dbapi']->campaigns->getByID($id);




			## BUILD XML OUTPUT
			$out = "<".$this->xml_record_tagname." ";

			foreach($row as $key=>$val){


				$out .= $key.'="'.htmlentities($val).'" ';

			}

			$out .= " />\n";






			///$out .= "</".$this->xml_record_tagname.">";

			echo $out;



			break;
            case 'getRowByID':
                $id = intval($_REQUEST['campaign_id']);
                if ($id) {
                    $row = $_SESSION['dbapi']->campaigns->getByID($id);
                }
                $sql = "SELECT `id`, `code` FROM `campaign_parents` WHERE `deleted` = 0";
                $res = $_SESSION['dbapi']->query($sql, 1);
                $row['parent_dd'] = "<select class='form-control' name='parent_campaign_id' id='dd-parent_campaign_id'>";
                $row['parent_dd'] .= "<option value='0'>[None]</option>";
                if ($res) {
                    for ($x = 0; $rrow = mysqli_fetch_array($res); $x++) {
                        $row['parent_dd'] .= "<option value='" . $rrow['id'] . "'";
                        if ($rrow['id'] == $row['parent_campaign_id']) {
                            $row['parent_dd'] .= " selected";
                        }
                        $row['parent_dd'] .= ">" . $rrow['code'] . "</option>";
                    }
                }
                $row['parent_dd'] .= "</select>";
                $out = json_encode($row);
                echo $out;
                break;
                
                
                
        case 'edit':
        	
        	
				$id = intval($_POST['adding_campaign']);
				$name = trim($_POST['name']);
				
				
				
				unset($dat);
				$dat['name'] = $name;
				$dat['parent_campaign_id'] = $_POST['parent_campaign_id'];
				$dat['status'] = $_POST['status'];
				
				if($_POST['px_hidden']){
					
					$dat['px_hidden'] = ($_POST['px_hidden'] == 'yes')?'yes':'no';
					
				}
				
				$dat['vici_campaign_id'] = trim($_POST['vici_campaign_id']);
				
				$dat['manager_transfer'] = ($_POST['manager_transfer'] == 'yes')?'yes':'no';
				
				if(isset($_POST['warm_transfers'])){
					
					$dat['warm_transfers'] = ($_POST['warm_transfers'] == 'yes')?'yes':'no';
					
				}
				
				
				if(isset($_POST['verifier_mode'])){
					$dat['verifier_mode'] = trim($_POST['verifier_mode']);
				}
				
				if(isset($_POST['agent_mode'])){
					$dat['agent_mode'] = trim($_POST['agent_mode']);
				}
				
				
				$dat['type'] = trim($_POST['type']);
				$dat['variables'] = trim($_REQUEST['variables']);
				if($id){
					$dat['time_modified'] = time();
					$_SESSION['dbapi']->aedit($id,$dat,$_SESSION['dbapi']->campaigns->table);
					logAction('edit', 'campaigns', $id, "Name: $name");
				}else{
					$dat['time_created'] = time();
					$_SESSION['dbapi']->aadd($dat,$_SESSION['dbapi']->campaigns->table);
					$id = mysqli_insert_id($_SESSION['dbapi']->db);
					logAction('add', 'campaigns', $id, "Name: $name");
				}
				$_SESSION['api']->outputEditSuccess($id);
				break;
		default:
		case 'list':
			$dat = array();
			$totalcount = 0;
			$pagemode = false;



			$dat['status'] = 'active';




			## ID SEARCH
			if($_REQUEST['s_id']){

				$dat['id'] = intval($_REQUEST['s_id']);

			}

			## USERNAME SEARCH
			if($_REQUEST['s_name']){

				$dat['name'] = trim($_REQUEST['s_name']);

			}

			if($_REQUEST['s_status']){

				$dat['status'] = $_REQUEST['s_status'];

			}




			## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
			if(isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])){

				$pagemode = true;

				$cntdat = $dat;
				$cntdat['fields'] = 'COUNT(id)';
				list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->campaigns->getResults($cntdat));

				$dat['limit'] = array(
									"offset"=>intval($_REQUEST['index']),
									"count"=>intval($_REQUEST['pagesize'])
								);

			}


			## ORDER BY SYSTEM
			if($_REQUEST['orderby'] && $_REQUEST['orderdir']){
				$dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
			}






			$res = $_SESSION['dbapi']->campaigns->getResults($dat);



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






}