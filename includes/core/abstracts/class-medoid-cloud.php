<?php

abstract class Medoid_Cloud implements Medoid_Cloud_Interface {
	protected $db;
	protected $_internal_cloud_id;
	protected $options;

	public function __construct( $id, $configs = array() ) {
		$this->set_id( $id );
		$this->set_options( $configs );
	}

	protected function set_options( $options ) {
		$this->options = $options;
	}

	protected function set_id( $id ) {
		$this->_internal_cloud_id = (int) $id;
	}

	public function get_id() {
		return $this->_internal_cloud_id;
	}

	public function get_db() {
		if ( empty( $this->db ) ) {
			$this->db = Medoid_Core_Db::instance();
		}
		return $this->db;
	}

	public function sync_to_cloud( $limit_items = 50 ) {
		$images = $this->get_db()->get_images(
			array(
				'cloud_id'    => $this->get_id(),
				'limit'       => $limit_items,
				'is_uploaded' => false,
				'is_deleted'  => false,
				'orderby'     => 'retry ASC, updated_at ASC, post_id ASC',
			)
		);

		Medoid_Logger::debug( 'Load images from database to sync to ' . $this->get_name(), array( 'total_images' => count( $images ) ) );
		if ( empty( $images ) ) {
			return;
		}

		foreach ( $images as $image ) {
			$attachment = get_post( $image->post_id );
			if ( empty( $attachment ) ) {
				$this->delete_image( $image, true, false );

				Medoid_Logger::debug( 'Delete the image not exists in WordPress', $image, false, 'medoid_syncer' );
				continue;
			}
			$file    = get_attached_file( $image->post_id, true );
			$newfile = $this->make_unique_file_name( $file, $image );

			try {
				$response = $this->upload( $file, $newfile );
				if ( $response->get_status() ) {
					$this->db->update_image(
						array(
							'ID'                => $image->ID,
							'image_url'         => $response->get_url(),
							'provider_image_id' => $response->get_provider_image_id(),
							'is_uploaded'       => true,
							'updated_at'        => current_time( 'mysql' ),
						)
					);

					/**
					 * Do actions after upload image to cloud success
					 */
					do_action( 'medoid_upload_cloud_image', $image, $response, $this );

					Medoid_Logger::info(
						sprintf( 'The image #%d(%s) to %s is uploaded successful', $image->ID, $image->image_url, $this->get_name() ),
						$response
					);
				} else {
					$this->db->update_image(
						array(
							'ID'         => $image->ID,
							'retry'      => (int) $image->retry + 1,
							'updated_at' => current_time( 'mysql' ),
						)
					);

					Medoid_Logger::warning(
						sprintf(
							'Upload image #%d(%s) to %s is failed: %s',
							$image->ID,
							$image->image_url,
							$this->get_name(),
							$response->get_error_message()
						),
						$response
					);
				}
			} catch ( Exception $e ) {
				Medoid_Logger::error( $e->getMessage(), $image, false, 'medoid_syncer' );
			}
		}
	}

	public function make_unique_file_name( $file, $medoid_image ) {
		return apply_filters_ref_array(
			'medoid_create_file_name_unique',
			array( basename( $file ), $medoid_image, &$this )
		);
	}
}
