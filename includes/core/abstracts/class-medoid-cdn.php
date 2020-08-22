<?php

abstract class Medoid_CDN implements Medoid_CDN_Interface {
	const TYPE_NAME = null;

	protected $image_url;
	protected $cloud;
	protected $options;

	public function __construct( $image_url, $cloud, $is_original = true, &$options ) {
		$this->image_url = $image_url;
		$this->cloud     = $cloud;
		$this->options   = wp_parse_args(
			$options,
			array(
				'account_id' => '',
			)
		);
	}

	public function is_support( $feature ) {
		$mapping = array( $this, $feature );
		return is_callable( $mapping );
	}
}
