<?	/***************************************************************
	 * Capacity Report - A report to show how many users are online, vs how many CAN be online, and show a capacity percentage
	 * Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['capacity_report'] = new CapacityReport;


class CapacityReport{




	function CapacityReport(){

		## REQURES DB CONNECTION!
		$this->handlePOST();
	}


	function handlePOST(){


	}

	function handleFLOW(){

		if(!checkAccess('capacity_report')){


			accessDenied("Capacity Report");

			return;

		}else{

			$this->makeReport();

		}

	}


	function generateChartData($mode, $stime, $etime){
		$stime = intval($stime);
		$etime = intval($etime);
		
		switch($mode){
		case 'day':
		default:
			
			
			

			// DETECT THE TIME OF THE FIRST USER
// 			list($min_time) = $_SESSION['dbapi']->ROqueryROW("SELECT MIN(`time`) FROM `server_logs` WHERE `time` BETWEEN '$stime' AND '$etime' AND `num_users` > 0");
			
// 			// DETECT THE TIME OF THE LAST USER
// 			list($max_time) = $_SESSION['dbapi']->ROqueryROW("SELECT MAX(`time`) FROM `server_logs` WHERE `time` BETWEEN '$stime' AND '$etime' AND `num_users` > 0");
			
			// USE THE FULL DAY INSTEAD (24 hours)
			$min_time = mktime(0,0,0, date("m", $stime), date("d", $stime), date("Y", $stime));
			$max_time = $min_time + 86399;
			
			/**
			 *  ['Year', 'Sales', 'Expenses', 'Profit'],
		          ['2014', 1000, 400, 200],
		          ['2015', 1170, 460, 250],
		          ['2016', 660, 1120, 300],
		          ['2017', 1030, 540, 350]
			 */
			
			$data = array();
			$data[0] = array("Hour", "Num. Users", "Max Users");

			list($max_users) = $_SESSION['dbapi']->ROqueryROW("SELECT SUM(max_users) FROM `vici_clusters` WHERE `status`='enabled' ");
			
			for($y=1, $x=$min_time;($x < $max_time); $x += 3600){
				
				$num_users = 0;
				
				$rowarr = $_SESSION['dbapi']->ROfetchAllAssoc("SELECT MAX(num_users) as num_users FROM `server_logs` WHERE `time` BETWEEN '$x' AND '".($x+3599)."' GROUP by `server_id`");
				
				foreach($rowarr as $row){
					$num_users += $row['num_users'];
				}
				//list($num_users, $num_records) = $_SESSION['dbapi']->ROqueryROW("SELECT SUM(num_users), COUNT(*) FROM `server_logs` WHERE `time` BETWEEN '$x' AND '".($x+3599)."'");
				
				$data[$y] = 
					array(
						date("Ha", $x),
						$num_users, //($num_records > 0)?round($num_users/$num_records):0,
						$max_users
							
				);
				
				$y++;
			}
			
			
			
			break;
			
		}
		
		
		return json_encode($data);
		
	}


	function generateData(){


		// GET ALL ACTIVE CLUSTERS FROM VICI_CLUSTERS
		$rowarr = $_SESSION['dbapi']->ROfetchAllAssoc(
			"SELECT ".
					" DISTINCT(`servers`.cluster_id) AS cluster_id,".
					" vici_clusters.name AS name , ".
					" SUM(`servers`.num_users) AS num_users, ".
					" `vici_clusters`.max_users AS max_users, ".
					"((SUM(`servers`.num_users) / `vici_clusters`.max_users) * 100) AS capacity_percent ".
				"FROM `servers` ".
			" INNER JOIN `vici_clusters` ON `vici_clusters`.id=`servers`.cluster_id ".
			" WHERE `running`='yes' ".
			" GROUP BY servers.cluster_id ".
			" ORDER BY `name` ASC"
		);
		
		
		$output = array();
		
		foreach($rowarr as $row){
			
			$output[$row['cluster_id']] = array(
					
					'name'=>$row['name'],
					'num_users' => $row['num_users'],
					'max_users' => $row['max_users'],
					'capacity_percent' => $row['capacity_percent']
					
			);
			
		}
		
		return $output;
	}


	function makeHTMLReport(){



		$data = $this->generateData();


		if(count($data) <= 0){
			return null;
		}



		// ACTIVATE OUTPUT BUFFERING
		ob_start();
		ob_clean();


		?>
    
    <table border="0" width="100%">
		<tr>
			<td colspan="2" style="border-bottom:1px solid #000;font-size:18px;font-weight:bold">

				<br />

				Dialer Capacity Report

			</td>
		</tr>
		<tr>
			<td colspan="2" >
				<input type="button" value="Reload Report" onclick="loadSection('?area=capacity_report&no_script=1');" /><br />
				<br />
				<div id="columnchart_material" style="width: 800px; height: 500px;"></div>
				<br />
			</td>
		</tr>
		<tr>
			<td><table id="capacity_report_table" border="0" width="500">
			<thead>
			<tr>
				<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="left">Dialer</th>
				<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="center">Users / Max </th>
				<th nowrap style="border-bottom:1px dotted #000;padding-left:3px" align="center">Capacity</th>
			</tr>
			</thead>
			<tbody><?
			//
/**
 * $output[$row['cluster_id']] = array(
					
					'name'=>$row['name'],
					'num_users' => $row['num_users'],
					'max_users' => $row['max_users'],
					'capacity_percent' => $row['capacity_percent']
					
			);
 *
 */
			$tcount=0;
			
			$total_users = 0;
			$total_capacity = 0;
			
			foreach($data as $cluster_id => $row){


				?><tr>
					<td style="border-right:1px dotted #CCC;padding-right:3px" align="left"><?=$row['name']?></td>
					<td style="border-right:1px dotted #CCC;padding-right:3px" align="center" title="Number of Users / Maximum Number of Users" ><?=$row['num_users'].' / '.$row['max_users']?></td>
					<td style="border-right:1px dotted #CCC;padding-right:3px" align="center" title="Capacity Percent: <?=$row['capacity_percent']?>"><span class="nod"><?=$row['capacity_percent']?></span><img src="percent.php?percent=<?=round($row['capacity_percent'],2)?>&width=100&height=20" width="100" height="20" border="0" /></td>
				</tr><?


				$tcount++;
				
				$total_users += $row['num_users'];
				$total_capacity += $row['max_users'];
			}

			?></tbody><?

			

			$total_percent = round( ($total_users / $total_capacity) * 100, 4);

			// TOTALS ROW
			?><tfoot>
			<tr>
				<td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="left" nowrap>Total - <?=$tcount?> Clusters:</td>
				<td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="center"><?=$total_users.' / '.$total_capacity?></td>
				<td style="border-right:1px dotted #CCC;border-top:1px solid #000;padding-right:3px" align="center"title="Total Capacity Percent: <?=$total_percent?>"><img src="percent.php?percent=<?=round($total_percent,2)?>&width=100&height=20" width="100" height="20" border="0" /></td>
			</tr>
			</tfoot>
			</table></td>
			<td>
				
			</td>
		</tr>


		</table><?

		// GRAB DATA FROM BUFFER
		$data = ob_get_contents();

		// TURN OFF OUTPUT BUFFERING, WITHOUT OUTPUTTING
		ob_end_clean();

		// CONNECT BACK TO PX BEFORE LEAVING
		connectPXDB();

		// RETURN HTML
		if($tcount > 0)
			return $data;
		else
			return null;

	}

	
	
	
	
	function makeReport(){



		$report = $this->makeHTMLReport();

		if(!$report){

			echo "No data";

		}else{
			echo $report;
		}


		if(!isset($_REQUEST['no_nav'])){
		    
    		?><script>

     			$(document).ready( function () {
 				
				    $('#capacity_report_table').DataTable({

 						"lengthMenu": [[ -1, 20, 50, 100, 500], ["All", 20, 50, 100,500 ]]


 				    });

		    		

				} );
			</script><?
			
		}
		
		?>
		
			
		    <script type="text/javascript">
		      
		
		      function drawChart() {


				var jsondata = $.ajax({
			          url: "ajax.php?mode=capacity_report",
			          dataType: "json",
			          async: false
			          }).responseText;

			      
		        var data = google.visualization.arrayToDataTable(JSON.parse(jsondata));
//				var data = new google.visualization.DataTable(jsondata);
		
		        var options = {
		          chart: {
		            title: 'Capacity Report',
		            subtitle: 'Number of users vs Max users',
		          },
		          //bars: 'horizontal',

		          series: {
		              0: { axis: 'users' }, // Bind series 0 to an axis named 'distance'.
		              1: { axis: 'users' } // Bind series 1 to an axis named 'brightness'.
		            },
		            axes: {
		              x: {
		            	  users: {label: 'Num Users'}, // Bottom x-axis.
		               
		              }
		            }
		        };
		
		        var chart = new google.charts.Bar(document.getElementById('columnchart_material'));
		        chart.draw(data, google.charts.Bar.convertOptions(options));

// 		        var chart = new google.visualization.AreaChart(document.getElementById('columnchart_material'));
// 		        chart.draw(data, options);
		        
		      }

		      google.charts.load('current', {'packages':['bar']});
//		      google.charts.load('current', {'packages':['corechart']});
		      google.charts.setOnLoadCallback(drawChart);
		    </script>
			<?
	}



} // END OF CLASS
