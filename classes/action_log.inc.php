<?	/***************************************************************
	 *	MOTHERFUCKING _ACTION LOGGGGG_!!!!!!!!!!!!
	 *	Written By: Jonathan Will, Professor of Computer Science at the Scumlord University of CCI
	 ***************************************************************/

$_SESSION['action_log'] = new ActionLog;


class ActionLog{

	var $table	= 'action_log';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'action_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'actionynextfrm';

	var $order_prepend = 'action_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function ActionLog(){


		## REQURES DB CONNECTION!



		$this->handlePOST();
	}


	function handlePOST(){

		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/action_log.api.php FOR POST HANDLING!
		// <3 <3 -Jon

	}

	function handleFLOW(){
		# Handle flow, based on query string


		if(($id=intval($_REQUEST['view_action'])) > 0){

			$this->makeView($id);

		// VIEW THE HISTORY OF A CERTAIN RECORD (almost anywhere in the LMT)
		}else if(isset($_REQUEST['view_change_history']) && trim($_REQUEST['view_area']) && intval($_REQUEST['view_area_id']) > 0){

			$this->makeViewHistory(trim($_REQUEST['view_area']), intval($_REQUEST['view_area_id']) );


		}else{

			$this->listEntrys();

		}


	}






	function listEntrys(){


		?><script>

			var action_delmsg = 'Are you sure you want to delete this action log? Too bad.';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var ActionsTableFormat = [

				['[time:time]','align_center'],
				['user','align_left'],
				['action','align_center'],
				['area','align_center'],
				['record_id','align_center'],
				['description','align_left'],
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getActionsURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=action_log&"+
								"mode=xml&"+

								's_user_id='+escape(frm.s_user_id.value)+"&"+
								's_username='+escape(frm.s_username.value)+"&"+
								's_action='+escape(frm.s_action.value)+"&"+
								's_area='+escape(frm.s_area.value)+"&"+
								's_record_id='+escape(frm.s_record_id.value)+"&"+
								's_desc='+escape(frm.s_desc.value)+"&"+

								's_date_month='+escape(frm.s_date_month.value)+"&"+'s_date_day='+escape(frm.s_date_day.value)+"&"+'s_date_year='+escape(frm.s_date_year.value)+"&"+
								's_date2_month='+escape(frm.s_date2_month.value)+"&"+'s_date2_day='+escape(frm.s_date2_day.value)+"&"+'s_date2_year='+escape(frm.s_date2_year.value)+"&"+

								's_date_mode='+escape(frm.s_date_mode.value)+"&"+



								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var actions_loading_flag = false;

			/**
			* Load the action data - make the ajax call, callback to the parse function
			*/
			function loadActions(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = actions_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("ACTIONZ ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('actions_loading_flag = true');
				}


				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				loadAjaxData(getActionsURL(),'parseActions');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseActions(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('action',ActionsTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('actions',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadActions()'
								);

				}else{

					hidePageSystem('actions');

				}

				eval('actions_loading_flag = false');
			}


			function handleActionListClick(id){

				displayAddActionDialog(id);

			}


			function displayAddActionDialog(id){

				var objname = 'dialog-modal-view-action';

				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=action_log&view_action="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');

			}

			function resetActionForm(frm){

				frm.reset();

				frm.s_user_id.value = '';
				frm.s_username.value = '';
				frm.s_action.value = '';
				frm.s_area.value = '';
				frm.s_desc.value = '';
				frm.s_record_id.value = '';

				frm.s_date_mode.value = 'date';


			}


			var actionsrchtog = false;

			function toggleActionSearch(){
				actionsrchtog = !actionsrchtog;
				ieDisplay('action_search_table', actionsrchtog);
			}

		</script>
		<div id="dialog-modal-view-action" title="View Action" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadActions();return false">
			<input type="hidden" name="searching_action">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td width="500">
						Action Log

					</td>
					<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadActivitys();return false">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="500">500</option>
					</select></td>
					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="actions_prev_td" class="page_system_prev"></td>
							<td id="actions_page_td" class="page_system_page"></td>
							<td id="actions_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" width="100%" id="action_search_table">
			<tr>
				<th class="row2">User ID</th>
				<th class="row2">Username</th>
				<th class="row2">Action</th>
				<th class="row2">Area</th>
				<th class="row2">Record ID</th>
				<th class="row2">Description</th>
				<th class="row2">
					<select name="s_date_mode" onchange="toggleDateMode(this.value);loadActions();">
						<option value="date">Date</option>
						<option value="daterange"<?=($_REQUEST['s_date_mode']=='daterange')?' SELECTED ':''?>>Date Range</option>
					</select>
				</th>
				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_user_id" size="5" value="<?=htmlentities($_REQUEST['s_user_id'])?>"></td>
				<td align="center"><input type="text" name="s_username" size="10" value="<?=htmlentities($_REQUEST['s_username'])?>"></td>
				<td align="center"><?

					echo $this->makeActionDD('s_action', $_REQUEST['s_action'], '', $this->index_name." = 0;loadActions()");

				?></td>
				<td align="center"><?

					echo $this->makeAreaDD('s_area', $_REQUEST['s_area'], '', $this->index_name." = 0;loadActions()");

				?></td>
				<td align="center"><input type="text" name="s_record_id" size="5" value="<?=htmlentities($_REQUEST['s_record_id'])?>"></td>
				<td align="center"><input type="text" name="s_desc" size="20" value="<?=htmlentities($_REQUEST['s_desc'])?>"></td>
				<td align="center"><?

					echo makeTimebar("s_date_",1,null,false,time()," onchange=\"".$this->index_name." = 0;loadActions()\" ");

					?><span id="date2_span" class="nod"><br /><?
						echo makeTimebar("s_date2_",1,null,false,time()," onchange=\"".$this->index_name." = 0;loadActions()\" ");
					?></span>
				</td>

				<td><input type="button" value="Reset" onclick="resetActionForm(this.form);resetPageSystem('<?=$this->index_name?>');loadActions();"></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="action_table">
			<tr>
				<th class="row2" align="center"><?=$this->getOrderLink('time')?>Time</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('user')?>Username</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('action')?>Action</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('area')?>Area</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('record_id')?>Record ID</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('description')?>Description</a></th>



			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-view-action").dialog({
				autoOpen: false,
				width: 500,
				height: 300,
				modal: false,
				draggable:true,
				resizable: true
			});

			loadActions();

		</script><?

	}


	function makeViewHistory($area, $area_id){

		$dat = array();

		$dat['area'] = trim($area);
		$dat['record_id'] = $area_id;


		$res = $_SESSION['dbapi']->action_log->getResults($dat);


		?><table border="0" width="100%">
		<tr>
			<th class="row2">User</th>
			<th class="row2">Time</th>
			<th class="row2">Changes</th>
		</tr><?

		$colspan= 3;

		if(mysqli_num_rows($res) <= 0){

			?><tr><td colspan="<?=$colspan?>" align="center"><i>No Changes found in Action log</i></td></tr><?
		}

		$color=0;
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			$class = 'row'.($color++%2);
			?><tr valign="top">
				<td class="<?=$class?>" align="left"><?=$row['user']?></td>
				<td class="<?=$class?>" align="center"><?=date("g:i:sa m/d/Y", $row['time'])?></td>
				<td class="<?=$class?>" align="left"><?=nl2br(htmlentities($row['changes_tracked']))?></td>
			</tr><?

		}



		?></table><?
	}




	function makeView($id){

		$row = $_SESSION['dbapi']->action_log->getByID($id);




		switch($row['area']){
		default:

		case 'campaigns':
		case 'employee_hours':
		case 'extensions':
		case 'features':
		case 'imports':
		case 'lead_management':
		case 'messages':
		case 'pac_reports':
		case 'report_emails':
		case 'scripts':
		case 'users':
		case 'user_groups':
		case 'voices':

			$areainfo = $row['area'].' (#'.$row['record_id'].')';

			break;
		}


		?><table border="0" width="100%">
		<tr>
			<th align="left">Action:</th>
			<td><?

				echo htmlentities($row['action']);

			?></td>
		</tr>
		<tr>
			<th align="left">Area/ID:</th>
			<td><?

				echo $areainfo;

			?></td>
		</tr>
		<tr>
			<th align="left">Time:</th>
			<td><?=date("g:i:sa m/d/Y T", $row['time'])?></td>
		</tr>
		<tr>
			<th align="left">User:</th>
			<td><?

				echo htmlentities($row['user']).' (#'.$row['user_id'].')';

			?></td>
		</tr><?

		if(trim($row['description'])){
			?><tr>
				<th align="left">Description:</th>
				<td><?

					echo htmlentities($row['description']);

				?></td>
			</tr><?
		}


		if(trim($row['changes_tracked']) ){
			?><tr>
				<th colspan="2" class="bl">Changes Tracked</th>
			</tr>
			<tr>
				<td colspan="2" class="lb" style="padding:5px"><?

					echo nl2br(htmlentities($row['changes_tracked']));

				?></td>
			</tr><?
		}
		?></table><?
	}


	function makeActionDD($name, $selected, $css, $onchange){

		$out = '<select name="'.$name.'" id="'.$name.'" ';

		$out .= ($css)?' class="'.$css.'" ':'';
		$out .= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out .= '>';

		$out .= '<option value="">[All]</option>';

		$res = $_SESSION['dbapi']->query("SELECT DISTINCT( `action` ) FROM `action_log` ");

		while($row = mysqli_fetch_row($res)){

			$out .= '<option value="'.$row[0].'" ';
			$out .= ($selected == $row[0])?' SELECTED ':'';
			$out .= '>'.htmlentities($row[0]).'</option>';


		}



		$out .= '</select>';

		return $out;
	}


	function makeAreaDD($name, $selected, $css, $onchange){

		$out = '<select name="'.$name.'" id="'.$name.'" ';

		$out .= ($css)?' class="'.$css.'" ':'';
		$out .= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out .= '>';

		$out .= '<option value="">[All]</option>';

		$res = $_SESSION['dbapi']->query("SELECT DISTINCT( `area` ) FROM `action_log` ");

		while($row = mysqli_fetch_row($res)){

			$out .= '<option value="'.$row[0].'" ';
			$out .= ($selected == $row[0])?' SELECTED ':'';
			$out .= '>'.htmlentities($row[0]).'</option>';


		}



		$out .= '</select>';

		return $out;
	}


	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadActions();return false;\">";

		return $var;
	}
}
