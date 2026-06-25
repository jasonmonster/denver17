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
| `inc/enqueue.php` | Enqueue `main.css` and `main.js` |
| `inc/theme-options.php` | Customizer: colors, contact info, social URLs, homepage images |
| `inc/template-functions.php` | Helper functions — `denver17_social_links()` outputs SVG icons |
| `inc/blocks.php` | Block registration, custom category, editor script enqueue |
| `header.php` | Nav with mega menu, social icons, Member Area CTA |
| `footer.php` | Footer with social links and footer nav |
| `front-page.php` | Homepage — renders via `the_content()` using custom blocks |
| `page.php` | Static page template |
| `index.php` | Minimal fallback loop |
| `template-parts/nav/mobile-menu.php` | Slide-in mobile drawer |
| `template-parts/home/hero.php` | Hero section — accepts `$args` from block render |
| `template-parts/home/hours-card.php` | Live-status hours card (JS-driven, no editable content) |
| `template-parts/home/feature-split.php` | Reusable feature section — accepts `$args` |
| `template-parts/home/membership-steps.php` | 3-step funnel — accepts `$args` |
| `template-parts/home/events-band.php` | Events grid — static placeholder, accepts `$args` |
| `template-parts/home/cta-band.php` | Closing CTA — accepts `$args` |
| `blocks/hero/` | `block.json` + `render.php` |
| `blocks/feature-split/` | `block.json` + `render.php` |
| `blocks/membership-steps/` | `block.json` + `render.php` |
| `blocks/events-band/` | `block.json` + `render.php` |
| `blocks/cta-band/` | `block.json` + `render.php` |
| `assets/css/main.css` | All front-end styles; `--gold` references Customizer `--color-accent` |
| `assets/css/editor-style.css` | Block editor canvas styles |
| `assets/js/main.js` | Mobile drawer, accordion toggles, hours card logic |
| `assets/js/blocks-editor.js` | Block editor UI — all 5 blocks, no build step, vanilla `wp.*` globals |
| `bin/setup.php` | WP-CLI one-time site setup script |
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

All block fields live in the **Inspector sidebar** (right panel). The canvas shows a labeled placeholder — no live preview, by design. These blocks are used once each; the sidebar UI is cleaner than inline editing for layout sections.

Feature Split heading field: hit Enter between lines — each line becomes a line break in the rendered heading.

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
- [x] Hours card open/closed logic (client-side placeholder)

### ✅ Session 2.5 — Custom Gutenberg Blocks

- [x] `inc/blocks.php` — registration, custom category, editor script enqueue
- [x] 5 blocks: Hero, Feature Split, Membership Steps, Events Band, CTA Band
- [x] Each block: `block.json` + PHP `render.php` calling existing template part
- [x] `assets/js/blocks-editor.js` — all editor UIs, no build step
- [x] `front-page.php` switched to `the_content()`
- [x] All template parts updated to accept `$args` with sensible defaults

### Session 4 — Inner Pages

- [ ] `single.php`
- [ ] `archive.php`
- [ ] Interior page templates: Visit, Learn, Community, Contact

### Session 5 — Content & Configuration

- [ ] Run `bin/setup.php` on staging (or confirm it ran)
- [ ] Upload logo via Appearance → Customize → Site Identity
- [ ] Set social URLs, phone, address in Customizer → Contact & Social
- [ ] Build homepage in block editor using Session 2.5 blocks
- [ ] Publish Home page

### Session 6 — Plugin

- [ ] Event calendar plugin (separate repo)
- [ ] Stripe ticketing integration
- [ ] Replace `events-band.php` static placeholder with live plugin output

---

## Key Decisions & Context

**Payments:** Stripe (Checkout + webhooks). Proven on a prior "Raise a Glass" project.

**Plugin architecture:** Custom WP plugin using CPTs (events, ticket types, orders) exposed via shortcodes or Gutenberg blocks.

**Content management:** Custom Gutenberg blocks for all homepage sections. No ACF. Sidebar inspector pattern — all fields in the right panel, no live canvas preview. Appropriate for one-off layout blocks.

**Hours card:** Client-side JavaScript, hardcoded schedule. Flagged for replacement with a real data source after launch.

**Member Area:** Nav CTA points to a future member portal. Content that doesn't require authentication should not be gated behind login — scope risk for MVP.

**Lodge history:** Founded 1882. As of 2026, that's 144 years. Do not use "oldest lodge west of the Mississippi" — San Francisco Lodge #3 holds that distinction.

**Volunteer turnover:** A core survivability concern. Strategy is quality documentation, not platform simplification.

**Scope philosophy:** Launch first, refine content later. The site was compromised and Google-SEO-hijacked — getting a clean site live fast is the priority.
