<?php

namespace Ubermuda\FeatureFlagsBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;
use Ubermuda\FeatureFlagsBundle\Repository\FeatureFlagRepository;
use Ubermuda\FeatureFlagsBundle\Scanner\FeatureFlagScanner;
use Ubermuda\FeatureFlagsBundle\Security\FeatureFlagVoter;

#[IsGranted(FeatureFlagVoter::ADMIN)]
final class ScanFeatureFlagsController extends AbstractController
{
    public function __construct(
        private readonly FeatureFlagRepository $featureFlags,
        private readonly FeatureFlagScanner $scanner,
    ) {
    }

    public function __invoke(): Response
    {
        $flags = $this->featureFlags->findAll();
        $definedNames = array_map(static fn (FeatureFlag $flag): string => $flag->name, $flags);
        $referencedNames = $this->scanner->findReferencedFlags();

        return $this->render('@UbermudaFeatureFlags/admin/scan.html.twig', [
            'undefined_flags' => array_values(array_diff($referencedNames, $definedNames)),
            'orphan_flags' => array_values(array_diff($definedNames, $referencedNames)),
        ]);
    }
}
