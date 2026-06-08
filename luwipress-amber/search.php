<?php
/**
 * Search results — Mobile Spec Preview §12.
 *
 * Sticky search bar (with AI tag) + result-type tabs (Products / Masters /
 * Journal) + image-left result rows. Tabs are AJAX-ready when the active
 * filter is set via ?type= query var; without it shows full mixed results.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$query        = get_search_query();
$total_found  = (int) ( $GLOBALS['wp_query']->found_posts ?? 0 );
$active_type  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
$tabs_default = [
	'all'      => __( 'All', 'luwipress-amber' ),
	'product'  => __( 'Products', 'luwipress-amber' ),
	'master'   => __( 'Masters', 'luwipress-amber' ),
	'post'     => __( 'Journal', 'luwipress-amber' ),
];

/**
 * Filter the search-result tabs. Drop entries whose CPT is not registered
 * to hide them. Each value is the tab label; key is the post_type.
 *
 * @since 1.5.4
 */
$tabs = apply_filters( 'luwipress_amber_search_tabs', $tabs_default );
foreach ( $tabs as $key => $label ) {
	if ( $key !== 'all' && ! post_type_exists( $key ) ) {
		unset( $tabs[ $key ] );
	}
}

// Helper — wrap query in <mark> for highlighted matches in titles.
$highlight = function ( $text ) use ( $query ) {
	if ( ! $query ) return $text;
	$pattern = '#(' . preg_quote( $query, '#' ) . ')#i';
	return preg_replace( $pattern, '<mark>$1</mark>', $text );
};

// Per-tab counts (cheap meta_query against current keyword)
$tab_counts = [];
if ( $query ) {
	foreach ( $tabs as $key => $_ ) {
		if ( $key === 'all' ) {
			$tab_counts[ $key ] = $total_found;
			continue;
		}
		$probe = new WP_Query( [
			's'              => $query,
			'post_type'      => $key,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		] );
		$tab_counts[ $key ] = (int) $probe->found_posts;
		wp_reset_postdata();
	}
}
?>

