<?php
class Medoid_Install {
	public static function active() {
		$db = new Medoid_Core_Db();
		$db->create_tables();
	}
}
