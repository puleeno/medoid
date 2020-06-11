<?php
class Medoid_CDN_CloudImage extends Medoid_CDN implements Medoid_CDN_Processing {
	const TYPE_NAME = 'cloudimage';

	public function __toString() {
	}

	public function load_options( $options = array() ) {
		$this->token = apply_filters(
			'medoid_cdn_cloudimage_token',
			array_get( $options, 'cloudimage_token', null ),
			$options
		);
	}

	public function get_name() {
		return 'Cloudimage';
	}
}
