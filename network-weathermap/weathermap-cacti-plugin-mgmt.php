<?php

/** This file is from Weathermap version 0.98 */

chdir('../../');
require_once "./include/auth.php";
require_once "./include/config.php";

require_once $config["library_path"] . "/database.php";

// include the weathermap class so that we can get the version
require_once dirname(__FILE__)."/lib/globals.php";
require_once dirname(__FILE__)."/lib/cacti-plugin-mgmt.php";
require_once dirname(__FILE__)."/lib/cacti-plugin-common.php";

$i_understand_file_permissions_and_how_to_fix_them = false;

$action = "";
if (isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif (isset($_GET['action'])) {
    $action = $_GET['action'];
}

$plugin = new WeatherMapCactiManagementPlugin($config, $colors);

$plugin->dispatchRequest($action, $_REQUEST, null);

// vim:ts=4:sw=4: