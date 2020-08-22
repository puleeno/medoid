<?php
class Medoid_Core_Syncer {
	protected $upload_events = array();
	protected $sync_events   = array();

	public function includes() {
		require_once MEDOID_ABSPATH . '/includes/core/class-medoid-response.php';

		$cloud_storage = new Medoid_Cloud_Storages();
		$cloud_storage->init();
		$cloud_storage->setup_clouds();
	}

	public function schedules( $schedules ) {
		$medoid_schedules = include MEDOID_ABSPATH . '/configs/schedules.php';

		return array_merge(
			$schedules,
			$medoid_schedules
		);
	}

	public function syncer( $args ) {
		if ( empty( $args['cloud_id'] ) ) {
			return;
		}

		try {
			$cloud = Medoid_Cloud_Storages::get_clouds( $args['cloud_id'] );
			$cloud->sync_to_cloud( $args['limit_items'] );
		} catch ( Exception $e ) {
			Logger::get( 'medoid' )->error( $e->getMessage(), $this );
		}
	}

	public function setup_cron() {
		$clouds = Medoid_Cloud_Storages::get_clouds();
		foreach ( $clouds as $cloud_id => $cloud ) {
			if ( empty( $cloud::CLOUD_TYPE ) ) {
				continue;
			}
			$cloud_schedule = '3minutes';
			$limit_items    = 20;
			$cron_key       = sprintf( '%s_id%s_hook', $cloud::CLOUD_TYPE, $cloud_id );

			add_action( $cron_key, array( $this, 'syncer' ) );

			$this->upload_events[ $cron_key ] = array(
				'cloud_id'    => $cloud_id,
				'limit_items' => $limit_items,
				'schedule'    => $cloud_schedule,
			);

			$is_local2cloud = true;
			if ( $is_local2cloud ) {
				$sync_key                       = sprintf( '%s_id%d_to_cloud', $cloud::CLOUD_TYPE, $cloud_id );
				$this->sync_events[ $sync_key ] = array(
					'cloud_id' => $cloud_id,
				);
			}
		}
	}

	public function run_cron() {
		foreach ( $this->upload_events as $cron_hook => $cloud_event ) {
			$args = array( $cloud_event );
			if ( is_medoid_debug() ) {
				do_action( $cron_hook );
				continue;
			}

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
