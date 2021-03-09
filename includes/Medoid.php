<?php
use Ramphor\Logger\Logger;
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Medoid\Installer;
use Medoid\Core\ImageDelivery;
use Medoid\Core\Syncer;
use Medoid\Core\UploadHandler;

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

		register_activation_hook( MEDOID_PLUGIN_FILE, array( Installer::class, 'active' ) );
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
		// Load medoid helpers
		require_once MEDOID_ABSPATH . '/includes/functions.php';

		// Load Medoid Admin
		if ( $this->is_request( 'admin' ) ) {
			require_once MEDOID_ABSPATH . '/includes/admin/class-medoid-admin.php';
		}
		if ( get_option( 'medoid_use_php_proxy', true ) ) {
			require_once MEDOID_ABSPATH . '/boots/fake-proxy.php';
		}

		new UploadHandler();
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

		$medoid_image = new ImageDelivery();
		add_action( 'init', array( $medoid_image, 'init_hooks' ) );

		if ( $this->is_request( 'cron' ) ) {
			$medoid_syncer = new Syncer();
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
}
