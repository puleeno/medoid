<?php

class Medoid_Core_Cdn_Integration {
	protected static $instance;

	protected $real_url;
	protected $url;

	protected $cdn_provider;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		$this->includes();
		$this->setup_cdn();
	}

	public function includes() {
		require_once MEDOID_ABSPATH . '/includes/cdn/class-medoid-cdn-gumlet.php';
		require_once MEDOID_ABSPATH . '/includes/cdn/class-medoid-cdn-imagecdn-app.php';
	}

	public function setup_cdn() {
		$options            = array();
		$this->cdn_provider = new Medoid_Cdn_Gumlet( $options );
	}

	public function is_enabled() {
		return true;
	}

	public function delivery( $url ) {
		return $this->cdn_provider->process( $url );
	}

	public function resize() {
		return call_user_func_array(
			array( $this->cdn_provider, 'resize' ),
			func_get_args()
		);
	}

	public function get_provider() {
		return $this->cdn_provider;
	}
}
