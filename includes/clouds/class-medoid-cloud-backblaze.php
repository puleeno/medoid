<?php
use Mhetreramesh\Flysystem\BackblazeAdapter;
use League\Flysystem\Filesystem;
use BackblazeB2\Client;

class Medoid_Cloud_Backblaze extends Medoid_Cloud {
	protected $filesystem;

	public function __construct( $configs = array() ) {
		$accountId      = MEDOID_BACKBLAZE_APP_KEY_ID;
		$applicationKey = MEDOID_BACKBLAZE_APP_MASTER_KEY;
		$bucketName     = MEDOID_BACKBLAZE_DEFAULT_BUCKET_NAME;
		$client         = new Client( $accountId, $applicationKey );
		$adapter        = new BackblazeAdapter( $client, $bucketName );

		$this->filesystem = new Filesystem( $adapter );
	}
}
