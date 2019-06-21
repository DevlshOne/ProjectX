<?	/***************************************************************
	 *	Messages - Handles list/search/add/edit of messages
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['messages'] = new Messages;


class Messages{

	var $table	= 'messages';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'ASC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'msg_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'msgnextfrm';

	var $order_prepend = 'msg_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function Messages(){


		## REQURES DB CONNECTION!

		## USES CAMPAIGNS FOR ITS DROPDOWN
		include("classes/campaigns.inc.php");


		$this->handlePOST();
	}


	function handlePOST(){

		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/names.api.php FOR POST HANDLING!
		// <3 <3 -Jon

	}

	function handleFLOW(){
		# Handle flow, based on query string

		if(!checkAccess('messages')){


			accessDenied("Messages");

			return;

		}else{
			if(isset($_REQUEST['add_message'])){

				$this->makeAdd($_REQUEST['add_message']);

			}else{
				$this->listEntrys();
			}

		}

	}






	function listEntrys(){


		?><script>

			var message_delmsg = 'Are you sure you want to delete this message?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var MessagesTableFormat = [
				['type','align_center'],
				['[render:who]','align_center'],
				['message','align_left'],
				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getMessagesURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=messages&"+
								"mode=xml&"+

								's_type='+escape(frm.s_type.value)+"&"+
								's_who='+escape(frm.s_who.value)+"&"+
								's_message='+escape(frm.s_message.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var messages_loading_flag = false;

			/**
			* Load the message data - make the ajax call, callback to the parse function
			*/
			function loadMessages(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = messages_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("MESSAGES ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('messages_loading_flag = true');
				}



				loadAjaxData(getMessagesURL(),'parseMessages');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseMessages(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('message',MessagesTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('messages',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadMessages()'
								);

				}else{

					hidePageSystem('messages');

				}

				eval('messages_loading_flag = false');
			}


			function handleMessageListClick(id){

				displayAddMessageDialog(id);

			}


			function displayAddMessageDialog(id){

				var objname = 'dialog-modal-add-message';


				if(id > 0){
					$('#'+objname).dialog( "option", "title", 'Editing message' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new message' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=messages&add_message="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			function resetMessageForm(frm){

				frm.s_type.value = '';
				frm.s_who.value = '';
				frm.s_message.value='';

			}


			var messagesrchtog = false;

			function toggleMessageSearch(){
				messagesrchtog = !messagesrchtog;
				ieDisplay('message_search_table', messagesrchtog);
			}

		</script>
		<div id="dialog-modal-add-message" title="Adding new message" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadMessages();return false">
			<input type="hidden" name="searching_message">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td>
						Messages
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Add" onclick="displayAddMessageDialog(0)">
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Search" onclick="toggleMessageSearch()">
					</td>
					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="messages_prev_td" class="page_system_prev"></td>
							<td id="messages_page_td" class="page_system_page"></td>
							<td id="messages_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" width="100%" id="message_search_table" class="nod">
			<tr>
				<td rowspan="2"><font size="+1">SEARCH</font></td>
				<th class="row2">Type</th>
				<th class="row2">Who</th>
				<th class="row2">Message</th>
				<td><input type="submit" value="Search" name="the_Search_button" onclick="<?=$this->index_name?> = 0;" ></td>
			</tr>
			<tr>
				<td align="center"><input type="text" name="s_type" size="5" value="<?=htmlentities($_REQUEST['s_type'])?>"></td>
				<td align="center"><input type="text" name="s_who" size="20" value="<?=htmlentities($_REQUEST['s_who'])?>"></td>
				<td align="center"><input type="text" name="s_message" size="20" value="<?=htmlentities($_REQUEST['s_message'])?>"></td>
				<td><input type="button" value="Reset" onclick="resetMessageForm(this.form);resetPageSystem('<?=$this->index_name?>');loadMessages();"></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="message_table">
			<tr>

				<th class="row2"><?=$this->getOrderLink('type')?>Type</a></th>
				<th class="row2"><?=$this->getOrderLink('who')?>Who</a></th>
				<th class="row2" align="left" width="50%"><?=$this->getOrderLink('message')?>Message</a></th>
				<th class="row2">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

			$("#dialog-modal-add-message").dialog({
				autoOpen: false,
				width: 420,
				height: 220,
				modal: false,
				draggable:true,
				resizable: false
			});

			loadMessages();

		</script><?

	}


	function makeAdd($id){

		$id=intval($id);


		if($id){

			$row = $_SESSION['dbapi']->messages->getByID($id);


		}

		?><script>

			function validateMessageField(name,value,frm){

				//alert(name+","+value);


				switch(name){
				default:

					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;

				case 'type':
				case 'message':
				case 'who':


					if(!value)return false;

					return true;


					break;

				}
				return true;
			}



			function checkMessageFrm(frm){


				var params = getFormValues(frm,'validateMessageField');


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
						url: 'api/api.php?get=messages&mode=xml&action=edit',
						data: params,
						error: function(){
							alert("Error saving message form. Please contact an admin.");
						},
						success: function(msg){

//alert(msg);

							var result = handleEditXML(msg);
							var res = result['result'];

							if(res <= 0){

								alert(result['message']);

								return;

							}


							loadMessages();


							displayAddMessageDialog(res);

							alert(result['message']);

						}


					});

				}

				return false;

			}




			// SET TITLEBAR
			$('#dialog-modal-add-message').dialog( "option", "title", '<?=($id)?'Editing Message #'.$id.' - '.htmlentities($row['name']):'Adding new Message'?>' );


			function toggleWho(type){

				ieDisplay('spn_all',0);
				ieDisplay('spn_user',0);
				ieDisplay('spn_campaign',0);

				ieDisplay('spn_'+type,1);
			}

		</script>
		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkMessageFrm(this); return false">
			<input type="hidden" id="adding_message" name="adding_message" value="<?=$id?>" >


		<table border="0" align="center">
		<tr>
			<th align="left" height="30">Type</th>
			<td><select name="type" onchange="toggleWho(this.value)">
				<option value="all"<?=(			$row['type'] == 'all')?" SELECTED ":""?>>All Users</option>
				<option value="campaign"<?=(	$row['type'] == 'campaign')?" SELECTED ":""?>>Campaign Specific</option>
				<option value="user"<?=(		$row['type'] == 'user')?" SELECTED ":""?>>User Specific</option>
			</select></td>
		</tr>
		<tr>
			<th align="left" height="30">Who</th>
			<td>
				<span id="spn_all" class="nod">All Users</span>
				<span id="spn_campaign" class="nod"><?

					echo $_SESSION['campaigns']->makeDD('campaign_id',$row['who'],'',"  ",'',false);

				?></span>
				<span id="spn_user" class="nod">
					<?

					echo $this->makeUserDD('username',$row['who'],'',"  ",'',false);

				?>
				</span>
			</td>
		</tr>
		<tr>
			<th align="left" height="30">Message</th>
			<td>
				<input type="text" name="message" size="50" value="<?=htmlentities($row['message'])?>">
			</td>
		</tr>
		<tr>
			<th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
		</tr>
		</form>
		</table>

		<script>
			toggleWho('<?=($row['id'] > 0)?$row['type']:'all'?>');
		</script><?


	}

	function makeUserDD($name,$sel,$class){

		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out .= ($class)?' class="'.$class.'" ':'';
		$out .= ' >';

		$sel = strtolower($sel);

		$res = $_SESSION['dbapi']->query("SELECT username FROM `users` WHERE priv=2 ORDER BY `username` ASC");

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$out .= '<option value="'.$row['username'].'" '.(($sel == strtolower($row['username']))?' SELECTED ':'').'>';
			$out .= $row['username'].'</option>';
		}

		$out .= '</select>';
		return $out;
	}




	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadMessages();return false;\">";

		return $var;
	}
}
