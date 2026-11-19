<?php

use function OSM_Map\handle_settings_save;
use function OSM_Map\render_elementor_version_notice;

class Test_Settings_Save extends WP_UnitTestCase
{
    public function set_up()
    {
        parent::set_up();
        delete_option('osm_widget');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = $_GET = $_REQUEST = [];
    }

    public function tear_down()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = $_GET = $_REQUEST = [];
        parent::tear_down();
    }

    private function register_settings_page()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        do_action('admin_menu');

        $hooks = array_filter(array_keys($GLOBALS['wp_filter']), function ($hook) {
            return false !== strpos($hook, 'page_osm-map-elementor');
        });
        $this->assertCount(2, $hooks, 'The settings page callback and its load- hook must both be registered');

        return array_values(array_filter($hooks, function ($hook) {
            return 0 !== strpos($hook, 'load-');
        }))[0];
    }

    private function render_settings_page()
    {
        $page_hook = $this->register_settings_page();

        ob_start();
        do_action($page_hook);

        return ob_get_clean();
    }

    private function submit(array $widget_input, $nonce = null)
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $nonce = null === $nonce ? wp_create_nonce('osm_settings_action') : $nonce;
        $_POST = $_REQUEST = [
            'osm_settings_nonce' => $nonce,
            'osm_widget' => wp_slash($widget_input),
        ];
    }

    private function capture_redirect()
    {
        add_filter('wp_redirect', function ($location) {
            throw new RuntimeException($location);
        });
    }

    private function save_and_capture_redirect()
    {
        $this->capture_redirect();
        try {
            handle_settings_save();
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        return null;
    }

    public function test_admin_with_valid_nonce_saves_sanitized_settings_and_redirects_with_confirmation()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->submit([
            'gmaps_key' => '  key-123<b>  ',
            'osm_custom' => 'https://{s}.tile.example.com/{z}/{x}/{y}.png',
            'osm_custom_attribution' => 'Example <script>x</script>Org',
            'osm_custom_attribution_url' => 'https://example.com/a b',
            'enable_fontawesome' => '1',
        ]);

        $location = $this->save_and_capture_redirect();

        $saved = get_option('osm_widget');
        $this->assertSame('key-123', $saved['gmaps_key']);
        $this->assertSame('https://{s}.tile.example.com/{z}/{x}/{y}.png', $saved['osm_custom']);
        $this->assertSame('Example Org', $saved['osm_custom_attribution']);
        $this->assertSame('https://example.com/a%20b', $saved['osm_custom_attribution_url']);
        $this->assertStringContainsString('page=osm-map-elementor', $location);
        $this->assertStringContainsString('action=settings_saved', $location);
    }

    public function test_attribution_url_with_a_script_scheme_is_not_stored()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->submit(['osm_custom_attribution_url' => 'javascript:alert(1)']);

        $this->save_and_capture_redirect();

        $this->assertEmpty(get_option('osm_widget')['osm_custom_attribution_url']);
    }

    public function test_slashed_input_is_stored_unslashed()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $this->submit(['osm_custom_attribution' => "O'Reilly"]);

        $this->save_and_capture_redirect();

        $this->assertSame("O'Reilly", get_option('osm_widget')['osm_custom_attribution']);
    }

    public function test_bad_nonce_dies_and_leaves_the_option_untouched()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        update_option('osm_widget', ['gmaps_key' => 'original']);
        $this->submit(['gmaps_key' => 'tampered'], 'not-a-nonce');

        $this->expectException(WPDieException::class);
        try {
            handle_settings_save();
        } finally {
            $this->assertSame('original', get_option('osm_widget')['gmaps_key']);
        }
    }

    public function test_post_without_a_nonce_dies_and_leaves_the_option_untouched()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        update_option('osm_widget', ['gmaps_key' => 'original']);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $_REQUEST = ['osm_widget' => ['gmaps_key' => 'tampered']];

        $this->expectException(WPDieException::class);
        try {
            handle_settings_save();
        } finally {
            $this->assertSame('original', get_option('osm_widget')['gmaps_key']);
        }
    }

    public function test_subscriber_with_a_valid_nonce_cannot_save()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));
        update_option('osm_widget', ['gmaps_key' => 'original']);
        $this->submit(['gmaps_key' => 'tampered']);

        $this->expectException(WPDieException::class);
        try {
            handle_settings_save();
        } finally {
            $this->assertSame('original', get_option('osm_widget')['gmaps_key']);
        }
    }

    public function test_get_request_is_a_no_op()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        update_option('osm_widget', ['gmaps_key' => 'original']);
        $_GET = $_REQUEST = ['page' => 'osm-map-elementor'];

        handle_settings_save();

        $this->assertSame('original', get_option('osm_widget')['gmaps_key']);
    }

    public function test_save_handler_runs_before_output_on_the_settings_page_load_hook()
    {
        $page_hook = $this->register_settings_page();

        $this->assertNotFalse(has_action('load-' . $page_hook, 'OSM_Map\handle_settings_save'));
    }

    public function test_user_without_manage_options_gets_no_settings_page_load_hook()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));

        do_action('admin_menu');

        $this->assertFalse(has_action('load-', 'OSM_Map\handle_settings_save'));
    }

    public function test_settings_page_escapes_saved_values_in_attributes()
    {
        update_option('osm_widget', ['gmaps_key' => '"><script>alert(1)</script>']);

        $html = $this->render_settings_page();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
    }

    public function test_settings_page_shows_the_saved_notice_after_a_redirect()
    {
        $_GET['action'] = 'settings_saved';

        $html = $this->render_settings_page();

        $this->assertStringContainsString('Settings saved.', $html);
    }

    public function test_external_settings_page_links_open_a_new_tab_with_noopener()
    {
        $html = $this->render_settings_page();

        preg_match_all('/<a\b[^>]*href="https?:\/\/[^"]*"[^>]*>/', $html, $links);
        $this->assertNotEmpty($links[0]);
        foreach ($links[0] as $link) {
            $this->assertStringContainsString('target="_blank"', $link);
            $this->assertStringContainsString('rel="noopener noreferrer"', $link);
        }
    }

    public function test_settings_page_tolerates_an_unset_option()
    {
        $html = $this->render_settings_page();

        $this->assertStringContainsString('osm_widget[gmaps_key]', $html);
    }

    public function test_plugin_no_longer_enqueues_jquery_in_the_head_of_every_page()
    {
        $this->assertFalse(has_action('wp_enqueue_scripts', 'OSM_Map\enqueue_jquery_in_head'));
        $this->assertFalse(function_exists('OSM_Map\enqueue_jquery_in_head'));
    }

    public function test_notice_is_shown_to_updaters_when_elementor_is_older_than_the_minimum()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

        ob_start();
        render_elementor_version_notice('3.0.0');
        $html = ob_get_clean();

        $this->assertStringContainsString('notice-warning', $html);
        $this->assertStringContainsString(OSM_MAP_MIN_ELEMENTOR_VERSION, $html);
    }

    public function test_notice_is_silent_when_elementor_meets_the_minimum()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));

        ob_start();
        render_elementor_version_notice(OSM_MAP_MIN_ELEMENTOR_VERSION);
        $html = ob_get_clean();

        $this->assertSame('', $html);
    }

    public function test_notice_is_silent_for_users_who_cannot_update_plugins()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'editor']));

        ob_start();
        render_elementor_version_notice('3.0.0');
        $html = ob_get_clean();

        $this->assertSame('', $html);
    }
}
