# local_themerules

A Moodle local plugin that dynamically selects the theme and/or navbar logo
shown to a user according to configurable logical rules (`condition
expression -> theme and/or logo`), evaluated server-side before Moodle
renders the page.

## Purpose

Administrators can define rules such as:

```
(course category = "FUNDAE")
AND
(
    user belongs to cohort "Company A"
    OR
    user belongs to cohort "Company B"
)
-> theme_cigales
```

through an admin UI, without writing code. If no rule matches, Moodle's
normal theme selection applies exactly as if the plugin were not
installed.

See `SPECIFICATIONS.md` for the full functional specification this plugin
was built against, and `DECISIONS.md` for the technical decisions made
while building it (in particular, why the theme is applied via
`$SESSION->theme` rather than `$PAGE->force_theme()`, and why a two-tier
hook/callback architecture is needed for course-based conditions).

## Requirements

- Moodle 4.5 LTS or later (developed and tested against 5.2.1+).
- A Boost-compatible theme for any theme a rule targets.
- No additional PHP extensions or third-party dependencies.

## Installation

Standard Moodle plugin installation:

1. Copy this directory to `<moodle>/local/themerules`.
2. Visit *Site administration > Notifications* (or run
   `php admin/cli/upgrade.php`) to complete the install.
3. Installing the plugin changes nothing by default: with zero rules
   defined, Moodle's theme selection is completely unaffected.

## Basic usage

1. Go to *Site administration > Appearance > Theme rules*.
2. Click **Create rule**.
3. Give it a name, choose the theme and/or logo to apply, and enter the
   condition expression. Theme and logo are independent - a rule can set
   either, both, or (leaving both blank) will be rejected as doing
   nothing.
4. Tick **Enabled** and save.

