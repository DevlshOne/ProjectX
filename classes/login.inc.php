<?php

class LoginClass
{
    function __construct()
    {
        $this->handleLoginPost();
    }

    /** handleLoginPost()
     * Login functions - such as the gui to login, and prolly the post handling codefu too
     * Requires DBAPI
     */
    function handleLoginPost()
    {
        if (isset($_POST['loginbutton']) && isset($_POST['username']) && isset($_POST['md5pass'])) {
            //echo "Login POST";
            //print_r($_REQUEST);exit;
            $user = trim($_POST['username']);
            $pass = trim($_POST['md5pass']);
            $kicktourl = ((isset($_POST['kick_to'])) ? trim($_POST['kick_to']) : '');
            $row = $_SESSION['dbapi']->users->checkLogin($user, $pass, $_SESSION['login_salt']);
            ## USER/PASS INVALID, OR ACCOUNT DISABLED
            if (!$row || $row <= 0) {
                $_SESSION['dbapi']->users->tracklogin(0, $user, $pass, 'Failure');
                jsAlert("ERROR: YOUR USER/PASS ARE INCORRECT", 0);
                # GENERATE NEW LOGIN SALT
                $_SESSION['login_salt'] = $_SESSION['dbapi']->users->generateSalt();
                jsRedirect(stripurl(array('area', 'no_script')));
                exit;
                // MUST BE AN ADMIN OR MANAGER TO ACCESS THIS CODE
            } else if ($row['priv'] < 4) {
                $_SESSION['dbapi']->users->tracklogin(0, $user, $pass, 'Failure');
                jsAlert("ERROR: You must be an administrator/manager to access this section.", 0);
                jsRedirect(stripurl(''));
                exit;
            } else {
                // LOAD AND CHECK ACCOUNT STATUS
                $_SESSION['account'] = $_SESSION['dbapi']->accounts->getByID($row['account_id']);
                if (!$_SESSION['account']['id']) {
                	
                    $_SESSION['dbapi']->users->tracklogin(0, $user, $pass, 'Failure', 'Account ' . intval($row['account_id']) . ' not found.');
                    
                    jsAlert("ERROR: Account ID#" . intval($row['account_id']) . " was not found.", 0);
                    
                    unset($_SESSION['account']);
                    
                    jsRedirect(stripurl(''));
                    exit;
                }
                if ($_SESSION['account']['status'] != 'active') {
                	
                    $_SESSION['dbapi']->users->tracklogin(0, $user, $pass, 'Failure', 'Account ' . intval($row['account_id']) . ' status is ' . $_SESSION['account']['status']);
                    
                    jsAlert("ERROR: Account ID#" . intval($row['account_id']) . " is listed as '" . $_SESSION['account']['status'] . "'", 0);
                    
                    unset($_SESSION['account']);
                    
                    jsRedirect(stripurl(''));
                    exit;
                }
                
                
                
                $action_time_range = time() - 3600;
                
                // CHECK FOR OTHER USERS FROM DIFFERENT IP ADDRESS LOGGED IN
                $last_login_res = $_SESSION['dbapi']->query(
                		
                		"SELECT * FROM `logins` " .
	                    " WHERE `username`='" . mysqli_real_escape_string($_SESSION['dbapi']->db, $row['username']) . "' " .
	                    // THEY SUCCESSFULLY LOGGED INTO THE ADMIN
	                    " AND `result`='success' AND `section`='admin' " .
	                    // AND THEY HAVEN'T LOGGED OUT PROPERLY
	                    " AND `time_out`=0 " .
                		" AND `time_last_action` > '$action_time_range' ".
	                    " ORDER BY id DESC", 1);
// 				print_r($last_login);
// 				exit;

                $login_instances = 0;
                
                $oldest_record = null;
                while($last_login = mysqli_fetch_array($last_login_res, MYSQLI_ASSOC) ){
                	
                
                
	                
	                // IF THEY HAVEN'T LOGGED OUT PROPERLY, AND ARE COMING FROM ANOTHER IP ADDRESS
	                // AND THERE LAST ACTION WAS SOONER THAN 15 MINUTES AGO
	                if ($last_login['time_out'] == 0 &&
	                    ($_SERVER['REMOTE_ADDR'] != $last_login['ip']) &&
	                    ($last_login['time_last_action'] > (time() - 900))
	                ) {
	                	
	                	$login_instances++;
	                	
	                	$oldest_record = $last_login;
	                	
	                } // END IF
                
                } // END WHILE
                
                
                
                if($login_instances >= $row['max_login_instances']){
                
                	
                	// KICK THE OLDEST ONE
                	jsAlert('WARNING: User ('.$row['username'].') was logged into another station('.$oldest_record['ip'].'), and will be kicked.\nLast Action: ' . date("H:i:s T", $oldest_record['time_last_action']), 1);
                	
                	$_SESSION['dbapi']->users->kickUserByLogin($oldest_record, 'User '.$row['username'].' has logged in from another station ('.$_SERVER['REMOTE_ADDR'].')');
                	
  /** HARD BLOCK MODE - DONT ALLOW ANOTHER USER
   *              	unset($_SESSION['account']);
                	// REJECT LOGIN!
                	$_SESSION['dbapi']->users->tracklogin(0, $user, $pass, 'Failure', 'User is logged in another station (' . $last_login['ip'] . ').');
                	jsAlert('ERROR: User is logged in another station (' . $last_login['ip'] . ')\nLast Action: ' . date("H:i:s", $last_login['time_last_action']), 1);
                	jsRedirect(stripurl(''));
                	exit;
                	
   **/
                }
                
                
                
              ## FINALLY IN FOR REAL, ALL TESTS PASSED  
                
                
                
                
                $login_id = $_SESSION['dbapi']->users->tracklogin($row['id'], $user, $pass, 'Success');
                ## STORE USER RECORD IN SESSION!
                $_SESSION['user'] = $row;
                # GENERATE NEW LOGIN SALT
                $_SESSION['login_salt'] = $_SESSION['dbapi']->users->generateSalt();
                $_SESSION['logins'] = $_SESSION['dbapi']->querySQL("SELECT * FROM `logins` WHERE id='" . $login_id . "' ");
                ## LOAD FEATURES FOR THE USER, IF THEY ARE SET
                if ($row['feature_id'] > 0) {
                    $_SESSION['features'] = $_SESSION['dbapi']->querySQL("SELECT * FROM features WHERE id='" . intval($row['feature_id']) . "' ");
                }
                // LOAD ASSIGNED OFFICES
                if ($row['priv'] < 5) {
                    // INIT THE ARRAY
                    $_SESSION['assigned_offices'] = array();
                    // POPULATE THE ALLOWED/ASSIGNED OFFICES ARRAY
                    $re2 = $_SESSION['dbapi']->query("SELECT * FROM `users_offices` WHERE user_id='" . mysqli_real_escape_string($_SESSION['dbapi']->db, $row['id']) . "'");
                    $_SESSION['assigned_office_groups'] = array();
                    $_SESSION['assigned_groups'] = array();
                    while ($r2 = mysqli_fetch_array($re2, MYSQLI_ASSOC)) {
                        $_SESSION['assigned_offices'][] = $r2['office_id'];
                        // POPULATE THE GROUP ARRAY FOR THE SELECTED OFFICE(S)
                        if (!is_array($_SESSION['assigned_office_groups'][$r2['office_id']])) {
                            $_SESSION['assigned_office_groups'][$r2['office_id']] = array();
                        }
                        $re3 = $_SESSION['dbapi']->query("SELECT * FROM `user_groups` WHERE `office`='" . mysqli_real_escape_string($_SESSION['dbapi']->db, $r2['office_id']) . "'");
                        while ($r3 = mysqli_fetch_array($re3, MYSQLI_ASSOC)) {
                            $_SESSION['assigned_groups'][] = $r3['user_group'];
                            $_SESSION['assigned_office_groups'][$r2['office_id']][] = $r3['user_group'];
                        }
                    }
                }
                ## UPDATE THE TIME OF LAST LOGIN
                $_SESSION['dbapi']->users->updateLastLoginTime();
                if ($kicktourl) {
                    $_SESSION['one_time_kick_to'] = $kicktourl;
                }
                jsRedirect('index.php');
                exit;
            }
        }
    }

