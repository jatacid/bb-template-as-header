<?php
/*
Plugin Name: BB Template as Header
Plugin URI: http://www.wpbeaverbuilder.com
Description: Lets you select a template that you've saved in BB to use as a header across every page of your website.
Author: Jatacid
Version: 1.0.0
Author URI: http://www.wpbeaverbuilder.com
GitHub Plugin URI: https://github.com/jatacid/bb-template-as-header
GitHub Branch:     master
*/




require_once( '/updater/BFIGitHubPluginUploader.php' );
if ( is_admin() ) {
    new BFIGitHubPluginUpdater( __FILE__, 'jatacid', "bb-template-as-header" );
}


require_once dirname( __FILE__ ) . '/class-tgm-plugin-activation.php';

require_once dirname( __FILE__ ) . '/insert-pages.php';

add_action( 'tgmpa_register', 'my_theme_register_required_plugins' );
function my_theme_register_required_plugins() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(

		// This is an example of how to include a plugin from an arbitrary external source in your theme.
		array(
			'name'         => 'Beaver Builder Plugin', // The plugin name.
			'slug'         => 'bb-plugin', // The plugin slug (typically the folder name).
			//'source'       => 'http://www.google.com', // The plugin source.
			'required'     => true, // If false, the plugin is only 'recommended' instead of required.
			//'external_url' => 'https://www.google.com', // If set, overrides default API URL and points to an external URL.
		),
	);

	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 * Only uncomment the strings in the config array if you want to customize the strings.
	 */
	$config = array(
		'id'           => 'tgmmpa',                 // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug'  => 'themes.php',            // Parent menu slug.
		'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => 'This message and the links are broken, but you need to download the following plugins',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,                   // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.

	);

	tgmpa( $plugins, $config );
}





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


