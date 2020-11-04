<?php
class Medoid_Image {
	protected $attachment_id;
	protected $medoid_image;
	protected $is_resize = false;

	protected $image_url;
	protected $image_resize_url;
	protected $image_cdn_url;
	protected $image_proxy_url;

	protected $image_size_array;

	protected $cdn_provider;
	protected $cdn_options = array();

	public function __construct( $attachment_id, $medoid_image, $image_size = null, $is_resize = false ) {
		$this->attachment_id = $attachment_id;

		if ( is_object( $medoid_image ) ) {
			$this->image_url    = $medoid_image->image_url;
			$this->medoid_image = $medoid_image;
		} else {
			$this->image_url = $medoid_image;
		}

		$this->image_size_array = is_array( $image_size ) ? $image_size : false;
	}

	protected function check_cdn_activate() {
		$cdn_options = get_option( 'medoid_cdn', false );
		if ( ! $cdn_options || ! is_array( $cdn_options ) ) {
			return false;
		}
		$is_active = array_get( $cdn_options, 'is_active', false );

		$this->active_cdn_provider = array_get( $cdn_options, 'cdn_provider' );
		$this->cdn_options         = (array) array_get( $cdn_options, 'options', array() );

		return $is_active;
	}

	protected function check_medoid_proxy_is_active() {
		return true;
	}

	public function to_string() {
		if ( ! $this->check_cdn_activate() ) {
			return $this->image_url;
		}
		if ( $this->check_medoid_proxy_is_active() ) {
			return $this->get_proxy_image_url();
		}

		$this->image_cdn_url = (string) $this->get_cdn_image_url();
		return $this->image_cdn_url;
	}

	// Create magic method to cast medoid image to string
	public function __toString() {
		return $this->to_string();
	}

	public function get_cdn_image_url() {
		$cdn_provider = Medoid_Core_Manager::get_instance()->get_cdn( $this->cdn_provider );
		if ( ! $cdn_provider || ! ( $cdn_class = array_get( $cdn_provider, 'class_name' ) ) ) {
			return $this->image_url;
		}

		$cdn_image = new $cdn_class(
			$this->image_url,
			isset( $this->medoid_image ) ? $this->medoid_image->cloud_id : null,
			! $this->is_resize,
			$this->cdn_options,
		);

		if ( $this->image_size_array ) {
			$cdn_image->resize(
				array_get( $this->image_size_array, 'width' ),
				array_get( $this->image_size_array, 'height', false )
			);
		}

		return $cdn_image;
	}

	public function get_proxy_image_url() {
	}

	public static function getImageFromAlias( $alias ) {
	}
}
