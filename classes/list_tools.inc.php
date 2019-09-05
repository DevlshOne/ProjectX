<?	/***************************************************************
	 *	List Tools - Replacement tools for Skynet
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['list_tools'] = new ListToolsClass;


class ListToolsClass{

	var $max_list_size = 120000; // MAX LEAD COUNT PER VICIDIAL LIST

	var $build_batch_size = 1000; // DEFAULT NUMBER OF RECORDS TO DO PER INSERT


	var $list_upload_folder = "/PX-List-Tool/raw_lists/";
	var $upload_api_script = "http://listtool.advancedtci.com/ajax.php";

	function ListToolsClass(){

		include_once($_SESSION['site_config']['basedir'].'/classes/JXMLP.inc.php');
		include_once($_SESSION['site_config']['basedir'].'/classes/lead_management.inc.php');

	}


	function handleFLOW(){

		if(!checkAccess('list_tools')){

			accessDenied("List Tools");
			return;

		}



		switch($_REQUEST['tool']){
		default:
		case 'load_list':


			$this->makeImportGUI();


			break;
		case 'dnc_tools':

			//echo "dnc tool here";

			$this->makeDNCGUI();


			break;
		case 'build_list':

			$this->makeBuildListGUI();

			break;

		case 'view_imports':

			include_once("classes/list_tool_imports.inc.php");
			$_SESSION['imports']->handleFLOW();
//			$this->makeListImports();

			break;

		case 'tasks':

			// PASS OFF TO TASKS FOR SUB-FLOW
			include_once("classes/list_tool_tasks.inc.php");
			$_SESSION['tasks']->handleFLOW();

			break;

		case 'manage_lists':

			// MANAGE VICIDIAL LISTS
			$this->makeViciTools();

			break;

		case 'vici_report':

			// MANAGE VICIDIAL LISTS
			include_once("classes/list_tools_vici_report.php");
			$_SESSION['vici_report']->handleFLOW();

			break;
		}


	}


	function generateAuthKey(){

		//$code = md5(  uniqid($_SESSION['user']['username'].$_SESSION['user']['id'].$_SESSION['user']['last_login'],1) );

		$code = generateRandomString(32);

		$dat = array(

			'code'=>$code,
			'time'=>time(),
			'ip_address'=>$_SERVER['REMOTE_ADDR'],
			'type'=>($_REQUEST['type'])?$_REQUEST['type']:null
		);

		// CONNECT TO LIST DB TO ADD KEY
		connectListDB();

		aadd($dat, 'auth_keys');


		// CONNECT BACK TO PX DB
		connectPXDB();

		return $code;
	}

	function addName($first, $last){

		connectListDB();

		return execSQL("INSERT IGNORE INTO `dnc_name_list`(`first_name`,`last_name`,`time_added`) VALUES ('".mysqli_real_escape_string($_SESSION['db'],$first)."','".mysqli_real_escape_string($_SESSION['db'],$last)."',UNIX_TIMESTAMP())");
	}


	function addNumber($num){

		connectListDB();

		return execSQL("INSERT IGNORE INTO `dnc_list`(`phone`,`time_added`) VALUES ('".mysqli_real_escape_string($_SESSION['db'],$num)."',UNIX_TIMESTAMP())");
	}


	function addCampaignNumber($num,$campaign='', $type="DNC"){

		$campaign = strtoupper($campaign);

		connectPXDB();

		return execSQL("INSERT IGNORE INTO `dnc_campaign_list`(`phone`,`campaign_code`,`dnc_type`,`time_added`) VALUES ".
					"('".mysqli_real_escape_string($_SESSION['db'],$num)."','".mysqli_real_escape_string($_SESSION['db'],$campaign)."',".
					"'".mysqli_real_escape_string($_SESSION['db'],$type)."',".
					"UNIX_TIMESTAMP())");
	}
	
	
	
	
	
	
	function convertCampaignToParents(){
		
		// load the parent campaign, and its subcampaigns
		// build SQL " IN () " query, to include all the sub campaigns for this parent
		connectPXDB();
		
		
		
		$res = query("SELECT * FROM campaign_parents WHERE deleted=0 ORDER BY `code` ASC", 1);
		
		echo date("g:i:s m/d/Y")." - Loading ".mysqli_num_rows($res)." campaign parents\n";
		
		$rowarr = array();
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			
			$rowarr[$row['id']] = array();
			$rowarr[$row['id']]['parent'] = $row;
			$rowarr[$row['id']]['children'] = array();
			
			$rowarr[$row['id']]['in_stack'] = array();
			
			$re2 = query("SELECT * FROM `campaigns` WHERE `status`='active' AND `parent_campaign_id`='".intval($row['id'])."' ");
			while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){
				
				$rowarr[$row['id']]['children'][] = $r2;
				
				$rowarr[$row['id']]['in_stack'][] = $r2['id'];
				
			}
		}
		

		
		echo date("g:i:s m/d/Y")." - Converting Children to Parent campaign ID...\n";
		
		// connect to list tool
		connectListDB();
		
		
		// update the lead_pulls table to point to the new parent ID, (WHERE campaign_id IN () from above)
		foreach($rowarr as $parent_campaign_id => $data){

			if(count($data['in_stack']) > 0){
				$sql = "UPDATE `leads_pulls` SET campaign_id=".intval($parent_campaign_id)." WHERE campaign_id IN (".implode(",", $data['in_stack'])."); ";
			
			
				echo $sql."\n";
				
				//execSQL($sql);
			}
		}
		
		echo date("g:i:s m/d/Y")." - DONE\n";
		
	}

	function removeNumber($num){

		connectListDB();

		return execSQL("DELETE FROM `dnc_list` WHERE `phone`= '".mysqli_real_escape_string($_SESSION['db'],$num)."'");
	}



	function removeCampaignNumber($num, $campaign, $type="DNC"){

		connectPXDB();

		// NOT ALLOWED TO REMOVE PERMA-DNC's
		if($type == "DNC" && $campaign == "[ALL]"){
			return -1;
		}

		return execSQL("DELETE FROM `dnc_campaign_list` WHERE `phone`= '".mysqli_real_escape_string($_SESSION['db'],$num)."' AND `campaign_code`='".mysqli_real_escape_string($_SESSION['db'],$campaign)."' AND `dnc_type`='".mysqli_real_escape_string($_SESSION['db'],$type)."' ");
	}

	function removeName($first, $last){

		connectListDB();

		return execSQL("DELETE FROM `dnc_name_list` WHERE `first_name`='".mysqli_real_escape_string($_SESSION['db'],$first)."' AND `last_name`='".mysqli_real_escape_string($_SESSION['db'],$last)."'");
	}


	function lookupNumber($num){

		connectListDB();

		$row = querySQL("SELECT * FROM `dnc_list` WHERE `phone`='".mysqli_real_escape_string($_SESSION['db'],$num)."'");

		return $row;
	}


	function lookupCampaignNumber($num, $campaign=''){

		connectPXDB();

		$campsql = ($campaign != '')?" AND `campaign_code`='".mysqli_real_escape_string($_SESSION['db'],$campaign)."' ":'';

		$res = query("SELECT * FROM `dnc_campaign_list` ".
						" WHERE `phone`='".mysqli_real_escape_string($_SESSION['db'],$num)."'".
						$campsql,	1 );
		$rowarr = array();
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$rowarr[] = $row;
		}

		return $rowarr;
	}

	function lookupName($first, $last){

		$first = trim($first);
		$last = trim($last);

		connectListDB();

		if($first && $last){

			$res = query("SELECT * FROM `dnc_name_list` WHERE `first_name`='".mysqli_real_escape_string($_SESSION['db'],$first)."' AND `last_name`='".mysqli_real_escape_string($_SESSION['db'],$last)."' ",1);

		}else if($first){
			$res = query("SELECT * FROM `dnc_name_list` WHERE `first_name`='".mysqli_real_escape_string($_SESSION['db'],$first)."'",1);
		}else{
			$res = query("SELECT * FROM `dnc_name_list` WHERE `last_name`='".mysqli_real_escape_string($_SESSION['db'],$last)."'",1);
		}

		return $res;
	}



	function getTimezones(){

		connectListDB();

		$rowarr = array();

		$res = query("SELECT * FROM `timezones` ", 1);


		while($row=mysqli_fetch_array($res, MYSQLI_ASSOC)){


			$rowarr[$row['name']] = $row['offset'];

		}


		return $rowarr;
	}

	function getTimezoneStates($tz_offset){

		$res = query("SELECT * FROM state_timezones WHERE `timezone`='".mysqli_real_escape_string($_SESSION['db'],$tz_offset)."' ORDER BY `state_short` ASC",1);

		$rowarr = array();
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			$rowarr[] = $row;
		}

		return $rowarr;
	}




	function makeImportDD($type,$id, $name, $sel, $onchange, $size, $blank_entry = false){


		$out = '<select name="'.$name.'" id="'.$id.'" ';

		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= ($size)?' size="'.$size.'" MULTIPLE ':'';

		$out .= '>';


		if($blank_entry){

			$out .= '<option value="">'.((is_string($blank_entry))?$blank_entry:"[All]").'</option>';

		}


		connectListDB();

		$res = query("SELECT * FROM `imports` WHERE `status`='active' ".

						(($type != null)?" AND `phone_type`='".mysqli_real_escape_string($_SESSION['db'],$type)."' ":"").

						" ORDER BY `time` ASC", 1);


		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


			$out .= '<option value="'.htmlentities($row['id']).'" ';

			$out .= ($sel == $row['id'])?' SELECTED ':'';

			$out .= '>'.date("m/d/Y",$row['time']).' - '.ucfirst($row['phone_type']).' - '.$row['name'].'</option>';


		}

		$out .= '</select>';


		// CONNECT BACK TO PX DB
		connectPXDB();

		return $out;

	}



	function makeViewTaskGUI(){

		?><div id="dialog-modal-view-task" title="View Task" class="nod">
		<?

		?>
		</div>

		<script>

			function displayViewTaskDialog(id){

				clearInterval(window.task_refresher);

				var objname = 'dialog-modal-view-task';



				$('#'+objname).dialog( "option", "title", 'Viewing Task #'+id );




				$('#'+objname).dialog("open");

				//$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=list_tools&tool=tasks&view_task="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			$("#dialog-modal-view-task").dialog({
				autoOpen: false,
				width: 700,
				height: 300,
				modal: false,
				draggable:true,
				resizable: true
			});

			$('#dialog-modal-view-task').on('dialogclose', function(event) {


				clearInterval(window.task_refresher);


 			});

		</script><?
	}


	function makeBuildListGUI(){

		// LOAD THE LISTS FROM ALL VICI CLUSTERS





		?><script>

		///var vici_cluster_ids = new Array();
		var vici_lists = new Array();

		var idx = 0;
		var y = 0;
		<?
		foreach($_SESSION['site_config']['db'] as $dbidx=>$db){

		////	echo 'vici_cluster_ids[idx] = parseInt(\''.$db['cluster_id'].'\');'."\n";

			// CONNECT TO VICI DB
			connectViciDB($dbidx);

			$res = query("SELECT `list_id`,`list_name`,`campaign_id`,`active` FROM `vicidial_lists` ORDER BY `list_id` ASC",1);


			echo 'vici_lists['.$db['cluster_id'].'] = new Array();'."\n";
			echo 'y = 0;'."\n";
			while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

				echo 'vici_lists['.$db['cluster_id'].'][y] = new Array();';

				echo 'vici_lists['.$db['cluster_id'].'][y]["id"] = '.$row['list_id'].';'."\n";
				echo 'vici_lists['.$db['cluster_id'].'][y]["name"] = "'.$row['list_name'].'";'."\n";
				echo 'vici_lists['.$db['cluster_id'].'][y]["campaign"] = "'.$row['campaign_id'].'";'."\n";

				echo 'y++;'."\n";


			}


			///echo 'idx++;'."\n";

		}

		// CONNECT BACK TO LIST DATABASE
		connectListDB();
		?>

			function buildListDD(cluster_id){



				var obj=getEl('target_list');
				var opt = obj.options;
			//	var catid=getEl('s_campaign_id').value;

				// Empty DD
				for(var x=0;x<opt.length;x++){obj.remove(x);}
				obj.options.length=0;

				var newopts = new Array();
//				newopts[0] = document.createElement("OPTION");
//
//				if(ie)	obj.add(newopts[0]);
//				else	obj.add(newopts[0],null);
//
//				newopts[0].innerText	= '';
//				newopts[0].value	= 0;


				if(!cluster_id){
				newopts[0] = document.createElement("OPTION");

				if(ie)	obj.add(newopts[0]);
				else	obj.add(newopts[0],null);

				newopts[0].innerText	= '[Pick a cluster first]';
				newopts[0].value	= 0;
					return;
				}

				var curid=0;
				for(x=0;x < vici_lists[cluster_id].length;x++){
					//curid=item_id[x];
					curid=x;

					//alert(which+' '+item_name[curid]);

//					if(catid && item_cpgnid[curid] != catid){
//						continue;
//					}

					newopts[x] = document.createElement("OPTION");

					if(ie)	obj.add(newopts[x]);
					else	obj.add(newopts[x],null);

					newopts[x].value	= vici_lists[cluster_id][curid]["id"];


					if(ie)	newopts[x].innerText	= vici_lists[cluster_id][curid]["id"]+" - "+vici_lists[cluster_id][curid]["name"];
					else	newopts[x].innerHTML	= vici_lists[cluster_id][curid]["id"]+" - "+vici_lists[cluster_id][curid]["name"];

				//if(selid == vici_lists[cluster_id][curid]["id"])obj.value=vici_lists[cluster_id][curid]["id"];



				}


			}


			function toggleTZCell(tzoffset, way){

//alert('tz_cell_'+tzoffset+" = "+way);

				ieDisplay('tz_cell_'+tzoffset, way);

			}



			function generateBuildAuthKey(){

				$.post("ajax.php?mode=generate_auth_key&type=build_list",null,ninjaPostBuildForm);
			}



			function validateBuildField(name,value,frm){

				//alert(name+","+value);


				switch(name){
				default:

					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;

				case 'campaign_id':


					if(!value)return false;

					return true;


					break;
				case 'target_cluster_id':

					if(!value)return false;

					return true;

					break;
				case 'last_called_days':

					if(value < 0 || value > 3287181)return false;

					return true;

					break;

				}
				return true;
			}



			function checkBuildListForm(frm){



				var params = getFormValues(frm,'validateBuildField');


				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'campaign_id':

						alert("Please select a campaign to tag this build to.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;
					case 'target_cluster_id':

						alert("Please select a target cluster");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;

					case 'last_called_days':

						alert("Please enter a valid 'Last Called' Number.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;
					}

				// SUCCESS - POST AJAX TO SERVER
				}else{


					if(! $('#source_import_id').val() ){

						alert("Please select a source import.");
						return false;
					}

					if(! $('#target_list').val() ){

						alert("Please select a destination target list.");
						return false;
					}


					if(!hasOneChecked('tzselected-') ){
						alert("Please select a Timezone to continue");
						return false;
					}

					if(!hasStateChecked()){

						alert("Please select a state within the selected timezones to continue.");
						return false;
					}


					var lead_cnt = getTotalListSize();

					if(lead_cnt <= 0){
						alert("ERROR: Please enter a 'Max List Size' for each timezone");
						return false;
					}

					if(lead_cnt > <?=$this->max_list_size?>){
						alert("ERROR: Leads exceeds maximum count.\nPlease reduce the number of leads to under <?=$this->max_list_size?>");
						return false;
					}





					$('#source_import_ids').val( $('#source_import_id').val() );
					$('#dest_target_list_ids').val( $('#target_list').val() );

//return false;

//alert(params);
//alert(frm.action);
//return false;
					generateBuildAuthKey();




				}


				return false;
			}

			function ninjaPostBuildForm(authcode){

				var frm = getEl('ninjabuildform');
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

							$('#build_result_cell').html(msg);

						}
					});
			}

			function hasOneChecked(obj_basename){

				var obj=null;

				for(var x=0;(obj = getEl(obj_basename+x)) ;x++){

					if(obj.checked == true)return true;

				}
				return false;
			}


			function hasStateChecked(){

				//x-y

				var obj, subobj;

				for(var x=0;(obj = getEl('tzselected-'+x)) ;x++){

					if(obj.checked == true){

						for(var y=0;(subobj = getEl('statecheck-'+x+'-'+y)) ;y++){

							if(subobj.checked == true){
								return true;
							}
						}

					}

				}
				return false;
			}

			function getTotalListSize(){

				var obj = null;

				var cnt = 0;
				for(var x=0;(obj = getEl('tzselected-'+x)) ;x++){

					if(obj.checked == true){


						cnt += parseInt( $('#maxlistsize-'+x).val() );
					}
				}

				return cnt;
			}




			var state_areacodes = new Array();
			var state_zipcodes = new Array();

			function populateAreaCodes(tzidx){

				if(!getEl('filter_areacode-'+tzidx).checked){
					return;
				}


				var sobj = null;
				var curstate = null;

//				var ac_array = new Array();
//				var acp = 0; // POINTER FOR ac_array

				var obj = getEl('area_codes_'+tzidx);
				obj.options.length = 0;

				// FOR EACH STATE
				for(var x=tzidx,y=0;(sobj=getEl('statecheck-'+x+'-'+y)) != null;y++){

					// SKIP STATES NOT CHECKED
					if(!sobj.checked) continue;

					// GET THE STATE
					curstate = sobj.value;

					// GET THE AREA CODES
					for(var z=0;z < state_areacodes[curstate+'-'+x].length;z++){

						obj.options[z] = new Option(""+state_areacodes[curstate+'-'+x][z], ""+state_areacodes[curstate+'-'+x][z], true, true);

						//ac_array[acp++] = state_areacodes[curstate+'-'+x][z];

					}
				}

				// PUSH THEM ALL TO THE AREACODE DROPDOWN





//				for(var z=0;z < acp;z++){
//
//					obj.options[z] = new Option(""+ac_array[z], ""+ac_array[z], true, true);
//				}



			}



			function populateZipCodes(tzidx){

				if(!getEl('filter_zipcode-'+tzidx).checked){
					return;
				}

				var sobj = null;
				var curstate = null;

//				var ac_array = new Array();
//				var acp = 0; // POINTER FOR ac_array

				var obj = getEl('zip_codes_'+tzidx);
				obj.options.length = 0;

				// FOR EACH STATE
				for(var x=tzidx,y=0;(sobj=getEl('statecheck-'+x+'-'+y)) != null;y++){

					// SKIP STATES NOT CHECKED
					if(!sobj.checked) continue;

					// GET THE STATE
					curstate = sobj.value;

					// GET THE AREA CODES
					for(var z=0;z < state_zipcodes[curstate+'-'+x].length;z++){

						obj.options[z] = new Option(""+state_zipcodes[curstate+'-'+x][z], ""+state_zipcodes[curstate+'-'+x][z], true, true);

//						ac_array[acp++] = state_zipcodes[curstate+'-'+x][z];

					}
				}

				// PUSH THEM ALL TO THE AREACODE DROPDOWN





//				for(var z=0;z < acp;z++){
//
//					obj.options[z] = new Option(""+ac_array[z], ""+ac_array[z], true, true);
//				}



			}


			function toggleAreaCodeList(tzidx, way){

				ieDisplay('area_codes_'+tzidx, way);
			}

			function toggleZipCodeList(tzidx, way){

				ieDisplay('zipcode_filter_span_'+tzidx, way);
			}


			function extractZipcodeArray(tzidx, unselectallfirst){

				var txt = $('#customziplist_'+tzidx).val();

				var zarr = txt.split(/[^0-9]+/);

				if(unselectallfirst){
					$("#zip_codes_"+tzidx+" option:selected").removeAttr("selected");
				}

				$.each(zarr, function(i,e){
				    $("#zip_codes_"+tzidx+" option[value='" + e + "']").prop("selected", true);
				});

//				for(var x=0;x < zarr.length;x++){
//
//
//
//					alert("Zip extracted "+x+" "+zarr[x]);
//				}

			}


		</script><?


			$this->makeViewTaskGUI();


		?>
		<form id="ninjabuildform" method="POST" action="<?=$this->upload_api_script.'?mode=build_list'?>" onsubmit="return checkBuildListForm(this)" enctype="multipart/form-data" target="iframe_upload">

			<input type="hidden" name="auth_code" id="auth_code">

			<input type="hidden" name="source_import_ids" id="source_import_ids">

			<input type="hidden" name="dest_target_list_ids" id="dest_target_list_ids">


			<?/*<input type="hidden" name="batch_size" value="<?=$this->build_batch_size?>" />*/?>

		<table border="0" width="100%" class="lb" cellspacing="0" align="center">
		<tr>
			<td height="40" class="pad_left ui-widget-header" colspan="2">

				<table border="0" width="100%" >
				<tr>
					<td>
						Build new List
					</td>
				</tr>
				</table>
			</td>
		</tr>

		<tr>
			<td>

				<table border="0">
				<tr valign="top">
					<th style="padding-left:5px" align="left">Source:</th>
					<td colspan="2"><?


						echo $this->makeImportDD(null,'source_import_id','','',"", 5, "");


					?></td>
				</tr>
				<tr>
					<th style="padding-left:5px" align="left">Source:</th>
					<td colspan="2"><?

						//echo $_SESSION['campaigns']->makeDDByCode('campaign_id',$row['campaign_id'],'',"",'',0," AND px_hidden='no' AND verifier_mode='no' ");
						
						echo $_SESSION['cmpgn_parents']->makeCampaignParentDD('campaign_id',$row['campaign_id'],'', false);
						
						//echo $_SESSION['campaigns']->makeDD('campaign_id',$row['campaign_id'],'',"",'',0," AND px_hidden='no' AND verifier_mode='no' ");


					?><td>
				</tr>
				<tr>
					<td colspan="3" >&nbsp;</td>
				</tr>
				<tr valign="top">
					<th style="padding-left:5px" align="left">Destination:</th>
					<td><?=makeClusterDD('target_cluster_id', '', '', "buildListDD(this.value)", "[Select Cluster]")?></td>
					<td>

						<select id="target_list" size="10" MULTIPLE >

							<option value="">[Pick a cluster first]</option>

						</select>
					</td>
				</tr>

				<tr>
					<th style="padding-left:5px" height="30" align="left">Last Called Days:</th>
					<td colspan="2">

						<input type="text" name="last_called_days" id="last_called_days" size="5" value="90" onkeyup="this.value = this.value.replace(/[^0-9]/g, '');" />

						<input type="checkbox" name="check_cross_campaign" id="check_cross_campaign" value="1" />Check across all Campaigns (Fresh leads only)

					</td>
				</tr>

				<tr>

					<th colspan="3">


						<input type="checkbox" name="randomize" value="yes" CHECKED />Randomize the build (LITTLE BIT SLOWER)

					</th>
				</tr>

				<tr>
					<th style="padding-left:5px" height="30" align="left">Total Lead Count:</th>
					<td align="center" style="border-bottom:1px dotted #000" >

						<span style="font-size:14px;" id="total_lead_count_span">0</span>
					</td>

					<th>Batch Size (<a href="#" onclick="alert('Determines how many leads to work with at a time.\nExample: How many to inject into vici at a time, or write to a file at a time. ');return false">help?</a>):
					<select name="batch_size">
						<option value="100">100</option>
						<option value="500">500</option>
						<option value="1000" SELECTED >1000</option>
						<option value="2000">2000</option>
						<option value="3000">3000</option>
						<option value="5000">5000</option>
						<option value="10000">10000</option>

					</select></th>

				</tr>
				</table>

				</td>
				<td width="60%" style="border-left:1px solid #000">


						&nbsp;



				</td>
			</tr>
			<tr>
				<td colspan="2">

				<table border="0" width="100%">
				<tr>
				<?

				$timezones = $this->getTimezones();

				$tzp = 0;
				foreach($timezones as $tzname=>$tzoffset){

					?><th align="left" style="font-size:16px;border-bottom:1px solid #000;padding-left:5px">
						<input type="checkbox" id="tzselected-<?=$tzp?>" name="tzselected[<?=$tzp?>]" value="<?=$tzoffset?>" onclick="toggleTZCell(this.value, this.checked)" CHECKED >
						<?=htmlentities($tzname)?> - <?=$tzoffset?>
					</th><?
					$tzp++;

				}

				?>
				</tr>
				<tr valign="top">
				<?

				$x=0;

				foreach($timezones as $tzname=>$tzoffset){

					$states = $this->getTimezoneStates($tzoffset);
					$y=0;

					$statecnt = count($states);

					// EXTRACT the timezone numbers from the gmt offset, to put in decimal format
					$tzcode = preg_replace("/[^0-9-\:]/",'',$tzoffset);
					$tzcode = preg_replace("/\:/",'.', $tzcode);

					$tzcode = round($tzcode, 1);

					if(strlen($tzcode) < 3){

						$tzcode = $tzcode.'.0';

					}

					//-5.00 -6.00 -7.00 -8.00 (from "GMT-5:00", etc)
					//echo $tzcode."\n";

					?><td class="lb">
						<input type="hidden" name="tz_index[<?=$x?>]" value="<?=htmlentities($tzname)?>" />

						<table border="0" width="100%" id="tz_cell_<?=$tzoffset?>">
						<tr>
							<th height="30">Max List Size</th>
							<td>

								<input type="text" id="maxlistsize-<?=$x?>" name="max_list_size[<?=$x?>]" value="0" size="7" maxlength="6" onkeyup="this.value = this.value.replace(/[^0-9]/g, '');if(!this.value)this.value='0';" onchange="$('#total_lead_count_span').html(getTotalListSize())" onclick="if(this.value=='0'){this.select()}">
							</td>
							<td rowspan="<?=$statecnt+1?>" valign="top">

								<input type="checkbox" name="filter_areacode[<?=$x?>]" id="filter_areacode-<?=$x?>" value="Filter Areacode" onclick="populateAreaCodes(<?=$x?>);toggleAreaCodeList(<?=$x?>, this.checked)" />Filter Areacode<br />
								<br />

								<select class="nod" id="area_codes_<?=$x?>" name="area_codes[<?=$x?>][]" size="6" MULTIPLE style="width:100%;height:200px">



								</select>
								<br />
								<input type="checkbox" name="filter_zipcode[<?=$x?>]" id="filter_zipcode-<?=$x?>" value="Filter Zipcode" onclick="populateZipCodes(<?=$x?>);toggleZipCodeList(<?=$x?>, this.checked)" />Filter Zip Code<br />
								<br />

								<span id="zipcode_filter_span_<?=$x?>" class="nod">


									<select id="zip_codes_<?=$x?>" name="zip_codes[<?=$x?>][]" size="6" MULTIPLE style="width:100%;height:200px">



									</select><br />
									<br />
									Zip Filter/Search:<br />
									<textarea id="customziplist_<?=$x?>"></textarea><br />
									<input type="button" value="Apply filter" onclick="extractZipcodeArray(<?=$x?>,0)" style="font-size:10px" />
									<input type="button" value="Apply Exclusive filter" onclick="extractZipcodeArray(<?=$x?>,1)" style="font-size:10px" />

								</span>

							</td>
						</tr><?



						foreach($states as $state){

						?><script>

							if(!state_areacodes['<?=$state['state_short']?>-<?=$x?>']){

								state_areacodes['<?=$state['state_short']?>-<?=$x?>'] = new Array();

							}

							if(!state_zipcodes['<?=$state['state_short']?>-<?=$x?>']){

								state_zipcodes['<?=$state['state_short']?>-<?=$x?>'] = new Array();

							}

							<?

								$re2 = query("SELECT * FROM `state_areacodes_timezones` WHERE state_short='".mysqli_real_escape_string($_SESSION['db'],$state['state_short'])."' AND `timezone_gmt`='".mysqli_real_escape_string($_SESSION['db'],$tzoffset)."' ORDER BY `area_code` ASC ",1);
								$z=0;
								while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

									?>state_areacodes['<?=$state['state_short']?>-<?=$x?>'][<?=$z++?>] = '<?=$r2['area_code']?>';
									<?

								}


								$re2 = query("SELECT * FROM `zipcode_timezones` ".
										" WHERE state='".mysqli_real_escape_string($_SESSION['db'],$state['state_short'])."' ".
										" AND `timezone`='".mysqli_real_escape_string($_SESSION['db'],$tzcode)."' ".

										" ORDER BY `zipcode` ASC ",1);
								$z=0;
								while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){

									?>state_zipcodes['<?=$state['state_short']?>-<?=$x?>'][<?=$z++?>] = '<?=$r2['zipcode']?>';
									<?

								}



							?>


						</script>
						<tr>
							<td colspan="2" align="left" style="padding-left:10px">

								<input type="checkbox" id="statecheck-<?=$x?>-<?=$y++?>" name="states_chk[<?=$x?>][<?=$y?>]" value="<?=$state['state_short']?>" <?=($state['enabled'] == 'yes')?' CHECKED ':''?> onclick="populateAreaCodes(<?=$x?>);populateZipCodes(<?=$x?>);"/>
								<?=$state['state_short'].' - '.$state['state']?><br />

							</td>
						</tr><?

						}



						?></table>

					<script>
						populateAreaCodes(<?=$x?>);
						populateZipCodes(<?=$x?>);
					</script>

					</td><?
					$x++;
				}



				?>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center" id="build_result_cell">
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">

				<input type="submit" value="Build List">

			</td>
		</tr>

		</form>
		</table>
		<?


	}


	function makeDNCGUI(){


		?><script src="js/ajax_uploader.js"></script>
		<script>

		function lookupDNCNumber(num){

			if(num.length < 10 || num.length > 10){
				$('#find_dnc_results').html('<span style="background-color:#ff0000">Error: Must be 10 digits</span>');
				return;
			}

			$('#find_dnc_results').html('<img src="images/ajax-loader.gif" width="30" /> Searching');

			$.post("ajax.php?mode=dnc&action=lookup_number&value="+escape(num),null,handleDNCNumberLookup);

		}


		function lookupCampaignDNCNumber(num, campaign){

			if(num.length < 10 || num.length > 10){
				$('#find_campaign_dnc_results').html('<span style="background-color:#ff0000">Error: Must be 10 digits</span>');
				return;
			}

			$('#find_campaign_dnc_results').html('<img src="images/ajax-loader.gif" width="30" /> Searching');

			var campurl = (campaign != null && campaign.length > 0)?"&campaign="+escape(campaign):'';

			$.post("ajax.php?mode=dnc&action=lookup_campaign_number&value="+escape(num)+campurl,null,handleCampaignDNCNumberLookup);

		}



		function lookupDNCName(first,last){

			if(first.trim().length < 1 && last.trim().length < 1){
				$('#find_dnc_name_results').html('<span style="background-color:#ff0000">Error: Must provide first or last name.</span>');
				return;
			}

			$('#find_dnc_name_results').html('<img src="images/ajax-loader.gif" width="30" /> Searching');

			$.post("ajax.php?mode=dnc&action=lookup_name&first_name="+escape(first)+"&last_name="+escape(last),null,handleDNCNameLookup);

		}

		function handleDNCNameLookup(res){

			//alert(res);

			var linearr = res.split("\n");
			var html = "";

			if(parseInt(linearr[0]) >= 1){

				for(var x = 1;x < linearr.length;x++){

					var tmparr = linearr[x].split(":");

					var tmp2 = tmparr[0].split(",",2);

					html += '<span style="background-color:#00ff00">Found - '+tmparr[0]+" - "+((tmparr[1] != 'Permanent')?'Added '+tmparr[1]:tmparr[1])+'</span> '+
								'<a href="#" onclick="if(confirm(\'Do you really want to remove '+tmparr[0]+'\')){removeDNCName(\''+tmp2[1]+'\',\''+tmp2[0]+'\');}return false;">[REMOVE IT]</a>'+
							'<br />';

				}

			}else{

				var tmparr = res.split(":");

				html = '<span style="background-color:#ff0000">'+tmparr[1]+'</span>';

			}

			$('#find_dnc_name_results').html(html);

		}


		function handleCampaignDNCNumberLookup(res){


			var lines = res.split("\n");
			var html = '<table border="0" width="100%" >';

			var added_header = 0;
			var cssclass = '';

			for(var x=0;x < lines.length;x++){

				if(lines[x].trim().length < 1)continue;


				var tmparr = lines[x].split(":");

				if(tmparr.length < 2)continue;

				if(tmparr[0] == '1'){
					//alert(tmparr[2]);

					if(added_header <= 0){

						html += '<tr>'+
									'<th class="row2" align="left">Result</th>'+
									'<th class="row2" align="center">Type</th>'+
									'<th class="row2" align="center">Campaign</th>'+
									'<th class="row2" align="center">Added</th>'+
									'<th class="row2" align="left">&nbsp;</th>'+
								'</tr>';


					}

					added_header++;

					// FOR COLOR ALTERNATING CSS ROWS
					cssclass = 'row'+(added_header%2);

					html += '<tr><th class="'+cssclass+'" align="left"><span style="background-color:#00ff00">FOUND</span></th>'+
							'<th class="'+cssclass+'">'+tmparr[2]+'</th>'+
							'<th class="'+cssclass+'">'+tmparr[1]+'</th>'+
							'<th class="'+cssclass+'">'+tmparr[3]+'</th>'+
							((tmparr[1] =='[ALL]' && tmparr[2] == 'DNC')?
								'<td class="'+cssclass+'">Permanent</td>':
								'<td class="'+cssclass+'" align="right"><a href="#" onclick="if(confirm(\'Do you really want to remove \'+$(\'#find_campaign_dnc_number\').val()+\'?\')){'+
									'removeCampaignDNCNumber( $(\'#find_campaign_dnc_number\').val(), \''+tmparr[1]+'\', \''+tmparr[2]+'\');}return false;">[REMOVE IT]</a></td>'
							)+
							'</tr>';

//					html += '<span style="background-color:#00ff00">Found - '+tmparr[1]+' - Added '+tmparr[2]+' '+
//							((tmparr[1] != 'Permanent')?'<a href="#" onclick="if(confirm(\'Do you really want to remove \'+$(\'#find_campaign_dnc_number\').val()+\'?\')){removeCampaignDNCNumber( $(\'#find_campaign_dnc_number\').val() );}return false;">[REMOVE IT]</a>':'')+
//							'</span><br />';

				}else{

					html += '<tr><td>'+
							'<span style="background-color:#ff0000">Not Found - <a href="#" onclick="addCampaignDNCNumber( $(\'#find_campaign_dnc_number\').val() );return false;">[ADD IT]</a></span>'
							'</td></tr>';

				}


			}

			html += '</table>';

			$('#find_campaign_dnc_results').html(html);
		}

		function handleDNCNumberLookup(res){

			//alert(res);

			var tmparr = res.split(":");

			var html = "";

			if(tmparr[0] == '1'){
				//alert(tmparr[2]);

				html = '<span style="background-color:#00ff00">Found - '+((tmparr[1] != 'Permanent')?'Added '+tmparr[1]:tmparr[1])+((tmparr[2].trim() != '')?' State: '+tmparr[2]:'')+'<br />'+
						'<a href="#" onclick="if(confirm(\'Do you really want to remove \'+$(\'#find_dnc_number\').val()+\'?\')){removeDNCNumber( $(\'#find_dnc_number\').val() );}return false;">[REMOVE IT]</a>'+
						'</span>';

			}else{

				html = '<span style="background-color:#ff0000">Not Found - <a href="#" onclick="addDNCNumber( $(\'#find_dnc_number\').val() );return false;">[ADD IT]</a></span>';

			}

			$('#find_dnc_results').html(html);

		}

		function addDNCName(first,last){

			if(first.trim().length < 1 || last.trim().length < 1){
				alert("ERROR: Please provide the First and Last name.");
				return;
			}

			$('#add_dnc_name_results').html('<img src="images/ajax-loader.gif" width="30" /> Adding');

			$.post("ajax.php?mode=dnc&action=add_name&first_name="+escape(first)+"&last_name="+escape(last),null,handleDNCNameAdd);
		}

		function addDNCNumber(num){

			if(num.length < 10 || num.length > 10){
				alert("ERROR: Phone number must be 10 digits long");
				return;
			}

			$('#add_dnc_number_results').html('<img src="images/ajax-loader.gif" width="30" /> Adding');

			$.post("ajax.php?mode=dnc&action=add_number&value="+escape(num),null,handleDNCNumberAdd);
		}

		function addCampaignDNCNumber(num, campaign){

			if(num.length < 10 || num.length > 10){
				alert("ERROR: Phone number must be 10 digits long");
				return;
			}

			campaign = (campaign != null && campaign.length > 0)?campaign:'';

			if((campaign == '' || campaign.length < 3) && !$('#add_campaign_dnc_all').is(':checked')){
				alert("ERROR: Please enter a valid campaign, or select ALL option.");
				return;
			}

			if($('#add_campaign_dnc_all').is(':checked')){
				campaign = '[ALL]';
			}

			$('#add_campaign_dnc_number_results').html('<img src="images/ajax-loader.gif" width="30" /> Adding');

			$.post("ajax.php?mode=dnc&action=add_campaign_number&value="+escape(num)+"&campaign="+escape(campaign),null,handleCampaignDNCNumberAdd);
		}


		function removeDNCName(first,last){

			if(first.trim().length < 1 || last.trim().length < 1){
				alert("ERROR: Please provide the First and Last name.");
				return;
			}

			$('#find_dnc_name_results').html('<img src="images/ajax-loader.gif" width="30" /> Removing');

			$.post("ajax.php?mode=dnc&action=remove_name&first_name="+escape(first)+"&last_name="+escape(last),null,handleDNCNameRemove);
		}

		function handleDNCNameRemove(res){

			//alert(res);

			var tmparr = res.split(":");

			var html = "";

			if(tmparr[0] == '1'){

				html = '<span style="background-color:#00ff00">'+tmparr[1]+'</span>';

			}else{

				html = '<span style="background-color:#ff0000">'+tmparr[1]+'</span>';

			}

			$('#find_dnc_name_results').html(html);

		}


		function removeCampaignDNCNumber(num, camp, type){

			if(num.length < 10 || num.length > 10){
				alert("ERROR: Phone number must be 10 digits long");
				return;
			}

			$('#find_campaign_dnc_results').html('<img src="images/ajax-loader.gif" width="30" /> Removing');

			$.post("ajax.php?mode=dnc&action=remove_campaign_number&value="+escape(num)+"&campaign="+escape(camp)+"&dnc_type="+escape(type),null,handleCampaignDNCNumberRemove);
		}

		function removeDNCNumber(num){

			if(num.length < 10 || num.length > 10){
				alert("ERROR: Phone number must be 10 digits long");
				return;
			}

			$('#find_dnc_results').html('<img src="images/ajax-loader.gif" width="30" /> Removing');

			$.post("ajax.php?mode=dnc&action=remove_number&value="+escape(num),null,handleDNCNumberRemove);
		}

		function handleCampaignDNCNumberRemove(res){

			var tmparr = res.split(":");

			var html = "";

			if(tmparr[0] == '1'){

				html = '<span style="background-color:#00ff00">'+tmparr[1]+'</span>';


				setTimeout(function(){ lookupCampaignDNCNumber($('#find_campaign_dnc_number').val());}, 2000);

			}else{

				html = '<span style="background-color:#ff0000">'+tmparr[1]+'</span>';

			}

			$('#find_campaign_dnc_results').html(html);


		} // END handleCampaignDNCNumberRemove()


		function handleDNCNumberRemove(res){

			//alert(res);

			var tmparr = res.split(":");

			var html = "";

			if(tmparr[0] == '1'){

				html = '<span style="background-color:#00ff00">'+tmparr[1]+'</span>';

			}else{

				html = '<span style="background-color:#ff0000">'+tmparr[1]+'</span>';

			}

			$('#find_dnc_results').html(html);

		}

		function handleCampaignDNCNumberAdd(res){

			var tmparr = res.split(":");

			var html = "";

			if(tmparr[0] == '1'){

				html = '<span style="background-color:#00ff00">'+tmparr[1]+'</span>';

			}else{

				html = '<span style="background-color:#ff0000">'+tmparr[1]+'</span>';

			}

			$('#add_campaign_dnc_number_results').html(html);
		}

		function handleDNCNumberAdd(res){

			//alert(res);

			var tmparr = res.split(":");

			var html = "";

			if(tmparr[0] == '1'){

				html = '<span style="background-color:#00ff00">'+tmparr[1]+'</span>';

			}else{

				html = '<span style="background-color:#ff0000">'+tmparr[1]+'</span>';

			}

			$('#add_dnc_number_results').html(html);

		}



		function handleDNCNameAdd(res){

			//alert(res);

			var tmparr = res.split(":");

			var html = "";

			if(tmparr[0] == '1'){

				html = '<span style="background-color:#00ff00">'+tmparr[1]+'</span>';

			}else{

				html = '<span style="background-color:#ff0000">'+tmparr[1]+'</span>';

			}

			$('#add_dnc_name_results').html(html);

		}






		function checkImportDNCForm(frm){

			if(!frm.upload_dnc_file.value)return alert('Please select a file to import');

			// NINJA FORM SUBMIT
			//ninjaUploadList();
			generateDNCAuthKey();

			return false;
		}



		function generateDNCAuthKey(){

			$.post("ajax.php?mode=generate_auth_key&type=import_dnc",null,ninjaUploadDNCList);
		}



		function ninjaUploadDNCList(code){



			$('#upload_dnc_status_cell').html('<div style="font-size:16px;height:30px;">Preparing upload...</div>');

			$('#progress-wrp').show();

			var ninjafrm=getEl('ninjaimportdncform');
			ninjafrm.auth_code.value = code.trim();


    		var upload = new Upload(listUploadDNCSuccess,listUploadDNCFailed);

				//startKeepAlive();

			// execute upload
			upload.doUpload(ninjafrm,"upload_dnc_file");//file);


			// BLANK THE PAGE
	/**		getEl('iframe_dnc_upload').src = 'about:blank';

			$('#upload_dnc_status_cell').html('<div style="font-size:16px;height:40px;"><img src="images/ajax-loader.gif" border="0" />Uploading file...</div>');

			// SUBMIT HIDDEN FORM
			ninjafrm.submit();
	**/
		}


		function listUploadDNCFailed(res){

			var msg = "ERROR! Upload failed:\n"+res;

			$('#upload_dnc_status_cell').html(msg);

			alert(msg);
		}



		function listUploadDNCSuccess(res){ ///url, warning_messages){

		//	warning_messages = $.trim(warning_messages);

			$('#upload_dnc_status_cell').html('Success');

			var result = parseInt(res);

			if(result > 0){
				// TASK ID
				displayViewTaskDialog(result);
			}else{
				alert(res);
			}


			/*if(warning_messages){
				alert("Successfully uploaded file"+ ((warning_messages)?". However warnings were issued:\n"+warning_messages:".") );
			}*/

			//displayAddScriptDialog

		} // END listUploadDNCSuccess()

		</script><?


			$this->makeViewTaskGUI();


		?>
		<table border="0" width="100%" class="lb" cellspacing="0" align="center">
		<tr>
			<td height="40" class="pad_left ui-widget-header" colspan="4">

				<table border="0" width="100%" >
				<tr>
					<td>
						DNC Tools
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr valign="top">
			<td align="center" width="33%">

				<h1 align="center">DNC By Phone #</h1>

				<table border="0" width="300" height="120" class="lb">
				<tr>
					<td colspan="2" style="padding-left:5px;font-size:16px;border-bottom:1px solid #000" height="25">
						Lookup DNC Number
					</td>
				</tr>
				<tr>
					<th style="padding-left:5px" align="left">10 Digit Phone #:</th>
					<td><input type="text" id="find_dnc_number" name="find_dnc_number" size="14" maxlength="20" onkeyup="this.value = this.value.replace(/[^0-9]/g, '');this.value = this.value.substr(0,10);if(event.keyCode == 13){lookupDNCNumber($('#find_dnc_number').val() );}"></td>
				</tr>
				<tr>
					<td colspan="2" align="center" id="find_dnc_results">

					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">

						<input type="button" value="Find DNC Number" onclick="lookupDNCNumber($('#find_dnc_number').val() )" />

					</td>
				</tr>

				</table>

				<br />

				<table border="0" width="300" height="120" class="lb">
				<tr>
					<td colspan="2" style="padding-left:5px;font-size:16px;border-bottom:1px solid #000" height="25">
						Add DNC Number
					</td>
				</tr>
				<tr>
					<th style="padding-left:5px" align="left">10 Digit Phone #:</th>
					<td><input type="text" id="add_dnc_number" name="add_dnc_number" size="14" maxlength="20" onkeyup="this.value = this.value.replace(/[^0-9]/g, '');this.value = this.value.substr(0,10)"></td>
				</tr>
				<tr>
					<td colspan="2" align="center" id="add_dnc_number_results">

					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">

						<input type="button" value="Add DNC Number"  onclick="addDNCNumber( $('#add_dnc_number').val() )"/>

					</td>
				</tr>

				</table>
				<br />


				<div class="nod">
					<iframe id="iframe_dnc_upload" name="iframe_dnc_upload" width="1" height="1" frameborder="0" src="about:blank"></iframe>
				</div>



				<form id="ninjaimportdncform" method="POST" action="<?=$this->upload_api_script.'?mode=list_dnc_upload'?>" onsubmit="return checkImportDNCForm(this)" enctype="multipart/form-data" target="iframe_dnc_upload">

					<input type="hidden" name="importing_dnc_list">
					<input type="hidden" name="auth_code" id="auth_code">

				<table border="0" width="300" height="120" class="lb">
				<tr>
					<td colspan="2" style="padding-left:5px;font-size:16px;border-bottom:1px solid #000" height="25">
						Import DNC List
					</td>
				</tr>
				<tr>
					<th style="padding-left:5px;" align="left">CSV File:</th>
					<td><input type="file" name="upload_dnc_file" id="upload_dnc_file"></td>
				</tr>
				<tr>
					<td style="padding-left:5px;" colspan="2" align="center">

						<input type="checkbox" name="state_specific_dnc" value="1" onclick="if(this.checked){ieDisplay('state_dd_div',1);}else{ieDisplay('state_dd_div',0);}"> State Specific DNC

						<div id="state_dd_div" class="nod">
							<select name="dnc_state" id="dnc_state">

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

						</div>
					</td>
				</tr>
				<tr>
					<td height="30" align="center" colspan="2" id="upload_dnc_status_cell" style="font-size:14px"><?

					?></td>
				</tr>
				<tr>
					<td colspan="2" align="center">

						<input type="submit" value="Upload DNC List" />

					</td>
				</tr>
				<tr>

					<td colspan="2" align="center">


						<div id="progress-wrp" class="nod">
						    <div class="progress-bar"></div>
						    <div class="status">0%</div>
						</div>

					</td>
				</tr>

				</table>

				</form>

			</td>
			<td align="center" width="33%" height="200">





				<h1>DNC By Campaign</h1>


				<table border="0" width="350" height="120" class="lb">
				<tr>
					<td colspan="2" style="padding-left:5px;font-size:16px;border-bottom:1px solid #000" height="25">
						Lookup Campaign DNC
					</td>
				</tr>
				<tr>
					<th style="padding-left:5px" align="left">10 Digit Phone #:</th>
					<td><input type="text" id="find_campaign_dnc_number" name="find_campaign_dnc_number" size="14" maxlength="20" onkeyup="this.value = this.value.replace(/[^0-9]/g, '');this.value = this.value.substr(0,10);if(event.keyCode == 13){lookupCampaignDNCNumber($('#find_campaign_dnc_number').val() );}"></td>
				</tr>
				<tr>
					<td colspan="2" align="center" id="find_campaign_dnc_results">

					</td>
				</tr>

				<tr>
					<td colspan="2" align="center">

						<input type="button" value="Find Campaign DNC" onclick="lookupCampaignDNCNumber($('#find_campaign_dnc_number').val() );" />

					</td>
				</tr>

				</table>

				<br />

				<table border="0" width="350" height="120" class="lb">
				<tr>
					<td colspan="2" style="padding-left:5px;font-size:16px;border-bottom:1px solid #000" height="25">
						Add Campaign DNC
					</td>
				</tr>
				<tr>
					<th style="padding-left:5px" align="left">10 Digit Phone #:</th>
					<td><input type="text" id="add_campaign_dnc_number" name="add_campaign_dnc_number" size="14" maxlength="20" onkeyup="this.value = this.value.replace(/[^0-9]/g, '');this.value = this.value.substr(0,10)"></td>
				</tr>
				<tr>
					<th valign="top" style="padding-left:5px" align="left">Campaign:</th>
					<td>
						<input type="text" id="add_campaign_dnc_campaign" name="add_campaign_dnc_campaign" size="14" maxlength="20" onkeyup="this.value = this.value.replace(/[^a-zA-Z0-9_-]/g, '');">
						<br />
						<input type="checkbox" id="add_campaign_dnc_all" name="add_campaign_dnc_all" value="[ALL]" onclick="if(this.checked){$('#add_campaign_dnc_campaign').hide();}else{$('#add_campaign_dnc_campaign').show();}" />ALL Campaigns (Permanent)
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center" id="add_campaign_dnc_number_results">

					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">

						<input type="button" value="Add Campaign DNC Number"  onclick="addCampaignDNCNumber( $('#add_campaign_dnc_number').val(), $('#add_campaign_dnc_campaign').val() )"/>

					</td>
				</tr>

				</table>







			</td>
			<td align="center" width="33%">





				<h1>DNC By Full Name</h1>

				<table border="0" width="350" height="120" class="lb">
				<tr>
					<td colspan="2" style="padding-left:5px;font-size:16px;border-bottom:1px solid #000" height="25">
						Lookup DNC Name
					</td>
				</tr>
				<tr>
					<th style="padding-left:5px" align="left">First Name:</th>
					<td><input type="text" id="find_dnc_first_name" name="find_dnc_first_name" size="30" maxlength="30" onkeyup="this.value = this.value.toUpperCase();if(event.keyCode == 13){lookupDNCName($('#find_dnc_first_name').val(),$('#find_dnc_last_name').val());}"></td>
				</tr>
				<tr>
					<th style="padding-left:5px" align="left">Last Name:</th>
					<td><input type="text" id="find_dnc_last_name" name="find_dnc_last_name" size="30" maxlength="30" onkeyup="this.value = this.value.toUpperCase();if(event.keyCode == 13){lookupDNCName($('#find_dnc_first_name').val(),$('#find_dnc_last_name').val());}"></td>
				</tr>

				<tr>
					<td colspan="2" align="center" id="find_dnc_name_results">

					</td>
				</tr>

				<tr>
					<td colspan="2" align="center">

						<input type="button" value="Find DNC Number" onclick="lookupDNCName($('#find_dnc_first_name').val(),$('#find_dnc_last_name').val())" />

					</td>
				</tr>

				</table>

				<br />

				<table border="0" width="350" height="120" class="lb">
				<tr>
					<td colspan="2" style="padding-left:5px;font-size:16px;border-bottom:1px solid #000" height="25">
						Add DNC Name
					</td>
				</tr>
				<tr>
					<th style="padding-left:5px" align="left">First Name:</th>
					<td><input type="text" id="add_dnc_first_name" name="add_dnc_first_name" size="30" maxlength="30" onkeyup="this.value = this.value.toUpperCase();"></td>
				</tr>
				<tr>
					<th style="padding-left:5px" align="left">Last Name:</th>
					<td><input type="text" id="add_dnc_last_name" name="add_dnc_last_name" size="30" maxlength="30" onkeyup="this.value = this.value.toUpperCase();"></td>
				</tr>
				<tr>
					<td colspan="2" align="center" id="add_dnc_name_results">

					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">

						<input type="button" value="Add DNC Number" onclick="addDNCName($('#add_dnc_first_name').val(),$('#add_dnc_last_name').val())" />

					</td>
				</tr>

				</table>
			</td>
		</tr>
		</table><?



	}




	function makeImportGUI(){


		?><script src="js/ajax_uploader.js"></script>
		<script>

			var adv_toggle = 0;

			function toggleAdvanced(){
				adv_toggle = !adv_toggle;
				ieDisplay('advanced_row', adv_toggle);
			}

			function togImportMode(mode){

				var phty = $('#lead_type').val();

				//alert(phty);

				if(mode == 'new'){
					ieDisplay('new_import_tbl', 1);


					ieDisplay('existing_mobile_import_tbl', 0);
					ieDisplay('existing_land_import_tbl', 0);

				}else{
					ieDisplay('new_import_tbl',0);

					if(phty == 'mobile'){
						ieDisplay('existing_mobile_import_tbl', 1);
						ieDisplay('existing_land_import_tbl', 0);
					}else{
						ieDisplay('existing_mobile_import_tbl', 0);
						ieDisplay('existing_land_import_tbl', 1);

					}
				}
			}


			var keepaliveinterval;

			function startKeepAlive(){

			//	keepaliveinterval = window.setInterval(keepAlivePOST, 60000);

			}

			function stopKeepAlive(){
				try{
					clearInterval(keepaliveinterval);
				}catch(ex){}
			}

			function keepAlivePOST(){
				$.ajax({
					type: "POST",
					cache: false,
					url: 'keepalive.php'
				});
			}

			function checkImportForm(frm){

				if(frm.upload_mode.value != 'manual'){
					if(!frm.lead_file.value)return alert('Please select the lead list file to upload.');
				}else{

					if(!frm.manual_file_path.value)return alert('Please enter the full path on the list server, to the CSV file.');

				}

				if(!frm.import_mode.value)return alert('Please select an import mode.');

				var phty = $('#lead_type').val();

				// IF NEW MODE/ADDING IMPORT LIST
				if(frm.import_mode.value == "new"){
					if(!frm.import_name.value || frm.import_name.value == "[List Name Here]"){

						return recheck("Please provide a name for this list", frm.import_name);
					}

				// EXISTING
				}else{

					if(phty == 'mobile'){
						if(!frm.existing_mobile_import_id.value)return alert("Error: please select the existing list to import into, or create it a new import list");
					}else{
						if(!frm.existing_land_import_id.value)return alert("Error: please select the existing list to import into, or create it a new import list");
					}

				}


				// NINJA FORM SUBMIT
				//ninjaUploadList();
				generateAuthKey();

				return false;
			}



			function generateAuthKey(){

				$.post("ajax.php?mode=generate_auth_key&type=import_list",null,ninjaUploadList);
			}



			function ninjaUploadList(code){

				if(code == "Not Logged in"){
					window.location = "index.php";
					return;
				}

				$('#progress-wrp').show();

				$('#upload_status_cell').html('<div style="font-size:16px;height:30px;">Preparing upload...</div>');

				var ninjafrm=getEl('ninjaimportform');

				ninjafrm.auth_code.value = code.trim();





				//var file = $('#lead_file')[0].files[0];
    			var upload = new Upload(listUploadSuccess,listUploadFailed);//file);

    			// maby check size or type here with upload.getSize() and upload.getType()

				//startKeepAlive();

			    // execute upload
			    upload.doUpload(ninjafrm, ((ninjafrm.upload_mode.value == "manual")?"":"lead_file"));//file);


/**
				// BLANK THE PAGE
				getEl('iframe_upload').src = 'about:blank';

				$('#upload_status_cell').html('<div style="font-size:16px;height:40px;"><img src="images/ajax-loader.gif" border="0" />Uploading file...</div>');

				startKeepAlive();

				// SUBMIT HIDDEN FORM
				ninjafrm.submit();

				**/
			}


			function listUploadFailed(res){

				stopKeepAlive();

				var msg = "ERROR! Upload failed:\n"+res;

				$('#upload_status_cell').html(res);

				alert(res);
			}

			function listUploadSuccess(res){//url, warning_messages){

				stopKeepAlive();

			//	warning_messages = $.trim(warning_messages);

				$('#upload_status_cell').html('Success');


				var result = parseInt(res);

				if(result > 0){
					// TASK ID
					displayViewTaskDialog(result);
				}else{
					alert(res);
				}


//				if(warning_messages){
//					alert("Successfully uploaded file"+ ((warning_messages)?". However warnings were issued:\n"+warning_messages:".") );
//				}

				//displayAddScriptDialog
			}


			function toggleUploadMode(way){

				if(way == 'manual'){
					ieDisplay('form_upload', 0);
					ieDisplay('manual_upload', 1);
				}else{
					ieDisplay('form_upload', 1);
					ieDisplay('manual_upload', 0);
				}


			}

		</script><?


		$this->makeViewTaskGUI();


		?>
		<div class="nod">
			<iframe id="iframe_upload" name="iframe_upload" width="1" height="1" frameborder="0" src="about:blank"></iframe>
		</div>

		<form id="ninjaimportform" method="POST" action="<?=$this->upload_api_script.'?mode=list_upload'?>" onsubmit="return checkImportForm(this)" enctype="multipart/form-data" target="iframe_upload">

			<input type="hidden" name="importing_list">
			<input type="hidden" name="auth_code" id="auth_code">

		<table border="0" width="500" class="lb" cellspacing="0" align="center">
		<tr>
			<td height="40" class="pad_left ui-widget-header" colspan="2">

				<table border="0" width="100%" >
				<tr>
					<td>
						Import new Leads
					</td>
				</tr>
				</table>
			</td>
		</tr>

		<tr>
			<td colspan="2" align="center">
				<table border="0">
				<tr>
					<th align="left">Upload Mode:</th>
					<td><select name="upload_mode" onchange="toggleUploadMode(this.value)">

						<option value="form">Web Form Upload</option>
						<option value="manual">Manually Upload</option>

					</select></td>
				</tr>
				<tr>
					<td colspan="2"><table border="0" width="100%" id="form_upload">
					<tr>
						<th align="left">Lead File:</th>
						<td height="30"><input type="file" id="lead_file" name="lead_file" /></td>
					</tr>
					</table></td>
				</tr>
				<tr>
					<td colspan="2"><table border="0" width="100%" id="manual_upload" class="nod">
					<tr>
						<th align="left">File Path:</th>
						<td height="30"><input type="text" name="manual_file_path" size="40" /></td>
					</tr>
					</table></td>
				</tr>
				<tr>
					<th width="100" align="left">Type:</th>
					<td height="30"><select name="lead_type" id="lead_type" onchange="togImportMode(  (( $('#import_mode_existing').is(':checked'))?'existing':'new') )">

						<option value="mobile">Mobile</option>
						<option value="land">Land</option>

					</select></td>
				</tr>

				<tr>
					<td colspan="2">

						<table border="0">
						<tr>
							<td align="left"><input type="radio" id="import_mode_new" name="import_mode" value="new" onclick="togImportMode(this.value)"></td>
							<th align="left">Create a new import list</th>
						</tr>
						<tr>
							<td align="left"><input type="radio" id="import_mode_existing" name="import_mode" value="existing" onclick="togImportMode(this.value)" ></td>
							<th align="left">Use Existing import list</th>
						</tr>
						</table>


						<table border="0" id="existing_mobile_import_tbl" class="nod">
						<tr>
							<th align="left">Existing Import:</th>
							<td height="30"><?

								echo $this->makeImportDD('mobile','existing_mobile_import_id','existing_mobile_import_id','',"",null,"");

							?></td>
						</tr>
						</table>
						<table border="0" id="existing_land_import_tbl" class="nod">
						<tr>
							<th align="left">Existing Import:</th>
							<td height="30"><?

								echo $this->makeImportDD('land','existing_land_import_id','existing_land_import_id','',"",null,"");

							?></td>
						</tr>
						</table>

						<table border="0" id="new_import_tbl" class="nod">
						<tr>
							<th align="left">Import Date:</th>
							<td height="30"><?=date("m/d/Y")?></td>
						</tr>

						<tr>
							<th align="left">Import name:</th>
							<td height="30"><input type="text" id="import_name" name="import_name" value="[List Name Here]"></td>
						</tr>
						<tr>
							<th align="left">Import description:</th>
							<td><textarea name="import_description" rows="2" cols="30"></textarea></td>
						</tr>

						</table>
					</td>
				</tr>


				<tr>
					<td colspan="2" align="center" height="30">

						<a href="#" onclick="toggleAdvanced();return false"><u>Advanced Options</u></a>

					</td>
				</tr>
				<tr>
					<td colspan="2">


						<table border="0" width="300" id="advanced_row" class="nod">
						<tr>
							<th width="100" align="left">Batch Size:</th>
							<td><select name="batch_size">

								<option value="50">50</option>
								<option value="100">100</option>
								<option value="500">500</option>
								<option value="1000" SELECTED >1000</option>
								<option value="2000">2000</option>
								<option value="3000">3000</option>

							</select></td>

						</tr>
						<tr>
							<th width="100" align="left">Delimiter:</th>
							<td>
								<select name="delimiter">
									<option value="comma">Comma Seperated
									<option value="tab">TAB Seperated
								</select>
							</td>
						</tr>
						<tr>
							<th width="100" align="left">Fields Enclosed By:</th>
							<td><input size="3" name="enclosed" value='"' /></td>
						</tr>
						</table>

					</td>
				</tr>



				<tr>
					<td height="30" align="center" colspan="2" id="upload_status_cell" style="font-size:14px"><?

					?></td>
				</tr>

				<tr>
					<td colspan="2" align="center">

						<input type="submit" value="Upload &amp; Import" />

					</td>
				</tr>
				<tr>

					<td colspan="2" align="center">


					<div id="progress-wrp" class="nod">
					    <div class="progress-bar"></div>
					    <div class="status">0%</div>
					</div>

					</td>
				</tr>
				</table>
			</td>
		</tr>

		</form>
		</table><?
	}










	function makeListImports(){

/********
		connectListDB();

		$res = query("SELECT * FROM `imports` WHERE `status`='active' ".

//						(($type != null)?" AND `phone_type`='".mysqli_real_escape_string($_SESSION['db'],$type)."' ":"").

						" ORDER BY `time` ASC", 1);

		?><table border="0" width="100%" class="lb" cellspacing="0" align="center">
		<tr>
			<td height="40" class="pad_left ui-widget-header" colspan="2">

				<table border="0" width="100%" >
				<tr>
					<td>
						Imports
					</td>
				</tr>
				</table>
			</td>
		</tr>

		<tr>
			<td colspan="2">
				<table border="0" width="500">
				<tr>
					<th class="row2">ID</th>
					<th class="row2">Type</th>
					<th class="row2" align="left">Name</th>
					<th class="row2" align="right">Count</th>
					<th class="row2" align="right">Last 30 Count</th>
				</tr><?

				while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

					?><tr>
						<td align="center"><?=$row['id']?></td>
						<td align="center"><?=$row['phone_type']?></td>
						<td align="left"><?=$row['name']?></td>
						<td align="right"><?

							list($cnt) = queryROW("SELECT COUNT(`phone`) FROM `leads` WHERE `import_id`='".$row['id']."'");

							echo number_format($cnt);
						?></td>
						<td align="right"><?
							$pulltime = time() - 2592000; // 30 days
							list($cnt) = queryROW("SELECT COUNT(`phone`) FROM `leads` WHERE `import_id`='".$row['id']."' AND `last_pull` <= '$pulltime'");

							echo number_format($cnt);
						?></td>
					</tr><?
				}

				?></table><?

			?><td>
		</tr>
		</table><?
*******/
	}


	function makeViciTools(){

		?><script>

				var vici_lists = new Array();
				var vici_loaded_status = new Array();
		var idx = 0;
		var y = 0;
		<?
		foreach($_SESSION['site_config']['db'] as $dbidx=>$db){

		////	echo 'vici_cluster_ids[idx] = parseInt(\''.$db['cluster_id'].'\');'."\n";

			// CONNECT TO VICI DB
			connectViciDB($dbidx);

			$res = query("SELECT `list_id`,`list_name`,`campaign_id`,`active` FROM `vicidial_lists` ORDER BY `list_id` ASC",1);


			echo 'vici_lists['.$db['cluster_id'].'] = new Array();'."\n";
			echo 'vici_loaded_status['.$db['cluster_id'].'] = false;'."\n";

			echo 'y = 0;'."\n";
			while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

//				$statestr = '';
//				$zz = 0;
//				$stateres = query("SELECT DISTINCT(state) AS state FROM asterisk.vicidial_list WHERE list_id='".$row['list_id']."' ORDER BY state ASC",1);
//				while($srow = mysql_fetch_array($stateres, MYSQL_ASSOC)){
//
//					if($zz++ > 0)$statestr.=',';
//					$statestr.= $srow['state'];
//				}
//				$statusstr = '';
//				$zz=0;
//				$statusres = query("SELECT DISTINCT(status) AS status FROM asterisk.vicidial_list WHERE list_id='".$row['list_id']."' ORDER BY status ASC",1);
//				while($srow = mysql_fetch_array($statusres, MYSQL_ASSOC)){
//
//					if($zz++ > 0)$statusstr.=',';
//					$statusstr.= $srow['status'];
//				}

				echo 'vici_lists['.$db['cluster_id'].'][y] = new Array();';

				echo 'vici_lists['.$db['cluster_id'].'][y]["id"] = '.$row['list_id'].';'."\n";
				echo 'vici_lists['.$db['cluster_id'].'][y]["name"] = "'.$row['list_name'].'";'."\n";
				echo 'vici_lists['.$db['cluster_id'].'][y]["campaign"] = "'.$row['campaign_id'].'";'."\n";

				echo 'vici_lists['.$db['cluster_id'].'][y]["states"] = "";'."\n";
				echo 'vici_lists['.$db['cluster_id'].'][y]["statuses"] = "";'."\n";

//				echo 'vici_lists['.$db['cluster_id'].'][y]["states"] = "'.$statestr.'";'."\n";
//				echo 'vici_lists['.$db['cluster_id'].'][y]["statuses"] = "'.$statusstr.'";'."\n";


				echo 'y++;'."\n";


			}


			///echo 'idx++;'."\n";

		}

		// CONNECT BACK TO LIST DATABASE
		connectListDB();
		?>
			function getStateStatuses(cluster_id, dd_id){

				if(vici_loaded_status[cluster_id] == false){

					$('#'+dd_id).hide();
					$('#loading_cluster_info').show();

					$.ajax({
						type: "POST",
						cache: false,
						url: 'ajax.php?mode=load_vici_list_info&cluster_id='+cluster_id,
						error: function(){
							alert("Error pulling states/statuses from vici. Please contact an admin.");
						},
						success: function(msg){

							$('#'+dd_id).show();
							$('#loading_cluster_info').hide();

							//alert(msg);

							var tmparr = msg.split(/\r\n|\n/);

							if(tmparr[0].trim() != "1"){

								alert("Error pulling states/statuses from vici.");

								return;
							}

							//tmparr[1] == "states";
							//tmparr[2] == "statuses";
							//findListIDX(list_id)

							var chunks = new Array();
							var listidx = -1;
							for(var x=1;x < tmparr.length;x++){

								chunks = tmparr[x].split(/\t/);

								listidx = findListIDX(chunks[0]);

								if(listidx < 0)continue;// SKIP LIST IDS THAT ARE INVALID OR NOT FOUND

//								alert(chunks[1]);
//								alert(chunks[2]);

								vici_lists[cluster_id][listidx]["states"] = chunks[1];
								vici_lists[cluster_id][listidx]["statuses"] = chunks[2];
							}






							vici_loaded_status[cluster_id] = true;
							//updateStatesStatuses();
						}


					});

				}

			}


			function buildListDD(dd_id, cluster_id){

				// ONLY SHOULD NEED THE STATE/STATUSES FROM THE SELECTED SOURCE CLUSTER, NOT THE TARGET CLUSTER
				if(dd_id == "source_list")getStateStatuses(cluster_id,dd_id);


				var obj=getEl(dd_id);
				var opt = obj.options;
			//	var catid=getEl('s_campaign_id').value;

				// Empty DD
				for(var x=0;x < opt.length;x++){obj.remove(x);}
				obj.options.length=0;

				var newopts = new Array();
//				newopts[0] = document.createElement("OPTION");
//
//				if(ie)	obj.add(newopts[0]);
//				else	obj.add(newopts[0],null);
//
//				newopts[0].innerText	= '';
//				newopts[0].value	= 0;


				if(!cluster_id){
				newopts[0] = document.createElement("OPTION");

				if(ie)	obj.add(newopts[0]);
				else	obj.add(newopts[0],null);

				newopts[0].innerText	= '[Pick a cluster first]';
				newopts[0].value	= 0;
					return;
				}

				var curid=0;
				for(x=0;x < vici_lists[cluster_id].length;x++){
					//curid=item_id[x];
					curid=x;

					//alert(which+' '+item_name[curid]);

//					if(catid && item_cpgnid[curid] != catid){
//						continue;
//					}

					newopts[x] = document.createElement("OPTION");

					if(ie)	obj.add(newopts[x]);
					else	obj.add(newopts[x],null);

					newopts[x].value	= vici_lists[cluster_id][curid]["id"];


					if(ie)	newopts[x].innerText	= vici_lists[cluster_id][curid]["id"]+" - "+vici_lists[cluster_id][curid]["name"];
					else	newopts[x].innerHTML	= vici_lists[cluster_id][curid]["id"]+" - "+vici_lists[cluster_id][curid]["name"];

				//if(selid == vici_lists[cluster_id][curid]["id"])obj.value=vici_lists[cluster_id][curid]["id"];



				}


				toggleShuffleVisability();

			}




			function findListIDX(list_id){

				var cluster_id = $('#source_cluster_id').val();

				for(var x=0;x < vici_lists[cluster_id].length;x++){

					if(vici_lists[cluster_id][x]["id"] == list_id)return x;
				}
				return -1;
			}


			function updateStatesStatuses(){

				var statehtml = "";
				var statushtml = "";

				var sources = $('#source_list').val();

				var cluster_id = $('#source_cluster_id').val();

				//alert(sources);


				if(sources == null){
					alert("Please select a list first");
					return;
				}

				var sourcearr = sources;//sources.split(/,/);
				var idx=-1;

				var statusarr = new Array();
				var statearr = new Array();
				var tmpstatearr = new Array();
				var tmpstatusarr = new Array();
				var stateptr=0;
				var statusptr=0;
				var y=0;
				for(var x=0;x < sourcearr.length;x++){


					idx = findListIDX(sourcearr[x]);
					//if(idx < 0){alert("Source "+sourcearr[x]+" not found"); continue;}



					tmpstatusarr = vici_lists[cluster_id][idx]["statuses"].split(',');
					tmpstatearr = vici_lists[cluster_id][idx]["states"].split(',');

					// FIND THE STATE ON THE ARRAY AND SKIP
					for(y=0;y < tmpstatearr.length;y++){

						if(statearr.indexOf(tmpstatearr[y]) > -1)continue;

						statearr[stateptr++] = tmpstatearr[y];

					}

					// FIND THE STATUSES AND SKIP
					for(y=0;y < tmpstatusarr.length;y++){

						if(statusarr.indexOf(tmpstatusarr[y]) > -1)continue;

						statusarr[statusptr++] = tmpstatusarr[y];

					}
				}

				var z=0;
				// BUILD STATUS DROPDOWNS
				for(y=0,z=0;y < statusarr.length;y++){

					if(!statusarr[y])continue;

					statushtml += '<input type="checkbox" id="selected_status_'+z+'" CHECKED ';

					if($('#status_selection_all').is(':checked')){

						statushtml += " DISABLED ";
					}

					statushtml += ' name="selected_statuses[]" value="'+statusarr[y]+'" /> '+statusarr[y]+'<br />';
					z++;
				}

				// BUILD STATE CHECKBOXES
				for(y=0,z=0;y < statearr.length;y++){
					if(!statearr[y])continue;
					statehtml += '<input type="checkbox" id="selected_states_'+z+'" CHECKED ';

					if($('#states_selection_all').is(':checked')){

						statehtml += " DISABLED ";
					}

					statehtml += ' name="selected_states[]" value="'+statearr[y]+'" /> '+statearr[y]+'<br />';
					z++;
				}



				$('#states_checkboxes').html(statehtml);
				$('#status_checkboxes').html(statushtml);


				applyUniformity();

			}






		function checkMoveListForm(frm){

			// CHECK THAT A SOURCE CLUSTER IS SELECTED
			if(!frm.source_cluster_id.value)return alert('Please select a source cluster.');

			// AND THAT A SOURCE LIST IS SELECTED
			if(!$('#source_list').val()){
				return alert('Please select a source list from the selected cluster.');
			}

			// MAKE SURE DESTINATION CLUSTER SELECTED
			if(!frm.target_cluster_id.value)return alert('Please select a destination cluster.');

			// AND A DESTINATION LIST IS SELECTED
			if(!$('#target_list').val()){
				return alert('Please select a target list.');
			}


			// MAKE SURE A STATUS IS CHECKED
			if( ! $('#status_selection_all').is(':checked')){
				if(!hasCheckedCheckboxes('selected_status_')){
					return alert('Please check at least one STATUS checkbox.');
				}
			}

			// MAKE SURE A STATE IS CHECKED
			if( ! $('#states_selection_all').is(':checked')){
				if(!hasCheckedCheckboxes('selected_states_')){
					return alert('Please check at least one STATE checkbox.');
				}
			}







			// NINJA FORM SUBMIT
			//ninjaUploadList();
			generateListAuthKey();

			return false;
		}



		function generateListAuthKey(){

			$.post("ajax.php?mode=generate_auth_key&type=move_vici_list",null,ninjaMoveViciList);
		}



		function ninjaMoveViciList(code){



			$('#move_list_status_cell').html('<div style="font-size:16px;height:30px;">Preparing upload...</div>');


			var frm = getEl('ninjavicitoolsform');
			frm.auth_code.value = code;

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

						//alert("VICI TOOLS POST: "+msg);

						var res = parseInt(msg);

						if(res > 0){

							$('#move_list_status_cell').html('Success');

							// TASK ID
							displayViewTaskDialog(res);


						}else{

							$('#move_list_status_cell').html(msg);

							alert(msg);

						}




					}
				});

