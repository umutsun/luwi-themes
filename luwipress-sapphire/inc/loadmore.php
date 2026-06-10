<?php
/**
 * Generic archive Load More / Infinite Scroll (1.10.17+).
 *
 * Drives infinite scroll on EVERY non-WooCommerce listing — the Vendor /
 * Luthier CPT archive, the Event CPT archive, and blog/category/tag/author/date
 * archives (which render through archive.php). The WooCommerce shop archive has
 * its own setup in inc/enqueue.php + woocommerce/archive-product.php; this file
 * deliberately steps aside there.
 *
 * SEO-safe: the template still prints the real paginated links (the_posts_pagination
 * / next_posts_link) before calling the wrap helper. Those links stay in the DOM
 * (CSS only visually hides them once load-more is active), so search engines
 * crawl every page. The JS is pure progressive enhancement over them.
 *
 * @package luwipress-sapphire
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the load-more wrapper for an archive listing.
 *
 * Call this in a template right AFTER the native pagination. Returns silently
 * when there is only one page (nothing to load).
 *
 * @param array $args {
 *     @type string $grid       Selector of the items container to append into.
 *     @type string $pagination Selector of the pagination block to keep in sync.
 *     @type string $next       Selector of the "next page" link.
 *     @type string $mode       'infinite' (default) | 'button'.
 *     @type int    $max        Total number of pages.
 * }
 */
function lwp_sapphire_loadmore_render( $args = array() ) {
	global $wp_query;
	$defaults = array(
		'grid'       => '.lwp-jnl-grid',
		'pagination' => '.lwp-jnl-pagination',
		'next'       => '.lwp-jnl-pagination__next a',
		'mode'       => (string) get_theme_mod( 'luwipress_sapphire_archive_loadmore_mode', 'infinite' ),
		'max'        => isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 1,
	);
	$a = wp_parse_args( $args, $defaults );

	if ( (int) $a['max'] < 2 ) {
		return; // single page — no load-more needed.
	}
	$current = max( 1, (int) get_query_var( 'paged' ) );
	?>
	<div class="lwp-loadmore-wrap"
		data-grid="<?php echo esc_attr( $a['grid'] ); ?>"
		data-pagination="<?php echo esc_attr( $a['pagination'] ); ?>"
		data-next="<?php echo esc_attr( $a['next'] ); ?>"
		data-mode="<?php echo esc_attr( $a['mode'] ); ?>"
		data-current="<?php echo esc_attr( (string) $current ); ?>"
		data-max="<?php echo esc_attr( (string) (int) $a['max'] ); ?>">
		<button type="button" class="lwp-loadmore-btn" hidden><?php esc_html_e( 'Load more', 'luwipress-sapphire' ); ?></button>
		<span class="lwp-loadmore-spinner" hidden aria-hidden="true"><span class="lwp-loadmore-spinner__ring"></span></span>
		<span class="lwp-loadmore-status" aria-live="polite"></span>
	</div>
	<?php
}

/**
 * Enqueue the load-more script on non-WooCommerce archives. WC archives are
 * handled in inc/enqueue.php so we bail there to avoid a double setup.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_archive() ) {
		return;
	}
	if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
		return; // WC shop archive has its own load-more wiring.
	}
	/**
	 * Let a site opt a specific archive out of infinite scroll.
	 *
	 * @param bool   $on  Whether to enable load-more here.
	 * @param mixed  $obj The queried object.
	 */
	if ( ! apply_filters( 'luwipress_sapphire_archive_loadmore', true, get_queried_object() ) ) {
		return;
	}

	$ver  = defined( 'LUWIPRESS_SAPPHIRE_VERSION' ) ? LUWIPRESS_SAPPHIRE_VERSION : '1';
	$path = get_template_directory() . '/assets/js/loadmore.js';
	$lmv  = $ver . '.' . ( file_exists( $path ) ? filemtime( $path ) : '0' );

	wp_enqueue_script( 'luwipress-sapphire-loadmore', LUWIPRESS_SAPPHIRE_URI . '/assets/js/loadmore.js', array(), $lmv, true );
	wp_localize_script( 'luwipress-sapphire-loadmore', 'LWP_SAPPHIRE_LM', array(
		'i18n' => array(
			'load_more' => __( 'Load more', 'luwipress-sapphire' ),
			'loading'   => __( 'Loading…', 'luwipress-sapphire' ),
		),
		'mode' => (string) get_theme_mod( 'luwipress_sapphire_archive_loadmore_mode', 'infinite' ),
	) );
}, 20 );
