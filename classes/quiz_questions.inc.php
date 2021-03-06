<? /***************************************************************
 *    Quiz Questions
 *    Written By: Jonathan Will
 ***************************************************************/

$_SESSION['quiz_questions'] = new QuizQuestions;


class QuizQuestions
{
    var $table = 'quiz_questions';            ## Classes main table to operate on
    var $orderby = 'id';        ## Default Order field
    var $orderdir = 'DESC';    ## Default order direction
    ## Page  Configuration
    var $pagesize = 20;    ## Adjusts how many items will appear on each page
    var $index = 0;        ## You dont really want to mess with this variable. Index is adjusted by code, to change the pages
    var $index_name = 'question_list';    ## THIS IS FOR THE NEXT PAGE SYSTEM; jsNextPage($total,$obj, $jsfunc) is located in the /jsfunc.php file
    var $frm_name = 'questionnextfrm';
    var $order_prepend = 'question_';                ## THIS IS USED TO KEEP THE ORDER URLS FROM DIFFERENT AREAS FROM COLLIDING
    function QuizQuestions()
    {
        ## REQURES DB CONNECTION!
        $this->handlePOST();
    }

    function handlePOST()
    {
        // THIS SHIT IS MOTHERFUCKIGN AJAXED TO THE TEETH
        // SEE api/names.api.php FOR POST HANDLING!
        // <3 <3 -Jon
    }

    function handleFLOW()
    {
        # Handle flow, based on query string
        if (!checkAccess('quiz_questions')) {
            accessDenied("Quiz Questions");
            return;
        } else {
            if (isset($_REQUEST['import_questions'])) {
                $this->importQuizQuestions($_REQUEST['f_quiz_id'], $_FILES['questions_file']);
                jsRedirect(stripurl(array('quiz_questions', 'no_script')));
            }
            if (isset($_REQUEST['add_question'])) {
                $this->makeAdd($_REQUEST['add_question']);
            } else if (isset($_REQUEST['play_quiz_file'])) {
                $this->PlayQuizFile($_REQUEST['play_quiz_file']);
            } else {
                $this->listEntrys();
            }
        }
    }

