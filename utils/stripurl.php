<?
	## STRIP URL - Removes specific variables from the query and returns a clean url
	## $ignore can be scalar or array()

	function stripurl($ignore=''){

		$url = $_SERVER['PHP_SELF'].'?';

		$arr = explode('&',$_SERVER['QUERY_STRING']);
		foreach($arr as $x=>$a){
			list($key,$val) = explode('=',$a,2);
			if(!$key){unset($arr[$x]);continue;}


			## FILTER OUT THE IGNORE FIELDS
			if(is_array($ignore)){
				foreach($ignore as $i){


					## ALLOWS NAME=>VALUE ARRAYS FOR FILTERING SPECIFIC KEY->VALUE COMBINATIONS
					if(is_array($i)){

						## KEY=>VALUE FILTER
						if($i[0] == $key && $i[1] == $val){unset($arr[$x]);continue 2;}

					}else{
						## KEY ONLY FILTER
						if($i == $key){unset($arr[$x]);continue 2;}
					}
				}
			}else{
				if($ignore == $key){unset($arr[$x]);continue;}
			}
			$arr[$key] = $val;
			unset($arr[$x]);
		}


		foreach($arr as $name=>$val){
			if(!$name) continue;

			$url .= "$name=$val&";
		}


		return $url;
	}
