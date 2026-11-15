<?php
/**
 * Plugin Name:                OSM Map Widget for Elementor
 * Description:                A free Elementor Map Widget that Utilizes Open Street Map. Comes with features like adding multiple markers, and choosing from a library of custom tiles to change the look and feel.
 * Version:                    1.4.0
 * Requires at least:          6.8
 * Requires PHP:               7.4
 * Requires Plugins:           elementor
 * Author:                     Plugin Contributors
 * Author URI:                 https://github.com/flopperj/osm-map-elementor/graphs/contributors
 * License:                    GPLv3
 * License URI:                https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:                osm-map-elementor
 * Elementor tested up to:     4.4.0
 * Elementor Pro tested up to: 4.4.0
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


function handle_settings_save()
{
    if (!isset($_SERVER['REQUEST_METHOD']) || 'POST' !== $_SERVER['REQUEST_METHOD']) {
        return;
    }

    check_admin_referer('osm_settings_action', 'osm_settings_nonce');

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to change these settings.', 'osm-map-elementor'), 403);
    }

    $input = isset($_POST['osm_widget']) && is_array($_POST['osm_widget'])
        ? map_deep(wp_unslash($_POST['osm_widget']), 'sanitize_text_field')
        : [];

    // osm_custom keeps sanitize_text_field only: esc_url_raw would strip the {s}/{z}/{x}/{y} tile placeholders
    $osm_settings = [
        'gmaps_key' => !empty($input['gmaps_key']) ? $input['gmaps_key'] : null,
        'mapbox_token' => !empty($input['mapbox_token']) ? $input['mapbox_token'] : null,
        'geoapify_key' => !empty($input['geoapify_key']) ? $input['geoapify_key'] : null,
        'osm_custom' => !empty($input['osm_custom']) ? $input['osm_custom'] : null,
        'osm_custom_attribution' => !empty($input['osm_custom_attribution']) ? $input['osm_custom_attribution'] : null,
        'osm_custom_attribution_url' => !empty($input['osm_custom_attribution_url']) ? esc_url_raw($input['osm_custom_attribution_url']) : null,
        'enable_fontawesome' => !empty($input['enable_fontawesome']) ? $input['enable_fontawesome'] : null
    ];

    update_option('osm_widget', $osm_settings);

    wp_safe_redirect(add_query_arg([
        'page' => 'osm-map-elementor',
        'action' => 'settings_saved',
    ], admin_url('options-general.php')));
    exit;
}

// Add global settings for our widget
add_action('admin_menu', function () {
    $page_hook = add_options_page('OSM Map Widget', 'OSM Map Widget', 'manage_options', 'osm-map-elementor', function () {

        // queue admin styles
        wp_register_style('osm-map-admin', plugins_url('/' . OSM_PLUGIN_FOLDER . '/assets/css/admin.css'), [], OSM_MAP_VERSION);
        wp_enqueue_style('osm-map-admin');

        $osm_settings = get_option('osm_widget');
        $setting = static function ($key) use ($osm_settings) {
            return is_array($osm_settings) && isset($osm_settings[$key]) ? (string) $osm_settings[$key] : '';
        };
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag after a nonce-checked redirect
        $settings_saved = isset($_GET['action']) && 'settings_saved' === sanitize_key(wp_unslash($_GET['action']));

        ?>
        <div id="osm-map-settings">
            <h2>OSM Map Widget Settings</h2>
            <?php if ($settings_saved): ?>
                <div style="background-color: rgb(255, 251, 204);" id="alert-message" class="updated"><p>
                        <strong><?php echo esc_html__('Settings saved.', 'osm-map-elementor'); ?></strong></p></div>
            <?php endif; ?>
            <form action="<?php
            $form_action_url = admin_url('options-general.php');
            echo esc_url(add_query_arg([
                'page' => 'osm-map-elementor',
                'action' => 'save_settings',
            ], $form_action_url));
            ?>" method="post">
                <?php wp_nonce_field('osm_settings_action', 'osm_settings_nonce'); ?>
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
                                   value="<?php echo esc_attr($setting('gmaps_key')); ?>"/>
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
                                   value="<?php echo esc_attr($setting('mapbox_token')); ?>"/>
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
                                   value="<?php echo esc_attr($setting('geoapify_key')); ?>"/>
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
                                    <?php echo (is_array($osm_settings) && !array_key_exists('enable_fontawesome', $osm_settings)) || !empty($osm_settings['enable_fontawesome']) ? "checked='checked'" : null; ?>>
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
                                   value="<?php echo esc_attr($setting('osm_custom')); ?>"/>

                            <br>
                            <p><strong>Additional Attribution</strong>
                            <p>Organization</p>
                            <input type="text" name="osm_widget[osm_custom_attribution]"
                                   value="<?php echo esc_attr($setting('osm_custom_attribution')); ?>"/>
                            <p>URL</p>
                            <input type="text" name="osm_widget[osm_custom_attribution_url]"
                                   value="<?php echo esc_attr($setting('osm_custom_attribution_url')); ?>"/>
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
    if ($page_hook) {
        add_action('load-' . $page_hook, __NAMESPACE__ . '\\handle_settings_save');
    }
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

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- clears core's activation notice flag; no input is processed.
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
});

// queue jquery in header
function enqueue_jquery_in_head()
{
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_jquery_in_head', 1);

function render_elementor_version_notice($installed_version)
{
    if (!$installed_version || !current_user_can('update_plugins') || version_compare($installed_version, OSM_MAP_MIN_ELEMENTOR_VERSION, '>=')) {
        return;
    }

    /* translators: %s: minimum supported Elementor version. */
    $message = sprintf(__('OSM Map Widget for Elementor is tested with Elementor %s or newer. Please update Elementor.', 'osm-map-elementor'), OSM_MAP_MIN_ELEMENTOR_VERSION);
    printf('<div class="notice notice-warning"><p>%s</p></div>', esc_html($message));
}

add_action('admin_notices', function () {
    render_elementor_version_notice(defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : null);
});
