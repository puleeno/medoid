<?php
/**
 * Plugin Name: Medoid Cloud Media
 * Plugin URI: https://github.com/medoid/medoid
 * Author: Puleeno Nguyen
 * Author URI: https://puleeno.com
 * Version: 0.1.0
 * Description: The media on the cloud replacement for WordPress Media to cloud services: AWS S3, Imgur, Cloudinary, Google Cloud Storage and more
 * Tag: Media, Cloud, Storage
 */

define( 'MEDOID_PLUGIN_FILE', __FILE__ );

if ( ! class_exists( Medoid::class ) ) {
	$composerAutoload = sprintf('%s/vendor/autoload.php', dirname(MEDOID_PLUGIN_FILE));
	if (file_exists($composerAutoload)) {
		require_once $composerAutoload;
	}
}

if ( ! function_exists( 'medoid' ) ) {
	function medoid() {
		if (!class_exists(Medoid::class)) {
			error_log(__('Plugin medoid is not loaded', 'medoid'));
			return;
		}
		return Medoid::instance();
	}
}

$GLOBALS['medoid'] = medoid();
