<?
class API_PhoneLookup{
	var $xml_parent_tagname = "Names";
	var $xml_record_tagname = "Name";
	var $json_parent_tagname = "ResultSet";
	var $json_record_tagname = "Result";
	function handleAPI(){
		if(!checkAccess('names')){
			$_SESSION['api']->errorOut('Access denied to Names');
			return;
		}
		switch($_REQUEST['action']){
		case 'deep':
			$phone_num = intval($_POST['phone_number']);
			break;
		default:
		    break;
		}
	}
}