//			var ninjafrm=getEl('ninjavicitoolsform');
//
//			ninjafrm.auth_code.value = code.trim();
//
//			// BLANK THE PAGE
//			getEl('iframe_vici_tools').src = 'about:blank';
//
//			$('#move_list_status_cell').html('<div style="font-size:16px;height:40px;"><img src="images/ajax-loader.gif" border="0" />Uploading file...</div>');
//
//			// SUBMIT HIDDEN FORM
//			ninjafrm.submit();
		}


		function moveViciListFailed(error_code, error_message){

			var msg = "ERROR! Upload failed:\n"+error_message;

			$('#move_list_status_cell').html(msg);

			alert(msg);
		}

		function moveViciListSuccess(warning_messages){

			warning_messages = $.trim(warning_messages);

			$('#move_list_status_cell').html('Success');

			if(warning_messages){
				alert("Successfully scheduled the List Move"+ ((warning_messages)?". However warnings were issued:\n"+warning_messages:".") );
			}

			//displayAddScriptDialog
		}

		function toggleShuffleVisability(){

			//

			var sourceval = parseInt($('#source_cluster_id').val());
			var targetval = parseInt($('#target_cluster_id').val());

			if(sourceval > 0 && targetval > 0 && sourceval != targetval){

				$('#shuffle_row').show();

			}else{

				$('#shuffle_row').hide();
			}

		}

		</script>


		<div class="nod">
			<iframe id="iframe_vici_tools" name="iframe_vici_tools" width="1" height="1" frameborder="0" src="about:blank"></iframe>
		</div><?




			$this->makeViewTaskGUI();



		?><form id="ninjavicitoolsform" method="POST" action="<?
				echo $this->upload_api_script.'?mode=move_vici_lists';
		?>" onsubmit="return checkMoveListForm(this)" target="iframe_vici_tools">

					<input type="hidden" name="moving vici_lists">
					<input type="hidden" name="auth_code" id="auth_code">



		<table border="0" width="100%" class="lb" cellspacing="0" align="center">
		<tr>
			<td height="40" class="pad_left ui-widget-header" colspan="2">

				<table border="0" width="100%" >
				<tr>
					<td>
						Vicidial Tools
					</td>
				</tr>
				</table>
			</td>
		</tr>

		<tr>
			<td align="left" width="500">

				<table border="0" width="500" style="border-right:1px dotted #000">
				<tr>
					<th colspan="4" class="big bl">Move List</th>
				</tr>
				<tr valign="top">
					<th colspan="2" align="left">

						<span class="big">Source</span><br />
						<?=makeClusterDD('source_cluster_id', '', '', "buildListDD('source_list', this.value);", "[Select Cluster]")?>
						<br />

						<select id="source_list" name="source_list[]" size="10" MULTIPLE onchange="updateStatesStatuses();" >

							<option value="">[Pick a cluster first]</option>

						</select>

						<span id="loading_cluster_info" class="nod"><img src="images/ajax-loader.gif" width="48" border="0" /></span>

					</th>

					<th colspan="2" align="left">
						<span class="big">Destination</span><br />
						<?=makeClusterDD('target_cluster_id', '', '', "buildListDD('target_list',this.value)", "[Select Cluster]")?><br />

						<select id="target_list" name="target_list" >

							<option value="">[Pick a cluster first]</option>

						</select><br />


						<table border="0">
						<tr>
							<th height="30"># of leads:<br/>(for destination list)</th>
							<td>
								<input type="text" name="lead_limit" value="0" size="5"  onkeyup="this.value = this.value.replace(/[^0-9]/g, '');if(!this.value)this.value='0';">
								(0 = All leads)
							</td>
						</tr>


						<tr id="shuffle_row" class="nod">
							<th height="30">Shuffle Leads:</th>
							<td><input type="checkbox" name="shuffle_leads"></td>
						</tr>

						<tr>
							<th height="30">Reset Call Counter:</th>
							<td><input type="checkbox" name="reset_call_counter"></td>
						</tr>
						<tr>
							<th height="30">Set Status to NEW:</th>
							<td><input type="checkbox" name="reset_call_status"></td>
						</tr>


						<tr>
							<th>Batch Size (<a href="#" onclick="alert('Determines how many leads to work with at a time.\nExample: How many to inject into vici at a time, or write to a file at a time. ');return false">help?</a>):</th>
							<td>
								<select name="batch_size">
									<option value="100">100</option>
									<option value="500">500</option>
									<option value="1000" SELECTED >1000</option>
									<option value="2000">2000</option>
									<option value="3000">3000</option>
									<option value="5000">5000</option>
									<option value="10000">10000</option>

								</select>
							</td>
						</tr>
						<tr valign="bottom">
							<td colspan="2" align="center" height="40">

								<input type="submit" value="Move Leads" >

							</td>
						</tr>
						<tr>
							<td colspan="2" align="center" id="move_list_status_cell">


							</td>
						</tr>
						</table>
					</th>
				</tr>
