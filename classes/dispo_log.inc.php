<? /***************************************************************
 *    Dispo Log - Displays list/search for all dispos agents send to the system.
 *    Written By: Jonathan Will
 ***************************************************************/

$_SESSION['dispo_log'] = new DispoLog;


class DispoLog
{


    var $table = 'dispo_log';            ## Classes main table to operate on
    var $orderby = 'id';        ## Default Order field
    var $orderdir = 'DESC';    ## Default order direction


    ## Page  Configuration
    var $frm_name = 'disponextfrm';
    var $index_name = 'dispo_list';
    var $order_prepend = 'dispo_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING


    ## Page  Configuration
    var $pagesize = 20;    ## Adjusts how many items will appear on each page
    var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages


    function DispoLog()
    {


        ## REQURES DB CONNECTION!
        include_once($_SESSION['site_config']['basedir'] . "/utils/db_utils.php");

        ## NEEDED FOR THE DISPO DROPDOWN FUNCTION
        include_once($_SESSION['site_config']['basedir'] . "/classes/lead_management.inc.php");

        $this->handlePOST();
    }


    function handlePOST()
    {

        // THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
        // SEE api/ringing_calls.api.php FOR POST HANDLING!
        // <3 <3 -Jon

    }

    function handleFLOW()
    {
        # Handle flow, based on query string


//		if(isset($_REQUEST['add_name'])){
//
//			$this->makeV($_REQUEST['add_name']);
//
//		}else{


        if (!checkAccess('dispo_log')) {


            accessDenied("Dispo Log");

            return;

        } else {

            if (($id = intval($_REQUEST['view_dispo'])) > 0) {

                $this->makeViewDispo($id);

            } else {


                $this->listEntrys();
            }


        }


//		}

    }


