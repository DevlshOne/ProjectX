<? /***************************************************************
 *    Login Tracker - Handles list/search logins
 ***************************************************************/

$_SESSION['login_tracker'] = new LoginTracker;


class LoginTracker
{

    var $table = 'logins';                    ## Class main table to operate on
    var $orderby = 'time';                    ## Default Order field
    var $orderdir = 'DESC';                    ## Default order direction


    ## Page  Configuration
    var $pagesize = 20;                        ## Adjusts how many items will appear on each page
    var $index = 0;                        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

    var $index_name = 'login_tracker_list';        ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    var $frm_name = 'login_trackernextfrm';

    var $order_prepend = 'login_tracker_';        ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

    function LoginTracker()
    {

        $this->handlePOST();

    }


    ## NOT USED CURRENTLY
    function handlePOST()
    {


    }

    ## HANDLE FLOW BASED ON QUERY STRINGS
    function handleFLOW()
    {

        ## CHECK FOR FEATURE ACCESS
        if (!checkAccess('login_tracker')) {


            accessDenied("LoginTracker");

            return;

        } else {

            ## DISPLAY MAKE VIEW LOGIN DIALOG OR LIST ENTRIES BASED ON QUERY STRING
            if (isset($_REQUEST['view_login'])) {

                $this->makeView($_REQUEST['view_login']);

            } else {

                $this->listEntrys();

            }

        }

    }

