<?php
/*
Plugin Name: BB Template as Header
Plugin URI: http://www.wpbeaverbuilder.com
Description: Lets you select a template that you've saved in BB to use as a header across every page of your website.
Author: Jatacid
Version: 1.0.3
Author URI: http://www.wpbeaverbuilder.com

*/



if ( ! defined( 'ABSPATH' ) ) die();
include( plugin_dir_path( __FILE__ ) . 'update.php');
// Hook for adding admin menus
if ( is_admin() ){ // admin actions
	
	$config = array(
			'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
			'proper_folder_name' => 'plugin-name', // this is the name of the folder your plugin lives in
			'api_url' => 'https://api.github.com/repos/jatacid/bb-template-as-header', // the github API url of your github repo
			'raw_url' => 'https://raw.github.com/jatacid/bb-template-as-header/master', // the github raw url of your github repo
			'github_url' => 'https://github.com/jatacid/bb-template-as-header', // the github url of your github repo
			'zip_url' => 'https://github.com/jatacid/bb-template-as-header/zipball/master', // the zip url of the github repo
			'sslverify' => true // wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
			'requires' => '3.0', // which version of WordPress does your plugin require?
			'tested' => '3.3', // which version of WordPress is your plugin tested up to?
			'readme' => 'README.MD' // which file to use as the readme for the version number
		);
		new WP_GitHub_Updater($config);
		// Hook for adding admin menu
		add_action( 'admin_menu', 'fc_op_page' );
		// Display the 'Settings' link in the plugin row on the installed plugins list page
		add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), 'fc_admin_plugin_actions', -10);
} 






require_once dirname( __FILE__ ) . '/insert-pages.php';
add_action( 'admin_menu', 'btah_add_admin_menu' );
add_action( 'admin_init', 'btah_settings_init' );


function btah_add_admin_menu(  ) { 

	add_menu_page( 'Insert Template as Header', 'Insert Template as Header', 'manage_options', 'bb_template_as_header', 'btah_options_page' );

}


function btah_settings_init(  ) { 

	register_setting( 'btahPluginPage', 'btah_settings' );

	add_settings_section(
		'btah_btahPluginPage_section', 
		__( 'Insert BB Template As a Header Options', 'wordpress' ), 
		'btah_settings_section_callback', 
		'btahPluginPage'
	);

	add_settings_field( 
		'btah_text_field_0', 
		__( 'Template ID number', 'wordpress' ), 
		'btah_text_field_0_render', 
		'btahPluginPage', 
		'btah_btahPluginPage_section' 
	);

	add_settings_field( 
		'btah_text_field_1', 
		__( 'Enter the classes you want to show in < header >tags', 'wordpress' ), 
		'btah_text_field_1_render', 
		'btahPluginPage', 
		'btah_btahPluginPage_section' 
	);


}


function btah_text_field_0_render(  ) { 

	$options = get_option( 'btah_settings' );
	$imgsrc = plugins_url() . '/bb-template-as-header/template_id.gif';
	?>

<p>Watch the Gif to learn how to get the POST ID </p>
<br>
	<?php
echo '<img src="' . $imgsrc .  '" > ';
?>
<br>

	<input type='text' name='btah_settings[btah_text_field_0]' value='<?php echo $options['btah_text_field_0']; ?>'>
	<?php

}

function btah_settings_section_callback(  ) { 

	echo __( 'Step 1 <br><Br> Build a Global Div in Beaver Builder that you want to use for a header. Put in any menus or logo images you want and save it as a global module. <br> <br> Step 2 <br> <br>
		Turn on your Templates admin by watching the gif below & copy pasting your Post_ID that you want to use as a header 
		 <br> <br> Step 3 <br> <br>
		 Add any classes for the header and hit save.
		  <br> <br> Step 4 <br> <br>
		  Turn off your bb-themes header by going into your customizer and selecting header layout to "none"', 'wordpress' );

}

function btah_text_field_1_render(  ) { 

	$options = get_option( 'btah_settings' );
	?>
	<input type='text' name='btah_settings[btah_text_field_1]' value='<?php echo $options['btah_text_field_1']; ?>'>
	<?php

}


function btah_options_page(  ) { 

	?>
	<form action='options.php' method='post'>
		
		<h2>Insert BB Template as Header</h2>
		
		<?php
		settings_fields( 'btahPluginPage' );
		do_settings_sections( 'btahPluginPage' );
		submit_button();
		?>
		
	</form>
	<?php

}




//Function to make it appear at the action fl_before_header
function bb_template_as_header(){



$options = get_option( 'btah_settings' );
$templateid= $options['btah_text_field_0'];
$classes= $options['btah_text_field_1'];


$a = do_shortcode('[insert page=' . $templateid . ' display="content"]');
$a = esc_html($a);

if  (!empty($a)) {
   echo '<header ' . $classes . '>' . do_shortcode('[insert page=' . $templateid . ' display="content"]') . '</header>';
}


}
add_action('fl_before_header', 'bb_template_as_header');


