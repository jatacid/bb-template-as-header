<?php

function wpip_add_admin_menu() {
	add_options_page( 'Insert Pages', 'Insert Pages', 'manage_options', 'insert_pages', 'wpip_options_page' );
}
add_action( 'admin_menu', 'wpip_add_admin_menu' );


function wpip_settings_init() {
	register_setting( 'wpipSettings', 'wpip_settings' );
	add_settings_section(
		'wpip_section',
		__( 'Insert Pages', 'wordpress' ),
		'wpip_settings_section_callback',
		'wpipSettings'
	);
	add_settings_field(
		'wpip_format',
		__( 'Shortcode format', 'wordpress' ),
		'wpip_format_render',
		'wpipSettings',
		'wpip_section'
	);
	add_settings_field(
		'wpip_wrapper',
		__( 'Wrapper for inserts', 'wordpress' ),
		'wpip_wrapper_render',
		'wpipSettings',
		'wpip_section'
	);




}
add_action( 'admin_init', 'wpip_settings_init' );


function wpip_set_defaults() {
	$options = get_option( 'wpip_settings' );
	if ( $options === FALSE ) {
		$options = array();
	}

	if ( ! array_key_exists( 'wpip_format', $options ) ) {
		$options['wpip_format'] = 'slug';
	}

	if ( ! array_key_exists( 'wpip_wrapper', $options ) ) {
		$options['wpip_wrapper'] = 'block';
	}

	update_option( 'wpip_settings', $options );

	return $options;
}
register_activation_hook( __FILE__, 'wpip_set_defaults' );


function wpip_settings_section_callback() {
	echo __( 'You may override some default settings here.', 'wordpress' );
}


function wpip_options_page() {
	?>
	<form action='options.php' method='post'>
		<?php
		settings_fields( 'wpipSettings' );
		do_settings_sections( 'wpipSettings' );
		submit_button();
		?>
	</form>
	<?php
}





function wpip_format_render() {
	$options = get_option( 'wpip_settings' );
	if ( $options === FALSE ) {
		$options = wpip_set_defaults();
	}
	?>
	<input type='radio' name='wpip_settings[wpip_format]' <?php checked( $options['wpip_format'], 'slug' ); ?> id="wpip_format_slug" value='slug'><label for="wpip_format_slug">Use page slugs (more readable). Example: <code>[insert&nbsp;page='hello&#8209;world&#8209;post'&nbsp;display='all']</code></label><br />
	<input type='radio' name='wpip_settings[wpip_format]' <?php checked( $options['wpip_format'], 'post_id' ); ?> id="wpip_format_id" value='post_id'><label for="wpip_format_id">Use page IDs (more compatible). Example: <code>[insert&nbsp;page='1'&nbsp;display='all']</code></label><br />
	<small><em>If your site reuses page slugs (for example, WPML sites often use the same page slug for each translation of the page in a different language), you should use page IDs.</em></small>
	<?php
}


function wpip_wrapper_render() {
	$options = get_option( 'wpip_settings' );
	if ( $options === FALSE ) {
		$options = wpip_set_defaults();
	}
	?>
	<input type='radio' name='wpip_settings[wpip_wrapper]' <?php checked( $options['wpip_wrapper'], 'block' ); ?> id="wpip_wrapper_block" value='block'><label for="wpip_wrapper_block">Use block wrapper (div). Example: <code>&lt;div data-post-id="1" class="insert-page">...&lt;/div></code></label><br />
	<input type='radio' name='wpip_settings[wpip_wrapper]' <?php checked( $options['wpip_wrapper'], 'inline' ); ?> id="wpip_wrapper_inline" value='inline'><label for="wpip_wrapper_inline">Use inline wrapper (span). Example: <code>&lt;span data-post-id="1" class="insert-page">...&lt;/span></code></label><br />
	<small><em>If you want to embed pages inline (for example, you can insert a link to a page in the flow of a normal paragraph), you should use inline tags. Note that the HTML spec does not allow block level elements within inline elements, so the inline wrapper has limited use.</em></small>
	<?php
}
