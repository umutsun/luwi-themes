# LuwiPress Sapphire — Features

*"Midnight Sapphire — trust, shipped."* A dark-first **SaaS / tech** theme for
the LuwiPress ecosystem — the blue, innovation-forward member of the jewel
family (sister to Gold, Emerald, Amber, Ruby and Onyx).

## Art direction

- **Midnight-navy base** `#070B16` (raised surfaces `#0A1124` / `#0E1730` /
  `#121C36`), an **electric sapphire** accent `#2563EB` (bright `#3B82F6`, glow
  `#60A5FA`), cool off-white ink `#E8EDF7`, sapphire-tinted hairlines.
- **Space Grotesk** for display headlines, **Inter** for body/UI, **JetBrains
  Mono** for eyebrows, version tags, code and stat numerics.
- A **sapphire-gradient** primary CTA (`.btn-gold`) and a hairline **ghost**
  button; rounded SaaS shape language + a soft sapphire glow on cards.
- Full parallel **"Ice & Sapphire" light mode** (`[data-theme="light"]`, base
  `#F5F8FF`, accent deepens to `#1D4ED8`), persisted in
  `localStorage('sapphire_theme')`, applied before first paint. Dark is default.
- An aurora radial wash behind the hero + CTA bands; reveal-on-scroll motion
  with `prefers-reduced-motion` support.

## Pages & templates

| View | Template | Highlights |
|------|----------|-----------|
| Home | `front-page.php` | Product-shot split hero, logo/trust strip, feature grid, product overview + animated stats, integrations wall, 3-tier pricing, testimonials, changelog/roadmap timeline, FAQ (+ FAQPage JSON-LD), CTA band |
| Pricing | *Sapphire — Pricing* | Starter / Pro / Studio tier cards (sold as WooCommerce products) + full feature-comparison matrix + trial CTA |
| Single Plan | *Sapphire — Single Plan* | Plan summary aside, product app-mock, what's-included checklist, "Everything in Pro" feature grid, sticky "Start free trial" CTA (+ SoftwareApplication JSON-LD) |
| Integrations | *Sapphire — Integrations* | Category-chip filtered integrations directory |
| About | *Sapphire — About* | Story + lead, stats, three principles, team grid, CTA band |
| Contact | *Sapphire — Contact* | Working form (built-in `wp_mail`, or CF7/WPForms shortcode) + contact channels + map |
| Search | *Sapphire — Search* + `search.php` | Search hero, popular chips, live WordPress results |
| Blog | `home.php` | Featured post + card grid |
| Article | `single.php` | Article hero, reading column, author byline, related |
| Archives / 404 / Page | `archive.php` · `index.php` · `404.php` · `page.php` | Sapphire styling throughout |

Every singular template **yields to Elementor** when the page is built with it.

## Chrome

- Utility bar (value-prop / announcement · contact email · EN/DE/FR language
  switch · social).
- Sticky header: logo (or "Sapphire" text wordmark), a **Product mega menu**
  (Features / Use cases / Highlights), Pricing / Blog / About / Contact, a search
  icon, the light/dark toggle and a **"Start free"** gradient CTA.
- Right-side mobile drawer with numbered nav, Features/Use-cases chips and a
  docked "Start free".
- Footer: brand, **Product / Company** link columns, a **"Ship notes"**
  newsletter and social.
- **Customer chat** is provided by **LuwiPress core**; the theme's own floating
  launcher hides when core is active (`luwipress_sapphire_show_chat_fab` filter).

## Elementor widget suite

Ships the full LuwiPress widget suite (60 widgets) — hero/feature/CTA/countdown/
stat-counter/testimonials/FAQ/newsletter/process-steps, AI search, KG-Stats &
KG-Trending, product card/grid, the topbar/header/footer builders and the
WooCommerce widgets (price, gallery, add-to-cart, tabs, rating, related…) — all
styled to the Midnight Sapphire palette and wired by the bundled interaction JS.

## Brand knob

Recolour the whole theme from **Customize → LuwiPress Sapphire → Brand**.
`assets/css/tokens.css` bridges the accent onto the Customizer `--primary` so a
single change cascades through every CTA, badge and accent — in both modes.

## Schema (AEO / SEO)

- **FAQPage** JSON-LD on the home FAQ.
- **SoftwareApplication** JSON-LD on the single-plan template.

## WooCommerce

Pricing tiers are sold as **WooCommerce products** (Starter / Pro / Studio), so
checkout, coupons and renewals work unchanged. Shop, product (PDP), cart,
checkout and my-account are all styled to the Sapphire palette.

## Ecosystem & integrations

LuwiPress core (AI search, onboarding wizard, maintenance suite, ecosystem
dashboard) + friendly-plugin glue for WooCommerce, WPML/Polylang, Rank
Math/Yoast and LiteSpeed — all optional, detected and amplified when present.

## Extending

- `luwipress_sapphire_pricing_tiers` — replace the pricing tier cards.
- `luwipress_sapphire_integrations_data` — replace the integrations directory.
- `luwipress_sapphire_mega_lists` — re-point the Product mega-menu links.
- `luwipress_sapphire_search_suggest` — set the popular search chips.

*Document version 1.0.0*
