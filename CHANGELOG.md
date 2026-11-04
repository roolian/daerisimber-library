# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.5] - 2026-11-04

### Fixed
- PHP 8.4+ deprecation on `BlockModel::render()` implicitly nullable `$wp_block`
- `BlockModel::render()` no longer fails when ACF passes `false` as block context

## [1.2.4] - 2026-11-04

### Added
- Mobile menu in base header layout
- `site.blocks_blacklist` config to remove block types from the editor
- `site.themes_remove_supports` config to remove theme supports
- `site.should_load_remote_block_patterns` config
- `is_thumbnail` flag in block context when rendering block previews

### Changed
- BlockModel throws an exception when block.json is unreadable or invalid

## [1.2.2]

### Added
Add dedicated admin js file
Read dist var from vite.json

## [1.2.0] - 2026-01-05

### Changed
- Updated to support iframe editor with apiVersion 3 for Gutenberg blocks

## [1.1.6] - 2026-01-05

### Added
- Initial library release for Daerisimber theme
- Timber 2.2+ integration
- PSR-4 autoload for `Daerisimber\` namespace
- Phlak Config 8.0 for configuration management
- Services architecture (Assets, Blocks, etc.)
- Base models and helpers for theme development
- Symfony Console 6.4 for CLI tools
- Timber Dump Extension for debugging
