<?php
/**
 * Hero "Tour Search" — turns the static homepage hero booking bar (.bookbar)
 * into a real tour finder.
 *
 * The visitor picks an Experience (a bookable tour), a date and a guest count,
 * presses Go, and lands on that tour's product page with the date + guests
 * already filled in (booking.js reads `?fbd_date` & `?fbd_pax`), so only
 * checkout remains.
 *
 * Progressive enhancement: the static markup stays as a no-JS fallback; the
 * script upgrades it in place — no Elementor editing required. Self-gates to
 * the front page and only loads when at least one bookable tour exists.
 *
 * @package LuwiPress_Amber
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bookable tours for the hero dropdown: [ { label, url, min, max, def } ].
 *
 * A tour is any published product flagged `_fbd_is_tour = yes`. That flag (and
 * the `_fbd_pax_*` config meta) lives ONLY on the default-language base product
 * — WPML does not copy custom meta to translations — so we discover the base set
 * in the default language, then map each base id to the CURRENT request language
 * via `wpml_object_id`. Labels come from the translated product name; URLs are
 * resolved by `get_permalink()` in the current-language context (which yields the
 * localized `/{lang}/` permalink). Pax bounds are read from the base product so
 * they are reliable even when a translation lacks the config meta.
 *
 * Cached for ten minutes in a PER-LANGUAGE transient (the homepage hero runs on
 * every visit); busted on product save. Degrades to single-language when WPML is
 * absent (the `wpml_*` filters return null / no-op and we fall back to base ids).
 *
 * @return array
 */
function lwp_amber_hero_tours() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$current_lang = (string) apply_filters( 'wpml_current_language', null );
	$default_lang = (string) apply_filters( 'wpml_default_language', null );
	$lang_key     = '' !== $current_lang ? $current_lang : 'na';

	$transient_key = 'lwp_amber_hero_tours_' . $lang_key;
	$cached        = get_transient( $transient_key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	// Track per-language transient keys so the flusher can delete them all.
	$keys = get_option( 'lwp_amber_hero_tours_keys', array() );
	if ( ! is_array( $keys ) ) {
		$keys = array();
	}
	if ( ! in_array( $transient_key, $keys, true ) ) {
		$keys[] = $transient_key;
		update_option( 'lwp_amber_hero_tours_keys', $keys, false );
	}

	$tours = lwp_amber_hero_tours_build( $current_lang, $default_lang );

	set_transient( $transient_key, $tours, 10 * MINUTE_IN_SECONDS );
	return $tours;
}

/**
 * Build the localized tour list. Switches WPML to the default language to run the
 * `_fbd_is_tour` meta query (the flag only exists on base products), restores the
 * original language, then maps each base id to the current language for label +
 * permalink. Pax bounds come from the base product config.
 *
 * @param string $current_lang Current request language code ('' when no WPML).
 * @param string $default_lang Default site language code ('' when no WPML).
 * @return array
 */
function lwp_amber_hero_tours_build( $current_lang, $default_lang ) {
	$has_wpml = ( '' !== $current_lang && '' !== $default_lang );

	// 1) Discover base tours in the DEFAULT language (where the meta lives).
	$switched = false;
	if ( $has_wpml && $current_lang !== $default_lang ) {
		do_action( 'wpml_switch_language', $default_lang );
		$switched = true;
	}

	$products = wc_get_products( array(
		'status'     => 'publish',
		'limit'      => 60,
		'orderby'    => 'title',
		'order'      => 'ASC',
		'meta_key'   => '_fbd_is_tour', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value' => 'yes',          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'return'     => 'ids',
	) );

	$base_ids = array();
	foreach ( (array) $products as $pid ) {
		$pid = (int) $pid;
		if ( $pid > 0 ) {
			$base_ids[] = $pid;
		}
	}

	// Restore the request language before resolving permalinks (get_permalink
	// must run in the CURRENT language to produce the localized URL).
	if ( $switched ) {
		do_action( 'wpml_switch_language', $current_lang );
	}

	// 2) Map each base id to the current language and build the entry.
	$tours = array();
	foreach ( $base_ids as $base_id ) {
		// Pax bounds from the BASE product config (meta only on base product).
		$cfg = function_exists( 'lwp_amber_tour_config' ) ? lwp_amber_tour_config( $base_id ) : array();

		// Resolve the translated product id for the current language. With no
		// WPML (or no translation) this returns the base id unchanged.
		$target_id = $base_id;
		if ( $has_wpml ) {
			$mapped = apply_filters( 'wpml_object_id', $base_id, 'product', true, $current_lang );
			if ( $mapped ) {
				$target_id = (int) $mapped;
			}
		}

		$label = get_the_title( $target_id );
		$url   = get_permalink( $target_id );
		if ( '' === (string) $label || ! $url ) {
			continue;
		}

		$tours[] = array(
			'label' => $label,
			'url'   => $url,
			'min'   => isset( $cfg['pax_min'] ) ? max( 1, (int) $cfg['pax_min'] ) : 1,
			'max'   => isset( $cfg['pax_max'] ) ? max( 1, (int) $cfg['pax_max'] ) : 20,
			'def'   => isset( $cfg['pax_default'] ) ? max( 1, (int) $cfg['pax_default'] ) : 2,
		);
	}

	return $tours;
}

