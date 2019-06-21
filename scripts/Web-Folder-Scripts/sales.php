<?php

	// NEED THIS DATA ACCESSABLE IN FUNCTIONS
	global $xml_dir, $showing_offices, $combine_users,$hours, $offices, $records, $groups, $sale_totals, $counts;


	require_once("site_config.php");
	require_once("utils/report_utils.php");
	require_once("utils/stripurl.php");


	$sales_file = $_SESSION['site_config']['xml_dir']."sales.xml";
	$hours_file = $_SESSION['site_config']['xml_dir']."vicihours.xml";





	// OPTIONAL OFFICE SPEFICATION
	// MULTIPLE OFFICES SPECIFIED via ?office=90;94
	$showing_offices = (isset($_REQUEST['office']))?trim($_REQUEST['office']):null;
	if(strpos($showing_offices,";")){

		$showing_offices = preg_split("/;/", $showing_offices, -1, PREG_SPLIT_NO_EMPTY);

	}


	$combine_users = (isset($_REQUEST['combine_users']))?true:false;





	$last_updated = filemtime($sales_file);


	// FAILSAFE TO KEEP IT FROM INFINATE REFRESHING IF THE FILE STOPS GETTING UPDATED
	if( (time()-$last_updated) < 180){

		$next_refresh =  ($last_updated + 180) - time();

	}else{
		$next_refresh = -1;
	}


/**
 * Hours parsing
 */
	parseHoursData($hours_file);
/**
 * SALES PARSING
 */
	parseSalesData($sales_file);





	function renderTable($mode, $data){


		// GROUP MODE
		if($mode == "group"){

			$group = $data;

			// FAILSAFE PATCH
			if(!$data){

				$data = uniqid();

			}


			$tablename = preg_replace("/[^a-zA-Z0-9]/",'',$data)."_table";


			// GET DATA
			$sales = getGroupSales($group);

			$sales_totals = getUserTotals($sales);


		// OFFICE
		}else{

			$office = $data;


			// FAILSAFE PATCH
			if(!$data){

				$data = uniqid();

			}

			$tablename = preg_replace("/[^a-zA-Z0-9]/",'',$data)."_table";


			// GET DATA
			$sales = getOfficeSales($office);

			$sales_totals = getUserTotals($sales);
		}




		?><h2><?=ucfirst($mode)?> : <?=$data?></h2>
		<table border="0" width="100%" align="left" id="<?=$tablename?>">
		<THEAD>
		<tr>
			<th align="left">Initials</th>
			<th align="left">Name</th>
			<th>Avg/hr</th>
			<th>Total</th>
			<th>Deals</th>
			<th>Avg Deal</th>
		</tr>
		</THEAD>
		<TBODY><?

		$x=0;
		$class='';
		$amt_total = 0;
		$cnt_total = 0;
		$hourly_total = 0;
		$hours_total = 0;
		foreach($sales_totals as $row){

			$deal_avg = $row->amount / $row->count;

			$user_hours = getUserHours($row->user);

			$hours_total += (float)$user_hours;

			$user_hourly_avg = $row->amount / (float)$user_hours;


			$hourly_total += $user_hourly_avg;

			?><tr>
				<td><?=$row->user?></td>
				<td><?=$row->name?></td>
				<td align="center">$<?=number_format($user_hourly_avg,2)?></td>
				<td align="center">$<?=number_format($row->amount)?></td>
				<td align="center"><?=number_format($row->count)?></td>
				<td align="center">$<?=number_format($deal_avg)?></td>
			</tr><?

			$amt_total += $row->amount;
			$cnt_total += $row->count;
			$x++;
		}


		$hourly_avg_per_user = $hourly_total / $x;

		$hours_avg = $hours_total / $x;

		?></TBODY>
		<TFOOT>
			<tr>
				<th colspan="2" align="left">Totals</th>
				<th>$<?=number_format($hourly_avg_per_user, 2)?>/hr</th>
				<th>$<?=number_format($amt_total)?></th>
				<th><?=number_format($cnt_total)?></th>
				<th>$<?=number_format($amt_total/$cnt_total)?>/deal</th>
			</tr>
		</TFOOT>
		</table>
		<script>
		$(document).ready(function() {
		    $('#<?=$tablename?>').dataTable({



		        "bPaginate": false,
		        "bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,

		        "aaSorting": [[ 3, "desc" ]],

		        "bInfo": false,
		        "bAutoWidth": false,

	<?/**	        aoColumns: [
		            null,
		            null,
		            {
		                fnRender: function ( o ) {
		                    return "$"+o.aData[ o.iDataColumn ];
		                },
		                bUseRendered: false
		            },
		            {
		                fnRender: function ( o ) {
		                    return "$"+o.aData[ o.iDataColumn ];
		                },
		                bUseRendered: false
		            },
		            null,
		            {
		                fnRender: function ( o ) {
		                    return "$"+o.aData[ o.iDataColumn ];
		                },
		                bUseRendered: false
		            }
		        ]
	**/?>


			});
		});
		</script><?



		//$amt_total

		return array($hourly_avg_per_user, $hours_avg, $amt_total, $cnt_total);
	}



	//print_r($output);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
	<!-- Debug info: <?=$next_refresh?> -->
	<title>CCI - Sales Report</title>
