<?php
class Medoid_Syncer {
    public function __construct() {
        $this->init_hooks();
    }

    public function init_hooks() {
        add_action( 'init', array($this, 'load_cloud_storages'), 25 );
    }

    public function load_cloud_storages() {
    }
}

new Medoid_Syncer();
