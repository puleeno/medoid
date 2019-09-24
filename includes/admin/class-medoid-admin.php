<?php

class Medoid_Admin {
    protected $admin_dir;

	public function __construct() {
        $this->admin_dir = dirname(__FILE__);
		$this->includes($this->admin_dir);
	}

	public function includes($admin_dir = null) {
        if(empty($admin_dir)) {
            $admin_dir = $this->admin_dir;
        }

        require_once $admin_dir . '/class-medoid-admin-menu.php';
	}
}

new Medoid_Admin();