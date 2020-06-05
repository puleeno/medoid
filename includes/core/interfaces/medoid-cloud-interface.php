<?php
interface Medoid_Cloud_Interface {
	public function get_name();

	public function get_id();

	public function upload( $file, $new_file );

	public function is_exists( $file);

	public function delete_file( $file_id);
}
