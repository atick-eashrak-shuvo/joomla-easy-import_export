# LinkedIn Post — Easy Import/Export for Joomla

---

**Copy-paste ready post:**

---

Ever tried migrating a Joomla site and realized there's no clean way to move your modules, menus, articles, and users between sites?

I've been there. And it's painful.

Here's what the process usually looks like:

- Manually recreating 50+ modules on a new site, one by one
- Copy-pasting menu structures and hoping the links don't break
- Re-adding articles manually one by one, or exporting through phpMyAdmin and dealing with broken category mappings
- Moving users across sites with no way to preserve their group assignments or profiles

None of the existing tools handle all of this in one place. Some cover articles. Some cover modules. None of them cover menus or users. And almost none support SP Page Builder module content.

So I built one.

**Introducing Easy Import/Export** — a free Joomla extension that lets you import and export everything in one place:

**Modules** — Full config, menu assignments, positions, and SP Page Builder content included.

**Menus** — Menu types, menu items, parent-child hierarchy, all preserved.

**Articles & Categories** — Articles with their categories, metadata, images, and featured flags.

**Users** — User accounts, group assignments, profiles, and password hashes (so users can log in immediately on the new site).

Everything exports as a clean, portable JSON file. Import it on any other Joomla site and the extension handles ID conflicts, missing dependencies, and schema differences automatically.

It works on **Joomla 4, 5, and 6**.

The UI is simple — search, filter, select what you need, export. Drag and drop to import. Dark mode supported.

No more phpMyAdmin hacks. No more manual recreation. No more broken migrations.

If you work with Joomla, this will save you hours.

#Joomla #WebDevelopment #OpenSource #PHP #CMS #JoomlaDevelopment #Migration #WebDesign #FreeTool

---

**Shorter version (if you prefer concise):**

---

Migrating Joomla sites shouldn't mean manually recreating every module, menu, article, and user on the new site.

But that's exactly what most of us do. Because there's no single tool that handles all of it.

I built **Easy Import/Export** — a free Joomla extension that exports and imports:

- Modules (including SP Page Builder content)
- Menus (with full hierarchy)
- Articles & Categories
- Users (with groups, profiles, and passwords)

One JSON file. Drag and drop. Works on Joomla 4, 5, and 6.

No phpMyAdmin. No manual work. No broken migrations.

#Joomla #WebDevelopment #OpenSource #PHP #CMS

---

**Key talking points if someone asks in the comments:**

1. **Why JSON instead of SQL dumps?** — JSON is portable, human-readable, and doesn't break when table structures differ between Joomla versions.

2. **Does it preserve passwords?** — Yes. Password hashes are exported and imported as-is, so users can log in with their existing credentials on the new site.

3. **What about SP Page Builder?** — SP Page Builder stores module content in its own table, not in the Joomla modules table. Easy Import/Export detects this and includes the SP Page Builder data automatically.

4. **Cross-version support?** — The extension dynamically checks the database schema and adapts. Columns that exist in Joomla 5 but not in Joomla 4 (like `authProvider` in the users table) are handled gracefully.

5. **Is it free?** — Yes, released under the GNU GPL v2 license.
