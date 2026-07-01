<?php

namespace Ubermuda\FeatureFlagsBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Ubermuda\FeatureFlagsBundle\Command\DeleteOrphanedFeatureFlagsCommand;
use Ubermuda\FeatureFlagsBundle\Command\DeleteOrphanedFeatureFlagsHandler;
use Ubermuda\FeatureFlagsBundle\Security\FeatureFlagVoter;
use Ubermuda\SymfonyExtra\Csrf\Attribute\CsrfToken;

#[IsGranted(FeatureFlagVoter::ADMIN)]
#[CsrfToken('feature_flag_delete_orphaned')]
final class DeleteOrphanedFeatureFlagsController extends AbstractController
{
    public function __construct(
        private readonly DeleteOrphanedFeatureFlagsHandler $deleteOrphanedFeatureFlags,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $selectedNames = array_values(array_filter($request->request->all('names'), is_string(...)));

        ($this->deleteOrphanedFeatureFlags)(new DeleteOrphanedFeatureFlagsCommand($selectedNames));

        return $this->redirectToRoute('ubermuda_feature_flags_scan');
    }
}
