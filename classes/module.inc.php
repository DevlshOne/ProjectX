<?php


class Module
{
    private $module_name;
    protected $db_table;
    protected $order_by;
    protected $sort_order;
    protected $pg_size;
    protected $start_idx;
    function __construct($m) {
        $this->setModuleName($m);
    }
    function setModuleName($mn) {
        $this->module_name = $mn;
    }
    function getModuleName() {
        return $this->module_name;
    }
    function setDBTable($dbt) {
        $this->db_table = $dbt;
    }
    function getDBTable() {
        return $this->db_table;
    }


}