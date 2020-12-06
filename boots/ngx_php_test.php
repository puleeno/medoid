<?php
global $is_exit;
if ( !defined( 'NGX_HTTP_NOT_FOUND' ) ) {
	define( 'NGX_HTTP_NOT_FOUND', 'NGX_HTTP_NOT_FOUND' );
}

if (!function_exists('ngx_request_uri')) {
	function ngx_request_uri() {
		if (isset($_SERVER['argv'])) {
			array_shift($_SERVER['argv']);
			if (!empty($_SERVER['argv'])) {
				return $_SERVER['argv'][0];
			}
		}
		return '/image/test';
	}
}

if ( ! function_exists( 'ngx_request_document_root' ) ) {
	function ngx_request_document_root() {
		return realpath( dirname(__FILE__) . '/../../../..'  );
	}
}

if ( ! function_exists( 'ngx_exit' ) ) {
	function ngx_exit( $status = true ) {
		global $is_exit;

		$is_exit = $status;
	}
}

if ( ! class_exists( 'ngx_var' ) ) {
	class ngx_var {
		public static $vars = array();
		public static function set( $name, $value ) {
			static::$vars[ $name ] = $value;
		}
	}
}

require_once dirname( __FILE__ ) . '/ngx_php.php';

$ngx_php = new Medoid_Ngx_PHP();
$ngx_php->load_image_url_from_database();
$ngx_php->set_image_url();

var_dump( 'Is exit:' . $is_exit );
var_dump( 'Variables:', var_export( ngx_var::$vars, true ) );
