# LuwiPress Onyx

**The midnight-and-champagne member of the LuwiPress theme family** — a
LuwiPress-powered, Elementor-ready WordPress theme for premium **real-estate**
brands (sister to Gold = editorial luxury, Emerald = B2B consulting,
Amber = travel). *"Quiet luxury, after dark."*

Built for the ArshaHomes (Dubai) design exploration: an onyx-black base
(`#0E0E10`) with a muted champagne-gold accent (`#C9A24B`), a platinum-gradient
primary CTA, **Bodoni Moda** display serif (headings + prices) paired with
**Nunito** for body/UI, and a full parallel **light mode** persisted in
`localStorage` (`onyx_theme`, dark by default, applied before first paint).

## What it renders out of the box

Activate the theme, create a handful of pages, and assign the page templates —
no Elementor required (though every template yields to Elementor when a page is
built with it):

- **Home** (`front-page.php`) — editorial split hero → trust strip → building
  overview → tabbed apartment plans → property grid → neighborhoods map →
  testimonials carousel → lifestyle/film band → journal teasers → **FAQ with
  FAQPage JSON-LD** → contact band.
- **Listings** (*Onyx — Listings* template) — filter sidebar (price dual-range,
  type, bedrooms, location, status), sort, grid/list toggle, favourites,
  pagination + mobile filter drawer. Cards render client-side from a JSON island
  (filterable via `luwipress_onyx_listings_data`).
- **Single Property** (*Onyx — Single Property* template) — gallery + lightbox,
  spec bar, amenities, floor plan, payment-plan timeline, live **mortgage
  calculator**, sticky CTA bar, similar residences + **Residence JSON-LD**.
- **Gallery** (*Onyx — Gallery* template) — category-chip filtered collection.
- **About** (*Onyx — About* template) — story, stats, three principles, advisor
  team grid (founder Ayhan Sahin).
- **Contact** (*Onyx — Contact* template) — a working form (self-contained
  `wp_mail` handler, or your Contact Form 7 / WPForms shortcode when present) +
  contact panel + embedded map.
- **Search** (*Onyx — Search* template + native `search.php`) — search hero,
  popular chips, live WordPress results as Onyx result rows.
- **Journal / Article** (`home.php` / `single.php`) + archives, 404 and a
  generic page template — all in the Onyx editorial style.

## Chrome

- **Utility bar** — address + email (left); call experts, language switch
  (EN/AR/RU, WPML/Polylang-aware), social (right).
- **Sticky header** — logo, a **Residences mega menu** (By Type / By
  Neighborhood + featured cards), Gallery / About / Journal / Contact, a search
  icon and the light/dark toggle. No header CTA — the primary call-to-action
  lives in the hero and on Contact.
- **Mobile drawer** — numbered serif nav, type/neighborhood chips, footer with
  language switch, theme toggle, call, "Book a viewing" and address.
- **Footer** — brand blurb, address, explore links, "The Quiet List" newsletter.
- **Customer chat** — comes from **LuwiPress core**; the theme's own floating
  launcher hides when core is active (override `luwipress_onyx_show_chat_fab`).

## Brand knob

Everything recolours from **Appearance → Customize → LuwiPress Onyx → Brand**.
The design system lives in `assets/css/onyx*.css`; `assets/css/tokens.css` is a
thin bridge that re-points the gold accent at the Customizer's `--primary` so a
single colour change cascades through every CTA, badge and accent — in both
dark and light modes.

## Requirements

- WordPress 6.4+, PHP 8.1+
- **Elementor** (free) + **LuwiPress** core (declared in `Requires Plugins`)
- WooCommerce, WPML/Polylang, Rank Math/Yoast, LiteSpeed — all optional, all
  detected and amplified when present.

## Notes

- Properties are normal posts/pages for now (no CPT required); a `property` CPT
  can back the listings later — the demo data is filterable.
- Build: `php build-theme-zip.php 1.0.0 luwipress-onyx-elementor luwipress-onyx`
  → `releases/luwipress-onyx-1.0.0.zip`.
