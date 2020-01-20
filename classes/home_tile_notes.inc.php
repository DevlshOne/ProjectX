<? /***************************************************************
 *    TILE NOTES - A person note tracking system in small/TILE format, for home screen integration
 *    Written By: Jonathan Will
 ***************************************************************/

$_SESSION['home_tile_notes'] = new HomeTileNotesClass;


class HomeTileNotesClass
{

    public $table = "notes";
    public $orderby = 'time';        ## Default Order field
    public $orderdir = 'DESC';    ## Default order direction


    ## Page  Configuration
    public $pagesize = 20;    ## Adjusts how many items will appear on each page
    public $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

    public $index_name = 'note_list';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    public $frm_name = 'notenextfrm';

    public $order_prepend = 'note_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING


    // READ ONLY PREFS VARIABLE (modify the one on the $_SESSION['home'] object)
    public $prefs = null;
    public $prefs_idx = -1;


    public $tile_width = 0; // 0 = USE DEFAULT FOR HOME SECTION. OTHERWISE = WIDTH OVERRIDE OF DEFAULT VALUE
    public $tile_height = 0;// 0 = USE DEFAULT FOR HOME SECTION. OTHERWISE = HEIGHT OVERRIDE OF DEFAULT VALUE

    function HomeTileNotesClass()
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

    function handleFLOW($tile_idx, $tile_prefs)
    {


        $this->prefs = $tile_prefs;
        $this->prefs_idx = $tile_idx;


        $this->renderTile($tile_idx, $tile_prefs);

    }

    function makeAdd($note_id)
    {

        $note_id = intval($note_id);


        if ($note_id > 0) {

            $row = $_SESSION['dbapi']->ROquerySQL("SELECT * FROM `notes`  WHERE deleted='no' AND user_id='" . $_SESSION['user']['id'] . "' AND id='" . $note_id . "'");
        }


        ?>
    <form method="POST" action="<?= stripurl('') ?>" onsubmit="saveNoteRecord(this); return false">
        <input type="hidden" name="saving_note"/>
        <input type="hidden" name="note_id" value="<?= intval($row['id']) ?>"/>

        <table border="0" width="100%" height="100%">
            <tr>
                <td height="*"><textarea name="note_text" rows="8" cols="50"
                                         style="width:100%;height:100%"><?= htmlentities($row['notes']) ?></textarea>
                </td>
            </tr>
            <tr>
                <td align="center">
                    <input type="submit" value="Save Note"/>
                </td>
            </tr>
        </table>
        </form><?
    }

    function renderTile($tidx, $tile)
    {
        ?>
        <script>
            function viewNotesRecord(id) {
                var objname = 'dialog-modal-view_notes';
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                $('#' + objname).load("index.php?area=home&sub_section=my_notes&edit_note=" + id + "&printable=1&no_script=1");
                $('#' + objname).dialog('option', 'position', 'center');
            }

            function saveNoteRecord(frm) {
                var params = getFormValues(frm);
                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=my_notes&mode=xml&action=edit',
                    data: params,
                    error: function () {
                        alert("Error saving form. Please contact an admin.");
                    },
                    success: function (msg) {
                        var result = handleEditXML(msg);
                        var res = result['result'];
                        if (res <= 0) {
                            alert(result['message']);
                            return;
                        } else {
                            loadNotes();
                            $('#dialog-modal-view_notes').dialog("close");
                        }
                    }
                });
            }

            var note_delmsg = 'Are you sure you want to delete this note?';
            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
            var <?=$this->index_name?> = 0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
            var NotesTableFormat = [
                ['[maxlen:notes:30]', 'align_left priorityRender'],
                ['[time:time]', 'align_center'],
                ['[delete]', 'align_center']
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getNotesURL() {
                var frm = getEl('<?=$this->frm_name?>');
                return 'api/api.php' +
                    "?get=notes&" +
                    "mode=xml&" +
                    "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }
            var notes_loading_flag = false;
            /**
             * Load the name data - make the ajax call, callback to the parse function
             */
            function loadNotes() {
                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = notes_loading_flag');
                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {
                    //console.log("NAMES ALREADY LOADING (BYPASSED) \n");
                    return;
                } else {

                    eval('notes_loading_flag = true');
                }
                <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());
                loadAjaxData(getNotesURL(), 'parseNotes');
            }

            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;

            function parseNotes(xmldoc) {

                <?=$this->order_prepend?>totalcount = parseXMLData('note', NotesTableFormat, xmldoc);


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

            function handleNoteListClick(id) {
                viewNotesRecord(id);
            }
        </script>
        <div id="dialog-modal-view_notes" title="Editing Note" class="nod"></div>
        <li id="tile_<?= $tidx ?>" class="col-sm-6 col-md-3">
            <div class="block block-rounded block-bordered">
                <div class="block-header bg-primary text-left">
                    <h4 class="block-title">Notes</h4>
                    <div class="block-options">
                        <button type="button" class="btn-block-option btn-sm">
                            <i class="fa fa-plus-circle" title="New Note"
                               onclick="viewNotesRecord(0);return false;"></i>
                        </button>
                        <button type="button" class="btn-block-option btn-sm">
                            <i class="fa fa-tools" title="Configure Notes"></i>
                        </button>
                        <button type="button" class="btn-block-option btn-sm"
                                onclick="deleteHomeTile(<?= $tidx ?>);return false">
                            <i class="fa fa-minus-circle" title="Delete Tile"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content">
                    <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST"
                          action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadNotes();return false">
                        <input type="hidden" name="searching_note">
                        <table class="tightTable table table-sm table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th class="row2 text-left pct66"><?= $this->getOrderLink('notes') ?>Note</a></th>
                                <th class="row2 text-center pct20"><?= $this->getOrderLink('time') ?>Updated</a></th>
                                <th class="row2 text-center">&nbsp</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>
                                    <table class="table table-sm" id="note_table">
                                        <tr>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </li>
        <script>
            $("#dialog-modal-view_notes").dialog({
                autoOpen: false,
                width: 500,
                height: 200,
                modal: false,
                draggable: true,
                resizable: true
            });
            loadNotes();
        </script>
        <?
    }


    function getOrderLink($field)
    {

        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';

        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";

        $var .= ");loadNotes();return false;\">";

        return $var;
    }

    function savePreferences()
    {

        $_SESSION['home']->savePreferences();

    }


}
