<?php
/**
 * Mega menu — admin UI for the per-item "Featured product" override.
 *
 * The widget at inc/widgets/lib/class-mega-menu.php already reads the meta
 * `_lwp_emerald_mega_featured` per top-level menu item and renders that
 * product/post in the panel's featured slot, falling back to the most-popular
 * descendant when empty. The piece that was missing: a UI to actually set
 * the override without dropping into phpMyAdmin.
 *
 * This file plugs into WP core's `wp_nav_menu_item_custom_fields` action
 * (introduced WP 5.4) so the input renders inside every menu item edit
 * panel on Appearance → Menus, and the matching `wp_update_nav_menu_item`
 * action persists the value.
 *
 * Pattern mirrors `inc/archive-parent-enrichment.php` term-meta UI for
 * `_lwp_emerald_archive_featured` so the operator's mental model is consistent
 * across the two surfaces (category Atelier Pick + mega menu Featured).
 *
 * Loaded unconditionally from functions.php — no Elementor dependency.
 *
 * @package luwipress-emerald
 * @since   1.7.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'luwipress_emerald_mega_featured_field' ) ) {
	/**
	 * Render the "Featured product (override)" + sibling fields inside the
	 * nav menu item edit panel. Fires on `wp_nav_menu_item_custom_fields`.
	 *
	 * @param int    $item_id  Menu item (post) ID.
	 * @param object $item     Menu item object.
	 * @param int    $depth    Menu depth — we only render at depth 0
	 *                         since the mega panel only attaches to top items.
	 * @param array  $args     wp_nav_menu_args.
	 * @param int    $current  Current menu ID.
	 */
	function luwipress_emerald_mega_featured_field( $item_id, $item, $depth = 0, $args = array(), $current = 0 ) {
		// Only top-level items host a mega panel. Hide field for nested items
		// so the menu admin doesn't get cluttered.
		if ( (int) $depth > 0 ) {
			return;
		}
		$current_pid = (int) get_post_meta( $item_id, '_lwp_emerald_mega_featured', true );
		$preview     = '';
		if ( $current_pid > 0 ) {
			$post = get_post( $current_pid );
			if ( $post ) {
				$preview = sprintf( '#%d · %s', $current_pid, mb_substr( $post->post_title, 0, 60 ) );
			} else {
				$preview = sprintf( '#%d · %s', $current_pid, esc_html__( 'post not found', 'luwipress-emerald' ) );
			}
		}
		?>
		<p class="field-lwp-emerald-mega-featured description description-wide">
			<label for="lwp-emerald-mega-featured-<?php echo (int) $item_id; ?>">
				<strong><?php esc_html_e( 'Mega menu — Featured product (override)', 'luwipress-emerald' ); ?></strong><br>
				<input type="number"
				       min="0"
				       id="lwp-emerald-mega-featured-<?php echo (int) $item_id; ?>"
				       name="lwp_emerald_mega_featured[<?php echo (int) $item_id; ?>]"
				       value="<?php echo $current_pid > 0 ? (int) $current_pid : ''; ?>"
				       class="widefat"
				       placeholder="<?php esc_attr_e( 'Product or post ID — leave empty for auto best-seller', 'luwipress-emerald' ); ?>" />
			</label>
			<?php if ( $preview ) : ?>
				<span class="description" style="display:block;margin-top:4px;color:#735c00;">
					<?php
					/* translators: %s: post title preview */
					printf( esc_html__( 'Currently featured: %s', 'luwipress-emerald' ), esc_html( $preview ) );
					?>
				</span>
			<?php else : ?>
				<span class="description" style="display:block;margin-top:4px;">
					<?php esc_html_e( 'Auto fallback: most-popular product matching this menu item\'s URL slug.', 'luwipress-emerald' ); ?>
				</span>
			<?php endif; ?>
		</p>
		<?php
	}
	add_action( 'wp_nav_menu_item_custom_fields', 'luwipress_emerald_mega_featured_field', 10, 5 );
}

if ( ! function_exists( 'luwipress_emerald_mega_featured_save' ) ) {
	/**
	 * Persist the per-item featured-product override when a menu item is saved.
	 * Fires on `wp_update_nav_menu_item`.
	 *
	 * @param int   $menu_id           Menu (term) ID — unused.
	 * @param int   $menu_item_db_id   Menu item post ID.
	 * @param array $menu_item_args    Sanitized menu item args — unused.
	 */
	function luwipress_emerald_mega_featured_save( $menu_id, $menu_item_db_id, $menu_item_args = array() ) {
		unset( $menu_id, $menu_item_args );
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		// The custom field arrives as $_POST['lwp_emerald_mega_featured'][<item_id>]
		// because every menu item posts under a single namespaced array.
		$bag = isset( $_POST['lwp_emerald_mega_featured'] ) && is_array( $_POST['lwp_emerald_mega_featured'] )
			? $_POST['lwp_emerald_mega_featured']
			: array();
		if ( ! array_key_exists( $menu_item_db_id, $bag ) ) {
			return; // Item not in this submit — leave whatever's there.
		}
		$pid = (int) $bag[ $menu_item_db_id ];
		if ( $pid <= 0 ) {
			delete_post_meta( $menu_item_db_id, '_lwp_emerald_mega_featured' );
			return;
		}
		// Validate: must be a publish-status post/product.
		$post = get_post( $pid );
		if ( ! $post || $post->post_status !== 'publish' ) {
			delete_post_meta( $menu_item_db_id, '_lwp_emerald_mega_featured' );
			return;
		}
		update_post_meta( $menu_item_db_id, '_lwp_emerald_mega_featured', $pid );
	}
	add_action( 'wp_update_nav_menu_item', 'luwipress_emerald_mega_featured_save', 10, 3 );
}
