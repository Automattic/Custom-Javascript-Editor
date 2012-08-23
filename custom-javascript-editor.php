<?php /*
Plugin Name:  Custom Javascript Editor
Plugin URI:   http://github.com/Automattic/custom-javascript-editor
Description:  Write custom javascript right from wp-admin!
Version:      1.0
Author:       Automattic
Author URI:   http://automattic.com
License:      GPLv2 or later
*/

class Custom_Javascript_Editor {

	const POST_TYPE = 'customjs';
	const PAGE_SLUG = 'custom-javascript';

	var $parent_slug = 'themes.php';
	var $capability = 'edit_theme_options';

	function __construct() {

		// Register the post type and allow the menu position and capability to be filtered
		add_action( 'init', array( $this, 'action_init' ) );

		// Override the edit link
		add_filter( 'get_edit_post_link', array( $this, 'revision_edit_link' ) );

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'handle_form' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		// Print scripts on the front end
		add_action( 'wp_print_footer_scripts', array( $this, 'print_scripts' ), 100 );
	}

	function action_init() {

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
		$title = __( 'Custom Javascript', 'custom-javascript-editor' );
		add_submenu_page( $this->parent_slug, $title, $title, $this->capability, self::PAGE_SLUG, array( $this, 'javascript_editor' ) );
	}

	function saved() {
		echo '<div id="message" class="updated fade"><p><strong>' . __('Javascript saved.', 'custom-javascript-editor' ) . '</strong></p></div>';
	}

	function print_scripts() {
		if ( ! is_admin() && strlen( $this->get_js() ) > 0 ) { ?>
				<script><?php echo wp_kses_decode_entities( stripslashes( $this->get_js() ) ); ?></script>
<?php
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
			<h2><?php esc_html_e( 'Custom Javascript', 'custom-javascript-editor' ); ?></h2>
			<form style="margin-top: 10px;" method="POST">
				<?php wp_nonce_field( 'custom-javascript-editor', 'custom-javascript-editor' ) ?>
				<textarea name="javascript" rows=20 style="width: 100%"><?php
					if ( $this->get_js() )
						echo stripslashes( $this->get_js() );
				?></textarea>
				<?php submit_button( __( 'Update', 'custom-javascript-editor' ), 'button-primary alignright', 'update', false, array( 'accesskey' => 's' ) ); ?>
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

	function handle_form() {

		if ( !isset( $_REQUEST['javascript'] ) || !isset( $_REQUEST['page'] ) || self::PAGE_SLUG != $_REQUEST['page'] )
			return;

		check_admin_referer( 'custom-javascript-editor', 'custom-javascript-editor' );

		//process
		$js = $_REQUEST['javascript'];
		$js = wp_kses( $js, array('script') );
		$js = esc_html( $js );

		//save
		$saved = $this->save_revision( $js );
		if ( $saved )
			add_action( 'admin_notices', array( $this, 'saved' ) );

		return;
	}

}

new Custom_Javascript_Editor();