<?php

class Medoid_Admin_Menus {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus' ) );
	}


	public function admin_menus() {
		add_menu_page( 'Medoid', 'Medoid', 'manage_options', 'medoid', array( $this, 'callback' ), '', 35 );
		add_submenu_page('medoid', __('Clouds', 'medoid'), __('Clouds', 'medoid'), 'manage_options', 'md-clouds', array($this, 'clouds'));
		add_submenu_page('medoid', __('Addons', 'medoid'), __('Addons', 'medoid'), 'manage_options', 'md-addons', array( $this, 'addons'));
		add_submenu_page('medoid', __('Settings', 'medoid'), __('Settings', 'medoid'), 'manage_options', 'md-settings', array( $this, 'settings'));
	}


	public function callback() {
		echo 'admin';
	}
}

return new Medoid_Admin_Menus();
