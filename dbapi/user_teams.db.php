<?
    /**
     * Users SQL Functions
     */

    class UserTeamsAPI {
        var $table = "user_teams";
        var $debug = false;
        /**
         * Marks a User Team as deleted
         */
        function delete($id) {
            $id = intval($id);
            $_SESSION['dbapi']->execSQL("UPDATE `" . $this->table . "` SET `status` = 'deleted' WHERE id = '$id' ");
        }
        function getName($id) {
            $id = intval($id);
            list($name) = $_SESSION['dbapi']->queryROW("SELECT `team_name` FROM `" . $this->table . "` " . " WHERE id = '" . $id . "' ");
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
            $sql = "SELECT $fields FROM `" . $this->table . "` WHERE `status` = 'enabled' ";
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
            ## AGENT NAME SEARCH
            if (is_array($info['team_name'])) {
                $sql .= " AND (";
                $x = 0;
                foreach ($info['team_name'] as $idx => $n) {
                    if ($x++ > 0) $sql .= " OR ";
                    $sql .= " team_name LIKE '%" . mysqli_real_escape_string($_SESSION['dbapi']->db, $n) . "%' ";
                }
                $sql .= ") ";
                ## SINGLE GROUP SEARCH
            } else if ($info['team_name']) {
                $sql .= " AND team_name LIKE '%" . mysqli_real_escape_string($_SESSION['dbapi']->db, $info['team_name']) . "%' ";
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
            ## RETURN RESULT SET
            if ($this->debug) echo $sql;
            return $_SESSION['dbapi']->query($sql);
        }
        function getCount() {
            $row = mysqli_fetch_row($this->getResults(array("fields" => "COUNT(id)")));
            return $row[0];
        }
    }
