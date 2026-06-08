# LuwiPress Amber — Theme Kit · Installation Guide

A complete editorial WooCommerce theme kit. Built for **Tapadum** (hand-crafted world instruments), but designed as a standalone, drop-in Elementor theme — no parent theme dependency required beyond a vanilla install. Includes page loader, scroll-reveal, skeleton placeholders and hover micro-interactions out of the box.

> **Stack:** WordPress + Elementor + ElementsKit Lite + WooCommerce. Works with **any** Elementor-compatible base theme (Hello Elementor, Astra, GeneratePress, Kadence, or your own child theme).

---

## 0 · Prerequisites

1. **WordPress 6.4+**
2. **Elementor 3.18+** (free is enough — no Pro required)
3. **ElementsKit Lite** — header builder, footer builder, mega menu
4. **WooCommerce** — for the `[products]` shortcodes and Single Product template
5. Any Elementor-compatible base theme (Hello Elementor recommended; Astra / Kadence work too)

Then create these placeholder pages so import targets exist:
`Home`, `Shop`, `About`, `Masters`, `Journal`, `Contact`.

---

## 1 · Install order

Import in the order below. Each file is self-contained; later files reference styles defined in earlier ones, so don't skip ahead.

| # | File | Where it goes | How |
|---|------|---------------|-----|
| 0 | `00-animations.json` | Inject once site-wide (see §3) — OR import as a Section on the homepage | Loader + scroll reveal + micro-interactions |
| 1 | `kit.json` | Elementor → Tools → Kit Library → **Import Kit** | Sets global colors, typography, button styles |
| 2 | `01-header.json` | Templates → Theme Builder → **Header** → Add New → Import | Set Display Conditions: *Entire Site* |
| 3 | `02-footer.json` | Templates → Theme Builder → **Footer** → Add New → Import | Set Display Conditions: *Entire Site* |
| 4 | `03-homepage.json` | Pages → `Home` → Edit with Elementor → ☰ → **Import Template** | Settings → Reading → set as *Front Page* |
| 5 | `04-shop.json` | Pages → `Shop` → Edit with Elementor → Import Template | Optional: WooCommerce → set as Shop page |
| 6 | `05-single-product.json` | Templates → Theme Builder → **Single Product** → Add New → Import | Display Conditions: *All Products* |
| 7 | `06-about.json` | Pages → `About` → Import Template | — |
| 8 | `07-master-profile.json` | Pages → save as **Master Profile** template | Use as base when creating each master's page |
| 9 | `08-journal.json` | Pages → `Journal` → Import Template | Set as Posts page in Reading Settings |
| 9a | `08a-journal-archive.json` | Templates → Theme Builder → **Archive** → Add New → Import | Display: All Archives (excl. product). Overrides default archive.php. |
| 9b | `08b-journal-single.json` | Templates → Theme Builder → **Single** → Add New → Single Post → Import | Display: All Posts. Requires `[tapadum_post_meta]` + `[tapadum_author_card]` shortcodes (see 08b-journal-single.json `_luwipress_notes`). |
| 10 | `09-contact.json` | Pages → `Contact` → Import Template | — |
| 11 | `10-404.json` | Templates → Theme Builder → **404** → Add New → Import | — |

---

## 2 · After-import checklist

- [ ] Replace placeholder gradient blocks with **real photography** — every `.tap-*-img`, `.tap-prod-img`, `.tap-master-img` div is a placeholder waiting for a photo.
- [ ] WooCommerce: create the **String / Percussions / Bowed / Winds** product categories. The homepage and shop sidebar reference them by slug.
- [ ] Create a **Luthier** product attribute (taxonomy: `pa_luthier`) and assign each product to its maker. Master profile pages filter by this attribute.
- [ ] Settings → Reading → set `Home` as **Front page**, `Journal` as **Posts page**.
- [ ] Set the WooCommerce account / cart / checkout pages.
- [ ] Wire the footer newsletter form to your provider (Mailchimp, MailPoet, ConvertKit shortcode, etc).

---

## 3 · Site-wide animation layer

`00-animations.json` is the LuwiPress Amber animation layer — a single HTML widget that owns the page loader, scroll reveal, skeleton placeholders and hover micro-interactions.

**Two ways to enable it:**

### Option A — Inject globally via your child theme's `functions.php` (recommended)

