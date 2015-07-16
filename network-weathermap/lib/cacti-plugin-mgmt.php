<?php

require_once "WeatherMapUIBase.class.php";

class WeatherMapCactiManagementPlugin extends WeatherMapUIBase
{
    public $config;
    public $configPath;
    public $colours;
    public $cactiBasePath;

    public $i_understand_file_permissions_and_how_to_fix_them;

    public $commands = array(
        'groupadmin_delete' => array('handler' => 'handleGroupDelete', 'args' => array(array("id","int"))),
        'groupadmin' => array('handler' => 'handleGroupSelect', 'args' => array()),
        'group_form' => array('handler' => 'handleGroupForm', 'args' => array(array("id","int"))),
        'group_update' => array('handler' => 'handleGroupUpdate', 'args' => array(array("id","int"),array("gname","non-empty-string"))),
        'move_group_up' => array('handler' => 'handleGroupOrderUp', 'args' => array(array("id","int"),array("order","int"))),
        'move_group_down' => array('handler' => 'handleGroupOrderDown', 'args' => array(array("id","int"),array("order","int"))),

        'chgroup_update' => array('handler' => 'handleMapChangeGroup', 'args' => array(array("map_id","int"),array("new_group","int"))),
        'chgroup' => array('handler' => 'handleMapGroupChangeForm', 'args' => array(array("id","int"))),

        'map_settings_delete' => array('handler' => 'handleMapSettingsDelete', 'args' => array(array("mapid","int"),array("id","int"))),
        'map_settings_form' => array('handler' => 'handleMapSettingsForm', 'args' => array(array("mapid","int"))),
        'map_settings' => array('handler' => 'handleMapSettingsPage', 'args' => array(array("id","int"))),
        'save' => array('handler' => 'handleMapSettingsSave', 'args' => array(array("mapid","int"),array("id","int"),array("name","non-empty-string"),array("value","string"))),

        'perms_add_user' => array('handler' => 'handlePermissionsAddUser', 'args' => array(array("mapid","int"),array("userid","int"))),
        'perms_delete_user' => array('handler' => 'handlePermissionsDeleteUser', 'args' => array(array("mapid","int"),array("userid","int"))),
        'perms_edit' => array('handler' => 'handlePermissionsPage', 'args' => array(array("id","int"))),

        'delete_map' => array('handler' => 'handleDeleteMap', 'args' => array(array("id","int"))),
        'deactivate_map' => array('handler' => 'handleDeactivateMap', 'args' => array(array("id","int"))),
        'activate_map' => array('handler' => 'handleActivateMap', 'args' => array(array("id","int"))),

        'addmap' => array('handler' => 'handleMapListAdd', 'args' => array(array("file","mapfile"))),
        'addmap_picker' => array('handler' => 'handleMapPicker', 'args' => array(array("show_all", "bool"))),

        'move_map_up' => array('handler' => 'handleMapOrderUp', 'args' => array(array("id","int"),array("order","int"))),
        'move_map_down' => array('handler' => 'handleMapOrderDown', 'args' => array(array("id","int"),array("order","int"))),

        'viewconfig' => array('handler' => 'handleViewConfig', 'args' => array( array("file","mapfile") )),

        'rebuildnow' => array('handler' => 'handleRebuildNowStep1', 'args' => array()),
        'rebuildnow2' => array('handler' => 'handleRebuildNowStep2', 'args' => array()),
        'settingsdump' => array('handler' => 'handleDumpSettings', 'args' => array()),
        ':: DEFAULT ::' => array('handler' => 'handleManagementMainScreen', 'args' => array())
    );

    public function __construct($config, $colours)
    {
        $this->config = $config;
        $this->colours = $colours;
        $this->configPath = realpath(dirname(__FILE__).'/../configs');
        $this->cactiBasePath = $config["base_path"];
        $this->i_understand_file_permissions_and_how_to_fix_them = false;
    }

    /**
     */
    public function handleManagementMainScreen($request, $appObject)
    {
        WMCactiAPI::pageTopConsole();
        // $this->wmMapManagementList4();
        $this->wmMapManagementList();

        wmGenerateFooterLinks();
        WMCactiAPI::pageBottom();
    }

    /**
     */
    public function handleDumpSettings($request, $appObject)
    {
        WMCactiAPI::pageTopConsole();

        $SQL = "select * from settings where name like 'weathermap_%' order by name";

        $queryResults = db_fetch_assoc($SQL);

        print "<table>";

        foreach ($queryResults as $setting) {
            printf(
                "<tr><th>%s</th><td>%s</td></tr>\n",
                htmlspecialchars($setting['name']),
                htmlspecialchars($setting['value'])
            );
        }

        print "</table>";

        wmGenerateFooterLinks();
        WMCactiAPI::pageBottom();
    }

    /**
     */
    public function handleRebuildNowStep2($request, $appObject)
    {
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "all.php";
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "poller-common.php";

        WMCactiAPI::pageTopConsole();
        print "<h3>Rebuilding all maps</h3><strong>NOTE: Because your Cacti poller process probably doesn't run as ";
        print "the same user as your webserver, it's possible this will fail with file permission problems even ";
        print "though the normal poller process runs fine. In some situations, it MAY have memory_limit problems, if ";
        print "your mod_php/ISAPI module uses a different php.ini to your command-line PHP.</strong><hr><pre>";

        weathermap_run_maps(realpath(dirname(__FILE__)."/.."));

        print "</pre>";
        print "<hr /><h3>Done.</h3>";
        WMCactiAPI::pageBottom();
    }

    /**
     */
    public function handleRebuildNowStep1($request, $appObject)
    {
        WMCactiAPI::pageTopConsole();

        print "<h3>REALLY Rebuild all maps?</h3><strong>NOTE: Because your Cacti poller process probably doesn't run ";
        print "as the same user as your webserver, it's possible this will fail with file permission problems even ";
        print "though the normal poller process runs fine. In some situations, it MAY have memory_limit problems, if";
        print " your mod_php/ISAPI module uses a different php.ini to your command-line PHP.</strong><hr>";

        print "<p>It is recommended that you don't use this feature, ";
        print "unless you understand and accept the problems it may cause.</p>";

        print "<h4><a href=\"weathermap-cacti-plugin-mgmt.php?action=rebuildnow2\">YES</a></h4>";
        print "<h1><a href=\"weathermap-cacti-plugin-mgmt.php\">NO</a></h1>";
        WMCactiAPI::pageBottom();
    }

    /**
     * @param $request
     */
    public function handleMapSettingsForm($request, $appObject)
    {
        if (isset($request['mapid']) && is_numeric($request['mapid'])) {
            WMCactiAPI::pageTopConsole();

            if (isset($request['id']) && is_numeric($request['id'])) {
                $this->wmuiMapSettingsForm(intval($request['mapid']), intval($request['id']));
            } else {
                $this->wmuiMapSettingsForm(intval($request['mapid']));
            }

            wmGenerateFooterLinks();
            WMCactiAPI::pageBottom();
        }
    }

