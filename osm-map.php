<?php

namespace OSM_Map;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

/**
 * OSM map widget.
 *
 * Widget that displays an embedded OSM map.
 *
 * @since 1.0.0
 */
class Widget_OSM_Map extends \Elementor\Widget_Base
{

    public static $slug = 'osm-map-elementor';

    var $__depended_scripts = [];

    public function __construct($data = [], $args = null)
    {
        parent::__construct($data, $args);
        $this->__queue_assets();
    }

    /**
     * Get widget name.
     *
     * Retrieve osm map widget name.
     *
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name()
    {
        return self::$slug;
    }

    /**
     * Get widget title.
     *
     * Retrieve osm map widget title.
     *
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title()
    {
        return __('OSM Map', self::$slug);
    }

    /**
     * Get widget icon.
     *
     * Retrieve osm map widget icon.
     *
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon()
    {
        return 'eicon-google-maps';
    }

    /**
     * Get widget categories.
     *
     * Retrieve the list of categories the osm map widget belongs to.
     *
     * Used to determine where to display the widget in the editor.
     *
     * @access public
     *
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return ['basic'];
    }

    public function get_script_depends()
    {
        return $this->__depended_scripts;
    }

    public function get_style_depends()
    {
        return ['leaflet'];
    }

    /**
     * Register osm map widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @access protected
     */
    protected function _register_controls()
    {

        $this->__register_content_controls();

        $this->__register_style_controls();
    }

    /**
     * Registers content controls
     */
    private function __register_content_controls()
    {

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'marker_title',
            [
                'label' => __('Title', self::$slug),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('Marker Title', self::$slug),
            ]
        );

        $repeater->add_control(
            'marker_location',
            [
                'label' => __('Location', self::$slug),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('Marker Location', self::$slug),
            ]
        );

        $repeater->add_control(
            'marker_coords',
            [
                'label' => __('Coordinates', self::$slug),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('lat, long', self::$slug),
            ]
        );

        $repeater->add_control(
            'marker_description',
            [
                'label' => __('Description', self::$slug),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => __('Marker Description', self::$slug),
            ]
        );

