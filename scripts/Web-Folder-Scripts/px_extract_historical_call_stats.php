#!/usr/bin/php
<?php
	$basedir = "/var/www/dev2/";

	include_once($basedir."db.inc.php");
	include_once($basedir."util/microtime.php");
	include_once($basedir."util/format_phone.php");
	include_once($basedir."util/db_utils.php");
	include_once($basedir."dbapi/dbapi.inc.php");
	include_once($basedir."classes/agent_call_stats.inc.php");

	$stime = mktime(0,0,0, date("m"), date("d"), date("Y") );
	$etime = $stime + 86399;

	connectPXDB();


	$source_cluster_id = 0;			// ZERO DEFAULT
	$ignore_source_cluster_id = 3;	// ZERO DEFAULT


	$call_group = null;//"94-COLD-PM"; // NULL DEFAULT


	if($argv[1] && $argv[2]){

		$start	= strtotime($argv[1]);
		$end	= strtotime($argv[2]);
		$stime = mktime(0,0,0, date("m",$start), date("d",$start), date("Y",$start) );
		$etime = mktime(23,59,59, date("m",$end), date("d",$end), date("Y",$end) );

	}else if($argv[1]){
		$start = strtotime($argv[1]);
		$stime = mktime(0,0,0, date("m",$start), date("d",$start), date("Y",$start) );
		$etime = $stime + 86399;



	}

	$days = ceil( ($etime - $stime) / 86400 );

	//echo "Days: ".$days."\n";

	echo "DATE\tTotal Calls\tTotal Sales\tTotal Hangups\tClosing %\tAdj. Closing%\tHangup %\n";




	for($x=0;$x < $days;$x++){

		$dtime = $stime + ($x * 86400);
		$detime = $dtime + 86399;

		//echo "Date: ".date("m/d/Y" , $dtime)."\n";

		/*
		 * generateData($cluster_id, $stime, $etime, $call_group = null, $use_archive_by_default = false, $ignore_arr = null, $source_cluster_id = 0, $ignore_source_cluster_id = 0, $source_user_group = null)
		 */
		$dataarr = $_SESSION['agent_call_stats']->generateData(9, $dtime, $detime, null, false, null, $source_cluster_id, $ignore_source_cluster_id, $call_group);


		$running_total_calls=0;
		$running_total_sales=0;
		$running_total_paid_sales = 0;
		$running_total_hangups = 0;
		$running_total_declines = 0;

		$tcount = 0;
		foreach($dataarr as $row){




				//$close_percent = number_format( round( (($row['sale_cnt']) / ($row['t_call_count'])) * 100, 2), 2);
				$close_percent = ($row['call_cnt'] <= 0)?0: number_format( round( (($row['sale_cnt']) / ($row['call_cnt'])) * 100, 2), 2);

				$adjusted_close_percent = (($row['call_cnt']-$row['hangup_cnt']) <= 0)?0:number_format( round( (($row['sale_cnt']) / ($row['call_cnt']-$row['hangup_cnt'])) * 100, 2), 2);


				$hangup_percent = ($row['call_cnt'] <= 0)?0:number_format( round( (($row['hangup_cnt']) / ($row['call_cnt'])) * 100, 2), 2);




				$running_total_calls += $row['call_cnt'];
				$running_total_sales += ($row['sale_cnt'] );
				$running_total_paid_sales += $row['paid_sale_cnt'];


				$running_total_hangups += $row['hangup_cnt'];
				$running_total_declines += $row['decline_cnt'];



//				echo $row['agent']['username'];
//
//				echo "\t".$close_percent.'%';
//
//				echo "\t".$adjusted_close_percent.'%';
//
//
//				echo "\t".$hangup_percent.'%';
//
//				echo "\n";

				$tcount++;
			}



			$total_close_percent = (($running_total_calls <= 0)?0:number_format( round( (($running_total_sales) / ($running_total_calls)) * 100, 2), 2));

			$total_adj_close_percent = ((($running_total_calls-$running_total_hangups) <= 0)?0:number_format( round( (($running_total_sales) / ($running_total_calls-$running_total_hangups)) * 100, 2), 2));

			$total_hangup_percent = (($running_total_calls <= 0)?0:number_format( round( (($running_total_hangups) / ($running_total_calls)) * 100, 2), 2));

			$total_percent_paidcc_calls = (($running_total_sales <= 0)?0:($running_total_paid_sales / $running_total_sales) * 100);


			echo date("m/d/Y" , $dtime)."\t".

					$running_total_calls."\t".
					$running_total_sales."\t".
					$running_total_hangups."\t";



			echo $total_close_percent.'%'."\t".$total_adj_close_percent.'%'."\t".$total_hangup_percent.'%'."\n";


		//print_r($dataarr);



	}

