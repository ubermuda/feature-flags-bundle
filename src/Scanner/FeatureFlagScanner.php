<?php

namespace Ubermuda\FeatureFlagsBundle\Scanner;

use Symfony\Component\Finder\Finder;

readonly class FeatureFlagScanner
{
    // Match the flag name as the first string argument, ignoring any trailing
    // arguments. The trailing `)` is deliberately NOT required: isEnabled(),
    // getIntValue() and getStringValue() take a default as a second argument,
    // and requiring the close paren would silently miss every such call.
    private const string TWIG_PATTERN = "/(?:is_feature_enabled|feature_flag_value)\\(\\s*['\"]([^'\"]+)['\"]/";
    private const string PHP_PATTERN = "/->(?:isEnabled|getValue|getIntValue|getStringValue)\\(\\s*['\"]([^'\"]+)['\"]/";

    // First argument given as a class-constant reference instead of a literal.
    // The constant's value is resolved against every `const X = 'literal'`
    // definition collected from the scanned files; unresolvable references are
    // skipped rather than guessed.
    private const string PHP_CONST_CALL_PATTERN = "/->(?:isEnabled|getValue|getIntValue|getStringValue)\\(\\s*(?:self|static|parent|[A-Za-z_\\\\][A-Za-z0-9_\\\\]*)::([A-Z][A-Z0-9_]*)/";
    private const string PHP_CONST_DEF_PATTERN = "/const\\s+(?:string\\s+)?([A-Z][A-Z0-9_]*)\\s*=\\s*['\"]([^'\"]+)['\"]/";

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

        $phpContents = [];
        $names = [];

        foreach ($this->files($existing) as $file) {
            if ('twig' === $file->getExtension()) {
                preg_match_all(self::TWIG_PATTERN, $file->getContents(), $matches);
                array_push($names, ...$matches[1]);
                continue;
            }

            $phpContents[] = $file->getContents();
        }

        $constants = [];
        foreach ($phpContents as $content) {
            preg_match_all(self::PHP_CONST_DEF_PATTERN, $content, $defs, PREG_SET_ORDER);
            foreach ($defs as $def) {
                $constants[$def[1]][] = $def[2];
            }
        }

        foreach ($phpContents as $content) {
            preg_match_all(self::PHP_PATTERN, $content, $matches);
            array_push($names, ...$matches[1]);

            preg_match_all(self::PHP_CONST_CALL_PATTERN, $content, $refs);
            foreach ($refs[1] as $constName) {
                array_push($names, ...($constants[$constName] ?? []));
            }
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
