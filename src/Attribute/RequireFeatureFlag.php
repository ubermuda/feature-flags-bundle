<?php

namespace Ubermuda\FeatureFlagsBundle\Attribute;

/**
 * Declarative feature-flag gating for controllers.
 *
 * Place on the controller class (or its __invoke method); RequireFeatureFlagListener
 * checks the named flag on kernel.controller, before the action runs, and throws a
 * 404 NotFoundHttpException when the flag is disabled.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class RequireFeatureFlag
{
    public function __construct(
        public string $name,
    ) {
    }
}
