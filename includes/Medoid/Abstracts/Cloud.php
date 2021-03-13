<?php
namespace Medoid\Abstracts;

use Medoid\Constracts\Cloud as CloudInterface;
use Medoid\DB;

abstract class Cloud implements CloudInterface
{
    protected $db;
    protected $_internal_cloud_id;
    protected $options;

    public function __construct($id, $configs = array())
    {
        $this->set_id($id);
        $this->set_options($configs);
    }

    protected function set_options($options)
    {
        $this->options = $options;
    }

    protected function set_id($id)
    {
        $this->_internal_cloud_id = (int) $id;
    }

    public function get_id()
    {
        return $this->_internal_cloud_id;
    }

    public function get_db()
    {
        if (empty($this->db)) {
            $this->db = DB::instance();
        }
        return $this->db;
    }

    public function sync_to_cloud($limit_items = 50)
    {
        $images = $this->get_db()->get_images(
            array(
                'cloud_id'    => $this->get_id(),
                'limit'       => $limit_items,
                'is_uploaded' => false,
                'is_deleted'  => false,
                'orderby'     => 'retry ASC, updated_at ASC, post_id ASC',
            )
        );

        $notify_key = sprintf('medoid_cloud_%s_notified', $this->get_id());
        $notified   = get_option($notify_key, false);
        if (empty($images)) {
            if (! $notified) {
                Logger::get('medoid')->notice(
                    sprintf(
                        'The %s #%s cloud sync process is maybe completed.',
                        $this->get_id(),
                        $this->get_name()
                    )
                );
                update_option($notify_key, true);
            }
            return;
        } elseif (! $notified) {
            update_option($notify_key, true);
        }

        Logger::get('medoid')->debug(
            'Load images from database to sync to ' . $this->get_name(),
            array( 'total_images' => count($images) )
        );
        foreach ($images as $image) {
            $attachment = get_post($image->post_id);
            if (empty($attachment)) {
                $this->delete_image($image, true, false);
                Logger::get('medoid')->warning(
                    sprintf('The image #%d is not exists in WordPress so it can not delete', $image->post_id),
                    (array) $image
                );
                continue;
            }

            try {
                $file = get_attached_file($image->post_id, true);
                if (! file_exists($file)) {
                    throw new Exception(
                        sprintf(
                            'The attachment #%d is exists but the real file %s is not exists',
                            $image->post_id,
                            $file
                        )
                    );
                }

                $newfile = $this->make_unique_file_name($file, $image);
                Logger::get('medoid')->info(
                    sprintf(
                        'File %s is generated a new name: %s',
                        $file,
                        $newfile
                    )
                );

                if (false === $newfile) {
                    Logger::get('medoid')->debug(
                        'Has error when genrate file name. This process for this file is stopping...'
                    );
                    continue;
                }

                Logger::get('medoid')->debug(
                    sprintf(
                        'The attachment #%d is uploading to the %s cloud',
                        $image->post_id,
                        $this->get_id()
                    )
                );
                $response = $this->upload($file, $newfile);
                if ($response->get_status()) {
                    $image_info = array(
                        'ID'                => $image->ID,
                        'image_url'         => $response->get_url(),
                        'provider_image_id' => $response->get_provider_image_id(),
                        'is_uploaded'       => true,
                        'updated_at'        => current_time('mysql'),
                    );
                    $this->db->update_image($image_info);

                    /**
                     * Do actions after upload image to cloud success
                     */
                    do_action('medoid_upload_cloud_image', $image, $response, $this);

                    Logger::get('medoid')->info(
                        sprintf(
                            'The image #%d(%s) to %s is uploaded successful',
                            $image->ID,
                            $image->image_url,
                            $this->get_name()
                        ),
                        (array) $response
                    );
                } else {
                    $this->db->update_image(
                        array(
                            'ID'         => $image->ID,
                            'retry'      => (int) $image->retry + 1,
                            'updated_at' => current_time('mysql'),
                        )
                    );

                    Logger::get('medoid')->warning(
                        sprintf(
                            'Upload image #%d(%s) to %s is failed: %s',
                            $image->ID,
                            $image->image_url,
                            $this->get_name(),
                            $response->get_error_message()
                        ),
                        (array) $response
                    );
                }
            } catch (Throwable $e) {
                Logger::get('medoid')->error(
                    sprintf(
                        "%s\n%s",
                        $e->getMessage(),
                        var_export(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
                    ),
                    $image
                );
            }
        }
    }

    protected function get_old_wordpress_images()
    {
        $not_sync_att_sql = DB::prepare(
            'SELECT `ID`, `guid` FROM '
                . DB::get_table('posts')
            . ' WHERE ID NOT IN (
				SELECT post_id FROM '
                    . DB::get_table('medoid_images')
                . ' WHERE cloud_id=%d
			) AND post_type=%s ORDER BY post_date ASC',
            $this->get_id(),
            'attachment'
        );
        return DB::get_results($not_sync_att_sql);
    }

    public function clone_attachments()
    {
        $synced_old_image_key = sprintf(
            'medoid_sync_old_image_to_cloud_%d',
            $this->get_id()
        );
        $is_synced            = get_option($synced_old_image_key, false);
        if ($is_synced) {
            return;
        }
        $images = $this->get_old_wordpress_images();
        if (count($images) < 1) {
            Logger::get('medoid')->notice(
                sprintf(
                    'Sync old images to Medoid %s #%d is completed',
                    $this->get_name(),
                    $this->get_id()
                )
            );
            update_option($synced_old_image_key, true);
            return;
        }

        foreach ($images as $image) {
            $file_name = str_replace(site_url('/'), '', $image->guid);
            $file_path = sprintf('%s%s', ABSPATH, $file_name);
            $file_size = file_exists($file_path) ? filesize($file_path) : 0;
            $mime_type = file_exists($file_path) ? mime_content_type($file_path) : 'image/jpeg';

            $image_data = array(
                'cloud_id'          => $this->get_id(),
                'post_id'           => (int) $image->ID,
                'image_url'         => $image->guid,
                'is_uploaded'       => 0,
                'is_deleted'        => 0,
                'delete_local_file' => 1,
                'file_name'         => $file_name,
                'mime_type'         => $mime_type,
                'file_size'         => $file_size,
                'created_at'        => current_time('mysql'),
                'updated_at'        => current_time('mysql'),
            );
            $new_id     = $this->get_db()->insert_image($image_data);
        }
    }

    public function make_unique_file_name($file, $medoid_image)
    {
        return apply_filters_ref_array(
            'medoid_create_file_name_unique',
            array( basename($file), $medoid_image, &$this )
        );
    }
}
