<?	/***************************************************************
	 *	TILE NOTES - A person note tracking system in small/TILE format, for home screen integration
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['home_tile_user_count'] = new HomeTileUserCountClass;


class HomeTileUserCountClass{

	// READ ONLY PREFS VARIABLE (modify the one on the $_SESSION['home'] object)
	public $prefs = null;
	public $prefs_idx = -1;
	
	
	public $area_name = 'user_count';
	
	public $tile_width = 0; // 0 = USE DEFAULT FOR HOME SECTION. OTHERWISE = WIDTH OVERRIDE OF DEFAULT VALUE
	public $tile_height = 0;// 0 = USE DEFAULT FOR HOME SECTION. OTHERWISE = HEIGHT OVERRIDE OF DEFAULT VALUE
	
	function HomeTileUserCountClass(){

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
	
	function makeConfigure($tile_idx){
		
		$this->prefs_idx = $tile_idx;
		
		?><form method="POST" action="<?=stripurl('')?>" onsubmit="saveConfigPrefs(this); return false">
			<input type="hidden" name="saving_config" value="<?=$this->area_name?>" />
			<input type="hidden" name="tile_idx" value="<?=$this->prefs_idx?>" />
			
		<table border="0" width="100%" height="100%">
		<tr>
			<th>Timeframe:</th>
			<td><select name="timeframe">
			
				<option value="day">Day</option>
				<option value="week"<?=(	$this->prefs['timeframe'] == 'week')?" SELECTED ":""?>>Week</option>
				<option value="month"<?=(	$this->prefs['timeframe'] == 'month')?" SELECTED ":""?>>Month</option>
				<option value="year"<?=(	$this->prefs['timeframe'] == 'year')?" SELECTED ":""?>>Year</option>
			
			</select></td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<input type="submit" value="Save" />
			</td>
		</tr>
		</table>
		</form><?
	}

	function renderTile(){
		
		

		
		?><script>
			function editConfig(){
				var objname = 'dialog-modal-edit_config';

				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("index.php?area=home&sub_section=<?=$this->area_name?>&edit_config=<?=$this->tile_idx?>&printable=1&no_script=1");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			function saveConfigPrefs(frm){

				var area = frm.saving_config.value;
				
				var params = getFormValues(frm);
				
				$.ajax({
					type: "POST",
					cache: false,
					url: 'api/api.php?get='+area+'&mode=xml&action=edit_config',
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

							$('#dialog-modal-edit_config').dialog("close");
						
							alert(result['message']);
						}
	
					}


				});

			}
			

		</script>
		
		<div id="dialog-modal-edit_config" title="Editing Configuration" class="nod"></div>
		
		
		<li id="homescr_tile_user_count" class="homeScreenTile" style="width:<?=$this->tile_width?>px">
		
			<table border="0" width="100%">
			<tr>
				<th class="homeScreenTitle">Users Online
				
					<span style="float:right;padding-right:10px">

										
						<a href="#" title="Configure" onclick="editConfig();return false"><img src="images/gear_icon.png" height="13" border="0" /></a>
						
					</span>
				</th>
			</tr>
			<tr>
				<td>
					<img src="graph.php?area=user_charts&max_mode=1&time_frame=<?=($this->prefs['timeframe'])?$this->prefs['timeframe']:'day'?>&width=<?=($this->tile_width-6)?>&height=<?=($this->tile_height-30)?>" border="0" height="<?=($this->tile_height-30)?>" width="<?=($this->tile_width-6)?>">
				</td>
			</tr>
			</table>
		</li>
		
		<script>
		

		$("#dialog-modal-edit_config").dialog({
			autoOpen: false,
			width: 500,
			height: 200,
			modal: false,
			draggable:true,
			resizable: true
		});

		
		</script><?
	
	}
	

	
	function savePreferences(){
		
		$_SESSION['home']->savePreferences();
	
	}
		
	


}
