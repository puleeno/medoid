<?php
use BackblazeB2\Client;
use BackblazeB2\Bucket;

class Medoid_Cloud_Backblaze extends Medoid_Cloud {
	const CLOUD_TYPE = 'backblaze';

	protected $client;
	protected $bucket_name;

	public function get_name() {
		return ucfirst( self::CLOUD_TYPE );
	}

	public function get_client() {
		if ( is_null( $this->client ) ) {
			$accountId      = MEDOID_BACKBLAZE_APP_KEY_ID;
			$applicationKey = MEDOID_BACKBLAZE_APP_MASTER_KEY;

			$this->bucket_name = MEDOID_BACKBLAZE_DEFAULT_BUCKET_NAME;
			$this->client      = new Client( $accountId, $applicationKey );
		}

		return $this->client;
	}

	public function upload( $file, $new_file ) {
		$response = new Medoid_Response( $this->get_id() );
		try {
			$resource = @fopen( $file, 'r' );
			if ( $resource === false ) {
				throw new Exception( sprintf( 'Can not open file %s', $file ) );
			}
			$backblaze_file = $this->get_client()->upload(
				array(
					'BucketName' => $this->bucket_name,
					'FileName'   => ltrim( $new_file, '/' ),
					'Body'       => $resource,
				)
			);
			$response->set_provider_image_id( $backblaze_file->getId() );

			$refClient          = new ReflectionClass( $this->client );
			$downloadUrlRefProp = $refClient->getProperty( 'downloadUrl' );
			$downloadUrlRefProp->setAccessible( true );
			$downloadUrl = $downloadUrlRefProp->getValue( $this->client );
			/**
			 * Image url has format
			 *
			 * https://<download_url>/file/<bucket_name>/<file_name>
			 */
			$url = sprintf( '%s/file/%s/%s', $downloadUrl, $this->bucket_name, $backblaze_file->getName() );
			$response->set_url( $url );

			$file_size = ! empty( $file['size'] ) ? $file['size'] : $backblaze_file->getSize();
			$response->set( 'file_size', $file_size );

			$response->set_status( true );
		} catch ( \Exception $e ) {
			$response->set_status( false );
			$response->set_error( $e );
		}

		return $response;
	}

	public function is_exists( $file ) {
		return $this->get_client()->fileExists(
			array(
				'BucketName' => $this->bucket_name,
				'FileName'   => $file,
			)
		);
	}

	public function delete_image( $image, $delete_cloud_file = true, $delete_attachment = true, $soft_delete = false ) {
		if ( is_numeric( $image ) ) {
			$image = $this->db->get_image( $image );
		}
		if ( empty( $image ) || $image->cloud_id != $this->get_id() ) {
			$image->current_cloud_id = $this->get_id();

			Medoid_Logger::debug( 'The delete action is skipped', $image );
			return;
		}

		try {
			if ( $soft_delete ) {
				if ( $delete_attachment ) {
					wp_update_post(
						array(
							'ID'          => $image->post_id,
							'post_status' => 'trash',
						)
					);
				}
				$this->db->delete_image( $image->ID, false );
				Medoid_Logger::debug(
					sprintf(
						'The image #%d is marked delete flag',
						$image->ID,
						array(
							'cloud_id' => $image->cloud_id,
							'post_id'  => $image->post_id,
						)
					)
				);
			} else {
				if ( ! empty( $image->provider_image_id ) ) {
					// Delete file on Backblaze via FileID
					$this->client->deleteFile( array( 'FileId' => $image->provider_image_id ) );
				} elseif ( (bool) $image->is_uploaded ) {
					Medoid_Logger::warning( 'Provider image ID is not exists so can not delete it', $image );
				}

				// Delete Medoid image from database
				$this->db->delete_image( $image->ID, $this->get_id(), true );

				// Delete WordPress attachment
				wp_delete_attachment( $image->post_id );
				Medoid_Logger::debug(
					sprintf(
						'The image #%d is deleted forever',
						$image->ID,
						array(
							'cloud_id' => $image->cloud_id,
							'post_id'  => $image->post_id,
						)
					)
				);
			}
		} catch ( Exception $e ) {
			Medoid_Logger::warning( $e->getMessage(), $image );
		}
	}
}

return Medoid_Cloud_Backblaze::class;
