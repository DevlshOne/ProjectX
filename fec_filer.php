<?php
/**
 * FEC FILER MAIN PAGE - The main entry point
 * Written By: Jonathan Will
 *
 */


	// ENSURE SESSION IS RUNNING, CAUSE WE NEED THAT SHIT
	session_start();





	/**
	 * Database connection made here
	 */
	include_once("site_config.php");

	// GENERIC DB FUNCTIONS
	include_once("db.inc.php");

	include_once("dbapi/dbapi.inc.php");


	/**
	 * Additional includes/requires go here
	 */
	include_once("utils/jsfunc.php");
	include_once("utils/stripurl.php");

	include_once("utils/microtime.php");
	include_once("utils/format_phone.php");
	include_once("utils/rendertime.php");
	include_once("utils/DropDowns.php");
	include_once("utils/functions.php");
	include_once("utils/feature_functions.php");
	include_once("utils/db_utils.php");


	include_once("classes/genericDD.inc.php");
	include_once("classes/interface.inc.php");
	include_once("classes/languages.inc.php");


	// DESTROY THE SESSION/LOGOUT ?o
	if(isset($_REQUEST['o'])){

		session_unset();


		jsRedirect("index.php");
		exit;

	}




	if(!isset($_REQUEST['no_script'])){
		?><!DOCTYPE HTML>
		<html>
		<head>
			<title>FEC FILER - TURBOPACS</title>


			<script src="js/functions.js"></script>

			<link rel="stylesheet" href="css/reset.css"> <!-- CSS reset -->


	<link href='https://fonts.googleapis.com/css?family=Open+Sans:300,400,700' rel='stylesheet' type='text/css'>

			<link rel="stylesheet" type="text/css" href="css/style.css" />
			<link rel="stylesheet" href="css/navstyle.css"> <!-- Resource style -->
			<link rel="stylesheet" type="text/css" href="css/cupertino/jquery-ui-1.10.3.custom.min.css" />

			<link rel="stylesheet" href="themes/default/css/uniform.default.css" media="screen" />

			<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
			<link rel="icon" type="image/x-icon"  href="favicon.ico">

			<link rel="stylesheet" type="text/css" href="css/jquery.dataTables.css" />

	<?/*			<script src="js/jquery-1.9.1.js"></script>**/?>

			<script src="js/jquery-1.10.2.min.js"></script>

			<?/*<script src="//code.jquery.com/jquery-2.2.4.min.js"></script>*/?>

			<script src="js/jquery-ui-1.10.3.custom.min.js"></script>
			<script src="js/jquery.uniform.min.js"></script>


			<?/*<script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>*/?>

			<script src="js/jquery.dataTables.min.js"></script>



			<script src="js/ajax_functions.js"></script>
			<script src="js/functions.js"></script>
			<script src="js/page_system.js"></script>



			<script src="js/modernizr.js"></script> <!-- Modernizr -->
			<script src="js/jquery.menu-aim.js"></script>
			<script src="js/main.js"></script> <!-- Resource jQuery -->


			<script>

				function applyUniformity(){
					$("input:submit, button, input:button").button();
					$("input:text, input:password, input:reset, input:checkbox, input:radio, input:file").uniform();
				}
			</script>
		</head>
		<body>
		<?

	}




	if($_SESSION['user']['priv'] >= 5){

		?><div class="content-wrapper" id="main_content" style="padding-left:15px;padding-top:10px">

		<?/**
		<a href="#">[Step 1]</a> | <a href="#">[Step 2]</a> | <a href="#">[Step 3]</a><br />
		**/


		switch($_REQUEST['area']){
		default:
		case 'fec_filer':

			include_once("classes/fec_filer.inc.php");
			$_SESSION['fec_filer']->handleFLOW();

			break;

		}

		?></div><?

	// USER NOT LOGGED IN, SHOW LOGIN SCREEN
	}else{

		include_once("classes/login.inc.php");

		$_SESSION['login'] = new LoginClass();

		$_SESSION['login']->makeLoginForm();


	}



	?><script>

		applyUniformity();

	</script><?


	// NO_SCRIPT - shuts off extra interface stuff, because page being loaded via AJAX
	if(!isset($_REQUEST['no_script']) ){
		?></body></html><?
	}