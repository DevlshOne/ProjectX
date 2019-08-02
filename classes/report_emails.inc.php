<?	/***************************************************************
	 *	Report Emails - A system to manage that damn report_emails table, so we don't gotta edit teh db manually everytime Sarah asks for a new group
	 *	Written By: Jonathan Will - 10-26-2016
	 ***************************************************************/

$_SESSION['report_emails'] = new ReportEmails;


class ReportEmails{

	var $table	= 'report_emails';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 30;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 're_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'renextfrm';

	var $order_prepend = 're_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function ReportEmails(){


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

		if(!checkAccess('report_emails')){


			accessDenied("Report Email Settings");

			return;

		}else{
			if(isset($_REQUEST['add_report_email'])){

				$this->makeAdd($_REQUEST['add_report_email']);

			}else{
				$this->listEntrys();
			}

		}

	}






	function listEntrys(){


		?><script>

			var report_delmsg = 'Are you sure you want to delete this Report Email?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var ReportsTableFormat = [
				['id','align_center'],
				['[get:report_name:report_id]','align_left'],
				['interval','align_center'],
				['[get:friendly_trigger_time:interval:trigger_time]','align_center'],
				['email_address','align_left'],
				['subject_append','align_left'],

				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getReportsURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=report_emails&"+
								"mode=xml&"+

								's_id='+escape(frm.s_id.value)+"&"+
								's_report_id='+escape(frm.s_report_id.value)+"&"+
								's_email_address='+escape(frm.s_email_address.value)+"&"+
								's_subject_append='+escape(frm.s_subject_append.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var reports_loading_flag = false;

			/**
			* Load the report data - make the ajax call, callback to the parse function
			*/
			function loadReports(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = reports_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("REPORTS ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('reports_loading_flag = true');
				}

				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				loadAjaxData(getReportsURL(),'parseReports');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseReports(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('report',ReportsTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('reports',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadReports()'
								);

				}else{

					hidePageSystem('reports');

				}

				eval('reports_loading_flag = false');
			}


			function handleReportListClick(id){

				displayAddReportDialog(id);

			}


			function displayAddReportDialog(id){

				var objname = 'dialog-modal-add-report';


				if(id > 0){
					$('#'+objname).dialog( "option", "title", 'Editing Report Email' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Report Email' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=report_emails&add_report_email="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			function resetReportForm(frm){

				frm.s_id.value = '';
				frm.s_report_type.value = '';
				frm.s_email_address.value = '';
				frm.s_subject_append.value = '';



			}


			var reportsrchtog = false;

			function toggleReportSearch(){
				reportsrchtog = !reportsrchtog;
				ieDisplay('report_search_table', reportsrchtog);
			}

		</script>
		<div id="dialog-modal-add-report" title="Adding new Report" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadReports();return false">
			<input type="hidden" name="searching_reports">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td width="500">
						Report Emails
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Add" onclick="displayAddReportDialog(0)">
						<?/**&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Search" onclick="toggleReportSearch()">**/?>
					</td>

					<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadNames();return false">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="500">500</option>
					</select></td>

					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="reports_prev_td" class="page_system_prev"></td>
							<td id="reports_page_td" class="page_system_page"></td>
							<td id="reports_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" id="report_search_table">
			<tr>
				<td rowspan="2"><font size="+1">SEARCH</font></td>
				<th class="row2">ID</th>
				<th class="row2">Report Type</th>
				<th class="row2">Email</th>
				<th class="row2">Subject</th>


				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_id" size="5" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>
				<td align="center"><select name="s_report_id">
					<option value=""<?=(!$_REQUEST['s_report_id'])?" SELECTED ":""?>>[SELECT TYPE]</option>
					<option value="1"<?=($_REQUEST['s_report_id'] == 1)?" SELECTED ":""?>>Sales Analysis</option>
					<option value="2"<?=($_REQUEST['s_report_id'] == 2)?" SELECTED ":""?>>Verifier Report</option>
					<option value="3"<?=($_REQUEST['s_report_id'] == 3)?" SELECTED ":""?>>Summary Report</option>
					<option value="4"<?=($_REQUEST['s_report_id'] == 4)?" SELECTED ":""?>>Rouster Report</option>
				</select></td>
				<td align="center"><input type="text" name="s_email_address" size="15" value="<?=htmlentities($_REQUEST['s_email_address'])?>"></td>
				<td align="center"><input type="text" name="s_subject_append" size="15" value="<?=htmlentities($_REQUEST['s_subject_append'])?>"></td>

				<td><input type="button" value="Reset" onclick="resetReportForm(this.form);resetPageSystem('<?=$this->index_name?>');loadReports();"></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="report_table">
			<tr>

				<th class="row2" align="center"><?=$this->getOrderLink('id')?>ID</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('report_id')?>Report Type</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('interval')?>Interval</a></th>
				<th class="row2" align="center"><?=$this->getOrderLink('trigger_time')?>Trigger Time</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('email_address')?>Email Address</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('subject_append')?>Subject Append</a></th>


				<th class="row2">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-add-report").dialog({
				autoOpen: false,
				width: 570,
				height: 350,
				modal: false,
				draggable:true,
				resizable: false
			});

			loadReports();

		</script><?

	}


	function makeAdd($id){

		$id=intval($id);


		if($id){

			$row = $_SESSION['dbapi']->report_emails->getByID($id);


			$diw = floor( ($row['trigger_time'] / 86400) );

			$diw_offset = ($diw * 86400);

			$timeoffset = ($row['trigger_time'] % 86400);





		}

		?><script>

			function validateReportField(name,value,frm){

				//alert(name+","+value);


				switch(name){
				default:

					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;

				case 'subject_append':


					if(!value)return false;

					return true;


					break;

				}
				return true;
			}



			function checkReportFrm(frm){


				var params = getFormValues(frm,'validateReportField');


				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){

					switch(params[0]){
					default:

						alert("Error submitting form. Check your values");

						break;

					case 'subject_append':

						alert("Please enter the subject for this email.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;

					}

				// SUCCESS - POST AJAX TO SERVER
				}else{


					//alert("Form validated, posting");

					$.ajax({
						type: "POST",
						cache: false,
						url: 'api/api.php?get=report_emails&mode=xml&action=edit',
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


							loadReports();


							displayAddReportDialog(res);

							alert(result['message']);

						}


					});

				}

				return false;

			}




			// SET TITLEBAR
			$('#dialog-modal-add-name').dialog( "option", "title", '<?=($id)?'Editing Report Email #'.$id.' - '.htmlentities($row['subject_append']):'Adding new Report Email'?>' );


			function toggleTimeMode(mode){

				if(mode == "weekly"){

					ieDisplay('weeklyrow', 1);
					ieDisplay('monthlyrow', 0);
				}else if(mode == "monthly"){

					ieDisplay('weeklyrow', 0);
					ieDisplay('monthlyrow', 1);

				}else{
					ieDisplay('monthlyrow', 0);
					ieDisplay('weeklyrow', 0);
				}

			}


		</script>
		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkReportFrm(this); return false">
			<input type="hidden" id="adding_name" name="adding_report" value="<?=$id?>" >


		<table border="0" align="center">

		<tr>
			<th align="left" width="100" height="30">Report Type:</th>
			<td><select name="report_id">
				<option value="1">Sales Analysis</option>
				<option value="2" <?=(($row['report_id'] == 2)?" SELECTED ":"")?> >
					Verifier Report
				</option>
				<option value="3" <?=(($row['report_id'] == 3)?" SELECTED ":"")?> >
					Summary Report
				</option>
				<option value="4" <?=(($row['report_id'] == 4)?" SELECTED ":"")?> >
					Rouster Report
				</option>
			</select></td>
		</tr>

		<tr>
			<th align="left" height="30">Subject<br />(Group Name):</th>
			<td><input name="subject_append" type="text" size="50" value="<?=htmlentities($row['subject_append'])?>"></td>
		</tr>
		<tr>
			<th align="left" height="30">Email:</th>
			<td><input name="email_address" type="text" size="50" value="<?=htmlentities($row['email_address'])?>"></td>
		</tr>
		<tr>
			<th align="left" height="30">Interval:</th>
			<td><select name="interval" id="interval" onchange="toggleTimeMode(this.value);">
				<option value="daily"<?=($row['interval'] == 'daily')?" SELECTED":""?>>Daily</option>
				<option value="weekly"<?=($row['interval'] == 'weekly')?" SELECTED":""?>>Weekly</option>
				<option value="monthly"<?=($row['interval'] == 'monthly')?" SELECTED":""?>>Monthly</option>
			</select></td>
		</tr>

		<tr id="weeklyrow">
			<th align="left" height="30" colspan="2" nowrap>Day&nbsp;of&nbsp;Wk:

				<select name="day_of_week_offset">
					<option value="0"<?=($diw == 0)?" SELECTED":""?>>Sunday</option>
					<option value="1"<?=($diw == 1)?" SELECTED":""?>>Monday</option>
					<option value="2"<?=($diw == 2)?" SELECTED":""?>>Tuesday</option>
					<option value="3"<?=($diw == 3)?" SELECTED":""?>>Wednesday</option>
					<option value="4"<?=($diw == 4)?" SELECTED":""?>>Thursday</option>
					<option value="5"<?=($diw == 5)?" SELECTED":""?>>Friday</option>
					<option value="6"<?=($diw == 6)?" SELECTED":""?>>Saturday</option>
				</select>
			</th>
		</tr>

		<tr id="monthlyrow">
			<th align="left" height="30">Day&nbsp;of&nbsp;the&nbsp;Month:</th>
			<td><?

				echo makeNumberDD('day_of_the_month_offset',($diw+1),1,31,1,false);

			?></td>
		</tr>

		<tr>
			<th align="left" height="30">Trigger Time:</th>
			<td><select name="trigger_time">

			<option value="0"<?=($timeoffset == 0)?" SELECTED ":""?>>12 AM</option>
			<option value="3600"<?=($timeoffset == 3600)?" SELECTED ":""?>>1 AM</option>
			<option value="7200"<?=($timeoffset == 7200)?" SELECTED ":""?>>2 AM</option>
			<option value="10800"<?=($timeoffset == 10800)?" SELECTED ":""?>>3 AM</option>
			<option value="14400"<?=($timeoffset == 14400)?" SELECTED ":""?>>4 AM</option>
			<option value="18000"<?=($timeoffset == 18000)?" SELECTED ":""?>>5 AM</option>
			<option value="21600"<?=($timeoffset == 21600)?" SELECTED ":""?>>6 AM</option>
			<option value="25200"<?=($timeoffset == 25200)?" SELECTED ":""?>>7 AM</option>
			<option value="28800"<?=($timeoffset == 28800)?" SELECTED ":""?>>8 AM</option>
			<option value="32400"<?=($timeoffset == 32400)?" SELECTED ":""?>>9 AM</option>
			<option value="36000"<?=($timeoffset == 36000)?" SELECTED ":""?>>10 AM</option>
			<option value="39600"<?=($timeoffset == 39600)?" SELECTED ":""?>>11 AM</option>
			<option value="43200"<?=($timeoffset == 43200)?" SELECTED ":""?>>12 PM</option>
			<option value="46800"<?=($timeoffset == 46800)?" SELECTED ":""?>>1 PM</option>
			<option value="50400"<?=($timeoffset == 50400)?" SELECTED ":""?>>2 PM</option>
			<option value="54000"<?=($timeoffset == 54000)?" SELECTED ":""?>>3 PM</option>
			<option value="57600"<?=($timeoffset == 57600)?" SELECTED ":""?>>4 PM</option>
			<option value="61200"<?=($timeoffset == 61200)?" SELECTED ":""?>>5 PM</option>
			<option value="64800"<?=($timeoffset == 64800)?" SELECTED ":""?>>6 PM</option>
			<option value="68400"<?=($timeoffset == 68400)?" SELECTED ":""?>>7 PM</option>
			<option value="72000"<?=($timeoffset == 72000)?" SELECTED ":""?>>8 PM</option>

			<option value="75600"<?=($timeoffset == 75600)?" SELECTED ":""?>>9 PM</option>

			<option value="79200"<?=($timeoffset == 79200)?" SELECTED ":""?>>10 PM</option>
			<option value="82800"<?=($timeoffset == 82800)?" SELECTED ":""?>>11 PM</option>


			</select></td>
		</tr>

		<tr>
			<td>&nbsp;</td>
			<td height="30">

				<input type="checkbox" name="fix_last_ran_time" value="1" <?=(!$id)?' CHECKED DISABLED':''?>>Fix/Re-Calculate Last ran time

			</td>
		</tr>

		<tr valign="top">
			<th align="left" height="30">Settings:</th>
			<td><textarea name="settings" rows="5" cols="55"><?=htmlentities($row['settings'])?></textarea></td>
		</tr>



		<tr>
			<th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
		</tr>
		</form>
		</table>
		<script>


			toggleTimeMode($('#interval').val());

		</script><?


	}




	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadReports();return false;\">";

		return $var;
	}
}
