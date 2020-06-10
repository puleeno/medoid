<?php
/**
 * This is the main gate to public image and delivery to your users.
 */
class Medoid_Image {
	protected $db;
	protected $cdn;

	public function __construct() {
		$this->db = Medoid_Core_Db::instance();
	}

	public function init_hooks() {
		add_action( 'wp_prepare_attachment_for_js', array( $this, 'prepare_json' ), 10, 3 );
		add_action( 'image_downsize', array( $this, 'image_downsize' ), 10, 3 );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'get_image_src' ), 99, 3 );

		// Hook actions to delete WordPress media
		add_action( 'delete_attachment', array( $this, 'delete_image' ) );
	}

	public function prepare_json( $response, $attachment, $meta ) {
		$medoid_image = $this->get_image( $attachment->ID );
		$thumbnail    = $this->get_image_size( $attachment->ID, array( 150, 150 ) );

		if ( $medoid_image ) {
			$medoid_image_url  = $medoid_image['image_url'];
			$response['url']   = $medoid_image_url;
			$response['icon']  = $thumbnail['image_url'];
			$response['sizes'] = array(
				'thumbnail' => array(
					'height'      => 150,
					'width'       => 150,
					'url'         => $thumbnail['image_url'],
					'orientation' => 'landscape',
				),
				'full'      => array(
					'height'      => 200,
					'width'       => 200,
					'url'         => $medoid_image_url,
					'orientation' => 'landscape',
				),
			);
		}

		return $response;
	}

	public function get_image( $attachment_id, $cloud_id = null ) {
		$medoid_image = $this->db->get_image_by_attachment_id(
			$attachment_id
		);
		if ( empty( $medoid_image ) ) {
			return;
		}

		return apply_filters( 'medoid_image', $medoid_image, $attachment_id, 'full', $cloud_id );
	}

	public function image_downsize( $downsize, $id, $size ) {
		return array(
			'wordpress_image' => $downsize,
			'processed'       => true,
		);
	}


	public function get_image_src( $medoid_image, $attachment_id, $size ) {
		if ( ! isset( $medoid_image['processed'] ) ) {
			return $medoid_image;
		}

		if ( $medoid_image['processed'] ) {
			return $medoid_image['wordpress_image'];
		}
	}

	public function delete_image( $attachment_id ) {
		$this->db->delete_image_from_attachment( $attachment_id );
	}
}
