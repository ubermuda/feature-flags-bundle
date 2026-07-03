<?php

namespace Ubermuda\FeatureFlagsBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Ubermuda\FeatureFlagsBundle\Attribute\RequireFeatureFlag;
use Ubermuda\FeatureFlagsBundle\FeatureFlagService;

/**
 * Validates the #[RequireFeatureFlag] attribute before the controller runs.
 *
 * Throws NotFoundHttpException (→ 404) when the named flag is disabled. No
 * isMainRequest() guard: a flag that is off must 404 in sub-requests too.
 */
#[AsEventListener]
final readonly class RequireFeatureFlagListener
{
    public function __construct(
        private FeatureFlagService $featureFlags,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ControllerEvent $event): void
    {
        foreach ($event->getAttributes(RequireFeatureFlag::class) as $attribute) {
            if (!$this->featureFlags->isEnabled($attribute->name)) {
                $this->logger->info('feature_flag.controller.denied', [
                    'flag' => $attribute->name,
                    'path' => $event->getRequest()->getPathInfo(),
                ]);

                throw new NotFoundHttpException(sprintf('Feature flag "%s" is not enabled.', $attribute->name));
            }
        }
    }
}
