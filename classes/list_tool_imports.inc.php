<?	/***************************************************************
	 *	List Tool - Imports
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['imports'] = new ListToolImports;


class ListToolImports{

	var $table	= 'imports';			## Classes main table to operate on
	var $orderby	= 'time';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'import_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'importnextfrm';

	var $order_prepend = 'import_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function ListToolImports(){


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
//
//		if(!checkAccess('names')){
//
//
//			accessDenied("Names");
//
//			return;
//
//		}else{


		if(!checkAccess('list_tools')){

			accessDenied("List Tools");
			return;

		}




		if(isset($_REQUEST['view_import'])){

			$this->makeView($_REQUEST['view_import']);

		}else{

			$this->listEntrys();

		}





	}





	function listEntrys(){


		?><script>

			var import_delmsg = 'Are you sure you want to delete this import and all of its leads?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var ImportsTableFormat = [
				['id','align_center'],
				['phone_type','align_center'],
				['[date:time]','align_center'],
				['name','align_left'],

				['[render:number:current_lead_count]','align_right'],

				['[call_function:manualRecount:Manual Recount:id]', 'align_center']

			<?/**	['[get:lead_count:id]','align_right'],

				['[delete]','align_center']**/?>
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getImportsURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=imports&"+
								"mode=xml&"+

