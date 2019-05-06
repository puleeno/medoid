<?php
class Medoud_Admin {
 	public function __construct() {
		add_action('init', array($this, 'includes'));
	}


	public function includes() {
		require_once dirname(__FILE__) . '/class-medoud-admin-menus.php';
	}
}

return new Medoud_Admin();