<?php

abstract class Medoid_Cdn implements Medoid_Cdn_Interface {
	protected $options;
	protected $processing = false;

	protected $support_url    = false;
	protected $support_proxy  = false;
	protected $support_crop   = false;
	protected $support_resize = false;

	public function __construct( $options = array() ) {
		$this->options = $this->load_options( $options );
	}

	public function is_support( $feature ) {
		$feature = sprintf( 'support_%s', $feature );
		if ( ! $this->processing || empty( $this->$feature ) ) {
			return false;
		}

		return (bool) $this->$feature;
	}
}
