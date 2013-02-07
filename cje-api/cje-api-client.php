<?php

require_once ABSPATH . WPINC . '/class-IXR.php';
require_once ABSPATH . WPINC . '/class-wp-http-ixr-client.php';

class CJE_API_Client {

	const OPTION = 'cje-api-client';
	const SLUG = 'custom-javascript';

	function __construct() {
		add_action( 'init', array( $this, 'handle_requests' ) );

		// Settings
		add_action( 'add_meta_boxes', array( $this, 'plugin_menu' ) );
		add_action( 'admin_init', array( $this, 'plugin_init' ) );

		// JS
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
	}

	function handle_requests() {
		if ( isset( $_POST['deploy'] ) ) {
			if ( check_admin_referer( self::OPTION, self::SLUG ) )
				$this->deployjs();
		} elseif ( isset( $_POST['clone'] ) ) {
			if ( check_admin_referer( self::OPTION, self::SLUG ) )
				$this->clonejs();
		} elseif ( isset( $_POST['merge'] ) ) {
			wp_safe_redirect( 'themes.php?page=custom-javascript' );
		} elseif ( isset( $_REQUEST['deploy-status'] ) && $_REQUEST['deploy-status'] == 'error' ) {
			add_action( 'admin_notices', array( $this, 'deploy_error' ) );
		} elseif ( isset( $_REQUEST['deploy-status'] ) && $_REQUEST['deploy-status'] == 'success' ) {
			add_action( 'admin_notices', array( $this, 'deploy_success' ) );
		} elseif ( isset( $_REQUEST['clone-status'] ) && $_REQUEST['clone-status'] == 'error' ) {
			add_action( 'admin_notices', array( $this, 'clone_error' ) );
		} elseif ( isset( $_REQUEST['clone-status'] ) && $_REQUEST['clone-status'] == 'success' ) {
			add_action( 'admin_notices', array( $this, 'clone_success' ) );
		}
	}

	function deploy_error() {
		printf( '<div class="error"><p><strong>%s</strong></p></div>', __( 'Problem deploying JavaScript. Check login info.', 'cje-api' ) );
	}

	function deploy_success() {
		printf( '<div class="updated"><p><strong>%s</strong></p></div>', __( 'JavaScript deployed successfully.', 'cje-api' ) );
	}

	function clone_error() {
		printf( '<div class="error"><p><strong>%s</strong></p></div>', __( 'Problem cloning JavaScript. Check login info.', 'cje-api' ) );
	}

	function clone_success() {
		printf( '<div class="updated"><p><strong>%s</strong></p></div>', __( 'JavaScript cloned successfully.', 'cje-api' ) );
	}

	function deployjs() {
		$options = get_option( self::OPTION );
		$js_post = $this->get_js_post();
		$js = isset( $_REQUEST['f3'] ) ? $_REQUEST['f3'] : $js_post['post_content'];

		$client = new WP_HTTP_IXR_Client( 'http://' . $options['site'] . '/xmlrpc.php' );
		$client->query( 'enterprise.pushJS', $options['username'], $options['password'], $js );

		$this->clonejs();
	}

	function clonejs() {
		if ( $js = $this->get_remote_js() )
			$clone = $this->save_revision( $js );

		if ( $js && $clone )
			$status = 'success';
		else
			$status = 'error';

		$query = http_build_query(array(
			'page' => 'custom-javascript',
			'clone-status' => $status
		));

		wp_safe_redirect( 'themes.php?' . $query );
		exit;
	}

	function get_remote_js() {
		$options = get_option( self::OPTION );

		$client = new WP_HTTP_IXR_Client( 'http://' . $options['site'] . '/xmlrpc.php' );
		$client->query( 'enterprise.pullJS', $options['username'], $options['password'] );

		if ( $client->isError() )
			return false;

		return $client->getResponse();
	}

	function get_js() {
		if( !$post = $this->get_js_post() )
			return false;

		 return $post['post_content'];
	}

	function get_js_post() {
		$args = array(
			'numberposts' => 1,
			'post_type' => 'customjs',
			'post_status' => 'publish',
		);

		if ( $post = array_shift( get_posts( $args ) ) )
			return get_object_vars( $post );

		return false;
	}