//								's_id='+escape(frm.s_id.value)+"&"+
//								's_name='+escape(frm.s_name.value)+"&"+
//								's_filename='+escape(frm.s_filename.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var imports_loading_flag = false;

			/**
			* Load the import - make the ajax call, callback to the parse function
			*/
			function loadImports(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = imports_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("NAMES ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('imports_loading_flag = true');
				}

				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				loadAjaxData(getImportsURL(),'parseImports');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseImports(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('import',ImportsTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('imports',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadImports()'
								);

				}else{

					hidePageSystem('imports');

				}

				eval('imports_loading_flag = false');
			}


			function handleImportListClick(id){

				displayImportDialog(id);

			}


			function displayImportDialog(id){

				var objname = 'dialog-modal-view-import';


				$('#'+objname).dialog( "option", "title", 'Viewing Import #'+id );



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=list_tools&tool=view_imports&view_import="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			function resetImportForm(frm){

//				frm.s_id.value = '';
//				frm.s_name.value = '';
//				frm.s_filename.value='';

			}


//			var importsrchtog = false;
//
//			function toggleNameSearch(){
//				namesrchtog = !namesrchtog;
//				ieDisplay('name_search_table', namesrchtog);
//			}



			function manualRecount(import_id){

				$('#import_loading_cell').html('<img src="images/ajax-loader.gif" border="0" /> Counting Leads...');

				$.ajax({
					type: "POST",
					cache: false,
					url: 'api/api.php?get=imports&mode=xml&action=recount&import_id='+import_id,
					error: function(){
						alert("Error submitting recounting request. Please contact an admin.");
						$('#import_loading_cell').html('');
					},
					success: function(msg){

						// REFRESH LIST WHEN FINISHED
						loadImports();


						$('#import_loading_cell').html('');


					}
				});


			}

		</script>
		<div id="dialog-modal-view-import" title="View Import" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadImports();return false">
			<input type="hidden" name="searching_import">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td width="300">
						Imports



					</td>
					<td id="import_loading_cell" nowrap>

					</td>
					<td>
						<input type="button" value="Refresh List" onclick="loadImports()" />
					</td>
					<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadImports();return false">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="500">500</option>
					</select></td>

					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="imports_prev_td" class="page_system_prev"></td>
							<td id="imports_page_td" class="page_system_page"></td>
							<td id="imports_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>
<?/*
		<tr>
			<td colspan="2"><table border="0" width="100%" id="import_search_table" class="nod">
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
				<td><input type="button" value="Reset" onclick="resetNameForm(this.form);resetPageSystem('<?=$this->index_name?>');loadNames();"></td>
			</tr>
			</table></td>
		</tr>

				['id','align_center'],
				['type','align_center'],
				['[date:time]','align_center'],
				['name','align_left'],

				['[get:lead_count:id]','align_right'],


	***/?>
		</form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="import_table">
			<tr>

				<th class="row2" align="center"><?=$this->getOrderLink('id')?>ID</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('phone_type')?>Phone Type</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('time')?>Import Date</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('name')?>Name</a></th>
				<th class="row2" align="right"><?=$this->getOrderLink('current_lead_count')?>Lead Count</a></th>
				<th class="row2" align="right">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-view-import").dialog({
				autoOpen: false,
				width: 500,
				height: 300,
				modal: false,
				draggable:true,
				resizable: true
			});

			loadImports();

		</script><?

	}


	function makeView($id){

		$id=intval($id);


		if($id){

			$row = $_SESSION['dbapi']->imports->getByID($id);


		}



		if(!$_REQUEST['section']){

			?><div id="importtabs">

				<ul>
					<li><a href="index.php?area=list_tools&tool=view_imports&view_import=<?=$id?>&section=general&printable=1&no_script=1">General</a></li>
					<li><a href="index.php?area=list_tools&tool=view_imports&view_import=<?=$id?>&section=tools&printable=1&no_script=1">Tools</a></li>
					<li><a href="index.php?area=list_tools&tool=view_imports&view_import=<?=$id?>&section=reports&printable=1&no_script=1">Reports</a></li>

				</ul>
			</div><?
		}

		switch($_REQUEST['section']){
		case 'general':

			$this->makeGeneralGUI($row);

			break;
		case 'tools':

			$this->makeToolsGUI($row);

			break;
		case 'reports':

			if($_REQUEST['import_reports']){

				$this->generateReport($row);

			}else{

				$this->makeReportGUI($row);

			}

			break;

		}



		?><script>

		  $(function() {

		    $( "#importtabs" ).tabs({
		    				heightStyle: "fill"
		    			 });

		  });

		</script><?


	}


	function makeGeneralGUI($row){

		?><script>

		function validateNameField(name,value,frm){

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



			function checkGeneralFrm(frm){


				var params = getFormValues(frm,'validateNameField');


				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'name':

						alert("Please enter a name for this Import!");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;

					}

				// SUCCESS - POST AJAX TO SERVER
				}else{


					//alert("Form validated, posting");

					$.ajax({
						type: "POST",
						cache: false,
						url: 'api/api.php?get=imports&mode=xml&action=edit',
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


							loadImports();


							displayImportDialog(res);

							alert(result['message']);

						}


					});

				}

				return false;

			}

		</script>


		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="return checkGeneralFrm(this)">
			<input type="hidden" id="import_general" name="import_general" value="<?=$row['id']?>" >

		<table border="0" align="center">
		<tr>
			<th align="left" height="20">Import Name:</th>
			<td><input name="name" type="text" size="50" value="<?=htmlentities($row['name'])?>"></td>
		</tr>
		<tr>
			<th align="left" height="20">Import Date:</th>
			<td><?=date("m/d/Y", $row['time'])?></td>
		</tr>
		<tr>
			<th align="left" height="20">Type:</th>
			<td><?=ucfirst($row['phone_type'])?></td>
		</tr>
		<tr>
			<th align="left" height="20">Lead Count:</th>
			<td><?=number_format($row['current_lead_count'])?></td>
		</tr>
		<tr>
			<th align="left">Description:</th>
			<td><textarea name="description" rows="3" cols="30"><?=htmlentities($row['description'])?></textarea></td>
		</tr>
		<tr>
			<th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
		</tr>
		</form>
		</table><?
	}

	function makeToolsGUI($row){
		?><script>


			function validateMoveImportField(name,value,frm){

				//alert(name+","+value);


				switch(name){
				default:

					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;

				case 'move_to_import_id':


					if(!value)return false;

					return true;


					break;
				}
				return true;
			}



			function checkMoveImportForm(frm){



				var params = getFormValues(frm,'validateMoveImportField');


				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'move_to_import_id':

						alert("Please select an Import to move the leads to.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;

					}

				// SUCCESS - POST AJAX TO SERVER
				}else{

					generateMoveLeadAuthKey();

				}


				return false;
			}

		function generateMoveLeadAuthKey(){

			$.post("ajax.php?mode=generate_auth_key&type=move_import",null,ninjaPostMoveForm);

		}


		function ninjaPostMoveForm(authcode){

				var frm = getEl('ninjamoveform');
				frm.auth_code.value = authcode;

				var params = getFormValues(frm);

				$.ajax({
						type: "POST",
						cache: false,
						url: frm.action,
						data: params,
						error: function(){
							alert("Error saving form. Please contact an admin.");
						},

						success: function(msg){

							//alert(msg);

							var taskid = parseInt(msg);

							window.parent.displayViewTaskDialog(taskid);

							//$('#build_result_cell').html(msg);

						}
					});
			}

