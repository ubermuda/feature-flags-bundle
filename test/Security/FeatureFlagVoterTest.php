<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Ubermuda\FeatureFlagsBundle\Security\FeatureFlagVoter;

final class FeatureFlagVoterTest extends TestCase
{
    public function testGrantsAdminsAndDeniesOthers(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $adminChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $adminChecker->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        $nonAdminChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $nonAdminChecker->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            (new FeatureFlagVoter($adminChecker))->vote($token, null, [FeatureFlagVoter::ADMIN]),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            (new FeatureFlagVoter($nonAdminChecker))->vote($token, null, [FeatureFlagVoter::ADMIN]),
        );
    }

    public function testAbstainsOnUnknownAttribute(): void
    {
        $voter = new FeatureFlagVoter($this->createMock(AuthorizationCheckerInterface::class));

        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($this->createMock(TokenInterface::class), null, ['something_else']),
        );
    }
}
