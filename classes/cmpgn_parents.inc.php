<?php

/***************************************************************
     *	Campaigns - handles listing and editing campaigns
     *	Written By: Jonathan Will
     ***************************************************************/

$_SESSION['cmpgn_parents'] = new CampaignParents;

class CampaignParents{
	
    public $table	= 'campaign_parents';			## Classes main table to operate on
    public $orderby	= 'id';		## Default Order field
    public $orderdir	= 'DESC';	## Default order direction
    ## Page  Configuration
    public $pagesize	= 20;	## Adjusts how many items will appear on each page
    public $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
    public $index_name = 'cmpgn_parents_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    public $frm_name = 'cmpgnparentsnextfrm';
    public $order_prepend = 'cmpgn_parents_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING
 
    
    public function CampaignParents(){
    	
        ## REQURES DB CONNECTION!
        $this->handlePOST();
        
    }
    
    
    
    
    /**
    *
    * @param string $currentSelected
    * @return string $showDD
    *
    **/
    public function makeDDvalIDtxtCODE($currentSelected){
    	
      $sql = "SELECT id, code FROM " . $this->table . " WHERE deleted=0";
      
      $res = $_SESSION['dbapi']->query($sql,1);
      
      $showDD = "<select name='parent_campaign_id' id='dd-parent_campaign_id'>";
      $showDD .= "<option value='0'>[None]</option>";
      
      if(mysqli_num_rows($res) > 0){
      	
        for($x=0;$row = mysqli_fetch_array($res);$x++){
          $showDD .= "<option value='" . $row['id'] . "'";
          
          if($row['id'] == $currentSelected) {
            $showDD .= " selected";
          }
          
          $showDD .= ">" . $row['code'] . "</option>";
          
        }
        
      }
      
      
      
      $showDD .= "</select>";
      return $showDD;
    }

    /**
     *
     * @param string $name name and id property of select statement being generated
     * @param string $sel current value for this record
     * @param string $onchange script to assign to onchange event
     * @param boolean|string $blank_entry option text to use for blank entry
     * @return string $showDD the complete select statement to be rendered
     *
     **/
    public function makeCampaignParentDD($name, $sel, $onchange=NULL, $blank_entry=false){
        $sql = "SELECT id, code FROM " . $this->table . " WHERE deleted=0 ORDER BY code ASC";
        $res = $_SESSION['dbapi']->query($sql,1);
        $showDD = "<select name='" . $name . "' id='" . $name . "'";
        if(isset($onchange)) {
            $showDD .= " onchange='" . htmlentities(trim($onchange)) . "'";
        }
        $showDD .= ">";
        if($blank_entry) {
            $showDD .= "<option value='0'>" . $blank_entry . "</option>";
        }
        if(mysqli_num_rows($res) > 0){
            for($x=0;$row = mysqli_fetch_array($res);$x++){
                $showDD .= "<option value='" . $row['id'] . "'";
                if($row['id'] == $sel) {
                    $showDD .= " selected";
                }
                $showDD .= ">" . $row['code'] . "</option>";
            }
        }
        $showDD .= "</select>";
        return $showDD;
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
    public function listEntrys(){
        ?>
		<script>
			var campaign_parent_delmsg = 'Are you sure you want to delete this campaign parent?';
			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";
			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
			var CampaignParentsTableFormat = [
				['id','align_center'],
				['name','align_left'],
				['code','align_center'],
  				['[delete]','align_center']
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
			function loadCampaign_parents(){
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
				<?=$this->order_prepend?>totalcount = parseXMLData('campaign_parent',CampaignParentsTableFormat,xmldoc);
				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){
					makePageSystem('campaign_parents',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadCampaign_parents()'
								);
				}else{
					hidePageSystem('campaign_parents');
				}
				eval('campaign_parents_loading_flag = false');
			}
			function handleCampaign_parentListClick(id){
				displayAddCampaignParentDialog(id);
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
		<div id="dialog-modal-add-campaign-parent" title="Adding new Campaign Parent" class="nod"></div>
		<form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadCampaign_parents();return false">
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
				height: 145,
				modal: false,
				draggable:true,
				resizable: false
			});
			loadCampaign_parents();
		</script>
		<?
    }

    public function makeAdd($id){
    	
        $id=intval($id);
        
        if ($id) {
            $row = $_SESSION['dbapi']->campaign_parents->getByID($id);
        } 
        
        
        
        ?><script>

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
							loadCampaign_parents();
							displayAddCampaignParentDialog(res);
							alert(result['message']);
						}
					});
				}
				return false;
			}
			// SET TITLEBAR
			$('#dialog-modal-add-campaign-parent').dialog( "option", "title", '<?=($id)?'Editing Campaign Parent #'.$id.' - '.addslashes(htmlentities($row['name'])):'Adding new Campaign Parent'?>' );
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
        <input id="campaign_parent_code" name="code" type="text" onkeyup="this.value = this.value.toUpperCase();"  title="3 - 16 characters, uppercase and digits only" size="20" pattern="[A-Z0-9]{3,16}" maxlength="16" value="<?=htmlentities($row['code'])?>">
			</td>
		</tr>
		<tr>
			<th colspan="2" align="center"><input type="submit" value="Save Changes"></th>
		</tr>
		</form>
		</table>
		<?
    }

    public function getOrderLink($field){
        $var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';
        $var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";
        $var.= ");loadCampaign_parents();return false;\">";
        return $var;
    }
}