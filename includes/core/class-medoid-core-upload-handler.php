<?php
class Medoid_Core_Upload_Handler {
	protected $results;

	public function __construct() {
		add_filter( 'pre_move_uploaded_file', array( $this, 'upload_handle' ), 10, 4 );
		add_filter( 'wp_handle_upload', array( $this, 'upload_results' ), 10, 2 );
		// _wp_attachment_metadata Add meta to integrated with WordPress core.
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
			$this->results = $response;
		}

		return 'custom_upload_handle';
	}

	public function upload_results( $results, $action ) {
		if ( empty( $this->results ) ) {
			return $results;
		}
		$results['url'] = (string) $this->results->get_url();

		return $results;
	}
}

new Medoid_Core_Upload_Handler();
