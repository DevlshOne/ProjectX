<?
    /**
     * Master User Groups SQL Functions
     */

    class UserGroupsMasterAPI
    {
        var $table = "user_groups_master";

        /**
         * Marks a User as enabled=no (deleted)
         */
        function delete($id)
        {
            $id = intval($id);
//		// DELETE FROM VICIDIAL FIRST
//		$this->deleteGroupFromVici($id);
            // THEN DELETE FROM PX
            $_SESSION['dbapi']->execSQL("DELETE FROM `user_groups_master` WHERE id='$id' ");
        }
        /**
         * Get a user by ID
         *
         * @param    $user_id           The database ID of the record
         * @param    $account_id        Optional account ID restriction
         * @return    assoc-array of the database record
         *
         */
        function getByID($id)
        {
            $id = intval($id);
            return $_SESSION['dbapi']->querySQL("SELECT * FROM `" . $this->table . "` " . " WHERE id='" . $id . "' "

            );
        }
        function getName($id)
        {
            $id = intval($id);
            list($name) = $_SESSION['dbapi']->queryROW("SELECT user_group FROM `" . $this->table . "` " . " WHERE id='" . $id . "' ");
            return $name;
        }
        /**
         * @param array $info generated in the api file
         * @return array
         *
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
            if (is_array($info['id'])) {
                $sql .= " AND (";
                $x = 0;
                foreach ($info['id'] as $idx => $sid) {
                    if ($x++ > 0) $sql .= " OR ";

                    $sql .= "`id`='" . intval($sid) . "' ";
                }
                $sql .= ") ";
                ## SINGLE ID SEARCH
            } else if (isset($info['id'])) {
                $sql .= " AND `id`='" . intval($info['id']) . "' ";
            }
            ## GROUP NAME SEARCH
            if (is_array($info['user_group'])) {
                $sql .= " AND (";
                $x = 0;
                foreach ($info['user_group'] as $idx => $n) {
                    if ($x++ > 0) $sql .= " OR ";
                    $sql .= " user_group LIKE '%" . mysqli_real_escape_string($_SESSION['dbapi']->db, $n) . "%' ";
                }
                $sql .= ") ";
                ## SINGLE GROUP SEARCH
            } else if (isset($info['user_group'])) {
                $sql .= " AND  user_group LIKE '%" . mysqli_real_escape_string($_SESSION['dbapi']->db, $info['user_group']) . "%' ";
            }
            ## COMPANY ID SEARCH
            if(isset($info['company_id'])) {
                $sql .= " AND `company_id` = " . intval($info['company_id']);
            }
            ## AGENT TYPE SEARCH
            if(isset($info['agent_type'])) {
                $sql .= " AND `agent_type` LIKE '%" . mysqli_real_escape_string($_SESSION['dbapi']->db, $info['agent_type']) . "%'";
            }
            ## SHIFT SEARCH
            if(isset($info['time_shift'])) {
                $sql .= " AND `time_shift` LIKE '%" . mysqli_real_escape_string($_SESSION['dbapi']->db, $info['time_shift']) . "%'";
            }
            ## OFFICE SEARCH
            if(isset($info['office'])) {
                $sql .= " AND `office` = " . intval($info['office']);
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
                $sql .= " LIMIT " . (($info['limit']['offset']) ? $info['limit']['offset'] . "," : '') . $info['limit']['count'];
            }
            #echo $sql . PHP_EOL;
            ## RETURN RESULT SET
            return $_SESSION['dbapi']->query($sql);
        }

        function getCount()
        {
            $row = mysqli_fetch_row($this->getResults(array("fields" => "COUNT(id)")));
            return $row[0];
        }
    }
