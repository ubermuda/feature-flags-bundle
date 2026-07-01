<?php

namespace Ubermuda\FeatureFlagsBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\Listing\ListPageRequest;
use Ubermuda\FeatureFlagsBundle\Listing\PageList;
use Ubermuda\FeatureFlagsBundle\Repository\FeatureFlagRepository;
use Ubermuda\FeatureFlagsBundle\Security\FeatureFlagVoter;

#[IsGranted(FeatureFlagVoter::ADMIN)]
final class ListFeatureFlagsController extends AbstractController
{
    private const int PER_PAGE = 20;
    private const array ALLOWED_SORTS = ['name', 'type'];

    public function __construct(
        private readonly FeatureFlagRepository $featureFlags,
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
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        if ($listRequest->page > $totalPages) {
            return $this->redirectToRoute(
                'ubermuda_feature_flags_list',
                [...$request->query->all(), 'page' => $totalPages],
            );
        }

        return $this->render('@UbermudaFeatureFlags/admin/list.html.twig', [
            'flags' => $paginator,
            'total' => $total,
            'page' => $listRequest->page,
            'totalPages' => $totalPages,
            'pageList' => PageList::build($listRequest->page, $totalPages),
            'allTags' => $this->featureFlags->findAllTags(),
            'activeTag' => '' !== $tag ? $tag : null,
            'sort' => $listRequest->sort,
            'dir' => $listRequest->dir,
            'filters' => $filters,
        ]);
    }
}
