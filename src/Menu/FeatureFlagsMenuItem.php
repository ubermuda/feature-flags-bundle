<?php

namespace Ubermuda\FeatureFlagsBundle\Menu;

use Ubermuda\AdminBundle\Menu\AdminMenuItemInterface;

/**
 * Contributes the "Feature Flags" entry to the admin sidebar.
 *
 * Auto-tagged (`app.admin_menu_item`) via the admin bundle's instanceof
 * autoconfiguration, since this bundle's services.php loads `src/` with
 * autoconfigure enabled and does not exclude `Menu/`.
 */
final class FeatureFlagsMenuItem implements AdminMenuItemInterface
{
    public function getLabel(): string
    {
        return 'Feature Flags';
    }

    public function getIcon(): string
    {
        return 'flag';
    }

    public function getRouteName(): string
    {
        return 'ubermuda_feature_flags_list';
    }

    public function getActiveRoutePrefix(): string
    {
        return 'ubermuda_feature_flags';
    }

    public function getPriority(): int
    {
        return 60;
    }
}
