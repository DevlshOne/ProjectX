<?php
	/***************************************************************
	 *	Feature Control - GUI/Interface to manage feature templates
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['feature_control'] = new FeatureControl;


class FeatureControl{

	var $table	= 'features';	## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'feat_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'featnextfrm';

	var $order_prepend = 'feat_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function FeatureControl(){

		## REQURES DB CONNECTION!
		$this->handlePOST();
	}




	function handlePOST(){

		## AJAX'd YO!

	}

	function handleFLOW(){
		# Handle flow, based on query string

		if(!checkAccess('feature_control')){


			accessDenied("Feature Control");

			return;

		}else{

			if(isset($_REQUEST['add_feature'])){

				$this->makeAdd($_REQUEST['add_feature']);

			}else{
				$this->listEntrys();
			}

		}

	}



	function makeDD($name, $sel, $class, $blank_entry="[NO SECTIONS/ACCESS]"){

		$info = array(

			'status'=>'active'
		);

		$out = '<select id="'.htmlentities($name).'" name="'.htmlentities($name).'" ';

		$out.= '>';


		$out .= '<option value="">'.htmlentities($blank_entry).'</option>';

		$res = $_SESSION['dbapi']->features->getResults($info);

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$out .= '<option value="'.$row['id'].'" '.(($sel == $row['id'])?' SELECTED ':'').'>';
			$out .= $row['name'].'</option>';

		}

		$out .= '</select>';

		return $out;
	}




	function listEntrys(){


		?><script>

			var feature_delmsg = 'Deleting this will cause any user using it, to lose access to the system.\nAre you sure you want to delete this feature set?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var FeaturesTableFormat = [
				['id','align_center'],
				['name','align_left'],
				['[get:users_assigned:id]','align_center'],
				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getFeaturesURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=features&"+
								"mode=xml&"+

								's_id='+escape(frm.s_id.value)+"&"+
								's_name='+escape(frm.s_name.value)+"&"+
								's_status='+escape(frm.s_status.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var features_loading_flag = false;

			/**
			* Load the data - make the ajax call, callback to the parse function
			*/
			function loadFeatures(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = features_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("extensions ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('features_loading_flag = true');
				}

				// PAGE SIZE SUPPORT!
				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());


				loadAjaxData(getFeaturesURL(),'parseFeatures');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseFeatures(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('feature',FeaturesTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('features',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadFeatures()'
								);

				}else{

					hidePageSystem('features');

				}

				eval('features_loading_flag = false');
			}


			function handleFeatureListClick(id){

				displayAddFeatureDialog(id);

			}


			function displayAddFeatureDialog(featureid){

				var objname = 'dialog-modal-add-feature';


				if(featureid > 0){
					$('#'+objname).dialog( "option", "title", 'Editing Feature Set' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Feature Set' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=feature_control&add_feature="+featureid+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			function resetFeatureForm(frm){

				frm.s_id.value='';
				frm.s_name.value = '';
				frm.s_status.value='active';

			}




		</script>
		<div id="dialog-modal-add-feature" title="Adding new Feature Set" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadFeatures();return false">
			<input type="hidden" name="searching_features">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td width="500">
						Features
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Add" onclick="displayAddFeatureDialog(0)">
					</td>
					<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadFeatures();return false">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="500">500</option>
					</select></td>
					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="features_prev_td" class="page_system_prev"></td>
							<td id="features_page_td" class="page_system_page"></td>
							<td id="features_next_td" class="page_system_next"></td>
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
					<option value="disabled">Disabled</option>
				</select></td>
				<td><input type="button" value="Reset" onclick="resetFeatureForm(this.form);resetPageSystem('<?=$this->index_name?>');loadFeatures();"></td>
			</tr>
			</table></td>
		</tr>

		</form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="feature_table">
			<tr>
				<th class="row2" align="center"><?=$this->getOrderLink('id')?>ID</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('name')?>Name</a></th>
				<th class="row2" align="center">Users Assigned</th>
				<th class="row2">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-add-feature").dialog({
				autoOpen: false,
				width: 430,
				height: 420,
				modal: false,
				draggable:true,
				resizable: false
			});

			// CALL FUNCTION TO POPULATE THE TABLE WITH DATA
			loadFeatures();

		</script><?

	}


	function makeAdd($id){

		$row = $_SESSION['dbapi']->features->getByID($id);


		?><script>

			function validateFeatureField(name,value,frm){

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
			}


			function submitFeatureChanges(frm){


				var params = getFormValues(frm,'validateFeatureField');

				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'name':

						alert("Please enter a name for the feature set.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;

					}

				// SUCCESS - POST AJAX TO SERVER
				}else{


					//alert("Form validated, posting");

					$.ajax({
						type: "POST",
						cache: false,
						url: 'api/api.php?get=features&mode=xml&action=edit',
						data: params,
						error: function(){
							alert("Error saving feature form. Please contact an admin.");
						},
						success: function(msg){


							var result = handleEditXML(msg);
							var res = result['result'];

							if(res <= 0){

								alert(result['message']);

								return;

							}

							// IF ADDING
							//if(parseInt(frm.adding_user.value) <= 0){

							alert(result['message']);

							try{

								loadFeatures();
							}catch(e){

								// LOAD FEATURES FAILS, MEANS WE ARE PROB IN USERS SECTION

								// ATTEMPT TO REFRESH PAGE OR DROPDOWN?


								loadSection('index.php?area=users&add_user=<?=intval($_REQUEST['add_user'])?>&printable=1&no_script=1');

							}


							try{
								displayAddFeatureDialog(res);
							}catch(e){}




						}


					});

				}

				return false;
			}

		</script>


		<form method="POST" action="<?=stripurl()?>" onsubmit="return submitFeatureChanges(this)">

			<input type="hidden" name="adding_feature" value="<?=$id?>">


		<table border="0">
		<tr>
			<th>Template Name</th>
			<td><input type="text" size="40" name="name" value="<?=htmlentities($row['name'])?>"></td>
		</tr>
		<tr>
			<th>Status</th>
			<td><select name="status">
					<option value="active"<?=($row['status'] == 'active')?" SELECTED ":""?>>Active</option>
					<option value="disabled"<?=($row['status'] == 'disabled')?" SELECTED ":""?>>Disabled</option>
			</select></td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input type="submit" value="Save Changes"></td>
		</tr>
		<tr valign="top">
			<td colspan="2">

				<table border="0" width="100%">
				<tr valign="top">
					<td>

						<table border="0" width="100%" >
						<tr>
							<th colspan="2" class="row2">Campaign Setup</th>
						</tr><?

						$this->renderFeatureRow('campaigns', 'Campaigns', ($row['campaigns'] == 'yes')?true:false );

						$this->renderFeatureRow('voices', 'Voices', ($row['voices'] == 'yes')?true:false );

						$this->renderFeatureRow('names', 'Names', ($row['names'] == 'yes')?true:false );

						$this->renderFeatureRow('scripts', 'Scripts', ($row['scripts'] == 'yes')?true:false );

						?><tr>
							<th colspan="2" class="row2">Management Tools</th>
						</tr><?

						$this->renderFeatureRow('lead_management', 'Lead Management', ($row['lead_management'] == 'yes')?true:false );
						$this->renderFeatureRow('lmt_change_dispo', '|--&gt;Change Dispo', ($row['lmt_change_dispo'] == 'yes')?true:false );
						$this->renderFeatureRow('sales_management', 'Sales Management', ($row['sales_management'] == 'yes')?true:false );
						
						
						$this->renderFeatureRow('employee_hours', 'Employee Hours', ($row['employee_hours'] == 'yes')?true:false );

						$this->renderFeatureRow('phone_lookup', 'DRIPP Phone lookup', ($row['phone_lookup'] == 'yes')?true:false );

						$this->renderFeatureRow('quiz_results', 'Quiz Results', ($row['quiz_results'] == 'yes')?true:false );

						$this->renderFeatureRow('ringing_calls', 'Ring Report', ($row['ringing_calls'] == 'yes')?true:false );
						$this->renderFeatureRow('messages', 'Agent Messages', ($row['messages'] == 'yes')?true:false );
						//$this->renderFeatureRow('login_tracker', 'Login Tracker', ($row['login_tracker'] == 'yes')?true:false );
						
						
						$this->renderFeatureRow('dialer_status', 'Dialer Status', ($row['dialer_status'] == 'yes')?true:false );
						$this->renderFeatureRow('server_status', 'Server Status', ($row['server_status'] == 'yes')?true:false );
						$this->renderFeatureRow('extensions', 'Extensions', ($row['extensions'] == 'yes')?true:false );


						?><tr>
							<th colspan="2" class="row2">List Tools</th>
						</tr><?

						$this->renderFeatureRow('list_tools', 'List Tools', ($row['list_tools'] == 'yes')?true:false );

						?></table>
					</td>
					<td width="20">&nbsp;</td>
					<td>

						<table border="0" width="100%">

						<tr>
							<th colspan="2" class="row2">Reports</th>
						</tr><?

						$this->renderFeatureRow('fronter_closer', 'Fronter/Closer Report', ($row['fronter_closer'] == 'yes')?true:false );
						$this->renderFeatureRow('sales_analysis', 'Sales Analysis', ($row['sales_analysis'] == 'yes')?true:false );
						$this->renderFeatureRow('agent_call_stats', 'Verifier Call Stats', ($row['agent_call_stats'] == 'yes')?true:false );


						$this->renderFeatureRow('rouster_report', 'Rouster Call Stats', ($row['rouster_report'] == 'yes')?true:false );
						
						$this->renderFeatureRow('summary_report', 'Summary Reports', ($row['summary_report'] == 'yes')?true:false );
						

						$this->renderFeatureRow('user_charts', 'User Charts', ($row['user_charts'] == 'yes')?true:false );
						$this->renderFeatureRow('recent_hangups', 'Recent Hangups', ($row['recent_hangups'] == 'yes')?true:false );
						$this->renderFeatureRow('script_statistics', 'Script Statistics', ($row['script_statistics'] == 'yes')?true:false );
						$this->renderFeatureRow('dispo_log', 'Dispo Log', ($row['dispo_log'] == 'yes')?true:false );

						?><tr>
							<th colspan="2" class="row2">PAC Maintenance</th>
						</tr><?
						
						$this->renderFeatureRow('pac_web_donations', 'Web Donations', ($row['pac_web_donations'] == 'yes')?true:false );
						
						
						?><tr>
							<th colspan="2" class="row2">Users</th>
						</tr><?

						$this->renderFeatureRow('users', 'Central User Management', ($row['users'] == 'yes')?true:false );

						$this->renderFeatureRow('feature_control', 'Feature Control', ($row['feature_control'] == 'yes')?true:false );
						$this->renderFeatureRow('login_tracker', 'Login Tracker', ($row['login_tracker'] == 'yes')?true:false );
						$this->renderFeatureRow('login_tracker_kick_user', '|--&gt;Kick User', ($row['login_tracker_kick_user'] == 'yes')?true:false );
						$this->renderFeatureRow('action_log', 'Action Log', ($row['action_log'] == 'yes')?true:false );

						?><tr>
							<th colspan="2" class="row2">Account</th>
						</tr><?

						$this->renderFeatureRow('change_password', 'Change Password', ($row['change_password'] == 'yes')?true:false );


						?></table>
					</td>
				</tr>
				</table>

			</td>
		</tr>


		</form>
		</table><?



	}


	function renderFeatureRow($name, $friendly_name, $checked){

		?><tr>
			<td align="center"><input type="checkbox" name="<?=htmlentities($name)?>" value="yes" <?=($checked)?' CHECKED ':''?>></td>
			<th align="left"><?=$friendly_name?></th>
		</tr><?
	}



	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadFeatures();return false;\">";

		return $var;
	}
}
