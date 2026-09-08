# symfony-remote: finish scaffolding, then design the package

## What this package is for

A shared Symfony package for anything that talks to an external service: PDF
generator, remove.bg-style API, Claude SDK, wex scripts, etc. (Postgres/Doctrine
is out of scope — it already has its own health-checking).

Idea validated with the user, not yet designed in detail:
- An overridable status class per integration (e.g. a `symfony-claude` package
  would declare its own `ClaudeRemoteMonitor`) that reports connection status.
- Possibly an admin page listing every registered remote's status at a glance,
  with a "Test connection" button per entry.
- Possibly a generic entity for storing credentials, reusable across
  integrations.

None of the above is decided — naming, storage strategy (encrypted entity vs.
env vars + status-only in DB), interface shape, and whether the admin page is
in scope now or later are all open. That design is this package's job, not
predetermined by this note.

## Blocking: scaffolding is incomplete

Diffed against `symfony-wex` (a comparable freshly-scaffolded sibling bundle).
Found before any feature work can start:

1. `composer.json` is skeletal (`{name, version}` only) — missing `type`,
   `license`, `autoload` (**PSR-4 is absent, so nothing under `src/` is
   loadable by Composer right now**), `authors`, `require` (php constraint,
   `wexample/symfony-helpers`).
2. No Symfony bundle infrastructure at all: no `WexampleSymfonyRemoteBundle.php`,
   no `src/DependencyInjection/` (`Configuration.php` + Extension class), no
   `src/Resources/config/services.yaml`. Every sibling bundle package has these
   three pieces.
3. `src/Entity/` and `src/Entity/Traits/Manipulator/` exist but are empty (not
   even `.gitkeep`, so git isn't tracking them) — and are owned by `root:root`
   instead of the normal user, apparently created from inside a Docker
   container. Fix ownership before writing files there.
4. `tests/` is empty, no `.gitkeep`.

Fix the scaffolding first (mirror `symfony-wex`'s `composer.json` and bundle
layout), then start on the design questions above.
