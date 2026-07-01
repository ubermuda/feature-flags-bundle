<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Listing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Ubermuda\FeatureFlagsBundle\Listing\AdminReturnTo;

final class AdminReturnToTest extends TestCase
{
    private AdminReturnTo $returnTo;

    protected function setUp(): void
    {
        $this->returnTo = new AdminReturnTo(new NullLogger());
    }

    public function testAcceptsLocalPath(): void
    {
        self::assertSame('/admin/feature-flags?q=x', $this->returnTo->validate('/admin/feature-flags?q=x'));
    }

    #[DataProvider('rejectedCandidates')]
    public function testRejectsUnsafeOrEmptyCandidates(mixed $candidate): void
    {
        self::assertNull($this->returnTo->validate($candidate));
    }

    /** @return iterable<string, array{mixed}> */
    public static function rejectedCandidates(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'protocol-relative' => ['//evil.example.com'];
        yield 'absolute url' => ['https://evil.example.com'];
        yield 'no leading slash' => ['admin/feature-flags'];
        yield 'backslash trick' => ['/\\evil.example.com'];
        yield 'non-string' => [['/admin']];
    }
}
