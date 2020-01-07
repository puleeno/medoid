<?php
/**
 * This is the main gate to public image and delivery to your users.
 */
class Medoid_Image {
	protected $db;

	public function __construct() {
		$this->db = Medoid_Core_Db::instance();
		add_action( 'init', array( $this, 'init_hooks' ) );
	}

	public function init_hooks() {
		add_action( 'wp_prepare_attachment_for_js', array( $this, 'prepare_json' ), 10, 3 );

		add_filter( 'image_downsize', array( $this, 'image_downsize' ), 10, 3 );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'image_src' ), 99, 3 );
	}

	public function load_image( $image, $attachment_id, $size ) {
		return $image;
	}

	public function get_image( $attachment_id ) {
		return $this->db->get_image_by_attachment_id(
			$attachment_id
		);
	}

	public function get_image_size( $attachment_id, $size = 'thumbnail' ) {
	}

	public function image_downsize( $image, $attachment_id, $size ) {
		return $image;
	}

	public function prepare_json( $response, $attachment, $meta ) {
		$medoid_image = $this->get_image( $attachment->ID );

		if ( $medoid_image ) {
			$medoid_image_url = $medoid_image['image_url'];

			$response['url']   = $medoid_image_url;
			$response['icon']  = $medoid_image_url;
			$response['sizes'] = array(
				'thumbnail' => array(
					'height'      => 150,
					'width'       => 150,
					'url'         => $medoid_image_url,
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

	public function image_src( $image, $attachment_id, $size ) {
		$medoid_image = $this->get_image( $attachment_id, $size );
		if ( $medoid_image ) {
			$image[0] = $medoid_image['image_url'];
		}
		return $image;
	}
}

new Medoid_Image();
