<?php
class Medoid_Install {
	public static function active() {
		$db = Medoid_Core_Db::instance();
		$db->load_db_fields();
		$db->create_tables();
	}
}
