# Denver Elks Lodge #17 — WordPress Theme

Custom WordPress theme for [denverelks.org](https://denverelks.org). Built by Overtime Agency.

- **Staging:** [elks.torreys.brighthosted.com](https://elks.torreys.brighthosted.com)
- **Repo:** [github.com/jasonmonster/denver17](https://github.com/jasonmonster/denver17)
- **Target launch:** Late July 2026
- **Design reference:** `WEBSITE BUILD/mockups/index.html`

---

## Deploy Workflow

Push to GitHub via SSH — GitHub Actions handles the rest.

```bash
# ~/.zshrc alias
deploy
```

The `deploy` alias runs `git add . && git commit -m "..." && git push`. The Actions workflow (`.github/workflows/deploy.yml`) SSHs into `elkstorreys@143.198.51.125` and pulls into `/sites/elks.torreys.brighthosted.com/files/wp-content/themes/denver17`.

**Do not use SpinupWP's built-in git integration.** It pulls into the site root and corrupts WP core files.

Local repo path: `/Users/jasonackerman/Dropbox (Personal)/Overtime Agency/BPOE 17/WEBSITE BUILD/denver17`

---

## Architecture Principles

- Strict separation of theme vs. plugin functions. The theme handles presentation only; event/ticketing logic lives in a separate plugin.
- Modular `inc/` structure — one concern per file.
- Design is locked. Customizer options cover globals (colors, contact info, social URLs) — not layout control.
- Custom Gutenberg blocks are the content management layer for all homepage sections. No ACF dependency. Content must be editable by non-developers from day one.
- Maintainability is the priority. This site will be handed off within ~2 years; content must be editable by non-developers.

---

## What's Built

| File | Notes |
|---|---|
| `functions.php` | Loader only — requires all `inc/` files |
| `inc/theme-setup.php` | Theme supports, nav menu registration |
| `inc/nav-walkers.php` | Desktop mega menu walker + mobile accordion walker |
| `inc/enqueue.php` | Enqueue `main.css` and `main.js`; `wp_localize_script` passes hours data to JS |
| `inc/theme-options.php` | Customizer: colors, contact info, social URLs, homepage images |
| `inc/template-functions.php` | Helper functions — `denver17_social_links()` outputs SVG icons |
| `inc/blocks.php` | Block registration, custom category, editor script enqueue |
| `inc/hours-feed.php` | Fetches lodge hours from Google Sheets, caches via WP transients (5 min TTL) |
| `inc/beer-feed.php` | Fetches tap list from Google Sheets, caches via WP transients (5 min TTL) |
| `header.php` | Nav with mega menu, social icons, Member Area CTA |
| `footer.php` | Footer with social links and footer nav |
| `front-page.php` | Homepage — renders via `the_content()` using custom blocks |
| `page.php` | Static page template |
| `index.php` | Minimal fallback loop |
| `template-parts/nav/mobile-menu.php` | Slide-in mobile drawer |
| `template-parts/home/hero.php` | Hero section — accepts `$args` from block render |
| `template-parts/home/hours-card.php` | Live-status hours card — data from Sheets via `wp_localize_script`, JS renders state |
| `template-parts/home/feature-split.php` | Reusable feature section — accepts `$args` |
| `template-parts/home/membership-steps.php` | 3-step funnel — accepts `$args` |
| `template-parts/home/events-band.php` | Events grid — static placeholder, accepts `$args` |
| `template-parts/home/cta-band.php` | Closing CTA — accepts `$args` |
| `blocks/hero/` | `block.json` + `render.php` |
| `blocks/feature-split/` | `block.json` + `render.php` |
| `blocks/membership-steps/` | `block.json` + `render.php` |
| `blocks/events-band/` | `block.json` + `render.php` |
| `blocks/cta-band/` | `block.json` + `render.php` |
| `blocks/hours-display/` | `block.json` + `render.php` — When & Where page; server-side status via `wp_timezone()` + `DateTime` |
| `blocks/beer-list/` | `block.json` + `render.php` — Jolly Corks Bar page; sidebar toggles for style, ABV, coming soon |
| `assets/css/main.css` | All front-end styles |
| `assets/css/editor-style.css` | Block editor canvas styles |
| `assets/js/main.js` | Mobile drawer, accordion toggles, hours card state rendering (reads `window.denver17Hours`) |
| `assets/js/blocks-editor.js` | Block editor UI — all 7 blocks, no build step, vanilla `wp.*` globals |
| `bin/setup.php` | WP-CLI one-time site setup script |
| `bin/populate-inner-pages.php` | WP-CLI content import — writes final copy into every inner page in one pass. See doc comment at top of file for what still needs manual input (officer names, a few Community pages, Customizer contact fields) |
| `.github/workflows/deploy.yml` | GitHub Actions deploy to staging |

---

## Block Editor — Homepage

All homepage content is managed through the block editor on the Home page.
Blocks appear under **Denver Elks #17** in the inserter. Add them in this order:

1. **Hero** (`denver17/hero`) — bg image, eyebrow, headline (2 lines), subtext, CTA button
2. **Feature Split** (`denver17/feature-split`) — bar section; variant: dark, layout: image-left
3. **Feature Split** (`denver17/feature-split`) — community section; variant: mid, layout: text-left
4. **Membership Steps** (`denver17/membership-steps`) — 3 steps with photos, titles, body copy
5. **Events Band** (`denver17/events-band`) — static placeholder until plugin is live
6. **CTA Band** (`denver17/cta-band`) — eyebrow, headline, button

All block fields live in the **Inspector sidebar** (right panel). The canvas shows a labeled placeholder — no live preview, by design.

Feature Split heading field: hit Enter between lines — each line becomes a line break in the rendered heading.

---

## Block Editor — Inner Pages

**Hours Display** (`denver17/hours-display`) — drop on the When & Where page. Pulls from the same Google Sheets source as the homepage hours card. Status computed server-side using `wp_timezone()` and `DateTime`. Sidebar toggles control which sections render (status indicator, special notice, base hours, note). Optional heading field.

**Beer List** (`denver17/beer-list`) — drop on the Jolly Corks Bar page. Pulls live tap list from Google Sheets. Sidebar toggles for showing style, ABV, and coming soon section. Optional heading field (defaults to "On Tap"). Beers sorted by the Order column in the sheet.

---

## Hours — Google Sheets Setup

`inc/hours-feed.php` fetches two tabs from a published Google Sheet and caches the result for 5 minutes via WP transients.

**Constants in `inc/hours-feed.php`:**
- `DENVER17_HOURS_SHEET_ID` — regular sheet ID from the URL
- `DENVER17_HOURS_PUBLISH_ID` — the `2PACX-...` string from File → Share → Publish to web (preferred; more reliable for server-side fetches)

**Tab: "Schedule"** — date-specific overrides
| A: Date | B: Open Time | C: Close Time | D: Special Notice |
|---------|-------------|---------------|-------------------|
| Friday, June 26 | 5:30 PM | Close | Drag Queen Pride Bingo! |
| Saturday, July 4 | *(blank)* | *(blank)* | Closed for Fourth of July |

Leave Open Time blank on closed days. Date format: `Day, Month D` (Google Sheets default).

**Tab: "Base Hours"** — default weekly schedule + display text
| Key | Value |
|-----|-------|
| open_days | Tue,Wed,Thu,Fri,Sat |
| open_time | 17:30 |
| display_line_1 | Tue–Sat · 5:30PM–Close |
| display_line_2 | *(optional second line)* |

**Dev testing:** Append `?hours=open`, `?hours=opens_at`, or `?hours=closed` to any URL to force a state without waiting for the clock.

**Cache bust:** `wp transient delete denver17_hours_data`

---

## Beer List — Google Sheets Setup

`inc/beer-feed.php` fetches from a published Google Sheet and caches for 5 minutes.

**Constants in `inc/beer-feed.php`:**
- `DENVER17_BEER_SHEET_ID` — regular sheet ID
- `DENVER17_BEER_PUBLISH_ID` — the `2PACX-...` string (preferred)
- `DENVER17_BEER_SHEET_NAME` — tab name, default `Beers`

**Tab: "Beers"**
| A: ID | B: Name | C: Style | D: ABV | E: Status | F: Order |
|-------|---------|----------|--------|-----------|----------|
| 1 | Coors Banquet | American Lager | 5.0 | On Tap | 1 |
| 2 | Modelo Especial | Mexican Lager | 5.4 | On Tap | 2 |

Status values: `On Tap`, `Coming Soon`, `Not In Stock`. Only the first two are displayed. Order column controls sort within each section; blank = sort last.

**Cache bust:** `wp transient delete denver17_beer_data`

---

## Build Plan

### ✅ Session 1 — Templates & Structure

- [x] `page.php`, `front-page.php`
- [x] All `template-parts/home/` sections

### ✅ Session 1.1 — Site Setup Script

- [x] `bin/setup.php` — idempotent WP-CLI script (pages, menus, Customizer defaults, front page)

### ✅ Session 2 — CSS

- [x] Full styles in `assets/css/main.css`
- [x] CSS custom properties — `--color-accent` wired to `--gold`
- [x] `.home .site-header { position: absolute }` for hero overlap
- [x] Full responsive pass at 760px breakpoint

### ✅ Session 3 — JavaScript

- [x] Mobile drawer open/close
- [x] Mobile accordion toggles
- [x] Hours card state rendering (reads `window.denver17Hours`)

### ✅ Session 2.5 — Custom Gutenberg Blocks

- [x] `inc/blocks.php` — registration, custom category, editor script enqueue
- [x] 5 blocks: Hero, Feature Split, Membership Steps, Events Band, CTA Band
- [x] Each block: `block.json` + PHP `render.php` calling existing template part
- [x] `assets/js/blocks-editor.js` — all editor UIs, no build step
- [x] `front-page.php` switched to `the_content()`
- [x] All template parts updated to accept `$args` with sensible defaults

### ✅ Session 4 — Inner Pages

- [x] `template-parts/page/banner.php` — shared page title banner
- [x] `page.php` — banner partial + `page-entry-content` wrapper
- [x] `single.php` — single post with category eyebrow, date/author meta, featured image
- [x] `archive.php` — 2-col post card grid with pagination
- [x] `inc/template-functions.php` — `denver17_placeholder()` helper added
- [x] `assets/css/main.css` — Sections 13–17: inner page styles

### ✅ Session 6.1 — Hours Card (Live Data)

- [x] `inc/hours-feed.php` — fetches Schedule + Base Hours tabs, caches via WP transients
- [x] `inc/enqueue.php` — `wp_localize_script` passes hours data as `window.denver17Hours`
- [x] `template-parts/home/hours-card.php` — restructured; JS drives all dynamic content; range hidden when closed
- [x] `assets/js/main.js` — `initHoursCard()` reads localized data; `?hours=` URL param for dev testing; "Open at [time]" when no fixed close
- [x] `assets/css/main.css` — amber (opens-at) and red (closed) dot states; `.hours-special`, `.hours-base`
- [x] `blocks/hours-display/` — `denver17/hours-display` block for When & Where page; server-side status using `wp_timezone()` + `DateTime`; sidebar toggles for all sections
- [x] "Closing time is at bartender's discretion." note added to both card and block

### ✅ Session 6.2 — Beer List (Live Data)

- [x] `inc/beer-feed.php` — fetches Beers tab, parses columns A–F, sorts by Order column, caches via WP transients
- [x] `blocks/beer-list/` — `denver17/beer-list` block; sidebar toggles for style, ABV, coming soon; empty-state fallback message
- [x] `inc/blocks.php` + `functions.php` + `assets/js/blocks-editor.js` — registered and wired

### Session 5 — Content & Configuration

- [ ] Run `bin/setup.php` on staging (or confirm it ran)
- [ ] Upload logo via Appearance → Customize → Site Identity
- [ ] Set social URLs, phone, address in Customizer → Contact & Social
- [ ] Build homepage in block editor using blocks
- [ ] Publish Home page
- [ ] Run `bin/populate-inner-pages.php` — writes final copy into every inner page as a draft
- [ ] Fill in Who's Who (officer names change annually — none are hardcoded)
- [ ] Verify Hoop Shoot, Soccer Shoot, Military & Veterans, and Scouts before publishing — these had no usable content on the old site, or (Scouts) had stale 2021 data that was deliberately left out
- [ ] Add real photos in place of `denver17_placeholder()` slots as they come in
- [ ] Publish inner pages once reviewed

### Session 6.3 — Calendar & Events

- [ ] Event calendar plugin (separate repo)
- [ ] Stripe ticketing integration (Checkout + webhooks)
- [ ] Replace `events-band.php` static placeholder with live plugin output
- [ ] CPTs: events, ticket types, orders

---

## Key Decisions & Context

**Payments:** Stripe (Checkout + webhooks). Proven on a prior "Raise a Glass" project.

**Plugin architecture:** Custom WP plugin using CPTs (events, ticket types, orders) exposed via shortcodes or Gutenberg blocks.

**Content management:** Custom Gutenberg blocks for all homepage sections. No ACF. Sidebar inspector pattern — all fields in the right panel, no live canvas preview.

**Hours card:** Google Sheet-driven. `inc/hours-feed.php` fetches Schedule and Base Hours tabs, caches with WP transients (5 min TTL). `DENVER17_HOURS_PUBLISH_ID` (the `2PACX-...` string) is the reliable fetch path — `gviz/tq` with the regular sheet ID fails on this server config. JS handles open/closed/opens-at state client-side from localized data; the `denver17/hours-display` inner page block computes status server-side. When there's no fixed close time, the card shows "Open at [time]" rather than "Open until close."

**Timezone:** The staging server runs UTC. WP timezone must be set to `America/Denver` in Settings → General. All date/time logic in PHP uses `wp_timezone()` and `DateTime` — never `date()` or `current_time('timestamp')` alone, as those use the server timezone and cause date drift of up to 6 hours.

**Beer list:** Google Sheet-driven via `inc/beer-feed.php`. Same published CSV URL pattern as hours. Sheet columns: A=ID, B=Name, C=Style, D=ABV, E=Status, F=Order. Status values are `On Tap`, `Coming Soon`, `Not In Stock`. Beers sorted by Order column within each section. The sheet format (Web page vs CSV) in the Publish to web dialog doesn't matter — we construct the CSV URL directly from the `2PACX-...` ID regardless of what format was selected when publishing.

**Member Area:** No login gate. The Member Area nav item exists for content relevant to current members (dues link, Slack invite, how-to docs), but no authentication is implemented. Maintainability was the deciding factor.

**Live data pattern:** Google Sheets as the CMS for hours and beer list. Fetch via published CSV URL (`/d/e/2PACX-.../pub?output=csv&sheet=TabName`), cached server-side with WP transients. The `gviz/tq` endpoint is unreliable on this host even with correct sharing settings — always use the published URL.

**Google Sheets date format:** Dates in the Schedule tab export as `Day, Month D` with a trailing non-breaking space (`\u00A0`, 2 bytes UTF-8). PHP's `trim()` won't catch it — use `preg_replace('/\s+$/u', '', $str)` with the Unicode flag. Strip the day-of-week prefix with `preg_replace('/^[A-Za-z]+,\s*/u', '', $str)`, then append the current year before passing to `DateTime::createFromFormat`.

**Lodge history:** Founded 1882. As of 2026, that's 144 years. Do not use "oldest lodge west of the Mississippi" — San Francisco Lodge #3 holds that distinction.

**Scope philosophy:** Launch first, refine content later. The site was compromised and SEO-hijacked — getting a clean site live fast is the priority.

**Page-lookup bug (get_page_by_path):** `get_page_by_path()` reconstructs a page's full ancestor path and compares it against the string you pass in. Given a bare slug like `'facilities'`, it only matches a page with no parent — a nested page's real path is `visit/facilities`, which doesn't equal `facilities`, so the lookup silently returns nothing. `bin/setup.php`, `bin/rebuild-menus.php`, and `bin/populate-inner-pages.php` all originally used this for slug-based lookups, which meant every nested page (18 of 24) was invisible to the "does this already exist" checks. On rerun, `setup.php` concluded those pages didn't exist and created duplicates with new IDs, and `populate-inner-pages.php` silently skipped writing content to any nested page. Fixed by replacing all three with a direct `get_posts( [ 'name' => $slug, 'post_status' => 'any', ... ] )` lookup, which matches on `post_name` alone with no hierarchy check. If a future script needs to find a page by slug, use that pattern, not `get_page_by_path()`, unless you're deliberately passing the full parent/child path.
