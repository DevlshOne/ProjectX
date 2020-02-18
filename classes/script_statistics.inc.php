<? /***************************************************************
 *    Script Statistics - A tool to view how many times a script has been played, and reset the counters
 *    Written By: Jonathan Will
 ***************************************************************/

$_SESSION['script_statistics'] = new ScriptStatistics;


class ScriptStatistics
{


    var $table = 'scripts';            ## Classes main table to operate on
    var $orderby = 'hit_counter';        ## Default Order field
    var $orderdir = 'DESC';    ## Default order direction


    ## Page  Configuration
    var $frm_name = 'ssnextfrm';
    var $index_name = 'ss_list';
    var $order_prepend = 'ss_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

    ## Page  Configuration
    var $pagesize = 20;    ## Adjusts how many items will appear on each page
    var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages


    function LeadManagement()
    {


        ## REQURES DB CONNECTION!
        include_once($_SESSION['site_config']['basedir'] . "/utils/db_utils.php");


        $this->handlePOST();
    }


    function handlePOST()
    {

        // THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
        // SEE api/lead_management.api.php FOR POST HANDLING!
        // <3 <3 -Jon

    }

    function handleFLOW()
    {
        # Handle flow, based on query string

        if (!checkAccess('script_statistics')) {


            accessDenied("Script statistics");

            return;

        } else {
            if (isset($_REQUEST['view_script'])) {

                $this->makeView(intval($_REQUEST['view_script']));

            } else {
                $this->listEntrys();
            }
        }

    }


