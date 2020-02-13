<?	/***************************************************************
	 *	Problems - You're having them, we want to fix them.
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['problems'] = new Problems;


class Problems{

	var $table	= 'lead_tracking';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'prob_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'probnextfrm';

	var $order_prepend = 'prob_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function Problems(){


		## REQURES DB CONNECTION!
		$this->handlePOST();
	}


	function handlePOST(){

		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/problems.api.php FOR POST HANDLING!
		// <3 <3 -Jon

	}

	function handleFLOW(){
		# Handle flow, based on query string

		if(!checkAccess('problems')){


			accessDenied("Problems");

			return;

		}else{
			if(isset($_REQUEST['view_problem'])){

				$this->makeView($_REQUEST['view_problem']);

			}else{
				$this->listEntrys();
			}
		}

	}






	function listEntrys(){


		?><script>

			var name_delmsg = 'Are you sure you want to delete this problem?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;




			var ProblemsTableFormat = [
				['extension','align_center'],
				['[get:server_name:px_server_id]','align_center'],
				['[render:vici_lead:lead_id]','align_center'],
				['[render:recording_url:recording_id]','align_center'],
				['[get:username:user_id]','align_center'],
				['[time:time]','align_center'],
				['problem_description','align_center'],
				['[render:carrier_channel]','align_center'],
				['[render:server_ip]','align_center']
				<?/**['[acknowledged]','align_center'],
				['[fixed]','align_center'],
				['[willnotfix]','align_center']**/?>
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getProblemsURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=problems&"+
								"mode=xml&"+

								's_id='+escape(frm.s_id.value)+"&"+
								's_px_server_id='+escape(frm.s_px_server_id.value)+"&"+
								's_lead_id='+escape(frm.s_lead_id.value)+"&"+
								's_problem='+escape(frm.s_problem.value)+"&"+
								's_problem_acknowledged='+escape(frm.s_problem_acknowledged.value)+"&"+
								's_problem_solved='+escape(frm.s_problem_solved.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var problems_loading_flag = false;

			/**
			* Load the name data - make the ajax call, callback to the parse function
			*/
			function loadProblems(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = problems_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("PROBLEMS ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('problems_loading_flag = true');
				}



				loadAjaxData(getProblemsURL(),'parseProblems');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseProblems(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('problem',ProblemsTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('problems',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadProblems()'
								);

				}else{

					hidePageSystem('problems');

				}

				eval('problems_loading_flag = false');
			}


			function handleProblemListClick(id){

				displayAddProblemDialog(id);

			}


			function displayAddProblemDialog(id){

				var objname = 'dialog-modal-add-problem';


				if(id > 0){
					$('#'+objname).dialog( "option", "title", 'Viewing Problem' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Problem' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=problems&view_problem="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			function resetProblemForm(frm){

				frm.s_id.value = '';
				frm.s_lead_id.value = '';
				frm.s_px_server_id.value = '';
				frm.s_problem.value = '';
				frm.s_problem_acknowledged.value = '';
				frm.s_problem_solved.value = '';

			}


			var problemsrchtog = false;

			function toggleProblemSearch(){
				problemsrchtog = !problemsrchtog;
				ieDisplay('problem_search_table', problemsrchtog);
			}


			function markRecord(id, field, value){

				//alert("id:"+id+" field:"+field+" val:"+value);

				// POST TO API
				var params = field+"="+value+'&record_id='+id;

				$.ajax({
						type: "POST",
						cache: false,
						url: 'ajax.php?mode=mark_record',
						data: params,
						error: function(){
							alert("Error saving user form. Please contact an admin.");
						},
						success: function(msg){


//alert(msg);

							// 	REFRESH LIST
							loadProblems();
						}
				});



			}

		</script>
		<div id="dialog-modal-add-problem" title="Viewing Problem" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadNames();return false">
			<input type="hidden" name="searching_problem">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td>
						Problems

						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Search" onclick="toggleProblemSearch()">
					</td>
					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="problems_prev_td" class="page_system_prev"></td>
							<td id="problems_page_td" class="page_system_page"></td>
							<td id="problems_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" width="100%" id="problem_search_table" class="nod">
			<tr>
				<td rowspan="2"><font size="+1">SEARCH</font></td>
				<th class="row2">ID</th>
				<th class="row2">Server</th>
				<th class="row2">Lead ID</th>
				<th class="row2">Problem</th>
				<th class="row2">Acknowledged</th>
				<th class="row2">Solved</th>

				<td><input type="submit" value="Search" name="the_Search_button" onclick="loadProblems()"></td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_id" size="5" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>
				<td align="center">
					<?=$this->makePXServerDD('s_px_server_id', intval($_REQUEST['s_px_server_id']))?>
				</td>
				<td align="center"><input type="text" name="s_lead_id" size="5" value="<?=htmlentities($_REQUEST['s_lead_id'])?>"></td>
				<td align="center"><input type="text" name="s_problem" size="12" value="<?=htmlentities($_REQUEST['s_problem'])?>"></td>
				<td align="center">
					<select name="s_problem_acknowledged">
						<option value="" <?=($_REQUEST['s_problem_acknowledged'] == '')?' SELECTED ':''?>>[All]</option>
						<option value="no" <?=($_REQUEST['s_problem_acknowledged'] == 'no')?' SELECTED ':''?>>No</option>
						<option value="yes" <?=($_REQUEST['s_problem_acknowledged'] == 'yes')?' SELECTED ':''?>>Yes</option>

					</select>
				</td>

				<td align="center">
					<select name="s_problem_solved">
						<option value="" <?=($_REQUEST['s_problem_solved'] == '')?' SELECTED ':''?>>[All]</option>
						<option value="no" <?=($_REQUEST['s_problem_solved'] == 'no')?' SELECTED ':''?>>No</option>
						<option value="yes"  <?=($_REQUEST['s_problem_solved'] == 'yes')?' SELECTED ':''?>>Yes</option>
						<option value="willnotfix" <?=($_REQUEST['s_problem_solved'] == 'willnotfix')?' SELECTED ':''?>>Will not fix</option>
					</select>

				</td>

				<td><input type="button" value="Reset" onclick="resetProblemForm(this.form);resetPageSystem('<?=$this->index_name?>');loadProblems();"></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="problem_table">
			<tr>

				<th class="row2" align="left"><?=$this->getOrderLink('extension')?>Extension</a></th>
				<th class="row2"><?=$this->getOrderLink('px_server_id')?>Server</a></th>
				<th class="row2"><?=$this->getOrderLink('lead_id')?>Lead ID</a></th>
				<th class="row2"><?=$this->getOrderLink('recording_id')?>Recording ID</a></th>
				<th class="row2"><?=$this->getOrderLink('user_id')?>User</a></th>
				<th class="row2"><?=$this->getOrderLink('time')?>Time</a></th>
				<th class="row2"><?=$this->getOrderLink('problem_description')?>Description</a></th>
				<th class="row2"><?=$this->getOrderLink('carrier_channel')?>Carrier</a></th>
				<th class="row2"><?=$this->getOrderLink('server_ip')?>Dialer</a></th>
				<th class="row2" colspan="3">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-add-problem").dialog({
				autoOpen: false,
				width: 400,
				height: 260,
				modal: false,
				draggable:true,
				resizable: false
			});

			loadProblems();

		</script><?

	}

	function makePXServerDD($name, $sel){

		$out = '<select name="'.$name.'" id="'.$name.'" >';
		$out.= '<option value="">[SELECT ONE]</option>';

		$res = $_SESSION['dbapi']->query("SELECT * FROM servers ORDER BY name ASC", 1);

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$out .= '<option value="'.$row['id'].'" ';
			$out .= ($sel == $row['id'])?' SELECTED ':'';
			$out .= '>'.htmlentities($row['name']).'</option>';
		}


		$out .= '</select>';
		return $out;
	}

	function makeView($id){

		$row = $_SESSION['dbapi']->problems->getByID($id);

//
//		print_r($row);


		?><table border="0" width="100%">
		<tr>
			<th align="left">Record ID:</th>
			<td><?=htmlentities($row['id'])?></td>
		</tr>
		<tr>
			<th align="left">Lead ID:</th>
			<td><?=htmlentities($row['lead_id'])?></td>
		</tr>
		<tr>
			<th align="left">Call ID:</th>
			<td><?=htmlentities($row['call_id'])?></td>
		</tr>
		<tr>
			<th align="left">Recording ID:</th>
			<td><a href="<?=htmlentities($row['recording_url'])?>" target="_blank" style="text-decoration:underline"><?=htmlentities($row['recording_id'])?></a></td>
		</tr>
		<tr>
			<th align="left">PX Server ID:</th>
			<td><?=htmlentities($row['px_server_id'])?></td>
		</tr>
		<tr>
			<th align="left">Dialer IP:</th>
			<td><?=htmlentities($row['server_ip'])?></td>
		</tr>
		<tr>
			<th align="left">Carrier Channel:</th>
			<td><?=htmlentities($row['carrier'])?></td>
		</tr>
		<tr>
			<th align="left">Time:</th>
			<td><?=date("g:i:sa m/d/Y", $row['time'])?></td>
		</tr>
		<tr>
			<th align="left">Problem:</th>
			<td><?=htmlentities($row['problem'].' - '.$row['problem_description'])?></td>
		</tr>

		<tr>
			<th align="left">Problem Acknowledged:</th>
			<td><?=htmlentities($row['problem_acknowledged'])?></td>
		</tr>

		<tr>
			<th align="left">Problem Solved:</th>
			<td><?=htmlentities($row['problem_solved'])?></td>
		</tr>
		<tr>
			<td colspan="2" align="center">
			<?
				$vici_ip = ($row['vici_ip'])?$row['vici_ip']:"10.100.0.90";

				?><input type="button" value="View Lead" onclick="window.open('http://<?=$vici_ip?>/vicidial/admin_modify_lead.php?lead_id=<?=$row['lead_id']?>&archive_search=No');">
			</td>
		</tr>
		</table><?

	}


	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadProblems();return false;\">";

		return $var;
	}
}
