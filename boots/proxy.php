<?php
/**
 * This file best working in case web has proxy CDN like CloudFlare
 */
class Medoid_Proxy {
	protected static $instance;

	protected $db;

	public static function get_instance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	private function __construct() {
		$this->init();
		$this->init_hooks();
	}

	public function init() {
		$this->db = Medoid_Core_Db::instance();
	}

	public function init_hooks() {
		add_action( 'init', array( $this, 'register_image_rewrite_url_rules' ), 5 );
		add_filter( 'query_vars', array( $this, 'register_new_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'show_image_content' ) );
	}

	public function register_image_rewrite_url_rules() {
		add_rewrite_rule(
			'^images/(\d{1,}x\d{1,}|thumbnail|medium|large|full)/(.*)$',
			'index.php?medoid=view-image-size&size=$matches[1]&alias=$matches[2]',
			'top'
		);
		add_rewrite_rule( '^images/(.*)$', 'index.php?medoid=view-image&alias=$matches[1]', 'top' );
	}

	public function register_new_query_vars( $query_vars ) {
		$query_vars = array_merge(
			$query_vars,
			array(
				'medoid',
				'size',
				'alias',
			)
		);
		return $query_vars;
	}

	public function get_image_from_alias($alias) {
		$db_image = $this->db->get_image_by_alias($alias);
		if ($db_image) {
			return new Medoid_Image($db_image->post_id, $db_image);
		}
	}

	public function get_image_size_from_alias($alias, $size) {
		$image_size = medoid_get_image_sizes($size);
		if (!is_array($image_size)) {
			return;
		}
		$db_image = $this->db->get_image_size_by_alias($alias, array($image_size['height'], $image_size['width']));
		if ($db_image) {
			return new Medoid_Image($db_image->post_id, $db_image, explode('x', $size));
		}
		$db_image = $this->db->get_image_by_alias($alias);
		if ($db_image) {
			return new Medoid_Image($db_image->post_id, $db_image, $image_size, true);
		}
	}

	public function show_image_content() {
		if ( ( $medoid_action = get_query_var( 'medoid' ) ) == '' ) {
			return;
		}
		if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
			header( 'HTTP/1.1 304 Not Modified' );
			die();
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
		}
		$medoid_image = $medoid_action === 'view-image'
			? $this->get_image_from_alias( get_query_var( 'alias' ) )
			: $this->get_image_size_from_alias( get_query_var( 'alias' ), get_query_var( 'size' ) );
		if ( empty( $medoid_image ) ) {
			// When the image is invalid return WordPress response
			return;
		}
		$medoid_image->create_proxy_image_content();

		$image_file = download_url( (string)$medoid_image );
		header( 'Content-Type: ' . mime_content_type( $image_file ) );
		header( 'Cache-control: max-age=' . ( 60 * 60 * 24 * 365 ) );
		header( 'Expires: ' . gmdate( DATE_RFC1123, time() + 60 * 60 * 24 * 365 ) );
		header( 'Last-Modified: ' . gmdate( DATE_RFC1123, filemtime( $image_file ) ) );

		echo file_get_contents( $image_file );
		@unlink( $image_file );
		die;
	}
}

Medoid_Proxy::get_instance();
