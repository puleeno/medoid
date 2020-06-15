<?php
class Medoid_CDN_CloudImage extends Medoid_CDN implements Medoid_CDN_Processing {
	const TYPE_NAME = 'cloudimage';
	const VERSION   = 'v7';

	protected $cloudimage_token;
	protected $cloudimage_output;

	protected $image_url;
	protected $cloud;

	protected $sizes = array();

	public function __toString() {
		$cloudimage_url = $this->get_url();
		$query_args     = array();

		if ( ! empty( $this->sizes ) ) {
			if ( $this->sizes['width'] > 0 ) {
				$query_args['w'] = $this->sizes['width'];
			}
			if ( $this->sizes['height'] > 0 ) {
				$query_args['h'] = $this->sizes['height'];
			}
		}

		$ret = $cloudimage_url . $this->image_url;
		if ( ! empty( $query_args ) ) {
			$ret .= '?' . http_build_query( $query_args );
		}

		return apply_filters( 'medoid_cdn_image_url_output', $ret, $this );
	}

	public function get_name() {
		return 'Cloudimage';
	}

	public function get_url() {
		return sprintf(
			'https://%s.cloudimg.io/%s/',
			$this->cloudimage_token,
			self::VERSION
		);
	}

	public function resize( $width, $height = false ) {
		$this->sizes = array(
			'width'  => $width,
			'height' => $height,
		);
	}
}
