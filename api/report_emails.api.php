<?


class API_ReportEmails
{

    var $xml_parent_tagname = "Reports";
    var $xml_record_tagname = "Report";

    var $json_parent_tagname = "ResultSet";
    var $json_record_tagname = "Result";


    function handleAPI()
    {


        if (!checkAccess('report_emails')) {


            $_SESSION['api']->errorOut('Access denied to Report Emails');

            return;
        }

//		if($_SESSION['user']['priv'] < 5){
//
//
//			$_SESSION['api']->errorOut('Access denied to non admins.');
//
//			return;
//		}

        switch ($_REQUEST['action']) {
            case 'delete':

                $id = intval($_REQUEST['id']);

                //$row = $_SESSION['dbapi']->campaigns->getByID($id);


                $_SESSION['dbapi']->report_emails->delete($id);

                logAction('delete', 'report_emails', $id, "");


                $_SESSION['api']->outputDeleteSuccess();


                break;

            case 'view':


                $id = intval($_REQUEST['id']);

                $row = $_SESSION['dbapi']->report_emails->getByID($id);


                ## BUILD XML OUTPUT
                $out = "<" . $this->xml_record_tagname . " ";

                foreach ($row as $key => $val) {


                    $out .= $key . '="' . htmlentities($val) . '" ';

                }

                $out .= " />\n";


                ///$out .= "</".$this->xml_record_tagname.">";

                echo $out;


                break;
            case 'bulk_add':


                /**
                 * Array
                 * (
                 * [bulk_adding_emails] =>
                 * [report_id] => 1
                 * [template_id] => Array
                 * (
                 * [0] => 1
                 * [1] => 3
                 * )
                 *
                 * // SALES ANAL FIELDS
                 * [combine_users] => on
                 * [sales_cluster_id] => 7
                 * // VERIFIER FIELDS
                 * [verifier_cluster_id] => 9
                 * // SUMMAR FIELDS
                 * [summary_report_type] => cold
                 * // ROUSTER FIELDS
                 * [rouster_cluster_id] => 1
                 *
                 *
                 *
                 * [email_address] => test
                 * [user_groups] => Array
                 * (
                 * [0] => 90-editing
                 * [1] => 92-Renewals
                 * [2] => 92-Training
                 * )
                 *
                 * [combined_group_report] => on
                 * )*/

                //print_r($_POST);
                //exit;
                $reptype = '';
                $report_id = intval($_POST['report_id']);
                $email_to = filterEmail(trim($_POST['email_address']));

                foreach ($_POST['template_id'] as $template_id) {

                    $template = $_SESSION['dbapi']->report_emails_templates->getByID($template_id);

                    $reptype = '';

                    // BUILD RECORD TO BE INSERTED
                    $dat = array();
                    $dat['enabled'] = 'yes';
                    $dat['report_id'] = $report_id;
                    $dat['email_address'] = $email_to;
                    $dat['interval'] = $template['interval'];
                    $dat['trigger_time'] = $template['trigger_time'];

                    // CALCULATE LAST RAN!
                    $calculated_last_ran_time = 0;

                    switch ($dat['interval']) {
                        case 'daily':
                        default:

                            // CURRENT DAY AT 0:00 AM PLUS THE DAY OFFSET (75600 = 9pm for example), minus 24 hours, for yesterdays time
                            $calculated_last_ran_time = (mktime(0, 0, 0) + $dat['trigger_time']) - 86400;


                            break;
                        case 'weekly':

                            $diw = date("w");

                            // GET TODAYS TIME, from 00:00:00
                            $tmptime = mktime(0, 0, 0);

                            // SUBTRACT DAY OFFSET, TO GET BEGINNING OF WEEK
                            $tmptime -= ($diw * 86400);

                            // SAVE THIS FOR LATER
                            $startofweek = $tmptime;

                            // APPLY TIME OFFSET
                            $tmptime += $dat['trigger_time'];

                            $calculated_last_ran_time = $tmptime - 604800;


                            break;
                        case 'monthly':

                            // GET FIRST DAY OF THE MONTH, FOR LAST MONTH
                            $calculated_last_ran_time = mktime(0, 0, 0, date("m") - 1, 1, date("Y")) + $dat['trigger_time'];

                            break;
                    }


                    $dat['last_ran'] = $calculated_last_ran_time;

                    $dat['settings'] = '';

                    $dat['json_settings'] = '';
                    $j_tmp = new stdClass();

                    $process_groups = true;

                    switch ($report_id) {
                        case 1:
                        default:
                            $saleclusterid = intval($_POST['sales_cluster_id']);
                            $saleclusterid = ($saleclusterid <= 0) ? -1 : $saleclusterid;
                            $dat['settings'] .= '$agent_cluster_idx = ' . $saleclusterid . ";\n";
                            $j_tmp->agent_cluster_idx = $saleclusterid;
                            if ($_POST['combine_users'] != 'on') {
                                $dat['settings'] .= '$combine_users = 0;' . "\n";
                                $j_tmp->combine_users = 0;
                            } else {
                                $dat['settings'] .= '$combine_users = 1;' . "\n";
                                $j_tmp->combine_users = 1;
                            }
                            $dat['json_settings'] = json_encode($j_tmp);
                            break;
                        case 2 :
                            $verifierclusterid = intval($_POST['verifier_cluster_id']);
                            $verifierclusterid = ($verifierclusterid <= 0) ? -1 : $verifierclusterid;
                            $dat['settings'] .= '$cluster_id = ' . $verifierclusterid . ";\n";
                            $j_tmp->cluster_id = $verifierclusterid;
                            $dat['json_settings'] = json_encode($j_tmp);
                            break;
                        case 3:
                            $process_groups = false;
                            $reptype = trim($_POST['summary_report_type']);
                            switch (strtolower($reptype)) {
                                default:
                                case 'cold':
                                    $reptype = 'cold';
                                    break;
                                case 'taps':
                                    $reptype = 'taps';
                                    break;
                                case 'verifier':
                                    $reptype = 'verifier';
                                    break;
                                case 'company':
                                    $reptype = 'company';

                                    break;
                                case 'roustercompany':
                                    $reptype = 'roustercompany';
                                    break;
                            }

                            $dat['settings'] .= '$report_type = \'' . $reptype . "';\n";
                            $j_tmp->report_type = $reptype;
                            $dat['json_settings'] = json_encode($j_tmp);
                            break;
                        case 4:
                            $rousterclusterid = intval($_POST['rouster_cluster_id']);
                            $rousterclusterid = ($rousterclusterid <= 0) ? -1 : $rousterclusterid;
                            $dat['settings'] .= '$cluster_id = ' . $rousterclusterid . ";\n";
                            $j_tmp->cluster_id = $rousterclusterid;
                            $dat['json_settings'] = json_encode($j_tmp);
                            break;
                    }

                    /**
                     * SALES/VERIFIER/Rouster Reports all use this path
                     */
                    if ($process_groups) {
                        // COMBINE TO SINGLE RECORD
                        if (strtolower(trim($_POST['combined_group_report'])) == 'on') {

                            $dat['settings'] .= '$user_group = array(';

                            //"'..'"
                            $x = 0;

                            $tmpstr = '';
                            foreach ($_POST['user_groups'] as $usergroup) {
                                if ($x++ > 0) $tmpstr .= ',';
                                $tmpstr .= '"' . addslashes($usergroup) . '"';
                                $j_tmp->user_group[] = $usergroup;
                            }

                            $dat['settings'] .= $tmpstr . ');' . "\n";
                            $dat['json_settings'] = json_encode($j_tmp);

                            $dat['subject_append'] = 'Combined Groups(' . $tmpstr . ')';

                            $_SESSION['dbapi']->aadd($dat, $_SESSION['dbapi']->report_emails->table);
                            $id = mysqli_insert_id($_SESSION['dbapi']->db);


                            logAction('add', 'report_emails', $id, "Subject_append=" . $dat['subject_append']);


                        } else {

                            $base_settings = $dat['settings'];


                            foreach ($_POST['user_groups'] as $usergroup) {

                                $dat['subject_append'] = $usergroup;

                                $dat['settings'] = $base_settings . '$user_group = "' . addslashes($usergroup) . '";' . "\n";

                                $j_tmp->user_group[] = $usergroup;
                                $dat['json_settings'] = json_encode($j_tmp);

                                $_SESSION['dbapi']->aadd($dat, $_SESSION['dbapi']->report_emails->table);
                                $id = mysqli_insert_id($_SESSION['dbapi']->db);


                                logAction('bulk_add', 'report_emails', $id, "Subject_append=" . $dat['subject_append']);

                            }

                        }

                        // SUMMARY REPORT ONLY, DOESN'T LOOP GROUPS
                    } else {
                        if ($reptype != 'roustercompany')
                            $dat['subject_append'] = ucfirst($reptype);
                        else
                            $dat['subject_append'] = 'Sub-Company Rousters';


                        $_SESSION['dbapi']->aadd($dat, $_SESSION['dbapi']->report_emails->table);
                        $id = mysqli_insert_id($_SESSION['dbapi']->db);


                        logAction('add', 'report_emails', $id, "Subject_append=" . $dat['subject_append']);


                    }

                }

                $_SESSION['api']->outputEditSuccess(1);

                break;

            case 'edit':
                $id = intval($_POST['adding_report']);
                unset($dat);
                $j_tmp = new stdClass();
                $dat['subject_append'] = trim($_POST['subject_append']);
                $dat['email_address'] = trim($_POST['email_address']);
                $dat['interval'] = trim($_POST['interval']);
                $dat['settings'] = trim($_POST['settings']);
                $dat['report_id'] = intval($_POST['report_id']);

                // 1 - Sales Analysis [agent_cluster_id, combine_users, user_group]
                // 2 - Verifier Report [cluster_id, user_group]
                // 3 - Summary Report [report_type]
                // 4 - Rouster Report [cluster_id, user_group]
                switch($dat['report_id']) {
                    default :
                        break;
                    case 1 :
                        $j_tmp->combine_users = trim($_POST['combine_users']) == 1 ? '1' : '0';
                        $j_tmp->user_group = trim($_POST['user_groups']);
                        $j_tmp->agent_cluster_idx = trim($_POST['agent_cluster_idx']);
                    break;
                    case 2 :
                    case 4 :
                        $j_tmp->user_group = trim($_POST['user_groups']);
                        $j_tmp->cluster_id = trim($_POST['cluster_id']);
                    break;
                    case 3 :
                        $j_tmp->report_type = trim($_POST['summary_report_type']);
                    break;
            }
                $calculated_last_ran_time = 0;
                $dat['json_settings'] = json_encode($j_tmp);
                switch ($dat['interval']) {
                    case 'daily':
                    default:

                        $dat['trigger_time'] = intval($_POST['trigger_time']);


                        // CURRENT DAY AT 0:00 AM PLUS THE DAY OFFSET (75600 = 9pm for example), minus 24 hours, for yesterdays time
                        $calculated_last_ran_time = (mktime(0, 0, 0) + $dat['trigger_time']) - 86400;


                        break;
                    case 'weekly':

                        $diw = intval($_REQUEST['day_of_week_offset']);

                        $dat['trigger_time'] = ($diw * 86400) + intval($_POST['trigger_time']);


                        $diw = date("w");

                        // GET TODAYS TIME, from 00:00:00
                        $tmptime = mktime(0, 0, 0);

                        // SUBTRACT DAY OFFSET, TO GET BEGINNING OF WEEK
                        $tmptime -= ($diw * 86400);

                        // SAVE THIS FOR LATER
                        $startofweek = $tmptime;

                        // APPLY TIME OFFSET
                        $tmptime += $dat['trigger_time'];

                        $calculated_last_ran_time = $tmptime;// - 604800;


                        break;
                    case 'monthly':


                        $dim = intval($_REQUEST['day_of_the_month_offset']);
                        $dim--;

                        $dim = ($dim < 0) ? 0 : $dim;

                        $dat['trigger_time'] = ($dim * 86400) + intval($_POST['trigger_time']);


                        // GET FIRST DAY OF THE MONTH, FOR LAST MONTH
                        $calculated_last_ran_time = mktime(0, 0, 0, date("m") - 1, 1, date("Y")) + $dat['trigger_time'];

                        break;
                }
                if (!$id || $_REQUEST['fix_last_ran_time']) {
                    // CALCULATE THE "last_ran" TIME, TO MAKE IT START AT THE PROPER TIME (IMPORTANT FOR WEEKLY AND MONTHLY MOSTLY)
                    $dat['last_ran'] = $calculated_last_ran_time;
                }
                if ($id) {
                    $_SESSION['dbapi']->aedit($id, $dat, $_SESSION['dbapi']->report_emails->table);
                    logAction('edit', 'report_emails', $id, "Name=" . $dat['name']);
                } else {
                    // ADDING A NEW REPORT_EMAIL RECORD
                    $_SESSION['dbapi']->aadd($dat, $_SESSION['dbapi']->report_emails->table);
                    $id = mysqli_insert_id($_SESSION['dbapi']->db);
                    logAction('add', 'report_emails', $id, "Subject_append=" . $dat['subject_append']);
                }
                $_SESSION['api']->outputEditSuccess($id);
                break;
            default:
            case 'list':


                $dat = array();
                $totalcount = 0;
                $pagemode = false;


                ## ID SEARCH
                if ($_REQUEST['s_id']) {

                    $dat['id'] = intval($_REQUEST['s_id']);

                }

                if ($_REQUEST['s_interval']) {

                    $dat['interval'] = trim($_REQUEST['s_interval']);

                }

                if ($_REQUEST['s_report_id']) {

                    $dat['report_id'] = trim($_REQUEST['s_report_id']);

                }

                if ($_REQUEST['s_subject_append']) {

                    $dat['subject_append'] = trim($_REQUEST['s_subject_append']);

                }

                if ($_REQUEST['s_email_address']) {

                    $dat['email_address'] = trim($_REQUEST['s_email_address']);

                }


                ## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
                if (isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])) {

                    $pagemode = true;

                    $cntdat = $dat;
                    $cntdat['fields'] = 'COUNT(id)';
                    list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->report_emails->getResults($cntdat));

