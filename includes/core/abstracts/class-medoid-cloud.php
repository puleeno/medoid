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

		$notify_key = sprintf( 'medoid_%s_%s_notified', $this->get_name(), $this->get_id() );
		$notified   = get_option( $notify_key, false );
		if ( empty( $images ) ) {
			if ( ! $notified ) {
				Logger::get( 'medoid' )->notice(
					sprintf(
						'The %s(#%s) sync process is maybe completed.',
						$this->get_name(),
						$this->get_id()
					)
				);
			}
			return;
		} elseif ( ! $notified ) {
			update_option( $notify_key, true );
		}

		Logger::get( 'medoid' )->debug( 'Load images from database to sync to ' . $this->get_name(), array( 'total_images' => count( $images ) ) );
		foreach ( $images as $image ) {
			$attachment = get_post( $image->post_id );
			if ( empty( $attachment ) ) {
				$this->delete_image( $image, true, false );
				Logger::get( 'medoid' )->warning(
					sprintf( 'The image #%d is not exists in WordPress so it can not delete', $image->post_id ),
					(array) $image
				);
				continue;
			}
			$file    = get_attached_file( $image->post_id, true );
			$newfile = $this->make_unique_file_name( $file, $image );
			Logger::get( 'medoid' )->info(
				sprintf(
					'File %s is generated a new name: %s',
					$file,
					$newfile
				)
			);

			try {
				Logger::get( 'medoid' )->debug(
					sprintf(
						'The attachment #%d is uploading to the %s cloud',
						$image->post_id,
						$this->get_id()
					)
				);
				$response = $this->upload( $file, $newfile );
				if ( $response->get_status() ) {
					$image_info = array(
						'ID'                => $image->ID,
						'image_url'         => $response->get_url(),
						'provider_image_id' => $response->get_provider_image_id(),
						'is_uploaded'       => true,
						'updated_at'        => current_time( 'mysql' ),
					);
					$this->db->update_image( $image_info );

					/**
					 * Do actions after upload image to cloud success
					 */
					do_action( 'medoid_upload_cloud_image', $image, $response, $this );

					Logger::get( 'medoid' )->info(
						sprintf(
							'The image #%d(%s) to %s is uploaded successful',
							$image->ID,
							$image->image_url,
							$this->get_name()
						),
						(array) $response
					);
				} else {
					$this->db->update_image(
						array(
							'ID'         => $image->ID,
							'retry'      => (int) $image->retry + 1,
							'updated_at' => current_time( 'mysql' ),
						)
					);

					Logger::get( 'medoid' )->warning(
						sprintf(
							'Upload image #%d(%s) to %s is failed: %s',
							$image->ID,
							$image->image_url,
							$this->get_name(),
							$response->get_error_message()
						),
						(array) $response
					);
				}
			} catch ( Exception $e ) {
				Logger::get( 'medoid' )->error(
					sprintf(
						"%s\n%s",
						$e->getMessage(),
						var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) )
					),
					$image
				);
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
