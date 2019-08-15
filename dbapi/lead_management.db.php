<?
/**
 * Ringing Calls Report SQL Functions
 */



class LeadManagementAPI{

	var $table = "lead_tracking";



	/**
	 * Marks a campaign as deleted
	 */
	function delete($id){

		return $_SESSION['dbapi']->adelete($id,$this->table);
	}


	/**
	 * Get a Name by ID
	 * @param 	$id		The database ID of the record
	 * 	 * @return	assoc-array of the database record
	 */
	function getByID($id){
		$id = intval($id);

		return $_SESSION['dbapi']->querySQL("SELECT * FROM `".$this->table."` ".
						" WHERE id='".$id."' "

					);
	}


	function getCampaignName($id){
		$id = intval($id);

		list($name) = $_SESSION['dbapi']->queryROW("SELECT name FROM `campaigns` WHERE id='".$id."' ");
		return $name;
	}


	/**
	 * getResults($asso_array)

	 * Array Fields:
	 * 	fields	: The select fields for the sql query, * is default
	 *	id		: Int/Array of Ints
	 *  enabled	: String only, "yes"/"no"
	 *

	 *  skip_id : Int/Array of ID's to skip (AND seperated, != operator)
	 *
	 *  order : ORDER BY field, Assoc-array,
	 * 		Example: "order" = array("id"=>"DESC")
	 *  limit : Assoc-Array of 2 keys/values.
	 * 		"count"=>(amount to limit by)
	 * 		"offset"=>(optional, the number of records to skip)
	 */
	function getResults($info){

		$fields = ($info['fields'])?$info['fields']:'*';



		//$sql = "SELECT $fields FROM `".$this->table."` WHERE account_id='".$_SESSION['account']['id']."' ";

		$start_sql = "SELECT $fields FROM `".$this->table."` ";

		$index_suggestion = "";




		// TIME SEARCH
		// array(start time, end time)
		if(is_array($info['time'])){

			$index_suggestion = (stripos($fields, "COUNT") > -1)?"":" IGNORE INDEX(PRIMARY) ";

			$sql =	" WHERE `time` BETWEEN '".intval($info['time'][0])."' AND '".intval($info['time'][1])."' ";

		}else{
			$sql = " WHERE 1 ";
		}



	## ID FIELD SEARCH
		## ARRAY OF id's SEARCH
		if(is_array($info['id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE ID SEARCH
		}else if($info['id']){

			$sql .= " AND `id`='".intval($info['id'])."' ";

		}

	## LEAD ID FIELD SEARCH
		## ARRAY OF id's SEARCH
		if(is_array($info['lead_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['lead_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`lead_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE LEAD ID SEARCH
		}else if($info['lead_id']){

			$sql .= " AND `lead_id`='".intval($info['lead_id'])."' ";

		}



	## OFFICE RESTRICTION/SEARCH
		if(is_array($info['office'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['office'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`office`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE LEAD ID SEARCH
		}else if($info['office']){

			$sql .= " AND `office`='".intval($info['office'])."' ";

		}



	## CAMPAIGN ID
		if(is_array($info['campaign_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['campaign_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`campaign_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE CAMPAIGN ID SEARCH
		}else if($info['campaign_id']){

			$sql .= " AND `campaign_id`='".intval($info['campaign_id'])."' ";

		}


		if(is_array($info['agent_username'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['agent_username'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`agent_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."' ";
			}

			$sql .= ") ";

		## SINGLE SEARCH
		}else if($info['agent_username']){

			$sql .= " AND `agent_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['agent_username'])."' ";

		}



		if(is_array($info['verifier_username'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['verifier_username'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`verifier_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."' ";
			}

			$sql .= ") ";

		## SINGLE SEARCH
		}else if($info['verifier_username']){

			$sql .= " AND `verifier_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['verifier_username'])."' ";

		}

		## AGENT USERNAME SEARCH
//		if(is_array($info['agent_username'])){
//
//			$sql .= " AND (";
//
//			$x=0;
//			foreach($info['agent_username'] as $idx=>$n){
//				if($x++ > 0)$sql .= " OR ";
//
//				$sql .= "(`agent_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."' OR `verifier_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."') ";
//			}
//
//			$sql .= ") ";
//
//		## SINGLE SEARCH
//		}else if($info['agent_username']){
//
//			$sql .= " AND (`agent_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['agent_username'])."' OR `verifier_username`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['agent_username'])."') ";
//
//		}


	### DISPO STATUS SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['status'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['status'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`dispo`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."' ";
			}

			$sql .= ") ";

		## SINGLE STATUS SEARCH
		}else if($info['status']){

			$sql .= " AND `dispo`='".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['status'])."' ";

		}


		### FIRSTNAME
		if(is_array($info['firstname'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['firstname'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`first_name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE PHONE SEARCH
		}else if($info['firstname']){

			$sql .= " AND `first_name` LIKE '".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['firstname'])."' ";

		}



		if(is_array($info['lastname'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['lastname'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`last_name` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE PHONE SEARCH
		}else if($info['lastname']){

			$sql .= " AND `last_name` LIKE '".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['lastname'])."' ";

		}



	### CITY SEARCH
		if(is_array($info['city'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['city'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`city` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE PHONE SEARCH
		}else if($info['city']){

			$sql .= " AND `city` LIKE '".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['city'])."' ";

		}

	### STATE SEARCH
		if(is_array($info['state'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['state'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`state` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE PHONE SEARCH
		}else if($info['state']){

			$sql .= " AND `state` LIKE '".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['state'])."' ";

		}

		if(is_array($info['vici_list_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['vici_list_id'] as $idx=>$sid){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`list_id`='".intval($sid)."' ";
			}

			$sql .= ") ";

		## SINGLE LEAD ID SEARCH
		}else if($info['vici_list_id']){

			$sql .= " AND `list_id`='".intval($info['vici_list_id'])."' ";

		}

	### PHONE SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['phone_number'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['phone_number'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "`phone_num` LIKE '%".mysqli_real_escape_string($_SESSION['dbapi']->db,$n)."%' ";
			}

			$sql .= ") ";

		## SINGLE PHONE SEARCH
		}else if($info['phone_number']){

			$sql .= " AND `phone_num` LIKE '".mysqli_real_escape_string($_SESSION['dbapi']->db,$info['phone_number'])."' ";

		}





	### Cluster SEARCH
		## ARRAY OF STRINGS, OR SEPERATED SEARCH
		if(is_array($info['cluster_id'])){

			$sql .= " AND (";

			$x=0;
			foreach($info['cluster_id'] as $idx=>$n){
				if($x++ > 0)$sql .= " OR ";

				$sql .= "(`vici_cluster_id`='".intval($n)."' OR `verifier_vici_cluster_id`='".intval($n)."')";
			}

			$sql .= ") ";

		## SINGLE CLUSTER SEARCH
		}else if($info['cluster_id']){

			$sql .= " AND (`vici_cluster_id`='".intval($info['cluster_id'])."' OR `verifier_vici_cluster_id`='".intval($info['cluster_id'])."') ";

		}



	## SKIP/IGNORE ID's
		if(isset($info['skip_id'])){

			$sql .= " AND (";

			if(is_array($info['skip_id'])){
				$x=0;
				foreach($info['skip_id'] as $sid){

					if($x++ > 0)$sql .= " AND ";

					$sql .= "`id` != '".intval($sid)."'";
				}

			}else{
				$sql .= "`id` != '".intval($info['skip_id'])."' ";
			}

			$sql .= ")";
		}


	### ORDER BY
		if(is_array($info['order'])){


			$index_suggestion .= " USE INDEX FOR ORDER BY () ";

			$sql .= " ORDER BY ";
			$x=0;
			foreach($info['order'] as $k=>$v){
				if($x++ > 0)$sql .= ",";

				$sql .= "`$k` ".mysqli_real_escape_string($_SESSION['dbapi']->db,$v)." ";
			}

		}

		if(is_array($info['limit'])){

			$sql .= " LIMIT ".
						(($info['limit']['offset'])?$info['limit']['offset'].",":'').
						$info['limit']['count'];

		}


		//echo $sql;

		## RETURN RESULT SET
		return $_SESSION['dbapi']->ROquery($start_sql.' '.$index_suggestion.' '.$sql);
	}



	function getCount(){

		$row = mysqli_fetch_row($this->getResults(
						array(
							"fields" => "COUNT(id)"
						)
					));

		return $row[0];
	}


}
