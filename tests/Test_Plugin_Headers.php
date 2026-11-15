<?php

class Test_Plugin_Headers extends WP_UnitTestCase
{
    private function plugin_file()
    {
        return dirname(__DIR__) . '/osm-map-elementor.php';
    }

    private function readme_field($field)
    {
        $readme = file_get_contents(dirname(__DIR__) . '/readme.txt');
        $this->assertSame(1, preg_match('/^' . preg_quote($field, '/') . ':\s*(.+)$/mi', $readme, $m), "readme.txt is missing '$field'");

        return trim($m[1]);
    }

    public function test_plugin_header_declares_requirements_and_text_domain()
    {
        $data = get_plugin_data($this->plugin_file(), false, false);

        $this->assertSame('osm-map-elementor', $data['TextDomain']);
        $this->assertSame('6.8', $data['RequiresWP']);
        $this->assertSame('7.4', $data['RequiresPHP']);
        $this->assertSame('elementor', $data['RequiresPlugins']);
        $this->assertSame('1.4.0', $data['Version']);
        $this->assertSame('GPLv3', $this->raw_header('License'));
    }

    private function raw_header($name)
    {
        $source = file_get_contents($this->plugin_file());
        preg_match('/^[ \t\/*#@]*' . preg_quote($name, '/') . ':\s*(.+)$/mi', $source, $m);

        return isset($m[1]) ? trim($m[1]) : null;
    }

    public function test_version_constant_matches_plugin_header()
    {
        $this->assertSame('1.4.0', OSM_MAP_VERSION);
        $this->assertSame(OSM_MAP_VERSION, get_plugin_data($this->plugin_file(), false, false)['Version']);
    }

    public function test_plugin_header_has_elementor_tested_up_to_but_no_wordpress_tested_up_to()
    {
        $source = file_get_contents($this->plugin_file());

        $this->assertStringContainsString('Elementor tested up to:     4.4.0', $source);
        $this->assertDoesNotMatchRegularExpression('/^[ \t*]*Tested up to:/mi', $source);
    }

    public function test_minimum_elementor_version_constant_is_declared()
    {
        $this->assertTrue(defined('OSM_MAP_MIN_ELEMENTOR_VERSION'));
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', OSM_MAP_MIN_ELEMENTOR_VERSION);
    }

    public function test_readme_requirements_match_plugin_header()
    {
        $this->assertSame('6.8', $this->readme_field('Requires at least'));
        $this->assertSame('7.4', $this->readme_field('Requires PHP'));
        $this->assertSame('7.1', $this->readme_field('Tested up to'));
    }

    public function test_readme_stable_tag_matches_the_plugin_version()
    {
        $this->assertSame(OSM_MAP_VERSION, $this->readme_field('Stable tag'));
    }

    public function test_constants_file_refuses_direct_access()
    {
        $source = file_get_contents(dirname(__DIR__) . '/constants.php');

        $this->assertMatchesRegularExpression("/^<\?php\s+if \(!defined\('ABSPATH'\)\) \{\s+exit;\s+\}/", $source);
    }
}
