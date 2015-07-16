"use strict";
/*global jQuery:false */
/*global _:false */
/*global Nodes:false */
/*global NodeIDs:false */
/*global Links:false */
/*global LinkIDs:false */
/*global document:false */
/*global wmPickerWindow:false */
/*jslint vars: true, plusplus: true, devel: true, nomen: true, indent: 4, maxerr: 50 */

_.templateSettings.variable = "rc";

var wmPickerWindow;

var wmEditor = {
    KEYCODE_ESCAPE : 27,
    KEYCODE_L : 76,
    KEYCODE_N : 78,
    KEYCODE_DEL : 46,

    start: function () {
        // check if there is a "No JavaScript" message
        jQuery("#nojs").hide();

        // hide all the dialog boxes for now
        jQuery(".dlgProperties").hide();
        // but show the 'start' dialog, if there is one... (only on the start page)
        jQuery("#dlgStart").show();

        console.debug(this);
        wmEditor.startClickListeners();
    },

    startClickListeners: function () {
        jQuery('area[id^="LINK:"]').attr("href", "#").click(wmEditor.handleMapClick).on("linkClicked", wmEditor.handleLinkClick);
        jQuery('area[id^="NODE:"]').attr("href", "#").click(wmEditor.handleMapClick).on("nodeClicked", wmEditor.handleNodeClick);

        var click_listeners = {
            "#tb_mapprops": wmEditor.handleMapProperties,
            "#tb_addnode": wmEditor.handleAddNode,
            "#tb_addlink": wmEditor.handleAddLink,
            "#node_move": wmEditor.handleMoveNode,
            "#node_delete": wmEditor.handleDeleteNode,
            "#node_clone": wmEditor.handleCloneNode,
            "#link_delete": wmEditor.handleDeleteLink,
            "#link_tidy": wmEditor.handleTidyLink,
            "#link_via": wmEditor.handleAddVia,
            "#link_novia": wmEditor.handleStraighten,
            "#tb_node_cancel": wmEditor.handleUnderlayClick,
            "#tb_link_cancel": wmEditor.handleUnderlayClick,
            "#tb_map_cancel": wmEditor.handleUnderlayClick,
            ".dlgUnderlay": wmEditor.handleUnderlayClick
        };

        jQuery.each(click_listeners, function (selector, handler) {
            jQuery(document).on("click", selector, handler);
        });
        // keyboard shortcuts
        jQuery(document).on('keyup', wmEditor.handleKey);

        // jQuery('area[id^="TIMES"]').attr("href", "#").click(position_timestamp);
        // jQuery('area[id^="LEGEN"]').attr("href", "#").click(position_legend);
    },

    handleKey: function (event) {
        if (event.keyCode === wmEditor.KEYCODE_ESCAPE) {
            wmEditor.handleUnderlayClick();
        }

        if (event.which === wmEditor.KEYCODE_N) {
            wmEditor.handleAddNode();
        }

        if (event.which === wmEditor.KEYCODE_L) {
            wmEditor.handleAddLink();
        }
    },

    // Translate a click event into a nodeClicked or linkClicked event
    // special handlers for those events do the real work. This will allow us to have multiple methods
    // of selecting nodes/links, like a pick-list.
    handleMapClick: function () {
        var element_id, objectname, objecttype, objectid;

        console.log("Click handler!");
        console.debug(this);

        element_id = jQuery(this).attr("id");

        objecttype = element_id.slice(0, 4);
        objectname = element_id.slice(5, element_id.length);
        objectid = objectname.slice(0, objectname.length - 2);

        if (objecttype === 'NODE') {
            objectname = NodeIDs[objectid];
            console.log("custom nodeClicked event for " + objectname);
            jQuery(this).trigger("nodeClicked", [objecttype, objectname]);
        }

        if (objecttype === 'LINK') {
            objectname = LinkIDs[objectid];
            console.log("custom linkClicked event for " + objectname);
            jQuery(this).trigger("linkClicked", [objecttype, objectname]);
        }
    },

    handleLinkClick: function (e, type, name) {
        //undefined(e, type);
        console.log("Showing link properties for " + name);
        wmEditor.showLinkProperties(name);
    },

    handleNodeClick: function (e, type, name) {
        //undefined(e, type);

        // TODO - check if we're in the middle of adding a node
        // don't show the properties then.

        var element_id, objectname, objectid,
            action = jQuery("#action").val();

        // alt = el.getAttribute('alt');
        element_id = jQuery(this).attr("id");

        objectname = element_id.slice(5, element_id.length);
        objectid = objectname.slice(0, objectname.length - 2);

        if (action === 'add_link') {
            jQuery("#param").val(NodeIDs[objectid]);
            document.frmMain.submit();
        } else if (action === 'add_link2') {
            jQuery("#param").val(NodeIDs[objectid]);
            document.frmMain.submit();
        } else {
            console.log("Showing node properties for " + name);

            var node = Nodes[name];
            var im = document.getElementById('existingdata');
            var canvas = document.getElementById('canvas_node_drag');
            var context = canvas.getContext("2d");

            var w = node.bbox[2] - node.bbox[0];
            var h = node.bbox[3] - node.bbox[1];

            canvas.width = w;
            canvas.height = h;

            canvas.left = node.x;
            canvas.top = node.y;

            context.drawImage(im, node.bbox[0], node.bbox[1], w, h, 0, 0, w, h);

            wmEditor.showNodeProperties(name);
        }
    },

    // They clicked away from the dialog. Cancel the interaction
    handleUnderlayClick: function () {
        wmEditor.hideAllDialogs();
        jQuery('#action').val('');
        jQuery('#param').val('');
    },

    showLinkProperties: function (name) {
        jQuery("#action").val("edit_link");
        wmEditor.setMapMode('existing');
        wmEditor.hideAllDialogs();

        var template = _.template(jQuery("script#tpl-dialog-link-properties").html());

        var input = {name: name, data: Links[name], via_label: "Add Via", show_straighten: false};

        if (Links[name].via.length > 0) {
            input.via_label = "Move Via";
            input.show_straighten = true;
        }

        jQuery("#mainarea").after(template(input));
        jQuery("#param").val(name);
        jQuery("#dlgLinkProperties").show().draggable({handle: "h3"});
        jQuery("#link_bandwidth_in").focus();

        jQuery("#link_cactipick").click(wmEditor.openCactiDSPicker);

    },

    openCactiDSPicker: function() {
        wmEditor.openPickerWindow("cacti-pick.php?command=link_step1");
        return false;
    },

    openCactiGraphPicker: function() {
        wmEditor.openPickerWindow("cacti-pick.php?command=node_step1");
        return false;
    },

    openPickerWindow: function(url) {
        if (!wmPickerWindow || wmPickerWindow.closed) {
            wmPickerWindow = window.open("", "openCactiDSPicker", "scrollbars=1,status=1,height=400,width=400,resizable=1");
        } else if (wmPickerWindow.focus) {
            // window is already open and focusable, so bring it to the front
            wmPickerWindow.focus();
        }
        wmPickerWindow.location = url;
    },

    showNodeProperties: function (name) {
        jQuery("#action").val("edit_node");
        wmEditor.setMapMode('existing');
        wmEditor.hideAllDialogs();

        var template = _.template(jQuery("script#tpl-dialog-node-properties").html());

        jQuery("#mainarea").after(template(Nodes[name]));
        jQuery("#param").val(name);
        jQuery("#dlgNodeProperties").show().draggable({handle: "h3"});
        jQuery("#node_new_name").focus();
    },

    showMapProperties: function () {
        wmEditor.hideAllDialogs();

        var template = _.template(jQuery("script#tpl-dialog-map-properties").html());
        jQuery("#mainarea").after(template(Map));
        jQuery("#dlgMapProperties").show().draggable({handle: "h3"});
    },

    hideAllDialogs: function () {
        jQuery(".dlgProperties").remove();
        jQuery('.dlgUnderlay').remove();
    },

    setMapMode: function (new_mode) {
        if (new_mode === 'xy') {
            jQuery("#debug").val("xy");
            jQuery("#xycapture").show();
            jQuery("#existingdata").hide();
        } else if (new_mode === 'existing') {
            jQuery("#debug").val("existing");
            jQuery("#existingdata").show();
            jQuery("#xycapture").hide();
        } else {
            alert('invalid mode');
        }
    },
    handleAddNode:   function () {
        console.log("in handleAddNode");
        wmEditor.setMapMode('xy');
        jQuery("#action").val("add_node");
    },
    handleAddLink:   function () {
        console.log("in handleAddLink");
        jQuery("#action").val("add_link");
        wmEditor.setMapMode('existing');
    },
    handleDeleteNode: function () {
        jQuery('#action').val('delete_node');
        document.frmMain.submit();
    },
    handleDeleteLink: function () {
        jQuery('#action').val('delete_link');
        document.frmMain.submit();
    },
    handleStraighten: function () {
        jQuery('#action').val('straight_link');
        document.frmMain.submit();
    },
    handleAddVia: function () {
        console.log("in handleAddVia - setting xy mode");
        jQuery('#action').val('via_link');
        wmEditor.hideAllDialogs();
        wmEditor.setMapMode('xy');
    },
    handleTidyLink: function () {
        jQuery('#action').val('link_tidy');
        document.frmMain.submit();
    },
    handleCloneNode: function () {
        jQuery('#action').val('clone_node');
        document.frmMain.submit();
    },
    handleMoveNode: function () {
        console.log("move node - setting xy mode");
        jQuery('#action').val('move_node');
        wmEditor.hideAllDialogs();
        wmEditor.setMapMode("xy");
    },
    handleMapProperties: function () {

        console.log("in handleMapProperties");
        wmEditor.showMapProperties();
    }
};

jQuery(document).ready(wmEditor.start);
