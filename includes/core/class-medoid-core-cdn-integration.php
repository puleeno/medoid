<?php

class Medoid_Core_Cdn_Integration {
	protected static $instance;

	protected $real_url;
	protected $url;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
	}

	public function __toString() {
		if ( is_string( $this->url ) ) {
			return $this->url;
		} elseif ( $this->real_url ) {
			return $this->real_url;
		}

		return __return_empty_string();
	}

	public function isEnabled() {
	}

	protected function process() {
		$this->url = $this->real_url;
	}

	public function delivery( $url ) {
		$this->real_url = $url;
		$this->process();

		return self;
	}
}
