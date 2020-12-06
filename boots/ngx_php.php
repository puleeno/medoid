<?php
/**
 * Create
 *
 * @link https://github.com/rryqszq4/ngx_php7
 */
if ( ! class_exists( 'Medoid_Boots_Image_Function' ) ) {
	require_once dirname( __FILE__ ) . '/image-function.php';
}

class Medoid_Ngx_PHP extends Medoid_Boots_Image_Function {
	protected $image_url;

	// Load WordPress
	public function __construct() {
		define( 'WP_USE_THEMES', false );
		require_once ngx_request_document_root() . '/wp-blog-header.php';
	}

	public function load_image_url_from_database() {
		$medoid_db = Medoid_Core_Db::instance();
		var_dump( $medoid_db );
		die;
	}

	public function set_image_url() {
		if ( empty( $this->image_url ) ) {
			ngx_exit( NGX_HTTP_NOT_FOUND );
		}
		ngx_var::set( 'image_url', $this->image_url );
	}
}
