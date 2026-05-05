<?php
/**
 * Theme header. Three rendering paths, picked at runtime:
 *
 *   1. Elementor Pro + Theme Builder Header template active
 *      → Pro renders our 01-header.json template; we yield via
 *        elementor_theme_do_location('header') and stop.
 *
 *   2. Elementor (free) + ElementsKit Lite Header template active
 *      → ElementsKit's Header Builder hooks before our fallback;
 *        elementor_theme_do_location returns true.
 *
 *   3. No theme builder header at all
 *      → We render a full-fidelity Gold header inline:
 *        topbar (location/phone/email + lang switcher) + sticky bar
 *        (logo + mega-menu walker + icon buttons) + megabar (sub-cat strip).
 *      Mega menu is built from any registered nav menu — picks the largest
 *      menu by item count if no `primary` location is set.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$elementor_header_active =
	did_action( 'elementor/loaded' ) &&
	function_exists( 'elementor_theme_do_location' ) &&
	elementor_theme_do_location( 'header' );

if ( ! $elementor_header_active ) :

	$primary_menu_id = 0;
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations['primary'] ) ) {
		$primary_menu_id = (int) $locations['primary'];
	} else {
		// Fall back to the largest registered menu.
		$menus = wp_get_nav_menus();
		$best  = null;
		foreach ( $menus as $m ) {
			$items = wp_get_nav_menu_items( $m->term_id );
			$count = is_array( $items ) ? count( $items ) : 0;
			if ( ! $best || $count > $best['count'] ) {
				$best = [ 'id' => $m->term_id, 'count' => $count ];
			}
		}
		$primary_menu_id = $best ? (int) $best['id'] : 0;
	}

	$menu_items = $primary_menu_id ? wp_get_nav_menu_items( $primary_menu_id ) : [];
	$tree = [];
	if ( ! empty( $menu_items ) && class_exists( 'LuwiPress_Gold_Widget_Mega_Menu' ) ) {
		$tree = LuwiPress_Gold_Widget_Mega_Menu::build_tree( $menu_items );
	}

	$site_url = esc_url( home_url( '/' ) );
	$contact_email = get_option( 'admin_email' );
?>

<div class="lwp-topbar">
	<div class="lwp-topbar-inner">
		<div class="lwp-topbar-l">
			<?php
			$loc   = get_theme_mod( 'luwipress_gold_topbar_location', '' );
			$phone = get_theme_mod( 'luwipress_gold_topbar_phone', '' );
			$email = get_theme_mod( 'luwipress_gold_topbar_email', $contact_email );
			if ( $loc !== '' ) {
				echo '<span>📍 ' . esc_html( $loc ) . '</span>';
			}
			if ( $phone !== '' ) {
				echo '<a href="' . esc_url( 'tel:' . preg_replace( '/\s+/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>';
			}
			if ( $email !== '' ) {
				echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
			}
			?>
		</div>
		<div class="lwp-topbar-r">
			<?php
			$promo = get_theme_mod( 'luwipress_gold_topbar_promo', __( 'Free DHL shipping over €450', 'luwipress-gold' ) );
			echo '<span>' . esc_html( $promo ) . '</span>';
			?>
		</div>
	</div>
</div>

<header class="lwp-site-header" role="banner">
	<div class="lwp-site-header-inner">
		<a class="lwp-site-header-logo" href="<?php echo $site_url; ?>" rel="home">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				echo '<span class="lwp-wordmark">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
			}
			?>
		</a>

		<?php if ( ! empty( $tree ) ) : ?>
		<nav class="lwp-mm" aria-label="<?php esc_attr_e( 'Primary', 'luwipress-gold' ); ?>">
			<ul class="lwp-mm-top">
				<?php
				foreach ( $tree as $node ) {
					$has_kids = ! empty( $node['children'] );
					$is_mega = LuwiPress_Gold_Widget_Mega_Menu::is_mega_candidate( $node, 4 );
					$cls = 'lwp-mm-item';
					if ( $has_kids ) $cls .= ' has-children';
					if ( $is_mega ) $cls .= ' is-mega';
					?>
					<li class="<?php echo esc_attr( $cls ); ?>">
						<a href="<?php echo esc_url( $node['url'] ); ?>"<?php echo $has_kids ? ' aria-haspopup="true"' : ''; ?>>
							<?php echo esc_html( $node['label'] ); ?>
							<?php if ( $has_kids ) echo '<span class="lwp-mm-arrow">›</span>'; ?>
						</a>
						<?php
						if ( $has_kids && $is_mega ) {
							$cols = LuwiPress_Gold_Widget_Mega_Menu::pick_columns( $node['children'], 'auto' );
							echo '<div class="lwp-mm-panel" role="menu"><div class="lwp-mm-panel-cols" style="grid-template-columns:repeat(' . count( $cols ) . ',1fr)">';
							foreach ( $cols as $col ) {
								echo '<div class="lwp-mm-col">';
								foreach ( $col as $entry ) {
									printf(
										'<h5 class="lwp-mm-col-head"><a href="%s">%s</a></h5>',
										esc_url( $entry['url'] ),
										esc_html( $entry['label'] )
									);
									if ( ! empty( $entry['children'] ) ) {
										echo '<ul>';
										foreach ( $entry['children'] as $c ) {
											$count = LuwiPress_Gold_Widget_Mega_Menu::resolve_item_count( $c );
											printf(
												'<li><a href="%s">%s%s</a></li>',
												esc_url( $c['url'] ),
												esc_html( $c['label'] ),
												$count !== '' ? '<span>' . esc_html( $count ) . '</span>' : ''
											);
										}
										echo '</ul>';
									}
								}
								echo '</div>';
							}
							echo '</div></div>';
						} elseif ( $has_kids ) {
							echo '<ul class="lwp-mm-dropdown">';
							foreach ( $node['children'] as $c ) {
								$count = LuwiPress_Gold_Widget_Mega_Menu::resolve_item_count( $c );
								printf(
									'<li><a href="%s">%s%s</a></li>',
									esc_url( $c['url'] ),
									esc_html( $c['label'] ),
									$count !== '' ? '<span class="lwp-mm-count">' . esc_html( $count ) . '</span>' : ''
								);
							}
							echo '</ul>';
						}
						?>
					</li>
				<?php } ?>
			</ul>
		</nav>
		<?php endif; ?>

		<div class="lwp-site-header-actions">
			<a href="<?php echo esc_url( get_search_link() ?: $site_url . '?s=' ); ?>" class="lwp-icon-btn" aria-label="<?php esc_attr_e( 'Search', 'luwipress-gold' ); ?>">⌕</a>
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="lwp-icon-btn" aria-label="<?php esc_attr_e( 'Account', 'luwipress-gold' ); ?>">◯</a>
				<?php $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
				<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="lwp-icon-btn lwp-cart-btn" aria-label="<?php esc_attr_e( 'Cart', 'luwipress-gold' ); ?>">
					▣<?php if ( $cart_count > 0 ) : ?><span class="lwp-cart-badge"><?php echo (int) $cart_count; ?></span><?php endif; ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<?php
// Megabar — sub-category strip (auto from top WC sub-cats).
if ( taxonomy_exists( 'product_cat' ) ) :
	$top_subs = get_terms( [
		'taxonomy'       => 'product_cat',
		'parent__not_in' => [ 0 ],
		'orderby'        => 'count',
		'order'          => 'DESC',
		'hide_empty'     => true,
		'number'         => 12,
	] );
	if ( ! is_wp_error( $top_subs ) && ! empty( $top_subs ) ) :
?>
<div class="lwp-megabar">
	<div class="lwp-megabar-inner">
		<?php
		$last = count( $top_subs ) - 1;
		foreach ( $top_subs as $i => $t ) {
			printf(
				'<a href="%s">%s</a>',
				esc_url( get_term_link( $t ) ),
				esc_html( $t->name )
			);
			if ( $i < $last ) echo '<span class="lwp-megabar-sep">·</span>';
		}
		?>
	</div>
</div>
<?php
	endif;
endif;
?>

<?php endif; // !elementor header ?>
