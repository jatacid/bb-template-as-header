<?php
/*
Plugin Name: BB Template as Header
Plugin URI: http://www.wpbeaverbuilder.com
Description: Lets you select a template that you've saved in BB to use as a header across every page of your website.
Author: Jatacid
Version: 1.0.7
Author URI: http://www.wpbeaverbuilder.com

*/

add_action( 'init', 'github_plugin_updater_test_init' );
function github_plugin_updater_test_init() {
	include_once 'updater.php';
	define( 'WP_GITHUB_FORCE_UPDATE', true );
	if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin
		$config = array(
			'slug' => plugin_basename( __FILE__ ),
			'proper_folder_name' => 'bb-template-as-header',
			'api_url' => 'https://api.github.com/repos/jatacid/bb-template-as-header',
			'raw_url' => 'https://raw.github.com/jatacid/bb-template-as-header/master',
			'github_url' => 'https://github.com/jatacid/bb-template-as-header',
			'zip_url' => 'https://github.com/jatacid/bb-template-as-header/archive/master.zip',
			'sslverify' => true,
			'requires' => '3.0',
			'tested' => '3.3',
			'readme' => 'README.md',
			'access_token' => '',
		);
		new WP_GitHub_Updater( $config );
	}
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


