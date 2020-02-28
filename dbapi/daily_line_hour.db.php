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

    public function getRoustingGroupStats($startTime, $endTime)
    {
        $sql = <<<SQL
SELECT
	call_group,
    sum(agent_paid_sales_cnt) as group_paid_sales_cnt,
    sum(agent_paid_sales_amount) as group_paid_sales_amount,
    sum(agent_activity_time) as group_activity_time
FROM ( 
	SELECT
		if(RIGHT(username,1) = 2, LEFT(username, length(username) -1), username) as `agent_id`,
        call_group,
		sum(paid_sales_cnt) as agent_paid_sales_cnt,
		sum(paid_sales_amount) as agent_paid_sales_amount,
		sum(activity_time)/count(1) as agent_activity_time,
		count(1) as hands
		FROM (
			 SELECT 
				sales.call_group,
				logins.username,
				activity_time,
				paid_sales_cnt,
				paid_sales_amount
			 FROM 
				(
					SELECT
						DISTINCT username as `username`
						FROM logins
					WHERE result='success' AND section IN('rouster','roustersys')
						AND `time` BETWEEN {$startTime} AND {$endTime}
					GROUP BY 1
					ORDER BY 1
				) logins
			 JOIN (
					SELECT 
					   username,
					   activity_time
					from activity_log
					WHERE time_started BETWEEN {$startTime} AND {$endTime}
				) activity ON logins.username = activity.username
			 JOIN (
					SELECT
						agent_username,
						call_group,
						sum(if(is_paid IN('roustedcc'), 1, 0)) as paid_sales_cnt,
						sum(if(is_paid IN('roustedcc'), amount, 0)) as paid_sales_amount
					FROM sales
						WHERE `sale_time` BETWEEN {$startTime} AND {$endTime}
					GROUP BY agent_username
			 ) sales on logins.username = sales.agent_username
		) login_totals
	GROUP BY 1
) agent_totals
GROUP BY 1;
SQL;

        if( isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) { var_dump($sql); die(); }

        $result = $_SESSION['dbapi']->ROfetchAllAssoc($sql);

        return $result;
    }

    public function getSalesGroupStats($startUnixTime, $endUnixTime)
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

    public function getVerifierGroupStats($startUnixTime, $endUnixTime)
    {
        $sql = <<<SQL
SELECT
	call_group,
    sum(agent_paid_sales_cnt) as group_paid_sales_cnt,
    sum(agent_paid_sales_amount) as group_paid_sales_amount,
    sum(agent_activity_time) as group_activity_time
FROM ( 
	SELECT
		if(RIGHT(username,1) = 2, LEFT(username, length(username) -1), username) as `agent_id`,
        call_group,
		sum(paid_sales_cnt) as agent_paid_sales_cnt,
		sum(paid_sales_amount) as agent_paid_sales_amount,
		sum(activity_time)/count(1) as agent_activity_time,
		count(1) as hands
		FROM (
			 SELECT 
				sales.call_group,
				activity.username,
				activity_time,
				paid_sales_cnt,
				paid_sales_amount
			 FROM 
				 (
					SELECT 
					   username,
					   activity_time
					from activity_log
					WHERE time_started BETWEEN {$startUnixTime} AND {$endUnixTime}
				) activity 
			 LEFT JOIN (
                 SELECT
                     verifier_username as `agent_username`,
                     call_group,
                     sum(if(is_paid = 'yes', 1, 0)) as paid_sales_cnt,
                     sum(if(is_paid = 'yes', amount, 0)) as paid_sales_amount
                 FROM sales
                     WHERE `sale_time` BETWEEN {$startUnixTime} AND {$endUnixTime}
                 GROUP BY verifier_username
			 ) sales on activity.username = sales.agent_username
		) login_totals
	GROUP BY 1
) agent_totals
GROUP BY 1
SQL;

        if( isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) { var_dump($sql); die(); }

        $result = $_SESSION['dbapi']->ROfetchAllAssoc($sql);

        return $result;
    }



}