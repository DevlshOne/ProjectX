<?php

	$in_file = "sales.xml";




	$sales = simplexml_load_file($in_file);


	$output = array();
	$counts = array();
	foreach ($sales as $child){

		$user = trim($child->agent_id);

		if(!array_key_exists($user,$output)){
			$output[ $user ] = 0;
			$counts[ $user ] = 0;
		}

		$output[ $user ] += $child->sale_amount;
		$counts[ $user ]++;

	}

	//print_r($output);

?><html>
<head>

<?
	## CONTAINS ALL THE INCLUDES AND SHIT
	include_once("header.php");

?>


</head>
<body>

	<table border="0" width="500" align="left" id="sales_table" class="lb">
	<THEAD>
	<tr>
		<th align="left">Agent Initials</th>
		<th>Total</th>
		<th>Deal Count</th>
	</tr>
	</THEAD>
	<TBODY><?

	$color=0;
	$class='';
	foreach($output as $key=>$value){


		?><tr>
			<td><?=$key?></td>
			<td align="center">$<?=number_format($value)?></td>
			<td align="center"><?=number_format($counts[$key])?></td>
		</tr><?


	}
	?><TBODY>
	</table>

<script>
$(document).ready(function() {
    /* Build the DataTable with third column using our custom sort functions */
    $('#sales_table').dataTable({
        "bPaginate": false,
        "bLengthChange": false,
        "bFilter": true,

        "aoColumnDefs": [
	      { "bSearchable": false, "aTargets": [ 0 ] }
	    ] },

        "bSort": true,
        "bInfo": false,
        "bAutoWidth": true
    }
    );

});
</script>

</body>
</html>