# Easy Import/Export for Joomla

**Version:** 2.1.0
**Author:** Atick Eashrak Shuvo
**License:** GNU General Public License v3 or later
**Compatibility:** Joomla 3.4+, 4.x, 5.x, 6.x

A powerful Joomla administrator component that lets you import and export modules, menus, articles, categories, and users with their full configurations using portable JSON files. **Export from any Joomla version and import into any other.**

---

## Two Packages, One Format

| Package | Joomla Version | PHP Version | File |
|---------|---------------|-------------|------|
| **J4/5/6 Edition** | 4.0+ / 5.x / 6.x | PHP 8.1+ | `com_easyimportexport_v2.1.0.zip` |
| **J3 Edition** | 3.4 – 3.10 | PHP 5.6+ | `com_easyimportexport_j3_v2.1.0.zip` |

Both packages produce the **same JSON export format**, enabling seamless cross-version migration:

- Export from Joomla 3 → Import into Joomla 5 or 6
- Export from Joomla 5 → Import into Joomla 3
- Export from Joomla 4 → Import into Joomla 6
- Any combination works

---

## Features

### Modules Import/Export

- Export selected modules or all modules at once
- Filter exports by client (Site, Administrator, or both)
- Preserves full module configuration including params, position, ordering, access levels, and language
- Preserves menu assignment mappings (which pages each module appears on)
- **SP Page Builder support** — automatically includes SP Page Builder content stored in the `#__sppagebuilder` table for `mod_sppagebuilder` modules
- Import with optional overwrite — matches existing modules by ID or title+type
- Validates that module types are installed on the target site before importing
- Skips unrecognized module types with clear warning messages

### Menus Import/Export

- Export selected menu items or export by menu type
- Preserves full menu item structure including parent-child hierarchy, link, type, component ID, params, and access levels
- Automatically creates missing menu types on the target site during import
- Maintains parent-child relationships with ID remapping during import
- Import with optional overwrite — matches by ID or alias+menutype
- Supports all menu item types (component, URL, alias, separator, heading)
- **Component validation** — verifies that required components are installed on the target site before importing component-type menu items; skips missing ones with clear warnings
- Non-component menu items (URL, alias, separator, heading) are always imported safely

### Articles Import/Export

- Export selected articles with their associated categories
- Export all articles, filter by category, or export categories only
- Preserves full article data including introtext, fulltext, images, URLs, metadata, featured flag, and custom fields
- **ZIP-based media export/import** — article exports produce a `.zip` containing a clean `data.json` plus a `media/` folder with raw image files (intro, full, inline images, CSS backgrounds)
- Import accepts both `.zip` (recommended) and legacy `.json` formats
- Supports JPG, PNG, GIF, WebP, SVG, BMP, ICO (up to 10 MB per file)
- Automatically creates or maps categories during article import
- Category import preserves hierarchy (parent-child, level, path)
- Import with optional overwrite — matches articles by ID or alias, categories by alias+extension
- Separate category-only export/import workflow

### Users Import/Export

- Export selected users or all users, optionally filtered by user group
- Preserves user data including name, username, email, password hash, registration date, last visit, and block status
- Exports and restores user group assignments
- Exports and restores user profile data (`#__user_profiles`)
- Exports user group definitions to ensure groups exist on the target site
- Import with optional overwrite — matches by ID, username, or email
- Duplicate detection — skips users with existing username or email when overwrite is disabled
- Password hashes are preserved, so users can log in with their existing passwords on the new site

### User Interface

- **Tabbed interface** with four tabs: Modules, Menus, Articles, Users
- **Statistics cards** at the top of each tab showing totals and key metrics
- **Search and filter** options for every data type
- **Bulk selection** with select-all checkboxes
- **Drag & drop file upload** for imports with file size display
- **Dark mode support** (J4/5/6 version) — fully adapts to Joomla's admin dark theme using CSS custom properties

### Cross-Version Import/Export

The `filterColumns()` system in both editions automatically handles schema differences between Joomla versions:

- Columns that exist in the export but not in the target database table are silently skipped
- Columns like `checked_out` (NOT NULL in J4, nullable in J5+) are handled correctly
- The `authProvider` column (J5+ only) is included when available, ignored when not
- J3 exports with different column sets import cleanly into J4/5/6 and vice versa

---

## Installation

### Joomla 4 / 5 / 6

1. Download `com_easyimportexport_v2.1.0.zip`
2. In the Joomla administrator, go to **System → Install → Extensions**
3. Upload the ZIP file and install
4. Access the component from the admin sidebar: **Components → Easy Import/Export**

### Joomla 3

1. Download `com_easyimportexport_j3_v2.1.0.zip`
2. In the Joomla administrator, go to **Extensions → Extension Manager → Upload Package File**
3. Upload the ZIP file and install
4. Access the component from the admin menu: **Components → Easy Import/Export**

---

## Cross-Version Migration Guide

### Example: Migrate from Joomla 3 to Joomla 5

1. Install the **J3 Edition** on your Joomla 3 site
2. Export modules, menus, articles, and users as JSON files
3. Install the **J4/5/6 Edition** on your Joomla 5 site
4. Import each JSON file — the component automatically handles schema differences

### Example: Migrate from Joomla 5 to Joomla 6

1. Export from Joomla 5 using the J4/5/6 Edition
2. Import into Joomla 6 using the same J4/5/6 Edition

---

## Export Format

All exports use JSON format with the following structure:

```json
{
  "meta": {
    "format_version": "1.x",
    "type": "modules|menus|articles|categories|users",
    "export_date": "2026-03-10 12:00:00",
    "joomla_version": "5.x.x",
    "site_name": "My Joomla Site",
    "site_url": "https://example.com/",
    "item_count": 42
  },
  "modules|menu_types|categories|articles|users": [...]
}
```

Export files are fully portable between any Joomla version (3, 4, 5, 6). The importer handles ID conflicts, missing dependencies, and schema differences automatically.

---

## Requirements

| Edition | Joomla | PHP | MySQL |
|---------|--------|-----|-------|
| J4/5/6 | 4.0.0+ | 8.1+ | 5.7+ / MariaDB 10.3+ |
| J3 | 3.4.0+ | 5.6+ | 5.5+ / MariaDB 10.0+ |

---

## Changelog

### 2.1.0 (March 2026)

- Article exports now include its medias
- Import accepts both ZIP (recommended) and legacy JSON formats
- Much smaller export files and lower memory usage
- Both J3 and J4/5/6 editions include ZIP media support

### 2.0.0 (March 2026)

- Menu import now validates that required components are installed on the target site
- Component-type menu items pointing to missing extensions are skipped with warnings
- Non-component menu items (URL, alias, separator, heading) always import safely
- Both J3 and J4/5/6 editions include all validation improvements

### 1.0.0 (March 2026)

- Initial release
- Module import/export with SP Page Builder support
- Menu import/export with hierarchy preservation
- Article and category import/export
- User import/export with group and profile data
- Dark mode support (J4/5/6)
- Joomla 3, 4, 5, and 6 compatibility
- Cross-version JSON format for migrating between any Joomla version
