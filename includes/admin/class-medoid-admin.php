<?php
class Medoid_Admin {
 	public function __construct() {
		add_action('init', array($this, 'includes'));
	}


	public function includes() {
		require_once dirname(__FILE__) . '/class-medoid-admin-menus.php';
	}
}

return new Medoid_Admin();