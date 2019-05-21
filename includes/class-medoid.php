
<?php
/**
 * The Medoid plugin main class
 *
 * @package Medoid
 */

/**
 * Medoid class
 */
class Medoid {
	/**
	 * Medoid instance
	 *
	 * @var Medoid
	 */
	protected static $instance;

	/**
	 * Get singleton Medoid instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * The class constructor
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->hooks();
	}

	private function define($name, $value) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	protected function define_constants() {
		$this->define( 'MD_ABSPATH', dirname( MEDOUD_PLUGIN_FILE ) );
	}

	/**
	 * Include plugin files to plugin working
	 */
	protected function includes() {
		require_once MD_ABSPATH . '/includes/medoid-helpers.php';

		if ( $this->is_request( 'admin' ) ) {
			require_once MD_ABSPATH . '/includes/admin/class-medoid-admin.php';
		}
	}

	/**
	 * Init mecloud plugin hooks
	 */
	protected function hooks() {
		// register_activation_hook( MEDOUD_PLUGIN_FILE, array( 'Medoid_Install', 'active' ) );
		// register_deactivation_hook( MEDOUD_PLUGIN_FILE, array( 'Medoid_Install', 'deactivate' ) );
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! $this->is_rest_api_request();
		}
	}

	public function is_rest_api_request() {
	}
}
