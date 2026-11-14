<?php

use Elementor\Plugin;
use OSM_Map\Widget_OSM_Map;

class Test_Widget_Render extends WP_UnitTestCase
{
    public function set_up()
    {
        parent::set_up();
        delete_option('osm_widget');
        \Elementor\Core\Kits\Manager::create_default_kit();
    }

    public function tear_down()
    {
        set_current_screen('front');
        delete_option('osm_widget');
        parent::tear_down();
    }

    private function make_widget(array $settings = [], $id = 'abc1234')
    {
        return Plugin::$instance->elements_manager->create_element_instance([
            'id' => $id,
            'elType' => 'widget',
            'widgetType' => 'osm-map-elementor',
            'settings' => $settings,
        ]);
    }

    private function marker(array $overrides = [])
    {
        return array_merge([
            '_id' => 'm1' . wp_generate_password(4, false),
            'marker_coords' => '43.65,-79.38',
            'marker_title' => 'Title',
            'show_button' => 'yes',
            'button_text' => 'Go',
            'button_url' => 'https://example.com/',
        ], $overrides);
    }

    private function render_admin(array $settings = [], $id = 'abc1234')
    {
        set_current_screen('edit-post');
        $widget = $this->make_widget($settings, $id);

        ob_start();
        $widget->render_content();

        return ob_get_clean();
    }

    private function js_value($html, $name)
    {
        foreach (preg_split('/\R/', $html) as $line) {
            $line = trim($line);
            if (0 === strpos($line, "const $name = ")) {
                return json_decode(rtrim(substr($line, strlen("const $name = ")), ';'), true);
            }
        }
        $this->fail("const $name not found in the rendered script");
    }

    private function markers_for(array $marker_overrides)
    {
        $html = $this->render_admin(['marker_list' => [$this->marker($marker_overrides)]]);

        return $this->js_value($html, 'markers');
    }

    public function invalid_coordinates()
    {
        return [
            'non numeric' => ['abc,def'],
            'too many parts' => ['1,2,3'],
            'empty' => [''],
            'latitude out of range' => ['95,10'],
            'longitude out of range' => ['10,190'],
            'single part' => ['43.65'],
        ];
    }

    /**
     * @dataProvider invalid_coordinates
     */
    public function test_invalid_marker_coordinates_are_skipped_without_errors($coords)
    {
        $markers = $this->markers_for(['marker_coords' => $coords]);

        $this->assertSame([], $markers);
    }

    public function test_marker_without_a_coordinates_key_is_skipped_without_errors()
    {
        $marker = $this->marker();
        unset($marker['marker_coords']);

        $html = $this->render_admin(['marker_list' => [$marker]]);

        $this->assertSame([], $this->js_value($html, 'markers'));
    }

    public function test_padded_coordinates_become_numeric_floats()
    {
        $markers = $this->markers_for(['marker_coords' => ' 43.65 , -79.38 ']);

        $this->assertSame(43.65, $markers[0]['lat']);
        $this->assertSame(-79.38, $markers[0]['lng']);
    }

    public function test_breakpoints_are_mapped_to_mobile_and_tablet_maximum_widths()
    {
        $config = Plugin::$instance->breakpoints->get_breakpoints_config();

        $display = $this->js_value($this->render_admin(), 'displaySettings');

        $this->assertSame((int) $config['mobile']['value'], $display['breakpoints']['sm']);
        $this->assertSame((int) $config['tablet']['value'], $display['breakpoints']['md']);
    }

    public function test_script_scheme_button_url_is_dropped_and_web_urls_are_kept()
    {
        $this->assertSame('', $this->markers_for(['button_url' => 'javascript:alert(1)'])[0]['marker']['button_url']);
        $this->assertSame(
            'https://ex.com/?a=1&b=2',
            $this->markers_for(['button_url' => 'https://ex.com/?a=1&b=2'])[0]['marker']['button_url']
        );
        $this->assertSame('mailto:a@example.com', $this->markers_for(['button_url' => 'mailto:a@example.com'])[0]['marker']['button_url']);
    }

    public function test_button_target_is_restricted_to_self_or_blank()
    {
        $this->assertSame(
            '_blank',
            $this->markers_for(['button_url_target' => '"><img src=x onerror=alert(1)>'])[0]['marker']['button_url_target']
        );
        $this->assertSame('_self', $this->markers_for(['button_url_target' => '_self'])[0]['marker']['button_url_target']);
    }

