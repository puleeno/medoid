<?php
interface Medoid_Cloud_Interface {
	public function get_name();

	public function get_id();

	public function upload( $file, $new_file );

	public function is_exists( $file );

	public function delete_image( $image, $delete_cloud_file = true, $delete_attachment = true, $soft_delete = false );

	public function make_unique_file_name( $file, $medoid_image );
}
