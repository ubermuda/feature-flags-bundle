<?php

namespace Ubermuda\FeatureFlagsBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Ubermuda\AdminBundle\Listing\ListPagePagination;
use Ubermuda\AdminBundle\Listing\ListPageRequest;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\Repository\FeatureFlagRepository;
use Ubermuda\FeatureFlagsBundle\Security\FeatureFlagVoter;

#[IsGranted(FeatureFlagVoter::ADMIN)]
final class ListFeatureFlagsController extends AbstractController
{
    private const int PER_PAGE = 20;
    private const array ALLOWED_SORTS = ['name', 'type'];

    public function __construct(
        private readonly FeatureFlagRepository $featureFlags,
        private readonly ListPagePagination $pagination,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $listRequest = ListPageRequest::fromRequest($request, self::ALLOWED_SORTS, 'name', 'asc');

        $search = trim($request->query->getString('q'));
        $type = $request->query->getString('type');
        $type = null !== FeatureFlagType::tryFrom($type) ? $type : '';
        $tag = $request->query->getString('tag');

        $filters = array_filter(['q' => $search, 'type' => $type, 'tag' => $tag]);

        $paginator = $this->featureFlags->findPaginated(
            page: $listRequest->page,
            limit: self::PER_PAGE,
            sort: $listRequest->sort,
            dir: $listRequest->dir,
            search: $search,
            type: $type,
            tag: $tag,
        );
        $total = count($paginator);

        $clampedPage = $this->pagination->clampPage('feature_flags', $listRequest->page, $total, self::PER_PAGE, $filters);
        if (null !== $clampedPage) {
            return $this->redirectToRoute(
                'ubermuda_feature_flags_list',
                [...$request->query->all(), 'page' => $clampedPage],
            );
        }

        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        return $this->render('@UbermudaFeatureFlags/admin/list.html.twig', [
            'flags' => $paginator,
            'total' => $total,
            'page' => $listRequest->page,
            'totalPages' => $totalPages,
            'pageList' => $this->pagination->buildPageList($listRequest->page, $totalPages),
            'allTags' => $this->featureFlags->findAllTags(),
            'activeTag' => '' !== $tag ? $tag : null,
            'sort' => $listRequest->sort,
            'dir' => $listRequest->dir,
            'filters' => $filters,
        ]);
    }
}
