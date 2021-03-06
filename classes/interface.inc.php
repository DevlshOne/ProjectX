<?php
/***************************************************************
 *    Interface class - handles generic interface stuff, like menus, navigation, etc
 *    Written By: Jonathan Will
 ***************************************************************/
$_SESSION['interface'] = new InterfaceClass;

class InterfaceClass
{
    public function InterfaceClass()
    {
    }

    public function makeNewHeader()
    {
        include_once("classes/home.inc.php");
        $_SESSION['home']->handleFLOW();
        ## CHECK IF PASSWORD IS OLDER THAN 6 MONTHS FOR PRIV 4 OR GREATER
        if ($_SESSION['user']['priv'] >= 4) {
            $sixmonthsago = strtotime("-6 months");
            if ($_SESSION['user']['changedpw_time'] < $sixmonthsago) {
                ?>
                <div id="change-password-expired-div" title="Password Expired - Change Required"></div>
                <script>
                    $('#change-password-expired-div').dialog({
                        dialogClass: "no-close",
                        autoOpen: false,
                        width: 400,
                        height: 280,
                        modal: true,
                        draggable: false,
                        resizable: false
                    });

                    $("#change-password-expired-div").closest('.ui-dialog').draggable("option","containment","#main-container");

                    function loadChangeExpiredPassword() {
                        $('#change-password-expired-div').dialog("open");
                        $('#change-password-expired-div').html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                        $('#change-password-expired-div').load("index.php?area=change_expired_password&printable=1&no_script=1");
                    }

                    loadChangeExpiredPassword();
                </script>
                <?
            }
        }
        ?>
        <div id="dialog-modal-view_change_history" title="View Change History" class="nod"></div>
        <div id="change-password-div" title="Change Password"></div>
        <script>
            $('#change-password-div').dialog({
                autoOpen: false,
                width: 400,
                height: 280,
                modal: false,
                draggable: true,
                resizable: true
            });
            $("#dialog-modal-view_change_history").dialog({
                autoOpen: false,
                width: 560,
                height: 360,
                modal: false,
                draggable: true,
                resizable: true
            });

            $("#change-password-div").closest('.ui-dialog').draggable("option","containment","#main-container");
            $("#dialog-modal-view_change_history").closest('.ui-dialog').draggable("option","containment","#main-container");

            
            function loadChangePassword() {
                $('#change-password-div').dialog("open");
                $('#change-password-div').html('<table border="0" width="100%" height="100%"><tr><td align="center"><img src="images/ajax-loader.gif" border="0" /> Loading...</td></tr></table>');
                $('#change-password-div').load("index.php?area=change_password&printable=1&no_script=1");
            }
        </script>
        <?
    }

    public function makeHeader()
    {
        ?>
        <table class="tightTable">
            <tr>
                <td><a href="index.php"><img src="images/cci-logo-300.png" width="200" border="0"/></a></td>
                <th align="left"><h1>Project X - Administration</h1></th>
                <td valign="top" align="right">
                    Logged in as: <?= $_SESSION['user']['username'] ?> |
                    <input type="button" value="Logout" onclick="go('?o')">
                </td>
            </tr>
            <tr>
                <td colspan="3" class="bl"></td>
            </tr>
        </table>
        <?
    }
}

