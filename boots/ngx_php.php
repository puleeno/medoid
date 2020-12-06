<?php
/**
 * Create
 *
 * @link https://github.com/rryqszq4/ngx_php7
 */
class Medoid_Ngx_PHP {
	protected $image_url;
	protected $conn;

	// Load WordPress
	public function __construct() {
		// Load WordPress constants
		$wp_config = sprintf( '%s/wp-config.php', ngx_request_document_root() );
		if ( ! file_exists( $wp_config ) ) {
			return;
		}
		if (!defined('WP_CONTENT_DIR')) {
			define('WP_CONTENT_DIR', sprintf('%s/wp-content/', ngx_request_document_root()));
		}
		require_once $wp_config;
	}

	public function load_image_url_from_database() {
		$request_uri = ngx_request_uri();

		if ($request_uri === '/image/test') {
			$this->image_url = 'http://images.pexels.com/photos/4275889/pexels-photo-4275889.jpeg?auto=compress&cs=tinysrgb&dpr=1&w=500';
			return;
		}


		$servername = constant( 'DB_HOST' );
		$username   = constant( 'DB_USER' );
		$password   = constant( 'DB_PASSWORD' );
		$dbname     = constant( 'DB_NAME' );

		// Create connection
		$this->conn = mysqli_connect($servername, $username, $password);
	}

	public function set_image_url() {
		if ( empty( $this->image_url ) ) {
			ngx_exit( NGX_HTTP_NOT_FOUND );
		}
		ngx_var::set( 'image_url', $this->image_url );
	}
}
