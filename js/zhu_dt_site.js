/*
 * Javascript to load into main site to support Dev Tools.  
 * Only loaded if the development tool bar is to be rendered
 */

jQuery(document).ready(function () {
    jQuery('#zhu_dt_stb_menu').show();

    jQuery('.zhu_dt_stb_box').draggable({
        handle: '.zhu_dt_stb_drag_handle'
    });

    jQuery('#zhu_dt_toolbar_guid').on('click', show_generate_GUID);
});

var zhu_dt_last_z_index = 9999;

/*
 * Extend jQuery.  Add method to ensure that the selected area, as best as possible, is visible 
 */
jQuery.fn.fit_in_viewport = function () {

    //ensure that our pop-up div is, as best as we can within the viewport area
    var element_left = this.offset().left;
    var element_top = this.offset().top;
    var element_width = this.outerWidth();
    var element_height = this.outerHeight();
    var viewport_left = jQuery(window).scrollLeft();
    var viewport_top = jQuery(window).scrollTop();
    var viewport_width = jQuery(window).width();
    var viewport_height = jQuery(window).height();

    var to_move = false;
    //has right hand of 'this', past edge of viewport?
    if ((element_left + element_width) > (viewport_left + viewport_width)) {
        //move to the left with a buffer on the right
        element_left = viewport_left + viewport_width - element_width - 20;
        to_move = true;
    }

    //is left off view?
    if (element_left < viewport_left) {
        //move to the left with a buffer on the left
        element_left = viewport_left + 20;
        to_move = true;
    }

    //is bottom of 'this', past edge of viewport?
    if ((element_top + element_height) > (viewport_top + viewport_height)) {
        //move up with a buffer on the bottom
        element_top = viewport_top + viewport_height - element_height - 20;
        to_move = true;
    }

    //if top off view?
    if (element_top < viewport_top) {
        //move to the top with a buffer on the top
        element_top = viewport_top + 30;            //30 incase admin bar is shown 
        to_move = true;
    }

    if (to_move) {
        this.css({top: element_top, left: element_left});
    }
};

/**
 * Extend jQuery.  Sets the main content of a tool's pop-up window
 * 
 * @param {string} content      HTML to place into the tool's window area
 * @returns {undefined}
 */
jQuery.fn.set_content = function (content) {
    this.find('.zhu_dt_div_content').html(content);
};

/**
 * Extend jQuery.  Sets the header of a tool's pop-up window
 * @param {string} text         Text (HTML) to display
 * @returns {undefined}
 */
jQuery.fn.set_header = function (text) {
    this.find('.zhu_dt_stb_drag_handle').html(text);
}

/**
 * Creates a pop-up window for a tool
 * 
 * @param {event} e                 JavaScript event object 
 * @param {string} div_id           Unique ID to give the pop-up window's outer most div
 * @param {string} title            Title (HTML) to display     
 * @param {string} box_style        CSS to include in the pop-up window's outer most div's style attribute
 * @param {string} content_style    Css to include in the pop-up window's content area div's style attribute
 * @returns {window.jQuery|jQuery|zhu_dt_create_pop_up.div}
 */
function zhu_dt_create_pop_up(e, div_id, title, box_style, content_style) {
    var div = jQuery('#' + div_id);

    //if div already exists, then toggle content
    if (div.length === 0) {

        content_style += ';max-height:' + (jQuery(window).height() * 0.8) + 'px';

        var minmax = "<div class='zhu_dt_popup_minmax' id='min_" + div_id + "'>&#9650;</div><div id='max_" + div_id + "' class='zhu_dt_popup_minmax' style='display:none'>&#9660;</div>";

        // add new div
        jQuery('body').prepend("<div id='" + div_id + "' class='zhu_dt_stb_box zhu_dt_stb_popup_box'  style='display:none;" + box_style + "'><div class='zhu_dt_stb_box_inner'><div class='zhu_dt_popup_close' id='close_" + div_id + "' >&#10006;</div>" + minmax + "<div class='zhu_dt_stb_drag_handle'>&nbsp;" + title + "</div><div id='content_outer" + div_id + "' class='zhu_dt_div_content_outer' style='" + content_style + "'><div class='zhu_dt_div_content'></div></div></div></div>");

        jQuery('#close_' + div_id).on('click', function () {
            jQuery('#' + div_id).hide();
        });

        jQuery('#min_' + div_id + ', #max_' + div_id).on('click', function () {
            // preserve width, do box does not shrink 
            var div = jQuery('#' + div_id);
            var w = div.width();
            // var h = jQuery('#' + div_id).height();

            //determine if currently visible 
            var co = jQuery('#content_outer' + div_id + ':visible');
            if (co.length) {
                // currently visible
                jQuery('#min_' + div_id).hide();
                jQuery('#max_' + div_id).show();

                jQuery('#' + div_id).width(w);

                //just hiding is not enough, as jQuery's draggable will fix the height when dragger
                div.attr('old_height', div.height());
                div.height(26);
                div.css('resize','none');
                co.hide();
            } else {
                jQuery('#min_' + div_id).show();
                jQuery('#max_' + div_id).hide();

                var co = jQuery('#content_outer' + div_id);

                //restore previoud height
                div.height(div.attr('old_height'));
                div.css('resize','both');
                co.show();
            }
        });

        div = jQuery('#' + div_id);

        div.css({'left': e.pageX, 'top': e.pageY, 'z-index': ++zhu_dt_last_z_index}).draggable({
            handle: '.zhu_dt_stb_drag_handle'
        });
    }

    return div;
}


function show_generate_GUID() {
    var cookie_name = 'zhu_dt_toolbar_guid';

    var res = document.cookie.match("(^|[^;]+)\\s*" + cookie_name + "\\s*=\\s*([^;]+)");
    var activation_id = '';

    if (res) {
        activation_id = res.pop();
    }

    if (activation_id === '') {
        activation_id = 'xxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });

        var dt = new Date();
        dt.setDate(dt.getDate() + 365);
        document.cookie = cookie_name + "=" + activation_id + "; expires=" + dt.toUTCString();
    }

    alert('Enter the Activation ID below into the Zhu Dev Tools Client Toolbar settings, then logout of WordPress for this toolbar to be displayed when not logged into WordPress\n\nActivation ID = ' + activation_id);
}