	function save_revision( $js, $is_preview = false ) {

		if ( !$js_post = $this->get_js_post() ) {
			$post = array(
				'post_content' => $js,
				'post_status' => 'publish',
				'post_type' => 'customjs'
			);

			$post_id = wp_insert_post( $post );

			return true;
		}

		$js_post['post_content'] = $js;

		if ( false === $is_preview )
			return wp_update_post( $js_post );
	}

	/**
	 * Set up the settings menu
	 */
	function plugin_menu() {
		add_meta_box( self::SLUG, __( 'Local WordPress Enterprise Development', 'cje-api' ), array( $this, 'options_page' ), 'customjs', 'normal' );
	}

	function options_page() { ?>
		<form action="options.php" method="post">
			<?php settings_fields( self::OPTION ); ?>
			<?php do_settings_sections( self::SLUG ); ?>
			<p>
				<?php submit_button( __( 'Save Settings', 'cje-api' ), 'primary', null, false ); ?>
				<?php submit_button( __( 'Merge', 'cje-api' ), 'secondary', 'merge', false ); ?>
				<?php submit_button( __( 'Deploy', 'cje-api' ), 'secondary', 'deploy', false ); ?>
				<?php submit_button( __( 'Clone', 'cje-api' ), 'secondary', 'clone', false ); ?>
			</p>
			<?php wp_nonce_field( self::OPTION, self::SLUG ); ?>
		</form>
		<p>
			<?php
				$options = get_option( self::OPTION );
				$remote = $this->get_remote_js();
				$current = $this->get_js_post();
				$original = array_shift(get_posts(array(
					'numberposts' => 1,
					'post_type' => 'revision',
					'post_parent' => $current['ID'],
					'post_status' => 'inherit',
					'orderby' => 'post_date',
					'order' => 'ASC'
				)));

				if ( !empty( $current ) && !empty( $original ) ):
					$original = $original->post_content;
					$current  = $current['post_content'];
					$remote   = $remote;

					// fix a merge conflict
					if ( $remote != $current && $remote != $original ): ?>
						<textarea id="f0" style="display:none"><?php echo $original; ?></textarea>
						<textarea id="f1" style="display:none"><?php echo $current; ?></textarea>
						<textarea id="f2" style="display:none"><?php echo $remote; ?></textarea>
					<?php endif; ?>
				<?php endif; ?>
		</p>
<?php }

	function plugin_init(){
		register_setting( self::OPTION, self::OPTION, array( $this, 'sanitize_text_field' ) );

		add_settings_section( self::SLUG, null, null, self::SLUG );

		add_settings_field( 'site', __( 'Site', 'cje-api' ), array( $this, 'render_site' ), self::SLUG, self::SLUG );
		add_settings_field( 'username', __( 'Username', 'cje-api' ), array( $this, 'render_username' ), self::SLUG, self::SLUG );
		add_settings_field( 'password', __( 'Password', 'cje-api' ), array( $this, 'render_password' ), self::SLUG, self::SLUG );
	}

	function render_site() {
		$option = self::OPTION;
		$options = get_option( self::OPTION );
		echo "<input id='plugin_text_string' name='{$option}[site]' size='40' type='text' value='{$options['site']}' />";
	}

	function render_username() {
		$option = self::OPTION;
		$options = get_option( self::OPTION );
		echo "<input id='plugin_text_string' name='{$option}[username]' size='40' type='text' value='{$options['username']}' />";
	}

	function render_password() {
		$option = self::OPTION;
		$options = get_option( self::OPTION );
		echo "<input id='plugin_text_string' name='{$option}[password]' size='40' type='password' value='{$options['password']}' />";
	}

	function sanitize_text_field( $input ) {
		foreach ( $input as $k => $i )
			$input[$k] = sanitize_text_field($i);

		return $input;
	}

	function scripts() {
		if ( !isset( $_REQUEST['page'] ) || $_REQUEST['page'] != self::SLUG )
			return;

		wp_enqueue_script('diff', plugins_url('diff.js', __FILE__));
		wp_enqueue_script('init', plugins_url('init.js', __FILE__), array('diff', 'jquery'));
	}

}

new CJE_API_Client();
