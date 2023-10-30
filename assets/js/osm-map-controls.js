/**
 * Called as a callback to google maps API to allow for autocompletion of coordinates
 */
function initOSMEditorControls() {
    jQuery(window).on(
        'elementor/init',
        function () {

            /**
             * Initiates autocomplete
             * @param input
             * @param callback
             */
            const initiateAutoComplete = (input, callback) => {
                if (typeof google === 'undefined') return;

                const autocomplete = new google.maps.places.Autocomplete(input);
                google.maps.event.addListener(autocomplete, 'place_changed', function () {
                    const place = autocomplete.getPlace();
                    if (typeof callback === 'function') {
                        callback(place);
                    }
                });
            };


            elementor.hooks.addAction('panel/open_editor/widget/osm-map-elementor', function (panel, model, view) {

                // if value changes in input fields, trigger an input event that will allow for data to be saved
                jQuery('input[data-setting="marker_coords"],input[data-setting="marker_title"], input[data-setting="size"]').change(function () {
                    jQuery(this).trigger('input');
                });

                // auto-populate our coordinates
                const initMarkerFields = () => {
                    const markers = jQuery('.elementor-control-marker_list .elementor-repeater-fields:visible');

                    // loop through all visible marker fields
                    jQuery.each(markers, function () {
                        const location_input = jQuery(this).find('input[data-setting="marker_location"]');
                        const coords_input = jQuery(this).find('input[data-setting="marker_coords"]');
                        initiateAutoComplete(document.getElementById(location_input[0].id), function (place) {
                            coords_input.val(`${place.geometry.location.lat()},${place.geometry.location.lng()}`);
                            coords_input.trigger('input');
                            location_input.trigger('input');
                        });
                    });
                };

                initMarkerFields();

                // initialize markers on more additions
                jQuery('.elementor-repeater-add:visible').click(function () {
                    setTimeout(initMarkerFields, 1000);
                });
            });
        }
    );
}
