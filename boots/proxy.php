<?php
/**
 * This file best working in case web has proxy CDN like CloudFlare
 */
class Medoid_Proxy {
	protected static $instance;

	public static function get_instance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	private function __construct() {
		$this->init_hooks();
	}

	public function init_hooks() {
		add_action( 'init', array( $this, 'register_image_rewrite_url_rules' ), 5 );
		add_filter( 'query_vars', array( $this, 'register_new_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'showImageContent' ) );
	}

	public function register_image_rewrite_url_rules() {
		add_rewrite_rule( '^images/([a-zA-Z0-9_-]+)/(.*)$', 'index.php?medoid=view-image-size&size=$matches[1]&alias=$matches[2]', 'top' );
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

	public function showImageContent() {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
		}
		$image_file = download_url( 'https://demo.cloudimg.io/v7/https://sango.puleeno.com/wp-content/uploads/2020/10/banner-khuyen-mai-san-go-gia-re-1168x118-1.gif' );
		header( 'Content-Type: ' . mime_content_type( $image_file ) );
		header( 'Cache-control: max-age=' . ( 60 * 60 * 24 * 365 ) );
		header( 'Expires: ' . gmdate( DATE_RFC1123, time() + 60 * 60 * 24 * 365 ) );
		header( 'Last-Modified: ' . gmdate( DATE_RFC1123, filemtime( $image_file ) ) );

		if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
			header( 'HTTP/1.1 304 Not Modified' );
			die();
		}

		echo file_get_contents( $image_file );
		@unlink( $image_file );
	}
}

Medoid_Proxy::get_instance();
