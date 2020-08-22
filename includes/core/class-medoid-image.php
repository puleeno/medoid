<?php
class Medoid_Image {
	protected $attachment_id;

	protected $image_url;
	protected $image_resize_url;
	protected $image_cdn_url;
	protected $image_proxy_url;

	protected $image_size_text;
	protected $image_size_array;

	public function __construct( $attachment_id, $image_url, $image_size = null ) {
		$this->attachment_id = $attachment_id;
		$this->image_url     = $image_url;

		// Convert image size to medoid sizes;
		$this->produce_image_size( $image_size );
	}

	protected function produce_image_size( $size ) {}

	public function to_string() {
	}

	// Create magic method to cast medoid image to string
	public function __toString() {
		return $this->to_string();
	}

	public function get_original_image_url() {
	}

	public function get_cdn_image_url() {
	}

	public function get_proxy_image_url() {
	}
}
