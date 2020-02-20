<?php
/**
 * Created by PhpStorm.
 * User: brent
 * Date: 2/18/20
 * Time: 9:08 AM
 */

$rr = new DailyLineHourAPI();

class DailyLineHourAPI
{
    public function __construct()
    {

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

    public function getSalesAnalysis($startUnixTime, $endUnixTime)
    {
        $sql = <<<SQL
SELECT
	agent_activity.call_group,
    sum(agent_sales) as `group_total_sales`,
    sum(agent_sales)/(sum(paid_minutes)/60) as `group_worked_hours`,
    sum(paid_sales_amount) as `group_cc_paid`
FROM (  SELECT
			if(RIGHT(username,1) = 2, LEFT(username, length(username) -1), username) as `agent_id`,
			call_group,
			count(1) as `hands`,
			sum(activity_time) as agent_activity_minutes,
			sum(activity_time)/count(1) as paid_minutes
		  FROM activity_log
		WHERE  time_started BETWEEN {$startUnixTime} AND {$endUnixTime}
		GROUP BY 1) agent_activity
LEFT JOIN (
		SELECT
			if(RIGHT(agent_username,1) = 2, LEFT(agent_username, length(agent_username) -1), agent_username) as `agent_id`,
			sales.call_group,
			count(1) as `paid_sales_cnt`,
			sum(amount) as `agent_sales`,
			sum(if(is_paid != 'NO', amount, 0)) as `paid_sales_amount`
		FROM sales
			WHERE `sale_time` BETWEEN {$startUnixTime} AND {$endUnixTime}
		GROUP BY 1
) as agent_sales ON agent_activity.agent_id = agent_sales.agent_id
GROUP BY 1
SQL;

        if( isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) { var_dump($sql); die(); }

        $result = $_SESSION['dbapi']->ROfetchAllAssoc($sql);

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