<?	/***************************************************************
	 *	Campaigns - handles listing and editing campaigns
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['scripts'] = new Scripts;


class Scripts{

	var $table		= 'scripts';		## Classes main table to operate on
	var $orderby	= 'id';				## Default Order field
	var $orderdir	= 'DESC';			## Default order direction

	## Page  Configuration
	var $pagesize	= 20;				## Adjusts how many items will appear on each page
	var $index		= 0;				## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'scr_list';		## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name 	= 'scrnextfrm';

	var $order_prepend = 'scr_';		## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function Scripts(){

		include_once("classes/campaigns.inc.php");

		$this->handlePOST();

	}



	function handlePOST(){
		# Ordering adjustments
		if($_GET[$this->order_prepend.'orderby'] && $_GET[$this->order_prepend.'orderdir']){
			if($_GET[$this->order_prepend.'orderdir']=='ASC')
				$this->orderdir	='ASC';
			else	$this->orderdir ='DESC';

			$this->orderby = $_GET[$this->order_prepend.'orderby'];	# Or switch order by

		}

		# Page index adjustments
		if($_REQUEST[$this->index_name]){

			$this->index = $_REQUEST[$this->index_name] * $this->pagesize;

		}

	}


	function handleFLOW(){
		# Handle flow, based on query string

		if(!checkAccess('scripts')){

			accessDenied("Scripts");
			return;

		}else{
		
			if(isset($_REQUEST['add_script'])){

				$this->makeAdd($_REQUEST['add_script']);

			}elseif(isset($_REQUEST['edit_voice_file'])) {

				$this->EditVoiceFile($_REQUEST['edit_voice_file']);

			}elseif(isset($_REQUEST['play_voice_file'])) {

				$this->PlayVoiceFile($_REQUEST['play_voice_file']);				

			}else{

				$this->listEntrys();

			}

		}

	}


	function listEntrys(){

		?><script>

			var script_delmsg = 'Are you sure you want to delete this script?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var ScriptsTableFormat = [
				['name','align_left'],
				['keys','align_center'],

				['[get:screen_name:screen_num]','align_center'],
				['[get:campaign_name:campaign_id]','align_center'],
				['[get:voice_name:voice_id]','align_center'],

				['[time:time_modified]','align_center'],

				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getScriptsURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=scripts&"+
								"mode=xml&"+

								//'s_id='+escape(frm.s_id.value)+"&"+
								's_name='+escape(frm.s_name.value)+"&"+
								's_filename='+escape(frm.s_filename.value)+"&"+
								's_keys='+escape(frm.s_keys.value)+"&"+
								's_campaign_id='+escape(frm.s_campaign_id.value)+"&"+
								's_voice_id='+escape(frm.s_voice_id.value)+"&"+
								's_variables='+escape(frm.s_variables.value)+"&"+
								((frm.s_screen_num.value > -1)?'s_screen_num='+escape(frm.s_screen_num.value)+"&":'')+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var scripts_loading_flag = false;

			/**
			* Load the scripts data - make the ajax call, callback to the parse function
			*/
			function loadScripts(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = scripts_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					return;

				}else{

					eval('scripts_loading_flag = true');

				}


				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				loadAjaxData(getScriptsURL(),'parseScripts');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseScripts(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('script',ScriptsTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(parseInt(<?=$this->order_prepend?>totalcount) > parseInt(<?=$this->order_prepend?>pagesize)){


					makePageSystem('scripts',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadScripts()'
								);

				}else{

					hidePageSystem('scripts');

				}

				eval('scripts_loading_flag = false');
			}


			function handleScriptListClick(id){

				displayAddScriptDialog(id);

			}



			function displayAddScriptDialog(scriptid){

				var objname = 'dialog-modal-add-script';


				if(scriptid > 0){
					$('#'+objname).dialog( "option", "title", 'Editing Script' );
					$('#'+objname).dialog( "option", "height", '600' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Script' );
					$('#'+objname).dialog( "option", "height", '380' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=scripts&add_script="+scriptid+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			function resetScriptForm(frm){

				frm.s_keys.value='';
				frm.s_name.value = '';
				frm.s_filename.value = '';
				frm.s_campaign_id.value = 0;
				frm.s_voice_id.value = 0;
				frm.s_screen_num.value=-1;
				frm.s_variables.value = '';

			}


			var scriptsrchtog = true;
			function toggleScriptSearch(){
				scriptsrchtog = !scriptsrchtog;
				ieDisplay('script_search_table', scriptsrchtog);
			}



			function buildVoicesDD(selid){

				var obj=getEl('s_voice_id');
				var opt = obj.options;
				var catid=getEl('s_campaign_id').value;

				// Empty DD
				for(var x=0;x<opt.length;x++){obj.remove(x);}
				obj.options.length=0;

				var newopts = new Array();
				newopts[0] = document.createElement("OPTION");

				if(ie)	obj.add(newopts[0]);
				else	obj.add(newopts[0],null);

				newopts[0].innerText	= '';
				newopts[0].value	= 0;
				var curid=0;
				for(x=0;x < item_id.length;x++){
					//curid=item_id[x];
					curid=x;

					//alert(which+' '+item_name[curid]);

					if(catid && item_cpgnid[curid] != catid){
						continue;
					}

					newopts[x] = document.createElement("OPTION");

					if(ie)	obj.add(newopts[x]);
					else	obj.add(newopts[x],null);

					newopts[x].value	= item_id[curid];


					if(ie)	newopts[x].innerText	= item_name[curid];
					else	newopts[x].innerHTML	= item_name[curid];

					if(selid == item_id[curid])obj.value=item_id[curid];



				}


			}


			var itemp = 0;
			var item_id	= new Array();
			var item_name	= new Array();
			var item_cpgnid	= new Array();
			var item_langid	= new Array();


			var lang_names = new Array();
			<?

				$res = $_SESSION['dbapi']->voices->getResults(array(
							'status'=>'enabled'
						));
				$x=0;
				while($r2 = mysqli_fetch_array($res, MYSQLI_ASSOC)){

					echo "item_id[".($x)."]=".intval($r2['id']).";";
					echo "item_name[".($x)."]='".addslashes($r2['name'])."';";
					echo "item_cpgnid[".($x)."]=".intval($r2['campaign_id']).";";
					echo "item_langid[".($x)."]=".intval($r2['language_id']).";";
					$x++;
				}

				$arr = $_SESSION['languages']->getLanguageArray();
				foreach($arr as $langrow){

					echo 'lang_names['.$langrow['id'].']="'.addslashes($langrow['name']).'";';

				}

			?>
			function getLangID(voice_id){
				for(x=0;x < item_id.length;x++){
					if(item_id[x] == voice_id){
						return item_langid[x];
					}
				}
				return 0;
			}

			function togVoiceDD(frm){

				buildVoicesDD(frm.s_voice_id.value);

				updateLanguage(frm);
			}

			function updateLanguage(frm){
				$('#lang_td_row').html( lang_names[getLangID(frm.s_voice_id.value)]  );
			}



		</script>
		<div id="dialog-modal-add-script" title="Adding new Script" class="nod">
		</div>
		<div id="dialog-modal-edit-voicefile" title="Editing Voice File" class="nod">
		</div>
		<form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadScripts();return false">
			<input type="hidden" name="searching_scripts">

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td width="500">
						Scripts
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Add" onclick="displayAddScriptDialog(0)">
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Search" onclick="toggleScriptSearch()">
					</td>
					<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadScripts();return false">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="500">500</option>
					</select></td>
					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="scripts_prev_td" class="page_system_prev"></td>
							<td id="scripts_page_td" class="page_system_page"></td>
							<td id="scripts_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>
		<tr>
			<td colspan="2"><table border="0" id="script_search_table">
			<tr>

				<th class="row2">Name</th>
				<th class="row2">Filename</th>
				<th class="row2">Keys</th>
				<th class="row2">Campaign</th>
				<th class="row2">Voice</th>
				<th class="row2">Screen</th>
				<th class="row2">Variables</th>
				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_name" size="15" value="<?=htmlentities($_REQUEST['s_name'])?>"></td>
				<td align="center"><input type="text" name="s_filename" size="10" value="<?=htmlentities($_REQUEST['s_filename'])?>"></td>
				<td align="center"><input type="text" name="s_keys" size="5" value="<?=htmlentities($_REQUEST['s_keys'])?>"></td>
				<td align="center"><?

					echo $_SESSION['campaigns']->makeDD('s_campaign_id',$_REQUEST['s_campaign_id'],'',"togVoiceDD(this.form)",0, 1);

				?></td>
				<td align="center"><select id="s_voice_id" name="s_voice_id" onchange="updateLanguage(this.form)">
					<option value="">[Loading...]</option>
				</select></td>
				<td align="center"><select name="s_screen_num">
					<option value="-1">[Show all]</option>
					<option value="0">Quick Keys</option>
					<option value="1"<?=($_REQUEST['s_screen_num'] == 1)?' SELECTED ':''?>>Intro Screen</option>
					<option value="2"<?=($_REQUEST['s_screen_num'] == 2)?' SELECTED ':''?>>Screen 2</option>
					<option value="3"<?=($_REQUEST['s_screen_num'] == 3)?' SELECTED ':''?>>Screen 3</option>
					<option value="4"<?=($_REQUEST['s_screen_num'] == 4)?' SELECTED ':''?>>Screen 4</option>
					<option value="5"<?=($_REQUEST['s_screen_num'] == 5)?' SELECTED ':''?>>Screen 5</option>
				</select></td>
				<td align="center"><input type="text" name="s_variables" size="20" value="<?=htmlentities($_REQUEST['s_variables'])?>"></td>
				<td><input type="button" value="Reset" onclick="resetScriptForm(this.form);resetPageSystem('<?=$this->index_name?>');loadScripts();"></td>
			</tr>
			</table></td>
		</tr>
		</form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="script_table">
			<tr>
				<th class="row2" align="left"><?=$this->getOrderLink('name')?>Name</a></th>
				<th class="row2"><?=$this->getOrderLink('keys')?>Keys</a></th>
				<th class="row2"><?=$this->getOrderLink('screen_num')?>Screen</a></th>
				<th class="row2"><?=$this->getOrderLink('campaign_id')?>Campaign</a></th>
				<th class="row2"><?=$this->getOrderLink('voice_id')?>Voice</a></th>
				<th class="row2"><?=$this->getOrderLink('time_modified')?>Last Modified</a></th>
				<th class="row2">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-add-script").dialog({
				autoOpen: false,
				width: 560,
				height: 360,
				modal: false,
				draggable:true,
				resizable: false
			});

			loadScripts();

			togVoiceDD(getEl('<?=$this->frm_name?>'));

		</script><?

	}


	function makeAdd($id){

		$id=intval($id);


		if($id){

			$row = $_SESSION['dbapi']->scripts->getByID($id);


		}

		?><script>

			function validateScriptField(name,value,frm){

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



			function checkScriptFrm(frm){

				var params = getFormValues(frm,'validateScriptField');

				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'name':

						alert("Please enter a name for this script.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;

					}

				// SUCCESS - POST AJAX TO SERVER
				}else{

					// AJAX CHECK TO MAKE SURE KEYS NOT IN USE
					$.ajax({
						type: "POST",
						cache: false,
						url: 'ajax.php?mode=check_keys&'+
								'campaign_id='+escape(frm.campaign_id.value)+'&'+
								'voice_id='+escape(frm.voice_id.value)+'&'+
								'screen_num='+escape(frm.screen_num.value)+'&'+
								'script_id='+escape(frm.adding_script.value)+'&'+

								'keys='+escape(frm.keys.value),


						error: function(){
							alert("Error saving script form. Please contact an admin.");
						},
						success: function(msg){

							if(msg == "1"){

								doScriptFormSubmittion(params);

							}else if(msg == "-1"){

								alert("Error: The keys specified contains a reserved keystroke and cannot be used.");

							}else{

								alert("Error: The keys specified appear to already be in use for this screen/campaign/voice");

							}

						}

					});

				}

				return false;

			}


			function doScriptFormSubmittion(params){

				$.ajax({
					type: "POST",
					cache: false,
					url: 'api/api.php?get=scripts&mode=xml&action=edit',
					data: params,
					error: function(){
						alert("Error saving script form. Please contact an admin.");
					},

					success: function(msg){


						var result = handleEditXML(msg);
						var res = result['result'];

						if(res <= 0){

							alert(result['message']);

							return;

						}


						loadScripts();


						displayAddScriptDialog(res);

					}

				});

			}


			function buildVoicesDD(selid){

				var obj=getEl('voice_id');
				var opt = obj.options;
				var catid=getEl('campaign_id').value;

				// Empty DD
				for(var x=0;x<opt.length;x++){obj.remove(x);}
				obj.options.length=0;

				var newopts = new Array();
				var curid=0;
				for(x=0;x < item_id.length;x++){

					curid=x;

					if(catid && item_cpgnid[curid] != catid){
						continue;
					}

					newopts[x] = document.createElement("OPTION");

					if(ie)	obj.add(newopts[x]);
					else	obj.add(newopts[x],null);

					newopts[x].value	= item_id[curid];


					if(ie)	newopts[x].innerText	= item_name[curid];
					else	newopts[x].innerHTML	= item_name[curid];

					if(selid == item_id[curid])obj.value=item_id[curid];



				}


			}

			var itemp = 0;
			var item_id	= new Array();
			var item_name	= new Array();
			var item_cpgnid	= new Array();
			var item_langid	= new Array();


			var lang_names = new Array();
			<?

				$res = $_SESSION['dbapi']->voices->getResults(array(
							'status'=>'enabled'
						));
				$x=0;
				while($r2 = mysqli_fetch_array($res, MYSQLI_ASSOC)){

					echo "item_id[".($x)."]=".intval($r2['id']).";";
					echo "item_name[".($x)."]='".mysqli_real_escape_string($_SESSION['dbapi']->db,$r2['name'])."';";
					echo "item_cpgnid[".($x)."]=".intval($r2['campaign_id']).";";
					echo "item_langid[".($x)."]=".intval($r2['language_id']).";";
					$x++;
				}

				$arr = $_SESSION['languages']->getLanguageArray();
				foreach($arr as $langrow){

					echo 'lang_names['.$langrow['id'].']="'.mysqli_real_escape_string($_SESSION['dbapi']->db,$langrow['name']).'";';

				}
			?>

			function getLangID(voice_id){
				for(x=0;x < item_id.length;x++){
					if(item_id[x] == voice_id){
						return item_langid[x];
					}
				}
				return 0;
			}

			function togVoiceDD(frm){

				buildVoicesDD(<?=intval($row['voice_id'])?>);

				updateLanguage(frm);
			}

			function updateLanguage(frm){
				$('#lang_td_row').html( lang_names[getLangID(frm.voice_id.value)]  );
			}


			function deleteVoiceFile(id){
				$('#del_voice_id').val(id);

				checkScriptFrm(getEl('scr_add_frm'));
			}			

			// SET TITLEBAR
			$('#dialog-modal-add-script').dialog( "option", "title", '<?=($id)?'Editing Script #'.$id.' - '.addslashes(htmlentities($row['name'])):'Adding new Script'?>' );



		</script>
		<form method="POST" id="scr_add_frm" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkScriptFrm(this); return false">
			<input type="hidden" id="adding_script" name="adding_script" value="<?=$id?>" >
			<input type="hidden" id="del_voice_id" name="del_voice_id" value="" >

		<table border="0" align="center">
		<tr>
			<th align="left" height="30">Campaign</th>
			<td><?
				echo $_SESSION['campaigns']->makeDD('campaign_id',$row['campaign_id'],''," togVoiceDD(this.form) ",'',false);
			?></td>
		</tr>
		<tr>
			<th align="left" height="30">Voice</th>
			<td><select id="voice_id" name="voice_id" onchange="updateLanguage(this.form)">
				<option value="">[Loading...]</option>
			</select></td>
		</tr>
		<tr>
			<th align="left" height="30">Language</th>
			<td id="lang_td_row">[Loading...]</td>
		</tr>
		<tr>
			<th align="left" height="30">Name</th>
			<td><input name="name" type="text" size="50" value="<?=htmlentities($row['name'])?>"></td>
		</tr>
		<tr>
			<th align="left" height="30">Description</th>
			<td><textarea name="description" rows="4" cols="45"><?=htmlentities($row['description'])?></textarea></td>
		</tr>
		<tr>
			<th align="left" height="30">Variables</th>
			<td><input type="text" name="variables" size="50" value="<?=htmlentities($row['variables'])?>"></td>
		</tr>
		<tr>
			<th align="left" height="30">Keys</th>
			<td>
				<input name="keys" type="text" size="3" maxlength="2" value="<?=htmlentities($row['keys'])?>">
				&nbsp;&nbsp;&nbsp;&nbsp;
				Advance the script a screen? <input type="checkbox" name="advance_script" value="1" <?=($row['advance_script'] == 'yes')?'CHECKED':''?> >

			</td>
		</tr>
		<tr>
			<th align="left" height="30">Screen Number</th>
			<td>
				<select name="screen_num" id="screen_num">
					<option value="0">Quick Keys</option>
					<option value="1"<?=($row['screen_num'] == 1)?' SELECTED ':''?>>Intro Screen</option>
					<option value="2"<?=($row['screen_num'] == 2)?' SELECTED ':''?>>Screen 2</option>
					<option value="3"<?=($row['screen_num'] == 3)?' SELECTED ':''?>>Screen 3</option>
					<option value="4"<?=($row['screen_num'] == 4)?' SELECTED ':''?>>Screen 4</option>
					<option value="5"<?=($row['screen_num'] == 5)?' SELECTED ':''?>>Screen 5</option>
				</select>
			</td>
		<tr>
			<th colspan="2" align="center" height="50">

				<input type="submit" value="Save Changes"><?


				if($id){

					?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

					<input type="button" value="View Change History" style="font-size:10px" onclick="viewChangeHistory('scripts', <?=$row['id']?>)" /><?
				}


			?></th>
		</tr>
		</form><?

		if($id > 0){
		?><tr>
			<td colspan="2">
			<script>


			function deleteImageRecord(file_id){

				var params = "file_id="+escape(file_id);

				$.ajax({
						type: "POST",
						url: 'api/api.php?get=scripts&mode=xml&action=delete_image',
						data: params,
						success: function(msg){

							// handleEditXML(xmldata, baseurl,uiobj,success_callback_func)
							var result = handleEditXML(msg);
							var res = result['result'];
							alert(res);
							if(res <= 0){

								alert(result['message']);

								return;

							}

							// SUCCESSFUL - REFRESH/REMOVE IMAGE...

							$('#upload_status_cell').html("");

						}

					});

			}

			function ninjaUploadImage(){

				$('#upload_status_cell').html('<div style="font-size:16px;height:30px;">Preparing upload...</div>');

				var ninjafrm=getEl('ninja_upload_<?=$id?>_frm');

				// BLANK THE PAGE
				getEl('iframe_upload').src = 'about:blank';

				$('#upload_status_cell').html('<div style="font-size:16px;height:40px;"><img src="images/ajax-loader.gif" border="0" />Uploading file...</div>');

				// SUBMIT HIDDEN FORM
				ninjafrm.submit();
			}

			function accountUploadFailed(error_code, error_message){

				var msg = "ERROR! Upload failed:\n"+error_message;

				$('#upload_status_cell').html(msg);

				alert(msg);
			}

			function accountUploadSuccess(url, warning_messages){

				warning_messages = $.trim(warning_messages);

				$('#upload_status_cell').html('Success');

				if(warning_messages){
					alert("Successfully uploaded file"+ ((warning_messages)?". However warnings were issued:\n"+warning_messages:".") );
				}

				displayAddScriptDialog(<?=$row['id']?>);
			}

			function reorderItem(file_id, direction){

				$.ajax({
						type: "POST",
						url: 'ajax.php?mode=change_order&script_id=<?=$row['id']?>&file_id='+file_id+'&direction='+direction,
						success: function(msg){

							// REFRESH LIST
							//checkScriptFrm(getEl('scr_add_frm'));

							displayAddScriptDialog(<?=$row['id']?>);

						}
				});

			}

			// Display edit voice file dialog with given voice file id
			function displayEditVoiceFile(voicefileid){

				var objname = 'dialog-modal-edit-voicefile';

				$('#'+objname).dialog( "option", "title", 'Editing Voice File #'+voicefileid );
				$('#'+objname).dialog( "option", "height", '250' );
				$('#'+objname).dialog( "option", "width", '400' );

				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=scripts&edit_voice_file="+voicefileid+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');

			}						

			// Function to display edit voice file dialog box on list click
			function handleFileListClick(id){

				displayEditVoiceFile(id);

			}	

			// Edit voice file dialog spec
			$("#dialog-modal-edit-voicefile").dialog({
				autoOpen: false,
				height: 250,
				width: 400,
				modal: false,
				draggable:true,
				resizable: false
			});

			</script>
				<div class="nod">
					<iframe id="iframe_upload" name="iframe_upload" width="1" height="1" frameborder="0" src="about:blank"></iframe>
				</div>

				<form id="ninja_upload_<?=$id?>_frm" name="ninja_upload_<?=$id?>_frm" method="POST" enctype="multipart/form-data" action="ajax.php?mode=sound_upload" target="iframe_upload">
					<input type="hidden" name="script_id" id="script_id" value="<?=$row['id']?>">
					<input type="hidden" name="voice_id" id="voice_id" value="<?=$row['voice_id']?>">

				<table border="0" width="100%">
				<tr>
					<th colspan="4" class="ui-widget-header">

						Voice Files (<a href="#" onclick="alert('These sounds are binding to the key you set above, for the screen you selected.\n\nUpload Script Sound: These are the main script sound and multipress sounds\nUpload REPEAT Sound: This will upload the special REPEAT sound, for when caller is interrupted mid-script.');return false;">help?</a>)

					</th>
				</tr>
				<tr>
					<th align="left">New Voice File's Description</th>
					<td colspan="2">
					<textarea name="file_description" rows="4" cols="35"></textarea>
					</td>
				</tr>				
				<tr>
					<th align="left">
						<select name="upload_mode">
							<option value="script">Upload Script Sound:</option>
							<option value="repeat-short">Upload SHORT REPEAT:</option>
							<option value="repeat-long">Upload LONG REPEAT:</option>
							<option value="repeat-question">Upload Q. ONLY REPEAT:</option>
						</select>
					</th>
					<td>
						<input type="file" name="sound_file" id="sound_file" onchange="ninjaUploadImage(<?=$id?>)">
					</td>
				</tr>
				<tr>
					<td align="center" colspan="2" id="upload_status_cell"><?

					?></td>
				</tr>
				<tr>
					<th class="row2" align="left">Filename</th>

					<th class="row2" align="left">Duration</th>
					<th class="row2" align="left">Order</th>
					<th class="row2" width="50">&nbsp;</th>
				</tr><?


				$re2 = $_SESSION['dbapi']->voices->getFiles($row['id']);
				if(mysqli_num_rows($re2) == 0){
					?><tr><td colspan="3" align="center"><i>No files found.</i></td></tr><?
				}

				$color=0;
				while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){
					$class = 'row'.($color++%2);
					?><tr>
						<td class="<?=$class?>"><span class="hand" onclick="handleFileListClick(<?=$r2['id']?>)"><?=htmlentities($r2['file'])?></span></td>
						<td class="<?=$class?>"><?=$r2['duration'].'&nbsp;sec'?></td>
						<td class="<?=$class?>" align="center"><?

							if($r2['repeat'] != 'no'){

								switch($r2['repeat']){
								case 'short':
									echo "SHORT REPEAT";
									break;
								case 'long':

									echo "LONG REPEAT";
									break;
								case 'question':
									echo "QUESTION REPEAT";
									break;
								}

							}else{
								?><a href="#" onclick="reorderItem(<?=$r2['id']?>, 'up');return false;">Up</a> |
								<a href="#" onclick="reorderItem(<?=$r2['id']?>, 'down');return false;">Down</a><?
							}

						?></td>

						<td align="center"><a href="#" onclick="deleteVoiceFile(<?=$r2['id']?>)">[delete]</a></td>
					</tr><?
				}

				?></table>
			</td>
		</tr><?

		}


		?>


		</form>
		</table>

		<script>

			togVoiceDD(getEl('scr_add_frm'));

		</script><?


	}


	function EditVoiceFile($id){

		# Function to edit voice file descriptions
		# Takes voice file id and pulls db record to be editted

		$id=intval($id);

		if($id){

			# Grab voice file db record
			$row = $_SESSION['dbapi']->scripts->getVoiceFileByID($id);

		}

		?><script>

			// Used by dialog box Cancel button
			function HideEditVoiceFile(){

				var objname = 'dialog-modal-edit-voicefile';

				$('#'+objname).dialog("close");
			
			}

			// Used by form submit to validate form fields
			// 'description' is the only required field when submitting
			function validateVoiceEditField(name,value,frm){

				switch(name){
				default:

					// Bypass fields not specified
					return true;
					break;

				case 'description':

					// Require 'description' field to have a value
					if(!value)return false;

					return true;
					break;

				}

				return true;

			}

			// Form submit function that validates form fields and posts AJAX
			function checkVoiceEditFrm(frm){

				var params = getFormValues(frm,'validateVoiceEditField');

				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'description':

						alert("Please enter a description for this voice file.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;

					}

				// SUCCESS - POST AJAX TO SERVER
				}else{
					
					$.ajax({
						type: "POST",
						cache: false,
						url: 'api/api.php?get=scripts&mode=xml&action=edit_voice_file',
						data: params,
						error: function(){
							alert("Error saving voice file edit form. Please contact an admin.");
						},

						success: function(msg){

							var result = handleEditXML(msg);
							var res = result['result'];

							if(res <= 0){

								alert(result['message']);

								return;

							}

							displayEditVoiceFile(res);

						}

					});

				}

				return false;

			}

			function playAudio(id){


				//$('#media_player').dialog("open");

				$('#media_player').children().filter("audio").each(function(){
					this.pause(); // can't hurt
					delete(this); // @sparkey reports that this did the trick!
					$(this).remove(); // not sure if this works after null assignment
				});
				$('#media_player').empty();

				$('#media_player').load("index.php?area=scripts&play_voice_file="+id+"&printable=1&no_script=1");
				// $('#media_player').load("test.php");

				// REMOVE AND READD TEH CLOSE BINDING, TO STOP THE AUDIO
				$('#media_player').unbind("dialogclose");
				$('#media_player').bind('dialogclose', function(event) {

					hideAudio();

				});


				}

			function hideAudio(){
				$('#media_player').children().filter("audio").each(function(){
					this.pause();
					delete(this);
					$(this).remove();

				});

				$('#media_player').empty();
			}


		</script>
		
		<form method="POST" id="scr_edit_vfile" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkVoiceEditFrm(this); return false">
			<input type="hidden" id="editing_vfile" name="editing_vfile" value="<?=$id?>" >

		<table border="0" align="center" width="100%">
		<tr>
			<th align="left" height="30">Filename</th>
			<td><?=$row['file']?></td>
		</tr>
		<tr>
			<th align="left" height="30">Description</th>
			<td><textarea name="description" rows="4" cols="35"><?=htmlentities($row['description'])?></textarea></td>
		</tr>
		<tr>
			<td align="center" colspan="2" height="50">
				<input type="submit" value="Save Changes">
				<input type="button" value="Cancel" onclick="hideAudio(); HideEditVoiceFile(); return false;">
				<input type="button" value="Listen" onclick="playAudio('<?=$row['id']?>')">
			</td>
		</tr>

		<center><div id="media_player" title="Playing Call Recording"></center>
		<?


	}


	function PlayVoiceFile($id){

		# Play audio file function - it will display audio player with play_audio_file.php as source

		$id=intval($id);

		if($id){

			# Grab voice file db record
			$row = $_SESSION['dbapi']->scripts->getVoiceFileByID($id);

		}
		?>
		<audio id="audio_obj" autoplay controls>
			<source src="play_audio_file.php?file=<?=htmlentities($row['file'])?>" type="audio/wav" />
			Your browser does not support the audio element.
		</audio><br>
		<a href="#" onclick="parent.hideAudio();return false">[Hide Player]</a>
		
		<script>
			parent.applyUniformity();
		</script><?

	}

	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadScripts();return false;\">";

		return $var;
	}
}
