<?php

class Medoid_Admin_Menu {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action('admin_menu', array($this, 'custom_menu_labels'), 33);
	}

	public function custom_menu_labels() {
		global $submenu;

		if(isset($submenu['medoid'][0][0])) {
			$submenu['medoid'][0][0] = __('Cloud Storages', 'medoid');
		}
	}

	public function register_menus() {
		add_menu_page( __( 'Medoid Cloud Storage', 'medoid' ), 'Medoid', 'manage_options', 'medoid', array( $this, 'cloud_storage' ) );
		add_submenu_page('medoid', __('Settings'), __('Settings'), 'manage_options', 'medoid-settings-page', array($this, 'settings_page'));
		add_submenu_page('medoid', __('Medoid Extensions', 'medoid'), __('Extensions', 'medoid'), 'manage_options', 'medoid-extensions', array($this, 'extensions'));
		add_submenu_page('medoid', __('Helps', 'medoid'), __('Get Helps', 'medoid'), 'manage_options', 'medoid-extensions', array($this, 'extensions'));
	}

	public function cloud_storage() {
	}

	public function settings_page() {
	}

	public function extensions() {
	}
}

new Medoid_Admin_Menu();
