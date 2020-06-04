<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

final class Medoid_Logger {
	private static $instances;
	protected static $callbacks = array( 'log', 'debug', 'info', 'warning', 'error', 'critical', 'alert', 'emergency' );

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

		$this->setup_logger( $name );
	}

	public function setup_logger() {
		$this->log->pushHandler( new StreamHandler( WP_CONTENT_DIR . '/medoid/logs/errors.log', Logger::WARNING ) );

		do_action( 'medoid_setup_logger', $this->log, $this->getLogger(), $name );
	}

	public function getLogger() {
		return $this->log;
	}

	public static function __callStatic( $name, $args ) {
		$args = $args + array( '', [], false, 'medoid' );
		list($message, $data, $cron_mode, $logger_name) = $args;

		$medoid_logger = self::instance( $logger_name );
		if ( is_null( $medoid_logger ) || ! in_array( $name, self::$callbacks ) ) {
			return;
		}

		/**
		 * Dont write log when cron mode is enable but run via frontend page
		 */
		if ( $cron_mode == true && ( ! defined( 'DOING_CRON' ) || false === DOING_CRON ) ) {
			return;
		}

		return call_user_func(
			array(
				$medoid_logger->getLogger(),
				$name,
			),
			$message,
			$data
		);
	}
}
