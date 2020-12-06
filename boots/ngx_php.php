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
		require_once $wp_config;

		$servername = constant( 'DB_HOST' );
		$username   = constant( 'DB_USER' );
		$password   = constant( 'DB_PASSWORD' );
		$dbname     = constant( 'DB_NAME' );

		// Create connection
		$conn = mysqli_connect($servername, $username, $password);

		// Check connection
		if (!$conn) {
			echo "Connection failed: " . mysqli_connect_error();
			exit();
		}
		echo "Connected successfully";
	}

	public function load_image_url_from_database() {
	}

	public function set_image_url() {
		if ( empty( $this->image_url ) ) {
			ngx_exit( NGX_HTTP_NOT_FOUND );
		}
		ngx_var::set( 'image_url', $this->image_url );
	}
}
