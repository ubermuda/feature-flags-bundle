<?php

namespace Ubermuda\FeatureFlagsBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Ubermuda\FeatureFlagsBundle\Command\ToggleFeatureFlagCommand;
use Ubermuda\FeatureFlagsBundle\Command\ToggleFeatureFlagHandler;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;
use Ubermuda\AdminBundle\Listing\AdminReturnTo;
use Ubermuda\FeatureFlagsBundle\Security\FeatureFlagVoter;
use Ubermuda\SymfonyExtra\Csrf\Attribute\CsrfToken;

#[IsGranted(FeatureFlagVoter::ADMIN)]
#[CsrfToken('feature_flag_toggle')]
final class ToggleFeatureFlagController extends AbstractController
{
    public function __construct(
        private readonly ToggleFeatureFlagHandler $toggleFeatureFlag,
        private readonly AdminReturnTo $returnTo,
    ) {
    }

    public function __invoke(FeatureFlag $flag, Request $request): Response
    {
        ($this->toggleFeatureFlag)(new ToggleFeatureFlagCommand($flag));

        $validatedReturnTo = $this->returnTo->validate('feature_flag', $request->request->get('returnTo'));

        return $this->redirect($validatedReturnTo ?? $this->generateUrl('ubermuda_feature_flags_list'));
    }
}
