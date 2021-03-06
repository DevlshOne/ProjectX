<?
class API_CampaignParents
{
    public $xml_parent_tagname = "Campaign_parents";
    public $xml_record_tagname = "Campaign_parent";
    public $json_parent_tagname = "ResultSet";
    public $json_record_tagname = "Result";
    public function handleAPI()
    {
        if (!checkAccess('campaigns')) {
            $_SESSION['api']->errorOut('Access denied to Campaign Parents');
            return;
        }
        switch ($_REQUEST['action']) {
        case 'delete':
            $id = intval($_REQUEST['id']);
            $_SESSION['dbapi']->campaign_parents->delete($id);
            logAction('delete', 'campaign_parents', $id, "");
            $_SESSION['api']->outputDeleteSuccess();
            break;
        case 'view':
            $id = intval($_REQUEST['id']);
            $row = $_SESSION['dbapi']->campaign_parents->getByID($id);
            ## BUILD XML OUTPUT
            $out = "<".$this->xml_record_tagname." ";
            foreach ($row as $key=>$val) {
                $out .= $key.'="'.htmlentities($val).'" ';
            }
            $out .= " />\n";
            ///$out .= "</".$this->xml_record_tagname.">";
            echo $out;
            break;
        case 'edit':
            $id = intval($_POST['adding_campaign_parent']);
            $name = trim($_POST['name']);
            unset($dat);
            $dat['name'] = $name;
            $dat['code'] = $_POST['code'];
            if ($id) {
                #$dat['time_modified'] = time();
                $_SESSION['dbapi']->aedit($id, $dat, $_SESSION['dbapi']->campaign_parents->table);
                logAction('edit', 'campaign_parents', $id, "Name: $name");
            } else {
                #$dat['time_created'] = time();
                $_SESSION['dbapi']->aadd($dat, $_SESSION['dbapi']->campaign_parents->table);
                $id = mysqli_insert_id($_SESSION['dbapi']->db);
                logAction('add', 'campaign_parents', $id, "Name: $name");
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
            if ($_REQUEST['s_code']) {
                $dat['code'] = $_REQUEST['s_code'];
            }
            ## PAGE SIZE / INDEX SYSTEM - OPTIONAL - IF index AND pagesize BOTH PASSED IN
            if (isset($_REQUEST['index']) && isset($_REQUEST['pagesize'])) {
                $pagemode = true;
                $cntdat = $dat;
                $cntdat['fields'] = 'COUNT(id)';
                list($totalcount) = mysqli_fetch_row($_SESSION['dbapi']->campaign_parents->getResults($cntdat));
                $dat['limit'] = array(
                                    "offset"=>intval($_REQUEST['index']),
                                    "count"=>intval($_REQUEST['pagesize'])
                                );
            }
            ## ORDER BY SYSTEM
            if ($_REQUEST['orderby'] && $_REQUEST['orderdir']) {
                $dat['order'] = array($_REQUEST['orderby']=>$_REQUEST['orderdir']);
            }
            $res = $_SESSION['dbapi']->campaign_parents->getResults($dat);
    ## OUTPUT FORMAT TOGGLE
            switch ($_SESSION['api']->mode) {
            default:
            case 'xml':
      ## GENERATE XML
                if ($pagemode) {
                    $out = '<'.$this->xml_parent_tagname." totalcount=\"".intval($totalcount)."\">\n";
                } else {
                    $out = '<'.$this->xml_parent_tagname.">\n";
                }
                $out .= $_SESSION['api']->renderResultSetXML($this->xml_record_tagname, $res);
                $out .= '</'.$this->xml_parent_tagname.">";
                break;
        ## GENERATE JSON
            case 'json':
                $out = '['."\n";
                $out .= $_SESSION['api']->renderResultSetJSON($this->json_record_tagname, $res);
                $out .= ']'."\n";
                break;
            }
    ## OUTPUT DATA!
            echo $out;
        }
    }
}
