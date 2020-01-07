<?php

interface Medoid_Cdn_Interface {
	public function load_options( $options = array());

	public function process( $file_path);
}
