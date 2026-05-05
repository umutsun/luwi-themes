# LuwiPress Gold

**The official theme of the LuwiPress ecosystem** — an AI-powered, Elementor-ready
WordPress theme designed for ethnic-instrument and craft-driven storefronts.
Black topbar, Playfair Display + Inter + JetBrains Mono pairing, gold accents,
V32-style info-bar, sticky header, mega menu, slide-in cart drawer, full
WooCommerce checkout suite + My Account dashboard.

Tightly integrated with the **LuwiPress plugin**: surfaces AI product search,
sticky customer chat, and Knowledge-Graph-curated related products straight
from the storefront. Every friendly plugin you activate (WPML, Rank Math,
Yoast, WP Rocket, FluentCRM, Mailchimp for WP, …) amplifies a feature visible
on the page — no settings to reconcile.

Ships with a **12-JSON Elementor Kit** that imports every page in one click:
homepage, shop, single product, about, master profile, journal, contact, 404,
plus the global header / footer / animations.

## Requirements

| Component | Version | Notes |
|---|---|---|
| WordPress | 6.4+ | |
| PHP | 8.1+ | 8.2 recommended |
| **LuwiPress plugin** | latest | **Required** — AI engine, chat, knowledge graph, plugin detector |
| Elementor | 3.18+ | **Free is enough** — no Pro required |
| WooCommerce | latest | Strongly recommended — cart, checkout, single product, archive |

`Requires Plugins: elementor, luwipress` is declared in `style.css`, so on
WordPress 6.5+ the theme refuses to activate when either plugin is missing.
WooCommerce is a soft dependency — the theme runs without it, but the bundled
Kit's shop / single-product / cart / checkout / account templates expect it.

## What ships

```
luwipress-gold/
├── style.css                    # WP theme metadata
├── theme.json                   # Gutenberg palette + fonts
├── functions.php                # bootstrap (loads inc/*)
├── header.php / footer.php      # minimal — Elementor Theme Builder takes over
├── page.php / index.php / single.php / 404.php / search.php   # fallbacks
├── inc/
│   ├── setup.php                # theme support + nav menus + image sizes
│   ├── enqueue.php              # font + tokens + woo-overrides + frontend.js
│   └── elementor-compat.php    # admin notice, body classes, canvas auto-template
├── assets/
│   ├── css/
│   │   ├── tokens.css           # CSS custom properties (--primary, --serif, …)
│   │   └── woo-overrides.css    # WC loop card → Gold ladder + sale badge
│   └── js/
│       └── frontend.js          # page loader + scroll reveal + cart bump
├── elementor-kit/               # ← deploy these via Tools → Kit Library
│   ├── kit.json                 # global colors, fonts, button styles
│   ├── manifest.json
│   ├── README.md                # operator import order
│   ├── 00-animations.json       # site-wide loader + scroll reveal markup
│   ├── 01-header.json           # Theme Builder header (sticky, mega menu)
│   ├── 02-footer.json           # Theme Builder footer (4 columns)
│   ├── 03-homepage.json         # hero, info-bar, products, atelier, journal, …
│   ├── 04-shop.json             # filtered catalogue
│   ├── 05-single-product.json   # gallery + buy + tabs
│   ├── 06-about.json            # atelier intro + dark-band timeline
│   ├── 07-master-profile.json   # luthier profile (portrait + stats + CTAs)
│   ├── 08-journal.json          # editorial / blog
│   ├── 09-contact.json          # 3 columns + form
│   └── 10-404.json
├── README.md (this file)
├── LICENSE                      # GPL-2.0-or-later
└── .gitignore
```

## Install

### Quick path — using the bundled Kit

1. Upload the theme: WP Admin → Appearance → Themes → Add New → Upload Theme
   → upload the `luwipress-gold` folder as a `.zip` → **Activate**.
2. Install the dependencies (Plugins → Add New): **Elementor**,
   **ElementsKit Lite**, **WooCommerce**.
3. **Import the Kit**:
   - WP Admin → Elementor → Tools → Kit Library → **Import Kit**
   - Choose `wp-content/themes/luwipress-gold/elementor-kit/kit.json` → Import
4. Import the page templates one by one (Templates → Theme Builder for
   header/footer/single-product/404; Pages → individual pages for the rest).
   See [`elementor-kit/README.md`](elementor-kit/README.md) for the full order.
5. Settings → Reading → Front page = `Home`, Posts page = `Journal`.
6. Replace placeholder gradients with real photography.

### Manual path — without the Kit

The theme works as a stripped-down fallback even without the Kit imported —
it ships a tiny header/footer/list/single layout so the site is browseable
during setup. The fallback uses the same tokens (gold + cream + black) and
respects the WC loop overrides.

## Design tokens (single source: `assets/css/tokens.css`)

```css
--primary       #735c00    /* gold */
--primary-light #D4AF37    /* gold-bright (italic emphasis) */
--accent        #545e76    /* slate */
--ink           #1b1c1c    /* primary text */
--ink-soft      #4d4635
--muted         #7f7663
--bg            #fcf9f8    /* cream background */
--bg-alt        #f6f3f2
--card          #ffffff
--line          #e8e2d3
--sale          #a33b3e    /* badges, errors */
--black         #0c0c0c    /* topbar / footer / info-bar */
--icon-red      #d83131    /* info-bar icon circles */

--serif Playfair Display, Georgia, serif
--sans  Inter, -apple-system, sans-serif
--mono  JetBrains Mono, ui-monospace, monospace

--content-w 1372px
```

