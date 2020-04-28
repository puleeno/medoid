<?php
final class Medoid {
	protected static $instance;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	public function define_constants() {
		$this->define( 'MEDOID_ABSPATH', dirname( MEDOID_PLUGIN_FILE ) );
		$this->define( 'MEDOID_CLOUDS_DIR', MEDOID_ABSPATH . '/includes/clouds' );
	}

	private function define( $name, $value ) {
		if ( defined( $name ) ) {
			return;
		}
		define( $name, $value );
	}

	public function includes() {
		$composer = sprintf( '%s/vendor/autoload.php', MEDOID_ABSPATH );
		if ( ! file_exists( $composer ) ) {
			if ( is_admin() ) {
				add_action( 'admin_notices', array( $this, 'composer_not_found' ) );
			}
			return;
		}
		/**
		 * Load dependences via Composer Package Manager
		 */
		require_once $composer;

		/**
		 * Require the interfaces and abstracts
		 */
		require_once MEDOID_ABSPATH . '/includes/core/interfaces/medoid-cloud-interface.php';
		require_once MEDOID_ABSPATH . '/includes/core/interfaces/medoid-cdn-interface.php';
		require_once MEDOID_ABSPATH . '/includes/core/abstracts/class-medoid-cloud.php';
		require_once MEDOID_ABSPATH . '/includes/core/abstracts/class-medoid-cdn.php';

		/**
		 * Install Medoid
		 */
		require_once MEDOID_ABSPATH . '/includes/core/class-medoid-core-db.php';
		require_once MEDOID_ABSPATH . '/includes/class-medoid-install.php';

		/**
		 * Load medoid helpers
		 */
		require_once MEDOID_ABSPATH . '/includes/medoid-common-helpers.php';

		if ( ! $this->is_request( 'cron' ) ) {
			/**
			 * Added medoid flow via WordPress Native upload Flow
			 */
			require_once MEDOID_ABSPATH . '/includes/core/class-medoid-core-cdn-integration.php';
			require_once MEDOID_ABSPATH . '/includes/core/class-medoid-core-upload-handler.php';

			/**
			 * Customize WordPress load the images
			 */
			require_once MEDOID_ABSPATH . '/includes/core/class-medoid-image.php';
			require_once MEDOID_ABSPATH . '/includes/core/class-medoid-image-proxy.php';
		} else {
			/**
			 * Load the Medoid syncer
			 * The image will be upload to Cloud Store via WordPress Cronjob
			 *
			 * Reference: https://developer.wordpress.org/plugins/cron/
			 */
			require_once MEDOID_ABSPATH . '/includes/core/class-medoid-syncer.php';
		}

		/**
		 * Load Medoid Admin
		 */
		if ( $this->is_request( 'admin' ) ) {
			require_once MEDOID_ABSPATH . '/includes/admin/class-medoid-admin.php';
		}
	}

	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) )
					&& ! defined( 'DOING_CRON' )
					&& ! defined( 'REST_REQUEST' );
		}
	}

	public function init_hooks() {
		register_activation_hook( MEDOID_PLUGIN_FILE, array( Medoid_Install::class, 'active' ) );

		add_filter( 'medoid_create_file_name_unique', 'medoid_create_file_name_unique', 10, 3 );
	}

	public function composer_not_found() {
		echo '<div class="notice notice-warning is-dismissible">
			<p>Medoid need composer to support cloud storages.</p>
		</div>';
	}

	public static function is_active() {
		return true;
	}
}