<main class="lwp-page lwp-search-page" id="primary">

	<div class="lwp-search-bar">
		<button type="button" class="back" onclick="history.length > 1 ? history.back() : (location.href='<?php echo esc_js( home_url( '/' ) ); ?>')" aria-label="<?php esc_attr_e( 'Back', 'luwipress-amber' ); ?>" style="width:36px;height:36px;border-radius:50%;border:1px solid var(--line);font-family:var(--mono);font-size:14px;flex:none;background:var(--bg);cursor:pointer">‹</button>
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="field" style="margin:0">
			<span aria-hidden="true">⌕</span>
			<label for="lwp-search-input" class="screen-reader-text"><?php esc_html_e( 'Search', 'luwipress-amber' ); ?></label>
			<input id="lwp-search-input" name="s" type="search" value="<?php echo esc_attr( $query ); ?>" autocapitalize="off" spellcheck="false" placeholder="<?php esc_attr_e( 'Search instruments, masters…', 'luwipress-amber' ); ?>" />
			<?php if ( defined( 'LUWIPRESS_VERSION' ) ) : ?>
				<span class="ai-tag" aria-label="<?php esc_attr_e( 'AI-powered search', 'luwipress-amber' ); ?>">AI</span>
			<?php endif; ?>
		</form>
	</div>

	<?php if ( count( $tabs ) > 1 ) : ?>
	<nav class="lwp-search-tabs" aria-label="<?php esc_attr_e( 'Search filters', 'luwipress-amber' ); ?>">
		<?php foreach ( $tabs as $key => $label ) :
			$is_active = ( $active_type === $key ) || ( $key === 'all' && '' === $active_type );
			$url       = ( $key === 'all' )
				? add_query_arg( [ 's' => $query ], home_url( '/' ) )
				: add_query_arg( [ 's' => $query, 'type' => $key ], home_url( '/' ) );
			$count     = isset( $tab_counts[ $key ] ) ? ' · ' . (int) $tab_counts[ $key ] : '';
		?>
			<a href="<?php echo esc_url( $url ); ?>" class="<?php echo $is_active ? 'is-active' : ''; ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
				<?php echo esc_html( $label . $count ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php endif; ?>

	<div style="padding:10px 16px;font-family:var(--mono);font-size:10.5px;letter-spacing:.14em;color:var(--muted);text-transform:uppercase;display:flex;justify-content:space-between;background:var(--bg-alt);border-bottom:1px solid var(--line)">
		<span><?php
			/* translators: 1: count 2: search term */
			printf(
				esc_html( _n( '%1$d result · "%2$s"', '%1$d results · "%2$s"', $total_found, 'luwipress-amber' ) ),
				$total_found,
				esc_html( $query )
			);
		?></span>
	</div>

	<?php if ( have_posts() ) : ?>

		<section class="lwp-search-results">
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class(); ?>>
					<a href="<?php the_permalink(); ?>" class="post-thumbnail" style="display:block">
						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail( 'medium' );
						} else {
							$type = get_post_type();
							$grad = ( 'product' === $type )
								? 'linear-gradient(135deg,#3d2f1f,#7a5a2c)'
								: 'linear-gradient(135deg,#1f1a0e,#5a4520)';
							echo '<div style="width:88px;aspect-ratio:4/5;border-radius:8px;background:' . esc_attr( $grad ) . '"></div>';
						}
						?>
					</a>
					<div>
						<?php
						$tax = ( 'product' === get_post_type() ) ? 'product_cat' : 'category';
						$terms = get_the_terms( get_the_ID(), $tax );
						if ( $terms && ! is_wp_error( $terms ) ) :
						?>
							<span class="cat-eyebrow"><?php echo wp_kses( $highlight( esc_html( $terms[0]->name ) ), [ 'mark' => [] ] ); ?></span>
						<?php endif; ?>
						<h2 class="entry-title">
							<a href="<?php the_permalink(); ?>"><?php echo wp_kses( $highlight( get_the_title() ), [ 'mark' => [] ] ); ?></a>
						</h2>
						<?php if ( 'product' === get_post_type() && function_exists( 'wc_get_product' ) ) :
							$product = wc_get_product( get_the_ID() );
							if ( $product ) :
						?>
							<span class="px" style="font-family:var(--serif);font-size:14.5px;color:var(--primary);margin-top:4px;display:block"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
						<?php endif; endif; ?>
						<?php $excerpt = get_the_excerpt(); if ( $excerpt && 'product' !== get_post_type() ) : ?>
							<p class="entry-summary"><?php echo esc_html( wp_trim_words( $excerpt, 18 ) ); ?></p>
						<?php endif; ?>
					</div>
				</article>
			<?php endwhile; ?>
		</section>

		<?php
		the_posts_pagination( [
			'mid_size'  => 1,
			'prev_text' => __( '← Newer', 'luwipress-amber' ),
			'next_text' => __( 'Older →', 'luwipress-amber' ),
		] );
		?>

	<?php else : ?>

		<section style="padding:48px 24px 32px;display:flex;flex-direction:column;gap:14px;align-items:flex-start">
			<span class="eyebrow"><?php esc_html_e( 'No results', 'luwipress-amber' ); ?></span>
			<h2 class="h-section" style="font-family:var(--serif);font-size:22px;font-weight:500"><?php
				/* translators: %s: search term */
				printf( esc_html__( 'Nothing for "%s" yet.', 'luwipress-amber' ), esc_html( $query ) );
			?></h2>
			<p style="font-size:15px;color:var(--ink-soft)"><?php esc_html_e( 'Try a wider search, or browse from the home page.', 'luwipress-amber' ); ?></p>
			<a class="cta-pill button" href="<?php echo esc_url( home_url( '/' ) ); ?>" style="padding:12px 22px;background:var(--ink);color:#fff;border-radius:999px;font-family:var(--mono);font-size:11.5px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;text-decoration:none;margin-top:8px"><?php esc_html_e( 'Back to home →', 'luwipress-amber' ); ?></a>
		</section>

	<?php endif; ?>

</main>

<?php
get_footer();
