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
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			define( 'WP_CONTENT_DIR', sprintf( '%s/wp-content/', ngx_request_document_root() ) );
		}
		require_once $wp_config;
	}

	protected function detect_image_alias( $uri ) {
		$uri = trim( $uri, '/' );
		$uri = ltrim( $uri, 'images/' );
		if ( preg_match( '/\d{1,}x\d{1,}\/(.+)/', $uri, $matches ) ) {
			return array(
				'is_crop_image'  => true,
				'alias'          => $uri,
				'original_alias' => $matches[1],
			);
		}

		return array(
			'is_crop_image' => false,
			'alias'         => $uri,
		);
	}

	public function load_image_url_from_database() {
		$request_uri = ngx_request_uri();
		if ( $request_uri === '/images/test' ) {
			$this->image_url = 'http://images.pexels.com/photos/4275889/pexels-photo-4275889.jpeg?auto=compress&cs=tinysrgb&dpr=1&w=500';
			return;
		}

		$image_info = $this->detect_image_alias( $request_uri );
		$servername = constant( 'DB_HOST' );
		$username   = constant( 'DB_USER' );
		$password   = constant( 'DB_PASSWORD' );
		$dbname     = constant( 'DB_NAME' );

		// Create connection
		$this->conn = mysqli_connect( $servername, $username, $password, $dbname );

		if ( $this->conn->connect_errno ) {
			error_log( $mysqli->connect_error );
			return;
		}

		global $table_prefix;

		if ( $image_info['is_crop_image'] ) {
			$sql = "SELECT * FROM {$table_prefix}medoid_image_sizes WHERE alias=? LIMIT 1";
		} else {
			$sql = "SELECT * FROM {$table_prefix}medoid_images WHERE alias=? LIMIT 1";
		}
		// Perform an SQL query
		$stmt = $this->conn->prepare( $sql );
		if ( ! $stmt ) {
			return;
		}

		$stmt->bind_param( 's', $image_info['alias'] );
		$stmt->execute();

		$result = $stmt->get_result();

		if ( mysqli_num_rows( $result ) <= 0 ) {
			$result->free_result();
			$stmt->close();
			return;
		}

		// Get first image in the results
		$image = $result->fetch_array( MYSQLI_ASSOC );

		$this->image_url = $this->get_image_url_from_result( $image );

		$result->free_result();
		$stmt->close();
	}

	public function set_image_url() {
		if ( empty( $this->image_url ) ) {
			ngx_exit( NGX_HTTP_NOT_FOUND );
		}
		ngx_var::set( 'image_url', $this->image_url );
	}

	public function get_image_url_from_result( $image ) {
		$fields = array( 'cdn_image_url', 'image_url' );
		foreach ( $fields as $field ) {
			if ( empty( $image[ $field ] ) ) {
				continue;
			}
			return $image[ $field ];
		}
		return false;
	}
}
