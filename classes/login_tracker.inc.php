<?	/***************************************************************
	 *	Login Tracker - Handles list/search logins
	 ***************************************************************/

$_SESSION['login_tracker'] = new LoginTracker;


class LoginTracker{

	var $table		= 'logins';		## Classes main table to operate on
	var $orderby	= 'time';		## Default Order field
	var $orderdir	= 'DESC';		## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'login_tracker_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'login_trackernextfrm';

	var $order_prepend = 'login_tracker_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function LoginTracker(){


		## REQURES DB CONNECTION!



		$this->handlePOST();
	}


	function handlePOST(){


	}

	function handleFLOW(){
		# Handle flow, based on query string

		if(!checkAccess('login_tracker')){


			accessDenied("LoginTracker");

			return;

		}else{

			if(isset($_REQUEST['add_login'])){

				$this->makeAdd($_REQUEST['add_login']);

			}else{
				$this->listEntrys();
			}

		}

	}






	function listEntrys(){


		?><script>

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;


			var LoginsTableFormat = [
				['id','align_left'],
				['[time:time]','align_left'],
				['username','align_left'],
				//['[get:voice_name:voice_id]','align_center'],
				['result','align_left'],
				['section','align_left'],
				['ip','align_left']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getLoginsURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=login_tracker&"+
								"mode=xml&"+

								's_id='+escape(frm.s_id.value)+"&"+
								's_username='+escape(frm.s_username.value)+"&"+
								's_result='+escape(frm.s_result.value)+"&"+
								's_section='+escape(frm.s_section.value)+"&"+
								's_ip='+escape(frm.s_ip.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var logintracker_loading_flag = false;

			/**
			* Load the login data - make the ajax call, callback to the parse function
			*/
			function loadLogins(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = logintracker_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("NAMES ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('logintracker_loading_flag = true');
				}

				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				loadAjaxData(getLoginsURL(),'parseLogins');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseLogins(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('login',LoginsTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('logins',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadLogins()'
								);

				}else{

					hidePageSystem('logins');

				}

				eval('logintracker_loading_flag = false');
			}


			function handleLoginListClick(id){

				displayAddLoginDialog(id);

			}


			function displayAddLoginDialog(id){

				var objname = 'dialog-modal-add-login';


				if(id > 0){
					$('#'+objname).dialog( "option", "title", 'Editing login' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Login' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=login_tracker&add_login="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');

				$('#'+objname).dialog('option', 'height', '300');
			}

			function resetLoginForm(frm){

				frm.s_id.value = '';
				frm.s_username.value = '';
				frm.s_result.value='';
				frm.s_section.value='';
				frm.s_ip.value = '';

			}


			var loginsrchtog = false;

			function toggleLoginSearch(){
				loginsrchtog = !loginsrchtog;
				ieDisplay('login_search_table', loginsrchtog);
			}

		</script>
		<div id="dialog-modal-add-login" title="Adding new Login" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadLogins();return false">
			<input type="hidden" name="searching_login">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td width="500">
						Logins
						&nbsp;&nbsp;&nbsp;&nbsp;
						<?#<input type="button" value="Add" onclick="displayAddLoginDialog(0)">?>
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Search" onclick="toggleLoginSearch()">
					</td>

					<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadLogins();return false">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="500">500</option>
					</select></td>

					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="logins_prev_td" class="page_system_prev"></td>
							<td id="logins_page_td" class="page_system_page"></td>
							<td id="logins_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" width="100%" id="login_search_table" class="nod">
			<tr>
				<td rowspan="2"></td>
				<th class="row2">ID</th>
				<th class="row2">Username</th>
				<th class="row2">Result</th>
				<th class="row2">Section</th>
				<th class="row2">IP</th>
				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_id" size="5" value="<?=htmlentities($_REQUEST['s_id'])?>"></td>
				<td align="center"><input type="text" name="s_username" size="20" value="<?=htmlentities($_REQUEST['s_username'])?>"></td>
				<td align="center"><select name="s_result">
						<option value="">[All]</option>

						<option value="success">success</option>
						<option value="failure">failure</option>
						<option value="success-code">success-code</option>
						<option value="failure-code">failure-code</option>
						<option value="success-api">success-api</option>
						<option value="failure-api">failure-api</option>
					</select></td>
				<td align="center"><select name="s_section">
						<option value="">[All]</option>

						<option value="admin">admin</option>
						<option value="client">client</option>
						<option value="liveclient">liveclient</option>
						<option value="quiz">quiz</option>
						<option value="rouster">rouster</option>
						<option value="roustersys">roustersys</option>
						<option value="verifier">verifier</option>
						<option value="API">API</option>
					</select></td>
				<td align="center"><input type="text" name="s_ip" size="20" value="<?=htmlentities($_REQUEST['s_ip'])?>"></td>
				<td><input type="button" value="Reset" onclick="resetLoginForm(this.form);resetPageSystem('<?=$this->index_name?>');loadLogins();"></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="login_table">
			<tr>

				<th class="row2" align="left"><?=$this->getOrderLink('id')?>ID</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('time')?>Time</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('username')?>Username</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('result')?>Result</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('section')?>Section</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('ip')?>IP</a></th>
				<th class="row2">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-add-login").dialog({
				autoOpen: false,
				width: 500,
				height: 200,
				modal: false,
				draggable:true,
				resizable: false
			});

			loadLogins();

		</script><?

	}


	function makeAdd($id){

		$id=intval($id);


		if($id){

			$row = $_SESSION['dbapi']->login_tracker->getByID($id);


		}

		?><script>

			function validateLoginField(name,value,frm){

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



			function checkLoginFrm(frm){


				var params = getFormValues(frm,'validateLoginField');


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
						url: 'api/api.php?get=logins&mode=xml&action=edit',
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


							loadLogins();


							displayAddLoginDialog(res);

							alert(result['message']);

						}


					});

				}

				return false;

			}




			// SET TITLEBAR
			$('#dialog-modal-add-login').dialog( "option", "title", '<?=($id)?'Viewing Login #'.$id.' - '.htmlentities($row['username']):'Adding new Login'?>' );



		</script>
		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkLoginFrm(this); return false">
			<input type="hidden" id="adding_login" name="adding_login" value="<?=$id?>" >


		<table border="0" align="center">
		<tr>
			<th align="left" height="30">User ID:</th>
			<td><?=htmlentities($row['user_id'])?></td>
		</tr>	
		<tr>
			<th align="left" height="30">Time:</th>
			<td><?=htmlentities($row['time'])?></td>
		</tr>	
		<tr>
			<th align="left" height="30">Username:</th>
			<td><?=htmlentities($row['username'])?></td>
		</tr>
		<tr>
			<th align="left" height="30">Result:</th>
			<td><?=htmlentities($row['result'])?></td>
		</tr>
		<tr>
			<th align="left" height="30">Section:</th>
			<td><?=htmlentities($row['section'])?></td>
		</tr>
		<tr>
			<th align="left" height="30">Script ID:</th>
			<td><?=htmlentities($row['script_id'])?></td>
		</tr>
		<tr>
			<th align="left" height="30">Voice ID:</th>
			<td><?=htmlentities($row['voice_id'])?></td>
		</tr>			
		<tr>
			<th align="left" height="30">IP:</th>
			<td><?=htmlentities($row['ip'])?></td>
		</tr>		
		</form>
		</table><?


	}




	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadLogins();return false;\">";

		return $var;
	}
}
