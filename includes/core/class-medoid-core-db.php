<?php

class Medoid_Core_Db {
	protected static $instance;
	protected $wpdb;
	protected $cloud_db_table;
	protected $image_db_table;
	protected $image_size_db_table;
	protected $create_tables = [];
	protected $wheremap      = array(
		'integer' => array( 'val' => '%d' ),
		'boolean' => array( 'val' => '%d' ),
		'string'  => array( 'val' => '%s' ),
		'array'   => array(
			'val'     => '%s',
			'compare' => 'IN',
		),
	);
	public $db_table_created = false;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->wpdb = $GLOBALS['wpdb'];
		$this->init_tables();
	}

	public function init_tables() {
		$this->cloud_db_table      = sprintf( '%smedoid_clouds', $this->wpdb->prefix );
		$this->image_db_table      = sprintf( '%smedoid_images', $this->wpdb->prefix );
		$this->image_size_db_table = sprintf( '%smedoid_image_sizes', $this->wpdb->prefix );

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
			'proxy_image_url'   => 'TEXT NULL',
			'hash_filename'     => 'TEXT',
			'file_name'         => 'VARCHAR(255)',
			'file_type'         => 'VARCHAR(255)',
			'mime_type'         => 'VARCHAR(255)',
			'file_size'         => 'BIGINT',
			'delete_local_file' => 'TINYINT DEFAULT 0',
			'created_at'        => 'TIMESTAMP NULL',
			'updated_at'        => 'TIMESTAMP NULL',
			'PRIMARY KEY'       => '(ID)',
		);
		$this->create_tables[ $this->image_size_db_table ] = array(
			'image_id'          => 'BIGINT',
			'cloud_id'          => 'BIGINT',
			'image_size'        => 'VARCHAR(255)',
			'image_url'         => 'TEXT NULL',
			'post_id'           => 'BIGINT NULL', // This field is used when use WordPress Native processing
			'provider_image_id' => 'TEXT NULL', // This field is used when use WordPress Native processing
			'is_uploaded'       => 'TINYINT DEFAULT 0', // This field is used when use WordPress Native processing
			'retry'             => 'INT DEFAULT 0', // This field is used when use WordPress Native processing
			'proxy_image_url'   => 'LONGTEXT',
			'created_at'        => 'TIMESTAMP NULL',
			'updated_at'        => 'TIMESTAMP NULL',
			'PRIMARY KEY'       => '(image_id, cloud_id, image_size)',
		);
	}

	public function create_tables() {
		if ( $this->db_table_created ) {
			return;
		}
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

			$this->wpdb->query( $sql );
		}
		update_option( '_medoid_created_db_tables', true );
	}

	public function create_cloud() {
	}

	public function update_cloud() {
	}

	public function delete_cloud() {
	}

	public function get_clouds() {
		return array(
			(object) array(
				'ID'         => 1,
				'name'       => 'Medoid Cloud Name',
				'cloud_type' => Medoid_Cloud_Backblaze::CLOUD_TYPE,
				'active'     => true,
			),
		);
	}

	public function get_images( $query_vars = array() ) {
		$sql = "SELECT * FROM {$this->image_db_table}";
		if ( ! empty( $query_vars ) ) {
			$limit = 0;
			if ( isset( $query_vars['limit'] ) ) {
				$limit = $query_vars['limit'];
				unset( $query_vars['limit'] );
			}

			$offset = 0;
			if ( isset( $query_vars['offset'] ) ) {
				$offset = $query_vars['offset'];
				unset( $query_vars['offset'] );
			}
			$orderby = 'post_id ASC';
			if ( isset( $query_vars['orderby'] ) ) {
				$orderby = $query_vars['orderby'];
				unset( $query_vars['orderby'] );
			}

			$sql .= ' WHERE ';
			foreach ( $query_vars as $key => $value ) {
				$type = gettype( $value );
				if ( ! isset( $this->wheremap[ $type ] ) ) {
					continue;
				}
				$placeholder = sprintf(
					' %s%s%s AND ',
					$key,
					isset( $this->wheremap[ $type ]['compare'] ) ? $this->wheremap[ $type ]['compare'] : '=',
					$this->wheremap[ $type ]['val']
				);

				$sql .= $this->wpdb->prepare(
					$placeholder,
					( $type === 'array' ) ? sprintf( '(%s)', implode( ', ', $value ) ) : $value
				);
			}
			$sql  = rtrim( $sql, 'AND ' );
			$sql .= sprintf( ' ORDER BY %s', $orderby );
			if ( $limit > 0 ) {
				$sql .= sprintf( ' LIMIT %d', $limit );
			}
			$sql .= sprintf( ' OFFSET %d', $offset );
		}

		return $this->wpdb->get_results( $sql );
	}

	public function get_image_by_attachment_id( $attatchment_id, $output = 'ARRAY_A' ) {
		$sql = "SELECT * FROM {$this->image_db_table} WHERE post_id=%d";

		return $this->wpdb->get_row(
			$this->wpdb->prepare( $sql, $attatchment_id ),
			$output
		);
	}

	public function get_image_size( $image_id, $size ) {
	}

	public function get_image_size_by_attachment_id( $attachment_id, $size ) {
		$sql = $this->wpdb->prepare(
			"SELECT s.*
			FROM {$this->image_size_db_table} s
			INNER JOIN {$this->image_db_table} i
				ON i.ID=s.image_id
			WHERE i.post_id=%d
				AND s.image_size=%s",
			$attachment_id,
			$size
		);

		return $this->wpdb->get_row( $sql );
	}

	public function insert_image( $image_data, $format = null ) {
		if ( empty( $image_data ) ) {
			return new WP_Error( 'empty_data', __( 'The image data is empty', 'medoid' ) );
		}
		try {
			$this->wpdb->insert( $this->image_db_table, $image_data, $format );
		} catch ( \Exception $e ) {
			return new WP_Error( 'sql_error', $e->getMessage() );
		}
	}

	public function update_image() {
	}

	public function delete_image() {
	}

	public function insert_image_size( $attachment_id, $image_size, $image_url, $cloud_id = 1, $proxy_image_url = null ) {
		$image = $this->get_image_by_attachment_id( $attachment_id );
		if ( empty( $image ) ) {
			return;
		}
		$image_id     = $image->ID;
		$current_time = time();
		if ( is_array( $image_size ) ) {
			$image_size = implode( 'x', $image_size );
		}

		$image_size_data = array(
			'image_id'        => $image_id,
			'cloud_id'        => $cloud_id,
			'image_size'      => $image_size,
			'image_url'       => $image_url,
			'proxy_image_url' => $proxy_image_url,
			'created_at'      => $current_time,
			'updated_at'      => $$current_time,
		);

		try {
			$this->wpdb->insert( $this->image_size_db_table, $image_size_data );
		} catch ( \Exception $e ) {
			return new WP_Error( 'sql_error', $e->getMessage() );
		}
	}
}
