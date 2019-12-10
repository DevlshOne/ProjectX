<?
    /**
     * Users SQL Functions
     */

    class UserGroupsAPI {

        var $table = "user_groups";

        private function deleteGroupFromVici($id) {

            $row = $this->getByID($id);

            $cluster_idx = getClusterIndex($row['vici_cluster_id']);

            connectViciDB($cluster_idx);

            return execSQL("DELETE FROM vicidial_user_groups WHERE `user_group`='" . mysqli_real_escape_string($_SESSION['db'], $row['user_group']) . "' LIMIT 1");

        }

        /**
         * Marks a User as enabled=no (deleted)
         */
        function delete($id) {
            $id = intval($id);

            // DELETE FROM VICIDIAL FIRST
            $this->deleteGroupFromVici($id);

            // THEN DELETE FROM PX
            $_SESSION['dbapi']->execSQL("DELETE FROM `user_groups` WHERE id='$id' ");

        }

        /**
         * Get a user by ID
         * @param    $user_id           The database ID of the record
         * @param    $account_id        Optional account ID restriction
         *                              * @return    assoc-array of the database record
         */
        function getByID($id) {
            $id = intval($id);

            return $_SESSION['dbapi']->querySQL("SELECT * FROM `" . $this->table . "` " . " WHERE id='" . $id . "' "

            );
        }

        function getName($id) {
            $id = intval($id);
            list($name) = $_SESSION['dbapi']->queryROW("SELECT user_group FROM `" . $this->table . "` " . " WHERE id='" . $id . "' ");
            return $name;
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
        function getResults($info) {

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
            } else if ($info['id']) {

                $sql .= " AND `id`='" . intval($info['id']) . "' ";

            }

            if (is_array($info['vici_cluster_id'])) {

                $sql .= " AND (";

                $x = 0;
                foreach ($info['vici_cluster_id'] as $idx => $sid) {
                    if ($x++ > 0) $sql .= " OR ";

                    $sql .= "`vici_cluster_id`='" . intval($sid) . "' ";
                }

                $sql .= ") ";

                ## SINGLE ID SEARCH
            } else if ($info['vici_cluster_id']) {

                $sql .= " AND `vici_cluster_id`='" . intval($info['vici_cluster_id']) . "' ";

            }

            ## AGENT NAME SEARCH
            if (is_array($info['name'])) {

                $sql .= " AND (";

                $x = 0;
                foreach ($info['name'] as $idx => $n) {
                    if ($x++ > 0) $sql .= " OR ";

                    $sql .= " `name` LIKE '%" . mysqli_real_escape_string($_SESSION['dbapi']->db, $n) . "%' ";
                }

                $sql .= ") ";

                ## SINGLE NAME SEARCH
            } else if ($info['name']) {

                $sql .= " AND `name` LIKE '%" . mysqli_real_escape_string($_SESSION['dbapi']->db, $info['name']) . "%' ";

            }

            ## AGENT NAME SEARCH
            if (is_array($info['group_name'])) {

                $sql .= " AND (";

                $x = 0;
                foreach ($info['group_name'] as $idx => $n) {
                    if ($x++ > 0) $sql .= " OR ";

                    $sql .= " user_group LIKE '%" . mysqli_real_escape_string($_SESSION['dbapi']->db, $n) . "%' ";
                }

                $sql .= ") ";

                ## SINGLE GROUP SEARCH
            } else if ($info['group_name']) {

                $sql .= " AND  user_group LIKE '%" . mysqli_real_escape_string($_SESSION['dbapi']->db, $info['group_name']) . "%' ";

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

            #echo $sql;

            ## RETURN RESULT SET
            return $_SESSION['dbapi']->query($sql);
        }

        function getCount() {

            $row = mysqli_fetch_row($this->getResults(array("fields" => "COUNT(id)")));

            return $row[0];
        }

    }
