<?php

class Medoid_Cloud_Storages {
	protected static $clouds = [];

	protected $query;

	public static function getClouds( $id = null ) {
		if ( is_null( $id ) ) {
			return self::$clouds;
		}
		if ( isset( self::$clouds[ $id ] ) ) {
			return self::$clouds[ $id ];
		}
		return false;
	}

	public function __construct() {
		$this->setup_clouds();
	}

	public static function getDefaultCloud() {
		return self::getClouds( 1 );
	}

	public function setup_clouds() {
		$options = array();
		$cloud   = new Medoid_Cloud_Backblaze( 1 );
		if ( $cloud instanceof Medoid_Cloud ) {
			self::$clouds[1] = $cloud;
		}
	}
}

new Medoid_Cloud_Storages();
