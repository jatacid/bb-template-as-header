<?php


/*  Shortcode Format:
	[insert page='{slug}|{id}' display='title|link|excerpt|excerpt-only|content|all|{custom-template.php}' class='any-classes']
*/

// Define the InsertPagesPlugin class (variables and functions)
if ( !class_exists( 'InsertPagesPlugin' ) ) {
	class InsertPagesPlugin {
		// Save the id of the page being edited
		protected $pageID;

		// Constructor
		public function InsertPagesPlugin() {
			// Include the code that generates the options page.
			require_once( dirname( __FILE__ ) . '/options.php' );
		}

		// Getter/Setter for pageID
		function getPageID() {
			return $this->pageID;
		}
		function setPageID( $id ) {
			return $this->pageID = $id;
		}

		// Action hook: Wordpress 'init'
		function insertPages_init() {
			add_shortcode( 'insert', array( $this, 'insertPages_handleShortcode_insert' ) );
		}

		// Action hook: Wordpress 'admin_init'
		function insertPages_admin_init() {
			// Get options set in WordPress dashboard (Settings > Insert Pages).
			$options = get_option( 'wpip_settings' );
			if ( $options === FALSE ) {
				$options = wpip_set_defaults();
			}

			// Add TinyMCE toolbar button filters only if current user has permissions
			if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) && get_user_option( 'rich_editing' )=='true' ) {

				// Register the TinyMCE toolbar button script
				wp_enqueue_script(
					'wpinsertpages',
					plugins_url( '/js/wpinsertpages.js', __FILE__ ),
					array( 'wpdialogs' ),
					'20151022'
				);
				wp_localize_script(
					'wpinsertpages',
					'wpInsertPagesL10n',
					array(
						'update' => __( 'Update' ),
						'save' => __( 'Insert Page' ),
						'noTitle' => __( '(no title)' ),
						'noMatchesFound' => __( 'No matches found.' ),
						'l10n_print_after' => 'try{convertEntities(wpLinkL10n);}catch(e){};',
						'format' => $options['wpip_format'],
					)
				);

				// Register the TinyMCE toolbar button styles
				wp_enqueue_style(
					'wpinsertpagescss',
					plugins_url( '/css/wpinsertpages.css', __FILE__ ),
					array( 'wp-jquery-ui-dialog' ),
					'20151022'
				);

				add_filter( 'mce_external_plugins', array( $this, 'insertPages_handleFilter_mceExternalPlugins' ) );
				add_filter( 'mce_buttons', array( $this, 'insertPages_handleFilter_mceButtons' ) );

				//load_plugin_textdomain('insert-pages', false, dirname(plugin_basename(__FILE__)).'/languages/');
			}

		}


		// Shortcode hook: Replace the [insert ...] shortcode with the inserted page's content
		function insertPages_handleShortcode_insert( $atts, $content = null ) {
			global $wp_query, $post, $wp_current_filter;
			extract( shortcode_atts( array(
				'page' => '0',
				'display' => 'all',
				'class' => '',
				'inline' => false,
			), $atts ) );

			// Get options set in WordPress dashboard (Settings > Insert Pages).
			$options = get_option( 'wpip_settings' );
			if ( $options === FALSE ) {
				$options = wpip_set_defaults();
			}

			// Validation checks.
			if ( $page === '0' ) {
				return $content;
			}

			// Trying to embed same page in itself.
			if ( $page == $post->ID || $page == $post->post_name ) {
				return $content;
			}

			$should_apply_nesting_check = true;
			/**
			 * Filter the flag indicating whether to apply deep nesting check
			 * that can prevent circular loops. Note that some use cases rely
			 * on inserting pages that themselves have inserted pages, so this
			 * check should be disabled for those individuals.
			 *
			 * @param bool $apply_the_content_filter Indicates whether to apply the_content filter.
			 */
			$should_apply_nesting_check = apply_filters( 'insert_pages_apply_nesting_check', $should_apply_nesting_check );

			// Don't allow inserted pages to be added to the_content more than once (prevent infinite loops).
			if ( $should_apply_nesting_check ) {
				$done = false;
				foreach ( $wp_current_filter as $filter ) {
					if ( 'the_content' == $filter ) {
						if ( $done ) {
							return $content;
						} else {
							$done = true;
						}
					}
				}
			}

			// Convert slugs to page IDs to standardize query_posts() lookup below.
			if ( ! is_numeric( $page ) ) {
				$page_object = get_page_by_path( $page, OBJECT, get_post_types() );
				$page = $page_object ? $page_object->ID : $page;
			}

			if ( is_numeric( $page ) ) {
				$args = array(
					'p' => intval( $page ),
					'post_type' => get_post_types(),
				);
			} else {
				$args = array(
					'name' => esc_attr( $page ),
					'post_type' => get_post_types(),
				);
			}

			query_posts( $args );

			$should_apply_the_content_filter = true;
			/**
			 * Filter the flag indicating whether to apply the_content filter to post
			 * contents and excerpts that are being inserted.
			 *
			 * @param bool $apply_the_content_filter Indicates whether to apply the_content filter.
			 */
			$should_apply_the_content_filter = apply_filters( 'insert_pages_apply_the_content_filter', $should_apply_the_content_filter );

			$should_use_inline_wrapper = ( $inline !== false && $inline !== 'false' ) || array_search( 'inline', $atts ) === 0 || ( array_key_exists( 'wpip_wrapper', $options ) && $options['wpip_wrapper'] === 'inline' );
			/**
			 * Filter the flag indicating whether to wrap the inserted content in inline tags (span).
			 *
			 * @param bool $use_inline_wrapper Indicates whether to wrap the content in span tags.
			 */
			$should_use_inline_wrapper = apply_filters( 'insert_pages_use_inline_wrapper', $should_use_inline_wrapper );

			// Disable the_content filter if using inline tags, since wpautop
			// inserts p tags and we can't have any inside inline elements.
			if ( $should_use_inline_wrapper ) {
				$should_apply_the_content_filter = false;
			}

			// Start our new Loop (only iterate once).
			if ( have_posts() ) {
				ob_start(); // Start output buffering so we can save the output to string

				// If Beaver Builder plugin is enabled, load any cached styles associated with the inserted page.
				// Note: Temporarily set the global $post->ID to the inserted page ID,
				// since Beaver Builder relies on it to load the appropraite styles.
				if ( class_exists( 'FLBuilder' ) ) {
					$old_post_id = $post->ID;
					$post->ID = $page;
					FLBuilder::enqueue_layout_styles_scripts( $page );
					$post->ID = $old_post_id;
				}

				// Show either the title, link, content, everything, or everything via a custom template
				// Note: if the sharing_display filter exists, it means Jetpack is installed and Sharing is enabled;
				// This plugin conflicts with Sharing, because Sharing assumes the_content and the_excerpt filters
				// are only getting called once. The fix here is to disable processing of filters on the_content in
				// the inserted page. @see https://codex.wordpress.org/Function_Reference/the_content#Alternative_Usage
				switch ( $display ) {
				case "title":
					the_post();
					$title_tag = $should_use_inline_wrapper ? 'span' : 'h1';
					echo "<$title_tag class='insert-page-title'>";
					the_title();
					echo "</$title_tag>";
					break;
				case "link":
					the_post();
					?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php
					break;
				case "excerpt":
					the_post();
					?><h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1><?php
					if ( $should_apply_the_content_filter ) the_excerpt(); else echo get_the_excerpt();
					break;
				case "excerpt-only":
					the_post();
					if ( $should_apply_the_content_filter ) the_excerpt(); else echo get_the_excerpt();
					break;
				case "content":
					the_post();
					if ( $should_apply_the_content_filter ) the_content(); else echo get_the_content();
					break;
				case "all":
					the_post();
					$title_tag = $should_use_inline_wrapper ? 'span' : 'h1';
					echo "<$title_tag class='insert-page-title'>";
					the_title();
					echo "</$title_tag>";
					if ( $should_apply_the_content_filter ) the_content(); else echo get_the_content();
					the_meta();
					break;
				default: // display is either invalid, or contains a template file to use
					$template = locate_template( $display );
					if ( strlen( $template ) > 0 ) {
						include $template; // execute the template code
					} else { // Couldn't find template, so fall back to printing a link to the page.
						the_post();
						?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php
					}
					break;
				}

				$content = ob_get_contents(); // Save off output buffer
				ob_end_clean(); // End output buffering
			} else {
				/**
				 * Filter the html that should be displayed if an inserted page was not found.
				 *
				 * @param string $content html to be displayed. Defaults to an empty string.
				 */
				$content = apply_filters( 'insert_pages_not_found_message', $content );
			}

			wp_reset_query();

			$wrapper_tag = $should_use_inline_wrapper ? 'span' : 'div';
		if  (!empty($content)) {

			$content = "<$wrapper_tag data-post-id='$page' class='insert-page insert-page-$page $class'>$content</$wrapper_tag>";
			return $content;
		}
			//return do_shortcode($content); // careful: watch for infinite loops with nested inserts
		}


		// Filter hook: Add a button to the TinyMCE toolbar for our insert page tool
		function insertPages_handleFilter_mceButtons( $buttons ) {
			array_push( $buttons, 'wpInsertPages_button' ); // add a separator and button to toolbar
			return $buttons;
		}

		// Filter hook: Load the javascript for our custom toolbar button
		function insertPages_handleFilter_mceExternalPlugins( $plugins ) {
			$plugins['wpInsertPages'] = plugins_url( '/js/wpinsertpages_plugin.js', __FILE__ );
			return $plugins;
		}

		/**
		 * Modified from /wp-admin/includes/internal-linking.php, function wp_link_dialog()
		 * Dialog for internal linking.
		 *
		 * @since 3.1.0
		 */
		function insertPages_wp_tinymce_dialog() {
			// If wp_editor() is being called outside of an admin context,
			// required dependencies for Insert Pages will be missing (e.g.,
			// wp-admin/includes/template.php will not be loaded, admin_head
			// action will not be fired). If that's the case, just skip loading
			// the Insert Pages tinymce button.
			if ( ! is_admin() || ! function_exists( 'page_template_dropdown' ) ) {
				return;
			}

			$options_panel_visible = '1' == get_user_setting( 'wpinsertpage', '0' ) ? ' options-panel-visible' : '';

			// Get ID of post currently being edited.
			$post_id = array_key_exists( 'post', $_REQUEST ) && intval( $_REQUEST['post'] ) > 0 ? intval( $_REQUEST['post'] ) : '';

			// display: none is required here, see #WP27605
			?><div id="wp-insertpage-backdrop" style="display: none"></div>
			<div id="wp-insertpage-wrap" class="wp-core-ui<?php echo $options_panel_visible; ?>" style="display: none">
			<form id="wp-insertpage" tabindex="-1">
			<?php wp_nonce_field( 'internal-inserting', '_ajax_inserting_nonce', false ); ?>
			<input type="hidden" id="insertpage-parent-pageID" value="<?php echo $post_id; ?>" />
			<div id="insertpage-modal-title">
				<?php _e( 'Insert page' ) ?>
				<div id="wp-insertpage-close" tabindex="0"></div>
			</div>
			<div id="insertpage-selector">
				<div id="insertpage-search-panel">
					<div class="insertpage-search-wrapper">
						<label>
							<span class="search-label"><?php _e( 'Search' ); ?></span>
							<input type="search" id="insertpage-search-field" class="insertpage-search-field" autocomplete="off" />
							<span class="spinner"></span>
						</label>
					</div>
					<div id="insertpage-search-results" class="query-results">
						<ul></ul>
						<div class="river-waiting">
							<span class="spinner"></span>
						</div>
					</div>
					<div id="insertpage-most-recent-results" class="query-results">
						<div class="query-notice"><em><?php _e( 'No search term specified. Showing recent items.' ); ?></em></div>
						<ul></ul>
						<div class="river-waiting">
							<span class="spinner"></span>
						</div>
					</div>
				</div>
				<p class="howto" id="insertpage-options-toggle"><?php _e( 'Options' ); ?></p>
				<div id="insertpage-options-panel">
					<div class="insertpage-options-wrapper">
						<label for="insertpage-slug-field">
							<span><?php _e( 'Slug or ID' ); ?></span>
							<input id="insertpage-slug-field" type="text" tabindex="10" autocomplete="off" />
							<input id="insertpage-pageID" type="hidden" />
						</label>
					</div>
					<div class="insertpage-format">
						<label for="insertpage-format-select">
							<?php _e( 'Display' ); ?>
							<select name="insertpage-format-select" id="insertpage-format-select">
								<option value='title'>Title</option>
								<option value='link'>Link</option>
								<option value='excerpt'>Excerpt</option>
								<option value='excerpt-only'>Excerpt only (no title)</option>
								<option value='content'>Content</option>
								<option value='all'>All (includes custom fields)</option>
								<option value='template'>Use a custom template &raquo;</option>
							</select>
							<select name="insertpage-template-select" id="insertpage-template-select" disabled="true">
								<option value='all'><?php _e( 'Default Template' ); ?></option>
								<?php page_template_dropdown(); ?>
							</select>
						</label>
					</div>
				</div>
			</div>
			<div class="submitbox">
				<div id="wp-insertpage-update">
					<input type="submit" value="<?php esc_attr_e( 'Insert Page' ); ?>" class="button button-primary" id="wp-insertpage-submit" name="wp-insertpage-submit">
				</div>
				<div id="wp-insertpage-cancel">
					<a class="submitdelete deletion" href="#"><?php _e( 'Cancel' ); ?></a>
				</div>
			</div>
			</form>
			</div>
			<?php
		}

		/** Modified from:
		 * Internal linking functions.
		 *
		 * @package WordPress
		 * @subpackage Administration
		 * @since 3.1.0
		 */
		function insertPages_insert_page_callback() {
			check_ajax_referer( 'internal-inserting', '_ajax_inserting_nonce' );
			$args = array();
			if ( isset( $_POST['search'] ) ) {
				$args['s'] = stripslashes( $_POST['search'] );
			}
			$args['pagenum'] = !empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
			$args['pageID'] =  !empty( $_POST['pageID'] ) ? absint( $_POST['pageID'] ) : 0;

			// Change search to slug or post ID if we're not doing a plaintext
			// search (e.g., if we're editing an existing shortcode and the
			// search field is populated with the post's slug or ID).
			if ( array_key_exists( 'type', $_POST ) && $_POST['type'] === 'slug' ) {
				$args['name'] = $args['s'];
				unset( $args['s'] );
			} else if ( array_key_exists( 'type', $_POST ) && $_POST['type'] === 'post_id' ) {
				$args['p'] = $args['s'];
				unset( $args['s'] );
			}

			$results = $this->insertPages_wp_query( $args );

			// Fail if our query didn't work.
			if ( ! isset( $results ) ) {
				die( '0' );
			}

			echo json_encode( $results );
			echo "\n";
			die();
		}

		/** Modified from:
		 * Performs post queries for internal linking.
		 *
		 * @since 3.1.0
		 * @param array   $args Optional. Accepts 'pagenum' and 's' (search) arguments.
		 * @return array Results.
		 */
		function insertPages_wp_query( $args = array() ) {
			$pts = get_post_types( array( 'public' => true ), 'objects' );
			$post_types = array_keys( $pts );

			/**
			 * Filter the post types that appear in the list of pages to insert.
			 *
			 * By default, all post types will apear.
			 *
			 * @since 2.0
			 *
			 * @param array   $post_types Array of post type names to include.
			 */
			$post_types = apply_filters( 'insert_pages_available_post_types', $post_types );

			$query = array(
				'post_type' => $post_types,
				'suppress_filters' => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'post_status' => 'publish',
				'order' => 'DESC',
				'orderby' => 'post_date',
				'posts_per_page' => 20,
				'post__not_in' => array( $args['pageID'] ),
			);

			$args['pagenum'] = isset( $args['pagenum'] ) ? absint( $args['pagenum'] ) : 1;
			$query['offset'] = $args['pagenum'] > 1 ? $query['posts_per_page'] * ( $args['pagenum'] - 1 ) : 0;

			// Search post content and post title.
			if ( isset( $args['s'] ) ) {
				$query['s'] = $args['s'];
			}

			// Search post_name (post slugs).
			if ( isset( $args['name'] ) ) {
				$query['name'] = $args['name'];
			}

			// Search post ids.
			if ( isset( $args['p'] ) ) {
				$query['p'] = $args['p'];
			}

			// Do main query.
			$get_posts = new WP_Query;
			$posts = $get_posts->query( $query );
			// Check if any posts were found.
			if ( ! $get_posts->post_count ) {
				return false;
			}

			// Build results.
			$results = array();
			foreach ( $posts as $post ) {
				if ( 'post' == $post->post_type ) {
					$info = mysql2date( __( 'Y/m/d' ), $post->post_date );
				} else {
					$info = $pts[ $post->post_type ]->labels->singular_name;
				}
				$results[] = array(
					'ID' => $post->ID,
					'title' => trim( esc_html( strip_tags( get_the_title( $post ) ) ) ),
					'permalink' => get_permalink( $post->ID ),
					'slug' => $post->post_name,
					'info' => $info,
				);
			}
			return $results;
		}

		function insertPages_add_quicktags() {
			if ( wp_script_is( 'quicktags' ) ) : ?>
				<script type="text/javascript">
					QTags.addButton( 'ed_insert_page', '[insert page]', "[insert page='your-page-slug' display='title|link|excerpt|excerpt-only|content|all']\n", '', '', 'Insert Page', 999 );
				</script>
			<?php endif;
		}

	}
}

// Initialize InsertPagesPlugin object
if ( class_exists( 'InsertPagesPlugin' ) ) {
	$insertPages_plugin = new InsertPagesPlugin();
}

// Actions and Filters handled by InsertPagesPlugin class
if ( isset( $insertPages_plugin ) ) {
	// Actions
	add_action( 'init', array( $insertPages_plugin, 'insertPages_init' ), 1 ); // Register Shortcodes here
	add_action( 'admin_head', array( $insertPages_plugin, 'insertPages_admin_init' ), 1 ); // Add TinyMCE buttons here
	add_action( 'before_wp_tiny_mce', array( $insertPages_plugin, 'insertPages_wp_tinymce_dialog' ), 1 ); // Preload TinyMCE popup
	add_action( 'wp_ajax_insertpage', array( $insertPages_plugin, 'insertPages_insert_page_callback' ) ); // Populate page search in TinyMCE button popup in this ajax call
	add_action( 'admin_print_footer_scripts', array( $insertPages_plugin, 'insertPages_add_quicktags' ) );
}
