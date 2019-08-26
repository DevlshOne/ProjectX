<?	/***************************************************************
	 *	TILE NOTES - A person note tracking system in small/TILE format, for home screen integration
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['home_tile_notes'] = new HomeTileNotesClass;


class HomeTileNotesClass{

	public $table = "notes";
	
	
	
	function HomeTileNotesClass(){

		$this->handlePOST();
	}


	function handlePOST(){


	}

	function handleFLOW(){

		$this->renderTile();

	}

	function renderTile(){
		
		
		
	}
		
	


}
