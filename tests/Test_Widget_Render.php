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

        $this->make_widget();
        $google = wp_scripts()->registered['google-maps'];

        $this->assertStringContainsString('key=' . rawurlencode('ab"c&d'), $google->src);
        $this->assertStringContainsString('loading=async', $google->src);
        $this->assertStringContainsString('callback=initOSMEditorControls', $google->src);
        $this->assertNull($google->ver);
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
