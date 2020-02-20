<?php
/* List Tools - Vicidial List report (total count and possibily more)
 * Written by: Jonathan P Will
 * Created on May 16, 2018
 *
 */

$_SESSION['vici_report'] = new VicidialListReport;

 class VicidialListReport{


	var $warn_limit = 6000000;	// THE WARNING LIMIT, USED FOR COLOR CODING
	var $crit_limit = 6500000;	// CRITICAL LIMIT, USED FOR COLOR CODING

	function generateData(){

		// LOAD THE ACTIVE CLUSTERS INTO AN ARRAY
		$res = query("SELECT id,name FROM vici_clusters WHERE `status`='enabled' ORDER BY `name` ASC",1);
		$clusters = array();
		while($vrow = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$clusters[$vrow['id']] = $vrow;

		}


		// LOOP THROUGH STACK OF VICIDIAL SERVERS
		foreach($clusters as $cluster_id => $vicidb ){

			$vici_cluster_id = $cluster_id;

			// LOCATE WHICH DB INDEX IT IS
			$dbidx = getClusterIndex($cluster_id);

			// CONNECT TO VICIDIAL DB
			connectViciDB($dbidx);


			// EXTRACT LIST TOTAL
			list($tcnt) = queryROW("SELECT COUNT(lead_id) FROM vicidial_list ");//WHERE 1

			// POPULATE THE EXISTING ARRAY WITH THE RESULTS/COUNTS
			$clusters[$cluster_id]['total_count'] = $tcnt;


		} // END FOREACH


		// RETURN THE ENTIRE ARRAY
		return $clusters;

	} // END generateData() Function




	function handleFLOW(){

		$this->makeGUI();

	}


	function makeGUI(){


		?>
        <table border="0" width="100%" class="lb" cellspacing="0">

            <tr class="ui-widget-header">
			<td height="40" class="block-header bg-primary-light">
                <h4 class="block-title">VICIDial List Report</h4>
                <button type="button" class="btn btn-sm btn-info" title="Refresh" onclick="loadSection('?area=list_tools&tool=vici_report&no_script=1')">Refresh</button>
			</td>
		</tr>
		<tr>
			<td align="left" style="padding-left:10px">
				<br />
				<table border="0" width="300" align="left">
				<tr>
					<th class="row2" align="left">Cluster</th>
					<th class="row2" align="right">Total Leads</th>
				</tr><?


				$data = $this->generateData();


				foreach($data as $cluster_id=>$row){



					$colorcode = ($row['total_count'] > $this->crit_limit)?"#ff0000":(($row['total_count'] > $this->warn_limit))?"#FFFF00":"transparent";

					?><tr style="font-size:16px">
						<td align="left"><?=htmlentities($row['name'])?></td>
						<td style="background-color:<?=$colorcode?>" align="right"><?=number_format($row['total_count'])?></td>
					</tr><?

				}


				?><tr valign="bottom">
					<td colspan="2" align="center" height="40">
						Last Generated: <?=date("H:i:s m/d/Y e")?>
					</td>
				</tr>

		</table>
			</td>
		</tr>
		</table>

		<script>
			applyUniformity();
		</script><?

	}


 }




