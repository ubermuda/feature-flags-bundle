<?php

namespace Ubermuda\FeatureFlagsBundle\Test\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Ubermuda\FeatureFlagsBundle\Attribute\RequireFeatureFlag;
use Ubermuda\FeatureFlagsBundle\Dto\ResolvedFlag;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\EventListener\RequireFeatureFlagListener;
use Ubermuda\FeatureFlagsBundle\FeatureFlagService;
use Ubermuda\FeatureFlagsBundle\Reader\InMemoryFeatureFlagReader;
use Ubermuda\FeatureFlagsBundle\Test\RecordingLogger;

final class RequireFeatureFlagListenerTest extends TestCase
{
    public function testThrowsNotFoundWhenFlagIsDisabled(): void
    {
        $listener = $this->listener();
        $event = $this->event(new RequireFeatureFlag('gated.feature'));

        $this->expectException(NotFoundHttpException::class);

        $listener($event);
    }

    public function testDoesNothingWhenFlagIsEnabled(): void
    {
        $listener = $this->listener(new ResolvedFlag('gated.feature', FeatureFlagType::Bool, true));
        $event = $this->event(new RequireFeatureFlag('gated.feature'));

        $listener($event);

        $this->addToAssertionCount(1);
    }

    public function testDoesNothingWhenNoAttributeIsPresent(): void
    {
        $listener = $this->listener();
        $event = $this->event();

        $listener($event);

        $this->addToAssertionCount(1);
    }

    private function listener(ResolvedFlag ...$flags): RequireFeatureFlagListener
    {
        $service = new FeatureFlagService(new InMemoryFeatureFlagReader($flags), new RecordingLogger());

        return new RequireFeatureFlagListener($service, new RecordingLogger());
    }

    private function event(object ...$attributes): ControllerEvent
    {
        $event = new ControllerEvent(
            $this->createStub(HttpKernelInterface::class),
            static fn () => null,
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $event->setController($event->getController(), $attributes);

        return $event;
    }
}
