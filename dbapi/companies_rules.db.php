<?
/**
 * Companies Rules SQL Functions
 */

class CompaniesRulesAPI
{

    var $table = "companies_rules";

    /**
     * Marks a rule as deleted
     */
    function delete($id)
    {
        return $_SESSION['dbapi']->adelete($id, $this->table);
    }

    /**
     * Get a Rule by ID
     * @param    $id   Integer     The database ID of the record
     * @return    assoc-array of the database record
     */
    function getByID($id)
    {
        $id = intval($id);
        return $_SESSION['dbapi']->querySQL("SELECT * FROM `" . $this->table . "` " . " WHERE `id` = '" . $id . "'");
    }

    /**
     * getResults($asso_array)
     * Array Fields:
     *    fields    : The select fields for the sql query, * is default
     *    id        : Int/Array of Ints
     *  enabled    : String only, "yes"/"no"
     *
     *  skip_id : Int/Array of ID's to skip (AND seperated, != operator)
     *
     *  order : ORDER BY field, Assoc-array,
     *        Example: "order" = array("id"=>"DESC")
     *  limit : Assoc-Array of 2 keys/values.
     *        "count"=>(amount to limit by)
     *        "offset"=>(optional, the number of records to skip)
     */
    function getResults($info)
    {
        $fields = ($info['fields']) ? $info['fields'] : '*';
        $sql = "SELECT $fields FROM `" . $this->table . "` WHERE 1 ";
        ## ID FIELD SEARCH
        ## ARRAY OF id's SEARCH
        if (is_array($info['company_id'])) {
            $sql .= " AND (";
            $x = 0;
            foreach ($info['company_id'] as $idx => $sid) {
                if ($x++ > 0) $sql .= " OR ";
                $sql .= "`company_id`='" . intval($sid) . "' ";
            }
            $sql .= ") ";
            ## SINGLE ID SEARCH
        } else if ($info['company_id']) {
            $sql .= " AND `company_id`='" . intval($info['company_id']) . "' ";
        }
        
        
        if (is_array($info['schedule_id'])) {
        	$sql .= " AND (";
        	$x = 0;
        	foreach ($info['schedule_id'] as $idx => $sid) {
        		if ($x++ > 0) $sql .= " OR ";
        		$sql .= "`schedule_id`='" . intval($sid) . "' ";
        	}
        	$sql .= ") ";
        	## SINGLE ID SEARCH
        } else if ($info['schedule_id']) {
        	$sql .= " AND `schedule_id`='" . intval($info['schedule_id']) . "' ";
        }
        
        
        if ($info['action_type']) {
            $sql .= " AND `action`='" . $info['action_type'] . "' ";
        }
        if ($info['action_value']) {
            $sql .= " AND `action_value`='" . floatval($info['action_value']) . "' ";
        }
        if ($info['trigger_name']) {
            $sql .= " AND `trigger_name`='" . $info['trigger_name'] . "' ";
        }
        if ($info['trigger_value']) {
            $sql .= " AND `trigger_value`='" . floatval($info['trigger_value']) . "' ";
        }
        ## SKIP/IGNORE ID's
        if (isset($info['skip_id'])) {

            $sql .= " AND (";

            if (is_array($info['skip_id'])) {
                $x = 0;
                foreach ($info['skip_id'] as $sid) {

                    if ($x++ > 0) $sql .= " AND ";

                    $sql .= "`id` != '" . intval($sid) . "'";
                }

            } else {
                $sql .= "`id` != '" . intval($info['skip_id']) . "' ";
            }

            $sql .= ")";
        }


        ### ORDER BY
        if (is_array($info['order'])) {

            $sql .= " ORDER BY ";
            $x = 0;
            foreach ($info['order'] as $k => $v) {
                if ($x++ > 0) $sql .= ",";

                $sql .= "`$k` " . mysqli_real_escape_string($_SESSION['dbapi']->db, $v) . " ";
            }

        }

        if (is_array($info['limit'])) {

            $sql .= " LIMIT " .
                (($info['limit']['offset']) ? $info['limit']['offset'] . "," : '') .
                $info['limit']['count'];

        }


        #echo $sql;

        ## RETURN RESULT SET
        return $_SESSION['dbapi']->query($sql);
    }

    function getCount()
    {
        $row = mysqli_fetch_row($this->getResults(
            array(
                "fields" => "COUNT(id)"
            )
        ));
        return $row[0];
    }
}
