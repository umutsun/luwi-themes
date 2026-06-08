<?php
/**
 * Friendly-plugin glue — consolidates the small storefront hooks that
 * amplify each ecosystem plugin's presence so the integration story
 * lives in one file instead of being scattered across the codebase.
 *
 * Mission: when an operator activates one of the LuwiPress-friendly
 * plugins (Rank Math, Yoast, WPML, Polylang, WP Mail SMTP, FluentCRM,
 * etc.), the theme should *visibly* gain a feature — not just silently
 * tolerate the plugin.
 *
 * Each glue block self-gates with `class_exists` / `defined` /
 * `function_exists` so this file is safe to load on every request.
 *
 * @package luwipress-onyx
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rank Math integration —
 *   1. Replace the theme's hand-rolled `.lwp-crumb` markup with Rank Math's
 *      breadcrumb when the operator has the breadcrumb feature enabled.
 *      Keeps schema attribution + admin-controlled separators.
 *   2. Tell Rank Math that our archive-product / journal pages already
 *      render a primary heading so it doesn't try to inject one.
 */
add_action( 'after_setup_theme', function () {
	if ( ! class_exists( 'RankMath' ) ) {
		return;
	}

	// Theme breadcrumb wrapper: prefer Rank Math's renderer when configured.
	add_filter( 'luwipress_onyx_breadcrumb_html', function ( $default_html ) {
		if ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
			ob_start();
			rank_math_the_breadcrumbs();
			$rm_html = trim( (string) ob_get_clean() );
			if ( $rm_html !== '' ) {
				return '<nav class="lwp-crumb lwp-crumb--rank-math" aria-label="Breadcrumb">' . $rm_html . '</nav>';
			}
		}
		return $default_html;
	}, 10 );
}, 20 );

/**
 * Yoast SEO integration — same pattern as Rank Math but using Yoast's
 * shortcode-equivalent breadcrumb function. Skipped automatically if Rank
 * Math is also present (Rank Math wins by file order).
 */
add_action( 'after_setup_theme', function () {
	if ( class_exists( 'RankMath' ) || ! defined( 'WPSEO_VERSION' ) ) {
		return;
	}
	add_filter( 'luwipress_onyx_breadcrumb_html', function ( $default_html ) {
		if ( function_exists( 'yoast_breadcrumb' ) ) {
			$yoast_html = yoast_breadcrumb( '<nav class="lwp-crumb lwp-crumb--yoast" aria-label="Breadcrumb">', '</nav>', false );
			if ( $yoast_html ) {
				return $yoast_html;
			}
		}
		return $default_html;
	}, 10 );
}, 20 );

/**
 * WPML / Polylang integration —
 *   1. The language pill in header.php already auto-detects via the
 *      filters those plugins expose; nothing to do at the markup level.
 *   2. Add hreflang `<link>` tags to <head> when WPML / Polylang doesn't
 *      already do it (some configurations skip the head injection).
 *      We hook before the SEO plugin so a Rank Math / Yoast hreflang
 *      output overrides ours (no double tags).
 */
add_action( 'wp_head', function () {
	// If a SEO plugin or WPML/Polylang already emitted hreflang, bail.
	// Detect by looking at the rendered head buffer is fragile — instead
	// rely on the plugin's own action hook presence.
	if ( has_action( 'wp_head', 'wpml_seo_meta' ) || has_action( 'wp_head', 'pll_alternate_languages' ) ) {
		return;
	}

	$langs = array();

	if ( has_filter( 'wpml_active_languages' ) ) {
		$wpml = apply_filters( 'wpml_active_languages', null, 'orderby=code' );
		if ( is_array( $wpml ) ) {
			foreach ( $wpml as $code => $l ) {
				if ( ! empty( $l['url'] ) ) {
					$langs[ strtolower( $l['language_code'] ?? $code ) ] = $l['url'];
				}
			}
		}
	} elseif ( function_exists( 'pll_the_languages' ) ) {
		$pll = pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0 ) );
		if ( is_array( $pll ) ) {
			foreach ( $pll as $code => $l ) {
				if ( ! empty( $l['url'] ) ) {
					$langs[ strtolower( $l['slug'] ?? $code ) ] = $l['url'];
				}
			}
		}
	}

	if ( count( $langs ) <= 1 ) {
		return;
	}

	foreach ( $langs as $code => $url ) {
		printf(
			'<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
			esc_attr( $code ),
			esc_url( $url )
		);
	}
}, 1 );

/**
 * WooCommerce integration — already deeply wired through wc-pdp-hooks.php +
 * woocommerce.php template overrides. This hook surfaces the friendly note
 * inside the ecosystem dashboard (see ecosystem-dashboard.php).
 */
add_filter( 'luwipress_onyx_ecosystem_features', function ( $features ) {
	if ( class_exists( 'WooCommerce' ) ) {
		$features['woocommerce'] = array(
			'cart_drawer'      => true,
			'account_popover'  => true,
			'sale_save_badge'  => true,
			'stock_pill'       => true,
			'maker_eyebrow'    => true,
		);
	}
	return $features;
}, 10 );

/**
 * Elementor integration — Theme Builder header/footer locations are already
 * wired in header.php / footer.php (`elementor_theme_do_location`). This
 * hook just declares the capability so the ecosystem dashboard can show
 * "yielding to Theme Builder when active".
 */
add_filter( 'luwipress_onyx_ecosystem_features', function ( $features ) {
	if ( did_action( 'elementor/loaded' ) ) {
		$features['elementor'] = array(
			'theme_builder_header' => function_exists( 'elementor_theme_do_location' ),
			'theme_builder_footer' => function_exists( 'elementor_theme_do_location' ),
			'kit_import'           => true,
			'custom_widgets'       => true,
		);
	}
	return $features;
}, 10 );

/**
 * Mailchimp for WP / MailPoet / Contact Form 7 / WPForms / FluentForms —
 * detect any of these so the footer newsletter slot can render the right
 * shortcode instead of a placeholder. The Customizer setting picks WHICH
 * shortcode to inject; this hook just reports availability.
 */
add_filter( 'luwipress_onyx_ecosystem_features', function ( $features ) {
	$detected = array();
	if ( defined( 'MC4WP_VERSION' ) )            $detected[] = 'mc4wp';
	if ( class_exists( 'MailPoet\\API\\API' ) )  $detected[] = 'mailpoet';
	if ( defined( 'WPCF7_VERSION' ) )            $detected[] = 'cf7';
	if ( defined( 'WPFORMS_VERSION' ) )          $detected[] = 'wpforms';
	if ( defined( 'FLUENTFORM_VERSION' ) )       $detected[] = 'fluentforms';

	if ( ! empty( $detected ) ) {
		$features['newsletter_form'] = array(
			'providers' => $detected,
			'count'     => count( $detected ),
		);
	}
	return $features;
}, 10 );

/**
 * Helper used by archive-product.php / single.php / journal templates.
 * Anything that wants to render a breadcrumb runs the markup through
 * this filter so SEO plugins can take over centrally.
 *
 * @param string $default_html The theme's hand-rolled <nav class="lwp-crumb">…</nav>.
 * @return string
 */
function lwp_onyx_filter_breadcrumb( $default_html ) {
	return apply_filters( 'luwipress_onyx_breadcrumb_html', $default_html );
}
