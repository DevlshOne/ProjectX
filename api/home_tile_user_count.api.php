<?



class API_HomeTileUserCount{

	var $area_name = "user_count";

	function handleAPI(){


		switch($_REQUEST['action']){

		case 'edit_config':
			

			$tile_idx = intval($_REQUEST['tile_idx']);
			
// 			print_r($_SESSION['home']->prefs);
			
			if(!$_SESSION['home']->prefs['tiles'][$tile_idx] || $_SESSION['home']->prefs['tiles'][$tile_idx]['type'] != $this->area_name){
				
				$_SESSION['api']->errorOut('Something changed while we were editing. Please refresh and try again.');
				
				return;
			}
			
			$_SESSION['home']->prefs['tiles'][$tile_idx]['timeframe'] = filterAZ09($_REQUEST['timeframe'], 8);
			$_SESSION['home']->savePreferences();

			
			$_SESSION['api']->outputEditSuccess($tile_idx);
			
			
			break;
		default:
			
			$_SESSION['api']->errorOut('Action not specified');
			
			return;
		}
	}



}

