# Changelog

All notable changes to **Easy Import/Export for Joomla** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.0.0] - 2026-03-09

### Added

- **Menu import component validation** — Menu items of type `component` are now validated against installed extensions on the target site before importing. If the required component (e.g., `com_virtuemart`, `com_k2`) is not installed, the menu item is skipped with a clear warning message. Non-component menu items (URL, alias, separator, heading) are always imported normally.
- **Module import type validation** — Module imports now verify that the module type (e.g., `mod_custom`, `mod_menu`) is installed on the target site. Missing module types are skipped with a descriptive warning.

### Improved

- Enhanced import safety for cross-site migrations where source and target sites have different extensions installed
- Better warning messages during import clearly identify which items were skipped and why
- Both J3 and J4/5/6 editions include all validation improvements

---

## [1.0.0] - 2026-03-08

### Added

- **Module import/export** with full configuration preservation (params, position, ordering, access levels, language, menu assignments)
- **SP Page Builder module support** — automatically exports/imports SP Page Builder content from `#__sppagebuilder` table
- **Menu import/export** with parent-child hierarchy preservation, ID remapping, and automatic menu type creation
- **Article and category import/export** with full content data, metadata, images, URLs, featured flag, and custom fields
- **Category hierarchy preservation** during import (parent-child, level, path)
- **User import/export** with group assignments, profile data (`#__user_profiles`), and password hash preservation
- **Cross-version JSON format** — export from any Joomla version (3, 4, 5, 6) and import into any other
- **Dynamic column filtering** (`filterColumns()`) to handle database schema differences between Joomla versions automatically
- **Asset management** — creates proper `#__assets` entries for imported articles and categories (J4/5/6) so they appear in the native Joomla content manager
- **Workflow association** — automatically associates imported articles with the default workflow stage (J4/5/6)
- **Dark mode support** (J4/5/6 edition) — fully adapts to Joomla's admin dark theme
- **Tabbed UI** with four tabs: Modules, Menus, Articles, Users
- **Statistics cards** showing totals and key metrics per tab
- **Search and filter** for every data type
- **Bulk selection** with select-all checkboxes
- **Drag & drop file upload** for imports with file size display
- **Overwrite option** for imports — update existing items or skip duplicates
- Joomla 3.4+, 4.x, 5.x, and 6.x compatibility
- Dual package architecture: J3 Edition (legacy MVC) and J4/5/6 Edition (namespaced modern MVC)
