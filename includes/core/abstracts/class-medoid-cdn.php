<?php

abstract class Medoid_CDN implements Medoid_CDN_Interface {
	const TYPE_NAME = null;

	protected $image_url;
	protected $cloud;

	public function __construct( $image_url, $cloud, $is_original = true ) {
		$this->image_url = $image_url;
		$this->cloud     = $cloud;
	}

	public function is_support( $feature ) {
		$mapping = array( $this, $feature );
		return is_callable( $mapping );
	}
}
