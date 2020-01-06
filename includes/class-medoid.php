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
		require_once $composer;

		require_once MEDOID_ABSPATH . '/includes/core/class-medoid-core-db.php';
		require_once MEDOID_ABSPATH . '/includes/class-medoid-install.php';

		require_once MEDOID_ABSPATH . '/includes/class-medoid-query.php';
		require_once MEDOID_ABSPATH . '/includes/core/class-medoid-response.php';
		require_once MEDOID_ABSPATH . '/includes/core/interfaces/medoid-cloud-interface.php';
		require_once MEDOID_ABSPATH . '/includes/core/interfaces/medoid-cdn-interface.php';
		require_once MEDOID_ABSPATH . '/includes/core/abstracts/class-medoid-cloud.php';
		require_once MEDOID_ABSPATH . '/includes/core/abstracts/class-medoid-cdn.php';

		$this->include_clouds();
		if ( $this->is_request( 'admin' ) ) {
			require_once MEDOID_ABSPATH . '/includes/core/class-medoid-core-manage-images.php';
			require_once MEDOID_ABSPATH . '/includes/admin/class-medoid-admin.php';
		}

		require_once MEDOID_ABSPATH . '/includes/core/medoid-core-common-helpers.php';
		require_once MEDOID_ABSPATH . '/includes/core/medoid-core-upload-helpers.php';
		require_once MEDOID_ABSPATH . '/includes/class-medoid-cloud-storages.php';
		require_once MEDOID_ABSPATH . '/includes/core/class-medoid-core-upload-handler.php';
		require_once MEDOID_ABSPATH . '/includes/class-medoid-image.php';
		require_once MEDOID_ABSPATH . '/includes/class-medoid-cdn-integration.php';

		$this->include_job_runners();
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


	public function include_clouds() {
		require_once MEDOID_ABSPATH . '/includes/clouds/class-medoid-cloud-backblaze.php';
	}

	public function include_job_runners() {
	}

	public function init_hooks() {
		register_activation_hook( MEDOID_PLUGIN_FILE, array( Medoid_Install::class, 'active' ) );
	}

	public function composer_not_found() {
		echo '<div class="notice notice-warning is-dismissible">
			<p>Medoid need composer to support cloud storages.</p>
		</div>';
	}
}
