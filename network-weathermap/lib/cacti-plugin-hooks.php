<?php

function weathermap_page_head()
{
    if (preg_match('/plugins\/weathermap\//', $_SERVER['REQUEST_URI'], $matches)) {
        ?>
        <link rel="stylesheet" type="text/css" media="screen" href="cacti-resources/weathermap.css" />
        <link rel="stylesheet" href="vendor/jquery-ui/themes/ui-lightness/jquery-ui.min.css" />
        <script src="vendor/jquery/dist/jquery.min.js"></script>
        <script src="vendor/jquery-ui/jquery-ui.min.js"></script>
    <?php
    }
}

function weathermap_page_title($t)
{
    if (!preg_match('/plugins\/weathermap\//', $_SERVER['REQUEST_URI'], $matches)) {
        return $t;
    }

    if (!preg_match('/plugins\/weathermap\/weathermap-cacti-plugin.php\?action=viewmap&id=([^&]+)/', $_SERVER['REQUEST_URI'], $matches)) {
        return $t;
    }

    $mapid = $matches[1];
    if (preg_match('/^\d+$/', $mapid)) {
        $title = db_fetch_cell("SELECT titlecache from weathermap_maps where ID=" . intval($mapid));
    } else {
        $title = db_fetch_cell("SELECT titlecache from weathermap_maps where filehash='" . mysql_real_escape_string($mapid) . "'");
    }
    if (isset($title)) {
        $t .= " - $title";
    }

    return ($t);
}

function weathermap_top_graph_refresh($refresh)
{
    if (basename($_SERVER["PHP_SELF"]) != "weathermap-cacti-plugin.php") {
        return $refresh;
    }

    // if we're cycling maps, then we want to handle reloads ourselves, thanks
    if (isset($_GET["action"]) && $_GET["action"] == 'viewmapcycle') {
        return(86400);
    }
    return ($refresh);
}

function weathermap_config_settings()
{
    global $tabs, $settings;
    $tabs["misc"] = "Misc";

    $temp = array(
        "weathermap_header" => array(
            "friendly_name" => "Network Weathermap",
            "method" => "spacer",
        ),
        "weathermap_pagestyle" => array(
            "friendly_name" => "Page style",
            "description" => "How to display multiple maps.",
            "method" => "drop_array",
            "array" => array(0 => "Thumbnail Overview", 1 => "Full Images", 2 => "Show Only First")
        ),
        "weathermap_thumbsize" => array(
            "friendly_name" => "Thumbnail Maximum Size",
            "description" => "The maximum width or height for thumbnails in thumbnail view, in pixels. Takes effect after the next poller run.",
            "method" => "textbox",
            "max_length" => 5,
        ),
        "weathermap_cycle_refresh" => array(
            "friendly_name" => "Refresh Time",
            "description" => "How often to refresh the page in Cycle mode. Automatic makes all available maps fit into one poller cycle (normally 5 minutes).",
            "method" => "drop_array",
            "array" => array(0 => "Automatic", 5 => "5 Seconds",
                15 => '15 Seconds',
                30 => '30 Seconds',
                60 => '1 Minute',
                120 => '2 Minutes',
                300 => '5 Minutes',
            )
        ),
        "weathermap_output_format" => array(
            "friendly_name" => "Output Format",
            "description" => "What format do you prefer for the generated map images and thumbnails?",
            "method" => "drop_array",
            "array" => array('png' => "PNG (default)",
                'jpg' => "JPEG",
                'gif' => 'GIF'
            )
        ),
        "weathermap_render_period" => array(
            "friendly_name" => "Map Rendering Interval",
            "description" => "How often do you want Weathermap to recalculate it's maps? You should not touch this unless you know what you are doing! It is mainly needed for people with non-standard polling setups.",
            "method" => "drop_array",
            "array" => array(-1 => "Never (manual updates)",
                0 => "Every Poller Cycle (default)",
                2 => 'Every 2 Poller Cycles',
                3 => 'Every 3 Poller Cycles',
                4 => 'Every 4 Poller Cycles',
                5 => 'Every 5 Poller Cycles',
                10 => 'Every 10 Poller Cycles',
                12 => 'Every 12 Poller Cycles',
                24 => 'Every 24 Poller Cycles',
                36 => 'Every 36 Poller Cycles',
                48 => 'Every 48 Poller Cycles',
                72 => 'Every 72 Poller Cycles',
                288 => 'Every 288 Poller Cycles',
            ),
        ),

        "weathermap_all_tab" => array(
            "friendly_name" => "Show 'All' Tab",
            "description" => "When using groups, add an 'All Maps' tab to the tab bar.",
            "method" => "drop_array",
            "array" => array(0 => "No (default)", 1 => "Yes")
        ),
        "weathermap_map_selector" => array(
            "friendly_name" => "Show Map Selector",
            "description" => "Show a combo-box map selector on the full-screen map view.",
            "method" => "drop_array",
            "array" => array(0 => "No", 1 => "Yes (default)")
        ),
        "weathermap_quiet_logging" => array(
            "friendly_name" => "Quiet Logging",
            "description" => "By default, even in LOW level logging, Weathermap logs normal activity. This makes it REALLY log only errors in LOW mode.",
            "method" => "drop_array",
            "array" => array(0 => "Chatty (default)", 1 => "Quiet")
        ),
        "weathermap_debug_data_only" => array(
            "friendly_name" => "Debug ReadData() Only",
            "description" => "With DEBUG logging on, only log data-reading information, not map-drawing etc etc",
            "method" => "drop_array",
            "array" => array(0 => "No", 1 => "Yes (default)")
        )
    );

    if (isset($settings["misc"])) {
        $settings["misc"] = array_merge($settings["misc"], $temp);
    } else {
        $settings["misc"] = $temp;
    }
}