/**
 * Bust every per-language cached tour list when a product changes.
 */
function lwp_amber_hero_tours_flush() {
	$keys = get_option( 'lwp_amber_hero_tours_keys', array() );
	if ( is_array( $keys ) ) {
		foreach ( $keys as $key ) {
			delete_transient( (string) $key );
		}
	}
	// Drop the legacy non-language-keyed transient from before this rewrite.
	delete_transient( 'lwp_amber_hero_tours' );
	delete_option( 'lwp_amber_hero_tours_keys' );
}
add_action( 'save_post_product', 'lwp_amber_hero_tours_flush' );
add_action( 'deleted_post', 'lwp_amber_hero_tours_flush' );

/**
 * Resolve a results-page permalink in the CURRENT request language.
 *
 * The tabbed hero deep-links to type-specific results pages (slugs "flights",
 * "hotels", "tours"). `get_page_by_path()` finds the default-language page; we
 * map it to the current language via `wpml_object_id` so the returned permalink
 * carries the localized `/{lang}/` prefix. Returns '' when no such page exists
 * yet (hotels/tours results pages are pending a parallel plugin track) so the JS
 * can degrade that tab to a "coming soon" no-op instead of a broken URL.
 *
 * @param string $slug Page slug to resolve ("flights" | "hotels" | "tours").
 * @return string Permalink in the current language, or '' if the page is missing.
 */
function lwp_amber_hero_results_url( $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page ) {
		return '';
	}

	$page_id = (int) $page->ID;

	$current_lang = (string) apply_filters( 'wpml_current_language', null );
	if ( '' !== $current_lang ) {
		$mapped = apply_filters( 'wpml_object_id', $page_id, 'page', true, $current_lang );
		if ( $mapped ) {
			$page_id = (int) $mapped;
		}
	}

	$url = get_permalink( $page_id );
	return $url ? (string) $url : '';
}

/**
 * Enqueue the hero search enhancer on the front page only, and only when there
 * is at least one bookable tour to offer.
 */
