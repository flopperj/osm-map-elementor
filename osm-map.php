<?php

namespace OSM_Map;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once('constants.php');

use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Core\Breakpoints\Manager;
use Elementor\Widget_Base;
use Elementor\Repeater;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

/**
 * OSM map widget.
 *
 * Widget that displays an embedded OSM map.
 *
 * @package OSM_Map
 * @since 1.0.0
 */
class Widget_OSM_Map extends Widget_Base
{

    public static $slug = OSM_MAP_SLUG;
    public static $ver = OSM_MAP_VERSION;

    /**
     * Widget OSM Map constructor.
     *
     * Initializing the widget base class.
     *
     * @param array $data Widget data. Default is an empty array.
     * @param array|null $args Optional. Widget default arguments. Default is null.
     * @since 1.0.0
     * @access public
     *
     */
    public function __construct($data = [], $args = null)
    {
        parent::__construct($data, $args);

        // add these scripts later
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
        return __('OSM Map', 'osm-map');
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

    /**
     * Retrieve the list of script dependencies the element requires.
     * @return string[]
     */
    public function get_script_depends()
    {
        return ['jquery'];
    }

    /**
     * Retrieve the list of style dependencies the element requires.
     * @return string[]
     */
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
    protected function register_controls()
    {

        // register content controls
        $this->__register_content_controls();

        // register style controls
        $this->__register_style_controls();
    }

    /**
     * Registers content controls
     */
    private function __register_content_controls()
    {

        $repeater = new Repeater();

        $repeater->add_control(
            'marker_title',
            [
                'label' => __('Title', 'osm-map'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Marker Title', 'osm-map'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'marker_location',
            [
                'label' => __('Location', 'osm-map'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Marker Location', 'osm-map'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'marker_coords',
            [
                'label' => __('Coordinates', 'osm-map'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('lat, long', 'osm-map'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'marker_description',
            [
                'label' => __('Description', 'osm-map'),
                'type' => Controls_Manager::TEXTAREA,
                'placeholder' => __('Marker Description', 'osm-map'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'marker_behavior',
            [
                'label' => __('Behavior', 'osm-map'),
                'type' => Controls_Manager::SELECT,
                'default' => 'popup',
                'options' => [
                    'popup' => 'Popup',
                    'tooltip' => 'Tooltip',
                    'static_close_on' => 'Static with close',
                    'static_close_off' => 'Static without close',
                    'none' => 'None'
                ]
            ]
        );

        $repeater->add_control(
            'show_button',
            [
                'label' => __('Show Button', 'osm-map'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'osm-map'),
                'label_off' => __('Hide', 'osm-map'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $repeater->add_control(
            'button_text',
            [
                'label' => __('Button Text', 'osm-map'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Button Text', 'osm-map'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'button_url',
            [
                'label' => __('Button URL', 'osm-map'),
                'type' => Controls_Manager::TEXT,
                'input_type' => 'url',
                'placeholder' => __('https://your-link.com', 'osm-map'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'button_url_target',
            [
                'label' => __('URL target', 'osm-map'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                        '_self' => 'Same Window',
                        '_blank' => 'New Window/Tab'
                ],
                'default' => '_blank',
                'condition' => [
                    'button_url!' => ''
                ]
            ]
        );

        $repeater->add_control(
            'marker_visible',
            [
                'label' => __('Show Marker', 'osm-map'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'osm-map'),
                'label_off' => __('Hide', 'osm-map'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );


        $this->start_controls_section(
            'section_map',
            [
                'label' => __('Map', 'osm-map'),
            ]
        );

        $this->add_control(
            'important_note',
            [
                'label' => __('Important Note', 'osm-map'),
                'type' => Controls_Manager::RAW_HTML,
                'raw' => __('<div class="elementor-control-field-description">To take advantage of custom tiles and auto-population of coordinates in markers, please update API keys in global settings <a target="_blank" href="/wp-admin/options-general.php?page=osm-map-elementor">here</a></div>', 'osm-map')
            ]
        );

        $this->add_responsive_control(
            'zoom',
            [
                'label' => __('Zoom Level', 'osm-map'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 20,
                    ],
                ],
                'devices' => ['desktop', 'tablet', 'mobile'],
                'desktop_default' => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'tablet_default' => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'mobile_default' => [
                    'size' => 10,
                    'unit' => 'px',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'zoom_control',
            [
                'label' => __('Zoom Control', 'osm-map'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'osm-map'),
                'label_off' => __('Disable', 'osm-map'),
                'return_value' => 'yes',
                'default' => 'yes'
            ]
        );

        $this->add_control(
            'scroll_zoom',
            [
                'label' => __('Scroll Zoom', 'osm-map'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'osm-map'),
                'label_off' => __('Disable', 'osm-map'),
                'return_value' => 'yes',
                'default' => 'yes'
            ]
        );

        $this->add_control(
            'pan_control',
            [
                'label' => __('Pan Control', 'osm-map'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'osm-map'),
                'label_off' => __('Disable', 'osm-map'),
                'return_value' => 'yes',
                'default' => 'yes',
                'separator' => 'after'
            ]
        );

        $this->add_control(
            'marker_list',
            [
                'label' => __('Marker List', 'osm-map'),
                'type' => Controls_Manager::REPEATER,
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
                'label' => __('View', 'osm-map'),
                'type' => Controls_Manager::HIDDEN,
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

        $global_settings = get_option('osm_widget');

        // START Map Section
        $this->start_controls_section(
            'section_map_style',
            [
                'label' => __('Map', 'osm-map'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // show notice about api keys
        if (empty($global_settings['mapbox_token']) || empty($global_settings['geoapify_key'])) {
            $this->add_control(
                'important_note2',
                [
                    'label' => __('Important Note', 'osm-map'),
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => __('<div class="elementor-control-field-description">To take advantage of custom tiles in markers, please update API keys in global settings <a target="_blank" href="/wp-admin/options-general.php?page=osm-map-elementor">here</a></div>', 'osm-map'),
                    'separator' => 'after'
                ]
            );
        }

        $this->add_control(
            'geoapify_tile',
            [
                'label' => __('Tile', 'osm-map'),
                'type' => Controls_Manager::SELECT,
                'default' => 'osm-carto',
                'options' => [
                    'osm-carto' => __('OSM Carto (Free)', 'osm-map'),
                    'osm-bright' => __('Geoapify - OSM Bright', 'osm-map'),
                    'osm-bright-grey' => __('Geoapify - OSM Bright Grey', 'osm-map'),
                    'osm-bright-smooth' => __('Geoapify - OSM Bright Smooth', 'osm-map'),
                    'klokantech-basic' => __('Geoapify - Klokantech Basic', 'osm-map'),
                    'osm-liberty' => __('Geoapify - OSM Liberty', 'osm-map'),
                    'maptiler-3d' => __('Geoapify - MapTiler 3D', 'osm-map'),
                    'toner' => __('Geoapify - Toner', 'osm-map'),
                    'toner-grey' => __('Geoapify - Toner Grey', 'osm-map'),
                    'positron' => __('Geoapify - Positron', 'osm-map'),
                    'positron-blue' => __('Geoapify - Positron Blue', 'osm-map'),
                    'positron-red' => __('Geoapify - Positron Red', 'osm-map'),
                    'dark-matter' => __('Geoapify - Dark Matter', 'osm-map'),
                    'dark-matter-brown' => __('Geoapify - Dark Matter Brown', 'osm-map'),
                    'dark-matter-dark-grey' => __('Geoapify - Dark Matter Dark Grey', 'osm-map'),
                    'dark-matter-dark-purple' => __('Geoapify - Dark Matter Dark Purple', 'osm-map'),
                    'dark-matter-purple-roads' => __('Geoapify - Dark Matter Purple Roads', 'osm-map'),
                    'dark-matter-yellow-roads' => __('Geoapify - Dark Matter Yellow Roads', 'osm-map'),
                    'stadia-osm-bright' => __('Stadia - OSM Bright', 'osm-map'),
                    'stadia-outdoors' => __('Stadia - Outdoors', 'osm-map'),
                    'stadia-alidade-smooth' => __('Stadia - Alidade Smooth', 'osm-map'),
                    'stadia-alidade-smooth-dark' => __('Stadia - Alidade Smooth Dark', 'osm-map'),
                    'stadia-alidade-satellite' => __('Stadia - Alidade Satellite', 'osm-map'),
                    'stadia-stamen-toner' => __('Stadia - Stamen Toner', 'osm-map'),
                    'stadia-stamen-toner-lite' => __('Stadia - Stamen Toner Lite', 'osm-map'),
                    'stadia-stamen-terrain' => __('Stadia - Stamen Terrain', 'osm-map'),
                    'stadia-stamen-watercolor' => __('Stadia - Stamen Watercolor', 'osm-map'),
                    'custom-tile' => __('Custom Map Tile', 'osm-map'),
                ]
            ]
        );

        $this->add_control(
            'hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $this->add_responsive_control(
            'width',
            [
                'label' => __('Width', 'osm-map'),
                'type' => Controls_Manager::SLIDER,
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
                'label' => __('Height', 'osm-map'),
                'type' => Controls_Manager::SLIDER,
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
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $this->add_control(
            'z_index',
            [
                'label' => __('z-index', 'osm-map'),
                'type' => Controls_Manager::SLIDER,
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

        // START Marker Icon section
        $this->start_controls_section(
            'section_marker_icon_style',
            [
                'label' => __('Marker Icon', 'osm-map'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );


        $this->add_control(
            'fontawesome_important_note',
            [
                'label' => __('Important Note', 'osm-map'),
                'type' => Controls_Manager::RAW_HTML,
                'raw' => __('<div class="elementor-control-field-description">If you\'re having conflicts with the widget\'s Font Awesome library, please toggle the <strong>Font Awesome</strong> setting in the global settings <a target="_blank" href="/wp-admin/options-general.php?page=osm-map-elementor">here</a></div>', 'osm-map'),
                'condition' => [
                    'icon_type' => 'fontawesome'
                ]
            ]
        );


        $this->add_control(
            'icon_type',
            [
                'label' => __('Icon Type', 'osm-map'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map'),
                    'fontawesome' => __('Font Awesome', 'osm-map'),
                    'custom_image' => __('Custom Image', 'osm-map'),
                ]
            ]
        );

        $this->add_control(
            'fontawesome_icon',
            [
                'label' => __('Font Awesome Icon', 'osm-map'),
                'type' => Controls_Manager::TEXT,
                'default' => 'fa fa-circle',
                'placeholder' => __('fa fa-circle', 'osm-map'),
                'condition' => [
                    'icon_type' => 'fontawesome'
                ]
            ]
        );

        $this->add_control(
            'marker_background_color',
            [
                'label' => __('Background Color', 'osm-map'),
                'type' => Controls_Manager::COLOR,
                'default' => '#368acc',
                'condition' => [
                    'icon_type' => 'fontawesome'
                ]
            ]
        );

        $this->add_control(
            'marker_stroke_color',
            [
                'label' => __('Stroke Color', 'osm-map'),
                'type' => Controls_Manager::COLOR,
                'default' => '#346F9E',
                'condition' => [
                    'icon_type' => 'fontawesome'
                ]
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label' => __('Icon Color', 'osm-map'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'condition' => [
                    'icon_type' => 'fontawesome'
                ]
            ]
        );

        // icon offset control
        $this->add_control(
            'icon_offset_type',
            [
                'label' => __('Icon Offset', 'osm-map'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map'),
                    'custom' => __('Custom', 'osm-map'),
                ],
                'condition' => [
                    'icon_type' => 'fontawesome'
                ]
            ]
        );

        $this->add_control(
            'icon_offset_x',
            [
                'label' => __('x Offset', 'osm-map'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'condition' => [
                    'icon_offset_type' => 'custom'
                ]
            ]
        );

        $this->add_control(
            'icon_offset_y',
            [
                'label' => __('y Offset', 'osm-map'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'condition' => [
                    'icon_offset_type' => 'custom',

                ]
            ]
        );
        // end icon offset control

        // add icon size control
        $this->add_control(
            'icon_size',
            [
                'label' => __('Icon Size', 'osm-map'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 20,
                        'step' => 1
                    ]
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 12,
                ],
                'condition' => [
                    'icon_type' => 'fontawesome'
                ]
            ]
        );
        // end icon size control

        // add custom marker graphic
        $this->start_controls_tabs('custom_icon', [
            'condition' => [
                'icon_type' => 'custom_image'
            ]
        ]);

        $this->start_controls_tab(
            'tab_custom_icon',
            [
                'label' => __('Main', 'osm-map'),
            ]
        );

        $this->add_control(
            'custom_icon_image',
            [
                'label' => __('Choose Image', 'osm-map'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => plugin_dir_url(__FILE__) . 'assets/leaflet/images/marker-icon.png',
                ]
            ]
        );

        $this->add_control(
            'icon_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        // icon size control
        $this->add_control(
            'custom_icon_image_size_type',
            [
                'label' => __('Size', 'osm-map'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map'),
                    'custom' => __('Custom', 'osm-map'),
                ]
            ]
        );

        $this->add_control(
            'custom_icon_image_width',
            [
                'label' => __('Width', 'osm-map'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 100,
                        'step' => 1,
                    ]
                ],
                'condition' => [
                    'custom_icon_image_size_type' => 'custom'
                ]
            ]
        );

        $this->add_control(
            'custom_icon_image_height',
            [
                'label' => __('Height', 'osm-map'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 100,
                    ]
                ],
                'condition' => [
                    'custom_icon_image_size_type' => 'custom'
                ]
            ]
        );
        // end icon size control

        $this->add_control(
            'icon_hr2',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        // icon anchor control
        $this->add_control(
            'custom_icon_image_anchor_type',
            [
                'label' => __('Anchor', 'osm-map'),
                'type' => Controls_Manager::SELECT,
                'default' => 'custom',
                'options' => [
                    '' => __('Default', 'osm-map'),
                    'custom' => __('Custom', 'osm-map'),
                ]
            ]
        );

        $this->add_control(
            'custom_icon_image_anchor_x',
            [
                'label' => __('x Offset', 'osm-map'),
                'type' => Controls_Manager::NUMBER,
                'default' => 12.5,
                'condition' => [
                    'custom_icon_image_anchor_type' => 'custom'
                ]
            ]
        );

        $this->add_control(
            'custom_icon_image_anchor_y',
            [
                'label' => __('y Offset', 'osm-map'),
                'type' => Controls_Manager::NUMBER,
                'default' => 41,
                'condition' => [
                    'custom_icon_image_anchor_type' => 'custom'
                ]
            ]
        );
        // end icon anchor control

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_custom_icon_shadow',
            [
                'label' => __('Shadow', 'osm-map'),
            ]
        );
        $this->add_control(
            'custom_icon_shadow_image',
            [
                'label' => __('Choose Image', 'osm-map'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => plugin_dir_url(__FILE__) . 'assets/leaflet/images/marker-shadow.png',
                ]
            ]
        );


        $this->add_control(
            'icon_hr3',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        // icon size control
        $this->add_control(
            'custom_icon_shadow_size_type',
            [
                'label' => __('Size', 'osm-map'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map'),
                    'custom' => __('Custom', 'osm-map'),
                ]
            ]
        );

        $this->add_control(
            'custom_icon_shadow_width',
            [
                'label' => __('Width', 'osm-map'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 100,
                        'step' => 1,
                    ]
                ],
                'condition' => [
                    'custom_icon_shadow_size_type' => 'custom'
                ]
            ]
        );

        $this->add_control(
            'custom_icon_shadow_height',
            [
                'label' => __('Height', 'osm-map'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 100,
                    ]
                ],
                'condition' => [
                    'custom_icon_shadow_size_type' => 'custom'
                ]
            ]
        );
        // end icon size control

        $this->add_control(
            'icon_hr4',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        // icon anchor control
        $this->add_control(
            'custom_icon_shadow_anchor_type',
            [
                'label' => __('Anchor', 'osm-map'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map'),
                    'custom' => __('Custom', 'osm-map'),
                ]
            ]
        );

        $this->add_control(
            'custom_icon_shadow_anchor_x',
            [
                'label' => __('x Offset', 'osm-map'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'condition' => [
                    'custom_icon_shadow_anchor_type' => 'custom'
                ]
            ]
        );

        $this->add_control(
            'custom_icon_shadow_anchor_y',
            [
                'label' => __('y Offset', 'osm-map'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'condition' => [
                    'custom_icon_shadow_anchor_type' => 'custom',

                ]
            ]
        );
        // end icon anchor control

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'icon_hr5',
            [
                'type' => Controls_Manager::DIVIDER,
                'condition' => [
                    'icon_type' => 'custom_image'
                ]
            ]
        );

        // popup anchor control
        $this->add_control(
            'custom_icon_image_popup_anchor_type',
            [
                'label' => __('Popup Anchor', 'osm-map'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map'),
                    'custom' => __('Custom', 'osm-map'),
                ],
                'condition' => [
                    'icon_type' => 'custom_image'
                ]
            ]
        );


        $this->add_control(
            'custom_icon_image_popup_anchor_x',
            [
                'label' => __('x Offset', 'osm-map'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'condition' => [
                    'custom_icon_image_popup_anchor_type' => 'custom',
                    'icon_type' => 'custom_image'
                ]
            ]
        );

        $this->add_control(
            'custom_icon_image_popup_anchor_y',
            [
                'label' => __('y Offset', 'osm-map'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'condition' => [
                    'custom_icon_image_popup_anchor_type' => 'custom',
                    'icon_type' => 'custom_image'
                ]
            ]
        );

        // end icon popup control

        $this->end_controls_section();


        // START Marker Title Section
        $this->start_controls_section(
            'section_marker_title_style',
            [
                'label' => __('Marker Title', 'osm-map'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'title_align',
            [
                'label' => __('Alignment', 'osm-map'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'osm-map'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'osm-map'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'osm-map'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __('Justified', 'osm-map'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .marker-tooltip .marker-title' => 'text-align: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'title_color',
            [
                'label' => __('Text Color', 'osm-map'),
                'type' => Controls_Manager::COLOR,
                'global' => [
                    'default' => Global_Colors::COLOR_SECONDARY,
                ],
                'selectors' => [
                    '{{WRAPPER}} .marker-title .elementor-heading-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
                ],
                'selector' => '{{WRAPPER}} .marker-title .elementor-heading-title',
            ]
        );

        $this->add_group_control(
            Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'title_text_shadow',
                'selector' => '{{WRAPPER}} .marker-title .elementor-heading-title',
            ]
        );

        $this->add_responsive_control(
            'title_padding',
            [
                'label' => __('Padding', 'osm-map'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .marker-title .elementor-heading-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'title_margin',
            [
                'label' => __('Margin', 'osm-map'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                    'unit' => 'px'
                ],
                'selectors' => [
                    '{{WRAPPER}} .marker-title .elementor-heading-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
        // END Marker Title Section

        // START Marker Content  Section
        $this->start_controls_section(
            'section_marker_content_style',
            [
                'label' => __('Marker Content', 'osm-map'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_responsive_control(
            'content_align',
            [
                'label' => __('Alignment', 'osm-map'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'osm-map'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'osm-map'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'osm-map'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __('Justified', 'osm-map'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .marker-content .marker-description' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'content_text_color',
            [
                'label' => __('Text Color', 'osm-map'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .marker-content .marker-description' => 'color: {{VALUE}};',
                ],
                'global' => [
                    'default' => Global_Colors::COLOR_TEXT,
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'label' => __('Typography', 'osm-map'),
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
                ],
                'selector' => '{{WRAPPER}} .marker-content .marker-description',
            ]
        );

        $this->add_responsive_control(
            'content_padding',
            [
                'label' => __('Padding', 'osm-map'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 0,
                    'bottom' => 5,
                    'left' => 0,
                    'right' => 0,
                    'unit' => 'px'
                ],
                'selectors' => [
                    '{{WRAPPER}} .marker-content .marker-description' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'content_margin',
            [
                'label' => __('Margin', 'osm-map'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .marker-content .marker-description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
        // END Marker Content Section

        // Start Marker Button Section
        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Marker Button', 'osm-map'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'button_align',
            [
                'label' => __('Alignment', 'osm-map'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'osm-map'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'osm-map'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'osm-map'),
                        'icon' => 'eicon-text-align-right',
                    ]
                ],
                'selectors' => [
                    '{{WRAPPER}} .marker-content .marker-button' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_ACCENT,
                ],
                'selector' => '{{WRAPPER}} .marker-button .elementor-button',
            ]
        );

        $this->add_group_control(
            Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'button_text_shadow',
                'selector' => '{{WRAPPER}} .marker-button .elementor-button',
            ]
        );

        $this->start_controls_tabs('tabs_button_style');

        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => __('Normal', 'osm-map'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Text Color', 'osm-map'),
                'type' => Controls_Manager::COLOR,
                'global' => [
                    'default' => Global_Colors::COLOR_SECONDARY
                ],
                'selectors' => [
                    '{{WRAPPER}} .marker-button .elementor-button' => 'fill: {{VALUE}}; color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label' => __('Background Color', 'osm-map'),
                'type' => Controls_Manager::COLOR,
                'global' => [
                    'default' => Global_Colors::COLOR_PRIMARY,
                ],
                'selectors' => [
                    '{{WRAPPER}} .marker-button .elementor-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => __('Hover', 'osm-map'),
            ]
        );

        $this->add_control(
            'button_hover_color',
            [
                'label' => __('Text Color', 'osm-map'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .marker-button .elementor-button:hover, {{WRAPPER}} .marker-button .elementor-button:focus' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .marker-button .elementor-button:hover svg, {{WRAPPER}} .marker-button .elementor-button:focus svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_hover_color',
            [
                'label' => __('Background Color', 'osm-map'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .marker-button .elementor-button:hover, {{WRAPPER}} .marker-button .elementor-button:focus' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_border_color',
            [
                'label' => __('Border Color', 'osm-map'),
                'type' => Controls_Manager::COLOR,
                'condition' => [
                    'border_border!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} .marker-button .elementor-button:hover, {{WRAPPER}} .marker-button .elementor-button:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_animation',
            [
                'label' => __('Hover Animation', 'osm-map'),
                'type' => Controls_Manager::HOVER_ANIMATION,
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .marker-button .elementor-button',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => __('Border Radius', 'osm-map'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .marker-button .elementor-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .marker-button .elementor-button',
            ]
        );

        $this->add_responsive_control(
            'button_text_padding',
            [
                'label' => __('Padding', 'osm-map'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .marker-button .elementor-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'button_margin',
            [
                'label' => __('Margin', 'osm-map'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .marker-button .elementor-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
        $settings['breakpoints'] = \Elementor\Plugin::$instance->breakpoints->get_breakpoints();

        if (0 === absint($settings['zoom']['size'])) {
            $settings['zoom']['size'] = 10;
        }

        if (0 === absint($settings['height']['size'])) {
            $settings['height']['size'] = 200;
        }

        // get all marker coords to help calculate center
        $coords = [];
        foreach ($markers as $marker) {

            // hide markers that have been toggled off
            if (isset($marker['marker_visible']) && empty($marker['marker_visible'])) {
                continue;
            }

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

        echo '<div id="' . esc_attr('osm-map-' . $this->get_id()) . '" 
        class="osm-map-container" 
        data-center="' . implode(',', $center_coords) . '"></div>';

        ?>
        <script type="text/javascript">
            <?php
            // start output buffer
            ob_start();
            ?>
            jQuery(window).ready(function () {
                "use strict";
                const displaySettings = <?php echo wp_json_encode($settings); ?>;
                const mapId = '<?php echo 'osm-map-' . $this->get_id(); ?>';
                const mapContainer = jQuery('#' + mapId);
                const center = mapContainer.data('center');
                const hasDesktopZoomLevel = displaySettings && displaySettings.hasOwnProperty('zoom') && displaySettings.zoom && displaySettings.zoom.hasOwnProperty('size');
                const hasTabletZoomLevel = displaySettings && displaySettings.hasOwnProperty('zoom_tablet') && displaySettings.zoom_tablet && displaySettings.zoom_tablet.hasOwnProperty('size');
                const hasMobileZoomLevel = displaySettings && displaySettings.hasOwnProperty('zoom_mobile') && displaySettings.zoom_mobile && displaySettings.zoom_mobile.hasOwnProperty('size');
                let zoomLevel = hasDesktopZoomLevel ? displaySettings.zoom.size : 10;

                // look at break points for different screen sizes
                const viewPortWidth = jQuery('body').width();
                const isTabletView = displaySettings &&
                    displaySettings.hasOwnProperty('breakpoints') && displaySettings.breakpoints &&
                    displaySettings.breakpoints.hasOwnProperty('sm') &&
                    displaySettings.breakpoints.hasOwnProperty('md') &&
                    viewPortWidth > displaySettings.breakpoints.sm &&
                    viewPortWidth <= displaySettings.breakpoints.md;
                const isMobileView = displaySettings &&
                    displaySettings.hasOwnProperty('breakpoints') && displaySettings.breakpoints &&
                    displaySettings.breakpoints.hasOwnProperty('sm') &&
                    viewPortWidth <= displaySettings.breakpoints.sm;

                // check for tablet and mobile screen sizes
                if (hasTabletZoomLevel && isTabletView) {
                    zoomLevel = displaySettings.zoom_tablet.size;
                } else if (hasMobileZoomLevel && isMobileView) {
                    zoomLevel = displaySettings.zoom_mobile.size;
                }

                // avoid recreating the html element
                if (L.DomUtil.get(mapId) !== undefined && L.DomUtil.get(mapId)) {
                    L.DomUtil.get(mapId)._leaflet_id = null;
                }

                const map = L.map(mapId, {
                    scrollWheelZoom: <?php echo !empty($settings['scroll_zoom']) ? 'true' : 'false'; ?>,
                    zoomControl: <?php echo !empty($settings['zoom_control']) ? 'true' : 'false'; ?>,
                    dragging: <?php echo !empty($settings['pan_control']) ? 'true' : 'false'; ?>
                });

                if (center) {
                    let centerCoords = center.split(',');
                    map.setView(centerCoords, zoomLevel);
                }
                
                <?php if(empty($settings['geoapify_tile']) || $settings['geoapify_tile'] == 'osm-carto'):?>
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( $settings['geoapify_tile'] == 'stadia-osm-bright'):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank">Stadia Maps</a> | &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( $settings['geoapify_tile'] == 'stadia-outdoors'):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/outdoors/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank">Stadia Maps</a> | &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( $settings['geoapify_tile'] == 'stadia-alidade-smooth'):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank">Stadia Maps</a> | &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( $settings['geoapify_tile'] == 'stadia-alidade-smooth-dark'):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank">Stadia Maps</a> | &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( $settings['geoapify_tile'] == 'stadia-alidade-satellite'):?>
                L.tileLayer('https://tiles.stadiamaps.com/data/satellite/{z}/{x}/{y}.jpg', {
                    attribution: '&copy; CNES, Distribution Airbus DS, &copy; Airbus DS, &copy; PlanetObserver (Contains Copernicus Data) | &copy; <a href="https://stadiamaps.com/" target="_blank">Stadia Maps</a> | &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( $settings['geoapify_tile'] == 'stadia-stamen-toner'):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_toner/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank">Stadia Maps</a> | &copy; <a href="https://stamen.com/" target="_blank">Stamen Design</a> | &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( $settings['geoapify_tile'] == 'stadia-stamen-toner-lite'):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_toner_lite/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank">Stadia Maps</a> | &copy; <a href="https://stamen.com/" target="_blank">Stamen Design</a> | &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( $settings['geoapify_tile'] == 'stadia-stamen-terrain'):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_terrain/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank">Stadia Maps</a> | &copy; <a href="https://stamen.com/" target="_blank">Stamen Design</a> | &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( $settings['geoapify_tile'] == 'stadia-stamen-watercolor'):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_watercolor/{z}/{x}/{y}.jpg', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank">Stadia Maps</a> | &copy; <a href="https://stamen.com/" target="_blank">Stamen Design</a> | &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
                    maxZoom: 16
                }).addTo(map);

                <?php elseif( $settings['geoapify_tile'] == 'custom-tile'):?>
                L.tileLayer('<?php echo !empty($global_settings['osm_custom']) ? esc_textarea(__($global_settings['osm_custom'], 'your-slug')) : null; ?>', {
                    attribution: '<a href="<?php echo !empty($global_settings['osm_custom_attribution_url']) ? esc_textarea(__($global_settings['osm_custom_attribution_url'], 'your-slug')) : null; ?>" target="_blank"><?php echo !empty($global_settings['osm_custom_attribution']) ? esc_textarea(__($global_settings['osm_custom_attribution'], 'your-slug')) : null; ?></a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php else: ?>

                // the attribution is required for the Geoapify Free tariff plan

                map.attributionControl.setPrefix('').addAttribution('Powered by <a href="https://www.geoapify.com/" target="_blank">Geoapify</a> | &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>');
                // install leaflet-mapbox-gl plugin

                L.mapboxGL({
                    style: 'https://maps.geoapify.com/v1/styles/<?php echo $settings['geoapify_tile']; ?>/style.json?apiKey=<?php echo !empty($global_settings['geoapify_key']) ? esc_textarea(__($global_settings['geoapify_key'], 'your-slug')) : null; ?>',
                    accessToken: '<?php echo !empty($global_settings['mapbox_token']) ? esc_textarea(__($global_settings['mapbox_token'], 'your-slug')) : 'no-token'; ?>'
                }).addTo(map);

                <?php endif; ?>

                // add available markers
                const markers = <?php echo wp_json_encode($coords); ?>;
                let markerIcon = null;
                let markerOptions = {};

                <?php
                $icon_type = !empty($settings['icon_type']) ? $settings['icon_type'] : null;
                switch ($icon_type):
                case 'fontawesome':
                $fontawesome_icon = !empty($settings['fontawesome_icon']) ? $settings['fontawesome_icon'] : 'fa fa-circle';
                $marker_background_color = !empty($settings['marker_background_color']) ? $settings['marker_background_color'] : null;
                $marker_stroke_color = !empty($settings['marker_stroke_color']) ? $settings['marker_stroke_color'] : null;
                $icon_color = !empty($settings['icon_color']) ? $settings['icon_color'] : null;
                $icon_size = !empty($settings['icon_size']['size']) ? $settings['icon_size']['size'] : 12;
                $icon_offset = [
                    'xOffset' => !empty($settings['icon_offset_x']) ? $settings['icon_offset_x'] : 0,
                    'yOffset' => !empty($settings['icon_offset_y']) ? $settings['icon_offset_y'] : 0
                ];

                $fa_icon_options = [
                    'iconClasses' => $fontawesome_icon,
                    // marker/background style
                    'markerColor' => $marker_background_color,
                    'markerFillOpacity' => 1,
                    'markerStrokeWidth' => 1,
                    'markerStrokeColor' => $marker_stroke_color,
                    // icon style
                    'iconColor' => $icon_color,
                    'iconSize' => $icon_size
                ];

                // update icon offset
                if (!empty($settings['icon_offset_type']) && $settings['icon_offset_type'] == 'custom') {
                    $fa_icon_options['iconXOffset'] = $icon_offset['xOffset'];
                    $fa_icon_options['iconYOffset'] = $icon_offset['yOffset'];
                }
                ?>

                markerOptions.icon = L.icon.fontAwesome(<?php echo wp_json_encode($fa_icon_options); ?>);
                <?php
                break;
                case "custom_image":
                ?>
                <?php if(!empty($settings['custom_icon_image']['url'])): ?>
                <?php

                $icon_size = [
                    'width' => !empty($settings['custom_icon_image_width']) ? $settings['custom_icon_image_width'] : null,
                    'height' => !empty($settings['custom_icon_image_height']) ? $settings['custom_icon_image_height'] : null,
                ];
                $shadow_size = [
                    'width' => !empty($settings['custom_icon_shadow_width']) ? $settings['custom_icon_shadow_width'] : null,
                    'height' => !empty($settings['custom_icon_shadow_height']) ? $settings['custom_icon_shadow_height'] : null,
                ];
                $icon_anchor = [
                    'xOffset' => isset($settings['custom_icon_image_anchor_x']) ? $settings['custom_icon_image_anchor_x'] : 0,
                    'yOffset' => isset($settings['custom_icon_image_anchor_y']) ? $settings['custom_icon_image_anchor_y'] : 0
                ];
                $shadow_anchor = [
                    'xOffset' => isset($settings['custom_icon_shadow_anchor_x']) ? $settings['custom_icon_shadow_anchor_x'] : 0,
                    'yOffset' => isset($settings['custom_icon_shadow_anchor_y']) ? $settings['custom_icon_shadow_anchor_y'] : 0
                ];
                $popup_anchor = [
                    'xOffset' => isset($settings['custom_icon_image_popup_anchor_x']) ? $settings['custom_icon_image_popup_anchor_x'] : 0,
                    'yOffset' => isset($settings['custom_icon_image_popup_anchor_y']) ? $settings['custom_icon_image_popup_anchor_y'] : 0
                ];

                $icon_options = [
                    'iconUrl' => $settings['custom_icon_image']['url'],
                    'shadowUrl' => !empty($settings['custom_icon_shadow_image']['url']) ? $settings['custom_icon_shadow_image']['url'] : null
                ];

                // size of the icon
                if (!empty($settings['custom_icon_image_size_type']) && $settings['custom_icon_image_size_type'] == 'custom' && !empty($icon_size['width']) && !empty($icon_size['height'])) {
                    $icon_options['iconSize'] = [$icon_size['width'], $icon_size['height']];
                }

                // size of the shadow
                if (!empty($settings['custom_icon_shadow_size_type']) && $settings['custom_icon_shadow_size_type'] == 'custom' && !empty($shadow_size['width']) && !empty($shadow_size['height'])) {
                    $icon_options['shadowSize'] = [$shadow_size['width'], $shadow_size['height']];
                }

                // point of the icon which will correspond to marker's location
                if (!empty($settings['custom_icon_image_anchor_type']) && $settings['custom_icon_image_anchor_type'] == 'custom' && is_numeric($icon_anchor['xOffset']) && is_numeric($icon_anchor['yOffset'])) {
                    $icon_options['iconAnchor'] = [$icon_anchor['xOffset'], $icon_anchor['yOffset']];
                }

                // point of the icon which will correspond to marker's location
                // the same for the shadow
                if (!empty($settings['custom_icon_shadow_anchor_type']) && $settings['custom_icon_shadow_anchor_type'] == 'custom' && is_numeric($shadow_anchor['xOffset']) && is_numeric($shadow_anchor['yOffset'])) {
                    $icon_options['shadowAnchor'] = [$shadow_anchor['xOffset'], $shadow_anchor['yOffset']];
                }

                //  point from which the popup should open relative to the iconAnchor
                if (!empty($settings['custom_icon_image_popup_anchor_type']) && $settings['custom_icon_image_popup_anchor_type'] == 'custom' && is_numeric($popup_anchor['xOffset']) && is_numeric($popup_anchor['yOffset'])) {
                    $icon_options['popupAnchor'] = [$popup_anchor['xOffset'], $popup_anchor['yOffset']];
                }
                ?>
                markerIcon = L.icon(<?php echo wp_json_encode($icon_options)?>);
                markerOptions.icon = markerIcon;
                <?php endif; ?>
                <?php break; ?>
                <?php endswitch; ?>

                /**
                 * Helper to add markers to our map
                 * @param {array}
                 */
                const buildMarkers = function (markers) {

                    let _markers = [];
                    jQuery.each(markers, function () {
                        const marker = L.marker([this.lat, this.lng], markerOptions);
                        _markers.push(marker);

                        // add marker to map
                        marker.addTo(map);

                        // prep tooltip content
                        let tooltipContent = '<div class="marker-tooltip">';

                        // add marker title
                        if (this.marker.marker_title) {
                            tooltipContent += `<div class="marker-title"><h5 class="elementor-heading-title elementor-size-default">${this.marker.marker_title}</h5></div>`;
                        }

                        // marker content
                        tooltipContent += '<div class="marker-content">';

                        // add marker description
                        if (this.marker.marker_description) {
                            tooltipContent += `<div class="marker-description">${this.marker.marker_description}</div>`;
                        }

                        // add marker button
                        if (this.marker.show_button === 'yes' && this.marker.button_text) {
                            let button_url_target = this.marker.hasOwnProperty('button_url_target') && this.marker.button_url_target ? this.marker.button_url_target : '_blank';
                            tooltipContent += `<div class="marker-button elementor-button-wrapper">
                                                <a class="elementor-button elementor-button-link" target="${button_url_target}" href='${this.marker.button_url}' role="button">
                                                    <span class="elementor-button-content-wrapper">
                                                        <span class="elementor-button-text">
                                                            ${this.marker.button_text}
                                                        </span>
                                                    </span>
                                                </a>
                                            </div>`;
                        }

                        tooltipContent += '</div>';
                        tooltipContent += '</div>';

                        // add tooltip / popup to marker
                        if (this.marker.marker_title || this.marker.marker_description || this.marker.button_text && this.marker.show_button) {
                            let markerBehavior = this.marker.hasOwnProperty('marker_behavior') ? this.marker.marker_behavior : null;
                            switch (markerBehavior) {
                                case 'popup':
                                    marker.bindPopup(tooltipContent);
                                    break;

                                case 'static_close_on':
                                    marker.bindPopup(tooltipContent,{closeOnClick: false, autoClose: false, closeOnEscapeKey: false}).openPopup();
                                    break;
                                
                                case 'static_close_off':
                                    marker.bindPopup(tooltipContent,{closeOnClick: false, autoClose: false, closeButton: false, closeOnEscapeKey: false}).openPopup();
                                    break;
                                    
                                case 'tooltip':

                                    let tooltipOptions = {};

                                <?php if($icon_type == 'fontawesome'): ?>
                                    // update offset for fontawesome markers
                                    tooltipOptions.direction = "top";
                                    tooltipOptions.offset = [0, -50];
                                <?php  endif; ?>

                                    marker.bindTooltip(tooltipContent, tooltipOptions);
                                    break;

                            }
                        }
                    });

                    setTimeout(function () {
                        map.invalidateSize();
                    }, 100)
                };

                /**
                 * Check whether we can render our map based on provided coordinates
                 * @type {boolean}
                 */
                const canRenderMap = markers.filter(function (marker) {
                    return !isNaN(marker.lat) && !isNaN(marker.lng)
                }).length > 0;

                // we want to make sure we have at least one marker visible
                if (canRenderMap) {
                    buildMarkers(markers);
                } else {

                    // set our default coordinates to Nairobi Kenya
                    let lat = -1.2920659;
                    let lng = 36.8219462;

                    // update the markers with default coordinates
                    markers.push({
                        lat: lat,
                        lng: lng,
                        marker: {
                            button_text: "",
                            button_url: "",
                            marker_coords: {
                                lat,
                                lng
                            },
                            marker_description: "",
                            marker_location: "",
                            marker_title: "",
                            show_button: "no"
                        }
                    });

                    // set center coordinates
                    map.setView([lat, lng], zoomLevel);

                    // build our markers
                    buildMarkers(markers);
                }

            });
            <?php
            $markers_script = ob_get_clean();

            // echo out the markers script while in admin mode.
            // all required scripts will be loaded in header
            echo is_admin() ? $markers_script : null;
            ?>
        </script>
        <?php

        // enqueue marker js only after all dependencies have been queued
        if (!is_admin()) {
            $this->__enqueue_inline_script('marker-' . $this->get_id(), $markers_script, [
                'jquery',
                'leaflet',
                'mapbox-gl',
                'leaflet-mapbox-gl',
                'leaflet-fa-markers'
            ], true);
        }
    }

    /**
     * queue assets needed by maps widget
     */
    private function __queue_assets()
    {
        // grab global settings
        $widget_settings = get_option('osm_widget', []);

        $styles = [
            'leaflet' => plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/leaflet/leaflet.css'),
            'mapbox-gl' => plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/css/mapbox-gl.css'),
            'leaflet-fa-markers' => plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/leaflet-fa-markers/L.Icon.FontAwesome.css'),
            'osm-map-elementor' => plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/css/osm-map-elementor.css')
        ];

        // load fontawesome
        if (!empty($widget_settings) && !array_key_exists('enable_fontawesome', $widget_settings) || !empty($widget_settings['enable_fontawesome'])) {
            $styles['font-awesome-free'] = plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/fontawesome-free/css/all.min.css');
        }

        foreach ($styles as $handle => $path) {
            wp_register_style($handle, $path);
            wp_enqueue_style($handle);
        }

        // queue admin js
        if (is_admin()) {

            // queue google maps key if provided
            $admin_scripts = [
                'osm-map-elementor-controls' => plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/js/osm-map-controls.js')
            ];

            // add google maps API
            if (!empty($widget_settings['gmaps_key'])) {
                $admin_scripts['google-maps'] = 'https://maps.googleapis.com/maps/api/js?libraries=places&callback=initOSMEditorControls&key=' . esc_textarea(__($widget_settings['gmaps_key'], 'your-slug'));
            }

            $dependencies = ['jquery'];
            foreach ($admin_scripts as $handle => $path) {
                wp_register_script($handle, $path, $dependencies, self::$ver);
                wp_enqueue_script($handle);
                $dependencies[] = $handle;
            }
        }

        // queue widget view js
        $scripts = [
            'leaflet' => plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/leaflet/leaflet.js'),
            'mapbox-gl' => plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/js/mapbox-gl.js'),
            'leaflet-mapbox-gl' => plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/leaflet/leaflet-mapbox-gl.js'),
            'leaflet-fa-markers' => plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/leaflet-fa-markers/L.Icon.FontAwesome.js'),
        ];
        $deps = [];
        foreach ($scripts as $handle => $path) {
            wp_register_script($handle, $path, $deps, self::$ver);
            wp_enqueue_script($handle);
            $deps[] = $handle;
        }
    }

    /**
     * Enqueue inline Javascript.
     * @param string $handle Identifying name for script
     * @param $js
     * @param array $deps (optional) Array of script names on which this script depends
     * @param bool $in_footer (optional) Whether to enqueue the script before </head> or before </body>
     *
     * @return null
     * @see wp_enqueue_script().
     *
     * KNOWN BUG: Inline scripts cannot be enqueued before
     *  any inline scripts it depends on, (unless they are
     *  placed in header, and the dependant in footer).
     */
    private function __enqueue_inline_script($handle, $js, $deps = array(), $in_footer = false)
    {
        // Callback for printing inline script.
        $cb = function () use ($handle, $js) {
            // Ensure script is only included once.
            if (wp_script_is($handle, 'done'))
                return;
            // Print script & mark it as included.
            echo "<script type=\"text/javascript\" id=\"js-$handle\">\n$js\n</script>\n";
            global $wp_scripts;
            $wp_scripts->done[] = $handle;
        };
        // (`wp_print_scripts` is called in header and footer, but $cb has re-inclusion protection.)
        $hook = $in_footer ? 'wp_print_footer_scripts' : 'wp_print_scripts';

        // If no dependencies, simply hook into header or footer.
        if (empty($deps)) {
            add_action($hook, $cb);
            return;
        }

        // Delay printing script until all dependencies have been included.
        $cb_maybe = function () use ($deps, $in_footer, $cb, &$cb_maybe) {
            foreach ($deps as &$dep) {
                if (!wp_script_is($dep, 'done')) {
                    // Dependencies not included in head, try again in footer.
                    if ($in_footer) {
                        add_action('wp_print_footer_scripts', $cb_maybe, 11);
                    } else {
                        // Dependencies were not included in `wp_head` or `wp_footer`.
                    }
                    return;
                }
            }
            call_user_func($cb);
        };
        add_action($hook, $cb_maybe, 0);
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
     * Prints out debug information about given variable.
     *
     * @param boolean $var Variable to show debug information for.
     * @param boolean $showHtml If set to true, the method prints the debug data in a screen-friendly way.
     * @param boolean $showFrom If set to true, the method prints from where the function was called.
     */
    private function __debug($var = false, $showHtml = false, $showFrom = true)
    {
        if ($showFrom) {
            $calledFrom = debug_backtrace();
            echo '<strong>' . substr($calledFrom[0]['file'], 1) . '</strong>';
            echo ' (line <strong>' . $calledFrom[0]['line'] . '</strong>)';
        }
        echo "\n<pre class=\"fi-debug\">\n";

        $var = print_r($var, true);
        if ($showHtml) {
            $var = str_replace('<', '&lt;', str_replace('>', '&gt;', $var));
        }
        echo $var . "\n</pre>\n";
    }
}