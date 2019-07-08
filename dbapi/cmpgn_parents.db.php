<?php
/**
 * Campaign Parents SQL Functions
 */

class CampaignParentsAPI
{
    public $table = "campaign_parents";
    /**
     * Marks a campaign as deleted
     */
    public function delete($id)
    {
        unset($dat);
        $dat['deleted'] = '1';
        return $_SESSION['dbapi']->aedit($id, $dat, $this->table);
    }
    /**
     * Get a campaign by ID
     * @param 	$campaign_id		The database ID of the record
     * 	 * @return	assoc-array of the database record
     */
    public function getByID($campaign_id)
    {
        $campaign_id = intval($campaign_id);
        return $_SESSION['dbapi']->querySQL(
            "SELECT * FROM `".$this->table."` ".
                        " WHERE id='".$campaign_id."' "
                    );
    }
    public function getName($campaign_id)
    {
        $campaign_id=intval($campaign_id);
        list($name) = $_SESSION['dbapi']->queryROW("SELECT name FROM `".$this->table."` ".
                        " WHERE id='".$campaign_id."' ");
        return $name;
    }

    /**
     * getResults($asso_array)

     * Array Fields:
     * 	fields	: The select fields for the sql query, * is default
     *	id		: Int/Array of Ints
     *  enabled	: String only, "yes"/"no"
     *

     *  skip_id : Int/Array of ID's to skip (AND seperated, != operator)
     *
     *  order : ORDER BY field, Assoc-array,
     * 		Example: "order" = array("id"=>"DESC")
     *  limit : Assoc-Array of 2 keys/values.
     * 		"count"=>(amount to limit by)
     * 		"offset"=>(optional, the number of records to skip)
     */
    public function getResults($info)
    {
        $fields = ($info['fields'])?$info['fields']:'*';
        $sql = "SELECT $fields FROM `".$this->table."` WHERE deleted='0' ";
        ## ID FIELD SEARCH
        ## ARRAY OF id's SEARCH
        if (is_array($info['id'])) {
            $sql .= " AND (";
            $x=0;
            foreach ($info['id'] as $idx=>$sid) {
                if ($x++ > 0) {
                    $sql .= " OR ";
                }
                $sql .= "`id`='".intval($sid)."' ";
            }
            $sql .= ") ";
        ## SINGLE ID SEARCH
        } elseif ($info['id']) {
            $sql .= " AND `id`='".intval($info['id'])."' ";
        }
        ### ENABLED FIELD
        if ($info['code']) {
            $sql .= " AND `code`='".mysqli_real_escape_string($_SESSION['dbapi']->db, $info['code'])."' ";
        }
        ### NAME SEARCH
        ## ARRAY OF STRINGS, OR SEPERATED SEARCH
        if (is_array($info['name'])) {
            $sql .= " AND (";
            $x=0;
            foreach ($info['name'] as $idx=>$n) {
                if ($x++ > 0) {
                    $sql .= " OR ";
                }
                $sql .= "`name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db, $n)."%' ";
            }
            $sql .= ") ";

        ## SINGLE NAME SEARCH
        } elseif ($info['name']) {
            $sql .= " AND `name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db, $info['name'])."%' ";
        }
        ## SKIP/IGNORE ID's
        if (isset($info['skip_id'])) {
            $sql .= " AND (";
            if (is_array($info['skip_id'])) {
                $x=0;
                foreach ($info['skip_id'] as $sid) {
                    if ($x++ > 0) {
                        $sql .= " AND ";
                    }
                    $sql .= "`id` != '".intval($sid)."'";
                }
            } else {
                $sql .= "`id` != '".intval($info['skip_id'])."' ";
            }
            $sql .= ")";
        }
        ### ORDER BY
        if (is_array($info['order'])) {
            $sql .= " ORDER BY ";
            $x=0;
            foreach ($info['order'] as $k=>$v) {
                if ($x++ > 0) {
                    $sql .= ",";
                }
                $sql .= "`$k` ".mysqli_real_escape_string($_SESSION['dbapi']->db, $v)." ";
            }
        }
        if (is_array($info['limit'])) {
            $sql .= " LIMIT ".
                        (($info['limit']['offset'])?$info['limit']['offset'].",":'').
                        $info['limit']['count'];
        }
        #echo $sql;
        ## RETURN RESULT SET
        return $_SESSION['dbapi']->query($sql);
    }
    public function getCount()
    {
        $row = mysqli_fetch_row($this->getResults(
            array(
                            "fields" => "COUNT(id)",
                            "status"=> "active"
                        )
                    ));
        return $row[0];
    }
}
