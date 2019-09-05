<?php
	/***************************************************************
	 *	FEC Filer
	 *	Written By: Jonathan Will
	 *
	 *
	 * Category {of disbursement} Codes:
	 * ( Excerpt from FEC E-FILING SPECIFICATIONS REQUIREMENTS - VERSION 8.2 - Page 12 )
	 	001 Administrative/Salary/Overhead Expenses (e.g., rent, staff salaries, postage, office supplies, equipment, furniture, ballot access fees, petition drives, party fees and legal and accounting expenses)
		002 Travel Expenses - including travel reimbursement expenses (e.g., costs of commercial carrier tickets; reimbursements for use of private vehicles, advance payments for use of corporate aircraft; lodging and meal expenses incurred during travel)
		003 Solicitation and Fundraising Expenses (e.g., costs for direct mail solicitations and fundraising events including printing, mailing lists, consultant fees, call lists, invitations, catering costs and room rental)
		004 Advertising Expenses -including general public political advertising (e.g., purchases of radio/television broadcast/cable time, print advertisements and related production costs)
		005 Polling Expenses
		006 Campaign Materials (e.g., buttons, bumper stickers, brochures, mass mailings, pens, posters, balloons)
		007 Campaign Event Expenses (e.g., costs associated with candidate appearances, campaign rallies, town meetings, phone banks, including catering costs, door to door get-out-the-vote efforts and driving voters to the polls)
		008 Transfers (e.g., to other authorized committees of the same candidate)
		009 Loan Repayments (e.g., repayments of loans made/guaranteed by the candidate or other person)
		010 Refunds of Contributions (e.g., contribution refunds to individuals/ persons, political party committees or other political committees)
		011 Political Contributions (e.g., contributions to other federal candidates and committees, donations to nonfederal candidates and committees)
		012 Donations (e.g., donations to charitable or civic organizations
	 *
	 ***************************************************************/

$_SESSION['fec_filer'] = new FECFiler;


class FECFiler{

	var $table			= 'pacs_filings';
	var $schedule_table	= 'pacs_schedules';
	var $donation_table	= 'pacs_donations';
	var $expenses_table	= 'pacs_expenses';
	var $pacs_table		= 'pacs';


	var $fec_format_version = '8.2';

	var $software_name = "TURBOPACS";
	var $software_version = "0.1";

	var $report_types = array(
						"F3XN"=>"New F3X Filing", // NEW F3X FILING
						"F3XA"=>"Ammended F3X Filing"  // AMMENDED F3X FILING?
						// MORE TO COME LATER, IM SURE
					);

	var $prefixes = array(
		'1st Lt'=>"First Lieutenant",
		'Adm'=>"Admiral",
		'Atty'=>"Attorney",
		'Brother'=>"Brother (religious)",
		'Capt'=>"Captain",
		'Chief'=>"Chief",
		'Cmdr'=>"Commander",
		'Col'=>"Colonel",
		'Dean'=>"University Dean (includes Assistant and Associate)",
		'Dr'=>"Doctor (Medical or Educator)",
		'Elder'=>"Elder (religious)",
		'Father'=>"Father (religious)",
		'Gen'=>"General",
		'Gov'=>"Governor",
		'Hon'=>"Honorable (Cabinet Officer, Commissioner, Congressman, Judge, Supreme Court, United Nations US Delegate, Major, Senator, and Representative)",
		'Lt Col'=>"Lieutenant Colonel",
		'Maj'=>"Major",
		'MSgt'=>"Major/Master Sergeant",
		'Mr'=>"Mister",
		'Mrs'=>"Married Woman",
		'Ms'=>"Single or Married Woman",
		'Prince'=>"Prince",
		'Prof'=>"Professor (includes Assistant and Associate",
		'Rabbi'=>"Rabbi (religious)",
		'Rev'=>"Reverend (religious)",
		'Sister'=>"Sister"
	);


	var $suffixes = array(

		'II'=>"The Second",

		'III'=>"The Third",
		'IV'=>"The Fourth",
		'CPA'=>"Certified Public Accountant",
		'DDS'=>"Doctor of Dental Medicine",
		'Esq'=>"Esquire",
		'JD'=>"Jurist Doctor",
		'Jr'=>"Junior",
		'LLD'=>"Doctor of Laws",
		'MD'=>"Doctor of Medicine",
		'PhD'=>"Doctorate",
		'Ret'=>"Retired from Armed Forces",
		'RN'=>"Registered Nurse",
		'Sr'=>"Senior",
		'DO'=>"Doctor of Osteopathy"

	);

	var $entity_types = array(
						'ORG',
						'IND',
						'CAN',
						'CCM',
						'COM',
						'PAC',
						'PTY'
					);

	var $report_codes = array(

						"Q1",
						"Q2",
						"Q3",
						"YE"

					);


	var $schedule_codes = array(
					"SA11AI",
					"SA11B",
					"SA11C",
					"SA12",
					"SA13",
					"SA14",
					"SA15",
					"SA16",
					"SA17",
					"SA18",


					"SB21B",
					"SB22",
					"SB23",
					"SB26",
					"SB27",
					"SB28A",
					"SB28B",
					"SB28C",
					"SB29"

					);

	var $field_sep = ',';

	var $line_ending = "\r\n";



	function FECFiler(){

		$this->handlePOST();
	}


	function handlePOST(){
//
//print_r($_SESSION['fecdata']);
//
//echo '<br /><br />';
//
//print_r($_POST);

//exit;

		if($_REQUEST['reset_fec_data']){

			$this->initFECArray();

			return;
		}

		if(($step = trim($_REQUEST['go_to_step'])) ){

			$_SESSION['fecdata']['current_step'] = $step;



			jsRedirect(stripurl('go_to_step'));
			exit;
		}


		if(isset($_REQUEST['finalize_donation_uploads'])){

			$this->createItemizedSchedulesFromDonations();

			jsRedirect(stripurl('finalize_donation_uploads'));
			exit;
		}

		if(isset($_REQUEST['finalize_expense_uploads'])){

			$this->createItemizedSchedulesFromExpenses();

			jsRedirect(stripurl('finalize_expense_uploads'));
			exit;
		}


		if(isset($_POST['attaching_previous_report']) && ($id=intval($_REQUEST['previous_filing_id'])) > 0){

			$this->calculateYearToDateTotals($id);

			jsRedirect(stripurl('attach_previous'));
			exit;

		}


		if(($id = intval($_REQUEST['delete_schedule'])) > 0){

			adelete($id, $this->schedule_table);

			jsRedirect(stripurl('delete_schedule'));
			exit;

		}

		// EDITING THE HEADER (can happen on step 2, 3, etc)
		if(isset($_POST['editing_file_header'])){
			/**
			 * [edit_header] =>
[editing_file_header] =>
[form_type] => F3XA
[report_code] => Q1
[start_date_month] => 1
[start_date_day] => 1
[start_date_year] => 2018
[end_date_month] => 3
[end_date_day] => 31
[end_date_year] => 2018
[hdr_fec_report_id] => FEC-1218876
[hdr_fec_report_num] => 1
[hdr_comment] =>
[committee_id] => C00671685
[committee_name] => AUTISM HEAR US NOW LLC
[address1] => One Westbrook Corporate Center
[address2] => Suite 300
[city] => Westchester
[state] => IL
[zip] => 60154
[treasurer_prefix] =>
[treasurer_first_name] => Ollie
[treasurer_middle_name] =>
[treasurer_last_name] => Cappleman
[treasurer_suffix] =>
[election_code] =>
[election_state] =>
[election_date] => 1969-12-31
			 */

			$dat['form_type']			= trim($_REQUEST['form_type']);
			$dat['report_code']			= trim($_REQUEST['report_code']);

			$dat['start_date']			= trim($_REQUEST['start_date_year'].'-'.$_REQUEST['start_date_month'].'-'.$_REQUEST['start_date_day']);
			$dat['end_date']			= trim($_REQUEST['end_date_year'].'-'.$_REQUEST['end_date_month'].'-'.$_REQUEST['end_date_day']);


			$dat['hdr_fec_report_id']	= trim($_REQUEST['hdr_fec_report_id']);
			$dat['hdr_fec_report_num']	= intval($_REQUEST['hdr_fec_report_num']);
			$dat['hdr_comment']			= trim($_REQUEST['hdr_comment']);
			$dat['committee_id']		= trim($_REQUEST['committee_id']);
			$dat['committee_name']		= trim($_REQUEST['committee_name']);
			$dat['address1']			= trim($_REQUEST['address1']);
			$dat['address2']			= trim($_REQUEST['address2']);
			$dat['city']			= trim($_REQUEST['city']);
			$dat['state']			= trim($_REQUEST['state']);
			$dat['zip']			= trim($_REQUEST['zip']);
			$dat['treasurer_prefix']			= trim($_REQUEST['treasurer_prefix']);
			$dat['treasurer_first_name']			= trim($_REQUEST['treasurer_first_name']);
			$dat['treasurer_middle_name']			= trim($_REQUEST['treasurer_middle_name']);
			$dat['treasurer_last_name']			= trim($_REQUEST['treasurer_last_name']);
			$dat['treasurer_suffix']			= trim($_REQUEST['treasurer_suffix']);
			$dat['election_code']			= trim($_REQUEST['election_code']);
			$dat['election_state']			= trim($_REQUEST['election_state']);
			$dat['election_date']			= date("Y-m-d",strtotime($_REQUEST['election_date']) );
//
//			echo nl2br(print_r($dat,1));
//			exit;

//			echo nl2br(print_r($_REQUEST,1));
//			exit;

			aedit($_SESSION['fecdata']['current_file']['id'], $dat, $this->table);


			// LOAD FRESH INTO SESSION
			$this->reloadCurrentFile();



			jsRedirect(stripurl('edit_header'));
			exit;

		}


		if($_POST['step']){

			$curstep = trim($_REQUEST['step']);

			switch($curstep){
			case '1':


				// SAVE THE SETTINGS
				$_SESSION['fecdata']['step1'] = $_POST;

				$_SESSION['fecdata']['current_pac'] = $this->loadPAC($_REQUEST['pac_id']);

				if($_REQUEST['report_mode'] == 'existing'){


					// PROMPT TO SELECT EXISTING
					$_SESSION['fecdata']['current_step'] = '1a';


				}else if($_REQUEST['report_mode'] == 'import'){

					// PROMPT TO SELECT FILE TO IMPORT
					$_SESSION['fecdata']['current_step'] = '1b';

				}else{

					// CREATE FILING RECORD
					$filing_id = $this->createNewFiling();

					// LOAD THE NEWLY CREATED RECORD
					$_SESSION['fecdata']['current_file'] = $this->loadFiling($_SESSION['fecdata']['current_pac']['id'], $filing_id);


					// MOVE TO THE NEXT STEP
					$_SESSION['fecdata']['current_step'] = '2';

				}

				//jsRedirect(stripurl(''));

				break;


			// Submitting a load of existing file!
			case '1a':


				$filing_id = intval($_REQUEST['filing_id']);

				if($filing_id > 0){

					// LOAD FILING
					$_SESSION['fecdata']['current_file'] = $this->loadFiling($_SESSION['fecdata']['current_pac']['id'], $filing_id);

					// MOVE ON TO STEP 2
					$_SESSION['fecdata']['current_step'] = '2';
				}

				//jsRedirect(stripurl(''));

				break;

			// IMPORTING EXISTING FEC FILING
			case '1b':

				if(isset($_REQUEST['importing_file_form'])){

					if($_FILES && $_FILES['fec_file']['error'] == 0){

//						echo nl2br(print_r($_POST,1));
//						echo nl2br(print_r($_FILES,1));
//						exit;

						$result = $this->importF3XFile($_SESSION['fecdata']['current_pac']['id'], $_FILES['fec_file']['tmp_name']);

						if($result > 0){
							jsAlert("Successfully imported form ".$_SESSION['fecdata']['current_file']['form_type'].' '.$_SESSION['fecdata']['current_file']['report_code']);
						}else{
							jsAlert($this->getErrorMessage($result) );
						}


					}else{

						jsAlert("Error uploading files: Error ".$_FILES['fec_file']['error']);

					}

					jsRedirect(stripurl(array('no_script')));
					exit;

				}




				break;

			// SAVING MANUAL SCHEDULE ENTRY
			case '2a':

				$id = intval($_REQUEST['editing_schedule']);


//				echo nl2br(print_r($_POST,1));
//				exit;
/**[step] => 2a
[editing_schedule] => 0
[schedule_code] => SA11AI
[entity_type] => ORG
[date_month] => 7
[date_day] => 7
[date_year] => 2018
[amount] => 234234
[amount_aggregate] => 234
[purpose_description] => saasdfsdfa
[election_code] => asdfa
[election_description] => sdf
[category_code] => asd
[reference_trans_id] => asdf
[reference_sched_name] => asf
[organization_name] => asdfas
[prefix] =>
[first_name] => dfasdf
[middle_name] => af
[last_name] => sdfas
[suffix] =>
[address1] => df
[address2] => as
[city] => asdfasdfdf
[state] => as
[zip] => afd
[employer] => df
[occupation] => asdfas
[committee_fec_id] => as
[committee_name] => asdf
[candidate_fec_id] => df
[candidate_prefix] => Adm
[candidate_first_name] => fasdf
[candidate_middle_name] => asd
[candidate_last_name] => f
[candidate_suffix] =>
[candidate_office] => d
[candidate_state] => f
[candidate_district] => a
[conduit_name] => sdfasd
[conduit_address1] => asdf
[conduit_address2] => fasdf
[conduit_city] => asd
[conduit_state] => sd
[conduit_zip] => asdfasdf
[memo_code] => yes
[memo_description] => sdfasdf
[reference_system_code] => sdfa
*/
				$dat = array();
				$dat['form_type'] = trim($_REQUEST['schedule_code']);

				$dat['date'] = trim($_REQUEST['date_year'].'-'.$_REQUEST['date_month'].'-'.$_REQUEST['date_day']);

				$dat['reference_trans_id'] = trim($_REQUEST['reference_trans_id']);
				$dat['reference_sched_name'] = trim($_REQUEST['reference_sched_name']);

				$dat['entity_type'] = trim($_REQUEST['entity_type']);

				$dat['amount'] = round(trim($_REQUEST['amount']), 2);
				$dat['amount_aggregate'] = round(trim($_REQUEST['amount_aggregate']), 2);

				$dat['purpose_description'] = trim($_REQUEST['purpose_description']);
				$dat['election_code'] = trim($_REQUEST['election_code']);
				$dat['election_description'] = trim($_REQUEST['election_description']);


				$dat['organization_name'] = trim($_REQUEST['organization_name']);
				$dat['prefix'] = trim($_REQUEST['prefix']);
				$dat['first_name'] = trim($_REQUEST['first_name']);
				$dat['middle_name'] = trim($_REQUEST['middle_name']);
				$dat['last_name'] = trim($_REQUEST['last_name']);
				$dat['suffix'] = trim($_REQUEST['suffix']);

				$dat['address1'] = trim($_REQUEST['address1']);
				$dat['address2'] = trim($_REQUEST['address2']);
				$dat['city'] = trim($_REQUEST['city']);
				$dat['state'] = trim($_REQUEST['state']);
				$dat['zip'] = trim($_REQUEST['zip']);


				$dat['employer'] = trim($_REQUEST['employer']);
				$dat['occupation'] = trim($_REQUEST['occupation']);
				$dat['category_code'] = trim($_REQUEST['category_code']);


				$dat['committee_fec_id'] = trim($_REQUEST['committee_fec_id']);
				$dat['committee_name'] = trim($_REQUEST['committee_name']);


				$dat['candidate_fec_id'] = trim($_REQUEST['candidate_fec_id']);
				$dat['candidate_prefix'] = trim($_REQUEST['candidate_prefix']);
				$dat['candidate_first_name'] = trim($_REQUEST['candidate_first_name']);
				$dat['candidate_middle_name'] = trim($_REQUEST['candidate_middle_name']);
				$dat['candidate_last_name'] = trim($_REQUEST['candidate_last_name']);
				$dat['candidate_suffix'] = trim($_REQUEST['candidate_suffix']);
				$dat['candidate_office'] = trim($_REQUEST['candidate_office']);
				$dat['candidate_state'] = trim($_REQUEST['candidate_state']);
				$dat['candidate_district'] = intval($_REQUEST['candidate_district']);

				$dat['conduit_name'] = trim($_REQUEST['conduit_name']);
				$dat['conduit_address1'] = trim($_REQUEST['conduit_address1']);
				$dat['conduit_address2'] = trim($_REQUEST['conduit_address2']);
				$dat['conduit_city'] = trim($_REQUEST['conduit_city']);
				$dat['conduit_state'] = trim($_REQUEST['conduit_state']);
				$dat['conduit_zip'] = trim($_REQUEST['conduit_zip']);

				$dat['memo_code'] = ($_REQUEST['memo_code'] == 'yes')?'yes':'no';
				$dat['memo_description'] = trim($_REQUEST['memo_description']);

				$dat['reference_system_code'] = trim($_REQUEST['reference_system_code']);


				if($id > 0){

					aedit($id, $dat, $this->schedule_table);
				}else{

					$dat['pac_id']		= $_SESSION['fecdata']['current_pac']['id'];
					$dat['filing_id']	= $_SESSION['fecdata']['current_file']['id'];
					$dat['committee_id'] = $_SESSION['fecdata']['current_pac']['committee_id'];

					aadd($dat, $this->schedule_table);
				}

				jsRedirect(stripurl('edit_schedule'));
				exit;


				break;
			// SAVING IMPORT SCHEDULE FORM
			case '2b':

				if(isset($_REQUEST['importing_schedule'])){

					if($_FILES && $_FILES['schedule_file']['error'] == 0){

//						echo nl2br(print_r($_POST,1));
//						echo nl2br(print_r($_FILES,1));
//						exit;

						$cnt = $this->importSchedule(trim($_REQUEST['schedule_code']), $_FILES['schedule_file']['tmp_name'], $_FILES['schedule_file']['name'] );

						jsAlert("Successfully imported ".$cnt." records!");


					}else{

						jsAlert("Error uploading files: Error ".$_FILES['schedule_file']['error']);

					}

					jsRedirect(stripurl(array('import_schedule', 'no_script')));
					exit;

				}

				break;

			## UPLOADING DONOR STUFF
			case '2c':

				if(isset($_REQUEST['uploading_donations'])){

					if($_FILES && $_FILES['donation_file']['error'] == 0){

//						echo nl2br(print_r($_POST,1));
//						echo nl2br(print_r($_FILES,1));
//						exit;

						$cnt = $this->importDonations($_FILES['donation_file']['tmp_name'], $_FILES['donation_file']['name']);//importSchedule(trim($_REQUEST['schedule_code']), $_FILES['schedule_file']['tmp_name'], $_FILES['schedule_file']['name'] );

						jsAlert("Successfully uploaded ".$cnt." records!");


						//	SELECT SUM(amount), * FROM pacs_donations WHERE date between '2018-04-01' AND '2018-06-31' GROUP BY `unique_id`
/**
 *
 *
SELECT SUM(amount) as total_amount, unique_id, first_name,last_name,address1,address2,city,state,zip,employer,occupation FROM pacs_donations
WHERE `date` between '2018-04-01' AND '2018-06-31'
GROUP BY `unique_id`
ORDER BY `total_amount` DESC

SELECT * FROM (
  SELECT SUM(amount) as total_amount, unique_id, first_name,last_name,address1,address2,city,state,zip,employer,occupation FROM pacs_donations
  WHERE `date` between '2018-04-01' AND '2018-06-31'
  GROUP BY `unique_id`
  ORDER BY `total_amount` DESC) as a
WHERE total_amount >= 200
;
 */

					}else{

						jsAlert("Error uploading files: Error ".$_FILES['donation_file']['error']);

					}

					jsRedirect(stripurl(array('upload_donations', 'no_script')));
					exit;

				}

			## UPLOADING EXPENSES/DISPURSEMENTS STUFF
			case '2d':

				if(isset($_REQUEST['uploading_expenses'])){

					if($_FILES && $_FILES['expense_file']['error'] == 0){

//						echo nl2br(print_r($_POST,1));
//						echo nl2br(print_r($_FILES,1));
//						exit;

						$cnt = $this->importExpenses($_FILES['expense_file']['tmp_name'], $_FILES['expense_file']['name']);//importSchedule(trim($_REQUEST['schedule_code']), $_FILES['schedule_file']['tmp_name'], $_FILES['schedule_file']['name'] );

						jsAlert("Successfully uploaded ".$cnt." records!");


					}else{

						jsAlert("Error uploading files: Error ".$_FILES['expense_file']['error']);

					}

					jsRedirect(stripurl(array('upload_expenses', 'no_script')));
					exit;

				}


				break;
			// MOVING ONTO STEP 3 (Schedules Completed)
			case '2':




				if(!isset($_REQUEST['dontadvance'])){

					// MOVE ON TO STEP 3
					$_SESSION['fecdata']['current_step'] = '3';

				}



				break;

			case '3':


//				echo nl2br(print_r($_POST,1));
//				exit;

				// UPDATE FILING FORM
				$dat = array();

				foreach($_POST as $key=>$val){
					$tpos = stripos($key, "col");

					if($tpos !== FALSE && $tpos == 0){
						$dat[$key] = preg_replace("/[^0-9-.]/",'',$val);


						if($dat[$key] == '')$dat[$key] = 0;

//						if($key[3] == 'B')echo '$dat[\''.$key.'\']		= $form_line[$pos++];'."<br />";
					}

				}

//exit;
//				print_r($dat);exit;

				aedit($_SESSION['fecdata']['current_file']['id'], $dat, $this->table);


				// RELOAD FRESH INTO SESSION
				$this->reloadCurrentFile();


				if($_POST['save_continue']){

					$_SESSION['fecdata']['current_step'] = '4';

				}


				break;

			// REVIEW AND FINALIZE STEP
			case '4':

				$dat = array();
				$dat['date_signed'] = trim($_REQUEST['signature_date_year'].'-'.$_REQUEST['signature_date_month'].'-'.$_REQUEST['signature_date_day']);
				aedit($_SESSION['fecdata']['current_file']['id'], $dat, $this->table);


				// RELOAD FRESH INTO SESSION
				$this->reloadCurrentFile();

				break;
			}// END SWITCH(step)

		} // END IF(step posted)

	}

	function initFECArray(){
		$_SESSION['fecdata'] = array();
		$_SESSION['fecdata']['current_step'] = '1';
	}

	function handleFLOW(){

		// INIT THE FEC DATA ARRAY, IF NOT EXISTING YET/FRESH LOAD
		if(!$_SESSION['fecdata']){

			$this->initFECArray();

		}

//echo $_SESSION['fecdata']['current_step'];


		if($_SESSION['fecdata']['current_file']['id'] > 0 && isset($_REQUEST['edit_header'])){

			$this->makeEditFileHeader();

			return;
		}

		switch($_SESSION['fecdata']['current_step']){
		case '1':
		default:

			if($_SESSION['fecdata']['current_file']['id'] > 0){

				$this->makeStepMenu();

			}

			$this->makeStep1();

			break;
		case '1a': // STEP 1a: LOAD EXISTING FILING MODE

			$this->makeStep1a();

			break;
		case '1b': // STEP 1b: UPLOAD PREVIOUS FILING MODE

			$this->makeStep1b();

			break;
		case '2':

			//echo "Step 2 coming soon";

			if(isset($_REQUEST['edit_schedule'])){

				$id = intval(	$_REQUEST['edit_schedule'] );

				$this->makeAddSchedule($id);

			}else if(isset($_REQUEST['import_schedule'])){

				$this->makeImportSchedule();

			}else if(isset($_REQUEST['upload_donations'])){

				$this->makeUploadDonations();

			}else if(isset($_REQUEST['upload_expenses'])){

				$this->makeUploadExpenses();

			}else{

				$this->makeStepMenu();

				$this->makeStep2();

			}



			break;
		case '3':

			if(isset($_REQUEST['attach_previous'])){

				// SELECT PREVIOUS QUARTER REPORT TO USE

				$this->makeAttachPreviousGUI();

			}else{

				$this->makeStepMenu();

				$this->makeStep3();

			}

			break;

		case '4':

			//echo "Step 4";

			$this->makeStepMenu();

			$this->makeStep4();

//			$this->makeViewF3XForm();

			break;
		} // END SWITCH(step)


	} // END handleFLOW()







