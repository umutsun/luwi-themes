<?php
/**
 * LuwiPress Emerald — searchform.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form role="search" method="get" class="emerald-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="emerald-s" class="screen-reader-text"><?php esc_html_e( 'Search for:', 'luwipress-emerald' ); ?></label>
	<input type="search" id="emerald-s" name="s" placeholder="<?php esc_attr_e( 'Search the site…', 'luwipress-emerald' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
	<button class="emerald-btn emerald-btn--primary" type="submit">
		<svg class="emerald-i emerald-i--sm" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
		<?php esc_html_e( 'Search', 'luwipress-emerald' ); ?>
	</button>
</form>
