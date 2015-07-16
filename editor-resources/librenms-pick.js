"use strict";
/*global jQuery:false */
/*global rra_path:false */
/*global base_url:false */
/*global overlib:false */
/*global aggregate:false */
/*global selected_host:false */

function librenmspicker() {
    // make sure it isn't already opened
    if (!newWindow || newWindow.closed) {
        newWindow = window.open("", "librenmspicker", "scrollbars=1,status=1,height=400,width=400,resizable=1");
    } else if (newWindow.focus) {
        // window is already open and focusable, so bring it to the front
        newWindow.focus();
    }

    // newWindow.location = "data-pick.php?command=link_step1";
    newWindow.location = "data-pick.php?command=link_step1";
}


function nodelibrenmspicker() {
    // make sure it isn't already opened
    if (!newWindow || newWindow.closed) {
		newWindow = window.open("", "librenmspicker", "scrollbars=1,status=1,height=400,width=400,resizable=1");
    } else if (newWindow.focus) {
        // window is already open and focusable, so bring it to the front
        newWindow.focus();
    }

    newWindow.location = "data-pick.php?command=node_step1";
}
