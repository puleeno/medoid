<?php
class Medoid_Image {
	protected $attachment_id;
	protected $image_id;
	protected $medoid_image;
	protected $is_resize            = false;
	protected $create_proxy_content = false;

	protected $alias;
	protected $image_url;
	protected $image_resize_url;
	protected $image_cdn_url;
	protected $image_proxy_url;

	protected $image_size_array;

	protected $cdn_provider;
	protected $cdn_options = array();

	protected $flag_cdn_url_is_generated   = false;
	protected $flag_proxy_url_is_generated = false;

	public function __construct( $attachment_id, $medoid_image, $image_size = null, $is_resize = false ) {
		$this->attachment_id = $attachment_id;

		if ( is_object( $medoid_image ) ) {
			$this->image_url = $medoid_image->image_url;
			$this->image_id  = isset($medoid_image->image_id) ? $medoid_image->image_id : $medoid_image->ID;
			$this->alias     = $medoid_image->alias;

			$this->medoid_image = $medoid_image;
		} else {
			$this->image_url = $medoid_image;
		}

		$this->image_size_array = is_array( $image_size ) ? $image_size : false;
		$this->is_resize        = $is_resize;
	}

	public function create_proxy_image_content() {
		$this->create_proxy_content = true;
	}

	protected function check_cdn_activate() {
		$cdn_options = get_option( 'medoid_cdn', false );
		if ( ! $cdn_options || ! is_array( $cdn_options ) ) {
			return false;
		}
		$is_active = array_get( $cdn_options, 'is_active', false );

		$this->active_cdn_provider = array_get( $cdn_options, 'cdn_provider' );
		$this->cdn_options         = (array) array_get( $cdn_options, 'options', array() );

		return apply_filters( 'medoid_cdn_active_status', $is_active );
	}

	protected function check_medoid_proxy_is_active() {
		$is_active = true;
		if ( $this->create_proxy_content ) {
			return false;
		}
		return apply_filters( 'medoid_enable_proxy_url', $is_active );
	}

	public function to_string() {
		$cdn_active_status = $this->check_cdn_activate();

		if ( $this->check_medoid_proxy_is_active() ) {
			$this->image_proxy_url = $this->get_proxy_image_url();
			return $this->image_proxy_url;
		}
		if ( $cdn_active_status ) {
			return $this->get_cdn_image_url();
		}

		if ( $this->image_resize_url ) {
			return $this->image_resize_url;
		}
		return $this->image_url;
	}

	// Create magic method to cast medoid image to string
	public function __toString() {
		$ret = $this->to_string();
		if ( is_null( $ret ) ) {
			return '';
		}

		$image_size_str = implode(
			'x',
			array(
				array_get( $this->image_size_array, 'width', 0 ),
				array_get( $this->image_size_array, 'height', 0 ),
			)
		);

		if ( $this->is_resize ) {
			$db            = Medoid_Core_Db::instance();
			$cdn_image_url = $this->get_cdn_image_url();

			$db->insert_image_size(
				$this->image_id,
				$image_size_str,
				sprintf( '%s/%s', $image_size_str, $this->alias ),
				$cdn_image_url != '' ? null : $this->image_url,
				$cdn_image_url,
				$this->get_proxy_image_url()
			);
		}
		return $ret;
	}

	public function get_cdn_image_url() {
		if ( $this->flag_cdn_url_is_generated ) {
			return $this->image_cdn_url;
		}

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

		$this->flag_cdn_url_is_generated = true;
		$this->image_cdn_url             = (string) $cdn_image;

		return $this->image_cdn_url;
	}

	public function get_proxy_image_url() {
		if ( $this->flag_proxy_url_is_generated ) {
			return $this->image_proxy_url;
		}

		$this->flag_proxy_url_is_generated = true;

		if ( ! $this->is_resize || ! $this->image_size_array ) {
			$this->image_proxy_url = site_url( 'images/' . $this->medoid_image->alias );
		} else {
			$this->image_proxy_url = site_url(
				sprintf(
					'images/%sx%s/%s',
					$this->image_size_array['width'],
					$this->image_size_array['height'],
					$this->medoid_image->alias
				)
			);
		}

		return apply_filters( 'medoid_proxy_image_url', $this->image_proxy_url );
	}
}
