<?php
interface Medoid_Cloud_Interface {
	public function __construct( $id, $configs = array() );

	public function get_id();

	public function upload( $file, $new_file, $post = null, $type = null);
}
