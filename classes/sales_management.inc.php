<?	/***************************************************************
	 *	SALES management tool - A tool to access the SALES table directly, w/o lead_tracking record being required
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['sales_management'] = new SalesManagement;


class SalesManagement{




	var $table	= 'sales';			## Classes main table to operate on
	var $orderby	= 'sale_time';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $frm_name = 'salenextfrm';
	var $index_name = 'sale_list';
	var $order_prepend = 'sale_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages


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

		if(!checkAccess('sales_management')){


			accessDenied("Sales Management");

			return;

		}else{
			if(isset($_REQUEST['view_sale'])){

				$this->makeView(intval($_REQUEST['view_sale']));

			}else{
				$this->listEntrys();
			}

		}

	}




	function listEntrys(){


		?><script>

			var sale_delmsg = 'Are you sure you want to delete this sale?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";

			var <?=$this->order_prepend?>orderby_default = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir_default= "<?=$this->orderdir?>";

			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;



			var SalesTableFormat = [
				//['id','align_center'],
				['lead_tracking_id','align_center'],
				['agent_lead_id','align_center'],
				['[get:cluster_name:agent_cluster_id]','align_center'],
				['campaign_code','align_center'],
				['[concat:agent_username:verifier_username]','align_center'],
				['[time:sale_time]','align_center'],
				['amount','align_center'],
				['phone','align_center'],

				//['[duration:agent_duration]','align_center'],



				['is_paid','align_center'],

				['[concat:first_name:last_name]','align_center'],

				['call_group','align_left'],
				['office','align_center'],
				
				//['city','align_center'],
				//['state','align_center'],


			];




			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getSalesURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=sales_management&"+
								"mode=xml&"+

								's_lead_id='+escape(frm.s_lead_id.value)+"&"+
								's_campaign_id='+escape(frm.s_campaign_id.value)+"&"+

								's_firstname='+escape(frm.s_firstname.value)+"&"+
								's_lastname='+escape(frm.s_lastname.value)+"&"+

								's_phone='+escape(frm.s_phone.value)+"&"+
								's_cluster_id='+escape(frm.s_cluster_id.value)+"&"+

								's_is_paid='+escape(frm.s_is_paid.value)+"&"+
								's_agent_username='+escape(frm.s_agent_username.value)+"&"+
								's_verifier_username='+escape(frm.s_verifier_username.value)+"&"+

								's_city='+escape(frm.s_city.value)+"&"+
								's_state='+escape(frm.s_state.value)+"&"+
								's_amount='+escape(frm.s_amount.value)+"&"+
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


			var sales_loading_flag = false;
			var page_load_start;

			/**
			* Load the name data - make the ajax call, callback to the parse function
			*/
			function loadSales(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = sales_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("NAMES ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('sales_loading_flag = true');
				}

				page_load_start = new Date();


				$('#total_count_div').html('<img src="images/ajax-loader.gif" border="0">');



				loadAjaxData(getSalesURL(),'parseSales');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseSales(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('sale',SalesTableFormat,xmldoc);


				var enddate = new Date();

				var loadtime = enddate - page_load_start;

				$('#page_load_time').html("Load and render time: "+loadtime+"ms");


				// ACTIVATE PAGE SYSTEM!
			//	if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('sales',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadSales()'
								);

			//	}else{

				//	hidePageSystem('leads');

			//	}




				eval('sales_loading_flag = false');
			}


			function handleSaleListClick(id){

				displaySaleDialog(id);

			}

			function displaySaleDialog(id, sub){

				var objname = 'dialog-modal-view_sale';


				$('#'+objname).dialog( "option", "title", 'Viewing Sale #'+id  );
				



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');


				if(sub){

					$('#'+objname).load("index.php?area=sales_management&view_sale="+id+"&sub="+sub+"&printable=1&no_script=1");
				}else{

					$('#'+objname).load("index.php?area=sales_management&view_sale="+id+"&printable=1&no_script=1");
				}

				$('#'+objname).dialog('option', 'position', 'center');

			}

			function resetSaleForm(frm){

				frm.reset();

				//frm.s_status.selectedIndex = 0;
				//frm.s_date.selectedIndex = 0;

				frm.s_cluster_id.selectedIndex = 0;
				frm.s_campaign_id.selectedIndex = 0;
				frm.s_lead_id.value = '';

				frm.s_agent_username.value = '';
				frm.s_verifier_username.value = '';

				frm.s_firstname.value = '';
				frm.s_lastname.value = '';

				frm.s_phone.value = '';


				frm.s_city.value = '';
				frm.s_state.value = '';
				frm.s_is_paid.value = '';


				toggleDateMode('date');


				// RESET ORDER BY
				<?=$this->order_prepend?>orderby = <?=$this->order_prepend?>orderby_default;
				<?=$this->order_prepend?>orderdir = <?=$this->order_prepend?>orderdir_default;



				loadSales();

			}


			function setPageSize(new_size){

				<?=$this->index_name?> = 0;
				<?=$this->order_prepend?>pagesize = new_size;
				loadSales();
			}

			var salesrchtog = false;

			function toggleSaleSearch(){
				salesrchtog = !salesrchtog;
				ieDisplay('sale_search_table', salesrchtog);
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
		<div id="dialog-modal-view_sale" title="Viewing Sale">


		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadSales();return false">
			<input type="hidden" name="searching_sales">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr class="ui-widget-header">
			<td height="40" class="pad_left">

				Sales Management

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
					<td id="sales_prev_td" class="page_system_prev"></td>
					<td id="sales_page_td" class="page_system_page"></td>
					<td id="sales_next_td" class="page_system_next"></td>
				</tr>
				</table>

			</td>
		</tr>

		<tr>
			<td colspan="2"><table border="0" width="700" id="sale_search_table">
			<tr>
				<td rowspan="2" width="70" align="center" style="border-right:1px solid #000">


					<div id="total_count_div"></div>

				</td>
				<th class="row2">Cluster</th>
				<th class="row2">Campaign</th>
				<th class="row2">Is Paid?</th>
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
				<td align="center"><select name="s_is_paid">
				
					<option value="">[All]</option>
					<option value="no"<?=(		$_REQUEST['s_is_paid'] == 'no')?" SELECTED ":''?>>No (Unpaid)</option>
					<option value="yes"<?=(		$_REQUEST['s_is_paid'] == 'yes')?" SELECTED ":''?>>Yes (Paid Credit Card)</option>
					<option value="roustedcc"<?=($_REQUEST['s_is_paid'] == 'roustedcc')?" SELECTED ":''?>>Yes (Rousted Credit Card)</option>
				
				</select></td>

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
				<td><input type="button" value="Reset" onclick="resetSaleForm(this.form);resetPageSystem('<?=$this->index_name?>');loadSales();"></td>
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
					<th class="row2">Amount</th>
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
					<td align="center"><input type="text" name="s_state" size="3" value="<?=htmlentities($_REQUEST['s_state'])?>"></td>
					<td align="center"><input type="text" name="s_amount" size="3" value="<?=htmlentities($_REQUEST['s_amount'])?>"></td>

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
			<td colspan="2"><table border="0" width="990" id="sale_table">
			<tr>
			<?/**
				var SalesTableFormat = [
				['id','align_center'],
				['lead_tracking_id','align_center'],
				['agent_lead_id','align_center'],
				['[get:cluster_name:agent_cluster_id]','align_center'],
				['campaign_code','align_center'],
				['[concat:agent_username:verifier_username]','align_center'],
				['[time:sale_time]','align_center'],
				['phone','align_center'],

				//['[duration:agent_duration]','align_center'],

				['is_paid','align_center'],

				['[concat:first_name:last_name]','align_center'],

				['call_group'],
				['office'],**/
				/**?>
				<th class="row2"><?=$this->getOrderLink('id')?>ID</a></th>**/
					
				?><th class="row2"><?=$this->getOrderLink('lead_tracking_id')?>PX ID</a></th>
				<th class="row2"><?=$this->getOrderLink('agent_lead_id')?>Lead ID</a></th>
				<th class="row2"><?=$this->getOrderLink('agent_cluster_id')?>Cluster</a></th>
				<th class="row2"><?=$this->getOrderLink('campaign_id')?>Campaign</a></th>
				<th class="row2"><?=$this->getOrderLink('agent_username')?>Agent</a>/<?=$this->getOrderLink('verifier_username')?>Verifier</a></th>
				<th class="row2"><?=$this->getOrderLink('sale_time')?>Sale Time</a></th>
				<th class="row2"><?=$this->getOrderLink('amount')?>Amount</a></th>
				<th class="row2"><?=$this->getOrderLink('phone')?>Phone Number</a></th>
				<th class="row2">Is Paid</th>
				<th class="row2"><?=$this->getOrderLink('first_name')?>First</a>/<?=$this->getOrderLink('last_name')?>Last</a> Name</a></th>

				<th class="row2"><?=$this->getOrderLink('call_group')?>User Group</a></th>
				<th class="row2"><?=$this->getOrderLink('office')?>Office</a></th>
				

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

				 $("#dialog-modal-view_sale").dialog({
					autoOpen: false,
					width: 780,
					height: 420,
					modal: false,
					draggable:true,
					resizable: false,
					close: function(event, ui){

						//hideAudio();
						
					}
				});

				<?
			if(($leadid=intval($_REQUEST['auto_open_sale'])) > 0){

				?>displaySaleDialog(<?=$leadid?>, 'general');<?
			}

		?>


			 });


			loadSales();



		</script><?

	}




	function makeView($id){

		$id = intval($id);


		if($id){

			$row = $_SESSION['dbapi']->sales_management->getByID($id);

			$lead_row = $_SESSION['dbapi']->lead_management->getByID($row['lead_tracking_id']);

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




			// SET TITLEBAR
			//$('#dialog-modal-view-sale').dialog( "option", "title", '<?=($id)?'Editing Lead #'.$id.' - '.addslashes(htmlentities($row['first_name'].' '.$row['last_name'])):'Adding new Lead'?>' );



		</script>

		<?

		if(intval($_REQUEST['no_script']) < 2){
		?><script>
				$(function() {
					$( "#sale_tabs" ).tabs();
				});
		</script>
		<div id="sale_tabs">
			<ul>
				<li><a href="?area=sales_management&view_sale=<?=$id?>&sub=general&printable=1&no_script=2">General</a></li>
				<?/*<li><a href="?area=sales_management&view_sale=<?=$id?>&sub=sales&printable=1&no_script=2">Xfers/Sales</a></li>
				<li><a href="?area=sales_management&view_sale=<?=$id?>&sub=recordings&printable=1&no_script=2">Recordings</a></li>
				*/?>
			</ul>
		</div><?

		}else{

			switch($_REQUEST['sub']){
			default:
			case 'general':

				$vici_url = getEditLeadURL($row['agent_cluster_id'], $row['agent_lead_id']);

				$vici_prod_search_url= getSearchLeadURL($row['agent_cluster_id'], $row['phone']);


				if($row['verifier_cluster_id'] > 0 && $row['verifier_cluster_id'] != $row['agent_cluster_id']){

					$vici_verifier_url = getEditLeadURL($row['verifier_cluster_id'], $row['verifier_lead_id']);

					$vici_ver_search_url = getSearchLeadURL($row['verifier_cluster_id'], $row['phone']);
				}


				?>
				<table border="0" width="100%">
				<tr valign="top">
					<td>

						<table border="0" align="center">
						<tr>
							<th align="left" height="25">Name:</th>
							<td><?=htmlentities($row['first_name'])?> <?=htmlentities($row['last_name'])?></td>
						</tr>
						<tr>
							<th align="left" height="25">Address:</th>
							<td><?=htmlentities($row['address1'])?></td>
						</tr><?
						
						if(trim($row['address2'])){
							?><tr>
								<th align="left" height="25">Address 2:</th>
								<td><?=htmlentities($row['address2'])?></td>
							</tr><?
						}
						
						?><tr>
							<th align="left" height="25">City/State/Zip:</th>
							<td>
								<?=htmlentities($row['city'])?>, <?=htmlentities($row['state'])?> <?=htmlentities($row['zip'])?>
							</td>
						</tr>
						<?/*
						<tr>
							<th align="left" height="25">Comments:</th>
							<td><?=htmlentities($row['comments'])?></td>
						</tr>*/?>
						<tr>
							<th align="left" height="25">Occupation:</th>
							<td><?=($lead_row)?(($lead_row['occupation'])?htmlentities($lead_row['occupation']):'-Not Specified-'):(($row['occupation'])?htmlentities($row['occupation']):'-Not Specified-')?></td>
						</tr>
						<tr>
							<th align="left" height="25">Employer:</th>
							<td><?=($lead_row)?(($lead_row['employer'])?htmlentities($lead_row['employer']):'-Not Specified-'):(($row['employer'])?htmlentities($row['employer']):'-Not Specified-')?></td>
						</tr>
						
						<tr>
							<th align="left" height="25">Agent/Verifier:</th>
							<td><?=htmlentities($row['agent_username']).' / '.htmlentities($row['verifier_username'])?></td>
						</tr>
						
						<tr>
							<th align="left" height="25">Campaign:</th>
							<td><?=htmlentities($row['campaign']).' / '.htmlentities($row['campaign_code'])?></td>
						</tr>
						
						</table>

					</td>
					<td>

						<table border="0" align="center">
						<tr>
							<th align="left" height="25">Phone Number:</th>
							<td><?=format_phone($row['phone'])?></td>
						</tr>
						<tr>
							<th align="left" height="25">Caller ID #:</th>
							<td><?=($lead_row['outbound_phone_num'] > 0)?format_phone($lead_row['outbound_phone_num']):'-'?></td>
						</tr>
						<tr>
							<th align="left" height="25">Sale Time:</th>
							<td><?=date("g:ia m/d/Y", $row['sale_time'])?></td>
						</tr>
						<tr>
							<th align="left" height="25">Sale ID#</th>
							<td><?=htmlentities($row['id'])?></td>
						</tr>
						<tr>
							<th align="left" height="25">PX ID#</th>
							<td><?
							
							if($lead_row){
								$url = "?area=lead_management&auto_open_lead=".$row['lead_tracking_id'].'&no_script=1';

								?><a href="<?=$url?>" onclick="loadSection(this.href);return false"><u><?=htmlentities($row['lead_tracking_id'])?></u></a><?
								
							}else{
								echo htmlentities($row['lead_tracking_id']);
							}
							
							?></td>
						</tr>
						<tr>
							<th align="left" height="25">Vici Lead ID#:</th>
							<td>
								<a href="<?=$vici_url?>" target="_blank"><u><?=htmlentities($row['agent_lead_id']).' on '.getClusterName($row['agent_cluster_id'])?></u></a>
								 |
								<a href="<?=$vici_prod_search_url?>" target="_blank"><u>Search by Phone</u></a>

							</td>
						</tr><?

						// CROSS CLUSTER
						if($row['verifier_cluster_id'] > 0 && $row['verifier_cluster_id'] != $row['agent_cluster_id']){

							?><tr>
								<th align="left" height="25">Verifier Lead ID#:</th>
								<td>
									<a href="<?=$vici_verifier_url?>" target="_blank"><u><?=htmlentities($row['verifier_lead_id']).' on '.getClusterName($row['verifier_cluster_id'])?></u></a>
									 |
								 	<a href="<?=$vici_ver_search_url?>" target="_blank"><u>Search by Phone</u></a>
								 </td>
							</tr><?



						}


						?><tr>

							<th align="left" height="25">Office/Group:</th>
							<td><?

								echo $row['office'].' / '.$row['call_group'];

							?></td>
						</tr><?







						?>

						<tr>
							<th align="left" height="25">Is Paid:</th>
							<td><?=htmlentities($row['is_paid'])?></td>
						</tr><?

						if($id > 0){
							?><tr>
								<td colspan="2" align="center" style="padding-top:10px">
	
									<input type="button" value="View Change History" style="font-size:10px" onclick="viewChangeHistory('lead_management', <?=$row['lead_tracking_id']?>)" />
	
	
								</td>
							</tr><?
						}

						?></table>


					</td>
				</tr>
				</table><?

				break;

			case 'sales':

				//$this->makeListSales($row);


				break;
			case 'recordings':

				//$this->makeRecordingSection($row);

				break;
			}


		}

		?></form><?


	}



	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadSales();return false;\">";

		return $var;
	}
}
