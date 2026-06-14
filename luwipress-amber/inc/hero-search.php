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
 * A tour is any published product flagged `_fbd_is_tour = yes`. Cached for ten
 * minutes (the homepage hero runs on every visit); busted on product save.
 *
 * @return array
 */
function lwp_amber_hero_tours() {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$cached = get_transient( 'lwp_amber_hero_tours' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$products = wc_get_products( array(
		'status'     => 'publish',
		'limit'      => 60,
		'orderby'    => 'title',
		'order'      => 'ASC',
		'meta_key'   => '_fbd_is_tour', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value' => 'yes',          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'return'     => 'objects',
	) );

	$tours = array();
	foreach ( (array) $products as $p ) {
		if ( ! is_object( $p ) || ! method_exists( $p, 'get_id' ) ) {
			continue;
		}
		$cfg = function_exists( 'lwp_amber_tour_config' ) ? lwp_amber_tour_config( $p->get_id() ) : array();
		$tours[] = array(
			'label' => $p->get_name(),
			'url'   => get_permalink( $p->get_id() ),
			'min'   => isset( $cfg['pax_min'] ) ? max( 1, (int) $cfg['pax_min'] ) : 1,
			'max'   => isset( $cfg['pax_max'] ) ? max( 1, (int) $cfg['pax_max'] ) : 20,
			'def'   => isset( $cfg['pax_default'] ) ? max( 1, (int) $cfg['pax_default'] ) : 2,
		);
	}

	set_transient( 'lwp_amber_hero_tours', $tours, 10 * MINUTE_IN_SECONDS );
	return $tours;
}

/**
 * Bust the cached tour list when a product changes.
 */
function lwp_amber_hero_tours_flush() {
	delete_transient( 'lwp_amber_hero_tours' );
}
add_action( 'save_post_product', 'lwp_amber_hero_tours_flush' );
add_action( 'deleted_post', 'lwp_amber_hero_tours_flush' );

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

	wp_localize_script( 'luwipress-amber-hero-search', 'LWP_HERO', array(
		'tours'      => $tours,
		'contactUrl' => $contact_url,
		'i18n'       => array(
			'choose' => __( 'Choose a tour', 'luwipress-amber' ),
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
