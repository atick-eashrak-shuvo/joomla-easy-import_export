# Easy Import/Export for Joomla

> A Joomla administrator component that lets you reliably import and export modules, menus, articles, categories, and users between Joomla sites (J4 / J5 / J6).

---

## ✨ Overview

Migrating or cloning Joomla sites is usually painful:

- Re-creating modules manually, one by one
- Re-building menu structures and fixing broken links
- Re-adding articles and categories or exporting raw SQL from phpMyAdmin
- Moving users without preserving their groups and profiles
- SP Page Builder modules losing their content because it lives in a separate table

**Easy Import/Export** solves this by giving you a single, friendly UI to export data as clean JSON files and import them on another site — with relationships and configuration preserved.

---

## 🚀 Features

### Modules

- Export **selected modules** or **all modules** (site / admin / both)
- Preserves:
  - Module params
  - Positions & ordering
  - Access level & language
  - Menu assignments (`#__modules_menu`)
- **SP Page Builder support**:
  - Detects `mod_sppagebuilder` modules
  - Exports related rows from `#__sppagebuilder`
  - Re-links them to the new module IDs on import
- Import with optional **overwrite** (by ID or by title+type)
- Skips missing module types with clear warnings

### Menus

- Export:
  - Selected menu items
  - Entire menu types
  - All site menus
- Preserves:
  - Menu types (`#__menu_types`)
  - Full menu item hierarchy (parent/child, level, lft/rgt)
  - Links, types, params, access, language, home flag
- Automatically creates missing menu types on import
- Import with optional overwrite (by ID or alias+menutype)

### Articles & Categories

- Export:
  - Selected articles (with their categories)
  - Selected categories only
  - All articles
  - All categories
- Preserves:
  - `introtext` / `fulltext`
  - Metadata, images, URLs, featured status
  - Category hierarchy and extension (`com_content`)
- Import:
  - Auto-creates/matches categories (by alias+extension)
  - Optionally overwrites existing articles/categories
  - Maintains article → category mapping (with ID remapping)

### Users

- Export:
  - Selected users
  - All users
  - All users from a specific group
- Preserves:
  - Name, username, email, block state
  - Password hashes (users can log in on the new site)
  - User groups (`#__user_usergroup_map`)
  - Profiles (`#__user_profiles`)
  - User group definitions (`#__usergroups`)
- Import:
  - Recreates/matches user groups first
  - Re-assigns users to groups
  - Restores profile data
  - Optional overwrite (by ID / username / email)
  - Duplicate-safe when overwrite is disabled

### UI / UX

- Single **tabbed interface**:
  - Modules / Menus / Articles / Users
- Stats cards for quick overview (totals, published, featured, etc.)
- Search & filter for each data type
- Bulk selection with select-all checkboxes
- Drag-and-drop JSON upload for imports
- Clean Bootstrap 5 modals
- **Dark mode aware** (honors Joomla admin dark mode using CSS variables)

---

## ✅ Compatibility

- **Joomla**: 4.0+, 5.x, 6.x
- **PHP**: 8.1+
- Handles schema differences between Joomla versions, e.g.:
  - `checked_out` being `NOT NULL` in J4
  - `authProvider` column existing only in J5+ `#__users`

---

## 📦 Installation

1. Download the release ZIP: `com_easyimportexport_v1.0.0.zip`
2. In the Joomla administrator, go to **System → Install → Extensions**
3. Upload the ZIP file and install
4. Open the component via **Components → Easy Import/Export**

---

## 🧑‍💻 Usage

1. Go to **Components → Easy Import/Export**
2. Choose a tab:
   - **Modules**, **Menus**, **Articles**, or **Users**
3. Use the filters/search to narrow down items
4. Select what you want to export
5. Click **Export Selected** or choose one of the **Export All** options
6. On the target site, go to the same tab and use the **Import** button:
   - Drag-and-drop your JSON file
   - Choose whether to **overwrite** existing items
   - Start the import and review any warnings

---

## 📄 Export Format (JSON)

Each export file looks like:

json
{
"meta": {
"format_version": "1.0",
"type": "modules|menus|articles|categories|users",
"export_date": "2026-03-10 12:00:00",
"joomla_version": "5.x.x",
"site_name": "My Joomla Site",
"site_url": "https://example.com/",
"item_count": 42
},
"modules|menu_types|menu_items|categories|articles|users": [
// ...
]
}

The importer uses `meta.type` and `meta.format_version` to route the data to the correct logic and handle future upgrades safely.

---

## 🛠 Development

- Namespaced component using Joomla’s modern MVC stack
- Service provider in `services/provider.php`
- Extension class: `Joomla\Component\Easyimportexport\Administrator\Extension\EasyimportexportComponent`
- Models:
  - `ModulesModel`
  - `MenusModel`
  - `ArticlesModel`
  - `UsersModel`
- Controllers:
  - Import/export controllers for each data type
  - CSRF-safe with token checks and redirect-based error handling

---

## 📜 License

This project is licensed under the **GNU General Public License v2.0 or later (GPL‑2.0-or-later)** — the same family license as Joomla itself.

You are free to:

- Use it on any number of Joomla sites
- Study and modify the code
- Redistribute it, as long as your derivative works remain GPL‑compatible

See the `LICENSE` file for full details.

---

## 💬 Feedback & Contributions

Issues, ideas, and pull requests are welcome.

If this extension saves you time on your next migration, consider:

- Starring the repository on GitHub ⭐
- Sharing it with other Joomla developers
- Posting feedback or feature requests in the Issues section

