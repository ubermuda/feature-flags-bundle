<?php

namespace Ubermuda\FeatureFlagsBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Authorization for the feature-flag admin. Controllers gate on the
 * {@see self::ADMIN} attribute rather than a hardcoded role, so the access
 * policy lives in one place and is the extension point: decorate or replace
 * this voter (or add another that votes on the same attribute) to change it.
 *
 * @extends Voter<self::ADMIN, mixed>
 */
final class FeatureFlagVoter extends Voter
{
    public const string ADMIN = 'feature_flag.admin';

    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ADMIN === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        // Default policy: any administrator manages feature flags.
        return $this->authorizationChecker->isGranted('ROLE_ADMIN');
    }
}
