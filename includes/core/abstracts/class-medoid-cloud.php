<?php

abstract class Medoid_Cloud implements Medoid_Cloud_Interface {
	protected $db;

	public function sync_to_cloud( $limit_items = 50 ) {
		$this->db = Medoid_Core_Db::instance();

		$this->db->get_images();
	}
}
