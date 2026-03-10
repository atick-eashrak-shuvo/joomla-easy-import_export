# Easy Import/Export for Joomla

**Version:** 1.0.0
**Author:** Atick Eashrak Shuvo
**License:** GNU General Public License v2 or later
**Compatibility:** Joomla 4.0+, Joomla 5.x, Joomla 6.x (PHP 8.1+)

A powerful Joomla administrator component that lets you import and export modules, menus, articles, categories, and users with their full configurations using portable JSON files.

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

### Articles Import/Export

- Export selected articles with their associated categories
- Export all articles, filter by category, or export categories only
- Preserves full article data including introtext, fulltext, images, URLs, metadata, featured flag, and custom fields
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
- **Bootstrap 5 modals** for import dialogs
- **Dark mode support** — fully adapts to Joomla's admin dark theme using CSS custom properties

### Cross-Version Compatibility (Joomla 4 / 5 / 6)

- Uses Joomla's modern MVC architecture with namespaced classes and service providers
- `checked_out` column handling compatible with both J4 (`NOT NULL DEFAULT 0`) and J5+ (`DEFAULT NULL`)
- `authProvider` user column detected dynamically — works on J4 (where it doesn't exist) and J5+ (where it does)
- Proper CSRF token validation using redirect instead of `die()`
- `DisplayController::display()` uses `static` return type compatible with both J4 and J5+ signatures
- `Uri::root()` explicitly cast to string for consistent JSON serialization
- Requires PHP 8.1+ (covers Joomla 4.4+, all Joomla 5.x, and Joomla 6.x)

---

## Installation

1. Download `com_easyimportexport_v1.0.0.zip`
2. In the Joomla administrator, go to **System → Install → Extensions**
3. Upload the ZIP file and install
4. Access the component from the admin sidebar: **Components → Easy Import/Export**

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

Export files are fully portable between Joomla sites. The importer handles ID conflicts, missing dependencies, and schema differences automatically.

---

## Requirements

| Requirement | Minimum |
|-------------|---------|
| Joomla | 4.0.0 |
| PHP | 8.1 |
| MySQL | 5.7+ / MariaDB 10.3+ |

---

## Changelog

### 1.0.0 (March 2026)

- Initial release
- Module import/export with SP Page Builder support
- Menu import/export with hierarchy preservation
- Article and category import/export
- User import/export with group and profile data
- Dark mode support
- Joomla 4, 5, and 6 compatibility
