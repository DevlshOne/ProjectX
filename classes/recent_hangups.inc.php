<?	/***************************************************************
	 * RECENT HANGUPS - Report to show the recent verifier calls dispo'd as "hangup"
	 * (So we can call them back!)
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['recent_hangups'] = new RecentHangups;


class RecentHangups{

	var $timeframe = 600;

	function RecentHangups(){


		## REQURES DB CONNECTION!
		$this->handlePOST();
	}


	function handlePOST(){


	}

	function handleFLOW(){

		if(!checkAccess('recent_hangups')){


			accessDenied("Recent Hangups");

			return;

		}else{

			$this->makeReport();

		}

	}





	function makeReport(){

		include_once("site_config.php");
		include_once("dbapi/dbapi.inc.php");

		include_once("db.inc.php");

		include_once('utils/db_utils.php');

		connectPXDB();

		$stime = time() - $this->timeframe;
		$etime = time() + 5; // JUST IN CASE

		$ofcsql = "";

		$officesstr = "";

		// OFFICE RESTRICTION/SEARCH ABILITY //&&(count($_SESSION['assigned_offices']) > 0)
		if(
			($_SESSION['user']['priv'] < 5) &&
			($_SESSION['user']['allow_all_offices'] != 'yes')
			){

			$ofcsql = " AND `office` IN(";
			$x=0;
			foreach($_SESSION['assigned_offices'] as $ofc){

				if($x++ > 0){
					$ofcsql.= ',';
					$officesstr.= ',';
				}

				$ofcsql.= intval($ofc);
				$officesstr.=intval($ofc);
			}

			$ofcsql.= ") ";

		}else{

		}

		$res = query("SELECT * FROM transfers ".
						" WHERE xfer_time BETWEEN '$stime' AND '$etime' ".
						" AND verifier_dispo='hangup' ".
						$ofcsql
						);


		?><table border="0" width="100%">
		<tr class="ui-widget-header">
			<td height="40" class="pad_left" colspan="6">
				Recent Hangups - Between <?=date("g:ia", $stime).' and '.date("g:ia", $etime)?>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<input type="button" value="Refresh" onclick="loadSection('?area=recent_hangups&no_script=1')">
			</td>
		</tr>
		<tr>
			<th class="row2">XFER Time</th>
			<th class="row2">Agent/Verifier</th>
			<th class="row2">Call Group</th>
			<th class="row2">Phone</th>
			<th class="row2">Agent Amount</th>
			<th class="row2" align="left">Name</th>
		</tr><?

		$colspan = 6;

		if(mysqli_num_rows($res) == 0){

			echo '<tr><td colspan="'.$colspan.'" align="center" class="italic" height="30">No records found';

			if($officesstr){
				echo ' for office(s): '.$officesstr;
			}

			echo '</td></tr>';

		}

		$color=0;
		while($row=mysqli_fetch_array($res, MYSQLI_ASSOC)){

			list($fn,$mi,$ln,$phone) = queryROW("SELECT first_name,middle_initial,last_name,phone_num FROM lead_tracking WHERE id='".$row['lead_tracking_id']."'");

			$class = 'hand row'.($color++%2);

			$onclick=" onclick=\"window.open('?area=lead_management&auto_open_lead=".$row['lead_tracking_id']."')\" ";

		?><tr>
			<td height="20" class="<?=$class?>" <?=$onclick?> align="center"><?=date("g:ia m/d/Y", $row['xfer_time'])?></td>
			<td class="<?=$class?>" <?=$onclick?> align="center"><?=strtoupper($row['agent_username']).' / '.strtoupper($row['verifier_username'])?></td>
			<td class="<?=$class?>" <?=$onclick?> align="center"><?=$row['call_group']?></td>
			<td class="<?=$class?>" <?=$onclick?> align="center"><?=format_phone($phone)?></td>
			<td class="<?=$class?>" <?=$onclick?> align="center">$<?=number_format($row['agent_amount'])?></td>
			<td class="<?=$class?>" <?=$onclick?> ><?=$fn.' '.$mi.' '.$ln?></td>
		</tr><?
		}




		?></table><?


	}



} // END OF CLASS
