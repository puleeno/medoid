<?php
use Ramphor\Logger\Logger;
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;

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

		// Create stream handler for logger
		$logsfile = sprintf( '%s/medoid.log', WP_CONTENT_DIR );
		$handler  = new StreamHandler(
			apply_filters( 'medoid_logs_file_path', $logsfile ),
			Monolog::DEBUG
		);
		$logger   = new Monolog( 'MEDOID' );
		$logger->pushHandler( $handler );

		// Get ramphor logger instance
		$ramphor_logger = Logger::instance();
		$ramphor_logger->registerLogger( 'medoid', $logger );

		register_activation_hook( MEDOID_PLUGIN_FILE, array( Medoid_Install::class, 'active' ) );
		add_action( 'plugins_loaded', array( $this, 'init_hooks' ) );
	}

	public function define_constants() {
		$this->define( 'MEDOID_ABSPATH', dirname( MEDOID_PLUGIN_FILE ) );
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

		// Require the Medoid interfaces
		require_once MEDOID_ABSPATH . '/includes/core/interfaces/medoid-cloud-interface.php';
		require_once MEDOID_ABSPATH . '/includes/core/interfaces/medoid-cdn-interface.php';
		require_once MEDOID_ABSPATH . '/includes/core/interfaces/medoid-cdn-processing.php';

		// Require the Medoid abstracts
		require_once MEDOID_ABSPATH . '/includes/core/abstracts/class-medoid-cloud.php';
		require_once MEDOID_ABSPATH . '/includes/core/abstracts/class-medoid-cdn.php';

		// Load Medoid core
		require_once MEDOID_ABSPATH . '/includes/core/class-medoid-core-db.php';

		// Install Medoid
		require_once MEDOID_ABSPATH . '/includes/core/class-medoid-install.php';

		// Load medoid helpers
		require_once MEDOID_ABSPATH . '/includes/medoid-common-helpers.php';

		// Load Medoid Admin
		if ( $this->is_request( 'admin' ) ) {
			require_once MEDOID_ABSPATH . '/includes/admin/class-medoid-admin.php';
		}

		// Only require classes when necessary
		spl_autoload_register( array( $this, 'autoload_medoid_classes' ) );

		require_once MEDOID_ABSPATH . '/includes/core/class-medoid-core-manager.php';
		require_once MEDOID_ABSPATH . '/includes/core/class-medoid-core-upload-handler.php';

		if ( $this->is_request( 'cron' ) ) {
			/**
			 * Load the Medoid syncer
			 * The image will be upload to Cloud Store via WordPress Cronjob
			 *
			 * Reference: https://developer.wordpress.org/plugins/cron/
			 */
			require_once MEDOID_ABSPATH . '/includes/core/class-medoid-core-syncer.php';
		}

		// Customize WordPress load the images
		require_once MEDOID_ABSPATH . '/includes/core/class-medoid-image.php';
		require_once MEDOID_ABSPATH . '/includes/core/class-medoid-core-image-delivery.php';
		if ( get_option( 'medoid_use_php_proxy', true ) ) {
			require_once MEDOID_ABSPATH . '/boots/fake-proxy.php';
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
		add_filter( 'medoid_create_file_name_unique', 'medoid_create_file_name_unique', 10, 3 );

		$medoid_image = new Medoid_Core_Image_Delivery();
		add_action( 'init', array( $medoid_image, 'init_hooks' ) );

		if ( $this->is_request( 'cron' ) ) {
			$medoid_syncer = new Medoid_Core_Syncer();
			$medoid_syncer->includes();

			add_filter( 'cron_schedules', array( $medoid_syncer, 'schedules' ) );
			/**
			 * Setup WordPress cron via action hooks
			 */
			add_action( 'init', array( $medoid_syncer, 'setup_cron' ), 20 );
			add_action( 'init', array( $medoid_syncer, 'run_cron' ), 30 );
		}
	}

	public function composer_not_found() {
		_e(
			'<div class="notice notice-warning is-dismissible">
			<p>Medoid need composer to support cloud storages.</p>
		</div>',
			'medoid'
		);
	}

	public static function is_active() {
		return true;
	}

	public function autoload_medoid_classes( $cls ) {
		if ( ! preg_match( '/^Medoid_/', $cls ) ) {
			return;
		}
		// Generate file name from class name
		$file_name = sprintf( '%s/includes/classes/class-%s.php', MEDOID_ABSPATH, str_replace( '_', '-', strtolower( $cls ) ) );

		// Check class file is exists and require
		if ( file_exists( $file_name ) ) {
			require_once $file_name;
		}
	}
}
