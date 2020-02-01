<?php

class Medoid_Cdn_Gumlet extends Medoid_Cdn {
	protected $support_url = false;
	protected $processing  = true;
	protected $resize      = true;
	protected $api_fields_maps;

	public function load_options( $options = array() ) {
		$this->api_fields_maps = apply_filters(
			'medoid_cdn_gumlet_maping_fields',
			array(
				'height' => 'h',
				'width'  => 'w',
			)
		);
	}

	protected function create_url( $url, $sizes = array() ) {
		$query     = '';
		$api_query = $this->convert_api_query( $sizes );

		if ( ! empty( $api_query ) ) {
			$query .= '?' . http_build_query( $api_query );
		}

		$image_url = str_replace(
			array(
				'f000.backblazeb2.com',
				'dev.loveofboys.com',
				'loveofboys.com',
			),
			array(
				MEDOID_GUMLET_DOMAIN,
				'loveofboys.gumlet.com',
				'loveofboys.gumlet.com',
			),
			$url
		);
		return sprintf( '%s%s', $image_url, $query );
	}

	public function resize( $url, $sizes ) {
		$sizes = wp_parse_args(
			$sizes,
			array(
				'mode' => 'crop',
			)
		);
		return $this->create_url( $url, $sizes );
	}

	public function process( $url ) {
		return $this->create_url( $url );
	}

	public function convert_api_query( $fields ) {
		$convert_fields = array();
		foreach ( $fields as $field => $value ) {
			if ( isset( $this->api_fields_maps[ $field ] ) ) {
				$field_name                    = $this->api_fields_maps[ $field ];
				$convert_fields[ $field_name ] = $value;
			} else {
				$convert_fields[ $field ] = $value;
			}
		}

		if ( true === $convert_fields['crop'] ) {
			$convert_fields['crop'] = 'entropy';
		}

		return $convert_fields;
	}
}
