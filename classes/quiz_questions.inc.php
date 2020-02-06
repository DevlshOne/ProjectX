<?	/***************************************************************
 *	Quiz Questions
 *	Written By: Jonathan Will
 ***************************************************************/

    $_SESSION['quiz_questions'] = new QuizQuestions;


    class QuizQuestions{

        var $table	= 'quiz_questions';			## Classes main table to operate on
        var $orderby	= 'id';		## Default Order field
        var $orderdir	= 'DESC';	## Default order direction


        ## Page  Configuration
        var $pagesize	= 20;	## Adjusts how many items will appear on each page
        var $index	= 0;		## You dont really want to mess with this variable. Index is adjusted by code, to change the pages

        var $index_name = 'question_list';	## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
        var $frm_name = 'questionnextfrm';

        var $order_prepend = 'question_';				## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING

        function QuizQuestions(){


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

            if(!checkAccess('quiz_questions')){


                accessDenied("Quiz Questions");

                return;

            }else{
                if(isset($_REQUEST['add_question'])){

                    $this->makeAdd($_REQUEST['add_question']);

                }else if(isset($_REQUEST['play_quiz_file'])){

                	$this->PlayQuizFile($_REQUEST['play_quiz_file']);

                }else{
                    $this->listEntrys();
                }

            }

        }
        function listEntrys(){
            ?>
            <script>
                var question_delmsg = 'Are you sure you want to delete this Quiz question?';
                var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
                var <?=$this->order_prepend?>orderdir= "<?=$this->orderdir?>";
                var <?=$this->index_name?> = 0;
                var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
                var QuestionsTableFormat = [
                    ['[get:quiz_name:quiz_id]','align_left'],
                    ['question','align_left'],
                    ['answer','align_center'],
                    ['file','align_center'],
                    ['duration','align_center'],
                    ['[delete]','align_center']
                ];
                /**
                 * Build the URL for AJAX to hit, to build the list
                 */
                function getQuestionsURL(){
                    var frm = getEl('<?=$this->frm_name?>');
                    var <?=$this->order_prepend?>pagesize = $('#<?=$this->order_prepend?>pagesizeDD').val();
                    return 'api/api.php'+
                        "?get=questions&"+
                        "mode=xml&"+
                        's_quiz_id='+escape(frm.s_quiz_id.value)+"&"+
                        's_question='+escape(frm.s_question.value)+"&"+
                        's_answer='+escape(frm.s_answer.value)+"&"+
                        's_filename='+escape(frm.s_filename.value)+"&"+
                        "index="+(<?=$this->index_name?> * <?=$this->order_prepend?>pagesize)+"&pagesize="+<?=$this->order_prepend?>pagesize+"&"+
                    "orderby="+<?=$this->order_prepend?>orderby+"&orderdir="+<?=$this->order_prepend?>orderdir;
                }
                var questions_loading_flag = false;
                /**
                 * Load the data - make the ajax call, callback to the parse function
                 */
                function loadQuestions(){
                    // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                    var val = null;
                    eval('val = questions_loading_flag');
                    // CHECK IF WE ARE ALREADY LOADING THIS DATA
                    if(val == true){
                        //console.log("Questions ALREADY LOADING (BYPASSED) \n");
                        return;
                    }else{
                        eval('questions_loading_flag = true');
                    }
                    <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());
                    var question_pagesize = 20;
                    loadAjaxData(getQuestionsURL(),'parseQuestions');
                }
                /**
                 * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
                 */
                var <?=$this->order_prepend?>totalcount = 0;
                function parseQuestions(xmldoc){
                    <?=$this->order_prepend?>totalcount = parseXMLData('question',QuestionsTableFormat,xmldoc);
                    // ACTIVATE PAGE SYSTEM!
                    if(<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize){
                        makePageSystem('questions',
                            '<?=$this->index_name?>',
                            <?=$this->order_prepend?>totalcount,
                            <?=$this->index_name?>,
                            <?=$this->order_prepend?>pagesize,
                            'loadQuestions()'
                        );
                    }else{
                        hidePageSystem('questions');
                    }
                    eval('questions_loading_flag = false');
                }
                function handleQuestionListClick(id){
                    displayAddQuestionDialog(id);
                }
                function displayAddQuestionDialog(id){
                    var objname = 'dialog-modal-add-question';
                    if(id > 0){
                        $('#'+objname).dialog( "option", "title", 'Editing Question' );
                    }else{
                        $('#'+objname).dialog( "option", "title", 'Adding new Question' );
                    }
                    $('#'+objname).dialog("open");
                    $('#'+objname).html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>').load("index.php?area=quiz_questions&add_question="+id+"&printable=1&no_script=1");
                }

                function resetQuestionForm(frm){
                    frm.s_quiz_id.value = '';
                    frm.s_question.value = '';
                    frm.s_answer.value = '';
                    frm.s_filename.value='';
                }
                var questionsrchtog = false;
                function toggleQuestionSearch(){
                    questionsrchtog = !questionsrchtog;
                    ieDisplay('question_search_table', questionsrchtog);
                }
            </script>
            <!-- ****START**** THIS AREA REPLACES THE OLD TABLES WITH THE NEW ONEUI INTERFACE BASED ON BOOTSTRAP -->
            <div class="block">
                <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" onsubmit="loadQuestions();return false;">
                    <div class="block-header bg-primary-light">
                        <h4 class="block-title">Quiz Questions</h4>
                        <button type="button" value="Add" title="Add Questions" class="btn btn-sm btn-primary" onclick="displayAddQuestionDialog(0)">Add</button>
                        <button type="button" value="Search" title="Toggle Search" class="btn btn-sm btn-primary" onclick="toggleQuestionSearch();">Toggle Search</button>
                        <div id="questions_prev_td" class="page_system_prev"></div>
                        <div id="questions_page_td" class="page_system_page"></div>
                        <div id="questions_next_td" class="page_system_next"></div>
                        <select title="Rows Per Page" class="custom-select-sm" name="<?=$this->order_prepend?>pagesize" id="<?=$this->order_prepend?>pagesizeDD" onchange="<?=$this->index_name?>=0;loadQuestions(); return false;">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="bg-info-light nod" id="question_search_table">
                        <div class="input-group input-group-sm">
                            <input type="hidden" name="searching_question"/>
                            <?=$this->makeDD('s_quiz_id',$_REQUEST['s_quiz_id'],'',"",0, "[All]");?>
                            <input type="text" class="form-control" placeholder="Question.." name="s_question" value="<?=htmlentities($_REQUEST['s_question'])?>" />
                            <input type="text" class="form-control" placeholder="Answer.." name="s_answer" value="<?= htmlentities($_REQUEST['s_answer']) ?>"/>
                            <input type="text" class="form-control" placeholder="Filename.." name="s_filename" value="<?= htmlentities($_REQUEST['s_filename']) ?>"/>
                            <button type="button" value="Search" title="Search Quiz Questions" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadQuestions();return false;">Search</button>
                            <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetQuestionForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadQuestions();return false;">Reset</button>
                        </div>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-striped" id="question_table">
                            <caption id="current_time_span" class="small text-right">Server Time: <?=date("g:ia m/d/Y T")?></caption>
                            <tr>
                                <th class="row2 text-left"><?=$this->getOrderLink('quiz_id')?>Quiz</a></th>
                                <th class="row2 text-left"><?=$this->getOrderLink('question')?>Question</a></th>
                                <th class="row2 text-center"><?=$this->getOrderLink('answer')?>Answer</a></th>
                                <th class="row2 text-center"><?=$this->getOrderLink('filename')?>Filename</a></th>
                                <th class="row2 text-center"><?=$this->getOrderLink('duration')?>Duration</a></th>
                                <th class="row2 text-center">&nbsp;</th>
                            </tr>
                        </table>
                    </div>
                </form>
            </div>
            <!-- ****END**** THIS AREA REPLACES THE OLD TABLES WITH THE NEW ONEUI INTERFACE BASED ON BOOTSTRAP -->
            <div id="dialog-modal-add-question" title="Adding new Question" class="nod"></div>
            <script>
                $("#dialog-modal-add-question").dialog({
                    autoOpen: false,
                    width: 'auto',
                    height: 250,
                    modal: false,
                    draggable:true,
                    resizable: false,
                    position: {my: 'center', at: 'center', of: '#main-container'},
                });
                loadQuestions();
                $('#s_quiz_id').attr('title', 'Select Quiz ID');
            </script>
            <?

        }


        function makeAdd($id){

            $id=intval($id);


            if($id){

                $row = $_SESSION['dbapi']->quiz_questions->getByID($id);


            }

            ?><script>

            	// Used by dialog box Cancel button
				function HideAddQuestion(){

					var objname = 'dialog-modal-add-question';

					$('#'+objname).dialog("close");

				}



                function validateQuestionField(name,value,frm){

                    //alert(name+","+value);


                    switch(name){
                        default:

                            // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                            return true;
                            break;

                        case 'filename':


                            if(!value)return false;

                            return true;


                            break;

                    }
                    return true;
                }



                function checkQuestionFrm(frm){


                    var params = getFormValues(frm,'validateQuestionField');


                    // FORM VALIDATION FAILED!
                    // param[0] == field name
                    // param[1] == field value
                    if(typeof params == "object"){

                        switch(params[0]){
                            default:

                                alert("Error submitting form. Check your values");

                                break;

                            case 'filename':

                                alert("Please enter the filename for this name.");
                                eval('try{frm.'+params[0]+'.select();}catch(e){}');
                                break;

                        }

                        // SUCCESS - POST AJAX TO SERVER
                    }else{


                        //alert("Form validated, posting");

                        $.ajax({
                            type: "POST",
                            cache: false,
                            url: 'api/api.php?get=quiz_questions&mode=xml&action=edit',
                            data: params,
                            error: function(){
                                alert("Error saving user form. Please contact an admin.");
                            },
                            success: function(msg){

//alert(msg);

                                var result = handleEditXML(msg);
                                var res = result['result'];

                                if(res <= 0){

                                    alert(result['message']);

                                    return;

                                }


                                loadQuestions();


                                displayAddQuestionDialog(res);

                                alert(result['message']);

                            }


                        });

                    }

                    return false;

                }


    			function playAudio(id){


    				//$('#media_player').dialog("open");

    				$('#quiz_media_player').children().filter("audio").each(function(){
    					this.pause(); // can't hurt
    					delete(this); // @sparkey reports that this did the trick!
    					$(this).remove(); // not sure if this works after null assignment
    				});
    				$('#quiz_media_player').empty();

    				$('#quiz_media_player').load("index.php?area=quiz_questions&play_quiz_file="+id+"&printable=1&no_script=1");
    				// $('#media_player').load("test.php");

    				// REMOVE AND READD TEH CLOSE BINDING, TO STOP THE AUDIO
    				$('#quiz_media_player').unbind("dialogclose");
    				$('#quiz_media_player').bind('dialogclose', function(event) {

    					hideAudio();

    				});


    			}

    			function hideAudio(){
    				$('#quiz_media_player').children().filter("audio").each(function(){
    					this.pause();
    					delete(this);
    					$(this).remove();

    				});

    				$('#quiz_media_player').empty();

    			}



                // SET TITLEBAR
                $('#dialog-modal-add-question').dialog( "option", "title", '<?=($id)?'Editing Question #'.$id.' - '.htmlentities($row['question']):'Adding new Question'?>' );



            </script>
            <center><div id="quiz_media_player" title="Playing Quiz File"></center>
            <form method="POST" action="<?=stripurl('')?>" autocomplete="off" onsubmit="checkQuestionFrm(this); return false">
                <input type="hidden" id="adding_question" name="adding_question" value="<?=$id?>" >


                <table border="0" align="center">
                    <tr>
                        <th align="left" height="30">Quiz:</th>
                        <td><?

                                echo $this->makeDD('quiz_id',$row['quiz_id'],'',"",0, 0);

                            ?></td>
                    </tr>
                    <tr>
                        <th align="left" height="30">Question:</th>
                        <td><input name="question" type="text" size="50" value="<?=htmlentities($row['question'])?>"></td>
                    </tr>
                    <tr>
                        <th align="left" height="30">Answer:</th>
                        <td><input name="answer" type="text" size="5" value="<?=htmlentities($row['answer'])?>"></td>
                    </tr>
                    <tr>
                        <th align="left" height="30">Filename:</th>
                        <td><input name="file" type="text" size="50" value="<?=htmlentities($row['file'])?>"></td>
                    </tr>
                    <tr>
						<th colspan="2" align="center"><input type="submit" value="Save Changes">
						<input type="button" value="Cancel" onclick="hideAudio(); HideAddQuestion(); return false;">
						<input type="button" value="Listen" onclick="playAudio('<?=$row['id']?>')"></th>
					</tr>
		</form>
		</table><?


        }


        function PlayQuizFile($id){

        	# Play audio file function - it will display audio player with play_audio_file.php as source

        	$id=intval($id);

        	if($id){

        		$row = $_SESSION['dbapi']->quiz_questions->getByID($id);

        	}

        	?>
		<audio id="audio_obj" autoplay controls>
			<source src="play_audio_file.php?file=<?=htmlentities($row['file'])?>" type="audio/wav" />
			Your browser does not support the audio element.
		</audio><br>
		<a href="#" onclick="parent.hideAudio();return false">[Hide Player]</a>

		<script>
			parent.applyUniformity();
		</script><?

	}



        function makeDD($name,$sel,$class,$onchange,$size, $blank_entry=1){

            $names		= 'name';	## or Array('field1','field2')
            $value		= 'id';
            $seperator	= '';		## If $names == Array, this will be the seperator between fields


            $fieldstring='';
            if(is_array($names)){
                $x=0;
                foreach($names as $name){
                    $fieldstring.= $name.',';
                }
            }else{	$fieldstring.=$names.',';}
            $fieldstring	.= $value;

            $sql = "SELECT $fieldstring FROM quiz WHERE 1 ";
            $DD = new genericDD($sql,$names,$value,$seperator);
            return $DD->makeDD($name,$sel,$class,$blank_entry,$onchange,$size);
        }

        function getOrderLink($field){

            $var = '<a href="#" onclick="setOrder(\''.addslashes($this->order_prepend).'\',\''.addslashes($field).'\',';

            $var .= "((".$this->order_prepend."orderdir == 'DESC')?'ASC':'DESC')";

            $var.= ");loadQuestions();return false;\">";

            return $var;
        }
    }