                    $dat['limit'] = array(
                        "offset" => intval($_REQUEST['index']),
                        "count" => intval($_REQUEST['pagesize'])
                    );

                }


                ## ORDER BY SYSTEM
                if ($_REQUEST['orderby'] && $_REQUEST['orderdir']) {
                    $dat['order'] = array($_REQUEST['orderby'] => $_REQUEST['orderdir']);
                }


                $res = $_SESSION['dbapi']->report_emails->getResults($dat);


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

            //print_r($tmparr);


            switch ($tmparr[1]) {
                default:

                    ## ERROR
                    $out_stack[$idx] = -1;

                    break;
                case 'report_name':

                    // COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
                    if ($tmparr[2] <= 0) {
                        $out_stack[$idx] = '-';
                    } else {

                        //echo "ID#".$tmparr[2];

                        list($name) = $_SESSION['dbapi']->queryROW("SELECT name FROM reports WHERE id='" . intval($tmparr[2]) . "' ");

                        $out_stack[$idx] = $name;//$_SESSION['dbapi']->voices->getName($tmparr[2]);
                    }

                    break;
                //['[get:friendly_trigger_time:interval:trigger_time]'

                case 'friendly_trigger_time':

                    if (count($tmparr) < 4 || !$tmparr[2] || !intval($tmparr[3])) {
                        $out_stack[$idx] = '-';
                    } else {
                        $interval = $tmparr[2];
                        $triggertime = $tmparr[3];


                        switch ($interval) {
                            default:
                            case 'daily':

                                $hours = floor($triggertime / 3600);

                                $out_stack[$idx] = ($hours > 12) ? ($hours - 12) . ' PM' : $hours . ' AM';

                                break;
                            case 'weekly':

                                $diw = floor($triggertime / 86400);

                                $timeoffset = $triggertime % 86400;

                                $hours = floor($timeoffset / 3600);

                                switch ($diw) {
                                    case 0:
                                    default:

                                        $out_stack[$idx] = 'Sunday @ ' . (($hours > 12) ? ($hours - 12) . ' PM' : $hours . ' AM');
                                        break;
                                    case 1:
                                        $out_stack[$idx] = 'Monday @ ' . (($hours > 12) ? ($hours - 12) . ' PM' : $hours . ' AM');
                                        break;
                                    case 2:
                                        $out_stack[$idx] = 'Tuesday @ ' . (($hours > 12) ? ($hours - 12) . ' PM' : $hours . ' AM');
                                        break;
                                    case 3:
                                        $out_stack[$idx] = 'Wednesday @ ' . (($hours > 12) ? ($hours - 12) . ' PM' : $hours . ' AM');
                                        break;
                                    case 4:
                                        $out_stack[$idx] = 'Thursday @ ' . (($hours > 12) ? ($hours - 12) . ' PM' : $hours . ' AM');
                                        break;
                                    case 5:
                                        $out_stack[$idx] = 'Friday @ ' . (($hours > 12) ? ($hours - 12) . ' PM' : $hours . ' AM');
                                        break;
                                    case 6:
                                        $out_stack[$idx] = 'Saturday @ ' . (($hours > 12) ? ($hours - 12) . ' PM' : $hours . ' AM');
                                        break;

                                }


                                break;
                            case 'monthly':

                                $dim = floor($triggertime / 86400);
                                $timeoffset = $triggertime % 86400;
                                $hours = floor($timeoffset / 3600);


                                $out_stack[$idx] = 'Day ' . ($dim + 1) . ' @ ' . (($hours > 12) ? ($hours - 12) . ' PM' : $hours . ' AM');

                                break;
                        }

                    }

                    break;


            }## END SWITCH


        }


        $out = $_SESSION['api']->renderSecondaryAjaxXML('Data', $out_stack);

        //print_r($out_stack);
        echo $out;

    } ## END HANDLE SECONDARY AJAX


}