//			function moveListSuccess(url, warning_messages){
//
//				warning_messages = $.trim(warning_messages);
//
//				$('#upload_dnc_status_cell').html('Success');
//
//				if(warning_messages){
//					alert("Successfully uploaded file"+ ((warning_messages)?". However warnings were issued:\n"+warning_messages:".") );
//				}
//
//				//displayAddScriptDialog
//			}


			function deleteImport(){

				$.post("ajax.php?mode=generate_auth_key&type=delete_import",null,ninjaDeleteImport);
			}

			function ninjaDeleteImport(code){

				$.ajax({
						type: "POST",
						cache: false,
						url: '<?=$_SESSION['list_tools']->upload_api_script.'?mode=delete_import'?>&import_id=<?=$row['id']?>&auth_code='+code,
						error: function(){
							alert("Error saving form. Please contact an admin.");
						},

						success: function(msg){

							var taskid = parseInt(msg);

							window.parent.displayViewTaskDialog(taskid);

							//$('#build_result_cell').html(msg);

						}
					});

			}


			function purgeImport(){

				$.post("ajax.php?mode=generate_auth_key&type=purge_import",null,ninjaPurgeImport);
			}

			function ninjaPurgeImport(code){

				$.ajax({
						type: "POST",
						cache: false,
						url: '<?=$_SESSION['list_tools']->upload_api_script.'?mode=purge_import'?>&import_id=<?=$row['id']?>&auth_code='+code,
						error: function(){
							alert("Error saving form. Please contact an admin.");
						},

						success: function(msg){

							var taskid = parseInt(msg);

							window.parent.displayViewTaskDialog(taskid);

							//$('#build_result_cell').html(msg);

						}
					});

			}

		</script>
		<?




			$_SESSION['list_tools']->makeViewTaskGUI();



		?>
		<form id="ninjamoveform" method="POST" action="<?=$_SESSION['list_tools']->upload_api_script.'?mode=move_import_leads'?>" autocomplete="off" onsubmit="return checkMoveImportForm(this)">

			<input type="hidden" id="import_tools" name="import_tools" value="<?=$row['id']?>" >

			<input type="hidden" name="auth_code" id="auth_code">

		<table border="0" width="100%" align="center">
		<tr>
			<td colspan="3" class="big bl">

				Move Leads - <?=htmlentities($row['name'])?>

			</td>
		</tr>
		<tr>
			<td colspan="3">

				<table border="0" width="100%">
				<tr>
					<th>Destination Import:</th>
					<td><?

						echo $_SESSION['list_tools']->makeImportDD($row['phone_type'],'move_to_import_id','move_to_import_id','',"",null,"");

					?></td>
				</tr>
				<tr>
					<th># of leads:<br>(0 is ALL)</th>
					<td>
						<input type="text" id="num_leads" name="num_leads" size="5" value="0" />
					</td>
				</tr>
				<tr>
					<th>
						Last Called Days:<br />
						(0 is ALL)
					</th>
					<td>

						<input type="text" name="last_called_days" id="last_called_days" size="5" value="0" onkeyup="this.value = this.value.replace(/[^0-9]/g, '');" />

					</td>
				</tr>
				<tr>
					<th>Timezone:</th>
					<td>

						<select name="timezone">
							<option value="">[All Timezones]</option>
							<option value="-5.0">Eastern</option>
							<option value="-6.0">Central</option>
							<option value="-7.0">Mountain</option>
							<option value="-8.0">Pacific</option>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">

						<input type="submit" value="Move Leads!" />

					</td>
				</tr>
				</table>
			</td>
		</tr>


		<tr valign="bottom">
			<td colspan="3" height="20"><hr /></td>
		</td>
		<tr>
			<td  align="center"><input type="button" value="MURDER Import" style="color:#ff0000" onclick="if(confirm('Deleting an import will also delete all the leads for the import.\nAre you SURE you want to DELETE this import and its leads?')){deleteImport();}" title="Delete all leads in the import AND delete the import record itself"></td>
			<td  align="center">
				<?/**<input type="button" value="Move Leads/Combine">**/?>
				&nbsp;

			</td>
			<td  align="center"><input type="button" value="Empty Leads" style="color:#ff0000" onclick="if(confirm('Are you sure you want to PURGE ALL LEADS for this import?')){purgeImport();}" title="This will remove all leads for the import, but leave the import intact"></td>
		</tr>

		</form>
		</table><?

	}


	function generateReport($row){

		$last_called = intval($_REQUEST['last_called_days']);

		$tz = trim($_REQUEST['timezone']);

		$state = trim($_REQUEST['state']);


		switch($_REQUEST['report_type']){
		default:
		case 'total':
			$report_name = "Total Report";
			break;
		case 'state_breakdown':

			$report_name = "State Breakdown";

			break;
		case 'tz_breakdown':


			$report_name = "Timezone Breakdown";

			break;
		}






		?><table border="0" width="100%" height="100%">
		<tr>
			<td align="center"><table border="0" align="center">
			<tr>
				<td colspan="2" height="30" class="big bl">Import #<?=$row['id'].' - '.$row['name'].' : '.$report_name?></td>
			</tr><?

			//if($last_called > 0){
				?><tr>
					<th align="left">Last Called Days:</th>
					<td><?

						if($last_called > 0)	echo $last_called;
						else					echo '[All]';

					?></td>
				</tr><?
			//}


			?><tr>
				<th align="left">Timezone:</th>
				<td><?

					if($tz)	echo $this->getTZName($tz);
					else	echo "[All]";

				?></td>
			</tr><?

			//if($state){
				?><tr>
					<th align="left">State:</th>
					<td><?

						if($state)	echo $state;
						else		echo '[All]';

					?></td>
				</tr><?
			//}


			$where = "WHERE leads.`import_id`='".$row['id']."' ";

			if($tz){

				$where .= " AND leads.`GMT_offset`='".mysqli_real_escape_string($_SESSION['db'],$tz)."' ";

			}

			if($state){

				$where .= " AND leads.`state`='".mysqli_real_escape_string($_SESSION['db'],$state)."' ";

			}



			switch($_REQUEST['report_type']){
			default:
			case 'total':
				// GENERATE REPORT

				if($last_called > 0){

					$days_offset_time = time() - ($last_called * 86400);

					$sql = "SELECT COUNT(DISTINCT(`leads`.`phone`)) FROM `leads` ".
										" LEFT JOIN `leads_pulls` ON `leads`.phone=`leads_pulls`.phone ".
										$where .
										"AND (`leads_pulls`.`time` IS NULL OR `leads_pulls`.`time` < '".$days_offset_time."')";

//	echo $sql;

					list($total) = queryROW($sql
									//" AND (`leads_pulls`.phone IS NULL) "

									);

				}else{

					$sql = "SELECT COUNT(`leads`.`phone`) FROM `leads` ".$where;

//	echo $sql;
					list($total) = queryROW($sql);
				}


				?><tr>
					<td class="big" colspan="2" width="200" height="50" align="center">

						Count: <?=number_format($total)?>

					</td>
				</tr><?




				break;
			case 'state_breakdown':

//				if($last_called > 0){
//
//					$days_offset_time = time() - ($last_called * 86400);
//
//					$sql = "SELECT DISTINCT(state) AS state FROM `leads` ".
//										" LEFT JOIN `leads_pulls` ON `leads`.phone=`leads_pulls`.phone ".
//										$where .
//										" AND (`leads_pulls`.`time` IS NULL OR `leads_pulls`.`time` < '".$days_offset_time."')";
//	//echo $sql;
//					$res = query($sql, 1);
//
//
//				}else{
//
//					$sql = "SELECT DISTINCT(state) AS state FROM `leads` ".$where;
//					$res = query($sql, 1);
////	echo $sql;
//				}


				$state_stack = array();
				$multi_stack = array();

				if($state){

					$tmpstack = array();
					$re2 = query("SELECT state_short,timezone FROM state_timezones WHERE `enabled`='yes' AND `state_short`='".mysqli_real_escape_string($_SESSION['db'],$state)."' ORDER BY `state_short` ASC");
					while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

						if(in_array($r2['state_short'], $tmpstack)){

							$state_stack[] = array('state'=>$r2['state_short'], 'timezone'=>$r2['timezone']);

							$multi_stack[] = $r2['state_short'];

						}else{
							$tmpstack[] = $r2['state_short'];
							$state_stack[] = array('state'=>$r2['state_short'], 'timezone'=>$r2['timezone']);
						}
					}





				}else if($tz){

					$tzstr = 'GMT'.(  (strpos($tz, "-") > -1)?"":"+" ) .str_replace(".", ":", $tz).'0';

					$re2 = query("SELECT state_short,timezone FROM state_timezones WHERE `timezone`='".mysqli_real_escape_string($_SESSION['db'],$tzstr)."'AND `enabled`='yes' ORDER BY `state_short` ASC ");
					while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){
						$state_stack[] = array('state'=>$r2['state_short'], 'timezone'=>$r2['timezone']);
					}
				}else{
					$tmpstack = array();
					$re2 = query("SELECT state_short,timezone FROM state_timezones WHERE `enabled`='yes' ORDER BY `state_short` ASC");
					while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

						if(in_array($r2['state_short'], $tmpstack)){

							$state_stack[] = array('state'=>$r2['state_short'], 'timezone'=>$r2['timezone']);

							$multi_stack[] = $r2['state_short'];

						}else{
							$tmpstack[] = $r2['state_short'];
							$state_stack[] = array('state'=>$r2['state_short'], 'timezone'=>$r2['timezone']);
						}
					}
				}



				?><tr>
					<td colspan="2">

						<table width="100%" border="0">
						<tr>
							<th class="row2">State</th>
							<th class="row2" align="right">Count</th>
						</tr><?

						$color = 0;
						$totalcnt = 0;
						foreach($state_stack as $curstate){
							$class = 'row'.($color++%2);
							?><tr>
								<th class="<?=$class?>"><?

								$tzstuff = "";

								if(is_array($multi_stack) && in_array($curstate['state'], $multi_stack)){

									echo $curstate['state'].' ('.$curstate['timezone'].')';

									$tmptz = str_replace(":",".",$curstate['timezone']);
									$tmptz = str_replace("GMT","",$tmptz);
									$tmptz = str_replace("+","",$tmptz);

									$tmptz = substr($tmptz,0,strlen($tmptz) - 1);

									//$tmptz = floatval($tmptz);

									$tzstuff = " AND l.GMT_offset='".mysqli_real_escape_string($_SESSION['db'],$tmptz)."' ";

								}else{
									echo $curstate['state'];
								}

								?></th>
								<td class="<?=$class?>" align="right"><?

									if($last_called > 0){

										$days_offset_time = time() - ($last_called * 86400);

										$sql = "SELECT COUNT(DISTINCT(l.`phone`)) FROM `leads` l ".
													" LEFT JOIN `leads_pulls` r ON l.phone=r.phone ".
													$where .
													$tzstuff.
													" AND (r.`time` IS NULL OR r.`time` < '".$days_offset_time."') ".
													" AND l.`state`='".mysqli_real_escape_string($_SESSION['db'],$curstate['state'])."' ";

						//echo $sql;

										list($total) = queryROW($sql);

									}else{

										$sql = "SELECT COUNT(l.`phone`) FROM `leads` l ".$where.
												$tzstuff.
												" AND l.`state`='".mysqli_real_escape_string($_SESSION['db'],$curstate['state'])."' ";

						//echo $sql;
										list($total) = queryROW($sql);
									}

									$totalcnt += $total;

									echo number_format($total);

								?></td>
							</tr><?

						}

						?><tr>
							<th class="big" style="border-top:1px solid #000">States Total:<br />(Enabled only)</th>
							<td class="big" style="border-top:1px solid #000" align="right"><?

								echo number_format($totalcnt);

							?></td>
						</tr>
						</table>
					</td>
				</tr><?



				break;
			case 'tz_breakdown':

				$tz_arr = array(
					array(
						'name'=>'Eastern',
						'offset'=>'-5.0'
						),
					array(
						'name'=>'Central',
						'offset'=>'-6.0'
						),
					array(
						'name'=>'Mountain',
						'offset'=>'-7.0'
						),
					array(
						'name'=>'Pacific',
						'offset'=>'-8.0'
						),
				);



				?><tr>
					<td colspan="2">

						<table width="100%" border="0">
						<tr>
							<th class="row2">Timezone:</th>
							<th class="row2" align="right">Count</th>
						</tr><?

						$totalcnt = 0;

						foreach($tz_arr as $tmptz){

							?><tr>
								<th align="left"><?=$tmptz['name']?></th>
								<td align="right"><?

									if($last_called > 0){

										$days_offset_time = time() - ($last_called * 86400);

										$sql = "SELECT COUNT(DISTINCT(`leads`.`phone`)) FROM `leads` ".
													" LEFT JOIN `leads_pulls` ON `leads`.`phone`=`leads_pulls`.`phone` ".
													$where .
													" AND (`leads_pulls`.`time` IS NULL OR `leads_pulls`.`time` < '".$days_offset_time."') ".
													" AND `leads`.`GMT_offset`='".mysqli_real_escape_string($_SESSION['db'],$tmptz['offset'])."' ";

						echo "Last CALL: ".$sql;

										list($total) = queryROW($sql);

									}else{

										$sql = "SELECT COUNT(`leads`.`phone`) FROM `leads` ".$where.
												" AND `leads`.`GMT_offset`='".mysqli_real_escape_string($_SESSION['db'],$tmptz['offset'])."' ";

						echo "ALL: ".$sql;
										list($total) = queryROW($sql);
									}

									$totalcnt += $total;

									echo number_format($total);


								?></td>
							</tr><?

						}

						?><tr>
							<th class="big" style="border-top:1px solid #000">Timezones Total:</th>
							<td class="big" style="border-top:1px solid #000" align="right"><?

								echo number_format($totalcnt);

							?></td>
						</tr>
						</table>
					</td>
				</tr><?



				break;
			}


			?></table></td>
		</tr>
		</table><?

	}



	function getTZName($tz){

		switch($tz){
		default:
			return $tz;
		case "-5.0": return 'Eastern';
		case "-6.0": return 'Central';
		case "-7.0": return 'Mountain';
		case "-8.0": return 'Pacific';
		}
	}




	function makeReportGUI($row){


		?><script>

		function generateCountReport(frm){

			//var type = frm.report_type.value;//$('#report_type').val();

			var win = window.open("about:blank", 'ImportReportWin<?=$row['id']?>'); //

			frm.submit();
		}

		</script>

		<form method="POST" action="index.php?area=list_tools&tool=view_imports&view_import=<?=$row['id']?>&section=reports&printable=1&no_script=1&force_scripts=1" autocomplete="off" target="ImportReportWin<?=$row['id']?>" onsubmit="generateCountReport(this);return false">

			<input type="hidden" id="import_reports" name="import_reports" value="<?=$row['id']?>" >


		<table border="0" width="100%" align="center">
		<tr>
			<td colspan="2" class="big bl">

				Count Leads - <?=number_format($row['current_lead_count'])?> Leads

			</td>
		</tr>
		<tr>
			<th height="30">Report Type:</th>
			<td>
				<select name="report_type" id="report_type">

					<option value="total">Total Count</option>
					<option value="state_breakdown">Breakdown By State</option>
					<option value="tz_breakdown">Breakdown By Timezone</option>
				</select>

			</td>
		</tr>
		<tr>
			<th>
				Last Called Days:<br />
				(0 is ALL)
			</th>
			<td>

				<input type="text" name="last_called_days" id="last_called_days" size="5" value="0" onkeyup="this.value = this.value.replace(/[^0-9]/g, '');" />

			</td>
		</tr>
		<tr>
			<th>Timezone:</th>
			<td>
				<select name="timezone">
					<option value="">[All Timezones]</option>
					<option value="-5.0">Eastern</option>
					<option value="-6.0">Central</option>
					<option value="-7.0">Mountain</option>
					<option value="-8.0">Pacific</option>
				</select>
			</td>
		</tr>
		<tr>
			<th>State:</th>
			<td>
				<select name="state" id="state">

					<OPTION value="">[All States]</OPTION>

					<OPTION value="AL"<?=( $row['state'] == 'AL')?" SELECTED ":""?>>Alabama</OPTION>
					<OPTION value="AK"<?=( $row['state'] == 'AK')?" SELECTED ":""?>>Alaska</OPTION>
					<OPTION value="AZ"<?=( $row['state'] == 'AZ')?" SELECTED ":""?>>Arizona</OPTION>
					<OPTION value="AR"<?=( $row['state'] == 'AR')?" SELECTED ":""?>>Arkansas</OPTION>
					<OPTION value="CA"<?=( $row['state'] == 'CA')?" SELECTED ":""?>>California</OPTION>
					<OPTION value="CO"<?=( $row['state'] == 'CO')?" SELECTED ":""?>>Colorado</OPTION>
					<OPTION value="CT"<?=( $row['state'] == 'CT')?" SELECTED ":""?>>Connecticut</OPTION>
					<OPTION value="DE"<?=( $row['state'] == 'DE')?" SELECTED ":""?>>Delaware</OPTION>
					<OPTION value="FL"<?=( $row['state'] == 'FL')?" SELECTED ":""?>>Florida</OPTION>
					<OPTION value="GA"<?=( $row['state'] == 'GA')?" SELECTED ":""?>>Georgia</OPTION>
					<OPTION value="HI"<?=( $row['state'] == 'HI')?" SELECTED ":""?>>Hawaii</OPTION>
					<OPTION value="ID"<?=( $row['state'] == 'ID')?" SELECTED ":""?>>Idaho</OPTION>
					<OPTION value="IL"<?=( $row['state'] == 'IL')?" SELECTED ":""?>>Illinois</OPTION>
					<OPTION value="IN"<?=( $row['state'] == 'IN')?" SELECTED ":""?>>Indiana</OPTION>
					<OPTION value="IA"<?=( $row['state'] == 'IA')?" SELECTED ":""?>>Iowa</OPTION>
					<OPTION value="KS"<?=( $row['state'] == 'KS')?" SELECTED ":""?>>Kansas</OPTION>
					<OPTION value="KY"<?=( $row['state'] == 'KY')?" SELECTED ":""?>>Kentucky</OPTION>
					<OPTION value="LA"<?=( $row['state'] == 'LA')?" SELECTED ":""?>>Louisiana</OPTION>
					<OPTION value="ME"<?=( $row['state'] == 'ME')?" SELECTED ":""?>>Maine</OPTION>
					<OPTION value="MD"<?=( $row['state'] == 'MD')?" SELECTED ":""?>>Maryland</OPTION>
					<OPTION value="MA"<?=( $row['state'] == 'MA')?" SELECTED ":""?>>Massachusetts</OPTION>
					<OPTION value="MI"<?=( $row['state'] == 'MI')?" SELECTED ":""?>>Michigan</OPTION>
					<OPTION value="MN"<?=( $row['state'] == 'MN')?" SELECTED ":""?>>Minnesota</OPTION>
					<OPTION value="MS"<?=( $row['state'] == 'MS')?" SELECTED ":""?>>Mississippi</OPTION>
					<OPTION value="MO"<?=( $row['state'] == 'MO')?" SELECTED ":""?>>Missouri</OPTION>
					<OPTION value="MT"<?=( $row['state'] == 'MT')?" SELECTED ":""?>>Montana</OPTION>
					<OPTION value="NE"<?=( $row['state'] == 'NE')?" SELECTED ":""?>>Nebraska</OPTION>
					<OPTION value="NV"<?=( $row['state'] == 'NV')?" SELECTED ":""?>>Nevada</OPTION>
					<OPTION value="NH"<?=( $row['state'] == 'NH')?" SELECTED ":""?>>New Hampshire</OPTION>
					<OPTION value="NJ"<?=( $row['state'] == 'NJ')?" SELECTED ":""?>>New Jersey</OPTION>
					<OPTION value="NM"<?=( $row['state'] == 'NM')?" SELECTED ":""?>>New Mexico</OPTION>
					<OPTION value="NY"<?=( $row['state'] == 'NY')?" SELECTED ":""?>>New York</OPTION>
					<OPTION value="NC"<?=( $row['state'] == 'NC')?" SELECTED ":""?>>North Carolina</OPTION>
					<OPTION value="ND"<?=( $row['state'] == 'ND')?" SELECTED ":""?>>North Dakota</OPTION>
					<OPTION value="OH"<?=( $row['state'] == 'OH')?" SELECTED ":""?>>Ohio</OPTION>
					<OPTION value="OK"<?=( $row['state'] == 'OK')?" SELECTED ":""?>>Oklahoma</OPTION>
					<OPTION value="OR"<?=( $row['state'] == 'OR')?" SELECTED ":""?>>Oregon</OPTION>
					<OPTION value="PA"<?=( $row['state'] == 'PA')?" SELECTED ":""?>>Pennsylvania</OPTION>
					<OPTION value="RI"<?=( $row['state'] == 'RI')?" SELECTED ":""?>>Rhode Island</OPTION>
					<OPTION value="SC"<?=( $row['state'] == 'SC')?" SELECTED ":""?>>South Carolina</OPTION>
					<OPTION value="SD"<?=( $row['state'] == 'SD')?" SELECTED ":""?>>South Dakota</OPTION>
					<OPTION value="TN"<?=( $row['state'] == 'TN')?" SELECTED ":""?>>Tennessee</OPTION>
					<OPTION value="TX"<?=( $row['state'] == 'TX')?" SELECTED ":""?>>Texas</OPTION>
					<OPTION value="UT"<?=( $row['state'] == 'UT')?" SELECTED ":""?>>Utah</OPTION>
					<OPTION value="VT"<?=( $row['state'] == 'VT')?" SELECTED ":""?>>Vermont</OPTION>
					<OPTION value="VA"<?=( $row['state'] == 'VA')?" SELECTED ":""?>>Virginia</OPTION>
					<OPTION value="WA"<?=( $row['state'] == 'WA')?" SELECTED ":""?>>Washington</OPTION>
					<OPTION value="DC"<?=( $row['state'] == 'DC')?" SELECTED ":""?>>Washington, DC</OPTION>
					<OPTION value="WV"<?=( $row['state'] == 'WV')?" SELECTED ":""?>>West Virginia</OPTION>
					<OPTION value="WI"<?=( $row['state'] == 'WI')?" SELECTED ":""?>>Wisconsin</OPTION>
					<OPTION value="WY"<?=( $row['state'] == 'WY')?" SELECTED ":""?>>Wyoming</OPTION>

				</select>

			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">

				<input type="submit" value="Generate!" />

			</td>
		</tr>
		</form>
		</table><?
	}



	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadImports();return false;\">";

		return $var;
	}
}
