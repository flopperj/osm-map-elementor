<?php

class Test_Composer_Platform extends WP_UnitTestCase
{
    private function json($file)
    {
        $decoded = json_decode(file_get_contents(dirname(__DIR__) . '/' . $file), true);
        $this->assertIsArray($decoded, "$file must be valid JSON");

        return $decoded;
    }

    private function declared_minimum_php()
    {
        $readme = file_get_contents(dirname(__DIR__) . '/readme.txt');
        $this->assertSame(1, preg_match('/^Requires PHP:\s*(\S+)/mi', $readme, $m));

        return $m[1];
    }

    public function test_platform_is_pinned_to_the_declared_minimum_php()
    {
        $composer = $this->json('composer.json');

        $this->assertArrayHasKey('platform', $composer['config']);
        $this->assertStringStartsWith($this->declared_minimum_php() . '.', $composer['config']['platform']['php']);
    }

    public function test_lock_file_was_resolved_against_the_pinned_platform()
    {
        $composer = $this->json('composer.json');
        $lock = $this->json('composer.lock');

        $this->assertSame($composer['config']['platform']['php'], $lock['platform-overrides']['php'] ?? null);
    }

    public function test_every_locked_package_installs_on_the_declared_minimum_php()
    {
        $lock = $this->json('composer.lock');
        $minimum = $this->json('composer.json')['config']['platform']['php'];
        $offenders = [];

        foreach (array_merge($lock['packages'], $lock['packages-dev']) as $package) {
            $constraint = $package['require']['php'] ?? null;
            if (null !== $constraint && !$this->satisfies($minimum, $constraint)) {
                $offenders[] = $package['name'] . ' ' . $package['version'] . ' requires php ' . $constraint;
            }
        }

        $this->assertSame([], $offenders);
    }

    private function satisfies($version, $constraint)
    {
        foreach (preg_split('/\s*\|\|?\s*/', trim($constraint)) as $alternative) {
            $matches = true;
            foreach (preg_split('/[\s,]+/', trim($alternative)) as $part) {
                if (!$this->part_satisfied($version, $part)) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return true;
            }
        }

        return false;
    }

    private function part_satisfied($version, $part)
    {
        $this->assertSame(1, preg_match('/^(\^|~|>=|<=|>|<|==|=)?v?(\d+(?:\.\d+)*)(?:\.\*)?$/', $part, $m), "Unsupported php constraint '$part'");
        $operator = $m[1];
        $bound = $m[2];
        $segments = array_map('intval', explode('.', $bound));

        switch ($operator) {
            case '^':
                return version_compare($version, $bound, '>=') && version_compare($version, ($segments[0] + 1) . '.0.0', '<');
            case '~':
                $upper = count($segments) > 2 ? $segments[0] . '.' . ($segments[1] + 1) . '.0' : ($segments[0] + 1) . '.0.0';
                return version_compare($version, $bound, '>=') && version_compare($version, $upper, '<');
            case '>=':
            case '<=':
            case '>':
            case '<':
                return version_compare($version, $bound, $operator);
            default:
                return 0 === strpos($version . '.', $bound . '.');
        }
    }
}
