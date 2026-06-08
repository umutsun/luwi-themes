<?php
/**
 * 404 — editorial. Replaced by `10-404.json` when imported as the Theme Builder
 * "Single 404" template. Otherwise renders the giant-digit fallback that
 * Mobile Spec Preview §13 specifies.
 *
 * Operators can curate the popular-links list with menu location
 * `404-popular` (falls back to a default 4-row list).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$home_url = home_url( '/' );

/**
 * Filter the popular-links rendered in the 404 editorial layout.
 * Each entry: [ 'label' => string, 'url' => string ].
 *
 * @since 1.5.4
 */
$default_links = [
	[ 'label' => __( 'New arrivals', 'luwipress-amber' ), 'url' => $home_url ],
	[ 'label' => __( 'Master luthiers', 'luwipress-amber' ), 'url' => $home_url ],
	[ 'label' => __( 'Custom commissions', 'luwipress-amber' ), 'url' => $home_url ],
	[ 'label' => __( 'Journal', 'luwipress-amber' ), 'url' => $home_url ],
];

$popular = apply_filters( 'luwipress_amber_404_popular_links', $default_links );

// If a menu is registered at 404-popular, prefer that (operator override).
$menu_locations = get_nav_menu_locations();
if ( ! empty( $menu_locations['404-popular'] ) ) {
	$menu = wp_get_nav_menu_object( $menu_locations['404-popular'] );
	if ( $menu ) {
		$items = wp_get_nav_menu_items( $menu->term_id );
		if ( $items ) {
			$popular = array_map( function ( $item ) {
				return [ 'label' => $item->title, 'url' => $item->url ];
			}, $items );
		}
	}
}
?>
<main class="lwp-page" id="primary">
	<section class="error-404 not-found">
		<span class="eyebrow">— <?php esc_html_e( 'Page not found', 'luwipress-amber' ); ?></span>
		<h1 class="digit" aria-label="404">4<em>0</em>4</h1>
		<h2><?php
			/* translators: keep <em> wrapping the closing word for italic accent */
			echo wp_kses(
				__( 'The workshop door <em>is closed</em>.', 'luwipress-amber' ),
				[ 'em' => [] ]
			);
		?></h2>
		<p><?php esc_html_e( 'This page either moved, sold out, or was never here. Try a different door:', 'luwipress-amber' ); ?></p>
		<a class="cta-pill button" href="<?php echo esc_url( $home_url ); ?>">
			<?php esc_html_e( 'Back to home →', 'luwipress-amber' ); ?>
		</a>
	</section>

	<div class="divider" role="presentation" aria-hidden="true"></div>

	<?php if ( ! empty( $popular ) ) : ?>
	<section class="lwp-404-popular" style="padding:20px;display:flex;flex-direction:column;gap:10px">
		<span class="eyebrow"><?php esc_html_e( 'Most asked-about', 'luwipress-amber' ); ?></span>
		<?php foreach ( $popular as $i => $row ) :
			if ( empty( $row['label'] ) || empty( $row['url'] ) ) continue;
			$last = ( $i === count( $popular ) - 1 );
		?>
			<a class="lwp-list-row" href="<?php echo esc_url( $row['url'] ); ?>" style="border-bottom:<?php echo $last ? '0' : '1px solid var(--line)'; ?>;padding-left:0;padding-right:0">
				<span class="ic" aria-hidden="true"></span>
				<span class="nm"><?php echo esc_html( $row['label'] ); ?></span>
				<span class="val">→</span>
			</a>
		<?php endforeach; ?>
	</section>
	<?php endif; ?>

	<section class="lwp-404-search" style="background:var(--bg-alt);padding:20px;display:flex;flex-direction:column;gap:10px">
		<span class="eyebrow"><?php esc_html_e( 'Or search', 'luwipress-amber' ); ?></span>
		<form role="search" method="get" action="<?php echo esc_url( $home_url ); ?>" style="display:flex;align-items:center;gap:8px;padding:13px 16px;background:var(--bg);border:1px solid var(--line);border-radius:24px">
			<span style="font-family:var(--mono);font-size:13px;color:var(--muted)" aria-hidden="true">⌕</span>
			<label for="lwp-404-s" class="screen-reader-text"><?php esc_html_e( 'Search', 'luwipress-amber' ); ?></label>
			<input id="lwp-404-s" name="s" type="search" placeholder="<?php esc_attr_e( 'Search instruments, masters…', 'luwipress-amber' ); ?>" style="flex:1;border:0;outline:none;background:transparent;font-size:16px" />
		</form>
	</section>
</main>
<?php
get_footer();
