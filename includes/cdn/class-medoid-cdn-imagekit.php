<?php
class Medoid_Cdn_Imagekit {
	protected $processing = true;

	protected $support_url    = false;
	protected $support_proxy  = true;
	protected $support_crop   = true;
	protected $support_resize = true;

	protected $domain = 'https://imagekit.io/';

	public function resize( $url, $sizes ) {
	}
}
