# LuwiPress Amber — Tour Booking module

A lightweight WooCommerce booking layer for travel agencies. **A tour is a
WooCommerce product.** No external booking plugin required.

## Phase 2A (shipped)

End-to-end "book a tour → voucher" slice + a browsable tour archive.

### WooCommerce data layer — `inc/booking/`
| File | Responsibility |
|------|----------------|
| `bootstrap.php` | HPOS declare + load order (loaded from `functions.php` inside the `class_exists('WooCommerce')` block). |
| `helpers.php` | `lwp_amber_tour_config()`, `lwp_amber_is_tour()`, sanitizers, floating (no-TZ) date parse, order accessors. |
| `class-tour-product.php` | Product **"Tour / Booking"** data tab (mark bookable + duration, pax range, pickup, deposit, time slots, add-ons). Per-person price = the WC price. |
| `cart.php` | Shared `.book-box` renderer + cart pipeline: `add_cart_item_data` → session → `before_calculate_totals` (price = per_person × pax + add-ons) → `get_item_data` → line-item meta. Qty stays 1; pax baked into unit price. |
| `checkout.php` | "Trip & pickup details" block → order meta (`_fbd_pickup_from`/`_pickup_time`/`_flight_no`/`_dropoff`/`_notes`). |
| `class-dispatch-metabox.php` | Admin **Dispatch / Voucher** order meta box (HPOS + legacy): driver/mobile/vehicle/plate + "Confirmed" flag. Fires `lwp_amber_voucher_confirmed`. |
| `voucher.php` | `lwp_amber_render_voucher()` → the `.voucher` card. Surfaces: My Account order view, customer email, print endpoint (`?fbd_voucher=ID&fbd_key=KEY`). |
| `ics.php` | `.ics` calendar download (`?fbd_ics=ID&item=LINE&fbd_key=KEY`) — floating wall-clock, Dubai-local times never shift. |
| `schema-trip.php` | **TouristTrip** JSON-LD via the core LuwiPress Schema Registry (`luwipress_schema_registry_init`). Auto-builds from product booking meta; no-ops without the core plugin. |

### Elementor widgets — `inc/widgets/lib/`
`Tour Booking Box`, `Tour Grid`, `Tour Filters`, `Tour Toolbar`, `Activity Cards`
(registered in `inc/widgets/loader.php`, category **LuwiPress Amber**).

### JS — `assets/js/`
`booking.js` (calendar date-picker + pax stepper + live total + date-required
guard, scoped per `.book-box`) · `tours.js` (category/duration/price filter +
sort, per `data-tours-group`). Registered in `inc/enqueue.php`, pulled by the
widgets via `get_script_depends()`, excluded from LiteSpeed JS-defer.

### Meta key map (theme-agnostic `_fbd_*`)
- **Product**: `_fbd_is_tour`, `_fbd_duration`, `_fbd_duration_bucket`, `_fbd_pax_min/_max/_default`, `_fbd_pickup_included`, `_fbd_deposit_pct`, `_fbd_cancellation`, `_fbd_time_slots`, `_fbd_addons`.
- **Order line item**: `_fbd_tour_date`, `_fbd_pax`, `_fbd_time_slot`, `_fbd_per_person`, `_fbd_addons_json`.
- **Order**: `_fbd_pickup_from`, `_fbd_pickup_time`, `_fbd_flight_no`, `_fbd_dropoff`, `_fbd_notes`, `_fbd_driver_name`, `_fbd_driver_mobile`, `_fbd_vehicle`, `_fbd_plate`, `_fbd_voucher_ready`.

## How to test (on a live WC site)
1. Edit a product → **Tour / Booking** tab → tick **Bookable tour**, set duration / guests / add-ons → Save.
2. Build a Single Product Elementor template (or use the native PDP) → drop **Tour Booking Box** → pick a date, set guests, add an add-on → **Reserve**.
3. Checkout → fill **Trip & pickup details** → place order.
4. WP Admin → the order → **Dispatch / Voucher** box → set driver/vehicle, tick **Confirmed** → Update.
5. My Account → the order → voucher renders with the driver block + **Add to calendar (.ics)** + **Print**. Customer email carries the voucher.
6. View the tour product source → confirm a `TouristTrip` JSON-LD block (needs the core LuwiPress Schema Registry active).
7. Build a Tour Packages page: **Tour Toolbar** + **Tour Filters** + **Tour Grid** (same *Filter group*). Filter/sort updates live.

## Roadmap (not yet built)

**Phase 2B — detail-page content widgets + booking polish**
- Detail widgets (pure-markup repeaters, CSS already ships): Quick Facts (`.facts`), Highlights (`.highlights`), Itinerary Timeline (`.timeline`), Included/Not-included (`.includes`), Partners (`.partner-grid`), Hero Booking Bar (`.bookbar`), Voucher display widget.
- Add-ons UX polish + 30% **deposit** option (negative cart fee).
- Inline-styled email voucher variant.

**Phase 2C — advanced**
- WhatsApp "offline" payment gateway (order pending + WA payment link).
- Per-line pickup for multi-tour carts.
- `lwp_amber_voucher_confirmed` auto-resend consumer.
- **Services CPT** (Visa / Transport / Tour Tickets / Excursions / MICE) via the core CPT Engine + Service schema + single/archive templates.

## Notes / edge cases
- **HPOS** declared; all order I/O via WC CRUD.
- **Variable products**: booking config on the parent; per-person price from the chosen variation at add-time.
- **Cart key**: WC hashes the `fbd` snapshot → identical bookings merge, different dates split.
- **WPML**: product meta translated per sibling (schema + voucher follow language); order meta never translated.
- **Public links** (print/.ics): authorized by owning user **or** the order_key secret.
