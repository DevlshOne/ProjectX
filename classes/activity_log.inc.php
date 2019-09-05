<?	/***************************************************************
	 *	Activity Log - Tracks employees hours, based on vici activity.
	 *	Written By: Jonathan Will, Professor of Computer Science at the Scumlord University of CCI
	 ***************************************************************/

$_SESSION['activity_log'] = new ActivityLog;


class ActivityLog{

	var $table	= 'activity_log';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'activity_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'activitynextfrm';

	var $order_prepend = 'activity_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function ActivityLog(){


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



		$this->listEntrys();


	}






	function listEntrys(){


		?><script>

			var activity_delmsg = 'Are you sure you want to delete this record?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var ActivitysTableFormat = [
				['username','align_left'],
				['campaign','align_center'],
				['[time:time_started]','align_center'],
				['[time:time_last_tick]','align_center'],

				['calls_today','align_center'],
				['[render:hours:activity_time]','align_center'],
				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getActivitysURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=activity_log&"+
								"mode=xml&"+

								's_id='+escape(frm.s_id.value)+"&"+
								's_username='+escape(frm.s_username.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var activitys_loading_flag = false;

			/**
			* Load the name data - make the ajax call, callback to the parse function
			*/
			function loadActivitys(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = activitys_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("NAMES ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('activitys_loading_flag = true');
				}


				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				loadAjaxData(getActivitysURL(),'parseActivitys');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseActivitys(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('activity',ActivitysTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('activitys',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadActivitys()'
								);

				}else{

					hidePageSystem('activitys');

				}

				eval('activitys_loading_flag = false');
			}


			function handleActivityListClick(id){

				displayAddActivityDialog(id);

			}


			function displayAddActivityDialog(id){

//				var objname = 'dialog-modal-add-activity';

//
//				if(id > 0){
//					$('#'+objname).dialog( "option", "title", 'Editing Activity' );
//				}else{
//					$('#'+objname).dialog( "option", "title", 'Adding new Name' );
//				}
//
//
//
//				$('#'+objname).dialog("open");
//
//				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
//
//				$('#'+objname).load("index.php?area=names&add_name="+id+"&printable=1&no_script=1");


			}

			function resetActivityForm(frm){

				frm.s_id.value = '';
				frm.s_username.value = '';


			}


			var activitysrchtog = false;

			function toggleNameSearch(){
				activitysrchtog = !activitysrchtog;
				ieDisplay('activity_search_table', activitysrchtog);
			}

		</script>
		<div id="dialog-modal-add-activity" title="Adding new Activity" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadActivitys();return false">
			<input type="hidden" name="searching_activity">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td width="500">
						Activity Log
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Search" onclick="toggleNameSearch()">
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
							<td id="activitys_prev_td" class="page_system_prev"></td>
							<td id="activitys_page_td" class="page_system_page"></td>
							<td id="activitys_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" width="100%" id="activity_search_table" class="nod">
			<tr>
				<td rowspan="2"><font size="+1">SEARCH</font></td>
				<th class="row2">ID</th>
				<th class="row2">Username</th>
				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_id" size="5" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>
				<td align="center"><input type="text" name="s_username" size="20" value="<?=htmlentities($_REQUEST['s_username'])?>"></td>
				<td><input type="button" value="Reset" onclick="resetActivityForm(this.form);resetPageSystem('<?=$this->index_name?>');loadActivitys();"></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="activity_table">
			<tr>

				<th class="row2" align="left"><?=$this->getOrderLink('username')?>Username</a></th>
				<th class="row2" ><?=$this->getOrderLink('campaign')?>Campaign</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('time_started')?>Time started</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('time_last_tick')?>Time Last Added</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('calls_today')?>Calls Today</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('activity_time')?>Activity Time</a></th>
				<th class="row2">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-add-activity").dialog({
				autoOpen: false,
				width: 400,
				height: 200,
				modal: false,
				draggable:true,
				resizable: false
			});

			loadActivitys();

		</script><?

	}


	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadActivitys();return false;\">";

		return $var;
	}
}
