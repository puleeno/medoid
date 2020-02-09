<?php
class Medoid_Syncer {
	protected $cloud_events = array();

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'includes' ), 30 );
		add_filter( 'cron_schedules', array( $this, 'schedules' ) );
		add_action( 'init', array( $this, 'setup_cron' ), 20 );
		add_action( 'init', array( $this, 'run_cron' ), 30 );
	}

	public function includes() {
		require_once dirname( __FILE__ ) . '/class-medoid-cloud-storages.php';
	}

	public function schedules( $schedules ) {
		$schedules['1_minute']   = array(
			'interval' => 60,
			'display'  => esc_html__( __( 'Every Minute', 'medoid' ) ),
		);
		$schedules['2_minutes']  = array(
			'interval' => 2 * 60,
			'display'  => esc_html__( __( 'Every Two Minutes', 'medoid' ) ),
		);
		$schedules['3_minutes']  = array(
			'interval' => 3 * 60,
			'display'  => esc_html__( __( 'Every Three Minutes', 'medoid' ) ),
		);
		$schedules['5_minutes']  = array(
			'interval' => 5 * 60,
			'display'  => esc_html__( __( 'Every Five Minutes', 'medoid' ) ),
		);
		$schedules['10_minutes'] = array(
			'interval' => 10 * 60,
			'display'  => esc_html__( __( 'Every Ten Minutes', 'medoid' ) ),
		);
		$schedules['15_minutes'] = array(
			'interval' => 15 * 60,
			'display'  => esc_html__( __( 'Every Fiveteen Minutes', 'medoid' ) ),
		);
		$schedules['20_minutes'] = array(
			'interval' => 20 * 60,
			'display'  => esc_html__( __( 'Every Twenty Minutes', 'medoid' ) ),
		);
		$schedules['30_minutes'] = array(
			'interval' => 30 * 60,
			'display'  => esc_html__( __( 'Every Thirdty Minutes', 'medoid' ) ),
		);

		return $schedules;
	}

	public function syncer( $args ) {
		var_dump( $args );
	}

	public function setup_cron() {
		$clouds = Medoid_Cloud_Storages::get_clouds();
		foreach ( $clouds as $cloud_id => $cloud ) {
			if ( empty( $cloud::CLOUD_TYPE ) ) {
				continue;
			}
			$cloud_schedule = '3_minutes';
			$limit_items    = 50;
			$cron_key       = sprintf( '%s_id%s_hook', $cloud::CLOUD_TYPE, $cloud_id );

			add_action( $cron_key, array( $this, 'syncer' ) );

			$this->cloud_events[ $cron_key ] = array(
				'cloud_id'    => $cloud_id,
				'limit_items' => $limit_items,
				'schedule'    => $cloud_schedule,
			);
		}
	}

	public function run_cron() {
		foreach ( $this->cloud_events as $cron_hook => $cloud_event ) {
			$args = array( $cloud_event );
			if ( ! wp_next_scheduled( $cron_hook, $args ) ) {
				wp_schedule_event(
					time(),
					$cloud_event['schedule'],
					$cron_hook,
					$args
				);
			}
		}
	}
}

new Medoid_Syncer();
