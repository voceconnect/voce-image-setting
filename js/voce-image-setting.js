/* global wpAjax, tb_remove */
var setVoceImageSetting;

jQuery(document).ready(function($){

	$(document).on('click', '.remove-voce-image-setting', function(e){
		e.preventDefault();
		var nonce = $(this).data('nonce'),
		setting_key = $(this).parents('.voce-image-setting').data('setting-key'),
		setting_group = $(this).parents('.voce-image-setting').data('setting-group'),
		setting_page = $(this).parents('.voce-image-setting').data('setting-page');

		setVoceImageSetting( -1, setting_key, setting_group, setting_page, nonce );
	});

	setVoceImageSetting = function( id, setting_key, setting_group, setting_page, nonce ){
		$.post( ajaxurl, {
			action: 'set_voce_image_setting',
			attachment_id: id,
			setting_page : setting_page,
			setting_group : setting_group,
			setting_key : setting_key,
			_ajax_nonce : nonce
		}, function(r){
			var res = wpAjax.parseAjaxResponse(r, 'ajax-response');
			if( !res || res.errors ){
				return;
			}

			tb_remove();
			$('#' + setting_page + '-' + setting_group + '-' + setting_key).replaceWith(res.responses[0].data);
		});
	};
});