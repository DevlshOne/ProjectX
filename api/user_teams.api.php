<?php

    class API_UserTeams {
        var $xml_parent_tagname = "Userteams";
        var $xml_record_tagname = "Userteam";
        var $json_parent_tagname = "ResultSet";
        var $json_record_tagname = "Result";
        var $debug = false;

        function handleAPI() {
            if (!checkAccess('users')) {
                $_SESSION['api']->errorOut('Access denied to User Groups');
                return;
            }
            switch ($_REQUEST['action']) {
                case 'delete':
                    $teamid = intval($_REQUEST['id']);
                    $q = "UPDATE user_teams SET status='deleted' WHERE id = $teamid";
                    $res = query($q, 3);
                    $_SESSION['api']->outputDeleteSuccess();
                    break;
                case 'view':
                    $id = intval($_REQUEST['id']);
                    $row = $_SESSION['dbapi']->user_teams->getByID($id);
                    ## BUILD XML OUTPUT
                    $out = "<" . $this->xml_record_tagname . " ";
                    foreach ($row as $key => $val) {
                        if ($key == 'password') continue;
                        $out .= $key . '="' . htmlentities($val) . '" ';
                    }
                    $out .= " >\n";
                    $out .= "</" . $this->xml_record_tagname . ">";
                    echo $out;
                    break;
                case 'edit':
                    $id = intval($_POST['adding_user_group']);
                    unset($dat);
                    $dat['vici_cluster_id'] = intval($_POST['vici_cluster_id']);
                    $dat['name'] = trim($_POST['name']);
                    $dat['office'] = trim($_POST['office']);
                    if ($id) {
                        $_SESSION['dbapi']->aedit($id, $dat, $_SESSION['dbapi']->user_teams->table);
                        logAction('edit', 'user_teams', $id, "");
                    } else {
                        $dat['user_team'] = trim($_POST['user_team']);
                        $_SESSION['dbapi']->aadd($dat, $_SESSION['dbapi']->user_teams->table);
                        $id = mysqli_insert_id($_SESSION['dbapi']->db);
                        logAction('add', 'user_teams', $id, "");
                    }
                    $_SESSION['api']->outputEditSuccess($id);
                    break;
                case 'getTeamMembers':
                    $teamid = intval($_REQUEST['team']);
                    $res = fetchAllAssoc("SELECT utm.`user_id`, utm.`username`, CONCAT(UCASE(u.first_name), ' ', UCASE(u.last_name)) AS fullname FROM user_teams_members AS utm INNER JOIN users AS u ON u.id = utm.user_id WHERE utm.team_id = $teamid ORDER BY utm.`username` ASC", 1);
                    $out = json_encode($res);
                    echo $out;
                    break;
                case 'getUserGroups':
                    $res = fetchAllAssoc("SELECT DISTINCT(`user_group`) AS `group_name`, `id` FROM user_groups_master GROUP BY `user_group` ORDER BY `user_group` ASC", 1);
                    $out = json_encode($res);
                    echo $out;
                    break;
                case 'getGroupUserList':
                    $groupname = strtoupper(trim($_REQUEST['group']));
                    if (empty($groupname)) {
                        $q = "SELECT ugt.user_id, UPPER(u.username) AS username, CONCAT(UCASE(u.first_name), ' ', UCASE(u.last_name)) AS fullname FROM user_group_translations AS ugt INNER JOIN users AS u ON ugt.user_id = u.id WHERE (u.username IS NOT NULL) AND u.enabled = 'yes' GROUP BY ugt.user_id ORDER BY u.username ASC";
                    } else {
                        // empty case vs populated
                        $q = "SELECT ugt.user_id, UPPER(u.username) AS username, CONCAT(UCASE(u.first_name), ' ', UCASE(u.last_name)) AS fullname FROM user_group_translations AS ugt INNER JOIN users AS u ON ugt.user_id = u.id WHERE (u.username IS NOT NULL) AND u.enabled = 'yes' AND UPPER(ugt.group_name) = '" . $groupname . "' GROUP BY ugt.user_id ORDER BY u.username ASC";
                    }
                    $res = fetchAllAssoc($q, 3);
                    $out = json_encode($res);
                    echo $out;
                    break;
                case 'getUserList':
                    $username = strtoupper(trim($_REQUEST['user']));
                    $q = "SELECT ugt.user_id, UPPER(u.username) AS username, CONCAT(UCASE(u.first_name), ' ', UCASE(u.last_name)) AS fullname FROM user_group_translations AS ugt INNER JOIN users AS u ON ugt.user_id = u.id WHERE (u.username LIKE '%" . $username . "%') AND u.enabled = 'yes' GROUP BY ugt.user_id ORDER BY u.username ASC";
                    $res = fetchAllAssoc($q, 3);
                    $out = json_encode($res);
                    echo $out;
                    break;
                case 'addTeamMember':
                    $teamid = intval($_REQUEST['team']);
                    $userid = intval($_REQUEST['userid']);
                    $q = "INSERT INTO user_teams_members (team_id, user_id, username) SELECT $teamid, $userid, users.username AS username FROM users WHERE users.id = $userid";
                    
                    execSQL($q);
                    
                    $_SESSION['api']->outputEditSuccess($teamid);
                    
                    break;
                case 'addNewTeam':
                    $teamname = trim($_REQUEST['name']);
                    $q = "INSERT INTO user_teams (`status`, `team_name`) VALUES ('enabled', '" . $teamname . "')";
                    
					execSQL($q);
					$teamid = mysqli_insert_id($_SESSION['db']);
					
					$_SESSION['api']->outputEditSuccess($teamid);
					
                    break;
                case 'removeTeamMember':
                    $teamid = intval($_REQUEST['team']);
                    $userid = intval($_REQUEST['userid']);
                    $q = "DELETE FROM user_teams_members WHERE team_id = $teamid AND user_id = $userid";
                    execSQL($q);
                    
                    $_SESSION['api']->outputEditSuccess($teamid);
                    break;
                case 'changeTeamName':
                    $teamid = intval($_REQUEST['team']);
                    $teamname = trim($_REQUEST['name']);
                    $q = "UPDATE user_teams SET team_name = '" . $teamname . "' WHERE id = $teamid";
                    execSQL($q);
                    
                    $_SESSION['api']->outputEditSuccess($teamid);
                    break;
                default:
                case 'list':
                    $dat = array();
                    $totalcount = 0;
                    $pagemode = false;
                    ## TEAM NAME SEARCH
                    if ($_REQUEST['s_team_name']) {
                        $dat['team_name'] = trim($_REQUEST['s_team_name']);
                    }
                    ## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
                    if (isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])) {
                        $pagemode = true;
                        $cntdat = $dat;
                        $cntdat['fields'] = 'COUNT(`id`)';
                        list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->user_teams->getResults($cntdat));
                        $dat['limit'] = array("offset" => intval($_REQUEST['index']), "count" => intval($_REQUEST['pagesize']));
                    }
                    ## ORDER BY SYSTEM
                    if ($_REQUEST['orderby'] && $_REQUEST['orderdir']) {
                        $dat['order'] = array($_REQUEST['orderby'] => $_REQUEST['orderdir']);
                    }
                    $dat['fields'] = "`team_name`, `id`";
                    $res = $_SESSION['dbapi']->user_teams->getResults($dat);
                    ## OUTPUT FORMAT TOGGLE
                    switch ($_SESSION['api']->mode) {
                        default:
                        case 'xml':
                            ## GENERATE XML
                            if ($pagemode) {
                                $out = '<' . $this->xml_parent_tagname . " totalcount=\"" . intval($totalcount) . "\">\n";
                            } else {
                                $out = '<' . $this->xml_parent_tagname . ">\n";
                            }
                            $out .= $_SESSION['api']->renderResultSetXML($this->xml_record_tagname, $res);
                            $out .= '</' . $this->xml_parent_tagname . ">";
                            break;
                        ## GENERATE JSON
                        case 'json':
                            $out = '[' . "\n";
                            $out .= $_SESSION['api']->renderResultSetJSON($this->json_record_tagname, $res);
                            $out .= ']' . "\n";
                            break;
                    }
                    ## OUTPUT DATA!
                    echo $out;
            }
        }

        function handleSecondaryAjax() {
            $out_stack = array();
            foreach ($_REQUEST['special_stack'] as $idx => $data) {
                $tmparr = preg_split("/:/", $data);
                if ($this->debug) print_r($tmparr);
                switch ($tmparr[1]) {
                    default:
                        ## ERROR
                        $out_stack[$idx] = -1;
                        break;
                    case 'num_users':
                        if ($tmparr[2] <= 0) {
                            $out_stack[$idx] = '-';
                        } else {
                            $q = "SELECT COUNT(`user_id`) FROM `user_teams_members` WHERE `team_id` = '" . intval($tmparr[2]) . "' ";
                            if ($this->debug) echo $q . PHP_EOL;
                            list($out_stack[$idx]) = $_SESSION['dbapi']->queryROW($q);
                        }
                        break;
                }
                ## END SWITCH
            }
            $out = $_SESSION['api']->renderSecondaryAjaxXML('Data', $out_stack);
            if ($this->debug) print_r($out);
            echo $out;
        } ## END HANDLE SECONDARY AJAX
    }