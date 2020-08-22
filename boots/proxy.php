<?php

class Medoid_Boot_Proxy {
    public function __construct() {
        add_action('init', array($this, 'register_rewrite_rules'), 5);
    }

    public function register_rewrite_rules() {
        add_rewrite_rule('^images/(.+)/?$', 'index.php?medoid=$matches[1]', 'top');
    }
}

new Medoid_Boot_Proxy();
