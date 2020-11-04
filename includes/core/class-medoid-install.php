<?php
class Medoid_Install {
	protected static $instance;

	protected $create_tables = array();
	public $db_table_created = false;

	protected $cloud_db_table;
	protected $image_db_table;
	protected $image_size_db_table;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->init_tables();
		$this->check_db();
	}

	public static function active() {
		$installer = self::get_instance();

		$installer->load_db_fields();
		$installer->create_tables();

		flush_rewrite_rules();
	}

	public function init_tables() {
		global $wpdb;

		$this->cloud_db_table      = sprintf( '%smedoid_clouds', $wpdb->prefix );
		$this->image_db_table      = sprintf( '%smedoid_images', $wpdb->prefix );
		$this->image_size_db_table = sprintf( '%smedoid_image_sizes', $wpdb->prefix );
	}

	public function check_db() {
		$this->db_table_created = get_option(
			'_medoid_created_db_tables',
			false
		);
	}


	public function load_db_fields() {
		$this->create_tables[ $this->cloud_db_table ]      = array(
			'ID'             => 'BIGINT NOT NULL AUTO_INCREMENT',
			'name'           => 'VARCHAR(255) NOT NULL',
			'cloud_type'     => 'VARCHAR(100) NOT NULL',
			'icon_name'      => 'VARCHAR(255) NULL',
			'image_url'      => 'TEXT NULL',
			'cloud_settings' => 'LONGTEXT',
			'description'    => 'TEXT NULL',
			'active'         => 'BOOLEAN NOT NULL DEFAULT 0',
			'created_at'     => 'TIMESTAMP NULL',
			'updated_at'     => 'TIMESTAMP NULL',
			'PRIMARY KEY'    => '(ID)',
		);
		$this->create_tables[ $this->image_db_table ]      = array(
			'ID'                => 'BIGINT NOT NULL AUTO_INCREMENT',
			'cloud_id'          => 'BIGINT',
			'post_id'           => 'BIGINT NOT NULL',
			'provider_image_id' => 'TEXT NULL',
			'image_url'         => 'TEXT NULL',
			'is_uploaded'       => 'TINYINT DEFAULT 0',
			'retry'             => 'INT DEFAULT 0',
			'proxy_id'          => 'TEXT NULL',
			'proxy_image_url'   => 'TEXT NULL',
			'cdn_image_url'     => 'TEXT NULL',
			'file_name'         => 'VARCHAR(255)',
			'file_type'         => 'VARCHAR(255)',
			'mime_type'         => 'VARCHAR(255)',
			'file_size'         => 'BIGINT',
			'height'            => 'INT DEFAULT 0',
			'width'             => 'INT DEFAULT 0',
			'is_deleted'        => 'TINYINT DEFAULT 0',
			'delete_local_file' => 'TINYINT DEFAULT 0',
			'created_at'        => 'TIMESTAMP NULL',
			'updated_at'        => 'TIMESTAMP NULL',
			'PRIMARY KEY'       => '(ID)',
		);
		$this->create_tables[ $this->image_size_db_table ] = array(
			'image_size_id'     => 'BIGINT NOT NULL AUTO_INCREMENT',
			'image_id'          => 'BIGINT',
			'image_size'        => 'VARCHAR(255)',
			'image_url'         => 'TEXT NULL',
			'provider_image_id' => 'TEXT NULL', // This field is used when use WordPress Native processing
			'is_uploaded'       => 'TINYINT DEFAULT 0', // This field is used when use WordPress Native processing
			'retry'             => 'INT DEFAULT 0', // This field is used when use WordPress Native processing
			'proxy_id'          => 'TEXT NULL',
			'proxy_image_url'   => 'LONGTEXT',
			'cdn_image_url'     => 'TEXT NULL',
			'height'            => 'INT DEFAULT 0',
			'width'             => 'INT DEFAULT 0',
			'created_at'        => 'TIMESTAMP NULL',
			'updated_at'        => 'TIMESTAMP NULL',
			'PRIMARY KEY'       => '(image_size_id)',
		);
	}

	public function create_tables() {
		if ( $this->db_table_created ) {
			return;
		}
		global $wpdb;

		foreach ( $this->create_tables as $table_name => $syntax_array ) {
			$syntax = '';
			foreach ( $syntax_array as $field => $args ) {
				$syntax .= sprintf( "%s %s, \n", $field, $args );
			}
			$syntax = rtrim( $syntax, ", \n" );

			$sql = sprintf(
				'CREATE TABLE IF NOT EXISTS %s(%s);',
				$table_name,
				$syntax
			);

			$wpdb->query( $sql );
		}
		update_option( '_medoid_created_db_tables', true );
	}
}
