<?php
class Medoid_Cdn_CloudImage {
	protected $processing = true;

	protected $support_url    = false;
	protected $support_proxy  = true;
	protected $support_crop   = true;
	protected $support_resize = true;

	protected $domain = 'https://cloudimage.io/';

	public function resize( $url, $sizes ) {
	}
}
