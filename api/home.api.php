<?



class API_Home{

	var $xml_parent_tagname = "Homes";
	var $xml_record_tagname = "Home";

	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";


	function handleAPI(){



		if(!checkAccess('homes')){


			$_SESSION['api']->errorOut('Access denied to Home screen');

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


			break;

		case 'view':





			break;
		case 'edit':

			break;

		default:
		case 'list':

			break;

		} // END SWITCH(action)
		
	} // END HANDLEAPI FUNCTION



}

