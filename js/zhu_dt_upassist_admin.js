/*
 * JavaScript for Dev Tools administration
 */
jQuery(document).ready(function () {

	jQuery('#zhu_dt_upassist_remove_core_update_lock_button').on('click', function (e) {

		var btn = jQuery('#zhu_dt_upassist_remove_core_update_lock_button');
		var text = btn.html();
		btn.html(zhu_dt_upassist_admin_local.removing).prop('disabled', true);

		jQuery.ajax({
			url: zhu_dt_upassist_admin_local.ajax_url,
			type: 'post',
			data: {
				'action': 'zhu_dt_upassist_remove_core_update_lock',
				'nonce': zhu_dt_upassist_admin_local.nonce
			},
			success: function (response) {
				response = response.trim();
				if (response.substring(0, 2) === '1|') {
					btn.html(response.substring(2)).prop('disabled', false);
				} else if (response.substring(0, 2) === '0|') {
					alert(response.substring(2));
					btn.html(text).prop('disabled', false);
				} else {
					alert(response);
					btn.html(text).prop('disabled', false);
				}
			},
			error: function (jqXHR, textStatus, errorThrown) {
				alert(textStatus + ' : ' + errorThrown);
				btn.html(text).prop('disabled', false);
			}
		});

	});
});
