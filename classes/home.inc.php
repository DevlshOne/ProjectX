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
	
	
	public $tile_width = 350; // TILE WIDTH IN PIXELS
	public $tile_height = 170; // TILE HEIGHT
	
	public $orig_prefs = null; // IDEA: USED TO TELL IF PREFERENCES HAVE SAVED SINCE LOADED
	
	
	// CONSTRUCTOR
	function HomeClass(){

		// LOAD HOME SCREEN PREFERENCES ON INIT
		$this->prefs = $_SESSION['dbapi']->user_prefs->getData($this->area_name, TRUE);
		
		// FIRST TIME INIT PREFS
		if(count($this->prefs['tiles']) <= 0){
			
			$this->prefs['tiles'] = array();
			
			$this->prefs['tiles'][] = array(
					
				'type' => 'my_notes',
					
			);
			
			$this->prefs['tiles'][] = array(
					
				'type' => 'user_count',
				'timeframe' => 'day'
					
			);
			
			$this->prefs['tiles'][] =array(
				'type' => 'sales_overview',
				'clusters' => array(23, 25),
				'user_groups' => array(), // ALL USER GROUPS
				'timeframe' => 'day'
			);
			
			$this->savePreferences();
			
		}
		
		$this->handlePOST();
	}




	function handlePOST(){


	}

	function handleFLOW(){

		switch($_REQUEST['sub_section']){
		default:
			
			$this->makeHome();
			
			break;
		case 'my_notes':
			
			$note_id = intval($_REQUEST['edit_note']);
			
			include_once("classes/home_tile_notes.inc.php");
			$_SESSION['home_tile_notes']->makeAdd($note_id);
			
			break;
		case 'user_count':
			
			
			include_once("classes/home_tile_user_count.inc.php");
			
			if(isset($_REQUEST['edit_config'])){
				
				$tidx = intval($_REQUEST['edit_config']);
				
				$_SESSION['home_tile_user_count']->makeConfigure($tidx);
				
			}else{
				
				echo "User Count Action not specified.";
			}
			
			break;
		}


	}

	function savePreferences(){
		
		return $_SESSION['dbapi']->user_prefs->updateByArray($this->area_name, $this->prefs);
		
	}
	
	
	function renderTile($tidx, $tile){
		
		
		switch($tile['type']){
		default:
			
			?><li class="homeScreenTile"  style="width:<?=$this->tile_width?>px">
				<table border="0">
				<tr>
					<td class="homeScreenTitle">
						Unknown/Unsupported Tile Type: '<?=htmlentities($tile['type'])?>'
					</td>
				</tr>
				</table>
			
			</li><?
			
			break;
		
			
		case 'my_notes':
			
			include_once("classes/home_tile_notes.inc.php");
			$_SESSION['home_tile_notes']->handleFLOW($tidx, $tile);
			
			break;
		case 'user_count':
			
			include_once("classes/home_tile_user_count.inc.php");
			$_SESSION['home_tile_user_count']->handleFLOW($tidx, $tile);
			
			break;
		}
		
	}
		
	

	function makeHome(){

		?><table style="width:100%;border:0">
		<tr>
			<td id="home_sortable"><?
			
			
				foreach($this->prefs['tiles'] as $tidx=>$tile){
					
					$this->renderTile($tidx, $tile);
					
				}
			
			
			
			?>
			
				<li id="homescr_tile_add" class="homeScreenTile" style="width:50px">
				
					<table border="0" width="100%" height="100%" class="hand" onclick="alert('Add new mini report here')">
					<tr>
						<td align="center">
							<img src="images/add_icon.png" width="40" border="0" />
						</td>
					</tr>
					</table>
				
				<li>
			</td>
		</tr>		
		</table>
		
		<script>
		
			$( function() {
			    $( "#home_sortable" ).sortable();
			    $( "#home_sortable" ).disableSelection();
			} );
			
		</script><?

	}


}
