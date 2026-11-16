<?php

class Test_Vendored_Marker_Geometry extends WP_UnitTestCase
{
    private function asset($relative_path)
    {
        $contents = file_get_contents(dirname(__DIR__) . '/assets/' . $relative_path);
        $this->assertNotFalse($contents, "assets/$relative_path is readable");

        return $contents;
    }

    public function test_leaflet_default_icon_geometry_matches_the_scaling_base()
    {
        $leaflet = $this->asset('leaflet/leaflet.js');

        $this->assertSame(
            1,
            substr_count($leaflet, 'iconSize:[25,41],iconAnchor:[12,41],popupAnchor:[1,-34],tooltipAnchor:[16,-28],shadowSize:[41,41]')
        );
    }

    public function test_font_awesome_pin_geometry_matches_the_scaling_base()
    {
        $library = $this->asset('leaflet-fa-markers/L.Icon.FontAwesome.js');
        $css = $this->asset('leaflet-fa-markers/L.Icon.FontAwesome.css');

        $this->assertStringContainsString('popupAnchor: [0, -50]', $library);
        $this->assertStringContainsString('viewBox="0 0 32 52"', $library);
        $this->assertStringContainsString('16,51 C16,51', $library);
        $this->assertStringContainsString('margin-top: -50px', $css);
    }

    public function test_font_awesome_library_supports_marker_scale()
    {
        $library = $this->asset('leaflet-fa-markers/L.Icon.FontAwesome.js');

        $this->assertStringContainsString('options.markerScale', $library);
        $this->assertStringContainsString('transformOrigin = "16px 51px"', $library);
    }
}
