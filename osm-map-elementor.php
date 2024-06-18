<?php
/**
 * Plugin Name:     OSM Map Widget for Elementor
 * Description:     A free Elementor Map Widget that Utilizes Open Street Map. Comes with features like adding multiple markers, and choosing from a library of custom tiles to change the look and feel.
 * Author:          Plugin Contributors
 * Author URI:      https://github.com/flopperj/osm-map-elementor/graphs/contributors
 * Version:         1.3.0
 */

namespace OSM_Map;

use Elementor\Plugin;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once('constants.php');

// The Widget_Base class is not available immediately after plugins are loaded, so
// we delay the class' use until Elementor widgets are registered
add_action('elementor/widgets/register', function () {
    require_once('osm-map.php');

    $osm_map = new Widget_OSM_Map();

    // Let Elementor know about our widget
    Plugin::instance()
        ->widgets_manager
        ->register($osm_map);
});


// Add global settings for our widget
add_action('admin_menu', function () {
    add_options_page('OSM Map Widget', 'OSM Map Widget', 'manage_options', 'osm-map-elementor', function () {

        // queue admin styles
        wp_register_style('osm-map-admin', plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/css/admin.css'));
        wp_enqueue_style('osm-map-admin');

        $action = !empty($_REQUEST['action']) && is_string($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : null;

        // grab settings, sanitize, validate and save them
        if (!empty($action) && $action == 'save_settings' && isset($_REQUEST['osm_widget']) && is_array($_REQUEST['osm_widget'])) {

            $input = isset($_REQUEST['osm_widget']) ? $_REQUEST['osm_widget'] : [];

            // sanitize user input
            $osm_settings = [
                'gmaps_key' => !empty($input['gmaps_key']) ? sanitize_text_field($input['gmaps_key']) : null,
                'mapbox_token' => !empty($input['mapbox_token']) ? sanitize_text_field($input['mapbox_token']) : null,
                'geoapify_key' => !empty($input['geoapify_key']) ? sanitize_text_field($input['geoapify_key']) : null,
                'osm_custom' => !empty($input['osm_custom']) ? sanitize_text_field($input['osm_custom']) : null,
                'osm_custom_attribution' => !empty($input['osm_custom_attribution']) ? sanitize_text_field($input['osm_custom_attribution']) : null,
                'osm_custom_attribution_url' => !empty($input['osm_custom_attribution_url']) ? sanitize_text_field($input['osm_custom_attribution_url']) : null,
                'enable_fontawesome' => !empty($input['enable_fontawesome']) ? sanitize_text_field($input['enable_fontawesome']) : null
            ];

            // save the sanitized data
            update_option('osm_widget', $osm_settings);

            // redirect to form with confirmation alert message
            wp_redirect($_SERVER['HTTP_REFERER'] . '&action=settings_saved');
        }

        $osm_settings = get_option('osm_widget');

        ?>
        <div id="osm-map-settings">
            <h2>OSM Map Widget Settings</h2>
            <?php if (!empty($_REQUEST['action']) && sanitize_key($_REQUEST['action']) == 'settings_saved'): ?>
                <div style="background-color: rgb(255, 251, 204);" id="alert-message" class="updated"><p>
                        <strong><?php echo __('Settings saved') ?>.</strong></p></div>
            <?php endif; ?>
            <form action="<?php echo $_SERVER['REQUEST_URI'] . '&action=save_settings'; ?>" method="post">
                <div class="form-group">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Google Maps API Key</h3>
                        </div>
                        <div class="card-content">
                            <p><strong>Note:</strong> This setting is required if you wish to use Google Maps to
                                lookup location coordinates. Need help to get a Google map API key? <a
                                        href="https://developers.google.com/maps/documentation/javascript/get-api-key"
                                        target="_blank">Read this resource</a>.</p>
                            <input type="text" name="osm_widget[gmaps_key]"
                                   value="<?php echo !empty($osm_settings['gmaps_key']) ? esc_textarea(__($osm_settings['gmaps_key'], OSM_MAP_SLUG)) : null; ?>"/>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Mapbox Access Token</h3>
                        </div>
                        <div class="card-content">
                            <p><strong>Note:</strong> This setting is required if you wish to use custom Mapbox /
                                Geoapify tiles. Need help to get a Mapbox Access Token? <a
                                        href="https://docs.mapbox.com/help/how-mapbox-works/access-tokens/"
                                        target="_blank">Read this resource</a></p>
                            <input type="text" name="osm_widget[mapbox_token]"
                                   value="<?php echo !empty($osm_settings['mapbox_token']) ? esc_textarea(__($osm_settings['mapbox_token'], OSM_MAP_SLUG)) : null; ?>"/>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Geoapify API Key</h3>
                        </div>
                        <div class="card-content">
                            <p><strong>Note:</strong> This setting is required if you wish to use custom Geoapify
                                tiles. Need help to get a Geoapify API key. <a
                                        href="https://www.geoapify.com/maps-api/">Read this resource.</a></p>
                            <input type="text" name="osm_widget[geoapify_key]"
                                   value="<?php echo !empty($osm_settings['geoapify_key']) ? esc_textarea(__($osm_settings['geoapify_key'], OSM_MAP_SLUG)) : null; ?>"/>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="card">
                        <div class="card-header" style="display: flex;align-items: center;">
                            <h3 class="card-title">Font Awesome</h3>
                            <!-- Rounded switch -->
                            <label class="switch" style="margin-left: auto; margin-right: 20px;">
                                <input type="checkbox" name="osm_widget[enable_fontawesome]"
                                       value="1"
                                    <?php echo is_array($osm_settings) && !array_key_exists('enable_fontawesome', $osm_settings) || !empty($osm_settings['enable_fontawesome']) ? "checked='checked'" : null; ?>>
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div class="card-content">
                            <p><strong>Note:</strong> If you are having conflicts or already have Font Awesome loaded on
                                your website, toggle the setting above to not load it again.</p>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Custom Map Tile URL</h3>
                        </div>
                        <div class="card-content">
                            <p><strong>See more tile servers:</strong>
                                <a href="http://wiki.openstreetmap.org/wiki/Tile_servers">here</a>
                                <br><strong>Example:</strong> https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png</p>
                            <input type="text" name="osm_widget[osm_custom]"
                                   value="<?php echo !empty($osm_settings['osm_custom']) ? esc_textarea(__($osm_settings['osm_custom'], OSM_MAP_SLUG)) : null; ?>"/>

                            <br>
                            <p><strong>Additional Attribution</strong>
                            <p>Organization</p>
                            <input type="text" name="osm_widget[osm_custom_attribution]"
                                   value="<?php echo !empty($osm_settings['osm_custom_attribution']) ? esc_textarea(__($osm_settings['osm_custom_attribution'], OSM_MAP_SLUG)) : null; ?>"/>
                            <p>URL</p>
                            <input type="text" name="osm_widget[osm_custom_attribution_url]"
                                   value="<?php echo !empty($osm_settings['osm_custom_attribution_url']) ? esc_textarea(__($osm_settings['osm_custom_attribution_url'], OSM_MAP_SLUG)) : null; ?>"/>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="osm-button">Save Changes</button>
                </div>
            </form>
        </div>

        <?php
    });
});

// Require Elementor plugin to be installed and activated
add_action('admin_init', function () {
    if (is_admin() && current_user_can('activate_plugins') && !is_plugin_active('elementor/elementor.php')) {
        add_action('admin_notices', function () {
            ?>
            <div class="error"><p>Sorry, but <strong>Elementor OSM Map</strong> Plugin requires that
                    <strong>Elementor</strong> plugin to be
                    installed and active.</p></div><?php
        });

        deactivate_plugins(plugin_basename(__FILE__));

        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
});

// queue jquery in header
add_action('init', function () {
    add_filter('wp_enqueue_scripts', function () {
        wp_enqueue_script('jquery', false, [], false, false);
    }, 1);
}, 1);
