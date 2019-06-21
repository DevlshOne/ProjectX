<?	/***************************************************************
	 *	Home class - handles the home interface
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['home'] = new HomeClass;


class HomeClass{

	function HomeClass(){


		$this->handlePOST();
	}




	function handlePOST(){


	}

	function handleFLOW(){

		$this->makeHome();

	}


	function makeHome(){

		?><table style="width:100%;border:0">
		<tr>
			<th height="150" class="lb">[Small graph could go here]</th>
			<th class="lb">[Small graph could go here]</th>
		</tr>
		<tr>
			<th height="150" class="lb">[Small graph could go here]</th>
			<th class="lb">[Small graph could go here]</th>
		</tr>

		</table><?

	}


}
