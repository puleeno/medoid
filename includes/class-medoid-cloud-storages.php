<?php

class Medoid_Cloud_Storages {
	protected static $clouds = [];
	protected $query;

	public function __construct() {
		$this->setup_clouds();
	}

	public function setup_clouds() {
		$options = array();
		$cloud   = new Medoid_Cloud_Backblaze( 1 );
		if ( $cloud instanceof Medoid_Cloud ) {
			self::$clouds[1] = $cloud;
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