    ## LIST ENTRIES FROM DATABASE
    function listEntrys()
    {


        ?>
        <script>

            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";


            var <?=$this->index_name?> =
            0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;


            var LoginsTableFormat = [
                ['id', 'text-left'],
                ['[time:time]', 'text-left'],
                ['[get:time_logged_out:time_out]', 'text-left'],
                ['username', 'text-left'],
                ['result', 'text-left'],
                ['section', 'text-left'],
                ['ip', 'text-left']
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getLoginsURL() {

                var frm = getEl('<?=$this->frm_name?>');

                return 'api/api.php' +
                    "?get=login_tracker&" +
                    "mode=xml&" +

                    's_id=' + escape(frm.s_id.value) + "&" +
                    's_username=' + escape(frm.s_username.value) + "&" +
                    's_result=' + escape(frm.s_result.value) + "&" +
                    's_section=' + escape(frm.s_section.value) + "&" +
                    's_ip=' + escape(frm.s_ip.value) + "&" +
                    's_browser=' + escape(frm.s_browser.value) + "&" +

                    's_date_month=' + escape(frm.s_date_month.value) + "&" + 's_date_day=' + escape(frm.s_date_day.value) + "&" + 's_date_year=' + escape(frm.s_date_year.value) + "&" +
                    's_date2_month=' + escape(frm.s_date2_month.value) + "&" + 's_date2_day=' + escape(frm.s_date2_day.value) + "&" + 's_date2_year=' + escape(frm.s_date2_year.value) + "&" +

                    's_date_mode=' + escape(frm.s_date_mode.value) + "&" +

                    'data_aggr_search=' + escape(frm.data_aggr_search.value) + "&" +
                    'data_aggr_range=' + escape(frm.data_aggr_range.value) + "&" +

                    "index=" + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +"&pagesize=" + <?=$this->order_prepend?>pagesize + "&" +
                "orderby=" + <?=$this->order_prepend?>orderby + "&orderdir=" + <?=$this->order_prepend?>orderdir;
            }


            var logintracker_loading_flag = false;

            /**
             * Load the login data - make the ajax call, callback to the parse function
             */
            function loadLogins() {

                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = logintracker_loading_flag');


                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {

                    return;

                } else {

                    eval('logintracker_loading_flag = true');
                }

                <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

                $('#total_count_div').html('<img src="images/ajax-loader.gif" height="20" border="0">');

                loadAjaxData(getLoginsURL(), 'parseLogins');

            }


            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;

            function parseLogins(xmldoc) {

                <?=$this->order_prepend?>totalcount = parseXMLData('login', LoginsTableFormat, xmldoc);


                // ACTIVATE PAGE SYSTEM!
                if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {


                    makePageSystem('logins',
                        '<?=$this->index_name?>',
                        <?=$this->order_prepend?>totalcount,
                        <?=$this->index_name?>,
                        <?=$this->order_prepend?>pagesize,
                        'loadLogins()'
                    );

                } else {

                    hidePageSystem('logins');

                }

                eval('logintracker_loading_flag = false');
            }


            function handleLoginListClick(id) {

                displayViewLoginDialog(id);

            }


            function displayViewLoginDialog(id) {

                var objname = 'dialog-modal-view-login';


                if (id > 0) {
                    $('#' + objname).dialog("option", "title", 'Viewing login');
                }


                $('#' + objname).dialog("open");

                $('#' + objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

                $('#' + objname).load("index.php?area=login_tracker&view_login=" + id + "&printable=1&no_script=1");

                $('#' + objname).dialog('option', 'position', 'center');

                $('#' + objname).dialog('option', 'height', '350');
            }

            function resetLoginForm(frm) {

                // SET FORM VALUES TO BLANK
                frm.s_id.value = '';
                frm.s_username.value = '';
                frm.s_result.value = '';
                frm.s_section.value = '';
                frm.s_ip.value = '';
                frm.s_browser.value = '';

                // SET DATE RANGE SEARCH MODE TO DATE ONLY
                frm.s_date_mode.value = 'date';

                // CLEAR CUSTOM SEARCH FIELDS
                frm.data_aggr_search.value = 'false';
                frm.data_aggr_range.value = 'false';

                // GET CURRENT DATE AND GRAB DAY + MONTH
                var d = new Date();
                var n = d.getDate();
                var m = d.getMonth();

                // SET DATE SEARCH INPUTS TO CURRENT DAY + MONTH
                frm.s_date_day.value = n;
                frm.s_date_month.value = m + 1;

                // RESET DATE RANGE FIELDS
                $('#nodate_span').hide();
                $('#s_date_mode').show();
                $('#date1_span').show();
                $('#date2_span').hide();

            }

            function toggleDateMode(way) {

                if (way == 'daterange') {
                    $('#nodate_span').hide();
                    $('#date1_span').show();

                    // SHOW EXTRA DATE FIELD
                    $('#date2_span').show();

                } else if (way == 'hide') {

                    // HIDE ALL INPUTS AND DISPLAY CUSTOM SPAN
                    $('#nodate_span').show();
                    $('#s_date_mode').hide();
                    $('#date1_span').hide();
                    $('#date2_span').hide();

                } else {

                    $('#nodate_span').hide();
                    $('#date1_span').show();

                    // HIDE IT
                    $('#date2_span').hide();
                }

            }


        </script>
        <div id="dialog-modal-view-login" title="Viewing Login" class="nod"></div>
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadLogins();return false">
                <! ** BEGIN BLOCK HEADER -->
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Login Tracker</h4>
                    <div id="logins_prev_td" class="page_system_prev"></div>
                    <div id="logins_page_td" class="page_system_page"></div>
                    <div id="logins_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?= $this->order_prepend ?>pagesize" id="<?= $this->order_prepend ?>pagesizeDD" onchange="<?= $this->index_name ?>=0;loadLogins(); return false;">
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
                <div class="bg-info-light" id="login_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_login">
                        <input type="hidden" name="data_aggr_search" value="false" id="data_aggr_search">
                        <input type="hidden" name="data_aggr_range" value="false" id="data_aggr_range">
                        <input type="text" class="form-control" placeholder="ID.." name="s_id" value="<?= htmlentities($_REQUEST['s_id']) ?>"/>
                        <input type="text" class="form-control" placeholder="Username.." name="s_username" value="<?= htmlentities($_REQUEST['s_username']) ?>"/>
                        <select class="form-control custom-select-sm" name="s_result" id="s_result">
                            <option value="">[Select Result]</option>
                            <option value="success">success</option>
                            <option value="failure">failure</option>
                            <option value="success-code">success-code</option>
                            <option value="failure-code">failure-code</option>
                            <option value="success-api">success-api</option>
                            <option value="failure-api">failure-api</option>
                        </select>
                        <select class="form-control custom-select-sm" name="s_section" id="s_section">
                            <option value="">[Select Section]</option>
                            <option value="admin">admin</option>
                            <option value="client">client</option>
                            <option value="liveclient">liveclient</option>
                            <option value="quiz">quiz</option>
                            <option value="rouster">rouster</option>
                            <option value="roustersys">roustersys</option>
                            <option value="verifier">verifier</option>
                            <option value="API">API</option>
                        </select>
                        <input type="text" class="form-control" placeholder="IP Address.." name="s_ip" value="<?= htmlentities($_REQUEST['s_ip']) ?>"/>
                        <input type="text" class="form-control" placeholder="Browser.." name="s_browser" size="15" value="<?= htmlentities($_REQUEST['s_browser']) ?>">
                    </div>
                    <div class="input-group input-group-sm">
                        <select class="form-control custom-select-sm" name="s_date_mode" onchange="toggleDateMode(this.value);loadLogins();" id="s_date_mode">
                            <option value="date">Date Mode</option>
                            <option value="daterange"<?= ($_REQUEST['s_date_mode'] == 'daterange') ? ' SELECTED ' : '' ?>>Date Range Mode</option>
                        </select>
                        <span id="date1_span">
                                <?= makeTimebar("s_date_", 1, null, false, time(), " onchange=\"" . $this->index_name . " = 0;loadLogins()\" "); ?>
                            </span>
                        <span id="date2_span" class="nod">
                                &nbsp;-&nbsp;
                                <?= makeTimebar("s_date2_", 1, null, false, time(), " onchange=\"" . $this->index_name . " = 0;loadLogins()\" "); ?>
                            </span>
                        <span id="nodate_span" class="nod">CUSTOM RANGE</span>
                        <button type="submit" value="Search" title="Search Names" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadLogins();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetLoginForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadLogins();return false;">Reset</button>
                    </div>
                </div>
                <! ** END BLOCK SEARCH TABLE -->
                <! ** BEGIN BLOCK LIST (DATATABLE) -->
                <div class="block-content">
                    <table class="table table-sm table-striped" id="login_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                        <tr>
                            <th class="row2 text-left"><?= $this->getOrderLink('id') ?>ID</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('time') ?>Time</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('time_out') ?>Logout Time</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('username') ?>Username</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('result') ?>Result</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('section') ?>Section</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('ip') ?>IP</a></th>
                        </tr>
                    </table>
                </div>
                <! ** END BLOCK LIST (DATATABLE) -->
            </form>
        </div>
        <script>
            $("#dialog-modal-view-login").dialog({
                autoOpen: false,
                width: 500,
                height: 200,
                modal: false,
                draggable: true,
                resizable: false,
                position: {my: 'center', at: 'center'},
                containment: '#main-container'
            });
            $("#dialog-modal-view-login").closest('.ui-dialog').draggable("option","containment","#main-container");
            loadLogins();
        </script>
        <br><br>
        <table width="100%">
            <tr>
                <td></td>

                <?

                ## GRAB DISTINCT SECTIONS AND BUILD DATA AGGREGATION TABLE
                $sections = $_SESSION['dbapi']->login_tracker->getLoginSections();

                ## BUILD HEADERS
                foreach ($sections as $value) {

                    ?>
                    <td align="center"><?= ucfirst($value['0']) ?></td><?

                }

                ?>

            </tr>
            <tr>
                <td align="right" height="35">1 hour</td>
                <?

                ## BUILD SECTIONS AND RESULTS FOR 1HOUR
                foreach ($sections as $value) {

                    ?>
                    <td align="center"><font size="1px" color="green"><span class="hand"
                                                                            onclick="document.getElementById('s_section').value = '<?= $value['0'] ?>'; document.getElementById('s_result').value = 'success';document.getElementById('data_aggr_search').value = 'true';document.getElementById('data_aggr_range').value = '1h';toggleDateMode('hide');loadLogins();">Success: <?= $_SESSION['dbapi']->login_tracker->getDataAggrCount('1h', $value['0'], 'success') ?></span></font>
                    <br>
                    <font size="1px" color="red"><span class="hand"
                                                       onclick="document.getElementById('s_section').value = '<?= $value['0'] ?>'; document.getElementById('s_result').value = 'failure';document.getElementById('data_aggr_search').value = 'true';document.getElementById('data_aggr_range').value = '1h';toggleDateMode('hide');loadLogins();">Failure: <?= $_SESSION['dbapi']->login_tracker->getDataAggrCount('1h', $value['0'], 'failure') ?></span></font>
                    </td><?

                }

                ?>
            </tr>
            <tr>
                <td align="right" height="35">24 hour</td>
                <?

                ## BUILD SECTIONS AND RESULTS FOR 24HOUR
                foreach ($sections as $value) {

                    ?>
                    <td align="center"><font size="1px" color="green"><span class="hand"
                                                                            onclick="document.getElementById('s_section').value = '<?= $value['0'] ?>'; document.getElementById('s_result').value = 'success';document.getElementById('data_aggr_search').value = 'true';document.getElementById('data_aggr_range').value = '24h';toggleDateMode('hide');loadLogins();">Success: <?= $_SESSION['dbapi']->login_tracker->getDataAggrCount('24h', $value['0'], 'success') ?></span></font>
                    <br>
                    <font size="1px" color="red"><span class="hand"
                                                       onclick="document.getElementById('s_section').value = '<?= $value['0'] ?>'; document.getElementById('s_result').value = 'failure';document.getElementById('data_aggr_search').value = 'true';document.getElementById('data_aggr_range').value = '24h';toggleDateMode('hide');loadLogins();">Failure: <?= $_SESSION['dbapi']->login_tracker->getDataAggrCount('24h', $value['0'], 'failure') ?></span></font>
                    </td><?

                }

                ?>
            </tr>
            <tr>
                <td align="right" height="35">7 day</td>
                <?

                ## BUILD SECTIONS AND RESULTS FOR 7DAYS
                foreach ($sections as $value) {

                    ?>
                    <td align="center"><font size="1px" color="green"><span class="hand"
                                                                            onclick="document.getElementById('s_section').value = '<?= $value['0'] ?>'; document.getElementById('s_result').value = 'success';document.getElementById('data_aggr_search').value = 'true';document.getElementById('data_aggr_range').value = '7d';toggleDateMode('hide');loadLogins();">Success: <?= $_SESSION['dbapi']->login_tracker->getDataAggrCount('7d', $value['0'], 'success') ?></span></font>
                    <br>
                    <font size="1px" color="red"><span class="hand"
                                                       onclick="document.getElementById('s_section').value = '<?= $value['0'] ?>'; document.getElementById('s_result').value = 'failure';document.getElementById('data_aggr_search').value = 'true';document.getElementById('data_aggr_range').value = '7d';toggleDateMode('hide');loadLogins();">Failure: <?= $_SESSION['dbapi']->login_tracker->getDataAggrCount('7d', $value['0'], 'failure') ?></span></font>
                    </td><?

                }

                ?>
            </tr>
        </table>

        <?

    }

    ## DISPLAY VIEW LOGIN FORM WHICH IS DISPLAY ONLY
    function makeView($id)
    {

        $id = intval($id);


        if ($id) {

            $row = $_SESSION['dbapi']->login_tracker->getByID($id);


        }

        ?>
        <script>


            // SET TITLEBAR
            $('#dialog-modal-view-login').dialog("option", "title", '<?=($id) ? 'Viewing Login #' . $id . ' - ' . htmlentities($row['username']) : ''?>');


            function kickoutUser(loginid) {


                $.ajax({
                    type: "GET",
                    cache: false,
                    url: 'ajax.php?mode=force_logout&force_logout_user=' + loginid,
                    error: function () {
                        alert("Error submitting. Please contact an admin.");
                    },
                    success: function (msg) {

                        alert(msg);

                    }
                });


            }

        </script>
        <form method="POST" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="checkLoginFrm(this); return false">
            <input type="hidden" id="viewing_login" name="viewing_login" value="<?= $id ?>">


            <table border="0" align="center">
                <tr>
                    <th align="left" height="30" width="100">User ID:</th>
                    <td><?= htmlentities($row['user_id']) ?></td>
                </tr>
                <tr>
                    <th align="left" height="30"><?= ($row['result'] == 'success') ? "Time Logged in:" : "Time Attempted:" ?></th>
                    <td><?= date("g:i:sa", $row['time']) ?></td>
                </tr><?

                if ($row['result'] == 'success') {
                    ?>
                    <tr>
                    <th align="left" height="30">Time Last Action:</th>
                    <td><?= date("g:i:sa", $row['time_last_action']) ?></td>
                    </tr>
                    <tr>
                    <th align="left" height="30">Time Logged out:</th>
                    <td><?= ($row['time_out'] == 0) ?
                            '[STILL LOGGED IN]' . ((checkAccess('login_tracker_kick_user')) ? '<input type="button" value="KICK THEM OUT" onclick="kickoutUser(' . $row['id'] . ');">' : '') :
                            date("g:i:sa", $row['time_out']) ?></td>
                    </tr><?


                }

                ?>
                <tr>
                    <th align="left" height="30">Username:</th>
                    <td><?= htmlentities($row['username']) ?></td>
                </tr>
                <tr>
                    <th align="left" height="30">Result:</th>
                    <td><?= htmlentities($row['result']) ?></td>
                </tr><?

                if (trim($row['details'])) {
                    ?>
                    <tr>
                    <th align="left" height="30">Reason:</th>
                    <td><?= htmlentities($row['details']) ?></td>
                    </tr><?
                }

                ?>
                <tr>
                    <th align="left" height="30">Section:</th>
                    <td><?= htmlentities($row['section']) ?></td>
                </tr><?

                if ($row['section'] != 'admin') {
                    ?>
                    <tr>
                    <th align="left" height="30">Campaign:</th>
                    <td><?= htmlentities($row['script_id']) ?> - <?

                        $campaign_info = $_SESSION['dbapi']->campaigns->getByID($row['script_id']);

                        if ($campaign_info) {

                            echo htmlentities($campaign_info['name']);

                        } else {

                            echo "Name not available";

                        }


                        ?></td>
                    </tr>
                    <tr>
                    <th align="left" height="30">Voice:</th>
                    <td><?= htmlentities($row['voice_id']) ?> - <?

                        $voice_info = $_SESSION['dbapi']->voices->getByID($row['voice_id']);

                        if ($voice_info) {

                            echo htmlentities($voice_info['name']);

                        } else {

                            echo "Name not available";

                        }


                        ?></td>
                    </tr><?

                }

                ?>
                <tr>
                    <th align="left" height="30">IP:</th>
                    <td><?= htmlentities($row['ip']) ?></td>
                </tr>
                <tr>
                    <th align="left" height="30">Browser:</th>
                    <td><?= htmlentities($row['browser']) ?></td>
                </tr>
        </form>
        </table><?


    }


    function getOrderLink($field)
    {

        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';

        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";

        $var .= ");loadLogins();return false;\">";

        return $var;
    }
}