function weathermap_setup_table()
{
    global $database_default;
    // include_once $config["library_path"] . DIRECTORY_SEPARATOR . "database.php";
    $dbversion = read_config_option("weathermap_db_version");

    $myversioninfo = plugin_weathermap_version();
    $myversion = $myversioninfo['version'];

    cacti_log("WM setup_table $myversion vs $dbversion\n", true, "WEATHERMAP");

    // only bother with all this if it's a new install, a new version, or we're in a development version
    // - saves a handful of db hits per request!
    if (strstr($myversion, "dev")===false && $dbversion != "" && $dbversion == $myversion) {
        return;
    }

    cacti_log("WM setup_table Creating Tables\n", true, "WEATHERMAP");

    $tables = weathermap_get_table_list();
    $sql = array();

    if (!in_array('weathermap_maps', $tables)) {
        $sql = weathermap_setup_maps_table($sql);
    } else {
        $sql = weathermap_update_maps_table($sql);
    }

    if (!in_array('weathermap_auth', $tables)) {
        $sql = weathermap_setup_auth_table($sql);
    }

    if (!in_array('weathermap_groups', $tables)) {
        $sql = weathermap_setup_groups_table($sql);
    }

    if (!in_array('weathermap_settings', $tables)) {
        $sql = weathermap_setup_settings_table($sql);
    }

    if (!in_array('weathermap_data', $tables)) {
        $sql = weathermap_setup_data_table($sql);
    } else {
        $sql = weathermap_update_data_table($sql);
    }

    $sql[] = "update weathermap_maps set sortorder=id where sortorder is null;";
    $sql[] = "update weathermap_maps set filehash=LEFT(MD5(concat(id,configfile,rand())),20) where filehash = '';";

    // create the settings entries, if necessary
    $sql = weathermap_setup_settings($sql, $myversion);

    if (!empty($sql)) {
        foreach ($sql as $s) {
            db_execute($s);
        }
    }
}

/**
 * @return array
 */