<?
	## CONTAINS ALL THE INCLUDES AND SHIT
	include_once("header.html");

?>


<script>

function refreshReady(){
	$('#data_span').css("background-color","#00FF00");
	$('#data_span').attr("title", "New data should be ready. Refresh to see it!");
}

setTimeout(refreshReady , <?=($next_refresh*1000)?>);

</script>

</head>
<body>




<div id="sales_tabs" style="margin:0px;padding:0px">

	<ul>
		<li><a href="#groupstab">Groups</a></li>
		<li><a href="#officestab">Offices</a></li>
		<li><a href="#totalstabs">Totals</a></li>
	</ul>


	<table border="0" width="100%">
	<tr>
		<td><?
			if($combine_users){
				?><input type="button" value="Show all Users" onclick="go('<?=stripurl('combine_users')?>')"><br /><?
			}else{
				?><input type="button" value="Combine Users" onclick="go('<?=stripurl('combine_users').'combine_users'?>')"><br /><?
			}
		?></td>
		<td>

			<input type="button" id="b_refresh" value="Refresh" onclick="window.location.reload()">

		</td>
		<td>

			<span id="data_span">Data: <?=date("g:ia",$last_updated)?></span>

		</td>
		<td>

			Page: <?=date("g:ia")?>

		</td>
	</tr></table>

	<div id="groupstab" style="margin:0px;padding:0px">
		<table border="0" width="100%" style="margin:0px;padding:0px"><?

		$cols = 1;
		$x=0;


		$group_totals = array();

		foreach($groups as $group){
			if($x%$cols==0)echo '<tr valign="top">';

			echo '<td class="lb" width="50%">';

			$group_totals[$group] = renderTable("group", $group);

			echo '</td>';



			$x++;
			if(($x % $cols) == 0)echo '</tr>';
		}

		if($x%$cols != 0){
			echo '<td colspan="'.($cols - ($x%$cols)).'">&nbsp;</td></tr>';
		}

		?></table>
	</div>

	<div id="officestab" style="margin:0px;padding:0px">
		<table border="0" width="100%" style="margin:0px;padding:0px"><?

		$cols = 1;
		$x=0;


		$office_totals = array();

		foreach($offices as $office){
			if($x%$cols==0)echo '<tr valign="top">';

			echo '<td class="lb" width="50%">';

			$office_totals[$office] = renderTable("office", $office);

			echo '</td>';



			$x++;
			if(($x % $cols) == 0)echo '</tr>';
		}

		if($x%$cols != 0){
			echo '<td colspan="'.($cols - ($x%$cols)).'">&nbsp;</td></tr>';
		}

		?></table>
	</div>

	<div id="totalstabs" style="margin:0px;padding:0px">

		<h2>Groups</h2>
		<table border="0" width="100%" id="totals_graphs_table" style="margin:0px;padding:0px">
		<THEAD>
		<tr>
			<th align="left">Group</th>
			<th align="right">Hourly Avg Per User</th>
			<th align="right">Total Per Hour</th>
			<th align="right">Total</th>
		</tr>
		</THEAD>
		<TBODY>
		<?

		$running_hourly = 0;
		$running_perhour= 0;
		$running_total = 0;
		$x=0;
		foreach($groups as $group){

			list($hourly_avg_per_user, $hours_avg, $amt_total, $cnt_total) = $group_totals[$group];

			$per_hour = $amt_total / $hours_avg;

			?><tr>
				<td><?=$group?></td>
				<td align="right">$<?=number_format($hourly_avg_per_user,2)?></td>
				<td align="right">$<?=number_format($per_hour,2)?></td>
				<td align="right">$<?=number_format($amt_total)?></td>
			</tr><?

			$running_hourly += $hourly_avg_per_user;
			$running_perhour += $per_hour;
			$running_total += $amt_total;
			$x++;
		}


		?></TBODY>
		<TFOOT>
			<tr>
				<th>Totals:</th>
				<th align="right">$<?=number_format($running_hourly/$x,2)?></th>
				<th align="right">$<?=number_format($running_perhour,2)?></th>
				<th align="right">$<?=number_format($running_total)?></th>
			</tr>
		</TFOOT>
		</table>

		<script>
		$(document).ready(function() {
		    $('#totals_graphs_table').dataTable({



		        "bPaginate": false,
		        "bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,

		        "aaSorting": [[ 3, "desc" ]],

		        "bInfo": false,
		        "bAutoWidth": false,
			});
		});
		</script>



		<h2>Offices</h2>

		<table border="0" width="100%" id="totals_offices_table" style="margin:0px;padding:0px">
		<THEAD>
		<tr>
			<th align="left">Office</th>
			<th align="right">Hourly Avg Per User</th>
			<th align="right">Total Per Hour</th>
			<th align="right">Total</th>
		</tr>
		</THEAD>
		<TBODY>
		<?

		$running_hourly = 0;
		$running_perhour= 0;
		$running_total = 0;
		$x=0;
		foreach($offices as $office){

			list($hourly_avg_per_user, $hours_avg, $amt_total, $cnt_total) = $office_totals[$office];

			$per_hour = $amt_total / $hours_avg;

			?><tr>
				<td><?=$office?></td>
				<td align="right">$<?=number_format($hourly_avg_per_user,2)?></td>
				<td align="right">$<?=number_format($per_hour,2)?></td>
				<td align="right">$<?=number_format($amt_total)?></td>
			</tr><?

			$running_hourly += $hourly_avg_per_user;
			$running_perhour += $per_hour;
			$running_total += $amt_total;
			$x++;
		}


		?></TBODY>
		<TFOOT>
			<tr>
				<th>Totals:</th>
				<th align="right">$<?=number_format($running_hourly/$x ,2)?></th>
				<th align="right">$<?=number_format($running_perhour,2)?></th>
				<th align="right">$<?=number_format($running_total)?></th>
			</tr>
		</TFOOT>
		</table>

		<script>
		$(document).ready(function() {
		    $('#totals_offices_table').dataTable({



		        "bPaginate": false,
		        "bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,

		        "aaSorting": [[ 3, "desc" ]],


		        "bInfo": false,
		        "bAutoWidth": false,
			});
		});
		</script>


	</div>

</div>

<script>
$(function() {

	$( "#sales_tabs" ).tabs();

	$( "#refreshtab" ).on( "tabsbeforeload", function( event, ui ) {
		window.location.reload();
		event.stopPropagation();
	});

});

applyUniformity();

</script>


</body>
</html>