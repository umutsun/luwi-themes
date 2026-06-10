<?php
/**
 * Native search results — Sapphire search hero + WordPress results as
 * `.sresult` rows. Reached via the `?s=` query (the search form posts here).
 *
 * @package luwipress-sapphire
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$sapphire_q = get_search_query();
get_template_part( 'template-parts/sapphire', 'search-hero', array( 'q' => $sapphire_q ) );

global $wp_query;
sapphire_render_search_results( $wp_query, $sapphire_q );

get_footer();
