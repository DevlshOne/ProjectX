<?
class API_PhoneLookup{
	function handleAPI(){
		switch($_REQUEST['action']){
		case 'deep':
			$phone_num = intval($_POST['phone_number']);
            $px_lookup = $_SESSION['dbapi']->phone_lookup->deepSearchPhone($phone_num);
            echo $px_lookup;
            break;
		default:
		    break;
		}
	}
}

