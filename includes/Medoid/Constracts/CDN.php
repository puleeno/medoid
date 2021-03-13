<?php
namespace Medoid\Constracts;

interface CDN
{
    public function __toString();

    public function get_name();

    public function get_url();
}
