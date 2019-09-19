<?

    class API_UserGroupsMaster
    {
        var $xml_parent_tagname = "User_groups_masters";
        var $xml_record_tagname = "User_groups_master";
        var $json_parent_tagname = "ResultSet";
        var $json_record_tagname = "Result";

        function handleAPI()
        {
            if (!checkAccess('users')) {
                $_SESSION['api']->errorOut('Access denied to User Groups');
                return;
            }
            switch ($_REQUEST['action']) {
                case 'delete':
                    $id = intval($_REQUEST['id']);
                    // DELETE FROM VICI, THEN FROM PX, USING THE ACTION PACKED, EDGE OF YOUR SEAT, ALL-IN-WONDER FUNCTION, delete()
                    $_SESSION['dbapi']->user_groups_master->delete($id);
                    logAction('delete', 'user_groups_master', $id, "");
                    $_SESSION['api']->outputDeleteSuccess();
                    break;
                case 'view':
                    $id = intval($_REQUEST['id']);
                    $row = $_SESSION['dbapi']->user_groups_master->getByID($id);
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
                    $id = intval($_POST['adding_user_groups_master']);
                    unset($dat);
                    $dat['group_name'] = trim($_POST['group_name']);
                    $dat['office'] = intval($_POST['office']);
                    $dat['time_shift'] = trim($_POST['time_shift']);
                    $dat['agent_type'] = trim($_POST['agent_type']);
                    $dat['company_id'] = intval($_POST['company_id']);
                    $dat['user_group'] = trim($_POST['user_group']);
                    if ($id) {
                        $_SESSION['dbapi']->aedit($id, $dat, $_SESSION['dbapi']->user_groups_master->table);
                        logAction('edit', 'user_groups_master', $id, "");
                    } else {
                        $_SESSION['dbapi']->aadd($dat, $_SESSION['dbapi']->user_groups_master->table);
                        $id = mysqli_insert_id($_SESSION['dbapi']->db);
                        logAction('add', 'user_groups_master', $id, "");
                    }
                    //	$this->syncGroupToVici($id);
                    $_SESSION['api']->outputEditSuccess($id);
                    break;
                default:
                case 'list':
                    $dat = array();
                    $totalcount = 0;
                    $pagemode = false;
                    ## USER GROUP SEARCH
                    if ($_REQUEST['s_user_group']) {
                        $dat['user_group'] = trim($_REQUEST['s_user_group']);
                    }
                    ## GROUP NAME SEARCH
                    if ($_REQUEST['s_group_name']) {
                        $dat['group_name'] = trim($_REQUEST['s_group_name']);
                    }
                    ## OFFICE SEARCH
                    if ($_REQUEST['s_office']) {
                        $dat['office'] = intval($_REQUEST['s_office']);
                    }
                    ## AGENT TYPE SEARCH
                    if ($_REQUEST['s_agent_type']) {
                        $dat['agent_type'] = trim($_REQUEST['s_agent_type']);
                    }
                    ## SHIFT SEARCH
                    if ($_REQUEST['s_time_shift']) {
                        $dat['time_shift'] = trim($_REQUEST['s_time_shift']);
                    }
                    ## COMPANY ID SEARCH
                    if ($_REQUEST['s_company_id']) {
                        $dat['company_id'] = intval($_REQUEST['s_company_id']);
                    }
                    ## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
                    if (isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])) {
                        $pagemode = true;
                        $cntdat = $dat;
                        $cntdat['fields'] = 'COUNT(id)';
                        list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->user_groups_master->getResults($cntdat));
                        $dat['limit'] = array("offset" => intval($_REQUEST['index']), "count" => intval($_REQUEST['pagesize']));
                    }
                    ## ORDER BY SYSTEM
                    if ($_REQUEST['orderby'] && $_REQUEST['orderdir']) {
                        $dat['order'] = array($_REQUEST['orderby'] => $_REQUEST['orderdir']);
                    }
                    $res = $_SESSION['dbapi']->user_groups_master->getResults($dat);
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
                        case 'json':
                            ## GENERATE JSON
                            $out = '[' . "\n";
                            $out .= $_SESSION['api']->renderResultSetJSON($this->json_record_tagname, $res);
                            $out .= ']' . "\n";
                            break;
                    }
                    ## OUTPUT DATA!
                    echo $out;
            }
        }

        function handleSecondaryAjax()
        {
            $out_stack = array();
            #print_r($_REQUEST);
            foreach ($_REQUEST['special_stack'] as $idx => $data) {
                $tmparr = preg_split("/:/", $data);
                #print_r($tmparr);
                switch ($tmparr[1]) {
                    default:
                        ## ERROR
                        $out_stack[$idx] = -1;
                        break;
                    case 'company_name':
                        if($tmparr[2] <= 0){
                            $out_stack[$idx] = '-';
                        }else{
                            list($out_stack[$idx]) = $_SESSION['dbapi']->queryROW("SELECT `name` FROM `companies` WHERE id='".intval($tmparr[2])."' ");
                        }
                        break;
                    case 'office_name':
                        if($tmparr[2] <= 0){
                            $out_stack[$idx] = '-';
                        }else{
                            list($out_stack[$idx]) = $_SESSION['dbapi']->queryROW("SELECT CONCAT(`id`,' - ',`name`) AS `office` FROM `offices` WHERE id='".intval($tmparr[2])."' ");
                        }
                        break;
                }
                ## END SWITCH
            }
            $out = $_SESSION['api']->renderSecondaryAjaxXML('Data', $out_stack);
            #print_r($out_stack);
            echo $out;
        } ## END HANDLE SECONDARY AJAX
    }