        $repeater->add_control(
            'show_button',
            [
                'label' => __('Show Button', self::$slug),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', self::$slug),
                'label_off' => __('Hide', self::$slug),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $repeater->add_control(
            'button_text',
            [
                'label' => __('Button Text', self::$slug),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('Button Text', self::$slug),
            ]
        );

        $repeater->add_control(
            'button_url',
            [
                'label' => __('Button URL', self::$slug),
                'type' => \Elementor\Controls_Manager::TEXT,
                'input_type' => 'url',
                'placeholder' => __('https://your-link.com', self::$slug),
            ]
        );

        $this->start_controls_section(
            'section_map',
            [
                'label' => __('Map', self::$slug),
            ]
        );

        $osm_settings = get_option('osm_widget');
        $this->add_control(
            'gmaps_key',
            [
                'label' => __('Google Maps Key', self::$slug),
                'type' => \Elementor\Controls_Manager::TEXT,
                'description' => 'Update API keys in global settings <a target="_blank" href="/wp-admin/options-general.php?page=osm-map-elementor">here</a>',
                'placeholder' => __('Google Maps API Key', self::$slug),
                'default' => !empty($osm_settings['gmaps_key']) ? $osm_settings['gmaps_key'] : null
            ]
        );

        $this->add_control(
            'zoom',
            [
                'label' => __('Zoom', self::$slug),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'default' => [
                    'size' => 10,
                ],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 20,
                    ],
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'marker_list',
            [
                'label' => __('Marker List', self::$slug),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    []
                ],
                'title_field' => '{{{ marker_title }}}'
            ]
        );

        $this->add_control(
            'view',
            [
                'label' => __('View', self::$slug),
                'type' => \Elementor\Controls_Manager::HIDDEN,
                'default' => 'traditional',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Registers style controls
     */
    private function __register_style_controls()
    {

        // START Map Section
        $this->start_controls_section(
            'section_map_style',
            [
                'label' => __('Map', self::$slug),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'geoapify_tile',
            [
                'label' => __('Tile', self::$slug),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'osm-carto',
                'options' => [
                    'osm-carto' => __('OSM Carto', self::$slug),
                    'osm-bright' => __('OSM Bright', self::$slug),
                    'osm-bright-grey' => __('OSM Bright Grey', self::$slug),
                    'osm-bright-smooth' => __('OSM Bright Smooth', self::$slug),
                    'klokantech-basic' => __('Klokantech Basic', self::$slug),
                    'positron' => __('Positron', self::$slug),
                    'positron-blue' => __('Positron Blue', self::$slug),
                    'positron-red' => __('Positron Red', self::$slug),
                    'dark-matter' => __('Dark Matter', self::$slug),
                    'dark-matter-brown' => __('Dark Matter Brown', self::$slug),
                    'dark-matter-dark-grey' => __('Dark Matter Dark Grey', self::$slug),
                    'dark-matter-dark-purple' => __('Dark Matter Dark Purple', self::$slug),
                    'dark-matter-purple-roads' => __('Dark Matter Purple Roads', self::$slug),
                    'dark-matter-yellow-roads' => __('Dark Matter Yellow Roads', self::$slug),
                ],
            ]
        );

        $this->add_control(
            'hr',
            [
                'type' => \Elementor\Controls_Manager::DIVIDER,
            ]
        );

        $this->add_responsive_control(
            'width',
            [
                'label' => __('Width', self::$slug),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 1000,
                        'step' => 5,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 100,
                ],
                'selectors' => [
                    '{{WRAPPER}} .osm-map-container' => 'width: {{SIZE}}{{UNIT}};',
                ]
            ]
        );

        $this->add_responsive_control(
            'height',
            [
                'label' => __('Height', self::$slug),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 40,
                        'max' => 1440,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ]
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 200,
                ],
                'selectors' => [
                    '{{WRAPPER}} .osm-map-container' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'hr_2',
            [
                'type' => \Elementor\Controls_Manager::DIVIDER,
            ]
        );

        $this->add_control(
            'z_index',
            [
                'label' => __('z-index', self::$slug),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    '' => [
                        'min' => 0,
                        'max' => 100
                    ]
                ],
                'default' => [
                    'size' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .osm-map-container' => 'z-index: {{SIZE}};',
                ],
            ]
        );

        $this->end_controls_section();
        // END Map Section


        // START Marker Title Section
        $this->start_controls_section(
            'section_marker_title_style',
            [
                'label' => __('Marker Title', self::$slug),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Text Color', self::$slug),
                'type' => \Elementor\Controls_Manager::COLOR,
                'global' => [
                    'default' => Global_Colors::COLOR_PRIMARY,
                ],
                'selectors' => [
                    '{{WRAPPER}} .marker-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
                ],
                'selector' => '{{WRAPPER}} .marker-title',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'text_shadow',
                'selector' => '{{WRAPPER}} .marker-title',
            ]
        );

        $this->end_controls_section();
        // END Marker Title Section

        // START Marker Content  Section
        $this->start_controls_section(
            'section_marker_content_style',
            [
                'label' => __('Marker Content', self::$slug),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'label' => __('Typography', self::$slug),
                'scheme' => \Elementor\Scheme_Typography::TYPOGRAPHY_1,
                'selector' => '{{WRAPPER}} .marker-content',
            ]
        );

        $this->end_controls_section();
        // END Marker Content Section

        // Start Marker Button Section
        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Marker Button', self::$slug),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_ACCENT,
                ],
                'selector' => '{{WRAPPER}} .elementor-button',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'text_shadow',
                'selector' => '{{WRAPPER}} .elementor-button',
            ]
        );

