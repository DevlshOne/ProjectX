<?php

	/***************************************************************
	 *	PAC Reports
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['pac_reports'] = new PACReports;


class PACReports{

	var $table	= 'pac_reports';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'pac_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'pacnextfrm';

	var $order_prepend = 'pac_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING




	// METHOD 0: SKIP THE PAYMENT GATEWAY FIELD (19 fields)
	// METHOD 1: INCLUDE THE PAYMENT GATEWAY AS THE [12] INDEX (20 fields)
	// METHOD 2: DOESN'T HAVE THE PAYMENT GATEWAY, OR THE VERIFICATION OF PHONE NUMBER (18 fields)
	var $format_mode = 1;
	var $project = "";

	var $rejects_pile = null;


	// POSITIONS IN THE LINE ARRAY, TO EXTRACT THE DATA FROM
	// WILL BE ADJUSTED/SET BY AUTO HEADER DETECTION
	var $index_payment_gateway = -1;
	var $index_date = 13;
	var $index_time = 14;
	var $index_ip = 15;
	var $index_phone = 16;
	var $index_employer = 18;
	var $index_profession = 19;


	var $index_status = 11;
	var $index_donation_id = 0;
	var $index_amount = 3;
	var $index_first_name = 9;
	var $index_last_name = 10;
	var $index_email = 11;

	var $index_company = 12;

	var $index_address1 = 13;
	var $index_address2 = 14;
	var $index_city = 15;
	var $index_state = 16;
	var $index_zip = 17;

	var $index_country = 18;







	function PACReports(){


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
		if(!checkAccess('pac_web_donations')){

			accessDenied("Web Donations");
			return;
		}

		if(isset($_REQUEST['add_pac'])){

			$this->makeAdd($_REQUEST['add_pac']);

		}else{
			$this->listEntrys();
		}

	}


	function TSVFilter($input){
		return preg_replace("/\t/", " ", $input, -1);
	}



	function exportNams($stime, $etime,$unsent_only=true){

		$stime = intval($stime);
		$etime = intval($etime);


		$extrasql = ($stime && $etime)?" AND `time` BETWEEN '$stime' AND '$etime' ":"";

		$extrasql.= ($unsent_only)?" AND sent_to_nams='no' ":'';

		$where = "WHERE 1 $extrasql";

		$res = query("SELECT * FROM pac_reports $where ORDER BY `datetime` ASC", 1);

		$output = "";
		$nl = "\r\n";
		$sep = "\t";
		$mobile_designator = "-M";

		$totals_arr = array();

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


			if(!is_array( $totals_arr[trim($row['project'])]  ) ){
				$totals_arr[trim($row['project'])] = array();

				$totals_arr[trim($row['project'])]['count'] = 0;
				$totals_arr[trim($row['project'])]['amount'] = 0;
			}

			$totals_arr[trim($row['project'])]['count']++;
			$totals_arr[trim($row['project'])]['amount'] += $row['amount'];

			$agent_user_filler = "WEB";
//
//
//			$lead = querySQL("SELECT * FROM lead_tracking WHERE id='".$row['lead_tracking_id']."'");

			// PHONE NUMBER - AGENT USER - AGENT NAME - DATE - DATE - TIME - LAST NAME - FIRST NAME - PERSON CONTACTED - ADDRESS1 - ADDRESS2 - CITY - STATE - ZIP - CAMPAIGN - AMOUNT - VERIFIER - OFFICE

			$output .= $this->TSVFilter($row['phone']).$sep;  // FIELD 1
			$output .= $this->TSVFilter($agent_user_filler).$sep;// FIELD 2
			$output .= $this->TSVFilter($agent_user_filler).$sep;// FIELD 3

			// NEEDS FIXED! USE VICI LAST LOCAL CALL TIME



			//list($date, $time) = preg_split("/\s/", $row['datetime'], 2);

			// REFORMAT DATE FOR NAMS SICK PLEASURE
			$date = date("m/d/Y", strtotime($row['datetime']));
			$time = date("g:ia", strtotime($row['datetime']));



			$output .= $this->TSVFilter($date).$sep;// FIELD 4
			$output .= $this->TSVFilter($date).$sep;// FIELD 5
			$output .= $this->TSVFilter($time).$sep;// FIELD 6


			$output .= $this->TSVFilter($row['last_name']).$sep;// FIELD 7
			$output .= $this->TSVFilter($row['first_name']).$sep;// FIELD 8

			// NAMS FORMAT - 2 BLANKS
			$output .= $sep; // SALUTATION	// FIELD 9
			$output .= $sep; // COMPANY		// FIELD 10

			// PERSON CONTACTED
			$output .= $this->TSVFilter($row['first_name']).$sep;// FIELD 11


			$output .= $this->TSVFilter($row['address1']).$sep;// FIELD 12
			$output .= $this->TSVFilter($row['address2']).$sep;// FIELD 13
			$output .= $this->TSVFilter($row['city']).$sep;// FIELD 14
			$output .= $this->TSVFilter($row['state']).$sep;// FIELD 15
			$output .= $this->TSVFilter($row['zip']).$sep;// FIELD 16


			// NAMS FORMAT - 2 BLANKS
			$output .= $sep;  // SOURCE				// FIELD 17
			$output .= $sep;  // RECTYPE (Renew Code or List Code)  Must start with C S or T // FIELD 18




			// CAMPAIGN
			$output .= $this->TSVFilter($row['project']).$sep;// FIELD 19

			// NAMS FORMAT - 2 BLANKS
			$output .= $sep; // LIST ID (can be used as optional ID field, but talk to nams before using!) // FIELD 20


			// MOB - MOBILE DESIGNATION
//			if(endsWith($row['campaign_code'], $mobile_designator)){
//				$output .= "MOB".$sep; // TYPE SALE
//			}else{
				$output .= $sep; // TYPE SALE // FIELD 21
//			}






			// SALE AMOUNT
			$output .= $this->TSVFilter($row['amount']).$sep; // FIELD 22


			// NAMS FORMAT - 4 BLANKS
			$output .= $sep; // SIZE CODE// FIELD 23
			$output .= $sep; // NUMBER (ticket/decal) // FIELD 24
			$output .= $sep; // DELIVERY (pickup, mail, other) // FIELD 25
			$output .= $sep; // SPEC INSTRUCTIONS // FIELD 26

			// VERIFIER
			$output .= $this->TSVFilter($agent_user_filler).$sep;// FIELD 27

			// OFFICE


/**90 -> R0
94 -> R4
M0 -> N0
M4 -> N4*/




			$office_code = "W";
			$output .= $office_code.$sep; // FIELD 28

