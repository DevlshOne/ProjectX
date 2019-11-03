<?
    /***************************************************************
     *    Dialer Status Dashboard - dialer data as draggable / sortable
     *    Written By: Dave Mednick
     ***************************************************************/

    $_SESSION['dialer_status'] = new DialerStatus;

    class DialerStatus {
        public $availableClusterIDs = [];
        public $availableClusterIPs = [];
        public $clusterNames = [];
        public $selectedClusters = [];
        public $table = 'vici_cluster';
        public $clusterInfo = [];

        ## Classes main table to operate on

        function DialerStatus() {
            $this->handlePOST();
        }

        function handlePOST() {
        }

        function handleFLOW() {
            $this->displayDialers();
        }

        function getClusterInfo() {
            foreach (getClusterIDs() as $i => $v) {
                $this->clusterInfo[$i]['cluster_id'] = $v;
                $this->clusterInfo[$i]['type'] = getClusterType($v);
                $this->clusterInfo[$i]['name'] = getClusterName($v);
                $this->clusterInfo[$i]['ip'] = getClusterWebHost($v);
                $this->clusterInfo[$i]['sel_campaigns'] = getClusterCampaigns($v);
                $this->clusterInfo[$i]['sel_user_groups'] = getClusterUserGroups($v);
                $this->clusterInfo[$i]['campaign_options'] = getClusterCampaigns($v);
                $this->clusterInfo[$i]['usergroup_options'] = getClusterUserGroups($v);
            }
        }

        function displayDialers() {
            /*
             * TODO
             * auto-refresh every 4 seconds
             * rebuild the url based on the selected clusters
             * calculate the spread of boxes?
             * get ALL the data and then only display the clusters requested or only get the clusters requested?
             */
            $this->availableClusterIDs = getClusterIDs();
            $this->getClusterInfo();
            ?>
            <table class="pct100 tightTable">
                <tr>
                    <td class="ht40 pad_left ui-widget-header">
                        <table class="pct100 tightTable">
                            <tr>
                                <td class="pct100">
                                    <div class="align_center" style="float:left;margin:7px;">Dialer Status Dashboard</div>
                                    <button id="clusterSelectButton" class="align_center ui-state-highlight" style="float:right;">Select Clusters</button>
                                    <button id="refreshRateButton" class="align_center refreshButton" style="float:right;">Change Refresh [40]</button>
                                    <button id="stopDialersButton" class="align_center ui-state-error" style="float:right;">Stop All Dialing</button>
                                    <button id="forceHopperButton" class="align_center" style="float:right;">Force Hopper</button>
                                    <button id="switchContrast" class="align_center" style="float:right;" value="Dark Mode" onclick="">Dark Mode</button>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <ul id="dialerStatusZone"></ul>
                    </td>
                </tr>
            </table>
            <div id="dialog-modal-add-tile" title="Add tile" class="nod"></div>
            <div id="dialog-modal-rename-tile" title="Rename tile" class="nod">
                <form method="post">
                    <table class="tightTable pct100">
                        <tbody>
                        <tr>
                            <td class="align_left"><label for="new_tile_name">Tile Name :</label></td>
                            <td class="align_right"><input type="text" id="new_tile_name" name="new_tile_name"/></td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <div id="dialog-modal-select-clusters" title="Cluster selection" class="nod"></div>
            <div id="dialog-modal-change-refresh" title="Modify refresh rate" class="nod"></div>
            <div id="dialog-modal-cluster-filters" title="Filters" class="nod"></div>
            <div id="dialog-modal-first-confirm" title="Confirmation Required" class="nod"></div>
            <div id="dialog-modal-second-confirm" title="Confirmation Required" class="nod"></div>
            <div id="dialog-modal-cluster-action-confirm" title="Confirmation Required" class="nod"></div>
            <div id="dialog-modal-vici-credentials" title="Vici Username/Password Required" class="nod">
                <form method="post">
                    <table class="tightTable pct100">
                        <tbody>
                        <tr>
                            <td class="align_left"><label for="vici_username">Username :</label></td>
                            <td class="align_right"><input type="text" id="vici_username" name="vici_username"/></td>
                        </tr>
                        <tr>
                            <td class="align_left"><label for="vici_password">Password :</label></td>
                            <td class="align_right"><input type="password" id="vici_password" name="vici_password" required/></td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <div id="dialog-modal-load-userprefs" title="Load User Preferences" class="nod">
                <form method="post">
                    <table class="tightTable pct100">
                        <tbody>
                        <tr>
                            <td class="align_left">Would you like to load your user preferences?</td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <script>
                /*
                  New Tile Object Structure:
                  tile -
                      clusters Array
                      options Array
                 */
                $('#dialerStatusZone').ready(function () {
                    var refreshInterval = 40;
                    var refreshEnabled = true;
                    var frontEnd_debug = false;
                    dispTimer = false;
                    // clusterInfo is an array that stores all the available information for a cluster, including the selectable campaigns and usergroups
                    var clusterInfo = <?=json_encode($this->clusterInfo);?>;
                    // availableClusters is an array that lists all available clusters for this user
                    var availableClusters = <?=json_encode($this->availableClusterIDs);?>;
                    // tileDefs is an array of clusterDefs (object storing the specifics for that cluster)
                    var tileDefs = [];

                    class clusterDef {
                        constructor(i, t, n, ip, g, ugf) {
                            this.cluster_id = i;
                            this.type = t;
                            this.name = n;
                            this.web_ip = ip;
                            this.groups = g;
                            this.user_group_filter = ugf;
                        }
                    }

                    $(clusterInfo).each(function (i, v) {
                        tileDefs.push(new clusterDef(v.cluster_id, v.type, v.name, v.ip, v.sel_campaigns, v.sel_user_groups));
                    });
                    if (frontEnd_debug) {
                        console.log('Initializing the variable `clusterInfo` :: ', clusterInfo);
                        console.log('Populated `tileDefs` :: ', tileDefs);
                    }
                    var dlgObj = {};
                    var highContrast = false;
                    var viciMisMatch = false;
                    //if ("<?//=$_SESSION['user']['vici_password'];?>//" !== "<?//=$_SESSION['user']['password'];?>//") {
                    //    viciMisMatch = true;
                    //}
                    var scriptRoot = '<?=$_SESSION['site_config']['basedir'];?>';
                    var useCache = true;
                    var cacheDebug = false;
                    const tileAdder = '<li id="tile_add" class="clusterTile adderTile"><table class="tightTable hand"><tr><td align="center"><img src="images/add_icon.png" width="60px" border="0" title="Add a new tile" /></td></tr></table></li>';

                    $('#dialog-modal-add-tile').dialog({
                        autoOpen: false,
                        width: 400,
                        modal: true,
                        draggable: true,
                        resizable: false,
                        title: 'Add New Tile',
                        buttons: {
                            'Save': function () {
                                $('#clusterSelection option:selected').each(function () {
                                    tileDefs.push(new clusterDef(clusterInfo[this.value].cluster_id, clusterInfo[this.value].type, clusterInfo[this.value].name, clusterInfo[this.value].web_ip, clusterInfo[this.value].groups, clusterInfo[this.value].user_group_filter));
                                });
                                if (frontEnd_debug) {
                                    console.log('Clusters have just been changed :: ', tileDefs);
                                }
                                saveUserPrefs();
                                initScreen();
                                getDialerStatusData();
                                $(this).dialog('close');
                            },
                            'Cancel': function () {
                                $(this).dialog('close');
                            }
                        },
                        position: 'center'
                    });

                    $('#dialog-modal-rename-tile').dialog({
                        autoOpen: false,
                        width: 400,
                        modal: false,
                        draggable: true,
                        resizable: false,
                        title: 'Rename Tile',
                        buttons: {
                            'Save': function () {
                                let tileID = $(this).data('tileID');
                                if($('#new_tile_name') != '') {
                                    tileDefs[tileID].name = $('#new_tile_name').val();
                                }
                                if (frontEnd_debug) {
                                    console.log('Tile name has just been changed :: ', tileDefs);
                                }
                                saveUserPrefs();
                                initScreen();
                                getDialerStatusData();
                                $(this).dialog('close');
                            },
                            'Cancel': function () {
                                $(this).dialog('close');
                            }
                        },
                        position: 'center'
                    });

                    $('#dialog-modal-select-clusters').dialog({
                        autoOpen: false,
                        width: 400,
                        modal: false,
                        draggable: true,
                        resizable: false,
                        title: 'Cluster Selection',
                        buttons: {
                            'Save': function () {
                                tileDefs = [];
                                $('#clusterSelection option:selected').each(function () {
                                    tileDefs.push(new clusterDef(this.value));
                                });
                                if (frontEnd_debug) {
                                    console.log('Clusters have just been changed :: ', tileDefs);
                                }
                                saveUserPrefs();
                                initScreen();
                                getDialerStatusData();
                                $(this).dialog('close');
                            },
                            'Cancel': function () {
                                $(this).dialog('close');
                            }
                        },
                        position: 'center'
                    });

                    $('#dialog-modal-change-refresh').dialog({
                        autoOpen: false,
                        width: 400,
                        modal: false,
                        draggable: true,
                        resizable: false,
                        title: 'Change Refresh Rate',
                        buttons: {
                            'Save': function () {
                                clearInterval(dispTimer);
                                refreshInterval = $('#refreshRate').val();
                                refreshEnabled = !$('#refreshEnabled').is(':checked');
                                if (!refreshEnabled) {
                                    $('#refreshRateButton').find('.ui-button-text').text('Change Refresh [OFF]');
                                } else {
                                    $('#refreshRateButton').find('.ui-button-text').text('Change Refresh [' + refreshInterval + ']');
                                    dispTimer = setInterval(getDialerStatusData, (refreshInterval * 1000));
                                }
                                saveUserPrefs();
                                $(this).dialog('close');
                            },
                            'Cancel': function () {
                                $(this).dialog('close');
                            }
                        },
                        position: 'center'
                    });

                    $('#dialog-modal-cluster-filters').dialog({
                        autoOpen: false,
                        width: 400,
                        modal: false,
                        draggable: true,
                        resizable: false,
                        title: 'Change Cluster Filters',
                        buttons: {
                            'Save': function (e) {
                                let tileID = $(this).data('tileID');
                                $('#campaignFilter option:selected').each(function (i, v) {
                                    tileDefs[tileID].groups.push(v.innerText);
                                });
                                tileDefs[tileID].groups = tmpArr;
                                $('#usergroupFilter option:selected').each(function (i, v) {
                                    tileDefs[tileID].user_group_filter.push(v.innerText);
                                });
                                saveUserPrefs();
                                $(this).dialog('close');
                            },
                            'Cancel': function () {
                                $(this).dialog('close');
                            }
                        },
                        position: 'center'
                    });

                    $('#dialog-modal-cluster-action-confirm').dialog({
                        autoOpen: false,
                        width: 400,
                        modal: true,
                        draggable: false,
                        resizable: false,
                        buttons: {
                            'Cancel': function () {
                                $(this).dialog('close');
                            },
                            'Confirm': function () {
                                let theAction = $(this).data('myAction');
                                let clusterID = $(this).data('clusterID');
                                $(this).dialog('close');
                                switch (theAction) {
                                    case 'forceHopper':
                                        forceHopper(clusterID);
                                        break;
                                    case 'stopDialers':
                                        stopDialers(clusterID);
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    });


                    $('#dialog-modal-second-confirm').dialog({
                        autoOpen: false,
                        width: 400,
                        modal: true,
                        draggable: false,
                        resizable: false,
                        buttons: {
                            'Cancel': function () {
                                $(this).dialog('close');
                            },
                            'Confirm': function () {
                                let theAction = $(this).data('myAction');
                                let clusterID = $(this).data('clusterID');
                                $(this).dialog('close');
                                switch (theAction) {
                                    case 'forceHopper':
                                        forceHopper('ALL');
                                        break;
                                    case 'stopDialers':
                                        stopDialers(clusterID);
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                    });

                    $('#dialog-modal-first-confirm').dialog({
                        autoOpen: false,
                        width: 400,
                        modal: true,
                        draggable: false,
                        resizable: false,
                        buttons: {
                            'Cancel': function () {
                                $(this).dialog('close');
                            },
                            'Confirm': function () {
                                let theAction = $(this).data('myAction');
                                let tileID = $(this).data('tileID');
                                let clusterID = $(this).data('clusterID');
                                $(this).dialog('close');
                                switch (theAction) {
                                    case 'forceHopper':
                                        $('#dialog-modal-second-confirm').html('<div class="secondConfirmation">This will EMPTY/ERASE ALL CALLS from the hopper, are you ABSOLUTELY sure?</div>');
                                        break;
                                    case 'stopDialers':
                                        $('#dialog-modal-second-confirm').html('<div class="secondConfirmation">This will STOP ALL DIALING on the PRODUCTION servers, are you ABSOLUTELY sure?</div>');
                                        break;
                                    default:
                                        break;
                                }
                                $('#dialog-modal-second-confirm').data('myAction', theAction);
                                $('#dialog-modal-second-confirm').data('tileID', tileID);
                                $('#dialog-modal-second-confirm').dialog('open');
                            }
                        }
                    });

                    $('#dialog-modal-vici-credentials').dialog({
                        autoOpen: viciMisMatch,
                        width: 400,
                        title: 'Vici Username/Password Required',
                        modal: true,
                        draggable: true,
                        resizable: false,
                        buttons: {
                            'Submit': function (e, ui) {
                                e.preventDefault();
                                $.ajax({
                                    type: 'POST',
                                    url: 'api/api.php?get=dialer_status&mode=json&action=setViciCreds&vici_username=' + $('#vici_username').val() + '&vici_password=' + $('#vici_password').val(),
                                    success: function () {
                                        alert('Vici Username/Password SAVED for this session');
                                    },
                                    error: function (response) {
                                        console.log('FAILURE - ' + response);
                                    }
                                });
                                getDialerStatusData();
                                $(this).dialog('close');
                            }
                        }
                    });

                    $('#dialog-modal-load-userprefs').dialog({
                        autoOpen: false,
                        width: 400,
                        title: 'Load User Preferences',
                        modal: true,
                        draggable: false,
                        resizable: false,
                        buttons: {
                            'Yes': function (e, ui) {
                                e.preventDefault();
                                loadUserPrefs();
                                $(this).dialog('close');
                            },
                            'No': function (e, ui) {
                                $(this).dialog('close');
                            }
                        }
                    });

                    function getClusterInfoByClusterID(i) {
                        let r = clusterInfo.filter(obj => {
                            return obj.cluster_id === i;
                        });
                        return r;
                    }

                    function loadUserPrefs() {
                        $.ajax({
                            type: "POST",
                            cache: false,
                            async: false,
                            dataType: 'json',
                            crossDomain: false,
                            crossOrigin: false,
                            url: 'api/api.php?get=dialer_status&mode=json&action=loadUserPrefs',
                            success: function (prefs) {
                                if (prefs.length) {
                                    let guiPrefs = prefs.pop();
                                    refreshInterval = guiPrefs.refreshInterval;
                                    refreshEnabled = guiPrefs.refreshEnabled;
                                    highContrast = guiPrefs.highContrast;
                                    tileDefs = prefs;
                                    let oldPrefsData = false;
                                    $.each(tileDefs, function (i, v) {
                                        if (tileDefs.name === undefined || tileDefs.name === '') {
                                            // missing tileDef data because the prefs format is outdated
                                            oldPrefsData = true;
                                            let clusterData = getClusterInfoByClusterID(v.cluster_id);
                                            tileDefs[i] = new clusterDef(v.cluster_id, clusterData['0'].type, clusterData['0'].name, clusterData['0'].ip, v.groups, v.user_group_filter);
                                        }
                                    });
                                    if(oldPrefsData) saveUserPrefs();
                                    if (frontEnd_debug) {
                                        console.log('Prefs have just been loaded :: ', tileDefs);
                                        console.log('User Preferences loaded');
                                    }
                                }
                            }
                        });
                    }

                    function saveUserPrefs() {
                        let tmpDefs = tileDefs.slice();
                        let tmpJSON = tmpDefs;
                        let idxGUIPrefs = tmpDefs.length++;
                        tmpJSON[idxGUIPrefs] = {
                            refreshInterval: refreshInterval,
                            refreshEnabled: refreshEnabled,
                            highContrast: highContrast,
                            viciUsername: '<?=$_SESSION['user']['username'];?>',
                            viciPassword: '<?=$_SESSION['user']['vici_password'];?>'
                        };
                        let tmpPrefs = JSON.stringify(tmpJSON);
                        let prefpoststr = 'prefs=' + tmpPrefs;
                        if (frontEnd_debug) {
                            console.log('Saving in user preferences :: tileDefs :: ', tileDefs);
                        }
                        $.ajax({
                            type: "POST",
                            cache: false,
                            async: false,
                            crossDomain: false,
                            crossOrigin: false,
                            data: prefpoststr,
                            url: 'api/api.php?get=dialer_status&mode=json&action=saveUserPrefs',
                            success: function () {
                                console.log('User Preferences saved');
                            }
                        });
                    }

                    $('#dialerStatusZone').sortable({
                        cancel: '#clusterTileAdder',
                        items: 'li:not(#tile_add)',
                        refreshPositions: true,
                        stop: function (e, ui) {
                            // the sort order has been changed - now re-arrange the selectedClusters array accordingly
                            let clusterTiles = $('#dialerStatusZone').children('li[id!="tile_add"]');
                            let newTileOrder = [];
                            $.each(clusterTiles, function (i, v) {
                                newTileOrder[i] = $(v).attr('id').split('_')[1];
                            });
                            tileDefs.sort(function (a, b) {
                                return newTileOrder.indexOf(a) - newTileOrder.indexOf(b);
                            });
                            if (frontEnd_debug) {
                                console.log('Tiles have just been sorted :: ', tileDefs);
                            }
                            saveUserPrefs();
                        }
                    });

                    function initScreen() {
                        $('#dialerStatusZone').empty();
                    }

                    $('#clusterSelectButton').on('click', function () {
                        let dlgObj = $('#dialog-modal-select-clusters');
                        let clusterSelect = '<select class="align_left" name="clusterSelection" id="clusterSelection" multiple size="6">';
                        $.each(availableClusters, function (i, v) {
                            clusterSelect += '<option value="' + v + '">' + clusterInfo[i].name + '</option>';
                        });
                        clusterSelect += '</select>';
                        dlgObj.dialog('open');
                        dlgObj.html('<table class="pct100 tightTable"><tbody><tr><td class="align_left"><label for="clusterSelection">Select Cluster(s) : </label></td><td class="align_right">' + clusterSelect + '</td></tr></tbody></table>');
                        $('#clusterSelection').val(tileDefs);
                    });
                    $('#refreshRateButton').on('click', function () {
                        let dlgObj = $('#dialog-modal-change-refresh');
                        dlgObj.dialog('open');
                        let refreshOptions = '<option value="4">4 seconds</option><option value="10">10 seconds</option><option value="20">20 seconds</option><option value="30">30 seconds</option><option value="40">40 seconds</option><option value="60">60 seconds</option><option value="120">2 minutes</option><option value="300">5 minutes</option><option value="600">10 minutes</option><option value="1200">20 minutes</option><option value="1800">30 minutes</option><option value="3600">60 minutes</option><option value="7200">120 minutes</option>';
                        dlgObj.html('<table class="pct100 tightTable"><tr><td class="align_left"><label for="refreshRate">Refresh Rate : </label><td class="align_right"><select id="refreshRate" name="refreshRate">' + refreshOptions + '</select></td></tr><tr><td class="align_left"><label for="refreshEnabled">Disable refresh : </label><td class="align_right"><input id="refreshEnabled" name="refreshEnabled" type="checkbox"' + (refreshEnabled ? '' : ' checked') + ' /></td></tr></table>');
                        $('#refreshRate').val(refreshInterval);
                    });
                    $('#forceHopperButton').on('click', function () {
                        let dlgObj = $('#dialog-modal-first-confirm');
                        dlgObj.data('myAction', 'forceHopper');
                        dlgObj.html('<div class="firstConfirmation">This will EMPTY/ERASE ALL CALLS from the hopper, are you sure?</div>');
                        dlgObj.dialog('open');
                    });
                    $('#stopDialersButton').on('click', function () {
                        let dlgObj = $('#dialog-modal-first-confirm');
                        dlgObj.data('myAction', 'stopDialers');
                        dlgObj.html('<div class="firstConfirmation">This will stop ALL DIALING on the PRODUCTION servers, are you sure?</div>');
                        dlgObj.dialog('open');
                    });
                    $('#switchContrast').on('click', function () {
                        if (!highContrast) {
                            $('body').css('background-color', '#000000');
                            $('body').css('color', '#FFFFFF');

                            $('#main_content').css('background-color', '#000000');
                            $('#main_content').css('color', '#FFFFFF');

                            $('#dialerStatusZone').css('background-color', '#000000');
                            $('.clusterTile').css('background-color', 'black');
                            $(this).button('option', 'label', 'Light Mode');
                            highContrast = true;
                        } else {
                            $('body').css('background-color', '#FFFFFF');
                            $('body').css('color', '#000000');

                            $('#main_content').css('background-color', '#FFFFFF');
                            $('#main_content').css('color', '#000000');

                            $('#dialerStatusZone').css('background-color', '#FFFFFF');
                            $('.clusterTile').css('background-color', 'navy');
                            $(this).button('option', 'label', 'Dark Mode');
                            highContrast = false;
                        }
                        saveUserPrefs();
                    });
                    $('#dialerStatusZone').on('click', '#tile_add', function () {
                        let dlgObj = $('#dialog-modal-add-tile');
                        let clusterSelect = '<select class="align_left" name="clusterSelection" id="clusterSelection">';
                        $.each(availableClusters, function (i) {
                            clusterSelect += '<option value="' + i + '">' + clusterInfo[i].name + '</option>';
                        });
                        clusterSelect += '</select>';
                        dlgObj.dialog('open');
                        dlgObj.html('<table class="pct100 tightTable"><tbody><tr><td class="align_left"><label for="clusterSelection">Select Cluster : </label></td><td class="align_right">' + clusterSelect + '</td></tr></tbody></table>');
                    });

                    $('#dialerStatusZone').on('click', '.selectFiltersButton', function () {
                        let tileID = $(this).closest('button').attr('id').split('_')[1];
                        let dlgObj = $('#dialog-modal-cluster-filters');
                        dlgObj.data('tileID', tileID);
                        dlgObj.data('clusterID', tileDefs[tileID].cluster_id);
                        dlgObj.dialog('open');
                        dlgObj.dialog({title: 'Change Cluster Filters - ' + tileDefs[tileID].name});
                        let campaignSelect = '<select name="groups" id="campaignFilter" multiple size="6"><option value="ALL-ACTIVE">ALL-ACTIVE</option>';
                        $.each(clusterInfo[tileID]['campaign_options'], function (i, v) {
                            campaignSelect += '<option value="' + v.groups + '">' + v.groups + '</option>';
                        });
                        campaignSelect += '</select>';
                        let ugSelect = '<select name="user_group_filter" id="usergroupFilter" multiple size="8"><option>ALL-GROUPS</option>';
                        $.each(clusterInfo[tileID]['usergroup_options'], function (i, v) {
                            ugSelect += '<option value="' + v.user_group_filter + '">' + v.user_group_filter + '</option>';
                        });
                        ugSelect += '</select>';
                        dlgObj.html('<table class="pct100 tightTable"><tr><td class="align_left"><label for="filterCampaigns">Select Campaign(s) : </label></td><td class="align_right">' + campaignSelect + '</td></tr><tr><td class="align_left"><label for="usergroupFilter">Select User Group(s) : </label></td><td class="align_right">' + ugSelect + '</td></tr></table>');
                        $('#campaignFilter').val(tileDefs[tileID].groups);
                        $('#usergroupFilter').val(tileDefs[tileID].user_group_filter);
                    });

                    $('#dialerStatusZone').on('click', '.clusterTitle', function () {
                        let tileID = $(this).closest('li').attr('id').split('_')[1].toString();
                        let dlgObj = $('#dialog-modal-rename-tile');
                        dlgObj.data('tileID', tileID);
                        dlgObj.dialog('open');
                        saveUserPrefs();
                        if (frontEnd_debug) {
                            console.log('Renamed tile :: ', tileID);
                            console.log('Prefs have just been saved :: ', tileDefs);
                        }
                    });

                    $('#dialerStatusZone').on('click', '.removeClusterButton', function () {
                        let tileID = $(this).attr('id').split('_')[1].toString();
                        $('#tile_' + tileID).remove();
                        tileDefs.splice(tileID, 1);
                        saveUserPrefs();
                        if (frontEnd_debug) {
                            console.log('Removed tile :: ', tileID);
                            console.log('Prefs have just been saved :: ', tileDefs);
                        }
                    });

                    $('#dialerStatusZone').on('click', '.stopDialersButton', function () {
                        let tileID = $(this).closest('button').attr('id').split('_')[1];
                        let dlgObj = $('#dialog-modal-cluster-action-confirm');
                        dlgObj.data('myAction', 'stopDialers');
                        dlgObj.data('tileID', tileID);
                        dlgObj.data('clusterID', tileDefs[tileID].cluster_id);
                        dlgObj.html('<div class="firstConfirmation">This will STOP all dialing for ' + tileDefs[tileID].name + ', are you sure?</div>');
                        dlgObj.dialog('open');
                    });

                    $('#dialerStatusZone').on('click', '.forceHopperButton', function () {
                        let tileID = $(this).closest('button').attr('id').split('_')[1];
                        let dlgObj = $('#dialog-modal-cluster-action-confirm');
                        dlgObj.data('myAction', 'forceHopper');
                        dlgObj.data('tileID', tileID);
                        dlgObj.data('clusterID', tileDefs[tileID].cluster_id);
                        dlgObj.html('<div class="firstConfirmation">This will RESET the hopper for ' + tileDefs[tileID].name + ', are you sure?</div>');
                        dlgObj.dialog('open');
                    });

                    $('#dialerStatusZone').on('click', '.showAgentsButton', function () {
                        let tileID = $(this).closest('button').attr('id').split('_')[1];
                        let $agentData = $('#tile_' + tileID).find('.agentInfo');
                        $agentData.toggle();
                    });

                    function stopDialers(clid) {
                        if (clid === 'ALL') {
                            $.each(tileDefs, function (i, v) {
                                $.ajax({
                                    type: "POST",
                                    cache: false,
                                    async: false,
                                    crossDomain: false,
                                    crossOrigin: false,
                                    url: 'api/api.php?get=dialer_status&mode=json&action=stopDialer&clusterid=' + v.cluster_id
                                });
                            });
                            alert('ALL dialers have been stopped!');
                        } else {
                            if (Array.isArray(clid)) {
                                $.each(clid, function (i, v) {
                                    $.ajax({
                                        type: "POST",
                                        cache: false,
                                        async: false,
                                        crossDomain: false,
                                        crossOrigin: false,
                                        url: 'api/api.php?get=dialer_status&mode=json&action=stopDialer&clusterid=' + v
                                    });
                                });
                                alert('Dialer for Cluster[s] ' + clid.join(', ') + ' have been stopped!');
                            } else {
                                $.ajax({
                                    type: "POST",
                                    cache: false,
                                    async: false,
                                    crossDomain: false,
                                    crossOrigin: false,
                                    url: 'api/api.php?get=dialer_status&mode=json&action=stopDialer&clusterid=' + clid
                                });
                                alert('Dialer for Cluster ' + clid + ' has been stopped!');
                            }
                        }
                    }

                    function forceHopper(clid) {
                        if (clid === 'ALL') {
                            $.each(tileDefs, function (i, v) {
                                $.ajax({
                                    type: "POST",
                                    cache: false,
                                    async: false,
                                    crossDomain: false,
                                    crossOrigin: false,
                                    url: 'api/api.php?get=dialer_status&mode=json&action=forceHopperReset&clusterid=' + v.cluster_id
                                });
                            });
                            alert('All hoppers have been reset!');
                        } else {
                            $.ajax({
                                type: "POST",
                                cache: false,
                                async: false,
                                crossDomain: false,
                                crossOrigin: false,
                                url: 'api/api.php?get=dialer_status&mode=json&action=forceHopperReset&clusterid=' + clid
                            });
                            alert('Hopper for Cluster ' + clid + ' has been reset!');
                        }
                    }

                    function applyAgentsThresh(s, v, cltype) {
                        let maxGood = 0;
                        let maxWarn = 0;
                        switch (cltype) {
                            default:
                            case 'cold':
                                maxGood = 4;
                                maxWarn = 8;
                                break;
                            case 'taps':
                                maxGood = 8;
                                maxWarn = 12;
                                break;
                        }
                        if (s <= maxGood && v >= 1) {
                            return 'greenThresh';
                        }
                        if (s > maxGood && s <= maxWarn && v >= 1) {
                            return 'yellowThresh';
                        }
                        if (s > maxWarn && v >= 1) {
                            return 'redThresh';
                        }
                    }


                    function applyPositiveThresh(v, crit, warn, default_color) {
                        if (parseInt(v) >= crit) {
                            return '<span style="color:red;">' + v.toString() + '</span>';
                        }
                        if (parseInt(v) >= warn) {
                            return '<span style="color:yellow;">' + v.toString() + '</span>';
                        }

                        if (default_color) {

                            return '<span style="color:' + default_color + ';">' + v.toString() + '</span>';

                        } else {
                            return v;
                        }
                    }

                    function applyThresh(v, crit, warn, default_color) {
                        if (parseInt(v) < crit) {
                            return '<span style="color:red;">' + v.toString() + '</span>';
                        }
                        if (parseInt(v) < warn) {
                            return '<span style="color:yellow;">' + v.toString() + '</span>';
                        }

                        if (default_color) {

                            return '<span style="color:' + default_color + ';">' + v.toString() + '</span>';

                        } else {
                            return v;
                        }
                    }

                    function parseTable(tile_id, tbl) {
                        let cltype = tileDefs[tile_id].type;
                        let clusterDataFields = [
                            'dial_level',
                            'do_NOT_use',
                            'filter',
                            'time',
                            'dialable_leads',
                            'calls_today',
                            'avg_agents',
                            'dial_method',
                            'do_NOT_use',
                            'do_NOT_use',
                            'dl_diff',
                            'statuses',
                            'hopper_leads',
                            'dropped_pct',
                            'diff',
                            'order',
                            'avg_agent_wait',
                            'avg_cust_time',
                            'avg_acw',
                            'avg_pause'
                        ];
                        let clusterSummaryFields = [
                            'calls_active',
                            'calls_ringing',
                            'calls_waiting',
                            'calls_ivr',
                            'agents_on',
                            'agents_active',
                            'agents_waiting',
                            'agents_paused',
                            'agents_dead',
                            'agents_dispo'
                        ];
                        const rgxPre = /<PRE>([\s\S]*)<\/PRE>/gi;
                        let preString = '';
                        if (tbl.match(rgxPre) !== null) {
                            preString = tbl.match(rgxPre)[0];
                        } else {
                            preString = '';
                        }
                        let agentDataOutput = '';
                        let clusterData = '<HTML>' + tbl.replace(rgxPre, '').split('</FORM>')[0] + '</HTML>';
                        let summaryData = '<HTML>' + tbl.replace(rgxPre, '').split('</FORM>')[1] + '</HTML>';
                        if (preString.length > 11) {
                            let tmpAgentData = '';
                            tmpAgentData = preString.match(rgxPre)[0];
                            let tmpAgentDataSplit = new Array();
                            tmpAgentDataSplit = tmpAgentData.match(/<b>(.*?)<\/b>/gi).map(function (val) {
                                return val.replace(/<\/?b>/gi, '').trim();
                            });
                            let parsedAgentData = [];
                            let rowNumber = 0;
                            let colNumber = 0;
                            parsedAgentData[0] = [];
                            for (let cell = 0; cell < tmpAgentDataSplit.length - 1; cell++) {
                                if ((cell > 0) && (cell % 7 === 0)) {
                                    rowNumber++;
                                    colNumber = 0;
                                    parsedAgentData[rowNumber] = [];
                                }
                                switch (colNumber) {
                                    case 0 :
                                        parsedAgentData[rowNumber]['station_id'] = tmpAgentDataSplit[cell];
                                        break;
                                    case 1 :
                                        parsedAgentData[rowNumber]['agent_user'] = tmpAgentDataSplit[cell];
                                        break;
                                    case 3 :
                                        parsedAgentData[rowNumber]['ready_status'] = tmpAgentDataSplit[cell];
                                        break;
                                    case 4 :
                                        parsedAgentData[rowNumber]['minutes_ready'] = parseInt(tmpAgentDataSplit[cell].split(':')[0]);
                                        parsedAgentData[rowNumber]['seconds_ready'] = parseInt(tmpAgentDataSplit[cell].split(':')[1]);
                                        break;
                                    default :
                                        break;
                                }
                                colNumber++;
                            }

                            function agentIsReady(v) {
                                return v['ready_status'] === 'READY';
                            }

                            let parsedAgentDataFiltered = parsedAgentData.filter(agentIsReady);
                            let userCounts = new Array();
                            for (let i = 0; i < 16; i++) {
                                userCounts[i] = 0;
                            }
                            $(parsedAgentDataFiltered).each(function (i, v) {
                                let s = parseInt((v['minutes_ready'] * 60) + (v['seconds_ready']));
                                if (s > 14) {
                                    userCounts[15] = userCounts[15] + 1;
                                } else {
                                    userCounts[s] = userCounts[s] + 1;
                                }
                            });
                            parsedAgentDataFiltered.sort((a, b) => (a['seconds_ready'] > b['seconds_ready']) ? 1 : -1);
                            agentDataOutput = '<table class="tightTable pct100">';
                            let secondsRow = '<thead><tr><th class="secondsRow" title="Wait time - In Seconds\nHow long the agents have been waiting for a call.">Wait Time</th>';
                            let countsRow = '<tbody><tr><td class="countsRow" title="Number of agents waiting for a call the specified wait seconds."># Agents</td>';
                            for (let i = 0; i < 16; i++) {
                                if (i === 15) {
                                    secondsRow += '<th class="secondsRow ' + applyAgentsThresh(i, userCounts[i], cltype) + '">' + i.toString() + '+</th>';
                                } else {
                                    secondsRow += '<th class="secondsRow ' + applyAgentsThresh(i, userCounts[i], cltype) + '">' + i.toString() + '</th>';
                                }
                                countsRow += '<td class="countsRow ' + applyAgentsThresh(i, userCounts[i], cltype) + '">' + userCounts[i] + '</td>';
                            }
                            secondsRow += '</tr></thead>';
                            countsRow += '</tr></tbody>';
                            agentDataOutput += secondsRow + countsRow + '</table>';
                        } else {
                            agentDataOutput = '';
                        }
                        let tdLabels = [];
                        let tdValues = [];
                        let clusterValues = [];
                        let summaryValues = [];
                        let noCalls = false;
                        let noAgents = false;
                        $(clusterData).find('TD').each(function (i, n) {
                            if (i === 0 || (i % 2) === 0) {
                                tdLabels.push(n.innerText.trim());
                            } else {
                                tdValues.push(n.innerText.trim());
                            }
                        });
                        tdLabels.pop();
                        tdValues.pop();
                        $.each(tdValues, function (i, v) {
                            switch (i) {
                                case 1:
                                    clusterValues['trunk_short'] = v.split('/')[0].trim();
                                    clusterValues['trunk_fill'] = v.split('/')[1].trim();
                                    break;
                                case 8:
                                    clusterValues['hopper_min'] = v.split('/')[0].trim();
                                    clusterValues['hopper_auto'] = v.split('/')[1].trim();
                                    break;
                                case 9:
                                    clusterValues['dropped'] = parseInt(v.split('/')[0].trim());
                                    clusterValues['answered'] = v.split('/')[1].trim();
                                    break;
                                default:
                                    clusterValues[clusterDataFields[i]] = v.trim();
                                    break;
                            }
                        });
                        if (summaryData.length > 8) {
                            noCalls = summaryData.includes('NO LIVE CALLS');
                            noAgents = summaryData.includes('NO AGENTS ON CALLS');
                            if (noAgents && noCalls) {
                                // handling the edge case for NO AGENTS ON CALLS or NO LIVE CALLS by loading up all 0s
                                $.each(clusterSummaryFields, function (i) {
                                    summaryValues[clusterSummaryFields[i]] = '0';
                                });
                            } else {
                                $(summaryData).find('font').each(function (i, n) {
                                    summaryValues[clusterSummaryFields[i]] = n.innerText.trim();
                                });
                                if (noCalls) {
                                    summaryValues['calls_active'] = '0';
                                    summaryValues['calls_ringing'] = '0';
                                    summaryValues['calls_waiting'] = '0';
                                    summaryValues['calls_ivr'] = '0';
                                }
                                if (noAgents) {
                                    summaryValues['agents_on'] = '0';
                                    summaryValues['agents_active'] = '0';
                                    summaryValues['agents_waiting'] = '0';
                                    summaryValues['agents_paused'] = '0';
                                    summaryValues['agents_dead'] = '0';
                                    summaryValues['agents_dispo'] = '0';
                                }
                                summaryValues.pop();
                                delete summaryValues['undefined'];
                            }
                        }

                        function loadClusterAssessment(c) {
                            let out = "?ADD=";
                            if(clusterInfo[c]['sel_campaigns'].length > 0 && clusterInfo[c]['sel_campaigns'][0].groups !== 'ALL-ACTIVE') {
                                out += '31&campaign_id=' + encodeURIComponent(clusterInfo[c]['sel_campaigns'][0].groups);
                            } else {
                                out += '10';
                            }
                            return out;
                        }

                        let objClusterData = Object.assign({}, clusterValues);
                        let objSummaryData = Object.assign({}, summaryValues);
                        let $newLayout = $('<table class="clusterDataTable"><tbody></tbody></table>');
                        $newLayout.tooltip();
                        if (tdValues.length > 1) {
                            $newLayout.append('<tr><td class="align_left">Server Time: </td><td class="clusterTime align_right">' + objClusterData.time + '</td></tr>');
                            if (cltype === 'cold') {
                                $newLayout.append('<tr title="Dialer Level: ' + objClusterData.dial_level + '&#10;Dialable Leads: ' + objClusterData.dialable_leads + '"><td class="align_left">Dialer:</td><td class="pct25 align_right">' + objClusterData.dial_level + ' - ' + applyThresh(objClusterData.dialable_leads, 2000, 5000) + ' leads</td></tr>');
                                //$newLayout.append('<tr title="Trunk Short: ' + objClusterData.trunk_short + '&#10;Trunk Fill: ' + objClusterData.trunk_fill + '"><td class="align_left">Trunk:</td><td class="pct25 align_right">' + objClusterData.trunk_short + ' / ' + objClusterData.trunk_fill + '</td></tr>');
                                $newLayout.append('<tr title="Hopper Min: ' + objClusterData.hopper_min + '&#10;Hopper Auto: ' + objClusterData.hopper_auto + '&#10;Leads in Hopper: ' + objClusterData.hopper_leads + '"><td class="align_left">Hopper:</td><td class="align_right">' + objClusterData.hopper_min + ' / ' + objClusterData.hopper_auto + ' - ' + applyThresh(objClusterData.hopper_leads, 2000, 5000) + ' leads</td></tr>');
                            } else if (cltype === 'taps') {
                                $newLayout.append('<tr title="Dialer Level: ' + objClusterData.dial_level + '&#10;Dialable Leads: ' + objClusterData.dialable_leads + '"><td class="align_left">Dialer:</td><td class="pct25 align_right">' + objClusterData.dial_level + ' - ' + applyThresh(objClusterData.dialable_leads, 2000, 5000) + ' leads</td></tr>');
                                //$newLayout.append('<tr title="Trunk Short: ' + objClusterData.trunk_short + '&#10;Trunk Fill: ' + objClusterData.trunk_fill + '"><td class="align_left">Trunk:</td><td class="pct25 align_right">' + objClusterData.trunk_short + ' / ' + objClusterData.trunk_fill + '</td></tr>');
                                $newLayout.append('<tr title="Hopper Min: ' + objClusterData.hopper_min + '&#10;Hopper Auto: ' + objClusterData.hopper_auto + '&#10;Leads in Hopper: ' + objClusterData.hopper_leads + '"><td class="align_left">Hopper:</td><td class="align_right">' + objClusterData.hopper_min + ' / ' + objClusterData.hopper_auto + ' - ' + applyThresh(objClusterData.hopper_leads, 2000, 5000) + ' leads</td></tr>');
                            }
                            $newLayout.append('<tr title="Calls Today: ' + objClusterData.calls_today + '&#10;Calls Dropped: ' + objClusterData.dropped + '&#10;Drop Rate: ' + objClusterData.dropped_pct + '&#10;Calls Answered: ' + objClusterData.answered + '"><td class="align_left">Stats:</td><td nowrap class="pct50 align_right">' + objClusterData.calls_today + ' / ' + objClusterData.dropped + ' (' + objClusterData.dropped_pct + ') / ' + objClusterData.answered + '</td></tr>');
                            $newLayout.append('<tr title="Average Customer Wait: ' + objClusterData.avg_agent_wait + 's&#10;Average Customer Time: ' + objClusterData.avg_cust_time + 's&#10;Average ACW: ' + objClusterData.avg_acw + 's&#10;Average Pause: ' + objClusterData.avg_pause + 's"><td class="align_left">Wait/Time/ACW/Pause:</td><td class="align_right">' + objClusterData.avg_agent_wait + ' / ' + objClusterData.avg_cust_time + ' / ' + objClusterData.avg_acw + ' / ' + objClusterData.avg_pause + '</td></tr>');
                            if (objSummaryData.calls_active !== undefined) {
                                $newLayout.append('<tr title="Active Calls: ' + objSummaryData.calls_active + '&#10;Calls Ringing: ' + objSummaryData.calls_ringing + '&#10;Calls Waiting: ' + objSummaryData.calls_waiting + '&#10;Interactive Voice Response: ' + objSummaryData.calls_ivr + '"><td class="align_left">Calls/Ring/Wait/IVR:</td><td class="align_right">' + objSummaryData.calls_active + ' / ' + objSummaryData.calls_ringing + ' / ' + objSummaryData.calls_waiting + ' / ' + objSummaryData.calls_ivr + '</td></tr>');
                                $newLayout.append('<tr title="Agents Logged In: ' + objSummaryData.agents_on + '&#10;Agents On Calls: ' + objSummaryData.agents_active + '&#10;Agents Waiting: ' + objSummaryData.agents_waiting + '&#10;Agents Paused: ' + objSummaryData.agents_paused + '&#10;Agents Dead: ' + objSummaryData.agents_dead + '&#10;Agents Dispo: ' + objSummaryData.agents_dispo + '"><td class="align_left">Agts/IC/W/P/Dd/Dsp:</td><td class="align_right">' + objSummaryData.agents_on + ' / ' + objSummaryData.agents_active + ' / ' + objSummaryData.agents_waiting + ' / ' + objSummaryData.agents_paused + ' / ' + objSummaryData.agents_dead + ' / ' + objSummaryData.agents_dispo + '</td></tr>');
                            }
                            $newLayout.append('<tr><td class="align_left" title="Order the lists/leads are being processed/dialed in">List Order:</td><td class="pct50 align_right">' + objClusterData.order + '</td></tr>');
                            $newLayout.append('<tr class="agentInfo" style="vertical-align:bottom;"><td colspan="2" class="pct_100 align_center">' + agentDataOutput + '</td></tr>');
                            $newLayout.append('<tr style="height:35px;vertical-align:bottom;"><td colspan="2" class="pct100 align_center"><button title="Select Filters for this Cluster" id="selectClusterFilters_' + tile_id + '" class="selectFiltersButton align_center ui-button-text-only">Filters</button><button title="Load in ViciDial" id="loadCluster_' + tile_id + '" class="loadClusterButton align_center ui-button-text-only"><a target="_blank" href="http://' + tileDefs[tile_id].web_ip + '/vicidial/admin.php' + loadClusterAssessment(tileDefs[tile_id].cluster_id) + '">Load</a></button><button title="View Cluster Details" class="ui-button-text-only align_center"><a target="_blank" href="http://' + tileDefs[tile_id].web_ip + '/vicidial/realtime_report.php">Details<a></button></td></tr>');
                            $newLayout.append('<tr style="height:35px;vertical-align:bottom;"><td colspan="2" class="pct100 align_center"><button title="Stop Dialing for this Cluster" id="stopDialersButton_' + tile_id + '" class="stopDialersButton align_center ui-button-text-only">Stop Dialer</button><button title="Force Hopper Reset for this Cluster" class="forceHopperButton ui-button-text-only align_center" id="forceHopperButton_' + tile_id + '">Force Hopper</button></td></tr>');
                        } else {
                            $newLayout.append(tbl);
                        }
                        return $newLayout;
                    }

                    function parseDialerStatusData(tileID, dialerStatusData) {
                        let titleRow = '<div class="clusterTitle" title="Click to rename">' + tileDefs[tileID].name + '<a id="removeCluster_' + tileID + '" class="removeClusterButton" title="Remove this Cluster">[x]</a></div>';
                        let $tile = $('#tile_' + tileID);
                        $tile.empty();
                        $tile.append(titleRow);
                        let prsdData = parseTable(tileID, dialerStatusData);
                        $tile.append(prsdData);
                    }

                    function getDialerStatusData() {
                        if (frontEnd_debug) {
                            console.log('Tiles are about to render :: ', tileDefs);
                        }
                        // NOTE - all these selectedClusters instances will need to be 0-based and incremented
                        $.each(tileDefs, function (i, v) {
                            if ($('li#tile_' + i.toString()).length === 0) {
                                $('#dialerStatusZone').append('<li id="tile_' + i.toString() + '" class="clusterTile"><span class="centerMessage">Loading data, standby...</span></li>');
                            }
                            $.ajax({
                                type: 'POST',
                                cache: false,
                                async: false,
                                dataType: 'json',
                                contentType: 'application/x-www-form-urlencoded',
                                crossDomain: false,
                                crossOrigin: false,
                                url: 'api/api.php?get=dialer_status&mode=json&action=getClusterDataByUserPrefs&c=' + v.cluster_id.toString(),
                                success: function (response) {
                                    parseDialerStatusData(i, response);
                                },
                                error: function (response) {
                                    console.log('FAILURE - ' + response);
                                }
                            });
                        });
                        if ($('li#tile_add').length === 0) {
                            $('#dialerStatusZone').append(tileAdder);
                        }
                        applyUniformity();
                        if (highContrast) {
                            $('body').css('background-color', '#000000');
                            $('body').css('color', '#FFFFFF');

                            $('#main_content').css('background-color', '#000000');
                            $('#main_content').css('color', '#FFFFFF');


                            $('#dialerStatusZone').css('background-color', '#000000');
                            $('.clusterTile').css('background-color', 'black');
                            $('button#switchContrast').button('option', 'label', 'Light Mode');
                        }
                        if (refreshEnabled) {
                            $('button#refreshRateButton').button('option', 'label', 'Change Refresh [' + refreshInterval + ']');
                            clearInterval(dispTimer);
                            dispTimer = setInterval(getDialerStatusData, (refreshInterval * 1000));
                        } else {
                            $('button#refreshRateButton').button('option', 'label', 'Change Refresh [OFF]');
                            clearInterval(dispTimer);
                        }
                    }

                    initScreen();
                    loadUserPrefs();
                    getDialerStatusData();
                });
            </script>
            <?
        }
    }
