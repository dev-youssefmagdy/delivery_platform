<?php

namespace Tests\Feature\Admin\Kit;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Plan §5.1 / SRS §18.1: admin views use logical utilities only (ms-/me-/ps-/pe-/start-/end-/text-start …).
 */
class LogicalDirectionUtilitiesTest extends TestCase
{
    private const FORBIDDEN = '/(?<![\w-])-?(?:[a-z0-9]+:)*(ml|mr|pl|pr|left|right|rounded-l|rounded-r|border-l|border-r|text-left|text-right|float-left|float-right)-[\w\[\]\/.]+|(?<![\w-])(?:[a-z0-9]+:)*(text-left|text-right|float-left|float-right)(?![\w-])/';

    public function test_admin_blade_files_do_not_use_physical_direction_utilities(): void
    {
        $finder = (new Finder)->files()->name('*.blade.php')->in([
            resource_path('views/admin'),
            resource_path('views/components/admin'),
        ]);
        $violations = [];

        foreach ($finder as $file) {
            preg_match_all('/class="([^"]*)"|:class="([^"]*)"|x-bind:class="([^"]*)"|@class\(\[(.*?)\]\)/s', $file->getContents(), $matches);

            foreach (array_merge(...array_slice($matches, 1)) as $classes) {
                if ($classes !== '' && preg_match_all(self::FORBIDDEN, $classes, $found)) {
                    $violations[] = $file->getRelativePathname().': '.implode(', ', $found[0]);
                }
            }
        }

        $this->assertNotEmpty(iterator_to_array($finder), 'admin views exist');
        $this->assertSame([], $violations, "Use logical utilities instead:\n".implode("\n", $violations));
    }

    public function test_the_detector_catches_physical_utilities(): void
    {
        foreach (['ml-2', 'pr-4', 'left-0', 'right-3', 'text-left', 'md:text-right', 'hover:mr-1', '-ml-1'] as $class) {
            $this->assertMatchesRegularExpression(self::FORBIDDEN, $class, $class);
        }

        foreach (['ms-2', 'pe-4', 'start-0', 'end-3', 'text-start', 'translate-x-full', 'html-left-x'] as $class) {
            $this->assertDoesNotMatchRegularExpression(self::FORBIDDEN, $class, $class);
        }
    }
}