function weathermap_get_table_list()
{
    $sql = "show tables";
    $result = db_fetch_assoc($sql) or die (mysql_error());

    $tables = array();
    $sql = array();

    foreach ($result as $index => $arr) {
        foreach ($arr as $t) {
            $tables[] = $t;
        }
    }
    return $tables;
}

/**
 * @param $sql
 * @param $myversion
 * @return array
 */
function weathermap_setup_settings($sql, $myversion)
{
    $defaults = array(
        "weathermap_pagestyle" => 0,
        "weathermap_cycle_refresh" => 0,
        "weathermap_render_period" => 0,
        "weathermap_quiet_logging" => 0,
        "weathermap_render_counter" => 0,
        "weathermap_output_format" => "png",
        "weathermap_thumbsize" => 250,
        "weathermap_map_selector" => 1,
        "weathermap_all_tab" => 0,
        "weathermap_debug_data_only" => 1
    );

    foreach ($defaults as $key => $defaultValue) {
        $current = read_config_option($key);
        if ($current == '') {
            $sql[] = sprintf("replace into settings values('%s','%s')", $key, $defaultValue);
        }
    }

    // update the version, so we can skip this next time
    $sql[] = "replace into settings values('weathermap_db_version','$myversion')";

    // patch up the sortorder for any maps that don't have one.
    $sql[] = "update weathermap_maps set sortorder=id where sortorder is null or sortorder=0;";

    return $sql;
}

/**
 * @param $sql
 * @param $database_default
 * @return array
 */
function weathermap_update_data_table($sql)
{
    global $database_default;

    $colsql = "show columns from weathermap_data from " . $database_default;
    $result = mysql_query($colsql) or die (mysql_error());
    $found_ldi = false;

    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        if ($row['Field'] == 'local_data_id') {
            $found_ldi = true;
        }
    }

    if (!$found_ldi) {
        $sql[] = "alter table weathermap_data add local_data_id int(11) NOT NULL default 0 after sequence";
        $sql[] = "alter table weathermap_data add index ( `local_data_id` )";
        # if there is existing data without a local_data_id, ditch it
        $sql[] = "delete from weathermap_data";
        return $sql;
    }
    return $sql;
}

/**
 * @param $sql
 * @return array
 */
function weathermap_setup_data_table($sql)
{
    $sql[] = "CREATE TABLE IF NOT EXISTS weathermap_data (id int(11) NOT NULL auto_increment,
                rrdfile varchar(255) NOT NULL, data_source_name varchar(19) NOT NULL,
                  last_time int(11) NOT NULL, last_value varchar(255) NOT NULL,
                last_calc varchar(255) NOT NULL, sequence int(11) NOT NULL, local_data_id int(11) NOT NULL DEFAULT 0, PRIMARY KEY  (id), KEY rrdfile (rrdfile),
                  KEY local_data_id (local_data_id), KEY data_source_name (data_source_name) ) ENGINE=MyISAM;";
    return $sql;
}

/**
 * @param $sql
 * @return array
 */
function weathermap_setup_settings_table($sql)
{
    $sql[] = "CREATE TABLE weathermap_settings (
                id int(11) NOT NULL auto_increment,
                mapid int(11) NOT NULL default '0',
                groupid int(11) NOT NULL default '0',
                optname varchar(128) NOT NULL default '',
                optvalue varchar(128) NOT NULL default '',
                PRIMARY KEY  (id)
            ) ENGINE=MyISAM;";
    return $sql;
}

/**
 * @param $sql
 * @return array
 */
function weathermap_setup_groups_table($sql)
{
    $sql[] = "CREATE TABLE  weathermap_groups (
                `id` INT(11) NOT NULL auto_increment,
                `name` VARCHAR( 128 ) NOT NULL default '',
                `sortorder` INT(11) NOT NULL default 0,
                PRIMARY KEY (id)
                ) ENGINE=MyISAM;";
    $sql[] = "INSERT INTO weathermap_groups (id,name,sortorder) VALUES (1,'Weathermaps',1)";
    return $sql;
}