    function listEntrys()
    {
        ?>
        <script>
            var question_delmsg = 'Are you sure you want to delete this Quiz question?';
            var <?=$this->order_prepend?>orderby = "<?=addslashes($this->orderby)?>";
            var <?=$this->order_prepend?>orderdir = "<?=$this->orderdir?>";
            var <?=$this->index_name?> =
            0;
            var <?=$this->order_prepend?>pagesize = <?=$this->pagesize?>;
            var QuestionsTableFormat = [
                ['[get:quiz_name:quiz_id]', 'align_left'],
                ['question', 'align_left'],
                ['answer', 'align_center'],
                ['file', 'align_center'],
                ['duration', 'align_center'],
                ['[delete]', 'align_center']
            ];

            /**
             * Build the URL for AJAX to hit, to build the list
             */
            function getQuestionsURL(csv_mode) {
                let frm = getEl('<?=$this->frm_name?>');
                let <?=$this->order_prepend?>pagesize = 0;
                if (csv_mode) {
                    if(!frm.s_quiz_id.value) {
                        alert('You must select a Quiz ID to export!');
                        loadQuestions();
                    }
                    <?=$this->order_prepend?>pagesize = <?=$this->order_prepend?>totalcount;
                } else {
                    <?=$this->order_prepend?>pagesize = $('#<?=$this->order_prepend?>pagesizeDD').val();
                }
                return 'api/api.php' +
                    '?get=questions&' +
                    'mode=' + ((csv_mode) ? 'csv' : 'xml') + '&' +
                    's_quiz_id=' + escape(frm.s_quiz_id.value) + '&' +
                    's_question=' + escape(frm.s_question.value) + '&' +
                    's_answer=' + escape(frm.s_answer.value) + '&' +
                    's_filename=' + escape(frm.s_filename.value) + '&' +
                    'index=' + (<?=$this->index_name?> * <?=$this->order_prepend?>pagesize
            )
                +'&pagesize=' + <?=$this->order_prepend?>pagesize + '&' +
                'orderby=' + <?=$this->order_prepend?>orderby + '&orderdir=' + <?=$this->order_prepend?>orderdir;
            }

            function exportQuestions() {
                let url = getQuestionsURL(true);
                window.open(url);
            }

            function importQuestions() {
                let objname = 'dialog-upload-import-file';
                $('#' + objname).dialog("open");
            }

            var questions_loading_flag = false;

            /**
             * Load the data - make the ajax call, callback to the parse function
             */
            function loadQuestions() {
                // ANTI-CLICK-SPAMMING/DOUBLE CLICK PROTECTION
                var val = null;
                eval('val = questions_loading_flag');
                // CHECK IF WE ARE ALREADY LOADING THIS DATA
                if (val == true) {
                    //console.log("Questions ALREADY LOADING (BYPASSED) \n");
                    return;
                } else {
                    eval('questions_loading_flag = true');
                }
                <?=$this->order_prepend?>pagesize = parseInt($('#<?=$this->order_prepend?>pagesizeDD').val());
                var question_pagesize = 20;
                loadAjaxData(getQuestionsURL(), 'parseQuestions');
            }

            /**
             * CALL THE CENTRAL PARSE FUNCTION WITH AREA SPECIFIC ARGS
             */
            var <?=$this->order_prepend?>totalcount = 0;

            function parseQuestions(xmldoc) {
                <?=$this->order_prepend?>totalcount = parseXMLData('question', QuestionsTableFormat, xmldoc);
                // ACTIVATE PAGE SYSTEM!
                if (<?=$this->order_prepend?>totalcount > <?=$this->order_prepend?>pagesize) {
                    makePageSystem('questions',
                        '<?=$this->index_name?>',
                        <?=$this->order_prepend?>totalcount,
                        <?=$this->index_name?>,
                        <?=$this->order_prepend?>pagesize,
                        'loadQuestions()'
                    );
                } else {
                    hidePageSystem('questions');
                }
                eval('questions_loading_flag = false');
            }

            function handleQuestionListClick(id) {
                displayAddQuestionDialog(id);
            }

            function displayAddQuestionDialog(id) {
                var objname = 'dialog-modal-add-question';
                if (id > 0) {
                    $('#' + objname).dialog("option", "title", 'Editing Question');
                } else {
                    $('#' + objname).dialog("option", "title", 'Adding new Question');
                }
                $('#' + objname).dialog("open");
                $('#' + objname).html('<table  class="table table-sm"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>').load("index.php?area=quiz_questions&add_question=" + id + "&printable=1&no_script=1");
            }

            function resetQuestionForm(frm) {
                frm.s_quiz_id.value = '';
                frm.s_question.value = '';
                frm.s_answer.value = '';
                frm.s_filename.value = '';
            }
            var questionsrchtog = true;
            function toggleQuestionSearch() {
                questionsrchtog = !questionsrchtog;
                ieDisplay('question_search_table', questionsrchtog);
            }
        </script>
        <!-- ****START**** THIS AREA REPLACES THE OLD TABLES WITH THE NEW ONEUI INTERFACE BASED ON BOOTSTRAP -->
        <div class="block">
            <form name="<?= $this->frm_name ?>" id="<?= $this->frm_name ?>" method="POST" action="<?= $_SERVER['REQUEST_URI']; ?>" onsubmit="loadQuestions();return false;">
                <div class="block-header bg-primary-light">
                    <h4 class="block-title">Quiz Questions</h4>
                    <button type="button" value="Add" title="Add Questions" class="btn btn-sm btn-primary" onclick="displayAddQuestionDialog(0)">Add</button>
                    <button type="button" value="Search" title="Toggle Search" class="btn btn-sm btn-primary" onclick="toggleQuestionSearch();">Toggle Search</button>
                    <div id="questions_prev_td" class="page_system_prev"></div>
                    <div id="questions_page_td" class="page_system_page"></div>
                    <div id="questions_next_td" class="page_system_next"></div>
                    <select title="Rows Per Page" class="custom-select-sm" name="<?= $this->order_prepend ?>pagesize" id="<?= $this->order_prepend ?>pagesizeDD" onchange="<?= $this->index_name ?>=0;loadQuestions(); return false;">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                    <div class="d-inline-block ml-2">
                        <button class="btn btn-sm btn-dark" title="Total Found">
                            <i class="si si-list"></i>
                            <span class="badge badge-light badge-pill"><div id="total_count_div"></div></span>
                        </button>
                    </div>
                </div>
                <div class="bg-info-light" id="question_search_table">
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="searching_question"/>
                        <?= $this->makeDD('s_quiz_id', $_REQUEST['s_quiz_id'], '', "loadQuestions();", 0, "[Select Quiz]"); ?>
                        <input type="text" class="form-control" placeholder="Question.." name="s_question" value="<?= htmlentities($_REQUEST['s_question']) ?>"/>
                        <input type="text" class="form-control" placeholder="Answer.." name="s_answer" value="<?= htmlentities($_REQUEST['s_answer']) ?>"/>
                        <input type="text" class="form-control" placeholder="Filename.." name="s_filename" value="<?= htmlentities($_REQUEST['s_filename']) ?>"/>
                        <button type="button" value="Search" title="Search Quiz Questions" class="btn btn-sm btn-primary" name="the_Search_button" onclick="loadQuestions();return false;">Search</button>
                        <button type="button" value="Reset" title="Reset Search Criteria" class="btn btn-sm btn-primary" onclick="resetQuestionForm(this.form);resetPageSystem('<?= $this->index_name ?>');loadQuestions();return false;">Reset</button>
                        <button type="button" value="Export" title="Export Results to CSV" class="btn btn-sm btn-danger" name="export_button" onclick="exportQuestions();">Export</button>
                        <button type="button" value="Import" title="Import Quiz Questions" class="btn btn-sm btn-success" name="import_button" onclick="importQuestions();">Import</button>
                    </div>
                </div>
                <div class="block-content">
                    <table class="table table-sm table-striped" id="question_table">
                        <caption id="current_time_span" class="small text-right">Server Time: <?= date("g:ia m/d/Y T") ?></caption>
                        <tr>
                            <th class="row2 text-left"><?= $this->getOrderLink('quiz_id') ?>Quiz</a></th>
                            <th class="row2 text-left"><?= $this->getOrderLink('question') ?>Question</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('answer') ?>Answer</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('filename') ?>Filename</a></th>
                            <th class="row2 text-center"><?= $this->getOrderLink('duration') ?>Duration</a></th>
                            <th class="row2 text-center">&nbsp;</th>
                        </tr>
                    </table>
                </div>
            </form>
        </div>
        <!-- ****END**** THIS AREA REPLACES THE OLD TABLES WITH THE NEW ONEUI INTERFACE BASED ON BOOTSTRAP -->
        <div id="dialog-modal-add-question" title="Adding new Question" class="nod"></div>
        <div id="dialog-upload-import-file" title="Import Quiz Questions" class="nod">
            <form method="POST" enctype="multipart/form-data" action="<?=$_SERVER['REQUEST_URI'];?>">
                <input type="hidden" name="import_questions" />
                <table class="table table-sm">
                    <tr>
                        <th>Select Quiz:</th>
                        <td>
                            <?= $this->makeDD('f_quiz_id', '', '', "", 0, ""); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Import Quiz Questions CSV File:</th>
                        <td><input type="file" accept="text/csv" name="questions_file" id="questions_file"></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="text-center">
                            <input class="btn btn-sm btn-success" type="submit" value="Upload"/>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <script>
            $("#dialog-modal-add-question").dialog({
                autoOpen: false,
                width: 'auto',
                height: 250,
                modal: false,
                draggable: true,
                resizable: false,
                position: {my: 'center', at: 'center'},
            });
            $("#dialog-modal-add-question").closest('.ui-dialog').draggable("option", "containment", "#main-container");
            loadQuestions();
            $('#s_quiz_id').attr('title', 'Select Quiz ID');
            $("#dialog-upload-import-file").dialog({
                autoOpen: false,
                width: 'auto',
                height: 200,
                modal: false,
                draggable: true,
                resizable: false,
                position: {my: 'center', at: 'center'},
            });
        </script>
        <?
    }

    function importQuizQuestions($qID, $qFile) {
        $qtmpFileName = $qFile['tmp_name'];
        $qusrFileName = $qFile['name'];
        // Get Quiz ID from filename
        $t = explode("-", $qusrFileName);
        $fQuizID = intval($t[2]);
        if ($qID != $fQuizID) {
            jsAlert("Quiz ID selected (" . $qID . ") does not match file (" . $fQuizID . ")");
            return;
        }
        // Get the file as a CSV (Intrinsic) and load it into an array
        $fArray = $fFields = array();
        $i = 0;
        $fHandle = @fopen($qtmpFileName, "r");
        if ($fHandle) {
            while (($fRow = fgetcsv($fHandle, 1000)) !== FALSE) {
                // Get the field names from the header row
                if (empty($fFields)) {
                    $fFields = $fRow;
                    continue;
                }
                // Assign the file's values associatively so we can create our insert statements without worry
                foreach ($fRow as $fKey => $fValue) {
                    $fArray[$i][$fFields[$fKey]] = $fValue;
                }
                $i++;
            }
            if (!feof($fHandle)) {
                jsAlert("Unexpected file error");
                return;
            }
            fclose($fHandle);
        } else {
            jsAlert("File not found");
            return;
        }
        // Iterate through the data and update the table (if necessary)
        $sCount = 0;
        foreach($fArray as $quizRowNum => $quizRow) {
            // SKIP BLANK LINES
            if (!is_array($quizRow)) continue;
            $dat = array();
            $dat['quiz_id']	= $qID;
            foreach($quizRow as $fHeaderKey => $fldValue){
                // Strip any style quotes from the current value
                str_replace('"', "", $fldValue);
                str_replace("'", "", $fldValue);
                // ID,Duration,Question,Answer,Variables,Filename,ScriptID,PlayIndex,RepeatMode
                switch($fHeaderKey){
                    default:
                        $dat[$fHeaderKey] = trim($fldValue);
                        break;
                    case 'Duration':
                        $dat['duration'] = floatval($fldValue);
                        break;
                    case 'Question':
                        $dat['question'] = ucwords($fldValue);
                        break;
                    case 'Variables':
                        $dat['variables'] = trim($fldValue);
                        break;
                    case 'Filename':
                        $dat['file'] = trim($fldValue);
                        break;
                    case 'Answer':
                        $dat['answer'] = intval($fldValue);
                        break;
                    case 'ScriptID':
                        $dat['script_id'] = intval($fldValue);
                        break;
                    case 'PlayIndex':
                        $dat['play_index'] = intval($fldValue);
                        break;
                    case 'RepeatMode':
                        $dat['script_repeat_mode'] = trim($fldValue);
                        break;
                }
                unset($dat['ID']);
            }
            // Check for an id - UPDATE if present, INSERT if not
            if($quizRow['id']) {
                 aedit($quizRow['id'], $dat, $this->table);
                $sCount++;
            } else {
                 aadd($dat, $this->table);
                $sCount++;
            }
        }
        jsAlert("Successfully imported " . $sCount . " records");
        return;
    }

