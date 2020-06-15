<?php
interface Medoid_CDN_Processing {
	const PROCESSING = true;

	public function resize( $width, $height = false );
}
