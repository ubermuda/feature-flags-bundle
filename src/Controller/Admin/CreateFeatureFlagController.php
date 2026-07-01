<?php

namespace Ubermuda\FeatureFlagsBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Ubermuda\FeatureFlagsBundle\Command\CreateFeatureFlagCommand;
use Ubermuda\FeatureFlagsBundle\Command\CreateFeatureFlagHandler;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\Form\FeatureFlagRequest;
use Ubermuda\FeatureFlagsBundle\Form\FeatureFlagType as FeatureFlagFormType;
use Ubermuda\FeatureFlagsBundle\Repository\FeatureFlagRepository;
use Ubermuda\FeatureFlagsBundle\Security\FeatureFlagVoter;

#[IsGranted(FeatureFlagVoter::ADMIN)]
final class CreateFeatureFlagController extends AbstractController
{
    public function __construct(
        private readonly FeatureFlagRepository $featureFlags,
        private readonly CreateFeatureFlagHandler $createFeatureFlag,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $flagRequest = new FeatureFlagRequest(name: $request->query->getString('name'));
        $form = $this->createForm(FeatureFlagFormType::class, $flagRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $value = match ($flagRequest->type) {
                FeatureFlagType::Bool => $flagRequest->boolValue ?? false,
                FeatureFlagType::Int => $flagRequest->intValue ?? 0,
                FeatureFlagType::Select => $flagRequest->selectValue,
            };
            $options = FeatureFlagType::Select === $flagRequest->type ? $flagRequest->options : null;

            ($this->createFeatureFlag)(new CreateFeatureFlagCommand(
                name: $flagRequest->name ?? throw new \LogicException('name required after validation'),
                type: $flagRequest->type,
                value: $value,
                tags: $flagRequest->tags,
                options: $options,
            ));

            $this->addFlash('success', 'feature_flags.flash.created');

            return $this->redirectToRoute('ubermuda_feature_flags_list');
        }

        return $this->render('@UbermudaFeatureFlags/admin/create.html.twig', [
            'form' => $form,
            'allTags' => $this->featureFlags->findAllTags(),
        ]);
    }
}