    function listEntrys()
    {


        ?>
        <script>
            var ss_confmsg = 'Are you sure you want to reset this scripts counter?';
            var ss_delmsg = 'Are you sure you want to delete this record?';

            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";


            var <?=$this->index_name?> =
            0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;


            var ScriptsTableFormat = [
                ['id', 'text-left'],
                ['name', 'text-left'],
                ['keys', 'text-center'],
                ['[get:campaign_name:campaign_id]', 'text-left'],
                ['[get:voice_name:voice_id]', 'text-left'],
                ['[render:number:hit_counter]', 'text-center'],
                ['[time:hit_last_reset]', 'text-left'],
                ['[button:Reset Counter:resetScriptCounter:id:ss_confmsg]', 'text-left'],
            ];


            /**
             * Reset script counter, a special function of parseXML in js/ajax_functions.js
             * Example TableFormat string:
             * ** Button Mode:Name of button:Function to call:Argument to pass to the function:Confirmation message
             * ['[button:Reset Counter:resetScriptCounter:id:ss_confmsg]','align_center'],
             */
            function resetScriptCounter(id) {

                // AJAX POST TO RESET COUNTER
                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=scriptstats&mode=xml&action=reset_script&script_id=' + id,
                    error: function () {
                        alert("Error reseting script stats. Please contact an admin.");
                    },
                    success: function (msg) {

                        var result = handleEditXML(msg);
                        var res = result['result'];

                        if (res <= 0) {

                            alert(result['message']);

                            return;

                        }

                        // REFRESH PAGE
                        loadScripts();

                    }


                });


            }

            function resetAllScripts() {

                // AJAX POST TO RESET COUNTER
                $.ajax({
                    type: "POST",
                    cache: false,
                    url: 'api/api.php?get=scriptstats&mode=xml&action=reset_all_scripts',
                    error: function () {
                        alert("Error saving lead form. Please contact an admin.");
                    },
                    success: function (msg) {

                        var result = handleEditXML(msg);
                        var res = result['result'];

                        // REFRESH PAGE
                        loadScripts();

                    }


                });

            }


            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getScriptsURL() {

                var frm = getEl('<?=$this->frm_name?>');

                return 'api/api.php' +
                    "?get=scripts&" +
                    "mode=xml&" +


                    's_campaign_id=' + escape(frm.s_campaign_id.value) + "&" +
                    's_voice_id=' + escape(frm.s_voice_id.value) + "&" +

                    's_name=' + escape(frm.s_name.value) + "&" +
                    's_key=' + escape(frm.s_key.value) + "&" +


                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }


            var scripts_loading_flag = false;
            var page_load_start;

            /**
             * Load the name data - make the ajax call, callback to the parse function
             */
            function loadScripts() {

                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = scripts_loading_flag');


                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {

                    //console.log("scripts ALREADY LOADING (BYPASSED) \n");
                    return;
                } else {

                    eval('scripts_loading_flag = true');
                }

                page_load_start = new Date();


                $('#total_count_div').html('<img src="images/ajax-loader.gif" height="20" border="0">');


                loadAjaxData(getScriptsURL(), 'parseScripts');

            }


            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;

            function parseScripts(xmldoc) {

                <?=$this->order_prepend?>totalcount = parseXMLData('script', ScriptsTableFormat, xmldoc);


                var enddate = new Date();

                var loadtime = enddate - page_load_start;

                $('#page_load_time').html("Load and render time: " + loadtime + "ms");


                // ACTIVATE PAGE SYSTEM!
                if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {


                    makePageSystem('scripts',
                        '<?=$this->index_name?>',
                        <?=$this->order_prepend?>totalcount,
                        <?=$this->index_name?>,
                        <?=$this->order_prepend?>pagesize,
                        'loadScripts()'
                    );

                } else {

                    hidePageSystem('scripts');

                }


                eval('scripts_loading_flag = false');
            }


            function handleScriptListClick(id) {

                displayEditScriptDialog(id);

            }

            function displayEditScriptDialog(id, sub) {
                var objname = 'dialog-modal-edit_script';
                if (id > 0) {
                    $('#' + objname).dialog("option", "title", 'Viewing Script #' + id);
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new Script');
                }
                /*******
                 $('#'+objname).dialog("open");

                 $('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');


                 if(sub){

					$('#'+objname).load("index.php?area=scripts&view_script="+id+"&sub="+sub+"&printable=1&no_script=1");
				}else{

					$('#'+objname).load("index.php?area=scripts&view_script="+id+"&printable=1&no_script=1");
				}
                 *****************/
            }

            function resetScriptForm(frm) {
                frm.reset();
                frm.s_campaign_id.selectedIndex = 0;
                frm.s_voice_id.value = '';
                //frm.s_.value = '';
                frm.s_name.value = '';
                frm.s_key.value = '';
                loadScripts();
            }

            var scriptsrchtog = true;
            function toggleScriptSearch() {
                scriptsrchtog = !scriptsrchtog;
                ieDisplay('script_search_table', scriptsrchtog);
            }
        </script>
        <div id="dialog-modal-edit_script" title="Viewing Script"></div>
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadScripts();return false">
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Script Statistics</h4>
                    <button type="button" title="Reset All Counters" class="btn btn-sm btn-danger" onclick="if(confirm('Are you sure you want to reset all script counters?')){resetAllScripts();}">Reset All Counters</button>
                    <button type="button" value="Search" title="Toggle Search" class="btn btn-sm btn-primary" onclick="toggleScriptSearch();">Toggle Search</button>
                    <div id="scripts_prev_td" class="page_system_prev"></div>
                    <div id="scripts_page_td" class="page_system_page"></div>
                    <div id="scripts_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?= $this->order_prepend ?>pagesize" id="<?= $this->order_prepend ?>pagesizeDD" onchange="<?= $this->index_name ?>=0;loadScripts(); return false;">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                    <div class="d-inline-block ml-2">
                        <button class="btn btn-sm btn-dark" title="Total Found">
                            <i class="si si-list"></i>
                            <span class="badge badge-light badge-pill"><div id="total_count_div"></div></span>
                        </button>
                    </div>
                </div>
                <div class="bg-info-light" id="script_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_script"/>
                        <?= makeCampaignIDDD('s_campaign_id', $_REQUEST['s_campaign_id'], 'form-control custom-select-sm', "loadScripts()", "[Select Campaign]"); ?>
                        <?= makeVoiceDD(0, 's_voice_id', $_REQUEST['s_voice_id'], 'form-control custom-select-sm', "[Select Voice]") ?>
                        <input type="text" class="form-control" placeholder="Name.." name="s_name" size="7" value="<?= htmlentities($_REQUEST['s_name']) ?>">
                        <input type="text" class="form-control" placeholder="Keys.." name="s_key" size="5" value="<?= htmlentities($_REQUEST['s_key']) ?>">
                        <button type="submit" value="Search" title="Search Names" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadScripts();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetScriptForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadScripts();return false;">Reset</button>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-sm table-striped" id="script_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                        <tr>
                            <th class="row2 text-left"><?= $this->getOrderLink('id') ?>ID</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('name') ?>Name</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('keys') ?>Keys</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('campaign_id') ?>Campaign</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('voice_id') ?>Voice</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('hit_counter') ?>Times played</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('hit_last_reset') ?>Last Reset Time</a></th>
                            <th class="row2 text-center">&nbsp;</th>
                        </tr>
                    </table>
                </div>
            </form>
        </div>
        <script>
            $(function () {
                $("#dialog-modal-edit_script").dialog({
                    autoOpen: false,
                    width: 780,
                    height: 420,
                    modal: false,
                    draggable: true,
                    resizable: false,
                    position: {my: 'center', at: 'center'},
                    containment: '#main-container'
                });
                $("#dialog-modal-edit_script").dialog("widget").draggable("option", "containment", "#main-container");
            });
            loadScripts();
        </script>
        <?

    }


    function getOrderLink($field)
    {
        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
        $var .= ");loadScripts();return false;\">";
        return $var;
    }
}
