<?php
namespace Medoid\Constracts;

interface CDNProcessing {
	const PROCESSING = true;

	public function resize( $width, $height = false );
}
