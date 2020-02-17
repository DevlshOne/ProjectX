<?	/***************************************************************
	 *	Offices GUI
	 ***************************************************************/

$_SESSION['offices'] = new OfficesClass;


class OfficesClass{


	var $table		= 'offices';		## Class main table to operate on
	var $orderby	= 'id';							## Default Order field
	var $orderdir	= 'DESC';							## Default order direction


	## Page  Configuration
	var $pagesize	= 20;						## Adjusts how many items will appear on each page
	var $index		= 0;						## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'ofc_list';		## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name 	= 'ofcnextfrm';

	var $order_prepend = 'ifc_';		## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING


	function OfficesClass(){

		include_once("db.inc.php");


		$this->handlePOST();

	}



	function handlePOST(){

	}

	function handleFLOW(){


		if(!checkAccess('offices')){


			accessDenied("Offices");

			return;

		}else{

			# Handle flow, based on query string
			if(isset($_REQUEST['add_office'])){

				$this->makeAdd($_REQUEST['add_office']);

			}else{

				$this->listEntrys();

			}

		}

	}



	function listEntrys(){


		?>
		<script>

			var office_delmsg = 'Are you sure you want to delete this office?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;


			var OfficesTableFormat = [
				['id','align_left'],
				['enabled','align_left'],
				['name','align_left'],
				['status','align_left'],
				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getOfficesURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=offices&"+
								"mode=xml&"+

								's_id='+escape(frm.s_id.value)+"&"+
								's_enabled='+escape(frm.s_enabled.value)+"&"+
								's_status='+escape(frm.s_status.value)+"&"+
								
								's_name='+escape(frm.s_name.value)+"&"+
								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var offices_loading_flag = false;

			/**
			* Load the offices data - make the ajax call, callback to the parse function
			*/
			function loadOffices(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = offices_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					return;

				}else{

					eval('offices_loading_flag = true');
				}

				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				$('#total_count_div').html('<img src="images/ajax-loader.gif" border="0">');

				loadAjaxData(getOfficesURL(),'parseOffices');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseOffices(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('office',OfficesTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('offices',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadOffices()'
								);

				}else{

					hidePageSystem('offices');

				}

				eval('offices_loading_flag = false');
			}


			function handleOfficeListClick(id){

				displayViewOfficeDialog(id);

			}


			function displayViewOfficeDialog(id){

				var objname = 'dialog-modal-view-office';


				if(id > 0){
					$('#'+objname).dialog( "option", "title", 'Editing Office' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Office' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=offices&add_office="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');

				$('#'+objname).dialog('option', 'height', '425');
			}

			function resetOfficesForm(frm){

				// SET FORM VALUES TO BLANK
				frm.s_id.value = '';
				frm.s_enabled.value = '';
				frm.s_name.value='';
				frm.s_status.value='';
			}



		</script>
		<div id="dialog-modal-view-office" title="Viewing Office" class="nod">
		<?

		?>
		</div><?



		?>
		<form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadOffices();return false">
		
		<input type="hidden" name="searching_office">

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">
				<table border="0" width="100%" >
					<tr>
						<td width="500">
						Offices
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Add" onclick="displayViewOfficeDialog(0)">
						</td>

						<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadOffices();return false">
							<option value="20">20</option>
							<option value="50">50</option>
							<option value="100">100</option>
							<option value="500">500</option>
						</select></td>

						<td align="right"><?
							/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
							<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
								<tr>
									<td id="offices_prev_td" class="page_system_prev"></td>
									<td id="offices_page_td" class="page_system_page"></td>
									<td id="offices_next_td" class="page_system_next"></td>
								</tr>
							</table></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<table border="0" width="100%" id="office_search_table">
					<tr>
						<td rowspan="2" width="100" align="center" style="border-right:1px solid #000">
							<span id="total_count_div"></span>
						</td>
						<th class="row2">ID</th>
						<th class="row2">Enabled</th>
						<th class="row2">Status</th>
						<th class="row2">Name</th>
						<td><input type="submit" value="Search" name="the_Search_button"></td>
					</tr>
					<tr>
						<td align="left"><input type="text" name="s_id" size="2" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>
						<td align="left"><select name="s_enabled" id="s_enabled">
								<option value="">[All]</option>
								<option value="yes">yes</option>
								<option value="no">no</option>
							</select>
						</td>
						<td align="left"><select name="s_status">
							<option value="">[All]</option>
							<option value="new" <?=($_REQUEST['s_status'] == 'new')?" SELECTED ":''?>>New</option>
							<option value="pending" <?=($_REQUEST['s_status'] == 'pending')?" SELECTED ":''?>>Pending</option>
							<option value="ready" <?=($_REQUEST['s_status'] == 'ready')?" SELECTED ":''?>>Ready</option>
						</select></td>
						<td align="left"><input type="text" name="s_name" size="10" value="<?=htmlentities($_REQUEST['s_name'])?>"></td>
						<td><input type="button" value="Reset" onclick="resetOfficesForm(this.form);resetPageSystem('<?=$this->index_name?>');loadOffices();"></td>
					</tr>
				</table>
			</td>
		</tr>
		</form>
		<tr>
			<td colspan="2">
				<table border="0" width="100%" id="office_table">
					<tr>
						<th class="row2" align="left"><?=$this->getOrderLink('id')?>ID</a></th>
						<th class="row2" align="left"><?=$this->getOrderLink('enabled')?>Enabled</a></th>
						<th class="row2" align="left"><?=$this->getOrderLink('name')?>Name</a></th>
						<th class="row2" align="left"><?=$this->getOrderLink('status')?>Status</a></th>
					</tr>
				</table>
			</td>
		</tr>

		</table>

		<script>

			$("#dialog-modal-view-office").dialog({
				autoOpen: false,
				width: 500,
				height: 425,
				modal: false,
				draggable:true,
				resizable: false
			});

			$("#dialog-modal-view-office").dialog("widget").draggable("option","containment","#main-container");

			loadOffices();

		</script><?


	}

	
	function makeAdd($id){

		$id=intval($id);


		if($id){

			$row = $_SESSION['dbapi']->offices->getByID($id);

		}

		?><script>


			function submitOfficeFrm(frm){

				var params = getFormValues(frm,'');

				$.ajax({
					type: "POST",
					cache: false,
					url: 'api/api.php?get=offices&mode=xml&action=edit',
					data: params,
					error: function(){
						alert("Error saving offices form. Please contact an admin.");
					},
					success: function(msg){

						var result = handleEditXML(msg);
						var res = result['result'];

						if(res <= 0){

							alert(result['message']);

							return;

						}

						loadOffices();

						displayViewOfficeDialog(res);

						alert(result['message']);

					}


				});

				return false;

			}


			var cpynewshow=false;

			function toggleNewCompany(){
				cpynewshow = !cpynewshow;
				
				if(!cpynewshow){
					// BLANK OUT COMPANY NAME
					$('#new_company_name').val('');
				}
				ieDisplay('newcmpytrrow', cpynewshow);
			}
			
			// SET TITLEBAR
			$('#dialog-modal-view-Office').dialog( "option", "title", '<?=($id)?'Editing Office #'.$id.' - '.htmlentities($row['name']):'Adding new Office'?>' );


		</script>

		<form method="POST" id="ofc_add_frm" action="<?=stripurl('')?>" autocomplete="off" onsubmit="submitOfficeFrm(this); return false">
			
			<input type="hidden" id="adding_office" name="adding_office" value="<?=$id?>">

			<table border="0" width="100%">
			<tr>
				<th align="left" height="30">OFFICE ID:</th>
				<td>
					<input type="text" name="office_id" value="<?=$row['id']?>" size="2" maxlength="2" onkeyup="this.value = this.value.replace(/^0-9/g, '')" />
					&nbsp;&nbsp;Enabled:<input type="checkbox" name="enabled" value="yes" <?=($row['enabled'] == 'yes')?" CHECKED ":''?>>
				</td>
			</tr>
			<tr>
				<th align="left" height="30">Company:</th>
				<td>
					<?=makeCompanyDD('company_id', ($row['id'])?intval($row['company_id']):"1", '', false)?>
					
					<input type="button" value="New" onclick="toggleNewCompany()" />
					
				</td>
			</tr>
			<tr>
				<td colspan="2" >
					<table border="0" align="center" id="newcmpytrrow" class="nod">
					<tr>
						<th align="left" height="30">New Company Name:</th>
						<td><input id="new_company_name" name="new_company_name" type="text" size="25" value="" required placeholder="Add new company name here"></td>
					</tr>
					</table>
				</td>
			</tr>
			<tr>
				<th align="left" height="30">Name:</th>
				<td><input name="name" type="text" size="25" value="<?=htmlentities($row['name'])?>" required placeholder="Enter a name for this office."></td>
			</tr>
			<tr>
				<th align="left" height="30">Status:</th>
				<td><select name="status">
					<option value="new" <?=($row['status'] == 'new')?" SELECTED ":''?>>New</option>
					<option value="pending" <?=($row['status'] == 'pending')?" SELECTED ":''?>>Pending</option>
					<option value="ready" <?=($row['status'] == 'ready')?" SELECTED ":''?>>Ready</option>
				</select></td>
			</tr>
			<tr>
				<th align="left" height="30">Contact Info:</th>
				<td><input name="contact_info" type="text" size="25" value="<?=htmlentities($row['contact_info'])?>"></td>
			</tr>
			<tr>
				<th align="left" height="30">Contact Number:</th>
				<td><input name="contact_number" type="text" size="40" value="<?=htmlentities($row['contact_number'])?>"></td>
			</tr>
			<tr>
				<th align="left" height="30">Notes:</th>
				<td><textarea name="notes" rows="5" cols="40"><?=htmlentities($row['notes'])?></textarea></td>
			</tr>			
			<tr>
				<th colspan="2" align="center" height="50">

					<input type="submit" value="Save Changes">

				</th>	
			</tr>
			</table>
		
		</form>
		
		<?


	}

	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadOffices();return false;\">";

		return $var;
	}

}


