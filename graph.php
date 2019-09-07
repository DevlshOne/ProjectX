<?
/**
 * GRAPH GENERATOR - OUTPUTS A GRAPHIC, NOT TEXT!
 */


	session_start();


// INCLUDES

	include_once("db.inc.php");
	include_once("utils/microtime.php");
	include_once("utils/db_utils.php");

	require_once 'utils/phplot-6.2.0/phplot.php';


	connectPXDB();




/** START CODE **/


switch($_REQUEST['area']){
default:

	die("No mode specified.");
	break;


case 'user_charts':


	include_once("classes/user_charts.inc.php");


//	// START THE LOAD TIMER
//	$curtime = microtime_float();
	
	$width = (intval($_REQUEST['width']) > 0)?intval($_REQUEST['width']):800;
	$height = (intval($_REQUEST['height']) > 0)?intval($_REQUEST['height']):600;
	
	
	$time_frame = ($_REQUEST['time_frame'])?trim($_REQUEST['time_frame']):'day';

	if($_REQUEST['start_time']){
		$stime = ($_REQUEST['start_time'])?intval($_REQUEST['start_time']): mktime(0,0,0);
	}else{

		$stime = ($_REQUEST['start_date'])?strtotime($_REQUEST['start_date']): mktime(0,0,0);

	}

	$max_mode = (intval($_REQUEST['max_mode']) == 1)?true:false;
	$short_mode = false;
	if($width < 400){
		
		$short_mode = true;
	}
	
	$data = $_SESSION['user_charts']->generateData($time_frame, $stime ,  $max_mode, $short_mode);



	$plot = new PHPlot($width, $height);
	$plot->SetImageBorderType('plain');

	//$plot->SetPlotType('lines');
	$plot->SetPlotType('linepoints');
	$plot->SetDataType('data-data');
	$plot->SetDataValues($data);


	switch($time_frame){
	default:
	case 'day':

		# Main plot title:
		$plot->SetTitle((($max_mode)?"Max ":"Avg ").'Users Logged in - '.date("m/d/Y", $stime) );

		break;
	case 'week':

		# Main plot title:
		$plot->SetTitle((($max_mode)?"Max ":"Avg ").'Users Logged in - Week starting '.date("m/d/Y", $stime) );


		break;
	case 'month':

		# Main plot title:
		$plot->SetTitle((($max_mode)?"Max ":"Avg ").'Users Logged in - Month of '.date("F Y", $stime) );
		break;
	case 'year':

		# Main plot title:
		$plot->SetTitle((($max_mode)?"Max ":"Avg ").'Users Logged in - '.date("Y", $stime). ' Year');

		break;
	}

	//# Make sure Y axis starts at 0:
	//$plot->SetPlotAreaWorld(NULL, 0, NULL, NULL);

	$plot->DrawGraph();

//	print_r($data);



//	// STOP THE LOAD TIMER
//	$endtime = microtime_float();
//
//	// OUTPUT LOAD TIMER RESULTS FOR THE DERPLINGS TO SEE
//	echo "\nExecution took: ".($endtime - $curtime)."ms ";
//


	break;

}
