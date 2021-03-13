<?php
namespace Medoid\Core;

use Medoid\Manager\CloudStorageManager;
use Medoid\DB;

class UploadHandler
{
    public static $current_post;
    protected $db;
    protected $result;

    public static function set_current_post($post)
    {
        if (! $post instanceof WP_Post) {
            $post = get_post($post);
        }
        self::$current_post = $post;
    }

    public function __construct()
    {
        $this->init();
        $this->init_hooks();
    }

    public function init()
    {
        $this->db = DB::instance();

        add_filter('wp_unique_filename', array( $this, 'check_and_create_new_unique_filename' ), 10, 3);
    }

    public function init_hooks()
    {
        add_action('wp_handle_upload', array( $this, 'get_upload_result' ), 10);
        add_action('add_attachment', array( $this, 'insert_temporary_cloud_image' ));

        // Hook actions to delete WordPress media
        add_action('delete_attachment', array( $this, 'delete_image' ));
    }

    public function get_upload_result($result)
    {
        $cloud_storage = new CloudStorageManager();
        $cloud_storage->init();

        return $this->result = $result;
    }

    public function insert_temporary_cloud_image($attachment_id)
    {
        $clouds = CloudStorageManager::get_active_clouds();
        if (! Medoid::is_active() || empty($clouds)) {
            return;
        };

        $delete_local_file = apply_filters('medoid_delete_local_file', true);

        $upload_dir = wp_upload_dir();
        $upload_dir = $upload_dir['basedir'] . '/';
        $file_name  = str_replace('\\', '/', $this->result['file']);
        if (PHP_OS === 'WINNT') {
            $upload_dir = str_replace('\\', '/', $upload_dir);
        }
        $file_name = str_replace($upload_dir, '', $file_name);

        $image_data = array(
            'post_id'           => $attachment_id,
            'image_url'         => $this->result['url'],
            'file_size'         => filesize($this->result['file']),
            'file_name'         => $file_name,
            'alias'             => $file_name,
            'mime_type'         => $this->result['type'],
            'delete_local_file' => $delete_local_file,
        );

        foreach (array_keys($clouds) as $cloud_id) {
            $image_data['cloud_id'] = $cloud_id;
            /**
             * The cloud_id is zero this mean the cloud is Local Storage
             * so the plugin don't need upload the image
             */
            $image_data['is_uploaded'] = $cloud_id === 0;

            $this->db->insert_image($image_data);
        }

        if ($this->cdn_support_resize = false) {
            remove_filter('intermediate_image_sizes', '__return_empty_array');
            remove_filter('wp_update_attachment_metadata', '__return_null');
        }
    }

    public function delete_image($attachment_id)
    {
        $this->db->delete_image_from_attachment($attachment_id);
    }

    public function check_and_create_new_unique_filename($filename, $ext, $dir)
    {
        $alias        = str_replace(
            WP_CONTENT_DIR,
            '',
            sprintf('%s/%s', $dir, $filename)
        );
        $alias        = rtrim($alias, '/');
        $medoid_image = $this->db->get_image_by_alias($alias, array( 'ID' ));
        if (is_null($medoid_image)) {
            return $filename;
        }
        return sprintf('%s-%s', date('YmdHis'), $filename);
    }
}
