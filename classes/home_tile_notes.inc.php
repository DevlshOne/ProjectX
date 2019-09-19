<?	/***************************************************************
	 *	TILE NOTES - A person note tracking system in small/TILE format, for home screen integration
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['home_tile_notes'] = new HomeTileNotesClass;


class HomeTileNotesClass{

	public $table = "notes";
	public $orderby	= 'time';		## Default Order field
	public $orderdir= 'DESC';	## Default order direction
	
	
	## Page  Configuration
	public $pagesize	= 20;	## Adjusts how many items will appear on each page
	public $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
	
	public $index_name = 'note_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	public $frm_name = 'notenextfrm';
	
	public $order_prepend = 'note_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING
	
	
	
	
	// READ ONLY PREFS VARIABLE (modify the one on the $_SESSION['home'] object)
	public $prefs = null;
	public $prefs_idx = -1;
	
	
	
	public $tile_width = 0; // 0 = USE DEFAULT FOR HOME SECTION. OTHERWISE = WIDTH OVERRIDE OF DEFAULT VALUE
	public $tile_height = 0;// 0 = USE DEFAULT FOR HOME SECTION. OTHERWISE = HEIGHT OVERRIDE OF DEFAULT VALUE
	
	function HomeTileNotesClass(){

		if($this->tile_width <= 0){
			
			$this->tile_width = $_SESSION['home']->tile_width;
		}
		if($this->tile_height <= 0){
			
			$this->tile_height = $_SESSION['home']->tile_height;
		}
		$this->handlePOST();
	}


	function handlePOST(){


	}

	function handleFLOW($tile_idx, $tile_prefs){

		
		$this->prefs = $tile_prefs;
		$this->prefs_idx = $tile_idx;
		
		
		$this->renderTile();

	}
	
	function makeAdd($note_id){
		
		$note_id = intval($note_id);
		
		
		if($note_id > 0){
			
			$row = $_SESSION['dbapi']->ROquerySQL("SELECT * FROM `notes`  WHERE deleted='no' AND user_id='".$_SESSION['user']['id']."' AND id='".$note_id."'");
		}
		
		
		?><form method="POST" action="<?=stripurl('')?>" onsubmit="saveNoteRecord(this); return false">
			<input type="hidden" name="saving_note" />
			<input type="hidden" name="note_id" value="<?=intval($row['id'])?>" />
			
		<table border="0" width="100%" height="100%">
		<tr>
			<td height="*"><textarea name="note_text" rows="8" cols="50" style="width:100%;height:100%"><?=htmlentities($row['notes'])?></textarea></td>
		</tr>
		<tr>
			<td align="center">
				<input type="submit" value="Save Note" />
			</td>
		</tr>
		</table>
		</form><?
	}

	function renderTile(){
		
		

		
		?><script>
			function viewNotesRecord(id){
				var objname = 'dialog-modal-view_notes';

				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=home&sub_section=my_notes&edit_note="+id+"&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			function saveNoteRecord(frm){

				var params = getFormValues(frm);
				
				$.ajax({
					type: "POST",
					cache: false,
					url: 'api/api.php?get=my_notes&mode=xml&action=edit',
					data: params,
					error: function(){
						alert("Error saving form. Please contact an admin.");
					},
					success: function(msg){

						var result = handleEditXML(msg);
						var res = result['result'];

						if(res <= 0){

							alert(result['message']);

							return;

						}else{

							loadNotes();
							
							$('#dialog-modal-view_notes').dialog("close");
							//loadNames();
							//displayAddNameDialog(res);

							//loadSection("?area=home");
							
							
							//alert(result['message']);
						}
	
					}


				});

			}
			
			var note_delmsg = 'Are you sure you want to delete this note?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var NotesTableFormat = [
				['[maxlen:notes:30]','align_left priorityRender'],
				['[time:time]','align_center'],
				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getNotesURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=notes&"+
								"mode=xml&"+


				

// 								's_id='+escape(frm.s_id.value)+"&"+
// 								's_name='+escape(frm.s_name.value)+"&"+


		//						"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var notes_loading_flag = false;

			/**
			* Load the name data - make the ajax call, callback to the parse function
			*/
			function loadNotes(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = notes_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("NAMES ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('notes_loading_flag = true');
				}

				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				loadAjaxData(getNotesURL(),'parseNotes');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseNotes(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('note',NotesTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				/*if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('notes',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadNotes()'
								);

				}else{

					hidePageSystem('notes');

				}*/

				eval('notes_loading_flag = false');
			}


			function handleNoteListClick(id){

				viewNotesRecord(id);

			}
		</script>
		
		<div id="dialog-modal-view_notes" title="Editing Note" class="nod"></div>
		
		
		<li id="homescr_tile_notes" class="homeScreenTile" style="width:<?=$this->tile_width?>px">
		
			<form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadNotes();return false">
				<input type="hidden" name="searching_note">
			<table border="0" width="100%">
			<tr>
				<th class="homeScreenTitle">PERSONAL NOTES
				
					<span style="float:right;padding-right:10px">

						<a href="#" title="Add New" onclick="viewNotesRecord(0);return false"><img src="images/add_icon.png" height="13" border="0" /></a>					
						<a href="#" title="Configure"><img src="images/gear_icon.png" height="13" border="0" /></a>
						
					</span>
				</th>
			</tr>
			<tr>
				<td>
					<div style="overflow-y:auto;height:<?=($this->tile_height-30)?>px;">
					<table border="0" width="100%" id="note_table">
					<tr>
						<th class="row2" align="left"><?=$this->getOrderLink('notes')?>Note</a></th>
						<td class="row2" align="center"><?=$this->getOrderLink('time')?>Last Updated</a></td>
						<td class="row2" align="center">&nbsp;</td>
					</tr><?
					
						// COMMENTED OUT BECAUSE WE ARE AJAX RENDERING THIS INSTEAD NOW
						/*$rowarr = $_SESSION['dbapi']->ROfetchAllAssoc("SELECT * FROM `notes` WHERE deleted='no' AND user_id='".$_SESSION['user']['id']."' ORDER BY `time` DESC ");
						
						$color=0;
						foreach($rowarr as $row){
							
							$class='row'.($color++%2);
							$trimmednote = (strlen($row['notes']) > 64)?substr($row['notes'], 0 , 64)."...":$row['notes'];
							
							?><tr>
								<td class="hand <?=$class?>" align="left" onclick="viewNotesRecord(<?=$row['id']?>)"><?=htmlentities($trimmednote)?></td>
								<td class="hand <?=$class?>" align="center" onclick="viewNotesRecord(<?=$row['id']?>)"><?=date("g:ia m/d/Y", $row['time'])?></td>
							</tr><?
						}*/
		
						
					?></table>
					</div>
				</td>
			</tr>
			</table>
		</li>
		
		<script>
		

		$("#dialog-modal-view_notes").dialog({
			autoOpen: false,
			width: 500,
			height: 200,
			modal: false,
			draggable:true,
			resizable: true
		});

		loadNotes();
		
		
		</script><?
	
	}
	
	
	function getOrderLink($field){
		
		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';
		
		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";
		
		$var.= ");loadNotes();return false;\">";
		
		return $var;
	}
	
	function savePreferences(){
		
		$_SESSION['home']->savePreferences();
	
	}
		
	


}
