<?
class API_PhoneLookup{
	function handleAPI(){
		switch($_REQUEST['action']){
		case 'deep':
			$phone_num = intval($_REQUEST['phone_number']);
            echo $_SESSION['dbapi']->phone_lookup->deepSearchPhone($phone_num);
            break;
		default:
		    break;
		}
	}
}

