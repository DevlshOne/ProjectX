<?	/***************************************************************
	 *	Home class - handles the home interface
	 *	Written By: Jonathan Will
	 *
	 *
	 *
	 *
	 *
	 *
	 *
	 *Preferences Array Structure
	 *
	 *"tiles"	This object will hold an array of the tiles that are to be displayed, along with the settings for each tile
	 *
	 *'tiles' => array(
	 *				'type'=>'graph_users_per_hour',
	 *				'timeframe'=>'week',
	 *				'size' => 'medium'
	 *			),
	 *			array(
	 *				'type' => 'my_notes',
	 *			),
	 *			array(
	 *				'type' => 'sales_overview',
	 *				'clusters' => array(23, 25),
	 *				'user_groups' => array(), // ALL USER GROUPS
	 *				'timeframe' => 'day'
	 *			)
	 *
	 *
	 *
	 *
	 ***************************************************************/

$_SESSION['home'] = new HomeClass;


class HomeClass{

	public $area_name = "home_screen";
	
	public $prefs = null;
	
	
	
	
	
	public $orig_prefs = null; // IDEA: USED TO TELL IF PREFERENCES HAVE SAVED SINCE LOADED
	
	
	
	function HomeClass(){

		// LOAD HOME SCREEN PREFERENCES ON INIT
		$this->prefs = $_SESSION['dbapi']->user_prefs->getData($this->area_name);
		
		
		$this->handlePOST();
	}




	function handlePOST(){


	}

	function handleFLOW(){

		$this->makeHome();

	}

	function savePreferences(){
		
		return $_SESSION['dbapi']->user_prefs->updateByArray($this->area_name, $this->prefs);
		
	}
	
	
	function renderTile($type){
		
		
		switch($type){
		default:
			break;
		
		}
		
	}
		
	

	function makeHome(){

		?><table style="width:100%;border:0">
		<tr>
			<th height="150" class="lb">[Small graph could go here]</th>
			<th class="lb">[Small graph could go here]</th>
		</tr>
		<tr>
			<th height="150" class="lb">[Small graph could go here]</th>
			<th class="lb">[Small graph could go here]</th>
		</tr>

		</table><?

	}


}
