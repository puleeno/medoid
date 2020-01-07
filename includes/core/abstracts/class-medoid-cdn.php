<?php

abstract class Medoid_Cdn implements Medoid_Cdn_Interface {
	protected $options;

	protected $support_url = false;
	protected $processing  = false;

	protected $crop   = false;
	protected $resize = false;

	public function __construct( $options = array() ) {
		$this->options = $this->load_options( $options );
	}

	public function is_support( $feature ) {
		if ( ! $this->processing || empty( $this->$feature ) ) {
			return false;
		}

		return (bool) $this->$feature;
	}
}
