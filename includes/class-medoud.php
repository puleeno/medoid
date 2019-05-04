<?php
/**
 * The Medoud plugin main class
 *
 * @package Medoud
 */

/**
 * Medoud class
 */
class Medoud {
	/**
	 * Medoud instance
	 *
	 * @var Medoud
	 */
	protected static $instance;

	/**
	 * Get singleton Medoud instance.
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
		$this->define( 'MEDOUD_INC_DIR', dirname( MEDOUD_PLUGIN_FILE ) . '/includes' );
	}

	/**
	 * Include plugin files to plugin working
	 */
	protected function includes() {
		require_once MEDOUD_INC_DIR . '/medoud-helpers.php';
	}

	/**
	 * Init mecloud plugin hooks
	 */
	protected function hooks() {
		register_activation_hook( MEDOUD_PLUGIN_FILE, array( 'Medoud_Install', 'active' ) );
		register_deactivation_hook( MEDOUD_PLUGIN_FILE, array( 'Medoud_Install', 'deactivate' ) );
	}
}
