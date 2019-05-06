<?php
/**
 * Plugin Name: Medoud
 * Plugin URI: https://puleeno.com
 * Author: Puleeno Nguyen
 * Author URI: https://puleeno.com
 * Version: 1.0.0
 * Description: The replacement for WordPress Media to cloud services: AWS S3, Cloudinary, Google Cloud Storage, etc
 * Tags: media, clouds, cdn, multi-cloud, synchonize
 *
 * @package Medoud
 */

define( 'MEDOUD_PLUGIN_FILE', __FILE__ );

if ( ! class_exists( 'Medoud' ) ) {
	require_once dirname( MEDOUD_PLUGIN_FILE ) . '/includes/class-medoud.php';
}
if ( ! function_exists( 'medoud' ) ) {
	/**
	 * Get the medoud singleton instance.
	 */
	function medoud() {
		return Medoud::instance();
	}
}


$GLOBALS['medoud'] = medoud();
