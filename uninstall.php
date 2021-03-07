<?php
class Medoid_Uninstaller {
	public function __construct() {
		$this->clean_options();
	}

	public function clean_options() {
		delete_option('_medoid_created_db_tables');
	}
}


new Medoid_Uninstaller();
