<?


class API_Schedules
{

    var $xml_parent_tagname = "Schedules";
    var $xml_record_tagname = "Schedule";

    var $json_parent_tagname = "ResultSet";
    var $json_record_tagname = "Result";


    function handleAPI()
    {


        if (!checkAccess('schedules')) {


            $_SESSION['api']->errorOut('Access denied to Schedules');

            return;
        }


        switch ($_REQUEST['action']) {
            case 'delete':
                $id = intval($_REQUEST['id']);
                $_SESSION['dbapi']->schedules->delete($id);
                logAction('delete', 'names', $id, "");
                $_SESSION['api']->outputDeleteSuccess();
                break;
            case 'view':
                $id = intval($_REQUEST['id']);
                $row = $_SESSION['dbapi']->schedules->getByID($id);
                ## BUILD XML OUTPUT
                $out = "<" . $this->xml_record_tagname . " ";
                foreach ($row as $key => $val) {
                    $out .= $key . '="' . htmlentities($val) . '" ';
                }
                $out .= " />\n";
                ///$out .= "</".$this->xml_record_tagname.">";
                echo $out;
                break;
            case 'edit':
                $id = intval($_REQUEST['adding_schedule']);
                unset($dat);
                $dat['name'] = trim($_POST['name']);
                $dat['company_id'] = intval($_POST['company_id']);
                $dat['office_id'] = intval($_POST['office_id']);
                $dat['start_time'] = intval(($_POST['start_offset_hour'] * 3600) + ($_POST['start_offset_min'] * 60) + ($_POST['start_offset_timemode'] === 'pm' ? 43200 : 0));
                $dat['end_time'] = intval(($_POST['end_offset_hour'] * 3600) + ($_POST['end_offset_min'] * 60) + ($_POST['end_offset_timemode'] === 'pm' ? 43200 : 0));
                $dat['work_sun'] = (trim($_POST['work_sun']) === 'yes' ? 'yes' : 'no');
                $dat['work_mon'] = (trim($_POST['work_mon']) === 'yes' ? 'yes' : 'no');
                $dat['work_tues'] = (trim($_POST['work_tues']) === 'yes' ? 'yes' : 'no');
                $dat['work_wed'] = (trim($_POST['work_wed']) === 'yes' ? 'yes' : 'no');
                $dat['work_thurs'] = (trim($_POST['work_thurs']) === 'yes' ? 'yes' : 'no');
                $dat['work_fri'] = (trim($_POST['work_fri']) === 'yes' ? 'yes' : 'no');
                $dat['work_sat'] = (trim($_POST['work_sat']) === 'yes' ? 'yes' : 'no');
                if(count($_POST['user_groups[]'])) {
                    $dat['user_group'] = join(",", $_POST['user_groups[]']);
                } else {
                    $dat['user_group'] = NULL;
                }
                if ($id) {
                    $_SESSION['dbapi']->aedit($id, $dat, $_SESSION['dbapi']->schedules->table);
                    logAction('edit', 'schedules', $id, "Schedule=" . $id);
                } else {
                    $_SESSION['dbapi']->aadd($dat, $_SESSION['dbapi']->schedules->table);
                    $id = mysqli_insert_id($_SESSION['dbapi']->db);
                    logAction('add', 'schedules', $id, "Schedule=" . $id);
                }
                $_SESSION['api']->outputEditSuccess($id);
                break;
            default:
            case 'list':
                $dat = array();
                $totalcount = 0;
                $pagemode = false;
                ## COMPANY ID SEARCH
                if ($_REQUEST['s_company_id']) {
                    $dat['company_id'] = intval($_REQUEST['s_company_id']);
                }
                if ($_REQUEST['s_office_id']) {
                    $dat['office_id'] = $_REQUEST['s_office_id'];
                }
                ## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
                if (isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])) {
                    $pagemode = true;
                    $cntdat = $dat;
                    $cntdat['fields'] = 'COUNT(id)';
                    list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->schedules->getResults($cntdat));
                    $dat['limit'] = array(
                        "offset" => intval($_REQUEST['index']),
                        "count" => intval($_REQUEST['pagesize'])
                    );
                }
                ## ORDER BY SYSTEM
                if ($_REQUEST['orderby'] && $_REQUEST['orderdir']) {
                    $dat['order'] = array($_REQUEST['orderby'] => $_REQUEST['orderdir']);
                }
                $res = $_SESSION['dbapi']->schedules->getResults($dat);
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

    function handleSecondaryAjax()
    {
        $out_stack = array();
        //print_r($_REQUEST);
        foreach ($_REQUEST['special_stack'] as $idx => $data) {
            $tmparr = preg_split("/:/", $data);
//            print_r($tmparr);
            switch ($tmparr[1]) {
                default:
                    ## ERROR
                    $out_stack[$idx] = -1;
                    break;
                case 'company_name':
                    if($tmparr[2] === '0') {
                        $out_stack[$idx] = 'DEFAULT';
                    } else {
                        if ($tmparr[2] <= 0) {
                            $out_stack[$idx] = '-';
                        } else {
                            list($out_stack[$idx]) = $_SESSION['dbapi']->queryROW("SELECT name AS company_name FROM companies WHERE id=" . intval($tmparr[2]) . " ");
                        }
                    }
                    break;
                case 'office_name':
                    if($tmparr[2] === '0') {
                        $out_stack[$idx] = 'DEFAULT';
                    } else {
                        if ($tmparr[2] <= 0) {
                            $out_stack[$idx] = '-';
                        } else {
                            list($out_stack[$idx]) = $_SESSION['dbapi']->queryROW("SELECT name AS office_name FROM offices WHERE id=" . intval($tmparr[2])) . " ";
                        }
                    }
                    break;
                case 'start_offset':
                case 'end_offset' :
                    if($tmparr[2] === '0') {
                        $out_stack[$idx] = 'NONE';
                    } else {
                        if ($tmparr[2] <= 0) {
                            $out_stack[$idx] = '-';
                        } else {
                            list($out_stack[$idx]) = date("H:i A", intval($tmparr[2])) . " ";
                        }
                    }
                    break;
            } ## END SWITCH
        }
        $out = $_SESSION['api']->renderSecondaryAjaxXML('Data', $out_stack);
        //print_r($out_stack);
        echo $out;
    } ## END HANDLE SECONDARY AJAX
}

