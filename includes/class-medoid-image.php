<?php
/**
 * This is the main gate to public image and delivery to your users.
 */
class Medoid_Image {
	protected $db;
	protected $cdn;

	public function __construct() {
		$this->db  = Medoid_Core_Db::instance();
		$this->cdn = Medoid_Core_Cdn_Integration::instance();

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
		$medoid_image = $this->db->get_image_by_attachment_id(
			$attachment_id
		);
		if ( empty( $medoid_image ) ) {
			return;
		}
		$medoid_image['image_url'] = $this->cdn->delivery( $medoid_image['image_url'] );

		return $medoid_image;
	}

	public function get_image_size( $attachment_id, $size = 'thumbnail' ) {
		$medoid_image = $this->db->get_image_by_attachment_id(
			$attachment_id
		);
		if ( empty( $medoid_image ) ) {
			return false;
		}

		if ( $this->cdn->get_provider()->is_support( 'resize' ) ) {
			$sizes = medoid_get_image_sizes( $size );
			if ( $medoid_image ) {
				$medoid_image['image_url'] = $this->cdn->resize( $medoid_image['image_url'], $sizes );
				$medoid_image['sizes']     = $sizes;

				return $medoid_image;
			}
		}

		return $this->db->get_image_size( $attachment_id, $size, $cloud_id = null );
	}

	public function image_downsize( $image, $attachment_id, $size ) {
		return $image;
	}

	public function prepare_json( $response, $attachment, $meta ) {
		$medoid_image = $this->get_image( $attachment->ID );
		$thumbnail    = $this->get_image_size( $attachment->ID, array( 150, 150 ) );

		if ( $medoid_image ) {
			$medoid_image_url = $medoid_image['image_url'];

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

	public function image_src( $image, $attachment_id, $size ) {
		$medoid_image = $this->get_image_size( $attachment_id, $size );
		if ( false !== $medoid_image ) {
			$image[0] = $medoid_image['image_url'];
			$image[1] = $medoid_image['sizes']['width'];
			$image[2] = $medoid_image['sizes']['height'];
		}

		return $image;
	}
}

new Medoid_Image();
