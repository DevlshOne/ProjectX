<?	/***************************************************************
	 *	Voices - What do you THINK it does?? geeze...
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['voices'] = new Voices;


class Voices{

	var $table	= 'voices';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'voice_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'voicenextfrm';

	var $order_prepend = 'voice_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function Voices(){


		## REQURES DB CONNECTION!

		## INCLUDED FOR ITS DROPDOWN
		include_once("classes/campaigns.inc.php");


		$this->handlePOST();
	}




	function handlePOST(){

		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/voices.api.php FOR POST HANDLING!
		// <3 <3 -Jon

	}



	function handleFLOW(){

		if(!checkAccess('voices')){


			accessDenied("Voices");

			return;

		}else{


			# Handle flow, based on query string
			if(isset($_REQUEST['add_voice'])){

				$this->makeAdd($_REQUEST['add_voice']);

			}else{
				$this->listEntrys();
			}

		}

	}






	function listEntrys(){


		?><script>

			var voice_delmsg = 'Are you sure you want to delete this voice?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var VoicesTableFormat = [

				['name','align_left'],
				['actor_name','align_left'],
				['[get:language_name:language_id]','align_center'],
				['[get:campaign_name:campaign_id]','align_center'],
				['status','align_center'],

				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getVoicesURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=voices&"+
								"mode=xml&"+

								//'s_id='+escape(frm.s_id.value)+"&"+
								//'s_name='+escape(frm.s_name.value)+"&"+
								//'s_status='+escape(frm.s_status.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var voices_loading_flag = false;

			/**
			* Load the voice data - make the ajax call, callback to the parse function
			*/
			function loadVoices(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = voices_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("voices ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('voices_loading_flag = true');
				}



				loadAjaxData(getVoicesURL(),'parseVoices');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseVoices(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('voice',VoicesTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('voices',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadVoices()'
								);

				}else{

					hidePageSystem('voices');

				}

				eval('voices_loading_flag = false');
			}


			function handleVoiceListClick(id){

				displayAddVoiceDialog(id);

			}


			function displayAddVoiceDialog(voiceid){

				var objname = 'dialog-modal-add-voice';


				if(voiceid > 0){
					$('#'+objname).dialog( "option", "title", 'Editing Voice' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Voice' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=voices&add_voice="+voiceid+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			function resetVoiceForm(frm){

//				frm.s_id.value='';
//				frm.s_name.value = '';
//				frm.s_status.value='active';

			}




		</script>
		<div id="dialog-modal-add-voice" title="Adding new Voice" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadCampaigns();return false">
			<input type="hidden" name="searching_voices">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td>
						Voices
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Add" onclick="displayAddVoiceDialog(0)">
					</td>
					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="voices_prev_td" class="page_system_prev"></td>
							<td id="voices_page_td" class="page_system_page"></td>
							<td id="voices_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>
		<?/**
		<tr>
			<td colspan="2"><table border="0" width="100%">
			<tr>
				<td rowspan="2"><font size="+1">SEARCH</font></td>
				<th class="row2">ID</th>
				<th class="row2">Name</th>
				<th class="row2">Status</th>
				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_id" size="5" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>
				<td align="center"><input type="text" name="s_name" size="20" value="<?=htmlentities($_REQUEST['s_name'])?>"></td>
				<td align="center"><select name="s_status">
					<option value="active">Active</option>
					<option value="suspended">Suspended</option>
					<option value="deleted">Deleted</option>
				</select></td>
				<td><input type="button" value="Reset" onclick="resetCampaignForm(this.form);resetPageSystem('<?=$this->index_name?>');loadCampaigns();"></td>
			</tr>
			</table></td>
		</tr>**/

		?></form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="voice_table">
			<tr>
				<th class="row2" align="left"><?=$this->getOrderLink('name')?>Name</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('actor_name')?>Voice Actor</a></th>
				<th class="row2" width="150"><?=$this->getOrderLink('language_id')?>Language</a></th>
				<th class="row2"><?=$this->getOrderLink('campaign_id')?>Campaign</a></th>
				<th class="row2" width="100"><?=$this->getOrderLink('status')?>Status</a></th>
				<th class="row2">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-add-voice").dialog({
				autoOpen: false,
				width: 450,
				height: 250,
				modal: false,
				draggable:true,
				resizable: false
			});

			loadVoices();

		</script><?

	}


	function makeAdd($id){

		$id=intval($id);


		if($id){

			$row = $_SESSION['dbapi']->voices->getByID($id);


		}

		?><script>

			function validateVoiceField(name,value,frm){

				//alert(name+","+value);


				switch(name){
				default:

					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;

				case 'name':


					if(!value)return false;

					return true;


					break;
				}
				return true;
			}



			function checkVoiceFrm(frm){


				var params = getFormValues(frm,'validateVoiceField');


				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'name':

						alert("Please enter a name for this voice group.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;

					}

				// SUCCESS - POST AJAX TO SERVER
				}else{


					//alert("Form validated, posting");

					$.ajax({
						type: "POST",
						cache: false,
						url: 'api/api.php?get=voices&mode=xml&action=edit',
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


							loadVoices();


							displayAddVoiceDialog(res);

							alert(result['message']);

						}


					});

				}

				return false;

			}



			// SET TITLEBAR
			$('#dialog-modal-add-voice').dialog( "option", "title", '<?=($id)?'Editing Voice #'.$id.' - '.htmlentities($row['name']):'Adding new Voice'?>' );



		</script>
		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkVoiceFrm(this); return false">
			<input type="hidden" id="adding_voice" name="adding_voice" value="<?=$id?>" >


		<table border="0" align="center">
		<tr>
			<th align="left" height="30">Campaign</th>
			<td><?

				echo $_SESSION['campaigns']->makeDD('campaign_id',$row['campaign_id'],'',"",'',1);

			?></td>
		</tr>
		<tr>
			<th align="left" height="30">Language</th>
			<td><?

				echo $_SESSION['languages']->makeDropdown('language_id', $row['language_id'], '',
															'', // EXTRA HTML ATTRIBUTE TAGS
															"", // EXTRA WHERE SQL, FOR CUSTOMIZATIONZZ
															true, // USE ID INSTEAD OF SHORT NAME FOR VALUE
															true); // USE LONG NAME INSTEAD OF SHORT NAME FOR TEXT


			?></td>
		</tr>
		<tr>
			<th align="left" height="30">Name</th>
			<td><input name="name" type="text" size="50" value="<?=htmlentities($row['name'])?>"></td>
		</tr>
		<tr>
			<th align="left" height="30">Actor Name</th>
			<td><input name="actor_name" type="text" size="50" value="<?=htmlentities($row['actor_name'])?>"></td>
		</tr>
		<tr>
			<th align="left" height="30">Status</th>
			<td><select name="status">
				<option value="enabled">Enabled</option>
				<option value="suspended"<?=($row['status'] == 'suspended')?' SELECTED ':''?>>Suspended</option>
				<option value="deleted"<?=($row['status'] == 'deleted')?' SELECTED ':''?>>Deleted</option>
			</select></td>
		</tr>

		<tr>
			<th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
		</tr>
		</form>
		</table><?


	}



	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadVoices();return false;\">";

		return $var;
	}
}
