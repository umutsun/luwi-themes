# LuwiPress Emerald

Sister theme to **LuwiPress Emerald** — same LuwiPress companion plumbing, but tuned for **B2B engagements, agencies, and knowledge work** rather than catalog-heavy retail.

- Typography: **Inter** (sans) + **JetBrains Mono** (numerics)
- Palette: slate neutrals + **emerald jewel green** built around `#047857` primary (operator-overrideable in Customizer → LuwiPress Emerald → Brand)
- Container: 1280px · 12-step spacing scale · three shadow tiers
- Sticky shrinking header · slide-in cart drawer · left-slide mobile nav
- WPML / Polylang language pill, WooCommerce-ready, Elementor + Theme Builder support
- Reveal-on-scroll motion with `prefers-reduced-motion` fallback

## Repo layout

```
themes/luwipress-emerald-elementor/
├── style.css                  Theme metadata (single source of version)
├── functions.php              Bootstrap — constants + module includes
├── theme.json                 Editor palette + typography
├── header.php / footer.php    Chrome (topbar, sticky header, footer + drawers)
├── header-canvas.php          Full-bleed canvas for Elementor Pro Theme Builder
├── footer-canvas.php
├── page-canvas.php            Page template — "Emerald Canvas"
├── index.php / page.php       WP defaults using Emerald primitives
├── single.php / archive.php
├── search.php / searchform.php
├── 404.php
├── comments.php / sidebar.php
├── inc/
│   ├── setup.php              Theme supports, nav menus, image sizes
│   ├── enqueue.php            Tokens + frontend.js + Google Fonts
│   └── customizer.php         Topbar / Header / Footer settings panel
├── template-parts/
│   └── insight-card.php       Journal/insight card primitive
├── assets/
│   ├── css/tokens.css         Design tokens + chrome + components
│   └── js/frontend.js         Header scroll, drawers, reveal, counter
└── languages/                 .pot extraction target
```

## Design reference

The 23 reference HTML pages (homepage, solutions index/detail, journal, team, account suite, cart, checkout, 404, search, mobile showcase, design system) live in the project repo at:

```
temp/Luwipress Emerald Elementor Theme/pages/
```

These are visual references for Elementor authoring. The PHP templates render WordPress / WooCommerce content through the standard template hierarchy and pull visual primitives from `assets/css/tokens.css`.

## Token primer

Spacing scale (`var(--sp-N)`):

```
sp-1 4px · sp-2 8px · sp-3 12px · sp-4 16px · sp-5 20px · sp-6 24px
sp-8 32px · sp-10 40px · sp-12 48px · sp-16 64px · sp-20 80px · sp-24 96px · sp-32 128px
```

Surfaces: `--bg` `#FFFFFF` · `--bg-alt` `#F8FAFC` · `--card` `#FFFFFF` · `--line` `#E2E8F0` · `--line-soft` `#F1F5F9`

Ink: `--ink` `#0F172A` · `--ink-soft` `#334155` · `--muted` `#64748B`

Brand: `--primary` `#047857` (emerald-700) · `--primary-hover` `#065F46` (emerald-800) · `--primary-soft` `#ECFDF5` (emerald-50)

Semantic: `--success` `#0D9F6E` · `--warning` `#B45309` · `--sale` `#DC2626` · `--info` `#0284C7`

## Versioning

Bump `Version:` in `style.css` only. `functions.php` reads it through `wp_get_theme()` and propagates the value to every cache-buster query string. Per the LuwiPress version-bump policy: **never bump unless the operator explicitly says so**, and batch multiple fixes into a single patch.
