<?php
if (!defined('ABSPATH')) {
    exit;
}

define('OSM_MAP_SLUG', 'osm-map-elementor');
define('OSM_PLUGIN_FOLDER', basename(__DIR__) != 'trunk' ? basename(__DIR__) : OSM_MAP_SLUG);
define('OSM_MAP_VERSION', '1.4.0');
define('OSM_MAP_MIN_ELEMENTOR_VERSION', '3.30.0');
