<?	/***************************************************************
	 *	Lead management tool - Replacement vici lead editing/searching tool
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['lead_management'] = new LeadManagement;


class LeadManagement{


	var $offices = array(
					"90",
					"92",
					"94",
					"98"
				);



	var $table	= 'lead_tracking';			## Classes main table to operate on
	var $orderby	= 'time';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $frm_name = 'leadnextfrm';
	var $index_name = 'lead_list';
	var $order_prepend = 'lead_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $dispo_options = array(
			'A'=>"Answering Machine",
			'B'=>"Busy",

			'CALLBK'=>"Call Back",

			'DC'=>"Disconnected Num",
			'DEC'=>"Decline",
			'DNC'=>"Do NOT call",
			'hangup'=>"Hangup",
			'MXFER'=>"Manager XFER",
			'NOVERI'=>"No Verifier",
			'NI'=>"Not Interested",
			'NIX'=>"NIX",
			'OTHER'=>"OTHER dispo", // so very specific
			'REVIEW'=>"Review Sale",
			'REVIEWCC'=>"Review CC Sale",
			'PAIDCC'=>"PAIDCC/DRIPP",
			'SALE'=>"Sale",
			'SALE/PAIDCC'=>"Any/ALL Sales",
			'SALECC'=>"Rousted CC Sale",
			'XFER'=>"Verifier Transfer",


			'VOID'=>"Rousting Void"

		);



	function LeadManagement(){


		## REQURES DB CONNECTION!
		include_once($_SESSION['site_config']['basedir']."/utils/db_utils.php");


		$this->handlePOST();
	}


	function handlePOST(){

		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/lead_management.api.php FOR POST HANDLING!
		// <3 <3 -Jon

	}

	function handleFLOW(){
		# Handle flow, based on query string

		if(!checkAccess('lead_management')){


			accessDenied("Lead Management");

			return;

		}else{
			if(isset($_REQUEST['edit_lead'])){

				$this->makeEdit(intval($_REQUEST['edit_lead']));

			}else{
				$this->listEntrys();
			}

		}

	}




	function listEntrys(){


		?><script>

			var lead_delmsg = 'Are you sure you want to delete this record?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";

			var <?=$this->order_prepend?>orderby_default = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir_default= "<?=$this->orderdir?>";

			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;



			var LeadsTableFormat = [
				['id','align_center'],
				['lead_id','align_center'],
				['list_id','align_center'],
				['[get:cluster_name:vici_cluster_id]','align_center'],
				['campaign_code','align_center'],
				['agent_username','align_center'],
				['[time:time]','align_center'],
				['phone_num','align_center'],

				<?/***['[duration:time:agent_dispo_time]','align_center'],***/?>
				['[duration:agent_duration]','align_center'],



				['dispo','align_center'],

				['[concat:first_name:last_name]','align_center'],

				['city','align_center'],
				['state','align_center'],


			];



			function playAudio(url){


					//$('#media_player').dialog("open");

					$('#media_player').children().filter("audio").each(function(){
					    this.pause(); // can't hurt
					    delete(this); // @sparkey reports that this did the trick!
					    $(this).remove(); // not sure if this works after null assignment
					});
					$('#media_player').empty();

					$('#media_player').load("play_rec.php?play_url="+url);

					// RESET OTHERS
					//resetImages();
					// CHANGE IMAGE
					//markPlayButton(call_id);



					// REMOVE AND READD TEH CLOSE BINDING, TO STOP THE AUDIO
					$('#media_player').off("dialogclose");
					$('#media_player').on('dialogclose', function(event) {

						hideAudio();

						//alert("pausing");
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


			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getLeadsURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=lead_management&"+
								"mode=xml&"+
								's_id='+escape(frm.s_id.value)+"&"+
								's_lead_id='+escape(frm.s_lead_id.value)+"&"+
								's_campaign_id='+escape(frm.s_campaign_id.value)+"&"+

								's_firstname='+escape(frm.s_firstname.value)+"&"+
								's_lastname='+escape(frm.s_lastname.value)+"&"+

								's_phone='+escape(frm.s_phone.value)+"&"+
								's_cluster_id='+escape(frm.s_cluster_id.value)+"&"+

								's_status='+escape(frm.s_status.value)+"&"+
								's_agent_username='+escape(frm.s_agent_username.value)+"&"+
								's_verifier_username='+escape(frm.s_verifier_username.value)+"&"+

								's_city='+escape(frm.s_city.value)+"&"+
								's_state='+escape(frm.s_state.value)+"&"+

								's_vici_list_id='+escape(frm.s_vici_list_id.value)+"&"+

								's_office_id='+escape(frm.s_office_id.value)+"&"+



								's_date_month='+escape(frm.stime_month.value)+"&"+'s_date_day='+escape(frm.stime_day.value)+"&"+'s_date_year='+escape(frm.stime_year.value)+"&"+
								's_date2_month='+escape(frm.etime_month.value)+"&"+'s_date2_day='+escape(frm.etime_day.value)+"&"+'s_date2_year='+escape(frm.etime_year.value)+"&"+

								's_date_hour='+escape(frm.stime_hour.value)+"&"+'s_date_min='+escape(frm.stime_min.value)+"&"+'s_date_timemode='+escape(frm.stime_timemode.value)+"&"+
								's_date2_hour='+escape(frm.etime_hour.value)+"&"+'s_date2_min='+escape(frm.etime_min.value)+"&"+'s_date2_timemode='+escape(frm.etime_timemode.value)+"&"+


								's_date_mode='+escape(frm.date_mode.value)+"&"+

								//'s_date='+escape(frm.s_date.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var leads_loading_flag = false;
			var page_load_start;

			/**
			* Load the name data - make the ajax call, callback to the parse function
			*/
			function loadLeads(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = leads_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("NAMES ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('leads_loading_flag = true');
				}

				page_load_start = new Date();


				$('#total_count_div').html('<img src="images/ajax-loader.gif" border="0">');



				loadAjaxData(getLeadsURL(),'parseLeads');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseLeads(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('lead',LeadsTableFormat,xmldoc);


				var enddate = new Date();

				var loadtime = enddate - page_load_start;

				$('#page_load_time').html("Load and render time: "+loadtime+"ms");



//alert(<?=$this->order_prepend?>totalcount+" vs "+<?=$this->order_prepend?>pagesize);

				// ACTIVATE PAGE SYSTEM!
			//	if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('leads',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadLeads()'
								);

			//	}else{

				//	hidePageSystem('leads');

			//	}




				eval('leads_loading_flag = false');
			}


			function handleLeadListClick(id){

				displayEditLeadDialog(id);

			}

			function displayEditLeadDialog(id, sub){

				var objname = 'dialog-modal-edit_lead';


				if(id > 0){
					$('#'+objname).dialog( "option", "title", 'Editing Lead #'+id  );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Lead' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');


				if(sub){

					$('#'+objname).load("index.php?area=lead_management&edit_lead="+id+"&sub="+sub+"&printable=1&no_script=1");
				}else{

					$('#'+objname).load("index.php?area=lead_management&edit_lead="+id+"&printable=1&no_script=1");
				}

				$('#'+objname).dialog('option', 'position', 'center');

			}

			function resetLeadForm(frm){

				frm.reset();

				//frm.s_status.selectedIndex = 0;
				//frm.s_date.selectedIndex = 0;

				frm.s_cluster_id.selectedIndex = 0;
				frm.s_campaign_id.selectedIndex = 0;
				frm.s_lead_id.value = '';
				frm.s_id.value = '';
				frm.s_agent_username.value = '';
				frm.s_verifier_username.value = '';

				frm.s_firstname.value = '';
				frm.s_lastname.value = '';

				frm.s_phone.value = '';


				frm.s_city.value = '';
				frm.s_state.value = '';
				frm.s_status.value = '';


				toggleDateMode('date');


				// RESET ORDER BY
				<?=$this->order_prepend?>orderby = <?=$this->order_prepend?>orderby_default;
				<?=$this->order_prepend?>orderdir = <?=$this->order_prepend?>orderdir_default;



				loadLeads();

			}


			function setPageSize(new_size){

				<?=$this->index_name?> = 0;
				<?=$this->order_prepend?>pagesize = new_size;
				loadLeads();
			}

			var leadsrchtog = false;

			function toggleLeadSearch(){
				leadsrchtog = !leadsrchtog;
				ieDisplay('lead_search_table', leadsrchtog);
			}

			function toggleDateMode(way){

				if(way == 'daterange'){
					$('#nodate_span').hide();
					$('#date1_span').show();

					// SHOW EXTRA DATE FIELD
					$('#date2_span').show();

					// HIDE TIME FIELDS
					$('#time1_span').hide();
					$('#time2_span').hide();

				}else if(way == 'any'){

					$('#nodate_span').show();
					$('#date1_span').hide();
					$('#date2_span').hide();

					// HIDE TIME FIELDS
					$('#time1_span').hide();
					$('#time2_span').hide();

				}else if(way == 'datetimerange'){

					$('#nodate_span').hide();
					$('#date1_span').show();

					// SHOW EXTRA DATE FIELD
					$('#date2_span').show();

					// SHOW TIME FIELDS AS WELL
					$('#time1_span').show();
					$('#time2_span').show();

				}else{
					$('#nodate_span').hide();

					$('#date1_span').show();

					// HIDE SECOND DATE FIELD
					$('#date2_span').hide();

					// HIDE TIME FIELDS
					$('#time1_span').hide();
					$('#time2_span').hide();
				}

			}

		</script>
		<div id="dialog-modal-edit_lead" title="Editing Lead">


		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadLeads();return false">
			<input type="hidden" name="searching_lead">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr class="ui-widget-header">
			<td height="40" class="pad_left">

				Lead Management

			</td>

			<td width="150" align="center">Page Size: <select name="s_pagesize" onchange="setPageSize(this.value);">
				<option value="20">20</option>
				<option value="50">50</option>
				<option value="100">100</option>
				<option value="500">500</option>
			</select></td>

			<td align="right"><?
				/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
				<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
				<tr>
					<td id="leads_prev_td" class="page_system_prev"></td>
					<td id="leads_page_td" class="page_system_page"></td>
					<td id="leads_next_td" class="page_system_next"></td>
				</tr>
				</table>

			</td>
		</tr>

		<tr>
			<td colspan="2"><table border="0" width="700" id="lead_search_table">
			<tr>
				<td rowspan="2" width="70" align="center" style="border-right:1px solid #000">


					<div id="total_count_div"></div>

				</td>
				<th class="row2">PX ID</th>
				<th class="row2">Cluster</th>
				<th class="row2">Campaign</th>
				<th class="row2">Dispo</th>
				<th class="row2"><select name="s_date_mode" id="date_mode" onchange="toggleDateMode(this.value);">
						<option value="date">Date</option>
						<option value="daterange"<?=($_REQUEST['s_date_mode']=='daterange')?' SELECTED ':''?>>Date Range</option>
						<option value="datetimerange"<?=($_REQUEST['s_date_mode']=='datetimerange')?' SELECTED ':''?>>Date/Time Range</option>
						<option value="any"<?=($_REQUEST['s_date_mode']=='any')?' SELECTED ':''?>>ANY</option>
				</select></th>


				<td>

					<input type="submit" value="Search" onclick="<?=$this->index_name?> = 0;"  name="the_Search_button">
				</td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_id" size="5" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>
				<td align="center">
					<?
						echo makeClusterDD('s_cluster_id', $_REQUEST['s_cluster_id'], '', ""); //loadLeads();
					?>
				</td>
				<td align="center">
					<?
						echo makeCampaignIDDD('s_campaign_id', $_REQUEST['s_campaign_id'], '', ""); //loadLeads();
					?>
				</td>
				<td align="center"><?

					echo $this->makeDispoDD('s_status', $_REQUEST['s_status'], "", true);

					/*?><input type="text" name="s_status" size="10" value="<?=htmlentities($_REQUEST['s_status'])?>"><?*/
				?></td>

				<td nowrap><?

					?><span id="date1_span"><?
						echo makeTimebar("stime_", 1, null,false,time());

						?><span id="time1_span" class="nod">

							<br /><?

							echo makeTimebar("stime_", 2, null,false,(time() - 3600));


						?><br /></span><?

					?></span><?

					?><span id="date2_span" class="nod"><br /><?

						echo makeTimebar("etime_",1,null,false,time());

						?><span id="time2_span" class="nod">

							<br /><?

							echo makeTimebar("etime_", 2, null,false,time());


						?></span><?

					?></span>



					<span id="nodate_span" class="nod">
						ANY/ALL DATES
					</span>
				</td>
				<td><input type="button" value="Reset" onclick="resetLeadForm(this.form);resetPageSystem('<?=$this->index_name?>');loadLeads();"></td>
			</tr>
			<tr>
				<td colspan="5"><table border="0" width="100%">
				<tr>
					<th class="row2">Agent</th>
					<th class="row2">Verifier</th>
					<th class="row2">First/Last Name</th>
					<th class="row2">Lead ID</th>

					<th class="row2">Phone</th>
					<th class="row2">City</th>
					<th class="row2">State</th>
					<th class="row2">Vici List ID</th>
					<th class="row2">Office</th>
				</tr>
				<tr>
					<td align="center"><input type="text" name="s_agent_username" size="5" value="<?=htmlentities($_REQUEST['s_agent_username'])?>"></td>
					<td align="center"><input type="text" name="s_verifier_username" size="5" value="<?=htmlentities($_REQUEST['s_verifier_username'])?>"></td>
					<td align="center" NOWRAP >
						<input type="text" name="s_firstname" size="5" value="<?=htmlentities($_REQUEST['s_firstname'])?>">
						<input type="text" name="s_lastname" size="5" value="<?=htmlentities($_REQUEST['s_lastname'])?>">
					</td>

					<td align="center"><input type="text" name="s_lead_id" size="5" value="<?=htmlentities($_REQUEST['s_lead_id'])?>"></td>


					<td align="center"><input type="text" name="s_phone" size="10" value="<?=htmlentities($_REQUEST['s_phone'])?>"></td>
					<td align="center"><input type="text" name="s_city" size="10" value="<?=htmlentities($_REQUEST['s_city'])?>"></td>
					<td align="center"><input type="text" name="s_state" size="10" value="<?=htmlentities($_REQUEST['s_state'])?>"></td>

					<td align="center"><input type="text" name="s_vici_list_id" size="5" value="<?=htmlentities($_REQUEST['s_vici_list_id'])?>"></td>


					<td align="center"><?

					/**if(		($_SESSION['user']['priv'] >= 5) ||
							($_SESSION['user']['allow_all_offices'] == 'yes')
						){**/


						echo makeOfficeDD('s_office_id', $_REQUEST['s_office_id'], '', "", 1);

				/**	}else{


						?><select name="s_office_id">
							<option value="">[All Assigned]</option><?

						foreach($_SESSION['assigned_offices'] as $ofc){
							echo '<option value="'.$ofc.'"';

							if($_REQUEST['s_office_id'] == $ofc) echo ' SELECTED ';

							echo '>Office '.$ofc.'</option>';
						}

						?></select><?


					}**/


					?></td>
				</tr>
				</table></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="990" id="lead_table">
			<tr>
			<?/**
							['id','align_center'],
				['lead_id','align_center'],
				['[cluster_name,vici_cluster_id]','align_center'],
				['agent_username','align_center'],
				['phone_num','align_center'],

				['[concat,first_name,last_name]','align_center'],

				['city','align_center'],
				['state','align_center'],**/
				?>
				<th class="row2"><?=$this->getOrderLink('id')?>ID</a></th>
				<th class="row2"><?=$this->getOrderLink('lead_id')?>Lead ID</a></th>
				<th class="row2"><?=$this->getOrderLink('list_id')?>List ID</a></th>
				<th class="row2"><?=$this->getOrderLink('vici_cluster_id')?>Cluster</a></th>
				<th class="row2"><?=$this->getOrderLink('campaign_id')?>Campaign</a></th>
				<th class="row2"><?=$this->getOrderLink('agent_username')?>Agent</a></th>
				<th class="row2"><?=$this->getOrderLink('time')?>Time</a></th>
				<th class="row2"><?=$this->getOrderLink('phone_num')?>Phone Number</a></th>
				<th class="row2"><?=$this->getOrderLink('agent_duration')?>Duration</a></th>
				<th class="row2">Dispo</th>
				<th class="row2"><?=$this->getOrderLink('first_name')?>First</a>/<?=$this->getOrderLink('last_name')?>Last</a> Name</a></th>

				<th class="row2"><?=$this->getOrderLink('city')?>City</a></th>
				<th class="row2"><?=$this->getOrderLink('state')?>State</a></th>

			</tr><?

			// MAGICAL FUCKING AJAX FAIRIES WILL POPULATE THIS SECTION

			?></table></td>
		</tr>
		<tr>
			<td colspan="2" height="50" valign="bottom">

				<span id="current_time_span" style="font-size:8px">

					Server Time: <?=date("g:ia m/d/Y T")?>

				</span>

			</td>
		</tr></table>

		<script>


			 $(function() {

				 //$( "#tabs" ).tabs();

				 $("#dialog-modal-edit_lead").dialog({
					autoOpen: false,
					width: 780,
					height: 420,
					modal: false,
					draggable:true,
					resizable: false,
					close: function(event, ui){

						hideAudio();
						
					}
				});

				<?
			if(($leadid=intval($_REQUEST['auto_open_lead'])) > 0){

				?>displayEditLeadDialog(<?=$leadid?>, 'general');<?
			}

		?>


			 });


			loadLeads();



		</script><?

	}


	function makeRecordingSection($row){


	/***	THESE FUNCTIONS WERE MOVED TO "listEntrys()" function instead
	 * ?><script>
			function playAudio(url){


				//$('#media_player').dialog("open");

				$('#media_player').children().filter("audio").each(function(){
				    this.pause(); // can't hurt
				    delete(this); // @sparkey reports that this did the trick!
				    $(this).remove(); // not sure if this works after null assignment
				});
				$('#media_player').empty();

				$('#media_player').load("play_rec.php?play_url="+url);

				// RESET OTHERS
				//resetImages();
				// CHANGE IMAGE
				//markPlayButton(call_id);



				// REMOVE AND READD TEH CLOSE BINDING, TO STOP THE AUDIO
				$('#media_player').off("dialogclose");
				$('#media_player').on('dialogclose', function(event) {

					hideAudio();

					//alert("pausing");
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
		<?***/
	
	
		?><div id="media_player" title="Playing Call Recording">


		</div><?


		$this->listRecordings($row, false);

		// WHEN CLUSTERS ARE SPLIT, WE WILL NEED TO GET RECORDINGS FROM BOTH
		if($row['vici_cluster_id'] != $row['verifier_vici_cluster_id']){

			$this->listRecordings($row, true);

		}


	//
	}


	/**
	 * List the recordings that the vici cluster has for this lead
	 * @param $row 	A database assoc-array containing the lead_tracking record for the lead.
	 */
	function listRecordings($leadrow, $verifier_mode = false){

		if($verifier_mode){
			$dbidx = getClusterIndex($leadrow['verifier_vici_cluster_id']);
			$lead_id = $leadrow['verifier_lead_id'];
		}else{
			$dbidx = getClusterIndex($leadrow['vici_cluster_id']);
			$lead_id = $leadrow['lead_id'];
		}


		if($dbidx < 0 || $lead_id < 1){

			// SKIPPING

			return;

		}

//	echo "DBIDX: ".$dbidx." lead:".$lead_id."\n";


		// CONNECT TO THE SPECIFIED CLUSTER
		connectViciDB($dbidx);

		$rowarr = array();
		$res = query("SELECT * FROM asterisk.recording_log WHERE lead_id='$lead_id'",1);
		while($r = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			$rowarr[] = $r;
		}


		?><table border="0" width="100%">
		<tr>
			<th class="ui-widget-header row2 padleft" height="40" colspan="4" align="left"><?=$_SESSION['site_config']['db'][$dbidx]['name']?> Recording logs - lead#<?=$lead_id?></th>
		</tr>
		<tr>
			<th class="row2">Time</th>
			<th class="row2">Duration</th>
			<th class="row2">Agent</th>
			<th class="row2">Recording</th>
			<th class="row2">&nbsp;</th>
		</tr><?

		foreach($rowarr as $rec){

			?><tr>
				<td align="center"><?=date("g:i:sa m/d/Y", $rec['start_epoch'])?></td>
				<td align="center"><?=renderTimeFormattedSTD($rec['length_in_sec'])?></td>
				<td align="center"><?=htmlentities($rec['user'])?></td>
				<td align="center"><?
					if($rec['location']){
						?><a href="<?=htmlentities($rec['location'])?>" target="_blank" onclick="return false">
							<input type="button" value="Download" onclick="window.open('<?=htmlentities($rec['location'])?>')">
						</a><?
					}else{
						echo "-processing-";
					}
				?></td>
				<td align="center"><input type="button" value="PLAY" onclick="playAudio('<?=htmlentities($rec['location'])?>')"></td>
			</tr><?

		}



		?></table><?


		mysqli_close($_SESSION['db']);
	}



	function makeCreateSale($leadrow, $xfer_id){


		connectPXDB();


		if($xfer_id){
			$row = querySQL("SELECT * FROM transfers WHERE id='".$xfer_id."' ");


			$sale = querySQL("SELECT * FROM sales WHERE transfer_id='".$row['id']."' ");

			$timestamp = $row['sale_time'];

			$xfer_time = $row['xfer_time'];



		}

		if(!$xfer_id || !$timestamp){
			$timestamp = time();
		}

		if(!$xfer_time){
			$xfer_time = time();
		}

		?><script>

			function checkCreateSaleForm(frm){

				// CHECK FOR FIELDS THAT MATTER, ONLY WHEN DISPO == SALE
				if(frm.dispo.value == "SALE"){


					if(!frm.agent_user_id.value){
						alert('ERROR: Please select the agent from the dropdown.');

						try{ frm.agent_username.select(); }catch(e){}

						return false;
					}

//					if(!frm.agent_amount.value){
//						alert('Error: Please enter the agents Amount ');
//						return false;
//					}


					if(!frm.verifier_user_id.value){
						alert('ERROR: Please select the verifier from the dropdown.');

						try{ frm.verifier_user_id.select(); }catch(e){}

						return false;
					}

					if(!frm.verifier_amount.value){
						alert('ERROR: Please enter the Verifiers closing Amount');

						try{ frm.verifier_amount.select(); }catch(e){}

						return false;
					}

				}

				if((frm.dispo.value != "SALE" && frm.dispo.value != "PAIDCC" && frm.dispo.value != "SALECC") && '<?=$row['verifier_dispo']?>' == "SALE"){

					if(!confirm('CHANGING A SALE TO NON-SALE WILL DELETE THE SALE RECORD.\nAre you sure you want to do this?')){
						return false;
					}

				}


				if((frm.dispo.value != "SALE" && frm.dispo.value != "PAIDCC" && frm.dispo.value != "SALECC") && frm.dispo.value != "REVIEW" && '<?=$row['verifier_dispo']?>' == "REVIEW"){

					if(!confirm('CHANGING A REVIEW TO NON-SALE WILL DELETE THE SALE RECORD.\nAre you sure you want to do this?')){
						return false;
					}

				}


				postSaleData(frm);

				return false;
			}


			function postSaleData(frm){

				if(doubleclkcockblocker == true){

					//alert("Skipping update, already submitting!");
					return false;

				}

				startBlocker();



				var params = getFormValues(frm);

				//alert("Form posting: "+params);

				$.ajax({
					type: "POST",
					cache: false,
					url: 'api/api.php?get=lead_management&mode=xml&action=create_sale',
					data: params,
					error: function(){
						alert("Error saving lead form. Please contact an admin.");
					},
					success: function(msg){



						var result = handleEditXML(msg);
						var res = result['result'];

						if(res <= 0){

							alert(result['message']);

							return;

						}


						displayEditLeadDialog(frm.editing_lead.value, "sales");

						alert(result['message']);

					}


				});


			}


			function toggleDispo(val){

				if(val != 'SALE' && val != 'PAIDCC' && val != 'SALECC'){

					$('#salerow').hide();
					$('#officerow').hide();

				}else{
					$('#salerow').show();
					$('#officerow').show();
				}
			}

		</script>


		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkCreateSaleForm(this); return false">
			<input type="hidden" id="editing_lead" name="editing_lead" value="<?=$leadrow['id']?>" >

			<input type="hidden" name="creating_sale" value="<?=$row['id']?>">

		<table border="0" align="center">
		<tr>
			<th colspan="4" height="30" class="ui-widget-header">

				<?=($xfer_id)?"Editing Sale":"Creating new Sale"?>
			</th>
		</tr>
		<tr>
			<th>Dispo</th>
			<td colspan="3"><?=$this->makeDispoDD('dispo', ((!$xfer_id)?'SALE':$leadrow['dispo']), "toggleDispo(this.value)", " ", "SALE/PAIDCC")?></td>
		</tr>


		<tr id="localtimerow">
			<th>Last Local Call Time:</th>
			<td colspan="3"><?

				echo $row['vici_last_call_time'];
				//echo makeTimebar("last_",0,null,false,$xfer_time,$extra_attr="");

			?></td>

		</tr>

		<tr id="xferrow">
			<th>XFER Time:</th>
			<td colspan="3"><?

				echo makeTimebar("xfer_",0,null,false,$xfer_time,$extra_attr="");

			?></td>

		</tr>
		<tr id="salerow">
			<th>Sale Time:</th>
			<td colspan="3"><?

				echo makeTimebar("sale_",0,null,false,$timestamp,$extra_attr="");

			?></td>

		</tr>

		<tr>
			<th align="left">Agent:</th>
			<td><?

				//$username = $_SESSION['dbapi']->lead_management->getUserByID();


				echo makeUserIDDD('agent_user_id', $leadrow['user_id'], '', '[Select user]');

			?></td>
			<th align="left">Agent $:</th>
			<td><input type="text" size="4" name="agent_amount" value="<?=($row['agent_amount'] > 0)?$row['agent_amount']:$leadrow['amount']?>"></td>
		</tr>


		<tr>
			<th align="left">Verifier:</th>
			<td><?

				echo makeUserIDDD('verifier_user_id', $leadrow['verifier_id'], '', '[Select user]');

			?></td>

			<th align="left">Verifer $:</th>
			<td><input type="text" size="4" name="verifier_amount" value="<?=$row['verifier_amount']?>"></td>
		</tr>

		<tr id="commentrow">
			<th align="left">Comment:</th>
			<td colspan="3">
				<input type="text" name="comments" size="40" value="<?=($sale['comments'])?$sale['comments']:$leadrow['comments']?>">
			</td>
		</tr>


		<tr id="officerow">
			<th align="left">Office:</th>
			<td colspan="3"><?

			 	echo makeOfficeDD('office', (($sale)?$sale['office']:$leadrow['office']), '', "", false);

				/***<select name="office">
			<?
				foreach($this->offices as $ofc){

					echo '<option value="'.$ofc.'" ';

					if($sale){
						echo ($ofc == $sale['office'])?' SELECTED ':'';
					}else{
						echo ($ofc == $leadrow['office'])?' SELECTED ':'';
					}

					echo '>'.$ofc.'</option>';
				}



			?>
			</select>***/

			?></td>
		</tr>
		<?/*<tr>
			<th align="left" colspan="4">

				<input type="checkbox" CHECKED name="update_lead_dispo" value="1">

				Update Leads Dispo-Status as well? (<a href="#" title="Consult the Help Horse with your inquiry" onclick="alert('This will set the lead records LAST DISPO to what ever you set the dispo to here.\nThis is usually done on the most recent sale records only.');return false">HELP?</a>)

			</th>
		</tr>*/?>


		<tr>
			<td colspan="4" align="center">
				<input type="submit" value="Update">

				&nbsp;&nbsp;&nbsp;&nbsp;

				<input type="button" value="Cancel" onclick="clearSection()">
			</td>
		</tr>
		</form>
		</table>
		<script>

			toggleDispo( $('#dispo').val() );

		</script><?
	}

	function makeResendSale($leadrow, $sale_id){
		
		$sale_id = intval($sale_id);
		
		connectPXDB();
		
		$sale = querySQL("SELECT * FROM `sales` WHERE id='$sale_id' ")

		?><script>



			function checkResendSaleForm(frm){




//				if(frm.dispo.value != "SALE" && (frm.change_dispo.value == "SALE" || frm.change_dispo.value == "PAIDCC")){
//
//					if(!confirm("CHANGING A SALE TO NON-SALE WILL DELETE THE SALE RECORD.\nAre you sure you want to do this?"))return false;
//				}

				postDispoChange(frm);


				return false;
			}






			function postDispoChange(frm){


				if(doubleclkcockblocker == true){

					alert("Skipping update, already submitting!");
					return false;

				}

				startBlocker();



				var params = getFormValues(frm);

					//alert("Form validated, posting");

				$.ajax({
					type: "POST",
					cache: false,
					url: 'api/api.php?get=lead_management&mode=xml&action=resend_sale',
					data: params,
					error: function(){
						alert("Error saving lead form. Please contact an admin.");
					},
					success: function(msg){



						var result = handleEditXML(msg);
						var res = result['result'];

						if(res <= 0){

							alert(result['message']);

							return;

						}
//
//
//						loadLeads();
//
						displayEditLeadDialog(res, "sales"); //frm.editing_lead.value, "sales");

						alert(result['message']);

					}


				});


			}

		</script>


		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkResendSaleForm(this); return false">
			<input type="hidden" id="editing_lead" name="editing_lead" value="<?=$leadrow['id']?>" >
			<input type="hidden" id="editing_lead" name="editing_sale_id" value="<?=$sale_id?>" >

		<table id="change_dispo_table" border="0" align="center">
		<tr>
			<th class="ui-widget-header" height="30" colspan="2">Resend Sale</th>
		</tr><?
		
		if($sale['is_paid'] != 'no'){
		
			?><tr>
				<td colspan="2" align="center" style="font-size:14px;color:#ff0000">
				
					WARNING: Resubmitting Credit card sales is not supported.
				
				</td>
			</tr><?
		}
		
		?><tr>
			<th>Sale ID#</th>
			<td><?=$sale_id?></td>
		</tr>
		<tr>
			<th>Reason</th>
			<td><input type="text" name="resend_reason" value="" /></td>
		</tr>


		<tr>
			<td colspan="2" align="center">
				<input type="submit" value="Save Change" >

				&nbsp;&nbsp;&nbsp;&nbsp;

				<input type="button" value="Cancel" onclick="clearSection()">
			</td>
		</tr>
		</form>
		</table>
		<script>

			//$('#change_dispo_table').hide();

			//toggleMode($('#mode').val());

			applyUniformity();

		</script><?



	}




	function makeChangeDispo($leadrow){


		connectPXDB();

		?><script>



			function checkChangeDispoForm(frm){




				if((frm.dispo.value != "SALE" && frm.dispo.value != 'PAIDCC' && frm.dispo.value != 'SALECC') && (frm.change_dispo.value == "SALE" || frm.change_dispo.value == "PAIDCC" || frm.change_dispo.value == "SALECC")){

					if(!confirm("CHANGING A SALE TO NON-SALE WILL DELETE THE SALE RECORD.\nAre you sure you want to do this?"))return false;
				}

				postDispoChange(frm);


				return false;
			}






			function postDispoChange(frm){


				if(doubleclkcockblocker == true){

					alert("Skipping update, already submitting!");
					return false;

				}

				startBlocker();



				var params = getFormValues(frm);

					//alert("Form validated, posting");

				$.ajax({
					type: "POST",
					cache: false,
					url: 'api/api.php?get=lead_management&mode=xml&action=change_dispo',
					data: params,
					error: function(){
						alert("Error saving lead form. Please contact an admin.");
					},
					success: function(msg){



						var result = handleEditXML(msg);
						var res = result['result'];

						if(res <= 0){

							alert(result['message']);

							return;

						}
//
//
//						loadLeads();
//
						displayEditLeadDialog(frm.editing_lead.value, "sales");

						alert(result['message']);

					}


				});


			}

		</script>


		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkChangeDispoForm(this); return false">
			<input type="hidden" id="editing_lead" name="editing_lead" value="<?=$leadrow['id']?>" >

			<input type="hidden" name="change_dispo" id="change_dispo" value="<?=$leadrow['dispo']?>">

		<table id="change_dispo_table" border="0" align="center">
		<tr>
			<th class="ui-widget-header" height="30" colspan="2">Change Dispo Status</th>
		</tr>
		<tr>
			<th>Current Lead Dispo</th>
			<td><?=($leadrow['dispo'])?$leadrow['dispo']:'-In Call-'?></td>
		</tr>
		<tr>
			<th>Dispo</th>
			<td><?=$this->makeDispoDD('dispo', $leadrow['dispo'], "", " ", array("SALE","PAIDCC","SALE/PAIDCC","SALECC"))?></td>
		</tr>
		<?/**
					'PAIDCC'=>"PAIDCC/DRIPP",
			'SALE'=>"Sale",
			'SALECC/PAIDCC'=>"Any CC Sale",
			'SALECC'=>"Rousted CC Sale",
			**/?>


		<tr>
			<td colspan="2" align="center">
				<input type="submit" value="Save Change" onclick="return checkChangeDispoForm(this.form)">

				&nbsp;&nbsp;&nbsp;&nbsp;

				<input type="button" value="Cancel" onclick="clearSection()">
			</td>
		</tr>
		</form>
		</table>
		<script>

			//$('#change_dispo_table').hide();

			//toggleMode($('#mode').val());

			applyUniformity();

		</script><?


	}






	function makeListSales($leadrow){

		//print_r($leadrow);

		connectPXDB();


		// CURRENTLY ONLY 1 RECORD PER, BUT MIGHT BE NEEDED FOR FUTURE -Jon 4/12/2015
		$sql = "SELECT * FROM transfers WHERE lead_tracking_id=".$leadrow['id']." ";

		//echo $sql;

		$res = query($sql, 1);
		$rowarr = array();
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			$rowarr[] = $row;
		}


		$res = query("SELECT * FROM sales WHERE lead_tracking_id='".$leadrow['id']."' ", 1);
		$sale_rowarr = array();
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			$sale_rowarr[] = $row;
		}




		$colspan = 6;

		?>
		<table border="0" width="100%">
		<tr>
			<th class="ui-widget-header row2 padleft" height="40" colspan="<?=$colspan?>">Transfers</th>
		</tr>
		<tr>
			<th class="row2">Transfer Time</th>
			<th class="row2">Sale Time</th>
			<th class="row2">Agent</th>
			<th class="row2">Verifier</th>
			<th class="row2">Verifier Dispo</th>
			<th class="row2">&nbsp;</th>
		</tr><?



		if(count($rowarr) == 0){

			?><tr><td colspan="<?=$colspan?>" align="center"><i>No transfer records found</i></tr><?
		}

		$total = 0;

		$change_dispo_allowed = checkAccess('lmt_change_dispo');
		$create_sale_allowed = checkAccess('lmt_create_sale');
		
		
		foreach($rowarr as $xfer){


			?><tr>
				<td align="center"><?=date("g:i:sa m/d/Y", $xfer['xfer_time'])?></td>
				<td align="center"><?=($xfer['sale_time'] > 0)?date("g:i:sa m/d/Y", $xfer['sale_time']):'-'?></td>
				<td align="center"><?=htmlentities($xfer['agent_username'])?> @ $<?=number_format($xfer['agent_amount'])?></td>
				<td align="center"><?=htmlentities($xfer['verifier_username'])?> @ $<?=number_format($xfer['verifier_amount'])?></td>
				<td align="center"><?=htmlentities($xfer['verifier_dispo'])?></td>
				<td align="center">
				<?
				if($create_sale_allowed){
					?><input type="button" value="Change" onclick="loadSaleSection(<?=$xfer['id']?>)"><?
				}else{
					?>&nbsp;<?
				}
				
				?></td>
			</tr><?


			if($xfer['sale_time'] > 0){
				$total += $xfer['verifier_amount'];
			}

		}

		/**?><tr><th align="left" colspan="<?=$colspan?>">Total: $<?=number_format($total)?></th></tr><?**/


		?></table>
		<br /><?


		$colspan = 8;



		?><table border="0" width="100%">
		<tr>
			<th colspan="<?=$colspan?>" class="ui-widget-header row2 padleft" height="40">Sales</th>
		</tr>
		<tr>
			<th class="row2">ID</th>
			<th class="row2">Local Call Time</th>
			<th class="row2">Sale Time</th>
			<th class="row2">Office</th>
			<th class="row2">Call Group</th>

			<th class="row2" align="right">Amount</th>
			<th class="row2" align="right">Is Paid</th>
			<th class="row2">&nbsp;</th>
		</tr><?

		if(count($sale_rowarr) == 0){
			?><tr>
				<th align="center" colspan="<?=$colspan?>"><i>No Sales records found.</i></th>
			</tr><?
		}


		foreach($sale_rowarr as $sale){

			?><tr>
				<td align="center"><?=$sale['id']?></td>
				<td align="center"><?=$sale['vici_last_call_time']?></td>
				<td align="center"><?=date("g:i:sa m/d/Y", $sale['sale_time'])?></td>
				<td align="center"><?=$sale['office']?></td>
				<td align="center"><?=$sale['call_group']?></td>

				<td align="right">$<?=$sale['amount']?></td>
				<td align="right"><?=$sale['is_paid']?></td>
				<td align="right"><?

					$curdate = date("m/d/Y");
					$saledate = date("m/d/Y", $sale['sale_time']);

					if($saledate != $curdate && $create_sale_allowed){

						?><input type="button" value="Resend Sale" onclick="loadSaleResendSection(<?=intval($sale['id'])?>)"><?

					}else{

						echo '&nbsp;';

					}

				?></td>
			</tr><?

			if($sale['resend_reason']){

				?><tr>
					<th align="center" colspan="<?=$colspan?>">
						<i>
							Resubmitting sale, Reason: <?=$sale['resend_reason']?>
						</i>
					</th>
				</tr><?

			}
		}


		?></table>

		<br />
		<?
		
		if($change_dispo_allowed){
			?><center><?
			
			if($change_dispo_allowed){
				?><input type="button" value="Change Dispo" onclick="loadDispoSection()"><?
			}
			if($create_sale_allowed){
				?><input type="button" value="Create new XFER and Sale" onclick="loadSaleSection(0)"><?
				
			}
			
			?></center><?
			
		}
		?>

		<br />

		<hr />

		<div id="loadthefuckerrighthere">

		</div>
		<a name="look_at_me"></a>



		<script>


			applyUniformity();

		</script><?

	}




	/**
	 * If $blank_entry is a String, it will render as the name of the blank entry, instead of "[ALL]"
	 */
	function makeDispoDD($name, $sel, $onchange, $blank_entry = false, $skip_dispos = null){

		$out = '<select name="'.$name.'" id="'.$name.'" ';

		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';

		$out .= '>';


		if($blank_entry){

			$out .= '<option value="" '.((trim($sel) == '')?' SELECTED ':'').'>'.((is_string($blank_entry))?$blank_entry:"[All]").'</option>';

		}

		foreach($this->dispo_options as $dispo_code=>$dispo_name){

			if($skip_dispos){

				if(is_array($skip_dispos)){

					foreach($skip_dispos as $tmpdispo){
						if($dispo_code == $tmpdispo){
							continue 2;
						}
					}

				}else{

					if($dispo_code == $skip_dispos){
						continue;
					}
				}


			}


			$out .= '<option value="'.$dispo_code.'" ';

			$out .= ($sel == $dispo_code)?' SELECTED ':'';

			$out .= '>'.$dispo_code.' - '.$dispo_name.'</option>';


		}

		$out .= '</select>';


		return $out;
	}



	function makeEdit($id){

		$id = intval($id);


		if($id){

			$row = $_SESSION['dbapi']->lead_management->getByID($id);


		}

		?><script>


			var doubleclkcockblocker = false;


			function resetBlocker(){
				doubleclkcockblocker = false;
			}

			function startBlocker(){

				doubleclkcockblocker = true;
				setTimeout("resetBlocker()", 2000);

			}


			function validateLeadField(name,value,frm){

				//alert(name+","+value);


				switch(name){
				default:

					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;

//				case 'filename':
//
//
//					if(!value)return false;
//
//					return true;
//
//
//					break;

				}
				return true;
			}



			function checkLeadFrm(frm){


				var params = getFormValues(frm,'validateLeadField');


				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;
//
//					case 'filename':
//
//						alert("Please enter the filename for this name.");
//						eval('try{frm.'+params[0]+'.select();}catch(e){}');
//						break;

					}

				// SUCCESS - POST AJAX TO SERVER
				}else{


					//alert("Form validated, posting");

					$.ajax({
						type: "POST",
						cache: false,
						url: 'api/api.php?get=lead_management&mode=xml&action=edit',
						data: params,
						error: function(){
							alert("Error saving lead form. Please contact an admin.");
						},
						success: function(msg){

//alert(msg);

							var result = handleEditXML(msg);
							var res = result['result'];

							if(res <= 0){

								alert(result['message']);

								return;

							}


							loadLeads();


							displayEditLeadDialog(res);

							alert(result['message']);

						}


					});

				}

				return false;

			}

			function clearSection(){
				$('#loadthefuckerrighthere').html("");
			}

			function loadDispoSection(){

				loadSectionInTab('?area=lead_management&edit_lead=<?=$id?>&sub=change_dispo&printable=1&no_script=2');


			}

			function loadSaleSection(id){

				loadSectionInTab('?area=lead_management&edit_lead=<?=$id?>&sub=create_sale&xfer_id='+id+'&printable=1&no_script=2');

			}

			function loadSaleResendSection(id){

				loadSectionInTab('?area=lead_management&edit_lead=<?=$id?>&sub=resend_sale&sale_id='+id+'&printable=1&no_script=2');

			}

			function loadSectionInTab(url){

				$('#loadthefuckerrighthere').load(url);



			}



			// SET TITLEBAR
			$('#dialog-modal-edit-lead').dialog( "option", "title", '<?=($id)?'Editing Lead #'.$id.' - '.addslashes(htmlentities($row['first_name'].' '.$row['last_name'])):'Adding new Lead'?>' );



		</script>

		<?

		if(intval($_REQUEST['no_script']) < 2){
		?><script>
				$(function() {
					$( "#client_tabs" ).tabs();
				});
		</script>
		<div id="client_tabs">
			<ul>
				<li><a href="?area=lead_management&edit_lead=<?=$id?>&sub=general&printable=1&no_script=2">General</a></li>
				<li><a href="?area=lead_management&edit_lead=<?=$id?>&sub=sales&printable=1&no_script=2">Xfers/Sales</a></li>
				<li><a href="?area=lead_management&edit_lead=<?=$id?>&sub=recordings&printable=1&no_script=2">Recordings</a></li>
			</ul>
		</div><?

		}else{

			switch($_REQUEST['sub']){
			default:
			case 'general':

				$vici_url = getEditLeadURL($row['vici_cluster_id'], $row['lead_id']);

				$vici_prod_search_url= getSearchLeadURL($row['vici_cluster_id'], $row['phone_num']);


				if($row['verifier_vici_cluster_id'] > 0 && $row['verifier_vici_cluster_id'] != $row['vici_cluster_id']){

					$vici_verifier_url = getEditLeadURL($row['verifier_vici_cluster_id'], $row['verifier_lead_id']);

					$vici_ver_search_url = getSearchLeadURL($row['verifier_vici_cluster_id'], $row['phone_num']);
				}

				
				if(checkAccess('lmt_edit_lead')){

				?><form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkLeadFrm(this); return false">
					<input type="hidden" id="editing_lead" name="editing_lead" value="<?=$id?>" >

				<table border="0" width="100%">
				<tr valign="top">
					<td>

						<table border="0" align="center">
						<tr>
							<th align="left" height="25">First Name:</th>
							<td><input name="first_name" type="text" size="30" value="<?=htmlentities($row['first_name'])?>"></td>
						</tr>
						<tr>
							<th align="left" height="25">Last Name:</th>
							<td><input name="last_name" type="text" size="30" value="<?=htmlentities($row['last_name'])?>"></td>
						</tr>
						<tr>
							<th align="left" height="25">Address:</th>
							<td><input name="address1" type="text" size="30" value="<?=htmlentities($row['address1'])?>"></td>
						</tr>
						<tr>
							<th align="left" height="25">Address 2:</th>
							<td><input name="address2" type="text" size="30" value="<?=htmlentities($row['address2'])?>"></td>
						</tr>
						<tr>
							<th align="left" height="25">City/State:</th>
							<td>
								<input name="city" type="text" size="20" value="<?=htmlentities($row['city'])?>">
								<input name="state" type="text" size="5" value="<?=htmlentities($row['state'])?>">
							</td>
						</tr>
						<tr>
							<th align="left" height="25">Zip Code:</th>
							<td><input name="zip_code" type="text" size="10" value="<?=htmlentities($row['zip_code'])?>"></td>
						</tr>
						<tr>
							<th align="left" height="25">Comments:</th>
							<td><input name="comments" type="text" size="30" value="<?=htmlentities($row['comments'])?>"></td>
						</tr>
						<tr>
							<th align="left" height="25">Occupation:</th>
							<td><input name="occupation" type="text" size="30" value="<?=htmlentities($row['occupation'])?>"></td>
						</tr>
						<tr>
							<th align="left" height="25">Employer:</th>
							<td><input name="employer" type="text" size="30" value="<?=htmlentities($row['employer'])?>"></td>
						</tr>
						</table>

					</td>
					<td>

						<table border="0" align="center">
						<tr>
							<th align="left" height="25">Phone Number:</th>
							<td><?=format_phone($row['phone_num'])?></td>
						</tr>
						<tr>
							<th align="left" height="25">Caller ID #:</th>
							<td><?=($row['outbound_phone_num'] > 0)?format_phone($row['outbound_phone_num']):'-'?></td>
						</tr>
						<tr>
							<th align="left" height="25">Time Added:</th>
							<td><?=date("g:ia m/d/Y", $row['time'])?></td>
						</tr>
						<tr>
							<th align="left" height="25">PX lead ID#</th>
							<td>
								<?
								
									echo htmlentities($row['id']);
									
									
									
									
									if($row['px_server_id'] > 0 ){
										
										$server = getPXServer($row['px_server_id']);
										
										echo ' - PX Server: ';
										
										echo $server['name'];
										
										if($_SESSION['user']['priv'] >= 5){
											echo ' ('.$server['ip_address'].')';
										}

									}
								
								
								
								?>
							
							
							</td>
						</tr>
						<tr>
							<th align="left" height="25">Vici Lead ID#:</th>
							<td>
								<a href="<?=$vici_url?>" target="_blank"><u><?=htmlentities($row['lead_id']).' on '.getClusterName($row['vici_cluster_id'])?></u></a>
								 |
								<a href="<?=$vici_prod_search_url?>" target="_blank"><u>Search by Phone</u></a>

							</td>
						</tr><?

						// CROSS CLUSTER
						if($row['verifier_vici_cluster_id'] > 0 && $row['verifier_vici_cluster_id'] != $row['vici_cluster_id']){

							?><tr>
								<th align="left" height="25">Verifier Lead ID#:</th>
								<td>
									<a href="<?=$vici_verifier_url?>" target="_blank"><u><?=htmlentities($row['verifier_lead_id']).' on '.getClusterName($row['verifier_vici_cluster_id'])?></u></a>
									 |
								 	<a href="<?=$vici_ver_search_url?>" target="_blank"><u>Search by Phone</u></a>
								 </td>
							</tr><?



						}


						?><tr>

							<th align="left" height="25">Office/Group:</th>
							<td><?

								echo $row['office'].' / '.$row['user_group'];

							?></td>
						</tr><?







						?><tr>
							<th align="left" height="25">Problem Call:</th>
							<td><?

								if($row['problem'] == 'yes'){

									echo "Yes".(trim($row['problem_description']))?" - ".$row['problem_description']:'';

								}else{

									echo "No";
								}

							?></td>
						</tr>

						<tr>
							<th align="left" height="25">Dispo:</th>
							<td><?=($row['dispo'])?htmlentities($row['dispo']).((checkAccess('lmt_change_dispo'))?'&nbsp;&nbsp;<input type="button" value="Change" onclick="$(\'#client_tabs\').tabs( \'option\', \'active\', 1 );">':''):'-In Call-'?> </td>
						</tr><?

						if($id > 0 && checkAccess('action_log')){
						?><tr>
							<td colspan="2" align="center" style="padding-top:10px">

								<input type="button" value="View Change History" style="font-size:10px" onclick="viewChangeHistory('lead_management', <?=$row['id']?>)" />


							</td>
						</tr><?
						}

						?></table>


					</td>
				</tr>
				<tr>
					<th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
				</tr>
				</form>
				</table><?
				
				// VIEW ONLY
				}else{
					
					?><table border="0" width="100%">
					<tr valign="top">
						<td>
	
							<table border="0" align="center">
							<tr>
								<th align="left" height="25">First Name:</th>
								<td><?=htmlentities($row['first_name'])?></td>
							</tr>
							<tr>
								<th align="left" height="25">Last Name:</th>
								<td><?=htmlentities($row['last_name'])?></td>
							</tr>
							<tr>
								<th align="left" height="25">Address:</th>
								<td><?=htmlentities($row['address1'])?></td>
							</tr>
							<tr>
								<th align="left" height="25">Address 2:</th>
								<td><?=htmlentities($row['address2'])?></td>
							</tr>
							<tr>
								<th align="left" height="25">City/State/Zip:</th>
								<td>
									<?=htmlentities($row['city'])?>, <?=htmlentities($row['state'])?> <?=htmlentities($row['zip_code'])?>
								</td>
							</tr>
							<tr>
								<th align="left" height="25">Comments:</th>
								<td><?=htmlentities($row['comments'])?></td>
							</tr>
							<tr>
								<th align="left" height="25">Occupation:</th>
								<td><?=htmlentities($row['occupation'])?></td>
							</tr>
							<tr>
								<th align="left" height="25">Employer:</th>
								<td><?=htmlentities($row['employer'])?></td>
							</tr>
							</table>
	
						</td>
						<td>
	
							<table border="0" align="center">
							<tr>
								<th align="left" height="25">Phone Number:</th>
								<td><?=format_phone($row['phone_num'])?></td>
							</tr>
							<tr>
								<th align="left" height="25">Time Added:</th>
								<td><?=date("g:ia m/d/Y", $row['time'])?></td>
							</tr>
							<tr>
								<th align="left" height="25">PX lead ID#</th>
								<td><?=htmlentities($row['id'])?></td>
							</tr>
							<tr>
								<th align="left" height="25">Vici Lead ID#:</th>
								<td>
									<a href="<?=$vici_url?>" target="_blank"><u><?=htmlentities($row['lead_id']).' on '.getClusterName($row['vici_cluster_id'])?></u></a>
									 |
									<a href="<?=$vici_prod_search_url?>" target="_blank"><u>Search by Phone</u></a>
	
								</td>
							</tr><?
	
							// CROSS CLUSTER
							if($row['verifier_vici_cluster_id'] > 0 && $row['verifier_vici_cluster_id'] != $row['vici_cluster_id']){
	
								?><tr>
									<th align="left" height="25">Verifier Lead ID#:</th>
									<td>
										<a href="<?=$vici_verifier_url?>" target="_blank"><u><?=htmlentities($row['verifier_lead_id']).' on '.getClusterName($row['verifier_vici_cluster_id'])?></u></a>
										 |
									 	<a href="<?=$vici_ver_search_url?>" target="_blank"><u>Search by Phone</u></a>
									 </td>
								</tr><?
	
	
	
							}
	
	
							?><tr>
	
								<th align="left" height="25">Office/Group:</th>
								<td><?
	
									echo $row['office'].' / '.$row['user_group'];
	
								?></td>
							</tr><?
	
	
	
	
	
	
	
							?><tr>
								<th align="left" height="25">Problem Call:</th>
								<td><?
	
									if($row['problem'] == 'yes'){
	
										echo "Yes".(trim($row['problem_description']))?" - ".$row['problem_description']:'';
	
									}else{
	
										echo "No";
									}
	
								?></td>
							</tr>
	
							<tr>
								<th align="left" height="25">Dispo:</th>
								<td><?=($row['dispo'])?htmlentities($row['dispo']).((checkAccess('lmt_change_dispo'))?'&nbsp;&nbsp;<input type="button" value="Change" onclick="$(\'#client_tabs\').tabs( \'option\', \'active\', 1 );">':''):'-In Call-'?> </td>
							</tr><?
	
							if($id > 0 && checkAccess('action_log')){
							?><tr>
								<td colspan="2" align="center" style="padding-top:10px">
	
									<input type="button" value="View Change History" style="font-size:10px" onclick="viewChangeHistory('lead_management', <?=$row['id']?>)" />
	
	
								</td>
							</tr><?
							}
	
							?></table>
	
	
						</td>
					</tr>
					</table><?
				}
					

				break;

			case 'resend_sale'://&sale_id'

				$this->makeResendSale($row, $_REQUEST['sale_id']);

				?><script>
					window.location = '#look_at_me';
				</script><?


				break;

			case 'change_dispo':

				if(checkAccess('lmt_change_dispo')){
					$this->makeChangeDispo($row);
				}else{
					echo "ACCESS DENIED TO CHANGE DISPO";
				}

				?><script>
					window.location = '#look_at_me';
				</script><?


				break;
			case 'create_sale':

				if(checkAccess('lmt_change_dispo')){
					
					
					$xfer_id = intval($_REQUEST['xfer_id']);

					$this->makeCreateSale($row, $xfer_id);
					
				}else{
					
					echo "ACCESS DENIED TO CHANGE DISPO";
					
				}

				//$this->makeChangeDispo($row);

				?><script>
					window.location = '#look_at_me';
				</script><?

				break;

			case 'modify_sale':

				//$this->makeChangeDispo($row);


				break;

			case 'sales':

				$this->makeListSales($row);


				break;
			case 'recordings':

				$this->makeRecordingSection($row);

				break;
			}


		}

		?></form><?


	}



	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadLeads();return false;\">";

		return $var;
	}
}
