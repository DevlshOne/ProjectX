<?	/***************************************************************
	 *	LCR Admin tool - Handles the data for least cost routing
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['lcr'] = new LCRAdmin;


class LCRAdmin{

	var $table	= 'lcr';			## Classes main table to operate on
	var $orderby	= 'npanxx';		## Default Order field
	var $orderdir	= 'ASC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'lcr_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'lcrnextfrm';

	var $order_prepend = 'lcr_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function LCRAdmin(){


		## REQURES DB CONNECTION!



		$this->handlePOST();
	}


	function handlePOST(){

		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/names.api.php FOR POST HANDLING!
		// <3 <3 -Jon

	}

	function handleFLOW(){
		# Handle flow, based on query string


//		if(isset($_REQUEST['import_lcr'])){

			$this->makeImport();//$_REQUEST['import_lcr']);

//		}else{
//			$this->listEntrys();
//		}

	}


	function makeCarrierDD($name, $sel, $css_class){

		$res = $_SESSION['dbapi']->query(

			"SELECT DISTINCT(`globals_string`) AS globals_string ".
			"FROM asterisk.vicidial_server_carriers "

		);




		$out = '<select name="'.htmlentities($name).'" id="'.htmlentities($name).'" ';
		$out.= ($css_class)?' class="'.htmlentities($css_class).'" ':'';
		$out.= ' >';

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$arr = preg_split("/=/", $row['globals_string']);

			$carrier = trim($arr[1]);

			// SKIP BLANK
			if($carrier == '')continue;

			$out .= '<option value="'.htmlentities($carrier).'" ';
			$out .= ($sel == $carrier)?' SELECTED ':'';
			$out .= '>'.htmlentities($carrier).'</option>';

		}


		$out .= '</select>';

		return $out;
	}


	function makeImport(){


		?><script>

		function submitStep(){

		}

		</script>
		<form id="importform" method="POST" enctype="multipart/form-data" action="<?=stripurl()?>" >
			<input type="hidden" name="import_submitted" />
		<?

		switch($_REQUEST['current_step']){
		default:
		case 0:

			$this->makeStep1();

			break;
		case 1:

			$this->makeStep2();

			break;
		case 2:

			$this->makeStep3();

			break;
		}


		?></form><?

	}


	/**
	 * Step 1: Upload/Import settings screen
	 */
	function makeStep1(){

		?><input type="hidden" name="submitting_step" value="1" />

		<table border="0" width="100%">
		<tr>
			<th colspan="2" class="header">Step 1</th>
		</tr>
		<tr>
			<th>File:</th>
			<td><input type="file" name="upload_file" /></td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<input type="submit" value="Upload" />
			</td>
		</tr>
		</table><?

	}




	/**
	 * Step 2: The main GUI - grid style layout to see what it is parsing/detecting and allow changes/adjustments
	 */
	function makeStep2(){

		print_r($_POST);

	}



	/**
	 * Step 3: Finish page - shows results and information perhaps
	 */
	function makeStep3(){




	}
}
