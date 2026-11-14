<?php

class Test_I18n extends WP_UnitTestCase
{
    const CALL = '(?<![\w>:$])(?:__|_e|esc_html__|esc_attr__|esc_html_e|esc_attr_e)\(';
    const LITERAL = "'(?:[^'\\\\]|\\\\.)*'";

    public function source_files()
    {
        return [
            'widget' => [dirname(__DIR__) . '/osm-map.php'],
            'bootstrap' => [dirname(__DIR__) . '/osm-map-elementor.php'],
        ];
    }

    /**
     * @dataProvider source_files
     */
    public function test_no_legacy_or_placeholder_text_domains($file)
    {
        $source = file_get_contents($file);

        $this->assertStringNotContainsString("'osm-map')", $source);
        $this->assertStringNotContainsString("'your-slug'", $source);
    }

    /**
     * @dataProvider source_files
     */
    public function test_no_constant_is_used_as_a_text_domain($file)
    {
        $this->assertDoesNotMatchRegularExpression('/,\s*OSM_MAP_SLUG\s*\)/', file_get_contents($file));
    }

    /**
     * @dataProvider source_files
     */
    public function test_every_translation_call_has_a_literal_text_and_the_plugin_domain($file)
    {
        $source = file_get_contents($file);

        $total = preg_match_all('/' . self::CALL . '/', $source);
        $compliant = preg_match_all(
            '/' . self::CALL . '\s*' . self::LITERAL . "\s*,\s*'osm-map-elementor'\s*\)/",
            $source
        );

        $this->assertGreaterThan(0, $total);
        $this->assertSame($total, $compliant, 'Translation calls must use a literal string and the osm-map-elementor domain');
    }
}
