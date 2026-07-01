# UbermudaFeatureFlagsBundle

Database-backed feature flags for Symfony, with a self-contained admin UI.

Flags have a type — **bool**, **int**, or **select** (one value from a fixed list)
— optional **tags**, and are read at runtime through a small service or two Twig
functions. A bundled admin UI lists, creates, edits, toggles, and prunes flags, and
can scan your code for referenced-but-undefined (and defined-but-orphaned) flags.

> **Requires PostgreSQL.** Tags are stored as `jsonb` and filtered with the `@>`
> containment operator. Other databases are not supported.

## Installation

```bash
composer require ubermuda/feature-flags-bundle
```

Register the bundle (Symfony Flex does this automatically):

```php
// config/bundles.php
return [
    // ...
    Ubermuda\FeatureFlagsBundle\UbermudaFeatureFlagsBundle::class => ['all' => true],
];
```

Import the admin routes:

```yaml
# config/routes/ubermuda_feature_flags.yaml
ubermuda_feature_flags:
    resource: '@UbermudaFeatureFlagsBundle/config/routes.php'
```

### Schema

The bundle ships the Doctrine mapping for the `feature_flag` table but cannot ship a
migration for your app. Generate one:

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

The canonical table (PostgreSQL):

```sql
CREATE TABLE feature_flag (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(255) NOT NULL,
    value JSON DEFAULT NULL,
    tags JSONB NOT NULL,
    options JSONB DEFAULT NULL
);
CREATE UNIQUE INDEX uniq_feature_flag_name ON feature_flag (name);
```

## Configuration

All keys are optional; defaults shown:

```yaml
# config/packages/ubermuda_feature_flags.yaml
ubermuda_feature_flags:
    route_prefix: /admin/feature-flags  # where the admin routes mount
    scan:
        paths:                          # directories scanned for referenced flags
            - '%kernel.project_dir%/templates'
            - '%kernel.project_dir%/src'
```

### Authorization

Admin access is gated by the `feature_flag.admin` permission, decided by
`FeatureFlagVoter` — which by default grants any user with `ROLE_ADMIN`. To change the
policy (e.g. a dedicated role, or per-flag rules), decorate/replace that voter or add
another voter that votes on the `feature_flag.admin` attribute; the controllers don't
change.

### CSRF

The toggle, delete, and prune-orphaned actions are hand-rolled POST forms guarded by
`ubermuda/symfony-extra`'s `#[CsrfToken]` (stateless). The bundle auto-registers their
token ids (`feature_flag_toggle`, `feature_flag_delete`, `feature_flag_delete_orphaned`) under
`framework.csrf_protection.stateless_token_ids`, so no app config is needed — but
`ubermuda/symfony-extra`'s bundle must be registered in your app for the validating
listener to run.

## Usage

### PHP

```php
use Ubermuda\FeatureFlagsBundle\FeatureFlagService;

public function __construct(private FeatureFlagService $featureFlags) {}

if ($this->featureFlags->isEnabled('poll.suggestions.enabled')) { /* ... */ }

$limit = $this->featureFlags->getIntValue('rsvp.max_guests', 10);
$style = $this->featureFlags->getValue('rsvp.nudge.style'); // select flag
```

A flag missing from the database returns the supplied default (`false` / the given
int / `null`). Reading a flag with the wrong accessor for its type logs an error and
returns the default.

### Twig

```twig
{% if is_feature_enabled('poll.suggestions.enabled') %}…{% endif %}
{{ feature_flag_value('rsvp.nudge.style') }}
```

### Custom runtime backend

The runtime read path is an interface, `FeatureFlagReaderInterface`, returning plain
`ResolvedFlag` value objects. The default `DoctrineFeatureFlagReader` is
Doctrine-backed and request-cached; an `InMemoryFeatureFlagReader` is provided for
tests. Alias the interface to your own service to swap the backend.

### Customising the admin UI

Admin templates extend `@UbermudaFeatureFlags/base.html.twig`. Override any template
by placing a file at the same path under your app's
`templates/bundles/UbermudaFeatureFlagsBundle/` directory.

### JavaScript (create/edit form)

The create/edit form uses a Stimulus controller, `feature-flag-form`, to show only the
value field(s) for the selected type and to keep the Select dropdown in sync with the
options textarea. The controller source ships with the bundle at
`assets/controllers/feature_flag_form_controller.js`.

Register it as an app-level Stimulus controller (so it keeps the simple
`feature-flag-form` identifier the templates use). With AssetMapper, the simplest route
is to copy or symlink the file into your app's `assets/controllers/` directory:

```bash
cp vendor/ubermuda/feature-flags-bundle/assets/controllers/feature_flag_form_controller.js \
   assets/controllers/
```

Without the controller the form still works — it just shows every value field at once.

## Testing

```bash
composer install
vendor/bin/phpunit
```

The bundle's own suite boots on SQLite (no server needed) and covers the runtime
service, the scanner, the reader mapping, the command handlers, template rendering,
and service wiring. The **PostgreSQL-specific repository queries** (`findByTag`, the
`tags @> :tag::jsonb` tag filter in `findPaginated`) are not exercised by these tests
— they require a live PostgreSQL database. Verify them against Postgres in your
consuming application's integration tests.
