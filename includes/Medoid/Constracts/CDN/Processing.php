<?php
namespace Medoid\Constracts\CDN;

interface Processing {
	const PROCESSING = true;

	public function resize( $width, $height = false );
}
