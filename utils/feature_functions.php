<?php
    function checkAccess($priv_name) {
        if ($_SESSION['user']['priv'] >= 5) return true; else if ($_SESSION['user']['priv'] == 4) return ($_SESSION['features'][$priv_name] == 'yes') ? true : false; else return false;
    }