	/**
	 * Make a DROPDOWN of existing filings, to load, review/edit/ammend?
	 */
	function makeFilingDD($pac_id, $name, $sel, $class, $onchange, $blank_option = 1, $ignore_id){

		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out.= ($class)?' class="'.$class.'" ':'';
		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= '>';

		if($blank_option){
			$out .= '<option value="" '.(($sel == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
		}

		$res = $_SESSION['dbapi']->query("SELECT id,CONCAT(form_type,' - ',report_code,' - #',id) as report_name FROM `".$this->table."` WHERE pac_id='".intval($pac_id)."' ORDER BY `id` DESC", 1);


		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


			// SKIP THIS ID (usually itself)
			if($ignore_id > 0 && $row['id'] == $ignore_id)continue;


			$out .= '<option value="'.htmlentities($row['id']).'" ';
			$out .= ($sel == $row['id'])?' SELECTED ':'';
			$out .= '>'.htmlentities($row['report_name']).'</option>';


		}

		$out .= '</select>';
		return $out;
	}


	function makePACDD($name, $sel, $class, $onchange, $blank_option = 1){

		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out.= ($class)?' class="'.$class.'" ':'';
		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= '>';

		if($blank_option){
			$out .= '<option value="" '.(($sel == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
		}

		$res = $_SESSION['dbapi']->query("SELECT id,`name` FROM `".$this->pacs_table."` ORDER BY `name` ASC", 1);


		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


			$out .= '<option value="'.htmlentities($row['id']).'" ';
			$out .= ($sel == $row['id'])?' SELECTED ':'';
			$out .= '>'.htmlentities($row['name']).'</option>';


		}

		$out .= '</select>';
		return $out;
	}




	function makePrefixesDD($name, $sel, $class, $onchange, $blank_option = 1){

		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out.= ($class)?' class="'.$class.'" ':'';
		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= '>';

		if($blank_option){
			$out .= '<option value="" '.(($sel == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
		}


		foreach($this->prefixes as $code=>$type){


			$out .= '<option value="'.htmlentities($code).'" ';
			$out .= ($sel == $code)?' SELECTED ':'';
			$out .= '>'.htmlentities($code).'</option>'; //.' - '.$type


		}

		$out .= '</select>';
		return $out;
	}

	function makeSuffixesDD($name, $sel, $class, $onchange, $blank_option = 1){

		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out.= ($class)?' class="'.$class.'" ':'';
		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= '>';

		if($blank_option){
			$out .= '<option value="" '.(($sel == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
		}


		foreach($this->suffixes as $code=>$type){


			$out .= '<option value="'.htmlentities($code).'" ';
			$out .= ($sel == $code)?' SELECTED ':'';
			$out .= '>'.htmlentities($code).'</option>'; //.' - '.$type


		}

		$out .= '</select>';
		return $out;
	}

	function makeReportTypeDD($name, $sel, $class, $onchange, $blank_option = 1){

		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out.= ($class)?' class="'.$class.'" ':'';
		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= '>';

		if($blank_option){
			$out .= '<option value="" '.(($sel == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
		}


		foreach($this->report_types as $code=>$type){


			$out .= '<option value="'.htmlentities($code).'" ';
			$out .= ($sel == $code)?' SELECTED ':'';
			$out .= '>'.htmlentities($code.' - '.$type).'</option>';


		}

		$out .= '</select>';
		return $out;
	}




	function makeReportCodeDD($name, $sel, $class, $onchange, $blank_option = 1){

		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out.= ($class)?' class="'.$class.'" ':'';
		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= '>';

		if($blank_option){
			$out .= '<option value="" '.(($sel == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
		}


		foreach($this->report_codes as $code){


			$out .= '<option value="'.htmlentities($code).'" ';
			$out .= ($sel == $code)?' SELECTED ':'';
			$out .= '>'.htmlentities($code).'</option>';


		}

		$out .= '</select>';
		return $out;
	}


	function makeScheduleCodeDD($name, $sel, $class, $onchange, $blank_option = 1){

		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out.= ($class)?' class="'.$class.'" ':'';
		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= '>';

		if($blank_option){
			$out .= '<option value="" '.(($sel == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
		}


		foreach($this->schedule_codes as $code){


			$out .= '<option value="'.htmlentities($code).'" ';
			$out .= ($sel == $code)?' SELECTED ':'';
			$out .= '>'.htmlentities($code).'</option>';


		}

		$out .= '</select>';
		return $out;
	}


	function makeEntityTypesDD($name, $sel, $class, $onchange, $blank_option = 1){

		$out = '<select name="'.$name.'" id="'.$name.'" ';
		$out.= ($class)?' class="'.$class.'" ':'';
		$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
		$out.= '>';

		if($blank_option){
			$out .= '<option value="" '.(($sel == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
		}


		foreach($this->entity_types as $code){


			$out .= '<option value="'.htmlentities($code).'" ';
			$out .= ($sel == $code)?' SELECTED ':'';
			$out .= '>'.htmlentities($code).'</option>';


		}

		$out .= '</select>';
		return $out;
	}



	function loadPAC($pac_id){

		return querySQL("SELECT * FROM `".$this->pacs_table."` WHERE id='".intval($pac_id)."'");
	}



	function calculateYearToDateFields(){

		// DETERMINE CURRENT FILING QUARTER

		switch($_SESSION['fecdata']['current_file']['report_code']){
		case 'Q1':

			// JUST COPY FROM COLUMN A to COLUMN B

			break;
		case 'Q2':

			// ADD Q1 and current Q2 columns TO COLUMN B

			break;

		case 'Q3':

			// TAKE Q2's COLUMN B and add to current Q3 columns


			break;

		case 'YE':

			// TAKE Q3's COLUMN B and add to current Q4 columns

			break;
		}


	}


	function createNewFiling(){

		$dat = array();

		// EXTRACT INFO FROM STEP1 POSTING
		$dat['form_type']		= $_SESSION['fecdata']['step1']['report_type'];
		$dat['report_code']		= $_SESSION['fecdata']['step1']['report_code'];

		// EXTRACT INFO FROM ALREADY LOADED PACS ARRAY
		$dat['pac_id']			= $_SESSION['fecdata']['current_pac']['id'];
		$dat['committee_id']	= $_SESSION['fecdata']['current_pac']['committee_id'];
		$dat['committee_name']	= $_SESSION['fecdata']['current_pac']['name'];
		$dat['request_change_of_address'] = $_SESSION['fecdata']['current_pac']['request_change_of_address'];
		$dat['address1']			= $_SESSION['fecdata']['current_pac']['address1'];
		$dat['address2']			= $_SESSION['fecdata']['current_pac']['address2'];
		$dat['city']				= $_SESSION['fecdata']['current_pac']['city'];
		$dat['state']				= $_SESSION['fecdata']['current_pac']['state'];
		$dat['zip']					= $_SESSION['fecdata']['current_pac']['zip'];

		$dat['qualified_committee'] = $_SESSION['fecdata']['current_pac']['qualified_committee'];

		$dat['treasurer_last_name']		= $_SESSION['fecdata']['current_pac']['treasurer_last_name'];
		$dat['treasurer_first_name']	= $_SESSION['fecdata']['current_pac']['treasurer_first_name'];
		$dat['treasurer_middle_name']	= $_SESSION['fecdata']['current_pac']['treasurer_middle_name'];
		$dat['treasurer_prefix']		= $_SESSION['fecdata']['current_pac']['treasurer_prefix'];
		$dat['treasurer_suffix']		= $_SESSION['fecdata']['current_pac']['treasurer_suffix'];


		$dat['colB_year_for_above']	= $_SESSION['fecdata']['step1']['report_tax_year'];

		switch($dat['report_code']){
		case 'Q1':

			$dat['start_date'] = date("Y-m-d", mktime(0,0,0,		1,1, $_SESSION['fecdata']['step1']['report_tax_year']));
			$dat['end_date'] = date("Y-m-d", 	mktime(23,59,59,	3,31, $_SESSION['fecdata']['step1']['report_tax_year']));

			break;
		case 'Q2':

			$dat['start_date'] = date("Y-m-d", mktime(0,0,0,		4,1, $_SESSION['fecdata']['step1']['report_tax_year']));
			$dat['end_date'] = date("Y-m-d", 	mktime(23,59,59,	6,30, $_SESSION['fecdata']['step1']['report_tax_year']));

			break;
		case 'Q3':
			$dat['start_date'] = date("Y-m-d", mktime(0,0,0,		7,1, $_SESSION['fecdata']['step1']['report_tax_year']));
			$dat['end_date'] = date("Y-m-d", 	mktime(23,59,59,	9,30, $_SESSION['fecdata']['step1']['report_tax_year']));
			break;
		case 'YE':
		case 'Q4':// I DONT THINK Q4 REPORT EXISTS...

			$dat['start_date'] = date("Y-m-d", mktime(0,0,0,		10,1, $_SESSION['fecdata']['step1']['report_tax_year']));
			$dat['end_date'] = date("Y-m-d", 	mktime(23,59,59,	12,31, $_SESSION['fecdata']['step1']['report_tax_year']));
			break;
		}


		aadd($dat, $this->table);


		return mysqli_insert_id($_SESSION['db']);
	}


	function loadFiling($pac_id, $filing_id){

//echo "<br />Calling loadFiling(".$pac_id.", ".$filing_id.")<br />";


		return querySQL("SELECT * FROM `".$this->table."` WHERE pac_id='".intval($pac_id)."' AND id='".intval($filing_id)."'");
	}




	function makeStep1a(){

		?><form method="POST" action="<?=stripurl(array('reset_fec_data','no_script'))?>">

			<input type="hidden" name="step" value="1a" />

		<table border="0" width="700" class="lb">
		<tr>
			<th height="40" class="pad_left ui-widget-header" colspan="2">
				FEC Filer - STEP 1a - Load Existing File - <input type="button" value="Reset/Start over" onclick="location='<?=stripurl('no_script')?>reset_fec_data=1';" />
			</th>
		</tr>
		<tr>
			<th height="30">PAC:</th>
			<td><?=htmlentities($_SESSION['fecdata']['current_pac']['name'])?> - <?=htmlentities($_SESSION['fecdata']['current_pac']['committee_id'])?></td>
		</tr>
		<tr>
			<th height="30">Filing:</th>
			<td><?

				echo $this->makeFilingDD($_SESSION['fecdata']['current_pac']['id'], 'filing_id', '', "", "", 0);

			?></td>
		</tr>
		<tr>
			<td colspan="2" align="center" style="padding-top:10px">

				<input type="submit" value="Continue" />

			</td>
		</tr>

		</table>
		</form><?

	}

	function makeStep1b(){

		?><form method="POST" enctype="multipart/form-data" action="<?=stripurl(array('reset_fec_data','no_script'))?>">
			<input type="hidden" name="importing_file_form" />
			<input type="hidden" name="step" value="1b" />

		<table border="0" width="700" class="lb">
		<tr>
			<th height="40" class="pad_left ui-widget-header" colspan="2">
				FEC Filer - STEP 1b - Upload Previous Filing - <input type="button" value="Reset/Start over" onclick="location='<?=stripurl('no_script')?>reset_fec_data=1';" />
			</th>
		</tr>
		<tr>
			<th height="30">PAC:</th>
			<td><?=htmlentities($_SESSION['fecdata']['current_pac']['name'])?> - <?=htmlentities($_SESSION['fecdata']['current_pac']['committee_id'])?></td>
		</tr>
		<tr>
			<th height="30">Import FEC Form 3X - CSV File:</th>
			<td><input type="file" name="fec_file" id="fec_file"></td>
		</tr>
		<tr>
			<td colspan="2" align="center" style="padding-top:10px">

				<input type="submit" value="Continue" />

			</td>
		</tr>

		</table>
		</form><?

	}



	function makeStepMenu(){


		?><table border="0" width="700">
		<tr>
			<th height="30" class="lb hand ui-widget-header" onclick="go('?go_to_step=1')">
				STEP 1 - Load/New FEC File
			</th>
			<th class="lb hand ui-widget-header" onclick="go('?go_to_step=2')">
				STEP 2 - Schedules (A/B)
			</th>
			<th class="lb hand ui-widget-header" onclick="go('?go_to_step=3')">
				STEP 3 - F3X Form
			</th>
			<th class="lb hand ui-widget-header" onclick="go('?go_to_step=4')">
				STEP 4 - Review/Finalize
			</th>
		</tr>
		</table>
		<br />
		<br /><?
	}



	function makeStep1(){

		?><script>

			function togReportMode(way){

				if(way == 'existing'){
					ieDisplay('existing_filing_tbl', 1);
					ieDisplay('new_filing_tbl', 0);
					ieDisplay('import_filing_tbl', 0);

				}else if(way == 'import'){

					ieDisplay('existing_filing_tbl', 0);
					ieDisplay('new_filing_tbl', 0);
					ieDisplay('import_filing_tbl', 1);

				}else{
					ieDisplay('existing_filing_tbl', 0);
					ieDisplay('new_filing_tbl', 1);
					ieDisplay('import_filing_tbl', 0);
				}
			}

		</script>

		<form method="POST" action="<?=stripurl(array('reset_fec_data','no_script'))?>">

			<input type="hidden" name="step" value="1" />

		<table border="0" width="700" class="lb">
		<tr>
			<th height="40" class="pad_left ui-widget-header" colspan="2">
				FEC Filer - STEP 1
			</th>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<table border="0" align="center">
				<tr>
					<th>PAC:</th>
					<td><?
						echo $this->makePACDD('pac_id', '', "", "", false);
					?></td>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<input type="radio" name="report_mode" value="new" CHECKED onclick="togReportMode(this.value)">NEW Report/Filing<br />
						<input type="radio" name="report_mode" value="existing" onclick="togReportMode(this.value)">Load Existing Report/Filing<br />
						<input type="radio" name="report_mode" value="import" onclick="togReportMode(this.value)">Upload/Import Previous Report/Filing<br />
						<br />
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2" id="new_filing_tbl"  align="center">
				<table border="0" align="center">
				<tr>
					<th>Form Type</th>
					<td><?

						echo $this->makeReportTypeDD('report_type','',"","", false);

					?></td>
				</tr>
				<tr>
					<th>Report Code</th>
					<td><?

						echo $this->makeReportCodeDD('report_code','',"","", false);

					?></td>
				</tr>
				<tr>
					<th>Tax Year</th>
					<td><?

						echo makeNumberDD('report_tax_year',date('Y'),2016,date("Y"),1,false,'',false);

					?></td>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2" id="existing_filing_tbl" class="nod" align="center">

				<center>[Continue to choose existing filing]</center>

			</td>
		</tr>
		<tr>
			<td colspan="2" id="import_filing_tbl" class="nod" align="center">

				<center>[Continue to upload previous filing]</center>

			</td>
		</tr>
		<tr>
			<td colspan="2" align="center" style="padding-top:10px">

				<input type="submit" value="Continue" />

			</td>
		</tr>

		</table>


		<script>

			togReportMode('new');

		</script><?
	} // END makeSTEP1()






	function makeStep2(){

		$this->updateFilingTotals();

//print_r($_SESSION['fecdata']);
/***
 * Array
(
    [current_step] => 2
    [step1] => Array
        (
            [step] => 1
            [pac_id] => 3
            [report_mode] => new
            [report_type] => F3XN
            [report_code] => Q1
            [report_tax_year] => 2018
        )

    [current_pac] => Array
        (
            [0] => 3
            [id] => 3
            [1] => C00671685
            [committee_id] => C00671685
            [2] => AUTISM HEAR US NOW LLC
            [name] => AUTISM HEAR US NOW LLC
            [3] => no
            [request_change_of_address] => no
            [4] => One Westbrook Corporate Center
            [address1] => One Westbrook Corporate Center
            [5] => Suite 300
            [address2] => Suite 300
            [6] => Westchester
            [city] => Westchester
            [7] => IL
            [state] => IL
            [8] => 60154
            [zip] => 60154
            [9] => no
            [qualified_committee] => no
            [10] => Cappleman
            [treasurer_last_name] => Cappleman
            [11] => Ollie
            [treasurer_first_name] => Ollie
            [12] =>
            [treasurer_middle_name] =>
            [13] =>
            [treasurer_prefix] =>
            [14] =>
            [treasurer_suffix] =>
        )

    [current_file] => Array
        (
            [0] => 100023
            [id] => 100023
            [1] => 3
            [pac_id] => 3
            [2] => F3XN
            [form_type] => F3XN
            [3] => C00671685
            [committee_id] => C00671685
            [4] => no
            [request_change_of_address] => no
            [5] => One Westbrook Corporate Center
            [address1] => One Westbrook Corporate Center
            [6] => Suite 300
            [address2] => Suite 300
            [7] => Westchester
            [city] => Westchester
            [8] => IL
            [state] => IL
            [9] => 60154
            [zip] => 60154
            [10] => Q1
            [report_code] => Q1
            [11] =>
            [election_code] =>
            [12] =>
            [election_date] =>
            [13] =>
            [election_state] =>
            [14] =>
            [start_date] =>
            [15] =>
            [end_date] =>
            [16] => no
            [qualified_committee] => no
            [17] => Cappleman
            [treasurer_last_name] => Cappleman
            [18] => Ollie
            [treasurer_first_name] => Ollie
            [19] =>
            [treasurer_middle_name] =>
            [20] =>
            [treasurer_prefix] =>
            [21] =>
            [treasurer_suffix] =>
            [22] =>
            [date_signed] =>
            [23] => 0.00
            [colA_6b_cash_on_hand] => 0.00
            [24] => 0.00
            [colA_6c_total_receipts] => 0.00
            [25] => 0.00
            [colA_6d_subtotal] => 0.00
            [26] => 0.00
            [colA_7_total_disbursements] => 0.00
            [27] => 0.00
            [colA_8_cash_on_hand_close] => 0.00
            [28] => 0.00
            [colA_9_debts_to] => 0.00
            [29] => 0.00
            [colA_10_debts_by] => 0.00
            [30] => 0.00
            [colA_11ai_itemized] => 0.00
            [31] => 0.00
            [colA_11aii_unitemized] => 0.00
            [32] => 0.00
            [colA_11aiii_total] => 0.00
            [33] => 0.00
            [colA_11b_pol_party_committees] => 0.00
            [34] => 0.00
            [colA_11c_other_pacs] => 0.00
            [35] => 0.00
            [colA_11d_total_contributions] => 0.00
            [36] => 0.00
            [colA_12_transfers] => 0.00
            [37] => 0.00
            [colA_13_loans_received] => 0.00
            [38] => 0.00
            [colA_14_loan_repayments_received] => 0.00
            [39] => 0.00
            [colA_15_offsets_refunds] => 0.00
            [40] => 0.00
            [colA_16_fed_contrib_refund] => 0.00
            [41] => 0.00
            [colA_17_other_fed_receipts] => 0.00
            [42] => 0.00
            [colA_18a_trans_nonfed_h3] => 0.00
            [43] => 0.00
            [colA_18b_trans_nonfed_h5] => 0.00
            [44] => 0.00
            [colA_18c_trans_nonfed_total] => 0.00
            [45] => 0.00
            [colA_19_total_receipts] => 0.00
            [46] => 0.00
            [colA_20_total_fed_receipts] => 0.00
            [47] => 0.00
            [colA_21ai_fed_share] => 0.00
            [48] => 0.00
            [colA_21aii_nonfed_share] => 0.00
            [49] => 0.00
            [colA_21b_other_fed_expenditures] => 0.00
            [50] => 0.00
            [colA_21c_total_operating_expenditures] => 0.00
            [51] => 0.00
            [colA_22_trans_affiliated_partys] => 0.00
            [52] => 0.00
            [colA_23_contrib_fed_candidates] => 0.00
            [53] => 0.00
            [colA_24_indep_expenditure] => 0.00
            [54] => 0.00
            [colA_25_coord_expenditures] => 0.00
            [55] => 0.00
            [colA_26_loan_repayments] => 0.00
            [56] => 0.00
            [colA_27_loans_made] => 0.00
            [57] => 0.00
            [colA_28a_individuals] => 0.00
            [58] => 0.00
            [colA_28b_pol_party_committees] => 0.00
            [59] => 0.00
            [colA_28c_other_pacs] => 0.00
            [60] => 0.00
            [colA_28d_total_contrib_refunds] => 0.00
            [61] => 0.00
            [colA_29_other_disbursements] => 0.00
            [62] => 0.00
            [colA_30ai_shared_fed_activity_h6_fed] => 0.00
            [63] => 0.00
            [colA_30aii_shared_fed_activity_nonfed] => 0.00
            [64] => 0.00
            [colA_30b_non_allocatable] => 0.00
            [65] => 0.00
            [colA_30c_total_fed_election_activity] => 0.00
            [66] => 0.00
            [colA_31_total_dibursements] => 0.00
            [67] => 0.00
            [colA_32_total_fed_disbursements] => 0.00
            [68] => 0.00
            [colA_33_total_contributions] => 0.00
            [69] => 0.00
            [colA_34_total_contribution_refunds] => 0.00
            [70] => 0.00
            [colA_35_net_contributions] => 0.00
            [71] => 0.00
            [colA_36_total_fed_op_expenditures] => 0.00
            [72] => 0.00
            [colA_37_offset_to_op_expenditures] => 0.00
            [73] => 0.00
            [colA_38_net_op_expenditures] => 0.00
            [74] => 0.00
            [colB_6a_cash_on_hand] => 0.00
            [75] => 1900
            [colB_year_for_above] => 1900
            [76] => 0.00
            [colB_6c_total_receipts] => 0.00
            [77] => 0.00
            [colB_6d_subtotal] => 0.00
            [78] => 0.00
            [colB_7_total_disbursements] => 0.00
            [79] => 0.00
            [colB_8_cash_on_hand_close] => 0.00
            [80] => 0.00
            [colB_11ai_itemized] => 0.00
            [81] => 0.00
            [colB_11aii_unitemized] => 0.00
            [82] => 0.00
            [colB_11aiii_total] => 0.00
            [83] => 0.00
            [colB_11b_pol_party_committees] => 0.00
            [84] => 0.00
            [colB_11c_other_pacs] => 0.00
            [85] => 0.00
            [colB_11d_total_contributions] => 0.00
            [86] => 0.00
            [colB_12_transfers] => 0.00
            [87] => 0.00
            [colB_13_loans_received] => 0.00
            [88] => 0.00
            [colB_14_loan_repayments_received] => 0.00
            [89] => 0.00
            [colB_15_offsets_refunds] => 0.00
            [90] => 0.00
            [colB_16_fed_contrib_refund] => 0.00
            [91] => 0.00
            [colB_17_other_fed_receipts] => 0.00
            [92] => 0.00
            [colB_18a_trans_nonfed_h3] => 0.00
            [93] => 0.00
            [colB_18b_trans_nonfed_h5] => 0.00
            [94] => 0.00
            [colB_18c_trans_nonfed_total] => 0.00
            [95] => 0.00
            [colB_19_total_receipts] => 0.00
            [96] => 0.00
            [colB_20_total_fed_receipts] => 0.00
            [97] => 0.00
            [colB_21ai_fed_share] => 0.00
            [98] => 0.00
            [colB_21aii_nonfed_share] => 0.00
            [99] => 0.00
            [colB_21b_other_fed_expenditures] => 0.00
            [100] => 0.00
            [colB_21c_total_operating_expenditures] => 0.00
            [101] => 0.00
            [colB_22_trans_affiliated_partys] => 0.00
            [102] => 0.00
            [colB_23_contrib_fed_candidates] => 0.00
            [103] => 0.00
            [colB_24_indep_expenditure] => 0.00
            [104] => 0.00
            [colB_25_coord_expenditures] => 0.00
            [105] => 0.00
            [colB_26_loan_repayments] => 0.00
            [106] => 0.00
            [colB_27_loans_made] => 0.00
            [107] => 0.00
            [colB_28a_individuals] => 0.00
            [108] => 0.00
            [colB_28b_pol_party_committees] => 0.00
            [109] => 0.00
            [colB_28c_other_pacs] => 0.00
            [110] => 0.00
            [colB_28d_total_contrib_refunds] => 0.00
            [111] => 0.00
            [colB_29_other_disbursements] => 0.00
            [112] => 0.00
            [colB_30ai_shared_fed_activity_h6_fed] => 0.00
            [113] => 0.00
            [colB_30aii_shared_fed_activity_nonfed] => 0.00
            [114] => 0.00
            [colB_30b_non_allocatable] => 0.00
            [115] => 0.00
            [colB_30c_total_fed_election_activity] => 0.00
            [116] => 0.00
            [colB_31_total_dibursements] => 0.00
            [117] => 0.00
            [colB_32_total_fed_disbursements] => 0.00
            [118] => 0.00
            [colB_33_total_contributions] => 0.00
            [119] => 0.00
            [colB_34_total_contribution_refunds] => 0.00
            [120] => 0.00
            [colB_35_net_contributions] => 0.00
            [121] => 0.00
            [colB_36_total_fed_op_expenditures] => 0.00
            [122] => 0.00
            [colB_37_offset_to_op_expenditures] => 0.00
            [123] => 0.00
            [colB_38_net_op_expenditures] => 0.00
        )

)
 */
		?><script>


			function displayEditScheduleDialog(id){

				var objname = 'dialog-modal-edit_schedule';


				if(id > 0){
					$('#'+objname).dialog( "option", "title", 'Editing Schedule #'+id  );
				}else{
					$('#'+objname).dialog( "option", "title", 'Adding new Schedule' );
				}



				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("fec_filer.php?edit_schedule="+id+"&no_script");

				$('#'+objname).dialog('option', 'position', 'center');
			}


			function displayImportScheduleDialog(){

				var objname = 'dialog-modal-import_schedule';



				$('#'+objname).dialog( "option", "title", 'Import Schedule(s)' );




				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("fec_filer.php?import_schedule&no_script");

				$('#'+objname).dialog('option', 'position', 'center');
			}

			function displayUploadDonationsDialog(){

				var objname = 'dialog-modal-upload_donations';



				$('#'+objname).dialog( "option", "title", 'Upload Donations' );




				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("fec_filer.php?upload_donations&no_script");

				$('#'+objname).dialog('option', 'position', 'center');
			}


			function displayUploadExpensesDialog(){

				var objname = 'dialog-modal-upload_expenses';



				$('#'+objname).dialog( "option", "title", 'Upload Expenses' );




				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("fec_filer.php?upload_expenses&no_script");

				$('#'+objname).dialog('option', 'position', 'center');
			}

		</script>

		<div id="dialog-modal-edit_schedule" title="Add/Edit Schedule">
		</div>

		<div id="dialog-modal-import_schedule" title="Import Schedules">

		</div>

		<div id="dialog-modal-upload_donations" title="Upload Donations">

		</div>

		<div id="dialog-modal-upload_expenses" title="Upload Expenses">

		</div>


		<form method="POST" action="<?=stripurl(array('reset_fec_data','no_script'))?>">

			<input type="hidden" name="step" value="2" />

		<table border="0" width="700" class="lb">
		<tr>
			<th height="40" class="pad_left ui-widget-header" colspan="2">
				FEC Filer - #<?=$_SESSION['fecdata']['current_file']['id']?> - STEP 2 <input type="button" value="Reset/Start over" onclick="location='<?=stripurl('no_script')?>reset_fec_data=1';" />
			</th>
		</tr>
		<tr>
			<td colspan="2"><?

				$this->makeViewFileHeader();

			?></td>
		</tr>
		<tr>
			<td colspan="2" align="center" style="padding-bottom:10px">

				<input type="submit" value="Continue to Step 3" />

			</td>
		</tr>
		<tr>
			<td colspan="2"><?

				$this->makeDonationGUI();

			?><td>
		</tr>
		<tr>
			<td colspan="2"><?

				$this->makeExpenseGUI();

			?><td>
		</tr>
		<tr>
			<td colspan="2"><?

				$this->makeScheduleList();

			?></td>
		</tr>
		</form>
		</table>

		<script>



			 $("#dialog-modal-edit_schedule").dialog({
				autoOpen: false,
				width: 780,
				height: 565,
				modal: false,
				draggable:true,
				resizable: false
			});

			$("#dialog-modal-import_schedule").dialog({
				autoOpen: false,
				width: 420,
				height: 160,
				modal: false,
				draggable:true,
				resizable: false
			});

			$("#dialog-modal-upload_donations").dialog({
				autoOpen: false,
				width: 420,
				height: 160,
				modal: false,
				draggable:true,
				resizable: false
			});

			$("#dialog-modal-upload_expenses").dialog({
				autoOpen: false,
				width: 420,
				height: 160,
				modal: false,
				draggable:true,
				resizable: false
			});
		</script><?
	} // END makeStep2()


	function makeStep3(){

		$this->updateFilingTotals();

//print_r($_SESSION['fecdata']['current_file']);exit;

		?><script>

			function displayAttachPreviousDialog(){

				var objname = 'dialog-modal-attach_previous';

				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("fec_filer.php?attach_previous=1&no_script");

				$('#'+objname).dialog('option', 'position', 'center');
			}

		</script>
		<div id="dialog-modal-attach_previous" title="Attach Previous Quarter Filing">
		</div>

		<form method="POST" action="<?=stripurl(array('reset_fec_data','no_script'))?>">

			<input type="hidden" name="step" value="3" />

		<table border="0" width="700" class="lb">
		<tr>
			<th height="40" class="pad_left ui-widget-header" colspan="2">
				FEC Filer - #<?=$_SESSION['fecdata']['current_file']['id']?> - STEP 3 <input type="button" value="Reset/Start over" onclick="location='<?=stripurl('no_script')?>reset_fec_data=1';" />
			</th>
		</tr>
		<tr>
			<td colspan="2"><?

				$this->makeViewFileHeader();

			?></td>
		</tr>
		<tr>
			<td colspan="2" align="center" style="padding-bottom:10px">

				<input type="submit" value="Save Changes" />

				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

				<a href="ajax.php?mode=download_fec_form" target="_blank"><input type="button" value="Download Current Form" /></a>

				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

				<input type="submit" name="save_continue" value="Save &amp; Continue" />


			</td>
		</tr>
		<tr>
			<td colspan="2"><?

				$this->makeF3XForm();

			?></td>
		</tr>
		<tr>
			<td colspan="2" align="center" style="padding-bottom:10px">

				<input type="submit" value="Save Changes" />

			</td>
		</tr>
		<?



		?></form>
		</table>
		<script>


			$("#dialog-modal-attach_previous").dialog({
				autoOpen: false,
				width: 420,
				height: 160,
				modal: false,
				draggable:true,
				resizable: false
			});

		</script><?
	}

	function makeStep4(){


		?><form method="POST" action="<?=stripurl(array('reset_fec_data','no_script'))?>">

			<input type="hidden" name="step" value="4" />

		<table border="0" width="700" class="lb">
		<tr>
			<th height="40" class="pad_left ui-widget-header" colspan="2">
				FEC Filer - #<?=$_SESSION['fecdata']['current_file']['id']?> - STEP 4 <input type="button" value="Reset/Start over" onclick="location='<?=stripurl('no_script')?>reset_fec_data=1';" />
			</th>
		</tr>
		<tr>
			<td colspan="2"><?

				$this->makeViewFileHeader();

			?></td>
		</tr>
		<tr>
			<td align="center" style="padding-bottom:10px">


				<table border="0">
				<tr>
					<th>Add signature date:</th>
					<td><?


						if($_SESSION['fecdata']['current_file']['date_signed']){
							$d_time = strtotime($_SESSION['fecdata']['current_file']['date_signed']);
						}else{
							$d_time = time();
						}

						echo makeTimebar("signature_date_",1,null,false,$d_time,"");
					?></td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<input type="submit" value="Sign &amp; Finalize" />
					</td>
				</tr>
				</table>

			</td>
			<td align="center" style="padding-bottom:10px">

				<a href="ajax.php?mode=download_fec_form" target="_blank"><input type="button" value="Download Current Form (.CSV)" /></a><br />

				<a href="ajax.php?mode=download_fec_form&format=fec" target="_blank"><input type="button" value="Download Current Form (.FEC)" /></a>



			</td>
		</tr>
		<tr>
			<td colspan="2"><?

				$this->makeViewF3XForm();

			?></td>
		</tr>
		<?



		?></form>
		</table><?
	}



	function makeViewF3XForm(){

		$row = $_SESSION['fecdata']['current_file'];
		$color=0;


		?><table border="0" width="100%">
		<tr>
			<th class="row2" align="left">Category</th>
			<th class="row2" align="right">Column A</th>
			<th class="row2" align="right">Column B</th>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">6(a) Cash on Hand beginning Jan 1st <?=$row['colB_year_for_above']?></th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_6a_cash_on_hand'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">6(b) Cash on Hand beginning</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_6b_cash_on_hand'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">6(c) Total Receipts (Line 19)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_6c_total_receipts'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_6c_total_receipts'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">6(d) Subtotal (6b + 6c)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_6d_subtotal'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_6d_subtotal'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">7. Total Disbursements (31)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_7_total_disbursements'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_7_total_disbursements'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">8. Cash on Hand at Close</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_8_cash_on_hand_close'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_8_cash_on_hand_close'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">9. Debts to</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_9_debts_to'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">10. Debts by</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_10_debts_by'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(a)i  Itemized (Sch A Totals)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_11ai_itemized'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_11ai_itemized'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(a)ii  Unitemized</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_11aii_unitemized'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_11aii_unitemized'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(a)iii Total (11ai + 11aii)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_11aiii_total'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_11aiii_total'],2)?></td>
		</tr>

		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(b) Political Party Committees</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_11b_pol_party_committees'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_11b_pol_party_committees'],2)?></td>

		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(c) Other Political Committees (PACs)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_11c_other_pacs'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_11c_other_pacs'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(d) Total Contributions (11aiii + 11b + 11c)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_11d_total_contributions'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_11d_total_contributions'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">12. Transfers from Affiliated/Other Party Cmtes</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_12_transfers'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_12_transfers'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">13. All Loans Received</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_13_loans_received'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_13_loans_received'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">14. Loan Repayments Received</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_14_loan_repayments_received'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_14_loan_repayments_received'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">15. Offsets to Operating Expenditures (refunds)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_15_offsets_refunds'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_15_offsets_refunds'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">16. Refunds of Federal Contributions</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_16_fed_contrib_refund'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_16_fed_contrib_refund'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">17. Other Federal Receipts (dividends)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_17_other_fed_receipts'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_17_other_fed_receipts'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">18(a) Transfers from Nonfederal Account (H3)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_18a_trans_nonfed_h3'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_18a_trans_nonfed_h3'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">18(b) Transfers from Non-Federal (Levin - H5)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_18b_trans_nonfed_h5'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_18b_trans_nonfed_h5'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">18(c) Total Non-Federal Transfers (18a+18b)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_18c_trans_nonfed_total'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_18c_trans_nonfed_total'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">19. Total Receipts (11d+12+13+14+15+16+17+18c)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_19_total_receipts'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_19_total_receipts'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">20. Total Federal Receipts (19 - 18c)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_20_total_fed_receipts'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_20_total_fed_receipts'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">21(a)i  Federal Share</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_21ai_fed_share'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_21ai_fed_share'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">21(a)ii  Non-Federal Share</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_21aii_nonfed_share'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_21aii_nonfed_share'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">21(b)  Other Federal Operating Expenditures</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_21b_other_fed_expenditures'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_21b_other_fed_expenditures'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">21(c)  Total Operating Expenditures (21ai + 21aii + 21b)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_21c_total_operating_expenditures'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_21c_total_operating_expenditures'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">22. Transfers to Affiliated/Other Party Cmtes</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_22_trans_affiliated_partys'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_22_trans_affiliated_partys'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">23. Contributions to Federal Candidates/Cmtes</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_23_contrib_fed_candidates'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_23_contrib_fed_candidates'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">24. Independent Expenditures</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_24_indep_expenditure'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_24_indep_expenditure'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">25. Coordinated Expend made by Party Cmtes</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_25_coord_expenditures'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_25_coord_expenditures'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">26. Loan Repayments</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_26_loan_repayments'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_26_loan_repayments'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">27. Loans Made</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_27_loans_made'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_27_loans_made'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">28(a) Individuals/Persons</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_28a_individuals'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_28a_individuals'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">28(b) Political Party Committees</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_28b_pol_party_committees'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_28b_pol_party_committees'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">28(c) Other Political Committee</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_28c_other_pacs'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_28c_other_pacs'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">28(d) Total Contributions Refunds (28a + 28b + 28c)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_28d_total_contrib_refunds'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_28d_total_contrib_refunds'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">29. Other Disbursements</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_29_other_disbursements'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_29_other_disbursements'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">30(a)i  Shared Federal Activity (H6) Fed Share</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_30ai_shared_fed_activity_h6_fed'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_30ai_shared_fed_activity_h6_fed'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">30(a)ii Shared Federal Activity (H6) Non-Fed</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_30aii_shared_fed_activity_nonfed'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_30aii_shared_fed_activity_nonfed'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">30(b) Non-Allocable 100% Fed Election Activity</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_30b_non_allocatable'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_30b_non_allocatable'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">30(c) Total Federal Election Activity</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_30c_total_fed_election_activity'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_30c_total_fed_election_activity'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">31. Total Disbursements (21c + 22-27 + 28d + 29)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_31_total_dibursements'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_31_total_dibursements'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">32. Total Federal Disbursements (31 - (21aii + 30aii))</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_32_total_fed_disbursements'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_32_total_fed_disbursements'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">33. Total Contributions (11d)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_33_total_contributions'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_33_total_contributions'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">34. Total Contribution Refunds (28d)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_34_total_contribution_refunds'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_34_total_contribution_refunds'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">35. Net Contributions (11d - 28d)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_35_net_contributions'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_35_net_contributions'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">36. Total Federal Operating Expenditures (21ai + 21b)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_36_total_fed_op_expenditures'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_36_total_fed_op_expenditures'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">37. Offsets to Operating Expenditures (15)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_37_offset_to_op_expenditures'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_37_offset_to_op_expenditures'],2)?></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">38. Net Operating Expenditures (21ai + 21b - 15)</th>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colA_38_net_op_expenditures'],2)?></td>
			<td class="row<?=($color%2)?>" align="right">$<?=number_format($row['colB_38_net_op_expenditures'],2)?></td>
		</tr>
		</table><?

	}



	function makeF3XForm(){

		$row = $_SESSION['fecdata']['current_file'];

		$color=0;

		?><script>

			function copyFieldOver(field_name){

				$('#'+field_name).val( $('#span_'+field_name).html() );
			}





		</script>




		<table border="0" width="100%">
		<tr>
			<th class="row2" align="left">Field</th>
			<th class="row2" align="right">Calculated</th>
			<th class="row2" align="right">Column A</th>
			<th class="row2" align="right">
				Column B<br />
				<input type="button" value="Attach Previous" style="font-size:10px" onclick="displayAttachPreviousDialog()" />

			</th>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">6(a) Cash on Hand beginning Jan 1st <input type="text" name="colB_year_for_above" id="colB_year_for_above" size="4" maxlength="4" value="<?=$row['colB_year_for_above']?>" style="text-align:center" /></th>
			<td class="row<?=($color%2)?>" align="right">

				<?/*<input type="button" value="Use Calculated Values" />*/?>

			</td>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_6a_cash_on_hand" id="colB_6a_cash_on_hand" size="12" value="<?=number_format($row['colB_6a_cash_on_hand'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">6(b) Cash on Hand beginning</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_6b_cash_on_hand" id="colA_6b_cash_on_hand" size="12" value="<?=number_format($row['colA_6b_cash_on_hand'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">6(c) Total Receipts (Line 19)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_6c_total_receipts" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_6c_total_receipts')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_6c_total_receipts'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_6c_total_receipts" id="colA_6c_total_receipts" size="12" value="<?=number_format($row['colA_6c_total_receipts'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_6c_total_receipts" id="colB_6c_total_receipts" size="12" value="<?=number_format($row['colB_6c_total_receipts'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">6(d) Subtotal (6b + 6c)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_6d_subtotal" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_6d_subtotal')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_6d_subtotal'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_6d_subtotal" id="colA_6d_subtotal" size="12" value="<?=number_format($row['colA_6d_subtotal'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_6d_subtotal" id="colB_6d_subtotal" size="12" value="<?=number_format($row['colB_6d_subtotal'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">7. Total Disbursements (31)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_7_total_disbursements" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_7_total_disbursements')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_31_total_dibursements'],2);

				?></span>


			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_7_total_disbursements" id="colA_7_total_disbursements" size="12" value="<?=number_format($row['colA_7_total_disbursements'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_7_total_disbursements" id="colB_7_total_disbursements" size="12" value="<?=number_format($row['colB_7_total_disbursements'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">8. Cash on Hand at Close</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_8_cash_on_hand_close" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_8_cash_on_hand_close')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_8_cash_on_hand_close'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_8_cash_on_hand_close" id="colA_8_cash_on_hand_close" size="12" value="<?=number_format($row['colA_8_cash_on_hand_close'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_8_cash_on_hand_close" id="colB_8_cash_on_hand_close" size="12" value="<?=number_format($row['colB_8_cash_on_hand_close'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">9. Debts to</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_9_debts_to" id="colA_9_debts_to" size="12" value="<?=number_format($row['colA_9_debts_to'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">10. Debts by</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_10_debts_by" id="colA_10_debts_by" size="12" value="<?=number_format($row['colA_10_debts_by'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(a)i  Itemized (Sch A Totals)</th>
			<td class="row<?=($color%2)?>" align="right">
				$<span id="span_colA_11ai_itemized" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_11ai_itemized')"><?
//
					echo number_format($_SESSION['fecdata']['total_calculations']['colA_11ai_itemized'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_11ai_itemized" id="colA_11ai_itemized" size="12" value="<?=number_format($row['colA_11ai_itemized'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_11ai_itemized" id="colB_11ai_itemized" size="12" value="<?=number_format($row['colB_11ai_itemized'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(a)ii  Unitemized</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_11aii_unitemized" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_11aii_unitemized')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['donors_total_unitemized'],2);

				?></span><?




			?></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_11aii_unitemized" id="colA_11aii_unitemized" size="12" value="<?=number_format($row['colA_11aii_unitemized'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_11aii_unitemized" id="colB_11aii_unitemized" size="12" value="<?=number_format($row['colB_11aii_unitemized'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(a)iii Total (11ai + 11aii)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_11aiii_total" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_11aiii_total')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_11aiii_total'],2);

				?></span><?

			?></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_11aiii_total" id="colA_11aiii_total" size="12" value="<?=number_format($row['colA_11aiii_total'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_11aiii_total" id="colB_11aiii_total" size="12" value="<?=number_format($row['colB_11aiii_total'],2)?>" style="text-align:right" /></td>
		</tr>

		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(b) Political Party Committees</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_11b_pol_party_committees" id="colA_11b_pol_party_committees" size="12" value="<?=number_format($row['colA_11b_pol_party_committees'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_11b_pol_party_committees" id="colB_11b_pol_party_committees" size="12" value="<?=number_format($row['colB_11b_pol_party_committees'],2)?>" style="text-align:right" /></td>

		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(c) Other Political Committees (PACs)</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_11c_other_pacs" id="colA_11c_other_pacs" size="12" value="<?=number_format($row['colA_11c_other_pacs'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_11c_other_pacs" id="colB_11c_other_pacs" size="12" value="<?=number_format($row['colB_11c_other_pacs'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">11(d) Total Contributions (11aiii + 11b + 11c)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_11d_total_contributions" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_11d_total_contributions')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_11d_total_contributions'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_11d_total_contributions" id="colA_11d_total_contributions" size="12" value="<?=number_format($row['colA_11d_total_contributions'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_11d_total_contributions" id="colB_11d_total_contributions" size="12" value="<?=number_format($row['colB_11d_total_contributions'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">12. Transfers from Affiliated/Other Party Cmtes</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_12_transfers" id="colA_12_transfers" size="12" value="<?=number_format($row['colA_12_transfers'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_12_transfers" id="colB_12_transfers" size="12" value="<?=number_format($row['colB_12_transfers'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">13. All Loans Received</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_13_loans_received" id="colA_13_loans_received" size="12" value="<?=number_format($row['colA_13_loans_received'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_13_loans_received" id="colB_13_loans_received" size="12" value="<?=number_format($row['colB_13_loans_received'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">14. Loan Repayments Received</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_14_loan_repayments_received" id="colA_14_loan_repayments_received" size="12" value="<?=number_format($row['colA_14_loan_repayments_received'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_14_loan_repayments_received" id="colB_14_loan_repayments_received" size="12" value="<?=number_format($row['colB_14_loan_repayments_received'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">15. Offsets to Operating Expenditures (refunds)</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_15_offsets_refunds" id="colA_15_offsets_refunds" size="12" value="<?=number_format($row['colA_15_offsets_refunds'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_15_offsets_refunds" id="colB_15_offsets_refunds" size="12" value="<?=number_format($row['colB_15_offsets_refunds'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">16. Refunds of Federal Contributions</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_16_fed_contrib_refund" id="colA_16_fed_contrib_refund" size="12" value="<?=number_format($row['colA_16_fed_contrib_refund'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_16_fed_contrib_refund" id="colB_16_fed_contrib_refund" size="12" value="<?=number_format($row['colB_16_fed_contrib_refund'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">17. Other Federal Receipts (dividends)</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_17_other_fed_receipts" id="colA_17_other_fed_receipts" size="12" value="<?=number_format($row['colA_17_other_fed_receipts'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_17_other_fed_receipts" id="colB_17_other_fed_receipts" size="12" value="<?=number_format($row['colB_17_other_fed_receipts'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">18(a) Transfers from Nonfederal Account (H3)</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_18a_trans_nonfed_h3" id="colA_18a_trans_nonfed_h3" size="12" value="<?=number_format($row['colA_18a_trans_nonfed_h3'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_18a_trans_nonfed_h3" id="colB_18a_trans_nonfed_h3" size="12" value="<?=number_format($row['colB_18a_trans_nonfed_h3'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">18(b) Transfers from Non-Federal (Levin - H5)</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_18b_trans_nonfed_h5" id="colA_18b_trans_nonfed_h5" size="12" value="<?=number_format($row['colA_18b_trans_nonfed_h5'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_18b_trans_nonfed_h5" id="colB_18b_trans_nonfed_h5" size="12" value="<?=number_format($row['colB_18b_trans_nonfed_h5'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">18(c) Total Non-Federal Transfers (18a+18b)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_18c_trans_nonfed_total" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_18c_trans_nonfed_total')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_18c_trans_nonfed_total'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_18c_trans_nonfed_total" id="colA_18c_trans_nonfed_total" size="12" value="<?=number_format($row['colA_18c_trans_nonfed_total'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_18c_trans_nonfed_total" id="colB_18c_trans_nonfed_total" size="12" value="<?=number_format($row['colB_18c_trans_nonfed_total'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">19. Total Receipts (11d+12+13+14+15+16+17+18c)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_19_total_receipts" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_19_total_receipts')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_19_total_receipts'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_19_total_receipts" id="colA_19_total_receipts" size="12" value="<?=number_format($row['colA_19_total_receipts'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_19_total_receipts" id="colB_19_total_receipts" size="12" value="<?=number_format($row['colB_19_total_receipts'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">20. Total Federal Receipts (19 - 18c)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_20_total_fed_receipts" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_20_total_fed_receipts')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_20_total_fed_receipts'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_20_total_fed_receipts" id="colA_20_total_fed_receipts" size="12" value="<?=number_format($row['colA_20_total_fed_receipts'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_20_total_fed_receipts" id="colB_20_total_fed_receipts" size="12" value="<?=number_format($row['colB_20_total_fed_receipts'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">21(a)i  Federal Share</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_21ai_fed_share" id="colA_21ai_fed_share" size="12" value="<?=number_format($row['colA_21ai_fed_share'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_21ai_fed_share" id="colB_21ai_fed_share" size="12" value="<?=number_format($row['colB_21ai_fed_share'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">21(a)ii  Non-Federal Share</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_21aii_nonfed_share" id="colA_21aii_nonfed_share" size="12" value="<?=number_format($row['colA_21aii_nonfed_share'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_21aii_nonfed_share" id="colB_21aii_nonfed_share" size="12" value="<?=number_format($row['colB_21aii_nonfed_share'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">21(b)  Other Federal Operating Expenditures</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_21b_other_fed_expenditures" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_21b_other_fed_expenditures')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['expenses_total'],2);

					//echo number_format($_SESSION['fecdata']['total_calculations']['colA_21b_other_fed_expenditures'],2);

				?></span><?
			?></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_21b_other_fed_expenditures" id="colA_21b_other_fed_expenditures" size="12" value="<?=number_format($row['colA_21b_other_fed_expenditures'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_21b_other_fed_expenditures" id="colB_21b_other_fed_expenditures" size="12" value="<?=number_format($row['colB_21b_other_fed_expenditures'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">21(c)  Total Operating Expenditures (21ai + 21aii + 21b)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_21c_total_operating_expenditures" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_21c_total_operating_expenditures')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_21c_total_operating_expenditures'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_21c_total_operating_expenditures" id="colA_21c_total_operating_expenditures" size="12" value="<?=number_format($row['colA_21c_total_operating_expenditures'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_21c_total_operating_expenditures" id="colB_21c_total_operating_expenditures" size="12" value="<?=number_format($row['colB_21c_total_operating_expenditures'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">22. Transfers to Affiliated/Other Party Cmtes</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_22_trans_affiliated_partys" id="colA_22_trans_affiliated_partys" size="12" value="<?=number_format($row['colA_22_trans_affiliated_partys'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_22_trans_affiliated_partys" id="colB_22_trans_affiliated_partys" size="12" value="<?=number_format($row['colB_22_trans_affiliated_partys'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">23. Contributions to Federal Candidates/Cmtes</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_23_contrib_fed_candidates" id="colA_23_contrib_fed_candidates" size="12" value="<?=number_format($row['colA_23_contrib_fed_candidates'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_23_contrib_fed_candidates" id="colB_23_contrib_fed_candidates" size="12" value="<?=number_format($row['colB_23_contrib_fed_candidates'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">24. Independent Expenditures</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_24_indep_expenditure" id="colA_24_indep_expenditure" size="12" value="<?=number_format($row['colA_24_indep_expenditure'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_24_indep_expenditure" id="colB_24_indep_expenditure" size="12" value="<?=number_format($row['colB_24_indep_expenditure'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">25. Coordinated Expend made by Party Cmtes</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_25_coord_expenditures" id="colA_25_coord_expenditures" size="12" value="<?=number_format($row['colA_25_coord_expenditures'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_25_coord_expenditures" id="colB_25_coord_expenditures" size="12" value="<?=number_format($row['colB_25_coord_expenditures'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">26. Loan Repayments</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_26_loan_repayments" id="colA_26_loan_repayments" size="12" value="<?=number_format($row['colA_26_loan_repayments'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_26_loan_repayments" id="colB_26_loan_repayments" size="12" value="<?=number_format($row['colB_26_loan_repayments'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">27. Loans Made</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_27_loans_made" id="colA_27_loans_made" size="12" value="<?=number_format($row['colA_27_loans_made'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_27_loans_made" id="colB_27_loans_made" size="12" value="<?=number_format($row['colB_27_loans_made'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">28(a) Individuals/Persons</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_28a_individuals" id="colA_28a_individuals" size="12" value="<?=number_format($row['colA_28a_individuals'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_28a_individuals" id="colB_28a_individuals" size="12" value="<?=number_format($row['colB_28a_individuals'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">28(b) Political Party Committees</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_28b_pol_party_committees" id="colA_28b_pol_party_committees" size="12" value="<?=number_format($row['colA_28b_pol_party_committees'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_28b_pol_party_committees" id="colB_28b_pol_party_committees" size="12" value="<?=number_format($row['colB_28b_pol_party_committees'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">28(c) Other Political Committee</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_28c_other_pacs" id="colA_28c_other_pacs" size="12" value="<?=number_format($row['colA_28c_other_pacs'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_28c_other_pacs" id="colB_28c_other_pacs" size="12" value="<?=number_format($row['colB_28c_other_pacs'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">28(d) Total Contributions Refunds (28a + 28b + 28c)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_28d_total_contrib_refunds" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_28d_total_contrib_refunds')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_28d_total_contrib_refunds'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_28d_total_contrib_refunds" id="colA_28d_total_contrib_refunds" size="12" value="<?=number_format($row['colA_28d_total_contrib_refunds'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_28d_total_contrib_refunds" id="colB_28d_total_contrib_refunds" size="12" value="<?=number_format($row['colB_28d_total_contrib_refunds'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">29. Other Disbursements</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_29_other_disbursements" id="colA_29_other_disbursements" size="12" value="<?=number_format($row['colA_29_other_disbursements'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_29_other_disbursements" id="colB_29_other_disbursements" size="12" value="<?=number_format($row['colB_29_other_disbursements'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">30(a)i  Shared Federal Activity (H6) Fed Share</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_30ai_shared_fed_activity_h6_fed" id="colA_30ai_shared_fed_activity_h6_fed" size="12" value="<?=number_format($row['colA_30ai_shared_fed_activity_h6_fed'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_30ai_shared_fed_activity_h6_fed" id="colB_30ai_shared_fed_activity_h6_fed" size="12" value="<?=number_format($row['colB_30ai_shared_fed_activity_h6_fed'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">30(a)ii Shared Federal Activity (H6) Non-Fed</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_30aii_shared_fed_activity_nonfed" id="colA_30aii_shared_fed_activity_nonfed" size="12" value="<?=number_format($row['colA_30aii_shared_fed_activity_nonfed'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_30aii_shared_fed_activity_nonfed" id="colB_30aii_shared_fed_activity_nonfed" size="12" value="<?=number_format($row['colB_30aii_shared_fed_activity_nonfed'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">30(b) Non-Allocable 100% Fed Election Activity</th>
			<td class="row<?=($color%2)?>" align="right">&nbsp;</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_30b_non_allocatable" id="colA_30b_non_allocatable" size="12" value="<?=number_format($row['colA_30b_non_allocatable'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_30b_non_allocatable" id="colB_30b_non_allocatable" size="12" value="<?=number_format($row['colB_30b_non_allocatable'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">30(c) Total Federal Election Activity</th>
			<td class="row<?=($color%2)?>" align="right">

				<?/*$<span id="span_colA_30c_total_fed_election_activity" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_30c_total_fed_election_activity')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_30c_total_fed_election_activity'],2);

				?></span>*/?>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_30c_total_fed_election_activity" id="colA_30c_total_fed_election_activity" size="12" value="<?=number_format($row['colA_30c_total_fed_election_activity'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_30c_total_fed_election_activity" id="colB_30c_total_fed_election_activity" size="12" value="<?=number_format($row['colB_30c_total_fed_election_activity'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">31. Total Disbursements (21c + 22-27 + 28d + 29)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_31_total_dibursements" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_31_total_dibursements')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_31_total_dibursements'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_31_total_dibursements" id="colA_31_total_dibursements" size="12" value="<?=number_format($row['colA_31_total_dibursements'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_31_total_dibursements" id="colB_31_total_dibursements" size="12" value="<?=number_format($row['colB_31_total_dibursements'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">32. Total Federal Disbursements (31 - (21aii + 30aii))</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_32_total_fed_disbursements" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_32_total_fed_disbursements')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_32_total_fed_disbursements'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_32_total_fed_disbursements" id="colA_32_total_fed_disbursements" size="12" value="<?=number_format($row['colA_32_total_fed_disbursements'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_32_total_fed_disbursements" id="colB_32_total_fed_disbursements" size="12" value="<?=number_format($row['colB_32_total_fed_disbursements'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">33. Total Contributions (11d)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_33_total_contributions" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_33_total_contributions')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_33_total_contributions'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_33_total_contributions" id="colA_33_total_contributions" size="12" value="<?=number_format($row['colA_33_total_contributions'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_33_total_contributions" id="colB_33_total_contributions" size="12" value="<?=number_format($row['colB_33_total_contributions'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">34. Total Contribution Refunds (28d)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_34_total_contribution_refunds" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_34_total_contribution_refunds')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_34_total_contribution_refunds'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_34_total_contribution_refunds" id="colA_34_total_contribution_refunds" size="12" value="<?=number_format($row['colA_34_total_contribution_refunds'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_34_total_contribution_refunds" id="colB_34_total_contribution_refunds" size="12" value="<?=number_format($row['colB_34_total_contribution_refunds'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">35. Net Contributions (11d - 28d)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_35_net_contributions" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_35_net_contributions')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_35_net_contributions'],2);

				?></span>
			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_35_net_contributions" id="colA_35_net_contributions" size="12" value="<?=number_format($row['colA_35_net_contributions'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_35_net_contributions" id="colB_35_net_contributions" size="12" value="<?=number_format($row['colB_35_net_contributions'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">36. Total Federal Operating Expenditures (21ai + 21b)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_36_total_fed_op_expenditures" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_36_total_fed_op_expenditures')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_36_total_fed_op_expenditures'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_36_total_fed_op_expenditures" id="colA_36_total_fed_op_expenditures" size="12" value="<?=number_format($row['colA_36_total_fed_op_expenditures'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_36_total_fed_op_expenditures" id="colB_36_total_fed_op_expenditures" size="12" value="<?=number_format($row['colB_36_total_fed_op_expenditures'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">37. Offsets to Operating Expenditures (15)</th>
			<td class="row<?=($color%2)?>" align="right">

				$<span id="span_colA_37_offset_to_op_expenditures" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_37_offset_to_op_expenditures')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_37_offset_to_op_expenditures'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_37_offset_to_op_expenditures" id="colA_37_offset_to_op_expenditures" size="12" value="<?=number_format($row['colA_37_offset_to_op_expenditures'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_37_offset_to_op_expenditures" id="colB_37_offset_to_op_expenditures" size="12" value="<?=number_format($row['colB_37_offset_to_op_expenditures'],2)?>" style="text-align:right" /></td>
		</tr>
		<tr>
			<th class="row<?=(++$color%2)?>" align="left">38. Net Operating Expenditures (21ai + 21b - 15)</th>
			<td class="row<?=($color%2)?>" align="right">


				$<span id="span_colA_38_net_op_expenditures" class="hand" style="border-bottom: 1px solid #000" onclick="copyFieldOver('colA_38_net_op_expenditures')"><?

					echo number_format($_SESSION['fecdata']['total_calculations']['colA_38_net_op_expenditures'],2);

				?></span>

			</td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colA_38_net_op_expenditures" id="colA_38_net_op_expenditures" size="12" value="<?=number_format($row['colA_38_net_op_expenditures'],2)?>" style="text-align:right" /></td>
			<td class="row<?=($color%2)?>" align="right">$<input type="text" name="colB_38_net_op_expenditures" id="colB_38_net_op_expenditures" size="12" value="<?=number_format($row['colB_38_net_op_expenditures'],2)?>" style="text-align:right" /></td>
		</tr>






		</table>

		<?

	}


	function makeEditFileHeader(){

		$file = $_SESSION['fecdata']['current_file'];

		?><form method="POST" action="<?=stripurl(array('reset_fec_data','no_script'))?>">
			<input type="hidden" name="editing_file_header" />

		<table border="0" width="100%" style="border-spacing: 5px;border-collapse: separate;">
		<tr>
			<th align="left">Form Type:</th>
			<td>
				<input type="text" size="4" maxlength="4" name="form_type" value="<?=htmlentities($file['form_type'])?>" />
				&nbsp;&nbsp;&nbsp;&nbsp;
				Report Code:
				<input type="text" size="3" maxlength="3" name="report_code" value="<?=htmlentities($file['report_code'])?>" />
			</td>
		</tr>
		<tr>
			<th align="left">Time Frame:</th>
			<td><?

				if($file['start_date']){
					$d_time = strtotime($file['start_date']);
				}else{
					$d_time = time();
				}

				echo makeTimebar("start_date_",1,null,false,$d_time,"");

				?> through <?

				if($file['end_date']){
					$d_time = strtotime($file['end_date']);
				}else{
					$d_time = time();
				}

				echo makeTimebar("end_date_",1,null,false,$d_time,"");


			?></td>
		</tr>
		<tr>
			<th align="left">Report ID/Number<br />(Amendments):</th>
			<td>
				<input type="text" size="15" maxlength="16" name="hdr_fec_report_id" value="<?=htmlentities($file['hdr_fec_report_id'])?>" />
				/
				<input type="text" size="3" maxlength="3" name="hdr_fec_report_num" value="<?=intval($file['hdr_fec_report_num'])?>" />

			</td>
		</tr>
		<tr>
			<th align="left">Header Comment:</th>
			<td>
				<input type="text" size="30" maxlength="200" name="hdr_comment" value="<?=htmlentities($file['hdr_comment'])?>" />
			</td>
		</tr>

		<tr>
			<th align="left">Committee ID:</th>
			<td>
				<input type="text" size="9" maxlength="9" name="committee_id" value="<?=htmlentities($file['committee_id'])?>" />
				 -
				<input type="checkbox" name="qualified_committee" value="yes" <?=($file['qualified_committee'] == 'yes')?" CHECKED ":''?> />Qualified Committee?
			</td>
		</tr>
		<tr>
			<th align="left">Committee Name:</th>
			<td>
				<input type="text" size="35" maxlength="200" name="committee_name" value="<?=htmlentities($file['committee_name'])?>" />

			</td>
		</tr>
		<tr valign="top">
			<th align="left">Address:</th>
			<td>
				<input type="checkbox" name="request_change_of_address" value="yes" <?=($file['request_change_of_address'] == 'yes')?" CHECKED ":''?> />Request Change of Address?<br />

				<input name="address1" type="text" size="30" value="<?=htmlentities($file['address1'])?>" title="Address line 1"><br />
				<input name="address2" type="text" size="30" value="<?=htmlentities($file['address2'])?>" title="Address line 2">
			</td>
		</tr>
		<tr>
			<th align="left">City/State/Zip:</th>
			<td nowrap>
				<input name="city" type="text" size="10" maxlength="30" value="<?=htmlentities($file['city'])?>" title="City">,
				<input name="state" type="text" size="2" maxlength="2" value="<?=htmlentities($file['state'])?>" title="State (2 letter)">
				<input name="zip" type="text" size="8" maxlength="10" value="<?=htmlentities($file['zip'])?>" title="Zip Code">
			</td>
		</tr>
		<tr>
			<th align="left">Treasurer:</th>
			<td nowrap><?


				echo $this->makePrefixesDD('treasurer_prefix', $file['treasurer_prefix'], '', "", '[Prefix]');

				?><input type="text" name="treasurer_first_name" maxlength="20" size="10" value="<?=htmlentities($file['treasurer_first_name'])?>" title="First Name"/>
				<input type="text" name="treasurer_middle_name" maxlength="20" size="2" value="<?=htmlentities($file['treasurer_middle_name'])?>" title="Middle Name" />
				<input type="text" name="treasurer_last_name" maxlength="30" size="10" value="<?=htmlentities($file['treasurer_last_name'])?>" title="Last Name" /><?


				echo $this->makeSuffixesDD('treasurer_suffix', $file['treasurer_suffix'], '', "", '[Suffix]');



			?></td>
		</tr>

		<tr>
			<th align="left">Election code/state/date:</th>
			<td><?
				$tmptime = strtotime($file['election_date']);

			?>
				<input name="election_code" type="text" size="5" maxlength="5" value="<?=htmlentities($file['election_code'])?>" title="Election Code">
				 /
				<input name="election_state" type="text" size="2" maxlength="2" value="<?=htmlentities($file['election_state'])?>" title="Election State">
				 /
				<input name="election_date" type="text" size="10" maxlength="12" value="<?=($tmptime < 0)?'':htmlentities($file['election_date'])?>" title="Election Date (Text, Y-m-d format)">
			</td>
		</tr>

		<tr>
			<td colspan="2" align="center">
				<input type="submit" value="Save Header Changes" />
			</td>
		</tr>
		</table>
		</form><?

	}

	function makeViewFileHeader(){

		$file = $_SESSION['fecdata']['current_file'];

		$amendment_mode = false;

		// CHECK IF THE LAST DIGIT OF THE FORM_TYPE ENDS WITH 'A' (for amendment)
		if($file['form_type'][strlen($file['form_type'])-1] == 'A'){
			$amendment_mode = true;
		}

		?><script>

			function displayEditHeaderDialog(){

				var objname = 'dialog-modal-edit_header';

				$('#'+objname).dialog("open");

				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');

				$('#'+objname).load("fec_filer.php?edit_header&no_script");

				$('#'+objname).dialog('option', 'position', 'center');
			}

		</script>

		<div id="dialog-modal-edit_header" title="Edit Header">
		</div>






		<table border="0" width="100%">
		<tr>
			<td>
				<table border="0" style="font-size:14px;border-spacing: 5px;border-collapse: separate;">
				<tr>
					<td colspan="2">

						<input type="button" value="Edit Header" style="font-size:11px" onclick="displayEditHeaderDialog()">

					</td>
				</tr>
				<tr>
					<th align="left">Form Type:</th>
					<td><?

						echo 	$file['form_type'].' - '.
								$file['report_code'];

					?></td>
				</tr>
				<tr>
					<th align="left">Time Frame:</th>
					<td><?

						if($file['start_date']){
							echo $file['start_date'].' to '.$file['end_date'];
						}else{
							echo '[None specified yet]';
						}

					?></td>
				</tr><?

				if($amendment_mode == true){
					?><tr>
						<th align="left">Report ID/Number:</th>
						<td><?=htmlentities($file['hdr_fec_report_id'])?> / <span title="Report Number"><?=($file['hdr_fec_report_num'])?></span></td>
					</tr><?
				}


				if($file['hdr_comment']){
					?><tr>
						<th align="left">Header Comments:</th>
						<td><?=htmlentities($file['hdr_comment'])?></span></td>
					</tr><?
				}


				?><tr>
					<th align="left">PAC:</th>
					<td><?=htmlentities($file['committee_id'])?> - <?=htmlentities($file['committee_name'])?></td>
				</tr>
				<tr>
					<th align="left">Qualified Committee?</th>
					<td><?=$file['qualified_committee']?></td>
				</tr>
				<tr valign="top">
					<th align="left">Address:</th>
					<td><?

						if($file['request_change_of_address'] == 'yes'){
							echo '[CHANGE OF ADDRESS REQUESTED]<br />';
						}

						echo $file['address1'].'<br />';
						echo ($file['address2'])?$file['address2'].'<br />':'';
						echo $file['city'].', '.$file['state'].' '.$file['zip'].'<br />';


					?></td>
				</tr>

				<tr>
					<th height="25" align="left">Treasurer:</th>
					<td><?

						echo ($file['treasurer_prefix'])?$file['treasurer_prefix'].' ':'';

						echo $file['treasurer_last_name'].', '.$file['treasurer_first_name'];

						echo ($file['treasurer_middle_name'])?' '.$file['treasurer_middle_name']:'';

						echo ($file['treasurer_suffix'])?' '.$file['treasurer_suffix']:'';


					?></td>
				</tr>
				<tr>
					<th height="25" align="left">Date Signed:</th>
					<td><?

						if($file['date_signed']){
							echo $file['date_signed'];
						}else{
							echo '[Not signed yet]';
						}

					?></td>
				</tr>
				</table>
			</td>
			<td>

				[Report summary/quick totals]

			</td>
		</tr>
		</table>


		<script>

			$("#dialog-modal-edit_header").dialog({
				autoOpen: false,
				width: 600,
				height: 420,
				modal: false,
				draggable:true,
				resizable: false
			});



		</script>
		<?
	}


	function loadSchedule($id){
		$id = intval($id);
		return querySQL("SELECT * FROM `".$this->schedule_table."` WHERE pac_id='".intval($_SESSION['fecdata']['current_pac']['id'])."' AND id='$id'");
	}

	function loadSchedules(){

		$schedules = array();

		$sql = "SELECT * FROM `".$this->schedule_table."` ".
				" WHERE pac_id='".intval($_SESSION['fecdata']['current_pac']['id'])."' AND filing_id='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
				" ORDER BY form_type ASC, organization_name ASC,last_name ASC, date ASC";


//		echo $sql;

		$res = query($sql,1);

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
			$schedules[$row['id']] = $row;
		}
//print_r($schedules);
		return $schedules;
	}


	function makeAddSchedule($id){

		$row = $this->loadSchedule($id);

		?><form method="POST" enctype="multipart/form-data" action="<?=stripurl(array('reset_fec_data','no_script'))?>">
			<input type="hidden" name="step" value="2a" />

			<input type="hidden" name="editing_schedule" value="<?=htmlentities($id)?>" />

		<table border="0" width="100%">
		<tr>
			<td colspan="2">

				<table border="0" width="100%">
				<tr valign="top">
					<td style="border-right:1px dotted #ccc">
						<table border="0">
						<tr>
							<th align="left">Schedule Type:</th>
							<td><?

								echo $this->makeScheduleCodeDD('schedule_code', $row['form_type'], "", "", 0);

							?></td>
						</tr>
						<tr>
							<th align="left">Entity Type:</th>
							<td><?

								echo $this->makeEntityTypesDD('entity_type', $row['entity_type'], "","", 0);

							?></td>
						</tr>
						</table>
					</td>
					<td style="border-right:1px dotted #ccc">
						<table border="0">
						<tr>
							<th align="left">Date:</th>
							<td><?

								if($row['date']){
									$d_time = strtotime($row['date']);
								}else{
									$d_time = time();
								}

								echo makeTimebar("date_",1,null,false,$d_time,"");

							?></td>
						</tr>
						<tr>
							<th align="left">Amount:</th>
							<td>$<input type="text" size="10" name="amount" value="<?=htmlentities($row['amount'])?>" /></td>
						</tr>
						<tr>
							<th align="left">Amount (AGGREGATE):</th>
							<td>$<input type="text" size="10" name="amount_aggregate" value="<?=htmlentities($row['amount_aggregate'])?>" /></td>
						</tr>
						<tr>
							<th align="left">Purpose Description:</th>
							<td><input type="text" size="20" maxlength="20" name="purpose_description" value="<?=htmlentities($row['purpose_description'])?>" /></td>
						</tr>
						</table>
					</td>
					<td>
						<table border="0">
						<tr>
							<th align="left">Election Code:</th>
							<td><input type="text" size="5" maxlength="5" name="election_code" value="<?=htmlentities($row['election_code'])?>" /></td>
						</tr>
						<tr>
							<th align="left">Election Description:</th>
							<td><input type="text" size="15" maxlength="20" name="election_description" value="<?=htmlentities($row['election_descriptions'])?>" /></td>
						</tr>
						<tr>
							<th align="left">Category Code (SB Form only):</th>
							<td><input type="text" size="3" maxlength="3" name="category_code" value="<?=htmlentities($row['category_code'])?>" /></td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2">

				<input type="submit" value="Save Schedule" />

			</td>
		</tr>
		<tr>
			<th align="left">Back References:</th>
			<td>
				Trans.ID: <input type="text" maxlength="20" size="15" name="reference_trans_id" value="<?=htmlentities($row['reference_trans_id'])?>" />
				&nbsp;&nbsp;&nbsp;
				Sched.Name: <input type="text" maxlength="8" size="8" name="reference_sched_name" value="<?=htmlentities($row['reference_sched_name'])?>" />
			</td>
		</tr>
		<tr>
			<th align="left">Organization Name:</th>
			<td><input type="text" name="organization_name" maxlength="200" size="40" value="<?=htmlentities($row['organization_name'])?>" /></td>
		</tr>
		<tr>
			<th align="left">Individual Name:</th>
			<td><?


				echo $this->makePrefixesDD('prefix', $row['prefix'], '', "", '[Prefix]');

				?><input type="text" name="first_name" maxlength="20" size="15" value="<?=htmlentities($row['first_name'])?>" title="First Name"/>
				<input type="text" name="middle_name" maxlength="20" size="6" value="<?=htmlentities($row['middle_name'])?>" title="Middle Name" />
				<input type="text" name="last_name" maxlength="30" size="20" value="<?=htmlentities($row['last_name'])?>" title="Last Name" /><?


				echo $this->makeSuffixesDD('suffix', $row['suffix'], '', "", '[Suffix]');



			?></td>
		</tr>
		<tr valign="top">
			<th align="left">Address:</th>
			<td>
				<input name="address1" type="text" size="30" value="<?=htmlentities($row['address1'])?>" title="Address line 1"><br />
				<input name="address2" type="text" size="30" value="<?=htmlentities($row['address2'])?>" title="Address line 2">
			</td>
		</tr>
		<tr>
			<th align="left">City/State/Zip:</th>
			<td>
				<input name="city" type="text" size="20" maxlength="30" value="<?=htmlentities($row['city'])?>" title="City">,
				<input name="state" type="text" size="2" maxlength="2" value="<?=htmlentities($row['state'])?>" title="State (2 letter)">
				<input name="zip" type="text" size="10" maxlength="10" value="<?=htmlentities($row['zip'])?>" title="Zip Code">
			</td>
		</tr>

		<tr>
			<th align="left">Employer/Occupation:</th>
			<td>
				<input name="employer" type="text" size="20" maxlength="38" value="<?=htmlentities($row['employer'])?>" title="Employer">
				&nbsp;&nbsp;
				<input name="occupation" type="text" size="20" maxlength="38" value="<?=htmlentities($row['occupation'])?>" title="Occupation">
			</td>
		</tr>

		<tr>
			<th align="left">Committee FEC ID/Name:</th>
			<td>
				<input name="committee_fec_id" type="text" size="9" value="<?=htmlentities($row['committee_fec_id'])?>" title="Committee FEC ID">
				&nbsp;&nbsp;
				<input name="committee_name" type="text" size="30" maxlength="200" value="<?=htmlentities($row['committee_name'])?>" title="Committee Name">
			</td>
		</tr>
		<tr>
			<th align="left">Candidate FEC ID/Name:</th>
			<td>
				<input name="candidate_fec_id" type="text" size="9" value="<?=htmlentities($row['candidate_fec_id'])?>" title="Candidate FEC ID">
			</td>
		</tr>
		<tr>
			<th align="left">Candidate Name:</th>
			<td><?

				echo $this->makePrefixesDD('candidate_prefix', $row['candidate_prefix'], '', "", '[Prefix]');

				?><input type="text" name="candidate_first_name" maxlength="20" size="15" value="<?=htmlentities($row['candidate_first_name'])?>" title="Candidate First Name"/>
				<input type="text" name="candidate_middle_name" maxlength="20" size="6" value="<?=htmlentities($row['candidate_middle_name'])?>" title="Candidate Middle Name" />
				<input type="text" name="candidate_last_name" maxlength="30" size="20" value="<?=htmlentities($row['candidate_last_name'])?>" title="Candidate Last Name" /><?


				echo $this->makeSuffixesDD('candidate_suffix', $row['candidate_suffix'], '', "", '[Suffix]');



			?></td>
		</tr>
		<tr>
			<th align="left">Candidate Office/State/District:</th>
			<td>

				<input name="candidate_office" type="text" size="1" maxlength="1" value="<?=htmlentities($row['candidate_office'])?>" title="Candidate Office (H,S,P)">
				&nbsp;&nbsp;
				<input name="candidate_state" type="text" size="2" maxlength="2" value="<?=htmlentities($row['candidate_state'])?>" title="Candidate State (2 letter)">
				&nbsp;&nbsp;
				<input name="candidate_district" type="text" size="2" maxlength="2" value="<?=htmlentities($row['candidate_district'])?>" title="Candidate District (01-99)">

			</td>
		</tr>



		<tr>
			<th align="left">Conduit Name:</th>
			<td>
				<input name="conduit_name" type="text" size="30" value="<?=htmlentities($row['conduit_name'])?>" title="Conduit Address line 1">
			</td>
		</tr>
		<tr valign="top">
			<th align="left">Conduit Address:</th>
			<td>
				<input name="conduit_address1" type="text" size="30" value="<?=htmlentities($row['conduit_address1'])?>" title="Conduit Address line 1"><br />
				<input name="conduit_address2" type="text" size="30" value="<?=htmlentities($row['conduit_address2'])?>" title="Address line 2">
			</td>
		</tr>
		<tr>
			<th align="left">Conduit City/State/Zip:</th>
			<td>
				<input name="conduit_city" type="text" size="20" maxlength="30" value="<?=htmlentities($row['conduit_city'])?>" title="Conduit City">,
				<input name="conduit_state" type="text" size="2" maxlength="2" value="<?=htmlentities($row['conduit_state'])?>" title="Conduit State (2 letter)">
				<input name="conduit_zip" type="text" size="10" maxlength="10" value="<?=htmlentities($row['conduit_zip'])?>" title="Conduit Zip Code">
			</td>
		</tr>
		<tr>
			<th align="left">Memo:</th>
			<td>

				<input type="checkbox" name="memo_code" value="yes" <?=($row['memo_code'] == 'yes')?' CHECKED ':''?> onclick="if(this.checked){ieDisplay('memospan',1);}else{ieDisplay('memospan',0);}"/>
				<span id="memospan" <?=($row['memo_code'] != 'yes')?' class="nod" ':''?>>
					<input type="text" name="memo_description" value="<?=htmlentities($row['memo_description'])?>" size="40" maxlength="100" />
				</span>

			</td>
		</tr>
		<tr>
			<th align="left">Reference (SI/SL) Code:</th>
			<td>
				<input name="reference_system_code" type="text" size="9" maxlength="9" value="<?=htmlentities($row['reference_system_code'])?>" title="Reference to SI or SL System Code">
			</td>
		</tr>
		<tr>
			<td colspan="2">

				<input type="submit" value="Save Schedule" />

			</td>
		</tr>
		</form>
		</table><?
	}

	function makeImportSchedule(){


		?><script>

		</script>

		<form method="POST" enctype="multipart/form-data" action="<?=stripurl(array('reset_fec_data','no_script'))?>">
			<input type="hidden" name="step" value="2b" />
			<input type="hidden" name="importing_schedule" />

		<table border="0" width="100%">
		<tr>
			<th>Schedule Type:</th>
			<td><?

				echo $this->makeScheduleCodeDD('schedule_code', '', "", "", 0);

			?></td>
		</tr>
		<tr>
			<th>Import CSV File:</th>
			<td><input type="file" name="schedule_file" id="schedule_file"></td>
		</tr>
		<tr>
			<td colspan="2">

				<input type="submit" value="Upload" />

			</td>
		</tr>
		</table>
		</form><?

	}



	function makeUploadDonations(){


		?><script>

		</script>

		<form method="POST" enctype="multipart/form-data" action="<?=stripurl(array('reset_fec_data','no_script'))?>">
			<input type="hidden" name="step" value="2c" />
			<input type="hidden" name="uploading_donations" />

		<table border="0" width="100%">
		<tr>
			<th>Import CSV File:</th>
			<td><input type="file" name="donation_file" id="donation_file"></td>
		</tr>
		<tr>
			<td colspan="2">

				<input type="submit" value="Upload" />

			</td>
		</tr>
		</table>
		</form><?

	}

	function makeUploadExpenses(){


		?><script>

		</script>

		<form method="POST" enctype="multipart/form-data" action="<?=stripurl(array('reset_fec_data','no_script'))?>">
			<input type="hidden" name="step" value="2d" />
			<input type="hidden" name="uploading_expenses" />

		<table border="0" width="100%">
		<tr>
			<th>Import Expense CSV File:</th>
			<td><input type="file" name="expense_file" id="expense_file"></td>
		</tr>
		<tr>
			<td colspan="2">

				<input type="submit" value="Upload" />

			</td>
		</tr>
		</table>
		</form><?

	}

	function makeAttachPreviousGUI(){

		?><form method="POST" action="<?=stripurl(array('reset_fec_data','no_script'))?>">
			<input type="hidden" name="attaching_previous_report" />


		<table border="0" width="100%">
		<tr>
			<th height="40">Previous Quarter:</th>
			<td><?

				echo $this->makeFilingDD($_SESSION['fecdata']['current_pac']['id'], 'previous_filing_id', '', "", "", false, $_SESSION['fecdata']['current_file']['id']);

			?></td>
		</tr>
		<tr>
			<td colspan="2" align="center">

				<input type="submit" value="Attach &amp; Calculate" />

			</td>
		</tr>
		</form>
		</table><?
	}

	function makeDonationGUI(){


		// CALCULATION DONATION AMOUNTS


		$colspan=3;
		?><table border="0" width="100%">
		<tr>
			<td colspan="<?=$colspan?>" height="30" class="pad_left ui-widget-header">
				Donations
			</td>
		</tr>
		<tr>
			<td colspan="<?=$colspan?>" align="center">

				<input type="button" value="Upload Donations" onclick="displayUploadDonationsDialog()" />
				 -
				<input type="button" value="Finalize Donations &amp; Total" onclick="if(confirm('This will add Schedule A (11ai) entries for all donors with total donations at or over 200.\nAre you done uploading donations and ready to do this?')){go('?finalize_donation_uploads=1');}" />

			</td>
		</tr>
		<tr>
			<td style="font-size:14px" height="30">Total Donations: $<?=number_format($_SESSION['fecdata']['total_calculations']['donors_total'], 2)?></td>
			<td style="font-size:14px">Donations to be Itemized: $<?=number_format($_SESSION['fecdata']['total_calculations']['donors_total_itemized'], 2)?></td>
			<td style="font-size:14px">Remaining UnItemized: $<?=number_format($_SESSION['fecdata']['total_calculations']['donors_total_unitemized'], 2)?></td>
		</tr>
		<tr>
			<td colspan="<?=$colspan?>">&nbsp;</td>
		</tr>
		</table><?
	}


	function makeExpenseGUI(){


		// CALCULATION EXPENSES AMOUNTS


		$colspan=3;
		?><table border="0" width="100%">
		<tr>
			<td colspan="<?=$colspan?>" height="30" class="pad_left ui-widget-header">
				Expenses
			</td>
		</tr>
		<tr>
			<td colspan="<?=$colspan?>" align="center">

				<input type="button" value="Upload Expenses" onclick="displayUploadExpensesDialog()" />
				 -
				<input type="button" value="Finalize Expenses &amp; Total" onclick="if(confirm('This will add Schedule B (21b) entries for all businesses with total expenses at or over 200.\nAre you done uploading donations and ready to do this?')){go('?finalize_expense_uploads=1');}" />

			</td>
		</tr>
		<tr>
			<td style="font-size:14px" height="30">Total Expenses: $<?=number_format($_SESSION['fecdata']['total_calculations']['expenses_total'], 2)?></td>
			<td style="font-size:14px">Expenses to be Itemized: $<?=number_format($_SESSION['fecdata']['total_calculations']['expenses_total_itemized'], 2)?></td>
			<td style="font-size:14px">Remaining UnItemized: $<?=number_format($_SESSION['fecdata']['total_calculations']['expenses_total_unitemized'], 2)?></td>
		</tr>
		<tr>
			<td colspan="<?=$colspan?>">&nbsp;</td>
		</tr>
		</table><?
	}

	function makeScheduleList(){

		$schedules = $this->loadSchedules();


		$colspan = 6;
		?><table border="0" width="100%">
		<tr>
			<td colspan="<?=$colspan?>" height="30" class="pad_left ui-widget-header">
				Schedules (Itemized Receipts/Dispursements/etc)
			</td>
		</tr>
		<tr>
			<td colspan="<?=$colspan?>" align="Center">

			 	<input type="button" value="Add Schedule"  onclick="displayEditScheduleDialog(0)" />
				 - <input type="button" value="Import Schedules" onclick="displayImportScheduleDialog()" />
			</td>
		</tr>
		<tr>
			<th class="row2">Type</th>
			<th align="left" class="row2">Name</th>
			<th align="left" class="row2">Description</th>
			<th class="row2">Date</th>
			<th align="right" class="row2">Amount</th>
			<th align="right" class="row2">Aggregate</th>
		</tr><?

		if(count($schedules) <= 0){

			?><tr>
				<td colspan="<?=$colspan?>" align="center">

					<i>No Schedules (Itemized Receipts/Dispursements/etc)</i><br />
					Use the ADD or IMPORT button above to add them.

				</td>
			</tr><?
		}

		$color=0;
		foreach($schedules as $row){
			$class = 'row'.($color++%2);

			$onclick = ' onclick="displayEditScheduleDialog('.$row['id'].')" ';

			?><tr>
				<td height="25" class="<?=$class?> hand" <?=$onclick?> align="center"><?=htmlentities($row['form_type'])?></td>
				<td class="<?=$class?> hand" <?=$onclick?>><?

					echo htmlentities($row['entity_type']).':';

					// NAME - DETERMINE SOURCE BY WHAT ENTITY TYPE IT IS
					switch($row['entity_type']){
					case 'CAN':
					case 'CCM':
					case 'COM':
					case 'ORG':
					case 'PAC':
					case 'PTY':

						echo htmlentities($row['organization_name']);
						break;
					case 'IND':
					default:

						if($row['organization_name']){
							echo htmlentities($row['organization_name']);
						}else{
							echo htmlentities($row['first_name'].(($row['middle_name'])?' '.$row['middle_name']:'').(($row['last_name'])?' '.$row['last_name']:'') );
						}

						break;
					}

				?></td>
				<td class="<?=$class?> hand" <?=$onclick?> align="left"><?

					$desc = $row['purpose_description'];

					// COULD TRIM HERE TO LOOK PERRDY

					echo htmlentities($desc);

				?></td>
				<td class="<?=$class?> hand" <?=$onclick?> align="center"><?=htmlentities($row['date'])?></td>
				<td class="<?=$class?> hand" <?=$onclick?> align="right">$<?=htmlentities(number_format($row['amount'],2))?></td>
				<td class="<?=$class?> hand" <?=$onclick?> align="right">$<?=htmlentities(number_format($row['amount_aggregate'],2))?></td>
				<td align="center">

					<a href="?delete_schedule=<?=$row['id']?>" onclick="return confirm('Are you sure you want to delete this schedule?')">
						<img src="images/delete.png" width="15" border="0" />
					</a>

				</td>
			</tr><?

		}


		?></table><?

	}


	function getErrorMessage($ret_code){

		switch($ret_code){
		default:	return $ret_code." - Unknown Error code.\n";
		case 0:		return "SUCCESS";

		case -200:	return "ERROR: NOT ENOUGH LINES IN THE FILE";
		case -201:	return "ERROR: HEADER APPEARS INVALID";
		case -202:	return "ERROR: FILE VERSION APPEARS TOO OLD. Must be 8.2 (or above)";
		}
	}

	function importF3XFile($pac_id, $filepath){

		$sep = ',';

		// LOAD THE PAC
		$_SESSION['fecdata']['current_pac'] = $this->loadPAC($pac_id);

		// START WITH BARE MINIMAL FIELDS SPECIFIED, THE REST WILL COME FROM THE REPORT ITSELF
		$dat = array(
				'pac_id'=>$_SESSION['fecdata']['current_pac']['id']
				);

		// READ FILE INTO MEMORY FOR PARSING
		$data = file_get_contents($filepath);


		// BREAK FILE INTO INDIVIDUAL LINES
		$lines = preg_split("/\r\n|\r|\n/", $data, -1, PREG_SPLIT_NO_EMPTY);

		// ERROR: NOT ENOUGH LINES IN THE FILE
		if(count($lines) < 2){

			return -200;

		}

		// PARSE FIRST LINE AS HEADER
		$header_line = str_getcsv ($lines[0],$sep,'"');
		$form_line = str_getcsv ($lines[1],$sep,'"');

		// ERROR: HEADER APPEARS INVALID
		if(trim($header_line[0]) != 'HDR' || $header_line[1] != 'FEC'){
			return -201;
		}


		// CHECK THE VERSION NUMBER
		// ERROR: FILE VERSION APPEARS TOO OLD. Must be 8.2 (or above)
		if(floatval($header_line[2]) < 8.2){
			return -202;
		}

		// HEADER LINE PASSED THE TESTS, START EXTRACTING DATA
		$dat['hdr_fec_report_id'] = $header_line[5];
		$dat['hdr_fec_report_num'] = intval($header_line[6]);
		$dat['hdr_comment'] = $header_line[7];


		// COULD DO VALIDATION HERE, ON FORM TYPE, FOR NOW, JUST SUCK IT IN
		$dat['form_type']					= $form_line[0];
		$dat['committee_id']				= $form_line[1];
		$dat['committee_name']				= $form_line[2];

		$dat['request_change_of_address']	= (strtoupper(trim($form_line[3])) == 'X')?'yes':'no';
		$dat['address1']		= $form_line[4];
		$dat['address2']		= $form_line[5];
		$dat['city']			= $form_line[6];
		$dat['state']			= $form_line[7];
		$dat['zip']				= $form_line[8];

		$dat['report_code']			= $form_line[9];

		$dat['election_code']		= $form_line[10];
		$dat['election_date']		= date("Y-m-d", strtotime($form_line[11]));
		$dat['election_state']		= $form_line[12];

		$dat['start_date']			= date("Y-m-d", strtotime($form_line[13]));
		$dat['end_date']			= date("Y-m-d", strtotime($form_line[14]));
		$dat['qualified_committee']	= (strtoupper(trim($form_line[15])) == 'X')?'yes':'no';

		$dat['treasurer_last_name']		= $form_line[16];
		$dat['treasurer_first_name']	= $form_line[17];
		$dat['treasurer_middle_name']	= $form_line[18];
		$dat['treasurer_prefix']		= $form_line[19];
		$dat['treasurer_suffix']		= $form_line[20];

		$dat['date_signed'] = date("Y-m-d", strtotime($form_line[21]));

		// START THE COUNTER AT BEGINNING OF COLUMN A
		$pos = 22;

		$dat['colA_6b_cash_on_hand']			= $form_line[$pos++]; // 22
		$dat['colA_6c_total_receipts']			= $form_line[$pos++]; // 23
		$dat['colA_6d_subtotal']				= $form_line[$pos++]; // 24
		$dat['colA_7_total_disbursements']		= $form_line[$pos++]; // 25
		$dat['colA_8_cash_on_hand_close']		= $form_line[$pos++]; // 26
		$dat['colA_9_debts_to']					= $form_line[$pos++]; // 27
		$dat['colA_10_debts_by']				= $form_line[$pos++]; // 28
		$dat['colA_11ai_itemized']				= $form_line[$pos++]; // 29
		$dat['colA_11aii_unitemized']			= $form_line[$pos++]; // 30
		$dat['colA_11aiii_total']				= $form_line[$pos++]; // 31
		$dat['colA_11b_pol_party_committees']	= $form_line[$pos++]; // 32
		$dat['colA_11c_other_pacs']				= $form_line[$pos++]; // 33
		$dat['colA_11d_total_contributions']	= $form_line[$pos++]; // 34
		$dat['colA_12_transfers']				= $form_line[$pos++]; // 35
		$dat['colA_13_loans_received']			= $form_line[$pos++]; // 36
		$dat['colA_14_loan_repayments_received']	= $form_line[$pos++]; // 37
		$dat['colA_15_offsets_refunds']			= $form_line[$pos++]; // 38
		$dat['colA_16_fed_contrib_refund']		= $form_line[$pos++]; // 39
		$dat['colA_17_other_fed_receipts']		= $form_line[$pos++]; // 40
		$dat['colA_18a_trans_nonfed_h3']		= $form_line[$pos++]; // 41
		$dat['colA_18b_trans_nonfed_h5']		= $form_line[$pos++]; // 42
		$dat['colA_18c_trans_nonfed_total']		= $form_line[$pos++]; // 43
		$dat['colA_19_total_receipts']			= $form_line[$pos++]; // 44
		$dat['colA_20_total_fed_receipts']		= $form_line[$pos++]; // 45
		$dat['colA_21ai_fed_share']				= $form_line[$pos++]; //46
		$dat['colA_21aii_nonfed_share']			= $form_line[$pos++]; // 47
		$dat['colA_21b_other_fed_expenditures']	= $form_line[$pos++]; // 48
		$dat['colA_21c_total_operating_expenditures']	= $form_line[$pos++]; // 49
		$dat['colA_22_trans_affiliated_partys']	= $form_line[$pos++]; // 50
		$dat['colA_23_contrib_fed_candidates']	= $form_line[$pos++]; // 51
		$dat['colA_24_indep_expenditure']		= $form_line[$pos++]; // 52
		$dat['colA_25_coord_expenditures']		= $form_line[$pos++]; // 53
		$dat['colA_26_loan_repayments']			= $form_line[$pos++]; // 54
		$dat['colA_27_loans_made']				= $form_line[$pos++]; // 55
		$dat['colA_28a_individuals']			= $form_line[$pos++]; // 56
		$dat['colA_28b_pol_party_committees']	= $form_line[$pos++]; // 57
		$dat['colA_28c_other_pacs']				= $form_line[$pos++]; // 58
		$dat['colA_28d_total_contrib_refunds']	= $form_line[$pos++]; // 59
		$dat['colA_29_other_disbursements']		= $form_line[$pos++]; // 60
		$dat['colA_30ai_shared_fed_activity_h6_fed']= $form_line[$pos++]; // 61
		$dat['colA_30aii_shared_fed_activity_nonfed']= $form_line[$pos++]; // 62
		$dat['colA_30b_non_allocatable']		= $form_line[$pos++]; // 63
		$dat['colA_30c_total_fed_election_activity']= $form_line[$pos++]; // 64
		$dat['colA_31_total_dibursements']		= $form_line[$pos++]; // 65
		$dat['colA_32_total_fed_disbursements']	= $form_line[$pos++]; // 66
		$dat['colA_33_total_contributions']		= $form_line[$pos++]; // 67
		$dat['colA_34_total_contribution_refunds']= $form_line[$pos++]; // 68
		$dat['colA_35_net_contributions']		= $form_line[$pos++]; // 69
		$dat['colA_36_total_fed_op_expenditures']= $form_line[$pos++]; // 70
		$dat['colA_37_offset_to_op_expenditures']= $form_line[$pos++]; // 71
		$dat['colA_38_net_op_expenditures']		= $form_line[$pos++]; // 72



		$dat['colB_6a_cash_on_hand']			= $form_line[$pos++]; // 73
		$dat['colB_year_for_above']				= $form_line[$pos++]; // 74
		$dat['colB_6c_total_receipts']			= $form_line[$pos++]; // 75
		$dat['colB_6d_subtotal']				= $form_line[$pos++]; // 76
		$dat['colB_7_total_disbursements']		= $form_line[$pos++]; // 77
		$dat['colB_8_cash_on_hand_close']		= $form_line[$pos++]; // 78
		$dat['colB_11ai_itemized']				= $form_line[$pos++]; // 79
		$dat['colB_11aii_unitemized']			= $form_line[$pos++]; // 80
		$dat['colB_11aiii_total']				= $form_line[$pos++]; // 81
		$dat['colB_11b_pol_party_committees']	= $form_line[$pos++]; // 82
		$dat['colB_11c_other_pacs']				= $form_line[$pos++]; // 83
		$dat['colB_11d_total_contributions']	= $form_line[$pos++]; // 84
		$dat['colB_12_transfers']				= $form_line[$pos++]; // 85
		$dat['colB_13_loans_received']			= $form_line[$pos++]; // 86
		$dat['colB_14_loan_repayments_received']= $form_line[$pos++]; // 87
		$dat['colB_15_offsets_refunds']			= $form_line[$pos++]; // 88
		$dat['colB_16_fed_contrib_refund']		= $form_line[$pos++]; // 89
		$dat['colB_17_other_fed_receipts']		= $form_line[$pos++]; // 90
		$dat['colB_18a_trans_nonfed_h3']		= $form_line[$pos++]; // 91
		$dat['colB_18b_trans_nonfed_h5']		= $form_line[$pos++]; // 92
		$dat['colB_18c_trans_nonfed_total']		= $form_line[$pos++]; // 93
		$dat['colB_19_total_receipts']			= $form_line[$pos++]; // 94
		$dat['colB_20_total_fed_receipts']		= $form_line[$pos++]; // 95
		$dat['colB_21ai_fed_share']				= $form_line[$pos++]; // 97
		$dat['colB_21aii_nonfed_share']			= $form_line[$pos++]; // 98
		$dat['colB_21b_other_fed_expenditures']	= $form_line[$pos++]; // 99
		$dat['colB_21c_total_operating_expenditures']= $form_line[$pos++]; // 99
		$dat['colB_22_trans_affiliated_partys']	= $form_line[$pos++]; // 100
		$dat['colB_23_contrib_fed_candidates']	= $form_line[$pos++]; // 101
		$dat['colB_24_indep_expenditure']		= $form_line[$pos++]; // 102
		$dat['colB_25_coord_expenditures']		= $form_line[$pos++]; // 103
		$dat['colB_26_loan_repayments']			= $form_line[$pos++]; // 104
		$dat['colB_27_loans_made']				= $form_line[$pos++]; // 105
		$dat['colB_28a_individuals']			= $form_line[$pos++]; // 106
		$dat['colB_28b_pol_party_committees']	= $form_line[$pos++]; // 107
		$dat['colB_28c_other_pacs']				= $form_line[$pos++]; // 108
		$dat['colB_28d_total_contrib_refunds']	= $form_line[$pos++]; // 109
		$dat['colB_29_other_disbursements']		= $form_line[$pos++]; // 110
		$dat['colB_30ai_shared_fed_activity_h6_fed']= $form_line[$pos++]; // 111
		$dat['colB_30aii_shared_fed_activity_nonfed']= $form_line[$pos++]; // 112
		$dat['colB_30b_non_allocatable']		= $form_line[$pos++]; // 113
		$dat['colB_30c_total_fed_election_activity']= $form_line[$pos++]; // 114
		$dat['colB_31_total_dibursements']		= $form_line[$pos++]; // 115
		$dat['colB_32_total_fed_disbursements']	= $form_line[$pos++]; // 116
		$dat['colB_33_total_contributions']		= $form_line[$pos++]; // 117
		$dat['colB_34_total_contribution_refunds']= $form_line[$pos++]; // 118
		$dat['colB_35_net_contributions']		= $form_line[$pos++]; // 119
		$dat['colB_36_total_fed_op_expenditures']= $form_line[$pos++]; // 120
		$dat['colB_37_offset_to_op_expenditures']= $form_line[$pos++]; // 121
		$dat['colB_38_net_op_expenditures']		= $form_line[$pos++]; // 122

		// ADD THE "FILING" RECORD FOR F3X FORM
		aadd($dat, $this->table);
		$filing_id = mysqli_insert_id($_SESSION['db']);

		// LOAD THE FILING DATA INTO THE SESSION
		$_SESSION['fecdata']['current_file'] = $this->loadFiling($pac_id, $filing_id);

		if(count($lines) > 2){

			// GO THROUGH EACH ADDITIONAL LINE (SCHEDULES)
			foreach($lines as $idx => $line){

				// SKIP THE FIRST 2 LINES WE ALREADY PROCESSED
				if($idx < 2)continue;


				// INIT THE RECORD ARRAY WITH THE BARE MINIMUMS FOR INSERTING
				$dat = array(
							"pac_id"		=> $pac_id,
							"filing_id"		=> $filing_id,
						);

				// PARSE THE CSV ROW
				$row = str_getcsv ($line,$sep,'"');

				// FILL THE HEADER INFORMATION
				$dat['form_type']		= trim($row[0]);
				$dat['committee_id']	= trim($row[1]);
				$dat['transaction_id']	= trim($row[2]);
				$dat['reference_trans_id'] = trim($row[3]);
				$dat['reference_sched_name'] = trim($row[4]);

				// INIT AT THE MEAT AND POTATOES
				$pos = 5;

				$dat['entity_type'] 			= trim($row[$pos++]);			// 5
				$dat['organization_name']		= trim($row[$pos++]);			// 6
				$dat['last_name']				= trim($row[$pos++]);			// 7
				$dat['first_name']				= trim($row[$pos++]);			// 8
				$dat['middle_name']				= trim($row[$pos++]);			// 9
				$dat['prefix']					= trim($row[$pos++]);			// 10
				$dat['suffix']					= trim($row[$pos++]);			// 11

				$dat['address1']				= trim($row[$pos++]);			// 12
				$dat['address2']				= trim($row[$pos++]);			// 13
				$dat['city']					= trim($row[$pos++]);			// 14
				$dat['state']					= trim($row[$pos++]);			// 15
				$dat['zip']						= trim($row[$pos++]);			// 16

				$dat['election_code']			= trim($row[$pos++]);			// 17
				$dat['election_description']	= trim($row[$pos++]);			// 18


				$dat['date']					= date("Y-m-d", strtotime(trim($row[$pos++])) ); // 19
				$dat['amount']					= round(trim($row[$pos++]) ,2); // 20
				$dat['amount_aggregate']		= round(trim($row[$pos++]) ,2); // 21
				$dat['purpose_description']		= trim($row[$pos++]); // 22


				// IF SCHEDULE A
				if(substr($dat['form_type'],0,2) == 'SA'){

					$dat['employer'] = trim($row[$pos++]); // 23
					$dat['occupation'] = trim($row[$pos++]); // 24

				// SCHEDULE B IS THE FALL THROUGH
				}else{

					$dat['category_code'] = trim($row[$pos++]); // 23

				}



				$dat['committee_fec_id']		= trim($row[$pos++]); // SA=25, SB=24
				$dat['committee_name']			= trim($row[$pos++]); // SA=26, SB=25

				$dat['candidate_fec_id']		= trim($row[$pos++]); // SA=27, SB=26

				$dat['candidate_last_name']		= trim($row[$pos++]); // SA=28, SB=27
				$dat['candidate_first_name']	= trim($row[$pos++]); // SA=29, SB=28
				$dat['candidate_middle_name']	= trim($row[$pos++]); // SA=30, SB=29
				$dat['candidate_prefix']		= trim($row[$pos++]); // SA=31, SB=30
				$dat['candidate_suffix']		= trim($row[$pos++]); // SA=32, SB=31
				$dat['candidate_office']		= trim($row[$pos++]); // SA=33, SB=32
				$dat['candidate_state']			= trim($row[$pos++]); // SA=34, SB=33
				$dat['candidate_district']		= intval($row[$pos++]); // SA=35, SB=34

				$dat['conduit_name']			= trim($row[$pos++]); // SA=36, SB=35
				$dat['conduit_address1']		= trim($row[$pos++]); // SA=37, SB=36
				$dat['conduit_address2']		= trim($row[$pos++]); // SA=38, SB=37
				$dat['conduit_city']			= trim($row[$pos++]); // SA=39, SB=38
				$dat['conduit_state']			= trim($row[$pos++]); // SA=40, SB=39
				$dat['conduit_zip']				= trim($row[$pos++]); // SA=41, SB=40

				$dat['memo_code']				= (strtoupper(trim($row[$pos++])) == 'X')?'yes':'no' ; // SA=42, SB=41
				$dat['memo_description']		= trim($row[$pos++]); // SA=43, SB=42

				$dat['reference_system_code']	= trim($row[$pos++]); // SA=44, SB=43


				aadd($dat, $this->schedule_table);



			} // END foreach(additional lines/schedules)


		}


		// SET CURRENT STEP ...
		$_SESSION['fecdata']['current_step'] = '2';

		return $filing_id;
	}


	function importSchedule($form_type, $filepath, $filename){


		if(stripos($filename, ".tsv") > -1){
			$sep = "\t";
		}else{
			$sep = ",";
		}

		$cnt = 0;

		// READ FILE INTO MEMORY FOR PARSING
		$data = file_get_contents($filepath);


		// BREAK FILE INTO INDIVIDUAL LINES
		$lines = preg_split("/\r\n|\r|\n/", $data, -1, PREG_SPLIT_NO_EMPTY);

		$header_format = array();

		// PARSE FIRST LINE AS HEADER
		$headerline = str_getcsv ($lines[0],$sep,'"');//preg_split("/,/", $lines[0]);

		$has_address2_field = false;

		foreach($headerline as $idx=>$name){

			$name = strtoupper($name);

			// DETECT THE NAME AND ASSIGN THE COLUMN INDEX TO THE RELATIVE DB FIELD NAME
			switch($name){
			case 'EMPLOYER':

				$header_format[$idx] = 'employer';

				break;

			case 'OCCUPATION':

				$header_format[$idx] = 'occupation';

				break;
			case 'PAIDDATE':
			case 'DATE':

				$header_format[$idx] = 'date';

				break;
			case 'COMPANY':
			case 'ORG':
			case 'ORGANIZATION':

				$header_format[$idx] = 'organization_name';

				break;

			case 'FIRSTNAME':
			case 'FIRST_NAME':
			case 'FIRST-NAME':
			case 'FNAME':

				$header_format[$idx] = 'first_name';

				break;
			case 'LASTNAME':
			case 'LAST_NAME':
			case 'LAST-NAME':
			case 'LNAME':

				$header_format[$idx] = 'last_name';

				break;
			case 'MIDDLENAME':
			case 'MIDDLE_NAME':
			case 'MIDDLE-NAME':
			case 'MNAME':

				$header_format[$idx] = 'middle_name';

				break;

			case 'AMOUNT':
			case 'AMT':
			case 'PAIDAMT':
			case 'TOTAL':

				$header_format[$idx] = 'amount';

				break;

			case 'ADDRESS':
			case 'ADDRESS1':
			case 'STREET':
			case 'STREET1':

				$header_format[$idx] = 'address1';

				break;

			case 'ADDRESS2':
			case 'STREET2':

				$header_format[$idx] = 'address2';

				$has_address2_field = true;

				break;



			case 'CITY':

				$header_format[$idx] = 'city';

				break;

			case 'STATE':
			case 'ST':

				$header_format[$idx] = 'state';

				break;

			case 'ZIP':
			case 'ZIPCODE':
			case 'POSTAL':
			case 'POST':
			case 'POSTALCODE':

				$header_format[$idx] = 'zip';

				break;

			case 'DESCRIPTION':
			case 'DESC':
			case 'REASON':
			case 'PURPOSE':
			case 'PURPOSE_DESCRIPTION':

				$header_format[$idx] = 'purpose_description';

				break;

			//case 'expendituretypeid':
//			case 'EXPENDITURETYPEID':
//				$header_format[$idx] = 'category_code';
//
//				break;

			case 'categorycodeID':
			case 'CATEGORYCODEID':

				$header_format[$idx] = 'category_code';

				break;

			case 'CHECKNUMBER':
			case 'CHECKNUM':

				$header_format[$idx] = 'check_number';


				break;
			}

		} // END foreach(header column)

		// OKAY, HEADERS ARE ORGANIZED BY THEIR ARRAY INDEX AND PERSPECTIVE DB FIELD NAMES
		// LOOP THROUGH EACH LINE, PARSE AND INSERT
		foreach($lines as $linenum => $line){

			// SKIP HEADER LINE
			if($linenum == 0)continue;

			//$line_data = preg_split("/,/", $line);

			$line_data = str_getcsv ($line,$sep,'"');

//print_r($line_data);
//exit;

			$dat = array();
			$dat['pac_id']		= $_SESSION['fecdata']['current_pac']['id'];
			$dat['filing_id']	= $_SESSION['fecdata']['current_file']['id'];

			$dat['form_type'] = $form_type;

			foreach($header_format as $idx=>$field_name){


				// DETECT AND STRIP QUOTES
				if($line_data[$idx][0] == '"' && $line_data[$idx][strlen($line_data[$idx])-1] == '"'){

					$line_data[$idx] = substr($line_data[$idx],1, strlen($line_data[$idx])-2);

				}


				$line_data[$idx] = trim($line_data[$idx]);


				switch($field_name){
				default:

					$dat[$field_name] = $line_data[$idx];

					break;

				case 'organization_name':

					$dat[$field_name] = $line_data[$idx];

					if(trim($line_data[$idx])){
						$dat['entity_type'] = 'ORG';
					}
//					else{
//						$dat['entity_type'] = 'IND';
//					}

					break;

				case 'amount':

					$dat[$field_name] = preg_replace("/[^0-9-.]/", '', $line_data[$idx]);

					break;

				case 'date':

					$dat[$field_name] = date("Y-m-d", strtotime($line_data[$idx]));

					break;

				case 'zip':

					$dat[$field_name] = preg_replace("/[^0-9]/", '', $line_data[$idx]);

					break;
				case 'address1':

					// IF IT DOESNT CONTAIN THE SECOND ADDRESS LINE
					if(!$has_address2_field){
						// ATTEMPT TO PARSE THE FIRST LINE INTO 2 LINES
						$tidx = stripos($line_data[$idx]," suite");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," ste");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," apt");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," BLDG");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," UNIT");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," LOT");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," SPC");

						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," TRLR");

						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx],"#");

						// POSSIBLE DIVIDER DETECTED
						if($tidx > -1){

							$dat[$field_name] = trim(substr($line_data[$idx], 0,  $tidx));
							$dat['address2'] = trim(substr($line_data[$idx], $tidx));

						// NOTHING DETECTED, KEEP WHOLE
						}else{
							$dat[$field_name] = $line_data[$idx];
						}



					// ELSE JUST STORE AS ADDRESS1
					}else{
						$dat[$field_name] = $line_data[$idx];
					}

					break;

				} // END SWITCH(field name)

			} // END FOREACH(header field)


			$cnt += aadd($dat, $this->schedule_table);


		} // END FOREACH(line)




		return $cnt;
	}





	function importDonations($filepath, $filename){


		if(stripos($filename, ".tsv") > -1){
			$sep = "\t";
		}else{
			$sep = ",";
		}

		$cnt = 0;

		// READ FILE INTO MEMORY FOR PARSING
		$data = file_get_contents($filepath);


		// BREAK FILE INTO INDIVIDUAL LINES
		$lines = preg_split("/\r\n|\r|\n/", $data, -1, PREG_SPLIT_NO_EMPTY);

		$header_format = array();

		// PARSE FIRST LINE AS HEADER
		$headerline = str_getcsv ($lines[0],$sep,'"');//preg_split("/,/", $lines[0]);

		$has_address2_field = false;

		foreach($headerline as $idx=>$name){

			$name = strtoupper($name);

			// DETECT THE NAME AND ASSIGN THE COLUMN INDEX TO THE RELATIVE DB FIELD NAME
			switch($name){
			case 'EMPLOYER':

				$header_format[$idx] = 'employer';

				break;

			case 'OCCUPATION':

				$header_format[$idx] = 'occupation';

				break;
			case 'PAIDDATE':
			case 'DATE':
			case 'DATEPAID':

				$header_format[$idx] = 'date';

				break;
			case 'COMPANY':
			case 'ORG':
			case 'ORGANIZATION':

				$header_format[$idx] = 'company';

				break;

			case 'FIRSTNAME':
			case 'FIRST_NAME':
			case 'FIRST-NAME':
			case 'FNAME':

				$header_format[$idx] = 'first_name';

				break;
			case 'LASTNAME':
			case 'LAST_NAME':
			case 'LAST-NAME':
			case 'LNAME':

				$header_format[$idx] = 'last_name';

				break;
			case 'MIDDLENAME':
			case 'MIDDLE_NAME':
			case 'MIDDLE-NAME':
			case 'MNAME':

				$header_format[$idx] = 'middle_name';

				break;

			case 'AMOUNT':
			case 'AMT':
			case 'PAIDAMT':
			case 'TOTAL':

				$header_format[$idx] = 'amount';

				break;

			case 'ADDRESS':
			case 'ADDRESS1':
			case 'STREET':
			case 'STREET1':

				$header_format[$idx] = 'address1';

				break;

			case 'ADDRESS2':
			case 'STREET2':

				$header_format[$idx] = 'address2';

				$has_address2_field = true;

				break;


			case 'PH':
			case 'PHONE':
			case 'PHONENUM':
			case 'PHONENUMBER':

				$header_format[$idx] = 'phone';

				break;
			case 'CITY':

				$header_format[$idx] = 'city';

				break;

			case 'STATE':
			case 'ST':

				$header_format[$idx] = 'state';

				break;

			case 'ZIP':
			case 'ZIPCODE':
			case 'POSTAL':
			case 'POST':
			case 'POSTALCODE':

				$header_format[$idx] = 'zip';

				break;
			}

		} // END foreach(header column)

		// OKAY, HEADERS ARE ORGANIZED BY THEIR ARRAY INDEX AND PERSPECTIVE DB FIELD NAMES
		// LOOP THROUGH EACH LINE, PARSE AND INSERT
		foreach($lines as $linenum => $line){

			// SKIP HEADER LINE
			if($linenum == 0)continue;

			//$line_data = preg_split("/,/", $line);

			$line_data = str_getcsv ($line,$sep,'"');

//print_r($line_data);
//exit;

			$dat = array();
			$dat['pac_id']		= $_SESSION['fecdata']['current_pac']['id'];
			$dat['filing_id']	= $_SESSION['fecdata']['current_file']['id'];


			$full_address = '';

			foreach($header_format as $idx=>$field_name){


				// DETECT AND STRIP QUOTES
				if($line_data[$idx][0] == '"' && $line_data[$idx][strlen($line_data[$idx])-1] == '"'){

					$line_data[$idx] = substr($line_data[$idx],1, strlen($line_data[$idx])-2);

				}


				$line_data[$idx] = trim($line_data[$idx]);

				switch($field_name){
				default:

					$dat[$field_name] = trim($line_data[$idx]);

					break;

				case 'amount':

					$dat[$field_name] = preg_replace("/[^0-9-.]/", '', $line_data[$idx]);

					break;

				case 'date':

					$dat[$field_name] = date("Y-m-d", strtotime($line_data[$idx]));

					break;

				case 'zip':

					$dat[$field_name] = preg_replace("/[^0-9]/", '', $line_data[$idx]);

					break;
				case 'address1':

					$full_address = $line_data[$idx];

					// IF IT DOESNT CONTAIN THE SECOND ADDRESS LINE
					if(!$has_address2_field){
						// ATTEMPT TO PARSE THE FIRST LINE INTO 2 LINES
						$tidx = stripos($line_data[$idx]," suite");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," ste");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," apt");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," BLDG");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," UNIT");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," LOT");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," SPC");

						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," TRLR");

						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx],"#");

						// POSSIBLE DIVIDER DETECTED
						if($tidx > -1){

							$dat[$field_name] = trim(substr($line_data[$idx], 0,  $tidx));
							$dat['address2'] = trim(substr($line_data[$idx], $tidx));

						// NOTHING DETECTED, KEEP WHOLE
						}else{
							$dat[$field_name] = $line_data[$idx];
						}



					// ELSE JUST STORE AS ADDRESS1
					}else{
						$dat[$field_name] = $line_data[$idx];
					}

					break;

				} // END SWITCH(field name)

			} // END FOREACH(header field)


			$dat['unique_id'] = md5(trim($dat['first_name']).' '.trim($dat['last_name']).' '.trim($dat['zip']).' '.substr($full_address,0,8));


			$cnt += aadd($dat, $this->donation_table);


		} // END FOREACH(line)




		return $cnt;
	}




	function importExpenses($filepath, $filename){


		if(stripos($filename, ".tsv") > -1){
			$sep = "\t";
		}else{
			$sep = ",";
		}

		$cnt = 0;

		// READ FILE INTO MEMORY FOR PARSING
		$data = file_get_contents($filepath);


		// BREAK FILE INTO INDIVIDUAL LINES
		$lines = preg_split("/\r\n|\r|\n/", $data, -1, PREG_SPLIT_NO_EMPTY);

		$header_format = array();

		// PARSE FIRST LINE AS HEADER
		$headerline = str_getcsv ($lines[0],$sep,'"');//preg_split("/,/", $lines[0]);

		$has_address2_field = false;

		foreach($headerline as $idx=>$name){

			$name = strtoupper($name);

			// DETECT THE NAME AND ASSIGN THE COLUMN INDEX TO THE RELATIVE DB FIELD NAME
			switch($name){
			case 'EMPLOYER':

				$header_format[$idx] = 'employer';

				break;

			case 'OCCUPATION':

				$header_format[$idx] = 'occupation';

				break;
			case 'PAIDDATE':
			case 'DATE':
			case 'DATEPAID':

				$header_format[$idx] = 'date';

				break;
			case 'COMPANY':
			case 'ORG':
			case 'ORGANIZATION':

				$header_format[$idx] = 'company';

				break;

			case 'FIRSTNAME':
			case 'FIRST_NAME':
			case 'FIRST-NAME':
			case 'FNAME':

				$header_format[$idx] = 'first_name';

				break;
			case 'LASTNAME':
			case 'LAST_NAME':
			case 'LAST-NAME':
			case 'LNAME':

				$header_format[$idx] = 'last_name';

				break;
			case 'MIDDLENAME':
			case 'MIDDLE_NAME':
			case 'MIDDLE-NAME':
			case 'MNAME':

				$header_format[$idx] = 'middle_name';

				break;

			case 'AMOUNT':
			case 'AMT':
			case 'PAIDAMT':
			case 'TOTAL':

				$header_format[$idx] = 'amount';

				break;

			case 'ADDRESS':
			case 'ADDRESS1':
			case 'STREET':
			case 'STREET1':

				$header_format[$idx] = 'address1';

				break;

			case 'ADDRESS2':
			case 'STREET2':

				$header_format[$idx] = 'address2';

				$has_address2_field = true;

				break;


			case 'PH':
			case 'PHONE':
			case 'PHONENUM':
			case 'PHONENUMBER':

				$header_format[$idx] = 'phone';

				break;
			case 'CITY':

				$header_format[$idx] = 'city';

				break;

			case 'STATE':
			case 'ST':

				$header_format[$idx] = 'state';

				break;

			case 'ZIP':
			case 'ZIPCODE':
			case 'POSTAL':
			case 'POST':
			case 'POSTALCODE':

				$header_format[$idx] = 'zip';

				break;


			case 'DESCRIPTION':

				$header_format[$idx] = 'description';

				break;
			case 'categorycodeID':
			case 'CATEGORYCODEID':

				$header_format[$idx] = 'category_code';

				break;

//			case 'CHECKNUMBER':
//			case 'CHECKNUM':
//
//				$header_format[$idx] = 'check_number';
//
//
//				break;
			}

		} // END foreach(header column)

		// OKAY, HEADERS ARE ORGANIZED BY THEIR ARRAY INDEX AND PERSPECTIVE DB FIELD NAMES
		// LOOP THROUGH EACH LINE, PARSE AND INSERT
		foreach($lines as $linenum => $line){

			// SKIP HEADER LINE
			if($linenum == 0)continue;

			//$line_data = preg_split("/,/", $line);

			// SKIP BLANK LINES
			if(trim($line) == '')continue;

			$line_data = str_getcsv ($line,$sep,'"');

//print_r($line_data);
//exit;

			$dat = array();
			$dat['pac_id']		= $_SESSION['fecdata']['current_pac']['id'];
			$dat['filing_id']	= $_SESSION['fecdata']['current_file']['id'];


			$full_address = '';

			foreach($header_format as $idx=>$field_name){


				// DETECT AND STRIP QUOTES
				if($line_data[$idx][0] == '"' && $line_data[$idx][strlen($line_data[$idx])-1] == '"'){

					$line_data[$idx] = substr($line_data[$idx],1, strlen($line_data[$idx])-2);

				}


				$line_data[$idx] = trim($line_data[$idx]);

				switch($field_name){
				default:

					$dat[$field_name] = trim($line_data[$idx]);

					break;

				case 'amount':

					$dat[$field_name] = preg_replace("/[^0-9-.]/", '', $line_data[$idx]);

					break;


				case 'category_code':

					$dat[$field_name] = intval($line_data[$idx]);

					break;
				case 'date':

					$dat[$field_name] = date("Y-m-d", strtotime($line_data[$idx]));

					break;

				case 'zip':

					$dat[$field_name] = preg_replace("/[^0-9]/", '', $line_data[$idx]);

					break;
				case 'address1':

					$full_address = $line_data[$idx];

					// IF IT DOESNT CONTAIN THE SECOND ADDRESS LINE
					if(!$has_address2_field){
						// ATTEMPT TO PARSE THE FIRST LINE INTO 2 LINES
						$tidx = stripos($line_data[$idx]," suite");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," ste");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," apt");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," BLDG");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," UNIT");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," LOT");
						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," SPC");

						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx]," TRLR");

						$tidx = ($tidx > -1)?$tidx:strripos($line_data[$idx],"#");

						// POSSIBLE DIVIDER DETECTED
						if($tidx > -1){

							$dat[$field_name] = trim(substr($line_data[$idx], 0,  $tidx));
							$dat['address2'] = trim(substr($line_data[$idx], $tidx));

						// NOTHING DETECTED, KEEP WHOLE
						}else{
							$dat[$field_name] = $line_data[$idx];
						}



					// ELSE JUST STORE AS ADDRESS1
					}else{
						$dat[$field_name] = $line_data[$idx];
					}

					break;

				} // END SWITCH(field name)

			} // END FOREACH(header field)


			$dat['unique_id'] = md5(trim($dat['company']).' '.trim($dat['zip']).' '.substr($full_address,0,8));


			// MUST HAVE A DATE SPECIFIED, TO BE INCLUDED
			if($dat['date']){

				$cnt += aadd($dat, $this->expenses_table);

			}



		} // END FOREACH(line)




		return $cnt;
	}



	/**
	 * Build the entire Export Report, by calling each of the sub functions of the report.
	 * @return The header, body and schedules of the FEC report, using the specified field seperator and line seperators
	 *
	 */
	function exportReport(){

		$output = $this->exportReport_Header();
		$output .= $this->exportReport_ReportMain();
		$output .= $this->exportReport_Schedules();

		return $output;
	}


	/**
	 * Header Line for the FEC Reporting, includes software name/version, fec format version, and other basics
	 * (This is the first line of ANY FEC report.)
	 */
	function exportReport_Header(){

		$arr = array(
				'HDR',
				'FEC',
				$this->fec_format_version,
				$this->software_name,
				$this->software_version,

				// SKIPPING FOR NOW, FINISH THIS LATER!
				$_SESSION['fecdata']['current_file']['hdr_fec_report_id'], // FEC report ID of original report (Amendment only)
				($_SESSION['fecdata']['current_file']['hdr_fec_report_num']== 0)?'':$_SESSION['fecdata']['current_file']['hdr_fec_report_num'], // Sequential number of amendments
				$_SESSION['fecdata']['current_file']['hdr_comment'] // HEADER COMMENT (Use as needed  {no tabs, line-feeds, etc. allowed})


			);

		return $this->renderArrayAsLine($arr);

	} // END OF exportReport_Header()





	/**
	 * Exports the main line of the report(filing table record)
	 * (This is the second line of every F3X report(at least))
	 */
	function exportReport_ReportMain(){

		$file = $_SESSION['fecdata']['current_file'];


		$tmptime = strtotime($file['election_date']);

		/**
		 * Construct the massive primary record row, built from the "pacs_filings" table
		 */
		$arr = array(


			$file['form_type'],				// 1	FORM TYPE
			$file['committee_id'],			// 2	FILER COMMITTEE ID NUMBER
			$_SESSION['fecdata']['current_pac']['name'], // 3	COMMITTEE NAME

			($file['request_change_of_address'] == 'yes')?"X":"",	// 4	CHANGE OF ADDRESS

			$file['address1'],				// 5	STREET  1
			$file['address2'],				// 6	STREET  2
			$file['city'],					// 7	CITY
			$file['state'],					// 8	STATE
			$file['zip'],					// 9	ZIP
			$file['report_code'],			// 10	REPORT CODE
			$file['election_code'],			// 11	ELECTION CODE  {was RPTPGI}

			(($tmptime < 0)?'':$this->dateFormat($file['election_date'])),	// 12	DATE OF ELECTION
			$file['election_state'],					// 13	STATE OF ELECTION
			$this->dateFormat($file['start_date']),		// 14	COVERAGE FROM DATE
			$this->dateFormat($file['end_date']),		// 15	COVERAGE THROUGH DATE

			($file['qualified_committee']=='yes')?"X":'',// 16	QUALIFIED COMMITTEE

			$file['treasurer_last_name'],				// 17	TREASURER LAST NAME
			$file['treasurer_first_name'],				// 18	TREASURER FIRST NAME
			$file['treasurer_middle_name'],				// 19	TREASURER MIDDLE NAME
			$file['treasurer_prefix'],					// 20	TREASURER PREFIX
			$file['treasurer_suffix'],					// 21	TREASURER SUFFIX
			$this->dateFormat($file['date_signed']),	// 22	DATE SIGNED

			// START OF COLUMN A FIELDS!
			$file['colA_6b_cash_on_hand'],				// 23	6(b) Cash on Hand beginning
			$file['colA_6c_total_receipts'],			// 24	6(c) Total Receipts

			$file['colA_6d_subtotal'],					// 25	6(d) Subtotal
			$file['colA_7_total_disbursements'],		// 26	7. Total Disbursements
			$file['colA_8_cash_on_hand_close'],			// 27	8. Cash on Hand at Close
			$file['colA_9_debts_to'],					// 28	9. Debts to
			$file['colA_10_debts_by'],					// 29	10. Debts by
			$file['colA_11ai_itemized'],				// 30	11(a)i  Itemized
			$file['colA_11aii_unitemized'],				// 31	11(a)ii  Unitemized
			$file['colA_11aiii_total'],					// 32	11(a)iii Total

			$file['colA_11b_pol_party_committees'],		// 33	11(b) Political Party Committees
			$file['colA_11c_other_pacs'],				// 34	11(c) Other Political Committees (PACs)
			$file['colA_11d_total_contributions'],		// 35	11(d) Total Contributions

			$file['colA_12_transfers'],					// 36	12. Transfers from Affiliated/Other Party Cmtes
			$file['colA_13_loans_received'],			// 37	13. All Loans Received
			$file['colA_14_loan_repayments_received'],	// 38	14. Loan Repayments Received
			$file['colA_15_offsets_refunds'],			// 39	15. Offsets to Operating Expenditures (refunds)
			$file['colA_16_fed_contrib_refund'],		// 40	16. Refunds of Federal Contributions
			$file['colA_17_other_fed_receipts'],		// 41	17. Other Federal Receipts (dividends)
			$file['colA_18a_trans_nonfed_h3'],			// 42	18(a) Transfers from Nonfederal Account (H3)
			$file['colA_18b_trans_nonfed_h5'],			// 43	18(b) Transfers from Non-Federal (Levin - H5)
			$file['colA_18c_trans_nonfed_total'],		// 44	18(c) Total Non-Federal Transfers (18a+18b)
			$file['colA_19_total_receipts'],			// 45	19. Total Receipts

			$file['colA_20_total_fed_receipts'],		// 46	20. Total Federal Receipts
			$file['colA_21ai_fed_share'],				// 47	21(a)i  Federal Share
			$file['colA_21aii_nonfed_share'],			// 48	21(a)ii  Non-Federal Share
			$file['colA_21b_other_fed_expenditures'],	// 49	21(b)  Other Federal Operating Expenditures
			$file['colA_21c_total_operating_expenditures'],	// 50	21(c)  Total Operating Expenditures
			$file['colA_22_trans_affiliated_partys'],	// 51	22. Transfers to Affiliated/Other Party Cmtes
			$file['colA_23_contrib_fed_candidates'],	// 52	23. Contributions to Federal Candidates/Cmtes
			$file['colA_24_indep_expenditure'],			// 53	24. Independent Expenditures
			$file['colA_25_coord_expenditures'],		// 54	25. Coordinated Expend made by Party Cmtes
			$file['colA_26_loan_repayments'],			// 55	26. Loan Repayments
			$file['colA_27_loans_made'],				// 56	27. Loans Made
			$file['colA_28a_individuals'],				// 57	28(a) Individuals/Persons
			$file['colA_28b_pol_party_committees'],		// 58	28(b) Political Party Committees
			$file['colA_28c_other_pacs'],				// 59	28(c) Other Political Committees
			$file['colA_28d_total_contrib_refunds'],	// 60	28(d) Total Contributions Refunds
			$file['colA_29_other_disbursements'],		// 61	29. Other Disbursements

			$file['colA_30ai_shared_fed_activity_h6_fed'],// 62	30(a)i  Shared Federal Activity (H6) Fed Share
			$file['colA_30aii_shared_fed_activity_nonfed'],// 63	30(a)ii Shared Federal Activity (H6) Non-Fed
			$file['colA_30b_non_allocatable'],			// 64	30(b) Non-Allocable 100% Fed Election Activity
			$file['colA_30c_total_fed_election_activity'],// 65	30(c) Total Federal Election Activity
			$file['colA_31_total_dibursements'],		// 66	31. Total Disbursements
			$file['colA_32_total_fed_disbursements'],	// 67	32. Total Federal Disbursements
			$file['colA_33_total_contributions'],		// 68	33. Total Contributions
			$file['colA_34_total_contribution_refunds'],// 69	34. Total Contribution Refunds
			$file['colA_35_net_contributions'],			// 70	35. Net Contributions
			$file['colA_36_total_fed_op_expenditures'],	// 71	36. Total Federal Operating Expenditures
			$file['colA_37_offset_to_op_expenditures'],	// 72	37. Offsets to Operating Expenditures
			$file['colA_38_net_op_expenditures'],		// 73	38. Net Operating Expenditures


			// START OF COLUMN B!
			$file['colB_6a_cash_on_hand'],				// 74	 6(a) Cash on Hand Jan 1, 19
			$file['colB_year_for_above'],				// 75	Year for Above
			$file['colB_6c_total_receipts'],			// 76	6(c) Total Receipts
			$file['colB_6d_subtotal'],					// 77	6(d) Subtotal
			$file['colB_7_total_disbursements'],		// 78	7. Total disbursements
			$file['colB_8_cash_on_hand_close'],			// 79	8. Cash on Hand Close

			$file['colB_11ai_itemized'],				// 80	11(a)i  Itemized
			$file['colB_11aii_unitemized'],				// 81	11(a)ii  Unitemized
			$file['colB_11aiii_total'],					// 82	11(a)iii Total
			$file['colB_11b_pol_party_committees'],		// 83	11(b) Political Party committees
			$file['colB_11c_other_pacs'],				// 84	11(c) Other Political Committees  (PACs)
			$file['colB_11d_total_contributions'],		// 85	11(d) Total Contributions

			$file['colB_12_transfers'],					// 86	12. Transfers from Affiliated/Other Party Cmtes
			$file['colB_13_loans_received'],			// 87	13. All Loans Received
			$file['colB_14_loan_repayments_received'],	// 88	14. Loan Repayments Received
			$file['colB_15_offsets_refunds'],			// 89	15. Offsets to Operating Expenditures (refunds)
			$file['colB_16_fed_contrib_refund'],		// 90	16. Refunds of Federal Contributions
			$file['colB_17_other_fed_receipts'],		// 91	17. Other Federal Receipts (dividends)
			$file['colB_18a_trans_nonfed_h3'],			// 92	18(a) Transfers from Nonfederal Account (H3)
			$file['colB_18b_trans_nonfed_h5'],			// 93	18(b) Transfers from Non-Federal (Levin - H5)
			$file['colB_18c_trans_nonfed_total'],		// 94	18(c) Total Non-Federal Transfers (18a+18b)
			$file['colB_19_total_receipts'],			// 95	19. Total Receipts


			$file['colB_20_total_fed_receipts'],		// 96	20. Total Federal Receipts
			$file['colB_21ai_fed_share'],				// 97	21(a)i Federal Share
			$file['colB_21aii_nonfed_share'],			// 98	21(a)ii Non-Federal Share
			$file['colB_21b_other_fed_expenditures'],	// 99	21(b) Other Federal Operating Expenditures
			$file['colB_21c_total_operating_expenditures'],	// 100	21(c) Total operating Expenditures
			$file['colB_22_trans_affiliated_partys'],	// 101	22. Transfers to Affiliated/Other Party Cmtes
			$file['colB_23_contrib_fed_candidates'],	// 102	23. Contributions to Federal Candidates/Cmtes
			$file['colB_24_indep_expenditure'],			// 103	24. Independent Expenditures
			$file['colB_25_coord_expenditures'],		// 104	25. Coordinated Expend made by Party Cmtes
			$file['colB_26_loan_repayments'],			// 105	26. Loan Repayments Made
			$file['colB_27_loans_made'],				// 106	27. Loans Made
			$file['colB_28a_individuals'],				// 107	28(a) Individuals/Persons
			$file['colB_28b_pol_party_committees'],		// 108	28(b) Political Party Committees
			$file['colB_28c_other_pacs'],				// 109	28(c) Other Political  Committees
			$file['colB_28d_total_contrib_refunds'],	// 110	28(d) Total contributions Refunds
			$file['colB_29_other_disbursements'],		// 111	29. Other Disbursements


			$file['colB_30ai_shared_fed_activity_h6_fed'],// 112	30(a)i  Shared Federal Activity (H6) Fed Share
			$file['colB_30aii_shared_fed_activity_nonfed'],// 113	30(a)ii Shared Federal Activity (H6) Non-Fed
			$file['colB_30b_non_allocatable'],			// 114	30(b) Non-Allocable 100% Fed Election Activity
			$file['colB_30c_total_fed_election_activity'],// 115	30(c) Total Federal Election Activity
			$file['colB_31_total_dibursements'],		// 116	31. Total Disbursements
			$file['colB_32_total_fed_disbursements'],	// 117	32. Total Federal Disbursements
			$file['colB_33_total_contributions'],		// 118	33. Total Contributions
			$file['colB_34_total_contribution_refunds'],// 119	34. Total Contribution Refunds
			$file['colB_35_net_contributions'],			// 120	35. Net contributions
			$file['colB_36_total_fed_op_expenditures'],	// 121	36. Total Federal Operating Expenditures
			$file['colB_37_offset_to_op_expenditures'],	// 122	37. Offsets to Operating Expenditures
			$file['colB_38_net_op_expenditures']		// 123	38. Net Operating Expenditures





		);

		return $this->renderArrayAsLine($arr);

	} // END OF exportReport_ReportMain();






	/**
	 * Exports the SCHEDULES, which come after the HEADER and REPORT MAIN (second line) ROWS
	 * (Starts at line 3)
	 */
	function exportReport_Schedules(){


	// LOAD THE SCHEDULES FROM THE DB
		$schedules = $this->loadSchedules();

	// INIT VARIABLES

		$output = "";

		foreach($schedules as $row){

			$shorttype = substr($row['form_type'],0,2);
			if($shorttype == 'SA'){

				$arr = array(

					$row['form_type'],										//1	FORM TYPE
					$row['committee_id'],	//2	FILER COMMITTEE ID NUMBER
					$row['transaction_id'],//$row['form_type'].'-'.$row['id'],						//3	TRANSACTION ID
					$row['reference_trans_id'],								//4	BACK REFERENCE TRAN ID NUMBER
					$row['reference_sched_name'],							//5	BACK REFERENCE SCHED NAME

					$row['entity_type'],					// 6	ENTITY TYPE
					$row['organization_name'],				// 7	CONTRIBUTOR ORGANIZATION NAME
					$row['last_name'],						// 8	CONTRIBUTOR LAST NAME
					$row['first_name'],						// 9	CONTRIBUTOR FIRST NAME
					$row['middle_name'],					// 10	CONTRIBUTOR MIDDLE NAME
					$row['prefix'],							// 11	CONTRIBUTOR PREFIX
					$row['suffix'],							// 12	CONTRIBUTOR SUFFIX
					$row['address1'],						// 13	CONTRIBUTOR STREET  1
					$row['address2'],						// 14	CONTRIBUTOR STREET  2
					$row['city'],							// 15	CONTRIBUTOR CITY
					$row['state'],							// 16	CONTRIBUTOR STATE
					$row['zip'],							// 17	CONTRIBUTOR ZIP

					$row['election_code'],					// 18	ELECTION CODE
					$row['election_description'],			// 19	ELECTION OTHER DESCRIPTION

					$this->dateFormat($row['date']),		// 20	CONTRIBUTION DATE
					$row['amount'],							// 21	CONTRIBUTION AMOUNT {F3L Bundled}
					($row['amount_aggregate'] == 0)?'':$row['amount_aggregate'],				// 22	CONTRIBUTION AGGREGATE {F3L Semi-annual Bundled}

					$row['purpose_description'],			// 23	CONTRIBUTION PURPOSE DESCRIP
					$row['employer'],						// 24	CONTRIBUTOR EMPLOYER
					$row['occupation'],						// 25	CONTRIBUTOR OCCUPATION
					$row['committee_fec_id'],				// 26	DONOR COMMITTEE FEC ID
					$row['committee_name'],					// 27	DONOR COMMITTEE NAME
					$row['candidate_fec_id'],				// 28	DONOR CANDIDATE FEC ID
					$row['candidate_last_name'],			// 29	DONOR CANDIDATE LAST NAME
					$row['candidate_first_name'],			// 30	DONOR CANDIDATE FIRST NAME
					$row['candidate_middle_name'],			// 31	DONOR CANDIDATE MIDDLE NAME
					$row['candidate_prefix'],				// 32	DONOR CANDIDATE PREFIX
					$row['candidate_suffix'],				// 33	DONOR CANDIDATE SUFFIX
					$row['candidate_office'],				// 34	DONOR CANDIDATE OFFICE
					$row['candidate_state'],				// 35	DONOR CANDIDATE STATE

					($row['candidate_district'] == 0)?'':$row['candidate_district'],				// 36	DONOR CANDIDATE DISTRICT

					$row['conduit_name'],					// 37	CONDUIT NAME
					$row['conduit_address1'],				// 38	CONDUIT STREET1
					$row['conduit_address2'],				// 39	CONDUIT STREET2
					$row['conduit_city'],					// 40	CONDUIT CITY
					$row['conduit_state'],					// 41	CONDUIT STATE
					$row['conduit_zip'],					// 42	CONDUIT ZIP

					($row['memo_code'] == 'yes')?'X':'',	// 43	MEMO CODE
					$row['memo_description'],				// 44	MEMO TEXT/DESCRIPTION

					$row['reference_system_code'],			// 45	Reference to SI or SL system code that identifies the Account

				);


				$output .= $this->renderArrayAsLine($arr);

			// SCHEDULE B RENDERING (DISBURSEMENTS)
			}else if($shorttype == 'SB'){

				$arr = array(

					$row['form_type'],										//1	FORM TYPE
					$row['committee_id'], //$_SESSION['fecdata']['current_file']['committee_id'],	//2	FILER COMMITTEE ID NUMBER
					$row['transaction_id'],						//$row['form_type'].'-'.$row['id'] 3	TRANSACTION ID
					$row['reference_trans_id'],								//4	BACK REFERENCE TRAN ID NUMBER
					$row['reference_sched_name'],							//5	BACK REFERENCE SCHED NAME

					$row['entity_type'],					// 6	ENTITY TYPE
					$row['organization_name'],				// 7	PAYEE ORGANIZATION NAME
					$row['last_name'],						// 8	PAYEE LAST NAME
					$row['first_name'],						// 9	PAYEE FIRST NAME
					$row['middle_name'],					// 10	PAYEE MIDDLE NAME
					$row['prefix'],							// 11	PAYEE PREFIX
					$row['suffix'],							// 12	PAYEE SUFFIX
					$row['address1'],						// 13	PAYEE STREET  1
					$row['address2'],						// 14	PAYEE STREET  2
					$row['city'],							// 15	PAYEE CITY
					$row['state'],							// 16	PAYEE STATE
					$row['zip'],							// 17	PAYEE ZIP

					$row['election_code'],					// 18	ELECTION CODE
					$row['election_description'],			// 19	ELECTION OTHER DESCRIPTION

					$this->dateFormat($row['date']),		// 20	EXPENDITURE DATE
					$row['amount'],							// 21	EXPENDITURE AMOUNT {F3L Bundled}
					($row['amount_aggregate'] == 0)?'':$row['amount_aggregate'],				// 22	SEMI-ANNUAL REFUNDED BUNDLED AMT

					$row['purpose_description'],			// 23	EXPENDITURE PURPOSE DESCRIP

					(($row['category_code'] > 0)?zeropad($row['category_code'], 3):''),					// 24	CATEGORY CODE

					$row['committee_fec_id'],				// 25	BENEFICIARY COMMITTEE FEC ID
					$row['committee_name'],					// 26	BENEFICIARY COMMITTEE NAME
					$row['candidate_fec_id'],				// 27	BENEFICIARY CANDIDATE FEC ID
					$row['candidate_last_name'],			// 28	BENEFICIARY CANDIDATE LAST NAME
					$row['candidate_first_name'],			// 29	BENEFICIARY CANDIDATE FIRST NAME
					$row['candidate_middle_name'],			// 30	BENEFICIARY CANDIDATE MIDDLE NAME
					$row['candidate_prefix'],				// 31	BENEFICIARY CANDIDATE PREFIX
					$row['candidate_suffix'],				// 32	BENEFICIARY CANDIDATE SUFFIX
					$row['candidate_office'],				// 33	BENEFICIARY CANDIDATE OFFICE
					$row['candidate_state'],				// 34	BENEFICIARY CANDIDATE STATE
					($row['candidate_district'] == 0)?'':$row['candidate_district'],				// 35	BENEFICIARY CANDIDATE DISTRICT
					$row['conduit_name'],					// 36	CONDUIT NAME
					$row['conduit_address1'],				// 37	CONDUIT STREET1
					$row['conduit_address2'],				// 38	CONDUIT STREET2
					$row['conduit_city'],					// 39	CONDUIT CITY
					$row['conduit_state'],					// 40	CONDUIT STATE
					$row['conduit_zip'],					// 41	CONDUIT ZIP

					($row['memo_code'] == 'yes')?'X':'',						// 42	MEMO CODE
					$row['memo_description'],				// 43	MEMO TEXT/DESCRIPTION

					$row['reference_system_code'],			// 44	Reference to SI or SL system code that identifies the Account

				);


				$output .= $this->renderArrayAsLine($arr);

			}else{

				$arr = '';

			}






			// ADD THE END OF LINE (already added in the renderArrayAsLine() function)
			//$output .= $eol;
		}


		return $output;

	} // END OF exportReport_Schedules()











	/**
	 * Assumes MYSQL Date format as input
	 * ie "2018-07-06"
	 *
	 * Expected/required output:
	 * YYYYMMDD
	 * "20180706"
	 */
	function dateFormat($in){

		return preg_replace("/[^0-9]/",'',$in);
	}


	function filterField($in){

		return preg_replace('/,/', '_', $in);
	}


	var $encapsulate_with = '"';
	var $file_extension = "csv";

	function setFormat($format){

		$format = strtoupper($format);

		if($format == 'FEC'){

			$this->field_sep = chr(28);
			$this->encapsulate_with = '';
			$this->file_extension = "fec";

		// TAB SEPARATED
		}else if($format == 'TSV'){

			$this->field_sep = "\t";
			$this->encapsulate_with = '';
			$this->file_extension = "tsv";

		// CSV
		}else{

			$this->field_sep = ',';
			$this->encapsulate_with = '"';
			$this->file_extension = "csv";

		}
	}

	function renderArrayAsLine($arr){


		$encapsulate_with = $this->encapsulate_with;

	// INIT VARIABLES
		$output = "";

		$x=0;
		foreach($arr as $text){

			if($x++ > 0)$output .= $this->field_sep;

			// POTENTIAL TO FILTER HERE!
			$output .= $encapsulate_with.$this->filterField($text).$encapsulate_with;

		}

		$output .= $this->line_ending;

		return $output;
	}



	function createItemizedSchedulesFromDonations(){


		$ytd = $_SESSION['fecdata']['current_file']['colB_year_for_above'].'-01-01';

		$sql = "SELECT * FROM (".
			"SELECT SUM(amount) as total_amount, unique_id FROM pacs_donations ".
			"WHERE `date` between '".$ytd."' AND '".$_SESSION['fecdata']['current_file']['end_date']."' ".
			" AND `pac_id`='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
			" AND `filing_id`='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
		  	"GROUP BY `unique_id` ".
		  	"ORDER BY `total_amount` DESC) AS a ".
		  	"WHERE total_amount >= 200";

		$res = query($sql);

		$cnt=0;

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


			// GET ALL CUSTOMERS THAT DONATED ANYTHING GREATER THAN 200 FOR THE YEAR
			// GET EVERYTHING THAT CUSTOMER DID WITHIN THE QUARTER
			$re2 = query("SELECT * FROM pacs_donations ".
						"WHERE `date` between '".$_SESSION['fecdata']['current_file']['start_date']."' AND '".$_SESSION['fecdata']['current_file']['end_date']."' ".
						" AND `pac_id`='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
						" AND `filing_id`='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
						" AND `unique_id`='".$row['unique_id']."'"
			);

			while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){


				$dat = array();
				$dat['pac_id']			= $_SESSION['fecdata']['current_pac']['id'];
				$dat['filing_id']		= $_SESSION['fecdata']['current_file']['id'];
				$dat['committee_id']	= $_SESSION['fecdata']['current_file']['committee_id'];

				$dat['date']			= $r2['date'];

				// GENERATE A TRANSACTION ID FOR THIS SCHEDULE
				$dat['transaction_id']	= 'SA11AI-'.$r2['id'];
				$dat['form_type']		= 'SA11AI';

				$dat['entity_type']		= 'IND';

				$dat['first_name']		= $r2['first_name'];
				$dat['last_name']		= $r2['last_name'];
				$dat['middle_name']		= $r2['middle_name'];
				$dat['address1']		= $r2['address1'];
				$dat['address2']		= $r2['address2'];

				$dat['city']		= $r2['city'];
				$dat['state']		= $r2['state'];
				$dat['zip']			= $r2['zip'];

				$dat['amount']					= $r2['amount'];
				$dat['amount_aggregate']		= $row['total_amount'];

				$dat['employer']		= $r2['employer'];
				$dat['occupation']		= $r2['occupation'];

				$cnt += aadd($dat,$this->schedule_table);

			}


		}

		return $cnt;
	}



	function createItemizedSchedulesFromExpenses(){


		$ytd = $_SESSION['fecdata']['current_file']['colB_year_for_above'].'-01-01';

		$sql = "SELECT * FROM (".
			"SELECT SUM(amount) as total_amount, unique_id FROM pacs_expenses ".
			"WHERE `date` between '".$ytd."' AND '".$_SESSION['fecdata']['current_file']['end_date']."' ".
			" AND `pac_id`='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
			" AND `filing_id`='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
		  	"GROUP BY `unique_id` ".
		  	"ORDER BY `total_amount` DESC) AS a ".
		  	"WHERE total_amount >= 200";

		$res = query($sql);

		$cnt=0;

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){


			// GET ALL CUSTOMERS THAT DONATED ANYTHING GREATER THAN 200 FOR THE YEAR
			// GET EVERYTHING THAT CUSTOMER DID WITHIN THE QUARTER
			$re2 = query("SELECT * FROM pacs_expenses ".
						"WHERE `date` between '".$_SESSION['fecdata']['current_file']['start_date']."' AND '".$_SESSION['fecdata']['current_file']['end_date']."' ".
						" AND `pac_id`='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
						" AND `filing_id`='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
						" AND `unique_id`='".$row['unique_id']."'"
			);

			while($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)){


				$dat = array();
				$dat['pac_id']			= $_SESSION['fecdata']['current_pac']['id'];
				$dat['filing_id']		= $_SESSION['fecdata']['current_file']['id'];
				$dat['committee_id']	= $_SESSION['fecdata']['current_file']['committee_id'];

				$dat['date']			= $r2['date'];


				switch($r2['category_code']){
				default:
				case 1:
					// GENERATE A TRANSACTION ID FOR THIS SCHEDULE
					$dat['transaction_id']	= 'SB21B-'.$r2['id'];
					$dat['form_type']		= 'SB21B';

					break;
				}

				if(trim($r2['company'])){


					$dat['entity_type']		= 'ORG';
					$dat['organization_name']	= $r2['company'];


				}else{


					$dat['entity_type']		= 'IND';
					$dat['first_name']		= $r2['first_name'];
					$dat['last_name']		= $r2['last_name'];
					$dat['middle_name']		= $r2['middle_name'];
				}

				$dat['address1']		= $r2['address1'];
				$dat['address2']		= $r2['address2'];

				$dat['city']		= $r2['city'];
				$dat['state']		= $r2['state'];
				$dat['zip']			= $r2['zip'];

				$dat['amount']					= $r2['amount'];
				$dat['amount_aggregate']		= $row['total_amount'];

				$cnt += aadd($dat,$this->schedule_table);

			}


		}

		return $cnt;
	}



	/***
	 * Calculate the Column B numbers, using the specified previous (Quarter)'s filing
	 *
	 * Quote from FEC F3X MANUAL:
	 *	To derive the “Calendar Year-toDate”
		figure for each category, the
		political committee should add the
		“Calendar Year-to-Date” total from
		the previous report to the “Total This
		Period” from Column A for the current
		report. For the first report filed
		for a calendar year, the “Calendar
		Year-to-Date” figure is equal to the
		“Total This Period” figure
	 *
	 */
	function calculateYearToDateTotals($prev_file_id){

		$file = $_SESSION['fecdata']['current_file'];

		// TAKE THE PREVIOUS QUARTERS B COLUMN, AND ADD TO THE CURRENT QUARTERS A COLUMN
		$prev_file = $this->loadFiling($_SESSION['fecdata']['current_pac']['id'], $prev_file_id);

//echo nl2br(print_r($file,1));
//echo nl2br(print_r($prev_file,1));
//exit;

		// FIRST QUARTER, JUST COPY THE FIELDS OVER FROM COLUMN A
		if($file['report_code'] == 'Q1'){

			// CURRENT FILES B COLUMN = POPULATED FROM THE A COLUMN
			// $file['B COLUMN'] = $file['A COLUMN'];

			$file['colB_6a_cash_on_hand']			= $file['colA_6b_cash_on_hand'];
			$file['colB_6c_total_receipts']			= $file['colA_6c_total_receipts'];
			$file['colB_6d_subtotal']				= $file['colA_6d_subtotal'];
			$file['colB_7_total_disbursements']		= $file['colA_7_total_disbursements'];
			$file['colB_8_cash_on_hand_close']		= $file['colA_8_cash_on_hand_close'];

			$file['colB_11ai_itemized']				= $file['colA_11ai_itemized'];
			$file['colA_11aii_unitemized']			= $file['colA_11aii_unitemized'];
			$file['colB_11aiii_total']				= $file['colA_11aiii_total'];
			$file['colB_11b_pol_party_committees']	= $file['colA_11b_pol_party_committees'];
			$file['colB_11c_other_pacs']			= $file['colA_11c_other_pacs'];
			$file['colB_11d_total_contributions']	= $file['colA_11d_total_contributions'];
			$file['colB_12_transfers']				= $file['colA_12_transfers'];

			$file['colB_13_loans_received']			= $file['colA_13_loans_received'];
			$file['colB_14_loan_repayments_received']= $file['colA_14_loan_repayments_received'];
			$file['colB_15_offsets_refunds']		= $file['colA_15_offsets_refunds'];
			$file['colB_16_fed_contrib_refund']		= $file['colA_16_fed_contrib_refund'];
			$file['colB_17_other_fed_receipts']		= $file['colA_17_other_fed_receipts'];

			$file['colB_18a_trans_nonfed_h3']		= $file['colA_18a_trans_nonfed_h3'];
			$file['colB_18b_trans_nonfed_h5']		= $file['colA_18b_trans_nonfed_h5'];

			$file['colB_18c_trans_nonfed_total']	= $file['colA_18c_trans_nonfed_total'];
			$file['colB_19_total_receipts']			= $file['colA_19_total_receipts'];
			$file['colB_20_total_fed_receipts']		= $file['colA_20_total_fed_receipts'];
			$file['colB_21ai_fed_share']			= $file['colA_21ai_fed_share'];
			$file['colB_21aii_nonfed_share']		= $file['colA_21aii_nonfed_share'];
			$file['colB_21b_other_fed_expenditures']= $file['colA_21b_other_fed_expenditures'];
			$file['colB_21c_total_operating_expenditures']= $file['colA_21c_total_operating_expenditures'];
			$file['colB_22_trans_affiliated_partys']= $file['colA_22_trans_affiliated_partys'];
			$file['colB_23_contrib_fed_candidates']	= $file['colA_23_contrib_fed_candidates'];
			$file['colB_24_indep_expenditure']		= $file['colA_24_indep_expenditure'];
			$file['colB_25_coord_expenditures']		= $file['colA_25_coord_expenditures'];

			$file['colB_26_loan_repayments']		= $file['colA_26_loan_repayments'];
			$file['colB_27_loans_made']				= $file['colA_27_loans_made'];
			$file['colB_28a_individuals']			= $file['colA_28a_individuals'];
			$file['colB_28b_pol_party_committees']	= $file['colA_28b_pol_party_committees'];
			$file['colB_28c_other_pacs']			= $file['colA_28c_other_pacs'];
			$file['colB_28d_total_contrib_refunds']	= $file['colA_28d_total_contrib_refunds'];
			$file['colB_29_other_disbursements']	= $file['colA_29_other_disbursements'];


			$file['colB_30ai_shared_fed_activity_h6_fed']	= $file['colA_30ai_shared_fed_activity_h6_fed'];
			$file['colB_30aii_shared_fed_activity_nonfed']	= $file['colA_30aii_shared_fed_activity_nonfed'];

			$file['colB_30b_non_allocatable']				= $file['colA_30b_non_allocatable'];
			$file['colB_30c_total_fed_election_activity']	= $file['colA_30c_total_fed_election_activity'];
			$file['colB_31_total_dibursements']				= $file['colA_31_total_dibursements'];
			$file['colB_32_total_fed_disbursements']		= $file['colA_32_total_fed_disbursements'];
			$file['colB_33_total_contributions']			= $file['colA_33_total_contributions'];
			$file['colB_34_total_contribution_refunds']		= $file['colA_34_total_contribution_refunds'];
			$file['colB_35_net_contributions']				= $file['colA_35_net_contributions'];
			$file['colB_36_total_fed_op_expenditures']		= $file['colA_36_total_fed_op_expenditures'];

			$file['colB_37_offset_to_op_expenditures']		= $file['colA_37_offset_to_op_expenditures'];
			$file['colB_38_net_op_expenditures']			= $file['colA_38_net_op_expenditures'];





		// ELSE PULL FROM THE PREVIOUS QUARTERS B-COLUMN
		}else{

			//$file['B COLUMN'] = $prev_file['B COLUMN'] + $file['A COLUMN'];

			// CASH ON HAND AT THE START OF THE YEAR, PULL FROM THE PREVIOUS FILE
			$file['colB_6a_cash_on_hand'] = $prev_file['colB_6a_cash_on_hand'];


			$file['colB_6c_total_receipts']			= $prev_file['colB_6c_total_receipts'] + $file['colA_6c_total_receipts'];


			//add Lines 6(a) and 6(c) to derive the figure for Column B
			$file['colB_6d_subtotal']				= $file['colB_6a_cash_on_hand'] + $file['colB_6c_total_receipts'];///$prev_file['colB_6d_subtotal'] + $file['colA_6d_subtotal'];

			$file['colB_7_total_disbursements']		= $prev_file['colB_7_total_disbursements'] + $file['colA_7_total_disbursements'];
			$file['colB_8_cash_on_hand_close']		= $file['colA_8_cash_on_hand_close']; //$prev_file['colB_8_cash_on_hand_close'] +

			$file['colB_11ai_itemized']				= $prev_file['colB_11ai_itemized'] + $file['colA_11ai_itemized'];
			$file['colB_11aii_unitemized']			= $prev_file['colB_11aii_unitemized'] + $file['colA_11aii_unitemized'];
			$file['colB_11aiii_total']				= $prev_file['colB_11aiii_total'] + $file['colA_11aiii_total'];
			$file['colB_11b_pol_party_committees']	= $prev_file['colB_11b_pol_party_committees'] + $file['colA_11b_pol_party_committees'];
			$file['colB_11c_other_pacs']			= $prev_file['colB_11c_other_pacs'] + $file['colA_11c_other_pacs'];
			$file['colB_11d_total_contributions']	= $prev_file['colB_11d_total_contributions']  + $file['colA_11d_total_contributions'];
			$file['colB_12_transfers']				= $prev_file['colB_12_transfers'] + $file['colA_12_transfers'];

			$file['colB_13_loans_received']			= $prev_file['colB_13_loans_received'] + $file['colA_13_loans_received'];
			$file['colB_14_loan_repayments_received']= $prev_file['colB_14_loan_repayments_received'] + $file['colA_14_loan_repayments_received'];
			$file['colB_15_offsets_refunds']		= $prev_file['colB_15_offsets_refunds'] + $file['colA_15_offsets_refunds'];
			$file['colB_16_fed_contrib_refund']		= $prev_file['colB_16_fed_contrib_refund'] + $file['colA_16_fed_contrib_refund'];
			$file['colB_17_other_fed_receipts']		= $prev_file['colB_17_other_fed_receipts'] + $file['colA_17_other_fed_receipts'];

			$file['colB_18a_trans_nonfed_h3']		= $prev_file['colB_18a_trans_nonfed_h3'] + $file['colA_18a_trans_nonfed_h3'];
			$file['colB_18b_trans_nonfed_h5']		= $prev_file['colB_18b_trans_nonfed_h5'] + $file['colA_18b_trans_nonfed_h5'];

			$file['colB_18c_trans_nonfed_total']	= $prev_file['colB_18c_trans_nonfed_total'] + $file['colA_18c_trans_nonfed_total'];
			$file['colB_19_total_receipts']			= $prev_file['colB_19_total_receipts'] + $file['colA_19_total_receipts'];
			$file['colB_20_total_fed_receipts']		= $prev_file['colB_20_total_fed_receipts'] + $file['colA_20_total_fed_receipts'];
			$file['colB_21ai_fed_share']			= $prev_file['colB_21ai_fed_share'] + $file['colA_21ai_fed_share'];
			$file['colB_21aii_nonfed_share']		= $prev_file['colB_21aii_nonfed_share'] + $file['colA_21aii_nonfed_share'];
			$file['colB_21b_other_fed_expenditures']= $prev_file['colB_21b_other_fed_expenditures'] + $file['colA_21b_other_fed_expenditures'];
			$file['colB_21c_total_operating_expenditures']= $prev_file['colB_21c_total_operating_expenditures'] + $file['colA_21c_total_operating_expenditures'];
			$file['colB_22_trans_affiliated_partys']= $prev_file['colB_22_trans_affiliated_partys'] + $file['colA_22_trans_affiliated_partys'];
			$file['colB_23_contrib_fed_candidates']	= $prev_file['colB_23_contrib_fed_candidates'] + $file['colA_23_contrib_fed_candidates'];
			$file['colB_24_indep_expenditure']		= $prev_file['colB_24_indep_expenditure'] + $file['colA_24_indep_expenditure'];
			$file['colB_25_coord_expenditures']		= $prev_file['colB_25_coord_expenditures'] + $file['colA_25_coord_expenditures'];

			$file['colB_26_loan_repayments']		= $prev_file['colB_26_loan_repayments'] + $file['colA_26_loan_repayments'];
			$file['colB_27_loans_made']				= $prev_file['colB_27_loans_made'] + $file['colA_27_loans_made'];
			$file['colB_28a_individuals']			= $prev_file['colB_28a_individuals'] + $file['colA_28a_individuals'];
			$file['colB_28b_pol_party_committees']	= $prev_file['colB_28b_pol_party_committees'] + $file['colA_28b_pol_party_committees'];
			$file['colB_28c_other_pacs']			= $prev_file['colB_28c_other_pacs'] + $file['colA_28c_other_pacs'];
			$file['colB_28d_total_contrib_refunds']	= $prev_file['colB_28d_total_contrib_refunds'] + $file['colA_28d_total_contrib_refunds'];
			$file['colB_29_other_disbursements']	= $prev_file['colB_29_other_disbursements'] + $file['colA_29_other_disbursements'];


			$file['colB_30ai_shared_fed_activity_h6_fed']	= $prev_file['colB_30ai_shared_fed_activity_h6_fed'] + $file['colA_30ai_shared_fed_activity_h6_fed'];
			$file['colB_30aii_shared_fed_activity_nonfed']	= $prev_file['colB_30aii_shared_fed_activity_nonfed'] + $file['colA_30aii_shared_fed_activity_nonfed'];

			$file['colB_30b_non_allocatable']				= $prev_file['colB_30b_non_allocatable'] + $file['colA_30b_non_allocatable'];
			$file['colB_30c_total_fed_election_activity']	= $prev_file['colB_30c_total_fed_election_activity'] + $file['colA_30c_total_fed_election_activity'];
			$file['colB_31_total_dibursements']				= $prev_file['colB_31_total_dibursements'] + $file['colA_31_total_dibursements'];
			$file['colB_32_total_fed_disbursements']		= $prev_file['colB_32_total_fed_disbursements'] + $file['colA_32_total_fed_disbursements'];
			$file['colB_33_total_contributions']			= $prev_file['colB_33_total_contributions'] + $file['colA_33_total_contributions'];
			$file['colB_34_total_contribution_refunds']		= $prev_file['colB_34_total_contribution_refunds'] + $file['colA_34_total_contribution_refunds'];
			$file['colB_35_net_contributions']				= $prev_file['colB_35_net_contributions'] + $file['colA_35_net_contributions'];
			$file['colB_36_total_fed_op_expenditures']		= $prev_file['colB_36_total_fed_op_expenditures'] + $file['colA_36_total_fed_op_expenditures'];

			$file['colB_37_offset_to_op_expenditures']		= $prev_file['colB_37_offset_to_op_expenditures'] + $file['colA_37_offset_to_op_expenditures'];
			$file['colB_38_net_op_expenditures']			= $prev_file['colB_38_net_op_expenditures'] + $file['colA_38_net_op_expenditures'];


		} // END if(report quarter)





		// COPY IT BACK TO THE SESSION ONCE CALCULATED
		$_SESSION['fecdata']['current_file'] = $file;

		$this->saveCurrentFile();


	}


	function saveCurrentFile(){

		$dat = array();

		foreach($_SESSION['fecdata']['current_file'] as $key=>$val){
			$tpos = stripos($key, "col");

			if($tpos !== FALSE && $tpos == 0){
				$dat[$key] = preg_replace("/[^0-9-.]/",'',$val);
			}

		}
		aedit($_SESSION['fecdata']['current_file']['id'], $dat, $this->table);

	}





	/**
	 * Attempt to auto-calculate the suggested totals
	 */
	function updateFilingTotals(){

//		$dat = array();
//
//		// TOTAL SCHEDULE A - 11ai
		$sql = "SELECT SUM(amount) FROM `".$this->schedule_table."` ".
							" WHERE `pac_id`='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
							" AND `filing_id`='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
							" AND `form_type`='SA11AI' ";
//		echo $sql;
		list($schA_11ai_total) = queryROW($sql);
		$_SESSION['fecdata']['total_calculations']['colA_11ai_itemized'] = $schA_11ai_total;



		// TOTAL SCHEDULE B - 21b
		list($schB21B_total) = queryROW("SELECT SUM(amount) FROM `".$this->schedule_table."` ".
						" WHERE pac_id='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
						" AND filing_id='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
						" AND `form_type` LIKE 'SB21B' "
						);
		$_SESSION['fecdata']['total_calculations']['colA_21b_other_fed_expenditures'] = $schB21B_total;












	// TOTAL ITEMIZED DONORS (ANY DONOR WHO HAS GIVEN MORE THAN 200 IN CURRENT YEAR
		$ytd = $_SESSION['fecdata']['current_file']['colB_year_for_above'].'-01-01';
		$sql = "SELECT * FROM (".
  					"SELECT SUM(amount) as total_amount, unique_id FROM pacs_donations ".
					"WHERE `date` between '".$ytd."' AND '".$_SESSION['fecdata']['current_file']['end_date']."' ".
					" AND `pac_id`='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
					" AND `filing_id`='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
				  	"GROUP BY `unique_id` ".
				  	"ORDER BY `total_amount` DESC) AS a "
				  	."WHERE total_amount >= 200";

		$res = query($sql);

		$itemized_donors = 0;
		$ignore_sql = " AND `unique_id` NOT IN (";
		$x=0;
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			//list($customer_total, $unique_id) = $row;

			$ignore_sql .= ($x++ > 0)?",":'';

			$ignore_sql .= "'".$row['unique_id']."'";


			// GET THE CURRENT QUARTERS LINE ITEMS FOR THE CUSTOMERS WHO ARE OVER 200$
			list($cust_quarter_total) = queryROW(
											"SELECT SUM(amount) as total_amount FROM pacs_donations ".
											"WHERE `date` between '".$_SESSION['fecdata']['current_file']['start_date']."' AND '".$_SESSION['fecdata']['current_file']['end_date']."' ".
											" AND `pac_id`='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
											" AND `filing_id`='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
											" AND `unique_id`='".$row['unique_id']."'"
										);
			$itemized_donors += $cust_quarter_total;
		}

		// ITEMIZED DONORS FOR THE CURRENT QUARTER
		$_SESSION['fecdata']['total_calculations']['donors_total_itemized'] = $itemized_donors;

		if($x > 0)$ignore_sql .= ") ";

		$sql = "SELECT SUM(amount) as total_amount FROM pacs_donations ".
			"WHERE `date` between '".$_SESSION['fecdata']['current_file']['start_date']."' AND '".$_SESSION['fecdata']['current_file']['end_date']."' ".
			" AND `pac_id`='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
			" AND `filing_id`='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
			$ignore_sql;

//echo $sql;

		list($unitemized_donors) = queryROW($sql);
		$_SESSION['fecdata']['total_calculations']['donors_total_unitemized'] = $unitemized_donors;


		$_SESSION['fecdata']['total_calculations']['donors_total'] = $_SESSION['fecdata']['total_calculations']['donors_total_itemized'] + $_SESSION['fecdata']['total_calculations']['donors_total_unitemized'];


		$_SESSION['fecdata']['total_calculations']['colA_11aiii_total'] = $_SESSION['fecdata']['total_calculations']['colA_11ai_itemized'] +$_SESSION['fecdata']['total_calculations']['donors_total_unitemized'];




// CALCULATE EXPENSES



		// TOTAL UNIQUE COMPANIES OVER 200
		$ytd = $_SESSION['fecdata']['current_file']['colB_year_for_above'].'-01-01';
		$sql = "SELECT * FROM (".
  					"SELECT SUM(amount) as total_amount, unique_id FROM pacs_expenses ".
					"WHERE `date` between '".$ytd."' AND '".$_SESSION['fecdata']['current_file']['end_date']."' ".
					" AND `pac_id`='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
					" AND `filing_id`='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
				  	"GROUP BY `unique_id` ".
				  	"ORDER BY `total_amount` DESC) AS a "
				  	."WHERE total_amount >= 200";



		$res = query($sql);

		$itemized_expenses = 0;
		$ignore_sql = " AND `unique_id` NOT IN (";
		$x=0;
		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			//list($customer_total, $unique_id) = $row;

			$ignore_sql .= ($x++ > 0)?",":'';

			$ignore_sql .= "'".$row['unique_id']."'";


			// GET THE CURRENT QUARTERS LINE ITEMS FOR THE CUSTOMERS WHO ARE OVER 200$
			list($cust_quarter_total) = queryROW(
											"SELECT SUM(amount) as total_amount FROM pacs_expenses ".
											"WHERE `date` between '".$_SESSION['fecdata']['current_file']['start_date']."' AND '".$_SESSION['fecdata']['current_file']['end_date']."' ".
											" AND `pac_id`='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
											" AND `filing_id`='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
											" AND `unique_id`='".$row['unique_id']."'"
										);
			$itemized_expenses += $cust_quarter_total;
		}

		// ITEMIZED DONORS FOR THE CURRENT QUARTER
		$_SESSION['fecdata']['total_calculations']['expenses_total_itemized'] = $itemized_expenses;

		if($x > 0)	$ignore_sql .= ") ";
		else		$ignore_sql = '';


		$sql = "SELECT SUM(amount) as total_amount FROM pacs_expenses ".
			"WHERE `date` between '".$_SESSION['fecdata']['current_file']['start_date']."' AND '".$_SESSION['fecdata']['current_file']['end_date']."' ".
			" AND `pac_id`='".intval($_SESSION['fecdata']['current_pac']['id'])."' ".
			" AND `filing_id`='".intval($_SESSION['fecdata']['current_file']['id'])."' ".
			$ignore_sql;

//echo $sql;

		list($unitemized_expenses) = queryROW($sql);

		$_SESSION['fecdata']['total_calculations']['expenses_total_unitemized'] = $unitemized_expenses;



		$_SESSION['fecdata']['total_calculations']['expenses_total'] = $_SESSION['fecdata']['total_calculations']['expenses_total_itemized'] + $_SESSION['fecdata']['total_calculations']['expenses_total_unitemized'];




	//	colA_11aiii_total


//echo $sql."WHERE total_amount >= 200";

//		list($itemized_donors) = queryROW($sql."WHERE total_amount >= 200");
//		$_SESSION['fecdata']['total_calculations']['donors_total_itemized'] = $itemized_donors;
//
//		// TOTAL UNITEMIZED DONORS
//		list($unitemized_donors) = queryROW($sql."WHERE total_amount < 200");
//		$_SESSION['fecdata']['total_calculations']['donors_total_unitemized'] = $unitemized_donors;
//
//



		$_SESSION['fecdata']['total_calculations']['colA_18c_trans_nonfed_total'] = 	$_SESSION['fecdata']['current_file']['colA_18a_trans_nonfed_h3'] +
																						$_SESSION['fecdata']['current_file']['colA_18b_trans_nonfed_h5'];


		//colA_19_total_receipts = 11d+12+13+14+15+16+17+18c
		$_SESSION['fecdata']['total_calculations']['colA_19_total_receipts'] = $_SESSION['fecdata']['current_file']['colA_11d_total_contributions'] +
																				$_SESSION['fecdata']['current_file']['colA_12_transfers'] +
																				$_SESSION['fecdata']['current_file']['colA_13_loans_received'] +
																				$_SESSION['fecdata']['current_file']['colA_14_loan_repayments_received'] +
																				$_SESSION['fecdata']['current_file']['colA_15_offsets_refunds'] +
																				$_SESSION['fecdata']['current_file']['colA_16_fed_contrib_refund'] +
																				$_SESSION['fecdata']['current_file']['colA_17_other_fed_receipts'] +
																				$_SESSION['fecdata']['current_file']['colA_18c_trans_nonfed_total'];


		//$_SESSION['fecdata']['total_calculations']['colA_20_total_fed_receipts'] = $_SESSION['fecdata']['total_calculations']['colA_19_total_receipts'] - $_SESSION['fecdata']['current_file']['colA_18c_trans_nonfed_total'];
		$_SESSION['fecdata']['total_calculations']['colA_20_total_fed_receipts'] = $_SESSION['fecdata']['current_file']['colA_19_total_receipts'] - $_SESSION['fecdata']['current_file']['colA_18c_trans_nonfed_total'];



		$_SESSION['fecdata']['total_calculations']['colA_6c_total_receipts'] = $_SESSION['fecdata']['current_file']['colA_19_total_receipts'];//$_SESSION['fecdata']['total_calculations']['colA_19_total_receipts'];



		$_SESSION['fecdata']['total_calculations']['colA_6d_subtotal'] = $_SESSION['fecdata']['current_file']['colA_6b_cash_on_hand'] +
																		$_SESSION['fecdata']['current_file']['colA_6c_total_receipts'];
																		//$_SESSION['fecdata']['total_calculations']['colA_6c_total_receipts'];




		$_SESSION['fecdata']['total_calculations']['colA_11d_total_contributions'] = $_SESSION['fecdata']['current_file']['colA_11aiii_total'] +
																						$_SESSION['fecdata']['current_file']['colA_11b_pol_party_committees'] +
																						$_SESSION['fecdata']['current_file']['colA_11c_other_pacs'];




		$_SESSION['fecdata']['total_calculations']['colA_21c_total_operating_expenditures'] = $_SESSION['fecdata']['current_file']['colA_21ai_fed_share'] +
																									$_SESSION['fecdata']['current_file']['colA_21aii_nonfed_share'] +
																									$_SESSION['fecdata']['current_file']['colA_21b_other_fed_expenditures'];


		$_SESSION['fecdata']['total_calculations']['colA_28d_total_contrib_refunds'] = $_SESSION['fecdata']['current_file']['colA_28a_individuals'] +
																									$_SESSION['fecdata']['current_file']['colA_28b_pol_party_committees'] +
																									$_SESSION['fecdata']['current_file']['colA_28c_other_pacs'];

		$_SESSION['fecdata']['total_calculations']['colA_31_total_dibursements'] = $_SESSION['fecdata']['current_file']['colA_21c_total_operating_expenditures'] +
																($_SESSION['fecdata']['current_file']['colA_22_trans_affiliated_partys'] - $_SESSION['fecdata']['current_file']['colA_27_loans_made']) +
																$_SESSION['fecdata']['current_file']['colA_28d_total_contrib_refunds'] + $_SESSION['fecdata']['current_file']['colA_29_other_disbursements'];


		$_SESSION['fecdata']['total_calculations']['colA_32_total_fed_disbursements'] = $_SESSION['fecdata']['current_file']['colA_31_total_dibursements'] -
																							($_SESSION['fecdata']['current_file']['colA_21aii_nonfed_share'] + $_SESSION['fecdata']['current_file']['colA_30aii_shared_fed_activity_nonfed']);

		$_SESSION['fecdata']['total_calculations']['colA_33_total_contributions'] =  $_SESSION['fecdata']['current_file']['colA_11d_total_contributions'];

		$_SESSION['fecdata']['total_calculations']['colA_34_total_contribution_refunds'] =  $_SESSION['fecdata']['current_file']['colA_28d_total_contrib_refunds'];

		$_SESSION['fecdata']['total_calculations']['colA_35_net_contributions'] = $_SESSION['fecdata']['current_file']['colA_11d_total_contributions'] - $_SESSION['fecdata']['current_file']['colA_28d_total_contrib_refunds'];

		$_SESSION['fecdata']['total_calculations']['colA_36_total_fed_op_expenditures'] = $_SESSION['fecdata']['current_file']['colA_21ai_fed_share'] + $_SESSION['fecdata']['current_file']['colA_21b_other_fed_expenditures'];

		$_SESSION['fecdata']['total_calculations']['colA_37_offset_to_op_expenditures'] = $_SESSION['fecdata']['current_file']['colA_15_offsets_refunds'];


		// colA_38_net_op_expenditures = 36 - 37 or (21ai + 21b - 15)
		$_SESSION['fecdata']['total_calculations']['colA_38_net_op_expenditures'] = ($_SESSION['fecdata']['current_file']['colA_21ai_fed_share'] +
																					$_SESSION['fecdata']['current_file']['colA_21b_other_fed_expenditures']) -
																					$_SESSION['fecdata']['current_file']['colA_15_offsets_refunds'];



		$_SESSION['fecdata']['total_calculations']['colA_8_cash_on_hand_close'] = $_SESSION['fecdata']['current_file']['colA_6d_subtotal'] - $_SESSION['fecdata']['current_file']['colA_31_total_dibursements'];



//echo nl2br(print_r($_SESSION['fecdata']['total_calculations'],1));

//		aedit($_SESSION['fecdata']['current_file']['id'], $dat, $this->table);

		$this->reloadCurrentFile();

	} // END updateFilingTotals()



	function reloadCurrentFile(){
		// RELOAD SESSION DATA
		$_SESSION['fecdata']['current_file'] = $this->loadFiling($_SESSION['fecdata']['current_pac']['id'], $_SESSION['fecdata']['current_file']['id']);
	}

}