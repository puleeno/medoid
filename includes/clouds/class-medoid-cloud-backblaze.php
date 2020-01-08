<?php
use BackblazeB2\Client;
use BackblazeB2\Bucket;

class Medoid_Cloud_Backblaze extends Medoid_Cloud {
	protected $_internal_cloud_id;
	protected $client;
	protected $bucket_name;

	public function __construct( $id, $configs = array() ) {
		$this->_internal_cloud_id = $id;

		$accountId      = MEDOID_BACKBLAZE_APP_KEY_ID;
		$applicationKey = MEDOID_BACKBLAZE_APP_MASTER_KEY;

		$this->bucket_name = MEDOID_BACKBLAZE_DEFAULT_BUCKET_NAME;
		$this->client      = new Client( $accountId, $applicationKey );
	}

	public function upload( $file, $new_file, $post = null, $type = null ) {
		$response = new Medoid_Response( $this->_internal_cloud_id );
		try {
			$parent_folder = '';
			$folder        = '';
			if ( $post ) {
				$folder .= $post->post_name . '/';
				if ( $post->post_parent ) {
					$parent         = get_post( $post->post_parent );
					$parent_folder .= $parent->post_name . '/';
				}
			}
			$ext       = pathinfo( $file['name'], PATHINFO_EXTENSION );
			$file_name = sprintf( '%s%s%s.%s', $parent_folder, $folder, substr( md5( $file['name'] . time() ), 0, 6 ), $ext );
			$tmp_file  = $file['tmp_name'];
			$resource  = fopen( $tmp_file, 'r' );

			$backblaze_file = $this->client->upload(
				[
					'BucketName' => $this->bucket_name,
					'FileName'   => $file_name,
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
}
