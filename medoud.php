<?php
/**
 * Plugin Name: Medoud
 * Plugin URI: https://puleeno.com
 * Author: Puleeno Nguyen
 * Author URI: https://puleeno.com
 * Version: 1.0.0
 * Description: Replacement WordPress media with Cloud Storage and CDN
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
