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
- Custom Gutenberg blocks are on the table for custom UI that doesn't map cleanly to core blocks.
- Maintainability is the priority. This site will be handed off within ~2 years; content must be editable by non-developers.

---

## What's Built

| File | Notes |
|---|---|
| `functions.php` | Loader only — requires all `inc/` files |
| `inc/theme-setup.php` | Theme supports, nav menu registration |
| `inc/nav-walkers.php` | Desktop mega menu walker + mobile accordion walker |
| `inc/enqueue.php` | Enqueue styles and scripts |
| `inc/theme-options.php` | Customizer panels: colors, contact info, social URLs |
| `inc/template-functions.php` | Helper/utility functions |
| `header.php` | Full nav with mega menu, social icons, Member Area CTA |
| `template-parts/nav/mobile-menu.php` | Slide-in drawer |
| `footer.php` | Footer structure with social links |
| `index.php` | Minimal fallback loop |
| `.github/workflows/deploy.yml` | GitHub Actions deploy to staging |

---

## Build Plan

### Session 1 — Templates & Structure

- [ ] `page.php` — static page template
- [ ] `front-page.php` — homepage (the big one)
- [ ] `template-parts/home/hero.php`
- [ ] `template-parts/home/hours-card.php`
- [ ] `template-parts/home/feature-split.php`
- [ ] `template-parts/home/membership-steps.php`
- [ ] `template-parts/home/events-band.php`
- [ ] `template-parts/home/cta-band.php`

### Session 2 — CSS

- [ ] Port all mockup styles into `assets/css/main.css`
- [ ] Hook up CSS custom properties from Customizer
- [ ] Responsive pass (760px breakpoint)

### Session 3 — JavaScript

- [ ] Mobile drawer open/close (`assets/js/main.js`)
- [ ] Mobile accordion toggles
- [ ] Hours card open/closed logic (client-side placeholder — replace with real data source post-launch)

### Session 4 — Inner Pages

- [ ] `single.php`
- [ ] `archive.php`
- [ ] Interior page templates: Visit, Learn, Community, Contact

### Session 5 — Blocks & Content

- [ ] Custom Gutenberg blocks as needed
- [ ] Set up nav menus in WP admin
- [ ] Enter Customizer globals (colors, social URLs, phone, address)

### Session 6 — Plugin

- [ ] Event calendar plugin (separate repo)
- [ ] Stripe ticketing integration

---

## Key Decisions & Context

**Payments:** Stripe (Checkout + webhooks). Proven on a prior "Raise a Glass" project.

**Plugin architecture:** Custom WP plugin using CPTs (events, ticket types, orders) exposed via shortcodes or Gutenberg blocks.

**Hours card:** Currently client-side JavaScript. Flagged for replacement with a real data source after launch.

**Member Area:** Nav CTA points to a future member portal. Content that doesn't require authentication should not be gated behind login — scope risk for MVP.

**Lodge history:** Founded 1882. As of 2026, that's 144 years. Do not use "oldest lodge west of the Mississippi" — San Francisco Lodge #3 holds that distinction.

**Volunteer turnover:** A core survivability concern. Strategy is quality documentation, not platform simplification.

**Scope philosophy:** Launch first, refine content later. The site was compromised and Google-SEO-hijacked — getting a clean site live fast is the priority.