<?/**				<tr>
					<th>Lead Status:</th>
					<td colspan="3"><?

						echo $_SESSION['lead_management']->makeDispoDD('lead_status', '', "", "[All Statuses]", null);

					?></td>
				</tr>
	**/?>

				<tr>

					<td colspan="4">


						<table border="0" width="100%">

						<tr>
							<th class="big">Statuses</th>
							<th class="big">States</th>
						</tr>
						<tr>
							<td>

								<input type="radio" name="status_selection" id="status_selection_all" value="all" CHECKED onclick="toggleEnableAllChecks('selected_status_', 0);toggleAllChecks('selected_status_', 1);">All Statuses<br/>
								<input type="radio" name="status_selection" value="selective"  onclick="toggleEnableAllChecks('selected_status_', 1)">Selective Statuses

							</td>
							<td>

								<input type="radio" name="state_selection" id="states_selection_all" value="all" CHECKED onclick="toggleEnableAllChecks('selected_states_', 0);toggleAllChecks('selected_states_', 1);">All States<br/>
								<input type="radio" name="state_selection" value="selective" onclick="toggleEnableAllChecks('selected_states_', 1)">Selective States



							</td>
						</tr>
						<tr>
							<td align="center"><a href="#" onclick="toggleAllChecks('selected_status_', 1);return false;">[Check All]</a> <a href="#" onclick="toggleAllChecks('selected_status_', 0);return false;">[Uncheck All]</a>
							<td align="center"><a href="#" onclick="toggleAllChecks('selected_states_', 1);return false;">[Check All]</a> <a href="#" onclick="toggleAllChecks('selected_states_', 0);return false;">[Uncheck All]</a>
						</tr>
						<tr valign="top">
							<td style="border-right:1px dotted #000;border-top:1px dotted #000" id="status_checkboxes"></td>
							<td style="border-top:1px dotted #000">

								<div style="width:100%;height:300px;overflow:auto" id="states_checkboxes"></div>


							</td>
						</tr>
						</table>


					</td>

				</tr>

				</table>
			</td>
			<td>

				Other tools here....

			</td>
		</tr>
		</form>
		</table><?

	}



}
