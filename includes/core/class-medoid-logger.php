<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

final class Medoid_Logger {
	private static $instance;
	protected static $callbacks = array( 'debug', 'info', 'warning', 'error', 'critical', 'alert', 'emergency' );

	private $log;

	private static function instance() {
		if ( ! defined( 'WP_DEBUG_LOG' ) || empty( WP_DEBUG_LOG ) ) {
			return;
		}

		if ( is_null( self::$instance ) && class_exists( Logger::class ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct( $options = array() ) {
		$this->log = new Logger( 'Medoid' );
		$this->log->pushHandler( new StreamHandler( WP_CONTENT_DIR . '/medoid/logs/debug.log' ) );

		add_action( 'init', array( $this, 'setup_logger' ) );
	}

	public function setup_logger() {
		$this->log->pushHandler( new StreamHandler( WP_CONTENT_DIR . '/medoid/logs/errors.log', Logger::WARNING ) );

		do_action( 'medoid_setup_logger', $this->log );
	}

	public function getLogger() {
		return $this->log;
	}

	public static function __callStatic( $name, $args ) {
		$medoid_logger = self::instance();
		if ( is_null( $medoid_logger ) || ! in_array( $name, self::$callbacks ) ) {
			return;
		}

		return call_user_func_array(
			array(
				$medoid_logger->getLogger(),
				$name,
			),
			$args
		);
	}
}
