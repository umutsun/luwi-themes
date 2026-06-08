<?php
/**
 * Native search results — Onyx search hero + WordPress results as
 * `.sresult` rows. Reached via the `?s=` query (the search form posts here).
 *
 * @package luwipress-onyx
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$onyx_q = get_search_query();
get_template_part( 'template-parts/onyx', 'search-hero', array( 'q' => $onyx_q ) );

global $wp_query;
onyx_render_search_results( $wp_query, $onyx_q );

get_footer();