    /**
     * @param $request
     */
    public function handleMapSettingsSave($request, $appObject)
    {
        $mapid = null;
        $settingid = null;
        $name = '';
        $value = '';

        if (isset($request['mapid']) && is_numeric($request['mapid'])) {
            $mapid = intval($request['mapid']);
        }

        if (isset($request['id']) && is_numeric($request['id'])) {
            $settingid = intval($request['id']);
        }

        if (isset($request['name']) && $request['name']) {
            $name = $request['name'];
        }

        if (isset($request['value']) && $request['value']) {
            $value = $request['value'];
        }

        if (!is_null($mapid) && $settingid == 0) {
            // create setting
            $this->wmMapSettingAdd($mapid, $name, $value);
        } elseif (!is_null($mapid) && !is_null($settingid)) {
            // update setting
            $this->wmMapSettingUpdate($mapid, $settingid, $name, $value);
        }
        header("Location: weathermap-cacti-plugin-mgmt.php?action=map_settings&id=" . $mapid);
    }

    /**
     * @param $request
     */
    public function handleMapSettingsDelete($request, $appObject)
    {
        $mapid = null;
        $settingid = null;
        if (isset($request['mapid']) && is_numeric($request['mapid'])) {
            $mapid = intval($request['mapid']);
        }
        if (isset($request['id']) && is_numeric($request['id'])) {
            $settingid = intval($request['id']);
        }

        if (!is_null($mapid) && !is_null($settingid)) {
            // create setting
            $this->wmMapSettingDelete($mapid, $settingid);
        }
        header("Location: weathermap-cacti-plugin-mgmt.php?action=map_settings&id=" . $mapid);
    }