A new rule is always added at the bottom of the list. Evaluation order is
the list order itself, top to bottom - use the **↑**/**↓** icons in the
rule list to reorder rules; the first matching rule wins, exactly like
Moodle's own "Manage authentication"-style admin lists. There is no
separate numeric priority to set.

Theme and logo resolve independently across the whole rule set, not just
within one rule: if an earlier rule already set the theme, a later rule
can still supply the logo (or vice versa) without overriding what the
earlier rule already claimed. This is what lets several rules share one
theme while only varying the logo, without repeating the theme choice in
each of them - see the examples below.

### Logo library

Logos are managed separately from rules, at *Theme rules > Logos*: upload
an image once, give it a name, and it becomes selectable from any rule's
**Apply logo** dropdown. Uploading the same logo again under a different
rule reuses the same asset rather than duplicating the file. Only the
navbar/compact logo is affected (the one shown on most pages); the full
site logo used on the login page is Moodle's own single site-wide setting
and is not touched by this plugin.

The condition expression is entered as JSON in this first release; a
visual builder (`amd/src/rule_editor.js`) progressively enhances the same
field with add/remove condition and group controls, AND/OR toggles, so in
practice most administrators never need to type JSON by hand - but the
textarea is always the real data channel, and still works if JavaScript is
unavailable. `user`/`course` values use a type-ahead search picker instead
of a raw id (type a name/email or a course name to search); `cohort`/
`coursecategory` render as a real dropdown of everything this site actually
has; `coursegroup` is a two-step picker - choose a course first, then a
group within it, since a group id alone isn't identifiable without knowing
its course.

Use **Simulate** (same admin page) to check, for a given user id and
course id, exactly which rules match, why, and which theme *and* logo
would be selected - without affecting anyone's actual session. Leave the
user id at `0` (or blank) to simulate an anonymous, not-logged-in visitor
- that is genuinely what a real visitor's user id is at the point Tier A
resolution runs, so `user is 0` in a condition already means "anonymous
visitor" today, not a broken reference. It also lets you override the
device type being simulated (auto-detected from your own browser by
default), so you can test a `device` rule as "what if this were a tablet"
without needing an actual tablet.

### Export / import

**Export** (available to anyone with `local/themerules:view`) downloads
every rule on this site as a single JSON file:
`{"format": 1, "rules": [...]}`. **Import** (`local/themerules:manage`)
uploads that same kind of file back - useful for backups, or copying a
rule set between environments that share the same user/course/cohort/
group/logo ids (e.g. staging and production kept in sync). Imported rules
are always added as new rules, appended at the end of the evaluation order
and **disabled by default**, same as duplicating a rule - so importing a
large rule set never silently starts affecting live traffic before you've
reviewed it. Import is best-effort per rule: one malformed entry in an
otherwise-good file is skipped and reported, not fatal to the rest: every
rule goes through the exact same validation a hand-typed rule would. A
`theme`/`logoid`/condition value (user, course, cohort, group id) that
doesn't exist on the importing site is not rejected at import time - it
imports fine and is simply skipped safely at evaluation time, the same as
any other rule pointing at a deleted entity (see Troubleshooting below).

### Action types

| Type | Value | Notes |
|---|---|---|
| `theme` | A theme's directory name, e.g. `"boost"`. | Skipped safely (falls through to the next matching rule) if the theme has since been uninstalled. |
| `logo` | A logo asset id from the logo library (`logoid`). | Skipped safely if the logo has since been deleted. Applied via a small CSS override injected into every page's `<head>`, not by changing any Moodle-wide setting - see DECISIONS.md for why (the site logo has no per-request resolution mechanism to hook into, unlike the theme). |

### Condition identifiers

| Identifier | Operators | Notes |
|---|---|---|
| `user` | `is` | Value: a user id. `0` matches an anonymous, not-logged-in visitor (that is genuinely what their user id is at Tier A), not a broken reference. |
| `course` | `is` | Value: a course id. |
| `coursecategory` | `in_category` | Value: a category id. Optional `"includechildren": true` to also match descendant categories. |
| `cohort` | `member`, `not_member` | Value: a cohort id. |
| `coursegroup` | `member`, `not_member` | Value: a course group id, or `0` to mean "any group in this course" (mirrors Moodle's own "Restrict access > Group" condition). Only resolvable on a real course, same constraint as `course`/`coursecategory`/`coursetag`. |
| `device` | `is`, `is_not` | Value: one of `default`, `mobile`, `tablet`, `legacy` (matches `\core_useragent::DEVICETYPE_*`, including the user's own "view full site" override). Unlike the other conditions this is always resolvable, even before login. |
| `coursetag` | `has`, `not_has` | Value: a tag name, e.g. `"exam-mode"`. Matched case-insensitively with leading/trailing whitespace trimmed (the same normalization Moodle itself uses for tags) - `"Exam-Mode"` and `"exam-mode "` match the same tag, but a space and a hyphen are still different characters (`"Exam Mode"` ≠ `"exam-mode"`). Only resolvable on a real course, same constraint as `course`/`coursecategory`. |
| `profilefield` | `is`, `is_not` | `"field"`: a standard field shortname (`firstname`, `lastname`, `email`, `city`, `country`, `idnumber`, `institution`, `department`, `phone1`, `phone2`, `address` - the same list Moodle's own "Restrict access > User profile field" uses) or a custom profile field's shortname with `"customfield": true`. Value: a string, matched with exact case-sensitive equality (also matching Moodle's own `availability_profile` behaviour). The visual editor offers every field this site actually has (including real custom fields) as a dropdown, so this is rarely typed by hand. |

Group nodes use `"operator": "and"` or `"operator": "or"` with a
`"children"` array of further condition/group nodes, nested up to 10
levels deep, up to 100 nodes per rule.

## Rule examples

Simple: a specific user always gets `boost`, regardless of anything else:

```json
{"type": "condition", "condition": "user", "operator": "is", "value": 123}
```

Mobile visitors get a lighter theme, everyone else keeps the site default:

```json
{"type": "condition", "condition": "device", "operator": "is", "value": "mobile"}
```

Any course tagged "exam-mode" gets a distraction-free theme:

```json
{"type": "condition", "condition": "coursetag", "operator": "has", "value": "exam-mode"}
```

Members of course group 5 get their own branding; `0` would instead mean "any
group in this course":

```json
{"type": "condition", "condition": "coursegroup", "operator": "member", "value": 5}
```

Everyone whose institution profile field is "UTAD" gets that institution's
branding - the multi-tenant scenario this plugin's logo action was
originally built for, now reachable from a condition too:

```json
{"type": "condition", "condition": "profilefield", "operator": "is", "field": "institution", "value": "UTAD"}
```

The same idea with a custom profile field (`"customfield": true`, value
still a plain string):

```json
{"type": "condition", "condition": "profilefield", "operator": "is", "field": "employeeid", "customfield": true, "value": "99887"}
```

Cohort "Company A" gets both a theme and a matching logo from one rule.
The condition expression is unchanged from the earlier examples; what is
new here is the *action*, which the admin UI builds automatically from the
**Apply theme**/**Apply logo** fields as a list rather than a single value,
so a rule can set more than one axis:

