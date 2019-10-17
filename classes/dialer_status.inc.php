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
                $this->clusterInfo[$v]['type'] = getClusterType($v);
                $this->clusterInfo[$v]['name'] = getClusterName($v);
                $this->clusterInfo[$v]['ip'] = getClusterWebHost($v);
                $this->clusterInfo[$v]['sel_campaigns'] = getClusterCampaigns($v);
                $this->clusterInfo[$v]['sel_user_groups'] = getClusterUserGroups($v);
                $this->clusterInfo[$v]['campaign_options'] = getClusterCampaigns($v);
                $this->clusterInfo[$v]['usergroup_options'] = getClusterUserGroups($v);
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
                        <!--                        <tr>-->
                        <!--                            <td class="align_left"><label for="loadPrefs">Load User Preferences :</label></td>-->
                        <!--                            <td class="align_right"><input type="checkbox" id="loadPrefs" name="loadPrefs" checked/></td>-->
                        <!--                        </tr>-->
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
                $('#dialerStatusZone').ready(function () {
                    var refreshInterval = 40;
                    var refreshEnabled = true;
                    var frontEnd_debug = false;
                    dispTimer = false;
                    var clusterInfo = <?=json_encode($this->clusterInfo);?>;
                    var availableClusters = <?=json_encode($this->availableClusterIDs);?>;
                    var selectedClusters = <?=json_encode($this->availableClusterIDs);?>;
                    if (frontEnd_debug) {
                        console.log('Initializing the variable :: ', selectedClusters);
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

                    $('#dialog-modal-select-clusters').dialog({
                        autoOpen: false,
                        width: 400,
                        modal: false,
                        draggable: false,
                        resizable: false,
                        title: 'Cluster Selection',
                        buttons: {
                            'Save': function () {
                                selectedClusters = [];
                                $('#clusterSelection option:selected').each(function () {
                                    selectedClusters.push(this.value);
                                });
                                if (frontEnd_debug) {
                                    console.log('Clusters have just been changed :: ', selectedClusters);
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
                        draggable: false,
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
                        draggable: false,
                        resizable: false,
                        title: 'Change Cluster Filters',
                        buttons: {
                            'Save': function (e) {
                                let clusterid = $(this).data('cluster_id');
                                let tmpArr = [];
                                $('#campaignFilter option:selected').each(function (i, v) {
                                    tmpArr.push({
                                        groups: v.innerText
                                    });
                                });
                                clusterInfo[clusterid]['sel_campaigns'] = tmpArr;
                                tmpArr = [];
                                $('#usergroupFilter option:selected').each(function (i, v) {
                                    tmpArr.push({
                                        user_group_filter: v.innerText
                                    });
                                });
                                clusterInfo[clusterid]['sel_user_groups'] = tmpArr;
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
                                $(this).dialog('close');
                                switch (theAction) {
                                    case 'forceHopper':
                                        forceHopper('ALL');
                                        break;
                                    case 'stopDialers':
                                        stopDialers('ALL');
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
                                $('#dialog-modal-second-confirm').dialog('open');
                            }
                        }
                    });

                    $('#dialog-modal-vici-credentials').dialog({
                        autoOpen: viciMisMatch,
                        width: 400,
                        title: 'Vici Username/Password Required',
                        modal: true,
                        draggable: false,
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
                                    selectedClusters = [];
                                    $.each(prefs, function (i, v) {
                                        let tmpCLID = v.cluster_id.toString();
                                        let tmpGroups = v.groups;
                                        let tmpUserGroups = v.user_group_filter;
                                        selectedClusters.push(tmpCLID.toString());
                                        clusterInfo[tmpCLID]['sel_campaigns'] = [];
                                        $(tmpGroups).each(function (j, w) {
                                            clusterInfo[tmpCLID]['sel_campaigns'].push({
                                                groups: w
                                            });
                                        });
                                        clusterInfo[tmpCLID]['sel_user_groups'] = [];
                                        $(tmpUserGroups).each(function (j, w) {
                                            clusterInfo[tmpCLID]['sel_user_groups'].push({
                                                user_group_filter: w
                                            });
                                        });
                                    });
                                    if (frontEnd_debug) {
                                        console.log('Prefs have just been loaded :: ', selectedClusters);
                                        console.log('User Preferences loaded');
                                    }
                                }
                            }
                        });
                    }

                    function saveUserPrefs() {
                        let tmpJSON = [];
                        $.each(selectedClusters, function (i, v) {
                            let tmpGroups = [];
                            let tmpUserGroups = [];
                            $.each(clusterInfo[v].sel_campaigns, function (j, w) {
                                tmpGroups.push(w.groups);
                            });
                            $.each(clusterInfo[v].sel_user_groups, function (j, w) {
                                tmpUserGroups.push(w.user_group_filter);
                            });
                            tmpJSON.push({
                                cluster_id: v,
                                groups: tmpGroups,
                                user_group_filter: tmpUserGroups,
                            });
                        });
                        tmpJSON.push({
                            refreshInterval: refreshInterval,
                            refreshEnabled: refreshEnabled,
                            highContrast: highContrast,
                            viciUsername: '<?=$_SESSION['user']['username'];?>',
                            viciPassword: '<?=$_SESSION['user']['vici_password'];?>'
                        });
                        let tmpPrefs = JSON.stringify(tmpJSON);

                        let prefpoststr = 'prefs=' + tmpPrefs;
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
                        refreshPositions: true,
                        stop: function (e, ui) {
                            // the sort order has been changed - now re-arrange the selectedClusters array accordingly
                            let clusterTiles = $('#dialerStatusZone').children();
                            let newTileOrder = [];
                            $.each(clusterTiles, function (i, v) {
                                newTileOrder[i] = $(v).attr('id').split('_')[1];
                            });
                            selectedClusters.sort(function (a, b) {
                                return newTileOrder.indexOf(a) - newTileOrder.indexOf(b);
                            });
                            if (frontEnd_debug) {
                                console.log('Tiles have just been sorted :: ', selectedClusters);
                            }
                            saveUserPrefs();
                        }
                    });

                    function initScreen() {
                        $('#dialerStatusZone').empty();
                    }

                    $('#clusterSelectButton').on('click', function (e, ui) {
                        dlgObj = $('#dialog-modal-select-clusters');
                        let clusterSelect = '<select class="align_left" name="clusterSelection" id="clusterSelection" multiple size="6">';
                        $.each(availableClusters, function (i, v) {
                            clusterSelect += '<option value="' + v + '">' + clusterInfo[v]['name'] + '</option>';
                        });
                        clusterSelect += '</select>';
                        dlgObj.dialog('open');
                        dlgObj.html('<table class="pct100 tightTable"><tbody><tr><td class="align_left"><label for="clusterSelection">Select Cluster(s) : </label></td><td class="align_right">' + clusterSelect + '</td></tr></tbody></table>');
                        $('#clusterSelection').val(selectedClusters);
                    });
                    $('#refreshRateButton').on('click', function (e, ui) {
                        dlgObj = $('#dialog-modal-change-refresh');
                        dlgObj.dialog('open');
                        let refreshOptions = '<option value="4">4 seconds</option><option value="10">10 seconds</option><option value="20">20 seconds</option><option value="30">30 seconds</option><option value="40">40 seconds</option><option value="60">60 seconds</option><option value="120">2 minutes</option><option value="300">5 minutes</option><option value="600">10 minutes</option><option value="1200">20 minutes</option><option value="1800">30 minutes</option><option value="3600">60 minutes</option><option value="7200">120 minutes</option>';
                        dlgObj.html('<table class="pct100 tightTable"><tr><td class="align_left"><label for="refreshRate">Refresh Rate : </label><td class="align_right"><select id="refreshRate" name="refreshRate">' + refreshOptions + '</select></td></tr><tr><td class="align_left"><label for="refreshEnabled">Disable refresh : </label><td class="align_right"><input id="refreshEnabled" name="refreshEnabled" type="checkbox"' + (refreshEnabled ? '' : ' checked') + ' /></td></tr></table>');
                        $('#refreshRate').val(refreshInterval);
                    });
                    $('#forceHopperButton').on('click', function (e, ui) {
                        dlgObj = $('#dialog-modal-first-confirm');
                        dlgObj.data('myAction', 'forceHopper');
                        dlgObj.html('<div class="firstConfirmation">This will EMPTY/ERASE ALL CALLS from the hopper, are you sure?</div>');
                        dlgObj.dialog('open');
                    });
                    $('#stopDialersButton').on('click', function (e, ui) {
                        dlgObj = $('#dialog-modal-first-confirm');
                        dlgObj.data('myAction', 'stopDialers');
                        dlgObj.html('<div class="firstConfirmation">This will stop ALL DIALING on the PRODUCTION servers, are you sure?</div>');
                        dlgObj.dialog('open');
                    });
                    $('#switchContrast').on('click', function (e, ui) {
                        if (!highContrast) {
                            $('body').css('background-color', '#000000');
                            $('body').css('color', '#FFFFFF');
                            $('#dialerStatusZone').css('background-color', '#000000');
                            $('.clusterTile').css('background-color', 'black');
                            $(this).button('option', 'label', 'Light Mode');
                            highContrast = true;
                        } else {
                            $('body').css('background-color', '#FFFFFF');
                            $('body').css('color', '#000000');
                            $('#dialerStatusZone').css('background-color', '#FFFFFF');
                            $('.clusterTile').css('background-color', 'navy');
                            $(this).button('option', 'label', 'Dark Mode');
                            highContrast = false;
                        }
                        saveUserPrefs();
                    });
                    $('#dialerStatusZone').on('click', '.selectFiltersButton', function () {
                        let clid = $(this).closest('button').attr('id').split('_')[1];
                        dlgObj = $('#dialog-modal-cluster-filters');
                        dlgObj.data('cluster_id', clid);
                        dlgObj.dialog('open');
                        dlgObj.dialog({title: 'Change Cluster Filters - ' + clusterInfo[clid]['name']});
                        let campaignSelect = '<select name="groups" id="campaignFilter" multiple size="6"><option>ALL-ACTIVE</option>';
                        $.each(clusterInfo[clid]['campaign_options'], function (i, v) {
                            campaignSelect += '<option>' + v.groups + '</option>';
                        });
                        campaignSelect += '</select>';
                        let ugSelect = '<select name="user_group_filter" id="usergroupFilter" multiple size="8"><option>ALL-GROUPS</option>';
                        $.each(clusterInfo[clid]['usergroup_options'], function (i, v) {
                            ugSelect += '<option>' + v.user_group_filter + '</option>';
                        });
                        ugSelect += '</select>';
                        dlgObj.html('<table class="pct100 tightTable"><tr><td class="align_left"><label for="filterCampaigns">Select Campaign(s) : </label></td><td class="align_right">' + campaignSelect + '</td></tr><tr><td class="align_left"><label for="usergroupFilter">Select User Group(s) : </label></td><td class="align_right">' + ugSelect + '</td></tr></table>');
                        let arrSelTemp = [];
                        if (clusterInfo[clid]['campaign_options'].length === clusterInfo[clid]['sel_campaigns'].length) {
                            arrSelTemp.push('ALL-ACTIVE');
                            saveUserPrefs();
                        } else {
                            $.each(clusterInfo[clid]['sel_campaigns'], function (i, v) {
                                arrSelTemp.push(v.groups);
                            });
                        }
                        $('#campaignFilter').val(arrSelTemp);
                        arrSelTemp = [];
                        if (clusterInfo[clid]['usergroup_options'].length === clusterInfo[clid]['sel_user_groups'].length) {
                            arrSelTemp.push('ALL-GROUPS');
                            saveUserPrefs();
                        } else {
                            $.each(clusterInfo[clid]['sel_user_groups'], function (i, v) {
                                arrSelTemp.push(v.user_group_filter);
                            });
                        }
                        $('#usergroupFilter').val(arrSelTemp);
                    });

                    $('#dialerStatusZone').on('click', '.removeClusterButton', function () {
                        let clid = $(this).attr('id').split('_')[1].toString();
                        $('#clusterTile_' + clid).remove();
                        let i = selectedClusters.indexOf(clid);
                        if (i !== -1) {
                            selectedClusters.splice(i, 1);
                        }
                        saveUserPrefs();
                        if (frontEnd_debug) {
                            console.log('Prefs have just been saved :: ', selectedClusters);
                        }
                    });

                    $('#dialerStatusZone').on('click', '.stopDialersButton', function () {
                        let clid = $(this).closest('button').attr('id').split('_')[1];
                        dlgObj = $('#dialog-modal-cluster-action-confirm');
                        dlgObj.data('myAction', 'stopDialers');
                        dlgObj.data('clusterID', clid);
                        dlgObj.html('<div class="firstConfirmation">This will STOP all dialing for ' + clusterInfo[clid]['name'] + ', are you sure?</div>');
                        dlgObj.dialog('open');
                    });

                    $('#dialerStatusZone').on('click', '.forceHopperButton', function () {
                        let clid = $(this).closest('button').attr('id').split('_')[1];
                        dlgObj = $('#dialog-modal-cluster-action-confirm');
                        dlgObj.data('myAction', 'forceHopper');
                        dlgObj.data('clusterID', clid);
                        dlgObj.html('<div class="firstConfirmation">This will RESET the hopper for ' + clusterInfo[clid]['name'] + ', are you sure?</div>');
                        dlgObj.dialog('open');
                    });

                    $('#dialerStatusZone').on('click', '.showAgentsButton', function () {
                        let clid = $(this).closest('button').attr('id').split('_')[1];
                        let $agentData = $('#clusterTile_' + clid).find('.agentInfo');
                        $agentData.toggle();
                    });

                    function stopDialers(clid) {
                        if (clid === 'ALL') {
                            $.each(clusterInfo, function (i) {
                                $.ajax({
                                    type: "POST",
                                    cache: false,
                                    async: false,
                                    crossDomain: false,
                                    crossOrigin: false,
                                    url: 'api/api.php?get=dialer_status&mode=json&action=stopDialer&clusterid=' + i
                                });
                            });
                            alert('ALL dialers have been stopped!');
                        } else {
                            $.ajax({
                                type: "POST",
                                cache: false,
                                async: false,
                                crossDomain: false,
                                crossOrigin: false,
                                url: 'api/api.php?get=dialer_status&mode=json&action=stopDialer&clusterid=' + clid
                            });
                            alert('Dialer for Cluster ' + clid + ' is stopped!');
                        }
                    }

                    function forceHopper(clid) {
                        if (clid === 'ALL') {
                            $.each(clusterInfo, function (i) {
                                $.ajax({
                                    type: "POST",
                                    cache: false,
                                    async: false,
                                    crossDomain: false,
                                    crossOrigin: false,
                                    url: 'api/api.php?get=dialer_status&mode=json&action=forceHopperReset&clusterid=' + i
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

                    function applyThresh(v, crit, warn) {
                        if (parseInt(v) < crit) {
                            return '<span style="color:red;">' + v.toString() + '</span>';
                        }
                        if (parseInt(v) < warn) {
                            return '<span style="color:yellow;">' + v.toString() + '</span>';
                        }
                        return v;
                    }

                    function parseTable(clid, cltype, tbl) {
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

                        const rgxPre = /<pre>([\s\S]*)<\/pre>/g;
                        let clusterData = '<HTML>' + tbl.split('</FORM>')[0] + '</HTML>'.replace(rgxPre, '');
                        let summaryData = '<HTML>' + tbl.split('</FORM>')[1] + '</HTML>';

                        let preString = '<pre><font size="2">VICIDIAL: Agents Time On Calls Campaign: |ALL-ACTIVE|            2019-10-15 18:01:38\n' +
                            '+----------------+------------------------+-----------+----------+---------+------------+-------+------+------------------\n' +
                            '| STATION        | <a href="#" onclick="update_variables(\'orderby\',\'user\');">USER </a> <a href="#" onclick="update_variables(\'UidORname\',\'\');">SHOW ID </a>  INFO   | SESSIONID | STATUS   | <a href="#" onclick="update_variables(\'orderby\',\'time\');">MM:SS</a>   | <a href="#" onclick="update_variables(\'orderby\',\'campaign\');">CAMPAIGN  </a> | CALLS | HOLD | IN-GROUP \n' +
                            '+----------------+------------------------+-----------+----------+---------+------------+-------+------+------------------\n' +
                            '| <span class="lightblue"><b>IAX2/24134    </b></span> | <a href="./user_status.php?user=BJW2" target="_blank"><span class="lightblue"><b>BJW                 </b></span></a> <a href="javascript:ingroup_info(\'BJW2\',\'0\');">+</a> | <span class="lightblue"><b>8600068  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:02</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1832 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24199    </b></span> | <a href="./user_status.php?user=jnc" target="_blank"><span class="lightblue"><b>JNC                 </b></span></a> <a href="javascript:ingroup_info(\'jnc\',\'1\');">+</a> | <span class="lightblue"><b>8600056  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:03</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1834 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24194    </b></span> | <a href="./user_status.php?user=mod" target="_blank"><span class="lightblue"><b>MOD                 </b></span></a> <a href="javascript:ingroup_info(\'mod\',\'2\');">+</a> | <span class="lightblue"><b>8600061  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:01</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1974 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24133    </b></span> | <a href="./user_status.php?user=tll2" target="_blank"><span class="lightblue"><b>TLL                 </b></span></a> <a href="javascript:ingroup_info(\'tll2\',\'3\');">+</a> | <span class="lightblue"><b>8600063  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:01</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>2065 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24197    </b></span> | <a href="./user_status.php?user=scz" target="_blank"><span class="lightblue"><b>SCZ                 </b></span></a> <a href="javascript:ingroup_info(\'scz\',\'4\');">+</a> | <span class="lightblue"><b>8600070  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:03</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1645 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24184    </b></span> | <a href="./user_status.php?user=rja2" target="_blank"><span class="lightblue"><b>RJA                 </b></span></a> <a href="javascript:ingroup_info(\'rja2\',\'5\');">+</a> | <span class="lightblue"><b>8600056  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:02</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1914 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24141    </b></span> | <a href="./user_status.php?user=JRO2" target="_blank"><span class="lightblue"><b>JRO                 </b></span></a> <a href="javascript:ingroup_info(\'JRO2\',\'6\');">+</a> | <span class="lightblue"><b>8600053  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:03</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1893 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24108    </b></span> | <a href="./user_status.php?user=SAS" target="_blank"><span class="lightblue"><b>SAS                 </b></span></a> <a href="javascript:ingroup_info(\'SAS\',\'7\');">+</a> | <span class="lightblue"><b>8600061  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:01</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1564 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24102    </b></span> | <a href="./user_status.php?user=tjj" target="_blank"><span class="lightblue"><b>TJJ                 </b></span></a> <a href="javascript:ingroup_info(\'tjj\',\'8\');">+</a> | <span class="lightblue"><b>8600057  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:03</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1788 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24211    </b></span> | <a href="./user_status.php?user=BBR" target="_blank"><span class="lightblue"><b>BBR                 </b></span></a> <a href="javascript:ingroup_info(\'BBR\',\'9\');">+</a> | <span class="lightblue"><b>8600060  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:02</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1884 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24131    </b></span> | <a href="./user_status.php?user=SFB" target="_blank"><span class="lightblue"><b>SFB                 </b></span></a> <a href="javascript:ingroup_info(\'SFB\',\'10\');">+</a> | <span class="lightblue"><b>8600055  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:03</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>2279 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24139    </b></span> | <a href="./user_status.php?user=CJJ" target="_blank"><span class="lightblue"><b>CJJ                 </b></span></a> <a href="javascript:ingroup_info(\'CJJ\',\'11\');">+</a> | <span class="lightblue"><b>8600066  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:02</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1754 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24162    </b></span> | <a href="./user_status.php?user=rry2" target="_blank"><span class="lightblue"><b>RRY                 </b></span></a> <a href="javascript:ingroup_info(\'rry2\',\'12\');">+</a> | <span class="lightblue"><b>8600052  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:02</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1754 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24120    </b></span> | <a href="./user_status.php?user=jfn2" target="_blank"><span class="lightblue"><b>JFN                 </b></span></a> <a href="javascript:ingroup_info(\'jfn2\',\'13\');">+</a> | <span class="lightblue"><b>8600066  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:01</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1856 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24132    </b></span> | <a href="./user_status.php?user=tll" target="_blank"><span class="lightblue"><b>TLL                 </b></span></a> <a href="javascript:ingroup_info(\'tll\',\'14\');">+</a> | <span class="lightblue"><b>8600070  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:01</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>2095 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24171    </b></span> | <a href="./user_status.php?user=oja2" target="_blank"><span class="lightblue"><b>OJA                 </b></span></a> <a href="javascript:ingroup_info(\'oja2\',\'15\');">+</a> | <span class="lightblue"><b>8600064  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:00</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1673 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24140    </b></span> | <a href="./user_status.php?user=JRO" target="_blank"><span class="lightblue"><b>JRO                 </b></span></a> <a href="javascript:ingroup_info(\'JRO\',\'16\');">+</a> | <span class="lightblue"><b>8600051  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:01</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1937 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24106    </b></span> | <a href="./user_status.php?user=PRC2" target="_blank"><span class="lightblue"><b>PRC                 </b></span></a> <a href="javascript:ingroup_info(\'PRC2\',\'17\');">+</a> | <span class="lightblue"><b>8600075  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:00</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1753 </b></span> |\n' +
                            '| <span class="lightblue"><b>IAX2/24185    </b></span> | <a href="./user_status.php?user=RJA" target="_blank"><span class="lightblue"><b>RJA                 </b></span></a> <a href="javascript:ingroup_info(\'RJA\',\'18\');">+</a> | <span class="lightblue"><b>8600057  </b></span> | <span class="lightblue"><b>READY </b></span>   |<span class="lightblue"><b>    0:00</b></span> | <span class="lightblue"><b>VAFUS     </b></span> | <span class="lightblue"><b>1800 </b></span> |\n' +
                            '| IAX2/24105     | <a href="./user_status.php?user=emj2" target="_blank">EMJ                 </a> <a href="javascript:ingroup_info(\'emj2\',\'19\');">+</a> | 8600064   | QUEUE    |    0:44 | VAFUS      | 1890  |\n' +
                            '| <span class="thistle"><b>IAX2/24119    </b></span> | <a href="./user_status.php?user=jhn2" target="_blank"><span class="thistle"><b>JHN                 </b></span></a> <a href="javascript:ingroup_info(\'jhn2\',\'20\');">+</a> | <span class="thistle"><b>8600070  </b></span> | <span class="thistle"><b>INCALL</b></span> A |<span class="thistle"><b>    0:52</b></span> | <span class="thistle"><b>VAFUS     </b></span> | <span class="thistle"><b>1741 </b></span> |\n' +
                            '| <span class="thistle"><b>IAX2/24103    </b></span> | <a href="./user_status.php?user=tjj2" target="_blank"><span class="thistle"><b>TJJ                 </b></span></a> <a href="javascript:ingroup_info(\'tjj2\',\'21\');">+</a> | <span class="thistle"><b>8600058  </b></span> | <span class="thistle"><b>INCALL</b></span> A |<span class="thistle"><b>    0:43</b></span> | <span class="thistle"><b>VAFUS     </b></span> | <span class="thistle"><b>1842 </b></span> |\n' +
                            '| <span class="thistle"><b>IAX2/24104    </b></span> | <a href="./user_status.php?user=emj" target="_blank"><span class="thistle"><b>EMJ                 </b></span></a> <a href="javascript:ingroup_info(\'emj\',\'22\');">+</a> | <span class="thistle"><b>8600063  </b></span> | <span class="thistle"><b>INCALL</b></span> A |<span class="thistle"><b>    0:43</b></span> | <span class="thistle"><b>VAFUS     </b></span> | <span class="thistle"><b>1814 </b></span> |\n' +
                            '| <span class="thistle"><b>IAX2/24163    </b></span> | <a href="./user_status.php?user=rry" target="_blank"><span class="thistle"><b>RRY                 </b></span></a> <a href="javascript:ingroup_info(\'rry\',\'23\');">+</a> | <span class="thistle"><b>8600055  </b></span> | <span class="thistle"><b>INCALL</b></span> A |<span class="thistle"><b>    0:38</b></span> | <span class="thistle"><b>VAFUS     </b></span> | <span class="thistle"><b>1751 </b></span> |\n' +
                            '| <span class="thistle"><b>IAX2/24201    </b></span> | <a href="./user_status.php?user=mjh" target="_blank"><span class="thistle"><b>MJH                 </b></span></a> <a href="javascript:ingroup_info(\'mjh\',\'24\');">+</a> | <span class="thistle"><b>8600051  </b></span> | <span class="thistle"><b>INCALL</b></span> A |<span class="thistle"><b>    0:38</b></span> | <span class="thistle"><b>VAFUS     </b></span> | <span class="thistle"><b>1739 </b></span> |\n' +
                            '| <span class="thistle"><b>IAX2/24122    </b></span> | <a href="./user_status.php?user=ARB" target="_blank"><span class="thistle"><b>ARB                 </b></span></a> <a href="javascript:ingroup_info(\'ARB\',\'25\');">+</a> | <span class="thistle"><b>8600053  </b></span> | <span class="thistle"><b>INCALL</b></span> A |<span class="thistle"><b>    0:31</b></span> | <span class="thistle"><b>VAFUS     </b></span> | <span class="thistle"><b>1743 </b></span> |\n' +
                            '| <span class="thistle"><b>IAX2/24195    </b></span> | <a href="./user_status.php?user=mod2" target="_blank"><span class="thistle"><b>MOD                 </b></span></a> <a href="javascript:ingroup_info(\'mod2\',\'26\');">+</a> | <span class="thistle"><b>8600059  </b></span> | <span class="thistle"><b>INCALL</b></span> A |<span class="thistle"><b>    0:27</b></span> | <span class="thistle"><b>VAFUS     </b></span> | <span class="thistle"><b>1918 </b></span> |\n' +
                            '| <span class="thistle"><b>IAX2/24190    </b></span> | <a href="./user_status.php?user=njr2" target="_blank"><span class="thistle"><b>NJR                 </b></span></a> <a href="javascript:ingroup_info(\'njr2\',\'27\');">+</a> | <span class="thistle"><b>8600067  </b></span> | <span class="thistle"><b>INCALL</b></span> A |<span class="thistle"><b>    0:16</b></span> | <span class="thistle"><b>VAFUS     </b></span> | <span class="thistle"><b>1534 </b></span> |\n' +
                            '| IAX2/24116     | <a href="./user_status.php?user=MDR" target="_blank">MDR                 </a> <a href="javascript:ingroup_info(\'MDR\',\'28\');">+</a> | 8600071   |  DEAD  A |    0:00 | VAFUS      | 1408  |\n' +
                            '| <span class="thistle"><b>IAX2/24169    </b></span> | <a href="./user_status.php?user=mai2" target="_blank"><span class="thistle"><b>MAI                 </b></span></a> <a href="javascript:ingroup_info(\'mai2\',\'29\');">+</a> | <span class="thistle"><b>8600051  </b></span> | <span class="thistle"><b>INCALL</b></span> A |<span class="thistle"><b>    0:13</b></span> | <span class="thistle"><b>VAFUS     </b></span> | <span class="thistle"><b>1949 </b></span> |\n' +
                            '| <span class="thistle"><b>IAX2/24196    </b></span> | <a href="./user_status.php?user=scz2" target="_blank"><span class="thistle"><b>SCZ                 </b></span></a> <a href="javascript:ingroup_info(\'scz2\',\'30\');">+</a> | <span class="thistle"><b>8600069  </b></span> | <span class="thistle"><b>INCALL</b></span> A |<span class="thistle"><b>    0:11</b></span> | <span class="thistle"><b>VAFUS     </b></span> | <span class="thistle"><b>1722 </b></span> |\n' +
                            '| <span class="thistle"><b>IAX2/24123    </b></span> | <a href="./user_status.php?user=ARB2" target="_blank"><span class="thistle"><b>ARB                 </b></span></a> <a href="javascript:ingroup_info(\'ARB2\',\'31\');">+</a> | <span class="thistle"><b>8600054  </b></span> | <span class="thistle"><b>INCALL</b></span> A |<span class="thistle"><b>    0:10</b></span> | <span class="thistle"><b>VAFUS     </b></span> | <span class="thistle"><b>1769 </b></span> |\n' +
                            '| IAX2/24161     | <a href="./user_status.php?user=jaj" target="_blank">JAJ                 </a> <a href="javascript:ingroup_info(\'jaj\',\'32\');">+</a> | 8600053   | INCALL A |    0:08 | VAFUS      | 1770  |\n' +
                            '| IAX2/24135     | <a href="./user_status.php?user=BJW" target="_blank">BJW                 </a> <a href="javascript:ingroup_info(\'BJW\',\'33\');">+</a> | 8600069   | INCALL A |    0:08 | VAFUS      | 1913  |\n' +
                            '| IAX2/24115     | <a href="./user_status.php?user=SJR2" target="_blank">SJR                 </a> <a href="javascript:ingroup_info(\'SJR2\',\'34\');">+</a> | 8600068   |  DEAD  A |    0:01 | VAFUS      | 1963  |\n' +
                            '| IAX2/24206     | <a href="./user_status.php?user=ckr" target="_blank">CKR                 </a> <a href="javascript:ingroup_info(\'ckr\',\'35\');">+</a> | 8600066   | INCALL A |    0:06 | VAFUS      | 1742  |\n' +
                            '| IAX2/24127     | <a href="./user_status.php?user=mIr" target="_blank">MIR                 </a> <a href="javascript:ingroup_info(\'mIr\',\'36\');">+</a> | 8600059   | INCALL A |    0:05 | VAFUS      | 1864  |\n' +
                            '| IAX2/24210     | <a href="./user_status.php?user=BBR2" target="_blank">BBR                 </a> <a href="javascript:ingroup_info(\'BBR2\',\'37\');">+</a> | 8600062   | INCALL A |    0:04 | VAFUS      | 1952  |\n' +
                            '| IAX2/24200     | <a href="./user_status.php?user=mjh2" target="_blank">MJH                 </a> <a href="javascript:ingroup_info(\'mjh2\',\'38\');">+</a> | 8600055   | INCALL A |    0:04 | VAFUS      | 1713  |\n' +
                            '| IAX2/24130     | <a href="./user_status.php?user=sfb2" target="_blank">SFB                 </a> <a href="javascript:ingroup_info(\'sfb2\',\'39\');">+</a> | 8600056   | INCALL A |    0:03 | VAFUS      | 2241  |\n' +
                            '| IAX2/24138     | <a href="./user_status.php?user=CJJ2" target="_blank">CJJ                 </a> <a href="javascript:ingroup_info(\'CJJ2\',\'40\');">+</a> | 8600067   | INCALL A |    0:03 | VAFUS      | 1644  |\n' +
                            '| IAX2/24168     | <a href="./user_status.php?user=mai" target="_blank">MAI                 </a> <a href="javascript:ingroup_info(\'mai\',\'41\');">+</a> | 8600063   | INCALL A |    0:02 | VAFUS      | 1839  |\n' +
                            '| IAX2/24173     | <a href="./user_status.php?user=GRJ2" target="_blank">GRJ                 </a> <a href="javascript:ingroup_info(\'GRJ2\',\'42\');">+</a> | 8600059   | INCALL A |    0:01 | VAFUS      | 1948  |\n' +
                            '| IAX2/24191     | <a href="./user_status.php?user=njr" target="_blank">NJR                 </a> <a href="javascript:ingroup_info(\'njr\',\'43\');">+</a> | 8600068   | INCALL A |    0:01 | VAFUS      | 1561  |\n' +
                            '| IAX2/24107     | <a href="./user_status.php?user=PRC" target="_blank">PRC                 </a> <a href="javascript:ingroup_info(\'PRC\',\'44\');">+</a> | 8600074   | INCALL A |    0:01 | VAFUS      | 1756  |\n' +
                            '| IAX2/24121     | <a href="./user_status.php?user=jfn" target="_blank">JFN                 </a> <a href="javascript:ingroup_info(\'jfn\',\'45\');">+</a> | 8600065   | INCALL A |    0:01 | VAFUS      | 1875  |\n' +
                            '| IAX2/24114     | <a href="./user_status.php?user=SJR" target="_blank">SJR                 </a> <a href="javascript:ingroup_info(\'SJR\',\'46\');">+</a> | 8600067   | INCALL A |    0:00 | VAFUS      | 1991  |\n' +
                            '| IAX2/24170     | <a href="./user_status.php?user=oja" target="_blank">OJA                 </a> <a href="javascript:ingroup_info(\'oja\',\'47\');">+</a> | 8600062   | INCALL A |    0:00 | VAFUS      | 1663  |\n' +
                            '| IAX2/24207     | <a href="./user_status.php?user=ckr2" target="_blank">CKR                 </a> <a href="javascript:ingroup_info(\'ckr2\',\'48\');">+</a> | 8600065   | INCALL A |    0:00 | VAFUS      | 1698  |\n' +
                            '| IAX2/24172     | <a href="./user_status.php?user=GRJ" target="_blank">GRJ                 </a> <a href="javascript:ingroup_info(\'GRJ\',\'49\');">+</a> | 8600058   | INCALL A |    0:00 | VAFUS      | 1922  |\n' +
                            '| <span class="yellow"><b>IAX2/24202    </b></span> | <a href="./user_status.php?user=sbj" target="_blank"><span class="yellow"><b>SBJ                 </b></span></a> <a href="javascript:ingroup_info(\'sbj\',\'50\');">+</a> | <span class="yellow"><b>8600058  </b></span> | <span class="yellow"><b>PAUSED</b></span>   |<span class="yellow"><b>    4:09</b></span> | <span class="yellow"><b>VAFUS     </b></span> | <span class="yellow"><b>1742 </b></span> |\n' +
                            '| <span class="khaki"><b>IAX2/24118    </b></span> | <a href="./user_status.php?user=jhn" target="_blank"><span class="khaki"><b>JHN                 </b></span></a> <a href="javascript:ingroup_info(\'jhn\',\'51\');">+</a> | <span class="khaki"><b>8600069  </b></span> | <span class="khaki"><b>PAUSED</b></span>   |<span class="khaki"><b>    0:17</b></span> | <span class="khaki"><b>VAFUS     </b></span> | <span class="khaki"><b>1761 </b></span> |\n' +
                            '| IAX2/24160     | <a href="./user_status.php?user=jaj2" target="_blank">JAJ                 </a> <a href="javascript:ingroup_info(\'jaj2\',\'52\');">+</a> | 8600054   | DISPO    |    0:00 | VAFUS      | 1861  |\n' +
                            '| IAX2/24109     | <a href="./user_status.php?user=SAS2" target="_blank">SAS                 </a> <a href="javascript:ingroup_info(\'SAS2\',\'53\');">+</a> | 8600062   | DISPO    |    0:00 | VAFUS      | 1604  |\n' +
                            '| IAX2/24126     | <a href="./user_status.php?user=mIr2" target="_blank">MIR                 </a> <a href="javascript:ingroup_info(\'mIr2\',\'54\');">+</a> | 8600060   | DISPO    |    0:00 | VAFUS      | 1993  |\n' +
                            '| IAX2/24117     | <a href="./user_status.php?user=MDR2" target="_blank">MDR                 </a> <a href="javascript:ingroup_info(\'MDR2\',\'55\');">+</a> | 8600072   | DISPO    |    0:00 | VAFUS      | 1434  |\n' +
                            '+----------------+------------------------+-----------+----------+---------+------------+-------+------+------------------\n' +
                            '  56 agents logged in on all servers\n' +
                            '  System Load Average: 0.28 0.26 0.28  &nbsp; M\n' +
                            '\n' +
                            '  <span class="lightblue"><b>          </b></span><b> - Agent waiting for call</b>\n' +
                            '  <span class="blue"><b>          </b></span><b> - Agent waiting for call &gt; 1 minute</b>\n' +
                            '  <span class="midnightblue"><b>          </b></span><b> - Agent waiting for call &gt; 5 minutes</b>\n' +
                            '  <span class="thistle"><b>          </b></span><b> - Agent on call &gt; 10 seconds</b>\n' +
                            '  <span class="violet"><b>          </b></span><b> - Agent on call &gt; 1 minute</b>\n' +
                            '  <span class="purple"><b>          </b></span><b> - Agent on call &gt; 5 minutes</b>\n' +
                            '  <span class="khaki"><b>          </b></span><b> - Agent Paused &gt; 10 seconds</b>\n' +
                            '  <span class="yellow"><b>          </b></span><b> - Agent Paused &gt; 1 minute</b>\n' +
                            '  <span class="olive"><b>          </b></span><b> - Agent Paused &gt; 5 minutes</b>\n' +
                            '  <span class="lime"><b>          </b></span><b> - Agent in 3-WAY &gt; 10 seconds</b>\n' +
                            '  <span class="black"><b>          </b></span><b> - Agent on a dead call</b>\n' +
                            '</font></pre>';

                        let tmpAgentData = preString.match(rgxPre)[0];

                        let tmpAgentDataSplit = tmpAgentData.match(/<b>(.*?)<\/b>/g).map(function (val) {
                            return val.replace(/<\/?b>/g, '').trim();
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
                        for(let i = 0; i < 16; i++) {
                            userCounts[i] = 0;
                        }
                        $(parsedAgentDataFiltered).each(function(i, v) {
                            let s = parseInt((v['minutes_ready'] * 60) + (v['seconds_ready']));
                            if(s > 14) {
                                userCounts[15] = userCounts[15] + 1;
                            } else {
                                userCounts[s] = userCounts[s] + 1;
                            }
                        });
                        parsedAgentDataFiltered.sort((a,b) => (a['seconds_ready'] > b['seconds_ready']) ? 1 : -1);
                        let agentDataOutput = '<table class="tightTable pct100">';
                        let secondsRow = '<thead><tr><th class="secondsRow">Wait Time</th>';
                        let usersRow = '<tbody><tr><td class="countsRow">Users</td>';
                        for(let i = 0; i < 16; i++) {
                            secondsRow += '<th class="secondsRow">' + i + '</th>';
                            usersRow += '<td class="countsRow">' + userCounts[i] + '</td>';
                        }
                        secondsRow += '</tr></thead>';
                        usersRow += '</tr></tbody>';
                        agentDataOutput += secondsRow + usersRow + '</table>';
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
                                    clusterValues['dropped'] = v.split('/')[0].trim();
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
                        let objClusterData = Object.assign({}, clusterValues);
                        let objSummaryData = Object.assign({}, summaryValues);
                        let $newLayout = $('<table class="clusterDataTable"><tbody></tbody></table>');
                        $newLayout.tooltip();
                        if (tdValues.length > 1) {
                            $newLayout.append('<tr><td class="align_left">Server Time: </td><td class="clusterTime align_right">' + objClusterData.time + '</td></tr>');
                            if (cltype === 'cold') {
                                $newLayout.append('<tr title="Dialer Level: ' + objClusterData.dial_level + '&#10;Dialable Leads: ' + objClusterData.dialable_leads + '"><td class="align_left">Dialer:</td><td class="pct25 align_right">' + objClusterData.dial_level + ' - ' + applyThresh(objClusterData.dialable_leads, 2000, 5000) + ' leads</td></tr>');
                                $newLayout.append('<tr title="Trunk Short: ' + objClusterData.trunk_short + '&#10;Trunk Fill: ' + objClusterData.trunk_fill + '"><td class="align_left">Trunk:</td><td class="pct25 align_right">' + objClusterData.trunk_short + ' / ' + objClusterData.trunk_fill + '</td></tr>');
                                $newLayout.append('<tr title="Hopper Min: ' + objClusterData.hopper_min + '&#10;Hopper Auto: ' + objClusterData.hopper_auto + '&#10;Leads in Hopper: ' + objClusterData.hopper_leads + '"><td class="align_left">Hopper:</td><td class="align_right">' + objClusterData.hopper_min + ' / ' + objClusterData.hopper_auto + ' - ' + applyThresh(objClusterData.hopper_leads, 2000, 5000) + ' leads</td></tr>');
                            } else if (cltype === 'taps') {
                                $newLayout.append('<tr title="Dialer Level: ' + objClusterData.dial_level + '&#10;Dialable Leads: ' + objClusterData.dialable_leads + '"><td class="align_left">Dialer:</td><td class="pct25 align_right">' + objClusterData.dial_level + ' - ' + applyThresh(objClusterData.dialable_leads, 2000, 5000) + ' leads</td></tr>');
                                $newLayout.append('<tr title="Trunk Short: ' + objClusterData.trunk_short + '&#10;Trunk Fill: ' + objClusterData.trunk_fill + '"><td class="align_left">Trunk:</td><td class="pct25 align_right">' + objClusterData.trunk_short + ' / ' + objClusterData.trunk_fill + '</td></tr>');
                                $newLayout.append('<tr title="Hopper Min: ' + objClusterData.hopper_min + '&#10;Hopper Auto: ' + objClusterData.hopper_auto + '&#10;Leads in Hopper: ' + objClusterData.hopper_leads + '"><td class="align_left">Hopper:</td><td class="align_right">' + objClusterData.hopper_min + ' / ' + objClusterData.hopper_auto + ' - ' + applyThresh(objClusterData.hopper_leads, 2000, 5000) + ' leads</td></tr>');
                            }
                            $newLayout.append('<tr title="Calls Today: ' + objClusterData.calls_today + '&#10;Calls Dropped: ' + objClusterData.dropped + '&#10;Drop Rate: ' + objClusterData.dropped_pct + '&#10;Calls Answered: ' + objClusterData.answered + '"><td class="align_left">Stats:</td><td class="pct50 align_right">' + objClusterData.calls_today + ' / ' + objClusterData.dropped + ' (' + objClusterData.dropped_pct + ') / ' + objClusterData.answered + '</td></tr>');
                            $newLayout.append('<tr title="Average Customer Wait: ' + objClusterData.avg_agent_wait + 's&#10;Average Customer Time: ' + objClusterData.avg_cust_time + 's&#10;Average ACW: ' + objClusterData.avg_acw + 's&#10;Average Pause: ' + objClusterData.avg_pause + 's"><td class="align_left">Wait/Time/ACW/Pause:</td><td class="align_right">' + objClusterData.avg_agent_wait + ' / ' + objClusterData.avg_cust_time + ' / ' + objClusterData.avg_acw + ' / ' + objClusterData.avg_pause + '</td></tr>');
                            if (objSummaryData.calls_active !== undefined) {
                                $newLayout.append('<tr title="Active Calls: ' + objSummaryData.calls_active + '&#10;Calls Ringing: ' + objSummaryData.calls_ringing + '&#10;Calls Waiting: ' + objSummaryData.calls_waiting + '&#10;Interactive Voice Response: ' + objSummaryData.calls_ivr + '"><td class="align_left">Calls/Ring/Wait/IVR:</td><td class="align_right">' + objSummaryData.calls_active + ' / ' + objSummaryData.calls_ringing + ' / ' + objSummaryData.calls_waiting + ' / ' + objSummaryData.calls_ivr + '</td></tr>');
                                $newLayout.append('<tr title="Agents Logged In: ' + objSummaryData.agents_on + '&#10;Agents On Calls: ' + objSummaryData.agents_active + '&#10;Agents Waiting: ' + objSummaryData.agents_waiting + '&#10;Agents Paused: ' + objSummaryData.agents_paused + '&#10;Agents Dead: ' + objSummaryData.agents_dead + '&#10;Agents Dispo: ' + objSummaryData.agents_dispo + '"><td class="align_left">Agts/IC/W/P/Dd/Dsp:</td><td class="align_right">' + objSummaryData.agents_on + ' / ' + objSummaryData.agents_active + ' / ' + objSummaryData.agents_waiting + ' / ' + objSummaryData.agents_paused + ' / ' + objSummaryData.agents_dead + ' / ' + objSummaryData.agents_dispo + '</td></tr>');
                            }
                            $newLayout.append('<tr class="agentInfo" style="vertical-align:bottom;"><td colspan="2" class="pct_100 align_center">' + agentDataOutput + '</td></tr>');
                            $newLayout.append('<tr style="height:35px;vertical-align:bottom;"><td colspan="2" class="pct100 align_center"><button title="Select Filters for this Cluster" id="selectClusterFilters_' + clid + '" class="selectFiltersButton align_center ui-button-text-only">Filters</button><button title="Load in ViciDial" id="loadCluster_' + clid + '" class="loadClusterButton align_center ui-button-text-only"><a target="_blank" href="http://' + clusterInfo[clid]['ip'] + '/vicidial/admin.php?ADD=10">Load</a></button><button title="View Cluster Details" class="ui-button-text-only align_center"><a target="_blank" href="http://' + clusterInfo[clid]['ip'] + '/vicidial/realtime_report.php">Details<a></button></td></tr>');
                            $newLayout.append('<tr style="height:35px;vertical-align:bottom;"><td colspan="2" class="pct100 align_center"><button title="Stop Dialing for this Cluster" id="stopDialersButton_' + clid + '" class="stopDialersButton align_center ui-button-text-only">Stop Dialer</button><button title="Force Hopper Reset for this Cluster" class="forceHopperButton ui-button-text-only align_center" id="forceHopperButton_' + clid + '">Force Hopper</button></td></tr>');
                            $newLayout.append('<tr style="height:35px;vertical-align:bottom;"><td colspan="2" class="pct100 align_center"><button title="Show/Hide Agent Info" id="showAgentsButton_' + clid + '" class="showAgentsButton align_center ui-button-text-only">Toggle Agent Info</button></td></tr>');
                        } else {
                            $newLayout.append(tbl);
                        }
                        return $newLayout;
                    }

                    function parseDialerStatusData(clusterID, dialerStatusData) {
                        // NOTE - all these clusterID instances will need to be 0-based and incremented
                        let titleRow = '<div class="clusterTitle">' + clusterInfo[clusterID]['name'] + '<a id="removeCluster_' + clusterID + '" class="removeClusterButton" title="Remove this Cluster">[x]</a></div>';
                        let $tile = $('#clusterTile_' + clusterID);
                        $tile.empty();
                        $tile.append(titleRow);
                        let prsdData = parseTable(clusterID, clusterInfo[clusterID]['type'], dialerStatusData);
                        $tile.append(prsdData);
                    }

                    function getDialerStatusData() {
                        if (frontEnd_debug) {
                            console.log('Tiles are about to render :: ', selectedClusters);
                        }
                        // NOTE - all these selectedClusters instances will need to be 0-based and incremented
                        $.each(selectedClusters, function (i, v) {
                            let strV = v.toString();
                            if ($('li#clusterTile_' + strV).length === 0) {
                                $('#dialerStatusZone').append('<li id="clusterTile_' + strV + '" class="clusterTile"><span class="centerMessage">Loading data, standby...</span></li>');
                            }
                            $.ajax({
                                type: 'POST',
                                cache: false,
                                async: false,
                                dataType: 'json',
                                contentType: 'application/x-www-form-urlencoded',
                                crossDomain: false,
                                crossOrigin: false,
                                url: 'api/api.php?get=dialer_status&mode=json&action=getClusterDataByUserPrefs&c=' + strV,
                                success: function (response) {
                                    parseDialerStatusData(v, response);
                                },
                                error: function (response) {
                                    console.log('FAILURE - ' + response);
                                }
                            });
                        });
                        applyUniformity();
                        if (highContrast) {
                            $('body').css('background-color', '#000000');
                            $('body').css('color', '#FFFFFF');
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