//			if(endsWith($row['campaign_code'], $mobile_designator)){
//
//				$tmp = $row['office'];
//
//				$tmp[0] = 'M';
//
//
//				//$output .= $tmp.$sep; // 0 FOR NO LOCATION
//				$office_code = $tmp;
//			}else{
//				//$output .= $row['office'].$sep; // 0 FOR NO LOCATION
//
//				$office_code = $row['office'];
//			}





//			if(in_array($row['campaign'], $code_conversion_arr)){

//				switch($office_code){
//				default:
//					$output .= $office_code.$sep;
//					break;
//				case '90':
//					$output .= 'R0'.$sep;
//					break;
//				case '92':
//					$output .= 'R2'.$sep;
//					break;
//				case '94':
//					$output .= 'R4'.$sep;
//					break;
//				case '98':
//					$output .= 'R8'.$sep;
//					break;
//				case 'M0':
//					$output .= 'N0'.$sep;
//					break;
//				case 'M2':
//					$output .= 'N2'.$sep;
//					break;
//				case 'M4':
//					$output .= 'N4'.$sep;
//					break;
//				case 'M8':
//					$output .= 'N8'.$sep;
//					break;
//				}

//			}else{
//
//				$output .= $office_code.$sep;
//
//			}



			// NAMS FORMAT - 7 more BLANKS
			$output .= 'CREDIT'.$sep; // PAYMENT TYPE(CC, Credit, CK, Check, PD, PAID, Decline) // FIELD 29
			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // ETS FIELD
			$output .= $sep; // PREVIOUS NAMS invoice number


			$output .= $this->TSVFilter($row['profession']).$sep;
			$output .= $this->TSVFilter($row['employer']).$sep;

			$output .= $nl; // END NEW LINE

		}

		execSQL("UPDATE `pac_reports` SET sent_to_nams='yes' $where");


		return array($output,$totals_arr);
	}



	function listEntrys(){


		?><script>

			var pac_delmsg = 'Are you sure you want to delete this entry?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

//['[time:time]','align_center'],



			var PacsTableFormat = [
				['project','align_left'],
				['donation_id','align_center'],

				['first_name','align_left'],
				['last_name','align_left'],

				['amount','align_right'],

				['phone','align_left'],


				['employer','align_left'],
				['profession','align_left'],

				['datetime','align_center'],

				['payment_gateway','align_left'],


				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getPacsURL(){

				var frm = getEl('<?=$this->frm_name?>');
                let phone_num = frm.s_phone.value;
                phone_num = phone_num.replace(/[^0-9]/g,'');

				return 'api/api.php'+
								"?get=pac_reports&"+
								"mode=xml&"+

								's_project='+escape(frm.s_project.value)+"&"+
								's_amount='+escape(frm.s_amount.value)+"&"+
								's_phone='+escape(phone_num)+"&"+

								's_gateway='+escape(frm.s_gateway.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var pacs_loading_flag = false;

			/**
			* Load the name data - make the ajax call, callback to the parse function
			*/
			function loadPacs(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = pacs_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("PACS ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('pacs_loading_flag = true');
				}

				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				loadAjaxData(getPacsURL(),'parsePacs');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parsePacs(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('pac',PacsTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('pacs',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadPacs()'
								);

				}else{

					hidePageSystem('pacs');

				}

				eval('pacs_loading_flag = false');
			}


			function handlePacListClick(id){
				displayAddPacDialog(id);
			}

			function displayAddPacDialog(id){
				var objname = 'dialog-modal-add-pac';
				if(id > 0){
					$('#'+objname).dialog( "option", "title", 'Editing PAC entry' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new PAC entry' );
				}
				$('#'+objname).dialog("open");
				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
				$('#'+objname).load("index.php?area=pac_reports&add_pac="+id+"&printable=1&no_script=1");
			}

			function resetPacForm(frm){
                frm.reset();
				frm.s_project.value = '';
				frm.s_amount.value = '';
				frm.s_phone.value = '';
				frm.s_gateway.value = '';
			}

			var pacsrchtog = true;
			function togglePacSearch(){
				pacsrchtog = !pacsrchtog;
				ieDisplay('pac_search_table', pacsrchtog);
			}

			var namexporttog = false;
			function toggleNAMSExport(){
				namexporttog = !namexporttog;
				ieDisplay('nams_export_table', namexporttog);
			}

			function toggleDateMode(way){
				if(way == 'daterange'){
					$('#nodate_span').hide();
					$('#date1_span').show();
					// SHOW EXTRA DATE FIELD
					$('#date2_span').show();
				}else if(way == 'any'){
					$('#nodate_span').show();
					$('#date1_span').hide();
					$('#date2_span').hide();
				}else{
					$('#nodate_span').hide();
					$('#date1_span').show();
					// HIDE IT
					$('#date2_span').hide();
				}
			}

			function triggerExport(frm){
				var url = "ajax.php?mode=pac_reports_export&"+
						'date_month='+escape(frm.stime_month.value)+"&"+'date_day='+escape(frm.stime_day.value)+"&"+'date_year='+escape(frm.stime_year.value)+"&"+
						'date2_month='+escape(frm.etime_month.value)+"&"+'date2_day='+escape(frm.etime_day.value)+"&"+'date2_year='+escape(frm.etime_year.value)+"&"+
						'date_mode='+escape(frm.date_mode.value)+"&";

				window.open(url);
			}

		</script>
		<div id="dialog-modal-add-pac" title="Importing New PAC"></div>
        <div class="block">
        <form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadPacs();return false">
            <! ** BEGIN BLOCK HEADER -->
            <div class="block-header bg-primary-light">
                <h4 class="block-title">Web Donation Entries</h4>
                <button type="button" title="Import" class="btn btn-sm btn-info" onclick="displayAddPacDialog(0)">Import</button>
                <button type="button" title="Toggle Search" class="btn btn-sm btn-primary" onclick="togglePacSearch();">Toggle Search</button>
                <button type="button" title="Toggle Export NAMS" class="btn btn-sm btn-primary" onclick="toggleNAMSExport();">Toggle Export NAMS</button>
                <div id="pacs_prev_td" class="page_system_prev"></div>
                <div id="pacs_page_td" class="page_system_page"></div>
                <div id="pacs_next_td" class="page_system_next"></div>
                <select title="Rows Per Page" class="custom-select-sm" name="<?=$this->order_prepend?>pagesize" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0;loadPacs(); return false;">
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
            <! ** END BLOCK HEADER -->
            <! ** BEGIN BLOCK EXPORT TABLE -->
            <div class="bg-info-light nod" id="nams_export_table">
                <div class="input-group input-group-sm">
                    <input type="hidden" name="searching_pac"/>
                    <select name="date_mode" class="custom-select-sm"   id="date_mode" onchange="toggleDateMode(this.value);">
                        <option value="date">Date Mode</option>
                        <option value="daterange"<?=($_REQUEST['date_mode']=='daterange')?' SELECTED ':''?>>Date Range Mode</option>
                        <option value="any"<?=($_REQUEST['date_mode']=='any')?' SELECTED ':''?>>ANY</option>
                    </select>
                    <span id="date1_span">
                        <?=makeTimebar("stime_", 1, null,false,time());?>
                    </span>
                    <span id="date2_span" class="nod">&nbsp;-&nbsp;
                        <?=makeTimebar("etime_",1,null,false,time());?>
                    </span>
                    <span id="nodate_span" class="text-center font-weight-bold px-auto pt-1 align-middle nod">
						ANY/ALL DATES
					</span>
                    <button type="button" title="Export to NAMS" class="btn btn-sm btn-warning" name="the_Search_button" onclick="triggerExport(this.form);">Export Now</button>
                </div>
            </div>
            <! ** END BLOCK SEARCH TABLE -->
            <! ** BEGIN BLOCK EXPORT TABLE -->
            <div class="bg-info-light" id="pac_search_table">
                <div class="input-group input-group-sm">
                    <input type="hidden" name="searching_pac"/>
                    <?=$this->makeProjectDD('s_project', $_REQUEST['s_project'], 'form-control custom-select-sm', "", "[Select Project]");?>
                    <input type="text" class="form-control" placeholder="Amount.." name="s_amount" size="5" value="<?=htmlentities($_REQUEST['s_amount'])?>">
                    <input type="text" class="form-control" placeholder="Phone.." onkeyup="this.value=this.value.replace(/[^0-9]/g,'')" name="s_phone" size="5" value="<?=htmlentities($_REQUEST['s_phone'])?>">
                    <?=$this->makeGatewayDD('s_gateway', $_REQUEST['s_gateway'], 'form-control custom-select-sm', "", "[Select Gateway]");?>
                    <button type="submit" title="Search" class="btn btn-sm btn-success" name="the_Search_button">Search</button>
                    <button type="button" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetPacForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadPacs();return false;">Reset</button>
                </div>
            </div>
            <! ** END BLOCK SEARCH TABLE -->
            <! ** BEGIN BLOCK LIST (DATATABLE) -->
            <div class="block-content">
                <table class="table table-sm table-striped" id="pac_table">
                    <caption id="current_time_span" class="small text-right">Server Time: <?=date("g:ia m/d/Y T")?></caption>
                    <tr>
                        <th class="row2 text-left"><?=$this->getOrderLink('project')?>Project</a></th>
                        <th class="row2 text-left"><?=$this->getOrderLink('donation_id')?>Don.ID</a></th>
                        <th class="row2 text-left"><?=$this->getOrderLink('first_name')?>First&nbsp;Name</a></th>
                        <th class="row2 text-left"><?=$this->getOrderLink('last_name')?>Last&nbsp;Name</a></th>
                        <th class="row2 text-right"><?=$this->getOrderLink('amount')?>Amount</a></th>
                        <th class="row2 text-left"><?=$this->getOrderLink('phone')?>Phone</a></th>
                        <th class="row2 text-left"><?=$this->getOrderLink('employer')?>Employer</a></th>
                        <th class="row2 text-left"><?=$this->getOrderLink('profession')?>Profession</a></th>
                        <th class="row2 text-left"><?=$this->getOrderLink('time')?>Time</a></th>
                        <th class="row2 text-left"><?=$this->getOrderLink('payment_gateway')?>Payment Gateway</a></th>
                        <th class="row2 text-center">&nbsp;</th>
                    </tr>
                </table>
            </div>
        </form>
        </div>
		<script>
			$("#dialog-modal-add-pac").dialog({
				autoOpen: false,
				width: 'auto',
				height: 450,
				modal: false,
				draggable: true,
				resizable: false,
                position: {my: 'center', at: 'center'},
                containment: '#main-container'
			});
            $('#dialog-modal-add-pac').closest('.ui-dialog').draggable('option', 'containment', '#main-container');
			loadPacs();
		</script>
        <?
	}



	function makeProjectDD($name, $sel, $class, $onchange, $blank_option = 1){
		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out.= ($class)?' class="'.$class.'" ':'';
		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= '>';
		if($blank_option){
			$out .= '<option value="" '.(($sel == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
		}

		$res = $_SESSION['dbapi']->query("SELECT DISTINCT(`project`) AS `project` FROM pac_reports ORDER BY `project` ASC", 1);


		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


			$out .= '<option value="'.htmlentities($row['project']).'" ';
			$out .= ($sel == $row['project'])?' SELECTED ':'';
			$out .= '>'.htmlentities($row['project']).'</option>';


		}

		$out .= '</select>';
		return $out;
	}

	function makeGatewayDD($name, $sel, $class, $onchange, $blank_option = 1){

		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out.= ($class)?' class="'.$class.'" ':'';
		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= '>';

		if($blank_option){
			$out .= '<option value="" '.(($sel == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
		}

		$res = $_SESSION['dbapi']->query("SELECT DISTINCT(`payment_gateway`) AS `payment_gateway` FROM pac_reports ORDER BY `payment_gateway` ASC", 1);


		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


			$out .= '<option value="'.htmlentities($row['payment_gateway']).'" ';
			$out .= ($sel == $row['payment_gateway'])?' SELECTED ':'';
			$out .= '>'.htmlentities($row['payment_gateway']).'</option>';


		}

		$out .= '</select>';
		return $out;
	}


	function makeImportForm(){



		?><script>

		function WebCSVUploadSuccess(cnt){

			if(cnt > 0){
				alert("Successfully added "+cnt+" records.");

				$('#dialog-modal-add-pac').dialog("close");

				var ninjafrm=getEl('webdonation_upload_frm');
				ninjafrm.reset();

				loadPacs();
			}else{

				alert("Warning: "+cnt+" records processed. Might be Duplicates.");

				$('#upload_status_cell').html('<div style="font-size:14px;height:30px;">Warning: '+cnt+' records processed.<br />Might be Duplicates.</div>');
			}

		}

		function ninjaUploadCSV(){

			var ninjafrm=getEl('webdonation_upload_frm');

			if(!ninjafrm.project.value.trim()){

				alert("Error: Please enter the project first.");
				return;
			}

			$('#upload_status_cell').html('<div style="font-size:16px;height:30px;">Preparing upload...</div>');




			// BLANK THE PAGE
			getEl('iframe_upload').src = 'about:blank';

			$('#upload_status_cell').html('<div style="font-size:16px;height:40px;"><img src="images/ajax-loader.gif" height="40" border="0" />Uploading file...</div>');

			// SUBMIT HIDDEN FORM
			ninjafrm.submit();
		}
		</script>

		<div class="nod">
			<iframe id="iframe_upload" name="iframe_upload" width="1" height="1" frameborder="0" src="about:blank"></iframe>
		</div>

		<form id="webdonation_upload_frm" name="webdonation_upload_frm" method="POST" enctype="multipart/form-data" action="ajax.php?mode=web_donation_import_upload" target="iframe_upload">
			<input type="hidden" name="uploading_web_csv">

		<table class="tightTable">
		<tr>
			<th align="left" height="30">Project:</th>
			<td nowrap>
				<input type="text" size="10" id="project" name="project" onkeyup="this.value=this.value.toUpperCase()" />
			</td>
		</tr>
		<tr>
			<th align="left">CSV File:</th>
			<td>
				<input type="file" name="csv_file" id="csv_file" onchange="ninjaUploadCSV()">
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center" id="upload_status_cell" height="48" >
			</td>
		</tr>
		<tr>
			<th colspan="2" align="center"><input type="button" value="Save Changes" onclick="ninjaUploadCSV()"></th>
		</tr>
		</form>
		</table><?

	}


	function makeAdd($id){

		$id=intval($id);


		if($id){

			$row = $_SESSION['dbapi']->pac_reports->getByID($id);

//print_r($row);
		}else{

			//echo "Import GUI Here!";
			return $this->makeImportForm();

		}

		?><script>

			function validatePacField(name,value,frm){

				//alert(name+","+value);


				switch(name){
				default:

					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;

				case 'filename':


					if(!value)return false;

					return true;


					break;

				}
				return true;
			}



			function checkPacFrm(frm){


				var params = getFormValues(frm,'validatePacField');


				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'filename':

						alert("Please enter the filename for this name.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;

					}

				// SUCCESS - POST AJAX TO SERVER
				}else{


					//alert("Form validated, posting");

					$.ajax({
						type: "POST",
						cache: false,
						url: 'api/api.php?get=pacs&mode=xml&action=edit',
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


							loadPacs();


							displayAddPacDialog(res);

							//alert(result['message']);

						}


					});

				}

				return false;

			}




			// SET TITLEBAR
			$('#dialog-modal-add-pac').dialog( "option", "title", '<?=($id)?'Editing Web Entry #'.$id.' - '.htmlentities($row['name']):'Adding new Web entry'?>' );

		</script>
		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkPacFrm(this); return false">
			<input type="hidden" id="adding_pac" name="adding_pac" value="<?=$id?>" >


		<table class="tightTable">

		<tr>
			<th align="left" height="30">Date/time:</th>
			<td nowrap>
				<?=htmlentities($row['datetime'])?>
			</td>
		</tr>
		<tr>
			<th align="left" height="30">IP Address:</th>
			<td nowrap>
				<?=htmlentities($row['ip_address'])?>
			</td>
		</tr>
		<tr>
			<th align="left" height="30">Amount:</th>
			<td nowrap>
				$<?=htmlentities($row['amount'])?>
			</td>
		</tr>
		<tr>
			<th align="left" height="30">Name:</th>
			<td nowrap>
				<input name="first_name" type="text" size="25" value="<?=htmlentities($row['first_name'])?>">
				<input name="last_name" type="text" size="25" value="<?=htmlentities($row['last_name'])?>">
			</td>
		</tr>
		<tr valign="top">
			<th align="left" height="30">Address:</th>
			<td>
				<input name="address1" type="text" size="50" value="<?=htmlentities($row['address1'])?>"><br />
				<input name="address2" type="text" size="50" value="<?=htmlentities($row['address2'])?>">
			</td>
		</tr>

		<tr>
			<th align="left" height="30">City/State/Zip:</th>
			<td nowrap>
				<input name="city" type="text" size="25" value="<?=htmlentities($row['city'])?>" title="City">,
				<input name="state" type="text" size="3" value="<?=htmlentities($row['state'])?>" title="State (2 digit)">
				<input name="zip" type="text" size="10" value="<?=htmlentities($row['zip'])?>" title="Zip/Postal Code"  onkeyup="this.value=this.value.replace(/[^0-9]/g,'')">
				<input name="country" type="text" size="2" value="<?=htmlentities($row['country'])?>" title="Country">
			</td>
		</tr>
		<tr>
			<th align="left" height="30">Phone:</th>
			<td>
				<input name="phone" type="text" size="10" value="<?=htmlentities($row['phone'])?>" onkeyup="this.value=this.value.replace(/[^0-9]/g,'')">
			</td>
		</tr>
		<tr>
			<th align="left" height="30">Employer:</th>
			<td>
				<input name="employer" type="text" size="50" value="<?=htmlentities($row['employer'])?>">
			</td>
		</tr>
		<tr>
			<th align="left" height="30">Profession:</th>
			<td>
				<input name="profession" type="text" size="50" value="<?=htmlentities($row['profession'])?>">
			</td>
		</tr>
		<tr>
			<th align="left" height="30">Payment Gateway:</th>
			<td><?
				/**<input name="payment_gateway" type="text" size="10" value="<?=htmlentities($row['payment_gateway'])?>">**/

				echo $this->makeGatewayDD('payment_gateway', $row['payment_gateway'], '', "", 0);
			?></td>
		</tr>
		<tr>
			<th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
		</tr>
		</form>
		</table><?


	}

/***
 *
 * Donation ID
 * First Name
 * Last Name
 * Email Address
 * Address 1
 * Address 2
 * City	State
 * Zip
 * Country
 * Donation Total (&#36;)
 * Donation Status
 * Payment Gateway
 * Donation Date
 * Donation Time
 * Donor IP Address
 * phone_number
 * employed_by
 * work_profession

 */

	function parsePacsFile($csv_file){

		$output = array();

//		global $format_mode;
		global $rejects_pile;

		$data = file_get_contents($csv_file);
//echo $csv_file;
//echo "<br />\n\n";
//echo $data;
		$lines = preg_split("/\r\n|\n|\r/",$data, -1, PREG_SPLIT_NO_EMPTY);

		foreach($lines as $idx=>$line){

			//$arr = preg_split("/,/", $line);


			$arr = str_getcsv($line, ',', '"');


			// HEADER ROW
			if($idx == 0){

				print_r($arr);

				// DETECT $format_mode

//Donation ID	First Name	Last Name	Email Address	Address 1	Address 2	City	State	Zip	Country	Donation Total (&#36;)	Donation Status	Payment Gateway	Donation Date	Donation Time	Donor IP Address	phone_number	verify_phone_number	employed_by	your_profession
//Donation ID	First Name	Last Name	Email Address	Address 1	Address 2	City	State	Zip	Country	Donation Total (&#36;)	Donation Status	Payment Gateway	Donation Date	Donation Time	Donor IP Address	phone_number	employed_by	work_profession

				foreach($arr as $idx=>$name){

					$name = strtolower(trim($name));

					if(stripos($name, 'date') > -1){

						$_SESSION['pac_reports']->index_date = $idx;

					}else if(stripos($name, 'time') > -1){

						$_SESSION['pac_reports']->index_time = $idx;

					}else if(stripos($name, 'gateway') > -1){

						$_SESSION['pac_reports']->index_payment_gateway = $idx;

					}else if(stripos($name, 'ip') > -1 && stripos($name, 'address') > -1){

						$_SESSION['pac_reports']->index_ip = $idx;

					// THIS COULD BE EITHER PHONE OR VERIFY PHONE, BOTH SHOULD BE THE SAME
					}else if(stripos($name, 'phone') > -1){

						$_SESSION['pac_reports']->index_phone = $idx;

					}else if(stripos($name, 'employ') > -1){

						$_SESSION['pac_reports']->index_employer = $idx;

					}else if(stripos($name, 'profession') > -1){

						$_SESSION['pac_reports']->index_profession = $idx;

					}else if(stripos($name, 'status') > -1){
						$_SESSION['pac_reports']->index_status = $idx;
					}else if(stripos($name, 'donation id') > -1 ){

						$_SESSION['pac_reports']->index_donation_id = $idx;

					}else if(stripos($name, 'total') > -1 ){

						$_SESSION['pac_reports']->index_amount = $idx;

					}else if(stripos($name, 'first name') > -1 ){

						$_SESSION['pac_reports']->index_first_name = $idx;

					}else if(stripos($name, 'last name') > -1 ){

						$_SESSION['pac_reports']->index_last_name = $idx;

					}else if(stripos($name, 'email') > -1 ){

						$_SESSION['pac_reports']->index_email = $idx;

					}else if(stripos($name, 'company') > -1 ){

						$_SESSION['pac_reports']->index_company = $idx;

					}else if(stripos($name, 'address 1') > -1 ){

						$_SESSION['pac_reports']->index_address1 = $idx;

					}else if(stripos($name, 'address 2') > -1 ){

						$_SESSION['pac_reports']->index_address2 = $idx;

					}else if(stripos($name, 'city') > -1 ){

						$_SESSION['pac_reports']->index_city = $idx;

					}else if(stripos($name, 'state') > -1 ){

						$_SESSION['pac_reports']->index_state = $idx;

					}else if(stripos($name, 'zip') > -1 ){

						$_SESSION['pac_reports']->index_zip = $idx;

					}else if(stripos($name, 'country') > -1 ){

						$_SESSION['pac_reports']->index_country = $idx;



					}
				/**
				 * 	var $index_donation_id = 0;
	var $index_amount = 3;
	var $index_first_name = 9;
	var $index_last_name = 10;
	var $index_email = 11;

	var $index_company = 12;

	var $index_address1 = 13;
	var $index_address2 = 14;
	var $index_city = 15;
	var $index_state = 16;
	var $index_zip = 17;
				 */


				}





//				if(count($arr) == 20){
//					$_SESSION['pac_reports']->format_mode = 1;
//				}else if(count($arr) == 19){
//					$_SESSION['pac_reports']->format_mode = 0;
//				}else if(count($arr) == 18){
//					$_SESSION['pac_reports']->format_mode = 2;
//				}
//print_r($arr);
				continue;
			}

			if(strtolower($arr[$_SESSION['pac_reports']->index_status]) != 'complete'){
				$_SESSION['pac_reports']->rejects_pile[] = $arr;
				continue;
			}

			$output[] = $arr;
//			print_r($arr);

		}


//print_r($output);
		return $output;
	}


	function stringFilter($str){

		return preg_replace('/[^a-zA-Z0-9-\' ]/', '' , $str);
	}

	function pushPacsToDB($stack){

//		global $format_mode;
//		global $project;


		$cnt = 0;
/***
 *  [0] => Donation ID
    [1] => First Name
    [2] => Last Name
    [3] => Email Address
    [4] => Address 1
    [5] => Address 2
    [6] => City
    [7] => State
    [8] => Zip
    [9] => Country
    [10] => Donation Total (&#36;)
    [11] => Donation Status
    [12] => Donation Date
    [13] => Donation Time
    [14] => Donor IP Address
    [15] => phone_number
    [16] => employed_by
    [17] => work_profession

 */
		foreach($stack as $row){

			$dat = array();

			$dat['project'] = $_SESSION['pac_reports']->project;
/**
				 * 	var $index_donation_id = 0;
	var $index_amount = 3;
	var $index_first_name = 9;
	var $index_last_name = 10;
	var $index_email = 11;

	var $index_company = 12;

	var $index_address1 = 13;
	var $index_address2 = 14;
	var $index_city = 15;
	var $index_state = 16;
	var $index_zip = 17;
	$index_country
				 */


			$dat['donation_id']		= $row[$_SESSION['pac_reports']->index_donation_id];
			$dat['first_name']		= $row[$_SESSION['pac_reports']->index_first_name];
			$dat['last_name']		= $row[$_SESSION['pac_reports']->index_last_name];
			$dat['email_address']	= $row[$_SESSION['pac_reports']->index_email];
			$dat['address1']		= $row[$_SESSION['pac_reports']->index_address1];
			$dat['address2']		= $row[$_SESSION['pac_reports']->index_address2];
			$dat['city']			= $row[$_SESSION['pac_reports']->index_city];
			$dat['state']			= $row[$_SESSION['pac_reports']->index_state];
			$dat['zip']				= $row[$_SESSION['pac_reports']->index_zip];
			$dat['country']			= $row[$_SESSION['pac_reports']->index_country];
			$dat['amount']			= $row[$_SESSION['pac_reports']->index_amount];



/**
 * 	$_SESSION['pac_reports']->index_payment_gateway = -1;
	$_SESSION['pac_reports']->index_date = 13;
	$_SESSION['pac_reports']->index_time = 14;
	$_SESSION['pac_reports']->index_ip = 15;
	$_SESSION['pac_reports']->index_phone = 16;
	$_SESSION['pac_reports']->index_employer = 18;
	$_SESSION['pac_reports']->index_profession = 19;
 */

			if($_SESSION['pac_reports']->index_payment_gateway > -1){

				$dat['payment_gateway']			= $row[$_SESSION['pac_reports']->index_payment_gateway];

			}

			$dat['time']			= strtotime($row[$_SESSION['pac_reports']->index_time].' '.$row[$_SESSION['pac_reports']->index_date]);

			$date = date_create($row[$_SESSION['pac_reports']->index_date].' '.$row[$_SESSION['pac_reports']->index_time]);
			$dat['datetime']	= date_format($date, 'Y-m-d H:i:s');


			$dat['ip_address']		= $row[$_SESSION['pac_reports']->index_ip];
			$dat['phone']			= preg_replace("/[^0-9]/",'',$row[$_SESSION['pac_reports']->index_phone]);
			$dat['employer']		= $this->stringFilter($row[$_SESSION['pac_reports']->index_employer]);
			$dat['profession']		= $this->stringFilter($row[$_SESSION['pac_reports']->index_profession]);


/*
			if($_SESSION['pac_reports']->format_mode == 1){

				$dat['payment_gateway']			= $row[12];
				$dat['time']			= strtotime($row[14].' '.$row[13]);

				$date = date_create($row[13].' '.$row[14]);
				$dat['datetime']	= date_format($date, 'Y-m-d H:i:s');


				$dat['ip_address']		= $row[15];
				$dat['phone']			= preg_replace("/[^0-9]/",'',$row[16]);
				$dat['employer']		= $this->stringFilter($row[18]);
				$dat['profession']		= $this->stringFilter($row[19]);

			}else if($_SESSION['pac_reports']->format_mode == 2){

				$dat['time']			= strtotime($row[13].' '.$row[12]);

				$date = date_create($row[12].' '.$row[13]);
				$dat['datetime']	= date_format($date, 'Y-m-d H:i:s');

				$dat['ip_address']		= $row[14];
				$dat['phone']			= preg_replace("/[^0-9]/",'', $row[15]);
				$dat['employer']		= $this->stringFilter($row[16]);
				$dat['profession']		= $this->stringFilter($row[17]);


			}else{

				$dat['time']			= strtotime($row[13].' '.$row[12]);

				$date = date_create($row[12].' '.$row[13]);
				$dat['datetime']	= date_format($date, 'Y-m-d H:i:s');

				$dat['ip_address']		= $row[14];
				$dat['phone']			= preg_replace("/[^0-9]/",'', $row[15]);
				$dat['employer']		= $this->stringFilter($row[17]);
				$dat['profession']		= $this->stringFilter($row[18]);
			}
			*/

			$tcnt = aadd($dat,'pac_reports', true);

			if($tcnt < 1){
				echo "ERROR: Skipping Duplicate 'donation_id' of ".$row[0]." for Project '".$dat['project']."' \n";
			}

			$cnt += $tcnt;
		}

		return $cnt;
	}



	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadPacs();return false;\">";

		return $var;
	}
}
