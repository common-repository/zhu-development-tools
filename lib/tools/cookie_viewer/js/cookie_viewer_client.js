/*
 * JavaScript to support the Cookier Viewer tool
 * 
 */

var zhu_dt_main_cookie_div_id = 'zhu_dt_cv_div';
var zhu_dt_current_cookie_div = null;
var zhu_dt_cookie_popup = null;
var zhu_dt_last_cookie_date_time = null;

function zhu_dt_toggle_cookie_viewer(e) {
    e.preventDefault();



    zhu_dt_cookie_popup = jQuery('#' + zhu_dt_main_cookie_div_id);

    if (zhu_dt_cookie_popup.length !== 0 && zhu_dt_cookie_popup.is(':visible')) {
        zhu_dt_cookie_popup.hide();
    } else {
        if (zhu_dt_cookie_popup.length === 0) {
            zhu_dt_cookie_popup = zhu_dt_create_pop_up(e, zhu_dt_main_cookie_div_id, 'Cookie Viewer', '', '');
            zhu_dt_cookie_popup.set_content("<button type='button' id='zhu_dt_cookie_refresh' class='zhu_dt_stb_box_button'>Refresh</button><div style='padding-top:10px' id='zhu_dt_current_cookies'></div>");
        }

        zhu_dt_current_cookie_div = zhu_dt_cookie_popup.find('#zhu_dt_current_cookies');

        zhu_dt_cookie_refresh();

        zhu_dt_cookie_popup.show();
        zhu_dt_cookie_popup.fit_in_viewport();

        zhu_dt_cookie_popup.find('#zhu_dt_cookie_refresh').on('click', zhu_dt_cookie_refresh);
    }
}

//function zhu_dt_get_cookie_details() {
//    var cookies = document.cookie.split(';');
//    
//    var s = '';
//    if (cookies.length) {
//
//        //sork cookies
//        cookies.sort();
//        
//        for (var i = 0; i < cookies.length; i++) {
//            s += cookies[i].split('=')[0];
//            s += ' = ';
//            s += value = cookies[i].split('=')[1];
//            s += '\r';
//        }
//    }
//
//    return s;
//}

function zhu_dt_display_cookies() {
    var s = '';
    if (document.cookie) {
        var cookies = document.cookie.split(';');


        if (cookies.length) {

            //sork cookies
            cookies.sort();

            for (var i = 0; i < cookies.length; i++) {

                var cookie_name = cookies[i].split('=')[0];
                var cookie_value = cookies[i].split('=')[1];

                cookie_name = jQuery.trim(cookie_name);

                s += "<div class='zhu_dt_cookie_row_div'><button class='zhu_dt_delete_cookie_button' type='button' cookie_name='" + cookie_name + "'>delete</button>";
                s += "<span class='zhu_dt_cookie_pre'>";
                s += cookie_name + " = " + cookie_value;
                s += '</span></div>';
            }
        }
    }

    zhu_dt_current_cookie_div.html(s);

    var dt = new Date();
    zhu_dt_last_cookie_date_time = dt.toLocaleDateString() + ' ' + dt.toLocaleTimeString();
    zhu_dt_cookie_popup.set_header('&nbsp;Cookie Viewer (' + zhu_dt_last_cookie_date_time + ')');


    jQuery('.zhu_dt_delete_cookie_button').on('click', zhu_dt_delete_cookie);
}

/**
 * Called when the refresh button is clicked.
 *  
 * @returns {undefined}
 */
function zhu_dt_cookie_refresh() {
    zhu_dt_display_cookies();
}

/**
 * Called when a delete cookie button is closed.
 * 
 * @param {evente} e        JavaScript's event object
 * @returns {undefined}
 */
function  zhu_dt_delete_cookie(e) {
    var cookie_name = jQuery(e.target).attr('cookie_name');
    var dt = new Date();
    dt.setDate(dt.getDate() - 1);

    document.cookie = cookie_name + "= ; expires=" + dt.toUTCString();
    zhu_dt_cookie_refresh();
}
