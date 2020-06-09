<?php

abstract class Medoid_CDN implements Medoid_CDN_Interface {
	const TYPE_NAME = null;

	protected $options;
	protected $processing = false;

	/**
	 * CDN features
	 */
	protected $support_url         = false;
	protected $support_proxy       = false;
	protected $support_crop        = false;
	protected $support_resize      = false;
	protected $support_filters     = false;
	protected $support_operattions = false;
	protected $support_watermark   = false;

	/**
	 * CDN specs
	 */
	protected $spec_url_format = 'query'; // Support `query` or `separator`

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

	protected function get_filters() {
	}

	public function generate_url() {
	}
}
