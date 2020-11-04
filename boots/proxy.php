<?php
/**
 * This file best working in case web has proxy CDN like CloudFlare
 */
class Medoid_Proxy {
    protected static $instance;

    public static function get_instance() {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    public function init_hooks() {
        add_action('init', array($this, 'register_image_rewrite_url_rules'), 5);
        add_filter('query_vars', array($this, 'register_new_query_vars'));
        add_action('template_redirect', array($this, 'showImageContent'));
    }

    public function register_image_rewrite_url_rules() {
        add_rewrite_rule('^images/([a-zA-Z0-9_-]+)/(.*)$', 'index.php?medoid=view-image-size&size=$matches[1]&alias=$matches[2]', 'top');
        add_rewrite_rule('^images/(.*)$', 'index.php?medoid=view-image&alias=$matches[1]', 'top');
    }

    public function register_new_query_vars($query_vars) {
        $query_vars = array_merge($query_vars, array(
            'medoid',
            'size',
            'alias'
        ));
        return $query_vars;
    }

    public function showImageContent() {
    }
}

Medoid_Proxy::get_instance();
