<?php
/**
 * This is the main gate to public image and delivery to your users.
 */
class Medoid_Core_Image_Delivery {
	protected $db;
	protected $cdn;

	public function __construct() {
		$this->db      = Medoid_Core_Db::instance();
		$this->manager = Medoid_Core_Manager::get_instance();
	}

	public function init_hooks() {
		add_action( 'image_downsize', array( $this, 'image_downsize' ), 10, 3 );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'get_image_src' ), 99, 3 );
		add_action( 'wp_prepare_attachment_for_js', array( $this, 'prepare_json' ), 10, 3 );
	}

	public function prepare_json( $response, $attachment, $meta ) {
		$thumbnail = wp_get_attachment_image_src( $attachment->ID, array( 150, 150 ) );

		if ( $thumbnail ) {
			$thumbnail_url    = (string) $thumbnail[0];
			$response['icon'] = $thumbnail_url;

			// Override WordPress media JSON thumbnails
			$response['sizes']['thumbnail'] = array(
				'url'         => $thumbnail_url,
				'width'       => $thumbnail[1],
				'height'      => $thumbnail[2],
				'orientation' => 'landscape',
			);
		}

		return $response;
	}

	public function image_downsize( $downsize, $id, $size ) {
		$numeric_size = medoid_get_image_sizes( $size );
		$active_cloud = $this->manager->get_active_cloud();

		if ( empty( $downsize ) ) {
			$medoid_image = $this->db->get_image_size_by_attachment_id( $id, $size, $active_cloud->get_id() );
		}

		return $downsize;
	}

	public function get_image_src( $image, $attachment_id, $size ) {
		return $image;
	}
}
