<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Attribute;

use PHPUnit\Framework\TestCase;
use Ubermuda\FeatureFlagsBundle\Attribute\RequireFeatureFlag;

final class RequireFeatureFlagTest extends TestCase
{
    public function testExposesTheFlagName(): void
    {
        $attribute = new RequireFeatureFlag('poll.suggestions.enabled');

        self::assertSame('poll.suggestions.enabled', $attribute->name);
    }

    public function testTargetsClassesAndMethods(): void
    {
        $reflection = new \ReflectionClass(RequireFeatureFlag::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        self::assertCount(1, $attributes);
        self::assertSame(
            \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD,
            $attributes[0]->newInstance()->flags,
        );
    }
}
