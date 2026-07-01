<?php

namespace Ubermuda\FeatureFlagsBundle\Listing;

use Symfony\Component\HttpFoundation\Request;

/**
 * Minimal, self-contained parse of pagination/sort state from the query string.
 * Replaces the host app's admin listing primitives for this bundle.
 */
final readonly class ListPageRequest
{
    public function __construct(
        public int $page,
        public string $sort,
        public string $dir,
    ) {
    }

    /**
     * @param list<string> $allowedSorts
     */
    public static function fromRequest(
        Request $request,
        array $allowedSorts,
        string $defaultSort,
        string $defaultDir,
    ): self {
        $page = max(1, $request->query->getInt('page', 1));

        $sort = $request->query->getString('sort', $defaultSort);
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = $defaultSort;
        }

        $dir = strtolower($request->query->getString('dir', $defaultDir));
        if (!in_array($dir, ['asc', 'desc'], true)) {
            $dir = strtolower($defaultDir);
        }

        return new self($page, $sort, $dir);
    }
}