add_action( 'wp_enqueue_scripts', function () {
	$rel = '/assets/js/hero-search.js';
	$abs = get_template_directory() . $rel;
	$cb  = LUWIPRESS_AMBER_VERSION . '.' . ( file_exists( $abs ) ? filemtime( $abs ) : '0' );

	// REGISTER (not just enqueue) so the Tour Hero Elementor widget can pull it on
	// any page via get_script_depends(); enqueue it directly on the front page so
	// the legacy HTML hero keeps working without the widget.
	wp_register_script(
		'luwipress-amber-hero-search',
		LUWIPRESS_AMBER_URI . $rel . '?cb=' . $cb,
		array(),
		null,
		true
	);

	$tours = lwp_amber_hero_tours();

	// Resolve a contact-page URL so the hero "Plan My Trip" button (which links to
	// a possibly-missing #contact anchor) has a real destination. Prefer a page
	// at /contact/, then any page built on the contact template, else home.
	$contact_url  = '';
	$contact_page = get_page_by_path( 'contact' );
	if ( ! $contact_page ) {
		$tpl_pages = get_pages( array(
			'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => 'page-contact.php',  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'number'     => 1,
		) );
		if ( ! empty( $tpl_pages ) ) {
			$contact_page = $tpl_pages[0];
		}
	}
	if ( $contact_page ) {
		$contact_url = get_permalink( $contact_page );
	}

	// fbd-connect REST base for the From/To airport autocomplete. The flight
	// plugin only localizes window.fbdConnect on its own widget pages, so the
	// homepage hero would otherwise have no REST base and the autocomplete would
	// degrade to a plain text field. Pass the base here so the hero finder works
	// wherever the bar renders — only when the flight plugin is active (else the
	// route doesn't exist). No nonce: /places is a public read and a nonce baked
	// into a cached homepage would go stale and trip WP's "Cookie check failed".
	$fbd_rest = defined( 'FBD_CONNECT_VERSION' ) ? esc_url_raw( rest_url( 'flybydeniz/v1' ) ) : '';

	wp_localize_script( 'luwipress-amber-hero-search', 'LWP_HERO', array(
		'tours'      => $tours,
		'contactUrl' => $contact_url,
		'rest'       => $fbd_rest,

		// Deep-link bases for each tab's results page, in the current language.
		// '' when the page does not exist yet → the JS degrades that tab to a
		// "coming soon" no-op rather than navigating to a broken URL.
		'urls'       => array(
			'flights' => lwp_amber_hero_results_url( 'flights' ),
			'hotels'  => lwp_amber_hero_results_url( 'hotels' ),
			'tours'   => lwp_amber_hero_results_url( 'tours' ),
		),

		'i18n'       => array(
			// Tabs.
			'flights'       => __( 'Flights', 'luwipress-amber' ),
			'hotels'        => __( 'Hotels', 'luwipress-amber' ),
			'tours'         => __( 'Tours', 'luwipress-amber' ),

			// Field labels.
			'from'          => __( 'From', 'luwipress-amber' ),
			'to'            => __( 'To', 'luwipress-amber' ),
			'depart'        => __( 'Depart', 'luwipress-amber' ),
			'return'        => __( 'Return', 'luwipress-amber' ),
			'travellers'    => __( 'Travellers', 'luwipress-amber' ),
			'cabin'         => __( 'Cabin', 'luwipress-amber' ),
			'destination'   => __( 'Destination', 'luwipress-amber' ),
			'checkin'       => __( 'Check-in', 'luwipress-amber' ),
			'checkout'      => __( 'Check-out', 'luwipress-amber' ),
			'guests'        => __( 'Guests', 'luwipress-amber' ),
			'rooms'         => __( 'Rooms', 'luwipress-amber' ),
			'date'          => __( 'Date', 'luwipress-amber' ),

			// Cabin option labels.
			'cabinEconomy'  => __( 'Economy', 'luwipress-amber' ),
			'cabinPremium'  => __( 'Premium economy', 'luwipress-amber' ),
			'cabinBusiness' => __( 'Business', 'luwipress-amber' ),
			'cabinFirst'    => __( 'First', 'luwipress-amber' ),

			// Submit buttons.
			'searchFlights' => __( 'Search flights', 'luwipress-amber' ),
			'searchHotels'  => __( 'Search hotels', 'luwipress-amber' ),
			'searchTours'   => __( 'Search tours', 'luwipress-amber' ),

			// Misc UI.
			'comingSoon'    => __( 'Coming soon', 'luwipress-amber' ),
			'iataHint'      => __( 'Pick an airport or enter a 3-letter code', 'luwipress-amber' ),

			// Date-picker chrome.
			'choose'        => __( 'Choose', 'luwipress-amber' ),
			'chooseDate'    => __( 'Choose a date', 'luwipress-amber' ),
			'today'         => __( 'Today', 'luwipress-amber' ),
			'clear'         => __( 'Clear', 'luwipress-amber' ),
			'prevMonth'     => __( 'Previous month', 'luwipress-amber' ),
			'nextMonth'     => __( 'Next month', 'luwipress-amber' ),

			// Stepper a11y.
			'increase'      => __( 'Increase', 'luwipress-amber' ),
			'decrease'      => __( 'Decrease', 'luwipress-amber' ),

			// Legacy dropdown affordances (kept for the V2 fallback shape).
			'scrollUp'      => __( 'Scroll up', 'luwipress-amber' ),
			'scrollDn'      => __( 'Scroll down', 'luwipress-amber' ),
		),
	) );

	// Front page: enqueue directly for the legacy hand-built HTML hero. The Tour
	// Hero widget enqueues it itself via get_script_depends() wherever it renders.
	if ( is_front_page() && ! empty( $tours ) ) {
		wp_enqueue_script( 'luwipress-amber-hero-search' );
	}
}, 20 );

// Keep the hero-search script out of LiteSpeed "Delay JS" whenever it loads.
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( 'luwipress-amber-hero-search' === $handle ) {
		$tag = str_replace( ' src=', ' data-no-optimize="1" data-no-defer="true" data-cfasync="false" src=', $tag );
	}
	return $tag;
}, 10, 2 );
