<?php
class Medoid_Image_Proxy {
	public function __construct() {
		$this->init_hooks();
	}

	public function init_hooks() {
		add_action( 'wp_ajax_medoid_view_file', array( $this, 'view_file' ) );
		add_action( 'wp_ajax_nopriv_medoid_view_file', array( $this, 'view_file' ) );
	}

	public function view_file() {
	}
}

new Medoid_Image_Proxy();
