<?php
require_once MEDOID_ABSPATH . '/includes/class-medoid-wordpress-modification.php';

class Medoid_Install {
	public static function active() {
		$db = new Medoid_Core_Db();
		$db->load_db_fields();
		$db->create_tables();

		$modification = new Medoid_Wordpress_Modification();
		$modification->modify_wordpress_upload();
	}
}
