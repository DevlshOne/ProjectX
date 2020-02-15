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
                var <?=$this->order_prepend?>pagesize = $('#<?=$this->order_prepend?>pagesizeDD').val();
				return 'api/api.php'+
								"?get=voices&"+
								"mode=xml&"+
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
			}

			function resetVoiceForm(frm){
			}
		</script>
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadVoices();return false">
                <input type="hidden" name="searching_voices">
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Voices</h4>
                    <button type="button" value="Add" title="Add Voice" class="btn btn-sm btn-primary" onclick="displayAddVoiceDialog(0);">Add</button>
                    <div id="voices_prev_td" class="page_system_prev"></div>
                    <div id="voices_page_td" class="page_system_page"></div>
                    <div id="voices_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?=$this->order_prepend?>pagesize" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0;loadVoices(); return false;">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="block-content">
                    <table class="table table-sm table-striped" id="voice_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?=date("g:ia m/d/Y T")?></caption>
                        <tr>
                            <th class="row2 text-left"><?=$this->getOrderLink('name')?>Name</a></th>
                            <th class="row2 text-left"><?=$this->getOrderLink('actor_name')?>Voice Actor</a></th>
                            <th class="row2 text-center"><?=$this->getOrderLink('language_id')?>Language</a></th>
                            <th class="row2 text-center"><?=$this->getOrderLink('campaign_id')?>Campaign</a></th>
                            <th class="row2 text-center"><?=$this->getOrderLink('status')?>Status</a></th>
                            <th class="row2 text-center">&nbsp;</th>
                        </tr>
                    </table>
                </div>
            </form>
        </div>
        <div id="dialog-modal-add-voice" title="Adding new Voice" class="nod"></div>
		<script>
			$("#dialog-modal-add-voice").dialog({
				autoOpen: false,
				width: 'auto',
				height: 'auto',
				modal: false,
				draggable: true,
				resizable: false,
                position: {my: 'center', at: 'center'},
            });

			$("#dialog-modal-add-voice").dialog("widget").draggable("option","containment","#main-container");
			 
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
