# Feature Flags Bundle — Design Spec

**Date:** 2026-06-30
**Status:** Implemented (36 tests passing)
**Target:** A standalone Symfony bundle `ubermuda/feature-flags-bundle`, extracted from the make-plans `FeatureFlag` module.

## Goal

Extract make-plans' feature-flag system into a reusable, self-contained Symfony bundle living at `~/Code/feature-flags-bundle`. The bundle ships both the runtime read API and a complete admin UI, with no dependency on make-plans-specific infrastructure (`AppController`, the `Admin\Listing` framework, the project's `DomainErrors` and command-handler base classes). The command/handler *pattern* itself is kept — see the Admin Path section.

Extensibility hooks (mutable events, consumer-overridable Twig layouts) are **explicitly deferred** — out of scope for this version. The bundle is structured so they can be added later without rework, but no extension points are exposed now.

## Locked Decisions

| Decision | Value |
|---|---|
| Composer package | `ubermuda/feature-flags-bundle` |
| PHP namespace | `Ubermuda\FeatureFlagsBundle\` |
| Bundle class | `UbermudaFeatureFlagsBundle` |
| Checkout directory | `~/Code/feature-flags-bundle` (matches package name) |
| Scope | Runtime core + self-contained admin UI |
| Extensibility | Deferred (no events, no layout override) |
| Storage | Approach A — split read/admin seams (below) |
| Database | PostgreSQL required (jsonb), documented |

## Architecture

A standard Symfony bundle (`AbstractBundle`-based):

```
config/                                    # at package root (AbstractBundle layout)
  services.php                             # service wiring
  routes.php                               # admin routes (configurable prefix)
templates/                                 # @UbermudaFeatureFlags namespace
  base.html.twig                           # bundle-owned base layout
  admin/{list,create,edit,delete,scan}.html.twig
