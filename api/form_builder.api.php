<?

    class API_FormBuilder
    {
        var $xml_parent_tagname = "Formbuilders";
        var $xml_record_tagname = "Formbuilder";
        var $json_parent_tagname = "ResultSet";
        var $json_record_tagname = "Result";

        function handleAPI()
        {
            if (!checkAccess('form_builder')) {
                $_SESSION['api']->errorOut('Access denied to Form Builder');
                return;
            }
            switch ($_REQUEST['action']) {
                case 'copy':
                    $id = intvale($_REQUEST['id']);
                    $tgtCampaign = $_SESSION['dbapi']->form_builder->copy($id);
                    logAction('copy', 'form_builder', $id, "Copied form to campaign" . $tgtCampaign);
                    $_SESSION['api']->outputCopySuccess();
                    break;
                case 'delete':
                    $id = intval($_REQUEST['id']);
                    $_SESSION['dbapi']->form_builder->delete($id);
                    logAction('delete', 'form_builder', $id, "");
                    $_SESSION['api']->outputDeleteSuccess();
                    break;
                case 'view':
                    $id = intval($_REQUEST['id']);
                    $row = $_SESSION['dbapi']->form_builder->getByID($id);
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
                    $id = intval($_POST['adding_name']);
                    unset($dat);
                    $dat['name'] = trim($_POST['name']);
                    $dat['filename'] = trim($_POST['filename']);
                    $dat['voice_id'] = intval($_POST['voice_id']);
                    if ($id) {
                        $_SESSION['dbapi']->aedit($id, $dat, $_SESSION['dbapi']->form_builder->table);
                        logAction('edit', 'form_builder', $id, "Name=" . $dat['name']);
                    } else {
                        $_SESSION['dbapi']->aadd($dat, $_SESSION['dbapi']->form_builder->table);
                        $id = mysqli_insert_id($_SESSION['dbapi']->db);
                        logAction('add', 'form_builder', $id, "Name=" . $dat['name']);
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
                    ## USERNAME SEARCH
                    if ($_REQUEST['s_name']) {
                        $dat['name'] = trim($_REQUEST['s_name']);
                    }
                    if ($_REQUEST['s_filename']) {
                        $dat['filename'] = trim($_REQUEST['s_filename']);
                    }
                    ## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
                    if (isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])) {
                        $pagemode = true;
                        $cntdat = $dat;
                        $cntdat['fields'] = 'COUNT(id)';
                        list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->form_builder->getResults($cntdat));
                        $dat['limit'] = array("offset" => intval($_REQUEST['index']), "count" => intval($_REQUEST['pagesize']));
                    }
                    ## ORDER BY SYSTEM
                    if ($_REQUEST['orderby'] && $_REQUEST['orderdir']) {
                        $dat['order'] = array($_REQUEST['orderby'] => $_REQUEST['orderdir']);
                    }
                    $res = $_SESSION['dbapi']->form_builder->getResults($dat);
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
                    case 'voice_name':
                        // COULD BE REPLACED LATER WITH A CUSOMIZABLE SCREEN DB TABLE
                        if ($tmparr[2] <= 0) {
                            $out_stack[$idx] = '-';
                        } else {
                            //echo "ID#".$tmparr[2];
                            $out_stack[$idx] = $_SESSION['dbapi']->voices->getName($tmparr[2]);
                        }
                        break;
                }## END SWITCH
            }
            $out = $_SESSION['api']->renderSecondaryAjaxXML('Data', $out_stack);
            //print_r($out_stack);
            echo $out;
        } ## END HANDLE SECONDARY AJAX
    }