<?php /*
Plugin Name:  Custom Javascript Editor
Plugin URI:   http://wordpress.org/extend/plugins/custom-javascript-editor/
Description:  Add custom Javascript to your site from an editor in the WordPress admin
Version:      0.9.1
Author:       Automattic
Author URI:   http://automattic.com
License:      GPLv2 or later
*/

class Custom_Javascript_Editor {

	const POST_TYPE = 'customjs';
	const PAGE_SLUG = 'custom-javascript';
	const enqueue_option = 'cje_enqueue_scripts';

	var $parent_slug = 'themes.php';
	var $capability = 'edit_theme_options';

	var $available_scripts = array();

	function __construct() {

		// Register the post type and allow the menu position and capability to be filtered
		add_action( 'init', array( $this, 'action_init' ) );

		// Override the edit link
		add_filter( 'get_edit_post_link', array( $this, 'revision_edit_link' ) );

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'handle_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		// Show an updated message if things have been updated
		if ( isset( $_REQUEST['page'], $_REQUEST['message'] ) && self::PAGE_SLUG == $_REQUEST['page'] && 'updated' == $_REQUEST['message'] )
			add_action( 'admin_notices', array( $this, 'saved' ) );

		// Print scripts on the front end
		add_action( 'wp_print_footer_scripts', array( $this, 'print_scripts' ), 100 );
	}

