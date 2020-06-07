<?php

interface Medoid_CDN_Interface {
	public function load_options( $options = array() );

	public function process( $file_path );
}
