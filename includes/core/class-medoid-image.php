<?php
/**
 * This is the main gate to public image and delivery to your users.
 */
class Medoid_Image {
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

		// Hook actions to delete WordPress media
		add_action( 'delete_attachment', array( $this, 'delete_image' ) );
	}

	public function prepare_json( $response, $attachment, $meta ) {
		$thumbnail_size = array( 150, 150 );
		$meta           = wp_get_attachment_metadata( $attachment->ID );

		$image     = wp_get_attachment_url( $attachment->ID );
		$thumbnail = get_post_thumbnail( $attachment->ID, $thumbnail_size );

		if ( $medoid_image ) {
			$response['url']   = $image;
			$response['icon']  = $thumbnail;
			$response['sizes'] = array(
				'thumbnail' => array(
					'width'       => $thumbnail_size[0],
					'height'      => $thumbnail_size[1],
					'url'         => $thumbnail,
					'orientation' => 'landscape',
				),
				'full'      => array(
					'height'      => $meta['height'],
					'width'       => $meta['width'],
					'url'         => $image,
					'orientation' => 'landscape',
				),
			);
		}
		return $response;
	}

	public function image_downsize( $downsize, $id, $size ) {
		$numeric_size = medoid_get_image_sizes( $size );
		$active_cloud = $this->manager->get_active_cloud();
		if ( empty( $downsize ) ) {
			$medoid_image = $this->db->get_image_size_by_attachment_id( $id, $size, $active_cloud->get_id() );
			if ( empty( $medoid_image ) ) {
				$medoid_image = $this->db->get_image_by_attachment_id( $id, $active_cloud->get_id() );
				if ( empty( $medoid_image ) ) {
					return $downsize;
				}
				$downsize[0] = $medoid_image->image_url;
			} else {
				$downsize[3] = true;
			}
			$downsize[1] = $numeric_size['width'];
			$downsize[2] = $numeric_size['height'];
		}

		return $downsize;
	}

	public function get_image_src( $image, $attachment_id, $size ) {
		if ( isset( $image[3] ) && $image[3] ) {
			return $image;
		}
		$numeric_size    = medoid_get_image_sizes( $size );
		$active_cloud    = $this->manager->get_active_cloud();
		$active_cdn_info = $this->manager->get_cdn();
		if ( isset( $active_cdn_info['class_name'] ) && class_exists( $active_cdn_info['class_name'] ) ) {
			$cdn_classname = $active_cdn_info['class_name'];
			$cdn_image     = new $cdn_classname( $image[0], $active_cloud, empty( $image_size ), $active_cdn_info );
			if ( $cdn_image->is_support( 'resize' ) ) {
				$cdn_image->resize( $numeric_size['width'], $numeric_size['height'] );
			}
			$image[0] = $cdn_image;
		}

		return $image;
	}

	public function delete_image( $attachment_id ) {
		$this->db->delete_image_from_attachment( $attachment_id );
	}
}