assets/controllers/feature_flag_form_controller.js  # shipped Stimulus controller (field switching + options sync)
translations/messages.en.xlf               # flash strings (XLIFF, no symfony/yaml dep)
src/
  UbermudaFeatureFlagsBundle.php           # AbstractBundle: configure() + loadExtension() + prependExtension()
  Entity/FeatureFlag.php
  Enum/FeatureFlagType.php                  # Bool / Int / Select
  Dto/ResolvedFlag.php                      # plain runtime value object (no ORM)
  Reader/FeatureFlagReaderInterface.php     # the read seam: get() / all()
  Reader/DoctrineFeatureFlagReader.php      # default impl: Doctrine-backed, request-cached, maps entity -> DTO
  Reader/InMemoryFeatureFlagReader.php      # array-backed impl for tests / non-Doctrine runtime
  FeatureFlagService.php                    # isEnabled / getIntValue / getValue
  Repository/FeatureFlagRepository.php      # admin query/write (Doctrine, Postgres)
  Scanner/FeatureFlagScanner.php
  Twig/FeatureFlagExtension.php
  Form/FeatureFlagType.php + FeatureFlagRequest.php + ConfirmDeleteType.php
  Listing/ListPageRequest.php + PageList.php + AdminReturnTo.php  # minimal listing + returnTo guard
  Command/                                  # one command + handler pair per write action
    {Create,Update,Delete,Toggle,DeleteOrphaned}FeatureFlag(s)Command.php + Handler.php
  Security/FeatureFlagVoter.php             # feature_flag.admin permission (default ROLE_ADMIN)
  Controller/Admin/*Controller.php          # AbstractController, #[IsGranted] + #[CsrfToken]
test/                                       # the bundle's own PHPUnit suite (36 tests)
```

## Runtime Read Path (Approach A — two honest seams)

The storage "interface" is split along its two genuinely different access patterns.

The existing make-plans store (`FeatureFlagStore`) already sits between the service and the repository and *is* the read abstraction. Approach A does not add a layer beneath it — it **renames the store to `DoctrineFeatureFlagReader`** (symmetric with `InMemoryFeatureFlagReader`), gives it an interface, and changes its return type to a plain DTO.

- `FeatureFlagReaderInterface` — the read seam:
  - `get(string $name): ?ResolvedFlag`
  - `all(): array<string, ResolvedFlag>`
- `ResolvedFlag` — a plain `final readonly` DTO carrying `name`, `type` (`FeatureFlagType`), and `value` (`mixed`). **No ORM attributes.** This is what the runtime trades in, so a non-Doctrine backend never has to touch the mapped entity.
- `DoctrineFeatureFlagReader` — the **default implementation** of `FeatureFlagReaderInterface` (renamed from `FeatureFlagStore`). This is the current store, minimally changed: it stays Doctrine-backed (via `FeatureFlagRepository::findAllIndexed()`) and request-cached (loads once, indexes by name), but now maps each entity to a `ResolvedFlag` and is typed to the interface.
- `InMemoryFeatureFlagReader` — array-backed implementation built from `ResolvedFlag`s; used in tests and available for config-backed / non-Doctrine runtime.
- **No separate reader layer.** A generic "caching decorator over a raw fetcher" split (which would let several DB backends share one cache) is deliberately *not* built: only Doctrine + in-memory ship, and in-memory needs no cache, so the decorator would earn nothing. It can be factored out later if a third, cache-needing backend (e.g. Redis) ever lands.
- `FeatureFlagService` depends on `FeatureFlagReaderInterface`, and its public API is preserved verbatim:
  - `isEnabled(string $name, bool $default = false): bool`
  - `getIntValue(string $name, int $default): int`
  - `getValue(string $name): mixed`
  - Same type-mismatch logging behaviour (logs an error and returns the default when a flag's stored type doesn't match the accessor).
- `FeatureFlagExtension` (Twig) — unchanged: `is_feature_enabled(name)`, `feature_flag_value(name)`.

### Admin seam (concrete, Doctrine-only)

The admin query/write path is **not** abstracted behind an interface — only Doctrine will ever implement it, and pretending otherwise would leak `Paginator<FeatureFlag>` through a contract. The admin controllers and the command handlers depend on the concrete `FeatureFlagRepository`.

`FeatureFlagRepository` (ported from make-plans, Postgres jsonb retained):
- `findAllIndexed(): array<string, FeatureFlag>`
- `findByTag(string $tag): list<FeatureFlag>` — Postgres `tags @> :tag::jsonb`
- `findPaginated(page, limit, sort, dir, search, type, tag): Paginator<FeatureFlag>`
- `findAllTags(): list<string>`

## Admin Path (decoupled from make-plans)

Every make-plans-specific dependency is severed:

- **Base controller:** controllers extend Symfony `AbstractController`, not `AppController`.
- **Listing framework:** the `Admin\Listing` primitives (`ListPageRequest`, `ListPagePagination`, `AdminReturnTo`) are **reimplemented as a minimal, self-contained listing** inside the bundle — page param, sort whitelist, sort direction, search, type + tag filters, and page clamping. **`returnTo` is kept** so that acting on a row returns to the same filtered list, via a bundle-local `AdminReturnTo` generalized to an open-redirect guard that accepts any same-site **local path** (the make-plans version hardcoded a `/admin/` prefix; the bundle's route prefix is configurable). After an edit, the controller redirects back to the edit page with the validated `returnTo` forwarded so Back/Cancel still reach the list; Delete/Toggle redirect to `returnTo`.
- **Mutations:** the five command/handler pairs (create, update, delete, toggle, delete-orphaned) are **ported as-is** and kept as the bundle's write API — `final readonly` command DTOs carrying scalars + `final readonly` handlers with a single `__invoke` that owns persistence + flush. They are invoked directly by the controllers (e.g. `($this->updateFeatureFlag)(new UpdateFeatureFlagCommand(...))`), not via Messenger. They are decoupled only from make-plans base classes; any make-plans `DomainErrors` usage (e.g. duplicate-name validation surfaced as a form error) is replaced by a small bundle-local equivalent rather than a dependency on make-plans. Each handler emits a domain-observability `info` log (`feature_flag.created` / `.updated` / `.deleted` / `.toggled` / `.orphaned_pruned`) with entity ids, per the house convention.
- **Authorization:** controllers gate on the `feature_flag.admin` permission via `#[IsGranted(FeatureFlagVoter::ADMIN)]`. The bundle ships `FeatureFlagVoter`, which by default grants `ROLE_ADMIN`. The voter (and the dotted attribute) is the extension point — decorate/replace it to change policy without touching controllers. There is **no** `admin_role` config: a bundle shouldn't bake authorization into a role string.
- **Routing:** the admin route prefix is **configurable** via config (default `/admin/feature-flags`). The consuming app imports the bundle's `routes.php`.

### Admin actions ported

| Action | Current make-plans controller | Bundle equivalent |
|---|---|---|
| List (paginated/filtered/sorted) | `ListFeatureFlagsController` | `ListFeatureFlagsController` + minimal listing |
| Edit (GET/POST form) | `EditFeatureFlagController` | `EditFeatureFlagController` + `UpdateFeatureFlagHandler` |
| Create | `CreateFeatureFlagController` | `CreateFeatureFlagController` + `CreateFeatureFlagHandler` |
| Delete | `DeleteFeatureFlagController` | `DeleteFeatureFlagController` + `DeleteFeatureFlagHandler` |
| Toggle | `ToggleFeatureFlagController` | `ToggleFeatureFlagController` + `ToggleFeatureFlagHandler` |
| Scan / prune orphaned | `ScanFeatureFlagsController` + `DeleteOrphanedFeatureFlagsController` | `ScanFeatureFlagsController` + `DeleteOrphanedFeatureFlagsController` + `DeleteOrphanedFeatureFlagsHandler` |

### Forms

`FeatureFlagType` (form) + `FeatureFlagRequest` (DTO) are ported as-is, retaining the Bool/Int/Select value mapping and the `options` list for Select-typed flags. The two `FeatureFlagType` classes (enum vs form type) keep their existing import-alias pattern to avoid collisions.

## Scanner

The current scanner hardcodes `%kernel.project_dir%` and `->notPath('Module/FeatureFlag')`. Both break once the code lives in `vendor/`.

- Scan paths become **configurable** via `ubermuda_feature_flags.scan.paths` (default `['%kernel.project_dir%/templates', '%kernel.project_dir%/src']`).
- The self-exclusion (`notPath('Module/FeatureFlag')`) is **dropped** — the bundle lives in `vendor/`, outside the scanned application directories, so it no longer needs to exclude itself.
- Regex extraction targets the same call sites: Twig (`is_feature_enabled`, `feature_flag_value`) and PHP (`isEnabled`, `getValue`, `getIntValue`). **Deviation from "unchanged":** the make-plans regex required the flag name to be the *only* argument (a trailing `)` right after the name). That silently misses every `getIntValue($name, $default)` (the default is mandatory) and every two-argument `isEnabled($name, true)` — so those flags would be classified orphaned and could be deleted while in active use. The bundle relaxes the pattern to match the first string argument regardless of trailing arguments, closing a real data-loss footgun. This is a deliberate, surfaced fix, not a silent port change.

## Schema Delivery

A bundle cannot ship a make-plans migration. Instead:

- The bundle ships the Doctrine **mapping** (attributes on `FeatureFlag`), auto-registered for the consuming app.
- The bundle documents a canonical `CREATE TABLE feature_flag (...)` (Postgres, with jsonb `tags` and `options` columns) and instructs consumers to generate their own migration via `doctrine:migrations:diff`.
- PostgreSQL is a documented hard requirement (jsonb + the `@>` containment operator in tag queries).

## Configuration Surface

```yaml
ubermuda_feature_flags:
    route_prefix: /admin/feature-flags
    scan:
        paths:
            - '%kernel.project_dir%/templates'
            - '%kernel.project_dir%/src'
```

Authorization is via `FeatureFlagVoter` (permission `feature_flag.admin`, default `ROLE_ADMIN`), not config. The CSRF token ids for the hand-rolled forms are auto-registered into `framework.csrf_protection.stateless_token_ids` by the bundle.

## Testing

The bundle carries its own PHPUnit suite (36 tests, booting on SQLite):

- **`FeatureFlagService`** — unit tests via `InMemoryFeatureFlagReader`: default fallbacks, type-mismatch logging, Bool/Int/Select reads.
- **`FeatureFlagScanner`** — regex extraction over a fixture tree (Twig + PHP), de-dup, sort, including two-argument calls.
- **`ResolvedFlag` mapping** — `DoctrineFeatureFlagReader` maps entity → DTO correctly and caches per request.
- **Command handlers** — create/update/delete/toggle/delete-orphaned persistence behaviour (one test per handler) plus the `feature_flag.created` observability event.
- **`FeatureFlagVoter`** — grants `ROLE_ADMIN`, denies others, abstains on unknown attributes.
- **`PageList`** — windowed pagination list with ellipses.
- **`AdminReturnTo`** — local-path accepted; protocol-relative / absolute-URL / non-string rejected.
- **Template rendering** — compiles every template, renders the list + scan pages, and renders the create form to assert the Stimulus binding (`data-controller="feature-flag-form"` + targets/action) is present.
- **Wiring smoke test** — boots the bundle, asserts core services resolve and the admin routes mount under the configured prefix.

The Postgres-only repository queries (`findByTag`, the jsonb tag filter in `findPaginated`) are **not** exercised by this suite (it runs on SQLite) and must be verified in a consuming app's integration tests.

## Sequencing

1. **Phase 1 — Runtime core.** Bundle skeleton, entity, enum, `ResolvedFlag`, `FeatureFlagReaderInterface` with its two implementations (`DoctrineFeatureFlagReader` default + `InMemoryFeatureFlagReader`), `FeatureFlagService`, Twig extension, scanner, config + DI, and their unit tests. Lands as a clean, independently useful slice.
2. **Phase 2 — Admin UI.** The command/handler pairs, the admin controllers, minimal listing primitives, forms, templates + bundle base layout, routes, translations, and admin tests. The bulk of the effort.

## Implementation notes (as built)

Decisions made while building that refine or deviate from the design above:

- **Bundle layout.** `AbstractBundle::getPath()` returns the package root, so the
  modern layout is used: `config/` (services.php, routes.php), `templates/`, and
  `translations/` live at the **package root**, not under `src/Resources/`. Entity
  mapping is registered from `src/Entity` via `prependExtension`.
- **Scanner regex** relaxed to fix a data-loss footgun — see the Scanner section.
- **CSRF.** The toggle and delete-orphaned actions are guarded by `ubermuda/symfony-extra`'s
  `#[CsrfToken]` (stateless), matching the house convention. The bundle auto-registers their
  token ids via `prependExtension` (`framework.csrf_protection.stateless_token_ids`); the
  forms post `_csrf_token`. The consuming app must register `ubermuda/symfony-extra`'s bundle
  for the validating listener.
- **Authorization via voter.** A bundle expresses policy as a permission (`feature_flag.admin`
  + `FeatureFlagVoter`, default `ROLE_ADMIN`), not a hardcoded/configured role — giving an
  extension point. App conventions (inline `#[IsGranted('ROLE_ADMIN')]`) and bundle
  conventions differ here.
- **Observability.** Handlers log `feature_flag.*` `info` events per the house rule.
- **License** is `proprietary` (single private consumer), not MIT.
- **Confirm-delete form.** A bundle-local `ConfirmDeleteType` replaces make-plans'
  `App\Module\Event\Form\ConfirmDeleteType`.
- **JavaScript shipped as a Stimulus controller.** The bundle is private and the stack is
  fixed (Stimulus + AssetMapper), so the original `feature-flag-form` Stimulus controller is
  shipped verbatim at `assets/controllers/feature_flag_form_controller.js` and the form
  carries `data-controller="feature-flag-form"` (the form type already emits the matching
  targets/action). It is registered **app-level** (simple identifier) rather than via the UX
  `extra.symfony.controllers` packaging, which would namespace the identifier to
  `ubermuda--…` and force a form-type rewrite. The consuming app copies/symlinks the file
  into its `assets/controllers/`. (An earlier inline-vanilla approach was rejected: a strict
  CSP — make-plans ships `nelmio/security-bundle` — can block inline scripts.)
- **`returnTo` kept.** Acting on a row returns to the same filtered list; see the Admin
  Path section. Edit redirects back to the edit page (iterating) with `returnTo` forwarded.
- **Translations** ship as XLIFF (`messages.en.xlf`), avoiding a hard `symfony/yaml`
  dependency.

## Out of Scope (deferred)

- Mutable/observational events on either the write path or the runtime evaluation path.
- Consumer-overridable Twig base layout (the bundle ships its own layout; override hooks come later).
- Non-Postgres database support.
