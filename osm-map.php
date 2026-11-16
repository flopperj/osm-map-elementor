<?php

namespace OSM_Map;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once('constants.php');

use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
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

    private const OSM_MARKER_SIZE_MIN = 25;
    private const OSM_MARKER_SIZE_MAX = 200;
    // Mirrors L.Icon.Default in the vendored Leaflet; Test_Vendored_Marker_Geometry fails if they drift.
    private const OSM_DEFAULT_ICON_GEOMETRY = [
        'iconSize' => [25, 41],
        'iconAnchor' => [12, 41],
        'popupAnchor' => [1, -34],
        'tooltipAnchor' => [16, -28],
        'shadowSize' => [41, 41],
    ];
    private const OSM_FA_MARKER_TOP = -50;

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
        $this->osm_queue_assets();
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
        return __('OSM Map', 'osm-map-elementor');
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
     * Tiles selectable in the widget; also the allowlist applied when rendering.
     *
     * @return string[]
     */
    private static function osm_tile_options()
    {
        return [
            'osm-carto' => __('OSM Carto (Free)', 'osm-map-elementor'),
            'osm-bright' => __('Geoapify - OSM Bright', 'osm-map-elementor'),
            'osm-bright-grey' => __('Geoapify - OSM Bright Grey', 'osm-map-elementor'),
            'osm-bright-smooth' => __('Geoapify - OSM Bright Smooth', 'osm-map-elementor'),
            'klokantech-basic' => __('Geoapify - Klokantech Basic', 'osm-map-elementor'),
            'osm-liberty' => __('Geoapify - OSM Liberty', 'osm-map-elementor'),
            'maptiler-3d' => __('Geoapify - MapTiler 3D', 'osm-map-elementor'),
            'toner' => __('Geoapify - Toner', 'osm-map-elementor'),
            'toner-grey' => __('Geoapify - Toner Grey', 'osm-map-elementor'),
            'positron' => __('Geoapify - Positron', 'osm-map-elementor'),
            'positron-blue' => __('Geoapify - Positron Blue', 'osm-map-elementor'),
            'positron-red' => __('Geoapify - Positron Red', 'osm-map-elementor'),
            'dark-matter' => __('Geoapify - Dark Matter', 'osm-map-elementor'),
            'dark-matter-brown' => __('Geoapify - Dark Matter Brown', 'osm-map-elementor'),
            'dark-matter-dark-grey' => __('Geoapify - Dark Matter Dark Grey', 'osm-map-elementor'),
            'dark-matter-dark-purple' => __('Geoapify - Dark Matter Dark Purple', 'osm-map-elementor'),
            'dark-matter-purple-roads' => __('Geoapify - Dark Matter Purple Roads', 'osm-map-elementor'),
            'dark-matter-yellow-roads' => __('Geoapify - Dark Matter Yellow Roads', 'osm-map-elementor'),
            'stadia-osm-bright' => __('Stadia - OSM Bright', 'osm-map-elementor'),
            'stadia-outdoors' => __('Stadia - Outdoors', 'osm-map-elementor'),
            'stadia-alidade-smooth' => __('Stadia - Alidade Smooth', 'osm-map-elementor'),
            'stadia-alidade-smooth-dark' => __('Stadia - Alidade Smooth Dark', 'osm-map-elementor'),
            'stadia-alidade-satellite' => __('Stadia - Alidade Satellite', 'osm-map-elementor'),
            'stadia-stamen-toner' => __('Stadia - Stamen Toner', 'osm-map-elementor'),
            'stadia-stamen-toner-lite' => __('Stadia - Stamen Toner Lite', 'osm-map-elementor'),
            'stadia-stamen-terrain' => __('Stadia - Stamen Terrain', 'osm-map-elementor'),
            'stadia-stamen-watercolor' => __('Stadia - Stamen Watercolor', 'osm-map-elementor'),
            'custom-tile' => __('Custom Map Tile', 'osm-map-elementor'),
        ];
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
        $this->osm_register_content_controls();

        // register style controls
        $this->osm_register_style_controls();
    }

    private function osm_settings_note($message, array $leading_tags = [])
    {
        $link_open = '<a href="' . esc_url(admin_url('options-general.php?page=osm-map-elementor')) . '" target="_blank" rel="noopener">';

        return '<div class="elementor-control-field-description">'
            . wp_kses(
                vsprintf($message, array_merge($leading_tags, [$link_open, '</a>'])),
                [
                    'a' => ['href' => true, 'target' => true, 'rel' => true],
                    'strong' => [],
                ]
            )
            . '</div>';
    }

    /**
     * Registers content controls
     */
    private function osm_register_content_controls()
    {

        $repeater = new Repeater();

        $repeater->add_control(
            'marker_title',
            [
                'label' => __('Title', 'osm-map-elementor'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Marker Title', 'osm-map-elementor'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'marker_location',
            [
                'label' => __('Location', 'osm-map-elementor'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Marker Location', 'osm-map-elementor'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'marker_coords',
            [
                'label' => __('Coordinates', 'osm-map-elementor'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('lat, long', 'osm-map-elementor'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'marker_description',
            [
                'label' => __('Description', 'osm-map-elementor'),
                'type' => Controls_Manager::TEXTAREA,
                'placeholder' => __('Marker Description', 'osm-map-elementor'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'marker_behavior',
            [
                'label' => __('Behavior', 'osm-map-elementor'),
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
                'label' => __('Show Button', 'osm-map-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'osm-map-elementor'),
                'label_off' => __('Hide', 'osm-map-elementor'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $repeater->add_control(
            'button_text',
            [
                'label' => __('Button Text', 'osm-map-elementor'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Button Text', 'osm-map-elementor'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'button_url',
            [
                'label' => __('Button URL', 'osm-map-elementor'),
                'type' => Controls_Manager::TEXT,
                'input_type' => 'url',
                'placeholder' => __('https://your-link.com', 'osm-map-elementor'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $repeater->add_control(
            'button_url_target',
            [
                'label' => __('URL target', 'osm-map-elementor'),
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
                'label' => __('Show Marker', 'osm-map-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'osm-map-elementor'),
                'label_off' => __('Hide', 'osm-map-elementor'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );


        $this->start_controls_section(
            'section_map',
            [
                'label' => __('Map', 'osm-map-elementor'),
            ]
        );

        $this->add_control(
            'important_note',
            [
                'label' => __('Important Note', 'osm-map-elementor'),
                'type' => Controls_Manager::RAW_HTML,
                'raw' => $this->osm_settings_note(
                    /* translators: 1: opening link tag to the plugin settings page, 2: closing link tag. */
                    esc_html__('To take advantage of custom tiles and auto-population of coordinates in markers, please update API keys in global settings %1$shere%2$s', 'osm-map-elementor')
                )
            ]
        );

        $this->add_responsive_control(
            'zoom',
            [
                'label' => __('Zoom Level', 'osm-map-elementor'),
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
                'label' => __('Zoom Control', 'osm-map-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'osm-map-elementor'),
                'label_off' => __('Disable', 'osm-map-elementor'),
                'return_value' => 'yes',
                'default' => 'yes'
            ]
        );

        $this->add_control(
            'scroll_zoom',
            [
                'label' => __('Scroll Zoom', 'osm-map-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'osm-map-elementor'),
                'label_off' => __('Disable', 'osm-map-elementor'),
                'return_value' => 'yes',
                'default' => 'yes'
            ]
        );

        $this->add_control(
            'pan_control',
            [
                'label' => __('Pan Control', 'osm-map-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'osm-map-elementor'),
                'label_off' => __('Disable', 'osm-map-elementor'),
                'return_value' => 'yes',
                'default' => 'yes',
                'separator' => 'after'
            ]
        );

        $this->add_control(
            'marker_list',
            [
                'label' => __('Marker List', 'osm-map-elementor'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    []
                ],
                'title_field' => '{{ marker_title }}'
            ]
        );

        $this->add_control(
            'view',
            [
                'label' => __('View', 'osm-map-elementor'),
                'type' => Controls_Manager::HIDDEN,
                'default' => 'traditional',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Registers style controls
     */
    private function osm_register_style_controls()
    {

        $global_settings = get_option('osm_widget');

        // START Map Section
        $this->start_controls_section(
            'section_map_style',
            [
                'label' => __('Map', 'osm-map-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // show notice about api keys
        if (empty($global_settings['mapbox_token']) || empty($global_settings['geoapify_key'])) {
            $this->add_control(
                'important_note2',
                [
                    'label' => __('Important Note', 'osm-map-elementor'),
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => $this->osm_settings_note(
                        /* translators: 1: opening link tag to the plugin settings page, 2: closing link tag. */
                        esc_html__('To take advantage of custom tiles in markers, please update API keys in global settings %1$shere%2$s', 'osm-map-elementor')
                    ),
                    'separator' => 'after'
                ]
            );
        }

        $this->add_control(
            'geoapify_tile',
            [
                'label' => __('Tile', 'osm-map-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'osm-carto',
                'options' => self::osm_tile_options()
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
                'label' => __('Width', 'osm-map-elementor'),
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
                'label' => __('Height', 'osm-map-elementor'),
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
                'label' => __('z-index', 'osm-map-elementor'),
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
                'label' => __('Marker Icon', 'osm-map-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );


        $this->add_control(
            'fontawesome_important_note',
            [
                'label' => __('Important Note', 'osm-map-elementor'),
                'type' => Controls_Manager::RAW_HTML,
                'raw' => $this->osm_settings_note(
                    /* translators: 1: opening strong tag, 2: closing strong tag, 3: opening link tag to the plugin settings page, 4: closing link tag. */
                    esc_html__('If you\'re having conflicts with the widget\'s Font Awesome library, please toggle the %1$sFont Awesome%2$s setting in the global settings %3$shere%4$s', 'osm-map-elementor'),
                    ['<strong>', '</strong>']
                ),
                'condition' => [
                    'icon_type' => 'fontawesome'
                ]
            ]
        );


        $this->add_control(
            'icon_type',
            [
                'label' => __('Icon Type', 'osm-map-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map-elementor'),
                    'fontawesome' => __('Font Awesome', 'osm-map-elementor'),
                    'custom_image' => __('Custom Image', 'osm-map-elementor'),
                ]
            ]
        );

        $this->add_control(
            'marker_size',
            [
                'label' => __('Marker Size', 'osm-map-elementor'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['%'],
                'range' => [
                    '%' => [
                        'min' => self::OSM_MARKER_SIZE_MIN,
                        'max' => self::OSM_MARKER_SIZE_MAX,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 100,
                ],
                'description' => __('Scales the whole marker, including its icon and where its popup or tooltip opens.', 'osm-map-elementor'),
                'condition' => [
                    'icon_type' => ['', 'fontawesome'],
                ],
            ]
        );

        $this->add_control(
            'fontawesome_icon',
            [
                'label' => __('Font Awesome Icon', 'osm-map-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => 'fa fa-circle',
                'placeholder' => __('fa fa-circle', 'osm-map-elementor'),
                'condition' => [
                    'icon_type' => 'fontawesome'
                ]
            ]
        );

        $this->add_control(
            'marker_background_color',
            [
                'label' => __('Background Color', 'osm-map-elementor'),
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
                'label' => __('Stroke Color', 'osm-map-elementor'),
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
                'label' => __('Icon Color', 'osm-map-elementor'),
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
                'label' => __('Icon Offset', 'osm-map-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map-elementor'),
                    'custom' => __('Custom', 'osm-map-elementor'),
                ],
                'condition' => [
                    'icon_type' => 'fontawesome'
                ]
            ]
        );

        $this->add_control(
            'icon_offset_x',
            [
                'label' => __('x Offset', 'osm-map-elementor'),
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
                'label' => __('y Offset', 'osm-map-elementor'),
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
                'label' => __('Icon Size', 'osm-map-elementor'),
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
                'label' => __('Main', 'osm-map-elementor'),
            ]
        );

        $this->add_control(
            'custom_icon_image',
            [
                'label' => __('Choose Image', 'osm-map-elementor'),
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
                'label' => __('Size', 'osm-map-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map-elementor'),
                    'custom' => __('Custom', 'osm-map-elementor'),
                ]
            ]
        );

        $this->add_control(
            'custom_icon_image_width',
            [
                'label' => __('Width', 'osm-map-elementor'),
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
                'label' => __('Height', 'osm-map-elementor'),
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
                'label' => __('Anchor', 'osm-map-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'custom',
                'options' => [
                    '' => __('Default', 'osm-map-elementor'),
                    'custom' => __('Custom', 'osm-map-elementor'),
                ]
            ]
        );

        $this->add_control(
            'custom_icon_image_anchor_x',
            [
                'label' => __('x Offset', 'osm-map-elementor'),
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
                'label' => __('y Offset', 'osm-map-elementor'),
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
                'label' => __('Shadow', 'osm-map-elementor'),
            ]
        );
        $this->add_control(
            'custom_icon_shadow_image',
            [
                'label' => __('Choose Image', 'osm-map-elementor'),
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
                'label' => __('Size', 'osm-map-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map-elementor'),
                    'custom' => __('Custom', 'osm-map-elementor'),
                ]
            ]
        );

        $this->add_control(
            'custom_icon_shadow_width',
            [
                'label' => __('Width', 'osm-map-elementor'),
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
                'label' => __('Height', 'osm-map-elementor'),
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
                'label' => __('Anchor', 'osm-map-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map-elementor'),
                    'custom' => __('Custom', 'osm-map-elementor'),
                ]
            ]
        );

        $this->add_control(
            'custom_icon_shadow_anchor_x',
            [
                'label' => __('x Offset', 'osm-map-elementor'),
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
                'label' => __('y Offset', 'osm-map-elementor'),
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
                'label' => __('Popup Anchor', 'osm-map-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    '' => __('Default', 'osm-map-elementor'),
                    'custom' => __('Custom', 'osm-map-elementor'),
                ],
                'condition' => [
                    'icon_type' => 'custom_image'
                ]
            ]
        );


        $this->add_control(
            'custom_icon_image_popup_anchor_x',
            [
                'label' => __('x Offset', 'osm-map-elementor'),
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
                'label' => __('y Offset', 'osm-map-elementor'),
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
                'label' => __('Marker Title', 'osm-map-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'title_align',
            [
                'label' => __('Alignment', 'osm-map-elementor'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'osm-map-elementor'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'osm-map-elementor'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'osm-map-elementor'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __('Justified', 'osm-map-elementor'),
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
                'label' => __('Text Color', 'osm-map-elementor'),
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
                'label' => __('Padding', 'osm-map-elementor'),
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
                'label' => __('Margin', 'osm-map-elementor'),
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
                'label' => __('Marker Content', 'osm-map-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_responsive_control(
            'content_align',
            [
                'label' => __('Alignment', 'osm-map-elementor'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'osm-map-elementor'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'osm-map-elementor'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'osm-map-elementor'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __('Justified', 'osm-map-elementor'),
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
                'label' => __('Text Color', 'osm-map-elementor'),
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
                'label' => __('Typography', 'osm-map-elementor'),
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
                ],
                'selector' => '{{WRAPPER}} .marker-content .marker-description',
            ]
        );

        $this->add_responsive_control(
            'content_padding',
            [
                'label' => __('Padding', 'osm-map-elementor'),
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
                'label' => __('Margin', 'osm-map-elementor'),
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
                'label' => __('Marker Button', 'osm-map-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'button_align',
            [
                'label' => __('Alignment', 'osm-map-elementor'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'osm-map-elementor'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'osm-map-elementor'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'osm-map-elementor'),
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
                'label' => __('Normal', 'osm-map-elementor'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Text Color', 'osm-map-elementor'),
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
                'label' => __('Background Color', 'osm-map-elementor'),
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
                'label' => __('Hover', 'osm-map-elementor'),
            ]
        );

        $this->add_control(
            'button_hover_color',
            [
                'label' => __('Text Color', 'osm-map-elementor'),
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
                'label' => __('Background Color', 'osm-map-elementor'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .marker-button .elementor-button:hover, {{WRAPPER}} .marker-button .elementor-button:focus' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_border_color',
            [
                'label' => __('Border Color', 'osm-map-elementor'),
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
                'label' => __('Hover Animation', 'osm-map-elementor'),
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
                'label' => __('Border Radius', 'osm-map-elementor'),
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
                'label' => __('Padding', 'osm-map-elementor'),
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
                'label' => __('Margin', 'osm-map-elementor'),
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
        $breakpoints_config = \Elementor\Plugin::$instance->breakpoints->get_breakpoints_config();
        $settings['breakpoints'] = [
            'sm' => (int)($breakpoints_config['mobile']['value'] ?? 767),
            'md' => (int)($breakpoints_config['tablet']['value'] ?? 1024),
        ];

        // Ensure zoom settings exist and have proper defaults
        if (!isset($settings['zoom']) || !is_array($settings['zoom'])) {
            $settings['zoom'] = ['size' => 10];
        } elseif (!isset($settings['zoom']['size']) || 0 === absint($settings['zoom']['size'])) {
            $settings['zoom']['size'] = 10;
        }

        // Ensure height settings exist and have proper defaults
        if (!isset($settings['height']) || !is_array($settings['height'])) {
            $settings['height'] = ['size' => 200];
        } elseif (!isset($settings['height']['size']) || 0 === absint($settings['height']['size'])) {
            $settings['height']['size'] = 200;
        }

        // get all marker coords to help calculate center
        $coords = [];
        if (is_array($markers)) {
            foreach ($markers as $marker) {

                // hide markers that have been toggled off
                if (isset($marker['marker_visible']) && empty($marker['marker_visible'])) {
                    continue;
                }

                $parsed = $this->osm_parse_marker_coords($marker['marker_coords'] ?? null);
                if (null === $parsed) {
                    continue;
                }

                $marker['button_url'] = !empty($marker['button_url']) ? esc_url_raw($marker['button_url'], ['http', 'https', 'mailto', 'tel']) : '';
                $marker['button_url_target'] = in_array($marker['button_url_target'] ?? '', ['_self', '_blank'], true) ? $marker['button_url_target'] : '_blank';
                $coords[] = [
                    'marker' => $marker,
                    'lat' => $parsed['lat'],
                    'lng' => $parsed['lng']
                ];
            }
        }

        $custom_tile_url = !empty($global_settings['osm_custom']) ? $global_settings['osm_custom'] : '';
        $custom_attribution = '<a href="' . esc_url(!empty($global_settings['osm_custom_attribution_url']) ? $global_settings['osm_custom_attribution_url'] : '') . '" target="_blank" rel="noopener noreferrer">'
            . esc_html(!empty($global_settings['osm_custom_attribution']) ? $global_settings['osm_custom_attribution'] : '')
            . '</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>';
        $requested_tile = isset($settings['geoapify_tile']) && is_string($settings['geoapify_tile']) ? $settings['geoapify_tile'] : '';
        $geoapify_tile = array_key_exists($requested_tile, self::osm_tile_options()) ? $requested_tile : 'osm-carto';

        // get center coordinates for our map
        $center_coords = !empty($coords) ? $this->osm_get_center_coords($coords) : [];

        echo '<div id="' . esc_attr('osm-map-' . $this->get_id()) . '" 
        class="osm-map-container" 
        data-center="' . esc_attr(implode(',', $center_coords)) . '"></div>';

        ob_start();
        ?>
            jQuery(function () {
                "use strict";
                const displaySettings = <?php echo wp_json_encode($settings); ?>;
                const mapId = '<?php echo esc_js('osm-map-' . $this->get_id()); ?>';
                const mapContainer = jQuery('#' + mapId);
                const center = mapContainer.data('center');

                // Security: HTML escaping functions to prevent XSS
                const escapeHtml = function (text) {
                    if (!text) return '';
                    const map = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    };
                    return text.toString().replace(/[&<>"']/g, function (m) {
                        return map[m];
                    });
                };

                // Security: URL escaping function to prevent XSS in href attributes
                const escapeUrl = function (url) {
                    if (!url) return '#';
                    try {
                        const parsed = new URL(url, window.location.href);
                        return ['http:', 'https:', 'mailto:', 'tel:'].includes(parsed.protocol) ? escapeHtml(url) : '#';
                    } catch (e) {
                        return '#';
                    }
                };
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

                <?php if('osm-carto' === $geoapify_tile):?>
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( 'stadia-osm-bright' === $geoapify_tile):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank" rel="noopener noreferrer">Stadia Maps</a> | &copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener noreferrer">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( 'stadia-outdoors' === $geoapify_tile):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/outdoors/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank" rel="noopener noreferrer">Stadia Maps</a> | &copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener noreferrer">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( 'stadia-alidade-smooth' === $geoapify_tile):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank" rel="noopener noreferrer">Stadia Maps</a> | &copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener noreferrer">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( 'stadia-alidade-smooth-dark' === $geoapify_tile):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank" rel="noopener noreferrer">Stadia Maps</a> | &copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener noreferrer">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( 'stadia-alidade-satellite' === $geoapify_tile):?>
                L.tileLayer('https://tiles.stadiamaps.com/data/satellite/{z}/{x}/{y}.jpg', {
                    attribution: '&copy; CNES, Distribution Airbus DS, &copy; Airbus DS, &copy; PlanetObserver (Contains Copernicus Data) | &copy; <a href="https://stadiamaps.com/" target="_blank" rel="noopener noreferrer">Stadia Maps</a> | &copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener noreferrer">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( 'stadia-stamen-toner' === $geoapify_tile):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_toner/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank" rel="noopener noreferrer">Stadia Maps</a> | &copy; <a href="https://stamen.com/" target="_blank" rel="noopener noreferrer">Stamen Design</a> | &copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener noreferrer">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( 'stadia-stamen-toner-lite' === $geoapify_tile):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_toner_lite/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank" rel="noopener noreferrer">Stadia Maps</a> | &copy; <a href="https://stamen.com/" target="_blank" rel="noopener noreferrer">Stamen Design</a> | &copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener noreferrer">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( 'stadia-stamen-terrain' === $geoapify_tile):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_terrain/{z}/{x}/{y}@2x.png', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank" rel="noopener noreferrer">Stadia Maps</a> | &copy; <a href="https://stamen.com/" target="_blank" rel="noopener noreferrer">Stamen Design</a> | &copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener noreferrer">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
                    maxZoom: 20
                }).addTo(map);

                <?php elseif( 'stadia-stamen-watercolor' === $geoapify_tile):?>
                L.tileLayer('https://tiles.stadiamaps.com/tiles/stamen_watercolor/{z}/{x}/{y}.jpg', {
                    attribution: '&copy; <a href="https://stadiamaps.com/" target="_blank" rel="noopener noreferrer">Stadia Maps</a> | &copy; <a href="https://stamen.com/" target="_blank" rel="noopener noreferrer">Stamen Design</a> | &copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener noreferrer">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>',
                    maxZoom: 16
                }).addTo(map);

                <?php elseif( 'custom-tile' === $geoapify_tile):?>
                L.tileLayer(<?php echo wp_json_encode($custom_tile_url); ?>, {
                    attribution: <?php echo wp_json_encode($custom_attribution); ?>,
                    maxZoom: 20
                }).addTo(map);

                <?php else: ?>

                // the attribution is required for the Geoapify Free tariff plan

                map.attributionControl.setPrefix('').addAttribution('Powered by <a href="https://www.geoapify.com/" target="_blank" rel="noopener noreferrer">Geoapify</a> | &copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener noreferrer">OpenMapTiles</a> | &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>');
                // install leaflet-mapbox-gl plugin

                L.mapboxGL({
                    style: <?php echo wp_json_encode(sprintf('https://maps.geoapify.com/v1/styles/%s/style.json?apiKey=%s', rawurlencode($geoapify_tile), rawurlencode(!empty($global_settings['geoapify_key']) ? $global_settings['geoapify_key'] : ''))); ?>,
                    accessToken: <?php echo wp_json_encode(!empty($global_settings['mapbox_token']) ? $global_settings['mapbox_token'] : 'no-token'); ?>
                }).addTo(map);

                <?php endif; ?>

                // add available markers
                const markers = <?php echo wp_json_encode($coords); ?>;
                let markerIcon = null;
                let markerOptions = {};

                <?php
                $icon_type = !empty($settings['icon_type']) ? $settings['icon_type'] : null;
                $marker_scale = $this->osm_marker_scale($settings['marker_size'] ?? null);
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
                if (!empty($settings['icon_offset_type']) && 'custom' === $settings['icon_offset_type']) {
                    $fa_icon_options['iconXOffset'] = $icon_offset['xOffset'];
                    $fa_icon_options['iconYOffset'] = $icon_offset['yOffset'];
                }

                if (1.0 !== $marker_scale) {
                    $fa_icon_options['markerScale'] = $marker_scale;
                    $fa_icon_options['popupAnchor'] = self::osm_scale_point([0, self::OSM_FA_MARKER_TOP], $marker_scale);
                }
                $fa_tooltip_offset = self::osm_scale_point([0, self::OSM_FA_MARKER_TOP], $marker_scale);
                ?>

                const markerIconOptions = <?php echo wp_json_encode($fa_icon_options); ?>;
                const markerTooltipOffset = <?php echo wp_json_encode($fa_tooltip_offset); ?>;
                markerOptions.icon = L.icon.fontAwesome(markerIconOptions);
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
                if (!empty($settings['custom_icon_image_size_type']) && 'custom' === $settings['custom_icon_image_size_type'] && !empty($icon_size['width']) && !empty($icon_size['height'])) {
                    $icon_options['iconSize'] = [$icon_size['width'], $icon_size['height']];
                }

                // size of the shadow
                if (!empty($settings['custom_icon_shadow_size_type']) && 'custom' === $settings['custom_icon_shadow_size_type'] && !empty($shadow_size['width']) && !empty($shadow_size['height'])) {
                    $icon_options['shadowSize'] = [$shadow_size['width'], $shadow_size['height']];
                }

                // point of the icon which will correspond to marker's location
                if (!empty($settings['custom_icon_image_anchor_type']) && 'custom' === $settings['custom_icon_image_anchor_type'] && is_numeric($icon_anchor['xOffset']) && is_numeric($icon_anchor['yOffset'])) {
                    $icon_options['iconAnchor'] = [$icon_anchor['xOffset'], $icon_anchor['yOffset']];
                }

                // point of the icon which will correspond to marker's location
                // the same for the shadow
                if (!empty($settings['custom_icon_shadow_anchor_type']) && 'custom' === $settings['custom_icon_shadow_anchor_type'] && is_numeric($shadow_anchor['xOffset']) && is_numeric($shadow_anchor['yOffset'])) {
                    $icon_options['shadowAnchor'] = [$shadow_anchor['xOffset'], $shadow_anchor['yOffset']];
                }

                //  point from which the popup should open relative to the iconAnchor
                if (!empty($settings['custom_icon_image_popup_anchor_type']) && 'custom' === $settings['custom_icon_image_popup_anchor_type'] && is_numeric($popup_anchor['xOffset']) && is_numeric($popup_anchor['yOffset'])) {
                    $icon_options['popupAnchor'] = [$popup_anchor['xOffset'], $popup_anchor['yOffset']];
                }
                ?>
                markerIcon = L.icon(<?php echo wp_json_encode($icon_options)?>);
                markerOptions.icon = markerIcon;
                <?php endif; ?>
                <?php
                break;
                default:
                if (1.0 !== $marker_scale):
                    $default_icon_options = array_map(function ($point) use ($marker_scale) {
                        return self::osm_scale_point($point, $marker_scale);
                    }, self::OSM_DEFAULT_ICON_GEOMETRY);
                ?>
                const markerIconOptions = <?php echo wp_json_encode($default_icon_options); ?>;
                markerOptions.icon = new L.Icon.Default(markerIconOptions);
                <?php endif; ?>
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
                            tooltipContent += `<div class="marker-title"><h5 class="elementor-heading-title elementor-size-default">${escapeHtml(this.marker.marker_title)}</h5></div>`;
                        }

                        // marker content
                        tooltipContent += '<div class="marker-content">';

                        // add marker description
                        if (this.marker.marker_description) {
                            tooltipContent += `<div class="marker-description">${escapeHtml(this.marker.marker_description)}</div>`;
                        }

                        // add marker button
                        if (this.marker.show_button === 'yes' && this.marker.button_text) {
                            let button_url_target = this.marker.hasOwnProperty('button_url_target') && this.marker.button_url_target ? escapeHtml(this.marker.button_url_target) : '_blank';
                            let button_url = this.marker.button_url ? escapeUrl(this.marker.button_url) : '#';
                            const button_rel = button_url_target === '_blank' ? ' rel="noopener"' : '';
                            tooltipContent += `<div class="marker-button elementor-button-wrapper">
                                                <a class="elementor-button elementor-button-link" target="${button_url_target}" href="${button_url}" role="button"${button_rel}>
                                                    <span class="elementor-button-content-wrapper">
                                                        <span class="elementor-button-text">
                                                            ${escapeHtml(this.marker.button_text)}
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
                                    marker.bindPopup(tooltipContent, {
                                        closeOnClick: false,
                                        autoClose: false,
                                        closeOnEscapeKey: false
                                    }).openPopup();
                                    break;

                                case 'static_close_off':
                                    marker.bindPopup(tooltipContent, {
                                        closeOnClick: false,
                                        autoClose: false,
                                        closeButton: false,
                                        closeOnEscapeKey: false
                                    }).openPopup();
                                    break;

                                case 'tooltip':

                                    let tooltipOptions = {};

                                <?php if('fontawesome' === $icon_type): ?>
                                    // update offset for fontawesome markers
                                    tooltipOptions.direction = "top";
                                    tooltipOptions.offset = markerTooltipOffset;
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

        if (is_admin()) {
            wp_print_inline_script_tag($markers_script, ['id' => 'js-marker-' . $this->get_id()]);
        } else {
            wp_add_inline_script('leaflet-fa-markers', $markers_script);
        }
    }

    /**
     * queue assets needed by maps widget
     */
    private function osm_queue_assets()
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
        if ((!empty($widget_settings) && !array_key_exists('enable_fontawesome', $widget_settings)) || !empty($widget_settings['enable_fontawesome'])) {
            $styles['font-awesome-free'] = plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/fontawesome-free/css/all.min.css');
        }

        foreach ($styles as $handle => $path) {
            wp_register_style($handle, $path, [], self::$ver);
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
                $admin_scripts['google-maps'] = add_query_arg([
                    'libraries' => 'places',
                    'callback' => 'initOSMEditorControls',
                    'loading' => 'async',
                    'key' => rawurlencode($widget_settings['gmaps_key']),
                ], 'https://maps.googleapis.com/maps/api/js');
            }

            $dependencies = ['jquery'];
            foreach ($admin_scripts as $handle => $path) {
                wp_register_script($handle, $path, $dependencies, 'google-maps' === $handle ? null : self::$ver, true);
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
        $deps = ['jquery'];
        foreach ($scripts as $handle => $path) {
            wp_register_script($handle, $path, $deps, self::$ver, true);
            wp_enqueue_script($handle);
            $deps[] = $handle;
        }
    }

    /**
     * @param mixed $setting Elementor slider value.
     */
    private function osm_marker_scale($setting): float
    {
        if (!is_array($setting) || !isset($setting['size']) || !is_numeric($setting['size'])) {
            return 1.0;
        }

        $percent = max(self::OSM_MARKER_SIZE_MIN, min(self::OSM_MARKER_SIZE_MAX, (float) $setting['size']));

        return $percent / 100;
    }

    /**
     * @param int[] $point
     * @return int[]
     */
    private static function osm_scale_point(array $point, float $scale): array
    {
        return [(int) round($point[0] * $scale), (int) round($point[1] * $scale)];
    }

    /**
     * Parses a "lat,lng" string into floats, rejecting malformed or out-of-range values.
     *
     * @param mixed $raw
     * @return array{lat: float, lng: float}|null
     */
    private function osm_parse_marker_coords($raw)
    {
        if (!is_string($raw)) {
            return null;
        }

        $parts = array_map('trim', explode(',', $raw));
        if (2 !== count($parts) || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
            return null;
        }

        $lat = (float)$parts[0];
        $lng = (float)$parts[1];
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * Calculates center coordinates given a list of coordinates
     * @param $coords
     * @return float[]|int[]
     */
    private function osm_get_center_coords($coords)
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
}