Condition expression:

```json
{"type": "condition", "condition": "cohort", "operator": "member", "value": 7}
```

Action:

```json
[
  {"type": "theme", "theme": "boost"},
  {"type": "logo", "logoid": 3}
]
```

A rule only setting a logo (leaving whatever theme an earlier rule already
chose untouched) uses a single-element action list:
`[{"type": "logo", "logoid": 3}]`.

Course + cohort combination:

```json
{
  "type": "group",
  "operator": "and",
  "children": [
    {"type": "condition", "condition": "course", "operator": "is", "value": 5},
    {"type": "condition", "condition": "cohort", "operator": "member", "value": 7}
  ]
}
```

The plugin's canonical example - a category (including its subcategories)
combined with an OR of two cohorts:

```json
{
  "type": "group",
  "operator": "and",
  "children": [
    {
      "type": "condition",
      "condition": "coursecategory",
      "operator": "in_category",
      "value": 12,
      "includechildren": true
    },
    {
      "type": "group",
      "operator": "or",
      "children": [
        {"type": "condition", "condition": "cohort", "operator": "member", "value": 7},
        {"type": "condition", "condition": "cohort", "operator": "member", "value": 8}
      ]
    }
  ]
}
```

## Troubleshooting

- **A rule doesn't seem to apply.** Use the Simulator with that user's id
  (and course id, if the rule uses `course`/`coursecategory`) to see
  exactly which condition failed. Remember rules are evaluated top to
  bottom (the rule list's order *is* the evaluation order) and the *first*
  match wins - a rule higher up the list may be matching instead of the
  one you expected. Move it down (or move yours up) with the **↑**/**↓**
  icons.
- **A `course`/`coursecategory` condition never matches anywhere except on
  a course page.** This is expected outside a real course context (site
  front page, dashboard, admin pages): those facts are only resolved once
  Moodle knows the real course being viewed. See DECISIONS.md "Phase 2"
  for the underlying Moodle lifecycle constraint.
- **I changed a rule but the old theme is still showing.** The enabled-rule
  list is cached (MUC, area `rules`). The plugin invalidates it
  automatically on every save/enable/disable/reorder/delete through the admin UI;
  if a rule was changed by some other means (direct DB write, a script),
  run *Site administration > Development > Purge caches*.
- **A `profilefield` condition never matches for a logged-out visitor.**
  Expected: an anonymous visitor (`user` id `0`, see above) has no profile
  field values by definition, so `is` never matches for them. `is_not`
  always matches for them instead - a profile-field `is_not` rule will
  therefore also catch every anonymous visitor, not only real users with
  a genuinely different value. Combine it with a `device`/`user` condition
  if that's not the intent.
- **A rule points at a theme that no longer exists.** It is skipped safely
  (logged via `debugging()` when developer debugging is on) and the
  resolver moves on to the next rule; it does not break the page. The same
  applies to a rule pointing at a deleted logo.
- **The logo doesn't change even though the rule matches.** Only the
  navbar/compact logo is affected - if your theme renders it as something
  other than an `<img class="logo">` (most Boost-family themes do; a
  heavily customised theme might not), the CSS override this plugin
  injects has nothing to target. Check the page source for a
  `<style>img.logo { content: url(...) }</style>` block in `<head>`; if
  it's missing, the rule didn't match; if it's present but nothing
  changes, the theme's own markup is the mismatch.
- **Nothing changes even with rules enabled.** Check the plugin is enabled
  and that `local/themerules:view`/`:manage` capabilities are granted to
  the account trying to administer rules (Site administrator and the
  `manager` role archetype have them by default).

## Development / testing

```bash
# PHPUnit (from the Moodle root, after admin/tool/phpunit/cli/init.php)
vendor/bin/phpunit --testsuite=local_themerules_testsuite

# Behat (after admin/tool/behat/cli/init.php / util.php --enable)
vendor/bin/behat --config <behat dataroot>/behatrun/behat/behat.yml \
  --profile=chrome --tags=@local_themerules

# JS build (AMD)
node_modules/.bin/grunt amd --root=local/themerules

# Coding standard
vendor/bin/phpcs --standard=moodle local/themerules
```

See `DECISIONS.md` for what has actually been run and verified at each
development phase, including live (non-PHPUnit) verification against a
real Moodle instance for every phase.
