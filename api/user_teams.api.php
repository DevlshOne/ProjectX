<?php

    class API_UserTeams {
        var $xml_parent_tagname = "Userteams";
        var $xml_record_tagname = "Userteam";
        var $json_parent_tagname = "ResultSet";
        var $json_record_tagname = "Result";
        function handleAPI() {
            if (!checkAccess('users')) {
                $_SESSION['api']->errorOut('Access denied to User Groups');
                return;
            }
            switch ($_REQUEST['action']) {
                case 'delete':
                    $id = intval($_REQUEST['id']);
                    // DELETE FROM VICI, THEN FROM PX, USING THE ACTION PACKED, EDGE OF YOUR SEAT, ALL-IN-WONDER FUNCTION, delete()
                    $_SESSION['dbapi']->user_teams->delete($id);
                    logAction('delete', 'user_teams', $id, "");
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
                default:
                case 'list':
                $dat = array();
                $totalcount = 0;
                $pagemode = false;
                ## GROUP NAME
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
//                    $dat['fields'] = "DISTINCT(team_id) as id, team_id";
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
                switch ($tmparr[1]) {
                    default:
                        ## ERROR
                        $out_stack[$idx] = -1;
                        break;
                    case 'cluster_name':
                        // vici_cluster_id
                        if ($tmparr[2] <= 0) {
                            $out_stack[$idx] = '-';
                        } else {
                            $out_stack[$idx] = getClusterName($tmparr[2]);
                        }
                        break;
                }## END SWITCH
            }
            $out = $_SESSION['api']->renderSecondaryAjaxXML('Data', $out_stack);
            echo $out;
        } ## END HANDLE SECONDARY AJAX
    }