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

	public function image_downsize( $downsize, $id, $size ) {
		$numeric_size = medoid_get_image_sizes( $size );
		$active_cloud = $this->manager->get_active_cloud();
		if ( empty( $downsize ) ) {
			$image_size = $this->db->get_image_size_by_attachment_id( $id, $size, $active_cloud->get_id() );
			if ( empty( $image_size ) ) {
				$medoid_image = $this->db->get_image_by_attachment_id( $id, $active_cloud->get_id() );
				if ( empty( $medoid_image ) ) {
					return $downsize;
				}
				$downsize[0] = $medoid_image->image_url;
			}
			$downsize[1] = $numeric_size['width'];
			$downsize[2] = $numeric_size['height'];
		}

		$cdn_class = $this->manager->get_active_cdn();
		if ( $cdn_class ) {
			$cdn_image = new $cdn_class( $downsize[0], $active_cloud, empty( $image_size ) );
			if ( $cdn_image->is_support( 'resize' ) ) {
				$cdn_image->resize( $numeric_size );
			}
			$downsize[0] = $cdn_image;
		}

		return array(
			'wordpress_image' => $downsize,
			'processed'       => true,
		);
	}

	public function get_image_src( $medoid_image, $attachment_id, $size ) {
		if ( ! isset( $medoid_image['processed'] ) ) {
			return $medoid_image;
		}

		return $medoid_image['wordpress_image'];
	}

	public function delete_image( $attachment_id ) {
		$this->db->delete_image_from_attachment( $attachment_id );
	}
}
