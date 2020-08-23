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
			Logger::get( 'medoid' )->debug(
				sprintf(
					'Init the Backlaze client %s',
					var_export(
						array(
							'account_id'      => $accountId,
							'application_key' => $applicationKey,
						),
						true
					)
				)
			);

			$this->bucket_name = MEDOID_BACKBLAZE_DEFAULT_BUCKET_NAME;
			$this->client      = new Client( $accountId, $applicationKey );
		}

		return $this->client;
	}

	public function upload( $file, $new_file ) {
		$response = new Medoid_Response( $this->get_id() );
		try {
			if ( ! file_exists( $file ) ) {
				throw new Exception( sprintf( 'Can not open file %s', $file ) );
			}
			$new_file = preg_replace( '/\/{2,}/', '/', $new_file );
			$resource = @fopen( $file, 'r' );
			$b2_file  = $this->get_client()->upload(
				array(
					'BucketName' => $this->bucket_name,
					'FileName'   => medoid_remove_accents_file_name( ltrim( $new_file, '/' ) ),
					'Body'       => $resource,
				)
			);
			$response->set_provider_image_id( $b2_file->getId() );

			$refClient          = new ReflectionClass( $this->client );
			$downloadUrlRefProp = $refClient->getProperty( 'downloadUrl' );
			$downloadUrlRefProp->setAccessible( true );
			$downloadUrl = $downloadUrlRefProp->getValue( $this->client );
			/**
			 * Image url has format
			 *
			 * https://<download_url>/file/<bucket_name>/<file_name>
			 */
			$url = sprintf( '%s/file/%s/%s', $downloadUrl, $this->bucket_name, $b2_file->getName() );
			$response->set_url( $url );

			$file_size = ! empty( $file['size'] ) ? $file['size'] : $b2_file->getSize();
			$response->set( 'file_size', $file_size );

			$response->set_status( true );

			Logger::get( 'medoid' )->info(
				sprintf(
					'The attachment %s is uploaded successfully with new URL is "%s"',
					$file,
					$url
				)
			);
		} catch ( Throwable $e ) {
			$response->set_status( false );
			$response->set_error( $e );

			Logger::get( 'medoid' )->warning(
				sprintf(
					"%s\n%s",
					$e->getMessage(),
					var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true )
				),
				array(
					'file'     => $file,
					'new_file' => $new_file,
				)
			);
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

			Logger::get( 'medoid' )->warning(
				sprintf(
					'The delete action for image #%d - %s is skipped',
					$image->post_id,
					$image->image_url,
				),
				(array) $image
			);
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
				Logger::get( 'medoid' )->notice(
					sprintf(
						'The image #%d is marked delete flag',
						$image->ID
					),
					array(
						'cloud_id' => $image->cloud_id,
						'post_id'  => $image->post_id,
					)
				);
			} else {
				if ( ! empty( $image->provider_image_id ) ) {
					// Delete file on Backblaze via FileID
					$this->client->deleteFile( array( 'FileId' => $image->provider_image_id ) );
				} elseif ( (bool) $image->is_uploaded ) {
					Logger::get( 'medoid' )->warning(
						'Provider image ID is not exists so can not delete it',
						(array) $image
					);
				}

				// Delete Medoid image from database
				$this->db->delete_image( $image->ID, $this->get_id(), true );

				// Delete WordPress attachment
				wp_delete_attachment( $image->post_id );
				Logger::get( 'medoid' )->debug(
					sprintf(
						'The image #%d is deleted forever',
						$image->ID
					),
					array(
						'cloud_id' => $image->cloud_id,
						'post_id'  => $image->post_id,
					)
				);
			}
		} catch ( Exception $e ) {
			Logger::get( 'medoid' )->warning(
				sprintf(
					"%s\n\%s",
					$e->getMessage(),
					var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true )
				)
			);
		}
	}
}
