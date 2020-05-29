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
                $dat['company_id'] = intval($_POST['company_id']);
                $dat['rule_type'] = trim($_POST['rule_type']);
                $dat['trigger_name'] = trim($_POST['trigger_name']);
                $dat['trigger_value'] = round(floatval($_POST['trigger_value']),2);
                $dat['action'] = trim($_POST['action_type']);
                $dat['action_value'] = round(floatval($_POST['action_value']),2);
                if ($id) {
                    $_SESSION['dbapi']->aedit($id, $dat, $_SESSION['dbapi']->schedules->table);
                    logAction('edit', 'schedules', $id, "Schedule=" . $id);
                } else {
                    $_SESSION['dbapi']->aadd($dat, $_SESSION['dbapi']->companies_rules->table);
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
                if ($_REQUEST['s_trigger_value']) {
                    $dat['trigger_value'] = floatval($_REQUEST['s_trigger_value']);
                }
                if ($_REQUEST['s_action_type']) {
                    $dat['action_type'] = $_REQUEST['s_action_type'];
                }
                if ($_REQUEST['s_action_value']) {
                    $dat['action_value'] = floatval($_REQUEST['s_action_value']);
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
                case 'schedule_name':
                    if($tmparr[2] === '0') {
                        $out_stack[$idx] = 'DEFAULT';
                    } else {
                        if ($tmparr[2] <= 0) {
                            $out_stack[$idx] = '-';
                        } else {
                            list($out_stack[$idx]) = $_SESSION['dbapi']->queryROW("SELECT name AS schedule_name FROM schedules WHERE id=" . intval($tmparr[2]) . " ");
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