    /**
     * @param $request
     */
    public function handleMapGroupChangeForm($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id'])) {
            WMCactiAPI::pageTopConsole();
            $this->wmuiMapGroupChangePage(intval($request['id']));
            WMCactiAPI::pageBottom();
        } else {
            print "Something got lost back there.";
        }
    }

    /**
     * @param $request
     */
    public function handleMapChangeGroup($request, $appObject)
    {
        $mapid = -1;
        $groupid = -1;

        if (isset($request['map_id']) && is_numeric($request['map_id'])) {
            $mapid = intval($request['map_id']);
        }
        if (isset($request['new_group']) && is_numeric($request['new_group'])) {
            $groupid = intval($request['new_group']);
        }

        if (($groupid > 0) && ($mapid >= 0)) {
            $this->wmMapSetGroup($mapid, $groupid);
        }

        header("Location: weathermap-cacti-plugin-mgmt.php");
    }

    /**
     */
    public function handleGroupSelect($request, $appObject)
    {
        WMCactiAPI::pageTopConsole();
        $this->wmuiGroupList();
        wmGenerateFooterLinks();
        WMCactiAPI::pageBottom();
    }

    /**
     * @param $request
     */
    public function handleGroupForm($request, $appObject)
    {
        $id = -1;

        WMCactiAPI::pageTopConsole();
        if (isset($request['id']) && is_numeric($request['id'])) {
            $id = intval($request['id']);
        }

        if ($id >= 0) {
            $this->wmuiGroupForm($id);
        }

        wmGenerateFooterLinks();
        WMCactiAPI::pageBottom();
    }

    /**
     * @param $request
     */
    public function handleGroupDelete($request, $appObject)
    {
        $id = -1;

        if (isset($request['id']) && is_numeric($request['id'])) {
            $id = intval($request['id']);
        }

        if ($id >= 1) {
            $this->wmGroupDelete($id);
        }
        header("Location: weathermap-cacti-plugin-mgmt.php?action=groupadmin");
    }

    /**
     * @param $request
     */
    public function handleGroupUpdate($request, $appObject)
    {
        $id = -1;
        $newname = "";
        if (isset($request['id']) && is_numeric($request['id'])) {
            $id = intval($request['id']);
        }
        if (isset($request['gname']) && (strlen($request['gname']) > 0)) {
            $newname = $request['gname'];
        }

        if ($id >= 0 && $newname != "") {
            $this->wmGroupRename($id, $newname);
        }
        if ($id < 0 && $newname != "") {
            $this->wmGroupAdd($newname);
        }
        header("Location: weathermap-cacti-plugin-mgmt.php?action=groupadmin");
    }

    ///////////////////////////////////////////////////////////////////////////



    // Repair the sort order column (for when something is deleted or inserted, or moved between groups)
    // our primary concern is to make the sort order consistent, rather than any special 'correctness'
    public function wmMapResort()
    {
        $list = db_fetch_assoc("select * from weathermap_maps order by group_id,sortorder;");
        $i = 1;
        $last_group = -1020.5;

        foreach ($list as $map) {
            if ($last_group != $map['group_id']) {
                $last_group  = $map['group_id'];
                $i=1;
            }
            $sql[] = "update weathermap_maps set sortorder = $i where id = ".$map['id'];
            $i++;
        }
        if (!empty($sql)) {
            for ($a = 0; $a < count($sql); $a++) {
                $result = WMCactiAPI::executeDBQuery($sql[$a]);
            }
        }
    }

    // Repair the sort order column (for when something is deleted or inserted)
    public function wmMapGroupResort()
    {
        $list = db_fetch_assoc("select * from weathermap_groups order by sortorder;");
        $i = 1;
        foreach ($list as $group) {
            $sql[] = "update weathermap_groups set sortorder = $i where id = ".$group['id'];
            $i++;
        }
        if (!empty($sql)) {
            for ($a = 0; $a < count($sql); $a++) {
                $result = WMCactiAPI::executeDBQuery($sql[$a]);
            }
        }
    }

    public function wmMapMove($mapid, $junk, $direction)
    {
        $source = db_fetch_assoc("select * from weathermap_maps where id=$mapid");
        $oldorder = $source[0]['sortorder'];
        $group = $source[0]['group_id'];

        $neworder = $oldorder + $direction;
        $target = db_fetch_assoc("select * from weathermap_maps where group_id=$group and sortorder = $neworder");

        if (!empty($target[0]['id'])) {
            $otherid = $target[0]['id'];
            // move $mapid in direction $direction
            $sql[] = "update weathermap_maps set sortorder = $neworder where id=$mapid";
            // then find the other one with the same sortorder and move that in the opposite direction
            $sql[] = "update weathermap_maps set sortorder = $oldorder where id=$otherid";
        }
        if (!empty($sql)) {
            for ($a = 0; $a < count($sql); $a++) {
                $result = WMCactiAPI::executeDBQuery($sql[$a]);
            }
        }
    }

    public function wmMapGroupMove($id, $junk, $direction)
    {
        $source = db_fetch_assoc("select * from weathermap_groups where id=$id");
        $oldorder = $source[0]['sortorder'];

        $neworder = $oldorder + $direction;
        $target = db_fetch_assoc("select * from weathermap_groups where sortorder = $neworder");

        if (!empty($target[0]['id'])) {
            $otherid = $target[0]['id'];
            // move $mapid in direction $direction
            $sql[] = "update weathermap_groups set sortorder = $neworder where id=$id";
            // then find the other one with the same sortorder and move that in the opposite direction
            $sql[] = "update weathermap_groups set sortorder = $oldorder where id=$otherid";
        }
        if (!empty($sql)) {
            for ($a = 0; $a < count($sql); $a++) {
                $result = WMCactiAPI::executeDBQuery($sql[$a]);
            }
        }
    }

    public function wmMapManagementList4()
    {
        $had_warnings = 0;

        $users = $this->wmGenerateCactiUserList();
        $groups = $this->wmGenerateMapGroupList();


        $last_stats = read_config_option("weathermap_last_stats", true);

        if ($last_stats != "") {
            print "<div align='center'><strong>Last Completed Run:</strong> $last_stats</div>";
        }

        if ($had_warnings>0) {
            print '<div align="center" class="wm_warning">'.$had_warnings.' of your maps had warnings last time '.($had_warnings>1?"they":"it").' ran. You can try to find these in your Cacti log file or by clicking on the warning sign next to that map (you might need to increase the log line count).</div>';
        }



        print "<div class='wm_maplist'>";
        print "<h2 class='wm_allmaps'>All Maps <span class='wm_controls'>[Settings][Add Group]</span></h2>";


        foreach ($groups as $group_id => $groupname) {
            print "<div class='wm_mapgroup'>";
            printf("<h3 class=wm_group>%s <span class='wm_controls'>[Add Map][Settings][Rename][Delete][Move Up][Move Down]</span></h3>\n", $groupname);

            $index = 0;

            $queryrows = db_fetch_assoc(sprintf("select weathermap_maps.* from weathermap_maps where weathermap_maps.group_id=%d order by sortorder", $group_id));

            if (is_array($queryrows) && count($queryrows) > 0) {
                print "<table class='mapsortable'><tbody>";

                foreach ($queryrows as $map) {
                    $classes = "maplist-entry";
                    if ($index % 2 == 0) {
                        $classes .= " alt-row";
                    }
                    if ($map['active'] == 'off') {
                        $classes .= " wm_map_disabled";
                    }

                    printf("<tr class='%s' id='map_%d'>", $classes, $map['id']);

                    // drag handle and thumbnail
                    print "<td class='maptable_thumb'>";
                    # printf("<img class='draghandle' src='cacti-resources/drag-handle.png' id='draghandle_%d'> ", $map['id']);
                    printf("<a href='weathermap-cacti-plugin.php?action=viewmap&id=%s'><img src='weathermap-cacti-plugin.php?action=viewthumb48&id=%s' width=48 height=48 class='thumb48' /></a>", $map['filehash'], $map['filehash']);
                    print "</td>";

                    // map title and config filename + disclosure for more map info
                    print "<td class='maptable_disc'>";
                    print '<span class="ui-icon map-disclosure ui-icon-triangle-1-e"></span>';
                    print "</td>";

                    print "<td class='wm_maptitle maptable_names'>";
                    print '<span class="wm_maptitle">'.htmlspecialchars($map['titlecache'])."</span><br />";
                    print '<a title="Click to start editor with this file" href="weathermap-cacti-plugin-editor.php?action=nothing&mapname='.htmlspecialchars($map['configfile']).'">'.htmlspecialchars($map['configfile']).'</a>';
                    print "</td>";


                    // Last run status - time + warning count
                    print "<td class='maptable_status'>";
                    print sprintf("%.2gs", $map['runtime']);
                    if ($map['warncount']>0) {
                        $had_warnings++;
                        print '<br><br><a class="wm_warningcount" href="../../utilities.php?tail_lines=500&message_type=2&action=view_logfile&filter='.urlencode($map['configfile']).'" title="Check cacti.log for this map">'.$map['warncount'].' warnings</a>';
                    }
                    print "</td>";


                    // Active/Disabled + debug status for per-map debugging
                    $debugextra = "";

                    if ($map['debug'] == 'on') {
                        $debugextra="<br><img src='cacti-resources/img/bug.png' />";
                    }

                    if ($map['debug'] == 'once') {
                        $debugextra="<br><img src='cacti-resources/img/bug.png' />x1";
                    }

                    if ($map['active'] == 'on') {
                        $class = "wm_enabled";
                        $action = "deactivate_map";
                        $label = "Deactivate";
                        $label2 = "Enabled";
                    } else {
                        $class = "wm_disabled";
                        $action = "activate_map";
                        $label = "Activate";
                        $label2 = "Disabled";
                    }

                    if ($map['archiving'] == 'on') {
                        $debugextra .= ' <img src="cacti-resources/img/database.png" alt="Archiving" >';
                    }

                    if ($map['schedule'] != '*') {
                        $debugextra .= ' <img src="cacti-resources/img/clock.png" alt="Scheduled" >';
                    }


                    printf(
                        '<td class="%s maptable_active"><a title="Click to %s" href="?action=%s&id=%s">%s</a>%s</td>',
                        $class,
                        $label,
                        $action,
                        $map['id'],
                        $label2,
                        $debugextra
                    );


                    // SETtings
                    print "<td class='maptable_settings'>";
                    print "<a href='?action=map_settings&id=".$map['id']."'>";
                    $setting_count = db_fetch_cell("select count(*) from weathermap_settings where mapid=".$map['id']);
                    if ($setting_count > 0) {
                        print $setting_count." special";
                        if ($setting_count>1) {
                            print "s";
                        }
                    } else {
                        print "standard";
                    }
                    print "</a>";
                    print "</td>";

                    // Permissions
                    print '<td class="maptable_perms">';
                    $UserSQL = 'select * from weathermap_auth where mapid='.$map['id'].' order by userid';
                    $userlist = db_fetch_assoc($UserSQL);

                    $mapusers = array();
                    foreach ($userlist as $user) {
                        if (array_key_exists($user['userid'], $users)) {
                            $mapusers[] = $users[$user['userid']];
                        }
                    }

                    print '<a title="Click to edit permissions" href="?action=perms_edit&id='.$map['id'].'">';

                    if (count($mapusers) == 0) {
                        print "(no users)";
                    } else {
                        print join(", ", $mapusers);
                    }
                    print '</a>';
                    print '</td>';

                    print '<td>';
                    print '<a href="?action=move_map_up&order='.$map['sortorder'].'&id='.$map['id'].'"><img src="../../images/move_up.gif" width="14" height="10" border="0" alt="Move Map Up" title="Move Map Up"></a>';
                    print '<a href="?action=move_map_down&order='.$map['sortorder'].'&id='.$map['id'].'"><img src="../../images/move_down.gif" width="14" height="10" border="0" alt="Move Map Down" title="Move Map Down"></a>';
                    print "</td>";

                    // Delete
                    print '<td class="maptable_delete">';
                    print '<a href="?action=delete_map&id='.$map['id'].'"><img src="cacti-resources/img/delete_icon.png" width="10" height="10" border="0" alt="Delete Map" title="Delete Map"></a>';
                    print '</td>';


                    print '</tr>';
                    print "\n";

                }

                print "</tbody></table>";

            } else {
                print "<table class='mapsortable'><tbody>";
                print "<tr><td colspan=8>No maps in this group.</td></tr>";
                print "</tbody></table>";
            }

            print "</div>";
        }

        print "</div>";

        ?>

        <script type="text/javascript">


            $(document).ready( public function() {



            });

        </script>
        <?php

    }

    public function wmMapManagementList()
    {

        $last_started = read_config_option("weathermap_last_started_file", true);
        $last_finished = read_config_option("weathermap_last_finished_file", true);
        $last_start_time = intval(read_config_option("weathermap_last_start_time", true));
        $last_finish_time = intval(read_config_option("weathermap_last_finish_time", true));
        $poller_interval = intval(read_config_option("poller_interval"));

        if (($last_finish_time - $last_start_time) > $poller_interval) {
            if (($last_started != $last_finished) && ($last_started != "")) {
                print '<div align="center" class="wm_warning"><p>';
                print "Last time it ran, Weathermap did NOT complete it's run. It failed during processing for '$last_started'. ";
                print "This <strong>may</strong> have affected other plugins that run during the poller process. </p><p>";
                print "You should either disable this map, or fault-find. Possible causes include memory_limit issues. The log may have more information.";
                print '</p></div>';
            }
        }

        print "<p><a href='?action=settingsdump'>Cacti Settings Dump</a> (temporary link)</p>";

        html_start_box("<strong>Weathermaps</strong>", "78%", $this->colours["header"], "3", "center", "weathermap-cacti-plugin-mgmt.php?action=addmap_picker");

        $headers = array("", "Config File", "Title", "Group", "Last Run", "Active", "Settings", "Sort Order", "Accessible By", "");

        html_header($headers);

        $query = db_fetch_assoc("select id,username from user_auth");
        $users[0] = 'Anyone';

        foreach ($query as $user) {
            $users[$user['id']] = $user['username'];
        }

        // since it's apparently becoming a more common issue, just check that the tables
        // actually exist in the database!
        if (! wmCheckTablesExist()) {
            print "<div class='wm_warning'>Something bad has happened - none of the weathermap tables exist. This is probably a bug in setup.php - please look for a solution in the forums.</div>";
        }

        $i = 0;
        $queryrows = db_fetch_assoc("select weathermap_maps.*, weathermap_groups.name as groupname from weathermap_maps, weathermap_groups where weathermap_maps.group_id=weathermap_groups.id order by weathermap_groups.sortorder,sortorder");
        // or die (mysql_error("Could not connect to database") )

        $had_warnings = 0;
        if (is_array($queryrows)) {
            form_alternate_row_color($this->colours["alternate"], $this->colours["light"], $i);
            print "<td></td>";
            print "<td>ALL MAPS</td><td>(special settings for all maps)</td><td></td><td></td>";
            print "<td></td>";

            print "<td><a href='?action=map_settings&id=0'>";
            $setting_count = db_fetch_cell("select count(*) from weathermap_settings where mapid=0 and groupid=0");
            if ($setting_count > 0) {
                print $setting_count." special";
                if ($setting_count>1) {
                    print "s";
                }
            } else {
                print "standard";
            }
            print "</a>";

            print "</td>";
            print "<td></td>";
            print "<td></td>";
            print "<td></td>";
            print "</tr>";
            $i++;

            $rowcols = array($this->colours["light"], $this->colours["alternate"]);

            foreach ($queryrows as $map) {
                printf("<tr bgcolor='#%s'>", $rowcols[$i%2]);

                print "<td>";

                printf("<a href='weathermap-cacti-plugin.php?action=viewmap&id=%s'><img src='weathermap-cacti-plugin.php?action=viewthumb48&id=%s' width=48 height=48 border=0/></a></td>", $map['filehash'], $map['filehash']);

                print '<td><a title="Click to start editor with this file" href="editor.php?plug=1&mapname='.htmlspecialchars($map['configfile']).'">'.htmlspecialchars($map['configfile']).'</a>';
                print "</td>";

                #		print '<a href="?action=editor&plug=1&mapname='.htmlspecialchars($map['configfile']).'">[edit]</a></td>';
                print '<td>'.htmlspecialchars($map['titlecache']).'</td>';

                print '<td><a title="Click to change group" href="?action=chgroup&id='.$map['id'].'">'.htmlspecialchars($map['groupname']).'</a></td>';


                print "<td>";
                print sprintf("%.2gs", $map['runtime']);
                if ($map['warncount']>0) {
                    $had_warnings++;
                    print '<br><a href="../../utilities.php?tail_lines=500&message_type=2&action=view_logfile&filter='.urlencode($map['configfile']).'" title="Check cacti.log for this map"><img border=0 src="cacti-resources/img/exclamation.png" title="'.$map['warncount'].' warnings last time this map was run. Check your logs.">'.$map['warncount']."</a>";
                }
                print "</td>";


                $debugextra = "";
                if ($map['debug'] == 'on') {
                    $debugextra="<img src='cacti-resources/img/bug.png' />";
                }

                if ($map['debug'] == 'once') {
                    $debugextra="<img src='cacti-resources/img/bug.png' />x1";
                }

                if ($map['active'] == 'on') {
                    printf(
                        '<td class="%s"><a title="Click to %s" href="?action=%s&id=%s"><font color="green">Yes</font></a>%s</td>',
                        'wm_enabled',
                        'Deactivate',
                        "deactivate_map",
                        $map['id'],
                        $debugextra
                    );
                } else {
                    printf(
                        '<td class="%s"><a title="Click to %s" href="?action=%s&id=%s"><font color="red">No</font></a>%s</td>',
                        'wm_disabled',
                        'Activate',
                        "activate_map",
                        $map['id'],
                        $debugextra
                    );
                }

                print "<td>";
                print "<a href='?action=map_settings&id=".$map['id']."'>";
                $setting_count = db_fetch_cell("select count(*) from weathermap_settings where mapid=".$map['id']);
                if ($setting_count > 0) {
                    print $setting_count." special";
                    if ($setting_count>1) {
                        print "s";
                    }
                } else {
                    print "standard";
                }
                print "</a>";
                print "</td>";

                print '<td>';
                print '<a href="?action=move_map_up&order='.$map['sortorder'].'&id='.$map['id'].'"><img src="../../images/move_up.gif" width="14" height="10" border="0" alt="Move Map Up" title="Move Map Up"></a>';
                print '<a href="?action=move_map_down&order='.$map['sortorder'].'&id='.$map['id'].'"><img src="../../images/move_down.gif" width="14" height="10" border="0" alt="Move Map Down" title="Move Map Down"></a>';
                print "</td>";

                print '<td>';
                $UserSQL = 'select * from weathermap_auth where mapid='.$map['id'].' order by userid';
                $userlist = db_fetch_assoc($UserSQL);

                $mapusers = array();
                foreach ($userlist as $user) {
                    if (array_key_exists($user['userid'], $users)) {
                        $mapusers[] = $users[$user['userid']];
                    }
                }

                print '<a title="Click to edit permissions" href="?action=perms_edit&id='.$map['id'].'">';
                if (count($mapusers) == 0) {
                    print "(no users)";
                } else {
                    print join(", ", $mapusers);
                }
                print '</a>';

                print '</td>';
                print '<td>';
                print '<a href="?action=delete_map&id='.$map['id'].'"><img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="Delete Map" title="Delete Map"></a>';
                print '</td>';

                print '</tr>';
                $i++;
            }
        }



        if ($i==0) {
            print "<tr><td><em>No Weathermaps Configured</em></td></tr>\n";
        }

        print "</tbody>";


        html_end_box();

        $last_stats = read_config_option("weathermap_last_stats", true);

        if ($last_stats != "") {
            print "<div align='center'><strong>Last Completed Run:</strong> $last_stats</div>";
        }

        if ($had_warnings>0) {
            print '<div align="center" class="wm_warning">'.$had_warnings.' of your maps had warnings last time '.($had_warnings>1?"they":"it").' ran. You can try to find these in your Cacti log file or by clicking on the warning sign next to that map (you might need to increase the log line count).</div>';
        }

        print "<div align='center'>";
        print "<a href='weathermap-cacti-plugin-mgmt.php?action=groupadmin'><img src='cacti-resources/img/button_editgroups.png' border=0 alt='Edit Groups' /></a>";
        print "&nbsp;<a href='../../settings.php?tab=misc'><img src='cacti-resources/img/button_settings.gif' border=0 alt='Settings' /></a>";
        if ($i>0 && $this->i_understand_file_permissions_and_how_to_fix_them) {
            print '<br /><a href="?action=rebuildnow"><img src="cacti-resources/img/btn_recalc.png" border="0" alt="Rebuild All Maps Right Now"><br />(Experimental - You should NOT need to use this normally)</a><br />';
        }
        print "</div>";

    }

    public function wmuiMapFilePicker($showAllFiles = false)
    {
        $this->colours = $this->colours;
        $this->configDirectory = $this->configPath;

        $loaded=array();
        $flags=array();
        // find out what maps are already in the database, so we can skip those
        $queryrows = db_fetch_assoc("select * from weathermap_maps");
        if (is_array($queryrows)) {
            foreach ($queryrows as $map) {
                $loaded[]=$map['configfile'];
            }
        }

        if (!is_dir($this->configDirectory)) {
            print "There is no directory named $this->configDirectory - you will need to create it, and set it to be readable by the webserver. If you want to upload configuration files from inside Cacti, then it should be <i>writable</i> by the webserver too.";
            return;
        }

        $nFiles=0;
        $nSkipped = 0;


        $directoryHandle = opendir($this->configDirectory);

        if (!$directoryHandle) {
            print "Can't open $this->configDirectory to read - you should set it to be readable by the webserver.";
            return;
        }

        while ($file = readdir($directoryHandle)) {
            $realfile = $this->configDirectory.'/'.$file;

            // skip .-prefixed files like .htaccess, since it seems
            // that otherwise people will add them as map config files.
            // and the index.php too - for the same reason
            if (substr($file, 0, 1) != '.' && $file != "index.php") {
                $used = in_array($file, $loaded);
                $flags[$file] = '';
                if ($used) {
                    $flags[$file] = 'USED';
                }

                if (is_file($realfile)) {
                    if ($used && !$showAllFiles) {
                        $nSkipped++;
                    } else {
                        $title = $this->wmMapGetTitleFromFile($realfile);
                        $titles[$file] = $title;
                        $nFiles++;
                    }
                }
            }
        }
        closedir($directoryHandle);

        if (($nFiles + $nSkipped) == 0) {
            print "No files were found in the configs directory.";
            return;
        }

        if ($nFiles == 0) {
            print "No files to show. ";
        }

        if (($nFiles == 0) && $nSkipped>0) {
            print "($nSkipped files weren't shown because they are already in the database - <a href='?action=addmap_picker&show_all=1'>Show Them</a>)";
            return;
        }

        html_start_box("<strong>Available Weathermap Configuration Files</strong>", "78%", $this->colours["header"], "1", "center", "");
        html_header(array("", "", "Config File", "Title", ""), 2);

        if ($nFiles>0) {
            ksort($titles);

            $nFiles=0;
            foreach ($titles as $file => $title) {
                $title = $titles[$file];
                form_alternate_row_color($this->colours["alternate"], $this->colours["light"], $nFiles);
                print '<td><a href="?action=addmap&amp;file='.$file.'" title="Add the configuration file">Add</a></td>';
                print '<td><a href="?action=viewconfig&amp;file='.$file.'" title="View the configuration file in a new window" target="_blank">View</a></td>';
                print '<td>'.htmlspecialchars($file);
                if ($flags[$file] == 'USED') {
                    print ' <b>(USED)</b>';
                }
                print '</td>';
                print '<td><em>'.htmlspecialchars($title).'</em></td>';
                print '</tr>';
                $nFiles++;
            }
        }

        html_end_box();

        if ($nSkipped>0) {
            print "<p align=center>Some files are not shown because they have already been added. You can <a href='?action=addmap_picker&show=all'>show these files too</a>, if you need to.</p>";
        }

        if ($showAllFiles) {
            print "<p align=center>Some files are shown even though they have already been added. You can <a href='?action=addmap_picker'>hide those files too</a>, if you need to.</p>";
        }
    }

    public function wmuiPreviewConfig($file)
    {
        $weathermap_confdir = $this->configPath;

        chdir($weathermap_confdir);

        $path_parts = pathinfo($file);
        $file_dir = realpath($path_parts['dirname']);

        if ($file_dir != $weathermap_confdir) {
            // someone is trying to read arbitrary files?
            // print "$file_dir != $weathermap_confdir";
            print "<h3>Path mismatch</h3>";
        } else {
            html_start_box("<strong>Preview of $file</strong>", "98%", $this->colours["header"], "3", "center", "");

            print '<tr><td valign="top" bgcolor="#'.$this->colours["light"].'" class="textArea">';
            print '<pre>';
            $realfile = $weathermap_confdir.'/'.$file;
            if (is_file($realfile)) {
                $filehandle = fopen($realfile, "r");
                while (!feof($filehandle)) {
                    $buffer = fgets($filehandle, 4096);
                    print $buffer;
                }
                fclose($filehandle);
            }
            print '</pre>';
            print '</td></tr>';
            html_end_box();
        }
    }

    public function wmMapAdd($file)
    {
        $weathermap_confdir = $this->configPath;

        chdir($weathermap_confdir);

        $path_parts = pathinfo($file);
        $file_dir = realpath($path_parts['dirname']);

        if ($file_dir != $weathermap_confdir) {
            // someone is trying to read arbitrary files?
            print "<h3>Path mismatch</h3>";
        } else {
            $realfile = $weathermap_confdir.DIRECTORY_SEPARATOR.$file;
            $title = $this->wmMapGetTitleFromFile($realfile);

            $file = mysql_real_escape_string($file);
            $title = mysql_real_escape_string($title);
            $SQL = "insert into weathermap_maps (configfile,titlecache,active,imagefile,htmlfile,filehash,config) VALUES ('$file','$title','on','','','','')";
            WMCactiAPI::executeDBQuery($SQL);

            // add auth for current user
            $last_id = mysql_insert_id();
            // $myuid = (int)$_SESSION["sess_user_id"];
            $myuid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);
            $SQL = "insert into weathermap_auth (mapid,userid) VALUES ($last_id,$myuid)";
            WMCactiAPI::executeDBQuery($SQL);

            // Now we know the database ID, figure out the filehash
            WMCactiAPI::executeDBQuery("update weathermap_maps set filehash=LEFT(MD5(concat(id,configfile,rand())),20) where id=$last_id");

            $this->wmMapResort();
        }
    }

    public function wmMapGetTitleFromFile($filename)
    {
        $title = "(no title)";
        $filehandle = fopen($filename, "r");
        while (!feof($filehandle)) {
            $buffer = fgets($filehandle, 4096);
            if (preg_match("/^\s*TITLE\s+(.*)/i", $buffer, $matches)) {
                $title = $matches[1];
                break;
            }
            // this regexp is tweaked from the ReadConfig version, to only match TITLEPOS lines *with* a title appended
            if (preg_match("/^\s*TITLEPOS\s+\d+\s+\d+\s+(.+)/i", $buffer, $matches)) {
                $title = $matches[1];
                break;
            }
            // strip out any DOS line endings that got through
            $title=str_replace("\r", "", $title);
        }
        fclose($filehandle);

        return($title);
    }

    public function wmMapDeactivate($id)
    {
        $SQL = "update weathermap_maps set active='off' where id=" . $id;
        WMCactiAPI::executeDBQuery($SQL);
    }

    public function wmMapActivate($id)
    {
        $SQL = "update weathermap_maps set active='on' where id=" . $id;
        WMCactiAPI::executeDBQuery($SQL);
    }

    public function wmMapDelete($id)
    {
        $SQL = "delete from weathermap_maps where id=".$id;
        WMCactiAPI::executeDBQuery($SQL);

        $SQL = "delete from weathermap_auth where mapid=".$id;
        WMCactiAPI::executeDBQuery($SQL);

        $SQL = "delete from weathermap_settings where mapid=".$id;
        WMCactiAPI::executeDBQuery($SQL);

        $this->wmMapResort();
    }

    public function wmMapSetGroup($mapid, $groupid)
    {
        $SQL = sprintf("update weathermap_maps set group_id=%d where id=%d", $groupid, $mapid);
        WMCactiAPI::executeDBQuery($SQL);
        $this->wmMapResort();
    }

    public function wmPermissionsUserAdd($mapid, $userid)
    {
        $SQL = "insert into weathermap_auth (mapid,userid) values($mapid,$userid)";
        WMCactiAPI::executeDBQuery($SQL);
    }

    public function wmPermissionsUserDelete($mapid, $userid)
    {
        $SQL = "delete from weathermap_auth where mapid=$mapid and userid=$userid";
        WMCactiAPI::executeDBQuery($SQL);
    }

    public function wmuiMapPermissionsPage($id)
    {

        $title = db_fetch_cell("select titlecache from weathermap_maps where id=".intval($id));

        $auth_sql = "select * from weathermap_auth where mapid=$id order by userid";

        $query = db_fetch_assoc("select id,username from user_auth order by username");
        $users[0] = 'Anyone';
        foreach ($query as $user) {
            $users[$user['id']] = $user['username'];
        }

        $auth_results = db_fetch_assoc($auth_sql);
        $mapusers = array();
        $mapuserids = array();
        foreach ($auth_results as $user) {
            if (isset($users[$user['userid']])) {
                $mapusers[] = $users[$user['userid']];
                $mapuserids[] = $user['userid'];
            }
        }

        $userselect="";
        foreach ($users as $uid => $name) {
            if (!in_array($uid, $mapuserids)) {
                $userselect .= "<option value=\"$uid\">$name</option>\n";
            }
        }

        html_start_box("<strong>Edit permissions for Weathermap $id: $title</strong>", "70%", $this->colours["header"], "2", "center", "");
        html_header(array("Username", ""));

        $row = 0;
        foreach ($mapuserids as $user) {
            form_alternate_row_color($this->colours["alternate"], $this->colours["light"], $row);
            print "<td>".$users[$user]."</td>";
            print '<td><a href="?action=perms_delete_user&mapid='.$id.'&userid='.$user.'"><img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="Remove permissions for this user to see this map"></a></td>';

            print "</tr>";
            $row++;
        }
        if ($row==0) {
            print "<tr><td><em><strong>nobody</strong> can see this map</em></td></tr>";
        }
        html_end_box();

        html_start_box("", "70%", $this->colours["header"], "3", "center", "");
        print "<tr>";
        if ($userselect == '') {
            print "<td><em>There aren't any users left to add!</em></td></tr>";
        } else {
            print "<td><form action=\"\">Allow <input type=\"hidden\" name=\"action\" value=\"perms_add_user\"><input type=\"hidden\" name=\"mapid\" value=\"$id\"><select name=\"userid\">";
            print $userselect;
            print "</select> to see this map <input type=\"submit\" value=\"Update\"></form></td>";
            print "</tr>";
        }
        html_end_box();
    }

    public function wmuiMapSettingsPage($id)
    {

        if ($id==0) {
            $title = "Additional settings for ALL maps";
            $nonemsg = "There are no settings for all maps yet. You can add some by clicking Add up in the top-right, or choose a single map from the management screen to add settings for that map.";
            $type = "global";
            $settingrows = db_fetch_assoc("select * from weathermap_settings where mapid=0 and groupid=0");

        } elseif ($id<0) {
            $group_id = -intval($id);
            $groupname = db_fetch_cell("select name from weathermap_groups where id=".$group_id);
            $title = "Edit per-map settings for Group ". $group_id . ": " . $groupname;
            $nonemsg = "There are no per-group settings for this group yet. You can add some by clicking Add up in the top-right.";
            $type="group";
            $settingrows = db_fetch_assoc("select * from weathermap_settings where groupid=".$group_id);
        } else {
            // print "Per-map settings for map $id";
            $map = db_fetch_row("select * from weathermap_maps where id=".intval($id));

            $groupname = db_fetch_cell("select name from weathermap_groups where id=".intval($map['group_id']));
            $title = "Edit per-map settings for Weathermap $id: " . $map['titlecache'];
            $nonemsg = "There are no per-map settings for this map yet. You can add some by clicking Add up in the top-right.";
            $type = "map";
            $settingrows = db_fetch_assoc("select * from weathermap_settings where mapid=".intval($id));
        }

        if ($type == "group") {
            print "<p>All maps in this group are also affected by the following GLOBAL settings (group overrides global, map overrides group, but BOTH override SET commands within the map config file):</p>";
            $this->wmuiMapSettingsReadOnly(0, "Global Settings");

        }

        if ($type == "map") {
            print "<p>This map is also affected by the following GLOBAL and GROUP settings (group overrides global, map overrides group, but BOTH override SET commands within the map config file):</p>";

            $this->wmuiMapSettingsReadOnly(0, "Global Settings");

            $this->wmuiMapSettingsReadOnly(-$map['group_id'], "Group Settings (".htmlspecialchars($groupname).")");
        }

        html_start_box("<strong>$title</strong>", "70%", $this->colours["header"], "2", "center", "weathermap-cacti-plugin-mgmt.php?action=map_settings_form&mapid=".intval($id));
        html_header(array("", "Name", "Value", ""));

        $row=0;

        if (is_array($settingrows)) {
            if (sizeof($settingrows)>0) {
                foreach ($settingrows as $setting) {
                    form_alternate_row_color($this->colours["alternate"], $this->colours["light"], $row);
                    print '<td><a href="?action=map_settings_form&mapid='.$id.'&id='.intval($setting['id']).'"><img src="../../images/graph_properties.gif" width="16" height="16" border="0" alt="Edit this definition">Edit</a></td>';
                    print "<td>".htmlspecialchars($setting['optname'])."</td>";
                    print "<td>".htmlspecialchars($setting['optvalue'])."</td>";
                    print '<td><a href="?action=map_settings_delete&mapid='.$id.'&id='.intval($setting['id']).'"><img src="../../images/delete_icon_large.gif" width="12" height="12" border="0" alt="Remove this definition from this map"></a></td>';
                    print "</tr>";
                    $row++;
                }
            } else {
                print "<tr>";
                print "<td colspan=2>$nonemsg</td>";
                print "</tr>";
            }
        }

        html_end_box();

        print "<div align=center>";
        if ($type == "group") {
            print "<a href='?action=groupadmin'>Back to Group Admin</a>";
        }
        if ($type == "global") {
            print "<a href='?action='>Back to Map Admin</a>";
        }
        print "</div>";
    }

    public function wmuiMapSettingsReadOnly($id, $title = "Settings")
    {

        if ($id == 0) {
            $query = "select * from weathermap_settings where mapid=0 and groupid=0";
        }
        if ($id < 0) {
            $query = "select * from weathermap_settings where mapid=0 and groupid=".(-intval($id));
        }
        if ($id > 0) {
            $query = "select * from weathermap_settings where mapid=".intval($id);
        }

        $settings = db_fetch_assoc($query);

        html_start_box("<strong>$title</strong>", "70%", $this->colours["header"], "2", "center", "");
        html_header(array("", "Name", "Value", ""));

        $row=0;

        if (sizeof($settings)>0) {
            foreach ($settings as $setting) {
                form_alternate_row_color($this->colours["alternate"], $this->colours["light"], $row);
                print "<td></td>";
                print "<td>".htmlspecialchars($setting['optname'])."</td>";
                print "<td>".htmlspecialchars($setting['optvalue'])."</td>";
                print "<td></td>";
                print "</tr>";
                $row++;
            }
        } else {
            form_alternate_row_color($this->colours["alternate"], $this->colours["light"], $row);
            print "<td colspan=4><em>No Settings</em></td>";
            print "</tr>";
        }

        html_end_box();

    }

    public function wmuiMapSettingsForm($mapid = 0, $settingid = 0)
    {

        // print "Per-map settings for map $id";

        if ($mapid > 0) {
            $title = db_fetch_cell("select titlecache from weathermap_maps where id=".intval($mapid));
        }
        if ($mapid < 0) {
            $title = db_fetch_cell("select name from weathermap_groups where id=".intval(-$mapid));
        }

        $name = "";
        $value = "";

        if ($settingid != 0) {
            $result = db_fetch_assoc("select * from weathermap_settings where id=".intval($settingid));

            if (is_array($result) && sizeof($result)>0) {
                $name = $result[0]['optname'];
                $value = $result[0]['optvalue'];
            }
        }

        $values_ar = array();

        $field_ar = array(
            "mapid" => array("friendly_name" => "Map ID", "method" => "hidden_zero", "value" => $mapid ) ,
            "id" => array("friendly_name" => "Setting ID", "method" => "hidden_zero", "value" => $settingid ) ,
            "name" => array("friendly_name" => "Name", "method" => "textbox", "max_length"=>128, "description"=>"The name of the map-global SET variable", "value"=>$name),
            "value" => array("friendly_name" => "Value", "method" => "textbox", "max_length"=>128, "description"=>"What to set it to", "value"=>$value)
        );

        $action = "Edit";
        if ($settingid == 0) {
            $action ="Create";
        }

        if ($mapid == 0) {
            $title = "setting for ALL maps";
        } elseif ($mapid < 0) {
            $grpid = -$mapid;
            $title = "per-group setting for Group $grpid: $title";
        } else {
            $title = "per-map setting for Weathermap $mapid: $title";
        }

        html_start_box("<strong>$action $title</strong>", "98%", $this->colours["header"], "3", "center", "");
        draw_edit_form(array("config"=>$values_ar, "fields"=>$field_ar));
        html_end_box();

        form_save_button("weathermap-cacti-plugin-mgmt.php?action=map_settings&id=".$mapid);

    }

    public function wmMapSettingAdd($mapid, $name, $value)
    {
        if ($mapid >0) {
            WMCactiAPI::executeDBQuery("insert into weathermap_settings (mapid, optname, optvalue) values ($mapid, '".mysql_real_escape_string($name)."', '".mysql_real_escape_string($value)."')");
        } elseif ($mapid <0) {
            WMCactiAPI::executeDBQuery("insert into weathermap_settings (mapid, groupid, optname, optvalue) values (0, -$mapid, '".mysql_real_escape_string($name)."', '".mysql_real_escape_string($value)."')");
        } else {
            WMCactiAPI::executeDBQuery("insert into weathermap_settings (mapid, groupid, optname, optvalue) values (0, 0, '".mysql_real_escape_string($name)."', '".mysql_real_escape_string($value)."')");
        }
    }

    public function wmMapSettingUpdate($mapid, $settingid, $name, $value)
    {
        WMCactiAPI::executeDBQuery("update weathermap_settings set optname='".mysql_real_escape_string($name)."', optvalue='".mysql_real_escape_string($value)."' where id=".intval($settingid));
    }

    public function wmMapSettingDelete($mapid, $settingid)
    {
        WMCactiAPI::executeDBQuery("delete from weathermap_settings where id=".intval($settingid)." and mapid=".intval($mapid));
    }

    public function wmuiMapGroupChangePage($id)
    {

        $n = 0;

        $title = db_fetch_cell("select titlecache from weathermap_maps where id=".intval($id));
        $curgroup = db_fetch_cell("select group_id from weathermap_maps where id=".intval($id));

        print "<form>";
        print "<input type=hidden name='map_id' value='".$id."'>";
        print "<input type=hidden name='action' value='chgroup_update'>";
        html_start_box("<strong>Edit map group for Weathermap $id: $title</strong>", "70%", $this->colours["header"], "2", "center", "");

        form_alternate_row_color($this->colours["alternate"], $this->colours["light"], $n++);
        print "<td><strong>Choose an existing Group:</strong><select name='new_group'>";
        $SQL = "select * from weathermap_groups order by sortorder";
        $results = db_fetch_assoc($SQL);

        foreach ($results as $grp) {
            print "<option ";
            if ($grp['id'] == $curgroup) {
                print " SELECTED ";
            }
            print "value=".$grp['id'].">".htmlspecialchars($grp['name'])."</option>";
        }

        print "</select>";
        print '<input type="image" src="../../images/button_save.gif"  border="0" alt="Change Group" title="Change Group" />';
        print "</td>";
        print "</tr>\n";
        print "<tr><td></td></tr>";

        print "<tr><td><p>or create a new group in the <strong><a href='?action=groupadmin'>group management screen</a></strong></p></td></tr>";

        html_end_box();
        print "</form>\n";
    }

    public function wmuiGroupForm($id = 0)
    {
        $grouptext = "";
        // if id==0, it's an Add, otherwise it's an editor.
        if ($id == 0) {
            print "Adding a group...";
        } else {
            print "Editing group $id\n";
            $grouptext = db_fetch_cell("select name from weathermap_groups where id=".$id);
        }

        print "<form action=weathermap-cacti-plugin-mgmt.php>\n<input type=hidden name=action value=group_update />\n";

        print "Group Name: <input name='gname' value='".htmlspecialchars($grouptext)."'/>\n";
        if ($id>0) {
            print "<input type=hidden name=id value=$id />\n";
            print "Group Name: <input type=submit value='Update' />\n";
        } else {
            print "Group Name: <input type=submit value='Add' />\n";
        }

        print "</form>\n";
    }

    public function wmuiGroupList()
    {

        html_start_box("<strong>Edit Map Groups</strong>", "70%", $this->colours["header"], "2", "center", "weathermap-cacti-plugin-mgmt.php?action=group_form&id=0");
        html_header(array("", "Group Name", "Settings", "Sort Order", ""));

        $groups = db_fetch_assoc("select * from weathermap_groups order by sortorder");

        $row = 0;

        if (is_array($groups)) {
            if (sizeof($groups)>0) {
                foreach ($groups as $group) {
                    form_alternate_row_color($this->colours["alternate"], $this->colours["light"], $row);
                    print '<td><a href="weathermap-cacti-plugin-mgmt.php?action=group_form&id='.intval($group['id']).'"><img src="../../images/graph_properties.gif" width="16" height="16" border="0" alt="Rename This Group" title="Rename This Group">Rename</a></td>';
                    print "<td>".htmlspecialchars($group['name'])."</td>";

                    print "<td>";

                    print "<a href='?action=map_settings&id=-".$group['id']."'>";
                    $setting_count = db_fetch_cell("select count(*) from weathermap_settings where mapid=0 and groupid=".$group['id']);
                    if ($setting_count > 0) {
                        print $setting_count." special";
                        if ($setting_count>1) {
                            print "s";
                        }
                    } else {
                        print "standard";
                    }
                    print "</a>";

                    print "</td>";


                    print '<td>';

                    print '<a href="weathermap-cacti-plugin-mgmt.php?action=move_group_up&order='.$group['sortorder'].'&id='.$group['id'].'"><img src="../../images/move_up.gif" width="14" height="10" border="0" alt="Move Group Up" title="Move Group Up"></a>';
                    print '<a href="weathermap-cacti-plugin-mgmt.php?action=move_group_down&order='.$group['sortorder'].'&id='.$group['id'].'"><img src="../../images/move_down.gif" width="14" height="10" border="0" alt="Move Group Down" title="Move Group Down"></a>';

                    print "</td>";

                    print '<td>';
                    if ($group['id']>1) {
                        print '<a href="weathermap-cacti-plugin-mgmt.php?action=groupadmin_delete&id='.intval($group['id']).'"><img src="../../images/delete_icon.gif" width="10" height="10" border="0" alt="Remove this definition from this map"></a>';
                    }
                    print '</td>';

                    print "</tr>";
                    $row++;
                }
            } else {
                print "<tr>";
                print "<td colspan=2>No groups are defined.</td>";
                print "</tr>";
            }
        }

        html_end_box();
    }

    public function wmGroupAdd($newname)
    {
        if ($newname == "") {
            return;
        }

        $sortorder = db_fetch_cell("select max(sortorder)+1 from weathermap_groups");
        $SQL = sprintf(
            "insert into weathermap_groups (name, sortorder) values ('%s',%d)",
            mysql_escape_string($newname),
            $sortorder
        );
        WMCactiAPI::executeDBQuery($SQL);
    }

    public function wmGroupRename($id, $newname)
    {
        if ($newname == "") {
            return;
        }

        $SQL = sprintf("update weathermap_groups set name='%s' where id=%d", mysql_escape_string($newname), $id);
        WMCactiAPI::executeDBQuery($SQL);
    }

    public function wmGroupDelete($id)
    {
        $SQL1 = "SELECT MIN(id) from weathermap_groups where id <> ". $id;
        $newid = db_fetch_cell($SQL1);

        # move any maps out of this group into a still-existing one
        $SQL2 = "UPDATE weathermap_maps set group_id=$newid where group_id=".$id;
        # then delete the group
        $SQL3 = "DELETE from weathermap_groups where id=".$id;
        WMCactiAPI::executeDBQuery($SQL2);
        WMCactiAPI::executeDBQuery($SQL3);
    }

    public function wmGenerateCactiUserList()
    {
        $query = db_fetch_assoc("select id,username from user_auth");
        $users[0] = 'Anyone';

        foreach ($query as $user) {
            $users[$user['id']] = $user['username'];
        }
        return $users;
    }

    public function wmGenerateMapGroupList()
    {
        $gquery = db_fetch_assoc("select * from weathermap_groups order by weathermap_groups.sortorder");

        foreach ($gquery as $g) {
            $groups[$g['id']] = $g['name'];
        }

        return $groups;
    }

    /**
     * @param $request
     */
    protected function handleMapSettingsPage($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id'])) {
            WMCactiAPI::pageTopConsole();
            $this->wmuiMapSettingsPage(intval($request['id']));
            wmGenerateFooterLinks();
            WMCactiAPI::pageBottom();
        }
    }

    /**
     * @param $request
     */
    protected function handlePermissionsAddUser($request, $appObject)
    {
        if (isset($request['mapid']) && is_numeric($request['mapid'])
            && isset($request['userid']) && is_numeric($request['userid'])
        ) {
            $this->wmPermissionsUserAdd(intval($request['mapid']), intval($request['userid']));
            header("Location: weathermap-cacti-plugin-mgmt.php?action=perms_edit&id=" . intval($request['mapid']));
        }
    }

    /**
     * @param $request
     */
    protected function handlePermissionsDeleteUser($request, $appObject)
    {
        if (isset($request['mapid']) && is_numeric($request['mapid'])
            && isset($request['userid']) && is_numeric($request['userid'])
        ) {
            $this->wmPermissionsUserDelete($request['mapid'], $request['userid']);
            header("Location: weathermap-cacti-plugin-mgmt.php?action=perms_edit&id=" . $request['mapid']);
        }
    }

    /**
     * @param $request
     */
    protected function handlePermissionsPage($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id'])) {
            WMCactiAPI::pageTopConsole();
            $this->wmuiMapPermissionsPage($request['id']);
            WMCactiAPI::pageBottom();
        } else {
            print "Something got lost back there.";
        }
    }

    /**
     * @param $request
     */
    protected function handleDeleteMap($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->wmMapDelete($request['id']);
        }
        header("Location: weathermap-cacti-plugin-mgmt.php");
    }

    /**
     * @param $request
     */
    protected function handleDeactivateMap($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->wmMapDeactivate($request['id']);
        }
        header("Location: weathermap-cacti-plugin-mgmt.php");
    }

    /**
     * @param $request
     */
    protected function handleActivateMap($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id'])) {
            $this->wmMapActivate($request['id']);
        }
        header("Location: weathermap-cacti-plugin-mgmt.php");
    }

    /**
     * @param $request
     */
    protected function handleMapOrderUp($request)
    {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->wmMapMove($request['id'], $request['order'], -1);
        }
        header("Location: weathermap-cacti-plugin-mgmt.php");
    }

    /**
     * @param $request
     */
    protected function handleMapOrderDown($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->wmMapMove($request['id'], $request['order'], +1);
        }
        header("Location: weathermap-cacti-plugin-mgmt.php");
    }

    /**
     * @param $request
     */
    protected function handleGroupOrderUp($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->wmMapGroupMove(intval($request['id']), intval($request['order']), -1);
        }
        header("Location: weathermap-cacti-plugin-mgmt.php?action=groupadmin");
    }

    /**
     * @param $request
     */
    protected function handleGroupOrderDown($request, $appObject)
    {
        if (isset($request['id']) && is_numeric($request['id']) &&
            isset($request['order']) && is_numeric($request['order'])
        ) {
            $this->wmMapGroupMove(intval($request['id']), intval($request['order']), 1);
        }
        header("Location: weathermap-cacti-plugin-mgmt.php?action=groupadmin");
    }

    /**
     * @param $request
     */
    protected function handleMapListAdd($request, $appObject)
    {
        if (isset($request['file'])) {
            $this->wmMapAdd($request['file']);
            header("Location: weathermap-cacti-plugin-mgmt.php");
        } else {
            print "No such file.";
        }
    }

    /**
     * @param $request
     */
    protected function handleMapPicker($request, $appObject)
    {
        WMCactiAPI::pageTopConsole();

        if (isset($request['show_all']) && $request['show_all'] == '1') {
            $this->wmuiMapFilePicker(true);
        } else {
            $this->wmuiMapFilePicker(false);
        }
        WMCactiAPI::pageBottom();
    }

    /**
     * @param $request
     */
    protected function handleViewConfig($request, $appObject)
    {
        WMCactiAPI::pageTop();
        $this->wmuiPreviewConfig($request['file']);
        WMCactiAPI::pageBottom();
    }
}