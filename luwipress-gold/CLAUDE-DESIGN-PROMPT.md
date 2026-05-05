# Prompt — Claude Design Follow-Up Round

A **single-shot chat input** for Claude Design. Copy-paste this whole
document into a new chat to brief them on what's done and what's still
needed.

---

## Context

> You delivered the **LuwiPress Gold v1.1.0** Elementor Kit (Tapadum
> world-instruments e-commerce, Brisighella). Since handoff, the kit
> has been ported into a **standalone WordPress PHP theme** at
> `https://new.tapadum.com` (live), now at version **1.2.3**.
>
> **What's already shipped in the theme:**
>
> - Full WC commerce flow (cart, checkout, order received, my-account
>   dashboard / orders / view-order / addresses / details / login)
> - Custom product card with eyebrow + Playfair name + italic maker +
>   gold serif price ladder (no inline cart button on cards)
> - Animation kit from `00-animations.json` (loader, scroll reveal,
>   stagger, skeleton, image fade, cart bump, hero pill blink,
>   underline-grow, hover lift) with `prefers-reduced-motion`
> - Smart UI: search overlay, mini-cart drawer (auto-opens on
>   `added_to_cart`), PDP sticky add-to-cart bar, account popover
>   dropdown
> - PDP polish: **eyebrow above title**, **"Save €X" badge** on sale
>   price, **stock pill** (in/out/backorder), 4-perk rail under the
>   buy column
> - Journal **listing** (1 big featured + 3-col grid, reading-time
>   computed) and **single post** (editorial 720px reading column +
>   prev/next nav + tags)
> - Login marketing card (Tapadum perks + featured product) when
>   register is disabled
> - Elementor "default container" override → all containers go
>   edge-to-edge by default; operator can opt back to boxed via
>   `body.lwp-layout-boxed` class
>
> **Visual design tokens, typography, spacing, and interactions are
> all locked in from your v1.1.0 kit** — the asks below are the
> remaining gaps that genuinely need design / asset / non-trivial
> creative input from your end.

---

## Asks — priority order

Each ask = **one self-contained file** ready to drop into the theme.
Don't deliver placeholders or "operator should fill this in" — give a
working copy I can paste once.

---

### 🟥 P0 — Required for CodeCanyon submission

#### 1. Demo content (one-click "Import sample data")

A single **WordPress eXtended RSS (WXR)** export file or **JSON**
(WP REST schema) covering:

- **8 sample products**, 2 per primary category (Oud, Darbuka,
  Kemence, Ney). Mix simple + variable types. Include:
  - Title, slug, short description (1 sentence), long description
    (3 paragraphs)
  - Regular price + sale price (3 of the 8 on sale)
  - Featured image URL pointing to **picsum.photos** (e.g.,
    `https://picsum.photos/seed/oud/800/800`)
  - Category assignment (`product_cat`)
  - Stock status (1 out-of-stock for testing the pill)
  - `pa_master` attribute term per product (Yildirim, Feramis, Hamid,
    A. Golestani)
- **6 product categories** (String, Percussions, Bowed, Winds,
  Accessories, Sound Healing) with descriptions matching the kit
  voice
- **5 sample blog posts** matching the journal listing reference —
  each with featured image (picsum), excerpt, 2-paragraph content,
  category assignment
- **3 sample customer addresses** for my-account demo

The deliverable: **one `.xml` file** I can drop in the theme's
`demo-content/` folder + a `theme-activation` import hook in PHP.

Why P0: WP themes in CodeCanyon get rejected if reviewers see "no
sample content" — they need to be able to import + see the design
system populated within 30 seconds of activation.

#### 2. CodeCanyon preview image (`preview.png`)

A **2200 × 1700 px** marketing collage showing 6 templates in a single
image:

```
┌────────────────────────────────┬───────────────┐
│                                │               │
│      Homepage hero (large)     │   PDP card    │
│                                │               │
├──────────────┬─────────────────┴───────────────┤
│              │                                 │
│  Shop grid   │     My-account dashboard        │
│              │                                 │
├──────────────┴─────────────┬────────┬──────────┤
│                            │        │          │
│       Cart drawer          │ Sticky │ Checkout │
│                            │  PDP   │          │
└────────────────────────────┴────────┴──────────┘
```

Headline: **"LuwiPress Gold — World Instruments WooCommerce Theme"**
in Cormorant Garamond italic. Subtext: feature pills (Elementor
Compatible · WooCommerce · WPML · Mobile First · CodeCanyon Ready).

Reference style: ThemeForest's editorial bestseller landing. Use real
Tapadum-Gold typography + the gold/cream palette. PNG, ≤ 800 KB.

#### 3. Theme `screenshot.png`

**1200 × 900 px** screenshot of the homepage hero + sticky header in
the brand state (Tapadum wordmark / mega menu / italic gold headline +
image card). Same source as #2 but cropped to the WP-required ratio.

---

### 🟧 P1 — High-impact polish

#### 4. WooCommerce email templates (3 files)

WC's order-confirmation / shipped / refund emails go through plain WC
defaults right now. Match the kit's editorial visual language. Need:

| File | Use |
|---|---|
| `woocommerce/emails/email-header.php` | Cormorant title + cream header band |
| `woocommerce/emails/email-styles.php` | Inline CSS variables (Outlook-safe table layout) |
| `woocommerce/emails/customer-processing-order.php` | Black-pill CTA + line items table with mono uppercase column headers |
| `woocommerce/emails/customer-completed-order.php` | Green check icon + tracking timeline + order summary (mirrors `Tapadum-Order-Received.html`) |

