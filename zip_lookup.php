<?
	require_once("/var/www/db.inc.php");

	header("Content-Type: text/xml");
	echo "<?xml version=\"1.0\" ?>\n";


	if(!$_REQUEST['zip']){

		die("<Error>No zip specified.</Error>\n");

	}



	$zip = trim($_REQUEST['zip']);


	$res = query("SELECT * FROM zipcodes WHERE zip='".addslashes($zip)."' LIMIT 1");




	if(mysqli_num_rows($res) > 0){//$row){

		$row = mysqli_fetch_array($res, MYSQLI_ASSOC);

		echo "<Zip ";

		foreach($row as $key=>$val){

			echo $key."=\"".$val."\" ";

		}


		echo " />\n";

	}else{
		die("<Error>Zip '".$zip."' not found.</Error>\n");
	}