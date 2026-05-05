# Theme Screenshot — How to Capture

WordPress requires `screenshot.png` at the theme root for the
**Appearance → Themes** preview tile and CodeCanyon submission. Generic
placeholder is what shows now until this file is dropped in.

## Required spec
- **File**: `screenshot.png` (exactly this name, lowercase)
- **Location**: `themes/luwipress-gold/screenshot.png`
- **Dimensions**: 1200 × 900 px (4:3 ratio)
- **Max file size**: 1 MB (recommend ≤ 600 KB)
- **Format**: PNG (preferred) or JPG

## Recommended capture flow

1. Open the live homepage in Chrome (`https://new.tapadum.com/`)
2. DevTools → Toggle Device Toolbar → set viewport to **1372 × 900** (matches
   the theme's content max-width)
3. Wait for loader + scroll-reveal animations to settle
4. DevTools → ⋮ menu → **Capture screenshot** (full visible viewport)
5. Crop the result to **1200 × 900** in any image editor (Preview, GIMP,
   Photopea, Figma)
6. Save as `screenshot.png` and drop into the theme root

## What to capture

The screenshot shows in WP admin theme tile + CodeCanyon listing — make
the first impression count:

- **Frame on the hero section** so the topbar + sticky header + Tapadum
  brand wordmark + mega menu + hero italic-gold headline + image card are
  all visible
- **Avoid placeholder gradient blocks** — make sure real photography is
  loaded first (otherwise the screenshot shows brown gradient squares)
- **Hide cookie banner + chat widget** before capturing (use DevTools
  → element panel → delete the node temporarily)

## Optional alternative — CodeCanyon preview

For CodeCanyon submission you also want a **2200 × 1700 px** preview
image showcasing more of the design system. That's a separate file
(usually `preview.png` in the submission package, not in the theme
itself).
