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
                $this->clusterInfo[$v]['campaigns'] = getClusterCampaigns($v);
                $this->clusterInfo[$v]['user_groups'] = getClusterUserGroups($v);
                $this->clusterInfo[$v]['sel_campaigns'] = getClusterCampaigns($v);
                $this->clusterInfo[$v]['sel_user_groups'] = getClusterUserGroups($v);
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
            <div id="dialog-modal-select-clusters" title="Cluster selection" class="nod"></div>
            <div id="dialog-modal-change-refresh" title="Modify refresh rate" class="nod"></div>
            <div id="dialog-modal-cluster-filters" title="Filters" class="nod"></div>
            <div id="dialog-modal-first-confirm" title="Confirmation Required" class="nod"></div>
            <div id="dialog-modal-second-confirm" title="Confirmation Required" class="nod"></div>
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
                        <tr>
                            <td class="align_left"><label for="loadPrefs">Load User Preferences :</label></td>
                            <td class="align_right"><input type="checkbox" id="loadPrefs" name="loadPrefs" checked /></td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <script>
                $('#dialerStatusZone').ready(function () {

                        var refreshInterval = 4;
                        var refreshEnabled = true;
                        dispTimer = false;
                        var clusterInfo = <?=json_encode($this->clusterInfo);?>;
                        var availableClusters = <?=json_encode($this->availableClusterIDs);?>;
                        var selectedClusters = <?=json_encode($this->availableClusterIDs);?>;
                        var dlgObj = {};
                        var highContrast = false;
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
                                    if ($('#savePrefs').is(':checked')) {
                                        saveUserPrefs();
                                    }
                                    $(this).dialog('close');
                                    $('#dialerStatusZone').empty();
                                    initScreen();
                                    getDialerStatusData();
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
                                'Save': function (e) {
                                    refreshInterval = $('#refreshRate').val();
                                    refreshEnabled = !$('#refreshEnabled').is(':checked');
                                    if (!refreshEnabled) {
                                        $('#refreshRateButton').find('.ui-button-text').text('Change Refresh [OFF]');
                                    } else {
                                        $('#refreshRateButton').find('.ui-button-text').text('Change Refresh [' + refreshInterval + ']');
                                        getDialerStatusData();
                                    }
                                    dlgObj.dialog('close');
                                },
                                'Cancel': function () {
                                    dlgObj.dialog('close');
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
                                    if (dispTimer) {
                                        clearTimeout(dispTimer);
                                    }
                                    let clusterid = $(this).data('cluster_id');
                                    let objTmp = {};
                                    clusterInfo[clusterid]['sel_campaigns'] = [];
                                    $('#campaignFilter option:selected').each(function (i, v) {
                                        objTmp['groups'] = v.innerText;
                                        clusterInfo[clusterid]['sel_campaigns'].push(objTmp);
                                    });
                                    clusterInfo[clusterid]['sel_user_groups'] = [];
                                    $('#usergroupFilter option:selected').each(function (i, v) {
                                        objTmp['user_group_filter'] = v.innerText;
                                        clusterInfo[clusterid]['sel_user_groups'].push(objTmp);
                                    });
                                    if ($('#savePrefs').is(':checked')) {
                                        saveUserPrefs();
                                    }
                                    $(this).dialog('close');
                                    $('#dialerStatusZone').empty();
                                    initScreen();
                                    getDialerStatusData();
                                },
                                'Cancel': function () {
                                    $(this).dialog('close');
                                }
                            },
                            position: 'center'
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
                            autoOpen: true,
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
                                    if ($('#loadPrefs').is(':checked')) {
                                        loadUserPrefs();
                                    }
                                    $(this).dialog('close');
                                }
                            }
                        });

                        function loadUserPrefs() {
                            $.ajax({
                                type: "GET",
                                cache: false,
                                async: false,
                                crossDomain: false,
                                crossOrigin: false,
                                url: 'api/api.php?get=dialer_status&mode=json&action=loadUserPrefs',
                                success: function (prefs) {
                                    if (prefs.length) {
                                        let tmpJSON = JSON.parse(prefs);
                                        $.each(prefs, function (i, v) {
                                            let tmpCLID = v.cluster_id;
                                            let tmpGroups = v.groups;
                                            let tmpUserGroups = v.usergroups;
                                            let objTmp = {};
                                            refreshInterval = v.refreshInterval;
                                            refreshEnabled = v.refreshEnabled;
                                            selectedClusters = [];
                                            selectedClusters.push(tmpCLID);
                                            clusterInfo[tmpCLID]['sel_campaigns'] = [];
                                            $(tmpGroups).each(function (j, w) {
                                                objTmp['groups'] = w;
                                                clusterInfo[tmpCLID]['sel_campaigns'].push(objTmp);
                                            });
                                            clusterInfo[tmpCLID]['sel_user_groups'] = [];
                                            $(tmpUserGroups).each(function (j, w) {
                                                objTmp['user_group_filter'] = w;
                                                clusterInfo[tmpCLID]['sel_user_groups'].push(objTmp);
                                            });
                                        });
                                        alert('User Preferences loaded');
                                    }
                                }
                            });
                        }

                        function saveUserPrefs() {
                            let tmpJSON = {};
                            tmpJSON.refreshInterval = refreshInterval;
                            tmpJSON.refreshEnabled = refreshEnabled;
                            let tmpGroups = [];
                            let tmpUserGroups = [];
                            $.each(selectedClusters, function (i, v) {
                                tmpJSON[i] = [];
                                $.each(clusterInfo[v]['sel_campaigns'], function (j, w) {
                                    tmpGroups.push(w.groups);
                                });
                                $.each(clusterInfo[v]['sel_user_groups'], function (j, w) {
                                    tmpUserGroups.push(w.user_group_filter);
                                });
                                tmpJSON[i].push({
                                    cluster_id : v,
                                    groups : tmpGroups,
                                    usergroups : tmpUserGroups
                                });
                            });
                            let tmpPrefs = JSON.stringify(tmpJSON);
                            $.ajax({
                                type: "POST",
                                cache: false,
                                async: false,
                                crossDomain: false,
                                crossOrigin: false,
                                url: 'api/api.php?get=dialer_status&mode=json&action=saveUserPrefs&prefs=' + tmpPrefs,
                                success: function() {
                                    alert('User Preferences saved');
                                }
                            });
                        }

                        function initScreen() {
                            $.each(selectedClusters, function (i, v) {
                                $('#dialerStatusZone').append('<li id="clusterTile_' + v + '" class="clusterTile"></li>');
                            });
                            $('#dialerStatusZone').sortable();
                            $('#dialerStatusZone').disableSelection();
                            $('#dialerStatusZone').sortable({
                                cancel: '#clusterTileAdder'
                            });
                            $('#clusterSelectButton').on('click', function (e, ui) {
                                dlgObj = $('#dialog-modal-select-clusters');
                                let clusterSelect = '<select class="align_left" name="clusterSelection" id="clusterSelection" multiple size="6">';
                                $.each(availableClusters, function (i, v) {
                                    clusterSelect += '<option value="' + v + '">' + clusterInfo[v]['name'] + '</option>';
                                });
                                clusterSelect += '</select>';
                                dlgObj.dialog('open');
                                dlgObj.html('<table class="pct100 tightTable"><tbody><tr><td class="align_left"><label for="clusterSelection">Select Cluster(s) : </label></td><td class="align_right">' + clusterSelect + '</td></tr><tr><td class="align_left"><label for="savePrefs">Save User Preferences : </label></td><td class="align_right"><input type="checkbox" name="savePrefs" id="savePrefs" checked /></td></tr></tbody></table>');
                                $('#clusterSelection').val(selectedClusters);
                            });
                            applyUniformity();
                        }

                        $('#refreshRateButton').on('click', function (e, ui) {
                            dlgObj = $('#dialog-modal-change-refresh');
                            dlgObj.dialog('open');
                            dlgObj.html('<table class="pct100 tightTable"><tr><td class="align_left"><label for="refreshRate">Refresh (seconds) : </label><td class="align_right"><input id="refreshRate" name="refreshRate" type="number" min="4" max="300" value="' + refreshInterval + '" /></td></tr><tr><td class="align_left"><label for="refreshEnabled">Disable refresh : </label><td class="align_right"><input id="refreshEnabled" name="refreshEnabled" type="checkbox"' + (refreshEnabled ? '' : ' checked') + ' /></td></tr></table>');
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
                                $(this).button('option', 'label', 'Normal Mode');
                                highContrast = true;
                            } else {
                                $('body').css('background-color', '#FFFFFF');
                                $('body').css('color', '#000000');
                                $('#dialerStatusZone').css('background-color', '#FFFFFF');
                                $('.clusterTile').css('background-color', 'navy');
                                $(this).button('option', 'label', 'Dark Mode');
                                highContrast = false;
                            }
                        });
                        $('#dialerStatusZone').on('click', '.selectFiltersButton', function () {
                            let clid = $(this).closest('button').attr('id').split('_')[1];
                            dlgObj = $('#dialog-modal-cluster-filters');
                            dlgObj.data('cluster_id', clid);
                            dlgObj.dialog('open');
                            dlgObj.dialog({title: 'Change Cluster Filters - ' + clusterInfo[clid]['name']});
                            let campaignSelect = '<select name="groups" id="campaignFilter" multiple size="6">';
                            $.each(clusterInfo[clid]['campaigns'], function (i, v) {
                                campaignSelect += '<option>' + v.groups + '</option>';
                            });
                            campaignSelect += '</select>';
                            let ugSelect = '<select name="user_group_filter" id="usergroupFilter" multiple size="8">';
                            $.each(clusterInfo[clid]['user_groups'], function (i, v) {
                                ugSelect += '<option>' + v.user_group_filter + '</option>';
                            });
                            ugSelect += '</select>';
                            dlgObj.html('<table class="pct100 tightTable"><tr><td class="align_left"><label for="filterCampaigns">Select Campaign(s) : </label></td><td class="align_right">' + campaignSelect + '</td></tr><tr><td class="align_left"><label for="usergroupFilter">Select User Group(s) : </label></td><td class="align_right">' + ugSelect + '</td></tr><tr><td class="align_left"><label for="savePrefs">Save User Preferences : </label></td><td class="align_right"><input type="checkbox" name="savePrefs" id="savePrefs" checked /></td></tr></table>');
                            let arrSelTemp = [];
                            $.each(clusterInfo[clid]['sel_campaigns'], function (i, v) {
                                arrSelTemp.push(v.groups);
                            });
                            $('#campaignFilter').val(arrSelTemp);
                            arrSelTemp = [];
                            $.each(clusterInfo[clid]['sel_user_groups'], function (i, v) {
                                arrSelTemp.push(v.user_group_filter);
                            });
                            $('#usergroupFilter').val(arrSelTemp);
                        });

                        $('#dialerStatusZone').on('click', '.removeClusterButton', function () {
                            let clid = $(this).closest('button').attr('id').split('_')[1];
                            $('#clusterTile_' + clid).remove();
                            let i = $.inArray(parseInt(clid), selectedClusters);
                            if (i !== -1) {
                                selectedClusters.splice(i, 1);
                            }
                            $('#dialerStatusZone').empty();
                            initScreen();
                            getDialerStatusData();
                        });

                        $('#dialerStatusZone').on('click', '.stopDialersButton', function () {
                            let clid = $(this).closest('button').attr('id').split('_')[1];
                            stopDialers(clid);
                        });

                        $('#dialerStatusZone').on('click', '.forceHopperButton', function () {
                            let clid = $(this).closest('button').attr('id').split('_')[1];
                            forceHopper(clid);
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
                            } else {
                                $.ajax({
                                    type: "POST",
                                    cache: false,
                                    async: false,
                                    crossDomain: false,
                                    crossOrigin: false,
                                    url: 'api/api.php?get=dialer_status&mode=json&action=stopDialer&clusterid=' + clid
                                });
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
                            } else {
                                $.ajax({
                                    type: "POST",
                                    cache: false,
                                    async: false,
                                    crossDomain: false,
                                    crossOrigin: false,
                                    url: 'api/api.php?get=dialer_status&mode=json&action=forceHopperReset&clusterid=' + clid
                                });
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
                            let clusterData = '<html>' + tbl.split('</FORM>')[0];
                            let summaryData = '<html>' + tbl.split('</FORM>')[1];
                            let tdLabels = [];
                            let tdValues = [];
                            let clusterValues = [];
                            let summaryValues = [];
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
                                if (!summaryData.includes('NO AGENTS ON CALLS') && !clusterData.includes('NO AGENTS ON CALLS') && !summaryData.includes('NO LIVE CALLS') && !clusterData.includes('NO LIVE CALLS')) {
                                    $(summaryData).find('font').each(function (i, n) {
                                        summaryValues[clusterSummaryFields[i]] = n.innerText.trim();
                                    });
                                    summaryValues.pop();
                                    delete summaryValues['undefined'];
                                } else {
                                    // handling the edge case for NO AGENTS ON CALLS or NO LIVE CALLS by loading up all 0s
                                    $.each(clusterSummaryFields, function (i) {
                                        summaryValues[clusterSummaryFields[i]] = '0';
                                    });
                                }
                            }
                            let objClusterData = Object.assign({}, clusterValues);
                            let objSummaryData = Object.assign({}, summaryValues);
                            let $titleBar = $('#clusterTile_' + clid).find('.clusterTitle');
                            let closeButton = '<button id="removeCluster_' + clid + '" class="removeClusterButton ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only" role="button" aria-disabled="false" title="Remove this Cluster"><span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span></button>';
                            $titleBar.append(closeButton);
                            let $newLayout = $('<table class="clusterDataTable"><tbody></tbody></table>');
                            $newLayout.tooltip();
                            if (tdValues.length > 1) {
                                $newLayout.append('<tr><td class="align_left">Server Time: </td><td class="clusterTime align_right">' + objClusterData.time + '</td></tr>');
                                if (cltype === 'cold') {
                                    $newLayout.append('<tr title="Dialer Level: ' + objClusterData.dial_level + '&#10;Dialable Leads: ' + objClusterData.dialable_leads + '&#10;Trunk Short: ' + objClusterData.trunk_short + '&#10;Trunk Fill: ' + objClusterData.trunk_fill + '"><td class="align_left">Dialer:</td><td class="pct25 align_right">' + objClusterData.dial_level + ' - ' + applyThresh(objClusterData.dialable_leads, 2000, 5000) + ' leads (Trunk: ' + objClusterData.trunk_short + ' / ' + objClusterData.trunk_fill + ')</td></tr>');
                                    $newLayout.append('<tr title="Hopper Min: ' + objClusterData.hopper_min + '&#10;Hopper Auto: ' + objClusterData.hopper_auto + '&#10;Leads in Hopper: ' + objClusterData.hopper_leads + '"><td class="align_left">Hopper:</td><td class="align_right">' + objClusterData.hopper_min + ' / ' + objClusterData.hopper_auto + ' - ' + applyThresh(objClusterData.hopper_leads, 2000, 5000) + ' leads</td></tr>');
                                }
                                if (cltype === 'taps') {
                                    $newLayout.append('<tr title="Dialer Level: ' + objClusterData.dial_level + '&#10;Dialable Leads: ' + objClusterData.dialable_leads + '&#10;Trunk Short: ' + objClusterData.trunk_short + '&#10;Trunk Fill: ' + objClusterData.trunk_fill + '"><td class="align_left">Dialer:</td><td class="pct25 align_right">' + objClusterData.dial_level + ' - ' + applyThresh(objClusterData.dialable_leads, 200, 500) + ' leads (Trunk: ' + objClusterData.trunk_short + ' / ' + objClusterData.trunk_fill + ')</td></tr>');
                                    $newLayout.append('<tr title="Hopper Min: ' + objClusterData.hopper_min + '&#10;Hopper Auto: ' + objClusterData.hopper_auto + '&#10;Leads in Hopper: ' + objClusterData.hopper_leads + '"><td class="align_left">Hopper:</td><td class="align_right">' + objClusterData.hopper_min + ' / ' + objClusterData.hopper_auto + ' - ' + applyThresh(objClusterData.hopper_leads, 2000, 5000) + ' leads</td></tr>');
                                }
                                $newLayout.append('<tr title="Calls Today: ' + objClusterData.calls_today + '&#10;Calls Dropped: ' + objClusterData.dropped + '&#10;Drop Rate: ' + objClusterData.dropped_pct + '&#10;Calls Answered: ' + objClusterData.answered + '"><td class="align_left">Stats:</td><td class="pct50 align_right">' + objClusterData.calls_today + ' / ' + objClusterData.dropped + ' (' + objClusterData.dropped_pct + ') / ' + objClusterData.answered + '</td></tr>');
                                $newLayout.append('<tr title="Average Customer Wait: ' + objClusterData.avg_agent_wait + 's&#10;Average Customer Time: ' + objClusterData.avg_cust_time + 's&#10;Average ACW: ' + objClusterData.avg_acw + 's&#10;Average Pause: ' + objClusterData.avg_pause + 's"><td class="align_left">Wait/Time/ACW/Pause:</td><td class="align_right">' + objClusterData.avg_agent_wait + ' / ' + objClusterData.avg_cust_time + ' / ' + objClusterData.avg_acw + ' / ' + objClusterData.avg_pause + '</td></tr>');
                                if (objSummaryData.calls_active !== undefined) {
                                    $newLayout.append('<tr title="Active Calls: ' + objSummaryData.calls_active + '&#10;Calls Ringing: ' + objSummaryData.calls_ringing + '&#10;Calls Waiting: ' + objSummaryData.calls_waiting + '&#10;Interactive Voice Response: ' + objSummaryData.calls_ivr + '"><td class="align_left">Calls/Ring/Wait/IVR:</td><td class="align_right">' + objSummaryData.calls_active + ' / ' + objSummaryData.calls_ringing + ' / ' + objSummaryData.calls_waiting + ' / ' + objSummaryData.calls_ivr + '</td></tr>');
                                    $newLayout.append('<tr title="Agents Logged In: ' + objSummaryData.agents_on + '&#10;Agents On Calls: ' + objSummaryData.agents_active + '&#10;Agents Waiting: ' + objSummaryData.agents_waiting + '&#10;Agents Paused: ' + objSummaryData.agents_paused + '&#10;Agents Dead: ' + objSummaryData.agents_dead + '&#10;Agents Dispo: ' + objSummaryData.agents_dispo + '"><td class="align_left">Agts/IC/W/P/Dd/Dsp:</td><td class="align_right">' + objSummaryData.agents_on + ' / ' + objSummaryData.agents_active + ' / ' + objSummaryData.agents_waiting + ' / ' + objSummaryData.agents_paused + ' / ' + objSummaryData.agents_dead + ' / ' + objSummaryData.agents_dispo + '</td></tr>');
                                }
                                $newLayout.append('<tr style="height:35px;vertical-align:bottom;"><td colspan="2" class="pct100 align_center"><button title="Select Filters for this Cluster" id="selectClusterFilters_' + clid + '" class="selectFiltersButton align_center ui-button-text-only">Filters</button><button title="Load in ViciDial" id="loadCluster_' + clid + '" class="loadClusterButton align_center ui-button-text-only"><a target="_blank" href="http://' + clusterInfo[clid]['ip'] + '/vicidial/admin.php?ADD=10">Load</a></button><button title="View Cluster Details" class="ui-button-text-only align_center"><a target="_blank" href="http://' + clusterInfo[clid]['ip'] + '/vicidial/realtime_report.php">Details<a></button></td></tr>');
                                $newLayout.append('<tr style="height:35px;vertical-align:bottom;"><td colspan="2" class="pct100 align_center"><button title="Stop Dialing for this Cluster" id="stopDialersButton_' + clid + '" class="stopDialersButton align_center ui-button-text-only">Stop Dialer</button><button title="Force Hopper Reset for this Cluster" class="forceHopperButton ui-button-text-only align_center" id="forceHopperButton_' + clid + '">Force Hopper</button></td></tr>');
                            } else {
                                $newLayout.append(tbl);
                            }
                            return $newLayout;
                        }

                        function parseDialerStatusData(clusterID, dialerStatusData) {
                            let titleRow = '<div class="clusterTitle">' + clusterInfo[clusterID]['name'] + '</div>';
                            let $tile = $('#clusterTile_' + clusterID);
                            $tile.empty();
                            $tile.append(titleRow);
                            let prsdData = parseTable(clusterID, clusterInfo[clusterID]['type'], dialerStatusData);
                            $tile.append(prsdData);
                        }

                        function getDialerStatusData() {
                            $.each(selectedClusters, function (i, v) {
                                let tmpGroups = '';
                                $.each(clusterInfo[v]['sel_campaigns'], function (j, w) {
                                    tmpGroups += '&groups[]=' + w.groups;
                                });
                                let tmpUserGroups = '';
                                $.each(clusterInfo[v]['sel_user_groups'], function (j, w) {
                                    tmpUserGroups += '&usergroup[]=' + w.user_group_filter;
                                });
                                $.ajax({
                                    type: "POST",
                                    cache: false,
                                    async: false,
                                    crossDomain: true,
                                    crossOrigin: true,
                                    url: 'api/api.php?get=dialer_status&mode=json&action=getClusterData&webip=' + clusterInfo[v]['ip'] + tmpGroups + tmpUserGroups,
                                    success: function (response) {
                                        parseDialerStatusData(v, response);
                                    },
                                    error: function (response) {
                                        console.log('FAILURE - ' + response);
                                    }
                                });
                            });
                            if (refreshEnabled) {
                                dispTimer = setTimeout(function () {
                                    getDialerStatusData();
                                    applyUniformity();
                                }, (refreshInterval * 1000));
                            }
                        }

                        initScreen();
                        getDialerStatusData();
                    }
                )
                ;
            </script>
            <table class="pct100 tightTable">
                <tr>
                    <td class="ht40 pad_left ui-widget-header">
                        <table class="pct100 tightTable">
                            <tr>
                                <td class="pct100">
                                    <div class="align_center" style="float:left;margin:7px;">Dialer Status Dashboard</div>
                                    <button type="button" id="clusterSelectButton" class="align_center ui-state-highlight" style="float:right;">Select Clusters</button>
                                    <button type="button" id="refreshRateButton" class="align_center refreshButton" style="float:right;">Change Refresh [4]</button>
                                    <button type="button" id="stopDialersButton" class="align_center ui-state-error" style="float:right;">Stop All Dialing</button>
                                    <button type="button" id="forceHopperButton" class="align_center" style="float:right;">Force Hopper</button>
                                    <button type="button" id="switchContrast" class="align_center" style="float:right;" value="Dark Mode" onclick="">Dark Mode</button>
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
            <?
        }
    }
