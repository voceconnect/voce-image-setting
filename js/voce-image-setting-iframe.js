jQuery(document).ready(function($){
	var win = window.dialogArguments || opener || parent || top;
	$('body#media-upload').on('click', 'a.set-voce-image-setting', function(e){
		e.preventDefault();
		win.setVoceImageSetting( $(this).data('attachment-id'), $(this).data('setting-key'), $(this).data('setting-group'), $(this).data('setting-page'), $(this).data('nonce') );
		return false;
	});
});