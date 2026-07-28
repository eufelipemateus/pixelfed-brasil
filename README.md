<p align="center">
<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://pixelfed.nyc3.cdn.digitaloceanspaces.com/logos/pixelfed-full-color-dark.svg">
  <source media="(prefers-color-scheme: light)" srcset="https://pixelfed.nyc3.cdn.digitaloceanspaces.com/logos/pixelfed-full-color.svg">
  <img alt="Pixelfed logo" src="https://pixelfed.nyc3.cdn.digitaloceanspaces.com/logos/pixelfed-full-color.svg">
</picture>
</p>

<p align="center">
<a href="https://packagist.org/packages/pixelfed/pixelfed"><img src="https://poser.pugx.org/pixelfed/pixelfed/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/pixelfed/pixelfed"><img src="https://poser.pugx.org/pixelfed/pixelfed/license.svg" alt="License"></a>
<a title="Crowdin" target="_blank" href="https://crowdin.com/project/pixelfed"><img src="https://badges.crowdin.net/pixelfed/localized.svg"></a>
</p>

<p align="center">
<a href="https://fedidb.org/software/pixelfed"><img src="https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fapi.fedidb.org%2Fv1%2Fsoftware%2Fpixelfed&query=%24.user_count&logo=pixelfed&logoColor=white&label=Total%20Users" alt="Total Pixelfed users from FediDB" /></a>
</p>

# Pixelfed Branch Workflow

This repository follows a branch flow to keep upstream updates, integration, development, and production separated and stable.

## Branch Roles

- `dev-contrib-origin`: Always synchronized with upstream Pixelfed. No local custom changes should be committed here.
- `update-dev`: Intermediate integration branch. It receives `dev` and merges with `dev-contrib-origin`, resolving possible conflicts.
- `dev`: Development branch associated with https://pixelfed.dev.br.
- `main`: Production branch used to publish https://pixelfed.com.br.
- `feat/<feature-name>`: Branch for local updates that do not come from upstream. It must be merged into `dev`.
- `hotfix/<name>`: Branch for urgent production fixes. Direct merge to `main` is allowed only for this case.

## Pixelfed Update Flow

```mermaid
flowchart LR
  A[dev-contrib-origin\nUpstream mirror\nNo local changes] --> B[update-dev\nIntermediate merge\nConflict resolution]
  C[dev\nEnvironment: pixelfed.dev.br] --> B
  B --> C
  C --> E[main\nProduction: pixelfed.com.br]
  E --> F[feat/<feature-name>\nLocal custom updates]
  F --> C
  E --> H[hotfix/<name>\nCritical production fix]
  H --> E
```

## Recommended Update Process

1. Update `dev-contrib-origin` from upstream Pixelfed.
2. Merge `dev` into `update-dev`.
3. Merge `dev-contrib-origin` into `update-dev`.
4. Resolve conflicts in `update-dev` and validate the application.
5. Merge `update-dev` back into `dev`.
6. After validation on `dev` (`pixelfed.dev.br`), promote changes to `main` (`pixelfed.com.br`).

## Local Changes and Merge Policy

1. Every new local change starts from `main`, using either `feat/<feature-name>` or `hotfix/<name>`.
2. Any local update that does not come from upstream must be developed in a `feat/<feature-name>` branch and merged into `dev`.
3. Direct merges into `main` are not allowed during normal development.
4. Direct merges into `main` are allowed only for urgent `hotfix/<name>` branches that fix problematic production updates.

## Quick Links

- Stable (production): https://github.com/eufelipemateus/pixelfed/tree/main
- Development: https://github.com/eufelipemateus/pixelfed/tree/dev
