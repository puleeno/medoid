<?php
namespace Medoid\Core;

use Medoid\Cache;
use Medoid\Image;
use Medoid\DB;
use Medoid\Core\Manager;

/**
 * This is the main gate to public image and delivery to your users.
 */
class ImageDelivery
{
    protected $db;
    protected $cdn;
    protected $need_downsize = false;

    public function __construct()
    {
        $this->db      = DB::instance();
        $this->manager = Manager::get_instance();
    }

    public function init_hooks()
    {
        add_action('image_downsize', array( $this, 'image_downsize' ), 10, 3);
        add_filter('wp_get_attachment_image_src', array( $this, 'get_image_src' ), 99, 3);
        add_action('wp_prepare_attachment_for_js', array( $this, 'prepare_json' ), 10, 3);
    }

    public function prepare_json($response, $attachment, $meta)
    {
        $thumbnail = wp_get_attachment_image_src($attachment->ID, array( 150, 150 ));
        $full_url  = wp_get_attachment_image_src($attachment->ID, 'full');

        if (isset($full_url[0]) && is_a($full_url[0], Image::class)) {
            $response['url'] = (string) $full_url[0]->to_string();
        }

        if ($thumbnail) {
            $thumbnail_url    = (string) $thumbnail[0];
            $response['icon'] = $thumbnail_url;

            // Override WordPress media JSON thumbnails
            $response['sizes']['thumbnail'] = array(
                'url'         => $thumbnail_url,
                'width'       => $thumbnail[1],
                'height'      => $thumbnail[2],
                'orientation' => 'landscape',
            );
        }

        return $response;
    }

    public function image_downsize($downsize, $id, $size)
    {
        // Reset the need_downsize flag
        $this->need_downsize = false;

        $numeric_size = medoid_get_image_sizes($size);
        $active_cloud = $this->manager->get_active_cloud();

        if (empty($downsize)) {
            $medoid_image = $this->db->get_image_size_by_attachment_id($id, $size, $active_cloud->get_id());
            if ($medoid_image) {
                $downsize[0] = Image::create_image($id, $medoid_image, $numeric_size);
                $downsize[0]->set_resized(true);
            } else {
                $this->need_downsize = ! ( is_string($size) && in_array($size, array( null, 'full' )) );
            }
        }

        return $downsize;
    }

    public function get_image_src($image, $attachment_id, $size)
    {
        if (false === array_get($image, 3, false)) {
            $cached_image_url = Cache::get_image_cache($attachment_id, $size);
            $numeric_size     = medoid_get_image_sizes($size);

            if (! $cached_image_url) {
                $active_cloud    = $this->manager->get_active_cloud();
                $medoid_db_image = $this->db->get_image_by_attachment_id(
                    $attachment_id,
                    $active_cloud->get_id()
                );

                $medoid_image = false;
                if ($medoid_db_image) {
                    $medoid_image = Image::create_image($attachment_id, $medoid_db_image, $numeric_size, $this->need_downsize);
                } else {
                    $attachment = get_post($attachment_id);
                    if ($attachment) {
                        $medoid_image = Image::create_image($attachment_id, $attachment->guid, $numeric_size, $this->need_downsize);
                    }
                }
                if ($medoid_image) {
                    $image[0] = $medoid_image;
                    Cache::set_image_cache($attachment_id, $size, $medoid_image);
                }
            } else {
                $image[0] = $cached_image_url;
            }

            // Override image sizes
            if (isset($image[0])) {
                if (is_array($numeric_size)) {
                    $image[1] = $numeric_size['width'];
                    $image[2] = $numeric_size['height'];
                } elseif (! isset($image[1], $image[2])) {
                    $image[1] = 0;
                    $image[2] = 0;
                }
            }
        }

        return $image;
    }
}
