<?php

namespace Ubermuda\FeatureFlagsBundle\Scanner;

use Symfony\Component\Finder\Finder;

readonly class FeatureFlagScanner
{
    // Match the flag name as the first string argument, ignoring any trailing
    // arguments. The trailing `)` is deliberately NOT required: isEnabled() and
    // getIntValue() take a default as a second argument, and requiring the close
    // paren would silently miss every such call (and flag those flags as orphaned).
    private const string TWIG_PATTERN = "/(?:is_feature_enabled|feature_flag_value)\\(\\s*['\"]([^'\"]+)['\"]/";
    private const string PHP_PATTERN = "/->(?:isEnabled|getValue|getIntValue)\\(\\s*['\"]([^'\"]+)['\"]/";

    /**
     * @param list<string> $scanPaths Directories scanned for referenced flag names
     */
    public function __construct(
        private array $scanPaths,
    ) {
    }

    /**
     * Returns all flag names referenced in the configured scan paths.
     *
     * @return list<string>
     */
    public function findReferencedFlags(): array
    {
        $existing = array_values(array_filter($this->scanPaths, is_dir(...)));

        if ([] === $existing) {
            return [];
        }

        $names = [];

        foreach ($this->files($existing) as $file) {
            $pattern = 'twig' === $file->getExtension() ? self::TWIG_PATTERN : self::PHP_PATTERN;
            preg_match_all($pattern, $file->getContents(), $matches);
            array_push($names, ...$matches[1]);
        }

        $names = array_unique($names);
        sort($names);

        return $names;
    }

    /**
     * @param list<string> $paths
     */
    private function files(array $paths): Finder
    {
        return new Finder()
            ->files()
            ->in($paths)
            ->name(['*.twig', '*.php']);
    }
}
