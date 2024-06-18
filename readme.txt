=== OSM Map Widget for Elementor ===
Plugin Name: OSM Map Widget for Elementor
Version: 1.3.0
Author: Plugin Contributors
Author URI: https://github.com/flopperj/osm-map-elementor/graphs/contributors
Contributors: garbowza, intelchip, youngmedianetwork
Tags: elementor, elementor widget, map widget, open street map, addons
Requires at least: 6.0
Tested up to: 6.5.4
Requires PHP: 7.3
Stable tag: 1.3.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==
A free Elementor Map Widget that utilizes [Open Street Map](https://www.openstreetmap.org/about). Comes with features like adding multiple markers, and choosing from a library of custom tiles to change the look and feel. Requires [Elementor Page Builder](https://wordpress.org/plugins/elementor/) Plugin Version: 3.5+

This plugin is [open sourced](https://github.com/flopperj/osm-map-elementor) so feel free to contribute to it by:
1. Adding a topic/issue [here](https://github.com/flopperj/osm-map-elementor/issues)
2. Submitting a pull request with any well-written code that implements the desired feature.

### Usage
1. Make sure that **Elementor** plugin is installed
2. Add optional API Keys to the settings page:
    - **Google Maps API Key** *(Used to autocomplete and update the coordinates of markers in a map. Need help to get a Google map API key? [Read this resource](https://developers.google.com/maps/documentation/javascript/get-api-key))*
    - **Mapbox Access Token** *(Used for custom tiles. Need help to get a Mapbox Access Token? [Read this resource](https://docs.mapbox.com/help/how-mapbox-works/access-tokens/))*
    - **Geoapify API Key** *(Used for custom tiles. Need help to get a Geoapify API key? [Read this resource](https://www.geoapify.com/maps-api/))*
3. Add OSM Map widget to your elementor page and have fun :)
    - Find OSM Map widget from elements listing and add it to your elementor page
    - Add multiple markers, change Zoom level style them accordingly
    - *Editing the markers:* A marker's settings contains **Title, Location, Coordinates, Description, Button Text, and Button URL** fields. Of those fields, only the **Coordinates** field is required to render a marker on the map. If you have a Google Maps API key added, you'll be able to automatically populate the coordinates field without manually doing so.
    - Change Tiles from style section *requires **[Mapbox Access Token](https://docs.mapbox.com/help/how-mapbox-works/access-tokens/)** and **[Geoapify API Key](https://www.geoapify.com/maps-api/)***

== Screenshots ==
1. Update optional API keys
2. Add widget to your elementor page
3. Add multiple markers & style them accordingly
4. Edit the marker's coordinates to render them in the map
5. Choose from 14 different custom tiles
6. NEW: Add custom marker icons. Available Icon types: Default, Font Awesome and Custom Image.


== Changelog ==
Thank you for using OSM Map Widget for Elementor.

To make your experience using the widget better we release updates regularly, you can view the full changelog [here](https://github.com/flopperj/osm-map-elementor/wiki/Changelog)
