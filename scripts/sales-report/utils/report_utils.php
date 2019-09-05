<?

	class SaleRecord{
		var $user;
		var $name;
		var $group;
		var $amount;
		var $count;

		function SaleRecord($user,$name, $group, $office, $amount){
			$this->user = $user;
			$this->name = $name;
			$this->group = $group;
			$this->office = $office;
			$this->amount = $amount;
		}
	}

	class ViciHours{
		var $user;
		var $hours;
		function ViciHours($user, $hours){
			$this->user = $user;
			$this->hours = $hours;
		}
	}

	function getOfficeSales($office){

		global $records;

		$sales = array();


		foreach($records as $row){

			if($row->office != $office){
				continue;
			}

			$sales[] = $row;
		}

		return $sales;
	}


	function parseHoursData($hours_file){

		global $combine_users,$hours, $offices, $records, $groups, $sale_totals, $counts;

		$hours = array();
		$hours_xmlobj = simplexml_load_file($hours_file);
		foreach ($hours_xmlobj as $child){
			$user = trim($child->user);

	#print_r($child);

			if($combine_users){

				if(endsWith($user, "2")){
					$user = substr($user, 0, strlen($user)-1);
				}
			}

			$uhrs = $child->hours->asXML();
			//echo $uhrs;

			$uhrs = $child->hours;
	//
	//		if($hours[$user]){

	//			$hours[$user]->hours = (($uhrs + $hours[$user]->hours)/2);
	//
	//		}else{
				$hours[$user] = new ViciHours(strtoupper(trim($user)), $uhrs);
	//		}

		}

	}

	function parseSalesData($sales_file){

		global $combine_users,$showing_offices, $hours, $offices, $records, $groups, $sale_totals, $counts;

		$sales = simplexml_load_file($sales_file);

		$records = array();
		$groups = array();
		$sale_totals = array();
		$counts = array();
		$offices = array();
		$x=0;
		$gidx = 0;
		foreach ($sales as $child){

			$user = strtoupper(trim($child->agent_id));
			$name = trim($child->agent_name);
			$group= trim($child->user_group);
			$office=  trim($child->office);

			// OFFICE SPEFICIED
			if($showing_offices != null){

				// MULTIPLE OFFICES SPECIFIED
				if(is_array($showing_offices)){

					$pass = false;
					foreach($showing_offices as $o){
						if($office == $o){$pass = true;break;}
					}

					if(!$pass)continue;

				}else{
					// SKIP IF OFFICE DOESNT MATCH
					if($office != trim($showing_offices))continue;
				}

			}


			$records[$x] = new SaleRecord($user,$name, $group,$office, $child->sale_amount);

			if(!array_key_exists($user,$sale_totals)){
				$sale_totals[ $user ] = 0;
				$counts[ $user ] = 0;
			}

			if(!in_array($office, $offices)){
				$offices[] = $office;
			}

			if(!in_array($group, $groups)){

				$groups[$gidx++] = $group;

			}


			$sale_totals[ $user ] += $child->sale_amount;
			$counts[ $user ]++;

			$x++;

	//user_group

		}

	}

	function getGroupSales($group){

		global $records;

		$sales = array();


		foreach($records as $row){

			if($row->group != $group){
				continue;
			}

			$sales[] = $row;
		}

		return $sales;
	}

	function getUserHours($user){
		global $hours;

		$user = strtoupper(trim($user));

		foreach($hours as $obj){

			if(strtoupper(trim($obj->user)) == $user){
				return $obj->hours;
			}

		}

		return 0;
	}

	function getUserTotals($sales){

		global $combine_users;

		$output = array();
		$count = array();
		$names = array();
		$groups = array();
		$offices= array();
		foreach($sales as $idx=>$row){
			$user = $row->user;

			if($combine_users){

				if(endsWith($user, "2")){
					$user = substr($user, 0, strlen($user)-1);


					//echo $user." ";
				}
			}

			if(!array_key_exists($user, $output)){
				$output[$user] = 0;
				$count[$user] = 0;
			}

			$output[$user] += $row->amount;
			$count[$user] ++;
			$names[$user] = $row->name;
			$groups[$user] = $row->group;
			$offices[$user]= $row->office;
		}


		$newsales = array();
		$idx=0;
		foreach($output as $user=>$amount){
			$newsales[$idx] = new SaleRecord($user,$names[$user],$groups[$user],$offices[$user], $amount);
			$newsales[$idx]->count = $count[$user];
			$idx++;
		}
		return $newsales;//array($output, $count);
	}

	function startsWith($haystack, $needle){	return $needle === "" || strpos($haystack, $needle) === 0;}
	function endsWith($haystack, $needle){			return $needle === "" || substr($haystack, -strlen($needle)) === $needle;}