    function makeAdd($id)
    {
        $id = intval($id);
        if ($id) {
            $row = $_SESSION['dbapi']->quiz_questions->getByID($id);
        }
        ?>
        <script>
            // Used by dialog box Cancel button
            function HideAddQuestion() {
                var objname = 'dialog-modal-add-question';
                $('#' + objname).dialog("close");
            }

            function validateQuestionField(name, value, frm) {
                //alert(name+","+value);
                switch (name) {
                    default:
                        // ALLOW FIELDS WE DONT SPECIFY TO BYPASS!
                        return true;
                        break;
                    case 'filename':
                        if (!value) return false;
                        return true;
                        break;
                }
                return true;
            }
            function checkQuestionFrm(frm) {
                let params = getFormValues(frm, 'validateQuestionField');
                // FORM VALIDATION FAILED!
                // params[0] == field name
                // params[1] == field value
                if (typeof params == "object") {
                    switch (params[0]) {
                        default:
                            alert("Error submitting form. Check your values");
                            break;
                    }
                    // SUCCESS - POST AJAX TO SERVER
                } else {
                    //alert("Form validated, posting");
                    $.ajax({
                        type: "POST",
                        method: 'POST',
                        cache: false,
                        url: 'api/api.php?get=quiz_questions&mode=xml&action=edit',
                        // data: params,
                        data: params,
                        contentType: false,
                        processData: false,
                        error: function () {
                            alert("Error saving user form. Please contact an admin.");
                        },
                        success: function (msg) {
                            var result = handleEditXML(msg);
                            var res = result['result'];
                            if (res <= 0) {
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

            function playAudio(id) {
                //$('#media_player').dialog("open");
                $('#quiz_media_player').children().filter("audio").each(function () {
                    this.pause(); // can't hurt
                    delete (this); // @sparkey reports that this did the trick!
                    $(this).remove(); // not sure if this works after null assignment
                });
                $('#quiz_media_player').empty();
                $('#quiz_media_player').load("index.php?area=quiz_questions&play_quiz_file=" + id + "&printable=1&no_script=1");
                // $('#media_player').load("test.php");
                // REMOVE AND READD TEH CLOSE BINDING, TO STOP THE AUDIO
                $('#quiz_media_player').unbind("dialogclose");
                $('#quiz_media_player').bind('dialogclose', function (event) {
                    hideAudio();
                });
            }

            function hideAudio() {
                $('#quiz_media_player').children().filter("audio").each(function () {
                    this.pause();
                    delete (this);
                    $(this).remove();
                });
                $('#quiz_media_player').empty();
            }

            function uploadSoundFile(file) {
                let audioFile = $('#newSoundFile').prop('files')[0];
                let questionID = $('#adding_question').value();
                let data = new FormData(frm);
                data.append('questionid', questionID);
                data.append('wavfile', audioFile);
                console.log(data);
                $.ajax({
                    url: 'api/api.php?get=quiz_questions&mode=xml&action=upload',
                    dataType: 'text',
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: data,
                    type: 'post',
                    success: function(response){
                        alert(response);
                    }
                });
            }
            // SET TITLEBAR
            $('#dialog-modal-add-question').dialog("option", "title", '<?=($id) ? 'Editing Question #' . $id . ' - ' . htmlentities($row['question']) : 'Adding new Question'?>');
        </script>
        <div class="text-center" id="quiz_media_player" title="Playing Quiz File">
            <form method="POST" enctype="multipart/form-data" action="<?= stripurl('') ?>" autocomplete="off" onsubmit="checkQuestionFrm(this); return false">
                <input type="hidden" id="adding_question" name="adding_question" value="<?= $id ?>">
                <table class="table table-sm">
                    <tr>
                        <th class="text-left" height="30">Quiz:</th>
                        <td><?= $this->makeDD('quiz_id', $row['quiz_id'], '', "", 0, 0); ?></td>
                    </tr>
                    <tr>
                        <th class="text-left" height="30">Question:</th>
                        <td><input name="question" type="text" size="50" value="<?= htmlentities($row['question']) ?>"></td>
                    </tr>
                    <tr>
                        <th class="text-left" height="30">Answer:</th>
                        <td><input name="answer" type="text" size="5" value="<?= htmlentities($row['answer']) ?>"></td>
                    </tr>
                    <tr>
                        <th class="text-left" height="30">Sound File:</th>
                        <td>
                            <?=htmlentities($row['file']);?><br />
                            <input title="Upload a sound file to assign to this question" name="newSoundFile" id="newSoundFile" type="file" accept="audio/wav"><br />
                            <input type="button" class="btn btn-sm btn-warning" title="Upload File" id="uploadSoundFile" onclick="uploadSoundFile()" value="Upload" />
                        </td>
                    </tr>
                    <tr>
                        <th colspan="2" align="center"><input type="submit" value="Save Changes">
                            <input type="button" value="Cancel" onclick="hideAudio(); HideAddQuestion(); return false;">
                            <input type="button" value="Listen" onclick="playAudio('<?= $row['id'] ?>')"></th>
                    </tr>
            </form>
        </div>
        </table>
        <?
    }

    function PlayQuizFile($id)
    {
        # Play audio file function - it will display audio player with play_audio_file.php as source
        $id = intval($id);
        if ($id) {
            $row = $_SESSION['dbapi']->quiz_questions->getByID($id);
        }
        ?>
        <audio id="audio_obj" autoplay controls>
            <source src="play_audio_file.php?file=<?= htmlentities($row['file']) ?>" type="audio/wav"/>
            Your browser does not support the audio element.
        </audio><br>
        <a href="#" onclick="parent.hideAudio();return false">[Hide Player]</a>
        <script>
            parent.applyUniformity();
        </script>
        <?
    }

    function makeDD($name, $sel, $class, $onchange, $size, $blank_entry = 1)
    {
        $names = 'name';    ## or Array('field1','field2')
        $value = 'id';
        $seperator = '';        ## If $names == Array, this will be the seperator between fields
        $fieldstring = '';
        if (is_array($names)) {
            $x = 0;
            foreach ($names as $name) {
                $fieldstring .= $name . ',';
            }
        } else {
            $fieldstring .= $names . ',';
        }
        $fieldstring .= $value;
        $sql = "SELECT $fieldstring FROM quiz WHERE 1 ";
        $DD = new genericDD($sql, $names, $value, $seperator);
        return $DD->makeDD($name, $sel, $class, $blank_entry, $onchange, $size);
    }

    function getOrderLink($field)
    {
        $var = '<a href="#" onclick="setOrder(\'' . addslashes($this->order_prepend) . '\',\'' . addslashes($field) . '\',';
        $var .= "((" . $this->order_prepend . "orderdir == 'DESC')?'ASC':'DESC')";
        $var .= ");loadQuestions();return false;\">";
        return $var;
    }
}
