<?php

if ( !class_exists( 'Voce_Image_Setting' ) ){

	class Voce_Image_Setting {
		public static function initialize(){
			add_filter( 'media_upload_tabs', function($tabs){
				if ( isset( $_REQUEST['context'] ) && $_REQUEST['context'] ==  'voce-image-setting' )
					return array( 'library' => __('Image Library'), 'type' => __('From Computer') );

				return $tabs;
			});

			add_action( 'wp_ajax_set_voce_image_setting', function(){
				check_ajax_referer( 'set_voce_image_setting' );

				if( !isset($_REQUEST['attachment_id']) || !isset($_REQUEST['setting_page']) || !isset($_REQUEST['setting_group']) || !isset($_REQUEST['setting_key']) ){
					die(0);
				}

				$attachment_id = (int) $_REQUEST['attachment_id'];
				$setting_key = trim(sanitize_key($_REQUEST['setting_key']));
				$setting_group = trim(sanitize_key($_REQUEST['setting_group']));
				$setting_page = trim(sanitize_key($_REQUEST['setting_page']));

				if( $attachment_id == -1 ){
					Voce_Settings_API::GetInstance()->set_setting( $setting_key , $setting_group, '' );
					$response = new WP_Ajax_Response(array(
						'what'=>'voce-image-setting',
						'action'=>'set_voce_image_setting',
						'id'=> -1,
						'data' => Voce_Image_Setting::render_html( false, $setting_key, $setting_group, $setting_page, true )
					));
					$response->send();
				} elseif('attachment' == get_post_type($attachment_id)){
					Voce_Settings_API::GetInstance()->set_setting( $setting_key, $setting_group, $attachment_id );
					$response = new WP_Ajax_Response(array(
						'what'=>'voce-image-setting',
						'action'=>'set_voce_image_setting',
						'id'=> 1,
						'data' => Voce_Image_Setting::render_html( $attachment_id, $setting_key, $setting_group, $setting_page, true )
					));
					$response->send();
				}
			});

			add_action( 'admin_enqueue_scripts', function($hook){
				$allowed_hooks = apply_filters('voce-image-settings-js-hooks', array());
				if( 'settings_page_' == substr( $hook, 0, 14 ) || in_array($hook, $allowed_hooks) ) {
					add_thickbox();
					wp_enqueue_script('voce-image-setting', plugins_url( '/js/voce-image-setting.js', __FILE__ ), array( 'jquery', 'media-upload', 'wp-ajax-response' ) );
				} else if($hook == 'media-upload-popup'){
					wp_enqueue_script('voce-image-setting-iframe', plugins_url( '/js/voce-image-setting-iframe.js', __FILE__ ), array( 'jquery' ) );
				}
			});

			add_filter( 'attachment_fields_to_edit', function( $form_fields, $post ){
				if (( isset( $_REQUEST['context'] ) && $_REQUEST['context'] == 'voce-image-setting' ) ) {
					$setting_key = (isset($_REQUEST['setting_key'])) ? $_REQUEST['setting_key'] : '';
					$setting_group = (isset($_REQUEST['setting_group'])) ? $_REQUEST['setting_group'] : '';
					$setting_page = (isset($_REQUEST['setting_page'])) ? $_REQUEST['setting_page'] : '';
				} elseif( ( $referer = wp_get_referer() ) && ( $query_vars = wp_parse_args(parse_url($referer, PHP_URL_QUERY)) ) && (isset($query_vars['context'])) && ($query_vars['context'] == 'voce-image-setting' ) ){
					$setting_key = (isset($query_vars['setting_key'])) ? $query_vars['setting_key'] : '';
					$setting_group = (isset($query_vars['setting_group'])) ? $query_vars['setting_group'] : '';
					$setting_page = (isset($query_vars['setting_page'])) ? $query_vars['setting_page'] : '';
				} else {
					return $form_fields;
				}

				$html = sprintf( '<tr class="submit"><td></td><td><a data-attachment-id="%s" data-setting-page="%s" data-setting-group="%s" data-setting-key="%s" data-nonce="%s" class="set-voce-image-setting button">Choose Image</a></td></tr>',
					esc_attr( $post->ID ), esc_attr( $setting_page ), esc_attr( $setting_group ), esc_attr( $setting_key ), esc_attr( wp_create_nonce( 'set_voce_image_setting' ) ) );
				$form_fields = array(
					'voce-image-setting' => array( 'label' => '', 'input' => 'html', 'html' => $html)
				);

				return $form_fields;
			}, 20, 2 );
		}

		public static function display_image_select( $value, $setting, $args ){
			self::render_html( $value, $setting->setting_key, $setting->group->group_key, $setting->group->page->page_key );
			if(!empty($args['description']))
				printf('<span class="description">%s</span>', $args['description']);
		}

		public static function render_html( $value, $setting_key, $setting_group, $setting_page, $return = false ){
			$image_library_url = get_upload_iframe_src( 'image', null, 'library' );
			$image_library_url = remove_query_arg( 'TB_iframe', $image_library_url );
			$image_library_url = add_query_arg( array( 'context' => 'voce-image-setting', 'setting_key' => $setting_key, 'setting_group' => $setting_group, 'setting_page' => $setting_page, 'TB_iframe' => 1 ), $image_library_url );
			$link = '<a href="%s" class="%s button" %s>%s</a>';
			$image = ( $value && ($src = wp_get_attachment_image_src( $value, true )));

			// href, extra classes, extra attributes, text
			$link = ($image) ?
				sprintf( $link, '', 'remove-voce-image-setting', sprintf(' data-nonce="%s"', wp_create_nonce('set_voce_image_setting')), 'Remove Image' ) :
				sprintf( $link, $image_library_url, 'set-voce-image-setting thickbox', '', 'Set Image');

			$id = $setting_page . '-' . $setting_group . '-' . $setting_key;

			$html = sprintf( '<div id="%s" data-setting-page="%s" data-setting-group="%s" data-setting-key="%s" class="voce-image-setting"><div class="ajax-response"></div>',
				esc_attr($id), esc_attr($setting_page), esc_attr($setting_group), esc_attr($setting_key) );

			if($image)
				$html .= sprintf( '<img src="%s" style="max-width:500px; height:auto;" /><br/>', esc_url( $src[0] ) );

			$html .= $link . '</div>';

			if($return)
				return $html;

			echo $html;
		}

		public static function sanitize_image_select_callback($value, $setting, $args){
			return Voce_Settings_API::GetInstance()->get_setting( $setting->setting_key, $setting->group->group_key );
		}
	}
	add_action( 'init', array( 'Voce_Image_Setting', 'initialize' ) );

}