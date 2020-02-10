<?php
/***************************************************************
 *    Drop Down Functions
 *    Written By:    Jonathan Will
 ***************************************************************/

    function makeServerIPDD($name, $sel, $blank_field = 0)
    {
        $out = '<select name="' . $name . '" id="' . $name . '">';

        $res = query("SELECT * FROM servers WHERE running='yes' ORDER BY name ASC", 1);

        if ($blank_field) {
            $out .= '<option value="">[SELECT ONE]</option>';
        }

        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
            $out .= '<option value="' . $row['ip_address'] . '"';

            $out .= ($row['ip_address'] == $sel) ? ' SELECTED ' : '';

            $out .= '>' . $row['name'] . ' - ' . $row['ip_address'] . '</option>';
        }

        $out .= '</select>';

        return $out;
    }

    /**
     * @param string      $name        the name and id of the select element
     * @param string      $sel         the currently selected option
     * @param string|null $onchange    if populated, script to execute onchange
     * @param string|bool $blank_entry if populated, string that represents the option text when field is blank
     *
     * @return string $showDD complete select statement ready to be rendered
     */
    function makeCompanyDD($name, $sel, $onchange = NULL, $blank_entry = false)
    {
        $sql = "SELECT `id`, `name` FROM companies WHERE `status` = 'enabled' ORDER BY `name` ASC";
        $res = query($sql, 1);
        $showDD = "<select name='" . $name . "' id='" . $name . "'";
        if (isset($onchange)) {
            $showDD .= " onchange='" . htmlentities(trim($onchange)) . "'";
        }
        $showDD .= ">";
        if ($blank_entry) {
            $showDD .= "<option value=''>" . $blank_entry . "</option>";
        }
        if (mysqli_num_rows($res) > 0) {
            for ($x = 0; $row = mysqli_fetch_array($res); $x++) {
                $showDD .= "<option value='" . $row['id'] . "'";
                if ($row['id'] == $sel) {
                    $showDD .= " selected";
                }
                $showDD .= ">" . $row['name'] . "</option>";
            }
        }
        $showDD .= "</select>";
        return $showDD;
    }

    function makeServerDD($name, $sel, $blank_field = 0)
    {
        $out = '<select name="' . $name . '" id="' . $name . '">';

        $res = query("SELECT * FROM servers ORDER BY name ASC", 1);

        if ($blank_field) {
            $out .= '<option value="">[SELECT ONE]</option>';
        }

        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
            $out .= '<option value="' . $row['id'] . '"';

            $out .= ($row['id'] == $sel) ? ' SELECTED ' : '';

            $out .= '>' . $row['name'] . '</option>';
        }

        $out .= '</select>';

        return $out;
    }

    /** MAKE A DROPDOWN OF USERS
     * If $blank_entry is a String, it will render as the name of the blank entry, instead of "[ALL]"
     */
    function makeUserDD($name, $sel, $onchange, $blank_entry = false)
    {
        $out = '<select name="' . $name . '" id="' . $name . '" ';

        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';

        $out .= '>';

        if ($blank_entry) {
            $out .= '<option value="">' . ((is_string($blank_entry)) ? $blank_entry : "[All]") . '</option>';
        }

        $res = query("SELECT * FROM users WHERE enabled='yes' ORDER BY username ASC", 1);

        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
            $out .= '<option value="' . htmlentities($row['username']) . '" ';

            $out .= ($sel == $row['username']) ? ' SELECTED ' : '';

            $out .= '>' . $row['username'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'] . '</option>';
        }

        $out .= '</select>';

        return $out;
    }

    /**
     * Makes a user Dropdown where the VALUE of the select box, is the ID, instead of name
     */
    function makeUserIDDD($name, $sel, $onchange, $blank_entry = false)
    {
        $out = '<select name="' . $name . '" id="' . $name . '" ';

        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';

        $out .= '>';

        if ($blank_entry) {
            $out .= '<option value="">' . ((is_string($blank_entry)) ? $blank_entry : "[All]") . '</option>';
        }

        $res = query("SELECT * FROM users WHERE enabled='yes' ORDER BY username ASC", 1);

        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
            $out .= '<option value="' . $row['id'] . '" ';

            $out .= ($sel == $row['id']) ? ' SELECTED ' : '';

            $out .= '>' . $row['username'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'] . '</option>';
        }

        $out .= '</select>';

        return $out;
    }

    function makeViciUserGroupDD($name, $selected, $css, $onchange, $size = 0, $blank_option = 1)
    {
        connectPXDB();

        //$res = query("SELECT DISTINCT(user_group) AS user_group FROM users WHERE user_group IS NOT NULL AND enabled='yes' ");

        //ADDING OFFICE MODS - TO RESTRICT WHAT GROUPS APPEAR, DEPENDING ON OFFICE ASSIGNMENT
        $ofcsql = "";
        if (
            ($_SESSION['user']['priv'] < 5) &&
            ($_SESSION['user']['allow_all_offices'] != 'yes')
        ) {
            $ofcsql = " AND `office` IN (";
            $x = 0;
            foreach ($_SESSION['assigned_offices'] as $ofc) {
                if ($x++ > 0) {
                    $ofcsql .= ",";
                }

                $ofcsql .= intval($ofc);
            }
            if ($x > 0) {
                $ofcsql .= ") ";
            }
        }


        $res = query("SELECT DISTINCT(user_group) AS user_group FROM user_groups ".
                    " WHERE user_group IS NOT NULL ".
                    $ofcsql.
                    "ORDER BY user_group ASC");

        $force_height = $size * 20;

        $out = '<select name="' . $name . '" id="' . $name . '" ';

        $out .= ($size > 0) ? " MULTIPLE size=\"" . $size . "\" style=\"height:".$force_height."px\"" : '';

        $out .= ($css) ? ' class="' . $css . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= '>';

        //$out .= '<option value="" '.(($selected == '')?' SELECTED ':'').'>[All]</option>';
        if ($blank_option) {
            $out .= '<option value="" ' . ((!$selected || $selected[0] == '') ? ' SELECTED ' : '') . '>' . ((!is_numeric($blank_option)) ? $blank_option : "[All]") . '</option>';
        }

        while ($row = mysqli_fetch_array($res)) {
            $is_sel = false;

            if (is_array($selected)) {
                foreach ($selected as $sel) {
                    if ($sel == $row['user_group']) {
                        $is_sel = true;
                        break;
                    }
                }
            } else {
                $is_sel = ($selected == $row['user_group']) ? true : false;
            }

            $out .= '<option value="' . $row['user_group'] . '" ';
            $out .= ($is_sel) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['user_group']) . '</option>';
        }

        $out .= '</select>';

        return $out;
    }

    function makeOfficeDD($name, $selected, $css, $onchange, $blank_option = 1, $size = 0){
        $size = intval($size);

        //if (!$_SESSION['offices_data']) {
            connectPXDB();

            $res = query("SELECT name, id FROM offices WHERE `enabled`='yes'");
            $rowarr = array();
            while ($row = mysqli_fetch_array($res)) {
                $rowarr[$row['id']] = $row['name'];
            }

            $_SESSION['offices_data'] = $rowarr;
        //}

        $out = '<select name="' . $name . '" id="' . $name . '" ';

        $out .= ($css) ? ' class="' . $css . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';

        $out .= ($size > 0) ? ' size="' . $size . '" MULTIPLE ' : '';

        $out .= '>';

        if (
            ($_SESSION['user']['priv'] < 5) &&
            ($_SESSION['user']['allow_all_offices'] != 'yes')
        ) {
            $default_blank_option = "[All Assigned]";
        } else {
            $default_blank_option = "[All]";
        }

        if ($blank_option) {
            $out .= '<option value="" ' . (($selected == '') ? ' SELECTED ' : '') . '>' . ((!is_numeric($blank_option)) ? $blank_option : $default_blank_option) . '</option>';
        }

        foreach ($_SESSION['offices_data'] as $id => $name) {
            if (
                ($_SESSION['user']['priv'] < 5) &&
                ($_SESSION['user']['allow_all_offices'] != 'yes')
            ) {
                if (!in_array($id, $_SESSION['assigned_offices'])) {
                    continue;
                }
            }

            $out .= '<option value="' . $id . '" ';

            if (is_array($selected)) {
                foreach ($selected as $sel) {
                    if ($id == $sel) {
                        $out .= ' SELECTED ';
                        break;
                    }
                }
            } else {
                $out .= ($selected == $id) ? ' SELECTED ' : '';
            }

            $out .= '>Office ' . $id . " - " . htmlentities($name) . '</option>';
        }

        $out .= '</select>';

        return $out;
    }

    function makeVoiceDD($campaign_id, $name, $sel, $class, $blank_opt)
    {
        $out = '<select name="' . $name . '" id="' . $name . '" ';
        $out .= ($class) ? ' class="' . $class . '" ' : '';
        ##$out.= ($onchange)?' onchange="'.$onchange.'" ':'';
        $out .= '>';

        $dat = array();

        $dat['status'] = 'enabled';

        if ($campaign_id > 0) {
            $dat['campaign_id'] = intval($campaign_id);
        }

        if ($blank_opt) {
            $out .= '<option value="">[All]</option>';
        }

        $dat['order'] = array('id' => 'ASC');

        $res = $_SESSION['dbapi']->voices->getResults($dat);

        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
            $out .= '<option value="' . $row['id'] . '" ';
            $out .= ($sel == $row['id']) ? ' SELECTED ' : '';
            $out .= '>#' . $row['id'] . ' ' . htmlentities($row['name']) . '</option>';
        }

        $out .= '</select>';

        return $out;
    }

    function makeCampaignIDDD($name, $selected, $css, $onchange, $blank_option = 1)
    {
        connectPXDB();

        $res = query("SELECT name, id FROM campaigns WHERE `status`='active'");

        $out = '<select name="' . $name . '" id="' . $name . '" ';

        $out .= ($css) ? ' class="' . $css . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= '>';

        if ($blank_option) {
            $out .= '<option value="" ' . (($selected == '') ? ' SELECTED ' : '') . '>' . ((!is_numeric($blank_option)) ? $blank_option : "[All]") . '</option>';
        }

        while ($row = mysqli_fetch_array($res)) {
            $out .= '<option value="' . $row['id'] . '" ';
            $out .= ($selected == $row['id']) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['name']) . '</option>';
        }

        $out .= '</select>';

        return $out;
    }

    function makeCampaignDD($name, $selected, $css, $onchange, $blank_option = 1)
    {
        connectPXDB();

        $res = query("SELECT name, vici_campaign_id FROM campaigns WHERE `status`='active'");

        $out = '<select name="' . $name . '" id="' . $name . '" ';

        $out .= ($css) ? ' class="' . $css . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= '>';

        if ($blank_option) {
            $out .= '<option value="" ' . (($selected == '') ? ' SELECTED ' : '') . '>' . ((!is_numeric($blank_option)) ? $blank_option : "[All]") . '</option>';
        }

        while ($row = mysqli_fetch_array($res)) {
            $out .= '<option value="' . $row['vici_campaign_id'] . '" ';
            $out .= ($selected == $row['vici_campaign_id']) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['name']) . '</option>';
        }

        $out .= '</select>';

        return $out;
    }

    function makeDialerDD($name, $selected, $css, $onchange, $blank_option = 1)
    {
        $res = query("SELECT DISTINCT `agent_cluster_id` AS `dialer` FROM `sales` WHERE `agent_cluster_id` > 0");
        $out = '<select name="'.$name.'" id="'.$name.'" ';
        $out .= ($css)?' class="'.$css.'" ':'';
        $out .= ($onchange)?' onchange="'.$onchange.'" ':'';
        $out .= '>';
        if ($blank_option) {
            $out .= '<option value="" '.(($selected == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
        }
        while ($row = mysqli_fetch_array($res)) {
            $out .= '<option value="'.$row['dialer'].'" ';
            $out .= ($selected == $row['dialer'])?' SELECTED ':'';
            $out .= '>'.htmlentities($row['dialer']).'</option>';
        }
        $out .= '</select>';
        return $out;
    }

    function makeAreaCodeDD($name, $selected, $css, $onchange, $blank_option = 1) {
        $res = query("SELECT DISTINCT LEFT(`phone`, 3) AS `area_code` FROM `sales` WHERE `phone` <> ''");
        $out = '<select name="'.$name.'" id="'.$name.'" ';
        $out .= ($css)?' class="'.$css.'" ':'';
        $out .= ($onchange)?' onchange="'.$onchange.'" ':'';
        $out .= '>';
        if ($blank_option) {
            $out .= '<option value="" '.(($selected == '')?' SELECTED ':'').'>'.((!is_numeric($blank_option))?$blank_option:"[All]").'</option>';
        }
        while ($row = mysqli_fetch_array($res)) {
            $out .= '<option value="'.$row['area_code'].'" ';
            $out .= ($selected == $row['area_code'])?' SELECTED ':'';
            $out .= '>'.htmlentities($row['area_code']).'</option>';
        }
        $out .= '</select>';
        return $out;    }


        /**
    function makeTimebar($basename="time_", $mode=0, $selarr=null, $stack=false, $timestamp=0, $extra_attr="")
    {
        connectPXDB();
        $res = query("SELECT `name`, `vici_campaign_id` FROM campaigns WHERE `status`='active'");
        $out = '<select name="' . $name . '" id="' . $name . '" ';
        $out .= ($css) ? ' class="' . $css . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= '>';
        if ($blank_option) {
            $out .= '<option value="" ' . (($selected == '') ? ' SELECTED ' : '') . '>' . ((!is_numeric($blank_option)) ? $blank_option : "[All]") . '</option>';
        }
        while ($row = mysqli_fetch_array($res)) {
            $out .= '<option value="' . $row['vici_campaign_id'] . '" ';
            $out .= ($selected == $row['vici_campaign_id']) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['name']) . '</option>';
        }
        $out .= '</select>';
        return $out;
    }**/

    function makeNoFormsCampaignDD($name, $selected, $css, $onchange, $blank_option = 1)
    {
        connectPXDB();
        $res = query("SELECT DISTINCT(`c`.`name`), `c`.`id` FROM `campaigns` AS `c` WHERE `c`.`status`='active' AND `c`.`id` NOT IN (SELECT `campaign_id` FROM `custom_fields`) ORDER BY `c`.`name`");
        $out = '<select name="' . $name . '" id="' . $name . '" ';
        $out .= ($css) ? ' class="' . $css . '" ' : '';
        $out .= ($onchange) ? ' onchange="' . $onchange . '" ' : '';
        $out .= '>';
        if ($blank_option) {
            $out .= '<option value="" ' . (($selected == '') ? ' SELECTED ' : '') . '>' . ((!is_numeric($blank_option)) ? $blank_option : "[All]") . '</option>';
        }
        while ($row = mysqli_fetch_array($res)) {
            $out .= '<option value="' . $row['id'] . '" ';
            $out .= ($selected == $row['id']) ? ' SELECTED ' : '';
            $out .= '>' . htmlentities($row['name']) . '</option>';
        }
        $out .= '</select>';
        return $out;
    }

    function makeTimebar($basename = "time_", $mode = 0, $selarr = NULL, $stack = false, $timestamp = 0, $extra_attr = "")
    {
        ## Mode 0 = full
        ## Mode 1 = date
        ## mode 2 = hour
        ## $selarr = time array
        ## mode 0: Array(hours,minutes,month,day,year)
        ## mode 1: Array(month,day,year)
        ## mode 2: Array(hour,minute)
        ## stack = align hours, minutes on top of month, day, year for the presentation layer.
        if ($timestamp) {
            $selarr = array();
            switch ($mode) {
                case 0:
                    $selarr[0] = date("H", $timestamp); // hours
                    $selarr[1] = date("i", $timestamp); // mins
                    $selarr[2] = date("m", $timestamp); // month
                    $selarr[3] = date("d", $timestamp); // day
                    $selarr[4] = date("Y", $timestamp); // day
                    break;
                case 1:
                    $selarr[0] = date("m", $timestamp); // month
                    $selarr[1] = date("d", $timestamp); // day
                    $selarr[2] = date("Y", $timestamp); // year
                    break;
                case 2:
                    $selarr[0] = date("H", $timestamp); // hours
                    $selarr[1] = date("i", $timestamp); // mins
                    break;
            }
        }

        if ($mode == 0 || $mode == 2) {

            /// makeNumberDD($name,$sel,$start,$end,$inc,$zeropad,$tag_inject)
            echo makeNumberDD($basename . 'hour', (($selarr[0] % 12 == 0) ? 12 : ($selarr[0] % 12)), 1, 12, 1, 0, $extra_attr) . ' : ';    # Hours
            echo makeNumberDD($basename . 'min', $selarr[1], 0, 59, 1, 1, 0, $extra_attr);        # minutes
            echo '<select name="' . $basename . 'timemode' . '" ' . $extra_attr . ' id="' . $basename . 'timemode' . '"><option value="am"' . (($selarr[0] < 12) ? ' SELECTED' : '') . '>AM<option value="pm"' . (($selarr[0] >= 12 && $selarr[0] < 24) ? ' SELECTED' : '') . '>PM</select>';
        }

        if ($mode != 2) {
            echo ($mode == 0) ? ' &nbsp; ' : '';
            echo (($stack) ? '<br>' : '') . getMonthDD($basename . 'month', ((!$mode) ? $selarr[2] : $selarr[0]), $extra_attr) . '/' . getDayDD($basename . 'day', ((!$mode) ? $selarr[3] : $selarr[1]), $extra_attr) . '/' . getYearDD($basename . 'year', ((!$mode) ? $selarr[4] : $selarr[2]), $extra_attr);
        }
    }

    function makeHourDD($name, $sel, $class)
    {
        $out = '<select name="' . $name . '" id="' . $name . '" ';
        $out .= ($class) ? ' class="' . $class . '"' : '';
        $out .= '>';

        for ($x = 1; $x < 25; $x++) {
            $out .= '<option value="' . $x . '"';
            $out .= ($sel == $x) ? ' SELECTED' : '';
            $out .= '>';
            if ($x == 24) {
                $out .= 'Midnight';
            } elseif ($x == 12) {
                $out .= 'Noon';
            } else {
                $out .= ($x % 12) . (($x >= 12) ? ' PM' : ' AM');
            }
        }

        $out .= '</select>';
        return $out;
    }

    function makeNumberDD($name, $sel, $start, $end, $inc, $zeropad = false, $tag_inject = '', $blankfield = false)
    {
        $sel = intval($sel);

        $out = '<select name="' . $name . '" id="' . $name . '" ' . $tag_inject . ' >';

        $out .= ($blankfield) ? '<option value=""></option>' : '';

        for ($x = $start; $x <= $end; $x += $inc) {
            $out .= '<option value="' . (($zeropad && $x < 10) ? '0' . $x : $x) . '"';
            $out .= ($sel == $x) ? ' SELECTED ' : '';
            $out .= '>' . (($zeropad && $x < 10) ? ('0' . $x) : $x);
        }
        $out .= '</select>';

        return $out;
    }

    function getMonthDD($name, $sel, $extra_attr)
    {
        $out = '<select name="' . $name . '"  id="' . $name . '" ' . $extra_attr . ' >';
        for ($x = 1; $x <= 12; $x++) {
            $out .= '<option value="' . $x . '"';
            if ($x == $sel) {
                $out .= ' selected ';
            }
            $out .= '>' . $x . '</option>';
        }
        $out .= '</select>';
        return $out;
    }

    function getDayDD($name, $sel, $extra_attr)
    {
        $out = '<select name="' . $name . '"  id="' . $name . '" ' . $extra_attr . ' >';
        for ($x = 1; $x <= 31; $x++) {
            $out .= '<option value="' . $x . '"';
            if ($x == $sel) {
                $out .= ' selected ';
            }
            $out .= '>' . $x . '</option>';
        }
        $out .= '</select>';
        return $out;
    }

    function getYearDD($name, $sel, $extra_attr)
    {
        $out = '<select name="' . $name . '" id="' . $name . '" ' . $extra_attr . ' >';
        for ($x = 1900; $x < (date("Y") + 15); $x++) {
            $out .= '<option value="' . $x . '"';
            if ($x == $sel) {
                $out .= ' selected ';
            }
            $out .= '>' . $x . '</option>';
        }
        $out .= '</select>';
        return $out;
    }
