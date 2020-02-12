<?php
/**
 * User: brent
 * Date: 1/7/20
 * Time: 11:14 AM
 */

$rr = new RoustingReportAPI();

class RoustingReportAPI
{
    public function __construct()
    {

    }

    public function getDetails($clusterId, $userGroup, $startTime, $endTime)
    {
        $rousterReport = new RousterReport();
        $roustingData = $rousterReport->generateData($clusterId, null, $startTime, $endTime, $userGroup);

        return $roustingData;
    }


    public function getRoustingTotals($clusterId, $userGroup, $startTime, $endTime)
    {
        $users = "'".implode("','", $this->getUsers($clusterId, $userGroup, $startTime, $endTime))."'";


        $hourlySql = <<<SQL
            SELECT
                 '{$userGroup}' as `user_group`,
                 sum(agent_sales.paid_sales_amount) as `paid_cc`,
                 round(sum(agent_sales.paid_sales_amount)/(sum(activity_log.paid_time)/60),2) as `paid_per_hour`
            FROM `activity_log` 
                LEFT JOIN (
                    SELECT
                        agent_username,
                        sum(if(is_paid IN('yes','roustedcc'), 1, 0)) as paid_sales_cnt,
                        sum(if(is_paid IN('yes','roustedcc'), amount, 0)) as paid_sales_amount
                    FROM sales
                        WHERE `sale_time` BETWEEN {$startTime} AND {$endTime}
                        AND ( agent_cluster_id = {$clusterId})
                        AND agent_username IN ( {$users} )
                        AND call_group = '{$userGroup}'
                    GROUP BY agent_username
                ) `agent_sales` ON `activity_log`.`username` = `agent_sales`.`agent_username`
            WHERE
              `time_started` BETWEEN {$startTime} AND {$endTime}
            AND `vici_cluster_id` = {$clusterId}
            AND `username` IN ( {$users} )
SQL;
        
        if( isset($_REQUEST['debug']) && $_REQUEST['debug'] == 2) { var_dump($hourlySql); die(); }
        
        $result = $_SESSION['dbapi']->ROquerySQL($hourlySql);

        return $result;
    }

    public function getUsers($clusterId, $userGroup, $startTime, $endTime)
    {
        $sql = <<<SQL
          SELECT DISTINCT(username) FROM `logins` 
            WHERE result='success' AND section IN('rouster','roustersys')
            AND `time` BETWEEN '{$startTime}' AND '{$endTime}' 
            AND cluster_id='{$clusterId}'
            AND `user_group` IN ('{$userGroup}')
SQL;

        if( isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) { var_dump($sql); die(); }

        foreach ($_SESSION['dbapi']->getResult($sql) as $result) {
            $users[] = $result['username'];
        }

        return $users;
    }

}