        $this->start_controls_tabs('tabs_button_style');

        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => __('Normal', self::$slug),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Text Color', self::$slug),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'fill: {{VALUE}}; color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label' => __('Background Color', self::$slug),
                'type' => \Elementor\Controls_Manager::COLOR,
                'global' => [
                    'default' => Global_Colors::COLOR_ACCENT,
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => __('Hover', self::$slug),
            ]
        );

        $this->add_control(
            'hover_color',
            [
                'label' => __('Text Color', self::$slug),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button:hover, {{WRAPPER}} .elementor-button:focus' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .elementor-button:hover svg, {{WRAPPER}} .elementor-button:focus svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_hover_color',
            [
                'label' => __('Background Color', self::$slug),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .elementor-button:hover, {{WRAPPER}} .elementor-button:focus' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_border_color',
            [
                'label' => __('Border Color', self::$slug),
                'type' => \Elementor\ Controls_Manager::COLOR,
                'condition' => [
                    'border_border!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button:hover, {{WRAPPER}} .elementor-button:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'hover_animation',
            [
                'label' => __('Hover Animation', self::$slug),
                'type' => \Elementor\Controls_Manager::HOVER_ANIMATION,
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'border',
                'selector' => '{{WRAPPER}} .elementor-button',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label' => __('Border Radius', self::$slug),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .elementor-button',
            ]
        );

        $this->add_responsive_control(
            'text_padding',
            [
                'label' => __('Padding', self::$slug),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'button_margin',
            [
                'label' => __('Margin', self::$slug),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .elementor-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render osm map widget output on the frontend.
     *
     * @access protected
     */
    protected function render()
    {
        $global_settings = get_option('osm_widget');
        $settings = $this->get_settings_for_display();
        $markers = $this->get_settings_for_display('marker_list');

        // queue google maps key if provided
        if (is_admin()) {
            wp_register_script('google-maps', 'https://maps.googleapis.com/maps/api/js?libraries=places&key=' . $settings['gmaps_key']);
            wp_enqueue_script('google-maps');
        }

        if (0 === absint($settings['zoom']['size'])) {
            $settings['zoom']['size'] = 10;
        }

        if (0 === absint($settings['height']['size'])) {
            $settings['height']['size'] = 200;
        }

        // get all marker coords to help calculate center
        $coords = [];
        foreach ($markers as $marker) {
            $loc = explode(',', $marker['marker_coords']);
            if (!empty($loc) && sizeof($loc) == 2) {
                $coords[] = [
                    'marker' => $marker,
                    'lat' => $loc[0],
                    'lng' => $loc[1]
                ];
            }
        }

        // get center coordinates for our map
        $center_coords = !empty($coords) ? $this->__get_center_coords($coords) : [];

        echo '<div id="osm-map-' . $this->get_id() . '" 
        class="osm-map-container" 
        data-gmap-key="' . $settings['gmaps_key'] . '" 
        data-center="' . implode(',', $center_coords) . '" 
        data-zoom="' . $settings['zoom']['size'] . '"
        data-markers=\'' . json_encode($coords) . '\'></div>';
        ?>
        <script type="text/javascript">
            jQuery(window).ready(function () {
                "use strict";

                const mapId = '<?php echo 'osm-map-' . $this->get_id(); ?>';
                const mapContainer = jQuery('#' + mapId);
                const center = mapContainer.data('center');
                const zoom = mapContainer.data('zoom');

                // avoid recreating the html element
                if (L.DomUtil.get(mapId) !== undefined) {
                    L.DomUtil.get(mapId)._leaflet_id = null;
                }

                const map = L.map(mapId);

                if (center) {
                    let centerCoords = center.split(',');
                    map.setView(centerCoords, zoom);
                }

                <?php if(empty($settings['geoapify_tile']) || $settings['geoapify_tile'] == 'osm-carto'): ?>
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 18
                }).addTo(map);
                <?php else: ?>
                // the attribution is required for the Geoapify Free tariff plan
                map.attributionControl.setPrefix('').addAttribution('Powered by <a href="https://www.geoapify.com/" target="_blank">Geoapify</a> | © OpenStreetMap <a href="https://www.openstreetmap.org/copyright" target="_blank">contributors</a>');

                // install leaflet-mapbox-gl plugin
                L.mapboxGL({
                    style: 'https://maps.geoapify.com/v1/styles/<?php echo $settings['geoapify_tile']; ?>/style.json?apiKey=<?php echo !empty($global_settings['geoapify_key']) ? $global_settings['geoapify_key'] : null; ?>',
                    accessToken: '<?php echo !empty($global_settings['mapbox_token']) ? $global_settings['mapbox_token'] : 'no-token'; ?>'
                }).addTo(map);
                <?php endif; ?>

                // add available markers
                const markers = jQuery(mapContainer).data('markers');
                jQuery.each(markers, function () {
                    const marker = L.marker([this.lat, this.lng]);

                    // add marker to map
                    marker.addTo(map);

                    // prep tooltip content
                    let tooltipContent = null;
                    if (this.marker.marker_title && !this.marker.marker_description) {
                        tooltipContent = `<div class="marker-tooltip"><div class="marker-title">${this.marker.marker_title}</div></div>`;
                    } else if (this.marker.marker_description && this.marker.show_button !== 'yes') {
                        tooltipContent = `<div class="marker-tooltip"><div class="marker-title">${this.marker.marker_title}</div><div class="marker-content">${this.marker.marker_description}</div></div>`;
                    } else if (this.marker.show_button === 'yes') {
                        tooltipContent = `<div class="marker-tooltip"><div class="marker-title">${this.marker.marker_title}</div><div class="marker-content">${this.marker.marker_description}<br /><a class="elementor-button" target="_blank" href='${this.marker.button_url}'>${this.marker.button_text}</a></div></div>`;
                    }

                    // add tooltip to marker
                    if (tooltipContent) {
                        marker.bindPopup(tooltipContent);
                    }
                });

                setTimeout(function () {
                    map.invalidateSize();
                }, 100)
            });
        </script>
        <?php
    }

    /**
     * queue assets needed by maps widget
     */
    private function __queue_assets()
    {
        $styles = [
            'leaflet' => '//unpkg.com/leaflet@1.6.0/dist/leaflet.css',
            'mapbox-gl' => '//api.mapbox.com/mapbox-gl-js/v1.12.0/mapbox-gl.css',
        ];

        foreach ($styles as $handle => $path) {
            wp_register_style($handle, $path);
            wp_enqueue_style($handle);
        }

        // queue admin js
        if (is_admin()) {
            $admin_scripts = [
                'osm-map-elementor-controls' => plugins_url('/osm-map-elementor/assets/js/osm-map-controls.js')
            ];

            $dependencies = [
            ];
            foreach ($admin_scripts as $handle => $path) {
                wp_register_script($handle, $path, $dependencies, '1.0', false);
                wp_enqueue_script($handle);
            }
        }


        // queue widget view js
        $scripts = [
            'leaflet' => '//unpkg.com/leaflet@1.6.0/dist/leaflet.js',
            'mapbox-gl' => '//api.mapbox.com/mapbox-gl-js/v1.12.0/mapbox-gl.js',
            'leaflet-mapbox-gl' => '//unpkg.com/mapbox-gl-leaflet/leaflet-mapbox-gl.js"'
        ];
        $deps = [];
        foreach ($scripts as $handle => $path) {
            wp_register_script($handle, $path, $deps, '1.0', false);
            wp_enqueue_script($handle);
            $deps[] = $handle;
        }
    }

    /**
     * Calculates center coordinates given a list of coordinates
     * @param $coords
     * @return float[]|int[]
     */
    private function __get_center_coords($coords)
    {
        $count_coords = count($coords);
        $xcos = 0.0;
        $ycos = 0.0;
        $zsin = 0.0;

        foreach ($coords as $lnglat) {
            $lat = $lnglat['lat'] * pi() / 180;
            $lon = $lnglat['lng'] * pi() / 180;

            $acos = cos($lat) * cos($lon);
            $bcos = cos($lat) * sin($lon);
            $csin = sin($lat);
            $xcos += $acos;
            $ycos += $bcos;
            $zsin += $csin;
        }

        $xcos /= $count_coords;
        $ycos /= $count_coords;
        $zsin /= $count_coords;
        $lon = atan2($ycos, $xcos);
        $sqrt = sqrt($xcos * $xcos + $ycos * $ycos);
        $lat = atan2($zsin, $sqrt);

        return array($lat * 180 / pi(), $lon * 180 / pi());
    }

    /**
     * Prints out data for debugging
     * @param $data
     */
    private function __debug($data)
    {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
}