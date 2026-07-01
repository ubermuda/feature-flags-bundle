<?php

namespace Ubermuda\FeatureFlagsBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ubermuda\FeatureFlagsBundle\Command\UpdateFeatureFlagCommand;
use Ubermuda\FeatureFlagsBundle\Command\UpdateFeatureFlagHandler;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\Form\FeatureFlagRequest;
use Ubermuda\FeatureFlagsBundle\Form\FeatureFlagType as FeatureFlagFormType;
use Ubermuda\FeatureFlagsBundle\Listing\AdminReturnTo;
use Ubermuda\FeatureFlagsBundle\Repository\FeatureFlagRepository;
use Ubermuda\FeatureFlagsBundle\Security\FeatureFlagVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(FeatureFlagVoter::ADMIN)]
final class EditFeatureFlagController extends AbstractController
{
    public function __construct(
        private readonly FeatureFlagRepository $featureFlags,
        private readonly UpdateFeatureFlagHandler $updateFeatureFlag,
        private readonly AdminReturnTo $returnTo,
    ) {
    }

    public function __invoke(FeatureFlag $flag, Request $request): Response
    {
        $flagRequest = FeatureFlagRequest::fromFlag($flag);
        $form = $this->createForm(FeatureFlagFormType::class, $flagRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $value = match ($flagRequest->type) {
                FeatureFlagType::Bool => $flagRequest->boolValue ?? false,
                FeatureFlagType::Int => $flagRequest->intValue ?? 0,
                FeatureFlagType::Select => $flagRequest->selectValue,
            };
            $options = FeatureFlagType::Select === $flagRequest->type ? $flagRequest->options : null;

            ($this->updateFeatureFlag)(new UpdateFeatureFlagCommand(
                flag: $flag,
                name: $flagRequest->name ?? throw new \LogicException('name required after validation'),
                type: $flagRequest->type,
                value: $value,
                tags: $flagRequest->tags,
                options: $options,
            ));

            $this->addFlash('success', 'feature_flags.flash.updated');

            // Redirect back to the edit page so admins can keep iterating; forward the
            // validated returnTo so the form's Back/Cancel links still reach the list.
            $redirectParams = ['id' => $flag->id];
            $validatedReturnTo = $this->returnTo->validate($request->query->get('returnTo'));
            if (null !== $validatedReturnTo) {
                $redirectParams['returnTo'] = $validatedReturnTo;
            }

            return $this->redirectToRoute('ubermuda_feature_flags_edit', $redirectParams);
        }

        return $this->render('@UbermudaFeatureFlags/admin/edit.html.twig', [
            'form' => $form,
            'flag' => $flag,
            'allTags' => $this->featureFlags->findAllTags(),
        ]);
    }
}