/**
 * @param $sql
 * @return array
 */
function weathermap_setup_auth_table($sql)
{
    $sql[] = "CREATE TABLE weathermap_auth (
                userid mediumint(9) NOT NULL default '0',
                mapid int(11) NOT NULL default '0'
            ) ENGINE=MyISAM;";
    return $sql;
}

/**
 * @param $database_default
 * @param $sql
 * @return array
 */
function weathermap_update_maps_table($sql)
{
    global $database_default;

    $columnListSQL = "show columns from weathermap_maps from " . $database_default;
    $result = mysql_query($columnListSQL) or die (mysql_error());

    $maps_field_changes = array(
        'sortorder' => array("alter table weathermap_maps add sortorder int(11) NOT NULL default 0 after id"),
        'filehash' => array("alter table weathermap_maps add filehash varchar(40) NOT NULL default '' after titlecache"),
        'warncount' => array("alter table weathermap_maps add warncount int(11) NOT NULL default 0 after filehash"),
        'config' => array("alter table weathermap_maps add config text NOT NULL after warncount"),
        'thumb_width' => array(
            "alter table weathermap_maps add thumb_width int(11) NOT NULL default 0 after config",
            "alter table weathermap_maps add thumb_height int(11) NOT NULL default 0 after thumb_width",
            "alter table weathermap_maps add schedule varchar(32) NOT NULL default '*' after thumb_height",
            "alter table weathermap_maps add archiving set('on','off') NOT NULL default 'off' after schedule"
        ),
        'group_id' => array(
            "alter table weathermap_maps add group_id int(11) NOT NULL default 1 after sortorder",
            "ALTER TABLE `weathermap_settings` ADD `groupid` INT NOT NULL DEFAULT '0' AFTER `mapid`"
        ),
        'debug' => array(
            "alter table weathermap_maps add runtime double NOT NULL default 0 after warncount",
            "alter table weathermap_maps add lastrun datetime after runtime",
            "alter table weathermap_maps add debug set('on','off','once') NOT NULL default 'off' after warncount;"
        )
    );

    $maps_field_fields = array_keys($maps_field_changes);

    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        // if the table already has the field, remove that entry from the potential changes list
        if (in_array($row['Field'], $maps_field_fields)) {
            unset($maps_field_changes[$row['Field']]);
        }
    }
    foreach ($maps_field_changes as $change_list) {
        foreach ($change_list as $change) {
            $sql [] = $change;
        }
    }
    return array($sql);
}

/**
 * @param $sql
 * @return array
 */
function weathermap_setup_maps_table($sql)
{
    $sql[] = "CREATE TABLE weathermap_maps (
                id int(11) NOT NULL auto_increment,
                sortorder int(11) NOT NULL default 0,
                group_id int(11) NOT NULL default 1,
                active set('on','off') NOT NULL default 'on',
                configfile text NOT NULL,
                imagefile text NOT NULL,
                htmlfile text NOT NULL,
                titlecache text NOT NULL,
                filehash varchar (40) NOT NULL default '',
                warncount int(11) NOT NULL default 0,
                debug set('on','off','once') NOT NULL default 'off',
                runtime double NOT NULL default 0,
                lastrun datetime,
                config text NOT NULL,
                thumb_width int(11) NOT NULL default 0,
                thumb_height int(11) NOT NULL default 0,
                schedule varchar(32) NOT NULL default '*',
                archiving set('on','off') NOT NULL default 'off',
                PRIMARY KEY  (id)
            ) ENGINE=MyISAM;";

    return $sql;
}

