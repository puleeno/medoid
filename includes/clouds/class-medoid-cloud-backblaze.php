<?php
use BackblazeB2\Client;
use BackblazeB2\Bucket;

class Medoid_Cloud_Backblaze extends Medoid_Cloud {
	const CLOUD_TYPE = 'backblaze';

	protected $client;
	protected $bucket_name;

	public function __construct( $id, $configs = array() ) {
		$this->set_id( $id );

		$accountId      = MEDOID_BACKBLAZE_APP_KEY_ID;
		$applicationKey = MEDOID_BACKBLAZE_APP_MASTER_KEY;

		$this->bucket_name = MEDOID_BACKBLAZE_DEFAULT_BUCKET_NAME;
		$this->client      = new Client( $accountId, $applicationKey );
	}

	public function upload( $file, $new_file ) {
		$response = new Medoid_Response( $this->get_id() );
		try {
			$resource       = fopen( $file, 'r' );
			$backblaze_file = $this->client->upload(
				[
					'BucketName' => $this->bucket_name,
					'FileName'   => $new_file,
					'Body'       => $resource,
				]
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
		var_dump( $file );
		die;
		return $this->client->fileExists(
			array(
				'BucketName' => $this->bucket_name,
				'FileName'   => $file,
			)
		);
	}

	public function delete_file( $file_id ) {
	}
}
