<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Listing;

use PHPUnit\Framework\TestCase;
use Ubermuda\FeatureFlagsBundle\Listing\PageList;

final class PageListTest extends TestCase
{
    public function testSinglePage(): void
    {
        self::assertSame([1], PageList::build(1, 1));
        self::assertSame([1], PageList::build(1, 0));
    }

    public function testNoEllipsisWhenAllPagesFit(): void
    {
        self::assertSame([1, 2, 3], PageList::build(2, 3));
    }

    public function testEllipsisAroundCurrentInTheMiddle(): void
    {
        self::assertSame([1, '…', 4, 5, 6, '…', 20], PageList::build(5, 20));
    }

    public function testNoEllipsisCollapsesAdjacentEdge(): void
    {
        self::assertSame([1, 2, 3, '…', 20], PageList::build(2, 20));
    }
}
