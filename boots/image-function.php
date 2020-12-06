<?php

class Medoid_Boots_Image_Function {
	protected $db;

	public function get_image_size_from_alias( $alias, $size ) {
		$image_size = medoid_get_image_sizes( $size );
		if ( ! is_array( $image_size ) ) {
			return;
		}
		$db_image = $this->db->get_image_size_by_alias( $alias, array( $image_size['width'], $image_size['height'] ) );
		if ( $db_image ) {
			return new Medoid_Image( $db_image->post_id, $db_image, explode( 'x', $size ) );
		}
		$db_image = $this->db->get_image_by_alias( $alias );
		if ( $db_image ) {
			return new Medoid_Image( $db_image->post_id, $db_image, $image_size, true );
		}
	}
}
