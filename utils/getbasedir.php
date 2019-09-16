<?

	/**
	 * Gets the base directory of the script that is being executed
	 */
	function getBaseDir(){
//		$out='/';
//		$arr = preg_split("/\//",$_SERVER['PHP_SELF'],-1,PREG_SPLIT_NO_EMPTY);
//
//		foreach($arr as $x=>$a){
//
//
//
//			$out.= $a.'/';
//
//			if(($x+1) >= (count($arr)-1))break;
//
//		}

		$out = dirname($_SERVER["SCRIPT_NAME"]);

		if($out[strlen($out)-1] != '/')$out .= '/';

		#jsAlert($out);

		return $out;
	}
