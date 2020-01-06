<?php
class Medoid_Upgrade {
	public function __construct() {
		add_action( 'upgrader_process_complete', array( $this, 'update_wordpress_core' ) );
	}

	public function update_wordpress_core() {
		if ( ! class_exists( 'Medoid_Wordpress_Modification' ) ) {
			require_once MEDOID_ABSPATH . '/includes/class-medoid-wordpress-modification.php';
		}
		$modification = new Medoid_Wordpress_Modification();
		$modification->modify_wordpress_upload();
	}
}

new Medoid_Upgrade();
