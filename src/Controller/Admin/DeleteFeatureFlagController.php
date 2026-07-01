<?php

namespace Ubermuda\FeatureFlagsBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ubermuda\FeatureFlagsBundle\Command\DeleteFeatureFlagCommand;
use Ubermuda\FeatureFlagsBundle\Command\DeleteFeatureFlagHandler;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;
use Ubermuda\FeatureFlagsBundle\Form\ConfirmDeleteType;
use Ubermuda\FeatureFlagsBundle\Listing\AdminReturnTo;
use Ubermuda\FeatureFlagsBundle\Security\FeatureFlagVoter;

#[IsGranted(FeatureFlagVoter::ADMIN)]
final class DeleteFeatureFlagController extends AbstractController
{
    public function __construct(
        private readonly DeleteFeatureFlagHandler $deleteFeatureFlag,
        private readonly AdminReturnTo $returnTo,
    ) {
    }

    public function __invoke(FeatureFlag $flag, Request $request): Response
    {
        $form = $this->createForm(ConfirmDeleteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            ($this->deleteFeatureFlag)(new DeleteFeatureFlagCommand($flag));

            $this->addFlash('success', 'feature_flags.flash.deleted');

            $validatedReturnTo = $this->returnTo->validate($request->request->get('returnTo'));

            return $this->redirect($validatedReturnTo ?? $this->generateUrl('ubermuda_feature_flags_list'));
        }

        return $this->render('@UbermudaFeatureFlags/admin/delete.html.twig', [
            'flag' => $flag,
            'form' => $form,
        ]);
    }
}
