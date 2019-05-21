<?php
/**
 * Plugin Name: Medoid
 * Plugin URI: https://puleeno.com
 * Author: Puleeno Nguyen
 * Author URI: https://puleeno.com
 * Version: 1.0.0
 * Description: The replacement for WordPress Media to cloud services: AWS S3, Cloudinary, Google Cloud Storage, etc
 * Tags: media, clouds, cdn, multi-cloud, synchonize
 *
 * @package Medoid
 */

define( 'MEDOUD_PLUGIN_FILE', __FILE__ );

if ( ! class_exists( 'Medoid' ) ) {
	require_once dirname( MEDOUD_PLUGIN_FILE ) . '/includes/class-medoid.php';
}
if ( ! function_exists( 'medoid' ) ) {
	/**
	 * Get the medoid singleton instance.
	 */
	function medoid() {
		return Medoid::instance();
	}
}


$GLOBALS['medoid'] = medoid();
