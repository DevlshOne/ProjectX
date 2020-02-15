<?php
	/***************************************************************
	 *	Agent tracker - Used by the monitors to track agents strong and weak areas
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['agent_tracker'] = new AgentTracker;


class AgentTracker{

	var $table	= 'agent_tracker';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'agttkr_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'agentnextfrm';

	var $order_prepend = 'agent_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function AgentTracker(){


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


		if(isset($_REQUEST['add_tracker'])){

			$this->makeAdd($_REQUEST['add_tracker']);

		}else{
			$this->listEntrys();
		}

	}






	function listEntrys(){


		?><script>

			var tracker_delmsg = 'Are you sure you want to delete this users records?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var TrackersTableFormat = [
				['name','align_left'],
				['[get:voice_name:voice_id]','align_center'],
				['filename','align_center'],

				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getTrackersURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=agent_tracker&"+
								"mode=xml&"+

								's_id='+escape(frm.s_id.value)+"&"+
								's_name='+escape(frm.s_name.value)+"&"+
								's_filename='+escape(frm.s_filename.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var trackers_loading_flag = false;

			/**
			* Load the name data - make the ajax call, callback to the parse function
			*/
			function loadTrackers(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = trackers_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("AGENT TRACKER ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('trackers_loading_flag = true');
				}



				loadAjaxData(getTrackersURL(),'parseTrackers');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseTrackers(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('tracker',TrackersTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('trackers',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadTrackers()'
								);

				}else{

					hidePageSystem('trackers');

				}

				eval('trackers_loading_flag = false');
			}


			function handleTrackerListClick(id){

				displayAddTrackerDialog(id);

			}


			function displayAddTrackerDialog(id){

				var objname = 'dialog-modal-add-tracker';


				if(id > 0){
					$('#'+objname).dialog( "option", "title", 'Editing Agent Tracker' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Agent Tracker' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=agent_tracker&add_tracker="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');

			}

			function resetTrackerForm(frm){

				frm.s_id.value = '';
				frm.s_name.value = '';
				frm.s_filename.value='';

			}


			var trackersrchtog = false;

			function toggleTrackerSearch(){
				trackersrchtog = !trackersrchtog;
				ieDisplay('tracker_search_table', trackersrchtog);
			}

		</script>
		<div id="dialog-modal-add-tracker" title="Adding new Tracker" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadNames();return false">
			<input type="hidden" name="searching_tracker">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td>
						Agent Tracker
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Add" onclick="displayAddTrackerDialog(0)">
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Search" onclick="toggleTrackerSearch()">
					</td>
					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="trackers_prev_td" class="page_system_prev"></td>
							<td id="trackers_page_td" class="page_system_page"></td>
							<td id="trackers_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" width="100%" id="tracker_search_table" class="nod">
			<tr>
				<td rowspan="2"><font size="+1">SEARCH</font></td>
				<th class="row2">ID</th>
				<th class="row2">Name</th>
				<th class="row2">Filename</th>
				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_id" size="5" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>
				<td align="center"><input type="text" name="s_name" size="20" value="<?=htmlentities($_REQUEST['s_name'])?>"></td>
				<td align="center"><input type="text" name="s_filename" size="20" value="<?=htmlentities($_REQUEST['s_filename'])?>"></td>
				<td><input type="button" value="Reset" onclick="resetTrackerForm(this.form);resetPageSystem('<?=$this->index_name?>');loadTrackers();"></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="tracker_table">
			<tr>

				<th class="row2" align="left"><?=$this->getOrderLink('name')?>Name</a></th>
				<th class="row2"><?=$this->getOrderLink('voice_id')?>Voice</a></th>
				<th class="row2"><?=$this->getOrderLink('filename')?>Filename</a></th>
				<th class="row2">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-add-tracker").dialog({
				autoOpen: false,
				width: 400,
				height: 200,
				modal: false,
				draggable:true,
				resizable: false
			});

			$("#dialog-modal-add-tracker").dialog("widget").draggable("option","containment","#main-container");
			
			loadTrackers();

		</script><?

	}


	function makeAdd($id){

		$id=intval($id);


		if($id){

			//$row = $_SESSION['dbapi']->names->getByID($id);


		}

		?><script>

			function validateTrackerField(name,value,frm){

				//alert(name+","+value);


				switch(name){
				default:

					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;

				case 'user_id':


					if(!value)return false;

					return true;


					break;

				}
				return true;
			}



			function checkTrackerFrm(frm){


				var params = getFormValues(frm,'validateTrackerField');


				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'user_id':

						alert("Please select a user first.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;

					}

				// SUCCESS - POST AJAX TO SERVER
				}else{


					//alert("Form validated, posting");

					$.ajax({
						type: "POST",
						cache: false,
						url: 'api/api.php?get=agent_tracker&mode=xml&action=edit',
						data: params,
						error: function(){
							alert("Error saving user form. Please contact an admin.");
						},
						success: function(msg){

//alert(msg);

							var result = handleEditXML(msg);
							var res = result['result'];

							if(res <= 0){

								alert(result['message']);

								return;

							}


							loadTrackers();


							displayAddTrackerDialog(res);

							alert(result['message']);

						}


					});

				}

				return false;

			}




			// SET TITLEBAR
			$('#dialog-modal-add-tracker').dialog( "option", "title", '<?=($id)?'Editing Tracker #'.$id:'Adding new Tracker'?>' );



		</script>
		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkTrackerFrm(this); return false">
			<input type="hidden" id="adding_tracker" name="adding_tracker" value="<?=$id?>" >


		<table border="0" align="center">

		<tr>
			<th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
		</tr>
		</form>
		</table><?


	}



	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadTracker();return false;\">";

		return $var;
	}
}
