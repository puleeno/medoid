<?php
/**
 * Create
 *
 * @link https://github.com/rryqszq4/ngx_php7
 */
class Medoid_Ngx_PHP {
	protected $image_url;

	// Load WordPress
	public function __construct() {
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