    public function test_custom_tile_url_is_json_encoded_and_keeps_its_placeholders()
    {
        update_option('osm_widget', ['osm_custom' => 'https://{s}.tile.example.com/{z}/{x}/{y}.png']);

        $html = $this->render_admin(['geoapify_tile' => 'custom-tile']);

        $this->assertStringContainsString(wp_json_encode('https://{s}.tile.example.com/{z}/{x}/{y}.png'), $html);
    }

    public function test_custom_attribution_cannot_break_out_of_its_script_context()
    {
        update_option('osm_widget', [
            'osm_custom' => 'https://t.example.com/{z}/{x}/{y}.png',
            'osm_custom_attribution' => "x'</script><script>alert(1)</script>",
            'osm_custom_attribution_url' => 'https://example.com/',
        ]);

        $html = $this->render_admin(['geoapify_tile' => 'custom-tile']);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_unknown_geoapify_tile_falls_back_to_the_default_tile()
    {
        $html = $this->render_admin(['geoapify_tile' => 'osm-bright</script><script>alert(1)//']);

        $this->assertStringNotContainsString('</script><script>', $html);
        $this->assertStringNotContainsString('maps.geoapify.com', $html);
        $this->assertStringContainsString('tile.openstreetmap.org', $html);
    }

    public function test_geoapify_style_url_and_token_are_encoded_for_their_contexts()
    {
        update_option('osm_widget', ['geoapify_key' => 'k&e"y', 'mapbox_token' => "tok'en"]);

        $html = $this->render_admin(['geoapify_tile' => 'osm-bright']);

        $this->assertStringContainsString(
            wp_json_encode('https://maps.geoapify.com/v1/styles/osm-bright/style.json?apiKey=' . rawurlencode('k&e"y')),
            $html
        );
        $this->assertStringContainsString('accessToken: ' . wp_json_encode("tok'en"), $html);
    }

    public function test_mapbox_token_defaults_to_a_placeholder_when_unset()
    {
        $html = $this->render_admin(['geoapify_tile' => 'osm-bright']);

        $this->assertStringContainsString('accessToken: "no-token"', $html);
    }

    public function test_widget_does_not_opt_into_element_caching()
    {
        $declaring_class = (new ReflectionMethod(Widget_OSM_Map::class, 'is_dynamic_content'))->getDeclaringClass()->getName();

        $this->assertNotSame(Widget_OSM_Map::class, $declaring_class);
    }

    public function test_repeater_title_field_is_escaped_in_the_editor_template()
    {
        $controls = $this->make_widget()->get_controls();

        $this->assertSame('{{ marker_title }}', $controls['marker_list']['title_field']);
    }

    public function test_admin_notes_link_to_the_settings_page_through_admin_url()
    {
        $controls = $this->make_widget()->get_controls();
        $settings_url = esc_url(admin_url('options-general.php?page=osm-map-elementor'));

        $this->assertStringContainsString('href="' . $settings_url . '"', $controls['important_note']['raw']);
        $this->assertStringContainsString('href="' . $settings_url . '"', $controls['important_note2']['raw']);
        $this->assertStringContainsString('<strong>Font Awesome</strong>', $controls['fontawesome_important_note']['raw']);
        $this->assertStringContainsString('href="' . $settings_url . '"', $controls['fontawesome_important_note']['raw']);
    }

    public function test_google_maps_script_url_encodes_the_key_loads_async_and_has_no_version_query()
    {
        update_option('osm_widget', ['gmaps_key' => 'ab"c&d']);
        set_current_screen('edit-post');

        $this->make_widget();
        $google = wp_scripts()->registered['google-maps'];

        $this->assertStringContainsString('key=' . rawurlencode('ab"c&d'), $google->src);
        $this->assertStringContainsString('loading=async', $google->src);
        $this->assertStringContainsString('callback=initOSMEditorControls', $google->src);
        $this->assertNull($google->ver);
    }

    public function test_debug_helper_is_removed()
    {
        $this->assertFalse((new ReflectionClass(Widget_OSM_Map::class))->hasMethod('__debug'));
    }
}
