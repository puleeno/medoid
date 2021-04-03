<?php
namespace Medoid\Abstracts;

use Medoid\Constracts\CDN as CDNInterface;

abstract class CDN implements CDNInterface
{
    const TYPE_NAME = null;

    protected $image_url;
    protected $cloud;
    protected $options;
    protected $is_original_image;

    public function __construct($image_url, $cloud, $is_original = true, &$options)
    {
        $this->image_url         = $image_url;
        $this->cloud             = $cloud;
        $this->is_original_image = $is_original;

        $this->options = wp_parse_args(
            $options,
            array(
                'account_id' => '',
            )
        );
    }

    public function __toString()
    {
        $cloudimage_url = $this->get_url();
        $query_args     = array();

        if (! $this->is_original_image && ! empty($this->sizes)) {
            if ($this->sizes['width'] > 0) {
                $query_args['w'] = $this->sizes['width'];
            }
            if ($this->sizes['height'] > 0) {
                $query_args['h'] = $this->sizes['height'];
            }
        }

        $ret = $cloudimage_url . $this->image_url;
        if (! empty($query_args)) {
            $ret .= '?' . http_build_query($query_args);
        }

        return apply_filters(
            strtolower("medoid_cdn_{$this->get_name()}_image_url_output"),
            $ret,
            $this
        );
    }

    public function is_support($feature)
    {
        $mapping = array( $this, $feature );
        return is_callable($mapping);
    }

    public function support_http() {
        return true;
    }

    public function convert_image_url_https_to_http()
    {
        // default do not run any actions
    }
}
