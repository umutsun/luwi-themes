<?php
/**
 * Template Name: Onyx — Search
 *
 * Standalone search landing (the header search icon points here). Renders
 * the Onyx search hero + a real GET search form; submitting hits the native
 * search.php with WordPress results. Below the form it previews recent
 * content as `.sresult` rows.
 *
 * @package luwipress-onyx
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$onyx_pid = get_queried_object_id();
if ( $onyx_pid && get_post_meta( $onyx_pid, '_elementor_edit_mode', true ) && get_post_meta( $onyx_pid, '_elementor_data', true ) ) {
	get_header();
	while ( have_posts() ) { the_post(); the_content(); }
	get_footer();
	return;
}

get_header();

get_template_part( 'template-parts/onyx', 'search-hero', array( 'q' => '' ) );

$onyx_recent = new WP_Query( array(
	'post_type'           => array( 'post', 'page' ),
	'posts_per_page'      => 9,
	'post_status'         => 'publish',
	'ignore_sticky_posts' => true,
	'post__not_in'        => array( (int) $onyx_pid ),
) );
onyx_render_search_results( $onyx_recent, '' );

get_footer();
