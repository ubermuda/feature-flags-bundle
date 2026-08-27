<?php

namespace Ubermuda\FeatureFlagsBundle\Test;

use PHPUnit\Framework\TestCase;

final class TemplateConventionsTest extends TestCase
{
    /**
     * Tailwind arbitrary values (`min-h-[2.5rem]`) are baked into the bundle and
     * cannot be re-themed by the consuming app, which owns the Tailwind pass.
     * Use the standard scale, or a CSS custom property the app can override.
     */
    public function testShippedTemplatesUseNoArbitraryTailwindValues(): void
    {
        $offenders = [];

        foreach ($this->templateFiles() as $path) {
            preg_match_all('/class="([^"]*)"/', (string) file_get_contents($path), $attributes);

            foreach ($attributes[1] as $classList) {
                if (preg_match_all('/(?<![\w:\/-])[a-z][a-z0-9:\/-]*-\[[^\]]+\]/', $classList, $matches)) {
                    foreach ($matches[0] as $utility) {
                        $offenders[] = basename($path).': '.$utility;
                    }
                }
            }
        }

        self::assertSame([], $offenders, 'Arbitrary Tailwind utilities found in shipped templates.');
    }

    /** @return list<string> */
    private function templateFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__.'/../templates', \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && 'twig' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }

        self::assertNotEmpty($files, 'No templates found — the scan would pass vacuously.');

        return $files;
    }
}
