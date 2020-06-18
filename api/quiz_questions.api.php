<?php


class API_Questions
{

    var $xml_parent_tagname = "Questions";
    var $xml_record_tagname = "Question";

    var $json_parent_tagname = "ResultSet";
    var $json_record_tagname = "Result";


    function handleAPI()
    {


        if (!checkAccess('quiz_questions')) {


            $_SESSION['api']->errorOut('Access denied to Quiz Questions');

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


                $_SESSION['dbapi']->quiz_questions->delete($id);

                logAction('delete', 'quiz_questions', $id, "");


                $_SESSION['api']->outputDeleteSuccess();


                break;

            case 'view':


                $id = intval($_REQUEST['id']);

                $row = $_SESSION['dbapi']->quiz_questions->getByID($id);


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

                $id = intval($_POST['adding_question']);


                unset($dat);
                $dat['quiz_id'] = intval($_POST['quiz_id']);
                $dat['question'] = trim($_POST['question']);
                $dat['answer'] = trim($_POST['answer']);
                $dat['file'] = trim($_POST['file']);


                if ($id) {

                    $_SESSION['dbapi']->aedit($id, $dat, $_SESSION['dbapi']->quiz_questions->table);

                    logAction('edit', 'quiz_questions', $id, "Question=" . $dat['question']);

                } else {


                    $_SESSION['dbapi']->aadd($dat, $_SESSION['dbapi']->quiz_questions->table);
                    $id = mysqli_insert_id($_SESSION['dbapi']->db);


                    logAction('add', 'quiz_questions', $id, "Question=" . $dat['question']);
                }


                $_SESSION['api']->outputEditSuccess($id);


                break;

            default:
            case 'list':


                $dat = array();
                $totalcount = 0;
                $pagemode = false;


                ## ID SEARCH
                if ($_REQUEST['s_quiz_id']) {

                    $dat['quiz_id'] = intval($_REQUEST['s_quiz_id']);

                }

                ## Question SEARCH
                if ($_REQUEST['s_question']) {

                    $dat['question'] = trim($_REQUEST['s_question']);

                }


                ## ANSWER SEARCH
                if ($_REQUEST['s_answer']) {

                    $dat['answer'] = trim($_REQUEST['s_answer']);

                }

                if ($_REQUEST['s_filename']) {

                    $dat['filename'] = trim($_REQUEST['s_filename']);

                }


                ## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
                if (isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])) {

                    $pagemode = true;

                    $cntdat = $dat;
                    $cntdat['fields'] = 'COUNT(id)';
                    list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->quiz_questions->getResults($cntdat));

                    $dat['limit'] = array(
                        "offset" => intval($_REQUEST['index']),
                        "count" => intval($_REQUEST['pagesize'])
                    );

                }


                ## ORDER BY SYSTEM
                if ($_REQUEST['orderby'] && $_REQUEST['orderdir']) {
                    $dat['order'] = array($_REQUEST['orderby'] => $_REQUEST['orderdir']);
                }
                $res = $_SESSION['dbapi']->quiz_questions->getResults($dat);
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
                    case 'csv':
                        $filename = "QuizQuestions-" . $dat['quiz_id'] . ".csv";
                        $out = "Duration,Question,Answer,Variables,Filename,Script,Index,Repeat\r\n";
                        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
                            $out .= $row['duration'] . "," .
                                $row['question'] . "," .
                                $row['answer'] . "," .
                                htmlentities($row['variables']) . "," .
                                htmlentities($row['file']) . "," .
                                $row['script_id'] . "," .
                                $row['play_index'] . "," .
                                $row['script_repeat_mode'] . "\r\n";
                        }
                        header("Content-Type: text/csv");
                        header('Content-Disposition: attachment; filename="' . $filename . '"');
                        echo $out;
                        exit;
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
                case 'quiz_name':

                    // COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
                    if ($tmparr[2] <= 0) {
                        $out_stack[$idx] = '-';
                    } else {

                        //echo "ID#".$tmparr[2];

                        list($out_stack[$idx]) = $_SESSION['dbapi']->queryROW("SELECT name FROM quiz WHERE id=" . intval($tmparr[2]));
                    }

                    break;

            }## END SWITCH


        }


        $out = $_SESSION['api']->renderSecondaryAjaxXML('Data', $out_stack);

        //print_r($out_stack);
        echo $out;

    } ## END HANDLE SECONDARY AJAX


}

