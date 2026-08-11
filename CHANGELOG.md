# Changelog

All notable changes to local_themerules are documented here.

## v0.1.0 — First public release (2026-08-11)

Give every learner the right look, automatically — a different theme and logo per
client company, a distraction-free skin during exams, a lighter theme on phones —
all from admin-configured rules, no code and no per-course setup.

- **Rule engine.** Ordered, enable/disable-able rules, each pairing a logical
  condition tree (AND/OR groups, nested up to 10 levels) with an action (a
  theme, a logo, or both). The first enabled matching rule wins; no match
  falls through to Moodle's normal theme selection unchanged.
- **Conditions.** User, course, course category (with optional subcategories),
  cohort, course group, course tag, device type (desktop/mobile/tablet/legacy),
  and standard or custom profile fields.
- **Visual rule builder.** A JS condition editor with entity-search pickers for
  user/course/group - no hand-written JSON required, though the underlying
  expression is still plain JSON for anyone who wants it.
- **Simulator.** Check what a given user/course/device combination would
  resolve to, with a full per-condition trace, without logging in as anyone.
- **Import/export.** Move a rule set between sites as a single JSON file;
  imported rules always arrive disabled for a deliberate review first.
- **In-app quick reference** (`help.php`) plus a full bilingual (EN/ES)
  illustrated user guide, published on GitHub Pages.
- **Engineering.** Caching-aware resolution, Moodle event logging, a privacy
  API provider, PHPUnit and Behat coverage, and a GitHub Actions
  `moodle-plugin-ci` workflow (PHP 8.1–8.4, Moodle 4.5–5.2, Postgres/MariaDB).
