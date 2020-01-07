<?php
class Medoid_Core_Upload_Handler {
	protected $result;
	protected $db;
	protected $file;

	public function __construct() {
		$this->db = Medoid_Core_Db::instance();

		add_filter( 'pre_move_uploaded_file', array( $this, 'upload_handle' ), 10, 4 );
		add_filter( 'wp_handle_upload', array( $this, 'upload_result' ), 10, 2 );
		add_filter( 'wp_update_attachment_metadata', array( $this, 'update_image_meta' ), 10, 2 );
		add_filter( 'update_attached_file', array( $this, 'dont_create__wp_attached_file' ), 10, 2 );
	}

	public function upload_handle( $pre_move_file_handle, $file, $new_file, $type ) {
		$medoid_enabled = true;
		if ( ! $medoid_enabled ) {
			return $pre_move_file_handle;
		}
		$post = null;
		if ( isset( $_POST['post_id'] ) ) {
			$post = get_post( $_POST['post_id'] );
		} elseif ( isset( $_POST['post'] ) ) {
			$post = get_post( $_POST['post'] );
		}

		$medoid = apply_filters(
			'medoid_active_cloud_provider',
			Medoid_Cloud_Storages::getDefaultCloud(),
			$file,
			$post,
			array(
				'new_file' => $new_file,
				'type'     => $type,
				'request'  => $_POST,
			)
		);
		if ( ! $medoid instanceof Medoid_Cloud ) {
			return null;
		}

		$response = $medoid->upload( $file, $new_file, $post, $type );
		if ( $response instanceof Medoid_Response ) {
			$this->result = $response;
			$this->file   = $file;
		}

		return 'custom_upload_handle';
	}

	public function upload_result( $result, $action ) {
		if ( empty( $this->result ) ) {
			return false;
		}

		$result['url'] = (string) $this->result->get_url();
		return $result;
	}

	public function update_image_meta( $data, $attachment_id ) {
		if ( empty( $this->result ) ) {
			return $data;
		}

		$image_data = array(
			'post_id'           => $attachment_id,
			'cloud_id'          => $this->result->get_provider_id(),
			'provider_image_id' => $this->result->get_provider_image_id(),
			'image_url'         => $this->result->get_url(),
			'file_size'         => $this->result->get( 'file_size' ),
			'file_name'         => $this->file['name'],
			'mime_type'         => $this->file['type'],
		);

		$image = $this->db->insert_image( $image_data );
		if ( is_wp_error( $image ) ) {
			return $data;
		}

		return [];
	}

	public function dont_create__wp_attached_file( $file, $attachment_id ) {
		if ( $this->result ) {
			return null;
		}

		return $file;
	}
}

new Medoid_Core_Upload_Handler();
