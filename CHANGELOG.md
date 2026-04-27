# Changelog

All notable changes to this project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-04-27

### Added
- Initial release.
- Override category permastruct to remove the `/category/` base from generated permalinks.
- Custom `category_rewrite_rules` filter emitting root, paged, and feed rules per category, with nested-category support.
- 301 redirect from legacy `/category/<slug>/` URLs to the new bare-slug form via a `category_redirect` query var.
- Automatic rewrite-rule flush on activation, deactivation, and category create/edit/delete.
