<?php

class Medoid_Core_Db {
	protected static $instance;

	protected $cloud_db_table;
	protected $image_db_table;
	protected $image_size_db_table;
	protected $create_tables = [];

	public $db_table_created = false;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->init_tables();
	}

	public function init_tables() {
		global $wpdb;

		$this->cloud_db_table      = sprintf( '%smedoid_clouds', $wpdb->prefix );
		$this->image_db_table      = sprintf( '%smedoid_images', $wpdb->prefix );
		$this->image_size_db_table = sprintf( '%smedoid_image_sizes', $wpdb->prefix );

		$this->check_db();
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
			'image_url'         => 'TEXT',
			'proxy_image_url'   => 'TEXT NULL',
			'hash_filename'     => 'TEXT',
			'file_name'         => 'VARCHAR(255)',
			'file_type'         => 'VARCHAR(255)',
			'mime_type'         => 'VARCHAR(255)',
			'file_size'         => 'BIGINT',
			'created_at'        => 'TIMESTAMP NULL',
			'updated_at'        => 'TIMESTAMP NULL',
			'PRIMARY KEY'       => '(ID)',
		);
		$this->create_tables[ $this->image_size_db_table ] = array(
			'image_id'        => 'BIGINT',
			'cloud_id'        => 'BIGINT',
			'image_size'      => 'VARCHAR(255)',
			'image_url'       => 'TEXT NULL',
			'proxy_image_url' => 'LONGTEXT',
			'created_at'      => 'TIMESTAMP NULL',
			'updated_at'      => 'TIMESTAMP NULL',
			'PRIMARY KEY'     => '(image_id, cloud_id, image_size)',
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

	public function create_cloud() {
	}

	public function update_cloud() {
	}

	public function delete_cloud() {
	}

	public function get_image_by_attachment_id( $attatchment_id, $output = 'ARRAY_A' ) {
		global $wpdb;
		$sql = "SELECT * FROM {$this->image_db_table} WHERE post_id=%d";

		return $wpdb->get_row(
			$wpdb->prepare( $sql, $attatchment_id ),
			$output
		);
	}

	public function get_image_size( $attatchment_id ) {
		return wp_get_attachment_url( $attatchment_id );
	}

	public function insert_image( $image_data, $format = null ) {
		global $wpdb;
		if ( empty( $image_data ) ) {
			return new WP_Error( 'empty_data', __( 'The image data is empty', 'medoid' ) );
		}

		try {
			$wpdb->insert( $this->image_db_table, $image_data );
		} catch ( \Exception $e ) {
			return new WP_Error( 'sql_error', $e->getMessage() );
		}
	}

	public function update_image() {
	}

	public function delete_image() {
	}
}
