<?php
$_tests_dir = getenv('WP_TESTS_DIR') ?: '/wordpress-phpunit';

require_once dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
require_once $_tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', function () {
    require WP_PLUGIN_DIR . '/elementor/elementor.php';
    require dirname(__DIR__) . '/osm-map-elementor.php';
});

require $_tests_dir . '/includes/bootstrap.php';
