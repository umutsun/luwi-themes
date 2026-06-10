# LuwiPress Sapphire — Design Brief ("Midnight Sapphire")

The canonical art-direction spec for the 6th member of the LuwiPress
jewel-coded theme family. This is the north star every template, section
and component is built against. Authored in-house (no external design
hand-off); forked from the Onyx fork-tree and re-skinned.

---

## 1. Identity & positioning

| | |
|---|---|
| **Codename** | Sapphire / "Midnight Sapphire" |
| **Vertical** | Tech · SaaS · digital products · developer tools |
| **One-liner** | *Trust, shipped.* The blue, innovation-forward member of the family. |
| **Family seat** | Gold = luxury retail · Emerald = B2B consulting · Amber = travel · Ruby = editorial/fashion · Onyx = real-estate · **Sapphire = SaaS/tech** |
| **Mood** | Confident, crisp, engineered. Electric but calm. Aurora-on-midnight, not neon-cyberpunk. |
| **Mode** | **Dark-first** (Midnight). Full parallel **light** mode ("Ice & Sapphire"), toggle persisted in `localStorage`, applied before first paint. |

WooCommerce role: products are **plans / licenses / seats** (Starter, Pro,
Studio). Pricing tiers are real WC products so checkout, coupons, renewals
and the LuwiPress companion plumbing all work unchanged.

---

## 2. Color system  (source of truth: `assets/css/sapphire.css` + `tokens.css`)

**Dark — Midnight (default):**

| Token | Value | Role |
|---|---|---|
| `--sapphire-900` | `#070B16` | page base (deep midnight navy) |
| `--sapphire-850` | `#0A1124` | recessed band |
| `--sapphire-800` | `#0E1730` | utility / footer band |
| `--surface` | `#121C36` | cards |
| `--surface-2` | `#18254A` | raised cards / popovers |
| `--accent` | `#2563EB` | electric sapphire — primary |
| `--accent-soft` | `#3B82F6` | hover / headline |
| `--accent-glow` | `#60A5FA` | focus rings, glow, hover ink |
| `--ink` | `#E8EDF7` | text (cool off-white) |
| `--muted` | `#93A1BC` | secondary text (slate) |
| `--faint` | `#5C6A86` | tertiary / captions |

**Light — Ice & Sapphire** (`[data-theme="light"]`): base `#F5F8FF`,
surface `#FFFFFF`, accent deepens to `#1D4ED8`, ink `#0B1220`,
muted `#475569`.

**Gradients / glow:**
- `--grad-cta` = `linear-gradient(135deg,#3B82F6,#2563EB 55%,#1D4ED8)` — every primary CTA.
- `--grad-hero` = radial sapphire aurora wash top-right of the hero.
- `--glow` = soft 1px sapphire ring + drop shadow for hero cards, pricing "popular" tier, focus.

**Rule:** never hardcode brand hex in markup — consume `var(--accent)` /
`var(--ink)` / `var(--surface)` etc. so the Customizer brand knob and the
dark/light flip both cascade. The accent is rebrandable from
**Customize → LuwiPress Sapphire → Brand** (bridged via `--primary`).

---

## 3. Typography

| Role | Family | Notes |
|---|---|---|
| Display / headings | **Space Grotesk** (400–700) | geometric, techy; tight `-0.02em` tracking on large sizes |
| Body / UI | **Inter** (300–800) | the SaaS workhorse |
| Eyebrows / code / numerics | **JetBrains Mono** (400–600) | uppercase micro-labels, version tags, stat numbers, code/terminal mocks |

Eyebrows: JetBrains Mono, ~12px, `letter-spacing:0.28em`, uppercase, accent
color, with a short accent rule before the text. Headlines: Space Grotesk,
clamp-scaled, weight 600–700.

---

## 4. Shape · motion · texture

- **Radius:** `--radius-sm 8px` / `--radius 12px` / `--radius-lg 18px` / `--radius-pill 999px`. SaaS-rounded — no sharp-corner luxury language here.
- **Borders:** 1px hairlines (`--hair` sapphire-tinted, `--hair-soft` neutral). Cards = `--surface` + `--hair-soft` border + subtle `--glow` on hover.
- **Motion:** scroll-reveal (existing `sapphire.js`), `cubic-bezier(.2,.7,.2,1)`, 0.45s. Buttons sweep the gradient on hover. Respect `prefers-reduced-motion`.
- **Texture:** very subtle grain (`--grain-op 0.32` dark / `0.10` light), aurora radial wash behind the hero, faint dotted/grid background for feature bands. Keep it clean — restraint over noise.

---

## 5. Page architecture