function weathermap_config_arrays()
{
    global $menu;

    if (function_exists('api_plugin_register_realm')) {
        api_plugin_register_realm('weathermap', 'weathermap-cacti-plugin.php', 'Plugin -> Weathermap: View', 1);
        api_plugin_register_realm('weathermap', 'weathermap-cacti-plugin-mgmt.php', 'Plugin -> Weathermap: Configure/Manage', 1);
        api_plugin_register_realm('weathermap', 'weathermap-cacti-plugin-editor.php', 'Plugin -> Weathermap: Edit Maps', 1);
    }

    $menu["Management"]['plugins/weathermap/weathermap-cacti-plugin-mgmt.php'] = array(
        'plugins/weathermap/weathermap-cacti-plugin-mgmt.php' => "Weathermaps",
    );
}

function weathermap_show_tab()
{
    global $config, $user_auth_realm_filenames;
    $realm_id2 = 0;

    if (isset($user_auth_realm_filenames[basename('weathermap-cacti-plugin.php')])) {
        $realm_id2 = $user_auth_realm_filenames[basename('weathermap-cacti-plugin.php')];
    }

    $tabstyle = intval(read_config_option("superlinks_tabstyle"));
    $userid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

    if ((db_fetch_assoc("select user_auth_realm.realm_id from user_auth_realm where user_auth_realm.user_id='" . $userid . "' and user_auth_realm.realm_id='$realm_id2'")) || (empty($realm_id2))) {
        if ($tabstyle>0) {
            $prefix="s_";
        } else {
            $prefix="";
        }

        echo '<a href="' . $config['url_path'] . 'plugins/weathermap/weathermap-cacti-plugin.php"><img src="' . $config['url_path'] . 'plugins/weathermap/cacti-resources/img/'.$prefix.'tab_weathermap';

        // if we're ON a weathermap page, print '_red'
        if (preg_match('/plugins\/weathermap\/weathermap-cacti-plugin.php/', $_SERVER['REQUEST_URI'], $matches)) {
            echo "_red";
        }
        echo '.gif" alt="weathermap" align="absmiddle" border="0"></a>';
    }

    weathermap_setup_table();
}

