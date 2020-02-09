<?php

abstract class Medoid_Cloud implements Medoid_Cloud_Interface {
	protected $db;
	protected $_internal_cloud_id;

	protected function set_id( $id ) {
		$this->_internal_cloud_id = (int) $id;
	}

	public function get_id() {
		return $this->_internal_cloud_id;
	}

	public function sync_to_cloud( $limit_items = 50 ) {
		$this->db = Medoid_Core_Db::instance();
		$images   = $this->db->get_images(
			array(
				'cloud_id'    => $this->get_id(),
				'is_uploaded' => false,
				'limit'       => 50,
				'orderby'     => 'retry DESC, post_id ASC',
			)
		);
		if ( empty( $images ) ) {
			return;
		}

		foreach ( $images as $image ) {
			$file    = get_attached_file( $image->post_id, true );
			$newfile = apply_filters_ref_array(
				'medoid_create_file_name_unique',
				array( basename( $file ), $image, &$this )
			);

			if ( empty( $newfile ) ) {
				$this->delete_file( $image->ID );
				continue;
			}
			$response = $this->upload( $file, $newfile );

			if ( $response->status ) {
				$this->db->update_image(
					array(
						'image_url'   => $response->get_url(),
						'is_uploaded' => true,
						'updated_at'  => current_time( 'mysql' ),
					)
				);

				/**
				 * Do actions after upload image to cloud success
				 */
				do_action( 'medoid_upload_cloud_image', $image, $response, $this );
			} else {
				$this->db->update_image(
					array(
						'retry'      => (int) $image->retry + 1,
						'updated_at' => current_time( 'mysql' ),
					)
				);
			}
		}
	}
}