Constraints: **table-based**, no flexbox / grid (Outlook ignores them).
Inline-style every visible element. Single dark-mode media query at
the bottom is fine.

#### 5. PDP variation chips (vanilla JS)

Reference shows wood / tuning chips with `.chips button.on { border:
1px solid var(--primary); background: #fdfaef; color: var(--primary) }`.
WooCommerce ships these as `<select name="attribute_*">` dropdowns.

Need a **vanilla JS snippet** (~80–120 lines, no jQuery) that:

- Hooks `wc_variation_form` on document ready
- Walks each `<select>` inside `.variations`
- Replaces the select with a row of `<button class="lwp-pdp-chip">`
  buttons (one per option)
- Wires button click → triggers the underlying `<select>`'s `change`
  event so WC's variation logic still fires (price update, gallery
  swap, in-stock check)
- Marks the selected button with `.is-on`

Plus the `.lwp-pdp-chip` CSS to match (border 1px line, hover
primary, on-state primary border + #fdfaef bg).

#### 6. Wishlist plugin override (TI WooCommerce Wishlist)

Most-used free wishlist plugin: `ti-woocommerce-wishlist`. Override at
`woocommerce/tinvwl/wishlist-default.php` matching reference
`Tapadum-Wishlist.html`:

- 4-col grid of wish cards
- Each card: heart icon top-right (remove on click) / square image /
  category eyebrow / Playfair name / italic maker / gold price / black
  pill "Add to cart" CTA below

Use the plugin's variable names + hooks so its actions stay wired.

---

### 🟨 P2 — Nice to have (skippable for v1 release)

#### 7. Customizer panel registration (PHP)

WP Customizer panels for operator-tunable settings:

- **Brand panel**: logo upload (auto-fallback to text wordmark),
  primary / accent / sale color override, Cormorant vs alternative
  serif toggle
- **Topbar panel**: location text, phone, email, promo text
- **Social panel**: Instagram / Facebook / YouTube / WhatsApp URLs
- **Login panel**: register card on/off, brand card on/off, blurb
  override, featured product picker
- **Footer panel**: newsletter shortcode (Mailchimp / MailPoet / CF7),
  legal text override

Output: a single PHP class (e.g., `LuwiPress_Gold_Customizer`)
extending `WP_Customize_Manager` patterns. Register all sections,
settings, controls. ~200–400 lines of well-commented PHP.

#### 8. Theme logo mark (SVG)

Page loader currently uses a generic spin SVG arc. A theme-level brand
mark (e.g., Cormorant italic "L" with a tapered chevron, or a stylised
"LG" monogram) would make the loader feel proprietary.

Constraints:
- **60 × 60 viewBox**
- **Single color** — must use `currentColor` so the loader can switch
  between gold / dark contexts
- **No external dependencies** (no `<image>` tag, no embedded fonts)

Deliver as **inline `<svg>...</svg>`** I can paste into `frontend.js`.

#### 9. PDP gallery thumb-strip polish (CSS)

WooCommerce native gallery uses flexslider. The thumb strip below the
main image needs the gold `.on` border state from reference
`.sp-thumbs div.on`.

Need: **CSS-only override** of WooCommerce's native flexslider thumbs:

```css
.flex-control-thumbs li img.flex-active { border: 2px solid var(--primary); /* etc. */ }
```

Plus thumb sizing (4-up grid below the main image, 1:1 aspect, 4px
gap, 6px radius).

#### 10. PDP rating stars styling (CSS)

WC stars use a sprite font / CSS pseudo trick. Need: **CSS that
restyles `.star-rating` and adjacent count** to match reference
`.sp-rating .stars { color: var(--primary-light); letter-spacing: 1px }`
plus the count text in mono uppercase muted.

---

## Format — how to deliver

For every ask, deliver **one self-contained code block** that I can
copy-paste into the theme without modification:

- **PHP**: complete with `defined( 'ABSPATH' ) || exit;`, WC hooks,
  comments explaining what each block does
- **JS**: vanilla, IIFE-wrapped, respects `prefers-reduced-motion`
  like the existing `frontend.js`, no jQuery (except where WC itself
  emits jQuery events)
- **CSS**: scoped to `.lwp-*` classes, uses CSS variables from
  existing `tokens.css` / `widgets.css`, `!important` only when
  fighting plugin output
- **HTML / XML / JSON**: ready to import or drop in
- **SVG**: inline, no external refs, single color via `currentColor`

Naming convention: `inc/foo.php` / `assets/js/foo.js` /
`assets/css/foo.css` / `woocommerce/path/foo.php` /
`demo-content/foo.xml`.

---

## Don't deliver — out of design scope

- Real Tapadum photography (Tapadum content team)
- Real product / order / customer data (Tapadum content team)
- WPML translations (operator's content team via WPML String
  Translation)
- Actual social URLs / Mailchimp account / contact form provider IDs
- Server / hosting / CDN setup
- WooCommerce gateway configuration

---

## Want to see the current state?

The latest theme ZIP is at
`/releases/luwipress-gold-1.2.3.zip` — design tokens are unchanged
from your v1.1.0 kit so you have everything you need to deliver
against the existing system.

If anything in the asks above is unclear, the theme source is open —
read `widgets.css` and `tokens.css` for available CSS variables, read
`functions.php` to see how `inc/` files load.