	function action_init() {

		$this->available_scripts = array(
				array(
						'name'           => __( 'jQuery', 'custom-javascript-editor' ),
						'identifier'     => 'jquery',
					),
				array(
						'name'           => __( 'jQuery Form', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-form',
					),
				array(
						'name'           => __( 'jQuery Color', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-color',
					),
				array(
						'name'           => __( 'jQuery UI Core', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-core',
					),
				array(
						'name'           => __( 'jQuery UI Accordion', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-accordion',
					),
				array(
						'name'           => __( 'jQuery UI Autocomplete', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-autocomplete',
					),
				array(
						'name'           => __( 'jQuery UI Slider', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-slider',
					),
				array(
						'name'           => __( 'jQuery UI Tabs', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-tabs',
					),
				array(
						'name'           => __( 'jQuery UI Sortable', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-sortable',
					),
				array(
						'name'           => __( 'jQuery UI Draggable', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-draggable',
					),
				array(
						'name'           => __( 'jQuery UI Droppable', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-droppable',
					),
				array(
						'name'           => __( 'jQuery UI Selectable', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-selectable',
					),
				array(
						'name'           => __( 'jQuery UI Datepicker', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-datepicker',
					),
				array(
						'name'           => __( 'jQuery UI Resizable', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-resizable',
					),
				array(
						'name'           => __( 'jQuery UI Dialog', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-dialog',
					),
				array(
						'name'           => __( 'jQuery UI Button', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-ui-button',
					),
				array(
						'name'           => __( 'jQuery Schedule', 'custom-javascript-editor' ),
						'identifier'     => 'jquery-schedule',
					),
				// @todo include moar scripts here
			);
		$this->available_scripts = apply_filters( 'cje_available_scripts', $this->available_scripts );

		$this->parent_slug = apply_filters( 'cje_parent_slug', $this->parent_slug );
		$this->capability = apply_filters( 'cje_capability', $this->capability );

		$args = array(
				'supports' => array(
						'revisions',
					),
				'public' => false,
				'rewrite' => false,
			);
		register_post_type( self::POST_TYPE, $args );
	}

	function get_js() {
		if( !$post = $this->get_js_post() )
			return false;

		 return $post['post_content'];
	}

	function get_js_post() {
		$args = array(
			'numberposts' => 1,
			'post_type' => self::POST_TYPE,
			'post_status' => 'publish',
		);

		if ( $post = array_shift( get_posts( $args ) ) )
			return get_object_vars( $post );
		
		return false;
	}

	function get_current_revision() {
		if ( !$js = $this->get_js_post() )
			return false;

		if ( !empty( $js['ID'] ) )
			$revisions = wp_get_post_revisions( $js['ID'], 'orderby=ID&order=DESC&limit=1' );

		if ( empty( $revisions ) )
			return $js;

		return get_object_vars( array_shift( $revisions ) );
	}

	function save_revision( $js, $is_preview = false ) {

		if ( !$js_post = $this->get_js_post() ) {
			$post = array(
				'post_content' => $js,
				'post_status' => 'publish',
				'post_type' => self::POST_TYPE,
			);

			$post_id = wp_insert_post( $post );

			return true;
		}

		$js_post['post_content'] = $js;

		if ( false === $is_preview )
			return wp_update_post( $js_post );
	}

	function revisions_meta_box() {
		$post = $this->get_js_post();
		$args = array(
			'numberposts' => 5,
			'orderby' => 'ID',
			'order' => 'DESC'
		);

		if ( isset( $_GET['show_all_rev'] ) )
			unset( $args['numberposts'] );

		wp_list_post_revisions( $post['ID'], $args );
	}

	function revision_edit_link( $post_link ) {
		global $post;

		if ( isset( $post ) && self::POST_TYPE == $post->post_type )
			if ( strstr( $post_link, 'action=edit' ) )
				$post_link = 'themes.php?page=' . self::PAGE_SLUG;

		return $post_link;
	}

	function menu() {
		add_submenu_page( $this->parent_slug, __( 'Custom Javascript Editor', 'custom-javascript-editor' ), __( 'Custom Javascript', 'custom-javascript-editor' ), $this->capability, self::PAGE_SLUG, array( $this, 'javascript_editor' ) );
	}

	function saved() {
		echo '<div id="message" class="updated fade"><p><strong>' . __('Javascript saved.', 'custom-javascript-editor' ) . '</strong></p></div>';
	}

	function print_scripts() {
		if ( ! is_admin() && strlen( $this->get_js() ) > 0 ) { ?>
				<script><?php echo html_entity_decode( wp_kses_decode_entities( $this->get_js() ) ); ?></script>
<?php
		}
	}

	/**
	 * Enqueue any selected Javascript for the frontend
	 */
	function action_wp_enqueue_scripts() {
		$enqueue_scripts = get_option( self::enqueue_option, array() );
		foreach( $enqueue_scripts as $script_identifier ) {
			$script = array_pop( wp_filter_object_list( $this->available_scripts, array( 'identifier' => $script_identifier ) ) );
			// @todo Support for dependencies and specifying the path
			if ( ! empty( $script ) )
				wp_enqueue_script( $script );
		}
	}

	function admin_scripts() {
		if ( isset( $_REQUEST['page'] ) && self::POST_TYPE == $_REQUEST['page'] ) {
			wp_enqueue_script( 'jslint', plugins_url( '/jslint/jslint.js', __FILE__ ) );
			wp_enqueue_script( 'initui', plugins_url( '/jslint/initui.js', __FILE__ ), array( 'jquery', 'jslint' ) );
		}
	}

	function javascript_editor() {
		global $screen_layout_columns;
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php esc_html_e( 'Custom Javascript Editor', 'custom-javascript-editor' ); ?></h2>
			<form style="margin-top: 10px;" method="POST">
				<div style="width: 100%">
				<?php wp_nonce_field( 'custom-javascript-editor', 'custom-javascript-editor' ) ?>
				<div id="cje-js-container" style="width: 80%; float: left;">
				<textarea name="javascript" rows=20 style="width: 100%"><?php
					if ( $this->get_js() )
						echo esc_textarea( html_entity_decode( wp_kses_decode_entities( $this->get_js() ) ) );
				?></textarea>
				 </div>
				<div id="cje-frameworks-container" style="float: right; width: 20%; height: 350px;">
					<div style="padding-left: 20px">
					<h3 style="margin: 0;"><?php esc_html_e( 'Load also:', 'custom-javascript-editor' ); ?></h3><br />
						<?php $this->scripts_selector(); ?>
					</div>
				</div>
				<div style="clear:both;"></div>
				<?php submit_button( __( 'Update', 'custom-javascript-editor' ), 'primary', 'update', false, array( 'accesskey' => 's' ) ); ?>
				</div>
			</form>
			<div id="jslint_errors">
				<h3><?php esc_html_e( 'Errors', 'custom-javascript-editor' ); ?></h3>
				<div class="errors"></div>
			</div>
			<div id="poststuff" style="clear:both;" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
			<?php
				add_meta_box( 'revisionsdiv', __( 'Javascript Revisions', 'custom-javascript-editor' ), array( $this, 'revisions_meta_box' ), 'custom-javascript', 'normal' );
				do_meta_boxes( 'custom-javascript', 'normal', $this->get_js_post() );
			?>
			</div>
		</div>
<?php }

	/**
	 * An interface for selecting from available frameworks to enqueue
	 */
	function scripts_selector() {
		$enqueue_scripts = get_option( self::enqueue_option, array() );

		foreach( $this->available_scripts as $script ) {
			echo '<label for="' . esc_attr( 'script-' . $script['identifier'] ) . '">';
			echo '<input id="' . esc_attr( 'script-' . $script['identifier'] ) . '" type="checkbox" name="enqueue_scripts[]"';
			echo ' value="' . esc_attr( $script['identifier'] ) . '"';
			if ( in_array( $script['identifier'], $enqueue_scripts ) )
				echo ' checked="checked"';
			echo ' />&nbsp;&nbsp;' . $script['name'] . '</label><br />';
		}
	}

	function handle_form() {

		if ( !isset( $_REQUEST['javascript'] ) || !isset( $_REQUEST['page'] ) || self::PAGE_SLUG != $_REQUEST['page'] )
			return;

		check_admin_referer( 'custom-javascript-editor', 'custom-javascript-editor' );

		if ( !current_user_can( $this->capability ) )
			wp_die( __( "Whoops, you don't have permission to do that.", 'custom-javascript-editor' ) );

		//process
		$js = $_REQUEST['javascript'];
		$js = wp_kses_post( $js );
		$js = esc_html( $js );

		//save
		$saved = $this->save_revision( $js );

		// Save available scripts too
		if ( ! empty( $_REQUEST['enqueue_scripts'] ) ) {
			$enqueue_scripts = array_map( 'sanitize_key', (array)$_REQUEST['enqueue_scripts'] );
			update_option( self::enqueue_option, $enqueue_scripts );
		} else {
			delete_option( self::enqueue_option );
		}

		$query_args = array(
				'page'       => self::PAGE_SLUG,
				'message'    => 'updated',
			);
		$admin_page = add_query_arg( $query_args, admin_url( $this->parent_slug ) );
		wp_safe_redirect( $admin_page );
		exit;
	}

}

new Custom_Javascript_Editor();
