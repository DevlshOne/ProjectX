<?	/***************************************************************
	 *	Names - Handles list/search/import names
	 *	Written By: Jonathan Will
	 ***************************************************************/

$_SESSION['quiz_results'] = new QuizResults;


class QuizResults{

	var $table	= 'quiz_results';			## Classes main table to operate on
	var $orderby	= 'id';		## Default Order field
	var $orderdir	= 'DESC';	## Default order direction


	## Page  Configuration
	var $pagesize	= 20;	## Adjusts how many items will appear on each page
	var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

	var $index_name = 'quiz_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
	var $frm_name = 'quiznextfrm';

	var $order_prepend = 'quiz_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

	function QuizResults(){


		## REQURES DB CONNECTION!



		$this->handlePOST();
	}


	function handlePOST(){

		// THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
		// SEE api/names.api.php FOR POST HANDLING!
		// <3 <3 -Jon

	}

	function handleFLOW(){
		# Handle flow, based on query string

		if(!checkAccess('quiz_results')){


			accessDenied("Quiz Results");

			return;

		}else{

			$this->listEntrys();

		}

	}






	function listEntrys(){


		?><script>

			var quiz_delmsg = 'Are you sure you want to delete this result?';

			var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
			var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";


			var <?=$this->index_name?> = 0;
			var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;

			var QuizsTableFormat = [

				['[time:time_started]','align_left'],

				['[get:quiz_name:quiz_id]','align_left'],
				['username','align_center'],

				['[duration:time_started:time_ended]','align_center'],


				['response_time','align_center'],
				['hide_question','align_center'],

				['[percent:accuracy]','align_center'],
				['[percent:speed]','align_center'],

				['[delete]','align_center']
			];

			/**
			* Build the URL for AJAX to hit, to build the list
			*/
			function getQuizsURL(){

				var frm = getEl('<?=$this->frm_name?>');

				return 'api/api.php'+
								"?get=quiz_results&"+
								"mode=xml&"+

								's_quiz_id='+escape(frm.s_quiz_id.value)+"&"+
								's_username='+escape(frm.s_username.value)+"&"+

								's_response_time='+escape(frm.s_response_time.value)+"&"+
								's_hide_question='+escape(frm.s_hide_question.value)+"&"+

								's_date_month='+escape(frm.stime_month.value)+"&"+'s_date_day='+escape(frm.stime_day.value)+"&"+'s_date_year='+escape(frm.stime_year.value)+"&"+
								's_date2_month='+escape(frm.etime_month.value)+"&"+'s_date2_day='+escape(frm.etime_day.value)+"&"+'s_date2_year='+escape(frm.etime_year.value)+"&"+
								's_date_mode='+escape(frm.date_mode.value)+"&"+

								"index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
								"orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
			}


			var quizs_loading_flag = false;

			/**
			* Load the name data - make the ajax call, callback to the parse function
			*/
			function loadQuizs(){

				// ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
				var val = null;
				eval('val = quizs_loading_flag');


				// CHECK IF WE ARE ALREADY LOADING THIS DATA
				if(val == true){

					//console.log("QUIZ RESULTS ALREADY LOADING (BYPASSED) \n");
					return;
				}else{

					eval('quizs_loading_flag = true');
				}

				<?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());

				loadAjaxData(getQuizsURL(),'parseQuizs');

			}


			/**
			* CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
			*/
			var <?=$this->order_prepend?>totalcount = 0;
			function parseQuizs(xmldoc){

				<?=$this->order_prepend?>totalcount = parseXMLData('quiz',QuizsTableFormat,xmldoc);


				// ACTIVATE PAGE SYSTEM!
				if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){


					makePageSystem('quizs',
									'<?=$this->index_name?>',
									<?=$this->order_prepend?>totalcount,
									<?=$this->index_name?>,
									<?=$this->order_prepend?>pagesize,
									'loadQuizs()'
								);

				}else{

					hidePageSystem('quizs');

				}

				eval('quizs_loading_flag = false');
			}


			function handleQuizListClick(id){

				//displayAddQuizDialog(id);

			}


