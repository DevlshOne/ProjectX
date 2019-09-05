<?



	require_once("site_config.php");
	require_once("utils/report_utils.php");
	require_once("utils/stripurl.php");



	$user_xml_file = "/var/www/html/sales/xmldata/users.xml";


	$user_stack = array();

	$user_xmlobj = simplexml_load_file($user_xml_file);
	$x=0;
	foreach ($user_xmlobj as $child){

		$arr = $child->attributes();

		foreach($arr as $key=>$value){
			$user_stack[$x][(string)$key] = (string)$value;
		}

		$x++;
	}

	//print_r($user_stack);





?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
	<!-- Debug info: <?=$next_refresh?> -->
	<title>CCI - User Report</title>
<?
	## CONTAINS ALL THE INCLUDES AND SHIT
	include_once("header.html");

?>
</head>
<body>

<h2>CCI - Users</h2>


<table border="0" width="100%" align="left" id="user_table">
<THEAD>
<tr>
	<th align="left">ID</th>
	<th align="left">Username</th>
	<th>Name</th>
	<th>Level</th>
	<th>Group</th>
</tr>
</THEAD>
<TBODY><?

	foreach($user_stack as $user){

		?><tr>
			<td><?=htmlentities($user['user_id'])?></td>
			<td><?=htmlentities($user['user'])?></td>
			<td align="center"><?=htmlentities($user['full_name'])?></td>
			<td align="center"><?=htmlentities($user['user_level'])?></td>
			<td align="center"><?=htmlentities($user['user_group'])?></td>
		</tr><?

	}


?></TBODY>
<TFOOT>

	<tr>
		<th>User count:</th>
		<th align="left" colspan="4"><?=number_format(count($user_stack))?></th>
	</tr>

</TFOOT>
</table>
<script>
	$(document).ready(function() {
		    $('#user_table').dataTable({

		        "bPaginate": true,
		        "bLengthChange": false,
		        "bFilter": true,
		        "bSort": true,

		        "aaSorting": [[ 1, "asc" ]],
				"iDisplayLength": 20,

		        "bInfo": false,
		        "bAutoWidth": false
		    });
	});
</script>

</body>
</html>

