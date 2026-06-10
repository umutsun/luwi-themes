# LuwiPress Sapphire

**The blue, trust-and-innovation member of the LuwiPress theme family** — a
LuwiPress-powered, Elementor-ready WordPress theme for **SaaS, tech and
digital-product** brands (sister to Gold = editorial luxury, Emerald = B2B
consulting, Amber = travel, Ruby = editorial/fashion, Onyx = real-estate).
*"Midnight Sapphire — trust, shipped."*

Dark-first art direction: a deep midnight-navy base (`#070B16`) with an
**electric sapphire** accent (`#2563EB → #3B82F6`), a sapphire-gradient primary
CTA and an aurora wash behind the hero. **Space Grotesk** display headlines,
**Inter** body/UI, **JetBrains Mono** for eyebrows, version tags and stat
numerics. Full parallel **"Ice & Sapphire" light mode**, persisted in
`localStorage` (`sapphire_theme`, dark by default, applied before first paint).

Pricing tiers are sold as **WooCommerce products** (Starter / Pro / Studio), so
checkout, coupons, renewals and the LuwiPress companion plumbing all work
unchanged.

## What it renders (SaaS section system)

Activate the theme, create a handful of pages, and assign the page templates —
no Elementor required (though every template yields to Elementor when a page is
built with it):

- **Home** (`front-page.php`) — product-shot split hero → logo/trust strip →
  feature grid → how-it-works → integrations wall → animated stats band →
  3-tier pricing (monthly/annual toggle) → testimonials → changelog/roadmap
  timeline → **FAQ with FAQPage JSON-LD** → CTA band.
- **Pricing / Plans** (*Sapphire — Pricing* template) — tier cards + full
  feature-comparison matrix; tiers link to their WC products.
- **Single Plan / Product** (*Sapphire — Single Product*) — what's included,
  feature list, FAQ, sticky "Start trial" CTA + **SoftwareApplication JSON-LD**.
- **Integrations** (*Sapphire — Integrations* template) — category-chip filtered
  tile directory.
- **About** (*Sapphire — About* template) — story, stats, principles, team grid.
- **Contact / Talk to sales** (*Sapphire — Contact* template) — a working form
  (self-contained `wp_mail`, or your Contact Form 7 / WPForms shortcode when
  present) + contact panel + booking CTA.
- **Search** (*Sapphire — Search* + native `search.php`) — search hero, popular
  chips, live results.
- **Blog / Article** (`home.php` / `single.php`) + archives, 404 and a generic
  page template — editorial column with mono code blocks.

## Chrome

- **Utility bar** — short value-prop / "what's new" pill (left); changelog link,
  language switch (WPML/Polylang-aware), social (right).
- **Sticky header** — logo, a **Product mega menu** (Features / Integrations /
  Use-cases + featured card), Pricing, Docs/Blog, a search icon, the light/dark
  toggle, and a **"Start free"** gradient CTA.
- **Mobile drawer** — nav, "Start free", language + theme toggles.
- **Footer** — product columns (Product / Developers / Company / Legal), "Ship
  notes" newsletter, social, status-page link.
- **Customer chat** — comes from **LuwiPress core**; the theme's own floating
  launcher hides when core is active (override `luwipress_sapphire_show_chat_fab`).

## Brand knob

Everything recolours from **Appearance → Customize → LuwiPress Sapphire → Brand**.
The design system lives in `assets/css/sapphire*.css`; `assets/css/tokens.css` is
a thin bridge that re-points the sapphire accent at the Customizer's `--primary`
so a single colour change cascades through every CTA, badge and accent — in both
dark and light modes.

## Requirements

- WordPress 6.4+, PHP 8.1+
- **Elementor** (free) + **LuwiPress** core (declared in `Requires Plugins`)
- WooCommerce, WPML/Polylang, Rank Math/Yoast, LiteSpeed — all optional, all
  detected and amplified when present.

## Build status

- **Done:** foundation — full Midnight Sapphire / Ice & Sapphire color system
  (both modes), Space Grotesk + Inter + JetBrains Mono typography, Customizer
  brand defaults, `theme.json`, valid + activatable theme (PHP lints clean,
  no BOM). See `CLAUDE-DESIGN-PROMPT.md` for the complete design spec.
- **In progress:** the SaaS section layer — `front-page.php` + templates are
  being re-mapped from the Onyx fork-tree to the SaaS section order in the
  design brief (§5). Until then some page markup still reflects the fork
  origin, recoloured to sapphire.

## Notes

- Plans/tiers are normal WC products; a dedicated CPT can back them later.
- Build: `php build-theme-zip.php 1.0.0 luwipress-sapphire-elementor luwipress-sapphire`
  → `releases/luwipress-sapphire-1.0.0.zip`.
