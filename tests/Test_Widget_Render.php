<?php

use Elementor\Plugin;
use OSM_Map\Widget_OSM_Map;

class Test_Widget_Render extends WP_UnitTestCase
{
    public function set_up()
    {
        parent::set_up();
        $GLOBALS['wp_scripts'] = new WP_Scripts();
        $GLOBALS['wp_styles'] = new WP_Styles();
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

    public function test_display_settings_carry_only_the_zoom_levels_and_breakpoints()
    {
        $display = $this->js_value($this->render_admin([
            'marker_list' => [$this->marker(['marker_description' => 'Gate code 4321'])],
            'zoom_tablet' => ['unit' => 'px', 'size' => 7],
        ]), 'displaySettings');

        $this->assertSame([], array_diff(array_keys($display), ['zoom', 'zoom_tablet', 'zoom_mobile', 'breakpoints']));
        $this->assertArrayNotHasKey('marker_list', $display);
        $this->assertStringNotContainsString('Gate code 4321', wp_json_encode($display));
        $this->assertArrayHasKey('size', $display['zoom']);
        $this->assertSame(7, (int) $display['zoom_tablet']['size']);
        $this->assertArrayHasKey('sm', $display['breakpoints']);
        $this->assertArrayHasKey('md', $display['breakpoints']);
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

    public function test_marker_description_keeps_post_safe_html()
    {
        $html = '<strong>Bold</strong> <em>i</em><br><p>Para</p><a href="https://example.com/" target="_blank">Link</a>';

        $this->assertSame($html, $this->markers_for(['marker_description' => $html])[0]['marker']['marker_description']);
    }

    public function unsafe_descriptions()
    {
        return [
            'script element' => ['<script>alert(1)</script>Hi', '<script'],
            'event handler' => ['<img src="x" onerror="alert(1)">', 'onerror'],
            'script url' => ['<a href="javascript:alert(1)">x</a>', 'javascript:'],
        ];
    }

    /**
     * @dataProvider unsafe_descriptions
     */
    public function test_marker_description_strips_scripts_event_handlers_and_script_urls($description, $forbidden)
    {
        $this->assertStringNotContainsStringIgnoringCase(
            $forbidden,
            $this->markers_for(['marker_description' => $description])[0]['marker']['marker_description']
        );
    }

    public function test_marker_description_is_sanitized_for_users_with_unfiltered_html()
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        if (!current_user_can('unfiltered_html')) {
            $this->markTestSkipped('The administrator role lacks unfiltered_html on this install.');
        }

        $description = $this->markers_for(['marker_description' => '<script>alert(1)</script>Hi'])[0]['marker']['marker_description'];

        $this->assertStringNotContainsString('<script', $description);
        $this->assertSame('alert(1)Hi', $description);
    }

    public function non_string_descriptions()
    {
        return [
            'array' => [['<b>x</b>']],
            'null' => [null],
        ];
    }

    /**
     * @dataProvider non_string_descriptions
     */
    public function test_non_string_marker_description_becomes_empty($description)
    {
        $this->assertSame('', $this->markers_for(['marker_description' => $description])[0]['marker']['marker_description']);
    }

    public function test_missing_marker_description_becomes_empty()
    {
        $this->assertSame('', $this->markers_for([])[0]['marker']['marker_description']);
    }

    public function test_plain_text_description_keeps_its_meaning_and_line_breaks()
    {
        $this->assertSame(
            "Tom &amp; Jerry\na &lt; b",
            $this->markers_for(['marker_description' => "Tom & Jerry\na < b"])[0]['marker']['marker_description']
        );
    }

    public function test_script_inserts_the_description_as_html_and_still_escapes_title_and_button()
    {
        $js = $this->render_admin();

        $this->assertStringNotContainsString('escapeHtml(this.marker.marker_description)', $js);
        $this->assertStringContainsString('<div class="marker-description">${this.marker.marker_description}</div>', $js);
        $this->assertStringContainsString('escapeHtml(this.marker.marker_title)', $js);
        $this->assertStringContainsString('escapeHtml(this.marker.button_text)', $js);
    }

    public function test_editor_script_json_escapes_a_description_that_closes_the_script_tag()
    {
        $html = $this->render_admin(['marker_list' => [$this->marker(['marker_description' => '</script><script>alert(1)</script>'])]]);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertSame(substr_count($this->render_admin(), '</script>'), substr_count($html, '</script>'));
    }

    public function test_frontend_script_carries_the_sanitized_description_html()
    {
        $this->render_frontend(['marker_list' => [$this->marker(['marker_description' => '<strong>Bold</strong><script>alert(1)</script>'])]]);

        $markers = $this->js_value($this->inline_scripts_after_markers_handle(), 'markers');

        $this->assertSame('<strong>Bold</strong>alert(1)', $markers[0]['marker']['marker_description']);
    }

    public function test_text_in_angle_brackets_is_treated_as_markup()
    {
        $this->assertSame('Contact: Jane ', $this->markers_for(['marker_description' => 'Contact: Jane <jane@example.com>'])[0]['marker']['marker_description']);
        $this->assertSame(
            'Contact: Jane &lt;jane@example.com&gt;',
            $this->markers_for(['marker_description' => 'Contact: Jane &lt;jane@example.com&gt;'])[0]['marker']['marker_description']
        );
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

    public function test_admin_notes_render_the_link_inside_the_sentence()
    {
        $controls = $this->make_widget()->get_controls();
        $link = '<a href="' . esc_url(admin_url('options-general.php?page=osm-map-elementor')) . '" target="_blank" rel="noopener">here</a>';

        foreach (['important_note', 'important_note2', 'fontawesome_important_note'] as $id) {
            $this->assertStringContainsString('in global settings ' . $link . '</div>', str_replace('in the global settings', 'in global settings', $controls[$id]['raw']));
        }
        $this->assertStringContainsString('toggle the <strong>Font Awesome</strong> setting', $controls['fontawesome_important_note']['raw']);
    }

    public function test_google_maps_script_url_encodes_the_key_loads_async_and_has_no_version_query()
    {
        update_option('osm_widget', ['gmaps_key' => 'ab"c&d']);
        set_current_screen('edit-post');

        do_action('elementor/editor/after_enqueue_scripts');
        $google = wp_scripts()->registered['google-maps'];

        $this->assertStringContainsString('key=' . rawurlencode('ab"c&d'), $google->src);
        $this->assertStringContainsString('loading=async', $google->src);
        $this->assertStringContainsString('callback=initOSMEditorControls', $google->src);
        $this->assertNull($google->ver);
    }

    public function test_constructing_the_widget_enqueues_nothing()
    {
        $this->make_widget();

        $this->assertFalse(wp_script_is('leaflet', 'enqueued'));
        $this->assertFalse(wp_script_is('leaflet-fa-markers', 'enqueued'));
        $this->assertFalse(wp_style_is('leaflet', 'enqueued'));
        $this->assertFalse(wp_style_is('osm-map-elementor', 'enqueued'));
    }

    public function test_widget_declares_its_vendor_scripts_and_styles_as_dependencies()
    {
        $widget = $this->make_widget();

        $this->assertSame(['leaflet', 'mapbox-gl', 'leaflet-mapbox-gl', 'leaflet-fa-markers'], $widget->get_script_depends());

        update_option('osm_widget', ['enable_fontawesome' => 'yes']);
        $this->assertSame(['leaflet', 'mapbox-gl', 'leaflet-fa-markers', 'osm-map-elementor', 'font-awesome-free'], $widget->get_style_depends());

        delete_option('osm_widget');
        $this->assertSame(['leaflet', 'mapbox-gl', 'leaflet-fa-markers', 'osm-map-elementor'], $widget->get_style_depends());
    }

    public function test_rendering_a_map_registers_and_enqueues_its_assets()
    {
        $this->render_frontend();

        $this->assertTrue(wp_script_is('leaflet-fa-markers', 'enqueued'));
        $this->assertTrue(wp_style_is('leaflet', 'enqueued'));
        $this->assertTrue(wp_style_is('osm-map-elementor', 'enqueued'));
    }

    public function test_editor_controls_script_loads_only_on_the_editor_hook()
    {
        $this->render_admin();
        $this->assertFalse(wp_script_is('osm-map-elementor-controls', 'enqueued'));

        do_action('elementor/editor/after_enqueue_scripts');

        $this->assertTrue(wp_script_is('osm-map-elementor-controls', 'enqueued'));
    }

    public function test_asset_hooks_are_registered()
    {
        $this->assertNotFalse(has_action('elementor/frontend/after_register_scripts', 'OSM_Map\\register_widget_assets'));
        $this->assertNotFalse(has_action('elementor/frontend/after_register_styles', 'OSM_Map\\register_widget_assets'));
    }

    public function test_every_page_loading_is_opt_in_through_a_filter()
    {
        do_action('wp_enqueue_scripts');
        $this->assertFalse(wp_script_is('leaflet-fa-markers', 'enqueued'));

        add_filter('osm_map_elementor_load_assets_on_every_page', '__return_true');
        do_action('wp_enqueue_scripts');

        $this->assertTrue(wp_script_is('leaflet-fa-markers', 'enqueued'));
        $this->assertTrue(wp_style_is('leaflet', 'enqueued'));
        remove_filter('osm_map_elementor_load_assets_on_every_page', '__return_true');
    }

    private function render_frontend(array $settings = [], $id = 'abc1234')
    {
        set_current_screen('front');
        $widget = $this->make_widget($settings, $id);

        ob_start();
        $widget->render_content();

        return ob_get_clean();
    }

    private function inline_scripts_after_markers_handle()
    {
        return implode("\n", (array) wp_scripts()->get_data('leaflet-fa-markers', 'after'));
    }

    public function test_frontend_render_attaches_the_script_to_the_vendor_handle_and_prints_no_script_tag()
    {
        $html = $this->render_frontend();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString("mapId = 'osm-map-abc1234'", $this->inline_scripts_after_markers_handle());
    }

    public function test_admin_render_prints_exactly_one_identified_script_tag()
    {
        $html = $this->render_admin();

        $this->assertSame(1, substr_count($html, '<script'));
        $this->assertMatchesRegularExpression('/<script[^>]*id=["\']js-marker-abc1234["\']/', $html);
        $this->assertStringContainsString("mapId = 'osm-map-abc1234'", $html);
    }

    public function test_two_frontend_instances_each_get_their_own_script()
    {
        $this->render_frontend([], 'abc1234');
        $this->render_frontend([], 'def5678');

        $scripts = $this->inline_scripts_after_markers_handle();

        $this->assertStringContainsString("mapId = 'osm-map-abc1234'", $scripts);
        $this->assertStringContainsString("mapId = 'osm-map-def5678'", $scripts);
    }

    public function test_plugin_does_not_hook_the_footer_script_printer()
    {
        $this->render_frontend();

        $owners = [];
        foreach (wp_filter_callbacks_for('wp_print_footer_scripts') as $callback) {
            if ($callback instanceof Closure) {
                $owners[] = (new ReflectionFunction($callback))->getFileName();
            }
        }

        $this->assertNotContains(dirname(__DIR__) . '/osm-map.php', $owners);
    }

    public function test_vendor_script_chain_depends_on_jquery()
    {
        $this->render_frontend();

        $this->assertContains('jquery', wp_scripts()->registered['leaflet']->deps);
    }

    public function test_script_uses_the_document_ready_form_and_a_protocol_allowlist_for_links()
    {
        $js = $this->render_admin();

        $this->assertStringContainsString('jQuery(function () {', $js);
        $this->assertStringNotContainsString('jQuery(window).ready', $js);
        $this->assertStringContainsString("['http:', 'https:', 'mailto:', 'tel:'].includes(parsed.protocol)", $js);
        $this->assertStringNotContainsString("return 'javascript:void(0)'", $js);
    }

    public function test_script_resizes_the_map_when_its_container_changes_size()
    {
        $js = $this->render_admin();

        $this->assertStringContainsString("typeof ResizeObserver !== 'undefined'", $js);
        $this->assertMatchesRegularExpression('/new ResizeObserver\(function \(entries\) \{.*?map\.invalidateSize\(\);/s', $js);
        $this->assertStringContainsString('_osmResizeObserver.observe(mapElement)', $js);
        $this->assertStringContainsString('mapElement._osmResizeObserver.disconnect();', $js);
        $this->assertStringContainsString('if (!mapElement.isConnected)', $js);
    }

    public function test_widget_fills_the_width_of_an_elementor_container()
    {
        $css = file_get_contents(dirname(__DIR__) . '/assets/css/osm-map-elementor.css');

        $this->assertMatchesRegularExpression('/\.e-con \.elementor-widget-osm-map-elementor\s*\{\s*width:\s*100%;\s*\}/', $css);
    }

    public function test_osm_carto_tiles_load_from_the_single_tile_host()
    {
        $html = $this->render_admin(['geoapify_tile' => 'osm-carto']);

        $this->assertStringContainsString("L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png'", $html);
        $this->assertStringNotContainsString('{s}.tile.openstreetmap.org', $html);
    }

    private function new_tab_links($html)
    {
        preg_match_all('/<a\b[^>]*target="_blank"[^>]*>/', stripslashes($html), $links);

        return $links[0];
    }

    private function links_without_noopener(array $links)
    {
        return array_values(array_filter($links, function ($link) {
            return !preg_match('/\brel="[^"]*\bnoopener\b/', $link);
        }));
    }

    public function test_tile_attribution_links_that_open_a_new_tab_use_noopener()
    {
        update_option('osm_widget', [
            'osm_custom' => 'https://t.example.com/{z}/{x}/{y}.png',
            'osm_custom_attribution' => 'Example',
            'osm_custom_attribution_url' => 'https://example.com/',
        ]);
        $options = new ReflectionMethod(get_class($this->make_widget()), 'osm_tile_options');
        $options->setAccessible(true);

        $tiles_without_links = [];
        $missing = [];
        foreach (array_keys($options->invoke(null)) as $tile) {
            $links = $this->new_tab_links($this->render_admin(['geoapify_tile' => $tile]));
            if (!$links) {
                $tiles_without_links[] = $tile;
            }
            if ($this->links_without_noopener($links)) {
                $missing[$tile] = $this->links_without_noopener($links);
            }
        }

        $this->assertSame([], $tiles_without_links, 'Every tile renders at least one attribution link that opens a new tab');
        $this->assertSame([], $missing);
    }

    public function test_marker_button_gets_noopener_only_when_it_opens_a_new_tab()
    {
        $js = $this->render_admin();

        $this->assertStringContainsString("const button_rel = button_url_target === '_blank' ? ' rel=\"noopener\"' : '';", $js);
        $this->assertStringContainsString('role="button"${button_rel}>', $js);
        $this->assertDoesNotMatchRegularExpression('/elementor-button-link[^`]*noreferrer/', $js);
    }

    private function script_declares($html, $name)
    {
        return 1 === preg_match('/^\s*const ' . preg_quote($name, '/') . ' = /m', $html);
    }

    private function default_marker_geometry(array $options)
    {
        unset($options['iconUrl']);

        return $options;
    }

    private function default_marker_html($size)
    {
        return $this->render_admin([
            'marker_list' => [$this->marker()],
            'marker_size' => ['unit' => '%', 'size' => $size],
        ]);
    }

    private function font_awesome_marker_html(array $overrides = [])
    {
        return $this->render_admin(array_merge([
            'marker_list' => [$this->marker()],
            'icon_type' => 'fontawesome',
        ], $overrides));
    }

    public function test_marker_size_control_targets_default_and_font_awesome_markers()
    {
        $control = $this->make_widget()->get_controls('marker_size');

        $this->assertSame('slider', $control['type']);
        $this->assertSame(['%'], $control['size_units']);
        $this->assertEquals(['min' => 25, 'max' => 200, 'step' => 5], $control['range']['%']);
        $this->assertSame('%', $control['default']['unit']);
        $this->assertSame(100, $control['default']['size']);
        $this->assertSame(['icon_type' => ['', 'fontawesome']], $control['condition']);
    }

    public function test_default_marker_without_a_saved_size_renders_leaflets_default_icon()
    {
        $html = $this->render_admin(['marker_list' => [$this->marker()]]);

        $this->assertFalse($this->script_declares($html, 'markerIconOptions'));
        $this->assertStringNotContainsString('L.Icon.Default', $html);
    }

    public function test_default_marker_at_full_size_renders_leaflets_default_icon()
    {
        $html = $this->default_marker_html(100);

        $this->assertFalse($this->script_declares($html, 'markerIconOptions'));
        $this->assertStringNotContainsString('L.Icon.Default', $html);
    }

    public function scaled_default_geometry()
    {
        return [
            '150 percent' => [150, [
                'iconSize' => [38, 62],
                'iconAnchor' => [18, 62],
                'popupAnchor' => [2, -51],
                'tooltipAnchor' => [24, -42],
                'shadowSize' => [62, 62],
            ]],
            '50 percent' => [50, [
                'iconSize' => [13, 21],
                'iconAnchor' => [6, 21],
                'popupAnchor' => [1, -17],
                'tooltipAnchor' => [8, -14],
                'shadowSize' => [21, 21],
            ]],
            '200 percent' => [200, [
                'iconSize' => [50, 82],
                'iconAnchor' => [24, 82],
                'popupAnchor' => [2, -68],
                'tooltipAnchor' => [32, -56],
                'shadowSize' => [82, 82],
            ]],
        ];
    }

    /**
     * @dataProvider scaled_default_geometry
     */
    public function test_default_marker_geometry_scales_with_marker_size($size, array $expected)
    {
        $html = $this->default_marker_html($size);

        $this->assertSame($expected, $this->default_marker_geometry($this->js_value($html, 'markerIconOptions')));
        $this->assertStringContainsString('markerOptions.icon = new L.Icon.Default(markerIconOptions);', $html);
    }

    public function clamped_and_defaulted_sizes()
    {
        $minimum = [
            'iconSize' => [6, 10],
            'iconAnchor' => [3, 10],
            'popupAnchor' => [0, -9],
            'tooltipAnchor' => [4, -7],
            'shadowSize' => [10, 10],
        ];
        $maximum = [
            'iconSize' => [50, 82],
            'iconAnchor' => [24, 82],
            'popupAnchor' => [2, -68],
            'tooltipAnchor' => [32, -56],
            'shadowSize' => [82, 82],
        ];

        return [
            'below the minimum' => [['unit' => '%', 'size' => 10], $minimum],
            'above the maximum' => [['unit' => '%', 'size' => 500], $maximum],
            'negative' => [['unit' => '%', 'size' => -40], $minimum],
            'numeric string' => [['unit' => '%', 'size' => '25'], $minimum],
            'non numeric' => [['unit' => '%', 'size' => 'abc'], null],
            'empty string' => [['unit' => '%', 'size' => ''], null],
            'null size' => [['unit' => '%', 'size' => null], null],
            'missing size key' => [['unit' => '%'], null],
            'not an array' => ['150', null],
        ];
    }

    /**
     * @dataProvider clamped_and_defaulted_sizes
     */
    public function test_marker_size_is_clamped_and_defaulted_on_the_server($saved, $expected)
    {
        $html = $this->render_admin([
            'marker_list' => [$this->marker()],
            'marker_size' => $saved,
        ]);

        if (null === $expected) {
            $this->assertFalse($this->script_declares($html, 'markerIconOptions'));
            return;
        }

        $options = $this->default_marker_geometry($this->js_value($html, 'markerIconOptions'));
        $this->assertSame($expected, $options);
        array_walk_recursive($options, function ($value) {
            $this->assertIsInt($value);
        });
    }

    public function test_default_marker_below_full_size_keeps_leaflets_image_choice()
    {
        $options = $this->js_value($this->default_marker_html(50), 'markerIconOptions');

        $this->assertSame(['iconSize', 'iconAnchor', 'popupAnchor', 'tooltipAnchor', 'shadowSize'], array_keys($options));
    }

    public function test_default_marker_above_full_size_uses_the_high_resolution_image_file_name()
    {
        $options = $this->js_value($this->default_marker_html(150), 'markerIconOptions');

        $this->assertSame(['iconSize', 'iconAnchor', 'popupAnchor', 'tooltipAnchor', 'shadowSize', 'iconUrl'], array_keys($options));
        $this->assertSame('marker-icon-2x.png', $options['iconUrl']);
    }

    public function test_font_awesome_marker_options_are_unchanged_at_full_size()
    {
        $html = $this->font_awesome_marker_html();

        $this->assertSame([
            'iconClasses' => 'fa fa-circle',
            'markerColor' => '#368acc',
            'markerFillOpacity' => 1,
            'markerStrokeWidth' => 1,
            'markerStrokeColor' => '#346F9E',
            'iconColor' => '#ffffff',
            'iconSize' => 12,
        ], $this->js_value($html, 'markerIconOptions'));
        $this->assertSame([0, -50], $this->js_value($html, 'markerTooltipOffset'));
    }

    public function scaled_font_awesome_markers()
    {
        return [
            '150 percent' => [150, 1.5, [0, -75]],
            '50 percent' => [50, 0.5, [0, -25]],
        ];
    }

    /**
     * @dataProvider scaled_font_awesome_markers
     */
    public function test_font_awesome_marker_scale_and_anchors_follow_marker_size($size, $scale, array $anchor)
    {
        $unscaled = $this->js_value($this->font_awesome_marker_html(), 'markerIconOptions');
        $html = $this->font_awesome_marker_html(['marker_size' => ['unit' => '%', 'size' => $size]]);
        $options = $this->js_value($html, 'markerIconOptions');

        $this->assertSame($scale, $options['markerScale']);
        $this->assertSame($anchor, $options['popupAnchor']);
        $this->assertSame($anchor, $this->js_value($html, 'markerTooltipOffset'));
        unset($options['markerScale'], $options['popupAnchor']);
        $this->assertSame($unscaled, $options);
    }

    public function test_font_awesome_tooltip_uses_the_scaled_offset()
    {
        $html = $this->render_admin([
            'marker_list' => [$this->marker(['marker_behavior' => 'tooltip'])],
            'icon_type' => 'fontawesome',
            'marker_size' => ['unit' => '%', 'size' => 150],
        ]);

        $this->assertStringContainsString('tooltipOptions.offset = markerTooltipOffset;', $html);
        $this->assertStringNotContainsString('[0, -50]', $html);
    }

    public function test_font_awesome_icon_size_option_is_passed_through_unscaled()
    {
        $html = $this->font_awesome_marker_html([
            'icon_size' => ['unit' => 'px', 'size' => 18],
            'marker_size' => ['unit' => '%', 'size' => 200],
        ]);
        $options = $this->js_value($html, 'markerIconOptions');

        $this->assertSame(18, $options['iconSize']);
        $this->assertEquals(2, $options['markerScale']);
    }

    public function test_frontend_render_carries_the_scaled_font_awesome_options()
    {
        $this->render_frontend([
            'marker_list' => [$this->marker()],
            'icon_type' => 'fontawesome',
            'marker_size' => ['unit' => '%', 'size' => 150],
        ]);

        $this->assertStringContainsString('"markerScale":1.5', $this->inline_scripts_after_markers_handle());
    }

    public function test_custom_image_marker_ignores_a_stale_marker_size()
    {
        $html = $this->render_admin([
            'marker_list' => [$this->marker()],
            'icon_type' => 'custom_image',
            'custom_icon_image' => ['url' => 'https://example.com/pin.png'],
            'marker_size' => ['unit' => '%', 'size' => 150],
        ]);

        $this->assertStringContainsString('L.icon(', $html);
        $this->assertStringNotContainsString('L.Icon.Default', $html);
        $this->assertFalse($this->script_declares($html, 'markerIconOptions'));
        $this->assertSame(0, substr_count($html, 'const markerIconOptions'));
    }

    private function two_markers_on_the_equator()
    {
        return [
            $this->marker(['marker_coords' => '0,10']),
            $this->marker(['marker_coords' => '0,20']),
            $this->marker(['marker_coords' => '50,50', 'marker_visible' => '']),
        ];
    }

    private function center_attribute($html)
    {
        preg_match('/data-center="([^"]*)"/', $html, $match);

        return html_entity_decode($match[1]);
    }

    public function test_center_mode_control_defaults_to_midpoint_with_three_choices()
    {
        $control = $this->make_widget()->get_controls('center_mode');

        $this->assertSame('midpoint', $control['default']);
        $this->assertSame(['midpoint', 'fit', 'custom'], array_keys($control['options']));
    }

    public function test_center_coordinates_show_only_in_custom_mode_and_accept_dynamic_tags()
    {
        $control = $this->make_widget()->get_controls('center_coords');

        $this->assertSame(['center_mode' => 'custom'], $control['condition']);
        $this->assertTrue($control['dynamic']['active']);
    }

    public function test_fit_padding_shows_only_in_fit_mode_with_a_50px_default()
    {
        $control = $this->make_widget()->get_controls('fit_padding');

        $this->assertSame(['center_mode' => 'fit'], $control['condition']);
        $this->assertSame(50, $control['default']['size']);
        $this->assertEquals(['min' => 0, 'max' => 200, 'step' => 5], $control['range']['px']);
    }

    public function test_saved_widget_without_a_center_mode_keeps_the_marker_midpoint()
    {
        $settings = ['marker_list' => $this->two_markers_on_the_equator()];

        $html = $this->render_admin($settings);
        [$lat, $lng] = array_map('floatval', explode(',', $this->center_attribute($html)));

        $this->assertEqualsWithDelta(0.0, $lat, 0.0001);
        $this->assertEqualsWithDelta(15.0, $lng, 0.1);
        $this->assertSame($this->center_attribute($html), $this->center_attribute($this->render_admin($settings + ['center_mode' => 'midpoint'])));
        $this->assertStringContainsString('map.setView(centerCoords, zoomLevel);', $html);
        $this->assertSame('midpoint', $this->js_value($html, 'mapView')['mode']);
        $this->assertNull($this->js_value($html, 'mapView')['bounds']);
    }

    public function test_custom_center_replaces_the_midpoint()
    {
        $html = $this->render_admin([
            'marker_list' => $this->two_markers_on_the_equator(),
            'center_mode' => 'custom',
            'center_coords' => ' 45.5 , 7.6 ',
        ]);

        $this->assertSame('45.5,7.6', $this->center_attribute($html));
    }

    /**
     * @dataProvider invalid_coordinates
     */
    public function test_invalid_custom_center_falls_back_to_the_midpoint($coords)
    {
        $markers = $this->two_markers_on_the_equator();

        $custom = $this->render_admin(['marker_list' => $markers, 'center_mode' => 'custom', 'center_coords' => $coords]);
        $midpoint = $this->render_admin(['marker_list' => $markers]);

        $this->assertSame($this->center_attribute($midpoint), $this->center_attribute($custom));
    }

    public function test_custom_center_is_used_when_there_are_no_markers()
    {
        $html = $this->render_admin(['marker_list' => [], 'center_mode' => 'custom', 'center_coords' => '51.5072, -0.1276']);

        $this->assertSame('51.5072,-0.1276', $this->center_attribute($html));
        $this->assertSame([], $this->js_value($html, 'markers'));
        $this->assertStringContainsString('} else if (center) {', $html);
        $this->assertStringContainsString('buildMarkers([]);', $html);
    }

    public function test_center_coordinates_are_ignored_outside_custom_mode()
    {
        $markers = $this->two_markers_on_the_equator();
        $expected = $this->center_attribute($this->render_admin(['marker_list' => $markers]));

        foreach (['midpoint', 'fit'] as $mode) {
            $html = $this->render_admin(['marker_list' => $markers, 'center_mode' => $mode, 'center_coords' => '1,2']);
            $this->assertSame($expected, $this->center_attribute($html));
        }
    }

    public function test_custom_center_input_never_reaches_the_page_unparsed()
    {
        $html = $this->render_admin(['center_mode' => 'custom', 'center_coords' => '1,2"><script>x</script>']);

        $this->assertStringNotContainsString('<script>x', $html);
        $this->assertStringNotContainsString('1,2"', $html);
    }

    public function test_map_view_carries_only_the_normalised_mode_padding_and_bounds()
    {
        $view = $this->js_value($this->render_admin(['center_mode' => 'evil']), 'mapView');
        $this->assertSame(['mode', 'padding', 'bounds'], array_keys($view));
        $this->assertSame('midpoint', $view['mode']);

        $padding = function ($saved) {
            return $this->js_value($this->render_admin(['center_mode' => 'fit', 'fit_padding' => $saved]), 'mapView')['padding'];
        };
        $this->assertSame(200, $padding(['unit' => 'px', 'size' => 500]));
        $this->assertSame(0, $padding(['unit' => 'px', 'size' => -5]));
        $this->assertSame(50, $padding(null));
        $this->assertSame(50, $padding(['unit' => 'px', 'size' => 'abc']));
    }

    public function test_fit_bounds_cover_every_visible_marker()
    {
        $html = $this->render_admin([
            'marker_list' => [
                $this->marker(['marker_coords' => '10,20']),
                $this->marker(['marker_coords' => '-5,40']),
                $this->marker(['marker_coords' => '30,-10']),
                $this->marker(['marker_coords' => '80,170', 'marker_visible' => '']),
            ],
            'center_mode' => 'fit',
        ]);

        $this->assertEquals([[-5, -10], [30, 40]], $this->js_value($html, 'mapView')['bounds']);
    }

    public function test_display_settings_allowlist_is_unchanged_by_the_center_settings()
    {
        $display = $this->js_value($this->render_admin([
            'center_mode' => 'fit',
            'center_coords' => '1,2',
            'fit_padding' => ['unit' => 'px', 'size' => 20],
        ]), 'displaySettings');

        $this->assertSame([], array_diff(array_keys($display), ['zoom', 'zoom_tablet', 'zoom_mobile', 'breakpoints']));
    }

    public function test_fit_mode_script_fits_bounds_capped_at_the_zoom_level_and_refits_on_resize()
    {
        $js = $this->render_admin(['center_mode' => 'fit']);

        $this->assertMatchesRegularExpression('/fitBounds\(mapView\.bounds, \{padding: \[mapView\.padding, mapView\.padding\], maxZoom: zoomLevel, animate: false\}\)/', $js);
        $this->assertStringContainsString('size.x <= 0 || size.y <= 0', $js);
        $this->assertMatchesRegularExpression('/map\.invalidateSize\(\);\s*refitView\(\);/', $js);
    }

    public function test_fit_mode_stops_refitting_after_the_user_interacts()
    {
        $js = $this->render_admin(['center_mode' => 'fit']);

        $this->assertStringContainsString('userInteracted', $js);
        $this->assertStringContainsString("['pointerdown', 'wheel', 'keydown']", $js);
        $this->assertStringContainsString('{once: true, passive: true}', $js);
    }

    private function area_marker(array $overrides = [])
    {
        return $this->marker(array_merge([
            'marker_type' => 'area',
            'area_corner_1' => '45.9,7.0',
            'area_corner_2' => '44.0,9.5',
        ], $overrides));
    }

    private function areas_for(array $overrides, array $settings = [])
    {
        $html = $this->render_admin(array_merge(['marker_list' => [$this->area_marker($overrides)]], $settings));

        return $this->js_value($html, 'markers');
    }

    public function test_marker_type_defaults_to_point_and_shape_fields_show_only_for_their_types()
    {
        $fields = $this->make_widget()->get_controls('marker_list')['fields'];
        $shapes = ['area', 'circle', 'polygon', 'line', 'geojson', 'route'];

        $this->assertSame('point', $fields['marker_type']['default']);
        $this->assertSame(['point', 'area', 'circle', 'polygon', 'line', 'geojson', 'route'], array_keys($fields['marker_type']['options']));
        $this->assertSame('Rectangle', $fields['marker_type']['options']['area']);
        $this->assertSame(['marker_type!' => $shapes], $fields['marker_location']['condition']);
        $this->assertSame(['marker_type!' => $shapes], $fields['marker_coords']['condition']);
        $this->assertSame(['marker_type' => 'area'], $fields['area_corner_1']['condition']);
        $this->assertSame(['marker_type' => 'area'], $fields['area_corner_2']['condition']);
        $this->assertSame(['marker_type' => 'circle'], $fields['circle_center']['condition']);
        $this->assertSame(['marker_type' => 'circle'], $fields['circle_radius']['condition']);
        $this->assertSame(['marker_type' => ['polygon', 'line']], $fields['shape_points']['condition']);
        $this->assertSame(['marker_type' => $shapes], $fields['area_border_color']['condition']);
        $this->assertFalse($fields['area_border_color']['global']['active']);
        $this->assertFalse($fields['area_fill_color']['global']['active']);
    }

    public function test_fill_controls_are_hidden_for_lines()
    {
        $fields = $this->make_widget()->get_controls('marker_list')['fields'];

        $this->assertSame(['marker_type' => ['area', 'circle', 'polygon', 'geojson']], $fields['area_fill_color']['condition']);
        $this->assertSame(['marker_type' => ['area', 'circle', 'polygon', 'geojson']], $fields['area_fill_opacity']['condition']);
        $this->assertSame(['marker_type' => 'geojson'], $fields['geojson_data']['condition']);
        $this->assertSame(1, $fields['circle_radius']['min']);
    }

    public function test_saved_marker_without_a_type_renders_exactly_as_before()
    {
        $markers = $this->markers_for([]);

        $this->assertSame(['marker', 'lat', 'lng'], array_keys($markers[0]));
    }

    public function test_area_corners_are_normalised_in_either_order()
    {
        $forward = $this->areas_for([]);
        $reversed = $this->areas_for(['area_corner_1' => '44.0,9.5', 'area_corner_2' => '45.9,7.0']);

        $this->assertEquals([[44.0, 7.0], [45.9, 9.5]], $forward[0]['bounds']);
        $this->assertEquals($forward[0]['bounds'], $reversed[0]['bounds']);
        $this->assertEqualsWithDelta(44.95, $forward[0]['lat'], 1e-9);
        $this->assertEqualsWithDelta(8.25, $forward[0]['lng'], 1e-9);
    }

    /**
     * @dataProvider invalid_coordinates
     */
    public function test_invalid_area_corner_skips_the_area($coords)
    {
        $this->assertSame([], $this->areas_for(['area_corner_2' => $coords]));
        $this->assertSame([], $this->areas_for(['area_corner_1' => $coords]));
    }

    public function test_zero_area_rectangle_is_skipped()
    {
        $this->assertSame([], $this->areas_for(['area_corner_2' => '44.0,7.0']));
        $this->assertSame([], $this->areas_for(['area_corner_2' => '45.9,9.5', 'area_corner_1' => '45.9,7.0']));
    }

    public function test_area_colours_are_sanitised_and_default_to_leaflet_blue()
    {
        $this->assertSame('#3388ff', $this->areas_for(['area_border_color' => 'red;}<x>'])[0]['area']['color']);
        $this->assertSame('#3388ff', $this->areas_for([])[0]['area']['fillColor']);
        $this->assertSame('#d63638', $this->areas_for(['area_border_color' => '#d63638'])[0]['area']['color']);
        $this->assertSame('#d6363880', $this->areas_for(['area_border_color' => '#d6363880'])[0]['area']['color']);
        $this->assertSame('rgba(1, 2, 3, 0.5)', $this->areas_for(['area_fill_color' => 'rgba(1, 2, 3, 0.5)'])[0]['area']['fillColor']);
    }

    public function test_area_fill_opacity_is_clamped()
    {
        $opacity = function ($saved) {
            return $this->areas_for(['area_fill_opacity' => $saved])[0]['area']['fillOpacity'];
        };

        $this->assertEquals(1, $opacity(['unit' => 'px', 'size' => 1.5]));
        $this->assertEquals(0, $opacity(['unit' => 'px', 'size' => -1]));
        $this->assertEquals(0.35, $opacity(['unit' => 'px', 'size' => 0.35]));
        $this->assertEquals(0.2, $opacity(null));
    }

    public function test_area_description_is_sanitised_like_markers()
    {
        $markers = $this->areas_for(['marker_description' => '<script>alert(1)</script><b>ok</b>']);

        $this->assertSame('alert(1)<b>ok</b>', $markers[0]['marker']['marker_description']);
    }

    public function test_areas_extend_fit_bounds_by_both_corners_and_the_midpoint_by_their_centre()
    {
        $html = $this->render_admin([
            'marker_list' => [$this->area_marker(), $this->marker(['marker_coords' => '40,12'])],
            'center_mode' => 'fit',
        ]);

        $this->assertEquals([[40, 7.0], [45.9, 12]], $this->js_value($html, 'mapView')['bounds']);

        $midpoint = $this->render_admin(['marker_list' => [$this->area_marker()]]);
        $this->assertEqualsWithDelta(44.95, (float) explode(',', $this->center_attribute($midpoint))[0], 0.01);
    }

    public function test_script_draws_areas_as_rectangles_with_the_shared_popup_builder()
    {
        $js = $this->render_admin();

        $this->assertStringContainsString('L.rectangle(item.bounds, item.area)', $js);
        $this->assertStringContainsString('this.bounds ? buildShape(this) : L.marker(', $js);
        $this->assertStringContainsString('L.marker([this.lat, this.lng], markerOptions)', $js);
        $this->assertSame(1, substr_count($js, 'bindPopup(tooltipContent);'));
    }

    private function post_with_coordinates($title, array $meta, array $args = [])
    {
        $id = self::factory()->post->create(array_merge(['post_title' => $title, 'post_status' => 'publish'], $args));
        foreach ($meta as $key => $value) {
            update_post_meta($id, $key, $value);
        }

        return $id;
    }

    private function posts_markers_for(array $settings = [])
    {
        $html = $this->render_admin(array_merge([
            'marker_source' => 'posts',
            'posts_post_type' => 'post',
            'posts_coords_field' => 'geo',
            'posts_orderby' => 'title',
            'posts_order' => 'ASC',
        ], $settings));

        return $this->js_value($html, 'markers');
    }

    private function marker_titles(array $markers)
    {
        return array_map(function ($entry) {
            return $entry['marker']['marker_title'];
        }, $markers);
    }

    public function test_marker_source_defaults_to_manual_and_hides_the_repeater_for_posts()
    {
        $widget = $this->make_widget();

        $this->assertSame('manual', $widget->get_controls('marker_source')['default']);
        $this->assertSame(['manual', 'posts'], array_keys($widget->get_controls('marker_source')['options']));
        $this->assertSame(['marker_source!' => 'posts'], $widget->get_controls('marker_list')['condition']);
    }

    public function test_posts_controls_show_only_for_posts()
    {
        $controls = $this->make_widget()->get_controls();

        foreach (['posts_post_type', 'posts_taxonomy', 'posts_per_page', 'posts_orderby', 'posts_order', 'posts_coords_format', 'posts_description', 'posts_marker_behavior', 'posts_show_button'] as $id) {
            $this->assertSame('posts', $controls[$id]['condition']['marker_source'], $id);
        }
        $this->assertSame('single', $controls['posts_coords_field']['condition']['posts_coords_format']);
        $this->assertSame('separate', $controls['posts_lat_field']['condition']['posts_coords_format']);
        $this->assertSame('meta', $controls['posts_description_field']['condition']['posts_description']);
    }

    public function test_manual_source_ignores_posts()
    {
        $this->post_with_coordinates('Sapporo', ['geo' => '43.06,141.35']);

        $markers = $this->markers_for(['marker_title' => 'Manual only']);

        $this->assertSame(['Manual only'], $this->marker_titles($markers));
    }

    public function test_published_posts_with_coordinates_become_markers()
    {
        $this->post_with_coordinates('Beta', ['geo' => '12,22']);
        $this->post_with_coordinates('Alpha', ['geo' => ' 10 , 20 ']);

        $markers = $this->posts_markers_for();

        $this->assertSame(['Alpha', 'Beta'], $this->marker_titles($markers));
        $this->assertEquals(10.0, $markers[0]['lat']);
        $this->assertIsNotString($markers[0]['lat']);
        $this->assertEquals(20.0, $markers[0]['lng']);
        $this->assertIsNotString($markers[0]['lng']);
    }

    public function test_separate_latitude_and_longitude_fields_are_supported()
    {
        $this->post_with_coordinates('Split', ['lat_field' => '16', 'lng_field' => '26']);

        $markers = $this->posts_markers_for(['posts_coords_format' => 'separate', 'posts_lat_field' => 'lat_field', 'posts_lng_field' => 'lng_field']);

        $this->assertSame(['Split'], $this->marker_titles($markers));
        $this->assertEquals(16.0, $markers[0]['lat']);
        $this->assertIsNotString($markers[0]['lat']);
        $this->assertEquals(26.0, $markers[0]['lng']);
        $this->assertIsNotString($markers[0]['lng']);
    }

    public function test_acf_style_array_coordinates_are_supported()
    {
        $this->post_with_coordinates('Map field', ['geo' => ['lat' => 45.1, 'lng' => 7.6, 'address' => 'x']]);

        $markers = $this->posts_markers_for();

        $this->assertSame(45.1, $markers[0]['lat']);
        $this->assertSame(7.6, $markers[0]['lng']);
    }

    public function test_drafts_private_and_password_protected_posts_are_excluded()
    {
        $this->post_with_coordinates('Public', ['geo' => '1,1']);
        $this->post_with_coordinates('Draft', ['geo' => '2,2'], ['post_status' => 'draft']);
        $this->post_with_coordinates('Private', ['geo' => '3,3'], ['post_status' => 'private']);
        $this->post_with_coordinates('Protected', ['geo' => '4,4'], ['post_password' => 'secret']);

        $this->assertSame(['Public'], $this->marker_titles($this->posts_markers_for()));
    }

    public function test_posts_with_invalid_or_missing_coordinates_are_skipped()
    {
        $this->post_with_coordinates('Good', ['geo' => '1,1']);
        $this->post_with_coordinates('Bad', ['geo' => 'nonsense']);
        $this->post_with_coordinates('Out of range', ['geo' => '95,10']);
        $this->post_with_coordinates('Missing', []);

        $this->assertSame(['Good'], $this->marker_titles($this->posts_markers_for()));
    }

    public function test_protected_or_malformed_field_names_are_refused()
    {
        $this->post_with_coordinates('Hidden', ['_secret' => '1,1', 'a b' => '1,1', "x' OR 1" => '1,1']);

        foreach (['_secret', 'a b', "x' OR 1"] as $field) {
            $this->assertSame([], $this->posts_markers_for(['posts_coords_field' => $field]), $field);
        }
    }

    public function test_unknown_post_type_yields_no_markers()
    {
        $this->post_with_coordinates('Any', ['geo' => '1,1']);

        $this->assertSame([], $this->posts_markers_for(['posts_post_type' => 'nonexistent']));
        $this->assertSame([], $this->posts_markers_for(['posts_post_type' => 'attachment']));
    }

    public function test_attachment_and_elementor_library_are_not_offered()
    {
        $options = array_keys($this->make_widget()->get_controls('posts_post_type')['options']);

        $this->assertContains('post', $options);
        $this->assertNotContains('attachment', $options);
        $this->assertNotContains('elementor_library', $options);
    }

    public function test_taxonomy_terms_filter_the_posts()
    {
        $in = $this->post_with_coordinates('In term', ['geo' => '1,1']);
        $this->post_with_coordinates('Outside', ['geo' => '2,2']);
        $term = self::factory()->category->create(['slug' => 'wanted']);
        wp_set_object_terms($in, [$term], 'category');

        $markers = $this->posts_markers_for(['posts_taxonomy' => 'category', 'posts_terms' => 'wanted, ']);

        $this->assertSame(['In term'], $this->marker_titles($markers));
    }

    public function test_number_of_posts_is_capped_at_100()
    {
        $captured = [];
        $capture = function ($query) use (&$captured) {
            $captured = $query->query_vars;
        };
        add_action('pre_get_posts', $capture);

        $this->posts_markers_for(['posts_per_page' => 500]);

        remove_action('pre_get_posts', $capture);
        $this->assertSame(100, $captured['posts_per_page']);
        $this->assertTrue($captured['no_found_rows']);
        $this->assertFalse($captured['update_post_term_cache']);
        $this->assertNotFalse($captured['update_post_meta_cache']);
    }

    public function test_post_title_is_stripped_and_decoded_once()
    {
        $this->post_with_coordinates('Tom &amp; Jerry <b>x</b>', ['geo' => '1,1']);

        $this->assertSame(['Tom & Jerry x'], $this->marker_titles($this->posts_markers_for()));
    }

    public function test_description_uses_only_a_manual_excerpt()
    {
        $this->post_with_coordinates('Auto', ['geo' => '1,1'], ['post_content' => 'Long content that must never become a description', 'post_excerpt' => '']);
        $this->post_with_coordinates('Manual', ['geo' => '2,2'], ['post_excerpt' => '<strong>a</strong><script>b</script>']);

        $markers = $this->posts_markers_for();

        $this->assertSame('', $markers[0]['marker']['marker_description']);
        $this->assertSame('<strong>a</strong>b', $markers[1]['marker']['marker_description']);
    }

    public function test_description_meta_field_is_sanitised()
    {
        $this->post_with_coordinates('Meta', ['geo' => '1,1', 'blurb' => '<em>hi</em><script>x</script>']);

        $markers = $this->posts_markers_for(['posts_description' => 'meta', 'posts_description_field' => 'blurb']);

        $this->assertSame('<em>hi</em>x', $markers[0]['marker']['marker_description']);
    }

    public function test_post_button_links_to_the_permalink_in_the_same_tab()
    {
        $id = $this->post_with_coordinates('Linked', ['geo' => '1,1']);

        $marker = $this->posts_markers_for(['posts_show_button' => 'yes', 'posts_button_text' => 'Open'])[0]['marker'];

        $this->assertSame(get_permalink($id), $marker['button_url']);
        $this->assertSame('_self', $marker['button_url_target']);
        $this->assertSame('yes', $marker['show_button']);
        $this->assertSame('Open', $marker['button_text']);
    }

    public function test_posts_markers_feed_fit_bounds()
    {
        $this->post_with_coordinates('One', ['geo' => '10,20']);
        $this->post_with_coordinates('Two', ['geo' => '14,24']);

        $html = $this->render_admin([
            'marker_source' => 'posts',
            'posts_coords_field' => 'geo',
            'center_mode' => 'fit',
        ]);

        $this->assertEquals([[10, 20], [14, 24]], $this->js_value($html, 'mapView')['bounds']);
    }

    public function test_script_gives_non_image_markers_an_accessible_name()
    {
        $js = $this->render_admin(['icon_type' => 'fontawesome']);

        $this->assertStringContainsString("const markerLabel = " . wp_json_encode('Map marker') . ';', $js);
        $this->assertStringContainsString("'IMG' !== markerElement.tagName", $js);
        $this->assertStringContainsString("markerElement.setAttribute('aria-label', this.marker.marker_title || markerLabel);", $js);
    }

    private function shape_marker($type, array $overrides = [])
    {
        return $this->marker(array_merge(['marker_type' => $type], $overrides));
    }

    private function shapes_for($type, array $overrides)
    {
        $html = $this->render_admin(['marker_list' => [$this->shape_marker($type, $overrides)]]);

        return $this->js_value($html, 'markers');
    }

    public function test_circle_renders_its_centre_radius_and_bounds()
    {
        $shape = $this->shapes_for('circle', ['circle_center' => ' 45.0 , 9.0 ', 'circle_radius' => '5000'])[0];

        $this->assertSame('circle', $shape['shape']);
        $this->assertEquals(45.0, $shape['lat']);
        $this->assertIsNotString($shape['lat']);
        $this->assertEquals(9.0, $shape['lng']);
        $this->assertIsNotString($shape['lng']);
        $this->assertEquals(5000.0, $shape['radius']);
        $this->assertIsNotString($shape['radius']);
        $this->assertEqualsWithDelta(45.0 - 5000 / 111194.9, $shape['bounds'][0][0], 0.001);
        $this->assertEqualsWithDelta(45.0 + 5000 / 111194.9, $shape['bounds'][1][0], 0.001);
        $this->assertGreaterThan(9.0, $shape['bounds'][1][1]);
        $this->assertSame(['color', 'fillColor', 'fillOpacity'], array_keys($shape['area']));
    }

    public function invalid_radii()
    {
        return ['zero' => ['0'], 'negative' => ['-5'], 'empty' => ['']];
    }

    /**
     * @dataProvider invalid_radii
     */
    public function test_circle_with_an_invalid_radius_or_centre_is_skipped($radius)
    {
        $this->assertSame([], $this->shapes_for('circle', ['circle_center' => '45,9', 'circle_radius' => $radius]));
        $this->assertSame([], $this->shapes_for('circle', ['circle_center' => 'abc', 'circle_radius' => '100']));
    }

    public function test_circle_radius_is_capped()
    {
        $shape = $this->shapes_for('circle', ['circle_center' => '0,0', 'circle_radius' => '99999999'])[0];

        $this->assertEquals(1000000.0, $shape['radius']);
        $this->assertIsNotString($shape['radius']);
        $this->assertLessThanOrEqual(180, $shape['bounds'][1][1]);
    }

    public function test_polygon_renders_valid_points_and_skips_invalid_lines()
    {
        $text = "45.0, 7.0\nnot a point\n45.0, 9.0\r\n\n43.0, 8.0\n95, 10";

        $shape = $this->shapes_for('polygon', ['shape_points' => $text])[0];

        $this->assertSame('polygon', $shape['shape']);
        $this->assertEquals([[45.0, 7.0], [45.0, 9.0], [43.0, 8.0]], $shape['points']);
        $this->assertEquals([[43.0, 7.0], [45.0, 9.0]], $shape['bounds']);
        $this->assertEqualsWithDelta(44.0, $shape['lat'], 1e-9);
        $this->assertEqualsWithDelta(8.0, $shape['lng'], 1e-9);
    }

    public function test_polygon_needs_three_valid_points_and_line_needs_two()
    {
        $this->assertSame([], $this->shapes_for('polygon', ['shape_points' => "1,1\n2,2\nbad"]));
        $this->assertSame([], $this->shapes_for('polygon', ['shape_points' => '']));
        $this->assertSame([], $this->shapes_for('line', ['shape_points' => '1,1']));
        $this->assertSame('line', $this->shapes_for('line', ['shape_points' => "1,1\n2,2"])[0]['shape']);
    }

    public function test_line_is_stroke_only()
    {
        $shape = $this->shapes_for('line', ['shape_points' => "1,1\n2,2", 'area_border_color' => '#d63638'])[0];

        $this->assertSame(['color' => '#d63638', 'weight' => 3], $shape['area']);
    }

    public function test_shape_points_are_capped()
    {
        $lines = [];
        for ($i = 0; $i < 600; $i++) {
            $lines[] = ($i % 80) . ',' . ($i % 170);
        }

        $shape = $this->shapes_for('line', ['shape_points' => implode("\n", $lines)])[0];

        $this->assertCount(500, $shape['points']);
    }

    public function test_shapes_extend_fit_bounds_and_the_midpoint_by_their_centre()
    {
        $html = $this->render_admin([
            'marker_list' => [
                $this->shape_marker('circle', ['circle_center' => '0,0', 'circle_radius' => '111195']),
                $this->shape_marker('line', ['shape_points' => "10,20\n12,30"]),
            ],
            'center_mode' => 'fit',
        ]);

        $bounds = $this->js_value($html, 'mapView')['bounds'];
        $this->assertEqualsWithDelta(-1.0, $bounds[0][0], 0.01);
        $this->assertEqualsWithDelta(-1.0, $bounds[0][1], 0.01);
        $this->assertEqualsWithDelta(12.0, $bounds[1][0], 0.01);
        $this->assertEqualsWithDelta(30.0, $bounds[1][1], 0.01);
        $circle = $this->render_admin(['marker_list' => [$this->shape_marker('circle', ['circle_center' => '10,20', 'circle_radius' => '10'])]]);
        $this->assertSame('10,20', $this->center_attribute($circle));
    }

    public function test_raw_shape_input_never_reaches_the_script()
    {
        $html = $this->render_admin(['marker_list' => [
            $this->shape_marker('polygon', ['shape_points' => "1,1\n2,2\n3,1\n</script><script>alert(1)</script>"]),
            $this->shape_marker('circle', ['circle_center' => '1,1', 'circle_radius' => '5']),
            $this->route_marker(['route_waypoints' => "1,1\n</script><script>alert(1)</script>", 'shape_stroke_width' => ['unit' => 'px', 'size' => 4]]),
        ]]);
        $markers = $this->js_value($html, 'markers');

        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertCount(3, $markers);
        foreach ($markers as $entry) {
            foreach (['shape_points', 'circle_center', 'circle_radius', 'area_corner_1', 'area_corner_2', 'route_start', 'route_end', 'route_waypoints', 'route_mode', 'route_pins', 'route_summary', 'shape_stroke_width'] as $key) {
                $this->assertArrayNotHasKey($key, $entry['marker']);
            }
        }
    }

    public function test_script_builds_every_shape_through_the_shared_popup_builder()
    {
        $js = $this->render_admin();

        $this->assertStringContainsString('L.circle([item.lat, item.lng], Object.assign({radius: item.radius}, item.area))', $js);
        $this->assertStringContainsString('L.polygon(item.points, item.area)', $js);
        $this->assertStringContainsString('L.polyline(item.points, item.area)', $js);
    }

    private function geojson_for($geojson, array $overrides = [])
    {
        $text = is_string($geojson) ? $geojson : wp_json_encode($geojson);

        return $this->shapes_for('geojson', array_merge(['geojson_data' => $text], $overrides));
    }

    private function geojson_polygon()
    {
        return ['type' => 'Polygon', 'coordinates' => [[[9.0, 45.0], [10.0, 45.0], [10.0, 46.0], [9.0, 45.0]]]];
    }

    public function test_geojson_feature_collection_feature_and_bare_geometry_are_read()
    {
        $polygon = $this->geojson_polygon();
        $feature = ['type' => 'Feature', 'properties' => [], 'geometry' => $polygon];
        $collection = ['type' => 'FeatureCollection', 'features' => [
            $feature,
            ['type' => 'Feature', 'properties' => [], 'geometry' => ['type' => 'LineString', 'coordinates' => [[11.0, 40.0], [12.0, 41.0]]]],
            ['type' => 'Feature', 'properties' => [], 'geometry' => ['type' => 'Point', 'coordinates' => [13.0, 42.0]]],
        ]];

        foreach ([$polygon, $feature] as $input) {
            $shape = $this->geojson_for($input)[0];
            $this->assertSame('geojson', $shape['shape']);
            $this->assertCount(1, $shape['polygons']);
        }

        $shape = $this->geojson_for($collection)[0];
        $this->assertCount(1, $shape['polygons']);
        $this->assertCount(1, $shape['lines']);
        $this->assertEquals([[42.0, 13.0]], $shape['points']);
        $this->assertEquals([[40.0, 9.0], [46.0, 13.0]], $shape['bounds']);
    }

    public function test_geojson_longitude_latitude_order_is_converted_to_leaflet_order()
    {
        $shape = $this->geojson_for(['type' => 'Point', 'coordinates' => [7.6, 45.1, 300]])[0];

        $this->assertEquals([[45.1, 7.6]], $shape['points']);
        $this->assertEqualsWithDelta(45.1, $shape['lat'], 1e-9);
        $this->assertEqualsWithDelta(7.6, $shape['lng'], 1e-9);
    }

    public function test_geojson_multi_geometries_are_read()
    {
        $shape = $this->geojson_for(['type' => 'GeometryCollection', 'geometries' => []]);
        $this->assertSame([], $shape);

        $multi = $this->geojson_for(['type' => 'FeatureCollection', 'features' => [
            ['type' => 'Feature', 'geometry' => ['type' => 'MultiPoint', 'coordinates' => [[1, 2], [3, 4]]]],
            ['type' => 'Feature', 'geometry' => ['type' => 'MultiLineString', 'coordinates' => [[[1, 1], [2, 2]], [[3, 3], [4, 4]]]]],
            ['type' => 'Feature', 'geometry' => ['type' => 'MultiPolygon', 'coordinates' => [[[[0, 0], [1, 0], [1, 1], [0, 0]]], [[[5, 5], [6, 5], [6, 6], [5, 5]]]]]],
        ]])[0];

        $this->assertCount(2, $multi['points']);
        $this->assertCount(2, $multi['lines']);
        $this->assertCount(2, $multi['polygons']);
    }

    public function invalid_geojson_positions()
    {
        return [
            'latitude out of range' => [[10, 95]],
            'longitude out of range' => [[190, 10]],
            'non numeric' => [['a', 'b']],
            'single value' => [[10]],
            'not an array' => ['10,20'],
        ];
    }

    /**
     * @dataProvider invalid_geojson_positions
     */
    public function test_geojson_with_invalid_coordinates_is_skipped($position)
    {
        $this->assertSame([], $this->geojson_for(['type' => 'Point', 'coordinates' => $position]));
    }

    public function test_unsupported_and_malformed_geojson_is_skipped_without_errors()
    {
        foreach (['{"type": "Feature", "geometry": null}', '{"type": "Topology"}', 'not json', '', '[1,2,3]', '"string"', '{"type":"LineString","coordinates":[[1,1]]}', '{"type":"Polygon","coordinates":[[[0,0],[1,1]]]}'] as $text) {
            $this->assertSame([], $this->geojson_for($text), $text);
        }
        $this->assertSame([], $this->shapes_for('geojson', ['geojson_data' => ['x']]));
    }

    public function test_geojson_unsupported_geometries_are_ignored_but_supported_ones_remain()
    {
        $shape = $this->geojson_for(['type' => 'FeatureCollection', 'features' => [
            ['type' => 'Feature', 'geometry' => ['type' => 'GeometryCollection', 'geometries' => [['type' => 'Point', 'coordinates' => [1, 1]]]]],
            ['type' => 'Feature', 'geometry' => ['type' => 'Point', 'coordinates' => [2, 3]]],
        ]])[0];

        $this->assertEquals([[3.0, 2.0]], $shape['points']);
    }

    public function test_geojson_byte_and_point_caps_skip_the_item()
    {
        $positions = [];
        for ($i = 0; $i < 5001; $i++) {
            $positions[] = [($i % 170), ($i % 80)];
        }
        $this->assertSame([], $this->geojson_for(['type' => 'LineString', 'coordinates' => $positions]));

        $ok = array_slice($positions, 0, 5000);
        $this->assertCount(5000, $this->geojson_for(['type' => 'LineString', 'coordinates' => $ok])[0]['lines'][0]);

        $padded = '{"type":"Point","coordinates":[1,2]}' . str_repeat(' ', 204800);
        $this->assertSame([], $this->geojson_for($padded));
    }

    public function test_geojson_properties_never_reach_the_script()
    {
        $html = $this->render_admin(['marker_list' => [$this->shape_marker('geojson', ['geojson_data' => wp_json_encode([
            'type' => 'Feature',
            'properties' => ['name' => '<img src=x onerror=alert(1)>', 'description' => 'SECRET-PROPERTY'],
            'geometry' => ['type' => 'Point', 'coordinates' => [1, 2]],
        ])])]]);

        $this->assertStringNotContainsString('SECRET-PROPERTY', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertArrayNotHasKey('geojson_data', $this->js_value($html, 'markers')[0]['marker']);
    }

    public function test_geojson_extends_fit_bounds_and_the_midpoint()
    {
        $html = $this->render_admin([
            'marker_list' => [$this->shape_marker('geojson', ['geojson_data' => wp_json_encode($this->geojson_polygon())]), $this->marker(['marker_coords' => '50,20'])],
            'center_mode' => 'fit',
        ]);

        $this->assertEquals([[45.0, 9.0], [50.0, 20.0]], $this->js_value($html, 'mapView')['bounds']);
        $alone = $this->shapes_for('geojson', ['geojson_data' => wp_json_encode($this->geojson_polygon())])[0];
        $this->assertEqualsWithDelta(45.5, $alone['lat'], 1e-9);
        $this->assertEqualsWithDelta(9.5, $alone['lng'], 1e-9);
    }

    public function test_script_draws_geojson_as_polygons_lines_and_circle_markers_in_one_group()
    {
        $js = $this->render_admin();

        $this->assertStringContainsString('L.featureGroup(', $js);
        $this->assertStringContainsString('L.polygon(rings, item.area)', $js);
        $this->assertStringContainsString('L.polyline(line, {color: item.area.color})', $js);
        $this->assertStringContainsString('L.circleMarker(point, Object.assign({radius: 6}, item.area))', $js);
    }

    private function route_marker(array $overrides = [])
    {
        return $this->shape_marker('route', array_merge([
            'marker_title' => 'Milan to Bergamo',
            'route_start' => '45.4642, 9.1900',
            'route_end' => '45.7037, 9.6625',
        ], $overrides));
    }

    private function route_for(array $overrides = [], array $settings = [])
    {
        $html = $this->render_admin($settings + ['marker_list' => [$this->route_marker($overrides)]]);

        return $this->js_value($html, 'markers')[0] ?? null;
    }

    private function count_http_requests()
    {
        $calls = new ArrayObject();
        add_filter('pre_http_request', static function ($pre, $args, $url) use ($calls) {
            $calls[] = $url;

            return new WP_Error('blocked', 'Network is stubbed in tests.');
        }, 10, 3);

        return $calls;
    }

    public function test_route_type_shows_its_fields_and_hides_point_and_fill_fields()
    {
        $fields = $this->make_widget()->get_controls('marker_list')['fields'];

        $this->assertSame('Route', $fields['marker_type']['options']['route']);
        foreach (['route_start', 'route_end', 'route_waypoints', 'route_mode', 'route_pins', 'route_summary'] as $id) {
            $this->assertSame(['marker_type' => 'route'], $fields[$id]['condition'], $id);
        }
        $this->assertSame('drive', $fields['route_mode']['default']);
        $this->assertSame(['drive', 'walk', 'bicycle'], array_keys($fields['route_mode']['options']));
        $this->assertSame('yes', $fields['route_pins']['default']);
        $this->assertNotSame('yes', $fields['route_summary']['default']);
        $this->assertContains('route', $fields['area_border_color']['condition']['marker_type']);
        $this->assertNotContains('route', $fields['area_fill_color']['condition']['marker_type']);
        $this->assertNotContains('route', $fields['area_fill_opacity']['condition']['marker_type']);
        $this->assertContains('route', $fields['marker_coords']['condition']['marker_type!']);
    }

    public function test_stroke_width_shows_for_lines_and_routes_only()
    {
        $control = $this->make_widget()->get_controls('marker_list')['fields']['shape_stroke_width'];

        $this->assertSame(['marker_type' => ['line', 'route']], $control['condition']);
        $this->assertSame(3, $control['default']['size']);
        $this->assertSame(12, $control['range']['px']['max']);
    }

    public function test_route_key_note_is_registered_only_without_a_geoapify_key()
    {
        $without = $this->make_widget()->get_controls('marker_list')['fields'];
        $this->assertSame(['marker_type' => 'route'], $without['route_key_note']['condition']);
        $this->assertStringContainsString('options-general.php?page=osm-map-elementor', $without['route_key_note']['raw']);

        update_option('osm_widget', ['geoapify_key' => 'test-key']);
        $widget = $this->make_widget();
        Plugin::$instance->controls_manager->delete_stack($widget);
        $with = $this->make_widget()->get_controls('marker_list')['fields'];
        $this->assertArrayNotHasKey('route_key_note', $with);
        Plugin::$instance->controls_manager->delete_stack($widget);
    }

    /**
     * @dataProvider invalid_coordinates
     */
    public function test_route_with_an_invalid_start_or_end_is_skipped_without_requests($coords)
    {
        $calls = $this->count_http_requests();
        update_option('osm_widget', ['geoapify_key' => 'test-key']);

        $this->assertNull($this->route_for(['route_start' => $coords]));
        $this->assertNull($this->route_for(['route_end' => $coords]));
        $this->assertCount(0, $calls);
    }

    public function test_route_stops_are_capped_and_invalid_lines_dropped()
    {
        $stops = [];
        for ($i = 0; $i < 12; $i++) {
            $stops[] = (45.5 + $i / 100) . ',9.3';
        }
        $stops[1] = 'not a stop';
        $stops[3] = '99,9';

        $route = $this->route_for(['route_waypoints' => implode("\n", $stops)]);

        $this->assertCount(12, $route['lines'][0]);
        $this->assertSame([45.4642, 9.19], $route['lines'][0][0]);
        $this->assertSame([45.7037, 9.6625], $route['lines'][0][11]);
    }

    public function test_route_without_a_key_draws_a_straight_line_through_its_stops_without_requests()
    {
        $calls = $this->count_http_requests();

        $route = $this->route_for(['route_waypoints' => "45.4669, 9.1817\n45.4721, 9.1795"]);

        $this->assertSame('route', $route['shape']);
        $this->assertFalse($route['routed']);
        $this->assertEquals([[[45.4642, 9.19], [45.4669, 9.1817], [45.4721, 9.1795], [45.7037, 9.6625]]], $route['lines']);
        $this->assertCount(0, $calls);
    }

    public function test_route_pins_follow_the_switch()
    {
        $this->assertEquals([[45.4642, 9.19], [45.7037, 9.6625]], $this->route_for()['pins']);
        $this->assertSame([], $this->route_for(['route_pins' => ''])['pins']);
    }

    public function test_route_style_has_the_border_colour_and_a_clamped_width_but_no_fill()
    {
        $default = $this->route_for(['area_border_color' => '#d63638'])['area'];
        $wide = $this->route_for(['shape_stroke_width' => ['unit' => 'px', 'size' => 99]])['area'];

        $this->assertSame(['color' => '#d63638', 'weight' => 3], $default);
        $this->assertSame(12, $wide['weight']);
    }

    public function test_route_bounds_feed_fit_and_its_centre_feeds_the_midpoint()
    {
        $html = $this->render_admin([
            'marker_list' => [$this->route_marker(), $this->marker(['marker_coords' => '40,12'])],
            'center_mode' => 'fit',
        ]);

        $this->assertEquals([[40, 9.19], [45.7037, 12]], $this->js_value($html, 'mapView')['bounds']);
        $route_only = $this->render_admin(['marker_list' => [$this->route_marker()]]);
        [$lat, $lng] = array_map('floatval', explode(',', $this->center_attribute($route_only)));
        $this->assertEqualsWithDelta(45.58395, $lat, 1e-6);
        $this->assertEqualsWithDelta(9.42625, $lng, 1e-6);
    }

    public function test_saved_non_route_items_are_unchanged()
    {
        $items = [
            ['area', ['area_corner_1' => '45.9,7.0', 'area_corner_2' => '44.0,9.5']],
            ['circle', ['circle_center' => '45,9', 'circle_radius' => '5000']],
            ['polygon', ['shape_points' => "1,1\n2,2\n3,1"]],
            ['geojson', ['geojson_data' => '{"type":"Point","coordinates":[1,2]}']],
        ];

        foreach ($items as [$type, $overrides]) {
            $entry = $this->shapes_for($type, $overrides)[0];
            foreach (['routed', 'pins', 'summary'] as $key) {
                $this->assertArrayNotHasKey($key, $entry, $type);
            }
            $this->assertArrayNotHasKey('weight', $entry['area'], $type);
        }
    }

    public function test_editor_render_explains_a_straight_route_and_the_front_end_does_not()
    {
        $marker = $this->route_marker(['marker_title' => 'Trip <b>one</b>']);

        $editor = $this->render_admin(['marker_list' => [$marker]]);
        $front = $this->render_frontend(['marker_list' => [$marker]]);

        $this->assertStringContainsString('class="osm-map-notice"', $editor);
        $this->assertStringContainsString('Trip &lt;b&gt;one&lt;/b&gt;', $editor);
        $this->assertStringContainsString('Geoapify API key', $editor);
        $this->assertStringNotContainsString('osm-map-notice', $front);
    }

    public function test_script_draws_routes_as_a_group_of_line_and_pins_with_a_dashed_fallback()
    {
        $js = $this->render_admin();

        $this->assertStringContainsString("case 'route':", $js);
        $this->assertStringContainsString("Object.assign({dashArray: '8 8'}, item.area)", $js);
        $this->assertStringContainsString('L.polyline(item.lines', $js);
        $this->assertStringContainsString('const routeStartLabel = ' . wp_json_encode('start') . ';', $js);
        $this->assertStringContainsString('const routeEndLabel = ' . wp_json_encode('end') . ';', $js);
        $this->assertStringContainsString('marker-route-summary', $js);
    }

    private function with_geoapify_key()
    {
        update_option('osm_widget', ['geoapify_key' => 'test-key']);
    }

    private function route_response(array $lnglat_lines, $distance = 51234, $time = 2880, $type = 'MultiLineString')
    {
        return [
            'response' => ['code' => 200, 'message' => 'OK'],
            'headers' => [],
            'cookies' => [],
            'body' => wp_json_encode([
                'type' => 'FeatureCollection',
                'features' => [['type' => 'Feature', 'properties' => ['distance' => $distance, 'time' => $time], 'geometry' => ['type' => $type, 'coordinates' => $lnglat_lines]]],
            ]),
        ];
    }

    private function road_lines()
    {
        return [[[9.19, 45.4642], [9.2000012, 45.47001234], [9.4, 45.6], [9.6625, 45.7037]]];
    }

    private function stub_routing($responder)
    {
        $requests = new ArrayObject();
        add_filter('pre_http_request', static function ($pre, $args, $url) use ($responder, $requests) {
            $requests[] = ['url' => $url, 'args' => $args];

            return $responder($url, $args);
        }, 10, 3);

        return $requests;
    }

    private function route_cache_key($mode, array $stops)
    {
        $rounded = array_map(static function ($point) {
            return [round($point[0], 6), round($point[1], 6)];
        }, $stops);

        return 'osm_route_' . md5(wp_json_encode([$mode, $rounded]));
    }

    private function transient_lifetime($key)
    {
        return (int) get_option('_transient_timeout_' . $key) - time();
    }

    public function test_route_request_targets_geoapify_with_latitude_first_stops_mode_and_key()
    {
        $this->with_geoapify_key();
        $requests = $this->stub_routing(function () {
            return $this->route_response($this->road_lines());
        });

        $this->route_for(['route_mode' => 'walk']);

        $this->assertCount(1, $requests);
        $url = $requests[0]['url'];
        parse_str(wp_parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('api.geoapify.com', wp_parse_url($url, PHP_URL_HOST));
        $this->assertSame('/v1/routing', wp_parse_url($url, PHP_URL_PATH));
        $this->assertSame('45.4642,9.19|45.7037,9.6625', $query['waypoints']);
        $this->assertSame('walk', $query['mode']);
        $this->assertSame('test-key', $query['apiKey']);
        $this->assertLessThanOrEqual(5, $requests[0]['args']['timeout']);
        $this->assertSame(0, $requests[0]['args']['redirection']);
        $this->assertSame(5242880, $requests[0]['args']['limit_response_size']);
    }

    public function test_unknown_route_mode_becomes_drive()
    {
        $this->with_geoapify_key();
        $requests = $this->stub_routing(function () {
            return $this->route_response($this->road_lines());
        });

        $this->route_for(['route_mode' => 'teleport']);

        parse_str(wp_parse_url($requests[0]['url'], PHP_URL_QUERY), $query);
        $this->assertSame('drive', $query['mode']);
    }

    public function test_routed_geometry_is_converted_to_leaflet_order_and_rounded()
    {
        $this->with_geoapify_key();
        $this->stub_routing(function () {
            return $this->route_response($this->road_lines());
        });

        $route = $this->route_for();

        $this->assertTrue($route['routed']);
        $this->assertEquals([[[45.4642, 9.19], [45.47001, 9.2], [45.6, 9.4], [45.7037, 9.6625]]], $route['lines']);
        $this->assertEquals([[45.4642, 9.19], [45.7037, 9.6625]], $route['bounds']);
    }

    public function test_route_summary_shows_distance_and_time_only_when_enabled()
    {
        $this->with_geoapify_key();
        $this->stub_routing(function () {
            return $this->route_response($this->road_lines(), 51234, 2880);
        });

        $this->assertSame('51.2 km · 48 min', $this->route_for(['route_summary' => 'yes'])['summary']);
        $this->assertNull($this->route_for()['summary']);
    }

    public function test_route_summary_uses_hours_for_long_trips_and_ignores_unusable_numbers()
    {
        $this->with_geoapify_key();
        $this->stub_routing(function () {
            return $this->route_response($this->road_lines(), 251234, 11100);
        });
        $this->assertSame('251 km · 3 h 5 min', $this->route_for(['route_summary' => 'yes'])['summary']);

        delete_transient($this->route_cache_key('drive', [[45.4642, 9.19], [45.7037, 9.6625]]));
        remove_all_filters('pre_http_request');
        $this->stub_routing(function () {
            return $this->route_response($this->road_lines(), '<img src=x>', -5);
        });
        $this->assertNull($this->route_for(['route_summary' => 'yes'])['summary']);
    }

    public function test_cached_route_is_drawn_without_a_second_request()
    {
        $this->with_geoapify_key();
        $requests = $this->stub_routing(function () {
            return $this->route_response($this->road_lines());
        });

        $first = $this->route_for(['route_mode' => 'walk']);
        $second = $this->route_for(['route_mode' => 'walk']);
        $this->route_for(['route_mode' => 'bicycle']);

        $this->assertCount(2, $requests);
        $this->assertSame($first['lines'], $second['lines']);
        $this->assertTrue($second['routed']);
    }

    public function test_successful_route_is_cached_for_a_week_and_failures_for_ten_minutes()
    {
        $this->with_geoapify_key();
        $stops = [[45.4642, 9.19], [45.7037, 9.6625]];
        $this->stub_routing(function () {
            return $this->route_response($this->road_lines());
        });
        $this->route_for();
        $this->assertEqualsWithDelta(WEEK_IN_SECONDS, $this->transient_lifetime($this->route_cache_key('drive', $stops)), 60);

        delete_transient($this->route_cache_key('drive', $stops));
        remove_all_filters('pre_http_request');
        $this->stub_routing(static function () {
            return new WP_Error('http_request_failed', 'timeout');
        });
        $this->route_for();
        $this->assertEqualsWithDelta(600, $this->transient_lifetime($this->route_cache_key('drive', $stops)), 60);
    }

    public function failed_route_responses()
    {
        $too_many = [];
        for ($i = 0; $i < 20001; $i++) {
            $too_many[] = [9 + ($i % 1000) / 1000, 45 + ($i % 700) / 1000];
        }
        $status = static function ($code) {
            return ['response' => ['code' => $code], 'headers' => [], 'cookies' => [], 'body' => '{}'];
        };
        $ok = static function ($body) {
            return ['response' => ['code' => 200], 'headers' => [], 'cookies' => [], 'body' => $body];
        };

        return [
            'timeout' => [new WP_Error('http_request_failed', 'cURL error 28'), 'request_failed'],
            'server error' => [$status(500), 'request_failed'],
            'quota' => [$status(429), 'quota'],
            'bad key' => [$status(403), 'request_failed'],
            'not json' => [$ok('not json'), 'invalid_response'],
            'point geometry' => [$this->route_response([9.19, 45.46], 1, 1, 'Point'), 'invalid_response'],
            'non numeric coordinate' => [$this->route_response([[['a', 45.4], [9.2, 45.5]]]), 'invalid_response'],
            'latitude out of range' => [$this->route_response([[[9.2, 95.4], [9.3, 45.5]]]), 'invalid_response'],
            'empty collection' => [$ok('{"type":"FeatureCollection","features":[]}'), 'invalid_response'],
            'too many points' => [$this->route_response([$too_many]), 'invalid_response'],
            'body over 5 MB' => [$ok(str_repeat(' ', 5242881)), 'invalid_response'],
        ];
    }

    /**
     * @dataProvider failed_route_responses
     */
    public function test_failed_route_response_falls_back_to_a_straight_line($response, $reason)
    {
        $this->with_geoapify_key();
        $requests = $this->stub_routing(static function () use ($response) {
            return $response;
        });
        $failures = new ArrayObject();
        add_action('osm_map_elementor_route_failed', static function ($why, $status) use ($failures) {
            $failures[] = [$why, $status];
        }, 10, 2);

        $route = $this->route_for();

        $this->assertFalse($route['routed']);
        $this->assertEquals([[[45.4642, 9.19], [45.7037, 9.6625]]], $route['lines']);
        $this->assertCount(1, $failures);
        $this->assertSame($reason, $failures[0][0]);
        $this->assertStringNotContainsString('test-key', wp_json_encode($failures->getArrayCopy()));
        $this->assertStringNotContainsString('45.4642', wp_json_encode($failures->getArrayCopy()));

        $this->route_for();
        $this->assertCount(1, $requests, 'A failure is remembered instead of requested again.');
    }

    public function test_route_lock_draws_the_fallback_without_requesting_or_caching()
    {
        $this->with_geoapify_key();
        $stops = [[45.4642, 9.19], [45.7037, 9.6625]];
        $key = $this->route_cache_key('drive', $stops);
        $requests = $this->stub_routing(function () {
            return $this->route_response($this->road_lines());
        });
        set_transient($key . '_lock', 1, 30);

        $html = $this->render_admin(['marker_list' => [$this->route_marker()]]);

        $this->assertFalse($this->js_value($html, 'markers')[0]['routed']);
        $this->assertCount(0, $requests);
        $this->assertFalse(get_transient($key));
        $this->assertStringContainsString('still loading', $html);
    }

    public function test_each_render_requests_at_most_five_routes()
    {
        $this->with_geoapify_key();
        $requests = $this->stub_routing(function () {
            return $this->route_response($this->road_lines());
        });
        $markers = [];
        for ($i = 0; $i < 6; $i++) {
            $markers[] = $this->route_marker(['route_end' => (45.7 + $i / 100) . ', 9.66']);
        }

        $entries = $this->js_value($this->render_admin(['marker_list' => $markers]), 'markers');

        $this->assertCount(5, $requests);
        $this->assertSame([true, true, true, true, true, false], array_column($entries, 'routed'));
        $this->assertFalse(get_transient($this->route_cache_key('drive', [[45.4642, 9.19], [45.75, 9.66]])));
    }

    public function test_geoapify_key_never_reaches_the_page_or_the_cache()
    {
        $this->with_geoapify_key();
        $this->stub_routing(function () {
            return $this->route_response($this->road_lines());
        });

        $html = $this->render_frontend(['marker_list' => [$this->route_marker()]]);
        $scripts = $html . $this->inline_scripts_after_markers_handle();

        $this->assertStringNotContainsString('test-key', $scripts);
        $this->assertStringNotContainsString('apiKey', $scripts);
        $cached = get_transient($this->route_cache_key('drive', [[45.4642, 9.19], [45.7037, 9.6625]]));
        $this->assertStringNotContainsString('test-key', wp_json_encode($cached));
    }

    public function test_only_numbers_from_the_routing_response_reach_the_script()
    {
        $this->with_geoapify_key();
        $response = $this->route_response([[['9.19', '45.4642'], [9.6, 45.6], [9.6625, 45.7037]]]);
        $body = json_decode($response['body'], true);
        $body['features'][0]['properties']['name'] = '<script>alert(2)</script>';
        $response['body'] = wp_json_encode($body);
        $this->stub_routing(static function () use ($response) {
            return $response;
        });

        $html = $this->render_admin(['marker_list' => [$this->route_marker()]]);

        $this->assertStringNotContainsString('<script>alert(2)', $html);
        foreach ($this->js_value($html, 'markers')[0]['lines'] as $line) {
            foreach ($line as $point) {
                $this->assertTrue(is_float($point[0]) || is_int($point[0]));
                $this->assertTrue(is_float($point[1]) || is_int($point[1]));
            }
        }
    }

    public function test_maps_with_a_road_route_credit_geoapify_and_other_maps_do_not()
    {
        $this->with_geoapify_key();
        $stops = [[45.4642, 9.19], [45.7037, 9.6625]];
        $this->stub_routing(function () {
            return $this->route_response($this->road_lines());
        });
        $routed = $this->js_value($this->render_admin(['marker_list' => [$this->route_marker(), $this->marker()]]), 'markers');
        $this->assertSame('geoapify', $routed[0]['provider']);
        $this->assertArrayNotHasKey('provider', $routed[1]);

        $this->assertSame('geoapify', $this->route_for()['provider'], 'A cached route is still credited.');

        remove_all_filters('pre_http_request');
        $this->stub_routing(static function () {
            return new WP_Error('http_request_failed', 'timeout');
        });
        delete_transient($this->route_cache_key('drive', $stops));
        $this->assertArrayNotHasKey('provider', $this->route_for());

        delete_option('osm_widget');
        delete_transient($this->route_cache_key('drive', $stops));
        $this->assertArrayNotHasKey('provider', $this->route_for());
        $this->assertArrayNotHasKey('provider', $this->shapes_for('line', ['shape_points' => "1,1\n2,2"])[0]);
    }

    public function test_the_geoapify_credit_is_fixed_text_that_opens_in_a_new_tab_with_noopener()
    {
        $js = $this->render_admin();

        $this->assertStringContainsString("const routeAttribution = 'Routing &copy; <a href=\"https://www.geoapify.com/\" target=\"_blank\" rel=\"noopener noreferrer\">Geoapify</a>';", $js);
        $this->assertStringContainsString("if ('geoapify' === this.provider) {", $js);
        $this->assertStringContainsString('map.attributionControl.addAttribution(routeAttribution);', $js);
        $this->assertSame([], $this->links_without_noopener($this->new_tab_links($js)));
    }

    public function test_provider_flag_never_comes_from_the_routing_response()
    {
        $this->with_geoapify_key();
        $response = $this->route_response($this->road_lines());
        $body = json_decode($response['body'], true);
        $body['features'][0]['properties']['provider'] = '<script>alert(3)</script>';
        $body['provider'] = 'x"><img>';
        $response['body'] = wp_json_encode($body);
        $this->stub_routing(static function () use ($response) {
            return $response;
        });

        $html = $this->render_admin(['marker_list' => [$this->route_marker()]]);

        $this->assertSame('geoapify', $this->js_value($html, 'markers')[0]['provider']);
        $this->assertStringNotContainsString('alert(3)', $html);
    }

    public function test_debug_helper_is_removed()
    {
        $this->assertFalse((new ReflectionClass(Widget_OSM_Map::class))->hasMethod('__debug'));
    }
}

function wp_filter_callbacks_for($hook)
{
    $callbacks = [];
    if (isset($GLOBALS['wp_filter'][$hook])) {
        foreach ($GLOBALS['wp_filter'][$hook]->callbacks as $priority) {
            foreach ($priority as $entry) {
                $callbacks[] = $entry['function'];
            }
        }
    }

    return $callbacks;
}
