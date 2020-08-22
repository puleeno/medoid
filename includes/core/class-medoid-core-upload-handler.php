<?php
class Medoid_Core_Upload_Handler {
	public static $current_post;
	protected $db;
	protected $cdn;
	protected $result;

	public static function set_current_post( $post ) {
		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post );
		}
		self::$current_post = $post;
	}

	public function __construct() {
		$this->init();
		$this->init_hooks();
	}

	public function init() {
		$this->db = Medoid_Core_Db::instance();
	}

	public function init_hooks() {
		add_action( 'wp_handle_upload', array( $this, 'get_upload_result' ), 10 );
		add_action( 'add_attachment', array( $this, 'insert_temporary_cloud_image' ) );

		// Hook actions to delete WordPress media
		add_action( 'delete_attachment', array( $this, 'delete_image' ) );
	}

	public function get_upload_result( $result ) {
		$cloud_storage = new Medoid_Cloud_Storages();
		$cloud_storage->init();

		return $this->result = $result;
	}

	public function insert_temporary_cloud_image( $attachment_id ) {
		$clouds = Medoid_Cloud_Storages::get_active_clouds();
		if ( ! Medoid::is_active() || empty( $clouds ) ) {
			return;
		};

		$delete_local_file = apply_filters( 'medoid_delete_local_file', true );

		$image_data = array(
			'post_id'           => $attachment_id,
			'image_url'         => $this->result['url'],
			'file_size'         => filesize( $this->result['file'] ),
			'file_name'         => str_replace( ABSPATH, '', $this->result['file'] ),
			'mime_type'         => $this->result['type'],
			'delete_local_file' => $delete_local_file,
		);

		foreach ( array_keys( $clouds ) as $cloud_id ) {
			$image_data['cloud_id'] = $cloud_id;
			/**
			 * The cloud_id is zero this mean the cloud is Local Storage
			 * so the plugin don't need upload the image
			 */
			$image_data['is_uploaded'] = $cloud_id === 0;

			$this->db->insert_image( $image_data );
		}

		if ( $this->cdn_support_resize ) {
			remove_filter( 'intermediate_image_sizes', '__return_empty_array' );
			remove_filter( 'wp_update_attachment_metadata', '__return_null' );
		}
	}

	public function delete_image( $attachment_id ) {
		$this->db->delete_image_from_attachment( $attachment_id );
	}
}

new Medoid_Core_Upload_Handler();
