<?	/***************************************************************
	 *	Dispo Log - Displays list/search for all dispos agents send to the system.
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['dispo_log'] = new DispoLog;


class DispoLog{


	var $table	= 'dispo_log';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $frm_name = 'disponextfrm';
	var $index_name = 'dispo_list';
	var $order_prepend = 'dispo_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages




	function DispoLog(){


		## REQURES DB CONNECTION!
		include_once($_SESSION['site_config']['basedir']."/utils/db_utils.php");

		## NEEDED FOR THE DISPO DROPDOWN FUNCTION
		include_once($_SESSION['site_config']['basedir']."/classes/lead_management.inc.php");

		$this->handlePOST();
	}


	function handlePOST(){

		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/ringing_calls.api.php FOR POST HANDLING!
		// <3 <3 -Jon

	}

	function handleFLOW(){
		# Handle flow, based on query string


//		if(isset($_REQUEST['add_name'])){
//
//			$this->makeV($_REQUEST['add_name']);
//
//		}else{


		if(!checkAccess('dispo_log')){


			accessDenied("Dispo Log");

			return;

		}else{

			if(($id=intval($_REQUEST['view_dispo'])) > 0){

 				$this->makeViewDispo($id);

			}else{


				$this->listEntrys();
			}



		}



//		}

	}


	function listEntrys(){


		?><script>

			var dispo_delmsg = 'Are you sure you want to delete this dispo record?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var DisposTableFormat = [



				['[microtime:micro_time]','align_center'],
				['agent_username','align_left'],


				['lead_tracking_id','align_center'],
				['vici_lead_id','align_center'],

				['dispo','align_center'],
				['result','align_center'],
			];



			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getDisposURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=dispo_log&"+
								"mode=xml&"+

								's_lead_id='+escape(frm.s_lead_id.value)+"&"+
								's_lead_tracking_id='+escape(frm.s_lead_tracking_id.value)+"&"+
								's_username='+escape(frm.s_username.value)+"&"+
								's_dispo='+escape(frm.s_dispo.value)+"&"+
								//'s_date='+escape(frm.s_date.value)+"&"+
								's_result='+escape(frm.s_result.value)+"&"+

								's_date_month='+escape(frm.s_date_month.value)+"&"+'s_date_day='+escape(frm.s_date_day.value)+"&"+'s_date_year='+escape(frm.s_date_year.value)+"&"+
								's_date2_month='+escape(frm.s_date2_month.value)+"&"+'s_date2_day='+escape(frm.s_date2_day.value)+"&"+'s_date2_year='+escape(frm.s_date2_year.value)+"&"+
								's_date_mode='+escape(frm.s_date_mode.value)+"&"+


								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var dispos_loading_flag = false;
			var page_load_start;

			/**
			* Load the name data - make the ajax call, callback to the parse function
			*/
			function loadDispos(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = dispos_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('dispos_loading_flag = true');
				}

				page_load_start = new Date();


				$('#total_count_div').html('<img src="images/ajax-loader.gif" border="0">');



				loadAjaxData(getDisposURL(),'parseDispos');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseDispos(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('dispo',DisposTableFormat,xmldoc);


				var enddate = new Date();

				var loadtime = enddate - page_load_start;

				$('#page_load_time').html("Load and render time: "+loadtime+"ms");



				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('dispos',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadDispos()'
								);

				}else{

					hidePageSystem('dispos');

				}


				eval('dispos_loading_flag = false');
			}


			function handleDispoListClick(id){

				var objname = 'view_dispo_log';


				// RESET HEIGHT
				//	$('#'+objname).dialog('option', 'height', 320);

				$('#'+objname).dialog("open");
				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
				$('#'+objname).load("index.php?area=dispo_log&view_dispo="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');

			}



			function resetDispoForm(frm){

				frm.reset();

				frm.s_lead_id.value = '';
				frm.s_lead_tracking_id.value = '';
				frm.s_dispo.value = '';
				frm.s_result.value = '';
				frm.s_username.value = '';

				toggleDateMode(frm.s_date_mode.value);
				//loadDispos();

			}


			var disposrchtog = false;

			function toggleDispoSearch(){
				disposrchtog = !disposrchtog;
				ieDisplay('dispo_search_table', disposrchtog);
			}



			function toggleDateMode(way){

				if(way == 'daterange'){
					// SHOW EXTRA DATE FIELD
					$('#date2_span').show();
				}else{
					// HIDE IT
					$('#date2_span').hide();
				}

			}


		</script>
		<div id="view_dispo_log" title="View Dispo">


		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadDispos();return false">
			<input type="hidden" name="searching_dispo">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr class="ui-widget-header">
			<td height="40" class="pad_left">

				Dispo Log

			</td>
			<td align="right"><?
				/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
				<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
				<tr>
					<td id="dispos_prev_td" class="page_system_prev"></td>
					<td id="dispos_page_td" class="page_system_page"></td>
					<td id="dispos_next_td" class="page_system_next"></td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" width="700" id="dispo_search_table">
			<tr>
				<td rowspan="2" width="70" align="center" style="border-right:1px solid #000">


					<div id="total_count_div"></div>

				</td>
				<th class="row2">Agent</th>
				<th class="row2">PX ID</th>
				<th class="row2">Lead ID</th>
				<th class="row2">Dispo</th>
				<th class="row2">Result</th>
				<th class="row2">
					<select name="s_date_mode" onchange="toggleDateMode(this.value);loadDispos();">
						<option value="date">Date</option>
						<option value="daterange"<?=($_REQUEST['s_date_mode']=='daterange')?' SELECTED ':''?>>Date Range</option>
					</select>
				</th>

				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_username" size="10" value="<?=htmlentities($_REQUEST['s_username'])?>"></td>
				<td align="center"><input type="text" name="s_lead_tracking_id" size="5" value="<?=htmlentities($_REQUEST['s_lead_tracking_id'])?>"></td>
				<td align="center"><input type="text" name="s_lead_id" size="5" value="<?=htmlentities($_REQUEST['s_lead_id'])?>"></td>


				<td align="center"><?

					echo $_SESSION['lead_management']->makeDispoDD('s_dispo', $_REQUEST['s_dispo'], "", "[All]", null);

				?></td>

				<td align="center"><select name="s_result">

					<option value="">[All]</option>
					<option value="success"<?=($_REQUEST['s_result'] == 'success')?' SELECTED ':''?>>Success</option>
					<option value="failed"<?=($_REQUEST['s_result'] == 'success')?' SELECTED ':''?>>Failed</option>

				</select></td>

				<td align="center" nowrap ><?

					echo makeTimebar("s_date_",1,null,false,time()," onchange=\"loadDispos()\" ");

					?><span id="date2_span" class="nod"><br /><?
						echo makeTimebar("s_date2_",1,null,false,time()," onchange=\"loadDispos()\" ");
					?></span>

				</td>

				<td><input type="button" value="Reset" onclick="resetDispoForm(this.form);resetPageSystem('<?=$this->index_name?>');loadDispos();"></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="950" id="dispo_table">
			<tr>
				<th class="row2"><?=$this->getOrderLink('micro_time')?>Time</a></th>
				<th class="row2"><?=$this->getOrderLink('agent_username')?>Agent</a></th>
				<th class="row2"><?=$this->getOrderLink('lead_tracking_id')?>PX ID</a></th>
				<th class="row2"><?=$this->getOrderLink('vici_lead_id')?>Lead ID</a></th>
				<th class="row2"><?=$this->getOrderLink('dispo')?>Dispo</a></th>
				<th class="row2"><?=$this->getOrderLink('result')?>Result</a></th>
			</tr><?

// 			['[microtime:micro_time]','align_center'],
// 			['agent_username','align_left'],
// 			['lead_tracking_id','align_center'],
// 			['vici_lead_id','align_center'],
// 			['dispo','align_center'],
// 			['result','align_center'],



			// MAGICAL FUCKING AJAX FAIRIES WILL POPULATE THIS SECTION

			?></table></td>
		</tr></table>

		<script>


			 $(function() {

				 //$( "#tabs" ).tabs();

				 $("#view_dispo_log").dialog({
					autoOpen: false,
					width: 500,
					height: 160,
					modal: false,
					draggable:true,
					resizable: false
				});

				 $("#view_dispo_log").dialog("widget").draggable("option","containment","#main-container");
					
			 });


			loadDispos();



		</script><?

	}


	function makeViewDispo($id){

		$row = $_SESSION['dbapi']->dispo_log->getByID($id);

		?><table border="0" width="100%">
		<tr>
			<th>PX ID:</th>
			<td><?=htmlentities($row['lead_tracking_id'])?></td>
		</tr>
		<tr>
			<th>VICI Lead ID:</th>
			<td><?=htmlentities($row['vici_lead_id'])?></td>
		</tr>
		<tr>
			<th>Log:</th>
			<td><?=htmlentities($row['log'])?></td>
		</tr>

		</table><?

	}

	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadDispos();return false;\">";

		return $var;
	}
}
