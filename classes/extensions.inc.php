<?	/***************************************************************
	 *	Extensions - handles listing and editing of extensions/mappign to stations
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['extensions'] = new Extensions;


class Extensions{

	var $table	= 'extensions';	## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'ext_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'extnextfrm';

	var $order_prepend = 'ext_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function Extensions(){

		## REQURES DB CONNECTION!
		$this->handlePOST();
	}




	function handlePOST(){


	}

	function handleFLOW(){
		# Handle flow, based on query string

		if(!checkAccess('extensions')){


			accessDenied("Extensions");

			return;

		}else{

			if(isset($_REQUEST['add_extension'])){

				$this->makeAdd($_REQUEST['add_extension']);

			}else{
				$this->listEntrys();
			}

		}

	}






	function listEntrys(){


		?><script>

			var extension_delmsg = 'Are you sure you want to delete this extension?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var ExtensionsTableFormat = [
				['number','align_center'],
				['[get:server_name:server_id]','align_left'],

				['in_use','align_center'],
				['[get:username:in_use_by_userid]','align_center'],
				['status','align_center'],
				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getExtensionsURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=extensions&"+
								"mode=xml&"+

								's_id='+escape(frm.s_id.value)+"&"+
								's_number='+escape(frm.s_number.value)+"&"+
								's_status='+escape(frm.s_status.value)+"&"+

								's_in_use='+escape(frm.s_in_use.value)+"&"+



								's_server_id='+escape(frm.s_server_id.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var extensions_loading_flag = false;

			/**
			* Load the data - make the ajax call, callback to the parse function
			*/
			function loadExtensions(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = extensions_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("extensions ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('extensions_loading_flag = true');
				}

				// PAGE SIZE SUPPORT!
				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());


				loadAjaxData(getExtensionsURL(),'parseExtensions');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseExtensions(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('extension',ExtensionsTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('extensions',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadExtensions()'
								);

				}else{

					hidePageSystem('extensions');

				}

				eval('extensions_loading_flag = false');
			}


			function handleExtensionListClick(id){

				displayAddExtensionDialog(id);

			}


			function displayAddExtensionDialog(extensionid){

				var objname = 'dialog-modal-add-extension';


				if(extensionid > 0){
					$('#'+objname).dialog( "option", "title", 'Editing Extension' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Extension' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=extensions&add_extension="+extensionid+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			function resetExtensionForm(frm){

				frm.s_id.value='';
				frm.s_number.value = '';
				frm.s_status.value='enabled';
				frm.s_server_id.value = '';
				frm.s_in_use.value = '';
			}




		</script>
		<div id="dialog-modal-add-extension" title="Adding new Extension" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadExtensions();return false">
			<input type="hidden" name="searching_extensions">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td width="500">
						Extensions
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Add" onclick="displayAddExtensionDialog(0)">
					</td>
					<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadExtensions();return false">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="500">500</option>
					</select></td>
					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="extensions_prev_td" class="page_system_prev"></td>
							<td id="extensions_page_td" class="page_system_page"></td>
							<td id="extensions_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" width="100%">
			<tr>

				<th class="row2">Extension</th>
				<th class="row2">Server</th>
				<th class="row2">In Use</th>
				<th class="row2">Status</th>
				<th class="row2">ID</th>
				<td><input type="submit" value="Search" onclick="<?=$this->index_name?> = 0;" name="the_Search_button"></td>
			</tr>
			<tr>

				<td align="center"><input type="text" name="s_number" size="20" value="<?=htmlentities($_REQUEST['s_number'])?>"></td>
				<td align="center"><?
			 		echo $this->makeServerDD('s_server_id', $_REQUEST['s_server_id'], 1);
				?></td>
				<td align="center"><select name="s_in_use">
					<option value="">[All]</option>
					<option value="yes">Yes</option>
					<option value="no">No</option>

				</select></td>

				<td align="center"><select name="s_status">
					<option value="enabled">Enabled</option>
					<option value="suspended">Suspended</option>
					<option value="deleted">Deleted</option>
				</select></td>
				<td align="center"><input type="text" name="s_id" size="5" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>


				<td><input type="button" value="Reset" onclick="resetExtensionForm(this.form);resetPageSystem('<?=$this->index_name?>');loadExtensions();"></td>
			</tr>
			</table></td>
		</tr>

		</form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="extension_table">
			<tr>
				<th class="row2" align="center"><?=$this->getOrderLink('number')?>Extension</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('server_id')?>Server</a></th>
				<th class="row2"><?=$this->getOrderLink('in_use')?>In Use</a></th>
				<th class="row2"><?=$this->getOrderLink('in_use_by_userid')?>In Use By</a></th>
				<th class="row2"><?=$this->getOrderLink('status')?>Status</a></th>
				<th class="row2">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-add-extension").dialog({
				autoOpen: false,
				width: 430,
				height: 375,
				modal: false,
				draggable:true,
				resizable: false
			});

			loadExtensions();

		</script><?

	}


	function makeAdd($id){

		$id=intval($id);


		if($id){

			$row = $_SESSION['dbapi']->extensions->getByID($id);


		}

		?><script>

			function validateExtensionField(name,value,frm){

				//alert(name+","+value);


				switch(name){
				default:

					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;

				case 'number':


					if(!value)return false;

					return true;


					break;
//				case 'port_num':
//
//					if(value%2 != 0)return false;
//
//					value = parseInt(value);
//
//					if(value <= 0)return false;
//
//					break;
				}


				return true;
			}



			function checkExtensionFrm(frm){


				var params = getFormValues(frm,'validateExtensionField');


				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'number':

						alert("Please enter a number for this extension.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;
//					case 'port_num':
//						alert("The Port number must be an EVEN and UNUSED port number for the specified server.");
//						eval('try{frm.'+params[0]+'.select();}catch(e){}');
//						break;
					}

				// SUCCESS - POST AJAX TO SERVER
				}else{


					//alert("Form validated, posting");

					$.ajax({
						type: "POST",
						cache: false,
						url: 'api/api.php?get=extensions&mode=xml&action=edit',
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


							loadExtensions();


							displayAddExtensionDialog(res);

							alert(result['message']);

						}


					});

				}

				return false;

			}


			function suggestPort(){

				// AJAX PULL THE DATA


			}



			// SET TITLEBAR
			$('#dialog-modal-add-extension').dialog( "option", "title", '<?=($id)?'Editing Extension #'.$id.' - '.htmlentities($row['number']):'Adding new Extension'?>' );



		</script>
		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkExtensionFrm(this); return false">
			<input type="hidden" id="adding_extension" name="adding_extension" value="<?=$id?>" >


		<table border="0" align="center">
		<tr>
			<th align="left" height="30">Server ID:</th>
			<td><?

				echo $this->makeServerDD('server_id', $row['server_id']);

			?></td>
		</tr>
		<tr>
			<th align="left" height="30">Extension Number</th>
			<td><input name="number" type="text" size="30" value="<?=htmlentities($row['number'])?>"></td>
		</tr>
<?/**		<tr>
			<th align="left" height="30">Station ID</th>
			<td><input name="station_id" type="text" size="30" value="<?=htmlentities($row['station_id'])?>"></td>
		</tr>
**/?>
		<tr>
			<th align="left" height="30">SIP Password</th>
			<td><input name="password" type="text" size="30" value="<?=htmlentities($row['password'])?>"></td>
		</tr>
<?/**		<tr>
			<th align="left" height="30" <?

				if($id > 0 && intval($row['port_num']) <= 0){
					echo ' style="background-color:#FF0000" ';
				}

			?>>Bind Port</th>
			<td>
				<input name="port_num" type="text" size="10" value="<?=htmlentities($row['port_num'])?>">
				&nbsp;&nbsp;&nbsp;&nbsp;<?

				/**if(intval($row['port_num']) <= 0){
					?><input type="button" value="Suggest port" onclick="suggestPort()"><?
				}**
			?></td>
		</tr>
		**/

		?><tr>
			<th align="left" height="30">PX Register as User</th>
			<td><input name="register_as" type="text" size="30" value="<?=htmlentities($row['register_as'])?>"></td>
		</tr>
		<tr>
			<th align="left" height="30">PX Register Password</th>
			<td><input name="register_pass" type="text" size="30" value="<?=htmlentities($row['register_pass'])?>"></td>
		</tr>

		<tr>
			<th align="left" height="30">Status</th>
			<td><select name="status">
				<option value="enabled">Enabled</option>
				<option value="suspended"<?=($row['status'] == 'suspended')?' SELECTED ':''?>>Suspended</option>
				<option value="deleted"<?=($row['status'] == 'deleted')?' SELECTED ':''?>>Deleted</option>
			</select></td>
		</tr><?

		if($id){
			?><tr>
				<th align="left" height="30">In Use</th>
				<td><?=htmlentities($row['in_use'])?></td>
			</tr>
			<tr>
				<th align="left" height="30">In Use By User:</th>
				<td><?=htmlentities($row['in_use'])?></td>
			</tr>
			<tr>
				<th align="left" height="30">Time Started:</th>
				<td><?=date("g:ia m/d/Y", $row['time_started'])?></td>
			</tr><?

		}

		?><tr>
			<th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
		</tr>
		</form>
		</table><?
	}


	function makeServerDD($name, $sel, $blank_field=0){

		$out = '<select name="'.$name.'" id="'.$name.'">';

		$res = $_SESSION['dbapi']->query("SELECT * FROM servers ORDER BY name ASC");

		if($blank_field){

			$out .= '<option value="">[SELECT ONE]</option>';
		}

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$out .= '<option value="'.$row['id'].'"';

			$out .= ($row['id'] == $sel)?' SELECTED ':'';

			$out .= '>'.$row['name'].'</option>';
		}

		$out .= '</select>';

		return $out;
	}

	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadExtensions();return false;\">";

		return $var;
	}
}
