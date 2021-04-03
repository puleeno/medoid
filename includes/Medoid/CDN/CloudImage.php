<?php
namespace Medoid\CDN;

use Medoid\Abstracts\CDN;
use Medoid\Constracts\CDN\Processing;

class CloudImage extends CDN implements Processing
{
    const TYPE_NAME = 'cloudimage';
    const VERSION   = 'v7';

    protected $cloudimage_output;

    protected $image_url;
    protected $cloud;
    protected $is_original_image;
    protected $scheme = 'https';

    protected $sizes = array();

    public function get_name()
    {
        return 'Cloudimage';
    }

    public function get_url()
    {
        return sprintf(
            '%s://%s.cloudimg.io/%s/',
            $this->scheme,
            $this->options['account_id'],
            self::VERSION
        );
    }

    public function resize($width, $height = false)
    {
        $this->sizes = array(
            'width'  => $width,
            'height' => $height,
        );
    }

    public function support_http() {
        return true;
    }

    public function convert_image_url_https_to_http()
    {
        $this->scheme = 'http';
    }
}
