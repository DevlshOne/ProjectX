<?
/**
 * Core functions for the API system
 *
 */


class API_Functions{


	var $mode = 'xml';

	function __construct($mode=null){

		if(!$mode)
			$this->detectOutputMode();
		else
			$this->mode = $mode;
	}


	function errorOut($msg,$die=true, $error_code=0){


		switch($this->mode){
		default:
		case 'xml':

			echo "<error".(($error_code != 0)?' code="'.$error_code.'" ':'').">".htmlentities($msg)."</error>";

			if($die)exit;

			break;
		case 'json':

			echo 'error:"'.mysqli_real_escape_string($_SESSION['dbapi']->db,$msg).'"';

			if($die)exit;

			break;
		}

	}


	function outputDeleteSuccess(){

		echo '<Result>Success</Result>'."\n";
	}

	function outputCopySuccess() {}

	function outputEditSuccess($id,$warning_msgs=null){

		switch($_SESSION['api']->mode){
		default:
		case 'xml':

			if(!$warning_msgs){

				echo '<EditMode result="success" id="'.$id.'" />'."\n";

			}else{


				echo '<EditMode result="success" id="'.$id.'">'."\n";

				if(is_array($warning_msgs)){

					foreach($warning_msgs as $msg){

						$this->errorOut($msg,false);

					}

				}else{

					$this->errorOut($warning_msgs,false);

				}


				echo '</EditMode>'."\n";
			}

			break;
		case 'json':

			echo '{"Result":"Success", "id":"'.$id.'"}'."\n";
			break;
		}
	}


	function detectOutputMode(){
		$this->mode = ($_REQUEST['mode'] == 'json')?'json':(($_REQUEST['mode']=='csv')?'csv':(($_REQUEST['mode'] == 'raw')?'raw':'xml'));
	}

	function outputFileHeader(){


		## OUTPUT THE FILE HEADER
		switch($this->mode){
		default:
		case 'xml':

			##header("Content-Type: text/xml");

			echo '<?xml version="1.0"?>'."\n";

			break;
		case 'csv':
		case 'json':
		case 'raw':

			## NO HEADER REQUIRED.. THAT I KNOW OF YET!

			break;
		}
	}

	function renderResultSetJSON($tagname, $res){
//	    echo __METHOD__ . $res . PHP_EOL;
		$out = '';
		$newline = "\n";
		$y=0;
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			//$out .= '"'.mysqli_real_escape_string($_SESSION['dbapi']->db,$tagname).'":['.$newline;
			if($y++ > 0)$out .= ",";
			$out .= '['.$newline;
			$x=0;
			foreach($row as $key=>$val){
				if($x++ == 0){
					$out .= "\t{";
				}else{
					$out .= ",";
				}
				$out .= '"'.mysqli_real_escape_string($_SESSION['dbapi']->db,$key).'":"'.mysqli_real_escape_string($_SESSION['dbapi']->db,$val).'"';
			}
			if($x > 0){
				$out .= '}'.$newline;
			}
			$out .= ']'.$newline;
		}
//		$out = json_encode($res);
//		echo var_dump($out);
		return $out;
	}

	function renderSecondaryAjaxXML($tagname,$out_stack){
		$out = '<'.$tagname.' ';
		foreach($out_stack as $idx=>$data){
			$out .= ' data_'.intval($idx).'="'.htmlentities($data).'" ';
		}
		$out .= ' />'."\n";
		return $out;
	}

	function renderResultSetXML($tagname, $res){
		$_SESSION['API_CACHE_STORAGE'][$tagname] = array();
		$out = '';
		$taghead = '<'.$tagname.' ';
		$tagfoot = ' />'."\n";
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			## STORE IN SESSION CACHE, SO THAT SECONDARY AJAX POSTS CAN USE IT, TO SPEED THINGS UP
			if($row['id'] > 0){
				$_SESSION['API_CACHE_STORAGE'][$tagname][$row['id']] = $row;
			}
			$out .= $taghead;
			foreach($row as $key=>$val){
				$val = preg_replace('/[^a-zA-Z0-9.,-=_ $@#^&:;\'"\n\?]/','', $val);
				if($tagname == 'Account' && $key == 'name'){
					// SOME NINJA SHIT I HAD TO DO, FOR THE NEW TAB SYSTEM
					// ( TO POPULATE THE TABS NAME CONTENT )
					$val = '<span id="accnt_hiddenninja_namespan_'.$row['id'].'">'.htmlentities($val).'</span>';
				}
				$out .= $key.'="'.htmlentities($val).'" ';
			}
			$out .= $tagfoot;
		}
		return $out;
	}
}