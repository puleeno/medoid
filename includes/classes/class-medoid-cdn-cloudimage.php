<?php
class Medoid_CDN_CloudImage extends Medoid_CDN implements Medoid_CDN_Processing {
	const TYPE_NAME = 'cloudimage';

	public function __toString() {
		return $this->image_url;
	}

	public function get_name() {
		return 'Cloudimage';
	}
}
