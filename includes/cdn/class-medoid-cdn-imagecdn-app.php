<?php

class Medoid_Cdn_Imagecdn_App extends Medoid_Cdn {
	protected $support_url = true;
	protected $processing  = true;

	protected $resize = true;

	protected $domain = 'https://imagecdn.app';

	/**
	 * ImageCDN.app don't have any options
	 */
	public function load_options( $options = array() ) {}

	protected function create_url( $url, $sizes = array() ) {
		$query = '';
		if ( ! empty( $sizes ) ) {
			$query .= '?' . http_build_query( $sizes );
		}

		return sprintf( '%s/v2/image/%s%s', $this->domain, urlencode( $url ), $query );
	}

	public function resize( $url, $size ) {
		return $this->create_url( $url, $size );
	}

	public function process( $url ) {
		return $this->create_url( $url );
	}
}
