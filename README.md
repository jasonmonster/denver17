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
- Custom Gutenberg blocks are the content management layer for homepage sections. All editable homepage content lives in blocks, not hardcoded PHP.
- Maintainability is the priority. This site will be handed off within ~2 years; content must be editable by non-developers.

---

## What's Built

| File | Notes |
|---|---|
| `functions.php` | Loader only — requires all `inc/` files |
| `inc/theme-setup.php` | Theme supports, nav menu registration |
| `inc/nav-walkers.php` | Desktop mega menu walker + mobile accordion walker |
| `inc/enqueue.php` | Enqueue styles and scripts |
| `inc/theme-options.php` | Customizer panels: colors, contact info, social URLs, homepage images |
| `inc/template-functions.php` | Helper/utility functions (social links output SVGs) |
| `header.php` | Full nav with mega menu, social icons, Member Area CTA |
| `template-parts/nav/mobile-menu.php` | Slide-in drawer |
| `template-parts/home/hero.php` | Hero section — bg image from Customizer |
| `template-parts/home/hours-card.php` | Live-status hours card (client-side JS placeholder) |
| `template-parts/home/feature-split.php` | Reusable feature section — takes $args for variant/layout/content |
| `template-parts/home/membership-steps.php` | 3-step membership funnel |
| `template-parts/home/events-band.php` | Events grid — static placeholder until plugin is built |
| `template-parts/home/cta-band.php` | Closing CTA band |
| `footer.php` | Footer structure with social links |
| `front-page.php` | Homepage — calls template parts; content currently hardcoded in PHP |
| `page.php` | Static page template |
| `index.php` | Minimal fallback loop |
| `bin/setup.php` | WP-CLI one-time site setup script |
| `.github/workflows/deploy.yml` | GitHub Actions deploy to staging |

---

## Build Plan

### ✅ Session 1 — Templates & Structure

- [x] `page.php` — static page template
- [x] `front-page.php` — homepage
- [x] `template-parts/home/hero.php`
- [x] `template-parts/home/hours-card.php`
- [x] `template-parts/home/feature-split.php`
- [x] `template-parts/home/membership-steps.php`
- [x] `template-parts/home/events-band.php`
- [x] `template-parts/home/cta-band.php`

### ✅ Session 1.1 — Site Setup Script

- [x] `bin/setup.php` — idempotent WP-CLI script
  - Creates all pages as drafts with correct parent/child hierarchy
  - Creates and populates primary + footer nav menus
  - Assigns menus to theme locations
  - Sets Customizer defaults
  - Sets static front page in Reading Settings
- [ ] Update `$page_tree` in setup.php once sitemap is finalized

### Session 2 — CSS

- [ ] Port all mockup styles into `assets/css/main.css`
- [ ] Hook up CSS custom properties from Customizer (`--color-accent` → `--gold`)
- [ ] Responsive pass (760px breakpoint)
- [ ] `.home .site-header { position: absolute }` for hero overlap

### Session 3 — JavaScript

- [ ] Mobile drawer open/close (`assets/js/main.js`)
- [ ] Mobile accordion toggles
- [ ] Hours card open/closed logic (client-side placeholder — replace with real data source post-launch)

### Session 2.5 — Custom Gutenberg Blocks (after CSS, before inner pages)

All homepage section content must be editable by non-developers. Custom blocks are
the content management layer — no ACF dependency, no hardcoded copy in PHP.

**Decision:** `front-page.php` currently has all content hardcoded in template parts.
Once blocks are built, the template parts become block renderers (or are replaced
entirely by block output via `the_content()`).

Blocks to build (one per homepage section):

- [ ] **Hero Block** — headline, subhead, eyebrow, CTA button text/URL, bg image
- [ ] **Feature Split Block** — tag, heading, body, link, image, variant (dark/mid), layout (image-left/text-left)
- [ ] **Membership Steps Block** — 3 steps, each with image, number label, title, body
- [ ] **Events Band Block** — placeholder until plugin; headline + static event cards (date, name, image)
- [ ] **CTA Band Block** — eyebrow, headline, button text/URL

Each block:
- Registered in `inc/blocks.php` (new file), required from `functions.php`
- Render callback outputs the existing template part HTML (keeps CSS stable)
- Block attributes map 1:1 to the `$args` the template parts already accept
- `front-page.php` switches from explicit `get_template_part()` calls to `the_content()`
- Hours card stays PHP-rendered (no editable content)

### Session 4 — Inner Pages

- [ ] `single.php`
- [ ] `archive.php`
- [ ] Interior page templates: Visit, Learn, Community, Contact

### Session 5 — Blocks & Content

- [ ] Set up nav menus in WP admin (or run `bin/setup.php`)
- [ ] Enter Customizer globals (colors, social URLs, phone, address)
- [ ] Set homepage images via Customizer → Homepage Images
- [ ] Build out homepage in block editor using Session 2.5 blocks

### Session 6 — Plugin

- [ ] Event calendar plugin (separate repo)
- [ ] Stripe ticketing integration
- [ ] Replace `events-band.php` static placeholder with live plugin output

---

## Key Decisions & Context

**Payments:** Stripe (Checkout + webhooks). Proven on a prior "Raise a Glass" project.

**Plugin architecture:** Custom WP plugin using CPTs (events, ticket types, orders) exposed via shortcodes or Gutenberg blocks.

**Content management:** Custom Gutenberg blocks for all homepage sections. No ACF. Content must be editable by non-developers from day one — hardcoded copy in PHP is not acceptable for handoff.

**Hours card:** Currently client-side JavaScript. Flagged for replacement with a real data source after launch.

**Member Area:** Nav CTA points to a future member portal. Content that doesn't require authentication should not be gated behind login — scope risk for MVP.

**Lodge history:** Founded 1882. As of 2026, that's 144 years. Do not use "oldest lodge west of the Mississippi" — San Francisco Lodge #3 holds that distinction.

**Volunteer turnover:** A core survivability concern. Strategy is quality documentation, not platform simplification.

**Scope philosophy:** Launch first, refine content later. The site was compromised and Google-SEO-hijacked — getting a clean site live fast is the priority.