function weathermap_draw_navigation_text($nav)
{
    // I don't really know how this works! There needs to be one entry for each action though.

    $nav["weathermap-cacti-plugin.php:"] = array("title" => "Weathermap", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin.php", "level" => "1");
    $nav["weathermap-cacti-plugin.php:viewmap"] = array("title" => "Weathermap", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin.php", "level" => "1");
    $nav["weathermap-cacti-plugin.php:viewmapcycle"] = array("title" => "Weathermap", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin.php", "level" => "1");
    $nav["weathermap-cacti-plugin.php:viewimage"] = array("title" => "Weathermap", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin.php", "level" => "1");
    $nav["weathermap-cacti-plugin.php:viewthumb"] = array("title" => "Weathermap", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin.php", "level" => "1");

    $nav["weathermap-cacti-plugin-mgmt.php:"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");

    $nav["weathermap-cacti-plugin-mgmt.php:viewconfig"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:addmap"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:editmap"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:editor"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");

    $nav["weathermap-cacti-plugin-mgmt.php:perms_edit"] = array("title" => "Edit Permissions", "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:", "url" => "", "level" => "2");
    $nav["weathermap-cacti-plugin-mgmt.php:addmap_picker"] = array("title" => "Add Map", "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:", "url" => "", "level" => "2");
    $nav["weathermap-cacti-plugin-mgmt.php:map_settings"] = array("title" => "Map Settings", "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:", "url" => "", "level" => "2");
    $nav["weathermap-cacti-plugin-mgmt.php:map_settings_form"] = array("title" => "Map Settings", "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:", "url" => "", "level" => "2");
    $nav["weathermap-cacti-plugin-mgmt.php:map_settings_delete"] = array("title" => "Map Settings", "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:", "url" => "", "level" => "2");
    $nav["weathermap-cacti-plugin-mgmt.php:map_settings_update"] = array("title" => "Map Settings", "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:", "url" => "", "level" => "2");
    $nav["weathermap-cacti-plugin-mgmt.php:map_settings_add"] = array("title" => "Map Settings", "mapping" => "index.php:,weathermap-cacti-plugin-mgmt.php:", "url" => "", "level" => "2");

    $nav["weathermap-cacti-plugin-mgmt.php:perms_add_user"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:perms_delete_user"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:delete_map"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:move_map_down"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:move_map_up"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:move_group_down"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:move_group_up"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:group_form"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:group_update"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:activate_map"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:deactivate_map"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:rebuildnow"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:rebuildnow2"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");

    $nav["weathermap-cacti-plugin-mgmt.php:chgroup"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:chgroup_update"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:groupadmin"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");
    $nav["weathermap-cacti-plugin-mgmt.php:groupadmin_delete"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");

    $nav["weathermap-cacti-plugin-mgmt.php:settings_dump"] = array("title" => "Weathermap Management", "mapping" => "index.php:", "url" => "weathermap-cacti-plugin-mgmt.php", "level" => "1");

    return $nav;
}

// figure out if this poller run is hitting the 'cron' entry for any maps.
function weathermap_poller_top()
{
    global $weathermap_poller_start_time;

    $now = time();

    // round to the nearest minute, since that's all we need for the crontab-style stuff
    $weathermap_poller_start_time = $now - ($now%60);

}

function weathermap_poller_output($rrd_update_array)
{
    global $config;

    $logging = read_config_option("log_verbosity");

    if ($logging >= POLLER_VERBOSITY_DEBUG) {
        cacti_log("WM poller_output: STARTING\n", true, "WEATHERMAP");
    }

    // partially borrowed from Jimmy Conner's THold plugin.
    // (although I do things slightly differently - I go from filenames, and don't use the poller_interval)

    // new version works with *either* a local_data_id or rrdfile in the weathermap_data table, and returns BOTH
    $requiredlist = db_fetch_assoc("select distinct weathermap_data.id, weathermap_data.last_value, weathermap_data.last_time, weathermap_data.data_source_name, data_template_data.data_source_path, data_template_data.local_data_id, data_template_rrd.data_source_type_id from weathermap_data, data_template_data, data_template_rrd where weathermap_data.local_data_id=data_template_data.local_data_id and data_template_rrd.local_data_id=data_template_data.local_data_id and weathermap_data.local_data_id<>0;");

    $path_rra = $config["rra_path"];

    # especially on Windows, it seems that filenames are not reliable (sometimes \ and sometimes / even though path_rra is always /) .
    # let's make an index from local_data_id to filename, and then use local_data_id as the key...

    foreach (array_keys($rrd_update_array) as $key) {
        if (isset( $rrd_update_array[$key]['times']) && is_array($rrd_update_array[$key]['times'])) {
            $knownfiles[ $rrd_update_array[$key]["local_data_id"] ] = $key;
        }
    }

    foreach ($requiredlist as $required) {
        $file = str_replace("<path_rra>", $path_rra, $required['data_source_path']);
        $dsname = $required['data_source_name'];
        $local_data_id = $required['local_data_id'];

        if (isset($knownfiles[$local_data_id])) {
            $file2 = $knownfiles[$local_data_id];
            if ($file2 != '') {
                $file = $file2;
            }
        }

        if ($logging >= POLLER_VERBOSITY_DEBUG) {
            cacti_log("WM poller_output: Looking for $file ($local_data_id) (".$required['data_source_path'].")\n", true, "WEATHERMAP");
        }

        // TODO - is all this really necessary?
        if (isset($rrd_update_array[$file])
            && is_array($rrd_update_array[$file])
            && isset($rrd_update_array[$file]['times'])
            && is_array($rrd_update_array[$file]['times'])
            && isset($rrd_update_array{$file}['times'][key($rrd_update_array[$file]['times'])]{$dsname})) {
            weathermap_process_value($rrd_update_array, $file, $dsname, $required, $logging);
        }
    }

    if ($logging >= POLLER_VERBOSITY_DEBUG) {
        cacti_log("WM poller_output: ENDING\n", true, "WEATHERMAP");
    }

    return $rrd_update_array;
}

/**
 * @param $rrd_update_array
 * @param $file
 * @param $dsname
 * @param $required
 * @param $logging
 */
function weathermap_process_value($rrd_update_array, $file, $dsname, $required, $logging)
{
    $value = $rrd_update_array{$file}['times'][key($rrd_update_array[$file]['times'])]{$dsname};
    $time = key($rrd_update_array[$file]['times']);

    if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
        cacti_log("WM poller_output: Got one! $file:$dsname -> $time $value\n", true, "WEATHERMAP");
    }

    $period = $time - $required['last_time'];
    $lastval = $required['last_value'];

    // if the new value is a NaN, we'll give 0 instead, and pretend it didn't happen from the point
    // of view of the counter etc. That way, we don't get those enormous spikes. Still doesn't deal with
    // reboots very well, but it should improve it for drops.
    if ($value == 'U') {
        $newvalue = 0;
        $newlastvalue = $lastval;
        $newtime = $required['last_time'];
    } else {
        $newlastvalue = $value;
        $newtime = $time;

        switch ($required['data_source_type_id']) {
            case 1: //GAUGE
                $newvalue = $value;
                break;
            case 2: //COUNTER
                if ($value >= $lastval) {
                    // Everything is normal
                    $newvalue = $value - $lastval;
                } else {
                    // Possible overflow, see if its 32bit or 64bit
                    if ($lastval > 4294967295) {
                        $newvalue = (18446744073709551615 - $lastval) + $value;
                    } else {
                        $newvalue = (4294967295 - $lastval) + $value;
                    }
                }
                $newvalue = $newvalue / $period;
                break;
            case 3: //DERIVE
                $newvalue = ($value - $lastval) / $period;
                break;
            case 4: //ABSOLUTE
                $newvalue = $value / $period;
                break;
            default: // do something somewhat sensible in case something odd happens
                $newvalue = $value;
                wm_warn("poller_output found an unknown data_source_type_id for $file:$dsname");
                break;
        }
    }

    db_execute("UPDATE weathermap_data SET last_time=$newtime, last_calc='$newvalue', last_value='$newlastvalue',sequence=sequence+1  where id = " . $required['id']);

    if ($logging >= POLLER_VERBOSITY_DEBUG) {
        cacti_log("WM poller_output: Final value is $newvalue (was $lastval, period was $period)\n", true, "WEATHERMAP");
    }
}

function weathermap_poller_bottom()
{
    global $config;
    global $WEATHERMAP_VERSION;

    include_once $config["library_path"] . DIRECTORY_SEPARATOR . "database.php";
    include_once "poller-common.php";

    weathermap_setup_table();

    $renderperiod = intval(read_config_option("weathermap_render_period"));
    $rendercounter = intval(read_config_option("weathermap_render_counter"));
    $quietlogging = read_config_option("weathermap_quiet_logging");

    if ($renderperiod<0) {
        // manual updates only
        if ($quietlogging==0) {
            cacti_log("Weathermap $WEATHERMAP_VERSION - no updates ever", true, "WEATHERMAP");
        }
        return;
    } else {
        // if we're due, run the render updates
        if (( $renderperiod == 0) || ( ($rendercounter % $renderperiod) == 0)) {
            weathermap_run_maps(dirname(__FILE__)."/..");
        } else {
            if ($quietlogging==0) {
                cacti_log("Weathermap $WEATHERMAP_VERSION - no update in this cycle ($rendercounter)", true, "WEATHERMAP");
            }
        }

        // increment the counter
        $newcount = ($rendercounter+1)%1000;
        db_execute("replace into settings values('weathermap_render_counter',".$newcount.")");
    }
}
