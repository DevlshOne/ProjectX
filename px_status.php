<?php



	session_start();





	if(!isset($_SESSION['user']) || $_SESSION['user']['priv'] < 5){


		//die("Access denied to this section.");

		header("Location: index.php");
		exit;


	}



	include_once("site_config.php");
	include_once($_SESSION['site_config']['basedir']."dbapi/dbapi.inc.php");

	
	$_SESSION['dbapi']->users->updateLastActionTime();
		




?><!DOCTYPE HTML>
<html>
<head>
	<title>Project X - Server Overview</title>




	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.3/themes/cupertino/jquery-ui.css">
	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
	<script src="//code.jquery.com/ui/1.11.3/jquery-ui.js"></script>

	<script>
		function applyUniformity(){
			$("input:submit, button, input:button").button();
			$("input:text, input:password, input:reset, input:checkbox, input:radio, input:file").uniform();
		}

		$(function() {
			$( "#tabs" ).tabs();
		});
</script>
</head>
<body><?

	$tabidx = 1;

	$pxres = $_SESSION['dbapi']->query("SELECT * FROM servers ORDER BY `name` ASC", 1);

	$px_arr = array();

	while($row = mysqli_fetch_array($pxres, MYSQLI_ASSOC)){

		$px_arr[$row['ip_address']] = $row;

	}


?><div id="tabs" style="width:100%;height:100%">


	<ul>
		<li><a href="#tabs-<?=$tabidx++?>">Prod. LMT</a></li>
		<li><a href="#tabs-<?=$tabidx++?>">Devel. LMT</a></li>
		<li><a href="#tabs-<?=$tabidx++?>">Vicidials</a></li>
<?

		foreach($px_arr as $ip=>$row){

			echo '<li><a href="#tabs-'.($tabidx++).'">'.htmlentities($row['name']).'</a></li>'."\n";

		}


	$tabidx = 1;
?>
	</ul>


	<div id="tabs-<?=$tabidx++?>">

		<iframe src="http://10.101.15.51/dev/" width="100%" height="670"></iframe>

	</div>
	<div id="tabs-<?=$tabidx++?>">

		<iframe src="http://10.101.15.51/dev2/" width="100%" height="670"></iframe>

	</div>
	<div id="tabs-<?=$tabidx++?>">

		<iframe src="http://10.101.15.51/vicidial-test.php" width="100%" height="670"></iframe>

	</div>
	<?

		foreach($px_arr as $ip=>$row){


			?><div id="tabs-<?=($tabidx++)?>">

				<iframe src="http://<?=htmlentities($ip).':2288/Status'?>" width="100%" height="670"></iframe>

			</div><?
		}

	?>




</div>

</body>
</html>