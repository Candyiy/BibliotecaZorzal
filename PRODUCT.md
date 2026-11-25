# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- Library administrators who manage the catalogue, licences and subscriptions of a digital book library (inferred from the brief and the existing authenticated admin views; no interview was held — this is a subagent run from a complete brief).
- Subscribers who discover and read books from the collection (inferred from the brief).

## Product Purpose

ZORZEL is a subscription virtual library. Its authenticated area gives staff an overview of the collection and CRUD screens for its records (inferred from the brief); success is an admin who can scan state and manage records without friction (inferred).

## Positioning

An exclusive digital library that carries the feeling of a classical library into a modern SaaS product (inferred from the brief's own words).

## Operating Context

Desktop-first admin work: a dashboard, catalogue tables/forms, and authentication/settings screens. Spanish-language interface copy is already present in the existing CRUD views and is preserved.

## Capabilities and Constraints

- Existing implemented routes: dashboard; `autores`, `libros` (including PDF read/download) and `licencias`/`licencias-editoriales` CRUD; authentication, email verification, two-factor, passkeys and settings (repository evidence).
- The brief also names categorías, editoriales, suscriptores, préstamos, devoluciones, suscripciones and penalizaciones; no routes, controllers or views exist for these in the repository. No new routes or features are added in this redesign (explicit constraint + repository evidence).
- Do not modify business logic, controllers, models, routes, migrations or the database. Keep every existing CRUD and Laravel compatibility (explicit constraint).
- The entire application must be dark-only (explicit constraint).
- Design tokens must be centralised in CSS variables / design tokens (explicit constraint).

## Brand Commitments

- Name: ZORZEL (repository `APP_NAME` and brief).
- Theme name: DARK ACADEMIA PREMIUM (explicitly pinned in the brief).
- Palette: backgrounds `#171311`, sidebar `#251B18`, navbar `#1D1714`, cards `#35251F`, secondary surface `#2C211C`; primary `#722F3E` / hover `#873B4C`; secondary `#583B32`; accent `#B08D57` / hover `#C3A36D`; text `#E8DED2` / `#B8A99A` / `#88796D`; borders `#49372F` / `#44332C`; states success `#54866A`, warning `#C49A52`, danger `#B84C4C`, info `#708CA3`; inputs `#211A17` / `#49372F` / focus `#B08D57` (explicitly pinned in the brief).
- Typography: Cormorant Garamond or Playfair Display for titles, Inter or Manrope for UI and content (explicitly pinned in the brief).
- Iconography: Lucide, consistent stroke and weight (explicitly pinned in the brief).

## Evidence on Hand

- Laravel 13 app with Livewire/Flux starter-kit views, Tailwind CSS v4, Vite, SQLite database at `database/database.sqlite`.
- Existing views: `resources/views/dashboard.blade.php`, `libros/index.blade.php`, `autores/index.blade.php`, `licencias/index.blade.php`, auth and settings screens, `welcome.blade.php`.
- No PRODUCT.md or DESIGN.md existed before this run.

## Product Principles

- Keep the product's data and workflows intact; redesign only presentation and interaction language.
- A user should be able to scan state, act, and return to the task without decorative detours.
- One visual vocabulary across dashboard, tables, forms, dialogs, auth and settings.
- Dark surfaces are the only surfaces; contrast and keyboard focus are non-negotiable.

## Accessibility & Inclusion

Accessible contrast (body and placeholder ≥4.5:1, large text ≥3:1), visible keyboard focus and responsive layouts are explicitly required by the brief.
