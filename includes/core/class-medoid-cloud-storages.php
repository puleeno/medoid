<?php

class Medoid_Cloud_Storages {
	protected static $clouds = [];
	protected $query;
	protected $db;

	public function __construct() {
		$this->includes();
		$this->init();
		$this->setup_clouds();
	}

	public function includes() {
		$clouds = glob( MEDOID_CLOUDS_DIR . '/class-medoid-cloud*.php' );
		foreach ( $clouds as $cloud ) {
			require_once $cloud;
		}
	}

	public function init() {
		$this->db = Medoid_Core_Db::instance();
	}

	public function setup_clouds() {
		$options   = array();
		$providers = apply_filters(
			'medoid_cloud_providers',
			array(
				Medoid_Cloud_Backblaze::CLOUD_TYPE => Medoid_Cloud_Backblaze::class,
			)
		);

		$db_clouds = $this->db->get_clouds();
		foreach ( $db_clouds as $db_cloud ) {
			if ( ! isset( $db_cloud->cloud_type, $providers[ $db_cloud->cloud_type ] ) ) {
				continue;
			}
			$provider = $providers[ $db_cloud->cloud_type ];
			if ( class_exists( $provider ) ) {
				$cloud = new $provider( $db_cloud->ID, $db_cloud );
				if ( $cloud instanceof Medoid_Cloud ) {
					self::$clouds[ $db_cloud->ID ] = $cloud;
				}
			}
		}
	}

	public static function get_default_cloud() {
		return self::get_clouds( 1 );
	}

	public static function get_clouds( $id = null ) {
		if ( is_null( $id ) ) {
			return self::$clouds;
		}
		if ( isset( self::$clouds[ $id ] ) ) {
			return self::$clouds[ $id ];
		}
		return false;
	}

	public static function get_active_clouds() {
		return array(
			1 => 'Backblaze',
		);
	}
}

new Medoid_Cloud_Storages();
