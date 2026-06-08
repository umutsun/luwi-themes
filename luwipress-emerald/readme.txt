=== LuwiPress Emerald ===
Contributors: luwipress
Tags: e-commerce, blog, news, woocommerce, elementor, custom-logo, custom-menu, custom-colors, full-width-template, footer-widgets, theme-options, threaded-comments, translation-ready, rtl-language-support, sticky-header, mega-menu
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

LuwiPress-powered, Elementor-ready consulting and professional services theme. Editorial typography on Inter, ledger-precise numerics on JetBrains Mono, emerald jewel palette, sticky shrinking header, slide-in cart drawer, deep-ink CTA band.

== Description ==

Sister theme to LuwiPress Gold, tuned for B2B engagements, agencies, and knowledge work rather than catalog-heavy retail. Ships with:

* Inter (sans) + JetBrains Mono (numerics) typography pair
* Slate neutrals + emerald jewel green built around #047857 primary — fully overrideable from Customizer → Brand
* 1280px container, 12-step spacing scale, three shadow tiers
* Sticky header that shrinks on scroll, with WC cart drawer overlay
* Mobile left-slide nav drawer
* Editorial single post layout with reading-time estimate
* Insights/journal card grid for archives
* Full Customizer panel ported from Gold — Brand (10 color tokens), Topbar, Header, Footer (socials + newsletter), Animation, Performance
* WPML / Polylang language pill in the topbar
* WooCommerce mini-cart drawer with AJAX-refreshing count and subtotal
* Reveal-on-scroll motion with `prefers-reduced-motion` fallback
* Elementor + Elementor Pro Theme Builder support, plus a Canvas full-bleed page template

== Companion plumbing ==

When the LuwiPress core plugin is installed, the theme inherits the same companion stack as Gold:

* AI product / article search modal triggered from the header
* Sticky customer chat shell
* Knowledge-Graph-curated related content rail
* Onboarding wizard (multi-step setup that detects friendly plugins + applies the right defaults)
* 23-tool maintenance suite registered into LuwiPress Theme Bridge (canonical / hreflang / redirect-chain / slug-collision / language-drift / orphan-media / unwanted-landing-pages / subcategory-template-parity / WC template assignment / WPML term repair / sitemap parity / and more)
* Ecosystem dashboard surfacing version status across LuwiPress core + companions
* Featured Products registry with admin-bar one-click toggle
* Server-side page loader (no flash-of-content-before-loader)
* Slug-collision shim (auto-redirects /<hub>/ → /product-category/<hub>/ when both exist)

== Frequently Asked Questions ==

= Where do the 23 reference page designs live? =

They ship as a design system bundle in the project repo under `temp/Luwipress Emerald Elementor Theme/pages/`. They are HTML references for Elementor authoring — the theme renders WordPress / WooCommerce content through the standard template hierarchy and pulls visual primitives from `assets/css/tokens.css`.

= Does this replace LuwiPress Gold? =

No. Gold remains the music / craft-retail focused theme. Emerald is the sister theme for consulting, agencies, services, and editorial-led B2B sites. Both inherit the same LuwiPress plugin / WebMCP / Marketplace Sync / Open Claw ecosystem.

= Can I change the emerald-green primary to something else? =

Yes. Appearance → Customize → LuwiPress Emerald → Brand surfaces 10 color tokens — primary, primary hover, primary soft, accent, sale, success, ink, page background, surface alt, topbar/CTA-band black. Set each from the Color Picker; the theme writes the changes back as `--primary` etc. CSS variable overrides in `<head>`, so every component that uses the token shifts in one go.

== Changelog ==

= 1.0.0 =
* Initial release. Forked end-to-end from the LuwiPress Gold backbone (wizard, theme bridge, 23-tool maintenance suite, ecosystem dashboard, AI surface, featured-products registry, mega-menu admin, footer enhancements, page loader, smart filters, slug-collision shim, WC fallbacks, custom Elementor widgets), re-skinned with the Acme/Emerald design system.
* Visual layer: Inter + JetBrains Mono, emerald-700 primary (`#047857`), 12-step spacing scale, three shadow tiers, sticky shrinking header, slide-in cart drawer, deep-ink CTA band.
* Customizer Brand section flips palette defaults from Gold (`#735c00`) to Emerald (`#047857`). Operator can pick any primary color and the change propagates via `--primary` CSS variable override.
* Editor color palette + theme.json updated for Emerald.
* Bundled chrome: topbar, sticky shrinking header, 4-column footer, mobile nav drawer, WC cart drawer.
* Bundled templates: index, page, single, archive, search, 404, comments, canvas.
