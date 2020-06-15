<?php

class Medoid_Core_Manager {
	protected static $instance;

	protected $cloud_providers = array();
	protected $cdns;

	protected $default_cloud;
	protected $active_cloud;
	protected $active_cdn;

	protected $cdn_options;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->cdns = array(
			Medoid_CDN_CloudImage::TYPE_NAME . '_1' => array(
				'class_name' => Medoid_CDN_CloudImage::class,
			),
		);
		$this->active_cdn = Medoid_CDN_CloudImage::TYPE_NAME . '_1';
	}

	/**
	 * Get all cloud storage providers are supported via Medoid
	 *
	 * @return array
	 */
	public function get_cloud_providers( $refresh = false ) {
		if ( empty( $this->cloud_providers ) || $refresh ) {
			$default_clouds = array(
				Medoid_Cloud_Backblaze::CLOUD_TYPE => Medoid_Cloud_Backblaze::class,
			);

			$this->cloud_providers = apply_filters(
				'medoid_cloud_storage_providers',
				$this->cloud_providers + $default_cloud
			);
		}

		return $this->cloud_providers;
	}

	/**
	 * Get the cloud instances are created in your website
	 *
	 * @return array
	 */
	public function get_cloud_instances() {

	}

	/**
	 * Get default Cloud storage instance of your website
	 *
	 * @return Medoid_Cloud
	 */
	public function get_default_cloud() {
	}

	public function get_active_cloud() {
		return new Medoid_Cloud_Backblaze( 1 );
	}

	public function get_all_cdn() {

	}



	public function get_cdn($key_name = null) {
		if (is_null($key_name)) {
			$key_name = $this->active_cdn;
		}
		$cdn_infos = $this->cdns[$key_name];

		return apply_filters("medoid_cdn_{$key_name}_options", $cdn_infos);
	}
}