//			function displayAddQuizDialog(id){
//
//				var objname = 'dialog-modal-add-name';
//
//
//				if(id > 0){
//					$('#'+objname).dialog( "option", "title", 'Editing name' );
//				}else{
//					$('#'+objname).dialog( "option", "title", 'Adding new Name' );
//				}
//
//
//
//				$('#'+objname).dialog("open");
//
//				$('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
//
//				$('#'+objname).load("index.php?area=names&add_name="+id+"&printable=1&no_script=1");
//
//				$('#'+objname).dialog('option', 'position', 'center');
//			}

			function resetQuizForm(frm){

				frm.s_quiz_id.value = '';
				frm.s_username.value = '';

				frm.s_response_time.value='';
				frm.s_hide_question.value='';

			}


			var quizsrchtog = true;

			function toggleQuizSearch(){
				quizsrchtog = !quizsrchtog;
				ieDisplay('quiz_search_table', quizsrchtog);
			}

			function toggleDateMode(way){

				if(way == 'daterange'){
					$('#nodate_span').hide();
					$('#date1_span').show();

					// SHOW EXTRA DATE FIELD
					$('#date2_span').show();

				}else if(way == 'any'){

					$('#nodate_span').show();
					$('#date1_span').hide();
					$('#date2_span').hide();

				}else{
					$('#nodate_span').hide();

					$('#date1_span').show();

					// HIDE IT
					$('#date2_span').hide();
				}

			}

		</script>
		<div id="dialog-modal-view-quiz" title="Viewing Quiz results" class="nod">
		<?

		?>
		</div><?



		?><form name="<?=$this->frm_name?>" id="<?=$this->frm_name?>" method="POST" action="<?=$_SERVER['REQUEST_URI']?>" onsubmit="loadQuizs();return false">
			<input type="hidden" name="searching_quiz">
		<?/**<table border="0" width="100%" cellspacing="0" class="ui-widget" class="lb">**/?>

		<table border="0" width="100%" class="lb" cellspacing="0">
		<tr>
			<td height="40" class="pad_left ui-widget-header">

				<table border="0" width="100%" >
				<tr>
					<td width="500">
						Quiz Results

						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" value="Search" onclick="toggleQuizSearch()">
					</td>

					<td width="150" align="center">PAGE SIZE: <select name="<?=$this->order_prepend?>pagesizeDD" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0; loadQuizs();return false">
						<option value="20">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="500">500</option>
					</select></td>

					<td align="right"><?
						/** PAGE SYSTEM CELLS -- INJECTED INTO, BY JAVASCRIPT AFTER AJAX CALL **/?>
						<table border="0" cellpadding="0" cellspacing="0" class="page_system_container">
						<tr>
							<td id="quizs_prev_td" class="page_system_prev"></td>
							<td id="quizs_page_td" class="page_system_page"></td>
							<td id="quizs_next_td" class="page_system_next"></td>
						</tr>
						</table>

					</td>
				</tr>
				</table>

			</td>

		</tr>

		<tr>
			<td colspan="2"><table border="0" width="100%" id="quiz_search_table">
			<tr>
				<td rowspan="2"><font size="+1">SEARCH</font></td>
				<th class="row2">Quiz</th>
				<th class="row2">Username</th>

				<th class="row2">Response Time</th>
				<th class="row2">Hide Question</th>
				<th class="row2"><select name="s_date_mode" id="date_mode" onchange="toggleDateMode(this.value);">

						<option value="any"<?=($_REQUEST['s_date_mode']=='any')?' SELECTED ':''?>>ANY</option>
						<option value="date">Date</option>
						<option value="daterange"<?=($_REQUEST['s_date_mode']=='daterange')?' SELECTED ':''?>>Date Range</option>

				</select></th>

				<td><input type="submit" value="Search" name="the_Search_button"></td>
			</tr>
			<tr>
				<td align="center"><?

					echo $this->makeQuizDD('s_quiz_id', $_REQUEST['s_quiz_id'], 1);

				?></td>
				<td align="center"><input type="text" name="s_username" size="20" value="<?=htmlentities($_REQUEST['s_username'])?>"></td>
				<td align="center">

					<select name="s_response_time">
						<option value="">[All]</option>

						<option value="250">250ms (1/4 second)</option>
						<option value="500">500ms (1/2 second)</option>
						<option value="750">750ms (3/4 second)</option>
						<option value="1000">1 Second</option>
						<option value="2000">2 Seconds</option>
						<option value="3000">3 Seconds</option>
						<option value="4000">4 Seconds</option>
						<option value="5000">5 Seconds</option>
					</select>
				</td>
				<td align="center">
					<select name="s_hide_question">

						<option value="">[All]</option>
						<option value="true">True</option>
						<option value="false">False</option>

					</select>
				</td>
				<td nowrap><?

					?><span id="date1_span" class="nod"><?
						echo makeTimebar("stime_", 1, null,false,time());
					?></span><?

					?><span id="date2_span" class="nod"><br /><?
						echo makeTimebar("etime_",1,null,false,time());
					?></span>

					<span id="nodate_span">
						ANY/ALL DATES
					</span>
				</td>

				<td><input type="button" value="Reset" onclick="resetQuizForm(this.form);resetPageSystem('<?=$this->index_name?>');loadQuizs();"></td>
			</tr>
			</table></td>
		</tr></form>
		<tr>
			<td colspan="2"><table border="0" width="100%" id="quiz_table">
			<tr>
				<th class="row2" align="left"><?=$this->getOrderLink('time_started')?>Time</a></th>
				<th class="row2" align="left"><?=$this->getOrderLink('quiz_id')?>Quiz</a></th>
				<th class="row2"><?=$this->getOrderLink('username')?>Username</a></th>
				<th class="row2">Duration</th>
				<th class="row2" title="The maximum time to wait for them to answer, before its considered failed">Response Time</th>
				<th class="row2" title="If they chose to hide the question">Hide Ques.</th>
				<th class="row2">Accuracy</th>
				<th class="row2">Speed</th>
				<th class="row2">&nbsp;</th>
			</tr><?

			?></table></td>
		</tr></table>

		<script>

//			$("#dialog-modal-view-name").dialog({
//				autoOpen: false,
//				width: 500,
//				height: 200,
//				modal: false,
//				draggable:true,
//				resizable: false
//			});

			loadQuizs();

		</script><?

	}



	function makeQuizDD($name, $sel, $blank_field=0){

		$out = '<select name="'.$name.'" id="'.$name.'">';

		$res = query("SELECT * FROM quiz ORDER BY name ASC", 1);

		if($blank_field){

			$out .= '<option value="">[ALL]</option>';
		}

		while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){

			$out .= '<option value="'.$row['id'].'"';

			$out .= ($row['id'] == $sel)?' SELECTED ':'';

			$out .= '>'.$row['name'].'</option>';
		}

		$out .= '</select>';

		return $out;
	}

	function getOrderLink($field){

		$var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

		$var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

		$var.= ");loadQuizs();return false;\">";

		return $var;
	}
}