The same palette is exposed to the block editor through `theme.json` as
`var(--wp--preset--color--*)`.

## WooCommerce

`assets/css/woo-overrides.css` restyles the default `ul.products li.product`
loop card to match the Gold ladder:

- Sale badge (top-left, sale red `#a33b3e`)
- Italic gold price (`Playfair Display`, regular + struck-through old price)
- Pill add-to-cart button (`Ink` background, gold on hover, cart bump JS hook)
- Hidden star rating (set typography in Elementor instead)

It loads only when WooCommerce is active.

The Elementor Kit's `04-shop.json` uses ElementsKit's WooCommerce widget for
the actual archive layout; the override CSS sweetens the card edges that
neither Elementor nor ElementsKit fully control.

## Animation layer

`assets/js/frontend.js`:

- **Page loader**: gold "T" mark + 200 px progress bar, fades on `window.load`,
  hard cap at 4 s.
- **Scroll reveal**: any element with `data-lwp-reveal` (or directional variant
  `data-lwp-reveal="left|right|scale"`) fades in once it enters the viewport.
- **Stagger grid**: parent with `data-lwp-stagger` auto-staggers children at
  60 ms intervals.
- **Cart bump**: triggers a `.42 s` scale-bump on any element with
  `[data-lwp-cart-toggle]`, `.luwipress-gold-cart`, `.menu-cart-trigger`, or
  `.elementor-menu-cart__toggle` whenever a WooCommerce
  `added_to_cart`/`removed_from_cart`/`wc_fragments_refreshed` fires.

Everything no-ops when `prefers-reduced-motion: reduce`.

## Filters / hooks

| Filter | What it does |
|---|---|
| `luwipress_gold_kit_path` | Override path to the bundled Kit folder |
| `luwipress_gold_kit_files()` | PHP helper returning all bundled JSON paths |

The Kit JSONs themselves expose the entire homepage / shop / about / contact
layout via Elementor's settings — no PHP filters needed; edit visually.

## AI surface (1.3.0+)

The theme reads from LuwiPress's plugin contract directly — no HTTP round-trips
when the plugin is in-process. Three storefront features ship as first-class
parts of the theme:

| Feature | Where | Backed by |
|---|---|---|
| **AI search suggestions** | Search overlay (`⌕` icon in the header) | `LuwiPress_AI_Engine::dispatch('customer-chat', …)` with `json_mode`, cached 30 min per query |
| **Sticky customer chat** | Bottom-right floating bubble + slide-up panel | `POST /wp-json/luwipress/v1/chat/message` (rate-limited per IP); session id in localStorage |
| **KG-related rail** | Single-product page, below the buy column | Direct PHP query: same `pa_master` / `pa_luthier` term first, then primary `product_cat`. Cached 1 h per product. |

All three self-gate: when LuwiPress is missing or the chat module is off, the
relevant surface stays dark. Costs visible in the LuwiPress token-tracker.

Turn the chat on at: WP Admin → LuwiPress → Customer Chat → enable + set
greeting / tone / daily budget.

## Ecosystem dashboard

`Appearance → LuwiPress Gold` shows a single panel that reads from LuwiPress's
plugin detector + AI engine + chat module + token tracker. The operator sees
one story — theme + plugin + every friendly plugin — instead of bouncing
between two admin sections.

## Friendly plugins

LuwiPress brings an ecosystem to the world of popular WordPress plugins. The
theme amplifies each detected plugin's presence:

- **WPML / Polylang** — topbar language pill auto-detects active languages;
  hreflang tags emitted in `<head>` when the SEO plugin doesn't already do it.
- **Rank Math / Yoast** — theme breadcrumb yields to the SEO plugin's
  rendered breadcrumb (schema attribution + admin-controlled separators).
- **WooCommerce** — cart drawer, account popover, sale-save badge, stock pill,
  master/maker eyebrow on single products.
- **Elementor** — Theme Builder header/footer locations honored when the
  operator builds custom templates.
- **Mailchimp for WP / MailPoet / Contact Form 7 / WPForms / FluentForms** —
  Customizer dropdown picks which shortcode renders in the footer newsletter
  slot.
- **WP Rocket / LiteSpeed Cache** — exclude `frontend.js` and `ai-surface.js`
  from delay-JS so the loader never traps the page; AI surface REST endpoints
  are marked no-store at the plugin layer.

## Source

- Design exploration → Claude Design (HTML / CSS / JS prototypes)
- Prototypes: `Tapadum Homepage Gold v2.html` + 22 alt pages
- Elementor Kit handoff bundle (this folder)
- Provenance chat saved alongside the prototypes

## Development

The theme is part of the [umutsun/luwi-themes](https://github.com/umutsun/luwi-themes)
multi-theme repo. Sister themes: `luwi-elementor`, `luwi-emerald`, `luwi-gold`,
`luwi-ruby`, `stitch-3d-minimalist` — each one is a different palette/feel
applied to the same global structure.

Bug reports / feature requests → GitHub Issues.

## License

GPL-2.0-or-later. See [`LICENSE`](LICENSE).
