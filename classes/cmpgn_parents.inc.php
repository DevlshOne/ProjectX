<?
/***************************************************************
     *	Campaigns - handles listing and editing campaigns
     *	Written By: Jonathan Will
     ***************************************************************/

$_SESSION['cmpgn_parents'] = new CampaignParents;

class CampaignParents
{
    public $table	= 'campaign_parents';			## Classes main table to operate on
    public $orderby	= 'id';		## Default Order field
    public $orderdir	= 'DESC';	## Default order direction
    ## Page  Configuration
    public $pagesize	= 20;	## Adjusts how many items will appear on each page
    public $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
    public $index_name = 'cmpgn_parents_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    public $frm_name = 'cmpgnparentsnextfrm';
    public $order_prepend = 'cmpgn_parents_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING
    public function CampaignParents()
    {
        ## REQURES DB CONNECTION!
        $this->handlePOST();
    }
    public function makeDD($name, $sel, $class, $onchange, $size, $blank_entry=1, $extra_where=null)
    {
        $names		= 'name';	## or Array('field1','field2')
        $value		= 'id';
        $seperator	= '';		## If $names == Array, this will be the seperator between fields
        $fieldstring='';
        if (is_array($names)) {
            $x=0;
            foreach ($names as $name) {
                $fieldstring.= $name.',';
            }
        } else {
            $fieldstring.=$names.',';
        }
        $fieldstring	.= $value;
        $sql = "SELECT $fieldstring FROM ".$this->table." WHERE 1 ".(($extra_where != null)?$extra_where:'');
        $DD = new genericDD($sql, $names, $value, $seperator);
        return $DD->makeDD($name, $sel, $class, $blank_entry, $onchange, $size);
    }
    public function makeDDByCode($name, $sel, $class, $onchange, $size, $blank_entry=1, $extra_where=null)
    {
        $names		= 'vici_campaign_id';
        ## or Array('field1','field2')
        $value		= 'id';
        $seperator	= '';
        ## If $names == Array, this will be the seperator between fields
        $fieldstring='';
        if (is_array($names)) {
            $x=0;
            foreach ($names as $name) {
                $fieldstring.= $name.',';
            }
        } else {
            $fieldstring.=$names.',';
        }
        $fieldstring	.= $value;
        $sql = "SELECT $fieldstring FROM ".$this->table." WHERE 1 ".(($extra_where != null)?$extra_where:'');
        $DD = new genericDD($sql, $names, $value, $seperator);
        return $DD->makeDD($name, $sel, $class, $blank_entry, $onchange, $size);
    }
    public function handlePOST()
    {
        // THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
        // SEE api/campaigns.api.php FOR POST HANDLING!
        // <3 <3 -Jon
    }
    public function handleFLOW()
    {
        # Handle flow, based on query string
        if (!checkAccess('campaigns')) {
            accessDenied("Campaigns");
            return;
        } else {
            if (isset($_REQUEST['add_campaign_parent'])) {
                $this->makeAdd($_REQUEST['add_campaign_parent']);
            } else {
                $this->listEntrys();
            }
        }
    }
    public function listEntrys()
    {
        ?>
		<script>
			var campaign_parents_delmsg = 'Are you sure you want to delete this campaign parent?';
			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";
			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
			var CampaignParentsTableFormat = [
				['id','align_center'],
				['name','align_left'],
				['code','align_center']
			];
			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getCampaignParentsURL(){
				var frm = getEl('<?=$this->frm_name?>');
				return 'api/api.php'+
								"?get=campaign_parents&"+
								"mode=xml&"+
								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}
			var campaign_parents_loading_flag = false;
			/**
			* Load the campaign data - make the ajax call, callback to the parse function
			*/
			function loadCampaignParents(){
				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = campaign_parents_loading_flag');
				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){
					//console.log("CAMPAIGNS ALREADY LOADING (BYPASSED) \n");
					return;
				}else{
					eval('campaign_parents_loading_flag = true');
				}
				loadAjaxData(getCampaignParentsURL(),'parseCampaignParents');
			}
			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseCampaignParents(xmldoc){
				<?=$this->order_prepend?>totalcount = parseXMLData('campaign_parents',CampaignParentsTableFormat,xmldoc);
				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){
					makePageSystem('campaign_parents',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadCampaignParents()'
								);
				}else{
					hidePageSystem('campaign_parents');
				}
				eval('campaign_parents_loading_flag = false');
			}
			function handleCampaignParentsListClick(id){
				displayAddCampaignParentsDialog(id);
			}
			function displayAddCampaignParentDialog(campaignparentid){
				var objname = 'dialog-modal-add-campaign-parent';
				if(campaignparentid > 0){
					$('#'+objname).dialog( "option", "title", 'Editing Campaign Parent' );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Campaign Parent' );
				}
				$('#'+objname).dialog("open");
				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
				$('#'+objname).load("index.php?area=campaign_parents&add_campaign_parent="+campaignparentid+"&printable=1&no_script=1");
				$('#'+objname).dialog('option', 'position', 'center');
			}

			function resetCampaignParentForm(frm){
				frm.s_id.value='';
				frm.s_name.value = '';
			}
		</script>
		<div id="dialog-modal-add-campaign-parent" title="Adding new Campaign Parent" class="nod">
		</div>
		<form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadCampaignParents();return false">
			<input type="hidden" name="searching_campaign_parents">
		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">
				<table border="0" width="100%" >
				<tr>
					<td>
						Campaign Parents
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Add" onclick="displayAddCampaignParentDialog(0)">
					</td>
					<td align="right"><?
                        /** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="campaign_parents_prev_td" class="page_system_prev"></td>
							<td id="campaign_parents_page_td" class="page_system_page"></td>
							<td id="campaign_parents_next_td" class="page_system_next"></td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</td>
		</tr>
</form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="campaign_parent_table">
			<tr>
				<th class="row2"><?=$this->getOrderLink('id')?>ID</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('name')?>Name</a></th>
				<th class="row2"><?=$this->getOrderLink('code')?>Code</a></th>
				<th class="row2">&nbsp;</th>
			</tr></table></td>
		</tr></table>
		<script>
			$("#dialog-modal-add-campaign-parent").dialog({
				autoOpen: false,
				width: 480,
				height: 220,
				modal: false,
				draggable:true,
				resizable: false
			});
			loadCampaignParents();
		</script>
		<?
    }

    public function makeAdd($id)
    {
        $id=intval($id);
        if ($id) {
            $row = $_SESSION['dbapi']->campaign_parents->getByID($id);
        } ?>
		<script>

			function validateCampaignParentField(name,value,frm){
				//alert(name+","+value);
				switch(name){
				default:
					// ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
					return true;
					break;
				case 'name':
					if(!value)return false;
					return true;
					break;
				}
				return true;
			}

			function checkCampaignParentFrm(frm){
				var params = getFormValues(frm,'validateCampaignParentField');
				// FORM VALIDATION FAILED!
				// param[0] == field name
				// param[1] == field value
				if(typeof params == "object"){
					switch(params[0]){
					default:
						alert("Error submitting form. Check your values");
						break;
					case 'name':
						alert("Please enter a name for this campaign.");
						eval('try{frm.'+params[0]+'.select();}catch(e){}');
						break;
					}
				// SUCCESS - POST AJAX TO SERVER
				}else{
					//alert("Form validated, posting");
					$.ajax({
						type: "POST",
						cache: false,
						url: 'api/api.php?get=campaign_parents&mode=xml&action=edit',
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
							loadCampaignParents();
							displayAddCampaignParentDialog(res);
							alert(result['message']);
						}
					});
				}
				return false;
			}
			// SET TITLEBAR
			$('#dialog-modal-add-campaign-parent').dialog( "option", "title", '<?=($id)?'Editing Campaign Parent #'.$id.' - '.htmlentities($row['name']):'Adding new Campaign Parent'?>' );
		</script>
		<form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkCampaignParentFrm(this); return false">
			<input type="hidden" id="adding_campaign_parent" name="adding_campaign_parent" value="<?=$id?>" >
		<table border="0" align="center">
		<tr>
			<th align="left" height="30">Name</th>
			<td><input name="name" type="text" size="50" value="<?=htmlentities($row['name'])?>"></td>
		</tr>
		<tr>
			<th align="left" height="30">Code</th>
			<td>
        <input name="code" type="text" title="Four characters minimum, uppercase and digits only" size="20" pattern="[A-Z0-9]{4,16}" maxlength="16" value="<?=htmlentities($row['name'])?>">
			</td>
		</tr>
		<tr>
			<th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
		</tr>
		</form>
		</table>
		<?
    }

    public function getOrderLink($field)
    {
        $var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';
        $var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";
        $var.= ");loadCampaignParents();return false;\">";
        return $var;
    }
}