### Homepage (`front-page.php`) — SaaS section order
1. **Hero (split)** — eyebrow + Space Grotesk headline + sub + dual CTA ("Start free" primary / "Live demo" ghost) on the left; **product UI mock / app screenshot** in a glowing browser/terminal frame on the right, aurora wash behind. Trust microcopy under CTAs ("No card · 14-day trial").
2. **Logo / trust strip** — "Trusted by teams at…" monochrome logo row.
3. **Feature grid** — 3×2 cards, each: icon tile, title, one-line benefit. Hover = sapphire glow + lift.
4. **Showcase / how-it-works** — alternating media+text rows (2–3), with a code/terminal mock in one.
5. **Integrations wall** — grid of integration tiles (logos in rounded squares) + "and 100+ more".
6. **Stats band** — animated count-up KPIs (JetBrains Mono numerics) on a recessed `--sapphire-850` band.
7. **Pricing** — 3 tiers (Starter / Pro / Studio), middle = "Most popular" with `--glow` + gradient top border; monthly/annual toggle; feature checklist; CTA per tier → WC product.
8. **Testimonials** — carousel of quote cards + avatar + role, Review schema.
9. **Changelog / roadmap timeline** — JetBrains Mono version tags down a sapphire spine ("Shipped / In progress / Planned" status pills).
10. **FAQ** — accordion, FAQPage JSON-LD.
11. **CTA band** — full-width sapphire-gradient or aurora band, big headline + "Start free".

### Other templates (re-mapped from the Onyx real-estate fork)
| Onyx template (real-estate) | Sapphire template (SaaS) |
|---|---|
| Listings (filter sidebar, price range, beds) | **Pricing / Plans compare** — full feature-matrix table + tier cards |
| Single Property (mortgage calc, amenities, payment plan) | **Single Plan / Product** — feature list, what's included, FAQ, "Start trial" sticky CTA, SoftwareApplication schema |
| Gallery (filtered photos) | **Integrations directory** (or **Templates/Showcase gallery**) — category-chip filtered tile grid |
| Mortgage calculator | **ROI / pricing calculator** (seats × price, annual savings) |
| About / team | **About / team** — story, principles, team grid (keep) |
| Journal / single post | **Blog / Docs-style article** (keep editorial column; mono code blocks) |
| Contact + map | **Contact / "Talk to sales"** — working form + calendar/booking CTA |

### Chrome
- **Utility bar** — status/changelog link + language switch (WPML/Polylang-aware) on the right; short value-prop or "v2.4 shipped" pill on the left.
- **Sticky header** — logo, **Product mega menu** (Features / Integrations / Use-cases + a featured card), Pricing, Docs/Blog, a search icon, dark/light toggle, and a **"Start free"** gradient CTA (SaaS headers DO carry a header CTA — unlike Onyx).
- **Mobile drawer** — nav + "Start free" + theme/lang toggles.
- **Footer** — product columns (Product / Developers / Company / Legal), newsletter ("Ship notes"), social, status-page link.
- **Customer chat** — defers to LuwiPress core; theme FAB hides when core is active.

---

## 6. Components

- **Buttons:** `.btn-gold` = primary sapphire-gradient pill→`var(--radius)`, white ink, glow shadow. `.btn-ghost` = hairline border → sapphire on hover.
- **Feature card / pricing card / integration tile:** `--surface`, `--hair-soft` border, `--radius`, hover `--glow`.
- **Browser/terminal mock:** rounded frame, three traffic-light dots, mono content, sapphire caret/selection — the recurring "product" motif.
- **Eyebrow + accent rule**, **status pills** (shipped=accent, planned=muted), **version tags** (mono).
- **Stat counter:** big mono numeral + label, scroll-triggered count-up.

---

## 7. SEO / AEO

- `SoftwareApplication` JSON-LD on the single-plan/product template (name, offers/price, aggregateRating).
- `FAQPage` JSON-LD on homepage + FAQ sections.
- `Organization` + `BreadcrumbList` site-wide (inherited from the fork).

---

## 8. Build status (as of this commit)

**DONE — foundation / substrate:**
- Forked Onyx → `luwipress-sapphire-elementor` (178 files), full `onyx→sapphire` rename, valid + activatable, PHP lints clean, no BOM.
- **Color system** fully re-skinned to Midnight Sapphire (dark) + Ice & Sapphire (light) in `sapphire.css` + the `tokens.css` brand bridge (accent-only bridge so light mode flips correctly). Warm Onyx neutrals/veils → cool midnight; gold→sapphire across all section/product/pages CSS.
- **Typography** swapped to Space Grotesk + Inter + JetBrains Mono (enqueue URL, `theme.json`, design tokens).
- Customizer brand defaults + `theme.json` palette/fonts + `style.css` header all re-branded for Sapphire/SaaS.

**PENDING — design build wave (this brief is the spec):**
- Rebuild `front-page.php` + `sapphire-sections*.css` to the SaaS section order in §5 (currently still the Onyx real-estate section markup, sapphire-colored).
- Re-map the real-estate templates (listings/single-property/gallery/calculator) to the SaaS equivalents in §5.
- Header CTA + Product mega menu; footer product columns.
- Demo content (plans/features/integrations/changelog) + activation import.
- `screenshot.png` (1200×900 homepage hero) — needs a live render to capture.
- `README.md` / `FEATURES.md` SaaS rewrite.

---

## Conventions (inherited — do not break)
- Consume CSS tokens, never hardcode brand hex. `.lwp-*` / theme classes.
- No BOM in PHP. `defined('ABSPATH')||exit;` guard. WC = soft dep (guard `wc_*`).
- Customer chat / AI search / KG widgets / maintenance suite / ecosystem dashboard / onboarding wizard come from the shared fork — keep wired.
- Light mode must stay readable: never re-pin `--ink`/base in a flat `:root` after the design system.
