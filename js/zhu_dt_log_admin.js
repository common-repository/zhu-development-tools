/*
 * JavaScript for Dev Tools administration
 */
jQuery(document).ready(function () {
    jQuery('#zhu_dt_log_truncate_button').on('click', function (e) {
        e.preventDefault();

        if (confirm(zhu_dt_log_admin_local.are_you_sure)) {
            var btn = jQuery('#zhu_dt_log_truncate_button');
            var text = btn.html();
            btn.html(zhu_dt_log_admin_local.truncating).prop('disabled', true);

            jQuery.ajax({
                url: zhu_dt_log_admin_local.ajax_url,
                type: 'post',
                data: {
                    'action': 'zhu_dt_log_truncate',
                    'nonce' : zhu_dt_log_admin_local.nonce
                },
                success: function (response) {
                    response = response.trim();
                    if( response.substring(0,2) === '1|') {
                        btn.html(response.substring(2)).prop('disabled', false);
                    }
                    else {
                        alert(response);
                        btn.html(text).prop('disabled', false);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert(textStatus + ' : ' + errorThrown);
                    btn.html(text).prop('disabled', false);
                }
            });
        }
    });
});