    function listEntrys()
    {


        ?>
        <script>

            var dispo_delmsg = 'Are you sure you want to delete this dispo record?';

            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";


            var <?=$this->index_name?> =
            0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

            var DisposTableFormat = [
                ['[microtime:micro_time]', 'align_center'],
                ['agent_username', 'align_left'],
                ['lead_tracking_id', 'align_center'],
                ['vici_lead_id', 'align_center'],
                ['dispo', 'align_center'],
                ['result', 'align_center'],
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getDisposURL() {
                var frm = getEl('<?=$this->frm_name?>');
                return 'api/api.php' +
                    "?get=dispo_log&" +
                    "mode=xml&" +
                    's_lead_id=' + escape(frm.s_lead_id.value) + "&" +
                    's_lead_tracking_id=' + escape(frm.s_lead_tracking_id.value) + "&" +
                    's_username=' + escape(frm.s_username.value) + "&" +
                    's_dispo=' + escape(frm.s_dispo.value) + "&" +
                    //'s_date='+escape(frm.s_date.value)+"&"+
                    's_result=' + escape(frm.s_result.value) + "&" +
                    's_date_month=' + escape(frm.s_date_month.value) + "&" + 's_date_day=' + escape(frm.s_date_day.value) + "&" + 's_date_year=' + escape(frm.s_date_year.value) + "&" +
                    's_date2_month=' + escape(frm.s_date2_month.value) + "&" + 's_date2_day=' + escape(frm.s_date2_day.value) + "&" + 's_date2_year=' + escape(frm.s_date2_year.value) + "&" +
                    's_date_mode=' + escape(frm.s_date_mode.value) + "&" +
                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }


            var dispos_loading_flag = false;
            var page_load_start;

            /**
             * Load the name data - make the ajax call, callback to the parse function
             */
            function loadDispos() {

                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = dispos_loading_flag');


                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {

                    //console.log("ALREADY LOADING (BYPASSED) \n");
                    return;
                } else {

                    eval('dispos_loading_flag = true');
                }

                page_load_start = new Date();


                $('#total_count_div').html('<img src="images/ajax-loader.gif" height="20" border="0">');


                loadAjaxData(getDisposURL(), 'parseDispos');

            }


            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;

            function parseDispos(xmldoc) {

                <?=$this->order_prepend?>totalcount = parseXMLData('dispo', DisposTableFormat, xmldoc);


                var enddate = new Date();

                var loadtime = enddate - page_load_start;

                $('#page_load_time').html("Load and render time: " + loadtime + "ms");


                // ACTIVATE PAGE SYSTEM!
                if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {


                    makePageSystem('dispos',
                        '<?=$this->index_name?>',
                        <?=$this->order_prepend?>totalcount,
                        <?=$this->index_name?>,
                        <?=$this->order_prepend?>pagesize,
                        'loadDispos()'
                    );

                } else {

                    hidePageSystem('dispos');

                }


                eval('dispos_loading_flag = false');
            }


            function handleDispoListClick(id) {
                var objname = 'view_dispo_log_dialog';
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                $('#' + objname).load("index.php?area=dispo_log&view_dispo=" + id + "&printable=1&no_script=1");
            }
            function resetDispoForm(frm) {
                frm.reset();
                frm.s_lead_id.value = '';
                frm.s_lead_tracking_id.value = '';
                frm.s_dispo.value = '';
                frm.s_result.value = '';
                frm.s_username.value = '';
                toggleDateMode(frm.s_date_mode.value);
            }
            var disposrchtog = true;
            function toggleDispoSearch() {
                disposrchtog = !disposrchtog;
                ieDisplay('dispo_search_table', disposrchtog);
            }
            function toggleDateMode(way) {
                if (way == 'daterange') {
                    // SHOW EXTRA DATE FIELD
                    $('#date2_span').show();
                } else {
                    // HIDE IT
                    $('#date2_span').hide();
                }
            }
        </script>
        <div id="view_dispo_log_dialog" title="View Dispo"></div>
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadDispos();return false;">
                <! ** BEGIN BLOCK HEADER -->
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Disposition Logs</h4>
                    <button type="button" value="Search" title="Toggle Search" class="btn btn-sm btn-primary" onclick="toggleDispoSearch();">Toggle Search</button>
                    <div id="dispos_prev_td" class="page_system_prev"></div>
                    <div id="dispos_page_td" class="page_system_page"></div>
                    <div id="dispos_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?=$this->order_prepend?>pagesize" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0;loadDispos(); return false;">
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
                <! ** END BLOCK HEADER -->
                <! ** BEGIN BLOCK SEARCH TABLE -->
                <div class="bg-info-light" id="dispo_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_dispo"/>
                        <input type="text" class="form-control" placeholder="Agent.." name="s_username" size="10" value="<?= htmlentities($_REQUEST['s_username']) ?>">
                        <input type="text" class="form-control" placeholder="Lead ID.." name="s_lead_id" size="5" value="<?= htmlentities($_REQUEST['s_lead_id']) ?>">
                        <input type="text" class="form-control" placeholder="Lead Tracking ID.." name="s_lead_tracking_id" size="5" value="<?= htmlentities($_REQUEST['s_lead_tracking_id']) ?>">
                        <?=$_SESSION['lead_management']->makeDispoDD('s_dispo', $_REQUEST['s_dispo'], "", "[Select Dispo]", null);?>
                        <select class="form-control custom-select-sm" name="s_result">
                            <option value="">[Select Result]</option>
                            <option value="success"<?= ($_REQUEST['s_result'] == 'success') ? ' SELECTED ' : '' ?>>Success</option>
                            <option value="failed"<?= ($_REQUEST['s_result'] == 'success') ? ' SELECTED ' : '' ?>>Failed</option>
                        </select>
                        <button type="submit" value="Search" title="Search Names" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadDispos();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetDispoForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadDispos();return false;">Reset</button>
                    </div>
                    <div class="input-group input-group-sm">
                        <select class="custom-select-sm" name="s_date_mode" onchange="toggleDateMode(this.value);loadDispos();">
                            <option value="date">Date Mode</option>
                            <option value="daterange"<?= ($_REQUEST['s_date_mode'] == 'daterange') ? ' SELECTED ' : '' ?>>Date Range Mode</option>
                        </select>
                        <?=makeTimebar("s_date_", 1, null, false, time(), " onchange=\"loadDispos()\" ");?>
                        <span id="date2_span" class="nod">&nbsp;-&nbsp;
                            <?=makeTimebar("s_date2_", 1, null, false, time(), " onchange=\"loadDispos()\" ");?>
                        </span>
                    </div>
                </div>
                <! ** END BLOCK SEARCH TABLE -->
                <! ** BEGIN BLOCK LIST (DATATABLE) -->
                <div class="block-content">
                    <table class="table table-sm table-striped" id="dispo_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?=date("g:ia m/d/Y T")?></caption>
                        <tr>
                            <th class="row2 text-center"><?= $this->getOrderLink('micro_time') ?>Time</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('agent_username') ?>Agent</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('lead_tracking_id') ?>PX ID</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('vici_lead_id') ?>Lead ID</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('dispo') ?>Dispo</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('result') ?>Result</a></th>
                        </tr>
                    </table>
                </div>
                <! ** END BLOCK LIST (DATATABLE) -->
            </form>
        </div>
        <script>
            $(function () {
                $("#view_dispo_log_dialog").dialog({
                    autoOpen: false,
                    width: 'auto',
                    height: 'auto',
                    modal: false,
                    draggable: true,
                    resizable: false,
                    position: {my: 'center', at: 'center'},
                    containment: '#main-container'
                });
                $("#view_dispo_log").closest('.ui-dialog').draggable("option", "containment", "#main-container");
            });
            loadDispos();
        </script>
        <?
    }


    function makeViewDispo($id)
    {

        $row = $_SESSION['dbapi']->dispo_log->getByID($id);

        ?>
        <table border="0" width="100%">
        <tr>
            <th>PX ID:</th>
            <td><?= htmlentities($row['lead_tracking_id']) ?></td>
        </tr>
        <tr>
            <th>VICI Lead ID:</th>
            <td><?= htmlentities($row['vici_lead_id']) ?></td>
        </tr>
        <tr>
            <th>Log:</th>
            <td><?= htmlentities($row['log']) ?></td>
        </tr>

        </table><?

    }

    function getOrderLink($field)
    {

        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';

        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";

        $var .= ");loadDispos();return false;\">";

        return $var;
    }
}
