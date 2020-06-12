<?php
class Medoid_CDN_CloudImage extends Medoid_CDN implements Medoid_CDN_Processing {
	const TYPE_NAME = 'cloudimage';
	const VERSION   = 'v7';

	protected $cloudimage_token;
	protected $cloudimage_output;

	protected $image_url;
	protected $cloud;

	public function __toString() {
		return $this->image_url;
	}

	public function get_name() {
		return 'Cloudimage';
	}

	public function get_url() {
		return sprintf(
			'https://%s.cloudimg.io/%s',
			$this->cloudimage_token,
			self::VERSION
		);
	}

	public function resize( $size ) {
	}
}
