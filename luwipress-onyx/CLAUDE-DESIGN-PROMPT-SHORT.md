# LuwiPress Onyx — Follow-Up Asks

You delivered the **LuwiPress Onyx v1.1.0 Elementor Kit** (Tapadum,
Brisighella). It's now a live WordPress PHP theme at v1.2.3 —
homepage / shop / PDP / cart / checkout / my-account / journal all
shipped. Design tokens unchanged from your kit.

Five asks below. Each = one self-contained file I can copy-paste.
Deliver in priority order; **stop after Ask 1** if context is tight.

---

## 1. Demo content WXR (P0 — CodeCanyon blocker)

A WordPress export `.xml` covering: 8 sample products (mix simple +
variable, 3 on sale, 1 out of stock, picsum.photos featured images,
`pa_master` attribute terms), 6 product categories with descriptions,
5 sample blog posts with featured images, 3 sample customer addresses.

Plus a PHP activation hook (`after_switch_theme`) that points at the
XML and runs the WP importer.

Tokens: `--primary #735c00`, `--gold-bright #D4AF37`, `--ink #1A1612`,
`--cream #FAF6EE`. Voice: editorial, atelier, Brisighella.

---

## 2. WooCommerce email templates (P1)

Three files, table-based HTML (Outlook-safe), inline-styled:

- `woocommerce/emails/email-header.php` — Cormorant title + cream band
- `woocommerce/emails/customer-processing-order.php` — items table,
  black-pill CTA, mono uppercase column headers
- `woocommerce/emails/customer-completed-order.php` — green check icon,
  4-step tracking timeline, order summary (mirrors the
  `Tapadum-Order-Received.html` reference)

---

## 3. PDP variation chips (P1 — JS + CSS)

Vanilla JS (~80 lines) that converts `<select name="attribute_*">`
inside `.variations` into `<button class="lwp-pdp-chip">` buttons.
Click triggers the `<select>` change so WC's price/gallery/stock
logic still fires. Selected = `.is-on`.

CSS: `border:1px solid var(--line); padding:9px 16px; border-radius:6px;
on-state → border-color var(--primary), background #fdfaef, color
var(--primary)`.

---

## 4. CodeCanyon preview image `preview.png` (P0)

2200 × 1700 px collage: homepage hero (large) + PDP card + shop grid
+ my-account dashboard + cart drawer + sticky-PDP + checkout. Headline
in **Cormorant Garamond italic**: "LuwiPress Onyx — World Instruments
WooCommerce Theme". Feature pills below: Elementor · WooCommerce ·
WPML · Mobile First. PNG ≤ 800 KB.

Plus `screenshot.png` (1200 × 900, homepage hero only) for the WP
admin theme tile.

---

## 5. Wishlist plugin override (P2)

Override at `woocommerce/tinvwl/wishlist-default.php` for **TI
WooCommerce Wishlist plugin**. 4-col grid matching `Tapadum-Wishlist.html`
reference: heart icon top-right, square image, category eyebrow,
Playfair name, italic maker, gold price, black-pill "Add to cart"
under each card. Use plugin's hook + variable names so actions stay
wired.

---

## Format

- **PHP**: `defined('ABSPATH') || exit;` + WC hooks + comments
- **JS**: vanilla, IIFE, `prefers-reduced-motion` aware
- **CSS**: `.lwp-*` classes, use existing CSS variables
- **PNG/SVG**: ready to drop in

Don't deliver: real photography, WPML translations, social URLs,
gateway config — those are operator-side.
