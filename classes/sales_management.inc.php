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

								's_id='+escape(frm.s_id.value)+"&"+
								's_lead_tracking_id='+escape(frm.s_lead_tracking_id.value)+"&"+
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


				$('#total_count_div').html('<img src="images/ajax-loader.gif" height="20" border="0">');



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
				frm.s_id.value = '';
				frm.s_lead_tracking_id.value = '';

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

			var salesrchtog = true;

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
		<div id="dialog-modal-view_sale" title="Viewing Sale"></div>
            <!-- ****START**** THIS AREA REPLACES THE OLD TABLES WITH THE NEW ONEUI INTERFACE BASED ON BOOTSTRAP -->
            <div class="block">
                <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadSales();return false">
                    <input type="hidden" name="searching_sales">
                    <div class="block-header bg-primary-light">
                        <h4 class="block-title">Sales Management</h4>
<!--                        <button type="button" value="Search" title="Toggle Search" class="btn btn-sm btn-primary" onclick="toggleSaleSearch();">Toggle Search</button>-->
                        <div id="sales_prev_td" class="page_system_prev"></div>
                        <div id="sales_page_td" class="page_system_page"></div>
                        <div id="sales_next_td" class="page_system_next"></div>
                        <select title="Rows Per Page" class="custom-select-sm" name="<?=$this->order_prepend?>pagesize" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0;loadSales(); return false;">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                        <div class="d-inline-block ml-2">
                            <button class="btn btn-sm btn-dark" title="Total Found">
                                <i class="si si-list"></i>
                                <span class="badge badge-light badge-pill"><div id="total_count_div"></div></span>
                            </button>
                        </div>
                    </div>
                    <div class="bg-info-light" id="sale_search_table">
                        <div class="input-group input-group-sm">
                            <input type="hidden" name="searching_sales"/>
                            <input type="text" class="form-control" placeholder="Sale ID.." name="s_id" value="<?=htmlentities($_REQUEST['s_id'])?>" />
                            <input type="text" class="form-control" placeholder="PX ID.." name="s_lead_tracking_id" value="<?= htmlentities($_REQUEST['s_lead_tracking_id']) ?>"/>
                            <?=makeClusterDD('s_cluster_id', $_REQUEST['s_cluster_id'], '', "", "[Select Cluster]");?>
                            <?=makeCampaignIDDD('s_campaign_id', $_REQUEST['s_campaign_id'], '', "", "[Select Campaign]");?>
                            <select class="custom-select-sm" name="s_is_paid">
                                <option value="">[Select Paid Status]</option>
                                <option value="no"<?=(		$_REQUEST['s_is_paid'] == 'no')?" SELECTED ":''?>>No (Unpaid)</option>
                                <option value="yes"<?=(		$_REQUEST['s_is_paid'] == 'yes')?" SELECTED ":''?>>Yes (Paid Credit Card)</option>
                                <option value="roustedcc"<?=($_REQUEST['s_is_paid'] == 'roustedcc')?" SELECTED ":''?>>Yes (Rousted Credit Card)</option>
                            </select>
                            <input type="text" class="form-control" placeholder="Agent.." name="s_agent_username" value="<?=htmlentities($_REQUEST['s_agent_username'])?>" />
                            <input type="text" class="form-control" placeholder="Verifier.." name="s_verifier_username" value="<?= htmlentities($_REQUEST['s_verifier_username']) ?>"/>
                        </div>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" placeholder="First Name.." name="s_firstname" value="<?=htmlentities($_REQUEST['s_firstname'])?>" />
                            <input type="text" class="form-control" placeholder="Last Name.." name="s_lastname" value="<?= htmlentities($_REQUEST['s_lastname']) ?>"/>
                            <input type="text" class="form-control" placeholder="Lead ID.." name="s_lead_id" value="<?=htmlentities($_REQUEST['s_lead_id'])?>" />
                            <input type="text" class="form_control" placeholder="Phone #.." name="s_phone" size="10" value="<?= htmlentities($_REQUEST['s_phone']) ?>" onkeyup="this.value=this.value.replace(/[^0-9]/g,'')"/>
                            <input type="text" class="form_control" placeholder="City.." name="s_city" value="<?= htmlentities($_REQUEST['s_city']) ?>"/>
                            <input type="text" class="form_control" placeholder="State.." name="s_state" value="<?= htmlentities($_REQUEST['s_state']) ?>"/>
                            <input type="text" class="form_control" placeholder="Amount.." name="s_amount" value="<?= htmlentities($_REQUEST['s_amount']) ?>"/>
                            <?= makeOfficeDD('s_office_id', $_REQUEST['s_office_id'], '', "", "[Select Office]"); ?>
                            <button type="submit" value="Search" title="Search Sales" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadScripts();return false;">Search</button>
                            <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetSaleForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadSales();return false;">Reset</button>
                        </div>
                        <div class="input-group input-group-sm">
                            <select title="Select Date Mode" name="s_date_mode" id="date_mode" onchange="toggleDateMode(this.value);">
                                <option value="date">Date Mode</option>
                                <option value="daterange"<?= ($_REQUEST['s_date_mode'] == 'daterange') ? ' SELECTED ' : '' ?>>Date Range Mode</option>
                                <option value="datetimerange"<?= ($_REQUEST['s_date_mode'] == 'datetimerange') ? ' SELECTED ' : '' ?>>Date/Time Range Mode</option>
                                <option value="any"<?= ($_REQUEST['s_date_mode'] == 'any') ? ' SELECTED ' : '' ?>>ANY</option>
                            </select>
                            &nbsp;
                            <span id="date1_span">
                            <?= makeTimebar("stime_", 1, null, false, time()); ?>
                                <span id="time1_span" class="nod">
                                    <?= makeTimebar("stime_", 2, null, false, (time() - 3600)); ?>
                                </span>
                            </span>
                            <span id="date2_span" class="nod">
                                THRU<?= makeTimebar("etime_", 1, null, false, time()); ?>
                                <span id="time2_span" class="nod">
                                    <?= makeTimebar("etime_", 2, null, false, time()); ?>
                                </span>
                            </span>
                            <span id="nodate_span" class="nod">ANY/ALL DATES</span>

                        </div>
                    </div>
                    <div class="block-content block-content-full">
                        <table class="table table-sm table-striped" id="sale_table">
                            <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                            <tr>
                                <th class="row2 text-center"><?= $this->getOrderLink('lead_tracking_id') ?>PX ID</a></th>
                                <th class="row2 text-center"><?= $this->getOrderLink('agent_lead_id') ?>Lead ID</a></th>
                                <th class="row2 text-center"><?= $this->getOrderLink('agent_cluster_id') ?>Cluster</a></th>
                                <th class="row2 text-center"><?= $this->getOrderLink('campaign_id') ?>Campaign</a></th>
                                <th class="row2 text-center"><?= $this->getOrderLink('agent_username') ?>Agent / Verifier</a>&nbsp;/&nbsp;<?= $this->getOrderLink('verifier_username') ?>Verifier</a></th>
                                <th class="row2 text-center"><?= $this->getOrderLink('sale_time') ?>Sale Time</a></th>
                                <th class="row2 text-center"><?= $this->getOrderLink('amount') ?>Amount</a></th>
                                <th class="row2 text-center"><?= $this->getOrderLink('phone') ?>Phone Number</a></th>
                                <th class="row2 text-center">Is Paid</th>
                                <th class="row2 text-center"><?= $this->getOrderLink('first_name') ?>First / Last</a>/<?= $this->getOrderLink('last_name') ?>Last</a> Name</a></th>
                                <th class="row2 text-center"><?= $this->getOrderLink('call_group') ?>User Group</a></th>
                                <th class="row2 text-center"><?= $this->getOrderLink('office') ?>Office</a></th>
                            </tr>
                        </table>
                    </div>
                </form>
            </div>
            <!-- ****END**** THIS AREA REPLACES THE OLD TABLES WITH THE NEW ONEUI INTERFACE BASED ON BOOTSTRAP -->
		<script>
			 $(function() {
				 $("#dialog-modal-view_sale").dialog({
					autoOpen: false,
					width: 780,
					height: 420,
					modal: false,
					draggable:true,
					resizable: false,
                     position: {my: 'center', at: 'center'},
				});

				 $("#dialog-modal-view_sale").dialog("widget").draggable("option","containment","#main-container");
				 
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
