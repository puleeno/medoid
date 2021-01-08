<?php
class Medoid_Cache {
	protected static $cached_images = array();

	public static function set_image_cache($image_id, $size, &$url) {
		if (empty($size)) {
			$size = 'full';
		}
		if (!isset(static::$cached_images[$image_id])) {
			static::$cached_images[$image_id] = array();
		}
		static::$cached_images[$image_id][$size] = $url;
	}

	public static function get_image_cache($image_id, $size) {
		if (isset(static::$cached_images[$image_id][$size])) {
			return static::$cached_images[$image_id][$size];
		}
		return false;
	}
}
