<?php /*
Plugin Name:  WordPress.com Custom Javascript Editor
Plugin URI:   http://github.com/Automattic/custom-javascript-editor
Description:  Write custom javascript right from wp-admin!
Version:      1.0
Author:       Automattic
Author URI:   http://automattic.com
License:      GPLv2 or later
*/

class Custom_Javascript_Editor {

	const OPTION = 'customjs';
	const SLUG = 'custom-javascript';

	function __construct() {
		add_action( 'init', array( $this, 'create_post_type' ) );

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'handle_form' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'print_scripts' ), 100 );

		// Load JSLint
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	function create_post_type() {
		register_post_type( self::OPTION, array(
			'supports' => array( 'revisions' )
		) );
	}

	function get_js() {
		if( !$post = $this->get_js_post() )
			return false;

		 return $post['post_content'];
	}

	function get_js_post() {
		$args = array(
			'numberposts' => 1,
			'post_type' => self::OPTION,
			'post_status' => 'publish'
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
				'post_type' => self::OPTION
			);

			$post_id = wp_insert_post( $post );

			return true;
		}

		$js_post['post_content'] = $js;

		if ( false === $is_preview )
			return wp_update_post( $js_post );
	}

	function saved() {
		echo '<div id="message" class="updated fade"><p><strong>' . __('Javascript saved.') . '</strong></p></div>';
	}

	function menu() {
		$title = __( 'Custom Javascript' );
		add_theme_page( $title, $title, 'edit_theme_options', self::SLUG, array( $this, 'javascript_editor' ) );
	}

	function admin_scripts() {
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'custom-javascript' ) {
			wp_enqueue_script( 'json2', plugins_url( 'jslint/json2.js', __FILE__ ) );
			wp_enqueue_script( 'jslint', plugins_url( 'jslint/jslint.js', __FILE__ ) );
			wp_enqueue_script( 'adsafe', plugins_url( 'jslint/adsafe.js', __FILE__ ) );
			wp_enqueue_script( 'intercept', plugins_url( 'jslint/intercept.js', __FILE__ ), array( 'adsafe' ) );
		}
	}

	function print_scripts() {
		if ( ! is_admin() && strlen( get_option( 'custom-javascript-editor' ) ) > 0 ) { ?>
				<script><?php echo wp_kses_decode_entities( stripslashes( $this->get_js() ) ); ?></script>
<?php
		}
	}

	function javascript_editor() { ?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e( 'Custom Javascript' ); ?></h2>

			<div id="JSLINT_">
				<form style="margin-top: 10px;" method="POST">
					<?php wp_nonce_field( 'custom-javascript-editor', 'custom-javascript-editor' ) ?>
					<div id="JSLINT_SOURCE"><textarea name="javascript" rows=20 style="width: 100%"><?php
						if ( get_option( 'custom-javascript-editor' ) )
							echo stripslashes( $this->get_js() );
					?></textarea></div>
					<?php submit_button( __( 'Update' ), 'button-primary alignright', 'update', false, array( 'accesskey' => 's' ) ); ?>
				</form>
				<div id="JSLINT_EDITION" style="display: none;"></div>
				<div id="JSLINT_ERRORS" style="display: none;"><h1>Errors</h1><div></div></div>
				<div id="JSLINT_REPORT" style="display: none;"><h1>Function Report</h1><div></div></div>
				<div id="JSLINT_PROPERTIES" style="display: none;"></div>
				<div id="JSLINT_JSLINT"></div>

				<script src="<?php echo plugins_url( 'jslint/init_ui.js', __FILE__ ); ?>"></script>
			</div>
		</div>
<?php }

	function handle_form() {
		if ( !isset( $_REQUEST['javascript'] ) || !isset( $_REQUEST['page'] ) || self::SLUG != $_REQUEST['page'] )
			return;

		check_admin_referer( 'custom-javascript-editor', 'custom-javascript-editor' );

		//process
		$js = $_REQUEST['javascript'];
		$js = wp_kses( $js, array('script') );
		$js = esc_html( $js );

		//save
		$saved = $this->save_revision( $js );

		//tell user we saved
		if ( $saved )
			add_action( 'admin_notices', array( $this, 'saved' ) );

		return;
	}

}

new Custom_Javascript_Editor();