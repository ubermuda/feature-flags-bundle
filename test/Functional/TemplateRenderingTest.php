<?php

namespace Ubermuda\FeatureFlagsBundle\Test\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

final class TemplateRenderingTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return FeatureFlagsTestKernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    private function twig(): Environment
    {
        self::bootKernel();
        $container = self::getContainer();

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $container->get('request_stack')->push($request);

        return $container->get('twig');
    }

    public function testEveryTemplateCompiles(): void
    {
        $twig = $this->twig();

        foreach (['base', 'admin/list', 'admin/create', 'admin/edit', 'admin/scan'] as $name) {
            $twig->load('@UbermudaFeatureFlags/'.$name.'.html.twig');
        }

        $this->addToAssertionCount(1);
    }

    public function testListTemplateRenders(): void
    {
        $html = $this->twig()->render('@UbermudaFeatureFlags/admin/list.html.twig', [
            'flags' => [],
            'total' => 0,
            'page' => 1,
            'totalPages' => 1,
            'pageList' => [1],
            'allTags' => ['team'],
            'activeTag' => null,
            'sort' => 'name',
            'dir' => 'asc',
            'filters' => [],
        ]);

        self::assertStringContainsString('Feature flags', $html);
        self::assertStringContainsString('Name', $html);
        // The type filter is populated from the enum cases.
        self::assertStringContainsString('Bool', $html);
    }

    public function testCreateFormRendersWithFieldSwitchingHooks(): void
    {
        $twig = $this->twig();
        $form = self::getContainer()->get('form.factory')
            ->create(\Ubermuda\FeatureFlagsBundle\Form\FeatureFlagType::class, new \Ubermuda\FeatureFlagsBundle\Form\FeatureFlagRequest())
            ->createView();

        $html = $twig->render('@UbermudaFeatureFlags/admin/create.html.twig', ['form' => $form]);

        // The form is bound to the shipped Stimulus controller, and the value fields carry its targets.
        self::assertStringContainsString('data-controller="feature-flag-form"', $html);
        self::assertStringContainsString('data-feature-flag-form-target="typeSelect"', $html);
        self::assertStringContainsString('feature-flag-form#updateType', $html);
    }

    public function testScanTemplateRenders(): void
    {
        $html = $this->twig()->render('@UbermudaFeatureFlags/admin/scan.html.twig', [
            'undefined_flags' => ['undefined.flag'],
            'orphan_flags' => ['orphan.flag'],
        ]);

        self::assertStringContainsString('undefined.flag', $html);
        self::assertStringContainsString('orphan.flag', $html);
        self::assertStringContainsString('names[]', $html);
    }
}
