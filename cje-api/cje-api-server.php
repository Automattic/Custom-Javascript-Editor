<?php

class CJE_API_Server {

	const NSPACE = 'enterprise';

	function __construct() {
		add_filter( 'xmlrpc_methods', array( $this, 'xmlrpc_methods' ) );
	}

	/**
	 * XML-RPC: enterprise.pushJS
	 */
	function pushJS( $args ) {
		global $wp_xmlrpc_server;
		$wp_xmlrpc_server->escape( $args );

		$username = esc_attr( $args[0] );
		$password = esc_attr( $args[1] );
		$js       = esc_html( $args[2] );

		// We need a username and password
		if ( ! $user = $wp_xmlrpc_server->login( $username, $password ) )
			return $wp_xmlrpc_server->error;

		// We need permission to bring in JS,
		// which should be higher than just authoring a new post
		if ( ! user_can( $user->ID, 'edit_theme_options' ) )
			return $wp_xmlrpc_server->error;

		// Nothing we can do if the post type doesn't exist
		if ( ! post_type_exists( 'customjs' ) )
			return $wp_xmlrpc_server->error;

		// Add JS Revision with new js
		return $this->save_revision( $js );
	}

	function pullJS( $args ) {
		global $wp_xmlrpc_server;
		$wp_xmlrpc_server->escape( $args );

		$username = esc_attr( $args[0] );
		$password = esc_attr( $args[1] );

		// We need a username and password
		if ( ! $user = $wp_xmlrpc_server->login( $username, $password ) )
			return $wp_xmlrpc_server->error;

		// We need permission to bring in JS,
		// which should be higher than just authoring a new post
		if ( ! user_can( $user->ID, 'edit_theme_options' ) )
			return $wp_xmlrpc_server->error;

		// Nothing we can do if the post type doesn't exist
		if ( ! post_type_exists( 'customjs' ) )
			return $wp_xmlrpc_server->error;

		return $this->get_js();
	}

	/**
	 * List of new XML-RPC methods
	 */
	function xmlrpc_methods( $methods ) {
		$namespace = self::NSPACE;
		$methods["$namespace.pushJS"] = array( $this, 'pushJS' );
		$methods["$namespace.pullJS"] = array( $this, 'pullJS' );
		return $methods;
	}

	/**
	 * Get the JS
	 */
	function get_js() {
		if( !$post = $this->get_js_post() )
			return false;

		 return $post['post_content'];
	}

	/**
	 * Get the javascript post
	 */
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

	/**
	 * Add a revision
	 */
	function save_revision( $js, $is_preview = false ) {

		if ( !$js_post = $this->get_js_post() ) {
			$post = array(
				'post_content' => $js,
				'post_status' => 'publish',
				'post_type' => 'customjs',
			);

			$post_id = wp_insert_post( $post );

			return true;
		}

		$js_post['post_content'] = $js;

		if ( false === $is_preview )
			return wp_update_post( $js_post );
	}

}

new CJE_API_Server();
