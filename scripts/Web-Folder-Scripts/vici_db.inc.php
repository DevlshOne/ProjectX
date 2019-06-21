<?

	/***************************************/
	/* Associative array database INSERT AND RETURN ID */
	function insertID($assoarray,$table){
		$startsql	= "INSERT INTO `$table`(";
		$midsql		= ") VALUES (";
		$endsql		= ")";
		$out = $startsql;
		$x=0;
		foreach($assoarray as $key=>$val){
			$out.= "`$key`";
			$out.=($x+1<count($assoarray))?',':'';

			$midsql.= "'".addslashes($val)."'";
			$midsql.=($x+1<count($assoarray))?',':'';
			$x++;
		}
		$out .= $midsql.$endsql;
		execSQL($out);
		#jsAlert('Inserting into ' . $table . ' in db.inc.php the value ' . $midsql);
		return mysqli_insert_id($_SESSION['db']);
	}
	/***************************************/
	/* Associative array database add */
	function aadd($assoarray,$table){
		$startsql	= "INSERT INTO `$table`(";
		$midsql		= ") VALUES (";
		$endsql		= ")";
		$out = $startsql;
		$x=0;
		foreach($assoarray as $key=>$val){
			$out.= "`$key`";
			$out.=($x+1<count($assoarray))?',':'';

			$midsql.= "'".addslashes($val)."'";
			$midsql.=($x+1<count($assoarray))?',':'';
			$x++;
		}
		$out .= $midsql.$endsql;
		#print $out;
		return execSQL($out);
	}
	/***************************************/
	/* Associative array database edit */
	function aedit($id,$assoarray,$table,$extra_where=""){
		$startsql	= "UPDATE `$table` SET ";
		$endsql		= " WHERE id='$id'";

		$out = $startsql;
		$x=0;
		foreach($assoarray as $key=>$val){
			$out.= "`$key`='".addslashes($val)."'";
			$out.=($x+1<count($assoarray))?',':'';
			$x++;
		}
		$out .= $endsql.$extra_where;

		#jsAlert('OUT to Database from db.inc ' . $out);
		return execSQL($out);
	}
	/***************************************/
	/* Associative array database edit NEED this one cause of StreetID, Wifid... etc*/
	function aeditByField($field,$id,$assoarray,$table){
		$startsql	= "UPDATE `$table` SET ";
		$endsql		= " WHERE $field='$id'";

		$out = $startsql;
		$x=0;
		foreach($assoarray as $key=>$val){
			$out.= "`$key`='".addslashes($val)."'";
			$out.=($x+1<count($assoarray))?',':'';
			$x++;
		}
		$out .= $midsql.$endsql;
		#print $out;
		return execSQL($out);
	}
	/***************************************/
	/* Delete from $table by $id, common deletion */
	function adelete($id,$table){return execSQL("DELETE FROM `$table` WHERE id='$id'");}
	/***************************************/

	function getRESULT($cmd){	return query($cmd,1);}	# Returns  all the records returned.
	function queryROW($cmd)	{	return query($cmd,2);}	# Returns an array of 1 result
	function queryOBJ($cmd)	{	return query($cmd,3);} # Returns an object of first result
	function querySQL($cmd)	{	return query($cmd,4);} # Returns as associative-array(hash) of 1 result
	function queryROWS($cmd){ 	return query($cmd,5); }# Returns the number of rows in a result set
	function fetchROW($cmd)	{ 	return query($cmd,6); }# Returns an associative array that corresponds to the fetched row, or FALSE if there are no more rows.


	/***************************************/
	function query($cmd, $mode){			# with mode = 0 or 1, it will return the result set, all the records returned.
		##print $cmd."<br>";
		$res = mysqli_query($_SESSION['db'],$cmd);
		if(!$mode || $mode == 1){
			return $res;
		}else if($mode == 2){
			return mysqli_fetch_row($res);
		}else if($mode == 3){
			return mysqli_fetch_object($res);
		}else if($mode == 4){
			return mysqli_fetch_array($res);
		}else if($mode == 5){
			return mysqli_num_rows($res);
		}else if($mode == 6){
			return mysqli_fetch_assoc($res);
		}
	}
	/***************************************/
	function execSQL($cmd){
		mysqli_query($_SESSION['db'],$cmd) or die("Error in execSQL(".$cmd."):".mysqli_error($_SESSION['db']));
		if(($cnt=mysqli_affected_rows($_SESSION['db'])) > 0)return $cnt;
		else	return 0;
	}
	/***************************************/
	/* Count rows on table $where */
	function getCount($table,$whereclause){
		$cmd = "SELECT COUNT('".$table.".id') FROM $table $whereclause";
		$row = mysqli_fetch_row(mysqli_query($_SESSION['db'],$cmd));
		return $row[0];
	}
	/***************************************/
	 function buildWhereFromArray($tmpAr, $field,$restrict){
		$inlist="";
		$where='';
		if($tmpAr && !in_array($restrict, $tmpAr)){
			$where=" AND (";
			$lifecnt = count($tmpAr);
			for($x=0;$x<$lifecnt;$x++){
				if($x){
					$where .= " OR ";
				}
				$where .= "".$field." = '".addslashes($tmpAr[$x])."' ";
			}
			$where.=")";
		}
		return $where;
	}
	/***************************************/
	 function buildWhereInList($tmpAr, $field,$restrict){
		$inlist="";
		$where='';
		if($tmpAr && !in_array($restrict, $tmpAr)){
			$lifecnt = count($tmpAr);
			for($x=0;$x<$lifecnt;$x++){
				$inlist.="'".addslashes($tmpAr[$x])."'";
				if($x+1 < $lifecnt){
					$inlist.=",";
				}
			}
			if(strlen($inlist)){
				$where .= " AND ".$field." IN (".$inlist.") ";
			}
		}
		return $where;
	}
	/********************************
	 * Return associated array of objects by key val
	 * 	$stripNum strips unesseccary hash fields (number fields)  (ex: myArray[0] = 2,  myArray['id'] = 2, : $stripNum removes hash/fields like myArray[0]
	 * 	$oneResult returns and array hash if it exits, otherwise a standard array if only 1 result
	 *
	 */
	function queryAR($key, $sql, $stripNum, $oneResult) {
		unset($tempAr);

		$res = getRESULT($sql);
		while($row = mysqli_fetch_array($res)){
			$tempAr[$row[$key]] = $row;
		}

		if($stripNum) { // strip the extra numbered cells (good use when creating XML from array)
			foreach($tempAr as $id=>$info) {
				foreach($info as $hash=>$val) {
					if(is_int($hash)) {
						unset($tempAr[$id][$hash]);
					}
				}
			}
		}

		// User knows that there is only one result... that is really the ONLY time you should use this functionality
		if($oneResult) {
			if(count($tempAr) == 1) {
				foreach($tempAr as $id=>$info) {
					$tempAr = $info;
					break;
				}
			}
		}

		return $tempAr;

	}
