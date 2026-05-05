<?php
/**
 * Theme Customizer integration.
 *
 * Adds a top-level "LuwiPress Gold" panel to wp-admin → Customize with
 * 6 sections: Brand, Topbar, Header, Footer, Animation, Performance.
 *
 * Every setting that affects rendering is read at output time by:
 *   - inc/customizer/output-css.php → wp_add_inline_style on tokens
 *   - the various theme template files via get_theme_mod()
 *
 * The Customizer settings are the source of truth for everything the
 * wizard's Brand Override step writes — re-running the wizard or editing
 * via Customizer both write to the same `theme_mod_*` keys.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/output-css.php';

add_action( 'customize_register', 'luwipress_gold_customizer_register' );

function luwipress_gold_customizer_register( $wp_customize ) {

	/* ─────────────────────────────────────────────────────────────────
	 * Top-level panel
	 * ─────────────────────────────────────────────────────────────── */
	$wp_customize->add_panel( 'luwipress_gold', [
		'title'       => __( 'LuwiPress Gold', 'luwipress-gold' ),
		'description' => __( 'Theme-wide brand, topbar, header and footer settings. Anything you set here applies site-wide; per-page tweaks live in Elementor.', 'luwipress-gold' ),
		'priority'    => 10,
	] );

	/* ─────────────────────────────────────────────────────────────────
	 * Section 1 — Brand
	 * ─────────────────────────────────────────────────────────────── */
	$wp_customize->add_section( 'luwipress_gold_brand', [
		'title'    => __( 'Brand', 'luwipress-gold' ),
		'panel'    => 'luwipress_gold',
		'priority' => 10,
	] );

	$colors = [
		'primary'       => [ 'label' => __( 'Primary (gold)', 'luwipress-gold' ),       'default' => '#735c00' ],
		'primary_light' => [ 'label' => __( 'Primary light (highlight gold)', 'luwipress-gold' ), 'default' => '#D4AF37' ],
		'accent'        => [ 'label' => __( 'Accent (slate)', 'luwipress-gold' ),       'default' => '#545e76' ],
		'sale'          => [ 'label' => __( 'Sale red', 'luwipress-gold' ),             'default' => '#a33b3e' ],
		'icon_red'      => [ 'label' => __( 'Info-bar icon red', 'luwipress-gold' ),    'default' => '#d83131' ],
		'ink'           => [ 'label' => __( 'Ink (primary text)', 'luwipress-gold' ),   'default' => '#1b1c1c' ],
		'bg'            => [ 'label' => __( 'Page background', 'luwipress-gold' ),      'default' => '#fcf9f8' ],
		'black'         => [ 'label' => __( 'Topbar / footer black', 'luwipress-gold' ), 'default' => '#0c0c0c' ],
	];
	foreach ( $colors as $key => $cfg ) {
		$id = 'luwipress_gold_color_' . $key;
		$wp_customize->add_setting( $id, [
			'default'           => $cfg['default'],
			'transport'         => 'postMessage',
			'sanitize_callback' => 'sanitize_hex_color',
		] );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, [
			'label'    => $cfg['label'],
			'section'  => 'luwipress_gold_brand',
			'settings' => $id,
		] ) );
	}

	$wp_customize->add_setting( 'luwipress_gold_logo_accent_letter', [
		'default'           => 'u',
		'transport'         => 'refresh',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	$wp_customize->add_control( 'luwipress_gold_logo_accent_letter', [
		'label'       => __( 'Wordmark accent letter', 'luwipress-gold' ),
		'description' => __( 'When no custom logo is set, this letter is rendered in italic gold inside the site name (e.g. "tap<em>a</em>dum"). Single character, optional.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_brand',
		'type'        => 'text',
	] );

	/* ─────────────────────────────────────────────────────────────────
	 * Section 2 — Topbar
	 * ─────────────────────────────────────────────────────────────── */
	$wp_customize->add_section( 'luwipress_gold_topbar', [
		'title'       => __( 'Topbar', 'luwipress-gold' ),
		'description' => __( 'The thin black strip above the header — runs across every page.', 'luwipress-gold' ),
		'panel'       => 'luwipress_gold',
		'priority'    => 20,
	] );

	$wp_customize->add_setting( 'luwipress_gold_topbar_show', [
		'default'           => 1,
		'transport'         => 'refresh',
		'sanitize_callback' => function ( $v ) { return $v ? 1 : 0; },
	] );
	$wp_customize->add_control( 'luwipress_gold_topbar_show', [
		'label'   => __( 'Show topbar', 'luwipress-gold' ),
		'section' => 'luwipress_gold_topbar',
		'type'    => 'checkbox',
	] );

	$wp_customize->add_setting( 'luwipress_gold_topbar_location', [
		'default'           => '',
		'transport'         => 'refresh',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	$wp_customize->add_control( 'luwipress_gold_topbar_location', [
		'label'       => __( 'Topbar — location text', 'luwipress-gold' ),
		'description' => __( 'Shown left, with a 📍 prefix. Leave empty to hide.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_topbar',
		'type'        => 'text',
	] );

	$wp_customize->add_setting( 'luwipress_gold_topbar_phone', [
		'default'           => '',
		'transport'         => 'refresh',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	$wp_customize->add_control( 'luwipress_gold_topbar_phone', [
		'label'   => __( 'Topbar — phone number', 'luwipress-gold' ),
		'section' => 'luwipress_gold_topbar',
		'type'    => 'text',
	] );

	$wp_customize->add_setting( 'luwipress_gold_topbar_email', [
		'default'           => get_option( 'admin_email' ),
		'transport'         => 'refresh',
		'sanitize_callback' => 'sanitize_email',
	] );
	$wp_customize->add_control( 'luwipress_gold_topbar_email', [
		'label'       => __( 'Topbar — contact email', 'luwipress-gold' ),
		'description' => __( 'Defaults to your admin email. Override if you publish a different inbox.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_topbar',
		'type'        => 'email',
	] );

	$wp_customize->add_setting( 'luwipress_gold_topbar_promo', [
		'default'           => __( 'Free DHL shipping over €450', 'luwipress-gold' ),
		'transport'         => 'refresh',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	$wp_customize->add_control( 'luwipress_gold_topbar_promo', [
		'label'       => __( 'Topbar — promo line', 'luwipress-gold' ),
		'description' => __( 'Shown right, after the language switcher.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_topbar',
		'type'        => 'text',
	] );

	/* Track-order link — typically points at a page hosting the
	 * [woocommerce_order_tracking] shortcode. Empty value hides the
	 * link entirely. The label below is the visible text. */
	$wp_customize->add_setting( 'luwipress_gold_topbar_track_url', [
		'default'           => '',
		'transport'         => 'refresh',
		'sanitize_callback' => 'esc_url_raw',
	] );
	$wp_customize->add_control( 'luwipress_gold_topbar_track_url', [
		'label'       => __( 'Topbar — track-order URL', 'luwipress-gold' ),
		'description' => __( 'Link to a tracking page (e.g. a page with the [woocommerce_order_tracking] shortcode). Leave empty to hide the link.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_topbar',
		'type'        => 'url',
	] );

	$wp_customize->add_setting( 'luwipress_gold_topbar_track_label', [
		'default'           => __( 'Track order', 'luwipress-gold' ),
		'transport'         => 'refresh',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	$wp_customize->add_control( 'luwipress_gold_topbar_track_label', [
		'label'       => __( 'Topbar — track-order label', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_topbar',
		'type'        => 'text',
	] );

	/* ─────────────────────────────────────────────────────────────────
	 * Section 3 — Header
	 * ─────────────────────────────────────────────────────────────── */
	$wp_customize->add_section( 'luwipress_gold_header', [
		'title'    => __( 'Header', 'luwipress-gold' ),
		'panel'    => 'luwipress_gold',
		'priority' => 30,
	] );

	$wp_customize->add_setting( 'luwipress_gold_header_sticky', [
		'default'           => 1,
		'transport'         => 'refresh',
		'sanitize_callback' => function ( $v ) { return $v ? 1 : 0; },
	] );
	$wp_customize->add_control( 'luwipress_gold_header_sticky', [
		'label'   => __( 'Sticky header (shrinks on scroll)', 'luwipress-gold' ),
		'section' => 'luwipress_gold_header',
		'type'    => 'checkbox',
	] );

	$wp_customize->add_setting( 'luwipress_gold_header_cta_label', [
		'default'           => '',
		'transport'         => 'refresh',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	$wp_customize->add_control( 'luwipress_gold_header_cta_label', [
		'label'       => __( 'Header CTA — label', 'luwipress-gold' ),
		'description' => __( 'Optional gold gradient button on the right (e.g. "Birthday sale →"). Leave empty to hide.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_header',
		'type'        => 'text',
	] );

	$wp_customize->add_setting( 'luwipress_gold_header_cta_url', [
		'default'           => '',
		'transport'         => 'refresh',
		'sanitize_callback' => 'esc_url_raw',
	] );
	$wp_customize->add_control( 'luwipress_gold_header_cta_url', [
		'label'   => __( 'Header CTA — URL', 'luwipress-gold' ),
		'section' => 'luwipress_gold_header',
		'type'    => 'url',
	] );

	$wp_customize->add_setting( 'luwipress_gold_show_megabar', [
		'default'           => 1,
		'transport'         => 'refresh',
		'sanitize_callback' => function ( $v ) { return $v ? 1 : 0; },
	] );
	$wp_customize->add_control( 'luwipress_gold_show_megabar', [
		'label'       => __( 'Show subcategory strip (megabar)', 'luwipress-gold' ),
		'description' => __( 'The thin row of links under the header — auto-fills from your top WooCommerce sub-categories.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_header',
		'type'        => 'checkbox',
	] );

	/* ─────────────────────────────────────────────────────────────────
	 * Section 4 — Footer
	 * ─────────────────────────────────────────────────────────────── */
	$wp_customize->add_section( 'luwipress_gold_footer', [
		'title'    => __( 'Footer', 'luwipress-gold' ),
		'panel'    => 'luwipress_gold',
		'priority' => 40,
	] );

	$wp_customize->add_setting( 'luwipress_gold_footer_blurb', [
		'default'           => __( 'Hand-crafted instruments from the masters of Anatolia, Persia and the Mediterranean. Brisighella, Italy.', 'luwipress-gold' ),
		'transport'         => 'refresh',
		'sanitize_callback' => 'wp_kses_post',
	] );
	$wp_customize->add_control( 'luwipress_gold_footer_blurb', [
		'label'   => __( 'Brand blurb (footer left column)', 'luwipress-gold' ),
		'section' => 'luwipress_gold_footer',
		'type'    => 'textarea',
	] );

	$wp_customize->add_setting( 'luwipress_gold_footer_legal', [
		'default'           => '',
		'transport'         => 'refresh',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	$wp_customize->add_control( 'luwipress_gold_footer_legal', [
		'label'       => __( 'Legal line (footer bottom-left)', 'luwipress-gold' ),
		'description' => __( 'Free-form text — VAT number, company registration, etc. Year and site name are added automatically.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_footer',
		'type'        => 'text',
	] );

	$wp_customize->add_setting( 'luwipress_gold_footer_byline', [
		'default'           => __( 'Hand-tuned in Brisighella · Shipped worldwide', 'luwipress-gold' ),
		'transport'         => 'refresh',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	$wp_customize->add_control( 'luwipress_gold_footer_byline', [
		'label'   => __( 'Footer byline (bottom-right)', 'luwipress-gold' ),
		'section' => 'luwipress_gold_footer',
		'type'    => 'text',
	] );

	$social = [
		'instagram' => [ 'label' => 'Instagram', 'icon' => '◎' ],
		'youtube'   => [ 'label' => 'YouTube',   'icon' => '▶' ],
		'facebook'  => [ 'label' => 'Facebook',  'icon' => 'f' ],
		'whatsapp'  => [ 'label' => 'WhatsApp',  'icon' => 'w' ],
		'tiktok'    => [ 'label' => 'TikTok',    'icon' => 't' ],
		'pinterest' => [ 'label' => 'Pinterest', 'icon' => 'p' ],
		'twitter'   => [ 'label' => 'Twitter / X', 'icon' => '𝕏' ],
		'linkedin'  => [ 'label' => 'LinkedIn',  'icon' => 'in' ],
	];
	foreach ( $social as $key => $cfg ) {
		$id = 'luwipress_gold_social_' . $key;
		$wp_customize->add_setting( $id, [
			'default'           => '',
			'transport'         => 'refresh',
			'sanitize_callback' => 'esc_url_raw',
		] );
		$wp_customize->add_control( $id, [
			'label'   => $cfg['label'] . ' URL',
			'section' => 'luwipress_gold_footer',
			'type'    => 'url',
		] );
	}

	/* ─────────────────────────────────────────────────────────────────
	 * Section 5 — Animation
	 * ─────────────────────────────────────────────────────────────── */
	$wp_customize->add_section( 'luwipress_gold_animation', [
		'title'    => __( 'Animation', 'luwipress-gold' ),
		'panel'    => 'luwipress_gold',
		'priority' => 50,
	] );

	$wp_customize->add_setting( 'luwipress_gold_loader_enabled', [
		'default'           => 1,
		'transport'         => 'refresh',
		'sanitize_callback' => function ( $v ) { return $v ? 1 : 0; },
	] );
	$wp_customize->add_control( 'luwipress_gold_loader_enabled', [
		'label'       => __( 'Page loader (gold "T" mark)', 'luwipress-gold' ),
		'description' => __( 'Fades in on page load, hides on window.load (or 4 s hard cap). Disable if you serve heavy media.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_animation',
		'type'        => 'checkbox',
	] );

	$wp_customize->add_setting( 'luwipress_gold_scroll_reveal_enabled', [
		'default'           => 1,
		'transport'         => 'refresh',
		'sanitize_callback' => function ( $v ) { return $v ? 1 : 0; },
	] );
	$wp_customize->add_control( 'luwipress_gold_scroll_reveal_enabled', [
		'label'   => __( 'Scroll reveal animations', 'luwipress-gold' ),
		'section' => 'luwipress_gold_animation',
		'type'    => 'checkbox',
	] );

	$wp_customize->add_setting( 'luwipress_gold_cart_bump_enabled', [
		'default'           => 1,
		'transport'         => 'refresh',
		'sanitize_callback' => function ( $v ) { return $v ? 1 : 0; },
	] );
	$wp_customize->add_control( 'luwipress_gold_cart_bump_enabled', [
		'label'       => __( 'Cart bump animation', 'luwipress-gold' ),
		'description' => __( 'Cart icon scales when a product is added.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_animation',
		'type'        => 'checkbox',
	] );

	/* ─────────────────────────────────────────────────────────────────
	 * Section 6 — Performance
	 * ─────────────────────────────────────────────────────────────── */
	$wp_customize->add_section( 'luwipress_gold_performance', [
		'title'    => __( 'Performance', 'luwipress-gold' ),
		'panel'    => 'luwipress_gold',
		'priority' => 60,
	] );

	$wp_customize->add_setting( 'luwipress_gold_lazy_load_strategy', [
		'default'           => 'browser',
		'transport'         => 'refresh',
		'sanitize_callback' => function ( $v ) {
			return in_array( $v, [ 'browser', 'eager_hero', 'off' ], true ) ? $v : 'browser';
		},
	] );
	$wp_customize->add_control( 'luwipress_gold_lazy_load_strategy', [
		'label'       => __( 'Image lazy-load strategy', 'luwipress-gold' ),
		'description' => __( 'Browser-native is the default. "Eager hero" loads the first hero image immediately to improve LCP.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_performance',
		'type'        => 'select',
		'choices'     => [
			'browser'    => __( 'Browser-native (recommended)', 'luwipress-gold' ),
			'eager_hero' => __( 'Eager hero (faster LCP)', 'luwipress-gold' ),
			'off'        => __( 'Off', 'luwipress-gold' ),
		],
	] );

	$wp_customize->add_setting( 'luwipress_gold_emoji_disabled', [
		'default'           => 1,
		'transport'         => 'refresh',
		'sanitize_callback' => function ( $v ) { return $v ? 1 : 0; },
	] );
	$wp_customize->add_control( 'luwipress_gold_emoji_disabled', [
		'label'       => __( 'Disable WP emoji script', 'luwipress-gold' ),
		'description' => __( 'Modern browsers render emoji natively — saves a request.', 'luwipress-gold' ),
		'section'     => 'luwipress_gold_performance',
		'type'        => 'checkbox',
	] );

	$wp_customize->add_setting( 'luwipress_gold_dns_prefetch', [
		'default'           => 1,
		'transport'         => 'refresh',
		'sanitize_callback' => function ( $v ) { return $v ? 1 : 0; },
	] );
	$wp_customize->add_control( 'luwipress_gold_dns_prefetch', [
		'label'   => __( 'DNS prefetch for fonts.googleapis.com', 'luwipress-gold' ),
		'section' => 'luwipress_gold_performance',
		'type'    => 'checkbox',
	] );
}

/* ──────────────────────────────────────────────────────────────────
 * Apply Customizer settings to the site
 * ──────────────────────────────────────────────────────────────── */

// Performance toggles.
add_action( 'init', function () {
	if ( get_theme_mod( 'luwipress_gold_emoji_disabled', 1 ) ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}
}, 100 );

// Prefetch hint already lives in inc/enqueue.php; gate it on the customizer flag.
add_filter( 'wp_resource_hints', function ( $hints, $relation ) {
	if ( 'dns-prefetch' === $relation && get_theme_mod( 'luwipress_gold_dns_prefetch', 1 ) ) {
		$hints[] = '//fonts.googleapis.com';
		$hints[] = '//fonts.gstatic.com';
	}
	return $hints;
}, 10, 2 );

// Animation toggles → expose to frontend.js via a localized data object.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! wp_script_is( 'luwipress-gold-frontend', 'enqueued' ) ) return;
	wp_localize_script( 'luwipress-gold-frontend', 'LWP_GOLD_ANIM', [
		'loader' => (bool) get_theme_mod( 'luwipress_gold_loader_enabled', 1 ),
		'reveal' => (bool) get_theme_mod( 'luwipress_gold_scroll_reveal_enabled', 1 ),
		'bump'   => (bool) get_theme_mod( 'luwipress_gold_cart_bump_enabled', 1 ),
	] );
}, 25 );

// Logo accent letter filter.
add_filter( 'luwipress_gold_logo_accent_letter', function ( $default ) {
	$set = get_theme_mod( 'luwipress_gold_logo_accent_letter' );
	return $set !== '' ? $set : $default;
}, 5 );

// Footer blurb / legal / byline filters.
add_filter( 'luwipress_gold_footer_blurb', function ( $default ) {
	$set = get_theme_mod( 'luwipress_gold_footer_blurb' );
	return $set !== '' ? $set : $default;
}, 5 );
add_filter( 'luwipress_gold_legal_id', function ( $default ) {
	$set = get_theme_mod( 'luwipress_gold_footer_legal' );
	return $set !== '' ? $set : $default;
}, 5 );

// Topbar items filter — only fills slots the operator left non-empty.
add_filter( 'luwipress_gold_topbar_left', function ( $default_items ) {
	$loc   = get_theme_mod( 'luwipress_gold_topbar_location' );
	$phone = get_theme_mod( 'luwipress_gold_topbar_phone' );
	$email = get_theme_mod( 'luwipress_gold_topbar_email', get_option( 'admin_email' ) );
	$out = [];
	if ( $loc !== '' ) {
		$out[] = '<span>📍 ' . esc_html( $loc ) . '</span>';
	}
	if ( $phone !== '' ) {
		$out[] = '<a href="' . esc_url( 'tel:' . preg_replace( '/\s+/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>';
	}
	if ( $email !== '' ) {
		$out[] = '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
	}
	return $out ?: $default_items;
}, 5 );

add_filter( 'luwipress_gold_topbar_promo', function ( $default ) {
	$set = get_theme_mod( 'luwipress_gold_topbar_promo' );
	return $set !== '' ? $set : $default;
}, 5 );

// Social links: only return slots that have a URL set.
add_filter( 'luwipress_gold_social_links', function ( $default_links ) {
	$social = [
		'instagram' => '◎', 'youtube' => '▶', 'facebook' => 'f', 'whatsapp' => 'w',
		'tiktok' => 't', 'pinterest' => 'p', 'twitter' => '𝕏', 'linkedin' => 'in',
	];
	$out = [];
	foreach ( $social as $key => $icon ) {
		$url = get_theme_mod( 'luwipress_gold_social_' . $key );
		if ( $url !== '' ) {
			$out[ $key ] = [
				'label' => ucfirst( $key ),
				'url'   => $url,
				'icon'  => $icon,
			];
		}
	}
	return $out ?: $default_links;
}, 5 );

// Megabar visibility flag.
add_filter( 'luwipress_gold_show_megabar', function ( $default ) {
	return get_theme_mod( 'luwipress_gold_show_megabar', $default ) ? true : false;
}, 5 );

/* ──────────────────────────────────────────────────────────────────
 * Live preview JS — postMessage transport for color swaps.
 * ──────────────────────────────────────────────────────────────── */
add_action( 'customize_preview_init', function () {
	wp_enqueue_script(
		'luwipress-gold-customize-preview',
		LUWIPRESS_GOLD_URI . '/assets/js/customize-preview.js',
		[ 'customize-preview', 'jquery' ],
		LUWIPRESS_GOLD_VERSION,
		true
	);
} );
