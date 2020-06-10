<?php

class Medoid_Core_CDN_Integration {
	protected static $instance;

	protected $real_url;
	protected $url;

	protected $cdn_provider;
	protected $cdns;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		$this->includes();
		add_action( 'init', array( $this, 'setup_cdn' ) );
	}

	public function includes() {
		require_once MEDOID_ABSPATH . '/includes/cdn/class-medoid-cdn-gumlet.php';
		require_once MEDOID_ABSPATH . '/includes/cdn/class-medoid-cdn-cloudimage.php';
	}

	public function setup_cdn() {
		$this->cdns = array(
			Medoid_CDN_Gumlet::TYPE_NAME     => Medoid_CDN_Gumlet::class,
			Medoid_CDN_CloudImage::TYPE_NAME => Medoid_CDN_CloudImage::class,
		);

		$cdn_provider = apply_filters( 'medoid_apply_cdn_provider', $this->cdns[ Medoid_CDN_CloudImage::TYPE_NAME ] );

		/**
		 * Create CDN Provider via class name
		 */
		$this->cdn_provider = new $cdn_provider( array() );
		if ( ! ( $this->cdn_provider instanceof Medoid_CDN ) ) {
			Medoid_Logger::error( sprintf( '%s must be a instance of %s', $cdn_provider, Medoid_CDN::class ) );
		}
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
