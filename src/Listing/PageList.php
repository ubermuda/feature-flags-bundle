<?php

namespace Ubermuda\FeatureFlagsBundle\Listing;

final class PageList
{
    /**
     * Builds a windowed page list with ellipses, e.g. [1, '…', 4, 5, 6, '…', 20].
     *
     * @return list<int|string>
     */
    public static function build(int $current, int $total, int $window = 1): array
    {
        if ($total <= 1) {
            return [1];
        }

        $pages = [];
        for ($page = 1; $page <= $total; ++$page) {
            $isEdge = 1 === $page || $total === $page;
            $isNear = abs($page - $current) <= $window;

            if ($isEdge || $isNear) {
                $pages[] = $page;
            } elseif ('…' !== end($pages)) {
                $pages[] = '…';
            }
        }

        return $pages;
    }
}
