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

    public function getRoustingGroupStats($startUnixTime, $endUnixTime)
    {
        $sql = <<<SQL
SELECT SQL_NO_CACHE
	call_group,
    sum(agent_paid_sales_cnt) as group_paid_sales_cnt,
    sum(agent_paid_sales_amount) as group_paid_sales_amount,
    sum(agent_activity_time) as group_activity_time,
    sec_to_time(sum(agent_activity_time)) as _fat,
    sum(agent_paid_sales_amount)/(sum(agent_activity_time)/60) as _worked_hour
FROM ( 
	SELECT
		if(RIGHT(username,1) = 2, LEFT(username, length(username) -1), username) as `agent_id`,
        -- username,
        call_group,
		sum(paid_sales_cnt) as agent_paid_sales_cnt,
		sum(paid_sales_amount) as agent_paid_sales_amount,
		max(activity_time) as agent_activity_time,
        sec_to_time(max(activity_time)) as _fat,
        sum(paid_sales_amount)/(max(activity_time)/60) as _worked_hour,
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
						AND `time` BETWEEN {$startUnixTime} AND {$endUnixTime}
					GROUP BY 1
					ORDER BY 1
				) logins
			 JOIN (
					SELECT 
					   username,
					   max(seconds_INCALL+seconds_READY+seconds_QUEUE+seconds_PAUSED)/60 as `activity_time`
					from activity_log
					WHERE time_started BETWEEN {$startUnixTime} AND {$endUnixTime}
			        GROUP BY username
				) activity ON logins.username = activity.username
			 JOIN (
					SELECT
						agent_username,
						call_group,
						sum(if(is_paid IN('roustedcc'), 1, 0)) as paid_sales_cnt,
						sum(if(is_paid IN('roustedcc'), amount, 0)) as paid_sales_amount
					FROM sales
						WHERE `sale_time` BETWEEN {$startUnixTime} AND {$endUnixTime}
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
-- GROUP LEVEL
SELECT SQL_NO_CACHE
	call_group,
	sum(agent_total_sales_amount) as group_total_sales_amount,
    sum(agent_total_sales_amount)/(sum(_agent_wrkd_minutes)/60) as group_wrkd_hour_amount,
    sum(agent_paid_sales_amount) as group_paid_sales_amount
FROM (
    -- AGENT LEVEL
	SELECT 
		if(RIGHT(username,1) = 2, LEFT(username, length(username) -1), username) as `agent_id`,
		call_group,
		count(1) as _hands,
        max(hand_paid_minutes) as _agent_paid_minutes,
        max(hand_wrkd_minutes) as _agent_wrkd_minutes,
		max(hand_wrkd_minutes)/60 as agent_wrkd_hrs, -- correct
		sum(hand_total_calls) as agent_total_calls,
		sum(hand_total_sales) as agent_total_sales,
		sum(hand_paid_sales) as agent_paid_sales,
		sum(hand_paid_sales_amount) as agent_paid_sales_amount,
		(sum(hand_paid_sales)/sum(hand_total_sales)) * 100 as _paid_sales_perc,
		(sum(hand_paid_sales_amount))/sum(hand_total_sales_amount) * 100 as _paid_amount_sales_perc,
		sum(hand_unpaid_sales) as agent_unpaid_sales,
		sum(hand_unpaid_sales)/sum(hand_total_sales) * 100 as agent_unpaid_sales_perc,
		sum(hand_total_sales_amount) as agent_total_sales_amount,
		sum(hand_total_sales_amount)/sum(hand_total_sales) as agent_avg_sale,
		sum(hand_total_sales_amount)/max(hand_paid_minutes/60) as agent_paid_hour_amount,
		sum(hand_total_sales_amount)/max(hand_wrkd_minutes/60) as agent_wrkd_hour_amount
	FROM (
	        -- HAND LEVEL
            SELECT 
                username,
                call_group,
                sum(activity_time) as hand_activity_minutes,
                -- sec_to_time(max(activity_time)) as _hand_paid_minutes,
                max(activity_time) as hand_wrkd_minutes,
                calls_today as hand_total_calls,
                max(paid_time) as hand_paid_minutes
            FROM activity_log
            WHERE  time_started BETWEEN {$startUnixTime} AND {$endUnixTime}
            GROUP BY 1
		) activity_log
		LEFT JOIN (        
			SELECT
				agent_username,
				count(1) as `hand_total_sales`,
				sum(amount) as `hand_total_sales_amount`,
				sum(if(is_paid = 'NO', 1, 0)) as `hand_unpaid_sales`,
				sum(if(is_paid != 'NO', 1, 0)) as `hand_paid_sales`,
				sum(if(is_paid != 'NO', amount, 0)) as `hand_paid_sales_amount`
			FROM sales
				WHERE `sale_time` BETWEEN {$startUnixTime} AND {$endUnixTime}
			GROUP BY 1
		) sales on activity_log.username = sales.agent_username
	GROUP BY 1
) call_group_stats
GROUP BY 1
SQL;

        if( isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) { echo ($sql); die(); }

        $result = $_SESSION['dbapi']->ROfetchAllAssoc($sql);

        return $result;
    }

    public function getVerifierGroupStats($startUnixTime, $endUnixTime)
    {
        $sql = <<<SQL
SELECT SQL_NO_CACHE
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
					   sum(seconds_INCALL+seconds_READY+seconds_QUEUE+seconds_PAUSED)/60 as `activity_time`
					from activity_log
					WHERE time_started BETWEEN {$startUnixTime} AND {$endUnixTime}
				    GROUP BY username
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
