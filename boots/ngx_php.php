<?php
/**
 * Create
 * @link https://github.com/rryqszq4/ngx_php7
 */

class Medoid_Ngx_PHP {
	protected $image_url;
	protected $wpdb;

	public function __construct() {
		$abspath = ngx_request_document_root();
		$wp_config_file = sprintf('%s/wp-config.php', $abspath);
		$wpdb_class_file = sprintf('%s/wp-includes/wp-db.php', $abspath);

		require_once $wpdb_class_file;
		require_once $wp_config_file;

		$this->wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
	}



	public function load_image_url_from_database() {
	}

	public function set_image_url() {
		if (empty($this->image_url)) {
			ngx_exit(NGX_HTTP_NOT_FOUND);
		}
		ngx_var::set('image_url', $this->image_url);
	}
}
