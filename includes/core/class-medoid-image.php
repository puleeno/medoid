<?php
/**
 * This is the main gate to public image and delivery to your users.
 */
class Medoid_Image {
	protected $db;
	protected $cdn;

	public function __construct() {
		$this->db  = Medoid_Core_Db::instance();
		$this->cdn = Medoid_Core_CDN_Integration::instance();

		add_action( 'init', array( $this, 'init_hooks' ) );

		if ( defined( 'MEDOID_DEBUG' ) && MEDOID_DEBUG ) {
			add_filter( 'wp_get_attachment_image_src', array( $this, 'image_dev_url' ), 5 );
		}
	}

	public function init_hooks() {
		add_action( 'wp_prepare_attachment_for_js', array( $this, 'prepare_json' ), 10, 3 );
		add_action( 'image_downsize', array( $this, 'resize_image' ), 10, 3 );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'image_src' ), 99, 3 );

		// Hook actions to delete WordPress media
		add_action( 'delete_attachment', array( $this, 'delete_image' ) );
	}

	public function get_image( $attachment_id, $cloud_id = null ) {
		$medoid_image = $this->db->get_image_by_attachment_id(
			$attachment_id
		);
		if ( empty( $medoid_image ) ) {
			return;
		}
		if ( $this->cdn->is_enabled() ) {
			$medoid_image['image_url'] = $this->cdn->delivery( $medoid_image['image_url'] );
		}

		return apply_filters( 'medoid_image', $medoid_image, $attachment_id, 'full', $cloud_id );
	}

	public function get_image_size( $attachment_id, $size = 'thumbnail', $cloud_id = null ) {
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

				return apply_filters( 'medoid_image', $medoid_image, $attachment_id, 'full', $cloud_id );
			}
		}

		if ( 'full' === $size ) {
			return $this->get_image( $attachment_id, $cloud_id = null );
		}

		return apply_filters(
			'medoid_image',
			$this->db->get_image_size( $attachment_id, $size, $cloud_id = null ),
			$attachment_id,
			$size,
			$cloud_id
		);
	}

	public function resize_image( $downsize, $id, $size ) {
		return $downsize;
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

	public function image_src( $image, $attachment_id, $size ) {
		if ( empty( $image ) ) {
			$meta  = wp_get_attachment_metadata( $attachment_id );
			$image = array(
				wp_get_attachment_url( $attachment_id ),
				$meta['width'],
				$meta['height'],
			);
		}

		$sizes = medoid_get_image_sizes( $size );
		if ( ! isset( $sizes['width'], $sizes['height'] ) ) {
			return $image;
		}

		try {
			$str_size   = sprintf( '%sx%s', $sizes['width'], $sizes['height'] );
			$image_size = $this->db->get_image_size_by_attachment_id( $attachment_id, $str_size );
			if ( $image_size ) {
				$image[0] = $image_size;
				return $image;
			}

			$image_url = '';
			if ( $this->cdn->is_enabled() && $this->cdn->get_provider()->is_support( 'resize' ) ) {
				$medoid_image = $this->db->get_image_by_attachment_id( $attachment_id );

				if ( $medoid_image ) {
					$image_url = $this->cdn->resize( $medoid_image['image_url'], $sizes );
				} else {
					$image_url = $this->cdn->resize( wp_get_attachment_url( $attachment_id ), $sizes );
				}
			}
			if ( $image_url ) {
				return array( $image_url, $sizes['width'], $sizes['height'] );
			}
		} catch ( Exception $e ) {
			Medoid_Logger::error( $e->getMessage() );
		}
		return $image;
	}

	public function image_dev_url( $image_url ) {
		if ( ! defined( 'MEDOID_DEBUG_DOMAIN' ) ) {
			return $image_url;
		}
		 $dev_domain = constant( 'MEDOID_DEBUG_DOMAIN' );

		 return str_replace( site_url(), $dev_domain, $image_url );
	}

	public function delete_image( $attachment_id ) {
		$this->db->delete_image_from_attachment( $attachment_id );
	}
}

new Medoid_Image();