```php
add_action( 'wp_footer', function () {
    // Paste the inline <div class="lwp-loader">…</div> + <style>…</style> + <script>…</script>
    // block from 00-animations.json here, OR enqueue it as a separate file.
}, 5 );
```

Or save the markup as `lwp-animations.html` in the child theme and `include` it.

### Option B — Drop the widget on each page

Open the homepage in Elementor → ☰ → Import Template → `00-animations.json`. The widget self-contains; place it as the very first section. Repeat for any page where you want loader + reveal animations.

### What it adds

- **`#lwp-loader`** — fullscreen splash, hides 250ms after `window.load`, max 4s failsafe.
- **`[data-lwp-reveal]`** — fades + slides any element into view on scroll. Supports `data-lwp-reveal="left"|"right"|"scale"`.
- **`[data-lwp-stagger]`** — adds a staggered delay to each child (good for grids of cards).
- **`.lwp-skel`** — shimmer skeleton background for any block waiting on an image.
- **`.lwp-cart-bump`** — auto-bounces the cart icon on WooCommerce `added_to_cart`.
- **`.lwp-ulink`** — link with growing underline on hover.
- Respects `prefers-reduced-motion`.

### Adding reveal to existing sections

In Elementor → section → Advanced → CSS Classes, add e.g. `lwp-reveal` and add this once to the page Custom CSS:

```css
.lwp-reveal{opacity:0;transform:translateY(24px);transition:.8s}
.lwp-reveal.in{opacity:1;transform:none}
```

Or add `data-lwp-reveal` directly to any HTML widget's wrapper.

---

## 4 · Base theme integration (functions.php snippet)

Add this to your active theme's `functions.php` to load Google Fonts and global tokens once site-wide:

```php
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'luwipress-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap',
        [],
        null
    );
}, 5 );

add_action( 'wp_head', function () {
    echo '<style>
:root{
  --lwp-ink:#1A1612; --lwp-cream:#FAF6EE; --lwp-bone:#F2EAD3;
  --lwp-border:#E8DFC8; --lwp-amber:#9A7B3A; --lwp-amber-bright:#D4AF37;
  --lwp-rust:#C84B3A; --lwp-muted:#8b7f6a;
  --lwp-serif:"Cormorant Garamond",Georgia,serif;
  --lwp-sans:"Inter",-apple-system,sans-serif;
  --lwp-mono:"JetBrains Mono",ui-monospace,monospace;
}
body{font-family:var(--lwp-sans);color:var(--lwp-ink)}
h1,h2,h3,h4{font-family:var(--lwp-serif);font-weight:500;letter-spacing:-.5px}
</style>';
});
```

---

## 5 · Editing the design

Each section is built as Elementor **HTML widgets** with inline `<style>` blocks. To restyle:

- **Globally** — edit `kit.json` colors (or replace the inline `<style>` block in the widget you want to change).
- **One section** — open the page in Elementor, click the HTML widget, edit the `<style>` block at the bottom of the markup.
- **Swap to native widgets** — the design uses HTML widgets so it imports cleanly. To convert any block to native Elementor widgets (Heading, Image, Button…), copy the styles into Elementor's *Custom CSS* field.

---

## 6 · Files in this kit

```
elementor-kit/
├── README.md                 ← you are here
├── manifest.json             ← machine-readable index
├── kit.json                  ← global colors, fonts, button styles
├── 00-animations.json        ← LuwiPress Amber animation layer (loader + reveal + skeleton)
├── 01-header.json            ← sticky header with ElementsKit mega menu
├── 02-footer.json            ← 4-column footer with newsletter
├── 03-homepage.json          ← hero, infobar, products, categories, atelier, masters, YouTube, journal
├── 04-shop.json              ← filterable catalogue
├── 05-single-product.json    ← gallery + buy column + tabs
├── 06-about.json             ← story + timeline
├── 07-master-profile.json    ← single luthier page
├── 08-journal.json           ← blog index page (landing)
├── 08a-journal-archive.json  ← Theme Builder Archive: chips + featured + posts loop
├── 08b-journal-single.json   ← Theme Builder Single Post: cover + drop-cap + pull-quote + author + related
├── 09-contact.json           ← three-column contact + form + map
└── 10-404.json               ← brand-aware not-found page
```

---

**Visual reference:** open `Tapadum Homepage Gold v2.html` in this project — every Elementor section is a 1:1 port of the corresponding section there.
