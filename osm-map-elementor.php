<?php
/**
 * Plugin Name:     OSM Map Widget for Elementor
 * Description:     An Elementor Widget that creates an OSM Map. Requires Elementor Plugin Version: 3.0.5+
 * Author:          ACT Innovate, James Arama, Alex Hooten
 * Author URI:      https://github.com/flopperj/elementor-osm-map
 * Version:         1.0.1
 */

namespace OSM_Map;

use Elementor\Plugin;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once ('constants.php');

// The Widget_Base class is not available immediately after plugins are loaded, so
// we delay the class' use until Elementor widgets are registered
add_action('elementor/widgets/widgets_registered', function () {
    require_once('osm-map.php');

    $osm_map = new Widget_OSM_Map();

    // Let Elementor know about our widget
    Plugin::instance()
        ->widgets_manager
        ->register_widget_type($osm_map);
});


// Add global settings for our widget
add_action('admin_menu', function () {
    add_options_page('OSM Map Widget', 'OSM Map Widget', 'manage_options', 'osm-map-elementor', function () {

        // queue admin styles
        wp_register_style('osm-map-admin', plugins_url('/osm-map-elementor/assets/css/admin.css'));
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
                            <p><em><strong>Note:</strong> This setting is required if you wish to use Google Maps to
                                    lookup location coordinates. Need help to get a Google map API key? <a
                                            href="https://developers.google.com/maps/documentation/javascript/get-api-key"
                                            target="_blank">Read this resource</a>.</em></p>
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
                            <p><em><strong>Note:</strong> This setting is required if you wish to use custom Mapbox /
                                    Geoapify tiles. Need help to get a Mapbox Access Token? <a
                                            href="https://docs.mapbox.com/help/how-mapbox-works/access-tokens/"
                                            target="_blank">Read this resource</a></em></p>
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
                            <p><em><strong>Note:</strong> This setting is required if you wish to use custom Geoapify
                                    tiles. Need help to get a Geoapify API key. <a
                                            href="https://www.geoapify.com/maps-api/">Read this resource.</a></em></p>
                            <input type="text" name="osm_widget[geoapify_key]"
                                   value="<?php echo !empty($osm_settings['geoapify_key']) ? esc_textarea(__($osm_settings['geoapify_key'], OSM_MAP_SLUG)) : null; ?>"/>
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