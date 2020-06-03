<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

final class Medoid_Logger {
	private static $instances;
	protected static $callbacks = array( 'debug', 'info', 'warning', 'error', 'critical', 'alert', 'emergency' );

	private $log;

	private static function instance( $name = 'medoid' ) {
		if ( ! defined( 'WP_DEBUG_LOG' ) || empty( WP_DEBUG_LOG ) ) {
			return;
		}

		if ( is_null( self::$instances[ $name ] ) && class_exists( Logger::class ) ) {

			self::$instances[ $name ] = new self( $name );
		}

		return self::$instances[ $name ];
	}

	private function __construct( $name, $options = array() ) {

		$this->log = new Logger( strtoupper( $name ) );

		if ( $name === 'medoid' ) {
			$this->log->pushHandler( new StreamHandler( WP_CONTENT_DIR . '/medoid/logs/debug.log' ) );
		} else {
			$this->log->pushHandler( new StreamHandler( WP_CONTENT_DIR . '/medoid/logs/' . ltrim( $name, 'medoid_' ) . '.log' ) );
		}

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
		$logger_name = 'medoid';
		if ( ! empty( $args[2] ) ) {
			$logger_name .= '_' . $args[2];
		}
		$medoid_logger = self::instance( $logger_name );

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
