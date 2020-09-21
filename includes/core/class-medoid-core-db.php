<?php

class Medoid_Core_Db {
	protected static $instance;
	protected $wpdb;

	protected $cloud_db_table;
	protected $image_db_table;
	protected $image_size_db_table;

	protected $wheremap = array(
		'integer' => array( 'val' => '%d' ),
		'boolean' => array( 'val' => '%d' ),
		'string'  => array( 'val' => '%s' ),
		'array'   => array(
			'val'     => '%s',
			'compare' => 'IN',
		),
	);

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

	public function get_image_by_attachment_id( $attatchment_id, $cloud_id = 0 ) {
		$sql = "SELECT * FROM {$this->image_db_table} WHERE post_id=%d AND is_deleted=0";

		return $this->wpdb->get_row(
			$this->wpdb->prepare( $sql, $attatchment_id )
		);
	}

	public function get_image_size( $image_id, $size ) {
	}

	public function get_image_size_by_attachment_id( $attachment_id, $size, $cloud_id = 0 ) {
		if ( is_array( $size ) ) {
			$size = implode( 'x', $size );
		}
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
			return $this->wpdb->insert( $this->image_db_table, $image_data, $format );
		} catch ( \Exception $e ) {
			return new WP_Error( 'sql_error', $e->getMessage() );
		}
	}

	public function update_image( $image_data = array() ) {
		if ( empty( $image_data['ID'] ) ) {
			return new WP_Error( __( 'The image ID must be specific to update image', 'medoid' ) );
		}
		$where = array(
			'ID' => $image_data['ID'],
		);
		unset( $image_data['ID'] );

		return $this->wpdb->update(
			$this->image_db_table,
			$image_data,
			$where
		);
	}

	public function delete_image_by_attachment_id( $attachment_id ) {
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

	public function delete_image( $image_id, $cloud_id = null, $force_delete = true ) {
		$deleted_image = array( 'ID' => $image_id );
		if ( ! is_null( $cloud_id ) ) {
			$deleted_image['cloud_id'] = $cloud_id;
		}
		if ( $force_delete === false ) {
			$this->wpdb->update(
				$this->image_db_table,
				array( 'is_deleted' => true ),
				$deleted_image
			);
			return;
		}

		$this->delete_image_sizes( $image_id, $cloud_id );
		$this->wpdb->delete(
			$this->image_db_table,
			$deleted_image
		);
	}

	public function delete_image_sizes( $image_id, $cloud_id = null ) {
		$deleted_image_size = array(
			'image_id' => $image_id,
		);

		if ( is_null( $cloud_id ) ) {
			$deleted_image_size['cloud_id'] = $cloud_id;
		}

		$this->wpdb->delete(
			$this->image_size_db_table,
			$deleted_image_size
		);
	}

	public function delete_image_size( $image_size_id ) {
	}

	public function delete_image_from_attachment( $attachment_id ) {
		$image = $this->get_image_by_attachment_id( $attachment_id );
		if ( ! empty( $image ) ) {
			$this->delete_image( $image->ID, null, true );
		}
	}
}
