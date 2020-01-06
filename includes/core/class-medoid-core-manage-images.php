<?php
class Medoid_Core_Manage_Images {
	protected $db;

	public function __construct() {
		$this->db = Medoid_Core_Db::instance();

		/**
		 * Init the WordPress hooks
		 */
		add_action( 'delete_attachment', array( $this, 'delete_image' ) );
	}

	public function delete_image( $attachment_id ) {
		$this->delete_file_on_cloud_storage( $attachment_id );
		$this->delete_image_sizes( $attachment_id );

		$this->db->delete_image( $attachment_id );
	}

	public function delete_image_sizes( $attachment_id ) {
	}

	public function delete_file_on_cloud_storage( $attachment_id ) {
	}
}

new Medoid_Core_Manage_Images();
