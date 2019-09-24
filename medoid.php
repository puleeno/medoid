<?php
/**
 * Plugin Name: Medoid
 * Plugin URI: https://github.com/medoid/medoid
 * Author: Puleeno Nguyen
 * Author URI: https://puleeno.com
 * Version: 0.1.0
 * Description: The replacement for WordPress Media to cloud services: AWS S3, Imgur, Cloudinary, Google Cloud Storage and more
 * Tag: Media, Cloud, Storage
 */

define( 'MEDOID_PLUGIN_FILE', __FILE__ );

if (!class_exists('Medoid')) {
    require_once dirname(__FILE__) . '/includes/class-medoid.php';
}

if (function_exists('medoid')) {
    function medoid() {
        return Medoid::instance();
    }
}

$GLOBALS['medoid'] = medoid();
