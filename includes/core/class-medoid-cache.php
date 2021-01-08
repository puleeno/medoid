<?php
class Medoid_Cache {
	protected static $cached_images = array();

	public static function set_image_cache( $image_id, $size, &$url ) {
		$cache_key = Medoid_Image::create_instance_key( $size );
		if ( ! isset( static::$cached_images[ $image_id ] ) ) {
			static::$cached_images[ $image_id ] = array();
		}
		static::$cached_images[ $image_id ][ $cache_key ] = $url;
	}

	public static function get_image_cache( $image_id, $size ) {
		$cache_key = Medoid_Image::create_instance_key( $size );
		if ( isset( static::$cached_images[ $image_id ][ $cache_key ] ) ) {
			return static::$cached_images[ $image_id ][ $cache_key ];
		}
		return false;
	}
}
