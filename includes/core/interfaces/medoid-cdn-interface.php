<?php

interface Medoid_CDN_Interface {
	public function __toString();

	public function load_options( $options = array() );

	public function get_name();
}