    function makeLoginForm()
    {
        $kickto = isset($_REQUEST['kick_to']) ? $_REQUEST['kick_to'] : '';
        $uname = isset($_REQUEST['uname']) ? $_REQUEST['uname'] : '';
        ?>
        <main id="main-container">
            <script src="js/md5.js"></script>
            <style>
                .red {
                    color: red;
                }
            </style>
            <script>
                $('#page-container').remove('sidebar aside');
                $('#page-container').removeClass('sidebar-o sidebar-dark');

                function checkLoginForm(frm) {
                    if (!frm.username.value) {
                        alert("Error: Please enter a username");
                        frm.username.select();
                        return false;
                    }
                    if (!frm.password.value) {
                        alert("Error: Please enter your password");
                        frm.password.select();
                        return false;
                    }
                    var obj = getEl('md5pass');
                    obj.value = hex_md5(hex_md5(frm.password.value) + '<?=$_SESSION['login_salt']?>');
                    frm.password.value = '';
                    return true;
                }
            </script>
            <div class="row">
                <div class="col-md-3 text-center"></div>
                <div class="col-md-6 text-center">
                    <form method="POST" class="form-signin" action="<?= stripurl('') ?>" target="_top"
                          onsubmit="return checkLoginForm(this)">
                        <div class="block block-themed block-fx-shadow">
                            <div class="block-header bg-info text-center">
                                <img src="images/cci-logo-200-2.png" style="padding-right:8px;" height="30" border="0"/>
                                <h3 class="block-title">Project X - Administration</h3>
                                <div class="block-options">
                                    <button type="submit" value="Login" class="btn btm-sm btn-primary"
                                            name="loginbutton">
                                        Login
                                    </button>
                                    <button type="reset" class="btn btn-sm btn-secondary">Reset</button>
                                </div>
                            </div>
                            <div class="block-content text-left">
                                <div class="row justify-content-center py-sm-3 py-md-5">
                                    <div class="col-sm-10 col-md-8">
                                        <div class="form-group">
                                            <label for="username" class="text-left">Username</label>
                                            <input type="text" placeholder="Enter your username.."
                                                   class="form-control form-control-alt" id="username" name="username"
                                                   value="<?= $uname ?>"
                                            >
                                            <input type="hidden" name="kick_to"
                                                   value="<?= htmlentities($kickto) ?>">
                                            <input type="hidden" id="md5pass" name="md5pass" value="">
                                        </div>
                                        <div class="form-group">
                                            <label for="password" class="text-left">Password</label>
                                            <input type="password" placeholder="Enter your password.." name="password"
                                                   class="form-control form-control-alt" id="password"
                                                   value="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        <?
    }
}