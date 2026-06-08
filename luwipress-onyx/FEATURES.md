# LuwiPress Onyx — Features

*"Quiet luxury, after dark."* A dark-luxury real-estate theme for the LuwiPress
ecosystem — built from the ArshaHomes (Dubai) design exploration.

## Art direction

- **Onyx-black base** `#0E0E10` (raised surfaces `#121214` / `#161618` /
  `#1B1B1E`), a muted **champagne-gold** accent `#C9A24B` (soft `#D8B868`), warm
  off-white ink `#F4F1EA`, gold-tinted hairlines.
- **Bodoni Moda** display serif for headings, prices and numerals; **Nunito**
  for body and UI.
- A **platinum-gradient** primary CTA (`.btn-gold`) and a hairline **ghost**
  button — deliberately not flat gold fills.
- Full parallel **light mode** (`[data-theme="light"]`, gold darkens to
  `#9C7A28` for contrast), persisted in `localStorage('onyx_theme')`, applied
  before first paint to avoid a flash. Dark is the default.
- Reveal-on-scroll motion with `prefers-reduced-motion` support.

## Pages & templates

| View | Template | Highlights |
|------|----------|-----------|
| Home | `front-page.php` | Split hero, trust strip, building overview, tabbed apartment plans, property grid, neighborhoods map, testimonials carousel, lifestyle band, journal teasers, FAQ (+ FAQPage JSON-LD), contact band |
| Listings | *Onyx — Listings* | Filter sidebar (price dual-range, type, bedrooms, location, status), sort, grid/list, favourites, pagination, mobile filter drawer |
| Single Property | *Onyx — Single Property* | Gallery + lightbox, spec bar, amenities, floor plan, payment-plan timeline, live mortgage calculator, sticky CTA, similar residences (+ Residence JSON-LD) |
| Gallery | *Onyx — Gallery* | Category-chip filtered collection grid |
| About | *Onyx — About* | Story + lead, stats, three principles, advisor team grid |
| Contact | *Onyx — Contact* | Working form (built-in `wp_mail`, or CF7/WPForms shortcode) + contact panel + map |
| Search | *Onyx — Search* + `search.php` | Search hero, popular chips, live WordPress results |
| Journal | `home.php` | Featured post + card grid |
| Article | `single.php` | Article hero, reading column, author byline, related |
| Archives / 404 / Page | `archive.php` · `index.php` · `404.php` · `page.php` | Onyx editorial styling throughout |

Every singular template **yields to Elementor** when the page is built with it.

## Chrome

- Utility bar (address + email · call · EN/AR/RU language switch · social).
- Sticky header: logo, a **Residences mega menu** (By Type / By Neighborhood +
  featured cards), Gallery / About / Journal / Contact, search icon, light/dark
  toggle. No header CTA by design.
- Right-side mobile drawer with numbered serif nav, type/neighborhood chips and
  a docked "Book a viewing".
- Footer: brand, address, explore links, "The Quiet List" newsletter, social.
- **Customer chat** is provided by **LuwiPress core**; the theme's own floating
  launcher hides when core is active (`luwipress_onyx_show_chat_fab` filter).

## Brand knob

Recolour the whole theme from **Customize → LuwiPress Onyx → Brand**.
`assets/css/tokens.css` bridges the gold accent onto the Customizer `--primary`
so one change cascades through every CTA, badge and accent — in both modes.

## Schema (AEO / SEO)

- **FAQPage** JSON-LD on the home FAQ.
- **Residence** JSON-LD on the single-property template.

## Ecosystem & integrations

LuwiPress core (AI search, onboarding wizard, maintenance suite, ecosystem
dashboard) + friendly-plugin glue for WooCommerce, WPML/Polylang, Rank
Math/Yoast and LiteSpeed — all optional, detected and amplified when present.

## Extending

- `luwipress_onyx_listings_data` / `luwipress_onyx_gallery_data` — replace the
  demo residences (e.g. with a future `property` CPT).
- `luwipress_onyx_mega_lists` — re-point the mega menu type/neighborhood links.
- `luwipress_onyx_search_suggest` — set the popular search chips.

Properties are normal posts/pages for now; a CPT can back the listings later
without touching the design.

*Document version 1.0.0*
