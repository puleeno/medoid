<?php

class Medoud_Admin_Menus {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus' ) );
	}


	public function admin_menus() {
		add_menu_page( 'Medoud', 'Medoud', 'manage_options', 'medoud', array( $this, 'callback' ), '', 35 );
		add_submenu_page('medoud', __('Clouds', 'medoud'), __('Clouds', 'medoud'), 'manage_options', 'md-clouds', array($this, 'clouds'));
		add_submenu_page('medoud', __('Addons', 'medoud'), __('Addons', 'medoud'), 'manage_options', 'md-addons', array( $this, 'addons'));
		add_submenu_page('medoud', __('Settings', 'medoud'), __('Settings', 'medoud'), 'manage_options', 'md-settings', array( $this, 'settings'));
	}


	public function callback() {
		echo 'admin';
	}
}

return new Medoud_Admin_Menus();
