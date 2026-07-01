<?php

namespace Ubermuda\FeatureFlagsBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Ubermuda\FeatureFlagsBundle\Command\DeleteFeatureFlagCommand;
use Ubermuda\FeatureFlagsBundle\Command\DeleteFeatureFlagHandler;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;
use Ubermuda\AdminBundle\Listing\AdminReturnTo;
use Ubermuda\FeatureFlagsBundle\Security\FeatureFlagVoter;
use Ubermuda\SymfonyExtra\Csrf\Attribute\CsrfToken;

/**
 * Deletes a flag via a stateless CSRF-guarded POST — symmetric with the toggle
 * action — so a host app can wire an inline/modal delete button instead of a
 * dedicated confirmation page.
 */
#[IsGranted(FeatureFlagVoter::ADMIN)]
#[CsrfToken('feature_flag_delete')]
final class DeleteFeatureFlagController extends AbstractController
{
    public function __construct(
        private readonly DeleteFeatureFlagHandler $deleteFeatureFlag,
        private readonly AdminReturnTo $returnTo,
    ) {
    }

    public function __invoke(FeatureFlag $flag, Request $request): Response
    {
        ($this->deleteFeatureFlag)(new DeleteFeatureFlagCommand($flag));

        $this->addFlash('success', 'feature_flags.flash.deleted');

        $validatedReturnTo = $this->returnTo->validate('feature_flag', $request->request->get('returnTo'));

        return $this->redirect($validatedReturnTo ?? $this->generateUrl('ubermuda_feature_flags_list'));
    }
}
