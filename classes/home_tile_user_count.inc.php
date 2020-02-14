<?	/***************************************************************
	 *	TILE NOTES - A person note tracking system in small/TILE format, for home screen integration
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['home_tile_user_count'] = new HomeTileUserCountClass;


class HomeTileUserCountClass
{

    // READ ONLY PREFS VARIABLE (modify the one on the $_SESSION['home'] object)
    public $prefs = null;
    public $prefs_idx = -1;
    public $area_name = 'user_count';
    public $tile_width = 300; // 0 = USE DEFAULT FOR HOME SECTION. OTHERWISE = WIDTH OVERRIDE OF DEFAULT VALUE
    public $tile_height = 205;// 0 = USE DEFAULT FOR HOME SECTION. OTHERWISE = HEIGHT OVERRIDE OF DEFAULT VALUE

    function HomeTileUserCountClass()
    {
        if ($this->tile_width <= 0) {
            $this->tile_width = $_SESSION['home']->tile_width;
        }
        if ($this->tile_height <= 0) {
            $this->tile_height = $_SESSION['home']->tile_height;
        }
        $this->handlePOST();
    }

    function handlePOST()
    {
	}
	function handleFLOW($tile_idx, $tile_prefs){
		$this->prefs = $tile_prefs;
		$this->prefs_idx = $tile_idx;
		$this->renderTile($tile_idx, $tile_prefs);
	}
	function makeConfigure($tile_idx)
    {
        $this->prefs_idx = $tile_idx;
        ?>
        <form method="POST" action="<?= stripurl('') ?>" onsubmit="saveConfigPrefs(this); return false">
            <input type="hidden" name="saving_config" value="<?= $this->area_name ?>"/>
            <input type="hidden" name="tile_idx" value="<?= $this->prefs_idx ?>"/>
            <table border="0" width="100%" height="100%">
                <tr>
                    <th>Timeframe:</th>
                    <td>
                        <select name="timeframe">
                            <option value="day">Day</option>
                            <option value="week"
                                    <?= ($_SESSION['home']->prefs['tiles'][$this->prefs_idx]['timeframe'] == 'week') ? " SELECTED " : "" ?>>
                                Week
                            </option>
                            <option value="month"
                                    <?= ($_SESSION['home']->prefs['tiles'][$this->prefs_idx]['timeframe'] == 'month') ? " SELECTED " : "" ?>>
                                Month
                            </option>
                            <option value="year"
                                    <?= ($_SESSION['home']->prefs['tiles'][$this->prefs_idx]['timeframe'] == 'year') ? " SELECTED " : "" ?>>
                                Year
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        <input type="submit" value="Save"/>
                    </td>
                </tr>
            </table>
        </form>
        <?
    }

	function renderTile($tidx, $tile)
    {
        ?>
        <script>
            function editConfig() {
                var objname = 'dialog-modal-edit_config';
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                $('#' + objname).load("index.php?area=home&sub_section=<?=$this->area_name?>&edit_config=<?=$this->prefs_idx?>&printable=1&no_script=1");
                $('#' + objname).dialog('option', 'position', 'center');
            }

            function saveConfigPrefs(frm) {
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
                        } else {
                            $('#dialog-modal-edit_config').dialog("close");
                            loadSection("?area=home");
                            alert(result['message']);
                        }
                    }
                });
            }
        </script>
        <div id="dialog-modal-edit_config" title="Editing Configuration" class="nod"></div>
        <li id="tile_<?= $tidx ?>" class="user-count-tile">
            <div class="block block-rounded block-bordered">
                <div class="block-header bg-primary text-left">
                    <h4 class="block-title">Users Online</h4>
                    <div class="block-options">
                        <button type="button" class="btn-block-option btn-sm" onclick="editConfig();return false">
                            <i class="fa fa-tools" title="Configure Chart"></i>
                        </button>
                        <button type="button" class="btn-block-option btn-sm"
                                onclick="deleteHomeTile(<?= $tidx ?>);return false">
                            <i class="fa fa-minus-circle" title="Delete Tile"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content user-count-content">
                    <img src="graph.php?area=user_charts&max_mode=1&time_frame=<?= ($this->prefs['timeframe']) ? $this->prefs['timeframe'] : 'day' ?>&width=<?= ($this->tile_width - 6) ?>&height=<?= ($this->tile_height - 30) ?>"
                         border="0" height="<?= ($this->tile_height - 30) ?>" width="<?= ($this->tile_width - 6) ?>">
                </div>
            </div>
        </li>
        <script>
            $("#dialog-modal-edit_config").dialog({
                autoOpen: false,
                width: 500,
                height: 200,
                modal: false,
                draggable: true,
                resizable: true
            });

            $("#dialog-modal-edit_config").dialog("widget").draggable("option","containment","#main-container");
        </script>
        <?
    }

	function savePreferences(){

		$_SESSION['home']->savePreferences();

	}




}
