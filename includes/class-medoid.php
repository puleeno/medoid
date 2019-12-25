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
		require_once MEDOID_ABSPATH . '/includes/class-medoid-install.php';

		require_once MEDOID_ABSPATH . '/includes/interfaces/medoid-cloud-interface.php';
		require_once MEDOID_ABSPATH . '/includes/abstracts/class-medoid-cloud.php';

		$this->include_clouds();
		$this->include_job_runners();
		if ( $this->is_request( 'admin' ) ) {
			require_once MEDOID_ABSPATH . '/includes/admin/class-medoid-admin.php';
		}

		require_once MEDOID_ABSPATH . '/includes/class-medoid-image.php';
		require_once MEDOID_ABSPATH . '/includes/class-medoid-cdn-integration.php';
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
		require_once MEDOID_ABSPATH . '/includes/clouds/class-medoid-cloud-awss3.php';
		require_once MEDOID_ABSPATH . '/includes/clouds/class-medoid-cloud-backblaze.php';
	}

	public function include_job_runners() {
	}
}
