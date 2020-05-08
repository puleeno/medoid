<?php
class Medoid_CDN_CloudImage extends Medoid_CDN {
	protected $processing = true;

	protected $support_url         = true;
	protected $support_proxy       = true;
	protected $support_crop        = true;
	protected $support_resize      = true;
	protected $support_filters     = true;
	protected $support_operattions = true;
	protected $support_watermark   = true;

	protected $domain = 'cloudimage.io';
	protected $token;

	public function load_options( $options = array() ) {
		$this->token = apply_filters(
			'medoid_cdn_cloudimage_token',
			array_get( $options, 'cloudimage_token', null ),
			$options
		);
	}

	protected function create_url( $url, $sizes = array() ) {
		$query = '';

		if ( ! empty( $api_query ) ) {
			$query .= '?' . http_build_query( $sizes );
		}

		$site    = parse_url( site_url() );
		$filters = $this->get_filters();

		$image_url = preg_replace(
			$filters['search'],
			$filters['replace'],
			$url
		);

		return sprintf( '%s%s', $image_url, $query );
	}

	public function get_filters() {
		return apply_filters(
			'medoid_cdn_cloudimage_filters',
			array(
				'search'  => array(),
				'replace' => array(),
			)
		);
	}

	public function resize( $url, $sizes ) {
		$this->create_url( $url, $sizes );
	}

	public function process( $file_path ) {
	}
}
