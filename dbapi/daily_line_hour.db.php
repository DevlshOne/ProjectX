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
SELECT
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
SELECT 
	call_group,
    sum(_wrkd_hrs) as group_wrkd_hours,
    sum(_total_calls) as group_total_calls,
    sum(_agent_total_sales) as group_total_sales,
    sum(_agent_total_sales_amount) as group_total_sales_amount,
    sum(_agent_paid_sales) as group_paid_sales,
    sum(_agent_paid_sales_amount) as group_paid_sales_amount,
    sum(_agent_unpaid_sales) as group_unpaid_sales
FROM (
	SELECT -- AGENT LEVEL
		username,
        call_group,
		_wrkd_hrs,
		_total_calls,
		_agent_total_sales,
        _agent_total_sales_amount,
		_agent_paid_sales,
		_agent_paid_sales_amount,
		(_agent_paid_sales/_agent_total_sales) * 100 as _paid_sales_perc,
		(_agent_paid_sales_amount/_agent_total_sales_amount) * 100 as _paid_sales_perc_money,
		_agent_unpaid_sales,
		(1-(_agent_paid_sales/_agent_total_sales))*100 as _unpaid_sales_perc
	FROM (
		SELECT
			username,
			call_group,
			count(1) as `hands`,
			sum(activity_time)/60 as agent_activity_minutes,
			sec_to_time((max(activity_time)/60)) as paid_minutes,
			sec_to_time((max(activity_time)/60)) as _wrkd_hrs,
			calls_today as `_total_calls`
		FROM activity_log
		WHERE  time_startedBETWEEN {$startUnixTime} AND {$endUnixTime}
		GROUP BY 1
		) activity_log
	LEFT JOIN (        
		SELECT
			agent_username,
			count(1) as `_agent_total_sales`,
			sum(amount) as `_agent_total_sales_amount`,
			sum(if(is_paid = 'NO', 1, 0)) as `_agent_unpaid_sales`,
			sum(if(is_paid != 'NO', 1, 0)) as `_agent_paid_sales`,
			sum(if(is_paid != 'NO', amount, 0)) as `_agent_paid_sales_amount`
		FROM sales
			WHERE `sale_time` BETWEEN {$startUnixTime} AND {$endUnixTime}
		GROUP BY 1
	) sales on activity_log.username = sales.agent_username
) agent_values
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