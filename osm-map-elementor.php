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

        $action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : null;
        if (!empty($action) && $action == 'save_settings' && isset($_REQUEST['osm_widget'])) {
            $osm_settings = isset($_REQUEST['osm_widget']) ? $_REQUEST['osm_widget'] : [];
            update_option('osm_widget', $osm_settings);
            wp_redirect($_SERVER['HTTP_REFERER'] . '&action=settings_saved');
        }

        $osm_settings = get_option('osm_widget');

        ?>
        <style type="text/css">
            #osm-map-settings {
                margin: 5px 15px 2px;
            }

            #osm-map-settings div#alert-message {
                margin-left: 0;
            }

            #osm-map-settings h2 {
                color: #6d7882;
                font-weight: 400;
                font-size: 22px;
            }

            #osm-map-settings .card-header h3.card-title {
                color: #6d7882;
                padding: 5px 20px;
                font-weight: 500;
            }

            #osm-map-settings .card {
                padding: 0;
                border-color: #f1f1f1;;
            }

            #osm-map-settings .card-content {
                padding: 20px;
                background-color: #f7f7f7;
            }

            #osm-map-settings .card-header {
                border-bottom: 1px solid #f1f1f1;
            }

            #osm-map-settings input[type="text"] {
                width: 100%;
            }

            #osm-map-settings .osm-button {
                background: #e14d43;
                border-color: #e14d43;
                color: #fff;
                display: inline-block;
                text-decoration: none;
                font-size: 13px;
                line-height: 2.15384615;
                min-height: 30px;
                margin: 0;
                padding: 0 10px;
                cursor: pointer;
                border-width: 1px;
                border-style: solid;
                -webkit-appearance: none;
                border-radius: 3px;
                white-space: nowrap;
                box-sizing: border-box;
            }

            #osm-map-settings .form-group {
                margin-bottom: 25px;
            }
        </style>

        <div id="osm-map-settings">
            <h2>OSM Map Widget Settings</h2>
            <?php if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'settings_saved'): ?>
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
                                    lookup location coordinates.</em></p>
                            <input type="text" name="osm_widget[gmaps_key]"
                                   value="<?php echo !empty($osm_settings['gmaps_key']) ? $osm_settings['gmaps_key'] : null; ?>"/>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Mapbox Access Token</h3>
                        </div>
                        <div class="card-content">
                            <p><em><strong>Note:</strong> This setting is required if you wish to use custom Mapbox / Geoapify tiles.</em></p>
                            <input type="text" name="osm_widget[mapbox_token]"
                                   value="<?php echo !empty($osm_settings['mapbox_token']) ? $osm_settings['mapbox_token'] : null; ?>"/>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Geoapify API Key</h3>
                        </div>
                        <div class="card-content">
                            <p><em><strong>Note:</strong> This setting is required if you wish to use custom Geoapify tiles.</em></p>
                            <input type="text" name="osm_widget[geoapify_key]"
                                   value="<?php echo !empty($osm_settings['geoapify_key']) ? $osm_settings['geoapify_key'] : null; ?>"/>
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