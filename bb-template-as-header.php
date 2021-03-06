<?php
/*
Plugin Name: BB Template as Header
Plugin URI: http://www.wpbeaverbuilder.com
Description: Lets you select a template from the BB-theme customizer. You can use it as a header or footer across your entire website.
Author: Jatacid
Version: 2.7
Author URI: http://www.wpbeaverbuilder.com

*/

//checks for BB-theme
$theme = wp_get_theme();
if ('bb-theme' == $theme->name || 'Beaver Builder Theme' == $theme->parent_theme) {


if (!function_exists( 'github_plugin_updater_test_init' )) {
function github_plugin_updater_test_init() {
// ... proceed to declare your function
include_once 'updater.php';
define( 'WP_GITHUB_FORCE_UPDATE', true );
}
}
add_action( 'init', 'github_plugin_updater_test_init' );
function btah_updater() {
	if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin
$login = 'jatacid/bb-template-as-header';

		$config = array(
			'slug' => plugin_basename( __FILE__ ),
			'proper_folder_name' => 'bb-template-as-header',
			'api_url' => 'https://api.github.com/repos/' . $login,
			'raw_url' => 'https://raw.github.com/' . $login .'/master',
			'github_url' => 'https://github.com/'. $login,
			'zip_url' => 'https://github.com/'. $login .'/archive/master.zip',
			'sslverify' => true,
			'requires' => '3.0',
			'tested' => '3.3',
			'readme' => 'README.md',
			'access_token' => '',
		);
		new WP_GitHub_Updater( $config );
}
}
add_action( 'init', 'btah_updater' );








function custom_register_theme_customizer( $wp_customize ) {
// Add the Custom Template Settings to the customizer.
  $wp_customize->add_section( 'custom-media', array(
    'title'=> __( 'Custom Template Settings', 'fl-automator' ),
    'description' => __( 'Enter the id for a template to insert the beaver builder template on every page of your website.', 'fl-automator' ),
    'priority'=> 130,
    ) );

    $wp_customize->add_setting('custom_header_template', array(
      'default' => 'Choose A Template',
      )
    );

      $wp_customize->add_control('custom_header_template', array(
        'label' => 'Header Template',
        'description' => 'Choose a saved BB template/row to use as a header. It will have a custom CSS class of #custom-header and be wrapped in header tags',
        'section' => 'custom-media',
        'type' => 'select',
        'choices' => get_bb_templates()
        )
      );


    $wp_customize->add_setting('custom_footer_template', array(
      'default' => 'Choose A Template',
      )
    );

      $wp_customize->add_control('custom_footer_template', array(
        'label' => 'Footer Template',
        'description' => 'Choose a saved BB template/row to use as a footer. It will have a custom CSS Class of #custom-footer and be wrapped in footer tags',
        'section' => 'custom-media',
        'type' => 'select',
        'choices' => get_bb_templates()
        )
      );

}
add_action( 'customize_register', 'custom_register_theme_customizer' );






function insert_custom_template() {
$settings =  FLCustomizer::get_mods();
$template = $settings['custom_header_template'];
if ($template !== '' ){
echo '<header id="custom-header">' . do_shortcode('[fl_builder_insert_layout id="'.$template.'"]') . '</header>';
} 
}
add_action( 'fl_after_header', 'insert_custom_template' );

function insert_custom_footer_template() {
$settings =  FLCustomizer::get_mods();
$template = $settings['custom_footer_template'];
if ($template !== '' ){
echo '<footer id="custom-footer">' . do_shortcode('[fl_builder_insert_layout id="'.$template.'"]') . '</footer>';
} 
}
add_action( 'fl_before_footer', 'insert_custom_footer_template' );




function get_bb_templates() {
    $data  = array();
    $query = new WP_Query( array(
        'post_type'     => 'fl-builder-template',
        'orderby'       => 'title',
        'order'       => 'ASC',
        'posts_per_page'  => '-1'
    ));

  $data = array(
        '' => 'Choose A Template'
    );

foreach( $query->posts as &$post ) {
        $data[ $post->ID ] = $post->post_title;
    }
    return $data;
}






















}